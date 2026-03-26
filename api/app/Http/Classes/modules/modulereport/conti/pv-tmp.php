<?php

namespace App\Http\Classes\modules\modulereport\conti;

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

class pv
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
    $fields = ['radioprint','radioreporttype','prepared','approved','received','checked','payor','tin','position','print'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1,'radioreporttype.label','Print Cash/Check Voucher');
    data_set($col1,'radioreporttype.options',
      [
        ['label' => 'VOUCHER', 'value'=>'0','color'=>'blue'],
        // ['label' => 'CHECK', 'value'=>'1','color'=>'blue'],
        ['label' => 'BIR Form 2307', 'value'=>'2','color'=>'blue'],
        ['label' => 'BIR Form 2307 (New)', 'value'=>'3','color'=>'blue']
      ]
    );
    return array('col1'=>$col1);
  }

  public function reportparamsdata($config){
      return $this->coreFunctions->opentable(
        "select 
        'default' as print,
        '' as prepared,
        '' as approved,
        '' as received,
        '' as checked,
        '' as payor,
        '' as tin,
        '' as position,
        '0' as reporttype
        ");
  }

  public function reportplotting($config,$data){
    switch ($config['params']['dataparams']['reporttype']) {
      case 0: // VOUCHER
        $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT1($data,$config);
        break;
      // case 1:
      //   $str = $this->rpt_DEFAULT_CCVOUCHER_LAYOUT2($data,$config);
      //   break;
      case 2:
        $str = $this->rpt_CV_WTAXREPORT($data,$config);
        break;
      case 3:
        $str = $this->rpt_CV_WTAXREPORT_NEW($data,$config);
      break;
    }

    return $str;
  }

  public function report_default_query($filters){
    $trno = $filters['params']['dataid'];

    switch ($filters['params']['dataparams']['reporttype']) {
      case 2: case 3:
        $query = "select * from(
        select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref,detail.postdate,
        detail.db, detail.cr, detail.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join ewtlist on ewtlist.code = detail.ewtcode
        left join coa on coa.acnoid=detail.acnoid
        where head.doc='PV' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1)
        union all
        select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
        head.address,detail.rem, head.yourref, head.ourref,client.tin,
        coa.acno, coa.acnoname, detail.ref, detail.postdate,
        detail.db, detail.cr, dclient.client as dclient, detail.checkno,
        detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate,detail.isvewt,
        client.zipcode
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join coa on coa.acnoid=detail.acnoid
        left join client as dclient on dclient.clientid=detail.clientid
        left join ewtlist on ewtlist.code = detail.ewtcode
        where head.doc='PV' and head.trno ='$trno' and (detail.isewt = 1 or detail.isvewt=1))
        as tbl order by tbl.ewtdesc";

        $result1 = json_decode(json_encode($this->coreFunctions->opentable($query)), true);

        $arrs = [];
        $arrss=[];
        $ewt ='';
        foreach ($result1 as $key => $value) {
          $ewtrateval = floatval($value['ewtrate']) / 100;
          if($value['db'] == 0){ 
          //FOR CR
          if($value['cr'] < 0){
            $db = $value['cr'];
          }else{
            $db = floatval($value['cr']) * -1;
          }//end if

          if ($value['isvewt']==1){
            $db = $db/1.12;
          }

          $ewtamt = $db * $ewtrateval;
          }else{
          //FOR DB
          if($value['db'] < 0){
            $db = floatval($value['db']) * -1;
          }else{
            $db = $value['db'];
          }//end if

          if ($value['isvewt']==1){
            $db = $db/1.12;
          }
          $ewtamt = $db * $ewtrateval;
          } //end if

          if ($ewt != $value['ewtcode']){
          $arrs[$value['ewtcode']]['oamt']=$db;
          $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
          $arrs[$value['ewtcode']]['month'] = $value['month'];
          }else{
          array_push($arrss,$arrs);
          $arrs[$value['ewtcode']]['oamt']=$db;
          $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
          $arrs[$value['ewtcode']]['month'] = $value['month'];
          }

          $ewt = $value['ewtcode'];
        }//end for each

        array_push($arrss,$arrs);
        $keyers = '';
        $finalarrs = [];
        foreach ($arrss as $key => $value) {      
          foreach ($value as $key => $y) {
          if($keyers == ''){                    
          $keyers = $key;
          $finalarrs[$key]['oamt'] = $y['oamt'];
          $finalarrs[$key]['xamt'] = $y['xamt'];
          }else{
          if($keyers == $key){
          $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($y['oamt']);
          $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($y['xamt']);
          }else{
          $finalarrs[$key]['oamt'] = $y['oamt'];
          $finalarrs[$key]['xamt'] = $y['xamt'];
          }//end if
          }//end if
          $finalarrs[$key]['month'] = $y['month'];
          }
          
        }//end for each
        if(empty($result1)){
        $returnarr[0]['payee'] = '';
        $returnarr[0]['tin'] = '';
        $returnarr[0]['address'] = '';
        $returnarr[0]['month'] = '';
        $returnarr[0]['yr'] = '';
        }else{
        $returnarr[0]['payee'] = $result1[0]['clientname'];
        $returnarr[0]['tin'] = $result1[0]['tin'];
        $returnarr[0]['address'] = $result1[0]['address'];
        $returnarr[0]['month'] = $result1[0]['month'];
        $returnarr[0]['yr'] = $result1[0]['yr'];
        }

        $result = ['head'=>$returnarr,'detail'=>$finalarrs, 'res'=>$result1];
      break;

      default:
        $query ="select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
          head.rem as hrem,head.rem, head.yourref, head.ourref,
          coa.acno, coa.acnoname, detail.ref, detail.postdate, detail.db, detail.cr,
          detail.client as dclient, detail.checkno, head.project
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno 
          left join client on client.client=head.client
          left join coa on coa.acnoid=detail.acnoid
          where head.doc='pv' and md5(head.trno)='".md5($trno)."'
          union all
          select date(head.dateid) as dateid, head.docno, client.client, client.clientname, head.address, head.terms,
          head.rem as hrem,head.rem, head.yourref, head.ourref,
          coa.acno, coa.acnoname, detail.ref, detail.postdate, detail.db, detail.cr,
          dclient.client as dclient, detail.checkno, head.project
          from glhead as head 
          left join gldetail as detail on detail.trno=head.trno 
          left join client on client.clientid=head.clientid
          left join coa on coa.acnoid=detail.acnoid left join client as dclient on dclient.clientid=detail.clientid
          where head.doc='pv' and md5(head.trno)='".md5($trno)."'";
        $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
      break;
    }// end switch
    return $result;
  }

  public function rpt_default_header($data,$filters){

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCOUNTS PAYABLE VOUCHER','600',null,false,'1px solid ','','L','Century Gothic','18','B','','');
    $str .= $this->reporter->col('DOCUMENT # :','100',null,false,'1px solid ','','L','Century Gothic','13','B','','');
    $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'100',null,false,'1px solid ','B','L','Century Gothic','13','','','').'<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAYEE : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'520',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->col('DATE : ','40',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'480',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->col('REFERENCE # :','80',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'160',null,false,'1px solid ','B','R','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NOTES : ','80',null,false,'1px solid ','','L','Century Gothic','12','B','30px','4px');
    $str .= $this->reporter->col((isset($data[0]['rem'])? $data[0]['rem']:''),'720',null,false,'1px solid ','B','L','Century Gothic','12','','30px','4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null,null,false,'1px solid ','','R','Century Gothic','10','','','4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    //($w=null,$h=null, $bg=false,  $b=false, $al='',  $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCT.#','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('ACCOUNT NAME','200',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('CHECK DETAILS','200',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('DATE','75',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('DEBIT','100',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('CREDIT','100',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    $str .= $this->reporter->col('REMARKS','100',null,false,'1px solid ','B','C','Century Gothic','12','B','30px','8px');
    return $str;
  }

  public function rpt_default_header_ccvoucher($data,$filters){

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $prepared = $filters['params']['dataparams']['prepared'];
    $received = $filters['params']['dataparams']['received'];
    $approved = $filters['params']['dataparams']['approved'];

    $str = '';
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center .'&nbsp'  .'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
      // $str .= '<br/><br/>';
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('ACCOUNTS PAYABLE VOUCHER','400',null,false,'','','L','Century Gothic','18','B','','');
        $str .= $this->reporter->col('# :','251',null,false,'1px  ','','R','Century Gothic','13','B','','');
        $str .= $this->reporter->col((isset($data[0]['docno'])? $data[0]['docno']:''),'149',null,false,'','','R','Century Gothic','13','','','').'<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
              
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('PAID&nbspTO&nbsp:','70',null,false,'1px  ','','L','Century Gothic','12','B','30px','4px');
      $str .= $this->reporter->col((isset($data[0]['clientname'])? $data[0]['clientname']:''),'530',null,false,'1px  ','B','L','Century Gothic','12','','30px','4px');
      $str .= $this->reporter->col('DATE :','100',null,false,'','','R','Century Gothic','12','B','','');
      $str .= $this->reporter->col((isset($data[0]['dateid'])? $data[0]['dateid']:''),'160',null,false,'','','R','Century Gothic','12','','','');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('ADDRESS&nbsp:','30',null,false,'1px  ','','L','Century Gothic','12','B','30px','4px');
      $str .= $this->reporter->col((isset($data[0]['address'])? $data[0]['address']:''),'405',null,false,'','','L','Century Gothic','12','','30px','4px');
      $str .= $this->reporter->col('YOURREF&nbsp:','10',null,false,'','','R','Century Gothic','12','B','','4px');
      $str .= $this->reporter->col((isset($data[0]['yourref'])? $data[0]['yourref']:''),'120',null,false,'1px  ','B','R','Century Gothic','12','','','4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('P A R T I C U L A R S','650',null,false,'1px solid ','LRTB','C','Century Gothic','12','B','','3px');
      $str .= $this->reporter->col('AMOUNT','150',null,false,'1px solid ','LRTB','R','Century Gothic','12','B','','3px'); 
    return $str;
  }

  public function rpt_DEFAULT_CCVOUCHER_LAYOUT1($data,$filters){

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $prepared = $filters['params']['dataparams']['prepared'];
    $received = $filters['params']['dataparams']['received'];
    $approved = $filters['params']['dataparams']['approved'];


    $str = '';
    $count=60;
    $page=58;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header_ccvoucher($data,$filters);
    $totaldb=0;
    $totalcr=0;
    $remarks="";

    for($i=0;$i<count($data);$i++){       
      $str .= $this->reporter->startrow();
      $totaldb=$totaldb+$data[$i]['db'];
      $totalcr=$totalcr+$data[$i]['cr'];
              
      if($this->reporter->linecounter==$page){
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->printline();
        $page=$page + $count;
      }
    }

    if ($remarks==$data[0]['rem']){
      $remarks="";
    } else {
      $remarks=$data[0]['rem'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($remarks,'650',null,false,'1px solid ','LRBT','L','Century Gothic','11','','','3px');
    $str .= $this->reporter->col(number_format($totaldb,2),'150',null,false,'1px solid ','LRBT','R','Century Gothic','11','B','','3px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();

    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTING ENTRY','500',null,false,'1px solid ','LTB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->col('','100',null,false,'1px solid','RTB','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->col('DEBIT','100',null,false,'1px solid ','LRTB','R','Century Gothic','12','B','','3px');
    $str .= $this->reporter->col('CREDIT','100',null,false,'1px solid ','LRTB','R','Century Gothic','12','B','','3px');
    $totaldb=0;
    $totalcr=0;
    $acname="";
    for($i=0;$i<count($data);$i++){       
      $debit=number_format($data[$i]['db'],2);
      if ($debit<1)
      {
        $debit='';
      }
       $credit=number_format($data[$i]['cr'],2);
      if ($credit<1)
      {
        $credit='';
      }
      if ($acname==$data[$i]['acnoname']){
        $acname="";
      } else {
            $acname=$data[$i]['acnoname'];
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($acname,'500',null,false,'1px solid ','L','L','Century Gothic','11','','','3px');
      $str .= $this->reporter->col($data[$i]['project'],'100',null,false,'1px solid ','R','L','Century Gothic','11','','','3px');
      $str .= $this->reporter->col($debit,'100',null,false,'1px solid ','LR','R','Century Gothic','11','','','3px');
      $str .= $this->reporter->col($credit,'100',null,false,'1px solid ','LR','R','Century Gothic','11','','','3px');       
    }   

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('','500',null,false,'1px solid ','T','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->col('','100',null,false,'1px solid ','T','C','Century Gothic','12','B','','3px');
    $str .= $this->reporter->col('','100',null,false,'1px solid ','T','R','Century Gothic','12','B','','3px');
    $str .= $this->reporter->col('','100',null,false,'1px solid ','T','R','Century Gothic','12','B','','3px');
    $str .= $this->reporter->endrow();
    
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->col('Approved By :','266',null,false,'1px solid ','','C','Century Gothic','12','','','');
    $str .= $this->reporter->col('Received By :','266',null,false,'1px solid ','','L','Century Gothic','12','','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
      
    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared,'215',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->col($approved,'320',null,false,'1px solid ','','C','Century Gothic','12','B','','');
    $str .= $this->reporter->col($received,'266',null,false,'1px solid ','','L','Century Gothic','12','B','','');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
      
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }//end fn

  public function rpt_DEFAULT_CCVOUCHER_LAYOUT2($data,$filters){
    $trno = $filters['params']['dataid'];
    $str = '';

    $count=1;
    $page=30;
    $cc='';
    $cdate='';

    $qry ="select DATE_FORMAT(left(postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr from ladetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = '$trno' and left(coa.alias,2) = 'AP' group by detail.checkno,coa.acno
    UNION ALL
    select DATE_FORMAT(left(postdate,10),'%b %d, %Y') as postdate,detail.checkno,coa.acno,detail.cr from gldetail as detail
    left join coa on coa.acnoid = detail.acnoid
    where trno = '$trno'
    and left(coa.alias,2) = 'AP' group by detail.checkno,coa.acno";
    $data2 = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);


    for($i=0;$i<count($data2);$i++){
      if($this->reporter->linecounter==$page){                  
        $str .= $this->reporter->page_break();
      }
      $cc=$data2[$i]['cr'];
      $cdate = $data2[$i]['postdate'];

      $str .= '<div style="margin-top:-2px;letter-spacing: 3px;">';
      $str .= $this->reporter->beginreport('900');


      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','50',null,false,'1px solid ','','C','Verdana','13','B','30px','4px');
      $str .= $this->reporter->col('','670',null,false,'1px solid ','','L','Verdana','10','','30px','4px');
      $str .= $this->reporter->col((''.isset($cdate)? $cdate:''),'180',null,false,'1px solid ','','L','Verdana','10','','30px','4px');
      $str .= $this->reporter->col('','120',null,false,'1px solid ','','C','Verdana','13','B','','');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      $str .= $this->reporter->begintable('920');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','50',null,false,'1px solid ','','C','Verdana','13','B','30px','4px');
      $str .= $this->reporter->col($data[0]['clientname'],'720',null,false,'1px solid ','','L','Verdana','10','','30px','4px');
      $str .= $this->reporter->col((isset($cc)? number_format($cc,$decimal):''),'150',null,false,'1px solid ','','C','Verdana','10','','30px','4px');
      $str .= $this->reporter->col('','100',null,false,'1px solid ','','C','Verdana','13','B','','');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('920');
      $dd = number_format((float)$cc, 2, '.', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','50',null,false,'1px solid ','','C','Verdana','13','B','30px','4px');
      $str .= $this->reporter->col($this->ftNumberToWordsConverter($dd). ' ONLY','900',null,false,'1px solid ','','L','Verdana','10','','30px','4px');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      $str .= $this->reporter->endreport();
      $str .= '</div>';
      $this->reporter->linecounter=30;                    
    }
    return $str;
  }//end fn

  public function rpt_CV_WTAXREPORT($data,$filters){

    $str = '';
    $count=60;
    $page=58;

    $birlogo = URL::to('fimages/reports/birlogo.PNG');

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

      //1st row


      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();   

          $str .= $this->reporter->col('<img src ="'.$birlogo.'" alt="BIR" width="80px" height ="80px">','95',null,false,'2px solid ','LTB','C','Century Gothic','15','B','','');
          $str .= $this->reporter->col('Republika ng Pilipinas Kagawaran ng Pananalapi','90',null,false,'2px solid ','TB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','55',null,false,'2px solid ','TB','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Certificate of Creditable Tax Withheld At Source','350',null,false,'2px solid ','TB','C','Century Gothic','20','B','','');
          
          $str .= $this->reporter->col('','55',null,false,'2px solid ','TB','L','Century Gothic','11','B','','');
          
          $str .= $this->reporter->col('BIR Form No. <h4><b> 2307 </b></h4> September 2005 (ENCS)','18',null,false,'2px solid ','TB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','20',null,false,'2px solid ','RTB','L','Century Gothic','11','B','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //2nd row blank
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','125',null,false,'2px solid ','LT','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','180',null,false,'2px solid ','T','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','300',null,false,'2px solid','T','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','195',null,false,'2px solid ','RT','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //3rd row -> 1 for the period
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('1','40',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('For the Period','120',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','70',null,false,'2px solid','','C','Century Gothic','20','','','');


          switch ($data['head'][0]['month']) {
            case '1': case '2': case '3':
              $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('','270',null,false,'2px solid','','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('03','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('31','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              break;

            case '4': case '5': case '6':
              $str .= $this->reporter->col('04','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('','270',null,false,'2px solid','','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('06','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('30','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              break;

            case '7': case '8': case '9':
              $str .= $this->reporter->col('07','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('','270',null,false,'2px solid','','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('09','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('30','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              break;
            
            default:
              $str .= $this->reporter->col('10','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('','270',null,false,'2px solid','','C','Century Gothic','14','','','8px');

              $str .= $this->reporter->col('12','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('31','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              $str .= $this->reporter->col('18','10',null,false,'2px solid','LRTB','C','Century Gothic','14','','','8px');
              break;
          }


          

          $str .= $this->reporter->col('','340',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //4th row -> from
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','20',null,false,'2px solid ','LB','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('From','90',null,false,'2px solid ','B','R','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','60',null,false,'2px solid','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','B','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','B','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('(MM/DD/YY)','140',null,false,'2px solid','B','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('To','120',null,false,'2px solid ','B','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('','0',null,false,'2px solid ','B','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','100',null,false,'2px solid ','B','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('(MM/DD/YY)','240',null,false,'2px solid','RB','L','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //5th row -> part 1
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Part I','50',null,false,'2px solid ','TLB','C','Century Gothic','12','B','','3px');
          $str .= $this->reporter->col('Payee Information','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //6th row -> blank 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','125',null,false,'2px solid ','LT','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','180',null,false,'2px solid ','T','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','300',null,false,'2px solid','T','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','195',null,false,'2px solid ','RT','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //7th row -> 2 tax payer
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('2','30',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Tax Payer','100',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

          $str .= $this->reporter->col((isset($data['head'][0]['tin'])? $data['head'][0]['tin']:''),'250',null,false,'2px solid','LRTB','C','Century Gothic','20','','','15px');
         
          $str .= $this->reporter->col('','370',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //8th row -> 2 identification
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Identification Number','150',null,false,'2px solid ','','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid','','C','Century Gothic','20','','','3px');
          $str .= $this->reporter->col('','630',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //9th row -> 3 payees name
       $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('3','30',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col("Payee's Name" ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

          $str .= $this->reporter->col((isset($data['head'][0]['payee'])? $data['head'][0]['payee']:''),'610',null,false,'2px solid','LRTB','L','Century Gothic','12','B','','3px');
         
          $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //10th row -> registered name
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','90',null,false,'2px solid ','L','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','30',null,false,'2px solid','','C','Century Gothic','20','','','10px');

          $str .= $this->reporter->col('(Last Name, First Name, Middle Name for Individuals)(Registered Name for Non-Individuals)','560',null,false,'2px solid','','C','Century Gothic','11','','','');
         
          $str .= $this->reporter->col('','40',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //11th row -> 4 registered address
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('4','30',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Registered Address' ,'125',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','0',null,false,'2px solid','','C','Century Gothic','20','','','15px');

          $str .= $this->reporter->col((isset($data['head'][0]['address'])? $data['head'][0]['address']:''),'380',null,false,'2px solid','LRTB','L','Century Gothic','12','B','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','20','','','15px');
          $str .= $this->reporter->col('4A','5',null,false,'2px solid ','','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Zip Code','90',null,false,'2px solid ','R','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','145',null,false,'2px solid ','LRTB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //12th row -> blank 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','125',null,false,'2px solid ','L','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','180',null,false,'2px solid ','','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','300',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','195',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //13th row -> 4 foreign address
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('5','30',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Foreign Address' ,'127',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','0',null,false,'2px solid','','C','Century Gothic','20','','','15px');

          $str .= $this->reporter->col('','380',null,false,'2px solid','LRTB','C','Century Gothic','20','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','20','','','15px');
          $str .= $this->reporter->col('5A','5',null,false,'2px solid ','','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Zip Code','90',null,false,'2px solid ','R','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','145',null,false,'2px solid ','LRTB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //14th row -> blank 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','125',null,false,'2px solid ','LB','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','180',null,false,'2px solid ','B','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','300',null,false,'2px solid','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','195',null,false,'2px solid ','RB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //15th row -> blank 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','50',null,false,'2px solid ','TLB','C','Century Gothic','12','B','','3px');
          $str .= $this->reporter->col('Payor Information','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
          
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //16th row -> blank 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','125',null,false,'2px solid ','LT','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','180',null,false,'2px solid ','T','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','300',null,false,'2px solid','T','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','195',null,false,'2px solid ','RT','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //17th row -> 6 tax payer
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('6','30',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Tax Payer','100',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

          $str .= $this->reporter->col('','250',null,false,'2px solid','LRTB','C','Century Gothic','20','','','15px');
         
          $str .= $this->reporter->col('','370',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      //18th row ->  identification
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Identification Number','150',null,false,'2px solid ','','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid','','C','Century Gothic','20','','','3px');
          $str .= $this->reporter->col('','630',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //19th row -> 7 payors name
       $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('7','30',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col("Payor's Name" ,'100',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','20','','','15px');

          $str .= $this->reporter->col('','610',null,false,'2px solid','LRTB','C','Century Gothic','20','','','15px');
         
          $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //20th row -> registered name
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','90',null,false,'2px solid ','L','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','30',null,false,'2px solid','','C','Century Gothic','20','','','10px');

          $str .= $this->reporter->col('(Last Name, First Name, Middle Name for Individuals)(Registered Name for Non-Individuals)','560',null,false,'2px solid','','C','Century Gothic','11','','','');
         
          $str .= $this->reporter->col('','40',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //21th row -> 8 registered address
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('8','30',null,false,'2px solid ','L','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Registered Address' ,'127',null,false,'2px solid ','','L','Century Gothic','11','','','');

          $str .= $this->reporter->col('','0',null,false,'2px solid','','C','Century Gothic','20','','','15px');

          $str .= $this->reporter->col('','380',null,false,'2px solid','LRTB','C','Century Gothic','20','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','20','','','15px');
          $str .= $this->reporter->col('8A','5',null,false,'2px solid ','','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col('Zip Code','90',null,false,'2px solid ','R','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','145',null,false,'2px solid ','LRTB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //22th row -> blank 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','125',null,false,'2px solid ','LB','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','180',null,false,'2px solid ','B','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','300',null,false,'2px solid','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','195',null,false,'2px solid ','RB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //23th row -> part II
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Part II','50',null,false,'2px solid ','TLB','C','Century Gothic','12','B','','3px');
          $str .= $this->reporter->col('Details of Monthly Income Payments and Tax Withheld for the Quarter','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //24th row -> income payments 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRT','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRT','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS','380',null,false,'2px solid','LRTB','C','Century Gothic','11','B','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRT','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //25th row -> month header
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax','200',null,false,'2px solid ','LR','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('ATC','80',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('1st Month of the Quarter','95',null,false,'2px solid','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('2nd Month of the Quarter','95',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('3rd Month of the Quarter','95',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Total','95',null,false,'2px solid','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Tax Withheld For the Quarter','140',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //26th row -> blank 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LR','C','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LR','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LR','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      
      //27th row -> line
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          
          $str .= $this->reporter->col('','800',null,false,'2px solid ','LTRB','C','Century Gothic','12','B','','1px');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      //28th row -> atc1
     $str .= $this->reporter->begintable('800');
      
      $total =0;
          $a = -1;
          foreach ($data['detail'] as $key => $value) 
          {
            $a++;
            $str .= $this->reporter->startrow();
              $str .= $this->reporter->col($data['res'][$a]['ewtdesc'],'200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','15px');
              $str .= $this->reporter->col($key,'80',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');

              switch ($data['head'][0]['month']) 
              {
                case '1': case '2': case '3':
                  $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'],2),'95',null,false,'2px solid','LRB','R','Century Gothic','11','','','');
                  $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','11','','','');
                  $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','11','','','');
                   
                  break;
                case '4': case '5': case '6':
                  $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','R','Century Gothic','11','','','');
                  $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'],2),'95',null,false,'2px solid ','LRB','R','Century Gothic','11','','','');
                  $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','11','','','');
                  
                  break;
                default:
                  $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','R','Century Gothic','11','','','');
                  $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','11','','','');
                  $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'],2),'95',null,false,'2px solid ','LRB','R','Century Gothic','11','','','');
                  
                  break;
               
              }
              $total=number_format($data['detail'][$key]['oamt'],2);
              $str .= $this->reporter->col($total,'95',null,false,'2px solid','LRB','R','Century Gothic','11','','','');
              $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'],2),'140',null,false,'2px solid ','LRB','R','Century Gothic','11','','','');
              

          }
    $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

      
      //29th row -> total
      $str .= $this->reporter->begintable('800');
        $totaltax =0;

        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total','200',null,false,'2px solid ','LR','L','Century Gothic','11','B','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LR','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LR','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LR','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LR','R','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LR','R','Century Gothic','11','','','');
          
          foreach ($data['detail'] as $key => $value) {
            
            $totaltax =$totaltax + $data['detail'][$key]['xamt'];
            }  
          
          $str .= $this->reporter->col(number_format($totaltax,2),'140',null,false,'2px solid ','LR','R','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //30th row -> space for total 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','B','','15px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //31th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','3px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //32th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //32th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //32th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //32th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //32th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //32th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //32th row -> money payments row
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total','200',null,false,'2px solid ','LRB','L','Century Gothic','11','B','','10px');
          $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //33th row -> declaration
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by me, and to the best of my knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof.','800',null,false,'2px solid ','LRT','L','Century Gothic','11','','','10px');
         
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //34th row -> space after declaration
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','800',null,false,'2px solid ','LR','L','Century Gothic','11','B','','10px');
          
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //35th row -> signature line 1
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['payor']),'395',null,false,'2px solid ','B','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col($filters['params']['dataparams']['tin'],'175',null,false,'2px solid ','B','C','Century Gothic','11','B','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['position']),'175',null,false,'2px solid','B','C','Century Gothic','11','B','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //35th row -> signature line 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //36th row -> authorized signature
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col("Payor/Payor's Authorized Representative/Accredited Tax Agent",'395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('TIN of Signatory','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Title/Position of Signatory','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //37th row -> authorized signature 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col("(Signature Over Printed Name)",'395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

     //38th row -> blank space after authorized signature 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','10px');
          $str .= $this->reporter->col("",'395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      //39th row -> signature line 1
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','B','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','B','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //40th row -> signature line 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //41th row -> authorized signature
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col("Tax Agent Accreditation No./Attorney Roll No. (if applicable)",'395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Date of Issuance','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Date of Expiry','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      
      //42th row -> blank space after authorized signature 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','LB','L','Century Gothic','11','B','','5px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','B','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','RB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //43th row -> space after declaration
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Conforme','800',null,false,'2px solid ','LRT','L','Century Gothic','11','B','','3px');
          
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //44th row -> space after declaration
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','800',null,false,'2px solid ','LR','L','Century Gothic','11','B','','10px');
          
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //45th row -> signature line 1
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','B','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','120',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','140',null,false,'2px solid','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','85',null,false,'2px solid','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //45th row -> signature line 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','120',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','140',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','85',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //46th row -> authorized signature
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col("Payee/Payee's Authorized Representative/Accredited Tax Agent",'395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('TIN of Signatory','120',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','10',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Title/Position of Signatory','140',null,false,'2px solid','','C','Century Gothic','11','','','');
           $str .= $this->reporter->col('','10',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Date Signed','85',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //47th row -> authorized signature 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col("(Signature Over Printed Name)",'395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

     //48th row -> blank space after authorized signature 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','10px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();


      //49th row -> signature line 1
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','B','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','B','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //50th row -> signature line 2
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','3px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','','L','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      //51th row -> authorized signature
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','11','B','','');
          $str .= $this->reporter->col("Tax Agent Accreditation No./Attorney Roll No. (if applicable)",'395',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Date of Issuance','175',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('Date of Expiry','175',null,false,'2px solid','','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      
      //52th row -> blank space after authorized signature 
      $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('','10',null,false,'2px solid ','LB','L','Century Gothic','11','B','','10px');
          $str .= $this->reporter->col('','395',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','15',null,false,'2px solid ','B','C','Century Gothic','11','','','');
          $str .= $this->reporter->col('','175',null,false,'2px solid','B','C','Century Gothic','11','','','');
        
          $str .= $this->reporter->col('','15',null,false,'2px solid ','RB','C','Century Gothic','11','','','');
        $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();




    $str .= $this->reporter->endreport();
    return $str;
  }

  public function rpt_CV_WTAXREPORT_NEW($data,$filters){
          $str = '';
          $count=60;
          $page=58;

          $birlogo = URL::to('/images/reports/birlogo.png');
          $birblogo = URL::to('/images/reports/birbarcode.png');
          $bir = URL::to('/images/reports/bir2307.png');
          $str .= $this->reporter->beginreport();
          $str .= $this->reporter->endtable();
          $str .= '';

            //1st row
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();   
              $str .= $this->reporter->col('For BIR&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspBCS/<br/>Use Only&nbsp&nbsp&nbspItem:','10',null,false,'2px solid ','','L','Century Gothic','10','','','');
              //$str .= $this->reporter->col('BCS/<br/>Item:','10',null,false,'2px solid ','','L','Century Gothic','11','','','');
              $str .= $this->reporter->col('<img src ="'.$birlogo.'" alt="BIR" width="60px" height ="60px">','10',null,false,'2px solid ','','R','Century Gothic','15','B','','1px');
                $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue','60',null,false,'2px solid ','','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','90',null,false,'2px solid ','','C','Century Gothic','11','','','');
                $str .= $this->reporter->endrow(); 
         

               $str .= $this->reporter->begintable('800');
                $str .= $this->reporter->startrow();   
                // $str .= $this->reporter->col('<img src ="'.$birlogo.'" alt="BIR" width="80px" height ="80px">','95',null,false,'2px solid ','LTB','C','Century Gothic','15','B','','8px');
                 $str .= $this->reporter->col('<img src ="'.$bir.'">','135',null,false,'2px solid ','LRTB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','55',null,false,'2px solid ','TB','L','Century Gothic','11','B','','');
                $str .= $this->reporter->col('Certificate of Creditable Tax <br> Withheld At Source','450',null,false,'2px solid ','RTB','C','Century Gothic','16','B','','');
                
                //$str .= $this->reporter->col('','55',null,false,'2px solid ','TB','L','Century Gothic','11','B','','');
                
                $str .= $this->reporter->col('<img src ="'.$birblogo.'" alt="BIR" width="200px" height ="50px">','130',null,false,'2px solid ','TB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','5',null,false,'2px solid ','RTB','L','Century Gothic','11','B','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable('800');
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"','100',null,false,'2px solid ','LRTB','L','Century Gothic','9','','','');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            //2nd row blank
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','LT','C','Century Gothic','11','','','1px');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','T','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','T','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','RT','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //3rd row -> 1 for the period
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('1','40',null,false,'2px solid ','L','C','Century Gothic','10','B','','1px');
                $str .= $this->reporter->col('For the Period','120',null,false,'2px solid ','','L','Century Gothic','10','','','');

                $str .= $this->reporter->col('','70',null,false,'2px solid','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('From','70',null,false,'2px solid','','C','Century Gothic','10','','','');

                switch ($data['head'][0]['month']) {
                  case '1': case '2': case '3':
                    $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');

                    $str .= $this->reporter->col('(MM/DD/YY)','270',null,false,'2px solid ','R','L','Century Gothic','10','','','');

                    $str .= $this->reporter->col('03','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('31','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    break;

                  case '4': case '5': case '6':
                    $str .= $this->reporter->col('04','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');

                    //$str .= $this->reporter->col('','270',null,false,'2px solid','LR','C','Century Gothic','14','','','8px');
                    $str .= $this->reporter->col('(MM/DD/YY)','270',null,false,'2px solid ','R','L','Century Gothic','10','','','');

                    $str .= $this->reporter->col('06','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('30','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    break;

                  case '7': case '8': case '9':
                    $str .= $this->reporter->col('07','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');

                    $str .= $this->reporter->col('(MM/DD/YY)','270',null,false,'2px solid','LR','L','Century Gothic','10','','','3px');

                    $str .= $this->reporter->col('09','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('30','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    break;
                  
                  default:
                    $str .= $this->reporter->col('10','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('01','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');

                    $str .= $this->reporter->col('(MM/DD/YY)','270',null,false,'2px solid','LR','L','Century Gothic','10','','','3px');

                    $str .= $this->reporter->col('12','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col('31','10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    $str .= $this->reporter->col((isset($data['head'][0]['yr'])? $data['head'][0]['yr']:''),'10',null,false,'2px solid','LRTB','C','Century Gothic','10','B','','3px');
                    break;
                }

            $str .= $this->reporter->col('(MM/DD/YY)','270',null,false,'2px solid ','R','L','Century Gothic','10','','','');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','','C','Century Gothic','11','','','1px');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();          

            //5th row -> part 1
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Part I-Payee Information','800',null,false,'2px solid ','TLBR','C','Century Gothic','10','B','','');
                //$str .= $this->reporter->col('','750',null,false,'2px solid ','TRB','C','Century Gothic','12','B','','3px');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //6th row -> blank 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','LT','C','Century Gothic','10','','','1px');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','T','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','RT','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //7th row -> 2 tax payer
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('2','20',null,false,'2px solid ','L','C','Century Gothic','10','B','','');

                $str .= $this->reporter->col('Tax Payer Identification Number (TIN)','150',null,false,'2px solid ','','L','Century Gothic','10','','','3px');     
                $str .= $this->reporter->col((isset($data['head'][0]['tin'])? $data['head'][0]['tin']:''),'400',null,false,'2px solid','LRTB','L','Century Gothic','10','B','','3px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //blank row
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','L','C','Century Gothic','10','','','1px');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','LT','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','T','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','RT','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

           //9th row -> 3 payees name

             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('3','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col("Payee`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>" ,'610',null,false,'2px solid ','','L','Century Gothic','10','','','');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','10','','','3px');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','L','Century Gothic','10','B','','3px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //payees name box
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');

                $str .= $this->reporter->col((isset($data['head'][0]['payee'])? $data['head'][0]['payee']:''),'760',null,false,'2px solid','LRTB','L','Century Gothic','10','B','','3px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //registered address
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('4','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col("Registered Address" ,'610',null,false,'2px solid ','','L','Century Gothic','10','','','');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','10','','','3px');

                $str .= $this->reporter->col('4A','10',null,false,'2px solid','','L','Century Gothic','10','B','','');
               
                $str .= $this->reporter->col('Zipcode','10',null,false,'2px solid ','R','L','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //address name box
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
             

                $str .= $this->reporter->col((isset($data['head'][0]['address'])? $data['head'][0]['address']:''),'620',null,false,'2px solid','LRTB','L','Century Gothic','10','B','','3px');
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
                $str .= $this->reporter->col((isset($data['res'][0]['zipcode'])? $data['res'][0]['zipcode']:''),'50',null,false,'2px solid ','LRTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

          
            // 5 foreign address

             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('5','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col("Foreign Address, <i>if applicable <i/>" ,'610',null,false,'2px solid ','','L','Century Gothic','10','','','');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','10','','','3px');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','L','Century Gothic','10','B','','3px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //f address box
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
           

                $str .= $this->reporter->col('','760',null,false,'2px solid','LRTB','L','Century Gothic','10','B','','10px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
           
            //14th row -> blank 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','LB','C','Century Gothic','10','','','1px');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','B','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','RB','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //part II
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Part II-Payor Information','800',null,false,'2px solid ','TLBR','C','Century Gothic','10','B','','');
             
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //16th row -> blank 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','LT','C','Century Gothic','10','','','1px');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','T','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','RT','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //TIN payor
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('6','20',null,false,'2px solid ','L','C','Century Gothic','10','B','','');

                $str .= $this->reporter->col('Tax Payer Identification Number (TIN)','150',null,false,'2px solid ','','L','Century Gothic','10','','','3px');
             
                
                $str .= $this->reporter->col((isset($data['head'][0]['tin'])? $data['head'][0]['tin']:''),'400',null,false,'2px solid','LRTB','L','Century Gothic','10','B','','3px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //payor
           $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('7','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col("Payor`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>" ,'610',null,false,'2px solid ','','L','Century Gothic','10','','','');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','10','','','3px');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','L','Century Gothic','10','B','','3px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //Payor name box
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
              

                $str .= $this->reporter->col((isset($data['head'][0]['payee'])? $data['head'][0]['payee']:''),'760',null,false,'2px solid','LRTB','L','Century Gothic','10','B','','3px');
               
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //registered address
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('8','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col("Registered Address" ,'610',null,false,'2px solid ','','L','Century Gothic','10','','','');

                $str .= $this->reporter->col('','50',null,false,'2px solid','','C','Century Gothic','10','','','');

                $str .= $this->reporter->col('8A','10',null,false,'2px solid','','L','Century Gothic','10','B','','');
               
                $str .= $this->reporter->col('Zipcode','10',null,false,'2px solid ','R','L','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //address name box
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','30',null,false,'2px solid ','L','C','Century Gothic','10','B','','');
              

                $str .= $this->reporter->col((isset($data['head'][0]['address'])? $data['head'][0]['address']:''),'620',null,false,'2px solid','LRTB','L','Century Gothic','10','B','','3px');
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','','');
                $str .= $this->reporter->col((isset($data['res'][0]['zipcode'])? $data['res'][0]['zipcode']:''),'50',null,false,'2px solid ','LRTB','C','Century Gothic','10','B','','3px');
                $str .= $this->reporter->col('','10',null,false,'2px solid ','R','C','Century Gothic','10','','2px','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //22th row -> blank 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','125',null,false,'2px solid ','LB','C','Century Gothic','11','','','1px');
                $str .= $this->reporter->col('','180',null,false,'2px solid ','B','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','300',null,false,'2px solid','B','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','195',null,false,'2px solid ','RB','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //part III
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Part III-Details of Monthly Income Payments and Taxes Withheld','800',null,false,'2px solid ','TLBR','C','Century Gothic','10','B','','');
               
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //24th row -> income payments 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','200',null,false,'2px solid ','LRT','C','Century Gothic','11','','','3px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LRT','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS','380',null,false,'2px solid','LRTB','C','Century Gothic','11','B','','');
              
                $str .= $this->reporter->col('','140',null,false,'2px solid ','LRT','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //25th row -> month header
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax','200',null,false,'2px solid ','LR','C','Century Gothic','10','','','2px');
                $str .= $this->reporter->col('ATC','80',null,false,'2px solid ','LR','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('1st Month of the Quarter','95',null,false,'2px solid','LR','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('2nd Month of the Quarter','95',null,false,'2px solid ','LR','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('3rd Month of the Quarter','95',null,false,'2px solid ','LR','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('Total','95',null,false,'2px solid','LR','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('Tax Withheld For the Quarter','140',null,false,'2px solid ','LR','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //26th row -> blank 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','200',null,false,'2px solid ','LR','C','Century Gothic','11','','','1px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LR','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LR','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LR','C','Century Gothic','11','','','');
              
                $str .= $this->reporter->col('','140',null,false,'2px solid ','LR','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            
            //27th row -> line
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                
                $str .= $this->reporter->col('','800',null,false,'2px solid ','LTRB','C','Century Gothic','12','B','','1px');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //28th row -> atc1
           $str .= $this->reporter->begintable('800');
            
            $total =0;
              $totalwtx1 = 0;
              $totalwtx2 = 0;
              $totalwtx3 = 0;
              $totalwtx = 0;
                $a = -1;
                foreach ($data['detail'] as $key => $value) 
                {
                  $a++;
                  $str .= $this->reporter->startrow();
                    $str .= $this->reporter->col($data['res'][$a]['ewtdesc'],'200',null,false,'2px solid ','LRB','L','Century Gothic','10','','','2px');
                    $str .= $this->reporter->col($key,'80',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');

                    switch ($data['detail'][$key]['month']) 
                    {
                      case '1': case '4': case '7': case '10':
                        $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'],2),'95',null,false,'2px solid','LRB','R','Century Gothic','10','','','');
                        $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','10','','','');
                        $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','10','','','');
                        $totalwtx1 +=  $data['detail'][$key]['oamt'];
                        break;
                      case '2': case '5': case '8': case '11':
                        $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','R','Century Gothic','10','','','');
                        $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'],2),'95',null,false,'2px solid ','LRB','R','Century Gothic','10','','','');
                        $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','10','','','');
                        $totalwtx2 +=  $data['detail'][$key]['oamt'];
                        break;
                      default:
                        $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','R','Century Gothic','10','','','');
                        $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','R','Century Gothic','10','','','');
                        $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'],2),'95',null,false,'2px solid ','LRB','R','Century Gothic','10','','','');
                        $totalwtx3 +=  $data['detail'][$key]['oamt'];
                        break;
                     
                    }
                    $total=number_format($data['detail'][$key]['oamt'],2);
                    $str .= $this->reporter->col($total,'95',null,false,'2px solid','LRB','R','Century Gothic','10','','','');
                    $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'],2),'140',null,false,'2px solid ','LRB','R','Century Gothic','10','','','');
                    
                    $totalwtx += $data['detail'][$key]['oamt'];

                }
          $str .= $this->reporter->endrow();
                $str .= $this->reporter->endtable();

            
            //29th row -> total
            $str .= $this->reporter->begintable('800');
              $totaltax =0;

              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Total','200',null,false,'2px solid ','LR','L','Century Gothic','11','B','','');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LR','R','Century Gothic','11','','','');
                $str .= $this->reporter->col(($totalwtx1!=0?number_format($totalwtx1,2):''),'95',null,false,'2px solid','LR','R','Century Gothic','11','B','','');
                $str .= $this->reporter->col(($totalwtx2!=0?number_format($totalwtx2,2):''),'95',null,false,'2px solid ','LR','R','Century Gothic','11','B','','');
                $str .= $this->reporter->col(($totalwtx3!=0?number_format($totalwtx3,2):''),'95',null,false,'2px solid ','LR','R','Century Gothic','11','B','','');
                $str .= $this->reporter->col(($totalwtx!=0?number_format($totalwtx,2):''),'95',null,false,'2px solid','LR','R','Century Gothic','11','B','','');
                
                foreach ($data['detail'] as $key2 => $value2) {
                  
                  $totaltax =$totaltax + $data['detail'][$key2]['xamt'];
                  }  
                
                $str .= $this->reporter->col(number_format($totaltax,2),'140',null,false,'2px solid ','LR','R','Century Gothic','11','B','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //30th row -> space for total 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','B','','1px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
              
                $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //31th row -> money payments row
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)','200',null,false,'2px solid ','LRB','L','Century Gothic','10','','','1px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
              
                $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //32th row -> money payments row
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
              
                $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //32th row -> money payments row
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
              
                $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //32th row -> money payments row
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','200',null,false,'2px solid ','LRB','L','Century Gothic','11','','','10px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
              
                $str .= $this->reporter->col('','140',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //32th row -> money payments row
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Total','200',null,false,'2px solid ','LRB','L','Century Gothic','11','B','','1px');
                $str .= $this->reporter->col('','80',null,false,'2px solid ','LRB','L','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid ','LRB','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','95',null,false,'2px solid','LRB','C','Century Gothic','11','','','');
              
                $str .= $this->reporter->col(number_format($totaltax,2),'140',null,false,'2px solid ','LRB','R','Century Gothic','11','B','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //33th row -> declaration
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent  to the processing of our information as contemplated under  the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful  purposes.','800',null,false,'2px solid ','LRT','C','Century Gothic','10','','','3px');
               
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','LT','','Century Gothic','11','B','','1px');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','T','C','Century Gothic','11','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','T','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','T','C','Century Gothic','11','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','T','C','Century Gothic','11','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','T','C','Century Gothic','11','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','RT','C','Century Gothic','11','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //signatory from parameter
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','L','','Century Gothic','11','B','','3px');
                $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['payor']),'395',null,false,'2px solid ','','C','Century Gothic','11','B','','13px');
                $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','11','','','');
                $str .= $this->reporter->col($filters['params']['dataparams']['tin'],'175',null,false,'2px solid ','','C','Century Gothic','11','B','','13px');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','11','','','');
                $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['position']),'175',null,false,'2px solid','','C','Century Gothic','11','B','','13px');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','11','','','');
                $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //line after signatory
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','LT','','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','T','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','T','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','T','C','Century Gothic','10','B','','');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','RT','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //signatory
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent
            <br/>(Indicate Title/Designation and TIN)','800',null,false,'2px solid ','LRB','C','Century Gothic','10','','','');

              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //TAX Agent
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','10','B','','1px');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','10','','','');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //39th row -> signature line 1
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
              Attorney`s Roll No. (if applicable)','150',null,false,'2px solid ','L','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','5',null,false,'2px solid ','','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','120',null,false,'2px solid','LRTB','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)','10',null,false,'2px solid ','','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid ','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','30',null,false,'2px solid','LRTB','C','Century Gothic','10','','','');
              
                $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)','10',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid ','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','30',null,false,'2px solid','LRTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

                    
            //42th row -> blank space after authorized signature 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','LB','L','Century Gothic','10','B','','1px');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','B','C','Century Gothic','10','','','');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','RB','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //43th row -> space after declaration
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('CONFORME:','800',null,false,'2px solid ','TLBR','C','Century Gothic','10','B','','');
           
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','LT','','Century Gothic','10','B','','1px');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','T','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','T','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','T','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','RT','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //signatory from parameter
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','L','','Century Gothic','10','B','','3px');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','','C','Century Gothic','10','B','','13px');
                $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','10','B','','13px');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','10','B','','13px');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','10','','','');
                $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //line after signatory
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','LT','','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','T','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','T','C','Century Gothic','10','B','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','T','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','T','C','Century Gothic','10','B','','');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','RT','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //signatory
             $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent
            <br/>(Indicate Title/Designation and TIN)','800',null,false,'2px solid ','LRB','C','Century Gothic','10','','','');
        
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

            //TAX Agent
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','L','L','Century Gothic','10','B','','1px');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','','C','Century Gothic','10','','','');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //39th row -> signature line 1
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
                Attorney`s Roll No. (if applicable)','150',null,false,'2px solid ','L','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','5',null,false,'2px solid ','','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','120',null,false,'2px solid','LRTB','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)','10',null,false,'2px solid ','','L','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid ','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','30',null,false,'2px solid','LRTB','C','Century Gothic','10','','','');
              
                $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)','10',null,false,'2px solid ','','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid ','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','20',null,false,'2px solid','LTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','30',null,false,'2px solid','LRTB','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','R','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();


            //52th row -> blank space after authorized signature 
            $str .= $this->reporter->begintable('800');
              $str .= $this->reporter->startrow();
                $str .= $this->reporter->col('','10',null,false,'2px solid ','LB','L','Century Gothic','10','B','','1px');
                $str .= $this->reporter->col('','395',null,false,'2px solid ','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid ','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','15',null,false,'2px solid ','B','C','Century Gothic','10','','','');
                $str .= $this->reporter->col('','175',null,false,'2px solid','B','C','Century Gothic','10','','','');
              
                $str .= $this->reporter->col('','15',null,false,'2px solid ','RB','C','Century Gothic','10','','','');
              $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();

          $str .= $this->reporter->endreport();
          return $str;
  }

  public function ftNumberToWordsConverter($number) {
    $numberwords = $this->ftNumberToWordsBuilder($number);

    if(strpos($numberwords,"/") == false){
      $numberwords .= " PESOS ";
    }else{
      $numberwords = str_replace(" AND ", " PESOS AND " , $numberwords);
    }//end if

    return $numberwords;
  }//end function convert to words

  public function ftNumberToWordsBuilder($number){
    if($number == 0){
          return 'Zero';

        }else{
            $hyphen      = ' ';
            $conjunction = ' ';
            $separator   = ' ';
            $negative    = 'negative ';
            $decimal     = ' and ';
            $dictionary  = array(
                0                   => '',
                1                   => 'One',
                2                   => 'Two',
                3                   => 'Three',
                4                   => 'Four',
                5                   => 'Five',
                6                   => 'Six',
                7                   => 'Seven',
                8                   => 'Eight',
                9                   => 'Nine',
                10                  => 'Ten',
                11                  => 'Eleven',
                12                  => 'Twelve',
                13                  => 'Thirteen',
                14                  => 'Fourteen',
                15                  => 'Fifteen',
                16                  => 'Sixteen',
                17                  => 'Seventeen',
                18                  => 'Eighteen',
                19                  => 'Nineteen',
                20                  => 'Twenty',
                30                  => 'Thirty',
                40                  => 'Forty',
                50                  => 'Fifty',
                60                  => 'Sixty',
                70                  => 'Seventy',
                80                  => 'Eighty',
                90                  => 'Ninety',
                100                 => 'Hundred',
                1000                => 'Thousand',
                1000000             => 'Million',
                1000000000          => 'Billion',
                1000000000000       => 'Trillion',
                1000000000000000    => 'Quadrillion',
                1000000000000000000 => 'Quintillion');
     
            if (!is_numeric($number)) {
                return false;
            }//end if
     
            if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
                // overflow
                return false;
            }//end if

            if ($number < 0) {
                return $negative . $this->ftNumberToWordsBuilder(abs($number));
            }//end if
     
            $string = $fraction = null;
     
            if (strpos($number, '.') !== false) {
                $fractionvalues = explode('.', $number);
                if($fractionvalues[1] != '00' || $fractionvalues[1] != '0'){
                    list($number, $fraction) = explode('.', $number);
                }//end if
            }//end if
            
            switch (true) {
                case $number < 21:
                    $string = $dictionary[$number];
                    break;

                case $number < 100:
                    $tens   = ((int) ($number / 10)) * 10;
                    $units  = $number % 10;
                    $string = $dictionary[$tens];
                    if ($units) {
                        $string .= $hyphen . $dictionary[$units];
                    }//end if
                    break;

                case $number < 1000:
                    $hundreds  = $number / 100;
                    $remainder = $number % 100;
                    $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                    if ($remainder) {
                        $string .= $conjunction . $this->ftNumberToWordsBuilder($remainder);
                    }//end if
                    break;

                default:
                    $baseUnit = pow(1000, floor(log($number, 1000)));
                    $numBaseUnits = (int) ($number / $baseUnit);
                    $remainder = $number % $baseUnit;
                    $string = $this->ftNumberToWordsBuilder($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                    if ($remainder) {
                        $string .= $remainder < 100 ? $conjunction : $separator;
                        $string .= $this->ftNumberToWordsBuilder($remainder);
                    }//end if
                    break;
            }//end switch
          if (null !== $fraction && is_numeric($fraction)) {
              
              $string .= $decimal . ' ' . $fraction .  '/100';
              $words = array();
              $string .= implode(' ', $words);
          }//end if

          return strtoupper($string);                
      }//end
  }//end fn

}
