<?php

namespace App\Http\Classes\modules\modulereport\afti;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

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

class su
{
  private $modulename = "Stock Issuance";
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

  public function createreportfilter($config)
  {
    $fields = ['radioreporttype', 'radiosjaftilogo', 'prepared', 'noted', 'approved', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.type', 'lookup');
    data_set($col1, 'approved.action', 'lookuppreparedby');
    data_set($col1, 'approved.lookupclass', 'approved');
    data_set($col1, 'approved.readonly', true);

    data_set($col1, 'noted.type', 'lookup');
    data_set($col1, 'noted.action', 'lookuppreparedby');
    data_set($col1, 'noted.lookupclass', 'noted');
    data_set($col1, 'noted.readonly', true);

    data_set($col1, 'prepared.type', 'lookup');
    data_set($col1, 'prepared.action', 'lookuppreparedby');
    data_set($col1, 'prepared.lookupclass', 'prepared');
    data_set($col1, 'prepared.readonly', true);

    data_set(
      $col1,
      'radioreporttype.options',
      [
        ['label' => 'Default', 'value' => '0', 'color' => 'blue'],
        ['label' => 'Packing List', 'value' => '1', 'color' => 'blue'],
        ['label' => 'Commericial Invoice', 'value' => '2', 'color' => 'blue']
      ]
    );

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {

    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '0' as reporttype,
      'wlogo' as radiosjaftilogo, 
      '' as approved,
      '' as noted,
      '' as prepared
      "
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $rtype = $config['params']['dataparams']['reporttype'];

    if ($rtype == 2) {
      return $this->default_CI_su_query($trno);
    } else {
      return $this->default_su_query($trno);
    }
  } //end fn

  public function default_CI_su_query($trno)
  {
    $query = "select stock.line,head.docno,head.trno, head.clientname, head.address, date(head.dateid) as dateid,  head.rem,
    item.barcode, item.itemname, stock.isqty as qty,
    stock.uom,ba.contact as bcontact,concat(ba.addrline1,' ',ba.addrline2,' ',ba.city,' ',ba.province,' ',ba.country,' ',ba.zipcode) as baddr,ba.contactno as bcontactno,ba.country as bcountry,ba.zipcode as bzip,ba.fax as bfax,
    sa.contact as scontact,sa.addr as saddr,sa.contactno as scontactno,sa.country as scountry,sa.zipcode as szip,sa.fax as sfax,brand.brand_desc as brand,ii.itemdescription,ii.accessories,stock.rem as srem,item.partno,
    ba.addrline1 as baddrline1, ba.addrline2 as baddrline2, ba.city as bcity, ba.zipcode as bzipcode, ba.province as bprovince, ba.country as bcountry,
    sa.addrline1 as saddrline1, sa.addrline2 as saddrline2, sa.city as scity, sa.zipcode as szipcode, sa.province as sprovince, sa.country as scountry, concat(cp.fname,' ',cp.mname,' ',cp.lname) as billcontact, concat(scp.fname,' ',scp.mname,' ',scp.lname) as shipcontact
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on stock.itemid=item.itemid
    left join client on client.client=head.client
    left join billingaddr as ba on ba.line=head.billid
    left join billingaddr as sa on sa.line=head.shipid
    left join frontend_ebrands as brand on brand.brandid=item.brand
    left join iteminfo as ii on ii.itemid=item.itemid
    left join contactperson as cp on cp.clientid = client.clientid and cp.line = head.billcontactid
    left join contactperson as scp on scp.clientid = client.clientid and scp.line = head.shipcontactid
    where head.trno='$trno'
    union all
    select stock.line,head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.rem,
    item.barcode, item.itemname, stock.isqty as qty,
    stock.uom,ba.contact as bcontact,concat(ba.addrline1,' ',ba.addrline2,' ',ba.city,' ',ba.province,' ',ba.country,' ',ba.zipcode) as baddr,ba.contactno as bcontactno,ba.country as bcountry,ba.zipcode as bzip,ba.fax as bfax,
    sa.contact as scontact,sa.addr as saddr,sa.contactno as scontactno,sa.country as scountry,sa.zipcode as szip,sa.fax as sfax,brand.brand_desc as brand,ii.itemdescription,ii.accessories,stock.rem as srem,item.partno,
    ba.addrline1 as baddrline1, ba.addrline2 as baddrline2, ba.city as bcity, ba.zipcode as bzipcode, ba.province as bprovince, ba.country as bcountry,
    sa.addrline1 as saddrline1, sa.addrline2 as saddrline2, sa.city as scity, sa.zipcode as sa.zipcode as szipcode, sa.province as sprovince, sa.country as scountry, concat(cp.fname,' ',cp.mname,' ',cp.lname) as billcontact, concat(scp.fname,' ',scp.mname,' ',scp.lname) as shipcontact
    from (glhead as head
    left join glstock as stock on stock.trno=head.trno)
    left join item on stock.itemid=item.itemid
    left join client on client.clientid=head.clientid
    left join billingaddr as ba on ba.line=head.billid
    left join billingaddr as sa on sa.line=head.shipid
    left join frontend_ebrands as brand on brand.brandid=item.brand
    left join iteminfo as ii on ii.itemid=item.itemid
    where head.trno='$trno'
    order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $result;
  } //end fn

  public function default_su_query($trno)
  {
    $query = "select stock.line,head.docno,head.trno, head.clientname, head.address, date(head.dateid) as dateid,  head.rem,
    item.barcode, item.itemname, stock.isqty as qty,
    stock.uom,ba.contact as bcontact,concat(ba.addrline1,' ',ba.addrline2,' ',ba.city,' ',ba.province,' ',ba.country,' ',ba.zipcode) as baddr,ba.contactno as bcontactno,ba.country as bcountry,ba.zipcode as bzip,ba.fax as bfax,
    sa.contact as scontact,sa.addr as saddr,sa.contactno as scontactno,sa.country as scountry,sa.zipcode as szip,sa.fax as sfax,brand.brand_desc as brand,ii.itemdescription,ii.accessories,stock.rem as srem,item.partno,
    ba.addrline1 as baddrline1, ba.addrline2 as baddrline2, ba.city as bcity, ba.zipcode as bzipcode, ba.province as bprovince, ba.country as bcountry,
    sa.addrline1 as saddrline1, sa.addrline2 as saddrline2, sa.city as scity, sa.zipcode as szipcode, sa.province as sprovince, sa.country as scountry, concat(cp.fname,' ',cp.mname,' ',cp.lname) as billcontact, concat(scp.fname,' ',scp.mname,' ',scp.lname) as shipcontact
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on stock.itemid=item.itemid
    left join client on client.client=head.client
    left join billingaddr as ba on ba.line=head.billid
    left join billingaddr as sa on sa.line=head.shipid
    left join frontend_ebrands as brand on brand.brandid=item.brand
    left join iteminfo as ii on ii.itemid=item.itemid
    left join contactperson as cp on cp.clientid = client.clientid and cp.line = head.billcontactid
    left join contactperson as scp on scp.clientid = client.clientid and scp.line = head.shipcontactid
    where head.trno='$trno'
    union all
    select stock.line,head.docno, head.trno, head.clientname, head.address, date(head.dateid) as dateid, head.rem,
    item.barcode, item.itemname, stock.isqty as qty,
    stock.uom,ba.contact as bcontact,concat(ba.addrline1,' ',ba.addrline2,' ',ba.city,' ',ba.province,' ',ba.country,' ',ba.zipcode) as baddr,ba.contactno as bcontactno,ba.country as bcountry,ba.zipcode as bzip,ba.fax as bfax,
    sa.contact as scontact,sa.addr as saddr,sa.contactno as scontactno,sa.country as scountry,sa.zipcode as szip,sa.fax as sfax,brand.brand_desc as brand,ii.itemdescription,ii.accessories,stock.rem as srem,item.partno,
    ba.addrline1 as baddrline1, ba.addrline2 as baddrline2, ba.city as bcity, ba.zipcode as bzipcode, ba.province as bprovince, ba.country as bcountry,
    sa.addrline1 as saddrline1, sa.addrline2 as saddrline2, sa.city as scity, sa.zipcode as szipcode, sa.province as sprovince, sa.country as scountry, concat(cp.fname,' ',cp.mname,' ',cp.lname) as billcontact, concat(scp.fname,' ',scp.mname,' ',scp.lname) as shipcontact
    from (glhead as head
    left join glstock as stock on stock.trno=head.trno)
    left join item on stock.itemid=item.itemid
    left join client on client.clientid=head.clientid
    left join billingaddr as ba on ba.line=head.billid
    left join billingaddr as sa on sa.line=head.shipid
    left join frontend_ebrands as brand on brand.brandid=item.brand
    left join iteminfo as ii on ii.itemid=item.itemid
    left join contactperson as cp on cp.clientid = client.clientid and cp.line = head.billcontactid
    left join contactperson as scp on scp.clientid = client.clientid and scp.line = head.shipcontactid
    where head.trno='$trno'
    order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_su_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      switch ($params['params']['dataparams']['reporttype']) {
        case 0:
          return $this->SUPDF_LAYOUT($params, $data);
          break;
        case 1:
          return $this->PACKING_LAYOUT($params, $data);
          break;
        case 2:
          return $this->Commercial_Invoice_LAYOUT($params, $data);
          break;
      }
    }
  }

  public function SU_header_PDF($params, $data)
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

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)
    PDF::SetFont($font, '');
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::Image('public/images/afti/qslogo.png', '', '', 330, 80);
    PDF::SetMargins(40, 40);
    PDF::MultiCell(0, 0, "\n\n\n\n\n");
    PDF::MultiCell(300, 25, strtoupper($headerdata[0]->name), '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(300, 25, "DO NO : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 25, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(350, 15, 'UNIT 702 GREENBELT MANSION, 106 PEREA STREET BARANGAY SAN LORENZO, ', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(250, 15, "DO Date : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(350, 15, 'LEGASPI VILLAGE MAKATI CITY, METRO MANILA ', '', 'L');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(350, 15, 'Phone: 892-3883, 752-4100 Fax : 892-3882', '', 'L');
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(350, 15, 'Email: sales@afti.com.ph', '', 'L');

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(350, 15, 'Bill To : ', '', 'L', false, 0);
    PDF::MultiCell(230, 15, 'Ship To : ', '', 'R', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(500, 15, (isset($data[0]['bcontact']) ? $data[0]['bcontact'] : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 15, 'Attention : ', '', 'C', false, 0);
    PDF::MultiCell(100, 15, (isset($params['params']['dataparams']['attention']) ? $params['params']['dataparams']['attention'] : ''), '', 'L', false);

    PDF::MultiCell(500, 15, (isset($data[0]['baddr']) ? $data[0]['baddr'] : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 15, 'Company : ', '', 'C', false, 0);
    PDF::MultiCell(100, 15, '', '', 'L', false);

    PDF::MultiCell(500, 15, (isset($data[0]['bcontactno']) ? $data[0]['bcontactno'] : ''), '', 'L', false, 0);
    PDF::MultiCell(100, 15, 'Address : ', '', 'C', false, 0);
    PDF::MultiCell(100, 15, (isset($data[0]['saddr']) ? $data[0]['saddr'] : ''), '', 'L', false);

    PDF::MultiCell(500, 15, '', '', 'L', false, 0);
    PDF::MultiCell(100, 15, 'Contact : ', '', 'C', false, 0);
    PDF::MultiCell(100, 15, (isset($data[0]['scontactno']) ? $data[0]['scontactno'] : ''), '', 'L', false);

    PDF::MultiCell(500, 15, '', '', 'L', false, 0);
    PDF::MultiCell(100, 15, 'Fax : ', '', 'C', false, 0);
    PDF::MultiCell(100, 15, (isset($data[0]['scontactno']) ? $data[0]['scontactno'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(40, 0, "No.", 'B', 'C', false, 0);
    PDF::MultiCell(90, 0, "PART#", 'B', 'C', false, 0);
    PDF::MultiCell(90, 0, "MFR", 'B', 'C', false, 0);
    PDF::MultiCell(300, 0, "DESCRIPTION", 'B', 'L', false, 0);
    PDF::MultiCell(100, 0, "QTY", 'B', 'R', false, 0);
    PDF::MultiCell(50, 0, "UNIT", 'B', 'R', false);
  }

  public function SU_PDF($params, $data)
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
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->SU_header_PDF($params, $data);

    PDF::MultiCell(0, 0, "");

    $arritemname = array();
    $countarr = 0;

    $arrpart = array();
    $countarrpart = 0;

    $arrmfr = array();
    $countarrmfr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $arritemname = (str_split($data[$i]['itemname'] . ' ' . $data[$i]['itemdescription'] . ' ' . $data[$i]['accessories'], 32));
        $itemcodedescs = [];

        if (!empty($arritemname)) {
          foreach ($arritemname as $arri) {
            if (strstr($arri, "\n")) {
              $array = preg_split("/\r\n|\n|\r/", $arri);
              foreach ($array as $arr) {
                array_push($itemcodedescs, $arr);
              }
            } else {
              array_push($itemcodedescs, $arri);
            }
          }
        }
        $countarr = count($itemcodedescs);

        $arrpart = (str_split(trim($data[$i]['partno']), 12));
        $countarrpart = count($arrpart);

        $arrmfr = (str_split(trim($data[$i]['brand']), 12));
        $countarrmfr = count($arrmfr);

        $maxrow = 1;
        if ($countarr > $countarrpart) {
          $maxrow = $countarr;
        } else {
          $maxrow = $countarrpart;
        }

        if ($data[$i]['itemname'] == '') {
        } else {
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $inum = $i + 1;
              $qty = number_format($data[$i]['qty'], $decimalqty);
              $uom = $data[$i]['uom'];
            } else {
              $inum = '';
              $qty = '';
              $uom = '';
            }
            PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(40, 0, $inum, '', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(90, 0, isset($arrpart[$r]) ? $arrpart[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(90, 0, isset($arrmfr[$r]) ? $arrmfr[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(300, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(100, 0, $qty, '', 'R', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 0, $uom, '', 'R', false, 1, '', '', false, 1);
          }
        }

        if (intVal($i) + 1 == $page) {
          $this->SU_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
    PDF::MultiCell(0, 0, "");
    PDF::MultiCell(670, 0, "", "T");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'Remarks: ' . $data[0]['rem'], '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(253, 0, 'Attention By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Noted By: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['attention'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['noted'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  ///////////

  public function default_header($params, $data)
  {
    $this->modulename = app('App\Http\Classes\modules\sales\su')->modulename;

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCESS FRONTIER TECHNOLOGIES INC', '500', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DO NO : ', '100', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->col($data[0]['docno'], '200', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('UNIT 702 GREENBELT MANSION, 106 PEREA STREET BARANGAY SAN LORENZO, ', '500', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('DO Date : ', '100', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->col($data[0]['dateid'], '200', null, false, $border, '', 'L', $font, '13', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(' LEGASPI VILLAGE MAKATI CITY, METRO MANILA', '800', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Phone: 892-3883, 752-4100 Fax : 892-3882', '800', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Email: sales@afti.com.ph', '800', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Bill to : ', '500', null, false, $border, '', 'L', $font, '16', 'B', '', '');
    $str .= $this->reporter->col('Ship to : ', '300', null, false, $border, '', 'L', $font, '16', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['bcontact'], '500', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('Attention : ', '100', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['attention'], '200', null, false, $border, '', 'L', $font, '13', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['baddr'], '500', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('Company : ', '100', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, '13', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['bcontactno'], '500', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('Address : ', '100', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['saddr'], '200', null, false, $border, '', 'L', $font, '13', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('Contact : ', '100', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['scontactno'], '200', null, false, $border, '', 'L', $font, '13', 'BI', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col('Fax : ', '100', null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->col($data[0]['scontactno'], '200', null, false, $border, '', 'L', $font, '13', 'BI', '', '');
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
    $str .= $this->reporter->col('No.', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('PART#', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('MFR', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('DESCRIPTION', '300', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function default_su_layout($params, $data)
  {
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


    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($i + 1, '100', null, false, $border, 'LR', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['partno'], '100', null, false, $border, 'R', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['brand'], '100', null, false, $border, 'R', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'] . ' ' . $data[$i]['itemdescription'] . ' ' . $data[$i]['accessories'] . '<br>' . $data[$i]['srem'], '300', null, false, $border, 'R', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '100', null, false, $border, 'R', 'C', $font, $fontsize, '', '', '2px');

      $str .= $this->reporter->col($data[$i]['uom'], '100', null, false, $border, 'R', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '100', null, false, $border, 'T', 'R', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '12', 'B', '30px', '8px');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Remarks : ' . $data[0]['rem'], '800', null, false, $border, 'TBLR', 'C', $font, '12', '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Noted By :', '266', null, false, $border, '', 'C', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received the above item(s) in good order and condition', '266', null, false, $border, '', 'R', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '166', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '166', null, false, $border, '', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, '12', 'B', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  } //end fn

  public function bir_form_rr_layout($params, $data)
  {
  }

  public function default_pdfheader($params, $data, $font)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $this->modulename = app('App\Http\Classes\modules\purchase\rr')->modulename;

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);
    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 13);
    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($font, 'B', 18);
    PDF::MultiCell(560, 70, 'RECEIVING ITEM', '', 'L', 0, 0, '', '', false, 0, false, false, 70);
    PDF::SetFont($font, 'B', 13);
    PDF::MultiCell(100, 70, 'DOCUMENT #: ', '', 'L', false, 0, '', '', true, 0, false, true, 70, 'M');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 70, (isset($data[0]['docno']) ? $data[0]['docno'] . "" . PDF::write2DBarcode($data[0]['docno'] . '-' . $data[0]['trno'], 'QRCODE', PDF::GetX() + 25, PDF::GetY() + 20, 50, '', [], 'C') : ""), '', 'C', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "SUPPLIER: ", '', 'L', false, 0);
    PDF::MultiCell(420, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(80, 0, 'DATE: ', '', 'L', false, 0);
    PDF::MultiCell(160, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::MultiCell(100, 0, 'ADDRESS: ', '', 'L', false, 0);
    PDF::MultiCell(420, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(80, 0, 'TERMS: ', '', 'L', false, 0);
    PDF::MultiCell(160, 0, (isset($data[0]['terms']) ? $data[0]['terms'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 11);
    PDF::MultiCell(760, 0, "Page " . PDF::PageNo() . "  ", '', 'R', false);

    PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(50, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(50, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(300, 0, "D E S C R I P T I O N", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "EXPIRY", '', 'C', false, 0);
    PDF::MultiCell(105, 0, "UNIT PRICE", '', 'R', false, 0);
    PDF::MultiCell(50, 0, "DISC", '', 'R', false, 0);
    PDF::MultiCell(105, 0, "TOTAL", '', 'R', false);

    PDF::MultiCell(760, 0, '', 'B');
  }

  public function reportplottingpdf_default($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 850;
    $totalext = 0;

    $font = '';
    if (Storage::disk('sbcpath')->exists('/fonts/CENTURY.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/CENTURY.TTF');
    }
    $this->default_pdfheader($params, $data, $font);

    $arritemname = array();
    $countarr = 0;

    if (!empty($data)) {

      for ($i = 0; $i < count($data); $i++) {
        $arritemname = (str_split(trim($data[$i]['itemname']), 35));
        $countarr = count($arritemname) - 1;
        $ext = number_format($data[$i]['ext'], $decimalcurr);
        if ($ext < 1) $ext = '-';
        $netamt = number_format($data[$i]['netamt'], $decimalcurr);
        if ($netamt < 1) $netamt = '-';

        PDF::SetFont($font, '', 11);
        PDF::MultiCell(50, 0, number_format($data[$i]['qty'], $decimalqty), '', 'C', false, 0);
        PDF::MultiCell(50, 0, $data[$i]['uom'], '', 'C', false, 0);
        PDF::MultiCell(300, 0, $arritemname[0], '', 'L', 0, 0, '', '', true, 0, false, true, 0);
        PDF::MultiCell(100, 0, $data[$i]['expiry'], '', 'L', false, 0);
        PDF::MultiCell(105, 0, number_format($data[$i]['gross'], $decimalprice), '', 'R', false, 0);
        PDF::MultiCell(50, 0, $data[$i]['disc'], '', 'L', false, 0);
        PDF::MultiCell(105, 0, $ext, '', 'R');
        for ($b = 1; $b <= $countarr; $b++) {
          PDF::MultiCell(50, 0, '', '', 'C', false, 0);
          PDF::MultiCell(50, 0, '', '', 'C', false, 0);
          PDF::MultiCell(300, 0, $arritemname[$b], '', 'L', 0, 0);
          PDF::MultiCell(100, 0, '', '', 'L', false, 0);
          PDF::MultiCell(105, 0, '', '', 'R', false, 0);
          PDF::MultiCell(50, 0, PDF::getY(), '', 'L', false, 0);
          PDF::MultiCell(105, 0, $b, '', 'R');
          if (PDF::getY() >= $page) {
            $this->default_pdfheader($pdf, $params, $data, $font);
          }
        }

        $totalext = $totalext + $data[$i]['ext'];

        if (PDF::getY() >= $page) {
          $this->default_pdfheader($pdf, $params, $data, $font);
        }
      }
    }

    PDF::MultiCell(50, 0, '', '', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'C', false, 0);
    PDF::MultiCell(300, 0, '', '', 'L', 0, 0);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0);
    PDF::MultiCell(105, 0, '', '', 'R', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(105, 0, '', '', 'R');

    //1-w, 2-h, 3-txt, 4-border = 0, 5-align = 'J', 6-fill = 0, 7-ln = 1, 8-x = '', 9-y = '', 10-reseth = true, 11-stretch = 0, 12-ishtml = false, 13-autopadding = true, 14-maxh = 0
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(535, 0, '', '', '', false, 0, 100, 800);
    PDF::MultiCell(100, 0, 'GRAND TOTAL: ', '', 'L', false, 0);
    PDF::MultiCell(125, 0, number_format($totalext, $decimalcurr), '', 'R');

    PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, isset($data[0]['rem']) ? $data[0]['rem'] : '', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");

    PDF::MultiCell(253, 0, 'Prepared By: ', '', 'C', false, 0);
    PDF::MultiCell(253, 0, 'Approved By: ', '', 'C', false, 0);
    PDF::MultiCell(253, 0, 'Received By: ', '', 'C');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(253, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $params['params']['dataparams']['received'], '', 'L');

    PDF::AddPage();
    $b = 62;
    for ($i = 0; $i < 1000; $i++) {
      PDF::SetTextColor(0, 55, 255);
      PDF::MultiCell(200, 0, $i, '', 'C', false, 0);
      PDF::Cell(200, 5, ' ', '', 0, 'L', 0); //AAAAAAAAAAAA
      PDF::Cell(90, 5, '', '', 0, 'L', 0); //SSSSSSSSSSS
      PDF::SetLineStyle(array('width' => 0.5, 'cap' => 'butt', 'join' => 'miter', 'dash' => 1, 'color' => array(255, 255, 0)));
      PDF::Ln(15);
      if ($i == $b) {
        PDF::AddPage();
        $b = $b + 62;
      }
    }
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function report_SU_headerpdf($params, $data, $font)
  {
    $sjlogo = $params['params']['dataparams']['radiosjaftilogo'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::SetFont($font, '', 14);
    if ($sjlogo == 'wlogo') {
      PDF::Image('public/images/afti/qslogo.png', '', '', 200, 50);
      PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
      PDF::SetFont($font, 'B', $fontsize11);
      PDF::MultiCell(290, 0, 'STOCK ISSUANCE - ORIGINAL', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);
    } else {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(380, 0, 'A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
      PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
      PDF::SetFont($font, 'B', $fontsize11);
      PDF::MultiCell(290, 0, 'STOCK ISSUANCE - ORIGINAL', '', 'C', 0, 0, '310', '25', false, 0, false, false, 0);
    }

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(270, 15, $headerdata[0]->name, '', 'L', false, 0, '', '55');
    PDF::MultiCell(90, 15, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(60, 15, ' ' . 'DO No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 15, ' ' . (isset($data[0]['docno']) ? $data[0]['docno'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(270, 15, $headerdata[0]->address, '', 'L', false, 0);
    PDF::MultiCell(90, 15, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(60, 15, ' ' . 'DO Date',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 15, ' ' . date("F d,Y", strtotime($data[0]['dateid'])), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(270, 0, '', '', 'L', false, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(60, 15, ' ' . 'PO Ref No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 15, ' ' . (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(270, 15, '', '', 'L', false, 0);
    PDF::MultiCell(90, 15, '', '', 'L', false, 0);
    PDF::SetFillColor(211, 211, 211);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(60, 15, ' ' . 'Payment',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 15, ' ' . (isset($data[0]['terms']) ? $data[0]['terms'] : ''), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(270, 15, '', '', 'L', false, 0);
    PDF::MultiCell(90, 15, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(60, 15, ' ' . 'Page No.',  '1', 'L', 1, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 15, ' ' . 'Page    ' . PDF::PageNo() . '    of    ' . PDF::getAliasNbPages(), $border, 'L', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(270, 5, '', '', 'L', false, 0);
    PDF::MultiCell(90, 5, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(60, 5, ' ' . '',  '', 'L', 0, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 5, '', '', 'L', false);


    PDF::SetFont($font, 'B', $fontsize9);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(130, 0, '  ' . 'CUSTOMER NAME', 'LT', 'L', 1, 0);
    PDF::MultiCell(150, 0, '', 'TR', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(100, 0, '  ' . 'SHIP TO', 'LT', 'L', 1, 0);
    PDF::MultiCell(180, 0, '', 'TR', 'L', false);

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(280, 20, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'LR', 'L', false, 0);
    PDF::MultiCell(10, 20, '', '', 'L', false, 0);
    PDF::MultiCell(280, 20, '  ' . (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'LR', 'L', false);

    PDF::SetFont($font, '', $fontsize9);

    $baddr1 = $data[0]['baddrline1'];
    $baddr2 = $data[0]['baddrline2'];
    $bcity = $data[0]['bcity'] . ' ' . $data[0]['bzipcode'];
    $bprovince = $data[0]['bprovince'];
    $bcountry = $data[0]['bcountry'];

    $saddr1 = $data[0]['saddrline1'];
    $saddr2 = $data[0]['saddrline2'];
    $scity = $data[0]['scity'] . ' ' . $data[0]['szipcode'];
    $sprovince = $data[0]['sprovince'];
    $scountry = $data[0]['scountry'];

    $arrabddrline = $this->reporter->fixcolumn([$baddr1, $baddr2, $bcity, $bprovince, $bcountry], 50, 0);
    $caddrbline1 = count($arrabddrline);
    $arrasddrline = $this->reporter->fixcolumn([$saddr1, $saddr2, $scity, $sprovince, $scountry], 50, 0);
    $caddrsline1 = count($arrasddrline);

    $maxrow = max($caddrbline1, $caddrsline1);
    for ($r = 0; $r < $maxrow; $r++) {
      PDF::SetFont($font, '', $fontsize9);
      PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
      PDF::MultiCell(275, 0, isset($arrabddrline[$r]) ? ' ' . $arrabddrline[$r] : '', 'R', 'L', false, 0);
      PDF::MultiCell(10, 0, '', '', 'L', false, 0);
      PDF::MultiCell(5, 0, '', 'L', 'L', false, 0);
      PDF::MultiCell(275, 0, isset($arrasddrline[$r]) ? ' ' . $arrasddrline[$r] : '', 'R', 'L', false);
    }

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(280, 0, '', 'LR', 'L', false, 0);
    PDF::MultiCell(10, 0, '', '', 'L', false, 0);
    PDF::MultiCell(280, 0, '', 'LR', 'L', false);

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(75, 15, '  ' . 'Contact Name: ', 'BL', 'L', false, 0);
    PDF::MultiCell(205, 15, '  ' . (isset($data[0]['billcontact']) ? $data[0]['billcontact'] : ''), 'BR', 'L', false, 0);
    PDF::MultiCell(10, 15, '', '', 'L', false, 0);
    PDF::MultiCell(75, 15, '  ' . 'Contact Name: ', 'BL', 'L', false, 0);
    PDF::MultiCell(205, 15, '  ' . (isset($data[0]['shipcontact']) ? $data[0]['shipcontact'] : ''), 'BR', 'L', false);

    PDF::MultiCell(0, 0, "");

    $hheight = PDF::getStringHeight(40, 'Qty Ordered');
    PDF::SetFont($font, '', $fontsize9);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(25, 0, 'No.', '1', 'C', 1, 0);
    PDF::MultiCell(80, 0, 'Order Code', '1', 'C', 1, 0);
    PDF::MultiCell(80, 0, 'Mfr', '1', 'C', 1, 0);
    PDF::MultiCell(190, 0, 'Description', '1', 'C', 1, 0);
    PDF::MultiCell(45, 0, 'Qty Send', '1', 'C', 1, 0);
    PDF::MultiCell(65, 0, 'Qty Ordered', '1', 'C', 1, 0);
    PDF::MultiCell(35, 0, 'B/O', '1', 'C', 1, 0);
    PDF::MultiCell(50, 0, 'U/M', '1', 'C', 1);
  }

  public function SUPDF_LAYOUT($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $trno = $params['params']['dataid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $count = $page = 790;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;

    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";

    $font = '';

    $this->report_SU_headerpdf($params, $data, $font);

    $arritemname = array();
    $countarr = 0;

    $arrordercode = array();
    $countarrcode = 0;


    $arrmfr = array();
    $countarrmfr = 0;

    $totalctr = 0;

    $totalqty = 0;

    $newpageadd = 0;
    $datastock = $this->stockquery($trno);
    if (!empty($datastock)) {

      for ($i = 0; $i < count($datastock); $i++) {
        $inforem = '';
        $itemserialno = '';

        $unitprice = $datastock[$i]['amt'];
        $linetotal = $datastock[$i]['qty'] * $unitprice;

        if ($unitprice == 0) {
          $unitprice = 0;
        }

        if ($linetotal == 0) {
          $linetotal = 0;
        }

        $arrqty = (str_split(trim(intval($datastock[$i]['qty']) . ' ' . $datastock[$i]['uom']) . ' ', 10));
        $countarrqty = count($arrqty);

        $arrprice = (str_split(trim('PHP ' . number_format($unitprice, $decimalprice)) . ' ', 14));
        $countarrprice = count($arrprice);

        $arrlinetotal = (str_split(trim('PHP ' . number_format($linetotal, $decimalqty)) . ' ', 14));
        $countarrlinetotal = count($arrlinetotal);

        $trno = $datastock[$i]['trno'];
        $line = $datastock[$i]['line'];

        $serialdata = $this->serialquery($trno, $line);

        $itemcode = $datastock[$i]['itemname'];
        $itembrand = $datastock[$i]['brand_desc'];
        $itemdescription = $datastock[$i]['itemdescription'];

        if (!empty($serialdata)) {
          foreach ($serialdata as $key => $value) {
            $itemserialno .= $value['serialno'];
          }
          if ($itemserialno) {
            $itemserialno = "Serial No : " . $itemserialno;
          }
        }

        $itemaccessories = $datastock[$i]['accessories'];
        $iteminfo = $datastock[$i]['inforem'];


        $arrordercode = $this->reporter->fixcolumn([$itemcode], '10');
        $countarrcode = count($arrordercode);

        $arrmfr = $this->reporter->fixcolumn([$itembrand], '10');
        $countarrmfr = count($arrmfr);

        $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $itemserialno, $itemaccessories, $iteminfo], '30');
        $countarrcol = count($itemcoldes);

        $maxrow = 1;
        $maxrow = max($countarrcol, $countarrcode, $countarrmfr, $countarrqty, $countarrprice, $countarrlinetotal); // get max count

        if ($datastock[$i]['itemname'] == '') {
        } else {
          if ($newpageadd == 1) {
            $newpageadd = 0;
            $this->addrow('LRB');
          }
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $inum = $i + 1;

              if ($datastock[$i]['soqty'] == 0) {
                $soqty = number_format($datastock[$i]['qty'], 0);
              } else {
                $soqty = number_format($datastock[$i]['soqty'], 0);
              }

              $qty = number_format($datastock[$i]['qty'], 0);
              $uom = $datastock[$i]['uom'];
              $totalqty =  $soqty - $qty;
            } else {
              $inum = '';
              $soqty = '';
              $qty = '';
              $uom = '';
              $totalqty = '';
            }


            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(25, 15, $inum, 'LR', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(80, 15, isset($arrordercode[$r]) ? ' ' . $arrordercode[$r] : '', 'LR', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 15, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : ' ', 'LR', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(190, 15, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(45, 15, $soqty . ' ', 'LR', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(65, 15, $qty . ' ', 'LR', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(35, 15, $totalqty . ' ', 'LR', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(50, 15, $uom, 'LR', 'C', false, 1, '', '', false, 1);
            if (PDF::getY() >= $page) {
              $this->addrow('LRB');
              $this->report_SU_headerpdf($params, $data, $font);
              $this->addrow('TLR');
            }
          }
        }

        if ($datastock[0]['vattype'] == 'VATABLE') {
          $vatsales = $vatsales + $linetotal;
        } else {
          $vatsales = 0;
          $totalext = $totalext + $linetotal;
        }

        if (PDF::getY() >= $page) {
          $this->addrow('LRB');
          $newpageadd = 1;
          $this->report_SU_headerpdf($params, $data, $font);
        }
      }
    }

    if ($datastock[0]['vattype'] == 'VATABLE') {
      $vat = $vatsales * .12;
      $totalext = $vatsales + $vat;
    } else {
      $vat = 0;
    }

    if (PDF::getY() > 650) {
      $this->addrow('LRB');
      $newpageadd = 1;
      $this->report_SU_headerpdf($params, $data, $font);
    }
    do {
      $this->addrow('LR');
    } while (PDF::getY() < 650);

    $this->addrow('T');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(30, 20, ' ', '', 'L', false, 0, 10, PDF::getY());
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(140, 20, 'PREPARED BY:',  '', 'C', 1, 0);
    PDF::MultiCell(41, 20, '', '', 'L', false, 0);
    PDF::MultiCell(140, 20, 'APPROVED BY: ',  '', 'C', 1, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(50, 20, '', '', 'L', false, 0);
    PDF::MultiCell(140, 20, 'Received The Aboved item(s) In Good Order and Condition', '', 'C', false, 0);
    PDF::MultiCell(50, 20, '', '', 'R', false);

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(40, 25, '', '', 'L', false, 0);
    PDF::MultiCell(140, 25, '',  '', 'C', false, 0);
    PDF::MultiCell(41, 25, '', '', 'L', false, 0);
    PDF::MultiCell(140, 25, '',  '', 'C', false, 0);
    PDF::MultiCell(50, 25, '', '', 'L', false, 0);
    PDF::MultiCell(140, 25, '', '', 'C', false, 0);
    PDF::MultiCell(50, 25, '', '', 'R', false);

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(40, 0, '', '', 'L', false, 0);
    PDF::MultiCell(140, 0, '',  '', 'C', false, 0);
    PDF::MultiCell(41, 0, '', '', 'L', false, 0);
    PDF::MultiCell(140, 0, $params['params']['dataparams']['approved'],  'B', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(140, 0, '', '', 'C', false, 0);
    PDF::MultiCell(50, 0, '', '', 'R', false);

    PDF::MultiCell(0, 25, "");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(40, 20, '', '', 'L', false, 0);
    PDF::MultiCell(140, 20, '',  '', 'C', false, 0);
    PDF::MultiCell(41, 20, '', '', 'L', false, 0);
    PDF::MultiCell(140, 20, '',  '', 'C', false, 0);
    PDF::MultiCell(50, 20, '', '', 'L', false, 0);
    PDF::MultiCell(140, 20, 'Signature Over Printed Name', 'T', 'C', false, 0);
    PDF::MultiCell(50, 20, '', '', 'R', false);

    PDF::MultiCell(0, 10, "");

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(30, 0, '', '', 'L', false, 0);
    PDF::MultiCell(450, 0, 'BIR Permit No. 11-20-13-047-CGAR-', '', 'L', false, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function stockquery($trno)
  {

    $query = "select stock.trno, stock.line,stock.rem as srem, item.barcode,
        item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, brands.brand_desc,
        iteminfo.itemdescription, iteminfo.accessories,stockinfo.rem as inforem,ifnull(sostock.rrqty,0) as soqty, head.vattype,stock.refx
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join frontend_ebrands as brands on brands.brandid = item.brand
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
        left join hprstock as sostock on  sostock.line=stock.linex and sostock.trno =stock.refx
        left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
        where head.doc='SU' and head.trno='$trno' and stock.noprint = 0
         group by stock.trno, stock.line,stock.rem, item.barcode,
        item.itemname, stock.isqty, stock.uom, stock.isamt, stock.disc, stock.ext, brands.brand_desc,
        iteminfo.itemdescription, iteminfo.accessories,stockinfo.rem,sostock.rrqty, head.vattype,stock.refx
        union all
        select stock.trno, stock.line,stock.rem as srem, item.barcode,
        item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, brands.brand_desc,
        iteminfo.itemdescription, iteminfo.accessories,stockinfo.rem as inforem,ifnull(sostock.rrqty,0) as soqty, head.vattype,stock.refx
        from glhead as head
        left join glstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid
        left join frontend_ebrands as brands on brands.brandid = item.brand
        left join iteminfo on iteminfo.itemid = item.itemid
        left join stockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
        left join hprstock as sostock on  sostock.line=stock.linex and sostock.trno =stock.refx
        left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
        where head.doc='SU' and head.trno='$trno' and stock.noprint = 0 
         group by stock.trno, stock.line,stock.rem, item.barcode,
        item.itemname, stock.isqty, stock.uom, stock.isamt, stock.disc, stock.ext, brands.brand_desc,
        iteminfo.itemdescription, iteminfo.accessories,stockinfo.rem,sostock.rrqty, head.vattype,stock.refx
        order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  private function serialquery($trno, $line)
  {
    $query = "select ifnull(concat(rr.serial,'\\n\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\r'),'') as serialno
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where head.doc='SU' and head.trno='$trno' and stock.line = '$line' and stock.noprint = 0 and item.isserial = 1
    union all
    select ifnull(concat(rr.serial,'\\n\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\t\\r'),'') as serialno
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid
    left join serialout as rr on rr.trno = stock.trno and rr.line = stock.line
    where head.doc='SU' and head.trno='$trno' and stock.line = '$line' and stock.noprint = 0 and item.isserial = 1
    order by serialno";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }


  private function addrow($border)
  {
    PDF::MultiCell(25, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(80, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(80, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(190, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(45, 0, '', $border, 'R', false, 0, '', '', false, 1);
    PDF::MultiCell(65, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(35, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, 0, '', $border, 'R', false, 1, '', '', false, 1);
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
  }
  // Commercial_Invoice_headerpdf
  public function Commercial_Invoice_headerpdf($params, $data, $font)
  {
    $sjlogo = $params['params']['dataparams']['radiosjaftilogo'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";

    PDF::SetFont($font, '', 14);

    if ($sjlogo == 'wlogo') {
      PDF::Image('public/images/afti/qslogo.png', '', '', 200, 50);
      PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
      PDF::SetFont($font, 'B', $fontsize11);
      PDF::MultiCell(290, 0, '', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);
    } else {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(380, 0, 'A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
      PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
      PDF::SetFont($font, 'B', $fontsize11);
      PDF::MultiCell(290, 0, '', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);
    }

    PDF::MultiCell(0, 50, "\n");
    PDF::SetFont($font, 'B', 19);
    PDF::MultiCell(500, 0, 'Commercial Invoice', '', 'R', false);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(480, 15, ' ', 0, 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);


    $drdocno = isset($data[0]['docno']) ? $data[0]['docno'] : '';
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->name, '', 'L', false, 0, '', '');
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(80, 15, 'Proforma Invoice.:',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, ' ' . $drdocno, '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->address, '', '', false, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(80, 15, 'Date:',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, ' ' . date("F d,Y", strtotime($data[0]['dateid'])), '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize12);
    PDF::MultiCell(0, 0, "\n\n\n\n", '', 'L', false);


    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(260, 0, 'Bill To:', '', 'L', 0, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(80, 0, 'Ship To:', '', 'L', false, 0);
    PDF::MultiCell(140, 0, '', '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $data[0]['baddr'], '', 'L', 0, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(65, 0, 'Contact', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(155, 0, $data[0]['scontact'], '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, '', '', 'L', 0, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(65, 0, 'Company', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $data[0]['clientname'], '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, '', '', 'L', 0, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(65, 0, 'Address', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $data[0]['saddr'], '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, 'Phone: ' . $data[0]['bcontactno'], '', 'L', 0, 0);
    PDF::MultiCell(60, 0, '', '', 'L', false, 0);
    PDF::MultiCell(65, 0, 'Phone', '', 'L', false, 0);
    PDF::MultiCell(155, 0, $data[0]['scontactno'], '', 'L', false);

    $hheight = PDF::getStringHeight(40, 'Description');
    PDF::SetFillColor(211, 211, 211);

    PDF::SetFont($font, 'B', $fontsize9);

    PDF::MultiCell(20, 330, '', 'L', 'C', false, 0, '10', '359');
    PDF::MultiCell(300, 330, '', 'L', 'C', false, 0);
    PDF::MultiCell(50, 330, '', 'L', 'C', false, 0);
    PDF::MultiCell(75, 330, '', 'L', 'C', false, 0);
    PDF::MultiCell(100, 330, '', 'LR', 'C', 1);

    PDF::MultiCell(20, 0, 'No', 'TBL', 'C', 1, 0, '10', '347');
    PDF::MultiCell(300, 0, 'Item Description', 'TBL', 'C', 1, 0);
    PDF::MultiCell(50, 0, 'QTY', 'TBL', 'C', 1, 0);
    PDF::MultiCell(75, 0, 'Unit Price', 'TBL', 'C', 1, 0);
    PDF::MultiCell(100, 0, 'Total', 'TLBR', 'C', 1);
  }

  public function Commercial_Invoice_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $trno = $params['params']['dataid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $count = $page = 790;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;

    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";

    $font = '';

    $this->Commercial_Invoice_headerpdf($params, $data, $font);

    $total = 0;

    $datastock = $this->stockquery($trno);
    if (!empty($datastock)) {

      for ($i = 0; $i < count($datastock); $i++) {

        $inforem = '';
        $itemserialno = '';

        $unitprice = $datastock[$i]['amt'];
        $linetotal = $datastock[$i]['qty'] * $unitprice;

        if ($unitprice == 0) {
          $unitprice = 0;
        }

        if ($linetotal == 0) {
          $linetotal = 0;
        }
        $trno = $datastock[$i]['trno'];
        $line = $datastock[$i]['line'];
        $excess = 0;
        $serialline = 1;
        $seriallen = 0;
        $serialdata = $this->serialquery($trno, $line);

        $itemcode = $datastock[$i]['itemname'];
        $itembrand = $datastock[$i]['brand_desc'];
        $itemdescription = $datastock[$i]['itemdescription'];

        if (!empty($serialdata)) {
          foreach ($serialdata as $key => $value) {
            if ($itemserialno == '') {
              $itemserialno .= trim($value['serialno']);
            } else {
              $itemserialno .= ' / ' . trim($value['serialno']);
            }
          }
          if ($itemserialno) {
            $itemserialno = "Serial No : " . $itemserialno;
          }
        }

        $itemaccessories = $datastock[$i]['accessories'];
        $iteminfo = $datastock[$i]['inforem'];


        if (strlen($itemdescription) > 70) {
          $n = "\n";
        } else {
          $n = " ";
        }
        $itemcoldes = $itemdescription . ' ' . $itemaccessories . ' ' . $iteminfo . $n . $itemserialno;

        $itemlen = strlen($itemcoldes) / 70;

        if ($itemcoldes == '') {
          $itemlen = 1;
        }
        $padding = 29 * $itemlen;

        if ($datastock[$i]['itemname'] == '') {
        } else {
          $inum = $i + 1;
          $qty = number_format($datastock[$i]['qty'], 0);

          PDF::SetFont($font, '', $fontsize9);
          PDF::MultiCell(20, $padding, $inum, '', 'C', false, 0);
          PDF::MultiCell(10, $padding, '', '', 'C', false, 0);
          PDF::MultiCell(290, $padding, $itemcoldes, '', 'L', false, 0);
          PDF::MultiCell(50, $padding, $qty, '', 'C', false, 0);
          PDF::MultiCell(75, $padding, number_format($unitprice, 4), 'R', 'C', false, 0);
          PDF::MultiCell(100, $padding, number_format($linetotal, 2), 'LR', 'C', 1);
          $total += $linetotal;

        }

      }
    }

    PDF::SetFont($font, 'B', $fontsize12);
    PDF::MultiCell(545, 20, 'REMARKS: DEMO UNITS ONLY', 'LR', 'C', false, 1, '10', '690');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', 'L', 'C', false, 0);
    PDF::MultiCell(10, 20, '', 'L', 'C', false, 0);
    PDF::MultiCell(290, 20, "NOTES: " . $data[0]['rem'], '', 'L', false, 0);
    PDF::MultiCell(50, 20, '', 'L', 'C', false, 0);
    PDF::MultiCell(75, 20, '', 'L', 'R', false, 0);
    PDF::MultiCell(100, 20, '', 'LR', 'C', 1);

    PDF::MultiCell(20, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(10, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(290, 0, '', 'T', 'L', false, 0);
    PDF::MultiCell(50, 0, '', 'T', 'C', false, 0);
    PDF::MultiCell(75, 0, 'Total   ', 'T', 'R', false, 0);
    PDF::MultiCell(100, 0, number_format($total, 2), 'TBLR', 'C', 1);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(150, 0, 'Approved', '', 'L', false);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(150, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  public function PACKING_headerpdf($params, $data, $font)
  {
    $sjlogo = $params['params']['dataparams']['radiosjaftilogo'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,concat(address,' ',zipcode,'\n\r','Phone: ',tel,'\n\r','Email: ',email,'\n\r','VAT REG TIN: ',tin) as address,tel,tin from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [595, 842]);
    PDF::SetMargins(10, 10);

    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";
    $fontsize13 = '13';
    $fontsize14 = "14";
    $border = "1px solid ";


    PDF::SetFont($font, '', 14);

    if ($sjlogo == 'wlogo') {
      PDF::Image('public/images/afti/qslogo.png', '', '', 200, 50);
      PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
      PDF::SetFont($font, 'B', $fontsize11);
      PDF::MultiCell(290, 0, '', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);
    } else {
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(380, 0, 'A C C E S S ' . '   ' . 'F R O N T I E R', '', 'L', 0, 0, '50', '30', false, 0, false, false, 0);
      PDF::MultiCell(380, 0, '', '', 'L', 0, 0, '', '', false, 0, false, false, 0);
      PDF::SetFont($font, 'B', $fontsize11);
      PDF::MultiCell(290, 0, '', '', 'L', 0, 0, '370', '25', false, 0, false, false, 0);
    }

    PDF::MultiCell(0, 50, "\n");
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(500, 0, 'PACKING LIST', '', 'R', false);
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(480, 15, ' ' . date("F d,Y", strtotime($data[0]['dateid'])), 0, 'R', false, 1, '', '', true, 0, false, true, 0, 'M', true);


    $drdocno = isset($data[0]['docno']) ? $data[0]['docno'] : '';

    PDF::MultiCell(0, 30, "\n");
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->name, '', 'L', false, 0, '', '');
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, ' ' . 'DO No.:',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, ' ' . $drdocno, '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $headerdata[0]->address, '', '', false, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(50, 15, ' ' . 'DO Date:',  '', 'L', 0, 0, '', '', false, 0, false, true, 0, 'M', true);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(140, 15, ' ' . date("F d,Y", strtotime($data[0]['dateid'])), '', '', false, 1, '', '', true, 0, false, true, 0, 'M', true);

    PDF::MultiCell(0, 40, "\n");


    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(260, 0, 'Bill To:', '', 'L', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Ship To:', '', 'L', false, 0);
    PDF::MultiCell(140, 0, '', '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, $data[0]['baddr'], '', 'L', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Attention', '', 'L', false, 0);
    PDF::MultiCell(140, 0, $data[0]['scontact'], '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, '', '', 'L', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Company', '', 'L', false, 0);
    PDF::MultiCell(140, 0, $data[0]['clientname'], '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, '', '', 'L', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Address', '', 'L', false, 0);
    PDF::MultiCell(140, 0, $data[0]['saddr'], '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, '', '', 'L', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Contact', '', 'L', false, 0);
    PDF::MultiCell(140, 0, $data[0]['scontactno'], '', 'L', false);

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(260, 0, '', '', 'L', 0, 0);
    PDF::MultiCell(90, 0, '', '', 'L', false, 0);
    PDF::MultiCell(50, 0, 'Fax', '', 'L', false, 0);
    PDF::MultiCell(140, 0, $data[0]['sfax'], '', 'L', false);

    PDF::MultiCell(0, 40, "\n");

    $hheight = PDF::getStringHeight(40, 'Description');
    PDF::SetFont($font, '', $fontsize9);
    PDF::SetFillColor(211, 211, 211);
    PDF::MultiCell(25, 0, 'No.', '1', 'C', 1, 0);
    PDF::MultiCell(80, 0, 'Part', '1', 'C', 1, 0);
    PDF::MultiCell(80, 0, 'Mfr', '1', 'C', 1, 0);
    PDF::MultiCell(220, 0, 'Description', '1', 'C', 1, 0);
    PDF::MultiCell(70, 0, 'QTY', '1', 'C', 1, 0);
    PDF::MultiCell(70, 0, 'UOM', '1', 'C', 1);

  }

  public function PACKING_LAYOUT($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $trno = $params['params']['dataid'];
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $count = $page = 790;

    $linetotal = 0;
    $unitprice = 0;
    $vatsales = 0;
    $vat = 0;
    $totalext = 0;

    $fontsize9 = "9";
    $fontsize11 = "11";
    $fontsize12 = "12";

    $font = '';

    $this->PACKING_headerpdf($params, $data, $font);

    $arritemname = array();
    $countarr = 0;

    $arrordercode = array();
    $countarrcode = 0;


    $arrmfr = array();
    $countarrmfr = 0;

    $totalctr = 0;

    $totalqty = 0;


    $newpageadd = 0;
    $datastock = $this->stockquery($trno);
    if (!empty($datastock)) {

      for ($i = 0; $i < count($datastock); $i++) {
        $inforem = '';
        $itemserialno = '';

        $arrqty = (str_split(trim(intval($datastock[$i]['qty'])), 14));
        $countarrqty = count($arrqty);

        $arruom = (str_split(trim(intval($datastock[$i]['uom'])), 14));
        $countarruom = count($arruom);

        $arrpart = (str_split(trim($data[$i]['partno']), 14));
        $countarrpart = count($arrpart);

        $trno = $datastock[$i]['trno'];
        $line = $datastock[$i]['line'];

        $serialdata = $this->serialquery($trno, $line);

        $itemcode = $datastock[$i]['itemname'];
        $itembrand = $datastock[$i]['brand_desc'];
        $itemdescription = $datastock[$i]['itemdescription'];

        if (!empty($serialdata)) {
          foreach ($serialdata as $key => $value) {
            $itemserialno .= $value['serialno'];
          }
          if ($itemserialno) {
            $itemserialno = "Serial No : " . $itemserialno;
          }
        }

        $itemaccessories = $datastock[$i]['accessories'];
        $iteminfo = $datastock[$i]['inforem'];



        $arrmfr = $this->reporter->fixcolumn([$itembrand], '10');
        $countarrmfr = count($arrmfr);

        $itemcoldes = $this->reporter->fixcolumn([$itemdescription, $itemserialno, $itemaccessories, $iteminfo], '35');
        $countarrcol = count($itemcoldes);

        $maxrow = 1;
        $maxrow = max($countarrcol, $countarrpart, $countarrmfr, $countarrqty, $countarruom); // get max count

        if ($datastock[$i]['itemname'] == '') {
        } else {
          if ($newpageadd == 1) {
            $newpageadd = 0;
            $this->addrow('LRB');
          }
          for ($r = 0; $r < $maxrow; $r++) {
            if ($r == 0) {
              $inum = $i + 1;

              $soqty = number_format($datastock[$i]['soqty'], 0);
              $qty = number_format($datastock[$i]['qty'], 0);
              $uom = $datastock[$i]['uom'];
              $totalqty = $soqty - $qty;
            } else {
              $inum = '';
              $soqty = '';
              $qty = '';
              $uom = '';
              $totalqty = '';
            }


            PDF::SetFont($font, '', $fontsize9);
            PDF::MultiCell(25, 15, $inum, 'LR', 'C', false, 0, '', '', true, 1);
            PDF::MultiCell(80, 15, isset($arrpart[$r]) ? ' ' . $arrpart[$r] : '', 'LR', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(80, 15, isset($arrmfr[$r]) ? ' ' . $arrmfr[$r] : ' ', 'LR', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(220, 15, isset($itemcoldes[$r]) ? ' ' . $itemcoldes[$r] : '', 'LR', 'L', false, 0, '', '', false, 1);
            PDF::MultiCell(70, 15, $qty . ' ', 'LR', 'C', false, 0, '', '', false, 1);
            PDF::MultiCell(70, 15, $uom, 'LR', 'C', false, 1, '', '', false, 1);
            if (PDF::getY() >= $page) {
              $this->packing_addrow('LRB');
              $this->blankpage($params, $data, $font);
              $this->packing_addrow('TLR');
            }
          }
        }

        if ($datastock[0]['vattype'] == 'VATABLE') {
          $vatsales = $vatsales + $linetotal;
        } else {
          $vatsales = 0;
          $totalext = $totalext + $linetotal;
        }

        if (PDF::getY() >= $page) {
          $this->packing_addrow('LRB');
          $newpageadd = 1;
          $this->PACKING_headerpdf($params, $data, $font);
        }
      }

      do {
        $this->packing_addrow('LR');
      } while (PDF::getY() < 670);
    }

    $this->packing_addrow('LRB');

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(30, 0, 'Notes:', '', 'L', 0, 0);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(320, 0, $data[0]['rem'], '', 'L', false, 0);
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(190, 0, 'Received above item(s) in good order and condition', '', 'L', false);

    PDF::MultiCell(0, 10, "\n");

    PDF::SetFillColor(211, 211, 211);
    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(20, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, 'PREPARED BY', '', 'C', 1, 0);
    PDF::MultiCell(80, 0, '', '', 'L', false, 0);
    PDF::MultiCell(120, 0, 'NOTED BY', '', 'C', 1, 0);
    PDF::MultiCell(80, 0, '', '', 'C', false, 0);
    PDF::MultiCell(120, 0, '', '', 'C', false, 0);
    PDF::MultiCell(80, 0, '', '', 'C');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(20, 20, '', '', 'L', false, 0);
    PDF::MultiCell(120, 20, $params['params']['dataparams']['prepared'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 20, '', '', 'L', false, 0);
    PDF::MultiCell(120, 20, $params['params']['dataparams']['noted'], 'B', 'C', false, 0);
    PDF::MultiCell(80, 20, '', '', 'L', false, 0);
    PDF::MultiCell(120, 20, '', 'B', 'C', false, 0);
    PDF::MultiCell(80, 20, '', '', 'R');

    PDF::MultiCell(0, 20, "\n");

    PDF::SetFont($font, '', $fontsize9);
    PDF::MultiCell(535, 20, 'Please Contact the CS Ops Team at 892-3883 loc. 110 with any question/concern/discrepancy', '', 'C');
    PDF::SetFont($font, 'B', $fontsize9);
    PDF::MultiCell(535, 20, 'Thank you for your business!', '', 'C');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  private function packing_addrow($border)
  {
    PDF::MultiCell(25, 0, '', $border, 'C', false, 0, '', '', true, 1);
    PDF::MultiCell(80, 0, '', $border, 'C', false, 0, '', '', false, 1);
    PDF::MultiCell(80, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(220, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(70, 0, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(70, 0, '', $border, 'R', false, 1, '', '', false, 1);
  }
}
