<?php

namespace App\Http\Classes\modules\modulereport\housegem;

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

class cm
{
  private $modulename = "Sales Return";
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
    $fields = ['radioprint', 'radiohgccompany', 'prepared', 'received', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'received.label', 'Received & Reviewed by');

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        't0' as radiohgccompany,
        '' as prepared,
        '' as approved,
        '' as received
        "
    );
  }

  public function report_default_query($trno)
  {

    $query = "select head.vattype, head.tax, client.tel, stock.rem as remarks, m.model_name as model,item.sizeid,
    date(head.dateid) as reportdate, head.docno, client.client, client.clientname, head.address,
    head.terms, head.rem,head.yourref,head.ourref,
    item.barcode,item.brand,
    sj.yourref as pono,sjag.clientname as sjagname,
    item.itemname, stock.rrqty as qty, stock.uom, stock.amt as amt, stock.disc, stock.ext,
    right(stock.ref,5) as drno,ag.clientname as agname,
    ag.client as agcode,wh.client as whcode,wh.clientname as whname, stock.line,stock.sortline,head.rem,
    left(head.returndate,10) as returndate, right(head.returndate,8) as returntime,head.returndate as datereturn,left(sj.deldate,10) as deldate
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.clientid=stock.whid
    left join item on item.itemid=stock.itemid
    left join model_masterfile as m on m.model_id=item.model
    left join glhead as sj on sj.trno=stock.refx
    left join client as sjag on sjag.clientid=sj.agentid
    where head.doc='cm' and md5(head.trno)='" . md5($trno) . "'
    union all
    select head.vattype, head.tax, client.tel, stock.rem as remarks, m.model_name as model,item.sizeid,
    date(head.dateid) as reportdate, head.docno, client.client, client.clientname, head.address,
    head.terms, head.rem,head.yourref,head.ourref,
    item.barcode,item.brand,
    sj.yourref as pono,sjag.clientname as sjagname,
    item.itemname, stock.rrqty as qty, stock.uom, stock.amt as amt, stock.disc, stock.ext,
    right(stock.ref,5) as drno,ag.clientname as agname,
    ag.client as agcode,wh.client as whcode,wh.clientname as whname, stock.line,stock.sortline,head.rem,
    left(head.returndate,10) as returndate, right(head.returndate,8) as returntime,head.returndate as datereturn,
    left(sj.deldate,10) as deldate
    from glhead as head left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join model_masterfile as m on m.model_id=item.model
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=stock.whid
    left join glhead as sj on sj.trno=stock.refx
    left join client as sjag on sjag.clientid=sj.agentid
    where head.doc='cm' and md5(head.trno)='" . md5($trno) . "'
    order by sortline,line ";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($config, $data)
  {
    $radiohgccompany = $config['params']['dataparams']['radiohgccompany'];

    switch ($radiohgccompany) {
      case 't0':
        return $this->default_cm_PDF($config, $data);
        break;
      case 't1':
        return $this->t4triumph_cm_PDF($config, $data);
        break;
      case 't2':
        return $this->taitafalcon_cm_PDF($config, $data);
        break;
      case 't3':
        return $this->templewin_cm_PDF($config, $data);
        break;
    }
  }

  private function report_default_header($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '70', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '500', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DISC', '50', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    return $str;
  }

  public function default_cm_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->report_default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $ext = number_format($data[$i]['ext'], $decimal);
      $ext = $ext < 0 ? '-' : $ext;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $this->companysetup->getdecimal('price', $params['params'])), '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ext, '125', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        // ------------ HEADER ----------------
        $str .= $this->report_default_header($params, $data);

        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    } // end for

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '150', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function default_cm_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(50, 50);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, '', 50);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);

    $date = $data[0]['reportdate'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '385', '95');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '35',  '123');


    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($data[0]['whname']) ? $data[0]['whname'] : '', '', 'L', false, 1, '35', '170');
  }

  public function default_cm_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 13;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_cm_header_PDF($params, $data);
    PDF::SetFont($font, '', 30);

    PDF::MultiCell(700, 0, '', '', '', false, 1); //, '', '172'

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $item = $this->reporter->fixcolumn([$data[$i]['itemname']], '95', 0);
        $arritem = (str_split($data[$i]['itemname'], 35));
        $maxrow = 1;
        $countarr = count($arritem);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          $item = isset($data[$i]['itemname']) ? $data[$i]['itemname'] : '';
          $qty = isset($data[$i]['qty']) ? number_format($data[$i]['qty'], 0) : '0';
          $uom = isset($data[$i]['uom']) ? $data[$i]['uom'] : '';
          $amt = isset($data[$i]['amt']) ? number_format($data[$i]['amt'], 2) : '0';
          $disc = isset($data[$i]['disc']) ? $data[$i]['disc'] : '';
          $ext = isset($data[$i]['ext']) ? number_format($data[$i]['ext'], 2) : '0';
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
              $item = isset($arritem[$r]) ? $arritem[$r] : '';

              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
              PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '123', '', false, 1);
              PDF::MultiCell(90, 20, $qty . ' ' . $uom, '', 'R', false, 0, '283', '', false, 1);
              PDF::MultiCell(90, 20, $amt, '', 'R', false, 0, '340', '', false, 1);
              PDF::MultiCell(90, 20, $ext, '', 'R', false, 1, '430', '', false, 1);
            }
          }
        }
        $totalext += $data[$i]['ext'];
      }
    }
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);

    PDF::MultiCell(355, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Total', '', 'R', false, 0, '320', '438');
    PDF::MultiCell(75, 0, number_format($totalext, 2), '', 'R', false, 1, '447', '438');

    $this->cm_footer_pdf($params, $data);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_cm_footer_pdf($rcount, $maxpage, $params, $data, $totalext, $decimalprice, $font, $fontbold, $fontsize)
  {
    for ($a = $rcount; $a < ($maxpage + 11); $a++) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(760, 0, '', '', 'L', false, 1);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(760, 0, '', '', 'L', false, 1);
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(233, 0, ' ' . $params['params']['dataparams']['prepared'], '', 'C', false, 0);
    PDF::MultiCell(234, 0, ' ' . $params['params']['dataparams']['received'], '', 'C', false, 0);
    PDF::MultiCell(233, 0, ' ' . $params['params']['dataparams']['approved'], '', 'C', false, 1);
  }

  public function cm_footer_pdf($params, $data)
  {
    $font = '';
    $fontbold = '';
    $fontsize9 = '11';
    $prep = $params['params']['dataparams']['prepared'];
    $rec = $params['params']['dataparams']['received'];
    $app = $params['params']['dataparams']['approved'];


    $ag = '';
    $pono = '';
    $drno = '';
    $drnox = '';
    $ponox = '';
    $arag = [];
    $arrdr = [];
    $arrpo = [];

    for ($i = 0; $i < count($data); $i++) {

      if ($data[$i]['sjagname'] != '') {
        if (!in_array($data[0]['sjagname'], $arag)) {
          array_push($arag, $data[0]['sjagname']);
        }
      }


      if ($data[$i]['pono'] != '') {
        if (!in_array($data[$i]['pono'], $arrpo)) {
          array_push($arrpo, $data[$i]['pono']);
        }
      }

      if ($data[$i]['drno'] != '') {
        if (!in_array($data[$i]['drno'], $arrdr)) {
          array_push($arrdr, $data[$i]['drno']);
        }
      }
    }

    foreach ($arag as $key) {
      if ($ag == '') {
        $ag = $key;
      } else {
        $ag .= ", " . $key;
      }
    }

    foreach ($arrdr as $key) {
      if ($drno == '') {
        $drno = $key;
      } else {
        $drno .= ", " . $key;
      }
    }

    foreach ($arrpo as $key) {
      if ($drno == '') {
        $pono = $key;
      } else {
        $pono .= ", " . $key;
      }
    }

    $arrpono = $this->reporter->fixcolumn([substr($pono, 1), substr($drno, 1)], '30');
    $maxrow = 1;
    $maxrow = count($arrpono);


    $tarb = 631;
    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', $fontsize9);
      PDF::MultiCell(175, 15, isset($arrpono[$r]) ? $arrpono[$r] : '', '', 'L', false, 0, '150', $tarb);
      $tarb += 15;
    }

    PDF::SetFont($fontbold, '', $fontsize9);

    PDF::MultiCell(175, 15, $ag, '', 'L', false, 0, '397', '622');


    PDF::MultiCell(175, 15, $prep, '', 'L', false, 0, '40', '710');
    PDF::MultiCell(175, 15, $rec, '', 'L', false, 0, '200', '');
    PDF::MultiCell(175, 15, $app, '', 'L', false, 0, '375', '');
  }

  public function t4triumph_cm_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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

    // PDF::SetFont($font, '', 50);
    // PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);

    $date = $data[0]['reportdate'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '360', '72');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '15',  '95');

    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['whname']) ? $data[0]['whname'] : '', '', 'L', false, 1, '15', '150');

    PDF::MultiCell(100, 0, isset($data[0]['returndate']) ? $data[0]['returndate'] : '', '', 'L', false, 1, '280', '150');
    PDF::MultiCell(100, 0, isset($data[0]['returntime']) ? $data[0]['returntime'] : '', '', 'L', false, 1, '385', '150');
  }

  public function t4triumph_cm_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 13;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->t4triumph_cm_header_PDF($config, $data);
    PDF::SetFont($font, '', 31);
    PDF::MultiCell(700, 0, '', '', '', false, 1, '', '172');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $item = $this->reporter->fixcolumn([$data[$i]['itemname']], '95', 0);
        $arritem = (str_split($data[$i]['itemname'], 30));
        $maxrow = 1;
        $countarr = count($arritem);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
              $item = isset($arritem[$r]) ? $arritem[$r] : '';

              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
              PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '106', '', false, 1);
              PDF::MultiCell(90, 20, $qty . ' ' . $uom, '', 'R', false, 0, '239', '', false, 1);
              PDF::MultiCell(90, 20, $amt, '', 'R', false, 0, '294', '', false, 1);
              PDF::MultiCell(90, 20, $ext, '', 'R', false, 1, '385', '', false, 1);
            }
          }
        }
        $totalext += $data[$i]['ext'];
      }
    }
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);

    // public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false, $ln = 1, $x = '', $y = '', $reseth = true, $stretch = 0, $ishtml = false, $autopadding = true, $maxh = 0, $valign = 'T', $fitcell = false)

    // PDF::MultiCell(280, 30, '', '', 'L', false, 0, '', '', true, 1);
    $arrnotes = $this->reporter->fixcolumn([$data[0]['rem']], '55', 0);
    $max = count($arrnotes);
    $op = 0;
    for ($i = 0; $i < $max; $i++) {

      PDF::MultiCell(300, 0, isset($arrnotes[$i]) ? $arrnotes[$i] : '', '', 'L', false, 1, '8', 417 + $op, false, 0, false, false, 0, 'T', false);
      $op += 20;
    }


    PDF::MultiCell(355, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Total', '', 'L', false, 0, '350', '457');
    PDF::MultiCell(75, 0, number_format($totalext, 2), '', 'R', false, 1, '400', '457');


    $this->t4triumph_cm_footer_pdf($config, $data, $totalext, $font, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function t4triumph_cm_footer_pdf($config, $data, $totalext, $font, $fontsize)
  {
    $font = '';
    $fontbold = '';
    $fontsize9 = '11';
    $prep = $config['params']['dataparams']['prepared'];
    $rec = $config['params']['dataparams']['received'];
    $app = $config['params']['dataparams']['approved'];

    $ag = $data[0]['agname'];
    $drno = '';
    $pono = '';
    $deldate = '';

    for ($k = 0; $k < count($data); $k++) {
      if ($data[$k]['pono'] != '') {
        $pono = $data[$k]['pono'];
      }
      if ($data[$k]['drno'] != '') {
        $drno = $data[$k]['drno'];
      }
      if ($data[$k]['deldate'] != '') {
        $deldate = $data[$k]['deldate'];
      }
    }

    PDF::SetFont($fontbold, '', $fontsize9);

    PDF::MultiCell(175, 15, $deldate, '', 'L', false, 0, '150', '623');
    PDF::MultiCell(175, 15, $pono, '', 'L', false, 0, '150', '650');
    PDF::MultiCell(175, 15, $drno, '', 'L', false, 0, '150', '672');


    PDF::MultiCell(175, 15, $ag, '', 'L', false, 0, '365', '623');
    PDF::MultiCell(175, 15, '', '', 'L', false, 0, '365', '643');

    PDF::MultiCell(175, 15, $prep, '', 'L', false, 0, '15', '725');
    PDF::MultiCell(175, 15, $rec, '', 'L', false, 0, '165', '');
    PDF::MultiCell(175, 15, $app, '', 'L', false, 0, '335', '');
  }

  public function taitafalcon_cm_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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

    $date = $data[0]['reportdate'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '312', '120');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '23',  '120');


    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['whname']) ? $data[0]['whname'] : '', '', 'L', false, 1, '23', '180');
    PDF::MultiCell(100, 0, isset($data[0]['returndate']) ? $data[0]['returndate'] : '', '', 'L', false, 1, '306', '180');
    PDF::MultiCell(100, 0, isset($data[0]['returntime']) ? $data[0]['returntime'] : '', '', 'L', false, 1, '412', '180');
  }

  public function taitafalcon_cm_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 13;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->taitafalcon_cm_header_PDF($config, $data);
    PDF::SetFont($font, '', 30);
    PDF::MultiCell(700, 0, '', '', '', false, 1, '');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $item = $this->reporter->fixcolumn([$data[$i]['itemname']], '95', 0);
        $arritem = (str_split($data[$i]['itemname'], 30));
        $maxrow = 1;
        $countarr = count($arritem);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
              $item = isset($arritem[$r]) ? $arritem[$r] : '';

              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
              PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '130', '', false, 1);
              PDF::MultiCell(90, 20, $qty . ' ' . $uom, '', 'R', false, 0, '265', '', false, 1);
              PDF::MultiCell(90, 20, $amt, '', 'R', false, 0, '320', '', false, 1);
              PDF::MultiCell(90, 20, $ext, '', 'R', false, 1, '405', '', false, 1);
            }
          }
        }
        $totalext += $data[$i]['ext'];
      }
    }
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);

    $arrnotes = $this->reporter->fixcolumn([$data[0]['rem']], '55', 0);
    $max = count($arrnotes);
    $op = 0;
    for ($i = 0; $i < $max; $i++) {

      PDF::MultiCell(300, 0, isset($arrnotes[$i]) ? $arrnotes[$i] : '', '', 'L', false, 1, '25', 452 + $op, false, 0, false, false, 0, 'T', false);
      $op += 15;
    }

    PDF::MultiCell(355, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Total', '', 'L', false, 0, '365', '481');
    PDF::MultiCell(75, 0, number_format($totalext, 2), '', 'R', false, 1, '420', '481');

    $this->taitafalcon_cm_footer_pdf($config, $data, $totalext, $font, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function taitafalcon_cm_footer_pdf($config, $data, $totalext, $font, $fontsize)
  {
    $font = '';
    $fontbold = '';
    $fontsize9 = '11';
    $prep = $config['params']['dataparams']['prepared'];
    $rec = $config['params']['dataparams']['received'];
    $app = $config['params']['dataparams']['approved'];


    $ag = $data[0]['agname'];
    $pono = '';
    $drno = '';
    $deldate = '';

    for ($k = 0; $k < count($data); $k++) {
      if ($data[$k]['pono'] != '') {
        $pono = $data[$k]['pono'];
      }
      if ($data[$k]['drno'] != '') {
        $drno = $data[$k]['drno'];
      }
      if ($data[$k]['deldate'] != '') {
        $deldate = $data[$k]['deldate'];
      }
    }

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(175, 15, $deldate, '', 'L', false, 0, '160', '612');
    PDF::MultiCell(175, 15, $pono, '', 'L', false, 0, '160', '632');
    PDF::MultiCell(175, 15, $drno, '', 'L', false, 0, '160', '657');


    PDF::MultiCell(175, 15, $ag, '', 'L', false, 0, '390', '612');
    PDF::MultiCell(175, 15, '', '', 'L', false, 0, '390', '632');

    PDF::MultiCell(175, 15, $prep, '', 'L', false, 0, '35', '735');
    PDF::MultiCell(175, 15, $rec, '', 'L', false, 0, '205', '');
    PDF::MultiCell(175, 15, $app, '', 'L', false, 0, '375', '');
  }

  public function templewin_cm_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 12;
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

    $date = $data[0]['reportdate'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '305', '112');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '15',  '102');


    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['whname']) ? $data[0]['whname'] : '', '', 'L', false, 1, '15', '167');
    PDF::MultiCell(100, 0, isset($data[0]['returndate']) ? $data[0]['returndate'] : '', '', 'L', false, 1, '306', '167');

    $qry = "select sum(ext) from (
              select s.ext from lahead as h left join lastock as s on s.trno=h.trno
              where h.doc='CM' and h.trno = " . $config['params']['dataid'] . "
              union all
              select s.ext from glhead as h left join glstock as s on s.trno=h.trno
              where h.doc='CM' and h.trno = " . $config['params']['dataid'] . ") as a";
    $totalamt = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    // number_format($totalamt[0]['sum(ext)'], 2)
    PDF::MultiCell(100, 0, isset($data[0]['returntime']) ? $data[0]['returntime'] : '', '', 'L', false, 1, '412', '167');
  }

  public function templewin_cm_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 13;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->templewin_cm_header_PDF($config, $data);
    PDF::SetFont($font, '', 34);
    PDF::MultiCell(700, 0, '', '', '', false, 1, '', '172');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $item = $this->reporter->fixcolumn([$data[$i]['itemname']], '95', 0);
        $arritem = (str_split($data[$i]['itemname'], 30));
        $maxrow = 1;
        $countarr = count($arritem);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
              $item = isset($arritem[$r]) ? $arritem[$r] : '';

              PDF::SetFont($font, '', $fontsize);
              PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
              PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '125', '', false, 1);
              PDF::MultiCell(90, 20, $qty . ' ' . $uom, '', 'R', false, 0, '265', '', false, 1);
              PDF::MultiCell(90, 20, $amt, '', 'R', false, 0, '320', '', false, 1);
              PDF::MultiCell(90, 20, $ext, '', 'R', false, 1, '415', '', false, 1);
            }
          }
        }
        $totalext += $data[$i]['ext'];
      }
    }
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);
    PDF::MultiCell(700, 0, '', '', '', false, 1);

    $arrnotes = $this->reporter->fixcolumn([$data[0]['rem']], '55', 0);
    $max = count($arrnotes);
    $op = 0;
    for ($i = 0; $i < $max; $i++) {

      PDF::MultiCell(300, 0, isset($arrnotes[$i]) ? $arrnotes[$i] : '', '', 'L', false, 1, '15', 416 + $op, false, 0, false, false, 0, 'T', false);
      $op += 20;
    }


    PDF::MultiCell(355, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Total', '', 'L', false, 0, '365', '460');
    PDF::MultiCell(75, 0, number_format($totalext, 2), '', 'R', false, 1, '430', '460');

    $this->templewin_cm_footer_pdf($config, $data, $totalext, $font, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function templewin_cm_footer_pdf($config, $data, $totalext, $font, $fontsize)
  {
    $font = '';
    $fontbold = '';
    $fontsize9 = '11';
    $prep = $config['params']['dataparams']['prepared'];
    $rec = $config['params']['dataparams']['received'];
    $app = $config['params']['dataparams']['approved'];

    $ag = $data[0]['agname'];
    $pono = '';
    $drno = '';
    $deldate = '';

    for ($k = 0; $k < count($data); $k++) {
      if ($data[$k]['pono'] != '') {
        $pono = $data[$k]['pono'];
      }
      if ($data[$k]['drno'] != '') {
        $drno = $data[$k]['drno'];
      }
      if ($data[$k]['deldate'] != '') {
        $deldate = $data[$k]['deldate'];
      }
    }

    PDF::SetFont($fontbold, '', $fontsize9);
    PDF::MultiCell(175, 15, $deldate, '', 'L', false, 0, '155', '623');
    PDF::MultiCell(175, 15, $pono, '', 'L', false, 0, '155', '642');
    PDF::MultiCell(175, 15, $drno, '', 'L', false, 0, '155', '661');


    PDF::MultiCell(175, 15, $ag, '', 'L', false, 0, '345', '642');
    PDF::MultiCell(175, 15, '', '', 'L', false, 0, '345', '661');

    PDF::MultiCell(175, 15, $prep, '', 'L', false, 0, '25', '728');
    PDF::MultiCell(175, 15, $rec, '', 'L', false, 0, '195', '');
    PDF::MultiCell(175, 15, $app, '', 'L', false, 0, '377', '');
  }
}
