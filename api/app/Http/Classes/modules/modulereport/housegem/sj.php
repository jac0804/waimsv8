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

class sj
{
  private $modulename = "Sales Journal";
  private $coreFunctions;
  private $fieldClass;
  private $companysetup;
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

    $center = $config['params']['center'];
    $result = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", [$center]);
    $fields = ($result == 'TAITAFALCN')
      ? ['radioprint', 'radiohgccompany', 'prepared', 'received', 'checked', 'print']
      : ['radioprint', 'radiohgccompany', 'prepared', 'delivered', 'approved', 'checked', 'print'];


    $col1 = $this->fieldClass->create($fields);

    if ($result == 'TAITAFALCN') {
      data_set($col1, 'radiohgccompany.options', [
        ['label' => 'HOUSEGEM', 'value' => 't0', 'color' => 'green'],
        ['label' => 'T4TRIUMPH', 'value' => 't1', 'color' => 'green'],
        ['label' => 'TAITAFALCON', 'value' => 't2', 'color' => 'green'],
        ['label' => 'TEMPLEWIN', 'value' => 't3', 'color' => 'green'],
        ['label' => 'SVC ONLINE', 'value' => 't4', 'color' => 'green'],
        ['label' => 'KGM -Metro Manila', 'value' => 't5', 'color' => 'green']
      ]);
    }

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
        '' as delivered,
        '' as approved,
        '' as checked,
        '' as received
        "
    );
  }

  public function report_default_query($config)
  {

    $radiohgccompany = $config['params']['dataparams']['radiohgccompany'];

    $trno = $config['params']['dataid'];
    $query = "select stock.line, stock.sortline,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,head.due,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    left(item.itemname,30) as itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand, sohead.yourref as soref,client.bstyle,client.tel2 as mobile,
    wh.client as whcode, wh.clientname as whname, ((isamt * isqty)-ext) as discount,head.crref,head.terms,left(head.due,10) as due ,(select group_concat(distinct concat(left(s.ref,3),CAST(right(s.ref,12) AS UNSIGNED)) separator '/') as ref from lastock as s where s.trno = head.trno and s.ref <> '') as soreference
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
     left join sohead on sohead.trno=stock.trno
    where head.doc='sj' and head.trno='$trno'
    UNION ALL
    select stock.line,stock.sortline,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,head.due,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    left(item.itemname,30) as itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand, sohead.yourref as soref,client.bstyle,client.tel2 as mobile,
    wh.client as whcode, wh.clientname as whname ,((isamt * isqty)-ext) as discount,head.crref,head.terms,left(head.due,10) as due , (select group_concat(distinct concat(left(s.ref,3),CAST(right(s.ref,12) AS UNSIGNED)) separator '/') as ref
    from glstock as s where s.trno = head.trno  and s.ref <> '') as soreference
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    left join sohead on sohead.trno=stock.trno
    where head.doc='sj' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config, $data)
  {

    $center = $config['params']['center'];
    $result = $this->coreFunctions->getfieldvalue("center", "shortname", "code=?", [$center]);
    $radiohgccompany = $config['params']['dataparams']['radiohgccompany'];

    switch ($radiohgccompany) {
      case 't0':
        return $this->default_sj_PDF($config, $data);
        break;
      case 't1':
        return $this->t4triumph_sj_PDF($config, $data);
        break;
      case 't2':
        return $this->taitafalcon_sj_PDF($config, $data);
        break;
      case 't3':
        return $this->templewin_sj_PDF($config, $data);
        break;
    }
    if ($result == 'TAITAFALCN') {
      switch ($radiohgccompany) {
        case 't4':
          return $this->svc_online_sj_PDF($config, $data);
        case 't5':
          return $this->kgm_metromanila_sj_PDF($config, $data);
      }
    }
  }

  public function t4triumph_sj_header_PDF($params, $data)
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
    PDF::SetMargins(20, 20);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(510, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, date('m-d-Y', strtotime($data[0]['dateid'])), '', 'L', false, 0, '296', '80');
    PDF::MultiCell(150, 0, isset($data[0]['agname']) ? $data[0]['agname'] : '', '', 'L', false, 1, '395', '80');

    $address = '';
    if (isset($data[0]['shipto'])) {
      if ($data[0]['shipto'] != '') {
        $address = $data[0]['shipto'];
      } else {
        goto defaultaddrhere;
      }
    } else {
      defaultaddrhere:
      $address = isset($data[0]['address']) ? $data[0]['address'] : '';
    }

    PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(125, 0, '', '', 'L', false, 0);
    PDF::MultiCell(760, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '123', '110');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 0, $address, '', 'L', false, 1, '123', '130');
    PDF::MultiCell(105, 0, isset($data[0]['soreference']) ? $data[0]['soreference'] : '', '', 'L', false, 0, '123', '158');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(350, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 0, '345', '158');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
  }

  public function t4triumph_sj_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 20;
    $totalext = 0;
    $totalamt = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->t4triumph_sj_header_PDF($config, $data);

    PDF::SetFont($font, '', 15);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);


    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);
        $maxrow = 1;
        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            $rcount++;
            if ($r == 0) {

              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];

              $ext = number_format($data[$i]['ext'], 2);
              $total = $data[$i]['qty'] * $data[$i]['amt'];
            } else {
              $qty = '';
              $uom = '';
              $amt = '';
              $disc = '';
              $ext = '';
              $total = '';
            }
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(760, 21, '', '', 'L', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(125, 0, $qty, '', 'R', false, 0, '-65');
            PDF::MultiCell(75, 0, $uom, '', 'L', false, 0, '90');
            PDF::MultiCell(260, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '130');
            PDF::MultiCell(150, 0, $amt, '', 'R', false, 0, '240');
            PDF::MultiCell(130, 0, number_format($total, 2), '', 'R', false, 0, '350');
          }
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['discount'];
      }
    }
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 23, '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 15, '**Nothing Follows**', '', 'C');

    $this->t4triumph_sj_footer_pdf($rcount, $page, $config, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function t4triumph_sj_footer_pdf($rcount, $maxpage, $params, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize)
  {

    $qry  = "select name as value from center where code = 001";
    $company = $this->coreFunctions->datareader($qry);

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(200, 15, 'RO #: ' . $data[0]['crref'], '', 'L', false, 1, '25', '441');
    PDF::MultiCell(200, 15, 'STRICTLY: ' . $data[0]['terms'], '', 'L', false, 1, '25', '462');
    PDF::MultiCell(200, 15, 'DUE DATE: ' . $data[0]['due'], '', 'L', false, 1, '25', '483');
    PDF::MultiCell(300, 15, $company, '', 'L', false, 1, '25', '504');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, number_format($totalext, 2) == 0.00 ? '' : number_format($totalext, 2), '', 'R', false, 1, '330', '501');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, ' ' . $params['params']['dataparams']['prepared'], '', 'L', false, 0, '13', '678');
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['delivered'], '', 'L', false, 0, '141', '');
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['approved'], '', 'L', false, 1, '365', '');

    PDF::MultiCell(200, 0, ' ' . $params['params']['dataparams']['checked'], '', 'L', false, 1, '13', '740');
  }

  public function taitafalcon_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
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

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(510, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, date('m-d-Y', strtotime($data[0]['dateid'])), '', 'L', false, 0, '305', '115');
    PDF::MultiCell(150, 0, isset($data[0]['agname']) ? $data[0]['agname'] : '', '', 'L', false, 1, '405', '115');

    $address = '';
    if (isset($data[0]['shipto'])) {
      if ($data[0]['shipto'] != '') {
        $address = $data[0]['shipto'];
      } else {
        goto defaultaddrhere;
      }
    } else {
      defaultaddrhere:
      $address = isset($data[0]['address']) ? $data[0]['address'] : '';
    }

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(250, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '40', '115');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(250, 0, $address, '', 'L', false, 1, '40', '155');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(115, 0, isset($data[0]['soreference']) ? $data[0]['soreference'] : '', '', 'L', false, 0, '300', '155');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(120, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 0, '420', '155');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
  }

  public function taitafalcon_sj_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 20;
    $totalext = 0;
    $totalamt = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->taitafalcon_sj_header_PDF($config, $data);

    PDF::SetFont($font, '', 15);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);


    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);
        $maxrow = 1;
        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            $rcount++;
            if ($r == 0) {

              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];

              $ext = number_format($data[$i]['ext'], 2);
              $total = $data[$i]['qty'] * $data[$i]['amt'];
            } else {
              $qty = '';
              $uom = '';
              $amt = '';
              $disc = '';
              $ext = '';
              $total = '';
            }
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(760, 21, '', '', 'L', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(125, 0, $qty, '', 'R', false, 0, '-55');
            PDF::MultiCell(75, 0, $uom, '', 'L', false, 0, '90');
            PDF::MultiCell(260, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '136');
            PDF::MultiCell(150, 0, $amt, '', 'R', false, 0, '258');
            PDF::MultiCell(130, 0, number_format($total, 2), '', 'R', false, 0, '362');
          }
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['discount'];
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 21, '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 15, '**Nothing Follows**', '', 'C');

    $this->taitafalcon_sj_footer_pdf($rcount, $page, $config, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function taitafalcon_sj_footer_pdf($rcount, $maxpage, $params, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize)
  {
    $qry  = "select name as value from center where code = 001";
    $company = $this->coreFunctions->datareader($qry);

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(200, 15, 'RO #: ' . $data[0]['crref'], '', 'L', false, 1, '40', '420');
    PDF::MultiCell(200, 15, 'STRICTLY: ' . $data[0]['terms'], '', 'L', false, 1, '40', '440');
    PDF::MultiCell(200, 15, 'DUE DATE: ' . $data[0]['due'], '', 'L', false, 1, '40', '465');
    PDF::MultiCell(300, 15, $company, '', 'L', false, 1, '40', '485');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, number_format($totalext, 2) == 0.00 ? '' : number_format($totalext, 2), '', 'R', false, 1, '342', '485');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, ' ' . $params['params']['dataparams']['prepared'], '', 'L', false, 0, '27', '703');
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['delivered'], '', 'L', false, 0, '155', '');
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['approved'], '', 'L', false, 1, '379', '');

    PDF::MultiCell(200, 0, ' ' . $params['params']['dataparams']['checked'], '', 'L', false, 1, '27', '768');
  }

  public function templewin_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
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

    $address = '';
    if (isset($data[0]['shipto'])) {
      if ($data[0]['shipto'] != '') {
        $address = $data[0]['shipto'];
      } else {
        goto defaultaddrhere;
      }
    } else {
      defaultaddrhere:
      $address = isset($data[0]['address']) ? $data[0]['address'] : '';
    }

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(230, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '35', '91');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(230, 0, $address, '', 'L', false, 1, '270', '91');
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(115, 0, isset($data[0]['soreference']) ? $data[0]['soreference'] : '', '', 'L', false, 0, '35', '129');
    PDF::MultiCell(350, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 0, '160', '129');

    PDF::MultiCell(100, 0, date('m-d-Y', strtotime($data[0]['dateid'])), '', 'L', false, 0, '285', '129');
    PDF::MultiCell(150, 0, isset($data[0]['agname']) ? $data[0]['agname'] : '', '', 'L', false, 1, '390', '129');
  }

  public function templewin_sj_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 20;
    $totalext = 0;
    $totalamt = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "13";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->templewin_sj_header_PDF($config, $data);

    PDF::SetFont($font, '', 0);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);


    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);
        $maxrow = 1;
        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            $rcount++;
            if ($r == 0) {

              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];

              $ext = number_format($data[$i]['ext'], 2);
              $total = $data[$i]['qty'] * $data[$i]['amt'];
            } else {
              $qty = '';
              $uom = '';
              $amt = '';
              $disc = '';
              $ext = '';
              $total = '';
            }
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(760, 21, '', '', 'L', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(125, 0, $qty, '', 'R', false, 0, '-55');
            PDF::MultiCell(75, 0, $uom, '', 'L', false, 0, '104');
            PDF::MultiCell(260, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '140');
            PDF::MultiCell(150, 0, $amt, '', 'R', false, 0, '253');
            PDF::MultiCell(130, 0, number_format($total, 2), '', 'R', false, 0, '357');
          }
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['discount'];
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 21, '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(460, 15, '**Nothing Follows**', '', 'C');

    $this->templewin_sj_footer_pdf($rcount, $page, $config, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function templewin_sj_footer_pdf($rcount, $maxpage, $params, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize)
  {
    $qry  = "select name as value from center where code = 001";
    $company = $this->coreFunctions->datareader($qry);

    PDF::MultiCell(200, 15, 'RO #: ' . $data[0]['crref'], '', 'L', false, 1, '30', '425');
    PDF::MultiCell(200, 15, 'STRICTLY: ' . $data[0]['terms'], '', 'L', false, 1, '30', '446');
    PDF::MultiCell(200, 15, 'DUE DATE: ' . $data[0]['due'], '', 'L', false, 1, '30', '469');
    PDF::MultiCell(300, 15, $company, '', 'L', false, 1, '30', '494');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, number_format($totalext, 2) == 0.00 ? '' : number_format($totalext, 2), '', 'R', false, 1, '339', '494');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, ' ' . $params['params']['dataparams']['prepared'], '', 'L', false, 0, '27', '688');
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['delivered'], '', 'L', false, 0, '160', '');
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['approved'], '', 'L', false, 1, '373', '');

    PDF::MultiCell(200, 0, ' ' . $params['params']['dataparams']['checked'], '', 'L', false, 1, '27', '753');
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
    PDF::SetMargins(20, 20);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, '', 70);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(125, 0, '', '', 'L', false, 0);
    PDF::MultiCell(485, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 0, '120', '110');
    PDF::MultiCell(150, 0, date('m-d-Y', strtotime($data[0]['dateid'])), '', 'L', false, 1, '500');
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);


    $address = '';
    if (isset($data[0]['shipto'])) {
      if ($data[0]['shipto'] != '') {
        $address = $data[0]['shipto'];
      } else {
        goto defaultaddrhere;
      }
    } else {
      defaultaddrhere:
      $address = isset($data[0]['address']) ? $data[0]['address'] : '';
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(125, 0, '', '', 'L', false, 0);
    PDF::MultiCell(500, 0, $address, '', 'L', false, 0, '120');
    PDF::MultiCell(135, 0, isset($data[0]['ourref']) ? $data[0]['ourref'] : '', '', 'L', false, 1, '500');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, isset($data[0]['agname']) ? $data[0]['agname'] : '', '', 'L', false, 1, '500');
  }

  public function default_sj_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 20;
    $totalext = 0;
    $totalamt = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_sj_header_PDF($params, $data);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);


    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);
        $maxrow = 1;
        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(125, 0, number_format($data[$i]['qty'], $decimalqty), '', 'R', false, 0);
          PDF::MultiCell(75, 0, $data[$i]['uom'], '', 'C', false, 0);
          PDF::MultiCell(260, 0, $data[$i]['itemname'], '', 'L', false, 0);
          PDF::MultiCell(150, 0, number_format($data[$i]['amt'], $decimalprice), '', 'R', false, 0);
          PDF::MultiCell(130, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', false, 1);
        } else {
          if (($rcount + $maxrow) > $page) {
            $this->default_sj_footer_pdf($rcount, $page, $params, $data, $totalext,  $totaldisc, $decimalprice, $font, $fontbold, $fontsize);
            $this->default_sj_header_PDF($params, $data);
            $page += $count;
          }
          for ($r = 0; $r < $maxrow; $r++) {
            $rcount++;
            if ($r == 0) {

              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];

              $ext = number_format($data[$i]['ext'], 2);
              $total = $data[$i]['qty'] * $data[$i]['amt'];
            } else {
              $qty = '';
              $uom = '';
              $amt = '';
              $disc = '';
              $ext = '';
              $total = '';
            }
            PDF::setFontSpacing(2);
            PDF::SetFont($font, '', 10);
            PDF::MultiCell(760, 20, '', '', 'L', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(125, 0, $qty, '', 'C', false, 0, '10');
            PDF::MultiCell(75, 0, $uom, '', 'C', false, 0, '110');
            PDF::MultiCell(260, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '180');
            PDF::MultiCell(150, 0, $amt, '', 'R', false, 0, '330');
            PDF::MultiCell(130, 0, number_format($total, 2), '', 'R', false, 0, '460');
          }
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['discount'];
      }
    }
    PDF::setFontSpacing(0);
    $this->default_sj_footer_pdf($rcount, $page, $params, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_sj_footer_pdf($rcount, $maxpage, $params, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize)
  {
    for ($a = $rcount; $a < $maxpage; $a++) {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(760, 0, '', '', 'L', false, 1);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(760, 0, '', '', 'L', false, 1);
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);
    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, "Total Discount:", '', 'L', false, 0, '400', '650');
    PDF::SetFont($font, '', '15');
    PDF::MultiCell(150, 0, number_format($totaldisc, 2) == 0.00 ? '' : ($totaldisc < 0 ? '+' . number_format(abs($totaldisc), 2) : number_format($totaldisc, 2)), '', 'R', false, 1, '450', '650');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, "Total Amount:", '', 'L', false, 0, '400', '670');
    PDF::SetFont($font, '', '15');
    PDF::MultiCell(150, 0, number_format($totalext, 2) == 0.00 ? '' : number_format($totalext, 2), '', 'R', false, 1, '450', '670');

    PDF::SetFont($font, '', 50);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, "", '', 'L', false, 0, '400', '680');
    PDF::SetFont($font, '', '12');
    PDF::MultiCell(150, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, '510', '688');

    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, "", '', 'L', false, 0, '400', '660');
    PDF::SetFont($font, '', '12');
    PDF::MultiCell(150, 0, date('m-d-Y', strtotime($data[0]['due'])), '', 'L', false, 1, '510', '705');

    PDF::SetFont($font, '', 50);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(610, 0, '', '', 'L', false, 0);
    PDF::MultiCell(485, 0, isset($data[0]['yourref']) ? $data[0]['yourref'] : '', '', 'L', false, 0, '50', '650');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n\n\n");
    PDF::MultiCell(200, 0, ' ' . $params['params']['dataparams']['prepared'], '', 'C', false, 0);
    PDF::MultiCell(260, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['approved'], '', 'C', false, 0, '320', '');
    PDF::MultiCell(150, 0, ' ' . $params['params']['dataparams']['checked'], '', 'C', false, 1, '', '');
  }


  public function svc_online_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::SetMargins(32, 32);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(736, 15, '', '', 'C', false, 1, '32', '40');
    PDF::MultiCell(70, 0, '', '', 'L', false, 0, '32', '125'); //124 MATAAS
    PDF::MultiCell(401, 0,  isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 0, '123', '118');
    PDF::MultiCell(45, 0, '', '', 'L', false, 0, '511', '127');
    PDF::MultiCell(220, 0, date('m-d-Y', strtotime($data[0]['dateid'])), '', 'L', false, 1, '600', '118');

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(730, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '123', '139'); //140 MATAAS

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(730, 0, '', '', 'L', false, 1);
  }

  public function svc_online_sj_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 20;
    $totalext = 0;
    $totalamt = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->svc_online_sj_header_PDF($config, $data);

    PDF::SetFont($font, '', 14);
    PDF::MultiCell(740, 0, '', '', 'L', false, 1, '', 166);


    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);
        $maxrow = 1;
        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            $rcount++;
            if ($r == 0) {

              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
              $total = $data[$i]['qty'] * $data[$i]['amt'];
            } else {
              $qty = '';
              $uom = '';
              $amt = '';
              $disc = '';
              $ext = '';
              $total = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(78, 20, $qty, '', 'C', false, 0, 30, '');
            PDF::MultiCell(56, 20, $uom, '', 'C', false, 0, 115, '');
            PDF::MultiCell(380, 20, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, 185, '');
            PDF::MultiCell(82, 20, $amt, '', 'R', false, 0, 550, '');
            PDF::MultiCell(140, 20, number_format($total, 2), '', 'R', false, 1, 623, '');
          }
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['discount'];
      }
    }

    $this->svc_online_sj_footer_pdf($rcount, $page, $config, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function svc_online_sj_footer_pdf($rcount, $maxpage, $params, $data, $totalext, $totaldisc, $decimalprice, $font, $fontbold, $fontsize)
  {

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(599, 0, '', '', 'R', false, 0);
    PDF::MultiCell(131, 0, number_format($totalext, 2) == 0.00 ? '' : number_format($totalext, 2), '', 'R', false, 0, 632, 460);
    PDF::MultiCell(6, '', '', '', 'R', false, 1);
    PDF::SetFont($font, '', 25);
    PDF::MultiCell(736, 0, '', '', 'C', false, 1);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(736, 0, '', '', 'C', false, 0, 30, 487);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(245, 50, ' ' . $params['params']['dataparams']['prepared'], '', 'C', false, 0, 37, 540);
    PDF::MultiCell(246, 50, ' ' . $params['params']['dataparams']['checked'], '', 'C', false, 0);
    PDF::MultiCell(245, 50, ' ' . $params['params']['dataparams']['received'], '', 'C', false, 1);


    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(60, 0, "Contact#: ", '', 'L', false, 0, '35', '570');
    // PDF::MultiCell(200, 0, "" . isset($data[0]['mobile']) ? $data[0]['mobile'] : '', '', 'L', false, 1, '95', '570');

    if (!empty($data[0]['mobile'])) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(60, 0, "Contact#: ", '', 'L', false, 0, '35', '570');
      PDF::MultiCell(200, 0, $data[0]['mobile'], '', 'L', false, 1, '95', '570');
    }
  }



  public function kgm_metromanila_sj_header_PDF($params, $data)
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
    PDF::SetMargins(20, 20);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetFont($font, '', 20);
    PDF::MultiCell(760, 0, '', '', 'C', false, 1, '20', '20');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(760, 0, '', '', 'C', false, 1, '20', '55');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0,  isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 0, '83', '105');

    PDF::MultiCell(70, 0, date('m/d', strtotime($data[0]['dateid'])), '', 'L', false, 1, '280', '105');

    // PDF::MultiCell(70, 0, date('Y', strtotime($data[0]['dateid'])), '', 'L', false, 1, '272', '103');
    PDF::MultiCell(70, 0, date('y', strtotime($data[0]['dateid'])), '', 'L', false, 1, '341', '105');


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 0, '55', '126');
    PDF::MultiCell(200, 0, isset($data[0]['terms']) ? $data[0]['terms'] : '', '', 'L', false, 1, '268', '126');


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(760, 0, isset($data[0]['bstyle']) ? $data[0]['bstyle'] : '', '', 'L', false, 1, '87', '145');
    // PDF::MultiCell(300, 0, '', '', 'L', false, 1, '120', '135');


    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(760, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '70', '165');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1);
  }

  public function kgm_metromanila_sj_PDF($config, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 20;
    $totalext = 0;
    $totalamt = 0;
    $totaldisc = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    $unitfont = "9";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->kgm_metromanila_sj_header_PDF($config, $data);

    PDF::SetFont($font, '', 40);
    PDF::MultiCell(760, 0, '', '', 'L', false, 1, '', 166);

    $countarr = 0;
    $rcount = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);
        $maxrow = 1;
        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            $rcount++;
            if ($r == 0) {

              $qty = round($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $amt = number_format($data[$i]['amt'], 2);
              $disc = $data[$i]['disc'];

              $ext = number_format($data[$i]['ext'], 2);
              $total = $data[$i]['qty'] * $data[$i]['amt'];
            } else {
              $qty = '';
              $uom = '';
              $amt = '';
              $disc = '';
              $ext = '';
              $total = '';
            }
            // PDF::SetFont($font, '', 3);
            // PDF::MultiCell(400, 21, 'QTY', 'BT', 'C', false, 1);
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 30, $qty, '', 'C', false, 0, '6', '');
            PDF::SetFont($font, '', $unitfont);
            PDF::MultiCell(30, 30, $uom, '', 'C', false, 0, '39.5', '');
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(170, 30, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '70', '');
            PDF::MultiCell(75, 30, $amt, '', 'R', false, 0, '221', '');
            PDF::MultiCell(75, 30, number_format($total, 2), '', 'R', false, 1, '290', '');
          }
        }

        $totalext += $data[$i]['ext'];
        $totaldisc += $data[$i]['discount'];
      }
    }


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(200, 0, number_format($totalext, 2) == 0.00 ? '' : number_format($totalext, 2), '', 'L', false, 1, '320', '545');



    if (!empty($data[0]['mobile'])) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(60, 0, "Contact#: ", '', 'L', false, 0, '15', '570');
      PDF::MultiCell(200, 0, $data[0]['mobile'], '', 'L', false, 1, '70', '570');
    }
    // PDF::SetFont($font, '', $fontsize);
    // PDF::MultiCell(60, 0, "Contact#: ", '', 'L', false, 0, '15', '570');
    // PDF::MultiCell(200, 0, "" . isset($data[0]['mobile']) ? $data[0]['mobile'] : '', '', 'L', false, 1, '70', '570');
    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
