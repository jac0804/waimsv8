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

class pl
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
        $fields = ['radioprint','prepared','approved','received','print'];
        
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
            '' as received
        ");
    }

public function report_default_query($trno){
    $query = "select head.docno, left(head.dateid,10) as dateid, head.rem, head.plno, head.shipmentno, head.invoiceno, head.yourref,
    hpo.docno as podocno,left(hpo.dateid,10) as podate,hpo.rem,supplier.clientname
    from plhead as head
    left join plstock as stock on head.trno=stock.trno
    left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
    left join hpohead as hpo on hpo.trno=hpos.trno
    left join client as supplier on supplier.client=hpo.client
    where head.trno='$trno'
    group by head.docno,head.dateid,head.rem,head.plno,head.shipmentno, head.invoiceno, head.yourref,
    hpo.docno,hpo.dateid,hpo.rem,supplier.clientname
    union all
    select head.docno, left(head.dateid,10) as dateid, head.rem, head.plno, head.shipmentno, head.invoiceno, head.yourref,
    hpo.docno as podocno,left(hpo.dateid,10) as podate,hpo.rem,supplier.clientname
    from hplhead as head
    left join hplstock as stock on head.trno=stock.trno
    left join hpostock as hpos on hpos.trno = stock.refx and hpos.line = stock.linex
    left join hpohead as hpo on hpo.trno=hpos.trno
    left join client as supplier on supplier.client=hpo.client
    where head.trno='$trno'
    group by head.docno,head.dateid,head.rem,head.plno,head.shipmentno, head.invoiceno, head.yourref,
    hpo.docno,hpo.dateid,hpo.rem,supplier.clientname";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    
    return $result;
}//end fn


public function default_header($params, $data) {
  $center = $params['params']['center'];
  $username = $params['params']['user'];

  $str = "";
  $font =  "Century Gothic";
  $fontsize = "11";
  $border = "1px solid ";
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->letterhead($center,$username);
  $str .= $this->reporter->endtable();
  $str .= '<br><br>';


  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('DOC# : ','80',null,false, $border,'','L', $font,'12','B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'320',null,false, $border,'','L', $font,'12','','30px','4px');
  $str .= $this->reporter->col('PO# :','80',null,false, $border,'','L', $font,'13','B','','');
  $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'320',null,false, $border,'','L', $font,'13','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Packling List No. : ','120',null,false, $border,'','L', $font,'12','B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['plno'])? $data[0]['plno']:''),'280',null,false, $border,'','L', $font,'12','','30px','4px');
  $str .= $this->reporter->col('Shipment No. :','120',null,false, $border,'','L', $font,'13','B','','');
  $str .= $this->reporter->col((isset($data[0]['shipmentno'])? $data[0]['shipmentno']:''),'280',null,false, $border,'','L', $font,'13','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Proforma Invoice No. : ','160',null,false, $border,'','L', $font,'12','B','30px','4px');
  $str .= $this->reporter->col((isset($data[0]['invoiceno'])? $data[0]['invoiceno']:''),'240',null,false, $border,'','L', $font,'12','','30px','4px');
  $str .= $this->reporter->col('Transaction Date :','80',null,false, $border,'','L', $font,'13','B','','');
  $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'320',null,false, $border,'','L', $font,'13','','','');
  $str .= $this->reporter->endrow();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow(null,null,false, $border,'','R', $font, $fontsize,'','','4px');
  $str .= $this->reporter->pagenumber('Page');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();


  $str .= $this->reporter->printline();
  
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Document #.','100',null,false, $border,'B','C', $font,'12','B','30px','8px');
  $str .= $this->reporter->col('Date','100',null,false, $border,'B','C', $font,'12','B','30px','8px');
  $str .= $this->reporter->col('Supplier Name','500',null,false, $border,'B','L', $font,'12','B','30px','8px');
  $str .= $this->reporter->col('Notes','100',null,false, $border,'B','L', $font,'12','B','30px','8px');

  return $str;
}

public function reportplotting($params,$data){
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
  $str .= $this->default_header($params, $data);

  for($i=0;$i<count($data);$i++){
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col($data[$i]['podocno'],'100',null,false, $border,'','C', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['podate'],'100',null,false, $border,'','C', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['clientname'],'500',null,false, $border,'','L', $font, $fontsize,'','','2px');
    $str .= $this->reporter->col($data[$i]['rem'],'100',null,false, $border,'','L', $font, $fontsize,'','','2px');


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
  $str .= '<br><br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Prepared By : ','160',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Approved By :','160',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Received By :','160',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col($params['params']['dataparams']['prepared'],'160',null,false, $border,'B','C', $font,'12','B','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($params['params']['dataparams']['approved'],'160',null,false, $border,'B','C', $font,'12','B','','');
  $str .= $this->reporter->col(' ','160',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($params['params']['dataparams']['received'],'160',null,false, $border,'B','C', $font,'12','B','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->endtable();
  $str .= $this->reporter->endreport();

  return $str;
}


}
