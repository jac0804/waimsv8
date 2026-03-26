<?php

namespace App\Http\Classes\modules\modulereport\conti;

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

class sn
{

  private $modulename = "Supplier Invoice";

  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';


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

  public function createreportfilter()
  {

    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'pink'],
      ['label' => 'A/P Invoice', 'value' => '1', 'color' => 'pink']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $signatories = $this->othersClass->getSignatories($config);
    $approved = '';
    $prepared = '';
    $received = '';


    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'approved':
          $approved = $value->fieldvalue;
          break;
        case 'prepared':
          $prepared = $value->fieldvalue;
          break;
        case 'received':
          $received = $value->fieldvalue;
          break;
      }
    }

    return $this->coreFunctions->opentable(
      "select 
        'PDFM' as print,
        '$prepared' as prepared,
        '0' as reporttype,
        '$approved' as approved,
        '$received' as received
        "
    );
  }

  public function report_default_query($trno)
  {

    $query = "select snhead.docno, snhead.trno, snhead.clientname, snhead.address, date(snhead.dateid) as dateid, 
        snhead.terms, snhead.rem, item.barcode, item.itemname, stock." . $this->damt . " as gross, stock." . $this->hamt . " as netamt, stock." . $this->dqty . " as qty,
        stock.uom, stock.disc, stock.ext, stock.line, wh.client as wh, wh.clientname as whname, stock.loc,
        date(stock.expiry) as expiry, stock.rem as srem, item.sizeid, m.model_name as model,head.tax,head.vattype,p.code as projectcode,p.name as projectname,snhead.yourref,snhead.ourref
        from glhead as head 
        left join glstock as stock on stock.trno=head.trno
        left join cntnum on head.trno = cntnum.trno
        left join " . $this->head . " as snhead on snhead.trno = cntnum.svnum
        left join client as wh on wh.clientid = stock.whid
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        left join projectmasterfile as p on p.line=snhead.projectid
        where snhead.trno='$trno'
        union all
        select snhead.docno, snhead.trno, snhead.clientname, snhead.address, date(snhead.dateid) as dateid, snhead.terms, snhead.rem,
        item.barcode, item.itemname, stock." . $this->damt . " as gross, stock." . $this->hamt . " as netamt, stock." . $this->dqty . " as qty,
        stock.uom, stock.disc, stock.ext, stock.line, wh.client as wh, wh.clientname as whname, stock.loc, 
        date(stock.expiry) as expiry, stock.rem as srem, item.sizeid, m.model_name as model,head.tax,head.vattype,p.code as projectcode,p.name as projectname,snhead.yourref,snhead.ourref
        from (glhead as head 
        left join glstock as stock on stock.trno=head.trno
        left join cntnum on head.trno = cntnum.trno
        left join " . $this->hhead . " as snhead on snhead.trno = cntnum.svnum)
        left join item on item.itemid=stock.itemid
        left join client as wh on wh.clientid = stock.whid
        left join model_masterfile as m on m.model_id = item.model
        left join projectmasterfile as p on p.line=snhead.projectid
        where snhead.trno='$trno'
        order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function report_detail_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select head.docno,head.doc,c.acno,c.acnoname,detail.trno,detail.line,detail.db,detail.cr
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno
    left join coa as c on c.acnoid=detail.acnoid
    left join cntnum on head.trno = cntnum.trno
    where head.doc='SN' and cntnum.trno='$trno'
    union all
    select head.docno,head.doc,c.acno,c.acnoname,detail.trno,detail.line,detail.db,detail.cr
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    left join coa as c on c.acnoid=detail.acnoid
    left join cntnum on head.trno = cntnum.trno
    where head.doc='SN' and cntnum.trno='$trno'
    order by line";


    return $query;
  } //end fn


  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_sn_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {

      switch ($params['params']['dataparams']['reporttype']) {
        case '0':
          return $this->default_SN_PDF($params, $data);
          break;

        case '1':
          return $this->invoice_AP_PDF($params, $data);

          break;


      }
    }
  }

  public function Invoice_detail_AP_Header_PDF($params, $data)
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


    PDF::SetFont($fontbold, '', $fontsize);
    $style = array(
      'border' => false,
      'padding' => 0,
    );


    PDF::SetFont($font, '', 5);

    PDF::MultiCell(720, 0, '', 'TB');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "Code", '', 'L', false, 0);
    PDF::MultiCell(250, 0, "Acct Desc", '', 'L', false, 0);
    PDF::MultiCell(150, 0, "", '', 'C', false, 0);
    PDF::MultiCell(110, 0, "Debit", '', 'R', false, 0);
    PDF::MultiCell(110, 0, "Credit", '', 'R', false);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }

  public function invoice_AP_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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

   

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

   
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, 'A/P Invoice', '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Project: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(320, 0, (isset($data[0]['projectcode']) ? $data[0]['projectcode'] . '~' . $data[0]['projectname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Yourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Ourref: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['wh']) ? $data[0]['wh'] . '~' . $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Vattype: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['vattype']) ? $data[0]['vattype'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "EXPIRY", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function invoice_AP_PDF($params, $data)
  {
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $count = $page = 35;
    $totalext = 0;
    $totaldb = 0;
    $totalcr = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->invoice_AP_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);


        $maxrow = 1;

        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 0, number_format($data[$i]['qty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(200, 0, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, $data[$i]['expiry'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['netamt'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, $data[$i]['disc'], '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', 0, 1, '', '', true, 0, true, false);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $expiry = $data[$i]['expiry'];
              $qty = number_format($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $netamt = number_format($data[$i]['netamt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
            } else {
              $expiry = '';
              $qty = '';
              $uom = '';
              $netamt = '';
              $disc = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, $qty, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(200, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $expiry, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $netamt, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $disc, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ext, '', 'R', false, 1, '', '', false, 0);
          }
        }

        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->invoice_AP_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), '', 'R');



    PDF::MultiCell(0, 0, "\n\n\n");

    $query = $this->report_detail_query($params);

    $detailresult = json_decode(json_encode($this->coreFunctions->opentable($query)), true);


    if ($detailresult[0]['line'] != null) {

      $this->Invoice_detail_AP_Header_PDF($params, $data);

      for ($i = 0; $i < count($detailresult); $i++) {

        $maxrow = 1;

        $acno = $detailresult[$i]['acno'];
        $acnoname = $detailresult[$i]['acnoname'];
        $db = number_format($detailresult[$i]['db'], 2);
        $cr = number_format($detailresult[$i]['cr'], 2);


        $arr_acno = $this->reporter->fixcolumn([$acno], '15', 0);
        $arr_acnoname = $this->reporter->fixcolumn([$acnoname], '50', 0);
        $arr_db = $this->reporter->fixcolumn([$db], '13', 0);
        $arr_cr = $this->reporter->fixcolumn([$cr], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_acno, $arr_acnoname, $arr_db, $arr_cr]);
        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_acno[$r]) ? $arr_acno[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(250, 15, ' ' . (isset($arr_acnoname[$r]) ? $arr_acnoname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(150, 15, ' ', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(110, 15, ' ' . (isset($arr_db[$r]) ? $arr_db[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(110, 15, ' ' . (isset($arr_cr[$r]) ? $arr_cr[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }


        $totaldb += $detailresult[$i]['db'];
        $totalcr += $detailresult[$i]['cr'];

        if (PDF::getY() > 900) {
          $this->Invoice_detail_AP_Header_PDF($params, $data);
        }
      }
    }


    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "", '', 'C', false, 0);
    PDF::MultiCell(150, 0, "", '', 'C', false, 0);
    PDF::MultiCell(110, 0, number_format($totaldb, 2), 'T', 'R', false, 0);
    PDF::MultiCell(110, 0, number_format($totalcr, 2), 'T', 'R', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_header($params, $data)
  {

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : '') . QrCode::size(100)->generate($data[0]['docno'] . '-' . $data[0]['trno']), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
   

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '120', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
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

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
 
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '400px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('EXPIRY', '100px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DISC', '50px', null, false, $border, 'B', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'R', $font, '12', 'B', '30px', '8px');

    return $str;
  }

  public function default_sn_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $totalext = 0;

    for ($i = 0; $i < count($data); $i++) {

      $ext = number_format($data[$i]['ext'], $decimal);

      if ($ext < 1) {
        $ext = '-';
      }
      $netamt = number_format($data[$i]['netamt'], $decimal);

      if ($netamt < 1) {
        $netamt = '-';
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '400px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['expiry'], '100px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['gross'], $this->companysetup->getdecimal('price', $params['params'])), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($ext, '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
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

  public function default_SN_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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

  

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

   
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "EXPIRY", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_SN_PDF($params, $data)
  {
  
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_SN_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);


        $maxrow = 1;

        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 0, number_format($data[$i]['qty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(200, 0, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, $data[$i]['expiry'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['netamt'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, $data[$i]['disc'], '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', 0, 1, '', '', true, 0, true, false);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $expiry = $data[$i]['expiry'];
              $qty = number_format($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $netamt = number_format($data[$i]['netamt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
            } else {
              $expiry = '';
              $qty = '';
              $uom = '';
              $netamt = '';
              $disc = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, $qty, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(200, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $expiry, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $netamt, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $disc, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ext, '', 'R', false, 1, '', '', false, 0);
          }
        }

        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_SN_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

 

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_AP_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
   
    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
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

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, 'AP Invoice', '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Supplier: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Terms: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "EXPIRY", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_AP_PDF($params, $data)
  {
  
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_AP_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $itemname = $this->reporter->fixcolumn([$data[$i]['itemname']], '100', 0);


        $maxrow = 1;

        $countarr = count($itemname);
        $maxrow = $countarr;

        if ($data[$i]['itemname'] == '') {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50, 0, number_format($data[$i]['qty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(200, 0, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, $data[$i]['expiry'], '', 'C', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['netamt'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, $data[$i]['disc'], '', 'R', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', 0, 1, '', '', true, 0, true, false);
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $expiry = $data[$i]['expiry'];
              $qty = number_format($data[$i]['qty'], 2);
              $uom = $data[$i]['uom'];
              $netamt = number_format($data[$i]['netamt'], 2);
              $disc = $data[$i]['disc'];
              $ext = number_format($data[$i]['ext'], 2);
            } else {
              $expiry = '';
              $qty = '';
              $uom = '';
              $netamt = '';
              $disc = '';
              $ext = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(50, 0, $qty, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(200, 0, isset($itemname[$r]) ? $itemname[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $expiry, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $netamt, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $disc, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $ext, '', 'R', false, 1, '', '', false, 0);
          }
        }

        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_AP_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalprice), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
