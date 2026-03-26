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

class qt
{
  public $tablenum = 'transnum';
  public $head = 'qthead';
  public $hhead = 'hqthead';
  public $stock = 'qtstock';
  public $hstock = 'hqtstock';
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
  where head.doc='QT' and head.trno='$trno'
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
  where head.doc='QT' and head.trno='$trno' order by line";
    
  $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  return $result;
}//end fn 

public function report_quote_query($trno){

  $query = "select cust.tel,cust.email,head.docno,head.trno, head.clientname, head.address, 
  date(head.dateid) as dateid, head.rem,head.agent,head.wh,
  item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  item.sizeid,m.model_name as model, brands.brand_desc,iteminfo.itemdescription, iteminfo.accessories,
  agent.clientname as agentname,agent.tel as agtel,
  infotab.inspo,infotab.deldate, infotab.ispartial,infotab.instructions, infotab.period,
  infotab.isvalid,infotab.terms,infotab.termsdetails,infotab.proformainvoice,infotab.proformadate,
  infotab.leadfrom,infotab.leadto,infotab.leaddur,infotab.advised,infotab.tax,infotab.vattype,
  bill.addr as billaddr,bill.contact as billcontact,bill.contactno as billcontactno,
  ship.addr as shipaddr,ship.contact as shipcontact,ship.contactno as shipcontactno,
  stockinfo.rem as inforem, stockinfo.leadfrom as itemleadfrom,stockinfo.leadto as itemleadto,
  stockinfo.leaddur as itemleaddur,head.industry,cust.tin,head.yourref,cust.addr as clientaddr
  from ".$this->head." as head left join ".$this->stock." as stock on stock.trno=head.trno 
  left join item on item.itemid=stock.itemid
  left join model_masterfile as m on m.model_id = item.model
  left join client on client.client=head.wh
  left join client as cust on cust.client = head.client
  left join frontend_ebrands as brands on brands.brandid = item.brand
  left join iteminfo on iteminfo.itemid = item.itemid
  left join client as agent on agent.client= head.agent
  left join headinfotrans as infotab on infotab.trno = head.trno
  left join billingaddr as bill on bill.line = head.billid
  left join billingaddr as ship on ship.line = head.shipid
  left join stockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
  where head.doc='QT' and head.trno='$trno'
  union all
  select cust.tel,cust.email,head.docno,head.trno, head.clientname, head.address, 
  date(head.dateid) as dateid, head.rem,head.agent,head.wh,
  item.barcode, item.itemname, stock.isamt as gross, stock.amt as netamt, stock.isqty as qty,
  stock.uom, stock.disc, stock.ext, stock.line,item.brand,client.clientname as whname,
  item.sizeid,m.model_name as model, brands.brand_desc,iteminfo.itemdescription, iteminfo.accessories,
  agent.clientname as agentname,agent.tel as agtel,
  infotab.inspo,infotab.deldate, infotab.ispartial,infotab.instructions, infotab.period,
  infotab.isvalid,infotab.terms,infotab.termsdetails,infotab.proformainvoice,infotab.proformadate,
  infotab.leadfrom,infotab.leadto,infotab.leaddur,infotab.advised,infotab.tax,infotab.vattype,
  bill.addr as billaddr,bill.contact as billcontact,bill.contactno as billcontactno,
  ship.addr as shipaddr,ship.contact as shipcontact,ship.contactno as shipcontactno,
  stockinfo.rem as inforem, stockinfo.leadfrom as itemleadfrom,stockinfo.leadto as itemleadto,
  stockinfo.leaddur as itemleaddur,head.industry,cust.tin,head.yourref,cust.addr as clientaddr
  from ".$this->hhead." as head 
  left join ".$this->hstock." as stock on stock.trno=head.trno
  left join item on item.itemid=stock.itemid 
  left join model_masterfile as m on m.model_id = item.model
  left join client on client.client=head.wh
  left join client as cust on cust.client = head.client
  left join frontend_ebrands as brands on brands.brandid = item.brand
  left join iteminfo on iteminfo.itemid = item.itemid
  left join client as agent on agent.client= head.agent
  left join headinfotrans as infotab on infotab.trno = head.trno
  left join billingaddr as bill on bill.line = head.billid
  left join billingaddr as ship on ship.line = head.shipid
  left join stockinfotrans as stockinfo on stockinfo.trno = stock.trno and stockinfo.line = stock.line
  where head.doc='QT' and head.trno='$trno' order by line";
  
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

public function default_quote_header($params, $data) {
  $qslogo = URL::to('/images/afti/qslogo.png');

  $center = $params['params']['center'];
  $username = $params['params']['user'];
  
  $query ="select code,name,address,tel,tin from center where code = ".$center."";
  $result = $this->coreFunctions->opentable($query);

  $str = "";
  $font = "Arial";
  $fontsize = "12";
  $font_size = "14";
  $fontsize2 = '13';
  $border = "1px solid ";
  $border1 = "1px solid; background-color: lightgray";

  $str .= "<div style='position:absolute; top: 10px;'>";
  $str .= $this->reporter->col('<img src ="'.$qslogo.'" width="340px" height ="80px">','10',null,false,'2px solid ','','R','Century Gothic','15','B','','1px');
  $str .= "</div>";

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('QUOTATION','300',null,false, $border,'','C',$font,'18','B','','');
  // $str .= $this->reporter->col('','150',null,false, $border,'','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.'Quotation No.','100',null,false, $border1,'TRL','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.(isset($data[0]['docno'])? $data[0]['docno']:''),'200',null,false, $border,'TRL','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.'Quotation Date','100',null,false, $border1,'TRL','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.date("F d,Y", strtotime($data[0]['dateid'])),'200',null,false, $border,'TRL','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.'INQ Ref No.','100',null,false, $border1,'TRL','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.(isset($data[0]['inspo'])? $data[0]['inspo']:''),'200',null,false, $border,'TRL','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.'Payment Terms','100',null,false, $border1,'TRL','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.(isset($data[0]['terms'])? $data[0]['terms']:''),'200',null,false, $border,'TRL','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($result[0]->name,'400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('&nbsp'.'Page No.','100',null,false, $border1,'TRBL','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->pagenumber('&nbsp'.'Page','200',null,false, $border,'TRBL','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($result[0]->address,'400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($result[0]->tel,'400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('VAT REG TIN: '.$result[0]->tin,'400',null,false, $border,'','L',$font,$font_size,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','150',null,false, $border,'','L',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','390',null,false, $border,'','L',$font,$fontsize2,'B','','3px');
  $str .= $this->reporter->col('','20',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','110',null,false, $border,'','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col('','280',null,false, $border,'','L',$font,$fontsize2,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'390',null,false, $border,'TLR','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col('','20',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('Contact Name: ','110',null,false, $border,'TL','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col((isset($data[0]['billcontact'])? $data[0]['billcontact']:''),'280',null,false, $border,'TR','L',$font,$fontsize2,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('TIN: '.(isset($data[0]['tin'])? $data[0]['tin']:''),'390',null,false, $border,'LR','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col('','20',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('Phone: ','60',null,false, $border,'L','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col((isset($data[0]['billcontactno'])? $data[0]['billcontactno']:''),'330',null,false, $border,'R','L',$font,$fontsize2,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();  
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col((isset($data[0]['clientaddr'])? $data[0]['clientaddr']:''),'390',null,false, $border,'LRB','L',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('','20',null,false, $border,'','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col('Email: ','60',null,false, $border,'LB','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col('','330',null,false, $border,'BR','L',$font,$fontsize2,'','','');
  
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
 
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','390',null,false, $border,'','L',$font,$fontsize2,'B','','4px');
  $str .= $this->reporter->col('','20',null,false, $border,'','L',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','110',null,false, $border,'','L',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col('','280',null,false, $border,'','L',$font,$fontsize2,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('No.','50',null,false, $border1,'TLRB','C',$font,$font_size,'','','');
  $str .= $this->reporter->col('Order Code','100',null,false, $border1,'TRB','C',$font,$font_size,'','','');
  $str .= $this->reporter->col('Mfr','100',null,false, $border1,'TRB','C',$font,$font_size,'','','');
  $str .= $this->reporter->col('Description','200',null,false, $border1,'TRB','C',$font,$font_size,'','','');
  $str .= $this->reporter->col('Quantity','100',null,false, $border1,'TRB','C',$font,$font_size,'','','');
  $str .= $this->reporter->col('Unit Price','120',null,false, $border1,'TRB','C',$font,$font_size,'','','');
  $str .= $this->reporter->col('Line Total','130',null,false, $border1,'TBR','C',$font,$font_size,'','','');
  $str .= $this->reporter->endrow();

  return $str;
}

public function reportquoteplotting($params,$data){
  
  $companyid = $params['params']['companyid'];
  $decimal = $this->companysetup->getdecimal('currency', $params['params']);

  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = '';
  $font = "Arial";
  $fontsize = "12";
  $border = "1px solid ";
  $border1 = "1px solid ; background-color: lightgray";
  $count=35;
  $page=35;
  $str .= $this->reporter->beginreport();
  $str .= $this->default_quote_header($params, $data);
  $linetotal =0;
  $unitprice=0;
  $vatsales =0;
  $vat=0;
  $totalext=0;
  for($i=0;$i<count($data);$i++){
    $unitprice = $data[$i]['gross']-$data[$i]['disc'];
    $linetotal = $data[$i]['qty'] * $unitprice; 
    

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($i+1,'50',null,false, $border,'TLRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col($data[$i]['itemname'],'100',null,false, $border,'TRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col($data[$i]['brand_desc'],'100',null,false, $border,'TRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col($data[$i]['itemdescription'].$data[$i]['accessories'].'<br><br>'.$data[$i]['inforem'],'200',null,false, $border,'TRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty',$params['params'])),'100',null,false, $border,'TRB','CT',$font,$fontsize,'','','');
    $str .= $this->reporter->col('PHP '.number_format($unitprice,$decimal),'120',null,false, $border,'TRB','RT',$font,$fontsize,'','','');
    $str .= $this->reporter->col('PHP '.number_format($linetotal,$decimal),'130',null,false, $border,'TBR','RT',$font,$fontsize,'','','');

    if ($data[0]['vattype'] == 'VATABLE') {
      $vatsales = $vatsales+ $linetotal;
    }else {
      $vatsales = 0;
      $totalext = $totalext + $linetotal;
    }
    
    if($this->reporter->linecounter==$page){
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->default_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page=$page + $count;
    }
  }   
 
  $str .= $this->reporter->endtable();
  
  if ($data[0]['vattype'] == 'VATABLE') {
    $vat = $vatsales * .12;
    $totalext = $vatsales+$vat;
  }else {
    $vat = 0;
  }

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('*QUOTATION VALIDITY: ','150',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('30 Day/s *','400',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Vat Sales','121',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','29',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col(number_format($vatsales,$decimal),'100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
 
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('*STOCK SUBJECT TO PRIOR SALES* ','550',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('12% VAT','121',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','29',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col(number_format($vat,$decimal),'100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
 
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('*NON-CANCELLABLE AND NON-RETURNABLE* ','550',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('VAT Exempt','121',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','29',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('*LEAD TIME: ','90',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('3-5 WORKING DAYS *','460',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Zero Rated','121',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','29',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('*Please review data specs and or item description*','550',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('LESS: WTax','121',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','29',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','550',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('Delivery Charge','121',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','29',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','550',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('Amount Due:','121',null,false, $border1,'LRB','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','29',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col(number_format($totalext,$decimal),'100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br><br><br><br><br>';

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Contact Person: ','120',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col((isset($data[0]['agentname'])? $data[0]['agentname']:''),'200',null,false, $border,'','LT',$font,'13','','','');
  $str .= $this->reporter->col('All Goods Returned by reasons of client`s fault will be charged 20% re-stocking fee of invoice value and shall bear all the costs of returning the goods.','480',null,false, $border,'','LT',$font,'11','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Contact Number: ','120',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col((isset($data[0]['agtel'])? $data[0]['agtel']:''),'200',null,false, $border,'','LT',$font,'13','','','');
  $str .= $this->reporter->col('All Goods Returned must be reported within 7 (seven days and returned within 15 (fifteen) days from date of delivery undamaged and in its original packaging together with a written incidence report.','480',null,false, $border,'','LT',$font,'11','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();


  $str .= $this->reporter->endtable();


  $str .= $this->reporter->endreport();

  return $str;
}

public function reportinstructionform($params,$data){
  $companyid = $params['params']['companyid'];
  $decimal = $this->companysetup->getdecimal('currency', $params['params']);

  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = '';
  $font = "Arial";
  $fontsize = "13";
  $border = "1px solid ";
  $border1 = "1px solid lightgray; background-color: lightgray";
  $count=35;
  $page=35;
  $str .= $this->reporter->beginreport();

  $str .= $this->reporter->begintable('800');
  
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('INSTRUCTION FORM (should be attached to PO) ','320',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp'.'AFT#','280',null,false, $border,'TLRB','LT',$font,'15','B','','4px');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('INVOICE TO: ','320',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','280',null,false, $border,'','LT',$font,'15','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','15',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Company Name: ','205',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'290',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','10',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('PO No.:','100',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'180',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','15',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Contact Name: ','205',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['billcontact'])? $data[0]['billcontact']:''),'290',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','10',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('TIN:','100',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['tin'])? $data[0]['tin']:''),'180',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','15',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Address: ','205',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['billaddr'])? $data[0]['billaddr']:''),'290',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','10',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Contact No.:','100',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['tel'])? $data[0]['tel']:''),'180',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Industry / Vertical','220',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col((isset($data[0]['industry'])? $data[0]['industry']:''),'290',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','10',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Fluke/Amprobr/Hioki Reservation','280',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br><br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('COLLECTION DETAILS:','195',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','55',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('AMT','120',null,false, $border,'B','CT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','10',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('CR','120',null,false, $border,'B','CT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','300',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','30',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Balance for collection','165',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','55',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','120',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','10',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','120',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','300',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','20',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col("<div style='color:gray;'>" . 'For new company with Credit Term, Provide accounting contact details' . "</div>",'495',null,false, $border,'','LT',$font,'11','','','4px');
  $str .= $this->reporter->col('','10',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','275',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','15',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Name: ','205',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','300',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','280',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','15',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Number / Email: ','205',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','300',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','280',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','15',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Other Collection instruction: ','205',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','300',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','280',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('DELIVER TO:','220',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','355',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('TP','90',null,false, $border1,'LTR','RT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','135',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Company Name:','220',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'355',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('FSA','90',null,false, $border1,'LR','RT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','135',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Contact Name / Number / Email:','220',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col((isset($data[0]['shipcontact'])? $data[0]['shipcontact']:''),'355',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('TSRF','90',null,false, $border1,'LR','RT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','135',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Address:','220',null,false, $border,'','LT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col((isset($data[0]['shipaddr'])? $data[0]['shipaddr']:''),'355',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('LCAL','90',null,false, $border1,'LRB','RT',$font,$fontsize,'B','','4px');
  $str .= $this->reporter->col('','135',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $ispartial = '';
  if ($data[0]['ispartial'] == 1) {
    $ispartial ='yes';
  }else{
    $ispartial ='no';
  }

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('PARTIAL DELIVERY ALLOWED:','305',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col($ispartial,'165',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('&nbsp&nbsp'.'Creation Date: ','110',null,false, $border,'B','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col(date("F d,Y", strtotime($data[0]['dateid'])),'220',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Submitted by : '.'&nbsp'.(isset($data[0]['agentname'])? $data[0]['agentname']:''),'305',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','165',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','110',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->col('','220',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Other Delivery Instructions: ','800',null,false, $border,'','LT',$font,$fontsize,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('DDR : '. date("F d,Y", strtotime($data[0]['deldate'])),'800',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Item is on stock as per checking with Vendor','800',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= $this->reporter->endreport();

  return $str;
}

public function proformaplotting_header($params,$data){
  $companyid = $params['params']['companyid'];
  $decimal = $this->companysetup->getdecimal('currency',$params['params']);

  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $query ="select code,name,address,tel,tin from center where code = ".$center."";
  $result = $this->coreFunctions->opentable($query);

  $str = '';
  $font = "Arial";
  $fontsize = "11";
  $fontsize1 = "12";
  $fontsize2 = "13";
  $border = "1px solid ";
  $border1 = "1px solid ; background-color: lightgray";
  $count=35;
  $page=35;
  $str .= $this->reporter->beginreport();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('ACCESS FRONTIER','400',null,false, $border,'','LT',$font,'18','B','','4px');
  $str .= $this->reporter->col('PROFORMA INVOICE: ','200',null,false, $border,'','LT',$font,'14','B','','');
  $str .= $this->reporter->col((isset($data[0]['proformainvoice'])? $data[0]['proformainvoice']:''),'200',null,false, $border,'','LT',$font,'14','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($result[0]->address,'400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Date: ','200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col(date("F d,Y", strtotime($data[0]['proformadate'])),'200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($result[0]->tel,'400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('PO Ref: ','200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col((isset($data[0]['inspo'])? $data[0]['inspo']:''),'200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Payment Terms: ','200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col((isset($data[0]['terms'])? $data[0]['terms']:''),'200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br><br>';
  
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('VAT REG TIN: '.$result[0]->tin,'400',null,false, $border,'','LT',$font,'14','','','4px');
  $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'400',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col((isset($data[0]['billaddr'])? $data[0]['billaddr']:''),'400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Phone: '.(isset($data[0]['billcontactno'])? $data[0]['billcontactno']:''),'400',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','400',null,false, $border,'','LT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Contact Name: '.(isset($data[0]['billcontact'])? $data[0]['billcontact']:''),'400',null,false, $border,'','LT',$font,$fontsize2,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('No.','50',null,false, $border1,'TLBR','CT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Order Code','150',null,false, $border1,'TLBR','CT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Mfr','100',null,false, $border1,'TLBR','CT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Description','200',null,false, $border1,'TLBR','CT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Quantity','70',null,false, $border1,'TLBR','CT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Unit Price','100',null,false, $border1,'TLBR','CT',$font,$fontsize2,'','','');
  $str .= $this->reporter->col('Line Total','130',null,false, $border1,'TLBR','CT',$font,$fontsize2,'','','');
  $str .= $this->reporter->endrow();

  return $str;
}

public function reportproformaplotting($params,$data){
  $companyid = $params['params']['companyid'];
  $decimal = $this->companysetup->getdecimal('currency', $params['params']);

  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = '';
  $font = "Arial";
  $fontsize = "11";
  $fontsize1 = "12";
  $fontsize2 = "13";
  $border = "1px solid ";
  $border1 = "1px solid ; background-color: lightgray";
  $count=35;
  $page=35;
  $str .= $this->reporter->beginreport();

  $str .= $this->proformaplotting_header($params, $data);

  $linetotal =0;
  $unitprice=0;
  $vatsales =0;
  $vat=0;
  $totalext=0;
  for($i=0;$i<count($data);$i++){
    $unitprice = $data[$i]['gross']-$data[$i]['disc'];
    $linetotal = $data[$i]['qty'] * $unitprice; 

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($i+1,'50',null,false, $border,'TLRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col($data[$i]['itemname'],'100',null,false, $border,'TRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col($data[$i]['brand_desc'],'100',null,false, $border,'TRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col($data[$i]['itemdescription'].$data[$i]['accessories'].'<br><br>'.$data[$i]['inforem'],'200',null,false, $border,'TRB','LT',$font,$fontsize,'','','');
    $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty',$params['params'])).'&nbsp'.$data[$i]['uom'],'70',null,false, $border,'TRB','CT',$font,$fontsize,'','','');
    $str .= $this->reporter->col('PHP '.number_format($unitprice,$decimal),'130',null,false, $border,'TRB','RT',$font,$fontsize,'','','');
    $str .= $this->reporter->col('PHP '.number_format($linetotal,$decimal),'150',null,false, $border,'TBR','RT',$font,$fontsize,'','','');

    if ($data[0]['vattype'] == 'VATABLE') {
      $vatsales = $vatsales+ $linetotal;
    }else {
      $vatsales = 0;
      $totalext = $totalext + $linetotal;
    }
    

    if($this->reporter->linecounter==$page){
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->default_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page=$page + $count;
    }
  }   
 
  $str .= $this->reporter->endtable();
  
  if ($data[0]['vattype'] == 'VATABLE') {
    $vat = $vatsales * .12;
    $totalext = $vatsales+$vat;
  }else {
    $vat = 0;
  }

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  // $str .= $this->reporter->col('','150',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('','520',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Vat Sales','130',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','50',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col(number_format($vatsales,$decimal),'100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
 
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','520',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('12% VAT','130',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','50',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col(number_format($vat,$decimal),'100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
 
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','520',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('VAT Exempt','130',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','50',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','520',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('Zero Rated','130',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','50',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','520',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('LESS: WTax','130',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','50',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','520',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('Delivery Charge','130',null,false, $border1,'LBR','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','50',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('0.00','100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','520',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('Amount Due:','130',null,false, $border1,'LRB','CT',$font,'13','B','','');
  $str .= $this->reporter->col('PHP','50',null,false, $border,'B','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col(number_format($totalext,$decimal),'100',null,false, $border,'BR','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','50',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('Approved by: ','500',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('','121',null,false, $border1,'','CT',$font,'13','B','','');
  $str .= $this->reporter->col('','29',null,false, $border,'','LT',$font,$fontsize,'','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','RT',$font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= '<br>';

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','50',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('','250',null,false, $border,'B','LT',$font,'13','B','','');
  $str .= $this->reporter->col('','500',null,false, $border1,'','CT',$font,'13','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= '<br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','50',null,false, $border,'','LT',$font,'13','B','','');
  $str .= $this->reporter->col('Term: In the event of default of non-payment, the customer shall pay 12% interest per annum on all accounts over due plus 25% for attorney`s fees and cost of collection. The parties hereby voluntarily submit the jurisdiction of the proper court in Makat in case of litigation.','550',null,false, $border,'','LT',$font,$fontsize1,'B','','');
  $str .= $this->reporter->col('','200',null,false, $border1,'','CT',$font,'13','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->endreport();

  return $str;
}

}
