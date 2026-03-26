<?php

namespace App\Http\Classes\modules\modulereport\labsolcebu;

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

class jc
{
  private $modulename = "Job Completion";
  public $tablenum = 'cntnum';
  public $head = 'jchead';
  public $hhead = 'hjchead';
  public $stock = 'jcstock';
  public $hstock = 'hjcstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';

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
    $fields = ['radioprint','prepared','approved','print'];
    $col1 = $this->fieldClass->create($fields);

    return array('col1'=>$col1);
  }
  
  public function reportparamsdata(){
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

  public function reportplotting($params, $data) {
    return $this->vitaline_report($params, $data);
  }

  public function vitaline_report($params,$data){
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);
    
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
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($this->coreFunctions->getfieldvalue("center","name","code=?",[$params['params']['center']]),'600',null,false, $border,'','L', $font,'16','B','','');
      $str .= $this->reporter->col('Purchase Order','200',null,false, $border,'','L', $font,'18','B','','');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($this->companysetup->getaddress($params['params']),'300',null,false, $border,'','L', $font,'14','','','');
          $str .= $this->reporter->col('','300',null,false, $border,'','L', $font,'12','B','','');
          $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','B','','');
          $str .= $this->reporter->col('','100',null,false, $border,'','L', $font,'12','B','','');
        $str .= $this->reporter->endrow();    
      $str .= $this->reporter->endtable();
  
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','600',null,false, $border,'','L', $font,'14','','','');
          $str .= $this->reporter->col('Date','100',null,false, $border,'LTRB','C', $font,'12','B','','4px');
          $str .= $this->reporter->col('P.O No.','100',null,false, $border,'LTRB','C', $font,'12','B','','4px');
        $str .= $this->reporter->endrow();    
  
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','500',null,false, $border,'','L', $font,'14','','','');
          $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'150',null,false, $border,'LTRB','C', $font,'12','B','','4px');
          $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'150',null,false, $border,'LTRB','C', $font,'12','B','','4px');
        $str .= $this->reporter->endrow();    
  
      $str .= $this->reporter->endtable();
    $str .= '<br>';
  
    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Vendor','400',null,false, $border,'TRBL','C', $font,'14','','','');
        $str .= $this->reporter->col('','100',null,false, $border,'','C', $font,'14','','','');
        $str .= $this->reporter->col('Ship To','400',null,false, $border,'TRBL','C', $font,'14','','','');
      $str .= $this->reporter->endrow();    
  
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'400',null,false, $border,'RL','L', $font,'14','','','10px');
        $str .= $this->reporter->col('','100',null,false, $border,'','C', $font,'14','','','');
        $str .= $this->reporter->col((isset($data[0]['shipto'])? $data[0]['shipto']:''),'400',null,false, $border,'RL','L', $font,'14','','','10px');
      $str .= $this->reporter->endrow();
  
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','400',null,false, $border,'RL','L', $font,'14','','','10px');
        $str .= $this->reporter->col('','100',null,false, $border,'','C', $font,'14','','','');
        $str .= $this->reporter->col('','400',null,false, $border,'RL','L', $font,'14','','','10px');
      $str .= $this->reporter->endrow();
  
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','400',null,false, $border,'RBL','L', $font,'14','','','10px');
        $str .= $this->reporter->col('','100',null,false, $border,'','C', $font,'14','','','');
        $str .= $this->reporter->col('','400',null,false, $border,'RBL','L', $font,'14','','','10px');
      $str .= $this->reporter->endrow();
  
    $str .= $this->reporter->endtable();
    $str .= '<br>';
    // terms
  
    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','600',null,false, $border,'','C', $font,'14','','','5px');
        $str .= $this->reporter->col('Terms','200',null,false, $border,'TRBL','C', $font,'14','','','5px');
      $str .= $this->reporter->endrow();    
  
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','600',null,false, $border,'','C', $font,'14','','','5px');
        $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'200',null,false, $border,'TRL','C', $font,'14','','','5px');
      $str .= $this->reporter->endrow();
  
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','600',null,false, $border,'','C', $font,'14','','','5px');
        $str .= $this->reporter->col('','200',null,false, $border,'RBL','C', $font,'14','','','5px');
      $str .= $this->reporter->endrow();    
      
    $str .= $this->reporter->endtable();  
  
    // stock
    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Item','150',null,false, $border,'TRBL','C', $font,'14','','','2px');
        $str .= $this->reporter->col('Description','250',null,false, $border,'TRBL','C', $font,'14','','','2px');
        $str .= $this->reporter->col('Qty','75',null,false, $border,'TRBL','C', $font,'14','','','2px');
        $str .= $this->reporter->col('Uom','75',null,false, $border,'TRBL','C', $font,'14','','','2px');
        $str .= $this->reporter->col('Rate','100',null,false, $border,'TRBL','C', $font,'14','','','2px');
        $str .= $this->reporter->col('Amount','100',null,false, $border,'TRBL','C', $font,'14','','','2px');
      $str .= $this->reporter->endrow();
   
    $totalext=0;
    for($i=0;$i<count($data);$i++){
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['barcode'],'150',null,false, $border,'RL','L', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col($data[$i]['itemname'],'250',null,false, $border,'RL','L', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col(number_format($data[$i]['qty'],$decimal),'75',null,false, $border,'RL','L', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col($data[$i]['uom'],'75',null,false, $border,'RL','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col(number_format($data[$i]['netamt'],$decimal),'100',null,false, $border,'RL','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col(number_format($data[$i]['ext'],$decimal),'100',null,false, $border,'RL','C', $font, $fontsize,'','','2px');
      $totalext=$totalext+$data[$i]['ext'];
      $str .= $this->reporter->endrow();
    } 
  
    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','150',null,false, $border,'RBL','C', $font, $fontsize,'','','75px');
      $str .= $this->reporter->col('','250',null,false, $border,'RBL','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col('','75',null,false, $border,'RBL','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col('','75',null,false, $border,'RBL','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col('','100',null,false, $border,'RBL','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col('','100',null,false, $border,'RBL','C', $font, $fontsize,'','','2px');
    $str .= $this->reporter->endrow();
  
    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','150',null,false, $border,'','C', $font, $fontsize,'','','10px');
      $str .= $this->reporter->col('','250',null,false, $border,'','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col('','75',null,false, $border,'','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col('','75',null,false, $border,'','C', $font, $fontsize,'','','2px');
      $str .= $this->reporter->col('Total : ','100',null,false, $border,'TBL','L', $font,'12','B','','2px');
      $str .= $this->reporter->col('PHP '.number_format($totalext,$decimal),'100',null,false, $border,'TBR','R', $font,'12','B','','2px');
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
  
    $str .= $this->reporter->endreport();
  
    return $str;
  }



}
