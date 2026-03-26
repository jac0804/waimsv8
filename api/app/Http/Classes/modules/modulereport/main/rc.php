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

class rc
{

  private $modulename = "Received Checks";
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

  public function createreportfilter($config){
    $fields = ['radioprint','prepared','approved','received','print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
      // ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
    ]);
    if ($config['params']['companyid'] == 10 || $config['params']['companyid'] == 12) { // afti
        data_set($col1, 'prepared.readonly',true);
        data_set($col1, 'prepared.type','lookup');
        data_set($col1, 'prepared.action','lookupclient');
        data_set($col1, 'prepared.lookupclass','prepared');

        data_set($col1, 'approved.readonly',true);
        data_set($col1, 'approved.type','lookup');
        data_set($col1, 'approved.action','lookupclient');
        data_set($col1, 'approved.lookupclass','approved');

        data_set($col1, 'received.readonly',true);
        data_set($col1, 'received.type','lookup');
        data_set($col1, 'received.action','lookupclient');
        data_set($col1, 'received.lookupclass','received');
      }
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '' as prepared,
      '' as approved,
      '' as received
      ");
  }

  public function report_default_query($filters){
    $trno = $filters['params']['dataid'];
    $query="
    select detail.line, head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref, head.ourref,
    client.client, date(head.checkdate) as hcheckdate, date(detail.checkdate) as dcheckdate, head.checkno as hcheckno, head.rem,
     detail.amount, detail.checkno as dcheckno, concat(pm.code,'~',pm.name) as pmx, phase.code as phase, blklot.blk, blklot.lot, housemodel.model
    from rchead as head 
    left join rcdetail as detail on detail.trno=head.trno
    left join client on client.client=head.client
    left join projectmasterfile as pm on pm.line = head.projectid
    left join phase on phase.line = head.phaseid
    left join blklot on blklot.line = head.blklotid
    left join housemodel on housemodel.line = head.modelid
    where head.doc='rc' and head.trno='$trno'
    union all
    select detail.line, head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref, head.ourref,
    client.client, date(head.checkdate) as hcheckdate, date(detail.checkdate) as dcheckdate, head.checkno as hcheckno, head.rem,
    detail.amount, detail.checkno as dcheckno, concat(pm.code,'~',pm.name) as pmx, phase.code as phase, blklot.blk, blklot.lot, housemodel.model
    from hrchead as head 
    left join hrcdetail as detail on detail.trno=head.trno
    left join client on client.client=head.client
    left join projectmasterfile as pm on pm.line = head.projectid
    left join phase on phase.line = head.phaseid
    left join blklot on blklot.line = head.blklotid
    left join housemodel on housemodel.line = head.modelid
    where head.doc='rc' and head.trno='$trno' order by line";
    
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($params, $data) {
    return $this->default_RC_PDF($params, $data);
  }

  public function default_RC_header_PDF($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $companyid = $params['params']['user'];
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
    PDF::SetMargins(40, 40);
    PDF::AddPage('p', [800, 1000]);

    PDF::SetCellPaddings(4, 4, 4, 4);
    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::SetFont($font, '', 9);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');

    // MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 0, '',  '100');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Docno #: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(100, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(0, 40, "", '', 'L');
    PDF::MultiCell(80, 0, "Customer: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Date: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '',  '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(80, 0, "Address: ", '', 'L', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(470, 0, (isset($data[0]['address']) ? $data[0]['address'] : ''), 'B', 'L', false, 0, '',  '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, "Yourref: ", '', 'R', false, 0, '',  '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(100, 0, (isset($data[0]['yourref']) ? $data[0]['yourref'] : ''), 'B', 'L', false, 1, '',  '');

    if($this->companysetup->getsystemtype($params['params']) == 'REALSESTATE'){
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(80, 0, "Project: ", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(420, 0, (isset($data[0]['pmx']) ? $data[0]['pmx'] : ''), 'B', 'L', false, 0, '',  '');
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(100, 0, "House Model: ", '', 'R', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(100, 0, (isset($data[0]['model']) ? $data[0]['model'] : ''), 'B', 'L', false, 1, '',  '');
  
      PDF::SetFont($font, '', $fontsize);
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(80, 0, "Block : ", '', 'L', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(470, 0, (isset($data[0]['blk']) ? $data[0]['blk'] : ''), 'B', 'L', false, 0, '',  '');
      PDF::SetFont($fontbold, '', $fontsize);
      PDF::MultiCell(50, 0, "Lot : ", '', 'R', false, 0, '',  '');
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(100, 0, (isset($data[0]['lot']) ? $data[0]['lot'] : ''), 'B', 'L', false, 1, '',  '');
    }
   

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, 'B', 12);
    PDF::MultiCell(235, 0, "CHECK NO.", 'TB', 'L', false, 0);
    PDF::MultiCell(235, 0, "CHECK DATE", 'TB', 'C', false,0);
    PDF::MultiCell(235, 0, "AMOUNT", 'TB', 'R', false);
    PDF::SetFont($font, '', 5);
  }

  public function default_RC_PDF($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $params['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $params['params']);
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $count = $page = 35;

    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "11";
    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_RC_header_PDF($params, $data);

    PDF::SetFont($font, '', 5);
  
    $countarr = 0;
    $totaldb = 0;
    PDF::SetCellPaddings(1, 1, 1, 1);
    if (!empty($data)) {
      
      for ($i = 0; $i < count($data); $i++) {

        $maxrow = 1;
        $dcheckno = $data[$i]['dcheckno'];
        $dcheckdate = $data[$i]['dcheckdate'];
        $amount = number_format($data[$i]['amount'],$decimalcurr);

        $arrdcheckno = $this->reporter->fixcolumn([$dcheckno],'20',0);
        $arrdcheckdate = $this->reporter->fixcolumn([$dcheckdate],'20',0);
        $arramount = $this->reporter->fixcolumn([$amount],'20',0);

        $maxrow = $this->othersClass->getmaxcolumn([$arrdcheckno, $arrdcheckdate, $arramount]);

        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(235, 0, (isset($arrdcheckno[$r]) ? $arrdcheckno[$r] : ''), '', 'L', false, 0, '', '', true, 1);
          PDF::MultiCell(235, 0, (isset($arrdcheckdate[$r]) ? $arrdcheckdate[$r] : ''), '', 'C', false, 0, '', '', false, 1);
          PDF::MultiCell(235, 0, (isset($arramount[$r]) ? $arramount[$r] : ''), '', 'R', false, 1, '', '', false, 1);
        }
    
        $totaldb += $data[$i]['amount'];
      }
    }
 
    PDF::SetCellPaddings(4, 4, 4, 4);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(265, 0, '', 'T', 'R', false, 0);
    PDF::MultiCell(290, 0, 'GRAND TOTAL: ', 'T', 'R', false, 0);
    PDF::MultiCell(150, 0, number_format($totaldb, $decimalprice), 'T', 'R', false, 0);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(560, 0, '', '', 'L');

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

}
