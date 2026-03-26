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

class logistics
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
      $fields = ['radioprint','print'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'prepared.label', 'Released By');

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
  $query = "
  select head.trno, head.docno, head.client, head.clientname, head.address, 
  sum(stock.ext) as ext, ci.courier
  from lahead as head 
  left join lastock as stock on head.trno = stock.trno
  left join item as item on item.itemid = stock.itemid
  left join cntnuminfo as ci on ci.trno = head.trno
  where head.trno = ?
  group by head.trno, head.docno, head.client, head.clientname, head.address, ci.courier";

  $result = $this->coreFunctions->opentable($query, [$trno]);
  return $result;
}//end fn

public function report_waybill_query($config){
  $waybill = $config['params']['params1']['waybill'];
  $query = "
    select head.trno, head.docno, head.client, head.clientname, head.address, ci.courier
    from lahead as head 
    left join lastock as stock on head.trno = stock.trno
    left join item as item on item.itemid = stock.itemid
    left join cntnuminfo as ci on ci.trno = head.trno
    where head.waybill='" . $waybill . "' limit 1";

  $result = $this->coreFunctions->opentable($query);
  return $result;
}//end fn


public function reportplotting($params,$data){
  $companyid = $params['params']['companyid'];
  $companyname = $this->companysetup->getcompanyname(['companyid'=>$companyid]);
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
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("Shipment Release Form",'700',null,false, $border,'','C', $font,'16','B','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= "<br>";

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("Shipper: ",'100',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($companyname,'600',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');

  $str .= $this->reporter->endrow();

  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("Dealer's Name: ",'100',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($data[0]->clientname,'600',null,false, $border,'B','L', $font,'12','','','');

  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();

  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("Address: ",'100',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($data[0]->address,'600',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');

  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= "<br>";

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("Item Shipped: ",'100',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col('SPAREPARTS','600',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();

  $declared_amt = isset($params['params']['params1']['waybillamt']) ? $params['params']['params1']['waybillamt'] : $data[0]->ext ;
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("Amt. Shipped: ",'100',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col(number_format($declared_amt, $decimal),'600',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();

  if(isset($params['params']['params1']['waybill'])){
    $qry = "    select ifnull(sum(boxno),0) as value from (
      select  h.trno, count(distinct(boxno)) as boxno from boxinginfo as b left join lahead as h on h.trno=b.trno
      where h.waybill='". $params['params']['params1']['waybill'] ."' group by h.trno order by b.trno) as b";
  }else{
    $qry = "select count(distinct(boxno)) as value from boxinginfo where trno = '".$data[0]->trno."'";
  }
  $num_box = $this->coreFunctions->datareader($qry);

  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("No. of Boxes: ",'100',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($num_box,'600',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();

  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col("Forwarder: ",'100',null,false, $border,'','L', $font,'12','B','','');
  $str .= $this->reporter->col($data[0]->courier,'600',null,false, $border,'B','L', $font,'12','','','');
  $str .= $this->reporter->col('','200',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();


  $str .= "<br><br>";

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('In the case of parts lost or damaged during transit, this forwarder agrees to pay the amount of parts declared.','800',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= '<br>';
  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->col('Released By : ________________','266',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Approved By : ________________','266',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->col('Received By : ________________','266',null,false, $border,'','L', $font,'12','','','');
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->begintable('800');
  $str .= $this->reporter->startrow();
  $str .= $this->reporter->endrow();
  $str .= $this->reporter->endtable();

  $str .= $this->reporter->endtable();
  $str .= $this->reporter->endreport();

  return $str;
}


}
