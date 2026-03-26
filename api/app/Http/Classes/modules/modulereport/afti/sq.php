<?php

namespace App\Http\Classes\modules\modulereport\afti;

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

class sq
{
  private $modulename = "Sales Order";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;
  private $logger;

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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'prepared.type', 'lookup');
    data_set($col1, 'prepared.action', 'lookuppreparedby');
    data_set($col1, 'prepared.lookupclass', 'prepared');
    data_set($col1, 'prepared.readonly', true);

    data_set($col1, 'approved.type', 'lookup');
    data_set($col1, 'approved.action', 'lookuppreparedby');
    data_set($col1, 'approved.lookupclass', 'approved');
    data_set($col1, 'approved.readonly', true);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($trno)
  {

    $query = "select cat.cat_name as bstyle, qshead.trno, head.docno,left(head.dateid,10) as dateid,qshead.client,qshead.clientname,qshead.address,qshead.due,qshead.terms,
    qsstock.line,qsstock.isqty,qsstock.iss,qsstock.iss,qsstock.amt,qsstock.ext,qsstock.uom,qsstock.rem,item.itemname,
    qshead.termsdetails,
    infotab.inspo,qshead.deldate, infotab.ispartial,infotab.instructions, infotab.period,
    infotab.isvalid,
    infotab.leadfrom as headleadfrom,infotab.leadto as headleadto,infotab.leaddur as stockleaddur,infotab.advised,
    concat(infotab.leadfrom,' - ',infotab.leadto,' , ',infotab.leaddur) as headleadtime, infotab.taxdef,
    stockinfo.rem as inforem, iteminfo.itemdescription, iteminfo.accessories, 
    agent.clientname as agentname,agent.tel as agtel, if(qshead.cur = 'P', 'PHP',qshead.cur) as cur,
    qsstock.isamt as gross, qsstock.disc,
    qsstock.isqty as qty, brands.brand_desc, qshead.vattype, qshead.docno as qsdocno,
    qshead.yourref, cust.tin, cust.addr, cust.tel, qshead.industry,
    concat(conbill.fname,' ',conbill.mname,' ',conbill.lname) as billcontact,
    concat(conship.fname,' ',conship.mname,' ',conship.lname, ' / ', conship.contactno, ' / ', conship.email) as shipcontact,
    conbill.contactno as billcontactno,
    concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress,
    concat(ship.addrline1,' ',ship.addrline2,' ',ship.city,' ',ship.province,' ',ship.country,' ',ship.zipcode) as shippingaddress,
    conbill.email as billemail,stockinfo.leaddur as itemleadtime,left(qshead.dateid,10) as qtdate,
    bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince,
    bill.country as bcountry,ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as bzipcode, 
    ship.province as sprovince, ship.country as scountry, branch.clientname as branchname,cust.groupid
    from sqhead as head
    left join hqshead as qshead on qshead.sotrno=head.trno
    left join hqsstock as qsstock on qsstock.trno=qshead.trno
    left join item on item.itemid=qsstock.itemid
    left join hheadinfotrans as infotab on infotab.trno = qshead.trno
    left join hstockinfotrans as stockinfo on stockinfo.line = qsstock.line and stockinfo.trno = qsstock.trno
    left join iteminfo on iteminfo.itemid = item.itemid
    left join frontend_ebrands as brands on brands.brandid = item.brand
    left join client as cust on cust.client = qshead.client
    left join client as agent on agent.client= qshead.agent
    left join contactperson as conbill on conbill.line=qshead.billcontactid
    left join contactperson as conship on conship.line=qshead.shipcontactid
    left join billingaddr as bill on bill.line = qshead.billid and bill.clientid = cust.clientid
    left join billingaddr as ship on ship.line = qshead.shipid and ship.clientid = cust.clientid
    left join category_masterfile as cat on cat.cat_id=cust.category
    left join client as branch on branch.clientid = qshead.branch
    where head.trno='$trno' and head.doc='SQ' and qsstock.noprint = 0
    union all
    select cat.cat_name as bstyle, qshead.trno, head.docno,left(head.dateid,10) as dateid,qshead.client,qshead.clientname,qshead.address,qshead.due,qshead.terms,
    qsstock.line,qsstock.isqty,qsstock.iss,qsstock.iss,qsstock.amt,qsstock.ext,qsstock.uom,qsstock.rem,item.itemname,
    qshead.termsdetails, 
    infotab.inspo,qshead.deldate, infotab.ispartial,infotab.instructions, infotab.period,
    infotab.isvalid,
    infotab.leadfrom as headleadfrom,infotab.leadto as headleadto,infotab.leaddur as stockleaddur,infotab.advised,
    concat(infotab.leadfrom,' - ',infotab.leadto,' , ',infotab.leaddur) as headleadtime, infotab.taxdef,
    stockinfo.rem as inforem, iteminfo.itemdescription, iteminfo.accessories, 
    agent.clientname as agentname,agent.tel as agtel, if(qshead.cur = 'P', 'PHP',qshead.cur) as cur,
    qsstock.isamt as gross, qsstock.disc,
    qsstock.isqty as qty, brands.brand_desc, qshead.vattype, qshead.docno as qsdocno,
    qshead.yourref, cust.tin, cust.addr, cust.tel, qshead.industry,
    concat(conbill.fname,' ',conbill.mname,' ',conbill.lname) as billcontact,
    concat(conship.fname,' ',conship.mname,' ',conship.lname, ' / ', conship.contactno, ' / ', conship.email) as shipcontact,
    conbill.contactno as billcontactno,
    concat(bill.addrline1,' ',bill.addrline2,' ',bill.city,' ',bill.province,' ',bill.country,' ',bill.zipcode) as billingaddress,
    concat(ship.addrline1,' ',ship.addrline2,' ',ship.city,' ',ship.province,' ',ship.country,' ',ship.zipcode) as shippingaddress,
    conbill.email as billemail,stockinfo.leaddur as itemleadtime,left(qshead.dateid,10) as qtdate,
    bill.addrline1 as baddrline1, bill.addrline2 as baddrline2, bill.city as bcity, bill.zipcode as bzipcode, bill.province as bprovince,
    bill.country as bcountry,ship.addrline1 as saddrline1, ship.addrline2 as saddrline2, ship.city as scity, ship.zipcode as bzipcode, 
    ship.province as sprovince, ship.country as scountry, branch.clientname as branchname,cust.groupid
    from hsqhead as head
    left join hqshead as qshead on qshead.sotrno=head.trno
    left join hqsstock as qsstock on qsstock.trno=qshead.trno
    left join item on item.itemid=qsstock.itemid
    left join hheadinfotrans as infotab on infotab.trno = qshead.trno
    left join hstockinfotrans as stockinfo on stockinfo.line = qsstock.line and stockinfo.trno = qsstock.trno
    left join iteminfo on iteminfo.itemid = item.itemid
    left join frontend_ebrands as brands on brands.brandid = item.brand    
    left join client as cust on cust.client = qshead.client
    left join client as agent on agent.client= qshead.agent
    left join contactperson as conbill on conbill.line=qshead.billcontactid
    left join contactperson as conship on conship.line=qshead.shipcontactid
    left join billingaddr as bill on bill.line = qshead.billid and bill.clientid = cust.clientid
    left join billingaddr as ship on ship.line = qshead.shipid and ship.clientid = cust.clientid
    left join category_masterfile as cat on cat.cat_id=cust.category
    left join client as branch on branch.clientid = qshead.branch
    where head.trno='$trno' and head.doc='SQ' and qsstock.noprint = 0
    order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_sq_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->reportquoteplottingpdf($params, $data);
    }
  }

  public function default_header($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
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
    $str .= $this->reporter->col('SALES ORDER', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, '13', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '160', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '500', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['terms']) ? $data[0]['terms'] : ''), '150', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '400', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');

    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');

    return $str;
  }

  public function default_sq_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font = "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['isqty'], $this->companysetup->getdecimal('isqty', $params['params'])), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');

      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
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

    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '300', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_sq_header_PDF($params, $data)
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
      $font = TCPDF_FONTS::addTTFfont(database_path() . 'public/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . 'public/images/fonts/GOTHICB.TTF');
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
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

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
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
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

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(300, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "UNIT PRICE", '', 'C', false, 0);
    PDF::MultiCell(100, 0, "TOTAL", '', 'C', false);

    PDF::MultiCell(700, 0, '', 'B');
  }

  public function default_sq_PDF($params, $data)
  {
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
      $font = TCPDF_FONTS::addTTFfont(database_path() . 'public/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . 'public/images/fonts/GOTHICB.TTF');
    }
    $this->default_sq_header_PDF($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
      PDF::MultiCell(100, 0, number_format($data[$i]['isqty'], $decimalqty), '', 'C', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 0, $data[$i]['uom'], '', 'C', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(300, 0, $data[$i]['itemname'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 0, number_format($data[$i]['amt'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 0, number_format($data[$i]['ext'], $decimalprice), '', 'R', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 0, '', '', 'L', 0, 1, '', '', true, 0, false, false);
      $totalext += $data[$i]['ext'];

      if (intVal($i) + 1 == $page) {
        $this->default_sq_header_PDF($params, $data);
        $page += $count;
      }
    }

    PDF::MultiCell(700, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', '', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($totalext, 2), '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
  //PDF
  public function default_quote_headerpdf($params, $data, $font)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['companyid'];

    $qry = "select name,concat(address,' ',zipcode) as address,tel,tin,email from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\sales\sq')->modulename;

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $fontsize9 = "9";
    $fontsize11 = "9";
    $fontsize12 = "10";
    $fontsize13 = '10';
    $fontsize14 = "11";
    $border = "1px solid ";

    $terms = (str_split(trim($data[0]['termsdetails']), 15));

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::Image($this->companysetup->getlogopath($params['params']) . 'qslogo.png', '', '', 310, 80);
    PDF::MultiCell(0, 20, "\n");
    PDF::SetFont($font, 'B', 18, $border);
    PDF::MultiCell(320, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
    PDF::MultiCell(265, 0, 'QUOTATION', '', 'C', 0, 0, '', '', false, 0, false, false, 0);
    PDF::MultiCell(0, 30, "\n");

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(245, 15, '', '', 'R', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Quotation No.',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), $border, 'L', false);

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(245, 15, '', '', 'R', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Quotation Date',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . date("F d, Y", strtotime($data[0]['dateid'])), $border, 'L', false);

    $inspo = (isset($data[0]['inspo']) ? $data[0]['inspo'] : '');
    $arrinspo = $this->reporter->fixcolumn([$inspo], 30, 0);
    $cinspo = count($arrinspo);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)
    if ($cinspo != 1) {
      $h = 0;
    } else {
      $h = 15;
    }
    for ($r = 0; $r < $cinspo; $r++) {
      if ($r == 0) {
        $lbl = "INQ Ref No.";
      } else {
        $lbl = "";
      }
      PDF::SetFont($font, 'B', $fontsize11);
      PDF::MultiCell(245, 15, '', '', 'R', false, 0);
      PDF::MultiCell(75, 15, '', '', 'L', false, 0);
      PDF::SetFillColor(211, 211, 211);
      PDF::MultiCell(100, 15, ' ' . $lbl,  'LR', 'L', 1, 0);
      PDF::SetFont($font, '', $fontsize11);
      PDF::MultiCell(155, 15, ' ' . isset($arrinspo[$r]) ? ' ' . $arrinspo[$r] : '', 'LR', 'L', false);
    }

    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(245, 15, '', '', 'R', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Payment Terms',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . (isset($terms[0]) ? $terms[0] : ''), $border, 'L', false);

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(245, 15, $headerdata[0]->name, '', 'L', false, 0);
    PDF::MultiCell(75, 15, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(100, 15, ' ' . 'Page No.',  '1', 'L', 1, 0);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(155, 15, ' ' . 'Page    ' . PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), $border, 'L', false);

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(320, 15, $headerdata[0]->address, '', 'L', false, 0);
    PDF::MultiCell(50, 15, '', '', 'L', false, 0);
    PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
    PDF::MultiCell(155, 15, '', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(320, 15, $headerdata[0]->tel, '', 'L', false, 0);
    PDF::MultiCell(50, 15, '', '', 'L', false, 0);
    PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
    PDF::MultiCell(155, 15, '', '', 'L', false);

    PDF::SetFont($font, '', $fontsize13);
    PDF::MultiCell(320, 15, 'Email: ' . $headerdata[0]->email, '', 'L', false, 0);
    PDF::MultiCell(50, 15, '', '', 'L', false, 0);
    PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
    PDF::MultiCell(155, 15, '', '', 'L', false);

    if ($companyid == 10) {
      PDF::SetFont($font, '', $fontsize13);
      PDF::MultiCell(320, 15, 'VAT REG TIN: ' . $headerdata[0]->tin, '', 'L', false, 0);
      PDF::MultiCell(50, 15, '', '', 'L', false, 0);
      PDF::MultiCell(50, 15, '',  '', 'L', false, 0);
      PDF::MultiCell(165, 15, '', '', 'L', false);
    }

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(280, 0, '', 'TLR', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(280, 0, '', 'TLR', 'L', false);

    $groupid = "";
    if ($data[0]['groupid'] != "") {
      $groupid = " - " . $data[0]['groupid'];
    }


    $clientname = PDF::GetStringHeight(200, $data[0]['clientname'] . $groupid);
    $billcontact = PDF::GetStringHeight(200, $data[0]['billcontact']);
    $max_heights = max($clientname, $billcontact);

    PDF::SetFont($font, 'B', $fontsize13);
    PDF::MultiCell(5, $max_heights, '', 'L', 'L', false, 0);
    PDF::MultiCell(275, $max_heights, (isset($data[0]['clientname']) ? $data[0]['clientname'] . $groupid : ''), 'R', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(10, $max_heights, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(5, $max_heights, '', 'L', 'L', false, 0);
    PDF::MultiCell(75, $max_heights, 'Contact Name: ',  '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize11);
    PDF::MultiCell(200, $max_heights, (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ''), 'R', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    $arrphone = array();
    $countarrphone = 0;

    $branch = '<b>Branch:</b> ' . (isset($data[0]['branchname']) ? $data[0]['branchname'] : '');
    $billcon = '<b>Phone:</b> ' . (isset($data[0]['billcontactno']) ? $data[0]['billcontactno'] : '');
    $bstyle = '<b>Bus Style:</b> ' . (isset($data[0]['bstyle']) ? $data[0]['bstyle'] : '');
    $tin = '<b>TIN:</b> ' . (isset($data[0]['tin']) ? $data[0]['tin'] : '');
    $mobile = '<b>Mobile #:</b> ' . (isset($data[0]['bcmobile']) ? $data[0]['bcmobile'] : '');
    $billemail = '<b>Email Address:</b> ' . (isset($data[0]['billemail']) ? $data[0]['billemail'] : '');

    $arrbstyle = $this->reporter->fixcolumn([$branch, $bstyle, $tin, $data[0]['baddrline1'], $data[0]['baddrline2'], $data[0]['bcity'] . ' ' . $data[0]['bzipcode'], $data[0]['bprovince'], $data[0]['bcountry']], 35, 0);
    $cbstyle = count($arrbstyle);

    $arrbillcon = $this->reporter->fixcolumn([$billcon, $mobile, $billemail], 35, 0);
    $cbillcon = count($arrbillcon);

    $maxrow = max($cbstyle, $cbillcon);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', $fontsize11);
      PDF::MultiCell(5, 10, '', 'L', 'L', false, 0);
      PDF::MultiCell(275, 10, (isset($arrbstyle[$r]) ? $arrbstyle[$r] : ''), 'R', 'L', false, 0, '', '', true, 0, true);
      PDF::SetFont($font, '', $fontsize11);
      PDF::MultiCell(10, 10, '', '', 'L', false, 0);

      PDF::SetFont($font, 'B', $fontsize12);
      PDF::MultiCell(5, 10, '', 'L', 'L', false, 0);
      PDF::SetFont($font, '', $fontsize11);
      PDF::MultiCell(275, 10, (isset($arrbillcon[$r]) ? $arrbillcon[$r] : ''), 'R', 'L', false, 1, '', '', true, 0, true);
    }

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(280, 0, '', 'LRB', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(280, 0, '', 'LRB', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(25, 10, 'No.', '1', 'C', 1, 0);
    PDF::MultiCell(70, 10, 'Order Code', '1', 'C', 1, 0);
    PDF::MultiCell(70, 10, 'Mfr', '1', 'C', 1, 0);
    PDF::MultiCell(160, 10, 'Description', '1', 'C', 1, 0);
    PDF::MultiCell(60, 10, 'Quantity', '1', 'C', 1, 0);
    PDF::MultiCell(90, 10, 'Unit Price', '1', 'C', 1, 0);
    PDF::MultiCell(100, 10, 'Line Total', '1', 'C', 1, 0);
  }

  //PDF
  public function reportquoteplottingpdf($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 750;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;
    $amount = 0;

    $fontsize9 = "9";
    $fontsize11 = "9";
    $fontsize12 = "9";
    $fontsize13 = '10';
    $fontsize14 = "10";
    $fontsize15 = "11";

    $font = '';

    $this->default_quote_headerpdf($params, $data, $font);

    $arritemname = array();
    $countarr = 0;

    $arrordercode = array();
    $countarrcode = 0;

    $arrmfr = array();
    $countarrmfr = 0;

    $arrqty = array();
    $countarrqty = 0;

    $arrprice = array();
    $countarrprice = 0;

    $arrlinetotal = array();
    $countarrlinetotal = 0;

    $totalctr = 0;

    $leadtime = '';

    PDF::MultiCell(0, 0, "");
    PDF::MultiCell(0, 0, "");

    $newpageadd = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $inforem = '';
        $arrleaditem = [];

        $unitprice = $this->othersClass->Discount($data[$i]['gross'], $data[$i]['disc']);
        $linetotal = $data[$i]['qty'] * $unitprice;

        if ($unitprice == 0) {
          $unitprice = 0;
        }

        if ($linetotal == 0) {
          $linetotal = 0;
        }

        $arrqty = (str_split(trim(intval($data[$i]['qty']) . ' ' . $data[$i]['uom']) . ' ', 10));
        $countarrqty = count($arrqty);

        $arrprice = (str_split(trim($data[0]['cur'] . ' ' . number_format($unitprice, $decimalprice)) . ' ', 18));
        $countarrprice = count($arrprice);

        $arrlinetotal = (str_split(trim($data[0]['cur'] . ' ' . number_format($linetotal, 2)) . ' ', 18));
        $countarrlinetotal = count($arrlinetotal);

        $itemcode = $data[$i]['itemname'];
        $itembrand = $data[$i]['brand_desc'];
        $itemdescription = $data[$i]['itemdescription'];
        $itemaccessories = $data[$i]['accessories'];
        $iteminfo = $data[$i]['inforem'];
        $itemleadtime = $data[$i]['itemleadtime'];


        $arrordercode = $this->reporter->fixcolumn([$itemcode], '11', 1);
        $countarrcode = count($arrordercode);

        $arrmfr = $this->reporter->fixcolumn([$itembrand], '11', 1);
        $countarrmfr = count($arrmfr);

        $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $itemaccessories, $iteminfo, $arrleaditem], '28', 1);
        $countarrcol = count($itemcoldes);

        $maxrow = 1;
        $maxrow = max($countarrcol, $countarrcode, $countarrmfr, $countarrqty, $countarrprice, $countarrlinetotal); // get max count

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $inum = $i + 1;
            } else {
              $inum = '';
            }
            // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(25, 0, $inum, 'LR', 'C', false, 0, '', '', true, 1, false, true, 0, 'B', true);
            PDF::MultiCell(70, 0, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, false, true, 0, 'B', true);
            PDF::MultiCell(70, 0, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, false, true, 0, 'B', true);
            PDF::MultiCell(160, 0, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', true, 1, true, true, 0, 'B', true);
            PDF::MultiCell(60, 0, isset($arrqty[$r]) ? $arrqty[$r] : '', 'LR', 'C', false, 0, '', '', true, 1, false, true, 0, 'B', true);
            PDF::MultiCell(90, 0, isset($arrprice[$r]) ? $arrprice[$r] : '', 'LR', 'R', false, 0, '', '', true, 1, false, true, 0, 'B', true);
            PDF::MultiCell(100, 0, isset($arrlinetotal[$r]) ? $arrlinetotal[$r] : '', 'LR', 'R', false, 1, '', '', true, 1, false, true, 0, 'B', true);

            if (PDF::getY() >= $page) {
              $newpageadd = 1;
              $this->addrow('LRB');
              $this->blankpage($params, $data, $font);
            }
          }
        }

        //1-w, 2-h, 3-txt, 4-border = 0, 5-align = 'J', 6-fill = 0, 7-ln = 1, 8-x = '', 9-y = '', 10-reseth = true, 11-stretch = 0, 12-ishtml = false, 13-autopadding = true, 14-maxh = 0

        if ($data[0]['vattype'] == 'VATABLE') {
          $vatsales = $vatsales + $linetotal;
          $totalext = $totalext + $linetotal;
        } else {
          $vatsales = 0;
          $totalext = $totalext + $linetotal;
        }
      }
    }

    if ($data[0]['vattype'] == 'VATABLE') {
      $vat = round($vatsales * .12,2);
      $amount = round($totalext + $vat,2);
    } else {
      $vat = 0;
      $amount = $totalext;
    }

    if (PDF::getY() > 610) {
      $this->addrow('LRB');
      $newpageadd = 1;
      $this->default_quote_headerpdf($params, $data, $font);
    }
    do {
      $this->addrow('LR');
    } while (PDF::getY() < 610);

    $vtot = $ztot = 0;

    if ($data[0]['vattype'] == 'VATABLE') {
      $vtot = $totalext;
    } else {
      $ztot = $totalext;
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(125, 15, '*QUOTATION VALIDITY: ', 'T', 'L', false, 0, 10, PDF::getY());
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(260, 15, '30 Day/s *', 'T', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(90, 15, 'Vat Sales',  'TLRB', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(25, 15, $data[0]['cur'], 'TB', 'L', false, 0);
    PDF::MultiCell(75, 15, number_format($vtot, 2), 'TBR', 'R', false);

    if ($data[0]['vattype'] == 'VATABLE' && $data[0]['taxdef'] != 0) {
      $vat = $data[0]['taxdef'];
      $amount = $totalext + $vat;
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(385, 15, '*STOCK SUBJECT TO PRIOR SALES* ', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(90, 15, '12% VAT',  'LRB', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(25, 15, $data[0]['cur'], 'B', 'L', false, 0);
    PDF::MultiCell(75, 15, number_format($vat, 2), 'BR', 'R', false);

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(385, 15, '*NON-CANCELLABLE AND NON-RETURNABLE* ', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(90, 15, 'VAT Exempt',  'LRB', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(25, 15, $data[0]['cur'], 'B', 'L', false, 0);
    PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);

    $leadtime = "";
    if ($data[0]['advised'] == 1) {
      $leadtime = 'To Be advised';
    } else {
      $leadtime = $data[0]['headleadtime'];
      if ($data[0]['headleadfrom'] == 0 && $data[0]['headleadto'] == 0) {
        $leadtime = "";
      }
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(80, 15, '*LEAD TIME: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(305, 15, $leadtime, '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(90, 15, 'Zero Rated',  'LRB', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(25, 15, $data[0]['cur'], 'B', 'L', false, 0);
    PDF::MultiCell(75, 15, number_format($ztot, 2), 'BR', 'R', false);

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(385, 15, '*Please review data specs and or item description*', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(90, 15, 'LESS: WTax',  'LRB', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(25, 15, $data[0]['cur'], 'B', 'L', false, 0);
    PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(385, 15, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(90, 15, 'Delivery Charge',  'LRB', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(25, 15, $data[0]['cur'], 'B', 'L', false, 0);
    PDF::MultiCell(75, 15, '0.00', 'BR', 'R', false);

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(385, 15, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(90, 15, 'Amount Due:',  'LBR', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(25, 15, $data[0]['cur'], 'B', 'L', false, 0);
    PDF::MultiCell(75, 15, number_format($amount, 2), 'BR', 'R', false);

    PDF::MultiCell(0, 15, "");

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 20, 'Contact Person: ', '', 'L', false, 0);
    PDF::MultiCell(125, 20, (isset($data[0]['agentname']) ? $data[0]['agentname'] : ''),  '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(350, 20, 'All Goods Returned by reasons of client`s fault will be charged 20% re-stocking fee of invoice value and shall bear all the costs of returning the goods.', '', 'L', false);
    PDF::MultiCell(0, 0, "");

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 20, 'Contact Number: ', '', 'L', false, 0);
    PDF::MultiCell(125, 20, (isset($data[0]['agtel']) ? $data[0]['agtel'] : ''),  '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(350, 20, 'All Goods Returned must be reported within 7 (seven days and returned within 15 (fifteen) days from date of delivery undamaged and in its original packaging together with a written incidence report.', '', 'L', false);

    PDF::AddPage();
    PDF::SetMargins(40, 20);
    // instruction
    PDF::MultiCell(0, 15, "\n");

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(250, 20, 'INSTRUCTION FORM (should be attached to PO) ', '', 'L', false, 0);
    PDF::MultiCell(100, 20, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize15);
    PDF::MultiCell(175, 20, ' ' . 'AFT#',  'TLRB', 'L', false);

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(250, 20, 'INVOICE TO: ', '', 'L', false, 0);
    PDF::MultiCell(100, 20, '', '', 'L', false, 0);
    PDF::MultiCell(175, 20, '',  '', 'L', false);

    $groupid = "";
    if ($data[0]['groupid'] != "") {
      $groupid = " - " . $data[0]['groupid'];
    }

    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(15, 0, '',  '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 15, 'Company Name: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(225, 20, (isset($data[0]['clientname']) ? $data[0]['clientname'] . $groupid : ''), 'B', 'C', false, 0);
    PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(75, 15, 'PO No.: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(100, 15, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false);

    $c = ceil(strlen((isset($data[0]['clientname']) ? $data[0]['clientname'] . $groupid : ' ')) / 45);
    for ($i = 0; $i < $c; $i++) {
      PDF::MultiCell(0, 0, "\n");
    }

    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(15, 15, '',  '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 15, 'Contact Name: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(225, 15, (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ''), 'B', 'C', false, 0);
    PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(75, 15, 'TIN: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(100, 15, (isset($data[0]['tin']) ? $data[0]['tin'] : ''), 'B', 'L', false);

    $c = ceil(strlen((isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ' ')) / 45);
    for ($i = 0; $i < $c; $i++) {
      PDF::MultiCell(0, 0, "\n");
    }

    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(15, 15, '',  '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 15, 'Address: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(225, 15, (isset($data[0]['billingaddress']) ? $data[0]['billingaddress'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize13);
    PDF::MultiCell(75, 15, 'Contact No.: ', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(100, 15, (isset($data[0]['billcontactno']) ? $data[0]['billcontactno'] : ''), 'B', 'L', false);

    $c = ceil(strlen((isset($data[0]['billingaddress']) ? $data[0]['billingaddress'] : ' ')) / 65);
    for ($i = 0; $i < $c; $i++) {
      PDF::MultiCell(0, 0, "\n");
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(115, 15, 'Industry / Vertical', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(225, 15, (isset($data[0]['industry']) ? $data[0]['industry'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(10, 15, '',  '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(175, 15, '', '', 'L', false);

    $c = ceil(strlen((isset($data[0]['industry']) ? $data[0]['industry'] : ' ')) / 45);
    for ($i = 0; $i < $c; $i++) {
      PDF::MultiCell(0, 0, "\n");
    }

    $trno = $params['params']['dataid'];
    $doc = $params['params']['doc'];

    $isposted = $this->othersClass->isposted2($trno, "transnum");
    $tbls = '';
    $qttbl = '';
    if ($isposted) {
      $tbl = 'h' . strtolower($doc) . 'head';
      $tbls = 'h' . strtolower($doc) . 'stock';
      $qttbl = 'hqtstock';
    } else {
      $tbl = strtolower($doc) . 'head';
      $tbls = strtolower($doc) . 'stock';
      $qttbl = 'qtstock';
    }

    $qttrno = $this->coreFunctions->datareader("select trno as value from hqshead where sotrno=?", [$trno]);

    $total = $this->coreFunctions->getfieldvalue("hqsstock", "sum(ext)", "trno=?", [$qttrno]);
    $tax = $this->coreFunctions->getfieldvalue("hqshead", "tax", "trno=?", [$qttrno]);

    if ($tax != 0) {
      $total = round($total * 1.12, 2);
    }

    $data2 = $this->coreFunctions->opentable("select ifnull(group_concat(docno separator '/ '),'') as docno,ifnull(group_concat(distinct ourref),'') as ourref,ifnull(sum(db),0) as db
        from (select head.crref as docno,head.ourref,sum(detail.cr) as db
        from lahead as head 
        left join ladetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ?  and coa.alias in ('AR5','PD1')
        group by head.crref,head.ourref
        union all
        select head.crref as docno,head.ourref,sum(detail.cr) as db from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ? and coa.alias in ('AR5','PD1')
        group by head.crref,head.ourref) as a group by ourref  ", [$qttrno, $qttrno]);

    $ewt = $this->coreFunctions->datareader("select ifnull(sum(value),0) as value from  (select ifnull(sum(detail.db - detail.cr),0) as value
        from lahead as head 
        left join ladetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ?  and coa.alias in ('WT2')
        union all
        select ifnull(sum(detail.db - detail.cr),0) as value
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join coa on coa.acnoid = detail.acnoid 
        where detail.qttrno = ? and coa.alias in ('WT2')) as a
        ", [$qttrno, $qttrno]);

    $fp = "";
    $bal = "";
    $cr = "";
    $ptype = "";

    if (!empty($data2)) {
      if ($data2[0]->docno != "") {
        $fp = number_format($data2[0]->db - $ewt, 2);
        $bal = number_format($total - (($data2[0]->db - floatval($ewt)) + floatval($ewt)), 2);
        $ewt = number_format(floatval($ewt), 2);
        $cr = $data2[0]->docno;
        $ptype = $data2[0]->ourref;
      }
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(115, 25, 'COLLECTION DETAILS', '', 'L', false, 0);
    PDF::MultiCell(100, 25, 'AMT', '', 'C', false, 0);
    PDF::MultiCell(25, 25, '',  '', 'L', false, 0);
    PDF::MultiCell(100, 25, '  ' . 'CR#', '', 'C', false, 0);
    PDF::MultiCell(175, 25, '', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    if ($ptype == "FULL") {
      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(115, 15, '', '', 'L', false, 0);
      PDF::MultiCell(25, 15, 'FP:', '', 'L', false, 0);
      PDF::MultiCell(90, 15, $fp, 'B', 'L', false, 0);
      PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
      PDF::MultiCell(90, 15, $cr, 'B', 'L', false, 0);
      PDF::MultiCell(185, 15, '', '', 'L', false);

      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(110, 15, '', '', 'L', false, 0);
      PDF::MultiCell(30, 15, 'EWT:', '', 'L', false, 0);
      PDF::MultiCell(90, 15, $ewt, 'B', 'L', false, 0);
      PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
      PDF::MultiCell(90, 15, '', '', 'L', false, 0);
      PDF::MultiCell(185, 15, '', '', 'L', false);

      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(115, 15, '', '', 'L', false, 0);
      PDF::MultiCell(25, 15, 'BAL:', '', 'L', false, 0);
      PDF::MultiCell(90, 15, $bal, 'B', 'L', false, 0);
      PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
      PDF::MultiCell(90, 15, '', '', 'L', false, 0);
      PDF::MultiCell(185, 15, '', '', 'L', false);
    } else {

      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(115, 15, '', '', 'L', false, 0);
      PDF::MultiCell(25, 15, 'DP:', '', 'L', false, 0);
      PDF::MultiCell(90, 15, $fp, 'B', 'L', false, 0);
      PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
      PDF::MultiCell(90, 15, $cr, 'B', 'L', false, 0);
      PDF::MultiCell(185, 15, '', '', 'L', false);

      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(110, 15, '', '', 'L', false, 0);
      PDF::MultiCell(30, 15, 'EWT:', '', 'L', false, 0);
      PDF::MultiCell(90, 15, $ewt, 'B', 'L', false, 0);
      PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
      PDF::MultiCell(90, 15, '', '', 'L', false, 0);
      PDF::MultiCell(185, 15, '', '', 'L', false);

      PDF::MultiCell(0, 0, "\n");

      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(115, 15, '', '', 'L', false, 0);
      PDF::MultiCell(25, 15, 'BAL:', '', 'L', false, 0);
      PDF::MultiCell(90, 15, $bal, 'B', 'L', false, 0);
      PDF::MultiCell(20, 15, '',  '', 'L', false, 0);
      PDF::MultiCell(90, 15, '', '', 'L', false, 0);
      PDF::MultiCell(185, 15, '', '', 'L', false);
    }


    PDF::MultiCell(0, 30, "\n");

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(20, 25, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::SetTextColor(127);
    PDF::MultiCell(405, 25, 'For new company with Credit Term, Provide accounting contact details', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize11);
    PDF::MultiCell(100, 25, '', '', 'L', false);

    PDF::SetTextColor(0, 0, 0, 100);

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(5, 25, '', '', 'L', false, 0);
    PDF::MultiCell(175, 25, 'Other instruction Collection : ', '', 'L', false, 0);
    PDF::MultiCell(245, 25, '', 'B', 'L', false, 0);
    PDF::MultiCell(100, 25, '', '', 'C', false);

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(180, 20, 'DELIVER TO:', '', 'L', false, 0);
    PDF::MultiCell(245, 20, '', '', 'L', false, 0);
    PDF::MultiCell(100, 20, '', '', 'C', false);

    $clientname = $this->reporter->fixcolumn([$data[0]['clientname']], '38', 0);
    $arrclientname = count($clientname);
    $maxrow = $arrclientname;

    for ($r = 0; $r < $maxrow; $r++) {
      if ($r == 0) {
        $company = 'Company Name:';
      } else {
        $company = '';
      }

      $border = '';
      if ($r == $maxrow - 1) {
        $border = 'B';
      }
      PDF::SetFont($font, 'B', $fontsize12);
      PDF::MultiCell(180, 15, $company, '', 'L', false, 0);
      PDF::SetFont($font, '', $fontsize12);
      PDF::MultiCell(245, 15,  isset($clientname[$r]) ? $clientname[$r] : '', $border, 'L', false, 0);
      PDF::SetFont($font, 'B', $fontsize12);
      PDF::MultiCell(100, 15, '', '', 'C', false);
    }


    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(180, 30, 'Contact Name / Number / Email:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(245, 30, (isset($data[0]['shipcontact']) ? $data[0]['shipcontact'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 30, '', '', 'C', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(180, 30, 'Address:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);

    PDF::MultiCell(245, 30, ("" . isset($data[0]['shippingaddress']) ? $data[0]['shippingaddress'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 30, '', '', 'C', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(0, 0, "\n");

    $ispartial = '';
    if ($data[0]['ispartial'] == 1) {
      $ispartial = 'yes';
    } else {
      $ispartial = 'no';
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(200, 15, 'PARTIAL DELIVERY ALLOWED:', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(100, 15, $ispartial, '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(100, 15, 'Creation Date: ', 'B', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(125, 15, date("F d, Y", strtotime($data[0]['dateid'])), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(200, 15, 'Submitted by: ', '', 'L', false);
    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(265, 15, $data[0]['agentname'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(525, 15, 'Other Delivery Instructions: ', '', 'L', false);

    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(525, 15, 'DDR : ' . date("F d,Y", strtotime($data[0]['deldate'])), '', 'L', false);
    PDF::MultiCell(400, 15, $data[0]['instructions'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n");


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function addrow($border)
  {
    PDF::MultiCell(25, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(70, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(70, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(160, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(60, 0, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(90, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(100, 0, '', $border, 'R', false, 1, '', '', false, 1);
  }


  public function blankpage($params, $data, $font)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode) as address,tel,tin,email from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\sales\qs')->modulename;

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    PDF::MultiCell(0, 0, "\n");
    $this->addrow('TLR');
  }
}
