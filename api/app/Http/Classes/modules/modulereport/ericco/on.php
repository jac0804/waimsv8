<?php

namespace App\Http\Classes\modules\modulereport\ericco;

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
use App\Http\Classes\reportheader;
use App\Http\Classes\common\commonsbc;
use DateTime;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class on
{

  private $modulename = "Outright Invoice";
  private $reportheader;
  private $commonsbc;
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
    $this->reportheader = new reportheader;
    $this->commonsbc = new commonsbc;
  }

  public function createreportfilter($config)
  {

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);

    $fields = ['radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'radioreporttype.options', [
      //  ['label' => 'METRO GAISANO', 'value' => '0', 'color' => 'red'],
       ['label' => 'THE LANDMARK OUTRIGHT', 'value' => '0', 'color' => 'red'],
       ['label' => 'WILCON OUTRIGHT', 'value' => '1', 'color' => 'red'],
       ['label' => 'GENERAL FORMAT', 'value' => '2', 'color' => 'red']
    ]);
    data_set($col2, 'radioreporttype.label', 'Report Type');
    data_set($col2, 'approved.label', 'Checked By');
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'PDFM' as print,
        '' as prepared,
        '' as approved,
        '' as received,
          '0' as reporttype
        "
    );
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "select stock.line,left(stock.rem,11) as srem,head.rem,
            left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, head.agent,
            item.sizeid, left(ag.clientname,17) as agname, item.brand,ifnull(client.registername,'') as registername,date(head.due) as due,ifnull(client.contact,'') as contact,
            wh.client as whcode, wh.clientname as whname,client.clientid, item.partno,head.trno,head.vattype,head.ewtrate,
            stock.cost, concat(round(ifnull(stock.isqty,0),0),' ',stock.uom) as landmarkqty
            from lahead as head

            left join cntnum as num on num.svnum=head.trno
            left join glhead as head2 on head2.trno=num.trno
            left join glstock as stock on stock.trno=head2.trno
            left join client on client.client=head.client
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.client=head.agent
            left join client as wh on wh.client=head.wh
            where head.doc='on' and num.svnum='$trno'
            UNION ALL
            select stock.line,left(stock.rem,11) as srem,head.rem,
            left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
            head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
            item.itemname, stock.isqty as qty, stock.uom , stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
            item.sizeid, left(ag.clientname,17) as agname,item.brand,
            ifnull(client.registername,'') as registername,date(head.due) as due,ifnull(client.contact,'') as contact,
            wh.client as whcode, wh.clientname as whname,client.clientid, item.partno,head.trno,head.vattype,head.ewtrate,
            stock.cost, concat(round(ifnull(stock.isqty,0),0),' ',stock.uom) as landmarkqty
            from glhead as head
            left join cntnum as num on num.svnum=head.trno
            left join glhead as head2 on head2.trno=num.trno
            left join glstock as stock on stock.trno=head2.trno
            left join client on client.clientid=head.clientid
            left join item on item.itemid=stock.itemid
            left join client as ag on ag.clientid=head.agentid
            left join client as wh on wh.clientid=head.whid
            where head.doc='on' and num.svnum='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function report_sj_query($trno)
  {

    $query = "select stock.line,left(stock.rem,11) as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
          right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
          head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
          item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
          item.sizeid, ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc,head.trno,head.vattype,head.ewtrate,
          stock.cost,stock.amt as amt2
          from lahead as head
          left join cntnum as num on num.svnum=head.trno
          left join glhead as head2 on head2.trno=num.trno
          left join glstock as stock on stock.trno=head2.trno
          left join client on client.client=head.client
          left join item on item.itemid=stock.itemid
          left join client as ag on ag.client=head.agent
          left join client as wh on wh.client=head.wh
          left join part_masterfile as part on part.part_id = item.part
          left join frontend_ebrands as brands on brands.brandid = item.brand
          where head.doc='on' and  num.svnum='$trno'
          UNION ALL
          select stock.line,left(stock.rem,11) as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
          right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
          head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
          item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
          item.sizeid, ag.clientname as agname, item.brand,head.vattype,
          wh.client as whcode, wh.clientname as whname,client.tin,part.part_code,part.part_name,brands.brand_desc,head.trno,head.vattype,head.ewtrate,
          stock.cost,stock.amt as amt2
          from glhead as head
          left join cntnum as num on num.svnum=head.trno
          left join glhead as head2 on head2.trno=num.trno
          left join glstock as stock on stock.trno=head2.trno
          left join client on client.clientid=head.clientid
          left join item on item.itemid=stock.itemid
          left join client as ag on ag.clientid=head.agentid
          left join client as wh on wh.clientid=head.whid
          left join part_masterfile as part on part.part_id = item.part
          left join frontend_ebrands as brands on brands.brandid = item.brand
          where head.doc='on' and  num.svnum='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    $print = $params['params']['dataparams']['print'];
    $reporttype = $params['params']['dataparams']['reporttype'];

    switch ($print) {
      case 'PDFM':
        switch ($reporttype) {
          // case '0': //METRO GAISANO
          //   return $this->metro_gaisano_layout_PDF($params, $data);
          //   break;
          case '0': //THE LANDMARK OUTHRIGHT
            return $this->landmark_layout_PDF($params, $data);
            break;
          case '1': //WILCON OUTHRIGHT
            return $this->wilcon_layout_PDF($params, $data);
            break;
          case '2': //GENERAL OUTHRIGHT
            return $this->general_layout_PDF($params, $data);
            break;

        }
        break;
      default:
        return $this->default_sj_layout($params, $data);
        break;
    }

    
  }

  public function default_sj_layout($params, $data)
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
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        // <--- Header
        $str .= $this->report_default_header($params, $data);

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page = $page + $count;
      } //end if
    } //end for

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
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

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($this->modulename, '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
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
    $str .= $this->reporter->col('D E S C R P T I O N', '500px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    return $str;
  }

  

  //defaultt
  public function default_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

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

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');;
    $this->reportheader->getheader($params);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(500, 20, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Document # : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 20, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // PDF::SetFont($font, '', $fontsize);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Customer : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Date : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 20, "Address : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 20, "Terms : ", '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(200, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'R', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');
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
    $fontsize = "11";
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
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
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
          PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        $totalext += $data[$i]['ext'];

        if (PDF::getY() > 900) {
          $this->default_sj_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");


    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    //PDF::AddPage();
    //$b = 62;
    //for ($i = 0; $i < 1000; $i++) {
    //  PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
    //  PDF::MultiCell(0, 0, "\n");
    //  if($i==$b){
    //    PDF::AddPage();
    //    $b = $b + 62;
    //  }
    //}

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



  //metro gaisano

  public function header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 13;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40); //720

    // PDF::SetFont($font, '', 9);
    // PDF::MultiCell(0, 0, "\n\n\n\n\n\n\n");
    // PDF::SetFont($font, '', 8);
    // PDF::MultiCell(720, 0, '', '');

    PDF::SetXY(40, 118.75);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(75, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, '', true);
    PDF::MultiCell(368, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    // $y=PDF::getY();
    PDF::SetXY(40, 142);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(113, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, '', true);
    PDF::MultiCell(330, 20, (isset($data[0]['registername']) ? $data[0]['registername'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(40, 165);
    // $y=PDF::getY();
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(113, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 20, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    //  $y=PDF::getY();
    PDF::SetXY(40, 182);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(118, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(325, 20, (isset($data[0]['address']) ? $data[0]['address'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

 
    PDF::SetXY(40, 205);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(113, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(330, 20, (isset($data[0]['contact']) ? $data[0]['contact'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(127, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(150, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);
 
    //right

    $date = $data[0]['dateid'];
    $datetime = new DateTime($date);
    $datehere = $datetime->format('m-d-Y');

    PDF::SetXY(40, 113);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(70, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(373, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(152, 20,'', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20,  $datehere, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(25, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    PDF::SetXY(40, 130);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(152, 20,'', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20,(isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(25, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetXY(40, 150);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(152, 20,'', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20,(isset($data[0]['terms']) ? $data[0]['terms'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(25, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);


    $due = $data[0]['due'];
    $due1 = new DateTime($due);
    $duedate = $due1->format('m-d-Y');



    PDF::SetXY(40, 168);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(152, 20,'', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, $duedate, '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(25, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

     
    PDF::SetXY(40, 185);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
     PDF::MultiCell(152, 20,'', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, (isset($data[0]['agname']) ? $data[0]['agname'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(25, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);




    //dr number
    PDF::SetXY(40, 203);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(28, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(415, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(152, 20,'', '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20,(isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(25, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    
    PDF::MultiCell(0, 0, "\n\n\n");

  }

  public function metro_gaisano_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $trno = $params['params']['dataid'];

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->header_PDF($params, $data);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

            $rowCount = 0;
            $pageLimit = 16;
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

            // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '38', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '3', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([ $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_ext,$arr_disc]);
            for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(5, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(5, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(303, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(3, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(30, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(68, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(141, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(16, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            $rowCount++;  
            }
            
                 $totalext= $this->coreFunctions->datareader("
                    select sum(stock.ext) as value from glstock as stock
                                        left join glhead as head2 on head2.trno=stock.trno
                                        left join cntnum as num on num.trno=head2.trno
                                        where  num.svnum='".$data[$i]['trno']."'");     
                 if ($rowCount >= $pageLimit && $i < count($data) - 1) {
                        // $next=1;
                        $this->footer($params, $data,$totalext);
                        $this->header_PDF($params, $data);
                        $rowCount = 0; // reset counter
                    }
            }
            }

        
            $vatable=0;
            $vatamt=0;

        if ($data[0]['vattype'] == 'VATABLE') {
            $vatable=$totalext/1.12;
            $vatamt=$vatable*.12;
            }
        //VATABLE SALES
         PDF::SetXY(40, 525);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         //VAT
         PDF::SetXY(40, 545);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

            //ZER RATD
         PDF::SetXY(40, 565);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
     

            //VAT EXEMPT SALES
         PDF::SetXY(40, 585);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
 
       
          //TOTAL SALES
         PDF::SetXY(40, 600);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totalext, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
            //LESS VAT
         PDF::SetXY(40, 620);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
            //AMOUNT NET OF VAT
         PDF::SetXY(40, 640);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       

            //LESS DISCOUNT
        //  PDF::SetXY(40, 665);
        //  PDF::SetFont($fontbold, '', $fontsize);
        //  PDF::MultiCell(559, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(141, 0, '', '', 'R');
        //  PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
          //ADD VAT
         PDF::SetXY(40, 675);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
         $withholdingTax = 0;

            if ($data[0]['ewtrate'] != 0) {
                $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
            }
       
       
          //LESS WITHHOLDING TAX
         PDF::SetXY(40, 695);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($withholdingTax, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
        $totaldue=$totalext-$withholdingTax;
            //TOTAL AMOUNT DUE
         PDF::SetXY(40, 715);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totaldue, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
    
         //left here
            $prepared=$params['params']['dataparams']['prepared'];
            $checked=$params['params']['dataparams']['approved'];
            $received=$params['params']['dataparams']['received'];
         
         PDF::SetXY(40, 573);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(208, 0, strtoupper($received), '', 'C', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         PDF::SetXY(40, 623);
         PDF::SetFont($fontbold, '', 11);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(458, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(80, 0, '', '', 'R', false, 0);
         PDF::MultiCell(110, 0, strtoupper($prepared), '', 'C', false, 0);
          PDF::MultiCell(20, 0,'', '', 'L', false, 0);
         PDF::MultiCell(110, 0, strtoupper($checked), '', 'C', false, 0);

         PDF::MultiCell(78, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

       

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function footer($params,$data,$totalext){


    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $trno = $params['params']['dataid'];
    $prepared=$params['params']['dataparams']['prepared'];
    $checked=$params['params']['dataparams']['approved'];
    $received=$params['params']['dataparams']['received'];


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
        //  $y=PDF::getY();


            $vatable=0;
            $vatamt=0;

        if ($data[0]['vattype'] == 'VATABLE') {
            $vatable=$totalext/1.12;
            $vatamt=$vatable*.12;
            }
        //VATABLE SALES
         PDF::SetXY(40, 525);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         //VAT
         PDF::SetXY(40, 545);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

            //ZER RATD
         PDF::SetXY(40, 565);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
     

            //VAT EXEMPT SALES
         PDF::SetXY(40, 585);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
 
       
          //TOTAL SALES
         PDF::SetXY(40, 600);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totalext, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
            //LESS VAT
         PDF::SetXY(40, 620);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
            //AMOUNT NET OF VAT
         PDF::SetXY(40, 640);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       

            //LESS DISCOUNT
        //  PDF::SetXY(40, 665);
        //  PDF::SetFont($fontbold, '', $fontsize);
        //  PDF::MultiCell(559, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(141, 0, '', '', 'R');
        //  PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
          //ADD VAT
         PDF::SetXY(40, 675);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
         $withholdingTax = 0;

            if ($data[0]['ewtrate'] != 0) {
                $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
            }
       
       
          //LESS WITHHOLDING TAX
         PDF::SetXY(40, 695);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($withholdingTax, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
        $totaldue=$totalext-$withholdingTax;
            //TOTAL AMOUNT DUE
         PDF::SetXY(40, 715);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totaldue, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
    
         //left here
         
         PDF::SetXY(40, 573);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(208, 0, strtoupper($received), '', 'C', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         PDF::SetXY(40, 623);
         PDF::SetFont($fontbold, '', 11);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(458, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(80, 0, '', '', 'R', false, 0);
         PDF::MultiCell(115, 0, strtoupper($prepared), '', 'C', false, 0);
          PDF::MultiCell(20, 0,'', '', 'L', false, 0);
         PDF::MultiCell(118, 0, strtoupper($checked), '', 'C', false, 0);

         PDF::MultiCell(65, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

       
 }



  public function landmark_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $trno = $params['params']['dataid'];

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->header_PDF($params, $data);

        // PDF::SetFont($font, '', 3);
        // PDF::MultiCell(720, 0, '', '');

            PDF::SetXY(40, 271.75);

            $rowCount = 0;
            $pageLimit = 16;
            if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;
            $barcode = $data[$i]['barcode'];
            $itemname = $data[$i]['itemname'];
            $qty = $data[$i]['landmarkqty'];
            // $uom = $data[$i]['uom'];
            $amt = number_format($data[$i]['amt'], 2);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], 2);

            $unit = number_format($data[$i]['cost'], 2);

            // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '38', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_cost = $this->reporter->fixcolumn([$unit], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '3', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([ $arr_itemname, $arr_qty,$arr_cost, $arr_amt, $arr_ext,$arr_disc]);
            for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(10, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(75, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_cost[$r]) ? $arr_cost[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(5, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(303, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(3, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(30, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(68, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(141, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(16, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            $rowCount++;  
            }
            
                 $totalext= $this->coreFunctions->datareader("
                    select sum(stock.ext) as value from glstock as stock
                                        left join glhead as head2 on head2.trno=stock.trno
                                        left join cntnum as num on num.trno=head2.trno
                                        where  num.svnum='".$data[$i]['trno']."'");     
                 if ($rowCount >= $pageLimit && $i < count($data) - 1) {
                        $this->footer($params, $data,$totalext);
                        $this->header_PDF($params, $data);
                        $rowCount = 0; // reset counter
                    }
            }
            }

        
            $vatable=0;
            $vatamt=0;

        if ($data[0]['vattype'] == 'VATABLE') {
            $vatable=$totalext/1.12;
            $vatamt=$vatable*.12;
            }
        //VATABLE SALES
         PDF::SetXY(40, 525);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         //VAT
         PDF::SetXY(40, 545);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

            //ZER RATD
         PDF::SetXY(40, 565);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
     

            //VAT EXEMPT SALES
         PDF::SetXY(40, 585);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
 
       
          //TOTAL SALES
         PDF::SetXY(40, 600);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totalext, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
            //LESS VAT
         PDF::SetXY(40, 620);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
            //AMOUNT NET OF VAT
         PDF::SetXY(40, 640);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       

            //LESS DISCOUNT
        //  PDF::SetXY(40, 665);
        //  PDF::SetFont($fontbold, '', $fontsize);
        //  PDF::MultiCell(559, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(141, 0, '', '', 'R');
        //  PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
          //ADD VAT
         PDF::SetXY(40, 675);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
         $withholdingTax = 0;

            if ($data[0]['ewtrate'] != 0) {
                $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
            }
       
       
          //LESS WITHHOLDING TAX
         PDF::SetXY(40, 695);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($withholdingTax, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
        $totaldue=$totalext-$withholdingTax;
            //TOTAL AMOUNT DUE
         PDF::SetXY(40, 715);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totaldue, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
    
         //left here
            $prepared=$params['params']['dataparams']['prepared'];
            $checked=$params['params']['dataparams']['approved'];
            $received=$params['params']['dataparams']['received'];
         
         PDF::SetXY(40, 573);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(208, 0, strtoupper($received), '', 'C', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         PDF::SetXY(40, 623);
         PDF::SetFont($fontbold, '', 11);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(458, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(80, 0, '', '', 'R', false, 0);
         PDF::MultiCell(115, 0, strtoupper($prepared), '', 'C', false, 0);
          PDF::MultiCell(20, 0,'', '', 'L', false, 0);
         PDF::MultiCell(118, 0, strtoupper($checked), '', 'C', false, 0);

         PDF::MultiCell(65, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

       

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


    public function wilcon_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $trno = $params['params']['dataid'];

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->header_PDF($params, $data);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

            $rowCount = 0;
            $pageLimit = 16;
            if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;
            $barcode = $data[$i]['barcode'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 0);
            $srem = $data[$i]['srem'];
            $amt = number_format($data[$i]['amt'], 2);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], 2);

            // $arr_barcode = $this->reporter->fixcolumn([$barcode], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '38', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_srem = $this->reporter->fixcolumn([$srem], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '3', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([ $arr_itemname, $arr_qty, $arr_srem, $arr_amt, $arr_ext,$arr_disc]);
            for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(5, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_srem[$r]) ? $arr_srem[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(5, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(303, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(3, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(30, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(68, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(141, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(16, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            $rowCount++;  
            }
            
                 $totalext= $this->coreFunctions->datareader("
                    select sum(stock.ext) as value from glstock as stock
                                        left join glhead as head2 on head2.trno=stock.trno
                                        left join cntnum as num on num.trno=head2.trno
                                        where  num.svnum='".$data[$i]['trno']."'");     
                 if ($rowCount >= $pageLimit && $i < count($data) - 1) {
                        // $next=1;
                        $this->footer($params, $data,$totalext);
                        $this->header_PDF($params, $data);
                        $rowCount = 0; // reset counter
                    }
            }
            }

        
            $vatable=0;
            $vatamt=0;

        if ($data[0]['vattype'] == 'VATABLE') {
            $vatable=$totalext/1.12;
            $vatamt=$vatable*.12;
            }
        //VATABLE SALES
         PDF::SetXY(40, 525);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         //VAT
         PDF::SetXY(40, 545);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

            //ZER RATD
         PDF::SetXY(40, 565);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
     

            //VAT EXEMPT SALES
         PDF::SetXY(40, 585);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
 
       
          //TOTAL SALES
         PDF::SetXY(40, 600);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totalext, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
            //LESS VAT
         PDF::SetXY(40, 620);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
            //AMOUNT NET OF VAT
         PDF::SetXY(40, 640);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       

            //LESS DISCOUNT
        //  PDF::SetXY(40, 665);
        //  PDF::SetFont($fontbold, '', $fontsize);
        //  PDF::MultiCell(559, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(141, 0, '', '', 'R');
        //  PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
          //ADD VAT
         PDF::SetXY(40, 675);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
         $withholdingTax = 0;

            if ($data[0]['ewtrate'] != 0) {
                $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
            }
       
       
          //LESS WITHHOLDING TAX
         PDF::SetXY(40, 695);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($withholdingTax, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
        $totaldue=$totalext-$withholdingTax;
            //TOTAL AMOUNT DUE
         PDF::SetXY(40, 715);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totaldue, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
    
         //left here
            $prepared=$params['params']['dataparams']['prepared'];
            $checked=$params['params']['dataparams']['approved'];
            $received=$params['params']['dataparams']['received'];
         
         PDF::SetXY(40, 573);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(208, 0, strtoupper($received), '', 'C', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         PDF::SetXY(40, 623);
         PDF::SetFont($fontbold, '', 11);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(458, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(80, 0, '', '', 'R', false, 0);
         PDF::MultiCell(110, 0, strtoupper($prepared), '', 'C', false, 0);
          PDF::MultiCell(20, 0,'', '', 'L', false, 0);
         PDF::MultiCell(110, 0, strtoupper($checked), '', 'C', false, 0);

         PDF::MultiCell(78, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

       

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  
    public function general_layout_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $trno = $params['params']['dataid'];

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "12";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->header_PDF($params, $data);

    PDF::SetFont($font, '', 3);
    PDF::MultiCell(720, 0, '', '');

            $rowCount = 0;
            $pageLimit = 16;
            if (!empty($data)) {
            for ($i = 0; $i < count($data); $i++) {

            $maxrow = 1;
            $uom = $data[$i]['uom'];
            $itemname = $data[$i]['itemname'];
            $qty = number_format($data[$i]['qty'], 0);
            $srem = $data[$i]['srem'];
            $amt = number_format($data[$i]['amt'], 2);
            $disc = $data[$i]['disc'];
            $ext = number_format($data[$i]['ext'], 2);

            $arr_uom = $this->reporter->fixcolumn([$uom], '15', 0);
            $arr_itemname = $this->reporter->fixcolumn([$itemname], '38', 0);
            $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
            $arr_srem = $this->reporter->fixcolumn([$srem], '13', 0);
            $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
            $arr_disc = $this->reporter->fixcolumn([$disc], '3', 0);
            $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

            $maxrow = $this->othersClass->getmaxcolumn([ $arr_itemname, $arr_qty, $arr_amt, $arr_ext,$arr_disc,$arr_uom]);
            for ($r = 0; $r < $maxrow; $r++) {
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(5, 15, '', '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(70, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(5, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(303, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(3, 15, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(30, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(68, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(141, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(16, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
            $rowCount++;  
            }
            
                 $totalext= $this->coreFunctions->datareader("
                    select sum(stock.ext) as value from glstock as stock
                                        left join glhead as head2 on head2.trno=stock.trno
                                        left join cntnum as num on num.trno=head2.trno
                                        where  num.svnum='".$data[$i]['trno']."'");     
                 if ($rowCount >= $pageLimit && $i < count($data) - 1) {
                        // $next=1;
                        $this->footer($params, $data,$totalext);
                        $this->header_PDF($params, $data);
                        $rowCount = 0; // reset counter
                    }
            }
            }

        
            $vatable=0;
            $vatamt=0;

        if ($data[0]['vattype'] == 'VATABLE') {
            $vatable=$totalext/1.12;
            $vatamt=$vatable*.12;
            }
        //VATABLE SALES
         PDF::SetXY(40, 525);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         //VAT
         PDF::SetXY(40, 545);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

            //ZER RATD
         PDF::SetXY(40, 565);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
     

            //VAT EXEMPT SALES
         PDF::SetXY(40, 585);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
 
       
          //TOTAL SALES
         PDF::SetXY(40, 600);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totalext, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
            //LESS VAT
         PDF::SetXY(40, 620);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
            //AMOUNT NET OF VAT
         PDF::SetXY(40, 640);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatable, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       

            //LESS DISCOUNT
        //  PDF::SetXY(40, 665);
        //  PDF::SetFont($fontbold, '', $fontsize);
        //  PDF::MultiCell(559, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(141, 0, '', '', 'R');
        //  PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
          //ADD VAT
         PDF::SetXY(40, 675);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($vatamt, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
       
         $withholdingTax = 0;

            if ($data[0]['ewtrate'] != 0) {
                $withholdingTax = ($totalext / 1.12) * ($data[0]['ewtrate'] / 100);
            }
       
       
          //LESS WITHHOLDING TAX
         PDF::SetXY(40, 695);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($withholdingTax, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
        $totaldue=$totalext-$withholdingTax;
            //TOTAL AMOUNT DUE
         PDF::SetXY(40, 715);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(559, 0, '', '', 'R', false, 0);
         PDF::MultiCell(141, 0, number_format($totaldue, $decimalcurr), '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
       
    
         //left here
            $prepared=$params['params']['dataparams']['prepared'];
            $checked=$params['params']['dataparams']['approved'];
            $received=$params['params']['dataparams']['received'];
         
         PDF::SetXY(40, 573);
         PDF::SetFont($fontbold, '', $fontsize);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(208, 0, strtoupper($received), '', 'C', false, 0);
         PDF::MultiCell(100, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

         PDF::SetXY(40, 623);
         PDF::SetFont($fontbold, '', 11);
         PDF::MultiCell(5, 0, '', '', 'R', false, 0);
        //  PDF::MultiCell(458, 0, '', '', 'R', false, 0);
         PDF::MultiCell(150, 0, '', '', 'R', false, 0);
         PDF::MultiCell(80, 0, '', '', 'R', false, 0);
         PDF::MultiCell(110, 0, strtoupper($prepared), '', 'C', false, 0);
          PDF::MultiCell(20, 0,'', '', 'L', false, 0);
         PDF::MultiCell(110, 0, strtoupper($checked), '', 'C', false, 0);

         PDF::MultiCell(78, 0, '', '', 'R', false, 0);
         PDF::MultiCell(137, 0, '', '', 'R');
         PDF::MultiCell(20, 15, ' ', '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);

       

    return PDF::Output($this->modulename . '.pdf', 'S');
  }



}
