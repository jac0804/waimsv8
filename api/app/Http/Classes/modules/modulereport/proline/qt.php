<?php

namespace App\Http\Classes\modules\modulereport\proline;

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
use Illuminate\Support\Facades\Storage;

class qt
{
  public $tablenum = 'transnum';
  public $head = 'qthead';
  public $hhead = 'hqthead';
  public $stock = 'qtstock';
  public $hstock = 'hqtstock';
  private $modulename;
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    data_set($col1, 'radioreporttype.options', [
      ['label' => 'New Van', 'value' => 'newVan', 'color' => 'red'],
      ['label' => 'Repair', 'value' => 'repair', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      'newVan' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      "
    );
  }

  public function report_default_query($params, $trno)
  {
    $type = $params['params']['dataparams']['reporttype'];
    switch ($type) {
      case 'newVan':
        $query = "select head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.contact,cust.position,
        item.itemname,qinfo.disc,qinfo.isamt,qinfo.leadtime2 as leadtime,
        concat(qinfo.outdimlen, ' W = ', qinfo.outdimwd, ' H = ', qinfo.outdimht) as 'Outside Dimensions: L = ',
        concat(qinfo.indimlen, ' W = ', qinfo.indimwd, ' H = ', qinfo.indimht) as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ',qinfo.underchassis as 'Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Secondary Chassis: ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Floor Joist (12in spacing): ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, ' ', qinfo.flrtypeworkmat) as 'Flooring: ',
        qinfo.exterior as 'Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty),'') as 'Insulation: ',
        concat(qinfo.reardrstype, ' ', qinfo.reardrslock, ' ', qinfo.reardrshinger, ' ', qinfo.reardrsseals, ' ', qinfo.reardrsrem) as 'Rear Doors: ',
        concat(qinfo.sidedrstype, ' ', qinfo.sidedrslock, ' ', qinfo.sidedrshinger, ' ', qinfo.sidedrsseals, ' ', qinfo.sidedrsrem) as 'Side Doors: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Exterior: ',
        qinfo.normlights as 'Room Lights: ',
        qinfo.upclrlights as 'Upper ',qinfo.lowclrlights as 'Lower ',
        qinfo.paintcover as 'Painting Cover: ',qinfo.bodycolor as 'Painting Body Color: ',
        qinfo.flrcolor as 'Painting Floor Color: ',qinfo.unchassiscolor as 'Painting Under Chassis: ',
        qinfo.paintroof as 'Painting Roof: ',qinfo.exterior as 'Painting Exterior: ',
        qinfo.interior as 'Painting Interior: ',
        qinfo.sideguards as 'Sideguards: '
        from qthead as head
        left join client as cust on cust.client=head.client
        left join qtinfo as qinfo on qinfo.trno=head.trno
        left join item on item.itemid=qinfo.itemid
        where head.trno='$trno'
        union all
        select head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.contact,cust.position,
        item.itemname,qinfo.disc,qinfo.isamt,qinfo.leadtime2 as leadtime,
        concat(qinfo.outdimlen, ' W = ', qinfo.outdimwd, ' H = ', qinfo.outdimht) as 'Outside Dimensions: L = ',
        concat(qinfo.indimlen, ' W = ', qinfo.indimwd, ' H = ', qinfo.indimht) as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ',qinfo.underchassis as 'Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Secondary Chassis: ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Floor Joist (12in spacing): ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, qinfo.flrtypeworkmat) as 'Flooring: ',
        qinfo.exterior as 'Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty),'') as 'Insulation: ',
        concat(qinfo.reardrstype, ' ', qinfo.reardrslock, ' ', qinfo.reardrshinger, ' ', qinfo.reardrsseals, ' ', qinfo.reardrsrem) as 'Rear Doors: ',
        concat(qinfo.sidedrstype, ' ', qinfo.sidedrslock, ' ', qinfo.sidedrshinger, ' ', qinfo.sidedrsseals, ' ', qinfo.sidedrsrem) as 'Side Doors: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Exterior: ',
        qinfo.normlights as 'Room Lights: ',
        qinfo.upclrlights as 'Upper ',qinfo.lowclrlights as 'Lower ',
        qinfo.paintcover as 'Painting Cover: ',qinfo.bodycolor as 'Painting Body Color: ',
        qinfo.flrcolor as 'Painting Floor Color: ',qinfo.unchassiscolor as 'Painting Under Chassis: ',
        qinfo.paintroof as 'Painting Roof: ',qinfo.exterior as 'Painting Exterior: ',
        qinfo.interior as 'Painting Interior: ',
        qinfo.sideguards as 'Sideguards: '
        from hqthead as head
        left join client as cust on cust.client=head.client
        left join hqtinfo as qinfo on qinfo.trno=head.trno
        left join item on item.itemid=qinfo.itemid
        where head.trno='$trno'";
        break;
      case 'repair':
        $query = "select head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.contact,cust.position,
        head.brand as 'Truck Info: Brand: ',head.model as 'Truck Info: Model: ',
        qinfo.plateno as 'Truck Info: Plate No.: ',
        item.itemname,qinfo.disc,qinfo.isamt,qinfo.leadtime2 as leadtime,
        ifnull(concat(qinfo.outdimlen, if(qinfo.outdimwd = '', '', concat(' W = ', qinfo.outdimwd)), if(qinfo.outdimht = '', '', concat(' H = ', qinfo.outdimht))),'') as 'Outside Dimensions: L = ',
        ifnull(concat(qinfo.indimlen, if(qinfo.indimwd = '', '', concat(' W = ', qinfo.indimwd)), if(qinfo.indimht = '', '', concat(' H = ', qinfo.indimht))),'') as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ', qinfo.underchassis as 'Repair Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Repair Secondary Chassis: ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Repair Floor Joist (12in spacing): ',
        '-' as 'Structural Repairs: ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, ' ', qinfo.flrtypeworkmat) as 'Repair Flooring: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Repair Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Repair Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Repair Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty), '') as 'Install Insulation: ',
        concat(qinfo.reardrstype, ' ', qinfo.reardrslock, ' ', qinfo.reardrshinger, ' ', qinfo.reardrsseals, ' ', qinfo.reardrsrem) as 'Fabricate and Install Rear Doors: ',
        concat(qinfo.sidedrstype, ' ', qinfo.sidedrslock, ' ', qinfo.sidedrshinger, ' ', qinfo.sidedrsseals, ' ', qinfo.sidedrsrem) as 'Fabricate and Install Side Doors: ',
        qinfo.normlights as 'Repair Room Lights: ',
        concat(if(qinfo.upclrlights = '', '', concat('Upper ', qinfo.upclrlights)), if(qinfo.upclrlights = '', '', if(qinfo.lowclrlights = '', '', ', ')), if(qinfo.lowclrlights = '', '', concat('Lower ',qinfo.lowclrlights))) as 'Repair Clearance Lights: ',
        qinfo.paintcover as 'Painting Cover: ',qinfo.bodycolor as 'Painting Body Color: ',
        qinfo.flrcolor as 'Painting Floor Color: ',qinfo.unchassiscolor as 'Painting Under Chassis: ',
        qinfo.paintroof as 'Painting Roof: ',qinfo.exterior as 'Painting Exterior: ',
        qinfo.interior as 'Painting Interior: ',
        qinfo.sideguards as 'Install Sideguards: ',qinfo.reseal as 'Re-seal: '
        from qthead as head
        left join client as cust on cust.client=head.client
        left join qtinfo as qinfo on qinfo.trno=head.trno
        left join item on item.itemid=qinfo.itemid
        left join model_masterfile as model on model.model_id=item.model
        left join frontend_ebrands as brand on brand.brandid=item.brand
        where head.trno=" . $trno . "
        union all
        select head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.contact,cust.position,
        head.brand as 'Truck Info: Brand: ',head.model as 'Truck Info: Model: ',
        qinfo.plateno as 'Truck Info: Plate No.: ',
        item.itemname,qinfo.disc,qinfo.isamt,qinfo.leadtime2 as leadtime,
        ifnull(concat(qinfo.outdimlen, if(qinfo.outdimwd = '', '', concat(' W = ', qinfo.outdimwd)), if(qinfo.outdimht = '', '', concat(' H = ', qinfo.outdimht))),'') as 'Outside Dimensions: L = ',
        ifnull(concat(qinfo.indimlen, if(qinfo.indimwd = '', '', concat(' W = ', qinfo.indimwd)), if(qinfo.indimht = '', '', concat(' H = ', qinfo.indimht))),'') as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ',qinfo.underchassis as 'Repair Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Repair Secondary Chassis: ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Repair Floor Joist (12in spacing): ',
        '-' as 'Structural Repairs: ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, ' ', qinfo.flrtypeworkmat) as 'Repair Flooring: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Repair Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Repair Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Repair Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty), '') as 'Install Insulation: ',
        concat(qinfo.reardrstype, ' ', qinfo.reardrslock, ' ', qinfo.reardrshinger, ' ', qinfo.reardrsseals, ' ', qinfo.reardrsrem) as 'Fabricate and Install Rear Doors: ',
        concat(qinfo.sidedrstype, ' ', qinfo.sidedrslock, ' ', qinfo.sidedrshinger, ' ', qinfo.sidedrsseals, ' ', qinfo.sidedrsrem) as 'Fabricate and Install Side Doors: ',
        qinfo.normlights as 'Repair Room Lights: ',
        concat(if(qinfo.upclrlights = '', '', concat('Upper ', qinfo.upclrlights)), if(qinfo.upclrlights = '', '', if(qinfo.lowclrlights = '', '', ', ')), if(qinfo.lowclrlights = '', '', concat('Lower ',qinfo.lowclrlights))) as 'Repair Clearance Lights: ',
        qinfo.paintcover as 'Painting Cover: ',qinfo.bodycolor as 'Painting Body Color: ',
        qinfo.flrcolor as 'Painting Floor Color: ',qinfo.unchassiscolor as 'Painting Under Chassis: ',
        qinfo.paintroof as 'Painting Roof: ',qinfo.exterior as 'Painting Exterior: ',
        qinfo.interior as 'Painting Interior: ',
        qinfo.sideguards as 'Install Sideguards: ',qinfo.reseal as 'Re-seal: '
        from hqthead as head
        left join client as cust on cust.client=head.client
        left join hqtinfo as qinfo on qinfo.trno=head.trno
        left join item on item.itemid=qinfo.itemid
        left join model_masterfile as model on model.model_id=item.model
        left join frontend_ebrands as brand on brand.brandid=item.brand
        where head.trno=" . $trno;
        break;
    }


    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn 

  public function reportplotting($params, $data)
  {

    if ($params['params']['dataparams']['reporttype'] == "newVan") {
      return $this->newVan_PDF($params, $data);
    } else if ($params['params']['dataparams']['reporttype'] == "repair") {
      return $this->repair_PDF($params, $data);
    }
  }

  public function newVan_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $font11 = 11;
    $font9 = 9;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle('Quotation New Van');
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject('New Van Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1200]);
    PDF::SetMargins(40, 40);


    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n", '', 'C');

    PDF::MultiCell(0, 0, "QUOTATION\n\n\n", '', 'C');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(40, 0, "Date:", '', 'L', false, 0, '', '');
    PDF::MultiCell(70, 0, $data[0]['dateid'], '', 'L', false, 0, '', '');
    PDF::MultiCell(400, 0, '', '', 'R', false, 0, '', '');
    PDF::MultiCell(60, 0, 'Quote No.', '', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, $data[0]['docno'], '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, "Mr./Ms.:", '', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, $data[0]['contact'], 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, "Position:", '', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, $data[0]['position'], 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(100, 0, "Company Name:", '', 'L', false, 0, '', '');
    PDF::SetFont($font, 'U', $font11);
    PDF::MultiCell(700, 0, $data[0]['clientname'], '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(150, 0, '', '', 'L', false);
    PDF::MultiCell(150, 0, 'Greetings!', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(470, 0, "We thank you for giving us the opportunity to quote best price for your requirements for:", '', 'L', false, 0, '', '');
    PDF::SetFont($font, 'U', $font11);
    PDF::MultiCell(330, 0, $data[0]['itemname'], '', 'L', false);

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(200, 0, '', '', 'L', false);
    PDF::MultiCell(200, 0, 'The following are the scope of work:', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');
  }

  public function newVan_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $trno = $params['params']['dataid'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $font11 = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->newVan_header_PDF($params, $data);

    $txtBullet = 'a';
    foreach ($data as $key => $value) {
      $count = 0;
      $clear = 0;
      $clr = '';
      $paint = 0;
      foreach ($value as $key => $scope) {

        if ($key != 'docno' && $key != 'dateid' && $key != 'clientname' && $key != 'tel' && $key != 'position' && $key != 'itemname' && $key != 'disc' && $key != 'isamt' && $key != 'leadtime' && $key != 'contact') {
          if ($scope != '') {

            switch ($key) {
              case 'Upper ':
              case 'Lower ':
                PDF::SetFont($font, '', $font11);
                if ($clear == 0) {
                  $clr = "Clearance Lights: Upper " . $scope;
                  $clear = 1;
                } else {
                  $clr .= ", Lower " . $scope;
                }
                if ($clear == 1 && $clr != '' && $key == 'Lower ') {
                  $count += 1;
                  PDF::MultiCell(720, 0, $count . ") " . $clr, '', 'L', false, 1, '', '');
                }
                break;
              case 'Flooring: ':
              case 'Interior Ceiling and Doors: ':
                $count += 1;
                $txtBullet = 'a';
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(720, 0, $count . ") " . $key . $scope, '', 'L', false, 1, '', '');
                $label = '';
                $type = 0;
                if ($key == 'Flooring: ') {
                  $type = 0;
                } elseif ($key == 'Interior Ceiling and Doors: ') {
                  $type = 1;
                }
                $addon = $this->get_add_on($trno, $type);
                if (!empty($addon)) {
                  if ($key == 'Flooring: ') {
                    $label = ' Floor Add-ons: ';
                  } elseif ($key == 'Interior Ceiling and Doors: ') {
                    $label = ' Interior Add-ons: ';
                  }
                  $count += 1;
                  PDF::SetFont($font, '', $font11);
                  PDF::MultiCell(720, 0, $count . ")" . $label, '', 'L', false, 1, '', '');
                  $this->addonDisplay($addon, 'addons');
                }

                break;


              default:
                if (substr($key, 0, 8) == 'Painting') {
                  $labelcount = strlen($key);
                  $label = substr($key, 8, $labelcount);
                  if ($paint == 0) {
                    $count += 1;
                    PDF::SetFont($font, '', $font11);
                    PDF::MultiCell(720, 0, $count . ") Painting", '', 'L', false, 1, '', '');
                    $paint = 1;
                  }

                  PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . $label . $scope, '', 'L', false, 1, '', '');
                  $txtBullet++;
                } else {
                  $count += 1;
                  PDF::SetFont($font, '', $font11);
                  PDF::MultiCell(720, 0, $count . ") " . $key . $scope, '', 'L', false, 1, '', '');
                }

                break;
            }
          }
        }
      }
      $count += 1;
      PDF::SetFont($font, '', $font11);
      PDF::MultiCell(720, 0, $count . ") Accessories: ", '', 'L', false, 1, '', '');
      $addon = $this->get_add_on($trno, 2);
      $this->addonDisplay($addon, 'addons');

      $count += 1;
      PDF::SetFont($font, '', $font11);
      PDF::MultiCell(720, 0, $count . ") Notes: ", '', 'L', false, 1, '', '');
      $addon = $this->get_add_on($trno, 4);
      $this->addonDisplay($addon, 'rem');
    }
    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);

    PDF::MultiCell(720, 0, "Price for this work is <u>P&nbsp;&nbsp;&nbsp;&nbsp;" . number_format($data[0]['isamt'], 2) . "&nbsp;&nbsp;&nbsp;&nbsp;</u>&nbsp;&nbsp;
    <b>We can offer this at discounted price of <u>P&nbsp;&nbsp;&nbsp;&nbsp;" . ($data[0]['disc'] == '' ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : $data[0]['disc'] . "&nbsp;&nbsp;&nbsp;&nbsp;") . "</u>.</b>", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "Lead time for this work is <u>&nbsp;&nbsp;&nbsp;&nbsp;" . $data[0]['leadtime'] . "&nbsp;&nbsp;&nbsp;&nbsp;</u>working days from start of work. Due to full production schedule, work may not immediately begin once PO and signed quotation is received. Please coordinate with us on the schedule of work.", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "Signed conforme of quotation or Purchase Order and 50% of downpayment is required to start work.", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "The price in this quotation is valid within 15 days from the date above.", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "Looking forward to your favorable reply to this quotation.", '', 'L', false, 1, '', '', true, 0, true);

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(600, 0, "Thank you and best regards,", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(200, 0, "Conforme:", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(210, 0, "Customer's Signature & Printed Name", 'T', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    $txt = "Please send back through fax or E-mail the signed and approved copy of this quotation. We will not start any work without receiving a signed/approved copy and purchase order. Kindly contact us for any additional items or alterations to the specifications. These may require recalculation of the quotation.";
    PDF::MultiCell(720, 0, $txt, '', 'L', false, 1, '', '', true, 0, true);


    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function repair_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $font11 = 11;
    $font9 = 9;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle('Quotation Repair');
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject('Quotation Repair');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1200]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n", '', 'C');

    PDF::MultiCell(0, 0, "QUOTATION\n\n\n", '', 'C');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(40, 0, "Date:", '', 'L', false, 0, '', '');
    PDF::MultiCell(70, 0, $data[0]['dateid'], '', 'L', false, 0, '', '');
    PDF::MultiCell(400, 0, '', '', 'R', false, 0, '', '');
    PDF::MultiCell(60, 0, 'Quote No.', '', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, $data[0]['docno'], '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, "Mr./Ms.:", '', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, $data[0]['contact'], 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, "Position:", '', 'L', false, 0, '', '');
    PDF::MultiCell(150, 0, $data[0]['position'], 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(100, 0, "Company Name:", '', 'L', false, 0, '', '');
    PDF::SetFont($font, 'U', $font11);
    PDF::MultiCell(700, 0, $data[0]['clientname'], '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(150, 0, '', '', 'L', false);
    PDF::MultiCell(150, 0, 'Greetings!', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(470, 0, "We thank you for giving us the opportunity to quote best price for your requirements for:", '', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $font11);
    PDF::MultiCell(50, 0, "Repair of", '', 'L', false, 0, '', '');
    PDF::SetFont($font, 'U', $font11);
    PDF::MultiCell(200, 0, "&nbsp;&nbsp;&nbsp;" . $data[0]['itemname'] . "&nbsp;&nbsp;&nbsp;", '', 'L', false, 1, '', '', true, 0, true);
    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(250, 0, 'The following are the scope of work:', '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');
  }

  public function repair_PDF($params, $data)
  {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $trno = $params['params']['dataid'];
    $count = $page = 35;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $font11 = 11;
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->repair_header_PDF($params, $data);


    $txtBullet = 'a';
    foreach ($data as $key => $value) {
      $count = 0;
      $clear = 0;
      $clr = '';
      $paint = 0;
      $truck = 0;
      $tbl = '';
      $isposted = $this->othersClass->isposted2($trno, 'transnum');
      if ($isposted) {
        $tbl = 'hqtinfo';
      } else {
        $tbl = 'qtinfo';
      }
      foreach ($value as $key => $scope) {

        if ($key != 'docno' && $key != 'dateid' && $key != 'clientname' && $key != 'tel' && $key != 'position' && $key != 'leadtime' && $key != 'disc' && $key != 'itemname' && $key != 'isamt' && $key != 'contact') {
          if ($scope != '') {

            switch ($key) {
              case 'Repair Room Lights: ':
                $count += 1;
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(720, 0, $count . ") " . $key . $scope, '', 'L');
                $roomlightsinfo = $this->coreFunctions->getfieldvalue($tbl, "lightsrepair", "trno=?", [$trno]);
                $txtBullet = 'a';
                if (!empty($roomlightsinfo)) {
                  $rm = explode(',', $roomlightsinfo);
                  if (!empty($rm)) {
                    for ($r = 0; $r < count($rm); $r++) {
                      $rmm = trim($rm[$r]);
                      if ($r != 0) $txtBullet++;
                      if (strtolower(substr($rmm, 0, 6)) != 'repair') {
                        PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . 'Repair ' . $rmm, '', 'L');
                      } else {
                        PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . ucfirst($rmm), '', 'L');
                      }
                    }
                  }
                }
                break;

              case 'Repair Clearance Lights: ':
                $count += 1;
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(720, 0, $count . ") " . $key . $scope, '', 'L');
                $txtBullet = 'a';
                $uplowinfo = $this->coreFunctions->getfieldvalue($tbl, 'clrlightsrepair', "trno=?", [$trno]);
                if (!empty($uplowinfo)) {
                  $uplow = explode(',', $uplowinfo);
                  if (!empty($uplow)) {
                    for ($i = 0; $i < count($uplow); $i++) {
                      $uplows = trim($uplow[$i]);
                      if ($i != 0) $txtBullet++;
                      if (strtolower(substr($uplows, 0, 6)) != 'repair') {
                        PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . 'Repair ' . $uplows, '', 'L');
                      } else {
                        PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . ucfirst($uplows), '', 'L');
                      }
                    }
                  }
                }
                break;
              case 'Structural Repairs: ':
                $count += 1;
                PDF::SetFont($font, '', $font11);
                $repairs = $this->get_repairs($trno);
                if (!empty($repairs)) {
                  PDF::MultiCell(720, 0, $count . ") " . $key, '', 'L', false, 1, '', '');
                  $label = '';
                  $this->repairsDisplay($repairs);
                }
                break;

              case 'Repair Flooring: ':
              case 'Repair Interior Ceiling and Doors: ':
                $count += 1;
                $txtBullet = 'a';
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(720, 0, $count . ") " . $key . $scope, '', 'L', false, 1, '', '');
                $label = '';
                $type = 0;
                if ($key == 'Repair Flooring: ') {
                  $type = 0;
                } else if ($key == 'Repair Interior Ceiling and Doors: ') {
                  $type = 1;
                }
                $addon = $this->get_add_on($trno, $type);
                if (!empty($addon)) {
                  if ($key == 'Repair Flooring: ') {
                    $label = ' Floor Add-ons: ';
                  } elseif ($key == 'Repair Interior Ceiling and Doors: ') {
                    $label = ' Interior Add-ons: ';
                  }
                  $count += 1;
                  PDF::SetFont($font, '', $font11);
                  PDF::MultiCell(720, 0, $count . ")" . $label, '', 'L', false, 1, '', '');
                  $this->addonDisplay($addon, 'addons');
                }
                break;

              default:
                if (substr($key, 0, 8) == 'Painting') {
                  $labelcount = strlen($key);
                  $label = substr($key, 8, $labelcount);
                  if ($paint == 0) {
                    $count += 1;
                    PDF::SetFont($font, '', $font11);
                    PDF::MultiCell(720, 0, $count . ") Re-Painting", '', 'L', false, 1, '', '');
                    $paint = 1;
                  }

                  PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . $label . $scope, '', 'L', false, 1, '', '');
                  $txtBullet++;
                } elseif (substr($key, 0, 10) == 'Truck Info') {
                  $count += 1;
                  $labelcount = strlen($key);
                  $label = substr($key, 10, $labelcount);
                  if ($truck == 0) {
                    PDF::SetFont($font, '', $font11);
                    PDF::MultiCell(720, 0, $count . ") Truck Info: ", '', 'L', false, 1, '', '');
                    $truck = 1;
                  }

                  PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . $label . $scope, '', 'L', false, 1, '', '');
                  $txtBullet++;
                } else {
                  if (!ctype_space($scope)) {
                    $count += 1;
                    $txtBullet = 'a';
                    PDF::SetFont($font, '', $font11);
                    PDF::MultiCell(720, 0, $count . ") " . $key . $scope, '', 'L', false, 1, '', '');
                  }
                }

                break;
            }
          }
        }
      }
      $count += 1;
      PDF::SetFont($font, '', $font11);
      PDF::MultiCell(720, 0, $count . ") Install Add Ons: ", '', 'L', false, 1, '', '');
      $addon = $this->get_add_on($trno, 2);
      $this->addonDisplay($addon, 'addons');

      $count += 1;
      PDF::SetFont($font, '', $font11);
      PDF::MultiCell(720, 0, $count . ") Notes/Others: ", '', 'L', false, 1, '', '');
      $addon = $this->get_add_on($trno, 4);
      $this->addonDisplay($addon, 'rem');
    }
    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);

    PDF::MultiCell(720, 0, "Price for this work is <u>P&nbsp;&nbsp;&nbsp;&nbsp;" . number_format($data[0]['isamt'], 2) . "&nbsp;&nbsp;&nbsp;&nbsp;</u>&nbsp;&nbsp;
    <b>We can offer this at discounted price of <u>P&nbsp;&nbsp;&nbsp;&nbsp;" . ($data[0]['disc'] == '' ? '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' : $data[0]['disc'] . "&nbsp;&nbsp;&nbsp;&nbsp;") . "</u>.</b>", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "Lead time for this work is <u>&nbsp;&nbsp;&nbsp;&nbsp;" . $data[0]['leadtime'] . "&nbsp;&nbsp;&nbsp;&nbsp;</u>working days from start of work. Due to full production schedule, work may not immediately begin once PO and signed quotation is received. Please coordinate with us on the schedule of work.", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "Signed conforme of quotation or Purchase Order and 50% of downpayment is required to start work.", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "The price in this quotation is valid within 15 days from the date above.", '', 'L', false, 1, '', '', true, 0, true);
    PDF::MultiCell(720, 0, '');
    PDF::MultiCell(720, 0, "Looking forward to your favorable reply to this quotation.", '', 'L', false, 1, '', '', true, 0, true);

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "Thank you and best regards,", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "Conforme:", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(210, 0, "Customer's Signature & Printed Name", 'T', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, "", '', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    $txt = "Please send back through fax or E-mail the signed and approved copy of this quotation. We will not start any work without receiving a signed/approved copy and purchase order. Kindly contact us for any additional items or alterations to the specifications. These may require recalculation of the quotation.";
    PDF::MultiCell(720, 0, $txt, '', 'L', false, 1, '', '', true, 0, true);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function addonDisplay($addon, $col)
  {
    $txtBullet = 'a';
    for ($i = 0; $i < count($addon); $i++) {
      if ($addon[$i][$col] != "") {
        if ($i != 0) $txtBullet++;
        PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . $addon[$i][$col], '', 'L', false, 1, '', '');
      }
    }
  }

  public function get_add_on($trno, $type)
  {
    $qry = "select line,qty,addons,rem from qtaddons where trno='" . $trno . "' and addontype='" . $type . "' union all select line,qty,addons,rem from hqtaddons where trno='" . $trno . "' and addontype='" . $type . "' order by line";

    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  }

  public function get_repairs($trno)
  {
    $qry = "select line, side, parts from qtaddons where trno='" . $trno . "' and addontype=3 and (side<>'' or parts<>'') union all select line, side, parts from hqtaddons where trno='" . $trno . "' and addontype=3 and (side<>'' or parts<>'') order by line";
    $result = $this->coreFunctions->opentable($qry);
    return $result;
  }

  public function repairsDisplay($repairs)
  {
    $txtBullet = 'a';
    for ($i = 0; $i < count($repairs); $i++) {
      if ($i != 0) $txtBullet++;
      PDF::MultiCell(720, 0, "       " . $txtBullet . ".   " . $repairs[$i]->side . " " . $repairs[$i]->parts, '', 'L', false, 1, '', '');
    }
  }
}
