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

class cr
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
    $fields = ['radioprint','radioreporttype','prepared','approved','received','print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1,'radioreporttype.options',
      [
        ['label' => 'DEFAULT', 'value'=>'0','color'=>'blue'],
        ['label' => 'Acknowledgment Receipt of Cheques', 'value'=>'1','color'=>'blue']
      ]
    );
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
    return $this->coreFunctions->opentable(
      "select 
      'default' as print,
      '0' as reporttype,
      '' as prepared,
      '' as approved,
      '' as received
      ");
  }

  public function report_default_query($filters){
    $trno = $filters['params']['dataid'];

    switch ($filters['params']['dataparams']['reporttype']) {
      case '0':
        $query="
        select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
        client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
        from ((lahead as head 
        left join ladetail as detail on detail.trno=head.trno) 
        left join coa on coa.acnoid=detail.acnoid) 
        left join client on client.client=detail.client
        where head.doc='cr' and head.trno='$trno'
        union all
        select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
        client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
        from ((glhead as head 
        left join gldetail as detail on detail.trno=head.trno) 
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.clientid=detail.clientid 
        where head.doc='cr' and head.trno='$trno' order by line";
      break;

      case '1':
        $query="
        select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
        client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
        from ((lahead as head 
        left join ladetail as detail on detail.trno=head.trno) 
        left join coa on coa.acnoid=detail.acnoid) 
        left join client on client.client=detail.client
        where head.doc='cr' and head.trno='$trno' and left(coa.alias,2)  in ('cb','cr')
        union all
        select head.trno, date(head.dateid) as dateid, head.docno, head.clientname, head.address, head.yourref,head.ourref, left(coa.alias, 2) as alias, coa.acno, coa.acnoname, coa.alias as ali,
        client.client, detail.ref, date(detail.postdate) as postdate, detail.checkno, detail.db, detail.cr, detail.line
        from ((glhead as head 
        left join gldetail as detail on detail.trno=head.trno) 
        left join coa on coa.acnoid=detail.acnoid)
        left join client on client.clientid=detail.clientid 
        where head.doc='cr' and head.trno='$trno' and left(coa.alias,2) in ('cb','cr')
        order by line";
      break;
    }// end switch
    
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  }

  public function reportplotting($filters, $data){
    switch ($filters['params']['dataparams']['reporttype']) {
      case '0':
        $str = $this->report_cv_default($filters,$data);
      break;

      case '1':
        $str = $this->report_receipt_check($filters,$data);
      break;
    }// end switch

    return $str;
  }// end fn

  public function rpt_default_header($data,$filters){
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->letterhead($center,$username);
      $str .= $this->reporter->endtable();
      $str .= '<br/><br/>';

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      
      $str .= $this->reporter->col('RECEIVED PAYMENT','600',null,false,'1px solid ','','L','Avenir','18','B','','');
      $str .= $this->reporter->col('DOCUMENT # :','100',null,false,'1px solid ','','L','Avenir','13','B','','');
      $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','B','L','Avenir','13','','','').'<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('CUSTOMER : ','100',null,false,'1px solid ','','L','Avenir','12','B','30px','4px');
      $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,'1px solid ','B','L','Avenir','12','','30px','4px');
      $str .= $this->reporter->col('DATE : ','60',null,false,'1px solid ','','L','Avenir','12','B','','');
      $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'1px solid ','B','R','Avenir','12','','','');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ADDRESS : ','80',null,false,'1px solid ','','L','Avenir','12','B','30px','4px');
      $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'520',null,false,'1px solid ','B','L','Avenir','12','','30px','4px');
      $str .= $this->reporter->col('REF. :','40',null,false,'1px solid ','','L','Avenir','12','B','','');
      $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'160',null,false,'1px solid ','B','R','Avenir','12','','','');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow(null,null,false,'1px solid ','','R','Avenir','10','','','4px');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      
      $str .= $this->reporter->col('ACCT.#','75',null,false,'1px solid ','B','C','Avenir','12','B','30px','8px');
      $str .= $this->reporter->col('ACCOUNT NAME','320',null,false,'1px solid ','B','C','Avenir','12','B','30px','8px');
      $str .= $this->reporter->col('REFERENCE #','100',null,false,'1px solid ','B','C','Avenir','12','B','30px','8px');
      $str .= $this->reporter->col('DATE','75',null,false,'1px solid ','B','C','Avenir','12','B','30px','8px');
      $str .= $this->reporter->col('DEBIT','75',null,false,'1px solid ','B','C','Avenir','12','B','30px','8px');
      $str .= $this->reporter->col('CREDIT','75',null,false,'1px solid ','B','C','Avenir','12','B','30px','8px');
      $str .= $this->reporter->col('CLIENT','75',null,false,'1px solid ','B','C','Avenir','12','B','30px','8px');
    return $str;
  }


  private function report_cv_default($filters, $data){
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency',$filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count=30;
    $page=30;

    $str .= $this->reporter->beginreport();

    $str .= $this->rpt_default_header($data,$filters);

    $totaldb=0;
    $totalcr=0;
    for($i=0;$i<count($data);$i++){

      $debit=number_format($data[$i]['db'],$decimal);
      $debit = $debit < 0 ? '-' : $debit;
      $credit=number_format($data[$i]['cr'],$decimal);
      $credit = $credit < 0 ? '-' : $credit;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['acno'],'75',null,false,'1px solid ','','C','Avenir','11','','','2px');
      $str .= $this->reporter->col($data[$i]['acnoname'],'320',null,false,'1px solid ','','L','Avenir','11','','','2px');
      $str .= $this->reporter->col($data[$i]['ref'],'100',null,false,'1px solid ','','C','Avenir','11','','','2px');
      $str .= $this->reporter->col($data[$i]['postdate'],'75',null,false,'1px solid ','','C','Avenir','11','','','2px');
      $str .= $this->reporter->col($debit,'75',null,false,'1px solid ','','R','Avenir','11','','','2px');
      $str .= $this->reporter->col($credit,'75',null,false,'1px solid ','','R','Avenir','11','','','2px');
      $str .= $this->reporter->col($data[$i]['client'],'75',null,false,'1px solid ','','C','Avenir','11','','','2px');
      $totaldb=$totaldb+$data[$i]['db'];
      $totalcr=$totalcr+$data[$i]['cr'];

      if($this->reporter->linecounter==$page){
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->rpt_default_header($data,$filters);

        $str .= $this->reporter->printline();
        $page=$page + $count;
      }
    }       

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    
    $str .= $this->reporter->col('','75',null,false,'1px solid ','','C','Avenir','12','','','2px');

    $str .= $this->reporter->col('Check # : ','50',null,false,'1px solid ','','R','Avenir','12','i','','2px');
    for($c=0;$c<count($data);$c++){
      $str .= $this->reporter->col($data[$c]['checkno'],'50',null,false,'1px solid ','','L','Avenir','12','','','2px');
    }
    
    $str .= $this->reporter->col('','75',null,false,'1px solid ','','C','Avenir','12','','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px solid ','','C','Avenir','12','','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px solid ','','C','Avenir','12','','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px solid ','','C','Avenir','12','','','2px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('','70',null,false,'1px dotted ','T','C','Avenir','12','B','','2px');
    $str .= $this->reporter->col('','70',null,false,'1px dotted ','T','C','Avenir','12','B','','2px');
    $str .= $this->reporter->col('','70',null,false,'1px dotted ','T','C','Avenir','12','B','','2px');
    $str .= $this->reporter->col('GRAND TOTAL :','250',null,false,'1px dotted ','T','R','Avenir','12','B','30px','2px');
    $str .= $this->reporter->col(number_format($totaldb,$decimal),'70',null,false,'1px dotted ','T','R','Avenir','12','B','','2px');
    $str .= $this->reporter->col(number_format($totalcr,$decimal),'60',null,false,'1px dotted ','T','R','Avenir','12','B','','2px');
    $str .= $this->reporter->col('','75',null,false,'1px dotted ','T','C','Avenir','12','B','30px','8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ','266',null,false,'1px solid ','','L','Avenir','12','','','');
    $str .= $this->reporter->col('Approved By :','266',null,false,'1px solid ','','L','Avenir','12','','','');
    $str .= $this->reporter->col('Received By :','266',null,false,'1px solid ','','L','Avenir','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'],'266',null,false,'1px solid ','','L','Avenir','12','B','','');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'],'266',null,false,'1px solid ','','L','Avenir','12','B','','');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'],'266',null,false,'1px solid ','','L','Avenir','12','B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }

  private function report_receipt_check($filters, $data){
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count=30;
    $page=30;

    $str .= '<div style="margin-left:-10px;margin-top:50px;">';
    $str .= $this->reporter->beginreport();
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','25',null,false,'1px solid ','','L','Avenir','18','B','','4px');
        $str .= $this->reporter->col('','100',null,false,'1px solid ','','L','Avenir','13','','','4px');
        $str .= $this->reporter->col('','675',null,false,'1px solid ','','L','Avenir','13','B','','4px');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= "<div style='position: relative; margin-top: 50px;'>";

      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','50',null,false,'1px solid ','','L','Avenir','18','B','','4px');
        $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'150',null,false,'1px solid ','','L','Avenir','13','','','4px');
        $str .= $this->reporter->col('','600',null,false,'1px solid ','','L','Avenir','13','B','','4px');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('','50',null,false,'1px solid ','','L','Avenir','18','B','','4px');
        $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'150',null,false,'1px solid ','','L','Avenir','13','','','4px');
        $str .= $this->reporter->col('','600',null,false,'1px solid ','','L','Avenir','13','B','','4px');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $totaldb=0;
      $totalcr=0;
      $total = 0;
      $str .= "<div style='position: absolute; top: 100px;'>";
      $str .= $this->reporter->begintable('800');
      for($i=0;$i<count($data);$i++){

        $total = number_format($data[$i]['db']-$data[$i]['cr'],$decimal);
        $total = $total < 0 ? '-' : $total;

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data[$i]['postdate'],'75',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
        $str .= $this->reporter->col($data[$i]['acnoname'],'75',null,false,'1px solid ','','L','Century Gothic','11','','','2px');
        $str .= $this->reporter->col($data[$i]['checkno'],'75',null,false,'1px solid ','','C','Century Gothic','11','','','2px');
        $str .= $this->reporter->col($total,'75',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
        $str .= $this->reporter->col('','300',null,false,'1px solid ','','R','Century Gothic','11','','','2px');
        $str .= $this->reporter->endrow();

        if($this->reporter->linecounter==$page){
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
              $str .= $this->rpt_default_header($data,$filters);  
          $str .= $this->reporter->printline();
          $page=$page + $count;
        }
      }// end for loop
      $str .= $this->reporter->endtable();

       $str .= "<div style='position: absolute; top: 160px;'>";
        $str .= $this->reporter->begintable('800');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','100',null,false,'1px solid ','','L','Avenir','18','B','','4px');
          $str .= $this->reporter->col(count($data),'200',null,false,'1px solid ','','L','Avenir','13','','','4px');
          $str .= $this->reporter->col($total,'100',null,false,'1px solid ','','L','Avenir','13','B','','4px');
          $str .= $this->reporter->col('','300',null,false,'1px solid ','','L','Avenir','13','B','','4px');
          $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}
