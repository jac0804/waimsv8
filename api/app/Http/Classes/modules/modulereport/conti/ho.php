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

class ho
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
        $str = $this->rpt_ho_layout($config, $data);
      }else if($config['params']['dataparams']['print'] == "PDFM") {
        $str = $this->rpt_ho_PDF($config, $data);
      }
      return $str;
  }

  public function report_default_query($config)
  {

    $trno = $config['params']['dataid'];
    $query = "
        select
        num.trno, 
        num.docno, 
        head.empid,
        em.client as empcode, 
        em.clientname as empname, 
        head.deptid,
        d.client as dept, 
        d.clientname as deptname,
        date(head.dateid) as dateid,  
        head.jobtitle, 
        head.rem,
        detail.itemname, detail.amt, detail.rem as notes
        from turnoveritemhead as head
        left join turnoveritemdetail as detail on head.trno = detail.trno
        left join client as em on em.clientid=head.empid
        left join client as d on d.clientid=head.deptid
        left join hrisnum as num on num.trno = head.trno
        where num.trno = '$trno' and num.doc='HO'
        union all
        select
        num.trno,
        num.docno, 
        head.empid,
        em.client as empcode, 
        em.clientname as empname, 
        head.deptid,
        d.client as dept, 
        d.clientname as deptname,
        date(head.dateid) as dateid,  
        head.jobtitle,
        head.rem,
        detail.itemname, detail.amt, detail.rem as notes
        from hturnoveritemhead as head
        left join hturnoveritemdetail as detail on head.trno = detail.trno
        left join client as em on em.clientid=head.empid
        left join client as d on d.clientid=head.deptid
        left join hrisnum as num on num.trno = head.trno
        where num.trno = '$trno' and num.doc='HO'
      ";

      $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
      return $result;
  } //end fn

  public function PDF_header($params, $data) 
  {
    $border = '1px solid';
    $fontsize = '11';


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
    PDF::MultiCell(200, 15, "TURN OVER OF ITEMS ", '', 'L', false,0);
    PDF::MultiCell(320, 15, "", '', 'L', false,0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "Document # : ", '', 'R', false,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 15, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 10, "", '', 'L', false,0);
    PDF::MultiCell(420, 10, '', '', 'L', false,0);
    PDF::MultiCell(100, 10, "", '', 'L', false,0);
    PDF::MultiCell(140, 10, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "Employee : ", '', 'L', false,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 15, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), '', 'L', false,0);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "DATE : ", '', 'R', false,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(140, 15, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(420, 0, '', 'T', 'L', false,0);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(140, 0, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "Job Title : ", '', 'L', false,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 15, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(420, 0, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 15, "Department : ", '', 'L', false,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(420, 15, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '', 'L', false);

    PDF::SetFont($fontbold, '', 9);
    PDF::MultiCell(100, 0, "", '', 'L', false,0);
    PDF::MultiCell(420, 0, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(760, 0, '', 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(270, 5, "ITEM NAME", '', 'L', false,0);
    PDF::MultiCell(200, 5, "ESTIMATED VALUE", '', 'L', false,0);
    PDF::MultiCell(200, 5, "NOTES", '', 'L', false);

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(760, 0, '', 'B', 'L', false);
    
  }

  public function rpt_ho_PDF($config, $data)
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

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(760, 0, '', '', 'L', false);

    for ($i = 0; $i < count($data); $i++) 
    {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(270, 5, $data[$i]['itemname'], '', 'L', false,0);
      PDF::MultiCell(200, 5, $data[$i]['amt'], '', 'L', false,0);
      PDF::MultiCell(200, 5, $data[$i]['notes'], '', 'L', false);
    }

    PDF::SetFont($fontbold, '', 5);
    PDF::MultiCell(760, 5, "", 'B', 'L', false);


    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(100, 10, "NOTE : ", '', 'L', false, 0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(660, 10, (isset($data[0]['rem']) ? $data[0]['rem'] : ''), '', 'L', false);

    PDF::MultiCell(0, 0, "\n\n\n\n");
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, 'B', $fontsize);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $config['params']['dataparams']['received'], '', 'L');

    
    return PDF::Output($this->modulename . '.pdf', 'S');
    
  } //end fn


  public function default_header($params, $data) 
  {
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
   
    $str .= $this->reporter->col('TURN OVER OF ITEMS', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['docno']) ? $data[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['empname']) ? $data[0]['empname'] : ''), '360', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), '75', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Job Title : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), '650', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ', '105', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), '650', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM NAME', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('ESTIMATED VALUE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->col('NOTES', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;;
  }

  public function rpt_ho_layout($params, $data)
  {
    $str = '';
    $font = "Century Gothic ";
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
      $str .= $this->reporter->col($data[$i]['itemname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
      $str .= $this->reporter->col($data[$i]['amt'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
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
    $str .= $this->reporter->col($data[0]['rem'], '600', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
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
