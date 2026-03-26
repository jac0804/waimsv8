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
use Illuminate\Support\Facades\URL;

class kr
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

  public function report_default_query($filters){
    $trno = $filters['params']['dataid'];
    $query="
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
    head2.yourref as krourref,
    coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, ar.docno as ref,client.tel
    from (krhead as head 
    left join arledger as ar on ar.kr=head.trno)
    left join coa on coa.acnoid=ar.acnoid
    left join glhead as head2 on head2.trno = ar.trno 
    left join client on client.client = head.client
    where head.trno='$trno'
    union all
    select head.client,date(head.dateid) as dateid, concat(left(head.docno,3),right(head.docno,5)) as docno, head.clientname, head.address, head.yourref, head.ourref,
    head2.yourref as krourref,
    coa.acno, coa.acnoname, ar.db, ar.cr, date(ar.dateid) as postdate,head.rem, ar.docno as ref,client.tel
    from (hkrhead as head 
    left join arledger as ar on ar.kr=head.trno)
    left join coa on coa.acnoid=ar.acnoid
    left join glhead as head2 on head2.trno = ar.trno 
    left join client on client.client = head.client
    where head.trno='$trno' order by postdate,dateid, docno";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function rpt_default_header($data,$filters){
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $str .= '<div style="margin-left:-40px;margin-top:-10px;">';
    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'200',null,false,'1px solid ','','L','Century Gothic','12','B','2px','2px');
      $str .= $this->reporter->col('','600',null,false,'1px solid ','','L','Century Gothic','12','B','2px','2px');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<div style="margin-left:10px;margin-top:3px;">';
    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','30',null,false,'1px solid ','','L','Century Gothic','12','B','2px','2px');
      $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'200',null,false,'1px solid ','','L','Century Gothic','12','B','2px','2px');
      $str .= $this->reporter->col('','570',null,false,'1px solid ','','L','Century Gothic','12','B','2px','2px');
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportplotting($filters, $data){
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',$filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count=35;
    $page=35;

    $str .= $this->reporter->beginreport('800');

    $str .= $this->rpt_default_header($data,$filters);

    $str .= "<div style='position: absolute; top: 120px;'>";
    $str .= $this->reporter->begintable('800');
    $totaldb=0;
    $totalcr=0;
    for($i=0;$i<count($data);$i++){

      $total = number_format($data[$i]['db']-$data[$i]['cr'],$decimal);
      $total = $total < 0 ? '-' : $total;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['postdate'],'75',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
      $str .= $this->reporter->col($data[$i]['ref'],'75',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
      $str .= $this->reporter->col($total,'100',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
      $str .= $this->reporter->col('','400',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
      $str .= $this->reporter->endrow();
      
      if($this->reporter->linecounter==$page){
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
            $str .= $this->rpt_default_header($data,$filters);  
        $str .= $this->reporter->printline();
        $page=$page + $count;
      }
    }     

    $str .= $this->reporter->endtable();
    $str .= "<div style='position: absolute; top: 150px;'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','100',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'],'200',null,false,'1px solid ','','L','Century Gothic','12','B','','');
      $str .= $this->reporter->col('','500',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }//end fn

}
