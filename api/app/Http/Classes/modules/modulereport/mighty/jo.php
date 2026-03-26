<?php

namespace App\Http\Classes\modules\modulereport\mighty;

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

class jo
{
  private $modulename = "Job/Repair Order";
  private $fieldClass;
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function createReportFilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);

    return array('col1' => $col1);
  }

  public function reportParamsData()
  {
    return $this->coreFunctions->opentable("
      select
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
    ");
  }

  public function report_default_query($trno)
  {
    $query = "select head.docno, head.dateid, head.rem, head.wh, wh.clientname as whname, num.center, 
    head.client, head.clientname, ifnull(p.name, '') as projectname, info.strdate1 as stime, info.strdate2 as ctime,
    assessedby.clientname as empname, ifnull(assessedby.clientname,0) as assessedby, info.mileage, info.nodays, 
    item.barcode, item.itemname
    from johead as head
    left join transnum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join client as wh on wh.client = head.wh
    left join projectmasterfile as p on p.line = head.projectid
    left join headinfotrans as info on info.trno = head.trno
    left join item on item.itemid = info.itemid  
    left join client as assessedby on assessedby.clientid = info.assessedid  
    where head.trno = $trno
    union all 
    select head.docno, head.dateid, head.rem, head.wh, wh.clientname as whname, num.center, 
    head.client, head.clientname, ifnull(p.name, '') as projectname, info.strdate1 as stime, info.strdate2 as ctime,
    assessedby.clientname as empname, ifnull(assessedby.clientname,0) as assessedby, info.mileage, info.nodays,
    item.barcode, item.itemname
    from hjohead as head
    left join transnum as num on num.trno = head.trno
    left join client on head.client = client.client
    left join client as wh on wh.client = head.wh
    left join projectmasterfile as p on p.line = head.projectid
    left join hheadinfotrans as info on info.trno = head.trno
    left join item on item.itemid = info.itemid
    left join client as assessedby on assessedby.clientid = info.assessedid  
    where head.trno = $trno";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function report_mistock_query($trno)
  {
    $query = "select head.docno, item.barcode, if(ifnull(sit.itemdesc, '') = '', item.itemname, sit.itemdesc) as itemname, stock.uom, format(stock.isqty, 2) as isqty, left(stock.encodeddate, 10) as encodeddate, emp.clientname as driver
    from glstock as stock 
    left join glhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid 
    left join part_masterfile as part on part.part_id = item.part
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join hstockinfo as sit on sit.trno = stock.trno and sit.line=stock.line
    left join hcntnuminfo as info on info.trno = head.trno
    left join client as emp on emp.clientid = head.empid
    where head.doc = 'MI' and info.jotrno = $trno";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportAssessment($config)
  {
    $query = "select itemdesc from stockinfotrans where trno = " . $config['params']['dataid'];
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportJobDone($config)
  {
    $query = "select cl.client, cl.clientname, hp.rem from headprrem as hp left join client as cl on cl.clientid = hp.empid where hp.jotrno = " . $config['params']['dataid'] . " order by clientname";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportPlotting($params, $data)
  {
    return $this->defaultJO_PDF($params, $data);
  }

  public function defaultHeaderPDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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

    //$width = PDF::pixelsToUnits($width);
    //$height = PDF::pixelsToUnits($height);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(0, 0, strtoupper($this->modulename), '', 'L');

    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(720, 0, '', '', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(530, 0, "", '', 'R', false, 0);
    PDF::MultiCell(90, 0, "Document # : ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (!isset($data[0]['docno']) ? '' : $data[0]['docno']), 'B', 'L', false, 1);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "Name: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (!isset($data[0]['clientname']) ? '' : $data[0]['clientname']) .'('. $data[0]['client'] . ')', 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, date('Y-m-d', strtotime($data[0]['dateid'])), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "Asset/Truck: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (!isset($data[0]['barcode']) ? '' : $data[0]['itemname']) . '(' . $data[0]['barcode'] . ')', 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, "Project: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (!isset($data[0]['projectname']) ? '' : $data[0]['projectname']), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (!isset($data[0]['wh']) && $data[0]['whname'] ? '' : ($data[0]['wh'] . '~' . $data[0]['whname'])), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, "Start: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (!isset($data[0]['stime']) ? '' : $data[0]['stime']), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "Mileage/SMR: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (!isset($data[0]['mileage']) ? '' : $data[0]['mileage']), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, "Completion: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (!isset($data[0]['ctime']) ? '' : $data[0]['ctime']), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "Assessed by: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (!isset($data[0]['assessedby']) ? '' : $data[0]['assessedby']), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, "No. of days: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (!isset($data[0]['nodays']) ? '' : $data[0]['nodays']), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(110, 0, "NOTE: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 0, (!isset($data[0]['rem']) ? '' : $data[0]['rem']), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 1, '',  '');
  }

  public function defaultJO_PDF($params, $data)
  {
    $data2 = $this->report_mistock_query($params['params']['dataid']);
    $stockInfo = $this->reportAssessment($params);
    $jobDone = $this->reportJobDone($params);

    $font = "";
    $fontbold = "";
    $fontsize = "11";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $this->defaultHeaderPDF($params, $data);

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(720, 0, "COMPLAINTS/ASSESSMENT", 'TB', 'L', false, 1);

    for ($i = 0; $i < count($stockInfo); $i++) {

      $arr_itemdesc = $this->reporter->fixcolumn([$stockInfo[$i]['itemdesc']], '50', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_itemdesc]);

      for ($j = 0; $j < $maxrow; $j++) {
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(720, 0, (!isset($arr_itemdesc[$j]) ? '' : $arr_itemdesc[$j]), '', 'L', false, 1);
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "MI NO.", 'TB', 'L', false, 0);
    PDF::MultiCell(100, 0, "DATE", 'TB', 'C', false, 0);
    PDF::MultiCell(100, 0, "BARCODE", 'TB', 'C', false, 0);
    PDF::MultiCell(100, 0, "QTY", 'TB', 'C', false, 0);
    PDF::MultiCell(100, 0, "UNIT", 'TB', 'C', false, 0);
    PDF::MultiCell(220, 0, "PARTS", 'TB', 'L', false, 1);

    for ($i = 0; $i < count($data2); $i++) {
      $docno = $data2[$i]['docno'];
      $barcode = $data2[$i]['barcode'];
      $date = date('Y-m-d', strtotime($data2[$i]['encodeddate']));
      $qty = $data2[$i]['isqty'];
      $uom = $data2[$i]['uom'];
      $part = $data2[$i]['itemname'];

      $arr_docno = $this->reporter->fixcolumn([$docno], '20', 0);
      $arr_barcode = $this->reporter->fixcolumn([$barcode], '14', 0);
      $arr_date = $this->reporter->fixcolumn([$date], '14', 0);
      $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
      $arr_unit = $this->reporter->fixcolumn([$uom], '13', 0);
      $arr_part = $this->reporter->fixcolumn([$part], '50', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_docno, $arr_date, $arr_barcode, $arr_qty, $arr_unit, $arr_part]);

      for ($j = 0; $j < $maxrow; $j++) {
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(100, 0, (!isset($arr_docno[$j]) ? '' : $arr_docno[$j]), '', 'L', false, 0);
        PDF::MultiCell(100, 0, (!isset($arr_date[$j]) ? '' : $arr_date[$j]), '', 'C', false, 0);
        PDF::MultiCell(100, 0, (!isset($arr_barcode[$j]) ? '' : $arr_barcode[$j]), '', 'C', false, 0);
        PDF::MultiCell(100, 0, (!isset($arr_qty[$j]) ? '' : $arr_qty[$j]), '', 'C', false, 0);
        PDF::MultiCell(100, 0, (!isset($arr_unit[$j]) ? '' : $arr_unit[$j]), '', 'C', false, 0);
        PDF::MultiCell(220, 0, (!isset($arr_part[$j]) ? '' : $arr_part[$j]), '', 'L', false, 1);
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "NO.", 'TB', 'C', false, 0);
    PDF::MultiCell(100, 0, "EMPLOYEE CODE", 'TB', 'L', false, 0);
    PDF::MultiCell(200, 0, "NAME", 'TB', 'L', false, 0);
    PDF::MultiCell(370, 0, "ACTION/JOB DONE", 'TB', 'L', false, 1);

    for ($i = 0; $i < count($jobDone); $i++) {

      $arr_client = $this->reporter->fixcolumn([$jobDone[$i]['client']], '20', 0);
      $arr_clientname = $this->reporter->fixcolumn([$jobDone[$i]['clientname']], '40', 0);
      $arr_rem = $this->reporter->fixcolumn([$jobDone[$i]['rem']], '50', 0);
      $maxrow = $this->othersClass->getmaxcolumn([$arr_client, $arr_clientname, $arr_rem]);

      for ($j = 0; $j < $maxrow; $j++) {
        PDF::SetFont($font, '', 10);
        PDF::MultiCell(50, 0, $i + 1, '', 'C', false, 0);
        PDF::MultiCell(100, 0, (!isset($arr_client[$j]) ? '' : $arr_client[$j]), '', 'L', false, 0);
        PDF::MultiCell(200, 0, (!isset($arr_clientname[$j]) ? '' : $arr_clientname[$j]), '', 'L', false, 0);
        PDF::MultiCell(370, 0, (!isset($arr_rem[$j]) ? '' : $arr_rem[$j]), '', 'L', false, 1);
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(240, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Driver: ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(240, 0, '', '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
