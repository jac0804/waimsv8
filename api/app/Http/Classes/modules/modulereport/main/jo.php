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

class jo
{

  private $modulename = "Job Order";
  public $tablenum = 'transnum';
  public $head = 'johead';
  public $hhead = 'hjohead';
  public $stock = 'jostock';
  public $hstock = 'hjostock';

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
    $companyid = $config['params']['companyid'];
  
    $fields = ['radioprint','prepared','approved','received','print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1'=>$col1);
  }
  
  public function reportparamsdata($config){
    $companyid = $config['params']['companyid'];
    return $this->coreFunctions->opentable(
      "select 
      'default' as print,
      '' as prepared,
      '' as approved,
      '' as received
      ");
  }
  
  public function report_default_query($trno){
  
      $query = "select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, 
          head.terms,head.rem, item.barcode,
          item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
          from ".$this->head." as head left join ".$this->stock." as stock on stock.trno=head.trno 
          left join client on client.client=head.client
          left join item on item.itemid = stock.itemid
          left join model_masterfile as m on m.model_id = item.model
          where head.doc='JO' and head.trno='$trno'
          union all
          select date(head.dateid) as dateid, head.docno, client.client, client.clientname, 
          head.address, head.terms,head.rem, item.barcode,
          item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext,m.model_name as model,item.sizeid
          from ".$this->hhead." as head left join ".$this->hstock." as stock on stock.trno=head.trno 
          left join client on client.client=head.client
          left join item on item.itemid = stock.itemid
          left join model_masterfile as m on m.model_id = item.model
          where head.doc='JO' and head.trno='$trno'";
  
      $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
      return $result;
  }//end fn

  public function reportplotting($params,$data){
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',$params['params']);
  
    $center = $params['params']['center'];
    $username = $params['params']['user']; 
  
  
    $str = '';
    $count=35;
    $page=35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
  
    $str .= $this->reporter->beginreport();
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center,$username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col($this->modulename,'580',null,false, $border,'','L', $font,'18','B','','');
    $str .= $this->reporter->col('DOCUMENT # :','120',null,false, $border,'','L', $font,'13','B','','');
    $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false, $border,'B','L', $font,'13','','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER : ','80',null,false, $border,'','L', $font,'12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false, $border,'B','L', $font,'12','','30px','4px');
    $str .= $this->reporter->col('DATE : ','50',null,false, $border,'','L', $font,'12','B','','');
    $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'150',null,false, $border,'B','R', $font,'12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ','80',null,false, $border,'','L', $font,'12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'520',null,false, $border,'B','L', $font,'12','','30px','4px');
    $str .= $this->reporter->col('TERMS : ','60',null,false, $border,'','L', $font,'12','B','','');
    $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'140',null,false, $border,'B','R', $font,'12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null,null,false, $border,'','R', $font, $fontsize,'','','4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
  
    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE','50',null,false, $border,'B','C', $font,'12','B','30px','8px');
    $str .= $this->reporter->col('QTY','50',null,false, $border,'B','C', $font,'12','B','30px','8px');
    $str .= $this->reporter->col('UNIT','50',null,false, $border,'B','C', $font,'12','B','30px','8px');
    $str .= $this->reporter->col('D E S C R I P T I O N','475',null,false, $border,'B','C', $font,'12','B','30px','8px');
    $str .= $this->reporter->col('UNIT PRICE','100',null,false, $border,'B','C', $font,'12','B','30px','8px');
    $str .= $this->reporter->col('(+/-) %','75',null,false, $border,'B','C', $font,'12','B','30px','8px');
    $str .= $this->reporter->col('TOTAL','100',null,false, $border,'B','C', $font,'12','B','30px','8px');
  
    $totalext=0;
    for($i=0;$i<count($data);$i++){
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'],'50',null,false, $border,'','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty',$params['params'])),'50',null,false, $border,'','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col($data[$i]['uom'],'50',null,false, $border,'','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col($data[$i]['itemname'],'475',null,false, $border,'','L', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'],$this->companysetup->getdecimal('price',$params['params'])),'100',null,false, $border,'','R', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col($data[$i]['disc'],'75',null,false, $border,'','C', $font, $fontsize,'','','');
      $str .= $this->reporter->col(number_format($data[$i]['ext'],$decimal),'100',null,false, $border,'','R', $font, $fontsize,'','','2px');
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
        $str .= $this->reporter->col($this->modulename,'580',null,false, $border,'','L', $font,'18','B','','');
        $str .= $this->reporter->col('DOCUMENT # :','120',null,false, $border,'','L', $font,'13','B','','');
        $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false, $border,'B','L', $font,'13','','','').'<br />';
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('SUPPLIER : ','80',null,false, $border,'','L', $font,'12','B','30px','4px');
        $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false, $border,'B','L', $font,'12','','30px','4px');
        $str .= $this->reporter->col('DATE : ','50',null,false, $border,'','L', $font,'12','B','','');
        $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'150',null,false, $border,'B','R', $font,'12','','','');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ADDRESS : ','80',null,false, $border,'','L', $font,'12','B','30px','4px');
        $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'520',null,false, $border,'B','L', $font,'12','','30px','4px');
        $str .= $this->reporter->col('TERMS : ','60',null,false, $border,'','L', $font,'12','B','','');
        $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'140',null,false, $border,'B','R', $font,'12','','','');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
  
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow(null,null,false, $border,'','R', $font, $fontsize,'','','4px');
        $str .= $this->reporter->pagenumber('Page');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
  
        $str .= $this->reporter->printline();
        //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
        $str .= $this->reporter->col('CODE','50',null,false, $border,'B','C', $font,'12','B','30px','8px');
        $str .= $this->reporter->col('QTY','50',null,false, $border,'B','C', $font,'12','B','30px','8px');
        $str .= $this->reporter->col('UNIT','50',null,false, $border,'B','C', $font,'12','B','30px','8px');
        $str .= $this->reporter->col('D E S C R I P T I O N','475',null,false, $border,'B','C', $font,'12','B','30px','8px');
        $str .= $this->reporter->col('UNIT PRICE','100',null,false, $border,'B','C', $font,'12','B','30px','8px');
        $str .= $this->reporter->col('(+/-) %','75',null,false, $border,'B','C', $font,'12','B','30px','8px');
        $str .= $this->reporter->col('TOTAL','100',null,false, $border,'B','C', $font,'12','B','30px','8px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page=$page + $count;
      }
    }   
  
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ITEM(S)','50',null,false,'1px dotted ','T','C', $font, $fontsize,'B','','');
    $str .= $this->reporter->col($i,'50',null,false,'1px dotted ','T','C', $font, $fontsize,'B','','');
    $str .= $this->reporter->col('','50',null,false,'1px dotted ','T','C', $font, $fontsize,'B','','');
    $str .= $this->reporter->col('','440',null,false,'1px dotted ','T','C', $font, $fontsize,'B','','');
    $str .= $this->reporter->col('','100',null,false,'1px dotted ','T','C', $font, $fontsize,'B','','');
    $str .= $this->reporter->col('GRAND TOTAL :','110',null,false,'1px dotted ','T','R', $font, $fontsize,'B','','');
    $str .= $this->reporter->col(number_format($totalext,$decimal),'100',null,false,'1px dotted ','T','R', $font, $fontsize,'B','','');
    $str .= $this->reporter->endrow();
  
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTE : ','60',null,false, $border,'','L', $font,'12','B','','');
    $str .= $this->reporter->col($data[0]['rem'],'600',null,false, $border,'','L', $font,'12','','','');
    $str .= $this->reporter->col('','140',null,false, $border,'','L', $font,'12','B','','');
  
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ','266',null,false, $border,'','L', $font,'12','','','');
    $str .= $this->reporter->col('Approved By :','266',null,false, $border,'','C', $font,'12','','','');
    $str .= $this->reporter->col('Received By :','266',null,false, $border,'','R', $font,'12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'],'266',null,false, $border,'','L', $font,'12','B','','');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'],'266',null,false, $border,'','C', $font,'12','B','','');
    $str .= $this->reporter->col($params['params']['dataparams']['received'],'266',null,false, $border,'','R', $font,'12','B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
  
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
  
    return $str;
  }

  public function pagenumber($txt='', $font='', $fontSize=''){
    return '<style> body { counter-reset: page; border-collapse:collapse;clear: both; } 
      #pagenumber::before { counter-increment: page; content: counter(page); left: 0; top: 100%; white-space: nowrap; 
      z-index: 20; -moz-border-radius: 5px; -moz-box-shadow: 0px 0px 4px #222;  
      background-image: -moz-linear-gradient(top, #eeeeee, #cccccc} </style>
      <td style="font-family: '.$font.';
      font-size:'.$fontSize.'px;
      text-align:center; border: 1px solid; border-bottom: none;">'.$txt.' <span id="pagenumber"></span>
      </td>';
  }

}
