<?php

namespace App\Http\Classes\modules\modulereport\main;

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

class vl
{

  private $modulename = "Trip Ticket";
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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'blue'],
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
                  '' as prepared,
                  '' as approved,
                  '' as received";

    return $this->coreFunctions->opentable($paramstr);
  }

  public function report_default_query($config)
  {

    $curren = $this->othersClass->getCurrentTimeStamp();
    $trno = $config['params']['dataid'];

    $query = "
    select stock.line, date(head.dateid) as dateid, head.docno, emp.clientname as employee, head.rem, 
        driver.clientname as driver, dept.clientname as department, vehicle.clientname as vehicle,
        head.schedin as schedin, head.schedout as schedout, customer.client as customer, customer.clientname as customername, 
        head.status, stock.schedin as timein, stock.schedout as timeout,

        (select  group_concat(concat(i.itemname,' (',round(i.qty),' ',i.uom,')') SEPARATOR '\r\n') as itemdesc from vritems as i left join vrstock as s on s.trno=i.trno and s.line=i.line  
        where i.trno=head.trno and i.line = stock.line) as itemname,

        (select group_concat(p.purpose SEPARATOR ',\n') as purpose 
        from vrstock as s
        left join purpose_masterfile as p on s.purposeid = p.line
        where s.trno=head.trno and s.line = stock.line) as purpose,

         (select group_concat(concat(ifnull(concat(addr.addr),'')) separator '\r\n')  from vrstock as s 
         left join client on client.clientid=s.clientid 
         left join billingaddr as addr on addr.line=s.shipid where s.trno=head.trno and s.line = stock.line) as loc,

         (select group_concat(concat(p.clientname) SEPARATOR '\r\n') as passengername from vrpassenger as i 
         left join client as p on p.clientid = i.passengerid
        where i.trno=head.trno and i.line = stock.line) as passengername, stock.schedin as clschedin, stock.schedout as clschedout
        
        from vrhead as head left join vrstock as stock on stock.trno=head.trno
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as dept on dept.clientid = head.deptid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as customer on customer.clientid = stock.clientid
        where head.doc='vr' and head.trno='$trno'
        union all
        select stock.line, date(head.dateid) as dateid, head.docno, emp.clientname as employee, head.rem, 
        driver.clientname as driver, dept.clientname as department, vehicle.clientname as vehicle,
        head.schedin as schedin, head.schedout as schedout, customer.client as customer, customer.clientname as customername, 
        head.status , stock.schedin as timein, stock.schedout as timeout, 

        (select  group_concat(concat(i.itemname,' (',round(i.qty),' ',i.uom,')') SEPARATOR '\r\n') as itemdesc from hvritems as i left join 
        hvrstock as s on s.trno=i.trno and s.line=i.line  
        where i.trno=head.trno and i.line = stock.line) as itemname,

        (select group_concat(p.purpose SEPARATOR ',\n') as purpose 
        from hvrstock as s
        left join purpose_masterfile as p on s.purposeid = p.line
        where s.trno=head.trno and s.line = stock.line) as purpose,

         (select group_concat(concat(ifnull(concat(addr.addr),'')) separator '\r\n')  from hvrstock as s 
         left join client on client.clientid=s.clientid 
         left join billingaddr as addr on addr.line=s.shipid where s.trno=head.trno and s.line = stock.line) as loc,

         (select group_concat(concat(p.clientname) SEPARATOR '\r\n') as passengername from hvrpassenger as i 
         left join client as p on p.clientid = i.passengerid
        where i.trno=head.trno and i.line = stock.line) as passengername, stock.schedin as clschedin, stock.schedout as clschedout
        
        from hvrhead as head left join hvrstock as stock on stock.trno=head.trno
        left join client as emp on emp.clientid = head.clientid
        left join client as driver on driver.clientid = head.driverid
        left join client as dept on dept.clientid = head.deptid
        left join client as vehicle on vehicle.clientid = head.vehicleid
        left join client as customer on customer.clientid = stock.clientid
        where head.doc='vr' and head.trno='$trno'

        order by clschedin, clschedout
        ";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportplotting($params, $data)
  {
    return $this->default_PDF($params, $data);
  }

  public function default_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    $trno = $params['params']['dataid'];
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(900, 0, strtoupper($this->modulename), '', 'C', false, 1, '',  '100');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 00, "VEHICLE : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['vehicle']) ? $data[0]['vehicle'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(290, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "TRIP NO : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(110, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);
    // PDF::MultiCell(100, 0, '', '', 'L', false,0);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "DRIVER : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['driver']) ? $data[0]['driver'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(290, 10, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "DATE OF TRAVEL : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false);

    // PDF::MultiCell(0, 0, "");
    $time = strtotime($data[0]['schedin']);
    $timein = date('h:ia', $time);

    $time = strtotime($data[0]['schedout']);
    $timeout = date('h:ia', $time);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "DEPARTMENT : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(200, 0, (isset($data[0]['department']) ? $data[0]['department'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::MultiCell(285, 10, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(105, 0, "START & END TIME : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(110, 0, (isset($data[0]['schedin']) ? $timein : '') . ' - ' . (isset($data[0]['schedout']) ? $timeout : ''), 'B', 'L', false, 0);
    PDF::MultiCell(100, 10, '', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 0, "PASSENGERS : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(40, 0, $this->getpassenger($trno), 'B', 'C', false, 0, '',  '');
    PDF::MultiCell(760, 0, "", '', 'L', false, 1, '',  '');

    PDF::MultiCell(0, 0, "", '', 'L');

    PDF::SetFont($font, 'B', $fontsize);
    PDF::SetFillColor(102, 178, 255);
    PDF::SetTextColor(240, 240, 240);
    PDF::MultiCell(80, 25, "PURPOSE", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "PASSENGERS", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "CLIENT", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(200, 25, "LOCATION", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(100, 25, "ESTIMATED TIME", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(90, 25, "EFFECTIVE TIME", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(110, 25, "CARGO", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 25, "ODOMETER", 'TLRB', 'C', true, 0, '', '', true, 0, false, true, 0, 'M', true);
    PDF::MultiCell(70, 25, "RESULT", 'TLRB', 'C', true, 1, '', '', true, 0, false, true, 0, 'M', true);
    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0, $valign='T', $fitcell=false)

    // $this->addrow('LR');
    // $this->addrow('LRB');
    PDF::SetTextColor(30, 30, 30);
  }

  public function default_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    // $count = $page = 35;
    $count = $page = 900;
    $height = 15;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('l', [1000, 800]);
    PDF::SetMargins(40, 40);

    $this->default_header_PDF($params, $data);

    $arracnoname = array();
    $countarr = 0;
    PDF::SetFont($font, '', 5);
    $newpageadd = 0;
    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        //   $arracnoname = (str_split($data[$i]['customername'], 12));
        //   $acnonamedescs = [];

        //   if(!empty($arracnoname)) {
        //     foreach($arracnoname as $arri) {
        //         if(strstr($arri, "\n")) {
        //             $array = preg_split("/\r\n|\n|\r/", $arri);
        //             foreach($array as $arr) {
        //                 array_push($acnonamedescs, $arr);
        //             }
        //         } else {
        //             array_push($acnonamedescs, $arri);
        //         }
        //     }
        // }
        // $countarr = count($arracnoname);

        // $maxrow = $countarr;

        // if ($data[$i]['customername'] == '') {
        // } else {
        //     for($r = 0; $r < $maxrow; $r++) {
        //         if($r == 0) {

        //           $time = strtotime($data[$i]['timein']);
        //           $timein = date('H:i:s',$time);

        //           $time = strtotime($data[$i]['timeout']);
        //           $timeout = date('H:i:s',$time);

        //           // $time = strtotime($data[$i]['schedin']);
        //           // $stimein = date('H:i:s',$time);
        //           $stimein = '';

        //           // $time = strtotime($data[$i]['schedout']);
        //           // $stimeout = date('H:i:s',$time);
        //           $stimeout = '';

        //           $purpose =  $data[$i]['purpose'];
        //           $passengername = $data[$i]['passengername'];
        //           $customername = $data[$i]['customername'];
        //           $loc = $data[$i]['loc'];
        //           $actualtime = '';
        //           $itemname = $data[$i]['itemname'];
        //           $odometer = '';
        //           $result = '';

        //         } else {
        //           $purpose = '';
        //           $passengername = '';
        //           $customername = '';
        //           $loc = '';
        //           $timein = '';
        //           $timeout = '';
        //           $stimein = '';
        //           $stimeout = '';
        //           $actualtime = '';
        //           $itemname = '';
        //           $odometer = '';
        //           $result = '';
        //         }


        //        }
        //   }
        //   // $this->addrow('LRB');

        $time = strtotime($data[$i]['timein']);
        $timein = strtoupper(date('h:ia', $time));
        $time = strtotime($data[$i]['timeout']);
        $timeout = strtoupper(date('h:ia', $time));
        $stimein = '';
        $stimeout = '';
        $purpose =  $data[$i]['purpose'];
        $passengername = $data[$i]['passengername'];
        $customername = $data[$i]['customername'];
        $loc = $data[$i]['loc'];
        $itemname = $data[$i]['itemname'];
        $odometer = '';
        $result = '';

        $maxrow = 1;
        $arr_purpose = $this->reporter->fixcolumn([$data[$i]['purpose']],'16',0);
        $arr_passengername = $this->reporter->fixcolumn([$data[$i]['passengername']],'16',0);
        $arr_customername = $this->reporter->fixcolumn([$data[$i]['customername']],'16',0);
        $arr_loc = $this->reporter->fixcolumn([$data[$i]['loc']],'16',0);
        $arr_timein = $this->reporter->fixcolumn([$timein],'13',0);
        $arr_timeout = $this->reporter->fixcolumn([$timeout],'13',0);
        $arr_stimein = $this->reporter->fixcolumn([$stimein],'13',);
        $arr_stimeout = $this->reporter->fixcolumn([$stimeout],'13',0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname],'30',0);
        $arr_odometer = $this->reporter->fixcolumn([$odometer],'13',0);
        $arr_result = $this->reporter->fixcolumn([$result],'13',0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_purpose, $arr_passengername, $arr_customername, $arr_loc, $arr_timein, $arr_timeout, $arr_stimein, $arr_stimeout, $arr_itemname, $arr_odometer, $arr_result]);

        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(80, 0, (isset($arr_purpose[$r]) ? $arr_purpose[$r] : ''), 'LRB', 'L', false, 0, '', '', true, 1, false, true, 0, 'T', false);
          PDF::MultiCell(100, 0, (isset($arr_passengername[$r]) ? $arr_passengername[$r] : ''), 'LRB', 'L', false, 0, '', '', true, 1, false, true, 0, 'T', false);
          PDF::MultiCell(100, 0, (isset($arr_customername[$r]) ? $arr_customername[$r] : ''), 'LRB', 'L', false, 0, '', '', true, 1, false, true, 0, 'T', false);
          PDF::MultiCell(200, 0, (isset($arr_loc[$r]) ? $arr_loc[$r] : ''), 'LRB', 'L', false, 0, '', '', true, 1, false, true, 0, 'T', false);
          PDF::MultiCell(50, 0, (isset($arr_timein[$r]) ? $arr_timein[$r] : ''), 'LRB', 'C', false, 0, '', '', true, 1, false, true, 0, 'M', true);
          PDF::MultiCell(50, 0, (isset($arr_timeout[$r]) ? $arr_timeout[$r] : ''), 'LRB', 'C', false, 0, '', '', true, 1, false, true, 0, 'M', true);
          PDF::MultiCell(45, 0, (isset($arr_stimein[$r]) ? $arr_stimein[$r] : ''), 'LRB', 'C', false, 0, '', '', true, 1, false, true, 0, 'M', true);
          PDF::MultiCell(45, 0, (isset($arr_stimeout[$r]) ? $arr_stimeout[$r] : ''), 'LRB', 'C', false, 0, '', '', true, 1, false, true, 0, 'M', true);
          PDF::MultiCell(110, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'LRB', 'L', false, 0, '', '', true, 1, false, true, 0, 'T', false);
          PDF::MultiCell(70, 0, (isset($arr_odometer[$r]) ? $arr_odometer[$r] : ''), 'LRB', 'L', false, 0, '', '', true, 1, false, true, 0, 'T', false);
          PDF::MultiCell(70, 0, (isset($arr_result[$r]) ? $arr_result[$r] : ''), 'LRB', 'L', false, 1, '', '', true, 1, false, true, 0, 'T', false);
        }
      }
    }
    $this->addrow('LRB', 45);

    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(0, 0, "", '', 'L');

    $time = strtotime($data[0]['schedin']);
    $timein = date('H:i:s', $time);

    $time = strtotime($data[0]['schedout']);
    $timeout = date('H:i:s', $time);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 15, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "DRIVER : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, '', '', 'L', false);
    // PDF::MultiCell(150, 15, (isset($data[0]['driver']) ? $data[0]['driver'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 15, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "TIME IN : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, '', '', 'L', false);
    // PDF::MultiCell(150, 15, (isset($data[0]['schedin']) ? $timein : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 15, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "TIME OUT : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, '', '', 'L', false);
    // PDF::MultiCell(150, 15, (isset($data[0]['schedout']) ? $timeout : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 15, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "SIGNATURE : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, '', '', 'L', false);

    // PDF::MultiCell(0, 15, "", '', 'L');
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 15, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "ODOMETER START : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, '', '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(650, 15, " ", '', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "ODOMETER END : ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 15, '', '', 'L', false);


    return PDF::Output($this->modulename . '.pdf', 'S');
  }


  private function addrow($border, $h = 0)
  {
    PDF::MultiCell(80, $h, '', $border, 'L', false, 0, '', '', true, 1);
    PDF::MultiCell(100, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(100, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(200, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(50, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(45, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(45, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(110, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(70, $h, '', $border, 'L', false, 0, '', '', false, 1);
    PDF::MultiCell(70, $h, '', $border, 'L', false, 1, '', '', false, 0);
  }

  private function getpassenger($trno)
  {
    $qry  = "select sum(value) as value from (
      select count(distinct passengerid) as value from vrpassenger where trno = ? 
    union all 
    select count(distinct passengerid) as value from hvrpassenger where trno = ?) as p";
    return $this->coreFunctions->datareader($qry, [$trno, $trno]);
  }
}
