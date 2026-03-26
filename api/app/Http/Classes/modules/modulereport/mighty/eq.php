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
use App\Http\Classes\reportheader;

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class eq
{
  private $modulename = "Equipment Monitoring";
  private $reportheader;
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
  }

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $companyid = $config['params']['companyid'];

    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =? ", [$config['params']['user']]);
    $approved = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'approved' and doc =? ", [$config['params']['doc']]);
    $received = $this->coreFunctions->datareader("select fieldvalue as value from signatories where fieldname = 'received' and doc =? ", [$config['params']['doc']]);

    switch ($companyid) {
      case 40:
        $paramstr = "select
          'PDFM' as print,
          '$username' as prepared,
          '$approved' as approved,
          '$received' as received";
        break;
      default:
        $paramstr = "select
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";
        break;
    }

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $trno = $config['params']['dataid'];
    $query = "select head.docno, left(head.dateid, 10) as dateid, cl.clientname as empname, head.opincentive, cl.client as empcode, wh.clientname as whname, ifnull(item.barcode, '') as barcode, item.itemname, project.name as project, head.rem, cat.category, if(stock.starttime <> '', time_format(time(stock.starttime), '%H:%i'), '00:00') as starttime, if(stock.endtime <> '', time_format(time(stock.endtime), '%H:%i'), '00:00') as endtime, stock.duration, format(stock.odostart, 0) as odostart, format(stock.odoend, 0) as odoend, stock.distance, stock.fuelconsumption
    from eqhead as head
    left join transnum as num on num.trno = head.trno
    left join eqstock as stock on stock.trno = head.trno
    left join item on item.itemid = head.itemid
    left join employee as emp on emp.empid = head.empid
    left join client as cl on cl.clientid = emp.empid
    left join client as wh on wh.clientid = head.whid
    left join projectmasterfile as project on project.line = head.projectid
    left join reqcategory as cat on cat.line = stock.activityid
    where head.trno = " . $trno . "
    union all
    select head.docno, left(head.dateid, 10) as dateid, cl.clientname as empname, head.opincentive, cl.client as empcode, wh.clientname as whname, ifnull(item.barcode, '') as barcode, item.itemname, project.name as project, head.rem, cat.category, if(stock.starttime <> '', time_format(time(stock.starttime), '%H:%i'), '00:00') as starttime, if(stock.endtime <> '', time_format(time(stock.endtime), '%H:%i'), '00:00') as endtime, stock.duration, format(stock.odostart, 0) as odostart, format(stock.odoend, 0) as odoend, stock.distance, stock.fuelconsumption
    from heqhead as head
    left join transnum as num on num.trno = head.trno
    left join heqstock as stock on stock.trno = head.trno
    left join item on item.itemid = head.itemid
    left join employee as emp on emp.empid = head.empid
    left join client as cl on cl.clientid = emp.empid
    left join client as wh on wh.clientid = head.whid
    left join projectmasterfile as project on project.line = head.projectid
    left join reqcategory as cat on cat.line = stock.activityid
    where head.trno = " . $trno . "";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn  

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "default") {
      return $this->default_so_layout($params, $data);
    } else if ($params['params']['dataparams']['print'] == "PDFM") {
      return $this->default_so_PDF($params, $data);
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
    $str .= $this->reporter->col('QTY', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '500px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT PRICE', '125px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('(+/-) %', '50px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('TOTAL', '125px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '30px', '8px');

    return $str;
  }

  public function default_so_layout($params, $data)
  {
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    // $center = $params['params']['center'];
    // $username = $params['params']['user'];

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
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '500px', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['gross'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['disc'], '50px', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '125px', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
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
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '500px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '125px', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '50px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '125px', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
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

  public function default_so_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name, address, tel, code from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    // $current_timestamp = $this->othersClass->getCurrentTimeStamp();

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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel), '', 'C');
    PDF::MultiCell(0, 0, "\n");

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(540, 0, $this->modulename, '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, "", '', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, "", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "Document # : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Employee Name : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Date : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Warehouse Name : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Operator Incentive : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['opincentive']) ? $data[0]['opincentive'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Truct/Asset : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['itemname']) ? $data[0]['itemname'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Truct/Asset Code : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, (isset($data[0]['barcode']) ? $data[0]['barcode'] : ''), 'B', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, "Project : ", '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 20, (isset($data[0]['project']) ? $data[0]['project'] : ''), 'B', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 20, '', '', 'L', false, 0, '',  '', true, 0, false, true, 0, 'B', true);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 20, '', '', 'L', false, 1, '',  '', true, 0, false, true, 0, 'B', true);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(100, 0, "CATEGORY", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "START TIME", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "END TIME", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "DURATION", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "METER START", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "METER END", '', 'C', false, 0);
    PDF::MultiCell(80, 0, "DISTANCE", '', 'C', false, 0);
    PDF::MultiCell(140, 0, "FUEL CONSUMPTION", '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }

  public function default_so_PDF($params, $data)
  {
    // $companyid = $params['params']['companyid'];
    // $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    // $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    // $center = $params['params']['center'];
    // $username = $params['params']['user'];
    // $count = $page = 35;
    // $totalext = 0;

    $font = "";
    // $fontbold = "";
    // $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      // $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_so_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    // $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;

        $category = $data[$i]['category'];
        $stime = date('H:i', strtotime($data[$i]['starttime']));
        $etime = date('H:i', strtotime($data[$i]['endtime']));
        $duration = number_format($data[$i]['duration'], $decimalqty);
        $mstart = $data[$i]['odostart'];
        $mend = $data[$i]['odoend'];
        $distance = $data[$i]['distance'];
        $fconsumption = number_format($data[$i]['fuelconsumption'], $decimalqty);

        $arr_category = $this->reporter->fixcolumn([$category], '15', 0);
        $arr_stime = $this->reporter->fixcolumn([$stime], '15', 0);
        $arr_etime = $this->reporter->fixcolumn([$etime], '15', 0);
        $arr_duration = $this->reporter->fixcolumn([$duration], '15', 0);
        $arr_mstart = $this->reporter->fixcolumn([$mstart], '15', 0);
        $arr_mend = $this->reporter->fixcolumn([$mend], '15', 0);
        $arr_distance = $this->reporter->fixcolumn([$distance], '15', 0);
        $arr_fconsumption = $this->reporter->fixcolumn([$fconsumption], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_category, $arr_stime, $arr_etime, $arr_duration, $arr_mstart, $arr_mend, $arr_distance, $arr_fconsumption]);

        for ($r = 0; $r < $maxrow; $r++) {

          PDF::SetFont($font, '', $fontsize);
            PDF::MultiCell(100, 15, ' ' . (isset($arr_category[$r]) ? $arr_category[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_stime[$r]) ? $arr_stime[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_etime[$r]) ? $arr_etime[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_duration[$r]) ? $arr_duration[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_mstart[$r]) ? $arr_mstart[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(80, 15, ' ' . (isset($arr_mend[$r]) ? $arr_mend[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);            
            PDF::MultiCell(80, 15, ' ' . (isset($arr_distance[$r]) ? $arr_distance[$r] : ''), '', 'C', false, 0, '',  '', true, 0, false, true, 0, 'M', false);
            PDF::MultiCell(140, 15, ' ' . (isset($arr_fconsumption[$r]) ? $arr_fconsumption[$r] : ''), '', 'C', false, 1, '',  '', true, 0, false, true, 0, 'M', false);
        }

        if (PDF::getY() > 900) {
          $this->default_so_header_PDF($params, $data);
        }
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE: ', '', 'L', false, 0);
    PDF::MultiCell(560, 0, $data[0]['rem'], '', 'L');
    PDF::MultiCell(110, 0, '', '', 'L', false);
    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::MultiCell(240, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Approved By: ', '', 'L', false, 0);
    PDF::MultiCell(240, 0, 'Received By: ', '', 'L');
    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(240, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(240, 0, $params['params']['dataparams']['received'], '', 'L');


    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
