<?php

namespace App\Http\Classes\modules\modulereport\ati;

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
use Illuminate\Support\Facades\URL;

use PDF;
use TCPDF_FONTS;
use TCPDF;
use Illuminate\Support\Facades\Storage;

class po
{
  private $modulename = "Purchase Order";
  private $coreFunctions;
  private $companysetup;
  private $othersClass;
  private $fieldClass;
  private $reporter;
  private $logger;
  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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

    $fields = ['radioprint', 'radioaticompany',  'prepared', 'checked', 'received', 'approved'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'prepared.label', 'Prepared By / Requested By');
    data_set($col1, 'checked.label', 'Checked By / Approved By');
    data_set($col1, 'received.label', 'Issued By');
    data_set($col1, 'approved.label', 'Released By / Noted By');

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);


    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $paramstr = "select
                  'PDFM' as print,
                  'c2' as radioaticompany,
                  '' as prepared,
                  '' as checked,
                  '' as approved,
                  '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }
  // qwe @123qwE123
  public function report_default_query($trno)
  {
    $now = $this->othersClass->getCurrentDate();
    $query = "select head.trno,date(ifnull(hinfo.printdate,'" . $now . "')) as dateid, 
                    head.docno, client.client, client.clientname, head.address,head.tax,head.vattype,
                    head.terms,head.rem, item.barcode,concat(case when ifnull(info.itemdesc,'') <>'' 
                    then info.itemdesc else item.itemname end,if(stock.ref='',' ***','')) as itemname,
                    info.specs, stock.rem  as srem,stock.rrqty as qty, stock.uom, stock.rrcost as netamt, 
                    stock.disc, stock.ext as ext,item.sizeid,client.bstyle,client.tin,stock.isreturn, 
                    stock.void, stock.line, '1' as type,sinfo.amt1,sinfo.amt2,sinfo.amt3,sinfo.amt4,
                    sinfo.amt5,info.unit,om.paymenttype as paymentname, 
                    concat(pr.docno,' / ',stock.ref,' / ',head.docno) as reference,sinfo.waivedqty,
                    '' as waived2,head.cur,sinfo.waivedspecs,info.ctrlno,
                    ifnull(cat.category,'') as categoryname,hinfo.categoryid
              from pohead as head left join postock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid = stock.itemid
              left join stockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
              left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
              left join headinfotrans as hinfo on hinfo.trno=head.trno
              left join othermaster as om on om.line = hinfo.paymentid
              left join hprhead as pr on pr.trno=stock.reqtrno
              left join reqcategory as cat on cat.line=hinfo.categoryid
              where head.doc='po' and head.trno='$trno' and stock.isreturn=0
              union all
              select head.trno,date(ifnull(hinfo.printdate,'" . $now . "')) as dateid, 
                    head.docno, client.client, client.clientname, head.address,head.tax,head.vattype, 
                    head.terms,head.rem, item.barcode,concat(case when ifnull(info.itemdesc,'') <>'' 
                    then info.itemdesc else item.itemname end,if(stock.ref='',' ***','')) as itemname,
                    info.specs,  stock.rem  as srem,stock.rrqty as qty, stock.uom, stock.rrcost as netamt, 
                    stock.disc, stock.ext as ext,item.sizeid,client.bstyle,client.tin,stock.isreturn, 
                    stock.void, stock.line, '1' as type,sinfo.amt1,sinfo.amt2,sinfo.amt3,sinfo.amt4,
                    sinfo.amt5,info.unit,om.paymenttype as paymentname, 
                    concat(pr.docno,' / ',stock.ref,' / ',head.docno) as reference,'' as waivedqty, 
                    info.waivedqty as waived2,head.cur,sinfo.waivedspecs,info.ctrlno,
                    ifnull(cat.category,'') as categoryname,hinfo.categoryid
              from hpohead as head left join hpostock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid = stock.itemid
              left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
              left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
              left join hheadinfotrans as hinfo on hinfo.trno=head.trno
              left join othermaster as om on om.line = hinfo.paymentid
              left join hprhead as pr on pr.trno=stock.reqtrno
              left join reqcategory as cat on cat.line=hinfo.categoryid
              where head.doc='po' and head.trno='$trno' and stock.isreturn=0
              union all
              select head.trno,date(ifnull(hinfo.printdate,'" . $now . "')) as dateid, head.docno, 
                    client.client, client.clientname, head.address,head.tax,head.vattype,
                    head.terms,head.rem, item.barcode,concat(case when ifnull(info.itemdesc,'') <>'' 
                    then info.itemdesc else item.itemname end,if(stock.isreturn=1,' (RETURN)',' (CANCELLED)')) as itemname,
                    info.specs,  stock.rem  as srem,(stock.rrqty * -1) as qty, stock.uom, 
                    stock.rrcost as netamt, stock.disc, (stock.ext * -1) as ext,
                    item.sizeid,client.bstyle,client.tin,stock.isreturn, stock.void, 
                    stock.line, '2' as type,
                    sinfo.amt1,sinfo.amt2,sinfo.amt3,sinfo.amt4,sinfo.amt5,info.unit,
                    om.paymenttype as paymentname, 
                    concat(pr.docno,' / ',stock.ref,' / ',head.docno) as reference,sinfo.waivedqty,
                    '' as waived2,head.cur,sinfo.waivedspecs,info.ctrlno,
                    ifnull(cat.category,'') as categoryname,hinfo.categoryid
              from pohead as head left join postock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid = stock.itemid
              left join stockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
              left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
              left join headinfotrans as hinfo on hinfo.trno=head.trno
              left join othermaster as om on om.line = hinfo.paymentid
              left join hprhead as pr on pr.trno=stock.reqtrno
              left join reqcategory as cat on cat.line=hinfo.categoryid
              where head.doc='po' and head.trno='$trno' and (stock.void=1 or stock.isreturn=1)
              union all
              select head.trno,date(ifnull(hinfo.printdate,'" . $now . "')) as dateid, head.docno, 
                    client.client, client.clientname, head.address,head.tax,head.vattype,
                    head.terms,head.rem, item.barcode,concat(case when ifnull(info.itemdesc,'') <>'' 
                    then info.itemdesc else item.itemname end,if(stock.isreturn=1,' (RETURN)',' (CANCELLED)')) as itemname,
                    info.specs,  stock.rem as srem,(stock.rrqty * -1) as qty, stock.uom, stock.rrcost as netamt, 
                    stock.disc, (stock.ext * -1) as ext,item.sizeid,client.bstyle,client.tin,
                    stock.isreturn, stock.void, stock.line, '2' as type,sinfo.amt1,sinfo.amt2,
                    sinfo.amt3,sinfo.amt4,sinfo.amt5,info.unit,om.paymenttype as paymentname, 
                    concat(pr.docno,' / ',stock.ref,' / ',head.docno) as reference,'' as waivedqty,
                    info.waivedqty as waived2,head.cur,'' as waivedspecs,info.ctrlno,
                    ifnull(cat.category,'') as categoryname,hinfo.categoryid
              from hpohead as head left join hpostock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join item on item.itemid = stock.itemid
              left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line
              left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
              left join hheadinfotrans as hinfo on hinfo.trno=head.trno
              left join othermaster as om on om.line = hinfo.paymentid
              left join hprhead as pr on pr.trno=stock.reqtrno
              left join reqcategory as cat on cat.line=hinfo.categoryid
              where head.doc='po' and head.trno='$trno' and (stock.void=1 or stock.isreturn=1)
              order by ctrlno";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($config, $data)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');
    $radioaticompany = $config['params']['dataparams']['radioaticompany'];
    switch ($radioaticompany) {
      case 'c0':
        //old layout -> Wag iremove please HUHU ---- 2024.09.16 [KIM]
        // return $this->superfab_po_PDF($config, $data);

        //new layout -> requested by Mr. Sean
        return $this->superfabNEW_po_PDF($config, $data);
        break;
      case 'c1':
        return $this->tgraf_po_PDF($config, $data);
        break;
      case 'c2':
        return $this->ati_po_PDF($config, $data);
        break;
      case 'c3':
        return $this->dvi_po_PDF($config, $data);
        break;
      case 'c4':
        return $this->default_PO_PDF($config, $data);
        break;
    }
  }

  public function default_header($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename, '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '150', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, '12', 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, '12', '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '140', null, false, $border, 'B', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->title_header($params);
    return $str;
  }

  public function title_header($params)
  {
    $companyid = $params['params']['companyid'];
    $border = "1px solid ";
    $font =  "Century Gothic";
    $str = "";

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '475', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function default_po_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
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
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '475', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'], $this->companysetup->getdecimal('price', $params['params'])), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];
      $str .= $this->reporter->endrow();

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
    $str .= $this->reporter->col('ITEM(S)', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '440', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
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
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_PO_header_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
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

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
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

  public function default_PO_PDF($params, $data)
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
    $this->default_PO_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    $countarr = 0;

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $barcode = $data[$i]['barcode'];
      $itemname = trim($data[$i]['itemname'] . ' ' . $data[$i]['srem']);
      if ($companyid == 19) {
        $qty = round($data[$i]['qty'], 2);
      } else {
        $qty = number_format($data[$i]['qty'], 2);
      }

      $waived = $data[$i]['waivedqty'];
      $waived2 = $data[$i]['waived2'];
      if ($waived == 1 || $waived2 == 1) {
        $uom = "";
      } else {
        $uom = $data[$i]['uom'];
      }

      $netamt = number_format($data[$i]['netamt'], 2);
      $disc = $data[$i]['disc'];
      $ext = number_format($data[$i]['ext'], 2);

      $arr_barcode = $this->reporter->fixcolumn([$barcode], '13', 0);
      $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '8', 0);
      $arr_uom = $this->reporter->fixcolumn([$uom], '7', 0);
      $arr_amt = $this->reporter->fixcolumn([$netamt], '13', 0);
      $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
      $arr_ext = $this->reporter->fixcolumn([$ext], '15', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_uom, $arr_amt, $arr_disc, $arr_ext]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' . (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50, 15, ' ' . (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(200, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ' . (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
      }

      $totalext += $data[$i]['ext'];

      if (PDF::getY() > 900) {
        $this->default_PO_header_PDF($params, $data);
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
    PDF::MultiCell(560, 0, $data[0]['reference'], '', 'L');

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

  ////OLD LAYOUT [start] -- wag iremove
  public function superfab_po_header_PDF($config, $data) // old layout
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 16;
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

    $date = $data[0]['dateid'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '585', '100'); //565,108

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(550, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '70',  '100'); //65,108

    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '70', '125'); //65,130
    PDF::MultiCell(130, 0, isset($data[0]['bstyle']) ? $data[0]['bstyle']  : '', '', 'L', false, 1, '350', '125'); //350,130
    PDF::MultiCell(150, 0, $data[0]['terms'] . ' - ' . $data[0]['paymentname'], '', 'L', false, 1, '585', '125'); //565,130
    PDF::MultiCell(600, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '70', '150'); //65,155
  }

  public function superfab_po_PDF($config, $data) // old layout
  {
    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 13;
    $totalext = 0;
    $totalext1 = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    $lessamt = 0;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->superfab_po_header_PDF($config, $data);
    PDF::SetFont($font, '', 6);
    PDF::MultiCell(700, 0, '', '', '', false, 1, '', '205');

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
              union select sum(ext) as ext from hpostock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];
    $countarr = 0;
    $newpageadd = 1;
    $arrTotal = [];

    if (!empty($data)) {
      $totalperpage = 0;
      for ($i = 0; $i < count($data); $i++) {
        $itemtoprint = trim($data[$i]['itemname']) . ' ';
        if ($data[$i]['waivedspecs'] == 0) {
          if (trim($data[$i]['specs']) != '')
            $itemtoprint .=  '[' . trim($data[$i]['specs']) . ']';
        }

        if (trim($data[$i]['srem']) != '') $itemtoprint .=  '[' . trim($data[$i]['srem']) . ']';
        $arritem = [$itemtoprint];

        $arr_item = $this->reporter->fixcolumn($arritem, '95', 0);
        //
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 2)], '20', 0);
        $arr_netamt = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['netamt'], 2)], '20', 0);
        $waived = $data[$i]['waivedqty'];
        $waived2 = $data[$i]['waived2'];
        if ($waived == 1 || $waived2 == 1) {
          $arr_uom = "";
        } else {
          $arr_uom = $this->reporter->fixcolumn([$data[$i]['uom']], '10', 0);
        }
        $arr_ext = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['ext'], 2)], '20', 0);
        $maxrow = $this->othersClass->getmaxcolumn([$arr_item, $arr_qty, $arr_netamt, $arr_ext, $arr_uom]);
        $totalperpage += $data[$i]['ext'];

        for ($r = 0; $r < $maxrow; $r++) {
          $item = isset($arr_item[$r]) ? $arr_item[$r] : '';
          $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
          $uom = isset($arr_uom[$r]) ? $arr_uom[$r] : '';
          $amt = isset($arr_netamt[$r]) ? $arr_netamt[$r] : '';
          $ext = isset($arr_ext[$r]) ? $arr_ext[$r] : '';
          $item = isset($arr_item[$r]) ? $arr_item[$r] : '';

          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(90, 0, $qty, '', 'R', false, 0, '-30', '', false, 1);
          PDF::MultiCell(90, 0, $uom, '', 'L', false, 0, '65', '', false, 1);
          PDF::MultiCell(300, 0, $item, '', 'L', false, 0, '135', '', false, 1);
          PDF::MultiCell(90, 0, $amt, '', 'R', false, 0, '580', '', false, 1); //540
          PDF::MultiCell(90, 0, $ext, '', 'R', false, 1, '690', '', false, 1); //640

          if (PDF::getY() >= 580) { //600
            array_push($arrTotal, $totalperpage);
            $totalperpage = 0;
            if ($newpageadd == 1) {
              $this->superfab_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
            }
            $this->superfab_po_header_PDF($config, $data);
            PDF::SetFont($fontbold, '', $fontsize);
            PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '680');
            PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '600', '680');
            PDF::SetFont($font, '', 6);
            PDF::MultiCell(700, 0, '', '', '', false, 1, '', '205');
            $newpageadd += 1;
          } else {
            if ($i >= count($data) - 1 && $newpageadd <> 1) {
              PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '615');
              for ($k = 0; $k < count($arrTotal); $k++) {
                PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', '');
              }
              PDF::MultiCell(700, 0, 'Page ' . (count($arrTotal) + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($totalperpage, 2), '', '', false, 1, '580', '');
            }
          }
        }

        if ($data[$i]['ext'] > 0) {
          $totalext += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        } else {
          $lessamt += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        }
      }

      if ($newpageadd == 1) {
        $this->superfab_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
      }
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function superfab_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $vattable = '0.00';
    $vatamt = '0.00';
    $vatex = '0.00';
    $zerorated = '0.00';

    if (isset($data[0]['vattype'])) {
      if ($data[0]['vattype']) {
        switch ($data[0]['vattype']) {
          case 'Vat-registered':
            $vattable = number_format(($grandtotal + $lessamt) / 1.12, 2);
            $vatamt = number_format((($grandtotal + $lessamt) / 1.12) * 0.12, 2);
            break;

          case 'Non-vat':
            $vatex = number_format($grandtotal + $lessamt, 2);
            break;

          default:
            $zerorated = number_format($grandtotal + $lessamt, 2);
            break;
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, 'Vatable Sales', '', 'L', false, 0, '250', '630'); //250,620
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vattable, '', 'R', false, 0, '350', '630'); //350,620
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '600');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'VAT Amount', '', 'L', false, 0, '250', '653'); //250,643
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatamt, '', 'R', false, 0, '350', '653'); //350,643
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '610');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, 'VAT Exempt Sales', '', 'L', false, 0, '250', '675'); //250,670
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatex, '', 'R', false, 0, '350', '675'); //350,670
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '620');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Zero Rated', '', 'L', false, 0, '250', '698'); //250,694
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $zerorated, '', 'R', false, 0, '350', '700'); //350,694

    PDF::MultiCell(355, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'R', false, 0, '', '');
    PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '585', '700'); //650,694

    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    if ($data[0]['reference'] == '') {
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '55', '750');
    } else {
      PDF::MultiCell(0, 0, $data[0]['reference'], '', 'L', false, 0, '55', '750');
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '55', '770');
    }

    PDF::MultiCell(175, 15, $config['params']['dataparams']['prepared'], '', 'C', false, 0, '-7', '880'); //-5,860
    PDF::MultiCell(175, 15, $config['params']['dataparams']['checked'], '', 'C', false, 0, '310', '880'); //268,860
    if (str_contains($config['params']['dataparams']['approved'], 'PATRICIA CO')) {
      PDF::Image('public/images/reports/ATIsignature.jpg', '600', '785', 200, 155);
    } elseif (str_contains($config['params']['dataparams']['approved'], 'JOCELYN VILLAGRACIA')) {
      PDF::Image('public/images/reports/ATIsignatureJocelyn.png', '600', '785', 200, 155);
    } elseif (str_contains($config['params']['dataparams']['approved'], 'ANGEL LYN BARCOS')) {
      PDF::Image('public/images/reports/ATIsignatureAngel.png', '600', '785', 200, 155);
    }
    PDF::MultiCell(175, 15, $config['params']['dataparams']['approved'], '', 'C', false, 1, '625', ''); //573
  }

  //OLD LAYOUT [end] -- wag iremove

  //////
  //new layout [start]
  public function superfabNEW_po_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 16;
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

    $date = isset($data[0]['dateid']) ? $data[0]['dateid'] : '';
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '570', '70'); //570, 70

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(550, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '70',  '175'); //70, 175

    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '70', '195'); //70, 195
    PDF::MultiCell(180, 0, $data[0]['terms'] . ' - ' . $data[0]['paymentname'], '', 'L', false, 1, '615', '260'); //615,260
    PDF::MultiCell(450, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '70', '215'); //70,215
  }

  public function superfabNEW_po_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = 780; //790
    $page = 770; //780
    $test = 0;
    $test2 = 0;

    $totalext = 0;
    $totalext1 = 0;
    $totalext2 = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetMargins(20, 20);
    $this->superfabNEW_po_header_PDF($config, $data);
    PDF::SetFont($font, '', 40);
    PDF::MultiCell(760, 0, '', '', '', false, 1, '', '285'); //285

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
              union select sum(ext) as ext from hpostock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $countarr = 0;
    $blnLess = true;
    $lessamt = 0;
    $newpageadd = 1;
    $arrTotal = [];
    $test = 0;

    if (!empty($data)) {

      $totalperpage = 0;
      for ($i = 0; $i < count($data); $i++) {

        if ($data[$i]['ext'] < 0) {
          if ($blnLess) {
            PDF::MultiCell(20, 0, "\n");
            PDF::MultiCell(20, 20, '', '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(100, 0, 'Less:', '', 'L', false, 1);
            $blnLess = true;
          }
        }

        $itemtoprint = trim($data[$i]['itemname']) . ' ';

        if ($data[$i]['waivedspecs'] == 0) {
          if (trim($data[$i]['specs']) != '')
            $itemtoprint .=  '[' . trim($data[$i]['specs']) . ']';
        }

        if (trim($data[$i]['srem']) != '') $itemtoprint .=  '[' . trim($data[$i]['srem']) . ']';
        $arritem = [$itemtoprint];

        if ($data[$i]['amt1'] != 0) array_push($arritem, '  Addon: Delivery Fee ' . number_format($data[$i]['amt1'], 2));
        if ($data[$i]['amt2'] != 0) array_push($arritem, '  Addon: Diagnostic Fee ' . number_format($data[$i]['amt2'], 2));
        if ($data[$i]['amt3'] != 0) array_push($arritem, '  Addon: Installation Fee ' . number_format($data[$i]['amt3'], 2));
        if ($data[$i]['amt4'] != 0) array_push($arritem, '  Addon: Consultation Fee ' . number_format($data[$i]['amt4'], 2));
        if ($data[$i]['amt5'] != 0) array_push($arritem, '  Addon: Misc. Fee ' . number_format($data[$i]['amt5'], 2));

        $arr_item = $this->reporter->fixcolumn($arritem, '35', 0);
        if ($data[$i]['barcode'] == '') {
          $arr_barcode = $this->reporter->fixcolumn([$data[$i]['unit']], '12', 0);
        } else {
          $arr_barcode = $this->reporter->fixcolumn([$data[$i]['barcode']], '12', 0);
        }
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 2)], '20', 0);
        $arr_netamt = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['netamt'], 2)], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['ext'], 2)], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_item, $arr_qty, $arr_netamt, $arr_ext]);

        if ($data[$i]['itemname'] == '') {
        } else {
          $totalperpage += $data[$i]['ext'];
          for ($r = 0; $r < $maxrow; $r++) {
            $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
            $barcode = isset($arr_barcode[$r]) ? $arr_barcode[$r] : '';
            $amt = isset($arr_netamt[$r]) ? $arr_netamt[$r] : '';
            $ext = isset($arr_ext[$r]) ? $arr_ext[$r] : '';
            $item = trim(isset($arr_item[$r])) ? $arr_item[$r] : '';
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(115, 20, $qty, '', 'R', false, 0, '-15', '', true, 1);
            PDF::MultiCell(115, 20, $barcode, '', 'L', false, 0, '130', '', false, 1);
            PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(115, 20, $amt, '', 'R', false, 0, '590', '', false, 1);
            PDF::MultiCell(115, 20, $ext, '', 'R', false, 1, '690', '', false, 1);

            if (PDF::getY() > 630) { //580

              array_push($arrTotal, $totalperpage);
              $totalperpage = 0;

              if (PDF::PageNo() == 1) {
                $this->superfabNEW_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                $this->superfabNEW_po_signatory_PDF($config, $data);
              } else {
                PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '600');
                for ($k = 0; $k < count($arrTotal); $k++) {
                  PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', '');
                }
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '690');
                PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '625', '690');

                $this->superfabNEW_po_signatory_PDF($config, $data);
              }

              $this->superfabNEW_po_header_PDF($config, $data);
              $newpageadd++;
              PDF::SetFont($font, '', 40);
              PDF::MultiCell(760, 0, '', '', '', false, 1, '', '285');
            } else {
              if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
                if (PDF::PageNo() == 1) {
                  $this->superfabNEW_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                  $this->superfabNEW_po_signatory_PDF($config, $data);
                } else {
                  array_push($arrTotal, $totalperpage);
                  PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '600'); //580,600
                  for ($k = 0; $k < count($arrTotal); $k++) {
                    PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', ''); //580
                  }
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '690');
                  PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '625', '690');

                  $this->superfabNEW_po_signatory_PDF($config, $data);
                }
              }
            }
          }
        }

        if ($data[$i]['ext'] > 0) {
          $totalext += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        } else {
          $lessamt += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        }
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function superfabNEW_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $vattable = '0.00';
    $vatamt = '0.00';
    $vatex = '0.00';
    $zerorated = '0.00';

    if (isset($data[0]['vattype'])) {
      if ($data[0]['vattype']) {
        switch ($data[0]['vattype']) {
          case 'Vat-registered':
            $vattable = number_format(($grandtotal + $lessamt) / 1.12, 2);
            $vatamt = number_format((($grandtotal + $lessamt) / 1.12) * 0.12, 2);
            break;

          case 'Non-vat':
            $vatex = number_format($grandtotal + $lessamt, 2);
            break;

          default:
            $zerorated = number_format($grandtotal + $lessamt, 2);
            break;
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'Vatable Sales', '', 'L', false, 0, 280, 632); //280,628
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vattable, '', 'R', false, 0, '400', '632');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '700', '600');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'VAT Amount', '', 'L', false, 0, '280', '647');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatamt, '', 'R', false, 0, '400', '647');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '700', '610');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'VAT Exempt Sales', '', 'L', false, 0, '280', '662');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatex, '', 'R', false, 0, '400', '662');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '700', '620');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'Zero Rated', '', 'L', false, 0, '280', '677');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $zerorated, '', 'R', false, 0, '400', '677');
    PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '625', '647');

    if ($lessamt != 0) {
      PDF::MultiCell(555, 0, '', '', 'C', false, 0);
      PDF::MultiCell(100, 0, 'Less: ', '', 'R', false, 0, '', '');
      PDF::MultiCell(175, 0, number_format(abs($lessamt), 2), '', 'R', false, 1, '710', '650');
      PDF::MultiCell(555, 0, '', '', 'C', false, 0);
      PDF::MultiCell(100, 0, 'Total Amount:', '', 'R', false, 0, '', '');
      PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($totalext + $lessamt, 2), '', 'R', false, 1, '710', '670');
    }

    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    if ($data[0]['reference'] == '') {
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '55', '740');
    } else {
      PDF::MultiCell(0, 0, $data[0]['reference'], '', 'L', false, 0, '55', '740');
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '55', '760');
    }

    // PDF::MultiCell(125, 15, $config['params']['dataparams']['prepared'], '', 'C', false, 0, '65', '910');

    // switch ($data[0]['categoryid']) {
    //   case 1: //Overhead
    //   case 5: //Office Supplies
    //   case 15: //(LII) Purchases
    //     if (str_contains($config['params']['dataparams']['checked'], 'RALPH CO')) {
    //       PDF::Image('public/images/reports/RalphCoSignature.png', '330', '810', 160, 115);
    //     }
    //     break;
    // }


    // PDF::MultiCell(125, 15, $config['params']['dataparams']['checked'], '', 'C', false, 0, '325', '');
    // if (str_contains($config['params']['dataparams']['approved'], 'PATRICIA CO')) {
    //   PDF::Image('public/images/reports/ATIsignature.jpg', '550', '810', 200, 155);
    // }
    // PDF::MultiCell(220, 15, $config['params']['dataparams']['approved'], '', 'C', false, 1, '540', '');
  }


  public function superfabNEW_po_signatory_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::MultiCell(125, 15, $config['params']['dataparams']['prepared'], '', 'C', false, 0, '65', '910');

    switch ($data[0]['categoryid']) {
      case 1: //Overhead
      case 5: //Office Supplies
      case 15: //(LII) Purchases
      case 45: //Forecast - SFAB-OH
      case 46: //Forecast - SFAB-Purchases
      case 47: //Forecast - PMT
        if (str_contains($config['params']['dataparams']['checked'], 'RALPH CO')) {
          PDF::Image('public/images/reports/RalphCoSignature.png', '330', '810', 160, 115);
        }
        break;
    }


    PDF::MultiCell(125, 15, $config['params']['dataparams']['checked'], '', 'C', false, 0, '325', '');
    if (str_contains($config['params']['dataparams']['approved'], 'PATRICIA CO')) {
      PDF::Image('public/images/reports/ATIsignature.jpg', '550', '810', 200, 155);
    }
    PDF::MultiCell(220, 15, $config['params']['dataparams']['approved'], '', 'C', false, 1, '540', '');
  }

  //new layout [end]
  ////////

  public function tgraf_po_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 16;
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

    $date = $data[0]['dateid'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '565', '55');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(550, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '60',  '150');

    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(180, 0, $data[0]['terms'] . ' - ' . $data[0]['paymentname'], '', 'L', false, 1, '615', '250');
    PDF::MultiCell(300, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '76', '190');
  }

  public function tgraf_po_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = $page = 13;
    $totalext = 0;
    $totalext1 = 0;
    $lessamt = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "15"; //16
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->tgraf_po_header_PDF($config, $data);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(700, 0, '', '', '', false, 1, '', '305'); //295

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
              union select sum(ext) as ext from hpostock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $countarr = 0;
    $newpageadd = 1;
    $arrTotal = [];

    if (!empty($data)) {
      $totalperpage = 0;
      for ($i = 0; $i < count($data); $i++) {
        $itemtoprint = trim($data[$i]['itemname']) . ' ';
        if ($data[$i]['waivedspecs'] == 0) {
          if (trim($data[$i]['specs']) != '')
            $itemtoprint .=  '[' . trim($data[$i]['specs']) . ']';
        }
        if (trim($data[$i]['srem']) != '') $itemtoprint .=  '[' . trim($data[$i]['srem']) . ']';
        $arritem = [$itemtoprint];
        $arr_item = $this->reporter->fixcolumn($arritem, '95', 0);
        if ($data[$i]['barcode'] == '') {
          $arr_barcode = $this->reporter->fixcolumn([$data[$i]['unit']], '16', 0);
        } else {
          $arr_barcode = $this->reporter->fixcolumn([$data[$i]['barcode']], '16', 0);
        }
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 2)], '10', 0);
        $arr_netamt = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['netamt'], 2)], '15', 0);
        $arr_ext = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['ext'], 2)], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_item, $arr_qty, $arr_netamt, $arr_ext]);

        if ($data[$i]['itemname'] == '') {
        } else {
          $totalperpage += $data[$i]['ext'];
          for ($r = 0; $r < $maxrow; $r++) {
            $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
            $barcode = isset($arr_barcode[$r]) ? $arr_barcode[$r] : '';
            $amt = isset($arr_netamt[$r]) ? $arr_netamt[$r] : '';
            $ext = isset($arr_ext[$r]) ? $arr_ext[$r] : '';
            $item = isset($arr_item[$r]) ? $arr_item[$r] : '';

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 20, '', '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(90, 20, $qty, '', 'R', false, 0, '25', '', false, 1);
            PDF::SetFont($font, '', 14);
            PDF::MultiCell(120, 20, $barcode, '', 'L', false, 0, '130', '', false, 1); //100
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '260', '', false, 1); //210
            PDF::MultiCell(120, 20, $amt, '', 'R', false, 0, '580', '', false, 1);
            PDF::MultiCell(120, 20, $ext, '', 'R', false, 1, '680', '', false, 1);

            // if (PDF::getY() >= 580) { //600
            //   array_push($arrTotal, $totalperpage);
            //   $totalperpage = 0;
            //   if ($newpageadd == 1) {
            //     $this->tgraf_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
            //   }
            //   $this->tgraf_po_signatory_PDF($config, $data);
            //   $this->tgraf_po_header_PDF($config, $data);
            //   PDF::SetFont($fontbold, '', $fontsize);
            //   PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '760');
            //   PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '620', '760');
            //   PDF::SetFont($font, '', 13);
            //   PDF::MultiCell(700, 0, '', '', '', false, 1, '', '305');

            //   $newpageadd += 1;
            // } else {
            //   if ($i >= count($data) - 1 && $newpageadd <> 1) {
            //     PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '660');
            //     for ($k = 0; $k < count($arrTotal); $k++) {
            //       PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', '');
            //     }
            //     PDF::MultiCell(700, 0, 'Page ' . (count($arrTotal) + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($totalperpage, 2), '', '', false, 1, '580', '');
            //   }
            // }


            if (PDF::getY() > 630) { //580

              array_push($arrTotal, $totalperpage);
              $totalperpage = 0;

              if (PDF::PageNo() == 1) {
                $this->tgraf_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                $this->tgraf_po_signatory_PDF($config, $data);
              } else {
                PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '600');
                for ($k = 0; $k < count($arrTotal); $k++) {
                  PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', '');
                }
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '760');
                PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '620', '760');

                $this->tgraf_po_signatory_PDF($config, $data);
              }

              $this->tgraf_po_header_PDF($config, $data);
              $newpageadd++;
              PDF::SetFont($font, '', 40);
              PDF::MultiCell(760, 0, '', '', '', false, 1, '', '285');
            } else {
              if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
                if (PDF::PageNo() == 1) {
                  $this->tgraf_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                  $this->tgraf_po_signatory_PDF($config, $data);
                } else {
                  array_push($arrTotal, $totalperpage);
                  PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '600');
                  for ($k = 0; $k < count($arrTotal); $k++) {
                    PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', ''); //580
                  }
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '760');
                  PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '620', '760');

                  $this->tgraf_po_signatory_PDF($config, $data);
                }
              }
            }
          }
        }

        if ($data[$i]['ext'] > 0) {
          $totalext += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        } else {
          $lessamt += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        }
      }
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function tgraf_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $vattable = '0.00';
    $vatamt = '0.00';
    $vatex = '0.00';
    $zerorated = '0.00';

    if (isset($data[0]['vattype'])) {
      if ($data[0]['vattype']) {
        switch ($data[0]['vattype']) {
          case 'Vat-registered':
            $vattable = number_format(($grandtotal + $lessamt) / 1.12, 2);
            $vatamt = number_format((($grandtotal + $lessamt) / 1.12) * 0.12, 2);
            break;

          case 'Non-vat':
            $vatex = number_format($grandtotal + $lessamt, 2);
            break;

          default:
            $zerorated = number_format($grandtotal + $lessamt, 2);
            break;
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, 'Vatable Sales', '', 'L', false, 0, '340', '675');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vattable, '', 'R', false, 0, '433', '675');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '600');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'VAT Amount', '', 'L', false, 0, '340', '690');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatamt, '', 'R', false, 0, '433', '690');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '610');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, 'VAT Exempt Sales', '', 'L', false, 0, '340', '708');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatex, '', 'R', false, 0, '433', '708');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '620');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Zero Rated', '', 'L', false, 0, '340', '726');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $zerorated, '', 'R', false, 0, '433', '726');
    PDF::MultiCell(355, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'R', false, 0, '', '');
    PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '620', '703');

    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    if ($data[0]['reference'] == '') {
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '35', '805');
    } else {
      PDF::MultiCell(0, 0, $data[0]['reference'], '', 'L', false, 0, '35', '805');
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '35', '825');
    }
  }

  public function tgraf_po_signatory_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::MultiCell(175, 15, $config['params']['dataparams']['prepared'], '', 'C', false, 0, '80', '940');

    switch ($data[0]['categoryid']) {
      case 1: //Overhead
      case 5: //Office Supplies
      case 15: //(LII) Purchases
      case 45: //Forecast - SFAB-OH
      case 46: //Forecast - SFAB-Purchases
      case 47: //Forecast - PMT
        if (str_contains($config['params']['dataparams']['checked'], 'RALPH CO')) {
          PDF::Image('public/images/reports/RalphCoSignature.png', '370', '830', 160, 115);
        }
        break;
    }

    PDF::MultiCell(175, 15, $config['params']['dataparams']['checked'], '', 'C', false, 0, '335', '');
    if (str_contains($config['params']['dataparams']['approved'], 'PATRICIA CO')) {
      PDF::Image('public/images/reports/ATIsignature.jpg', '550', '824', 200, 155);
    }
    PDF::MultiCell(175, 15, $config['params']['dataparams']['approved'], '', 'C', false, 1, '578', '');
  }

  ////////

  public function ati_po_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 16;
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

    $date = isset($data[0]['dateid']) ? $data[0]['dateid'] : '';
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '570', '70'); //570, 70

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(550, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '70',  '175'); //70, 175

    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '70', '195'); //70, 195
    PDF::MultiCell(180, 0, $data[0]['terms'] . ' - ' . $data[0]['paymentname'], '', 'L', false, 1, '615', '260'); //615,260
    PDF::MultiCell(450, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '70', '215'); //70,215
  }

  public function ati_po_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = 780; //790
    $page = 770; //780
    $test = 0;
    $test2 = 0;

    $totalext = 0;
    $totalext1 = 0;
    $totalext2 = 0;


    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetMargins(20, 20);
    $this->ati_po_header_PDF($config, $data);
    PDF::SetFont($font, '', 40);
    PDF::MultiCell(760, 0, '', '', '', false, 1, '', '285'); //285

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
              union select sum(ext) as ext from hpostock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $countarr = 0;
    $blnLess = true;
    $lessamt = 0;
    $newpageadd = 1;
    $arrTotal = [];
    $test = 0;

    if (!empty($data)) {

      $totalperpage = 0;
      for ($i = 0; $i < count($data); $i++) {

        if ($data[$i]['ext'] < 0) {
          if ($blnLess) {
            PDF::MultiCell(20, 0, "\n");
            PDF::MultiCell(20, 20, '', '', 'L', false, 0, '', '', true, 1);
            PDF::MultiCell(100, 0, 'Less:', '', 'L', false, 1);
            $blnLess = true;
          }
        }

        $itemtoprint = trim($data[$i]['itemname']) . ' ';

        if ($data[$i]['waivedspecs'] == 0) {
          if (trim($data[$i]['specs']) != '')
            $itemtoprint .=  '[' . trim($data[$i]['specs']) . ']';
        }

        if (trim($data[$i]['srem']) != '') $itemtoprint .=  '[' . trim($data[$i]['srem']) . ']';
        $arritem = [$itemtoprint];

        if ($data[$i]['amt1'] != 0) array_push($arritem, '  Addon: Delivery Fee ' . number_format($data[$i]['amt1'], 2));
        if ($data[$i]['amt2'] != 0) array_push($arritem, '  Addon: Diagnostic Fee ' . number_format($data[$i]['amt2'], 2));
        if ($data[$i]['amt3'] != 0) array_push($arritem, '  Addon: Installation Fee ' . number_format($data[$i]['amt3'], 2));
        if ($data[$i]['amt4'] != 0) array_push($arritem, '  Addon: Consultation Fee ' . number_format($data[$i]['amt4'], 2));
        if ($data[$i]['amt5'] != 0) array_push($arritem, '  Addon: Misc. Fee ' . number_format($data[$i]['amt5'], 2));

        $arr_item = $this->reporter->fixcolumn($arritem, '35', 0);
        if ($data[$i]['barcode'] == '') {
          $arr_barcode = $this->reporter->fixcolumn([$data[$i]['unit']], '12', 0);
        } else {
          $arr_barcode = $this->reporter->fixcolumn([$data[$i]['barcode']], '12', 0);
        }
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 2)], '20', 0);
        $arr_netamt = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['netamt'], 2)], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['ext'], 2)], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_item, $arr_qty, $arr_netamt, $arr_ext]);

        if ($data[$i]['itemname'] == '') {
        } else {
          $totalperpage += $data[$i]['ext'];
          for ($r = 0; $r < $maxrow; $r++) {
            $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
            $barcode = isset($arr_barcode[$r]) ? $arr_barcode[$r] : '';
            $amt = isset($arr_netamt[$r]) ? $arr_netamt[$r] : '';
            $ext = isset($arr_ext[$r]) ? $arr_ext[$r] : '';
            $item = trim(isset($arr_item[$r])) ? $arr_item[$r] : '';
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(115, 20, $qty, '', 'R', false, 0, '-15', '', true, 1);
            PDF::MultiCell(115, 20, $barcode, '', 'L', false, 0, '130', '', false, 1);
            PDF::MultiCell(300, 20, $item, '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(115, 20, $amt, '', 'R', false, 0, '590', '', false, 1);
            PDF::MultiCell(115, 20, $ext, '', 'R', false, 1, '690', '', false, 1);

            if (PDF::getY() > 630) { //580

              array_push($arrTotal, $totalperpage);
              $totalperpage = 0;

              if (PDF::PageNo() == 1) {
                $this->ati_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                $this->ati_po_signatory_PDF($config, $data);
              } else {
                PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '600');
                for ($k = 0; $k < count($arrTotal); $k++) {
                  PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', '');
                }
                PDF::SetFont($fontbold, '', $fontsize);
                PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '690');
                PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '625', '690');

                $this->ati_po_signatory_PDF($config, $data);
              }

              $this->ati_po_header_PDF($config, $data);
              $newpageadd++;
              PDF::SetFont($font, '', 40);
              PDF::MultiCell(760, 0, '', '', '', false, 1, '', '285');
            } else {
              if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
                if (PDF::PageNo() == 1) {
                  $this->ati_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                  $this->ati_po_signatory_PDF($config, $data);
                } else {
                  array_push($arrTotal, $totalperpage);
                  PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '600');
                  for ($k = 0; $k < count($arrTotal); $k++) {
                    PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', ''); //580
                  }
                  PDF::SetFont($fontbold, '', $fontsize);
                  PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '500', '690');
                  PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '625', '690');

                  $this->ati_po_signatory_PDF($config, $data);
                }
              }
            }
          }
        }

        if ($data[$i]['ext'] > 0) {
          $totalext += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        } else {
          $lessamt += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        }
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function ati_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $vattable = '0.00';
    $vatamt = '0.00';
    $vatex = '0.00';
    $zerorated = '0.00';

    if (isset($data[0]['vattype'])) {
      if ($data[0]['vattype']) {
        switch ($data[0]['vattype']) {
          case 'Vat-registered':
            $vattable = number_format(($grandtotal + $lessamt) / 1.12, 2);
            $vatamt = number_format((($grandtotal + $lessamt) / 1.12) * 0.12, 2);
            break;

          case 'Non-vat':
            $vatex = number_format($grandtotal + $lessamt, 2);
            break;

          default:
            $zerorated = number_format($grandtotal + $lessamt, 2);
            break;
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'Vatable Sales', '', 'L', false, 0, 280, 632); //280,628
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vattable, '', 'R', false, 0, '400', '632');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '700', '600');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'VAT Amount', '', 'L', false, 0, '280', '647');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatamt, '', 'R', false, 0, '400', '647');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '700', '610');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'VAT Exempt Sales', '', 'L', false, 0, '280', '662');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatex, '', 'R', false, 0, '400', '662');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '700', '620');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, 'Zero Rated', '', 'L', false, 0, '280', '677');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $zerorated, '', 'R', false, 0, '400', '677');
    PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '625', '647');

    if ($lessamt != 0) {
      PDF::MultiCell(555, 0, '', '', 'C', false, 0);
      PDF::MultiCell(100, 0, 'Less: ', '', 'R', false, 0, '', '');
      PDF::MultiCell(175, 0, number_format(abs($lessamt), 2), '', 'R', false, 1, '710', '650');
      PDF::MultiCell(555, 0, '', '', 'C', false, 0);
      PDF::MultiCell(100, 0, 'Total Amount:', '', 'R', false, 0, '', '');
      PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($totalext + $lessamt, 2), '', 'R', false, 1, '710', '670');
    }

    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    if ($data[0]['reference'] == '') {
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '55', '740');
    } else {
      PDF::MultiCell(0, 0, $data[0]['reference'], '', 'L', false, 0, '55', '740');
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '55', '760');
    }
  }

  public function ati_po_signatory_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::MultiCell(125, 15, $config['params']['dataparams']['prepared'], '', 'C', false, 0, '65', '910');

    switch ($data[0]['categoryid']) {
      case 1: //Overhead
      case 5: //Office Supplies
      case 15: //(LII) Purchases
      case 45: //Forecast - SFAB-OH
      case 46: //Forecast - SFAB-Purchases
      case 47: //Forecast - PMT
        if (str_contains($config['params']['dataparams']['checked'], 'RALPH CO')) {
          PDF::Image('public/images/reports/RalphCoSignature.png', '330', '810', 160, 115);
        }
        break;
    }

    PDF::MultiCell(125, 15, $config['params']['dataparams']['checked'], '', 'C', false, 0, '325', '');
    if (str_contains($config['params']['dataparams']['approved'], 'PATRICIA CO')) {
      PDF::Image('public/images/reports/ATIsignature.jpg', '550', '810', 200, 155);
    }
    PDF::MultiCell(220, 15, $config['params']['dataparams']['approved'], '', 'C', false, 1, '540', '');
  }

  ////////

  public function dvi_po_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 16;
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

    $date = $data[0]['dateid'];
    $date = date_create($date);
    $date = date_format($date, "F d, Y");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '567', '65');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(550, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '80',  '170');

    PDF::SetFont($font, '', 40);
    PDF::MultiCell(700, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(190, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '80', '185');
    PDF::MultiCell(180, 0, $data[0]['terms'] . ' - ' . $data[0]['paymentname'], '', 'L', false, 1, '580', '245');
    PDF::MultiCell(300, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '80', '200');
  }

  public function dvi_po_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $count = 780; //790
    $page = 770; //780
    $totalext = 0;
    $lessamt = 0;
    $totalext1 = 0;
    $totalext2 = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "14";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->dvi_po_header_PDF($config, $data);
    PDF::SetFont($font, '', 8);
    PDF::MultiCell(700, 0, '', '', '', false, 1, '', '285');

    $trno = $data[0]['trno'];
    $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
              union select sum(ext) as ext from hpostock where trno = $trno) as a ";

    $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

    $grandtotal = $totresult[0]['ext'];

    $countarr = 0;
    $newpageadd = 1;
    $arrTotal = [];

    if (!empty($data)) {
      $totalperpage = 0;
      for ($i = 0; $i < count($data); $i++) {

        $itemtoprint = trim($data[$i]['itemname']) . ' ';

        if ($data[$i]['waivedspecs'] == 0) {
          if (trim($data[$i]['specs']) != '')
            $itemtoprint .=  '[' . trim($data[$i]['specs']) . ']';
        }

        if (trim($data[$i]['srem']) != '') $itemtoprint .=  '[' . trim($data[$i]['srem']) . ']';
        $arritem = [$itemtoprint];

        $arr_item = $this->reporter->fixcolumn($arritem, '45', 0);
        $arr_barcode = $this->reporter->fixcolumn([$data[$i]['barcode']], '12', 0);
        $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 2)], '20', 0);
        $arr_netamt = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['netamt'], 2)], '20', 0);
        $arr_ext = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['ext'], 2)], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_item, $arr_qty, $arr_netamt, $arr_ext]);

        if ($data[$i]['itemname'] == '') {
        } else {
          $totalperpage += $data[$i]['ext'];
          for ($r = 0; $r < $maxrow; $r++) {
            $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
            $barcode = isset($arr_barcode[$r]) ? $arr_barcode[$r] : '';
            $amt = isset($arr_netamt[$r]) ? $arr_netamt[$r] : '';
            $ext = isset($arr_ext[$r]) ? $arr_ext[$r] : '';
            $item = isset($arr_item[$r]) ? $arr_item[$r] : '';

            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(115, 0, $qty, '', 'R', false, 0, '30', '', true, 1);
            PDF::MultiCell(115, 0, $barcode, '', 'L', false, 0, '160', '', false, 1);
            PDF::MultiCell(300, 0, $item, '', 'L', false, 0, '260', '', false, 1); #390
            PDF::MultiCell(115, 0, $amt, '', 'R', false, 0, '540', '', false, 1); #90
            PDF::MultiCell(115, 0, $ext, '', 'R', false, 1, '638', '', false, 1); #90

            if (PDF::getY() >= 540) { //520

              array_push($arrTotal, $totalperpage);
              $totalperpage = 0;
              if (PDF::PageNo() == 1) {
                $this->dvi_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                $this->dvi_po_signatory_PDF($config, $data);
              }

              $this->dvi_po_header_PDF($config, $data);
              $newpageadd++;

              PDF::SetFont($fontbold, '', $fontsize);
              PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '460', '640');
              PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '570', '640');
              $this->dvi_po_signatory_PDF($config, $data);

              PDF::SetFont($font, '', 40);
              PDF::MultiCell(760, 0, '', '', '', false, 1, '', '285');
            } else {
              if (($i + 1) == count($data) && ($r + 1) == $maxrow) {
                if (PDF::PageNo() == 1) {
                  $this->dvi_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
                  $this->dvi_po_signatory_PDF($config, $data);
                } else {
                  array_push($arrTotal, $totalperpage);
                  PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '640'); //580,600
                  for ($k = 0; $k < count($arrTotal); $k++) {
                    PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', ''); //580
                  }
                  $this->dvi_po_signatory_PDF($config, $data);
                }
              }
            }
          }
        }
        if ($data[$i]['ext'] > 0) {
          $totalext += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        } else {
          $lessamt += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
        }
      }
    }

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function dvi_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $vattable = '0.00';
    $vatamt = '0.00';
    $vatex = '0.00';
    $zerorated = '0.00';

    if (isset($data[0]['vattype'])) {
      if ($data[0]['vattype']) {
        switch ($data[0]['vattype']) {
          case 'Vat-registered':
            $vattable = number_format(($grandtotal + $lessamt) / 1.12, 2);
            $vatamt = number_format((($grandtotal + $lessamt) / 1.12) * 0.12, 2);
            break;

          case 'Non-vat':
            $vatex = number_format($grandtotal + $lessamt, 2);
            break;

          default:
            $zerorated = number_format($grandtotal + $lessamt, 2);
            break;
        }
      }
    }

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, 'Vatable Sales', '', 'L', false, 0, '280', '540');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vattable, '', 'R', false, 0, '415', '540');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '600');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'VAT Amount', '', 'L', false, 0, '280', '560');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatamt, '', 'R', false, 0, '415', '560');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '610');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, 'VAT Exempt Sales', '', 'L', false, 0, '280', '580');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatex, '', 'R', false, 0, '415', '580');
    PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '620');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, 'Zero Rated', '', 'L', false, 0, '280', '600');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $zerorated, '', 'R', false, 0, '415', '600');


    PDF::MultiCell(355, 0, '', '', 'C', false, 0);
    PDF::MultiCell(100, 0, '', '', 'R', false, 0, '', '');
    PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '600', '585');

    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    if ($data[0]['reference'] == '') {
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '80', '645');
    } else {
      PDF::MultiCell(0, 0, $data[0]['reference'], '', 'L', false, 0, '80', '645');
      PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '80', '665');
    }
  }

  public function dvi_po_signatory_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $font = "";
    $count = 890;
    $page = 880;
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "16";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::MultiCell(175, 15, $config['params']['dataparams']['prepared'], '', 'C', false, 0, '100', '810'); //795

    switch ($data[0]['categoryid']) {
      case 1: //Overhead
      case 5: //Office Supplies
      case 15: //(LII) Purchases
      case 45: //Forecast - SFAB-OH
      case 46: //Forecast - SFAB-Purchases
      case 47: //Forecast - PMT
        if (str_contains($config['params']['dataparams']['checked'], 'RALPH CO')) {
          PDF::Image('public/images/reports/RalphCoSignature.png', '360', '715', 160, 115);
        }
        break;
    }

    PDF::MultiCell(175, 15, $config['params']['dataparams']['checked'], '', 'C', false, 0, '325', '');

    if (str_contains($config['params']['dataparams']['approved'], 'PATRICIA CO')) {
      PDF::Image('public/images/reports/ATIsignature.jpg', '515', '715', 200, 155);
    }
    PDF::MultiCell(175, 15, $config['params']['dataparams']['approved'], '', 'C', false, 1, '540', '');
  }

  #========================= old DVI
  // public function dvi_po_header_PDF($config, $data)
  // {
  //   $center = $config['params']['center'];
  //   $username = $config['params']['user'];

  //   $qry = "select name,address,tel from center where code = '" . $center . "'";
  //   $headerdata = $this->coreFunctions->opentable($qry);
  //   $current_timestamp = $this->othersClass->getCurrentTimeStamp();

  //   $font = "";
  //   $fontbold = "";
  //   $fontsize = 16;
  //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
  //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
  //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
  //   }

  //   PDF::SetTitle($this->modulename);
  //   PDF::SetAuthor('Solutionbase Corp.');
  //   PDF::SetCreator('Solutionbase Corp.');
  //   PDF::SetSubject($this->modulename . ' Module Report');
  //   PDF::setPageUnit('px');
  //   PDF::SetMargins(20, 20);
  //   PDF::AddPage('p', [800, 1000]);

  //   PDF::SetFont($fontbold, '', $fontsize);

  //   $date = $data[0]['dateid'];
  //   $date = date_create($date);
  //   $date = date_format($date, "F d, Y");


  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(400, 0, isset($date) ? $date : '', '', 'L', false, 1, '567', '65');

  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(550, 0, isset($data[0]['clientname']) ? $data[0]['clientname'] : '', '', 'L', false, 1, '80',  '170');

  //   PDF::SetFont($font, '', 40);
  //   PDF::MultiCell(700, 0, '', '', 'L', false, 1);

  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(190, 0, isset($data[0]['tin']) ? $data[0]['tin'] : '', '', 'L', false, 1, '80', '185');
  //   PDF::MultiCell(180, 0, $data[0]['terms'] . ' - ' . $data[0]['paymentname'], '', 'L', false, 1, '580', '245');
  //   PDF::MultiCell(300, 0, isset($data[0]['address']) ? $data[0]['address'] : '', '', 'L', false, 1, '80', '200');
  // }

  // public function dvi_po_PDF($config, $data)
  // {
  //   $companyid = $config['params']['companyid'];
  //   $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
  //   $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
  //   $decimalprice = $this->companysetup->getdecimal('price', $config['params']);
  //   $center = $config['params']['center'];
  //   $username = $config['params']['user'];
  //   $count = $page = 13;
  //   $totalext = 0;
  //   $lessamt = 0;
  //   $totalext1 = 0;
  //   $totalext2 = 0;

  //   $font = "";
  //   $fontbold = "";
  //   $border = "1px solid ";
  //   $fontsize = "16";
  //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
  //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
  //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
  //   }
  //   $this->dvi_po_header_PDF($config, $data);
  //   PDF::SetFont($font, '', 8);
  //   PDF::MultiCell(700, 0, '', '', '', false, 1, '', '285');


  //   $trno = $data[0]['trno'];
  //   $total = "select sum(ext) as ext from (select sum(ext) as ext from postock where trno = $trno
  //             union select sum(ext) as ext from hpostock where trno = $trno) as a ";

  //   $totresult = json_decode(json_encode($this->coreFunctions->opentable($total)), true);

  //   $grandtotal = $totresult[0]['ext'];

  //   $countarr = 0;
  //   $newpageadd = 1;
  //   $arrTotal = [];


  //   if (!empty($data)) {
  //     $totalperpage = 0;
  //     for ($i = 0; $i < count($data); $i++) {

  //       $itemtoprint = trim($data[$i]['itemname']) . ' ';

  //       if ($data[$i]['waivedspecs'] == 0) {
  //         if (trim($data[$i]['specs']) != '')
  //           $itemtoprint .=  '[' . trim($data[$i]['specs']) . ']';
  //       }

  //       if (trim($data[$i]['srem']) != '') $itemtoprint .=  '[' . trim($data[$i]['srem']) . ']';
  //       $arritem = [$itemtoprint];

  //       $arr_item = $this->reporter->fixcolumn($arritem, '70', 0);
  //       $arr_barcode = $this->reporter->fixcolumn([$data[$i]['barcode']], '12', 0);
  //       $arr_qty = $this->reporter->fixcolumn([number_format($data[$i]['qty'], 2)], '20', 0);
  //       $arr_netamt = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['netamt'], 2)], '20', 0);
  //       $arr_ext = $this->reporter->fixcolumn([$data[$i]['cur'] . ' ' . number_format($data[$i]['ext'], 2)], '20', 0);

  //       $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_item, $arr_qty, $arr_netamt, $arr_ext]);

  //       if ($data[$i]['itemname'] == '') {
  //       } else {
  //         $totalperpage += $data[$i]['ext'];
  //         for ($r = 0; $r < $maxrow; $r++) {
  //           $qty = isset($arr_qty[$r]) ? $arr_qty[$r] : '';
  //           $barcode = isset($arr_barcode[$r]) ? $arr_barcode[$r] : '';
  //           $amt = isset($arr_netamt[$r]) ? $arr_netamt[$r] : '';
  //           $ext = isset($arr_ext[$r]) ? $arr_ext[$r] : '';
  //           $item = isset($arr_item[$r]) ? $arr_item[$r] : '';

  //           PDF::SetFont($font, '', $fontsize);
  //           PDF::MultiCell(100, 23, '', '', 'L', false, 0, '', '', true, 1);
  //           PDF::MultiCell(90, 0, $qty, '', 'R', false, 0, '55', '', false, 1);
  //           PDF::MultiCell(110, 0, $barcode, '', 'L', false, 0, '160', '', false, 1);
  //           PDF::MultiCell(330, 0, $item, '', 'L', false, 0, '250', '', false, 1); #390
  //           PDF::MultiCell(120, 0, $amt, '', 'R', false, 0, '538', '', false, 1); #90
  //           PDF::MultiCell(120, 0, $ext, '', 'R', false, 1, '630', '', false, 1); #90


  //           if (PDF::getY() >= 520) { //520

  //             array_push($arrTotal, $totalperpage);
  //             $totalperpage = 0;

  //             if ($newpageadd == 1) {
  //               $this->dvi_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
  //             }

  //             $this->dvi_po_header_PDF($config, $data);
  //             PDF::SetFont($fontbold, '', $fontsize);
  //             PDF::MultiCell(175, 0, 'Grand Total: ', '', 'R', false, 1, '460', '640');
  //             PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '570', '640');
  //             PDF::SetFont($font, '', 8);
  //             PDF::MultiCell(
  //               700,
  //               0,
  //               '',
  //               '',
  //               '',
  //               false,
  //               1,
  //               '',
  //               '285'
  //             );
  //             $newpageadd += 1;
  //           } else {
  //             if ($i >= count($data) - 1 && $newpageadd <> 1) {
  //               PDF::MultiCell(700, 0, '', '', '', false, 1, '580', '520');
  //               for ($k = 0; $k < count($arrTotal); $k++) {
  //                 PDF::MultiCell(700, 0, 'Page ' . ($k + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($arrTotal[$k], 2), '', '', false, 1, '580', '');
  //               }
  //               PDF::MultiCell(700, 0, 'Page ' . (count($arrTotal) + 1) . ': ' . $data[0]['cur'] . ' ' . number_format($totalperpage, 2), '', '', false, 1, '580', '');
  //             }
  //           }
  //         }
  //       }
  //       if ($data[$i]['ext'] > 0) {
  //         $totalext += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
  //       } else {
  //         $lessamt += ($data[$i]['ext'] + $data[$i]['amt1'] + $data[$i]['amt2'] + $data[$i]['amt3'] + $data[$i]['amt4'] + $data[$i]['amt5']);
  //       }
  //     }

  //     if ($newpageadd == 1) {
  //       $this->dvi_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal);
  //     }
  //   }


  //   return PDF::Output($this->modulename . '.pdf', 'S');
  // }


  // public function dvi_po_footer2_PDF($config, $data, $totalext, $totalext1, $lessamt, $grandtotal)
  // {
  //   $center = $config['params']['center'];
  //   $username = $config['params']['user'];
  //   $font = "";
  //   $count = 890;
  //   $page = 880;
  //   $fontbold = "";
  //   $border = "1px solid ";
  //   $fontsize = "16";
  //   if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
  //     $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
  //     $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
  //   }

  //   $vattable = '0.00';
  //   $vatamt = '0.00';
  //   $vatex = '0.00';
  //   $zerorated = '0.00';

  //   if (isset($data[0]['vattype'])) {
  //     if ($data[0]['vattype']) {
  //       switch ($data[0]['vattype']) {
  //         case 'Vat-registered':
  //           $vattable = number_format(($grandtotal + $lessamt) / 1.12, 2);
  //           $vatamt = number_format((($grandtotal + $lessamt) / 1.12) * 0.12, 2);
  //           break;

  //         case 'Non-vat':
  //           $vatex = number_format($grandtotal + $lessamt, 2);
  //           break;

  //         default:
  //           $zerorated = number_format($grandtotal + $lessamt, 2);
  //           break;
  //       }
  //     }
  //   }

  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(150, 0, 'Vatable Sales', '', 'L', false, 0, '280', '540');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vattable, '', 'R', false, 0, '415', '540');
  //   PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '600');

  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(100, 0, 'VAT Amount', '', 'L', false, 0, '280', '560');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatamt, '', 'R', false, 0, '415', '560');
  //   PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '610');

  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(150, 0, 'VAT Exempt Sales', '', 'L', false, 0, '280', '580');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $vatex, '', 'R', false, 0, '415', '580');
  //   PDF::MultiCell(75, 0, '', '', 'R', false, 1, '710', '620');

  //   PDF::SetFont($font, '', $fontsize);
  //   PDF::MultiCell(100, 0, 'Zero Rated', '', 'L', false, 0, '280', '600');
  //   PDF::SetFont($fontbold, '', $fontsize);
  //   PDF::MultiCell(150, 0, $data[0]['cur'] . ' ' . $zerorated, '', 'R', false, 0, '415', '600');


  //   PDF::MultiCell(355, 0, '', '', 'C', false, 0);
  //   PDF::MultiCell(100, 0, '', '', 'R', false, 0, '', '');
  //   PDF::MultiCell(175, 0, $data[0]['cur'] . ' ' . number_format($grandtotal, 2), '', 'R', false, 1, '600', '585');

  //   PDF::MultiCell(20, 0, '', '', 'L', false, 0);
  //   if ($data[0]['reference'] == '') {
  //     PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '80', '645');
  //   } else {
  //     PDF::MultiCell(0, 0, $data[0]['reference'], '', 'L', false, 0, '80', '645');
  //     PDF::MultiCell(0, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L', false, 0, '80', '665');
  //   }

  //   PDF::MultiCell(175, 15, $config['params']['dataparams']['prepared'], '', 'C', false, 0, '100', '795');
  //   PDF::MultiCell(175, 15, $config['params']['dataparams']['checked'], '', 'C', false, 0, '325', '');

  //   if (str_contains($config['params']['dataparams']['approved'], 'PATRICIA CO')) {
  //     PDF::Image('public/images/reports/ATIsignature.jpg', '515', '700', 200, 155);
  //   }
  //   PDF::MultiCell(175, 15, $config['params']['dataparams']['approved'], '', 'C', false, 1, '540', '');
  // }
  #=========================

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

  ////////
}
