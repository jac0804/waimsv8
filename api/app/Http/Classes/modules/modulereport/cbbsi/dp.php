<?php

namespace App\Http\Classes\modules\modulereport\cbbsi;

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

class dp
{

  private $modulename = "Dispatch Schedule";
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
    $isposted = $this->othersClass->isposted($config);
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red'],
    ]);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $signatories = $this->othersClass->getSignatories($config);
    $prepared = '';
    $approved = '';
    $received =  '';
    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'prepared':
          $prepared = $value->fieldvalue;
          break;
        case 'approved':
          $approved = $value->fieldvalue;
          break;
        case 'received':
          $received = $value->fieldvalue;
          break;
      }
    }
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '". $prepared."' as prepared,
      '". $approved."' as approved,
      '". $received."' as received"
    );
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $qry = "select docno, date(dateid) as dateid, trnxtype, date(deldate) as deldate, truckno, driver, rem from dphead where trno=".$trno."
      union all
    select docno, date(dateid) as dateid, trnxtype, date(deldate) as deldate, truckno, driver, rem from hdphead where trno=".$trno;
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  } //end fn  

  public function getData($config) {
    $trno = $config['params']['dataid'];
    $qry = "select head.docno, date(head.dateid) as dateid, c.client, c.clientname, head.rem, 
    sum(stock.ext) as ext,
      info.shipdate,ifnull(num2.docno,'') as invoiceno, num.dptrno as trno, 0 as line, head.deldate, head.trnxtype, ag.clientname as agent
      FROM lahead as head 
      left join lastock as stock on stock.trno=head.trno
      left join client as c on c.client=head.client
      left join cntnum as num on num.trno=head.trno
      left join cntnuminfo as info on info.trno=head.trno
      left join cntnum as num2 on num2.trno=num.svnum
      left join client as ag on ag.client=head.agent
      where num.dptrno = $trno
      group by head.docno,head.dateid,c.client,c.clientname,head.rem,info.shipdate,num2.docno,num.dptrno,ag.clientname, head.deldate, head.trnxtype
      UNION ALL  
      select head.docno,date(head.dateid) as dateid,c.client,c.clientname,head.rem, 
      sum(stock.ext) as ext,
      info.shipdate,ifnull(num2.docno,'') as invoiceno, num.dptrno as trno,0 as line, head.deldate, head.trnxtype, ag.clientname as agent
      FROM glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client as c on c.clientid=head.clientid
      left join cntnum as num on num.trno=head.trno
      left join hcntnuminfo as info on info.trno=head.trno
      left join cntnum as num2 on num2.trno=num.svnum
      left join client as ag on ag.clientid=head.agentid
      where num.dptrno = $trno
      group by head.docno,head.dateid,c.client,c.clientname,head.rem,info.shipdate,num2.docno,num.dptrno,ag.clientname, head.deldate, head.trnxtype";
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);
    return $result;
  }

  public function reportplotting($params, $data)
  {
    return $this->default_dp_pdf($params, $data);
  }

  public function default_dp_pdf($params, $data2) {
    $decimalcurr = $this->companysetup->getdecimal('currency', $params['params']);
    $count = $page = 35;
    $totalext = 0;
    $extdecimal=$this->companysetup->getdecimal('price', $params['params']);

    $font = '';
    $fontbold = '';
    $border = '1px solid';
    $fontsize = '11';
    if(Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path().'/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path().'/images/fonts/GOTHICB.TTF');
    }
    $this->default_dp_header_pdf($params, $data2);
    $data = $this->getData($params);

    if(!empty($data)) {
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $drtsno = $data[$i]['docno'];
        $drtsdate = $data[$i]['dateid'];
        $invoiceno = $data[$i]['invoiceno'];
        $agent = $data[$i]['agent'];
        $cust = $data[$i]['clientname'];
        $ext = $data[$i]['ext'];

        $arr_drtsno = $this->reporter->fixcolumn([$drtsno], '15', 0);
        $arr_drtsdate = $this->reporter->fixcolumn([$drtsdate], '15', 0);
        $arr_invoiceno = $this->reporter->fixcolumn([$invoiceno], '15', 0);
        $arr_agent = $this->reporter->fixcolumn([$agent], '25', 0);
        $arr_cust = $this->reporter->fixcolumn([$cust], '18', 0);
        $arr_ext = $this->reporter->fixcolumn([number_format($ext,$extdecimal)], '15', 0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_drtsno, $arr_drtsdate, $arr_invoiceno, $arr_agent, $arr_cust, $arr_ext]);
        for ($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', '9');
          PDF::MultiCell(85, 0, ' '.(isset($arr_drtsno[$r]) ? $arr_drtsno[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(70, 0, ' '.(isset($arr_drtsdate[$r]) ? $arr_drtsdate[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(80, 0, ' '.(isset($arr_invoiceno[$r]) ? $arr_invoiceno[$r] : ''), '', 'C', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' '.(isset($arr_agent[$r]) ? $arr_agent[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(100, 0, ' '.(isset($arr_cust[$r]) ? $arr_cust[$r] : ''), '', 'L', false, 0, '', '', true, 0, false, true, 0, 'M', false);
          PDF::MultiCell(65, 0, ' '.(isset($arr_ext[$r]) ? $arr_ext[$r] : ''), '', 'R', false, 1, '', '', true, 0, false, true, 0, 'M', false);
          
          if(PDF::getY() > 900) {
            $this->default_dp_header_pdf($params, $data2);
          }
        }
        $totalext += $data[$i]['ext'];
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', '');

    PDF::SetFont($fontbold, '', '10');
    PDF::MultiCell(85, 0, 'No. of Trnx: '.count($data), '', 'L', false, 0);
    PDF::MultiCell(350, 0, 'GRAND TOTAL:', '', 'R', false, 0);
    PDF::MultiCell(65, 0, number_format($totalext, $decimalcurr).' ', '', 'R');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(50, 0, 'NOTE:', '', 'L', false, 0);
    PDF::MultiCell(670, 0, $data2[0]['rem'], '', 'L');

    PDF::MultiCell(0, 0, "\n\n\n");
    PDF::MultiCell(245, 0, 'Prepared by:', '', 'L', false, 0);
    PDF::MultiCell(245, 0, 'Approved by:', '', 'L', false, 0);
    PDF::MultiCell(230, 0, 'Received by:', '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(230, 0, $params['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(15, 0, '', '', 'L', false, 0);
    PDF::MultiCell(230, 0, $params['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(15, 0, '', '', 'L', false, 0);
    PDF::MultiCell(230, 0, $params['params']['dataparams']['received'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(100, 0, 'Vehicle:', '', 'L', false, 0);
    PDF::MultiCell(285, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(285, 0, 'SG Signature:', '', 'L', false);

    PDF::MultiCell(100, 0, 'Departure Time:', '', 'L', false, 0);
    PDF::MultiCell(285, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(285, 0, '', 'B', 'L', false, 1);

    PDF::MultiCell(100, 0, 'Arrival Time:', '', 'L', false, 0);
    PDF::MultiCell(285, 0, '', 'B', 'L', false, 0);
    PDF::MultiCell(50, 0, '', '', 'L', false, 0);
    PDF::MultiCell(285, 0, '', 'B', 'L', false);
    return PDF::Output($this->modulename . '.pdf', 'S');
  }

  public function default_dp_header_pdf($params, $data) {
    $center = $params['params']['center'];
    $username = $params['params']['user'];
    $qry = "select code, name, address, tel from center where code='".$center."'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $font = '';
    $fontbold = '';
    $fontsize = '11';
    if(Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path().'/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path().'/images/fonts/GOTHICB.TTF');
    }

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename.' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(40, 40);


    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 13);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');
    
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(540, 0, '', '', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Doc #: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', 10);
    PDF::MultiCell(130, 0, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($font, '', 10);
    PDF::MultiCell(540, 0, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 18);
    PDF::MultiCell(520, 0, $this->modulename, '', 'L', false, 1, '', '120');

    PDF::SetFont($fontbold, '', 8);
    PDF::MultiCell(720, 0, '', '', 'L', false);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, 'Trnx Type: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(450, 0, (isset($data[0]['trnxtype']) ? $data[0]['trnxtype'] : ''), 'B', 'L', false, 0, '', '');
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(50, 0, 'Date:', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(130, 0, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, 'Del/Ship Date:', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(630, 0, (isset($data[0]['deldate']) ? $data[0]['deldate'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, 'Truck No.: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(630, 0, (isset($data[0]['truckno']) ? $data[0]['truckno'] : ''), 'B', 'L', false, 1, '', '');

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(90, 0, 'Driver: ', '', 'L', false, 0, '', '');
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(630, 0, (isset($data[0]['driver']) ? $data[0]['driver'] : ''), 'B', 'L', false, 1, '', '');

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'T');

    PDF::SetFont($font, 'B', 10);
    PDF::MultiCell(85, 0, 'DR/TS No.', '', 'C', false, 0);
    PDF::MultiCell(70, 0, 'DR/TS Date', '', 'C', false, 0);
    PDF::MultiCell(80, 0, 'SI No.', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Agent', '', 'C', false, 0);
    PDF::MultiCell(100, 0, 'Cust/Dep', '', 'C', false, 0);
    PDF::MultiCell(65, 0, 'DR/TS Amt', '', 'C', false, 0);
    PDF::MultiCell(75, 0, 'Depart', '', 'C', false, 0);
    PDF::MultiCell(75, 0, 'Remarks', '', 'C', false, 0);
    PDF::MultiCell(70, 0, 'Cust. Sig.', '', 'C', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(720, 0, '', 'B');
  }
}
