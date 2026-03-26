<?php

namespace App\Http\Classes\modules\modulereport\conti;

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

class ht
{

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

  public function createreportfilter()
  {
    $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
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

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
    if($config['params']['dataparams']['print'] == "default"){
      $str = $this->rpt_ht_layout($config, $data);
    }else if($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_ht_PDF($config, $data);
    }
    return $str;
  }

  public function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
      select
      head.trno, 
      head.docno, 
      date(head.dateid) as dateid, 
      head.ttype,
      head.title, 
      head.venue, 
      head.tdate1, 
      head.tdate2, 
      head.speaker, 
      head.amt,
      head.cost, 
      head.attendees, 
      head.remarks, detail.notes, emp.client as empcode, emp.clientname as empname
      from traininghead as head
      left join hrisnum as num on num.trno = head.trno
      left join trainingdetail as detail on head.trno = detail.trno
      left join client as emp on emp.clientid = detail.empid
      where num.trno = '$trno' and num.doc='HT'
      union all
      select
      head.trno,
      head.docno,
      date(head.dateid) as dateid, 
      head.ttype,
      head.title, 
      head.venue,
      head.tdate1, 
      head.tdate2, 
      head.speaker, 
      head.amt,
      head.cost, 
      head.attendees, 
      head.remarks, detail.notes, emp.client as empcode, emp.clientname as empname
      from htraininghead as head
      left join hrisnum as num on num.trno = head.trno
      left join htrainingdetail as detail on head.trno = detail.trno
      left join client as emp on emp.clientid = detail.empid
      where num.trno = '$trno' and num.doc='HT'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  public function pdf_header($params, $data) 
  {
    $border = '1px solid';
    $font_size = '11';
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font = "";
    $fontbold = "";
    
    $count = 55;
    $page = 54;

    $prepared = $params['params']['dataparams']['prepared'];
    $received = $params['params']['dataparams']['received'];
    $approved = $params['params']['dataparams']['approved'];

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);

    PDF::SetFont($font, '', 9);
    $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);
    PDF::MultiCell(0, 0, $reporttimestamp, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');



    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(200, 15, "TRAINING ENTRY ", '', 'L', false,0);
    PDF::MultiCell(320, 15, "", '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(100, 15, "Document # : ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(140, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(420, 0, '', '', 'L', false,0);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(140, 0, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(100, 15, "Training Type : ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(420, 15, (isset($data[0]['ttype']) ? $data[0]['ttype'] : ''), '', 'L', false,0);
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(100, 15, "DATE : ", '', 'R', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(140, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(420, 0, '', 'T', 'L', false,0);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(140, 0, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(100, 15, "Title : ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(420, 15, (isset($data[0]['title']) ? $data[0]['title'] : ''), '', 'L', false);

    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(420, 0, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(100, 15, "Venue : ", '', 'L', false,0);
    PDF::SetFont($font, '', 12);
    PDF::MultiCell(420, 15, (isset($data[0]['venue']) ? $data[0]['venue'] : ''), '', 'L', false);

    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(420, 0, '', 'T', 'L', false);

    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(760, 30, "", 'T', 'L', false);



    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(270, 5, "EMPLOYEE CODE", '', 'L', false,0);
    PDF::MultiCell(200, 5, "EMPLOYEE NAME", '', 'L', false,0);
    PDF::MultiCell(200, 5, "NOTES", '', 'L', false);
    
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(760, 30, "", 'T', 'L', false, 0);

    PDF::MultiCell(0, 0, "\n");
    

    
   
  }

  public function rpt_ht_PDF($config, $data)
  {

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->pdf_header($config, $data);

    for ($i = 0; $i < count($data); $i++) 
    {
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(270, 5, $data[$i]['empcode'], '', 'L', false,0);
      PDF::MultiCell(200, 5, $data[$i]['empname'], '', 'L', false,0);
      PDF::MultiCell(200, 5, $data[$i]['notes'], '', 'L', false);
    }
    PDF::MultiCell(0, 0, "\n");
    PDF::MultiCell(760, 5, "", 'T', 'L', false, 0);


    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 14);
    PDF::MultiCell(100, 10, "NOTE : ", '', 'L', false, 0);
    PDF::MultiCell(660, 10, (isset($data[0]['remarks']) ? $data[0]['remarks'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  
  } //end fn

  public function default_header($params, $data) {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = "";
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";

    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('TRAINING ENTRY', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Training Type : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['ttype']) ? $data[0]['ttype'] : ''), '300', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '75', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Title : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['title']) ? $data[0]['title'] : ''), '565', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Venue : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['venue']) ? $data[0]['venue'] : ''), '565', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EMPLOYEE CODE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('EMPLOYEE NAME', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('NOTES', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function rpt_ht_layout($params, $data)
  {
    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';$font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);

    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['empcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['empname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['notes'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();

      if($this->reporter->linecounter==$page){
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params,$data);
        $str .= $this->reporter->begintable('800');
        $page=$page + $count;
      }
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '400px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col(' ', '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '50px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('', '125px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['remarks'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


}
