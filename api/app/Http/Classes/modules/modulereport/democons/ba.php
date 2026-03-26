<?php

namespace App\Http\Classes\modules\modulereport\democons;

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

class ba
{

  private $modulename = "Bill of Accomplishment";
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
  
  public function createreportfilter(){
    $fields = ['radioprint','prepared','approved','received','print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'radioprint.options', [
      ['label' => 'PDF', 'value' => 'PDFM', 'color' => 'red']
    ]);
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    $user = $config['params']['user'];
    $qry="select name from useraccess where username='$user'";
    $name = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    if((isset($name[0]['name']) ? $name[0]['name'] : '')!=''){
      $user = $name[0]['name'];
    }
    return $this->coreFunctions->opentable(
      "select 
      'PDFM' as print,
      '$user' as prepared,
      '' as approved,
      '' as received
      ");
  }

  public function report_default_query($config){
    $trno = $config['params']['dataid'];
    $query = "select head.projectid,proj.code as projcode,proj.name as projname,head.rem,
                  sp.subproject,st.stage,a.substage,sa.subactivity,sa.description,
                  stock.rrqty,psa.uom,stock.rrcost,stock.ext
              from bahead as head
              left join bastock as stock on stock.trno=head.trno
              left join projectmasterfile as proj on proj.line=head.projectid
              left join subproject as sp on sp.line=head.subproject and sp.projectid=proj.line
              left join stagesmasterfile as st on st.line=stock.stage
              left join substages as a on a.line=stock.activity
              left join subactivity as sa on sa.line=stock.subactivity
              left join psubactivity as psa on psa.line=stock.subactivity
              where head.trno=$trno and psa.uom <> '' and psa.rrqty <> '' and psa.qty <> ''
              union all
              select head.projectid,proj.code as projcode,proj.name as projname,head.rem,
                  sp.subproject,st.stage,a.substage,sa.subactivity,sa.description,
                  stock.rrqty,psa.uom,stock.rrcost,stock.ext
              from hbahead as head
              left join hbastock as stock on stock.trno=head.trno
              left join projectmasterfile as proj on proj.line=head.projectid
              left join subproject as sp on sp.line=head.subproject and sp.projectid=proj.line
              left join stagesmasterfile as st on st.line=stock.stage
              left join substages as a on a.line=stock.activity
              left join subactivity as sa on sa.line=stock.subactivity
              left join psubactivity as psa on psa.line=stock.subactivity
              where head.trno=$trno and psa.uom <> '' and psa.rrqty <> '' and psa.qty <> ''
              order by subactivity";
              
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }//end fn  

  public function reportplotting($params,$data){
    return $this->default_BA_PDF($params, $data);
  }

  public function default_BA_LAYOUT($params,$data){
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',$params['params']);
  
    $center = $params['params']['center'];
    $username = $params['params']['user'];
  
    $str = '';
    $count=35;
    $page=35;
    $str .= $this->reporter->beginreport();
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center,$username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('BILL OF ACCOMPLISHMENT','600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
    $str .= $this->reporter->col('DOCUMENT # :','100',null,false,'1px solid ','','L','Century Gothic','13','B','','');
    $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','B','L','Century Gothic','13','','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->col('DATE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PROJECT : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['projectname'])? $data[0]['projectname']:''),'500',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->col('TERMS : ','50',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'150',null,false,'1px solid ','B','R','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null,null,false,'1px solid ','','R','Century Gothic','10','','','4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    // $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('UNIT','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('D E S C R I P T I O N','500px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
  
    $totalext=0;
    for($i=0;$i<count($data);$i++){
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty', $params['params'])),'50px',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
      $str .= $this->reporter->col($data[$i]['uom'],'50px',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
      $str .= $this->reporter->col($data[$i]['itemname'],'500px',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
      $str .= $this->reporter->col('','125px',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
      $str .= $this->reporter->col('','50px',null,false,'1px solid ','','C','Century Gothic','11','','','');
      $str .= $this->reporter->col('','125px',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
      $totalext=$totalext+$data[$i]['ext'];  
  
      if($this->reporter->linecounter==$page){
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
  
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->letterhead($center, $username);
        $str .= $this->reporter->endtable();
        $str .= '<br><br>';
  
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col($this->modulename,'600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
        $str .= $this->reporter->col('DOCUMENT # :','100',null,false,'1px solid ','','L','Century Gothic','13','B','','');
        $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','B','L','Century Gothic','13','','','').'<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
        $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
        $str .= $this->reporter->col('DATE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
        $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('PROJECT : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
        $str .= $this->reporter->col((isset($data[0]['projectname'])? $data[0]['projectname']:''),'500',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
        $str .= $this->reporter->col('TERMS : ','50',null,false,'1px solid ','','L','Century Gothic','12','B','','');
        $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'150',null,false,'1px solid ','B','R','Century Gothic','12','','','');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
  
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null,null,false,'1px solid ','','R','Century Gothic','10','','','4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
  
        $str .= $this->reporter->printline();
  
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('QTY','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
        $str .= $this->reporter->col('UNIT','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
        $str .= $this->reporter->col('D E S C R P T I O N','500px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
        $str .= $this->reporter->col('','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
        $str .= $this->reporter->col('','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
        $str .= $this->reporter->col('','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page=$page + $count;
      }
    }   
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('','50px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
    $str .= $this->reporter->col('','50px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
    $str .= $this->reporter->col('','500px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
    $str .= $this->reporter->col('','125px',null,false,'1px dotted ','T','C','Century Gothic','12','B','','');
    $str .= $this->reporter->col('GRAND TOTAL :','50px',null,false,'1px dotted ','T','R','Century Gothic','12','B','','');
    $str .= $this->reporter->col(number_format($totalext,$decimal),'125px',null,false,'1px dotted ','T','R','Century Gothic','12','B','','');
    $str .= $this->reporter->endrow();
  
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col($data[0]['rem'],'600',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->col('','160',null,false,'1px solid ','','L','Century Gothic','12','B','','');
  
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->col('Approved By :','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->col('Received By :','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']["prepared"],'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col($params['params']['dataparams']["approved"],'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col($params['params']['dataparams']["received"],'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    $str .= $this->reporter->endtable();
  
  
    $str .= $this->reporter->endreport();
  
    return $str;
  }

  public function default_BA_header_PDF($params, $data)
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
    PDF::AddPage('l', [800, 1000]);
    PDF::SetMargins(40, 40);

    // SetFont(family, style, size)
    // MultiCell(width, height, txt, border, align, x, y)
    // write2DBarcode(code, type, x, y, width, height, style, align)

    PDF::MultiCell(0, 0, "\n");
    
  }

  public function default_BA_PDF($params, $data)
  {
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
    PDF::AddPage('l', [800, 1000]);
    PDF::SetMargins(40, 40);


    PDF::MultiCell(0, 0, "\n\n\n");
    // $this->default_BA_header_PDF($params, $data);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(140, 30, 'Project: ', '', 'L', false,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(820, 30, $data[0]['projname'].', '.$data[0]['projcode'], '', 'L', false);

    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(140, 30, 'Work Accomplishment: ', '', 'L', false,0);
    PDF::SetFont($font, '', $fontsize);
    PDF::MultiCell(820, 30, $data[0]['rem'], '', 'L', false);

    PDF::MultiCell(0, 0, "\n");

    $left = '5';
    $top = '';
    $right = '';
    $bottom = '';
    
    PDF::setCellPadding( $left, $top, $right, $bottom);
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(450, 0, 'Item No./Description', 'TLRB', 'C', false,0);
    PDF::MultiCell(100, 0, 'Qty', 'TLRB', 'C', false,0);
    PDF::MultiCell(100, 0, 'Unit', 'TLRB', 'C', false,0);
    PDF::MultiCell(130, 0, 'Unit Price', 'TLRB', 'C', false,0);
    PDF::MultiCell(130, 0, 'Total Price', 'TLRB', 'C', false);


    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(450, 0, 'A. '.$data[0]['subproject'], 'TLRB', 'L', false,0);
    PDF::MultiCell(100, 0, '', 'TLRB', 'C', false,0);
    PDF::MultiCell(100, 0, '', 'TLRB', 'C', false,0);
    PDF::MultiCell(130, 0, '', 'TLRB', 'C', false,0);
    PDF::MultiCell(130, 0, '', 'TLRB', 'C', false);

    $subactivity = '';
    $y = 0;
    $o = 0;
    $r = 1;
    $s = 1;
    $totalprice =0;
    $gtotalprice=0;

    for ($i=0; $i < count($data); $i++) { 
      if ($data[$i]['subactivity'] == $subactivity) {
        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 0, '', 'TLB', 'C', false,0);
        PDF::MultiCell(420, 0, 'A. '.'1.'.$y.'.'.$o.' '.$data[$i]['description'], 'TRB', 'L', false,0);
        PDF::MultiCell(100, 0, number_format($data[$i]['rrqty'],2), 'TLRB', 'R', false,0);
        PDF::MultiCell(100, 0, $data[$i]['uom'], 'TLRB', 'C', false,0);
        PDF::MultiCell(130, 0, number_format($data[$i]['rrcost'],2), 'TLRB', 'R', false,0);
        PDF::MultiCell(130, 0, number_format($data[$i]['ext'],2), 'TLRB', 'R', false);

        $o = $o+1;
      } else {
        PDF::SetFont($fontbold, '', $fontsize);
        PDF::MultiCell(450, 0, 'A.'.'1.'.$r.' '.$data[$i]['subactivity'], 'TLRB', 'L', false,0);
        PDF::MultiCell(100, 0, '', 'TLRB', 'C', false,0);
        PDF::MultiCell(100, 0, '', 'TLRB', 'C', false,0);
        PDF::MultiCell(130, 0, '', 'TLRB', 'C', false,0);
        PDF::MultiCell(130, 0, '', 'TLRB', 'C', false);

        PDF::SetFont($font, '', $fontsize);
        PDF::MultiCell(30, 0, '', 'TLB', 'C', false,0);
        PDF::MultiCell(420, 0, 'A. '.'1.'.$r.'.'.$s.' '.$data[$i]['description'], 'TRB', 'L', false,0);
        PDF::MultiCell(100, 0, number_format($data[$i]['rrqty'],2), 'TLRB', 'R', false,0);
        PDF::MultiCell(100, 0, $data[$i]['uom'], 'TLRB', 'C', false,0);
        PDF::MultiCell(130, 0, number_format($data[$i]['rrcost'],2), 'TLRB', 'R', false,0);
        PDF::MultiCell(130, 0, number_format($data[$i]['ext'],2), 'TLRB', 'R', false);
        $subactivity = $data[$i]['subactivity'];
        $y = $r;
        $r= $r+1;
        $o= $s+1;
      }

      $totalprice += $data[$i]['ext'];
      
    }
    PDF::SetFont($fontbold, '', $fontsize);
    PDF::MultiCell(30, 0, '', 'TLB', 'C', false,0);
    PDF::MultiCell(420, 0, '', 'TB', 'L', false,0);
    PDF::MultiCell(100, 0, '', 'TB', 'R', false,0);
    PDF::MultiCell(100, 0, '', 'TB', 'C', false,0);
    PDF::MultiCell(130, 0, 'Total Amount', 'TRB', 'R', false,0);
    PDF::MultiCell(130, 0, number_format($totalprice,2), 'TLRB', 'R', false);

    return PDF::Output($this->modulename . '.pdf', 'S');
  }
}
