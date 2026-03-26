<?php

namespace App\Http\Classes\modules\modulereport\unitech;

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

class mi
{

  private $modulename = "Material Issuance";
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
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'refresh'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']

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

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, head.agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname,emp.clientname as driver,info.odometer,ifnull(project.name,'') as projectname,coa.acnoname as expenses,
    ifnull(uom.factor,1) as factor,pe.docno as pedocno,i.itemname as headitemname,head.contra,coa.acno,coa.acnoname
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client on client.client=head.client
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.client=head.agent
    left join client as wh on wh.client=head.wh
    left join cntnuminfo as info on info.trno=head.trno
    left join client as emp on head.empid = emp.clientid
    left join projectmasterfile as project on project.line=head.projectid
    left join coa on coa.acno=head.contra
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join hprhead as pe on pe.trno=head.petrno
    left join item as i on i.itemid = pe.itemid
    where head.doc='MI' and head.trno='$trno'
    UNION ALL
    select stock.line,stock.rem as srem,head.rem,date_format(head.dateid,'%m/%d') as monthid,
    right(year(head.dateid),2) as year,left(head.dateid,10) as dateid, head.docno, client.client, client.clientname,
    head.address, head.terms, item.barcode, head.shipto, client.tin, head.yourref, head.ourref,
    item.itemname, stock.isqty as qty, stock.uom, stock.isamt as amt, stock.disc, stock.ext, ag.client as agent,
    item.sizeid, ag.clientname as agname, item.brand,
    wh.client as whcode, wh.clientname as whname,emp.clientname as driver,info.odometer,ifnull(project.name,'') as projectname,coa.acnoname as expenses,
    ifnull(uom.factor,1) as factor,pe.docno as pedocno,i.itemname as headitemname,head.contra,coa.acno,coa.acnoname
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client on client.clientid=head.clientid
    left join item on item.itemid=stock.itemid
    left join client as ag on ag.clientid=head.agentid
    left join client as wh on wh.clientid=head.whid
    left join hcntnuminfo as info on info.trno=head.trno
    left join client as emp on head.empid = emp.clientid
    left join projectmasterfile as project on project.line=head.projectid
    left join coa on coa.acno=head.contra
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join hprhead as pe on pe.trno=head.petrno
    left join item as i on i.itemid = pe.itemid
    where head.doc='MI' and head.trno='$trno' order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    return $this->default_MI_PDF($params, $data);
  }

  public function default_MI_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    //$width = 800; $height = 1000;

    $qry = "select name,address,tel,code from center where code = '" . $center . "'";
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

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');


    // PDF::Image('public/images/reports/mdc.jpg', '45', '35', 100, 40);
    // PDF::Image('public/images/reports/tuv.jpg', '630', '35', 100, 40);

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(490, 0, 'Raw '.$this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(110, 0, "Docno #: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(0, 30, "", '', 'L');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Production Request: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(370, 0, (isset($data[0]['pedocno']) ? $data[0]['pedocno'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(110, 0, "Date: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Warehouse: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(370, 0, (isset($data[0]['whname']) ? $data[0]['whname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(110, 0, "Account Code: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['acno']) ? $data[0]['acno'] : ''), 'B', 'L', false);



    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Itemname: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(370, 0, (isset($data[0]['headitemname']) ? $data[0]['headitemname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(110, 0, "Account Name: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['acnoname']) ? $data[0]['acnoname'] : ''), 'B', 'L', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(120, 0, "Notes: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(370, 0, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), 'B', 'L', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(20, 0, "", '', 'L', false, 0, '',  '');
    PDF::MultiCell(110, 0, "", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, '', '', 'L', false, 0, '',  '');

    PDF::MultiCell(0, 0, "\n\n\n");

    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(120, 0, "BARCODE", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "QTY", '', 'C', false, 0);
    PDF::MultiCell(60, 0, "UNIT", '', 'C', false, 0);
    PDF::MultiCell(250, 0, "DESCRIPTION", '', 'L', false, 0);
    PDF::MultiCell(100, 0, "COST", '', 'R', false, 0);
    // PDF::MultiCell(100, 0, "(+/-) %", '', 'R', false, 0);
    PDF::MultiCell(130, 0, "TOTAL", '', 'R', false);

    PDF::MultiCell(720, 0, '', 'B');
  }

  public function default_MI_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;
    $totalext = 0;
    $netdiscs = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_MI_header_PDF($params, $data);

    $arritemname = array();
    $countarr = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        // $arritemname = (str_split($data[$i]['itemname'], 28));
        // $itemcodedescs = [];
        // if(!empty($arritemname)) {
        //   foreach($arritemname as $arri) {
        //     if(strstr($arri, "\n")) {
        //       $array = preg_split("/\r\n|\n|\r/", $arri);
        //       foreach($array as $arr) {
        //         array_push($itemcodedescs, $arr);
        //       }
        //     } else {
        //       array_push($itemcodedescs, $arri);
        //     }
        //   }
        // }
        // $countarr = count($itemcodedescs);
        // $maxrow = $countarr;
        // $maxh = PDF::GetStringHeight(200, $data[$i]['itemname']);

        $maxrow = 1;
        $barcode = $data[$i]['barcode'];
        $qty = number_format($data[$i]['qty'], $decimalqty);
        $uom = $data[$i]['uom'];
        $itemname = $data[$i]['itemname'];
        $amt = number_format($data[$i]['amt'], 2);
        $disc = $data[$i]['disc'];
        $ext = number_format($data[$i]['ext'], $decimalprice);
        $netofdisc =  number_format($data[$i]['factor'] * $data[$i]['amt'], $decimalcurr);
        $net = $netofdisc - ($companyid == 43) ? $data[$i]['disc'] : number_format($data[$i]['disc'], 2);

        $arr_barcode = $this->reporter->fixcolumn([$barcode], '16', 0);
        $arr_qty = $this->reporter->fixcolumn([$qty], '13', 0);
        $arr_uom = $this->reporter->fixcolumn([$uom], '13', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '35', 0);
        $arr_amt = $this->reporter->fixcolumn([$amt], '13', 0);
        $arr_disc = $this->reporter->fixcolumn([$disc], '13', 0);
        $arr_ext = $this->reporter->fixcolumn([$ext], '13', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_qty, $arr_uom, $arr_itemname, $arr_amt, $arr_disc, $arr_ext]);

        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(120, 0, (isset($arr_barcode[$r]) ? $arr_barcode[$r] : ''), '', 'C', false, 0, '', '', true, 1);
          PDF::MultiCell(60, 0, (isset($arr_qty[$r]) ? $arr_qty[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(60, 0, (isset($arr_uom[$r]) ? $arr_uom[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(250, 0, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', false, 0, '', '', false, 1);
          PDF::MultiCell(100, 0, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          // PDF::MultiCell(100, 0, (isset($arr_disc[$r]) ? $arr_disc[$r] : ''), '', 'R', false, 0, '', '', false, 1);
          PDF::MultiCell(130, 0, (isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', false, 1);
        }

        // if ($data[$i]['itemname'] == '') {
        // } else {
        //   for($r = 0; $r < $maxrow; $r++) {
        //     if($r == 0) {
        //         $barcode =  $data[$i]['barcode'];
        //         $qty = number_format($data[$i]['qty'], $decimalqty);
        //         $uom = $data[$i]['uom'];
        //         $amt = number_format($data[$i]['amt'], $decimalprice);
        //         $disc = $data[$i]['disc'];
        //         $ext = number_format($data[$i]['ext'], $decimalprice);
        //     } else {
        //         $barcode = '';
        //         $qty = '';
        //         $uom = '';
        //         $amt = '';
        //         $disc = '';
        //         $ext = '';
        //     }
        //     PDF::SetFont($font, '', $fontsize);
        //     PDF::MultiCell(100, 0, $barcode, '', 'C', false, 0, '', '', true, 1);
        //     PDF::MultiCell(50, 0, $qty, '', 'R', false, 0, '', '', false, 1);
        //     PDF::MultiCell(50, 0, $uom, '', 'C', false, 0, '', '', false, 1);
        //     PDF::MultiCell(200, 0, isset($itemcodedescs[$r]) ? $itemcodedescs[$r] : '', '', 'L', false, 0, '', '', false, 1);
        //     PDF::MultiCell(100, 0, $amt, '', 'R', false, 0, '', '', false, 1);
        //     PDF::MultiCell(100, 0, $disc, '', 'R', false, 0, '', '', false, 1);
        //     PDF::MultiCell(100, 0, $ext, '', 'R', false, 1, '', '', false, 1);
        //   }
        // }
        $netdiscs += $net;
        $totalext += $data[$i]['ext'];

        if (intVal($i) + 1 == $page) {
          $this->default_MI_header_PDF($params, $data);
          $page += $count;
        }
      }
    }
    // PDF::MultiCell(700, 0, "", "T");

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(600, 0, 'GRAND TOTAL: ', 'T', 'R', false, 0);
    PDF::MultiCell(120, 0, number_format($totalext, $decimalprice), 'T', 'R');

    // PDF::MultiCell(760, 0, '', 'B');
    PDF::MultiCell(0, 0, "\n");

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
}
