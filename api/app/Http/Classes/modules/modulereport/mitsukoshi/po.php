<?php

namespace App\Http\Classes\modules\modulereport\mitsukoshi;

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

class po
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

    public function createreportfilter(){
        $fields = ['radioprint','prepared','approved','received','attention','print'];
        
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'attention.readonly', false);

        return array('col1'=>$col1);
    }

    public function reportparamsdata($config){
        return $this->coreFunctions->opentable(
            "select
            'default' as print,
            '' as prepared,
            '' as approved,
            '' as received,
            '' as attention
        ");
    }

public function report_default_query($trno){
    $query = "select 0 as num, date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms, head.rem, head.yourref,
        item.barcode, item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext, m.model_name as model, item.sizeid, stock.rem as srem,item.subcode,item.partno
        from pohead as head left join postock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'
        union all
        select 0 as num, date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms, head.rem, head.yourref,
        item.barcode, item.itemname, stock.rrqty as qty, stock.uom, stock.rrcost as netamt, stock.disc, stock.ext, m.model_name as model, item.sizeid, stock.rem as srem,item.subcode,item.partno
        from hpohead as head left join hpostock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join item on item.itemid = stock.itemid
        left join model_masterfile as m on m.model_id = item.model
        where head.doc='po' and head.trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
}//end fn


public function default_header($params, $data) {
  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = "";
  $font =  "Century Gothic";
  $fontsize = "13";
  $fontsize14 = "14";
  $border = "1px solid ";
  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->letterhead($center,$username);
  $str .= $this->reporter->endtable();
  $str .= '<br><br>';

  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('SUPPLIER : ','80',null,false, $border,'','L', $font,$fontsize,'','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'570',null,false, $border,'B','L', $font,$fontsize,'','30px','4px');
  $str .= $this->reporter->col('','50',null,false, $border,'','L', $font,$fontsize,'B','30px','4px');
  $str .= $this->reporter->col('DOCUMENT # :','120',null,false, $border,'','R', $font,$fontsize,'','','');
  $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'180',null,false, $border,'B','L', $font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  
  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('ADDRESS:','80',null,false, $border,'','L', $font,$fontsize,'','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'570',null,false, $border,'B','L', $font,$fontsize,'','30px','4px');
  $str .= $this->reporter->col('','50',null,false, $border,'','L', $font,$fontsize,'B','30px','4px');
  $str .= $this->reporter->col('DATE : ','120',null,false, $border,'','R', $font,$fontsize,'','','');
  $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'180',null,false, $border,'B','L', $font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('ATTENTION: ','80',null,false, $border,'B','L', $font,$fontsize,'','30px','4px');
  $str .= $this->reporter->col($params['params']['dataparams']['attention'],'570',null,false, $border,'B','L', $font,$fontsize,'','30px','4px');
  $str .= $this->reporter->col('','50',null,false, $border,'B','L', $font,$fontsize,'B','30px','4px');
  $str .= $this->reporter->col('PO # : ','120',null,false, $border,'B','R', $font,$fontsize,'','','');
  $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'180',null,false, $border,'B','L', $font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  
  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('','25',null,false, $border,'','C', $font,$fontsize14,'B','','');
  $str .= $this->reporter->col('','125',null,false, $border,'','C', $font,$fontsize14,'B','','');
  $str .= $this->reporter->col('NEW SKU','125',null,false, $border,'','C', $font,$fontsize14,'B','','');
  $str .= $this->reporter->col('','125',null,false, $border,'','C', $font,$fontsize14,'B','','');
  $str .= $this->reporter->col('','120',null,false, $border,'','C', $font,$fontsize14,'B','','');
  $str .= $this->reporter->col('','280',null,false, $border,'','C', $font,$fontsize14,'B','',''); 
  $str .= $this->reporter->col('','100',null,false, $border,'','C', $font,$fontsize14,'B','','');
  $str .= $this->reporter->col('','100',null,false, $border,'','C', $font,$fontsize14,'B','','');
  $str .= $this->reporter->endrow();

  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('#','25',null,false, $border,'B','C', $font,$fontsize14,'B','','2px');
  $str .= $this->reporter->col('PART NO','125',null,false, $border,'B','C', $font,$fontsize14,'B','','2px');
  $str .= $this->reporter->col('BARCODE','125',null,false, $border,'B','C', $font,$fontsize14,'B','','2px');
  $str .= $this->reporter->col('OLD SKU','125',null,false, $border,'B','C', $font,$fontsize14,'B','','2px');
  $str .= $this->reporter->col('MODEL','120',null,false, $border,'B','C', $font,$fontsize14,'B','','2px');
  $str .= $this->reporter->col('DESCRIPTION','280',null,false, $border,'B','C', $font,$fontsize14,'B','','2px'); 
  $str .= $this->reporter->col('QTY','100',null,false, $border,'B','C', $font,$fontsize14,'B','','2px');
  $str .= $this->reporter->col('REMARKS','100',null,false, $border,'B','C', $font,$fontsize14,'B','','2px');
  return $str;
}

public function reportplotting($params,$data){
  $companyid = $params['params']['companyid'];
  $decimal = $this->companysetup->getdecimal('currency',$params['params']);

  $center = $params['params']['center'];
  $username = $params['params']['user'];


  $str = '';
  $count=35;
  $page=35;
  $font =  "Century Gothic";
  $fontsize = "13";
  $border = "1px solid ";

  $str .= $this->reporter->beginreport();
  $str .= $this->default_header($params, $data);
  $str .= $this->reporter->begintable('1000');
  
  $totalnum=0;
  for($i=0;$i<count($data);$i++){
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $totalnum=$totalnum+1;
    $str .= $this->reporter->col($totalnum,'25',null,false, $border,'','CT', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['partno'],'125',null,false, $border,'','CT', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['barcode'],'125',null,false, $border,'','CT', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['subcode'],'125',null,false, $border,'','CT', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['model'],'120',null,false, $border,'','LT', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['itemname'],'280',null,false, $border,'','LT', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col(number_format($data[$i]['qty'],$this->companysetup->getdecimal('qty',$params['params'])),'100',null,false, $border,'','RT', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col('&nbsp&nbsp'.$data[$i]['srem'],'100',null,false, $border,'','LT', $font, $fontsize,'','','2px');
   
    

    if($this->reporter->linecounter==$page){
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->page_break();
      $str .= $this->default_header($params, $data);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->printline();
      $page=$page + $count;
    }
  }

  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();
  $str .= '<br><br><br><br>';
  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Prepared By : ','160',null,false, $border,'','L', $font,$fontsize,'','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,$fontsize,'','','');
  $str .= $this->reporter->col('Approved By :','160',null,false, $border,'','L', $font,$fontsize,'','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,$fontsize,'','','');
  $str .= $this->reporter->col('Received By :','160',null,false, $border,'','L', $font,$fontsize,'','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $str .= $this->reporter->begintable('1000');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($params['params']['dataparams']['prepared'],'160',null,false, $border,'B','C', $font,$fontsize,'B','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($params['params']['dataparams']['approved'],'160',null,false, $border,'B','C', $font,$fontsize,'B','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($params['params']['dataparams']['received'],'160',null,false, $border,'B','C', $font,$fontsize,'B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->endtable();
  $str .= $this->reporter->endreport();

  return $str;
}


}
