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

class hr
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

  public function report_default_query($config){
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
        detail.itemname, detail.amt, detail.rem as notes, detail.ref
        from returnitemhead as head 
        left join returnitemdetail as detail on detail.trno = head.trno 
        left join client as em on em.clientid=head.empid
        left join client as d on d.clientid=head.deptid
        left join hrisnum as num on num.trno = head.trno   
        where num.trno = '$trno' and num.doc='HR'
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
        detail.itemname, detail.amt, detail.rem as notes, detail.ref
        from hreturnitemhead as head
        left join hreturnitemdetail as detail on detail.trno = head.trno
        left join client as em on em.clientid=head.empid
        left join client as d on d.clientid=head.deptid
        left join hrisnum as num on num.trno = head.trno   
        where num.trno = '$trno' and num.doc='HR'
      ";

      $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
      return $result;
  } //end fn

  public function reportplotting($config,$data)
  { 
    $data = $this->report_default_query($config);
    if($config['params']['dataparams']['print'] == "default"){
      $str = $this->rpt_HR_layout($config, $data);
    } else if($config['params']['dataparams']['print'] == "PDFM"){
      $str = $this->rpt_HR_PDF($config, $data);
    }
    return $str;
  }


    public function default_header_PDF($config, $data) {
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
      PDF::MultiCell(0, 0, $center.' - '.date_format(date_create($current_timestamp),'m/d/Y H:i:s').'  '.$username, '', 'L');
      PDF::SetFont($fontbold, '', 12);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'C');
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address)."\n".strtoupper($headerdata[0]->tel)."\n\n\n", '', 'C');

      PDF::SetFont($fontbold, '', 14);
      PDF::MultiCell(550, 18, "RETURN OF ITEMS", '', 'L', false,0);
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(100, 18, "DOCUMENT # : ", '', 'R', false,0);
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(100, 18, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(600, 0, "", '', 'L', false, 0);
      PDF::MultiCell(160, 0, "", '', 'L', false);

      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(100, 18, "Employee : ", '', 'L', false,0);
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(450, 18, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false,0);
      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(100, 18, "DATE : ", '', 'R', false,0);
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(100, 18, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(600, 0, "", '', 'L', false, 0);
      PDF::MultiCell(160, 0, "", '', 'L', false);

      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(100, 18, "Job Title : ", '', 'L', false,0);
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(450, 18, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'B', 'L', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(600, 0, "", '', 'L', false, 0);
      PDF::MultiCell(160, 0, "", '', 'L', false);

      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(100, 18, "Department : ", '', 'L', false,0);
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(450, 18, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'B', 'L', false);
      
      PDF::MultiCell(0, 0, "\n\n");
      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
      PDF::MultiCell(60, 0, "", 'T', 'L', false);

      PDF::SetFont($fontbold, '', 11);
      PDF::MultiCell(190, 0, "ITEM NAME", '', 'L', false,0);
      PDF::MultiCell(190, 0, "ESTIMATED VALUE", '', 'L', false,0);
      PDF::MultiCell(190, 0, "NOTES", '', 'L', false,0);
      PDF::MultiCell(190, 0, "REFERENCE", '', 'L', false);

      PDF::SetFont($font, '', 5);
      PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
      PDF::MultiCell(60, 0, "", 'B', 'L', false);

      PDF::MultiCell(0, 0, "\n");

    }
  public function cdohris_default_header_PDF($config, $data)
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
    PDF::MultiCell(0, 0, "\n");
    PDF::SetFont($fontbold, '', 12);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->name), '', 'L');
    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(0, 0, strtoupper($headerdata[0]->address) . "\n" . strtoupper($headerdata[0]->tel) . "\n\n\n", '', 'L');

    $qry = "select division from employee  where empid  = '" . $data[0]['empid'] . "'";
    $div = $this->coreFunctions->opentable($qry);

    if ($div[0]->division == '001') {
      PDF::Image($this->companysetup->getlogopath($config['params']) . 'logocdo2cycles.jpg', '645', '-10', 160, 160);
    }
    if ($div[0]->division == '002') {
      PDF::Image($this->companysetup->getlogopath($config['params']) . 'logombc.jpg', '645', '-10', 160, 160);
    }
    if ($div[0]->division == '003') {
      PDF::Image($this->companysetup->getlogopath($config['params']) . 'logoridefund.png', '645', '-10', 160, 160);
    }

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(550, 18, "RETURN OF ITEMS", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Employee : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(450, 18, (isset($data[0]['empname']) ? $data[0]['empname'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Job Title : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(450, 18, (isset($data[0]['jobtitle']) ? $data[0]['jobtitle'] : ''), 'B', 'L', false,0);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "DOCUMENT # : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 18, (isset($data[0]['docno']) ? $data[0]['docno'] : ''), 'B', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", '', 'L', false, 0);
    PDF::MultiCell(160, 0, "", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "Department : ", '', 'L', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(450, 18, (isset($data[0]['deptname']) ? $data[0]['deptname'] : ''), 'B', 'L', false,0);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(100, 18, "DATE : ", '', 'R', false, 0);
    PDF::SetFont($font, '', 11);
    PDF::MultiCell(100, 18, (isset($data[0]['dateid']) ? $data[0]['dateid'] : ''), 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n\n");
    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'T', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(190, 0, "ITEM NAME", '', 'L', false, 0);
    PDF::MultiCell(190, 0, "ESTIMATED VALUE", '', 'L', false, 0);
    PDF::MultiCell(190, 0, "NOTES", '', 'L', false, 0);
    PDF::MultiCell(190, 0, "REFERENCE", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(700, 0, "", 'B', 'L', false, 0);
    PDF::MultiCell(60, 0, "", 'B', 'L', false);

    PDF::MultiCell(0, 0, "\n");
  }

    public function rpt_HR_PDF($config, $data)
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
      if($companyid == 58){
      $this->cdohris_default_header_PDF($config, $data);
      }else {
      $this->default_header_PDF($config, $data);
      }
     
     
      for ($i = 0; $i < count($data); $i++) {
        $maxrow = 1;
        $arr_itemname = $this->reporter->fixcolumn([$data[$i]['itemname']],'25',0);
        $arr_amt = $this->reporter->fixcolumn([$data[$i]['amt']],'25',0);
        $arr_notes = $this->reporter->fixcolumn([$data[$i]['notes']],'25',0);
        $arr_ref = $this->reporter->fixcolumn([$data[$i]['ref']],'25',0);

        $maxrow = $this->othersClass->getmaxcolumn([$arr_itemname, $arr_amt, $arr_notes, $arr_ref]);

        for($r = 0; $r < $maxrow; $r++) {
          PDF::SetFont($font, '', $fontsize);
          PDF::MultiCell(190, 20, (isset($arr_itemname[$r]) ? $arr_itemname[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(190, 20, (isset($arr_amt[$r]) ? $arr_amt[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(190, 20, (isset($arr_notes[$r]) ? $arr_notes[$r] : ''), '', 'L', 0, 0, '', '', true, 0, true, false);
          PDF::MultiCell(190, 20, (isset($arr_ref[$r]) ? $arr_ref[$r] : ''), '', 'L', 0, 1, '', '', true, 0, false, false);
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
      PDF::MultiCell(40, 0, "NOTE : ", '', 'L', false,0);
      PDF::SetFont($font, '', 11);
      PDF::MultiCell(720, 0, $data[0]['rem'], '', 'L', false);
      
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

    public function default_header($config, $data) {
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
      $str .= $this->reporter->col('RETURN OF ITEMS', '600', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
      $str .= $this->reporter->col('REFERENCE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '30px', '8px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      return $str;;
    }

    public function rpt_HR_layout($config, $data)
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
        $str .= $this->reporter->col($data[$i]['itemname'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col($data[$i]['amt'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col($data[$i]['notes'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->col($data[$i]['ref'], '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
        $str .= $this->reporter->endrow();

        if($this->reporter->linecounter==$page){
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_header($config,$data);
          $str .= $this->reporter->begintable('800');
          // $str .= $this->reporter->printline();
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
