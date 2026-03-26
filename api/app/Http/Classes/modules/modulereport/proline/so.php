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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class so
{

  private $modulename = "Job Order";
  private $coreFunctions;
  private $companysetup;
  private $fieldClass;
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
    $fields = ['radioprint', 'radioreporttype', 'prepared', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'received.label', 'Checked By: ');
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
        '' as received
        "
    );
  }

  public function report_default_query($params, $trno)
  {
    $type = $params['params']['dataparams']['reporttype'];
    switch ($type) {
      case 'newVan':
        $query = "select qhead.trno,qhead.docno as qdocno,head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.tel,cust.position,head.rem as notes,
        qinfo.plateno as truckplateno,brand.brand_desc as truckbrand,
        model.model_name as truckmodel,
        concat(qinfo.outdimlen, if(qinfo.outdimwd = '', '', concat(' W = ', qinfo.outdimwd)), if(qinfo.outdimht = '', '', concat(' H = ', qinfo.outdimht))) as 'Outside Dimensions: L = ',
        concat(qinfo.indimlen, if(qinfo.indimwd = '', '', concat(' W = ', qinfo.indimwd)), if(qinfo.indimht = '', '', concat(' H = ', qinfo.indimht))) as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ',qinfo.underchassis as 'Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Secondary Chassis: ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Floor Joist (12in spacing): ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, ' ', qinfo.flrtypeworkmat) as 'Flooring: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty), '') as 'Insulation: ',
        '-' as 'Rear Doors: ',
        '-' as 'Side Doors: ',
        qinfo.normlights as 'Room Lights: ',
        qinfo.upclrlights as 'Upper ',qinfo.lowclrlights as 'Lower ',
        '-' as 'Painting: ',
        qinfo.sideguards as 'Sideguards: ', qinfo.reseal as 'Re-seal: '
        from sohead as head
        left join hqthead as qhead on qhead.sotrno=head.trno
        left join client as cust on cust.client=head.client
        left join hqtinfo as qinfo on qinfo.trno=qhead.trno
        left join item on item.itemid=qinfo.itemid
        left join model_masterfile as model on model.model_id=item.model
        left join frontend_ebrands as brand on brand.brandid=item.brand
        where head.trno='$trno'
        union all 
        select qhead.trno,qhead.docno as qdocno,head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.tel,cust.position,head.rem as notes,
        qinfo.plateno as truckplateno,brand.brand_desc as truckbrand,
        model.model_name as truckmodel,
        concat(qinfo.outdimlen, if(qinfo.outdimwd = '', '', concat(' W = ', qinfo.outdimwd)), if(qinfo.outdimht = '', '', concat(' H = ', qinfo.outdimht))) as 'Outside Dimensions: L = ',
        concat(qinfo.indimlen, if(qinfo.indimwd = '', '', concat(' W = ', qinfo.indimwd)), if(qinfo.indimht = '', '', concat(' H = ', qinfo.indimht))) as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ',qinfo.underchassis as 'Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Secondary Chassis: ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Floor Joist (12in spacing): ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, ' ', qinfo.flrtypeworkmat) as 'Flooring: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty), '') as 'Insulation: ',
        '-' as 'Rear Doors: ',
        '-' as 'Side Doors: ',
        qinfo.normlights as 'Room Lights: ',
        qinfo.upclrlights as 'Upper ',qinfo.lowclrlights as 'Lower ',
        '-' as 'Painting: ',
        qinfo.sideguards as 'Sideguards: ', qinfo.reseal as 'Re-seal: '
        from hsohead as head
        left join hqthead as qhead on qhead.sotrno=head.trno
        left join client as cust on cust.client=head.client
        left join hqtinfo as qinfo on qinfo.trno=qhead.trno
        left join item on item.itemid=qinfo.itemid
        left join model_masterfile as model on model.model_id=item.model
        left join frontend_ebrands as brand on brand.brandid=item.brand
        where head.trno='$trno'";
        break;
      case 'repair':
        $query = "select qhead.trno,qhead.docno as qdocno,head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.tel,cust.position,head.rem as notes,
        qinfo.plateno as truckplateno,brand.brand_desc as truckbrand,
        model.model_name as truckmodel,
        concat(qinfo.outdimlen, if(qinfo.outdimwd = '', '', concat(' W = ', qinfo.outdimwd)), if(qinfo.outdimht = '', '', concat(' H = ', qinfo.outdimht))) as 'Outside Dimensions: L = ',
        concat(qinfo.indimlen, if(qinfo.indimwd = '', '', concat(' W = ', qinfo.indimwd)), if(qinfo.indimht = '', '', concat(' H = ', qinfo.indimht))) as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ',qinfo.underchassis as 'Repair Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Repair Secondary Chassis (12in spacing): ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Repair Floor Joist: ',
        '-' as 'Structural Repairs: ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, ' ', qinfo.flrtypeworkmat) as 'Repair Flooring: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Repair Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Repair Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Repair Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty), '') as 'Install Insulation: ',
        concat(qinfo.reardrstype, ' ', qinfo.reardrslock, ' ', qinfo.reardrshinger, ' ', qinfo.reardrsseals) as 'Fabricate and Install Rear Doors: ',
        concat(qinfo.sidedrstype, ' ', qinfo.sidedrslock, ' ', qinfo.sidedrshinger, ' ', qinfo.sidedrsseals) as 'Fabricate and Install Side Doors: ',
        '-' as 'Repair Room Lights: ',
        '-' as 'Repair Clearance Lights: ',
        '-' as 'Re-Painting: ',
        qinfo.exterior as 'Painting Exterior: ',
        qinfo.interior as 'Painting Interior: ',
        qinfo.sideguards as 'Install Sideguards: ',qinfo.reseal as 'Re-seal: '
        from sohead as head
        left join hqthead as qhead on qhead.sotrno=head.trno
        left join client as cust on cust.client=head.client
        left join hqtinfo as qinfo on qinfo.trno=qhead.trno
        left join item on item.itemid=qinfo.itemid
        left join model_masterfile as model on model.model_id=item.model
        left join frontend_ebrands as brand on brand.brandid=item.brand
        where head.trno='$trno'
        union all
        select qhead.trno,qhead.docno as qdocno,head.docno,date_format(head.dateid,'%Y-%m-%d') as dateid,cust.clientname,cust.tel,cust.position,head.rem as notes,
        qinfo.plateno as truckplateno,brand.brand_desc as truckbrand,
        model.model_name as truckmodel,
        concat(qinfo.outdimlen, if(qinfo.outdimwd = '', '', concat(' W = ', qinfo.outdimwd)), if(qinfo.outdimht = '', '', concat(' H = ', qinfo.outdimht))) as 'Outside Dimensions: L = ',
        concat(qinfo.indimlen, if(qinfo.indimwd = '', '', concat(' W = ', qinfo.indimwd)), if(qinfo.indimht = '', '', concat(' H = ', qinfo.indimht))) as 'Inside Dimensions: L = ',
        qinfo.chassiswd as 'Chassis Width: ',qinfo.underchassis as 'Repair Under Chassis Runner: ',
        concat(qinfo.secchassisqty, ' ', qinfo.secchassissz, ' ', qinfo.secchassistk, ' ', qinfo.secchassismat) as 'Repair Secondary Chassis (12in spacing): ',
        concat(qinfo.flrjoistqty, ' ', qinfo.flrjoistqtysz, ' ', qinfo.flrjoistqtytk, ' ', qinfo.flrjoistqtymat) as 'Repair Floor Joist: ',
        '-' as 'Structural Repairs: ',
        concat(qinfo.flrtypework, ' ', qinfo.flrtypeworktk, ' ', qinfo.flrtypeworkty, ' ', qinfo.flrtypeworkmat) as 'Repair Flooring: ',
        concat(qinfo.exttypework, ' ', qinfo.exttypeworkqty, ' ', qinfo.exttypeworkty) as 'Repair Exterior: ',
        concat(qinfo.inwalltypework, ' ', qinfo.inwalltypeworkqty, ' ', qinfo.inwalltypeworktk, ' ', qinfo.inwalltypeworkty) as 'Repair Interior Walls: ',
        concat(qinfo.inceiltypework, ' ', qinfo.inceiltypeworkqty, ' ', qinfo.inceiltypeworktk, ' ', qinfo.inceiltypeworkty) as 'Repair Interior Ceiling and Doors: ',
        ifnull(concat(qinfo.insultk, ' ', qinfo.insulty), '') as 'Install Insulation: ',
        concat(qinfo.reardrstype, ' ', qinfo.reardrslock, ' ', qinfo.reardrshinger, ' ', qinfo.reardrsseals) as 'Fabricate and Install Rear Doors: ',
        concat(qinfo.sidedrstype, ' ', qinfo.sidedrslock, ' ', qinfo.sidedrshinger, ' ', qinfo.sidedrsseals) as 'Fabricate and Install Side Doors: ',
        '-' as 'Repair Room Lights: ',
        '-' as 'Repair Clearance Lights: ',
        '-' as 'Re-Painting: ',
        qinfo.exterior as 'Painting Exterior: ',
        qinfo.interior as 'Painting Interior: ',
        qinfo.sideguards as 'Install Sideguards: ',qinfo.reseal as 'Re-seal: '
        from hsohead as head
        left join hqthead as qhead on qhead.sotrno=head.trno
        left join client as cust on cust.client=head.client
        left join hqtinfo as qinfo on qinfo.trno=qhead.trno
        left join item on item.itemid=qinfo.itemid
        left join model_masterfile as model on model.model_id=item.model
        left join frontend_ebrands as brand on brand.brandid=item.brand
        where head.trno='$trno'";
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

    PDF::SetTitle('JO New Van');
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject('JO New Van');
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

    PDF::MultiCell(720, 0, 'Van Fabrication Job Order' . "\n\n\n", '', 'C');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(70, 0, 'JO No.:', '', 'L', false, 0, '', '');
    PDF::MultiCell(110, 0, $data[0]['docno'], 'B', 'L', false, 0, '', '');
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(70, 0, 'Quote No.', '', 'L', false, 0, '', '');
    PDF::MultiCell(110, 0, $data[0]['qdocno'], 'B', 'L', false, 0, '', '');
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(60, 0, "Date:", '', 'L', false, 0, '', '');
    PDF::MultiCell(100, 0, $data[0]['dateid'], 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(100, 0, 'Customer Name', '', 'L', false, 0, '', '');
    PDF::MultiCell(620, 0, $data[0]['clientname'], 'B', 'L', false, 1, '', '');


    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, 'Brand.:', '', 'L', false, 0, '', '');
    PDF::MultiCell(196, 0, $data[0]['truckbrand'], 'B', 'L', false, 0, '', '');

    PDF::MultiCell(50, 0, 'Model.:', '', 'L', false, 0, '', '');
    PDF::MultiCell(196, 0, $data[0]['truckmodel'], 'B', 'L', false, 0, '', '');
   
    PDF::MultiCell(60, 0, "Plate No.:", '', 'L', false, 0, '', '');
    PDF::MultiCell(166, 0, $data[0]['truckplateno'], 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, 'Notes:', '', 'L', false, 0, '', '');
    PDF::MultiCell(670, 0, $data[0]['notes'], '', 'L', false, 1, '', '');


    PDF::SetFont($fontbold, '', $font11);
    PDF::MultiCell(500, 0, "   Specifications:", 'TBLR', 'L', false, 0, '', '');
    PDF::MultiCell(220, 0, "   PIC", 'TBR', 'L', false, 1, '', '');
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
    $qtrno = 0;

    $tbl = '';
    foreach ($data as $key => $value) {
      $count = 0;
      $clear = 0;
      $clr = '';
      $paint = 0;
      foreach ($value as $key => $scope) {
        if ($key == 'trno') $qtrno = $scope;
        $isposted = $this->othersClass->isposted2($qtrno, 'transnum');
        if ($isposted) {
          $tbl = 'hqtinfo';
        } else {
          $tbl = 'qtinfo';
        }
        if ($key != 'docno' && $key != 'dateid' && $key != 'clientname' && $key != 'tel' && $key != 'position' && $key != 'trno' && $key != 'qdocno' && $key != 'truckbrand' && $key != 'truckmodel' && $key != 'truckplateno' && $key != 'notes') {
          $border = '';
          $pic = '';
          switch ($key) {
            case 'Painting: ':
              $pic = '  Painter: ';
              $border = 'TLR';
              break;
            case 'Outside Dimensions: L = ':
              $pic = '  Bender: ';
              $border = 'TLR';
              break;
            case 'Chassis Width: ':
              $pic = '  Assembler: ';
              $border = 'LR';
              break;
            case 'Under Chassis Runner: ':
              $pic = '  Assembler: ';
              $border = 'TLR';
              break;
            case 'Exterior: ';
              $pic = '  Exterior: ';
              $border = 'TLR';
              break;
            case 'Interior Walls: ';
              $pic = '  Interior: ';
              $border = 'TLR';
              break;
            case 'Rear Doors: ';
              $pic = '  Assembler: ';
              $border = 'TLR';
              break;
            case 'Side Doors: ';
              $pic = '  Door Finishing: ';
              $border = 'LR';
              break;
            case 'Room Lights: ';
              $pic = '  Electrician: ';
              $border = 'TLR';
              break;
            case 'Re-seal: ';
              $pic = '  Sealing: ';
              $border = 'LR';
              break;
            case 'Sideguards: ';
              $pic = '  Mounting: ';
              $border = 'TLR';
              break;
            default:
              $pic = '';
              $border = 'LR';
              break;
          }
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
                PDF::MultiCell(500, 0, "         " . $count . ") " . $clr, $border, 'L', false, 0, '', '');
                PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
              }
              break;

            case 'Flooring: ':
            case 'Interior Ceiling and Doors: ':
              $count += 1;
              $txtBullet = 'a';
              PDF::SetFont($font, '', $font11);
              PDF::MultiCell(500, 0, "         " . $count . ") " . $key . $scope, $border, 'L', false, 0, '', '');
              PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
              $label = '';
              $type = 0;
              if ($key == 'Flooring: ') {
                $label = ' Floor Add-ons: ';
                $type = 0;
              } elseif ($key == 'Interior Ceiling and Doors: ') {
                $label = ' Interior Add-ons: ';
                $type = 1;
              }
              $addon = $this->get_add_on($qtrno, $type);
              $count += 1;
              PDF::SetFont($font, '', $font11);
              PDF::MultiCell(500, 0, "         " . $count . ")" . $label, $border, 'L', false, 0, '', '');
              PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
              $this->addonDisplay($addon, 'addons');

              break;

            case 'Rear Doors: ':
            case 'Side Doors: ':
              $count += 1;
              PDF::SetFont($font, '', $font11);
              PDF::MultiCell(500, 0, "         " . $count . ") " . $key, $border, 'L', false, 0, '', '');
              PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1);
              $rearsides = $this->getRearsides($qtrno, $tbl, $key);
              if (!empty($rearsides)) {
                $txtBullet = 'a';
                $rscount = 0;
                foreach ($rearsides[0] as $k => $p) {
                  if ($p != "") {
                    if ($rscount > 0) $txtBullet++;
                    PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . $p, 'LR', 'L', false, 0);
                    PDF::MultiCell(220, 0, '', 'LR');
                  }
                  $rscount++;
                }
              }
              break;

            case 'Painting: ':
              $count += 1;
              PDF::SetFont($font, '', $font11);
              PDF::MultiCell(500, 0, "         " . $count . ") " . $key, $border, 'L', false, 0, '', '');
              PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1);
              $paints = $this->getPaints($qtrno, $tbl, 'painting');
              if (!empty($paints)) {
                $txtBullet = 'a';
                $pcount = 0;
                foreach ($paints[0] as $k => $p) {
                  if ($p != "") {
                    if ($pcount > 0) $txtBullet++;
                    PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . $k . ' ' . $p, 'LR', 'L', false, 0);
                    PDF::MultiCell(220, 0, '', 'LR');
                  }
                  $pcount++;
                }
              }
              break;


            default:
              if (substr($key, 0, 8) == 'Painting') {
                $labelcount = strlen($key);
                $label = substr($key, 8, $labelcount);
                if ($paint == 0) {
                  $count += 1;
                  PDF::SetFont($font, '', $font11);
                  PDF::MultiCell(500, 0, "         " . $count . ") Painting", "T" . $border, 'L', false, 0, '', '');
                  PDF::MultiCell(220, 0, "  Painter: ", "T" . $border, 'L', false, 1, '', '');
                  $paint = 1;
                }

                PDF::MultiCell(500, 0, "         " . "       " . $txtBullet . ".   " . $label . $scope, $border, 'L', false, 0, '', '');
                PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                $txtBullet++;
              } else {
                if ($key == 'Re-seal: ') {
                  if (!ctype_space($scope)) {
                    if ($scope != "") {
                      $count += 1;
                      PDF::SetFont($font, '', $font11);
                      PDF::MultiCell(500, 0, "         " . $count . ") " . $key . $scope, $border, 'L', false, 0, '', '');
                      PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                    } else {
                      PDF::SetFont($font, '', $font11);
                      PDF::MultiCell(500, 0, "         ", $border, 'L', false, 0, '', '');
                      PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                    }
                  } else {
                    PDF::SetFont($font, '', $font11);
                    PDF::MultiCell(500, 0, '', $border, 'L', false, 0, '', '');
                    PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                  }
                } else {
                  if ($scope != "") {
                    if (!ctype_space($scope)) {
                      $arr_scope = $this->reporter->fixcolumn([$scope], '60', 0);
                      $maxrow = $this->othersClass->getmaxcolumn([$arr_scope]);
                      $count += 1;
                      $brdr = $border;
                      for ($r = 0; $r < $maxrow; $r++) {
                        if ($r > 0) $counta = 0;
                        PDF::SetFont($font, '', $font11);
                        if ($maxrow > 1) {
                          if ($r == 0) {
                            $brdr = $border;
                          } else {
                            $brdr = 'LR';
                          }
                        } else {
                          $brdr = $border;
                        }
                        PDF::MultiCell(500, 0, "         " . ($r == 0 ? $count . ") " . $key : '') . $arr_scope[$r], $brdr, 'L', false, 0, '', '');
                        PDF::MultiCell(220, 0, ($r == 0 ? $pic : ''), $brdr, 'L', false, 1, '', '');
                      }
                   
                    }
                  }
                }
              }

              break;
          }
        }
      }
      $count += 1;
      PDF::SetFont($font, '', $font11);
      PDF::MultiCell(500, 0, "         " . $count . ") Accessories: ", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, "  Finishing: ", 'LR', 'L', false, 1, '', '');
      $addon = $this->get_add_on($qtrno, 2);
      $this->addonDisplay($addon, 'addons');

      $count += 1;
      PDF::SetFont($font, '', $font11);
      PDF::MultiCell(500, 0, "         " . $count . ") Notes: ", 'TLR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, "", 'TLR', 'L', false, 1, '', '');
      $addon = $this->get_add_on($qtrno, 4);
      $this->addonDisplay($addon, 'rem');


      $count += 1;
      PDF::MultiCell(500, 0, "         " . $count . ") Passed quality check", 'LTR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '  QC: ', 'LTR', 'L', false);
      PDF::MultiCell(500, 0, "                a. Sealer and leak test", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
      PDF::MultiCell(500, 0, "                b. Painting quality", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
      PDF::MultiCell(500, 0, "                c. Door functionality", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
      PDF::MultiCell(500, 0, "                d. Lights functionality", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
    }
    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(104, 0, 'Prepared By: ', '', 'L', false, 0, '', '');
    PDF::MultiCell(194, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0, '', '');
    PDF::MultiCell(124, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(104, 0, 'Checked  By: ', '', 'L', false, 0, '', '');
    PDF::MultiCell(194, 0, $params['params']['dataparams']['received'], 'B', 'L', false, 1, '', '');





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

    PDF::SetTitle('JO Repair');
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject('JO Repair');
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

    PDF::MultiCell(720, 0, 'REPAIR JOB ORDER' . "\n\n\n", '', 'C');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(70, 0, 'JO No.:', '', 'L', false, 0, '', '');
    PDF::MultiCell(110, 0, $data[0]['docno'], 'B', 'L', false, 0, '', '');
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(70, 0, 'Quote No.', '', 'L', false, 0, '', '');
    PDF::MultiCell(110, 0, $data[0]['qdocno'], 'B', 'L', false, 0, '', '');
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(60, 0, "Date:", '', 'L', false, 0, '', '');
    PDF::MultiCell(100, 0, $data[0]['dateid'], 'B', 'R', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(100, 0, 'Customer Name', '', 'L', false, 0, '', '');
    PDF::MultiCell(620, 0, $data[0]['clientname'], 'B', 'L', false, 1, '', '');


    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, 'Brand.:', '', 'L', false, 0, '', '');
    PDF::MultiCell(196, 0, $data[0]['truckbrand'], 'B', 'L', false, 0, '', '');

    PDF::MultiCell(50, 0, 'Model.:', '', 'L', false, 0, '', '');
    PDF::MultiCell(196, 0, $data[0]['truckmodel'], 'B', 'L', false, 0, '', '');

    PDF::MultiCell(60, 0, "Plate No.:", '', 'L', false, 0, '', '');
    PDF::MultiCell(166, 0, $data[0]['truckplateno'], 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(50, 0, 'Notes:', '', 'L', false, 0, '', '');
    PDF::MultiCell(670, 0, $data[0]['notes'], '', 'L', false, 1, '', '');


    PDF::SetFont($fontbold, '', $font11);
    PDF::MultiCell(500, 0, "   Specifications:", 'TBLR', 'L', false, 0, '', '');
    PDF::MultiCell(220, 0, "   PIC", 'TBR', 'L', false, 1, '', '');
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

    $tbl = '';
    $txtBullet = 'a';
    foreach ($data as $key => $value) {
      $count = 0;
      $clear = 0;
      $clr = '';
      $paint = 0;
      $truck = 0;
      foreach ($value as $key => $scope) {
        if ($key == 'trno') $qtrno = $scope;
        $isposted = $this->othersClass->isposted2($qtrno, 'transnum');
        if ($isposted) {
          $tbl = 'hqtinfo';
        } else {
          $tbl = 'qtinfo';
        }
        if ($key != 'docno' && $key != 'dateid' && $key != 'clientname' && $key != 'tel' && $key != 'position' && $key != 'trno' && $key != 'qdocno' && $key != 'truckbrand' && $key != 'truckmodel' && $key != 'truckplateno' && $key != 'notes') {
          if ($scope != '') {
            $border = '';
            $pic = '';
            switch ($key) {
              case 'Outside Dimensions: L = ':
                $pic = '';
                $border = 'TLRB';
                break;
              case 'Repair Secondary Chassis (12in spacing): ':
              case 'Repair Floor Joist: ':
              case 'Floor Add-ons: ':
              case 'Repair Flooring: ':
              case 'Repair Interior Ceiling and Doors: ':
              case 'Interior Add-ons':
              case 'Install Insulation: ':
              case 'Repair Clearance Lights: ':
                $pic = '';
                $border = 'LR';
                break;
              case 'Repair Under Chassis Runner: ':
                $pic = '  Repair Team: ';
                $border = 'LR';
                break;
              case 'Re-Painting: ':
                $pic = '  Painter: ';
                $border = 'TLR';
                break;
              case 'Repair Exterior: ';
                $pic = '  Exterior: ';
                $border = 'TLR';
                break;
              case 'Repair Interior Walls: ';
                $pic = '  Interior: ';
                $border = 'TLR';
                break;
              case 'Fabricate and Install Rear Doors: ';
                $pic = '  Assembler: ';
                $border = 'TLR';
                break;
              case 'Fabricate and Install Side Doors: ';
                $pic = '  Door Finishing: ';
                $border = 'LR';
                break;
              case 'Repair Room Lights: ';
                $pic = '  Electrician: ';
                $border = 'TLR';
                break;
              case 'Install Sideguards: ';
                $pic = '  Mounting: ';
                $border = 'TLR';
                break;
              case 'Re-seal: ';
                $pic = '  Sealing: ';
                $border = 'TLR';
                break;
              default:
                $pic = '';
                $border = 'TLRB';
                break;
            }
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
                  PDF::MultiCell(500, 0, "         " . $count . ") " . $clr, $border, 'L', false, 0, '', '');
                  PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                }
                break;

              case 'Repair Clearance Lights: ':
                $count += 1;
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(500, 0, "         " . $count . ") " . $key, $border, 'L', false, 0);
                PDF::MultiCell(220, 0, $pic, $border);
                $txtBullet = 'a';
                $uplowinfo = $this->coreFunctions->getfieldvalue($tbl, 'clrlightsrepair', "trno=?", [$qtrno]);
                $upper = $this->coreFunctions->getfieldvalue($tbl, 'upclrlights', 'trno=?', [$qtrno]);
                $lower = $this->coreFunctions->getfieldvalue($tbl, 'lowclrlights', 'trno=?', [$qtrno]);
                if ($upper != '') {
                  PDF::MultiCell(500, 0, "                " . $txtBullet . ".   Upper " . $upper, 'LR', 'L', false, 0);
                  PDF::MultiCell(220, 0, '', 'LR');
                }
                if ($lower != '') {
                  $txtBullet++;
                  PDF::MultiCell(500, 0, "                " . $txtBullet . ".   Lower " . $lower, 'LR', 'L', false, 0);
                  PDF::MultiCell(220, 0, '', 'LR');
                }
                if (!empty($uplowinfo)) {
                  $uplow = explode(',', $uplowinfo);
                  if (!empty($uplow)) {
                    for ($i = 0; $i < count($uplow); $i++) {
                      $uplows = trim($uplow[$i]);
                      $txtBullet++;
                      if (strtolower(substr($uplows, 0, 6)) != 'repair') {
                        PDF::MultiCell(500, 0, "                " . $txtBullet . ".   Repair " . $uplows, 'LR', 'L', false, 0);
                        PDF::MultiCell(220, 0, '', 'LR');
                      } else {
                        PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . ucfirst($uplows), 'LR', 'L', false, 0);
                        PDF::MultiCell(220, 0, '', 'LR');
                      }
                    }
                  }
                }
                break;

              case 'Re-Painting: ':
                $count += 1;
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(500, 0, "         " . $count . ") " . $key, $border, 'L', false, 0, '', '');
                PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1);
                $paints = $this->getPaints($qtrno, $tbl);
                if (!empty($paints)) {
                  $txtBullet = 'a';
                  $pcount = 0;
                  foreach ($paints[0] as $k => $p) {
                    if ($p != "") {
                      if ($pcount > 0) $txtBullet++;
                      PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . $k . ' ' . $p, 'LR', 'L', false, 0);
                      PDF::MultiCell(220, 0, '', 'LR');
                    }
                    $pcount++;
                  }
                }
                break;

              case 'Repair Room Lights: ':
                $count += 1;
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(500, 0, "         " . $count . ") " . $key, $border, 'L', false, 0, '', '');
                PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1);
                $roomlightsinfo = $this->coreFunctions->getfieldvalue($tbl, "lightsrepair", "trno=?", [$qtrno]);
                $normlights = $this->coreFunctions->getfieldvalue($tbl, "normlights", "trno=?", [$qtrno]);
                if ($normlights != '') {
                  $txtBullet = 'a';
                  PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . $normlights, 'LR', 'L', false, 0);
                  PDF::MultiCell(220, 0, '', 'LR');
                }
                if (!empty($roomlightsinfo)) {
                  if ($normlights != '') {
                    $txtBullet++;
                  } else {
                    $txtBullet = 'a';
                  }
                  $rm = explode(',', $roomlightsinfo);
                  if (!empty($rm)) {
                    for ($r = 0; $r < count($rm); $r++) {
                      $rmm = trim($rm[$r]);
                      if ($r != 0) $txtBullet++;
                      if (strtolower(substr($rmm, 0, 6)) != 'repair') {
                        PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . 'Repair ' . $rmm, 'LR', 'L', false, 0);
                      } else {
                        PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . ucfirst($rmm), 'LR', 'L', false, 0);
                      }
                      PDF::MultiCell(220, 0, '', 'LR');
                    }
                  }
                }
                break;

              case 'Repair Flooring: ':
              case 'Repair Interior Ceiling and Doors: ':
                $count += 1;
                $txtBullet = 'a';
                PDF::SetFont($font, '', $font11);
                PDF::MultiCell(500, 0, "         " . $count . ") " . $key . $scope, $border, 'L', false, 0, '', '');
                PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                $label = '';
                $type = 0;
                if ($key == 'Repair Flooring: ') {
                  $label = ' Floor Add-ons: ';
                  $type = 0;
                } elseif ($key == 'Repair Interior Ceiling and Doors: ') {
                  $label = ' Interior Add-ons: ';
                  $type = 1;
                }
                $addon = $this->get_add_on($qtrno, $type);
                $count += 1;
                PDF::SetFont($font, '', $font11);
                if ($type == 1) {
                  $txt = '';
                  if (!empty($addon)) {
                    foreach ($addon as $a) {
                      if ($txt == '') {
                        $txt = $a['addons'];
                      } else {
                        $txt .= ', ' . $a['addons'];
                      }
                    }
                  }
                  PDF::MultiCell(500, 0, "         " . $count . ") " . $label . ' ' . $txt, $border, 'L', false, 0, '', '');
                  PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                } else {
                  PDF::MultiCell(500, 0, "         " . $count . ") " . $label, $border, 'L', false, 0, '', '');
                  PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                  $this->addonDisplay($addon, 'addons');
                }
                break;

              case 'Structural Repairs: ':
                $count += 1;
                PDF::SetFont($font, '', $font11);
                $repairs = $this->get_repairs($qtrno);
                if (!empty($repairs)) {
                  PDF::MultiCell(500, 0, "         " . $count . ") " . $key, 'LR', 'L', false, 0);
                  PDF::MultiCell(220, 0, '', 'LR');
                  $this->repairsDisplay($repairs);
                }
                break;

              default:
                if (substr($key, 0, 8) == 'Painting') {
                  $labelcount = strlen($key);
                  $label = substr($key, 8, $labelcount);
                  if ($paint == 0) {
                    $count += 1;
                    PDF::SetFont($font, '', $font11);
                    PDF::MultiCell(500, 0, "         " . $count . ") Re-Painting", "T" . 'TLR', 'L', false, 0, '', '');
                    PDF::MultiCell(220, 0, "  Painter:", "T" . 'TLR', 'L', false, 1, '', '');
                    $paint = 1;
                  }
                  PDF::MultiCell(500, 0, "         " . "       " . $txtBullet . ".   " . $label . $scope, 'LR', 'L', false, 0, '', '');
                  PDF::MultiCell(220, 0, $pic, 'LR', 'L', false, 1, '', '');
                  $txtBullet++;
                } elseif (substr($key, 0, 10) == 'Truck Info') {
                  $labelcount = strlen($key);
                  $label = substr($key, 10, $labelcount);
                  if ($truck == 0) {
                    PDF::SetFont($font, '', $font11);
                    PDF::MultiCell(500, 0, "         " . $count . ") Truck Info: ", $border, 'L', false, 0, '', '');
                    PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                    $truck = 1;
                  }

                  PDF::MultiCell(500, 0, "         " . "       " . $txtBullet . ".   " . $label . $scope, $border, 'L', false, 0, '', '');
                  PDF::MultiCell(220, 0, $pic, $border, 'L', false, 1, '', '');
                  $txtBullet++;
                } else {
                  if (!ctype_space($scope)) {
                    switch ($key) {
                      case 'Outside Dimensions: L = ':
                      case 'Inside Dimensions: L = ':
                        $arr_scope = $this->reporter->fixcolumn([$scope], '70', 0);
                        break;
                      default:
                        $arr_scope = $this->reporter->fixcolumn([$scope], '50', 0);
                        break;
                    }
                    $maxrow = $this->othersClass->getmaxcolumn([$arr_scope]);
                    $count += 1;
                    $txtBullet = 'a';
                    $counta = $count;
                    $brdr = $border;
                    for ($r = 0; $r < $maxrow; $r++) {
                      if ($r > 0) $counta = 0;
                      PDF::SetFont($font, '', $font11);
                      if ($maxrow > 1) {
                        if ($r == 0) {
                          $brdr = $border;
                        } else {
                          $brdr = 'LR';
                        }
                      } else {
                        $brdr = $border;
                      }
                      PDF::MultiCell(500, 0, "         " . ($r == 0 ? $count . ") " . $key : '') . $arr_scope[$r], $brdr, 'L', false, 0, '', '');
                      PDF::MultiCell(220, 0, ($r == 0 ? $pic : ''), $brdr, 'L', false, 1, '', '');
                    }
                  }
                }

                break;
            }
          }
        }
      }
      $count += 1;
      PDF::SetFont($font, '', $font11);
      PDF::MultiCell(500, 0, "         " . $count . ") Install Add Ons: ", $border, 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, "  Finishing:", $border, 'L', false, 1, '', '');
      $addon = $this->get_add_on($qtrno, 2);
      $this->addonDisplay($addon, 'addons');

      $count += 1;
      PDF::MultiCell(500, 0, "         " . $count . ") Notes/Others: ", 'LTR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LTR', 'L', false, 1, '', '');
      $addon = $this->get_add_on($qtrno, 4);
      $this->addonDisplay($addon, 'rem');

      $count += 1;
      PDF::MultiCell(500, 0, "         " . $count . ") Passed quality check", 'LTR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '  QC: ', 'LTR', 'L', false);
      PDF::MultiCell(500, 0, "                a. Sealer and leak test", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
      PDF::MultiCell(500, 0, "                b. Painting quality", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
      PDF::MultiCell(500, 0, "                c. Door functionality", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
      PDF::MultiCell(500, 0, "                d. Lights functionality", 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, '', 'LR', 'L', false);
    }
    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($font, '', $font11);
    PDF::MultiCell(104, 0, 'Prepared By: ', '', 'L', false, 0, '', '');
    PDF::MultiCell(194, 0, $params['params']['dataparams']['prepared'], 'B', 'L', false, 0, '', '');
    PDF::MultiCell(124, 0, '', '', 'L', false, 0, '', '');
    PDF::MultiCell(104, 0, 'Checked  By: ', '', 'L', false, 0, '', '');
    PDF::MultiCell(194, 0, $params['params']['dataparams']['received'], 'B', 'L', false, 1, '', '');



    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function addonDisplay($addon, $col)
  {
    $txtBullet = 'a';
    for ($i = 0; $i < count($addon); $i++) {
      if ($i != 0) {
        $txtBullet++;
      }
      PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . $addon[$i][$col], 'LR', 'L', false, 0, '', '');
      PDF::MultiCell(220, 0, "", 'LR', 'L', false, 1, '', '');
    }
  }

  public function get_add_on($trno, $type)
  {
    $qry = "select line,qty,addons,rem from qtaddons where trno='$trno' and addontype='$type'
    union all
    select line,qty,addons,rem from hqtaddons where trno='$trno' and addontype='$type' 
    order by line";
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
      PDF::MultiCell(500, 0, "                " . $txtBullet . ".   " . $repairs[$i]->side . " " . $repairs[$i]->parts, 'LR', 'L', false, 0);
      PDF::MultiCell(220, 0, '', 'LR');
    }
  }

  public function getPaints($trno, $tbl, $type = '')
  {
    if ($type == '') {
      $qry = "select paintcover as 'Cover: ', bodycolor as 'Body Color: ', flrcolor as 'Floor Color: ', unchassiscolor as 'Under Chassis: ', paintroof as 'Roof: ' from " . $tbl . " where trno=" . $trno;
    } else {
      $qry = "select paintcover as 'Cover: ', bodycolor as 'Body Color: ', flrcolor as 'Floor Color: ', unchassiscolor as 'Under Chassis: ', paintroof as 'Roof: ', exterior as 'Exterior: ', interior as 'Interior: ' from " . $tbl . " where trno=" . $trno;
    }
    return json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
  }

  public function getRearsides($trno, $tbl, $type)
  {
    if ($type == 'Rear Doors: ') {
      $qry = "select reardrstype, reardrslock, reardrshinger, reardrsseals, reardrsrem from " . $tbl . " where trno=" . $trno;
    } else {
      $qry = "select sidedrstype, sidedrslock, sidedrshinger, sidedrsseals, sidedrsrem from " . $tbl . " where trno=" . $trno;
    }
    return json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
  }

}
