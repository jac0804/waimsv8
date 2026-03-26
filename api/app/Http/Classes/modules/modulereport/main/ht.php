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
    // $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);
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
    $query = "
      select
      head.trno, 
      head.docno, 
      date(head.dateid) as dateid, 
      head.ttype,
      head.title, 
      head.venue, 
      date(head.tdate1) as tdate1, 
      date(head.tdate2) as tdate2,  
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
      date(head.tdate1) as tdate1, 
      date(head.tdate2) as tdate2, 
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

  public function reportplotting($config, $data)
  {
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_HT_layout($config, $data);
    } else {
      $str = $this->rpt_HT_PDF($config, $data);
    }
    return $str;
  }

  public function default_header_PDF($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $fontsize = "11";
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select name,address,tel from center where code = '" . $center . "'";
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
    PDF::MultiCell(0, 0, $center . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . $username, '', 'L');
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'C');

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 30, "TRAINING ENTRY", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 10, "DOCUMENT # : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false, 0);
    PDF::MultiCell(250, 10, '', '', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(120, 10, "DATE : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(140, 10, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 10, "Training Type : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, (isset($data[0]['ttype']) ? $data[0]['ttype'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(130, 10, 'Title : ', '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, (isset($data[0]['title']) ? $data[0]['title'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 10, "Venue : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(140, 10, (isset($data[0]['venue']) ? $data[0]['venue'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 10, "Date From : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, (isset($data[0]['tdate1']) ? $data[0]['tdate1'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(130, 10, 'Date To : ', '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, (isset($data[0]['tdate2']) ? $data[0]['tdate2'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 10, "Speaker : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(140, 10, (isset($data[0]['speaker']) ? $data[0]['speaker'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 10, "Training Cost : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, (isset($data[0]['cost']) ? $data[0]['cost'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(130, 10, 'Budget Per Employee : ', '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(150, 10, (isset($data[0]['amt']) ? $data[0]['amt'] : ''), 'B', 'L', false, 0);
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(90, 10, "Attendees : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(140, 10, (isset($data[0]['attendees']) ? $data[0]['attendees'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(253, 0, "EMPLOYEE CODE", '', 'L', false, 0);
    PDF::MultiCell(253, 0, "EMPLOYEE NAME", '', 'L', false, 0);
    PDF::MultiCell(254, 0, "NOTES", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");
  }

  public function rpt_HT_PDF($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $fontsize = "11";
    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->default_header_PDF($config, $data);

    for ($i = 0; $i < count($data); $i++) {
      $maxrow = 1;
      $arr_empcode = $this->reporter->fixcolumn([$data[$i]['empcode']], '35', 0);
      $arr_empname = $this->reporter->fixcolumn([$data[$i]['empname']], '35', 0);
      $arr_notes = $this->reporter->fixcolumn([$data[$i]['notes']], '35', 0);

      $maxrow = $this->othersClass->getmaxcolumn([$arr_empcode, $arr_empname, $arr_notes]);

      for ($r = 0; $r < $maxrow; $r++) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(253, 20, (isset($arr_empcode[$r]) ? $arr_empcode[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(253, 20, (isset($arr_empname[$r]) ? $arr_empname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
        PDF::MultiCell(254, 20, (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
      }

      if (intVal($i) + 1 == $page) {
        $this->default_header_PDF($config, $data);
        $page += $count;
      }
    }

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(40, 0, "NOTE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(720, 0, $data[0]['remarks'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(253, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(253, 0, 'Received By : ', '', 'L', false, 0);
    PDF::MultiCell(254, 0, 'Approved By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(253, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(253, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(254, 0, $config['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function default_header($config, $data)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = "";
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('TRAINING ENTRY', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT # :', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '110', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Training Type : ', '180', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['ttype']) ? $data[0]['ttype'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Title : ', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['title']) ? $data[0]['title'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Venue : ', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['venue']) ? $data[0]['venue'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date From : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['tdate1']) ? $data[0]['tdate1'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Date To : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['tdate2']) ? $data[0]['tdate2'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Speaker : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['speaker']) ? $data[0]['speaker'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Training Cost : ', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['cost']) ? $data[0]['cost'] : ''), '200', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Budget Per Employee : ', '250', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['amt']) ? $data[0]['amt'] : ''), '110', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('Attendees : ', '80', null, false, $border, '', 'R', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['attendees']) ? $data[0]['attendees'] : ''), '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

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

  public function rpt_HT_layout($config, $data)
  {
    $companyid = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $font = "Century Gothic ";
    $fontsize = "11";
    $border = "1px solid ";
    $count = 35;
    $page = 35;
    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($config, $data);

    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['empcode'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['empname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['notes'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($config, $data);
        $str .= $this->reporter->begintable('800');
        // $str .= $this->reporter->printline();
        $page = $page + $count;
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
    $str .= $this->reporter->col($config['params']['dataparams']["prepared"], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["approved"], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($config['params']['dataparams']["received"], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } //end fn


}
