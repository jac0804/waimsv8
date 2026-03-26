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

class bq
{

  private $modulename = "Bill of Quantity";
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
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable(
      "select 
      'default' as print,
      '' as prepared,
      '' as approved,
      '' as received
      ");
  }

  public function report_default_query($trno){
    $query = "select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model,concat(project.code,'-',project.name) as projectname
      from sohead as head left join sostock as stock on stock.trno=head.trno 
      left join item on item.itemid=stock.itemid
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join projectmasterfile as project on project.line = head.projectid
      where head.trno='$trno'
      union all
      select head.rtype,head.rdate,cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
      date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
      item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
      stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
      item.sizeid,m.model_name as model,concat(project.code,'-',project.name) as projectname
      from hsohead as head 
      left join hsostock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid 
      left join model_masterfile as m on m.model_id = item.model
      left join client on client.client=head.wh
      left join client as cust on cust.client = head.client
      left join projectmasterfile as project on project.line = head.projectid
      where head.doc='BQ' and head.trno='$trno' order by line";
      
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }//end fn  

  public function reportplotting($params,$data){
    $mdc = URL::to($this->companysetup->getlogopath($params).'mdc.jpg');
    $tuv = URL::to($this->companysetup->getlogopath($params).'tuv.jpg');
  
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',$params['params']);
  
    $center = $params['params']['center'];
    $username = $params['params']['user'];
  
    $str = '';
    $count=35;
    $page=35;
    $str .= $this->reporter->beginreport();
  
    if($companyid == 8) {
      $str .= "<div style='position: relative;'>";
      $str .= $this->reporter->begintable('800');
      $qry = "select name,address,tel from center where code = '".$center."'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name),null,null,false,'1px solid ','','c','Century Gothic','14','B','','').'<br />';
      $str .= $this->reporter->endrow();
  
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address),null,null,false,'1px solid ','','c','Century Gothic','13','B','','').'<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel),null,null,false,'1px solid ','','c','Century Gothic','13','B','','').'<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      
      $str .= "<div style='position:absolute; top: 60px;'>";
      $str .= $this->reporter->col('<img src ="'.$mdc.'" alt="MDC" width="140px" height ="70px">','10',null,false,'2px solid ','','R','Century Gothic','15','B','','1px');
      $str .= $this->reporter->col('<img src ="'.$tuv.'" alt="TUV" width="140px" height ="70px" style="margin-left: 510px;">','10',null,false,'2px solid ','','R','Century Gothic','15','B','','1px');
      $str .= "</div>";
  
      $str .= "</div>";
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->letterhead($center,$username);
      $str .= $this->reporter->endtable();
    }
  
    $str .= '<br><br>';
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('BILL OF QUANTITY','600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
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
  
    $str .= "</br>";
    // $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('QTY','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('UNIT','50px',null,false,'1px solid ','B','C','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('D E S C R I P T I O N','500px',null,false,'1px solid ','B','C','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('','125px',null,false,'1px solid ','B','C','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('AMOUNT','50px',null,false,'1px solid ','B','R','Century Gothic','12','B','','2px');
    $str .= $this->reporter->col('TOTAL','125px',null,false,'1px solid ','B','R','Century Gothic','12','B','','2px');
  
    $totalext=0;
    for($i=0;$i<count($data);$i++){
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty', $params['params'])),'50px',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
      $str .= $this->reporter->col($data[$i]['uom'],'50px',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
      $str .= $this->reporter->col($data[$i]['itemname'],'500px',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
      $str .= $this->reporter->col('','125px',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
      $str .= $this->reporter->col(number_format($data[$i]['gross'],2),'50px',null,false,'1px solid ','','R','Century Gothic','11','','','');
      $str .= $this->reporter->col(number_format($data[$i]['ext'],2),'125px',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
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
}
