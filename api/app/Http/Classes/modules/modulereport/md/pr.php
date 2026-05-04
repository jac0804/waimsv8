<?php

namespace App\Http\Classes\modules\modulereport\md;

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

use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

class pr
{

  private $modulename = "Purchase Requisition";
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


    $fields = ['approved', 'print'];
    $col2 = $this->fieldClass->create($fields);

    
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable(
        "select
          'PDFM' as print,
          '' as prepared,
          '' as checked,
          '' as approved,
          '' as delivered,
          '' as received,
          'default' as reporttype
          "
      );
  }

  public function report_default_query($trno)
  {

    // $trno = $config['params']['dataid'];
    $query = "select distinct date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,concat(left(head.wh, 2), ' - ', right(head.wh, 3))  as wh,
        head.rem, item.barcode, item.itemname, stock.rrqty as qty, stock.uom, head.createby, sit.requestorname
        from prhead as head left join prstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join (select trno, max(requestorname) as requestorname
        from stockinfotrans
        group by trno) as sit on sit.trno = head.trno
        where head.doc='PR' and head.trno='$trno'
        union all
        select distinct date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address,concat(left(head.wh, 2), ' - ', right(head.wh, 3))  as wh,
        head.rem, item.barcode, item.itemname, stock.rrqty as qty, stock.uom, head.createby, sit.requestorname
        from hprhead as head left join hprstock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join (select trno, max(requestorname) as requestorname
        from stockinfotrans
        group by trno) as sit on sit.trno = head.trno
        where head.doc='PR' and head.trno='$trno' 
        order by dateid";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function reportplotting($params, $data)
  {
    if ($params['params']['dataparams']['print'] == "PDFM") {
        switch ($params['params']['dataparams']['reporttype']) {
            case 'default':
                return $this->default_sj_PDF($params, $data);
                break;
        }
        return $this->default_sj_PDF($params, $data);
    }

        return $this->default_sj_PDF($params, $data);
  }

  private function reportheader($params, $data)
  {

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
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
    $str .= $this->reporter->col('PURCHASE REQUISITION', '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('QTY', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('UNIT', '50', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    $str .= $this->reporter->col('D E S C R I P T I O N', '475', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    // $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    // $str .= $this->reporter->col('', '75', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');
    // $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'C', $font, '12', 'B', '30px', '8px');

    return $str;
  }

  public function default_pr_layout($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 28;
    $page = 28;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();

    $str .= $this->reportheader($params, $data);

    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $this->companysetup->getdecimal('qty', $params['params'])), '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['uom'], '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['itemname'], '475', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      // $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      // $str .= $this->reporter->col('', '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      // $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '2px');
      $totalext = $totalext + $data[$i]['ext'];



      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->reportheader($params, $data);
        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM(S)', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($i, '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '440', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'C', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('GRAND TOTAL :', '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '60', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, '12', '', '', ''); //$data[0]['rem']
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'L', $font, '12', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_sj_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

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

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);

    PDF::SetFont($font, '', 9);

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

    PDF::Image(public_path('images/metrodragon/mdlogo.png'), 40, 50, 70, 50);

    PDF::SetY(47);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(75, 0,'', '', 'L', false,0, '', '');
    PDF::MultiCell(375, 0, strtoupper($headerdata[0]->name), '', 'L', false,0, '', '');
    PDF::MultiCell(40, 0,'', '', 'L', false,0, '', '');
    PDF::SetFillColor(0, 0, 0);     // background BLACK
    PDF::SetTextColor(255, 255, 255); // text WHITE
    PDF::MultiCell(210, 0, 'PURCHASE REQUISITION', 0, 'C', true);
    PDF::SetTextColor(0, 0, 0); // back to BLACK text

    PDF::SetY(75);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(75, 0,'', '', 'L', false,0, '', '');
    PDF::MultiCell(375, 0, strtoupper($headerdata[0]->address), '', 'L', false,0, '', '');
    PDF::MultiCell(40, 0,'', '', 'L', false,'', '', '');

    PDF::SetY(73);
    PDF::SetFont($fontbold, '', 15);
    PDF::MultiCell(520, 0,'', '', 'L', false,0, '', '');
    PDF::MultiCell(60, 0, "PR - No. ", '', 'R', false,0, '', '');
    PDF::MultiCell(120, 0,'', '', 'R', false);

    $docno = isset($data[0]['docno']) ? $data[0]['docno'] : '';

    PDF::SetY(75);
    PDF::SetFont($font, '', 13);
    PDF::MultiCell(580, 0, '', '', 'R', false,0, '', '');
    PDF::SetTextColor(255, 0, 0); // RED
    PDF::MultiCell(120, 0, $docno, '', 'R', false);
    PDF::SetTextColor(0, 0, 0); // back to BLACK

    PDF::SetY(110);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(40, 20, "Date : ", '', 'L', false, 0, '', '', true, 0, false, true, 0, 'B', true);
    PDF::MultiCell(100, 20, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '', true, 0, false, true, 0, 'B', true);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', '');

    PDF::SetFont($font, '', 12);
    PDF::MultiCell(50, 0, "ITEM", 'TBL', 'C', false, 0);
    PDF::MultiCell(100, 0, "CODING", 'TBL', 'C', false, 0);
    PDF::MultiCell(50, 0, "QTY", 'TBL', 'C', false, 0);
    PDF::MultiCell(230, 0, "DESCRIPTION", 'TBL', 'L', false, 0);
    PDF::MultiCell(110, 0, "REQ. BY", 'TBL', 'C', false, 0);
    PDF::MultiCell(160, 0, "REMARKS", 'TBLR', 'C', false);
  }

  public function default_sj_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 15;
    $totalext = 0;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_sj_header_PDF($params, $data);

    $countarr = 0;
    $itemno = 0;

    if (!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $itemno++;

        $barcode  = $data[$i]['barcode'];
        $itemname = $data[$i]['itemname'];
        $qty      = number_format($data[$i]['qty'], 2);
        $rem      = $data[$i]['rem'];
        $requestor      = $data[$i]['requestorname'];

        $arr_barcode  = $this->reporter->fixcolumn([$barcode],  '15', 0);
        $arr_itemname = $this->reporter->fixcolumn([$itemname], '30', 0);
        $arr_qty      = $this->reporter->fixcolumn([$qty],      '13', 0);
        $arr_rem      = $this->reporter->fixcolumn([$rem],      '13', 0);
        $arr_requestor = $this->reporter->fixcolumn([$requestor], '20', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_barcode, $arr_itemname, $arr_qty, $arr_rem, $arr_requestor]);

        for ($r = 0; $r < $maxrow; $r++) {
       
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(50,  15, ($r == 0 ? $itemno : ''), 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 15, ' ' . (isset($arr_barcode[$r])  ? $arr_barcode[$r]  : ''), 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(50,  15, ' ' . (isset($arr_qty[$r])      ? $arr_qty[$r]      : ''), 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(230, 15, ' ' . (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), 'BL', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(110, 15, ' ' . (isset($arr_requestor[$r]) ? $arr_requestor[$r] : ''), 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(160, 15, ' ' . (isset($arr_rem[$r])      ? $arr_rem[$r]      : ''), 'BLR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);
        }

        // if (PDF::getY() > 900) {
        //   $this->default_sj_header_PDF($params, $data);
        // }

        if (PDF::getY() > 390) {
            $this->default_sj_footer_PDF($params, $data);
            $this->default_sj_header_PDF($params, $data);
        }
      }
    }

    if (PDF::getY() > 390) {
        $this->default_sj_header_PDF($params, $data);
    }

    while (PDF::getY() < 390) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(50,  15, ' ', 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(100, 15, ' ', 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(50,  15, ' ', 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(230, 15, ' ', 'BL', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(110, 15, ' ', 'BL', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
        PDF::MultiCell(160, 15, ' ', 'BLR', 'L', false, 1, '', '', true, 0, false, true, 0, 'M', false);

        if (PDF::getY() > 390) {
            break;
        }
    }

    $preparedby = isset($data[0]['createby']) ? $data[0]['createby'] : '';

    PDF::SetY(400);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(150, 0, 'Prepared By: ', '', 'L', false, 0);
    PDF::MultiCell(400, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'Approved By: ', '', 'L');

    // PDF::MultiCell(0, 0, "\n");

    PDF::SetY(427);
    PDF::MultiCell(150, 0, $preparedby, 'B', 'C', false, 0);
    PDF::MultiCell(400, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, $params['params']['dataparams']['approved'], 'B', 'C');

    PDF::SetY(445);
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(150, 0, 'WAREHOUSE', '', 'C', false, 0);
    PDF::MultiCell(400, 0, '', '', 'L', false, 0);
    PDF::MultiCell(150, 0, 'VP-OPERATION', '', 'C',false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, '', 'B');

    $wh = isset($data[0]['wh']) ? $data[0]['wh'] : '';
    
    PDF::SetY(470);
    PDF::SetFont($font, '', 7.5);
    PDF::MultiCell(100, 15, 'FORM CODE : ' . $wh, '', 'C', false, 0);
    PDF::MultiCell(30, 15, '', '', 'L', false, 0);
    PDF::MultiCell(110, 15, "WHITE - PURCHASER'S COPY", '', 'L',false,0);
    PDF::MultiCell(15, 15, '', '', 'C',false,0);
    PDF::MultiCell(110, 15, "PINK - HEAD OFFICE COPY", '', 'L',false,0);
    PDF::MultiCell(15, 15, '', '', 'C',false,0);
    PDF::MultiCell(110, 15, "GREEN - MANAGER'S COPY", '', 'L',false,0);
    PDF::MultiCell(15, 15, '', '', 'C',false,0);
    PDF::MultiCell(110, 15, "YELLOW - WAREHOUSE COPY", '', 'L',false,0);
    PDF::MultiCell(15, 15, '', '', 'C',false,0);
    PDF::MultiCell(70, 15, '', '', 'R',false,0);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_sj_footer_PDF($params, $data)
  {
      $font = "";
      $fontbold = "";
      $fontsize = 11;

      if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
          $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
          $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
      }

      $preparedby = isset($data[0]['createby']) ? $data[0]['createby'] : '';

      PDF::SetY(400);
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(150, 0, 'Prepared By: ', '', 'L', false, 0);
      PDF::MultiCell(400, 0, '', '', 'L', false, 0);
      PDF::MultiCell(150, 0, 'Approved By: ', '', 'L');

      // PDF::MultiCell(0, 0, "\n");

      PDF::SetY(427);
      PDF::MultiCell(150, 0, $preparedby, 'B', 'C', false, 0);
      PDF::MultiCell(400, 0, '', '', 'L', false, 0);
      PDF::MultiCell(150, 0, $params['params']['dataparams']['approved'], 'B', 'C');

      PDF::SetY(445);
      PDF::SetFont($font, '', 10);
      PDF::MultiCell(150, 0, 'WAREHOUSE', '', 'C', false, 0);
      PDF::MultiCell(400, 0, '', '', 'L', false, 0);
      PDF::MultiCell(150, 0, 'VP-OPERATION', '', 'C', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, '', 'B');

      $wh = isset($data[0]['wh']) ? $data[0]['wh'] : '';

      PDF::SetY(470);
      PDF::SetFont($font, '', 7.5);
      PDF::MultiCell(100, 15, 'FORM CODE : ' . $wh, '', 'C', false, 0);
      PDF::MultiCell(30, 15, '', '', 'L', false, 0);
      PDF::MultiCell(110, 15, "WHITE - PURCHASER'S COPY", '', 'L', false, 0);
      PDF::MultiCell(15, 15, '', '', 'C', false, 0);
      PDF::MultiCell(110, 15, "PINK - HEAD OFFICE COPY", '', 'L', false, 0);
      PDF::MultiCell(15, 15, '', '', 'C', false, 0);
      PDF::MultiCell(110, 15, "GREEN - MANAGER'S COPY", '', 'L', false, 0);
      PDF::MultiCell(15, 15, '', '', 'C', false, 0);
      PDF::MultiCell(110, 15, "YELLOW - WAREHOUSE COPY", '', 'L', false, 0);
      PDF::MultiCell(15, 15, '', '', 'C', false, 0);
      PDF::MultiCell(70, 15, '', '', 'R', false, 0);
  }

}