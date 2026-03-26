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

class qs
{
  public $tablenum = 'transnum';
  public $head = 'qshead';
  public $hhead = 'hqshead';
  public $stock = 'qsstock';
  public $hstock = 'hqsstock';
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

public function createreportfilter($config){
  $companyid = $config['params']['companyid'];

  switch ($companyid) {
    case '10':
      $fields = ['radioprint','radioquotation','print'];
      break;
    
    default:
      $fields = ['radioprint','prepared','approved','received','print'];
      break;
  }
     
     $col1 = $this->fieldClass->create($fields);
     return array('col1'=>$col1);
}

public function reportparamsdata($config){
  $companyid = $config['params']['companyid'];

  switch ($companyid) {
    case '10':
      return $this->coreFunctions->opentable(
        "select 
        'default' as print,
        'quoteprint' as radioquotation
        ");
      break;
    
    default:
    return $this->coreFunctions->opentable(
      "select 
      'default' as print,
      '' as prepared,
      '' as approved,
      '' as received
      ");
      break;
  }
    
}

public function report_default_query($trno){

  $query = "select cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  date(head.dateid) as dateid,head.terms, head.rem,head.agent,head.wh,
  item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  item.sizeid,m.model_name as model
  from ".$this->head." as head left join ".$this->stock." as stock on stock.trno=head.trno 
  left join item on item.itemid=stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  left join client on client.client=head.wh
  left join client as cust on cust.client = head.client
  where head.doc='QS' and head.trno='$trno'
  union all
  select cust.tel2,cust.email,head.docno,head.trno, head.clientname, head.address, 
  date(head.dateid) as dateid, head.terms, head.rem,head.agent,head.wh,
  item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  item.sizeid,m.model_name as model
  from ".$this->hhead." as head 
  left join ".$this->hstock." as stock on stock.trno=head.trno
  left join item on item.itemid=stock.itemid 
  left join model_masterfile as m on m.model_id = item.model
  left join client on client.client=head.wh
  left join client as cust on cust.client = head.client
  where head.doc='QS' and head.trno='$trno' order by line";
    
  $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  return $result;
}//end fn 

public function default_header($params, $data) {
  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = "";
  $font = "Century Gothic";
  $fontsize = "11";
  $border = "1px solid ";

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->letterhead($center,$username);
  $str .= $this->reporter->endtable();
  $str .= '<br><br>';

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  $str .= $this->reporter->col('QUOTATION','600',null,false, $border,'','L',$font,'18','B','','');
  $str .= $this->reporter->col('DOCUMENT # :','100',null,false, $border,'','L',$font,'13','B','','');
  $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false, $border,'B','L',$font,'13','','','').'<br />';
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('CUSTOMER : ','80',null,false, $border,'','L',$font, $fontsize,'B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false, $border,'B','L',$font, $fontsize,'','30px','4px');
  $str .= $this->reporter->col('DATE : ','40',null,false, $border,'','L',$font, $fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false, $border,'B','R',$font, $fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('ADDRESS : ','80',null,false, $border,'','L',$font, $fontsize,'B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'500',null,false, $border,'B','L',$font, $fontsize,'','30px','4px');
  $str .= $this->reporter->col('TERMS : ','50',null,false, $border,'','L',$font, $fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'150',null,false, $border,'B','R',$font, $fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow(null,null,false, $border,'','R',$font,'10','','','4px');
  $str .= $this->reporter->pagenumber('Page');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  // $str .= $this->reporter->printline();
  //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  $str .= $this->reporter->col('QTY','50px',null,false, $border,'B','C',$font, $fontsize,'B','30px','8px');
  $str .= $this->reporter->col('UNIT','50px',null,false, $border,'B','C',$font, $fontsize,'B','30px','8px');
  $str .= $this->reporter->col('D E S C R I P T I O N','500px',null,false, $border,'B','C',$font, $fontsize,'B','30px','8px');
  $str .= $this->reporter->col('UNIT PRICE','125px',null,false, $border,'B','C',$font, $fontsize,'B','30px','8px');
  $str .= $this->reporter->col('(+/-) %','50px',null,false, $border,'B','C',$font, $fontsize,'B','30px','8px');
  $str .= $this->reporter->col('TOTAL','125px',null,false, $border,'B','C',$font, $fontsize,'B','30px','8px');

  return $str;
}

public function reportplotting($params,$data){
  $companyid = $params['params']['companyid'];
  $decimal = $this->companysetup->getdecimal('currency', $params['params']);

  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = '';
  $font = "Century Gothic";
  $fontsize = "11";
  $border = "1px solid ";
  $count=35;
  $page=35;
  $str .= $this->reporter->beginreport();
  $str .= $this->default_header($params, $data);

  $totalext=0;
  for($i=0;$i<count($data);$i++){
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty',$params['params'])),'50px',null,false, $border,'','C',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['uom'],'50px',null,false, $border,'','C',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['itemname'],'500px',null,false, $border,'','L',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col(number_format($data[$i]['gross'],$decimal),'125px',null,false, $border,'','R',$font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['disc'],'50px',null,false, $border,'','C',$font, $fontsize,'','','');
    $str .= $this->reporter->col(number_format($data[$i]['ext'],$decimal),'125px',null,false, $border,'','R',$font, $fontsize,'','','2px');
    $totalext=$totalext+$data[$i]['ext'];  

    if($this->reporter->linecounter==$page){
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->default_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page=$page + $count;
    }
  }   
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','50px',null,false,'1px dotted ','T','C',$font, $fontsize,'B','','');
  $str .= $this->reporter->col('','50px',null,false,'1px dotted ','T','C',$font, $fontsize,'B','','');
  $str .= $this->reporter->col('','500px',null,false,'1px dotted ','T','C',$font, $fontsize,'B','','');
  $str .= $this->reporter->col('','125px',null,false,'1px dotted ','T','C',$font, $fontsize,'B','','');
  $str .= $this->reporter->col('GRAND TOTAL :','50px',null,false,'1px dotted ','T','R',$font, $fontsize,'B','','');
  $str .= $this->reporter->col(number_format($totalext,$decimal),'125px',null,false,'1px dotted ','T','R',$font, $fontsize,'B','','');
  $str .= $this->reporter->endrow();

  $str .= $this->reporter->endtable();
  $str .= $this->reporter->printline();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('NOTE : ','40',null,false, $border,'','L',$font, $fontsize,'B','','');
  $str .= $this->reporter->col($data[0]['rem'],'600',null,false, $border,'','L',$font, $fontsize,'','','');
  $str .= $this->reporter->col('','160',null,false, $border,'','L',$font, $fontsize,'B','','');

  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= '<br><br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Prepared By : ','266',null,false, $border,'','L',$font, $fontsize,'','','');
  $str .= $this->reporter->col('Approved By :','266',null,false, $border,'','L',$font, $fontsize,'','','');
  $str .= $this->reporter->col('Received By :','266',null,false, $border,'','L',$font, $fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($params['params']['dataparams']["prepared"],'266',null,false, $border,'','L',$font, $fontsize,'B','','');
  $str .= $this->reporter->col($params['params']['dataparams']["approved"],'266',null,false, $border,'','L',$font, $fontsize,'B','','');
  $str .= $this->reporter->col($params['params']['dataparams']["received"],'266',null,false, $border,'','L',$font, $fontsize,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->endtable();


  $str .= $this->reporter->endreport();

  return $str;
}



}
