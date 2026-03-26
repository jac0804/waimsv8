<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class leloan
{
  private $fieldClass;
  private $tabClass;
  private $othersClass;
  public $modulename = 'Loan';
  public $tablenum = 'transnum';
  public $gridname = 'tableentry';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  private $companysetup;
  
  private $coreFunctions;
  private $logger;
  public $style = 'width:1500px;max-width:1500px;';
  public $issearchshow = true;
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function createTab($config)
  {
    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'leloangridlogs', 'label' => 'Loan Grid']];
    $obj = $this->tabClass->createtab($tab, []);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function createHeadField($config)
  {
    $doc = $config['params']['doc'];
    $isposted = $this->othersClass->isposted($config);

    $fields = [['intannum','interest'],'pf','nf','amortization','penalty','voidint'];

    $tblname = 'eahead';
    if($isposted){
      $tblname ='heahead';
    }
    $planid =$this->coreFunctions->getfieldvalue($tblname,"planid","trno = ?",[$config['params']['trno']]);
    $this->coreFunctions->LogConsole($planid.'----');
    $isdiminish = $this->coreFunctions->getfieldvalue("reqcategory","isdiminishing","line = ?",[$planid],'',true);
    $this->coreFunctions->LogConsole($isdiminish.'----');
    if($isdiminish ==1){
      $fields = [['intannum','interest'],'amortization','penalty','voidint'];
    }
    
    
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'penalty.readonly', false);
    data_set($col1, 'penalty.label', 'Penalty(%)');
    data_set($col1, 'interest.label', 'Interest(%)');

    if($isposted || $doc=='LA'){
      data_set($col1, 'interest.readonly', true);
      data_set($col1, 'pf.readonly', true);
      data_set($col1, 'nf.readonly', true);
      data_set($col1, 'amortization.readonly', true);
      data_set($col1, 'penalty.readonly', true);
      data_set($col1, 'intannum.readonly', true);
      data_set($col1, 'voidint.readonly', true);
    }
    
    $fields = ['update','lblsource','fmons','fannum','frate'];//, 'refresh'
    if($isposted || $doc=='LA'){
      $fields = ['lblsource','fmons','fannum','frate'];
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'update.label', 'Save');
    data_set($col2, 'refresh.label', 'Generate');
    data_set($col2, 'refresh.action', 'compute');
    data_set($col2, 'refresh.action', 'compute');
    data_set($col2, 'lblsource.label', 'Housing Loan Factors');
   
    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $qry = $this->get_head($config);
    return $this->coreFunctions->opentable($qry);
  }

  public function get_head($config)
  {
    $doc = $config['params']['doc'];
    $trno = (isset($config['params']['trno'])) ? $config['params']['trno'] : $config['params']['dataparams']['trno'] ;
    $filter = "";
    $field = "";

    if($doc=='LA'){
      $field = "'$trno' as cptrno,info.trno as trno,";
      $source1 = "
      from lahead as cp
      left join heahead as head on cp.aftrno = head.trno
      left join heainfo as info on head.trno = info.trno
      left join terms on terms.terms = head.terms";
      $source2 = "
      from glhead as cp
      left join heahead as head on cp.aftrno = head.trno
      left join heainfo as info on head.trno = info.trno
      left join terms on terms.terms = head.terms";
      $filter = "and cp.trno=$trno";
    }else{
      $field = "'$trno' as trno,info.fmons,info.fannum,info.frate,";
      $source1 = "
      from eainfo as info
      left join eahead as head on head.trno=info.trno 
      left join terms on terms.terms = head.terms";
      $source2 = "
      from heainfo as info
      left join heahead as head on head.trno=info.trno 
      left join terms on terms.terms = head.terms";
      $filter = "and info.trno=$trno";
    }

    $qry = "select $field format(head.interest,2) as interest,
    format(info.pf,2) as pf,
    format(info.nf,2) as nf,
    format(info.amortization,2) as amortization,
    format(info.penalty,2) as penalty,info.amount,terms.days as terms,head.planid,format(info.intannum,2) as intannum,info.voidint,format(info.mri,4) as mri,format(info.docstamp,4) as dst
    $source1
    where 1=1 $filter
    union all
    select $field format(head.interest,2) as interest,
    format(info.pf,2) as pf,
    format(info.nf,2) as nf,
    format(info.amortization,2) as amortization,
    format(info.penalty,2) as penalty,info.amount,terms.days as terms,head.planid,format(info.intannum,2) as intannum,info.voidint,format(info.mri,4) as mri,format(info.docstamp,4) as dst
    $source2
    where 1=1 $filter";
    return $qry;
    
  }

  public function data($config)
  {
    $doc = $config['params']['doc'];
    if($doc == 'LA'){
      $cptrno = (isset($config['params']['trno'])) ? $config['params']['trno'] : $config['params']['dataparams']['trno'];
      $qry = "select aftrno as value from lahead where trno=$cptrno
      union all
      select aftrno as value from glhead where trno=$cptrno";
      $trno = $this->coreFunctions->datareader($qry);
    }else{
      $trno = (isset($config['params']['trno'])) ? $config['params']['trno'] : $config['params']['dataparams']['trno'];
    }
    
    $qry = "select * from (select line,format(interest,2) as interest,format(principal,2) as principal,format(pfnf,2) as pfnf,format(nf,2) as nf,format(principal+interest+pfnf+nf+dst+mri,2) as ext,format(bal,2) as bal,format(dst,2) as dst ,format(mri,2) as mri  from tempdetailinfo where trno =  " . $trno .
    " union all
    select line,format(interest,2) as interest,format(principal,2) as principal,format(pfnf,2) as pfnf,format(nf,2) as nf,format(principal+interest+pfnf+nf+dst+mri,2) as ext,format(bal,2) as bal,format(dst,2) as dst ,format(mri,2) as mri from htempdetailinfo where trno =  " . $trno.") as a order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function loaddata($config)
  {
    $button = $config['params']['action2'];
    $trno = $config['params']['dataparams']['trno'];
    $head = [];
    $info = [];
    $head['interest'] = $config['params']['dataparams']['interest'];
    $info['pf'] = $config['params']['dataparams']['pf'];
    $info['nf'] = $config['params']['dataparams']['nf'];
    $info['amortization'] = $config['params']['dataparams']['amortization'];
    $info['penalty'] = $config['params']['dataparams']['penalty'];
    $info['intannum'] = $config['params']['dataparams']['intannum'];
    $info['voidint'] = $config['params']['dataparams']['voidint'];
    $loanamt = $config['params']['dataparams']['amount'];
    
    switch ($button){
      case 'update':        
        $info['pf'] = $this->othersClass->sanitizekeyfield('amt', $config['params']['dataparams']['pf']);
        $info['amortization'] = $this->othersClass->sanitizekeyfield('amt',$config['params']['dataparams']['amortization']);
        $info['penalty'] = $this->othersClass->sanitizekeyfield('amt',$config['params']['dataparams']['penalty']);
        $info['nf'] = $this->othersClass->sanitizekeyfield('amt',$config['params']['dataparams']['nf']);
        
        if($info['intannum']!=0){
          $head['interest'] = $info['intannum']/12;
        }

        $head['interest'] = $this->othersClass->sanitizekeyfield('amt',$head['interest']);
        $info['intannum'] = $this->othersClass->sanitizekeyfield('amt',$config['params']['dataparams']['intannum']);

        $info['voidint'] = $this->othersClass->sanitizekeyfield('qty',$config['params']['dataparams']['voidint']);

        $this->coreFunctions->sbcupdate('eahead', $head, ['trno' => $trno]);
        $this->coreFunctions->sbcupdate('eainfo', $info, ['trno' => $trno]);

        return $this->compute($config);
        break;
      case 'compute':
        return $this->compute($config);
       
        break;
    }
  } //end function

  private function compute($config){
    $trno = $config['params']['dataparams']['trno'];
    $head = [];
    $info = [];
    $head['interest'] = $config['params']['dataparams']['interest'];
    $info['pf'] = $config['params']['dataparams']['pf'];
    $info['nf'] = $config['params']['dataparams']['nf'];
    $info['amortization'] = $config['params']['dataparams']['amortization'];
    $info['penalty'] = $config['params']['dataparams']['penalty'];
    $info['intannum'] = $config['params']['dataparams']['intannum'];
    $info['voidint'] = $config['params']['dataparams']['voidint'];
    $loanamt = $config['params']['dataparams']['amount'];
    $isdiminish = $this->coreFunctions->getfieldvalue("reqcategory","isdiminishing","line = ?",[$config['params']['dataparams']['planid']],'',true);
    $balmons =  $config['params']['dataparams']['terms'];
    $rdate = strtotime($this->coreFunctions->getfieldvalue("eahead","dateid","trno=?",[$trno]));
    $pdate = date("Y-m-d",$rdate);
    $msg = 'Computed Successfully';
      $dinfo =[];
      $di=[];
      if($loanamt != 0){
        if($isdiminish == 0){
          $freeintmos = 0;  
          $interest = $loanamt * ($config['params']['dataparams']['interest']/100);
          $totint = $interest *  $config['params']['dataparams']['terms'];
          if($config['params']['dataparams']['voidint'] !=0){
            $freeintmos = $config['params']['dataparams']['voidint'];
            $balmons = $balmons + $freeintmos;
            $interest = $totint / ($config['params']['dataparams']['terms']+$freeintmos);
          }
          
          $pf = $config['params']['dataparams']['pf'];
          $nf = $config['params']['dataparams']['nf'];
          $dst = $config['params']['dataparams']['dst'];//info.docstamp monthly
          $mri = $config['params']['dataparams']['mri'];
          $totdst = round($dst * $config['params']['dataparams']['terms'],2);
          $totmri = round($mri * $config['params']['dataparams']['terms'],2);
          $totpf = $pf * $config['params']['dataparams']['terms'];
          $totnf = $nf * $config['params']['dataparams']['terms'];

          $principal = $loanamt / ($config['params']['dataparams']['terms']+$freeintmos);
          $amort = $principal+$interest+$pf+$nf+$dst+$mri;

          $info['amortization'] = $this->othersClass->sanitizekeyfield("amt", $amort);
          $this->coreFunctions->sbcupdate('eainfo', ['amortization'=>round($amort,2)], ['trno' => $trno]);         
                    
          $loanproc = 0;
          $tmri =0;
          $tdst=0;
          $tprincipal =0;
          $tinterest =0;
          $tpf=0;
          $tnf =0;
          $atmri =0;
          $atdst=0;
          $atprincipal =0;
          $atinterest =0;
          $atpf=0;
          $atnf =0;
         
          for ($y = 1; $y <= $balmons; $y++) {
            // if($y == $balmons){              
            //   $atinterest = $totint - ($tinterest + $interest);
            //   if($atinterest !=0){
            //     $this->coreFunctions->LogConsole($atinterest.'interest diff');
            //     $interest = $interest + $atinterest;
            //   }


            //   $atprincipal = $loanamt - ($tprincipal + $principal);
            //   if($atprincipal !=0){
            //     $this->coreFunctions->LogConsole($atprincipal);
            //     $principal = $principal + $atprincipal;
            //   }

            //   $atdst = $totdst - ($tdst + $dst);
            //   $this->coreFunctions->LogConsole($atdst.'dst diff'.$totdst.'total'.$tdst.'dst:'.$dst);
            //   if($atdst !=0){
            //     $this->coreFunctions->LogConsole($atdst.'dst diff'.$totdst.'total'.$tdst.'dst:'.$dst);
            //     $dst = $dst + $atdst;
            //     $this->coreFunctions->LogConsole($atdst.'dst diff'.$totdst.'total'.$tdst.'dst:'.$dst);
            //   }

            //   $atmri = $totmri - ($tmri + $mri);
            //   if($atmri !=0){
            //     $mri = $mri + $atmri;
            //   }

            //   $atpf = $totpf - ($tpf + $pf);
            //   if($atpf !=0){
            //     $pf = $pf + $atpf;
            //   }

            //   $atnf = $totnf - ($tnf + $nf);
            //   if($atnf !=0){
            //     $nf = $nf + $atnf;
            //   }


            // }            

            $di['trno'] = $trno;
            $di['line'] = $y;
            $di['interest'] = $interest;
            $di['principal'] = $principal;
            $di['pfnf'] = $pf;
            $di['nf'] = $nf;
            $di['dst'] = $dst;
            $di['mri'] = $mri;
            $di['dateid'] = $pdate;

            $tmri += round($mri,2);
            $tdst += round($dst,2);
            $tprincipal += $principal;
            $tinterest += $interest;
            $tpf += $pf;
            $tnf += $nf;
            
            $pdate = date("Y-m-d", strtotime("+$y month", $rdate));
            array_push($dinfo, $di);
          }
          $this->logger->sbcdel_log($trno, $config, 'LE','LOANCOMP');
          $this->coreFunctions->execqry("delete from tempdetailinfo where trno = " . $trno);
          $current_timestamp = $this->othersClass->getCurrentTimeStamp();
          foreach ($dinfo as $key => $value) {
            foreach ($value as $key2 => $value2) {
              $dinfo[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
            }
            $dinfo[$key]['editdate'] = $current_timestamp;
            $dinfo[$key]['editby'] = $config['params']['user'];
            $dinfo[$key]['interest'] = round($dinfo[$key]['interest'], 2);
            $dinfo[$key]['principal'] = round($dinfo[$key]['principal'], 2);
            $dinfo[$key]['pfnf'] = round($dinfo[$key]['pfnf'], 2);
            $dinfo[$key]['nf'] = round($dinfo[$key]['nf'], 2);
            $dinfo[$key]['dst'] = round($dinfo[$key]['dst'], 2);
            $dinfo[$key]['mri'] = round($dinfo[$key]['mri'], 2);
          }
          
          $this->logger->sbcwritelog($trno, $config, 'LOANCOMP', 'interest: '.$dinfo[$key]['interest'].', '.'principal: '.$dinfo[$key]['principal'].', '.'pfnf: '.$dinfo[$key]['pfnf'].', '.'nf: '.$dinfo[$key]['nf'].', '.'dst: '.$dinfo[$key]['dst'].', '.'mri: '.$dinfo[$key]['mri']);
          if (!$this->coreFunctions->sbcinsert('tempdetailinfo', $dinfo)) {
            $status = false;
            $msg = 'Error in Creating Loan Schedule';
          }
        }else{
          //if interest rate is per annum use formula below
          if($config['params']['dataparams']['intannum'] !=0){
            $intrate = $config['params']['dataparams']['intannum']/12;          
            $fmons = ($intrate/100)*pow((1+($intrate/100)),$balmons);
            $fannum = (pow((1+($intrate/100)),$balmons))-1;
            $frate = $fmons/$fannum;
            $amort = $frate*$loanamt;
          }else{
            return ['status' => false, 'msg' => 'Enter interest per annum', 'txtdata' => [],'data' => []];
            //if interest rate is already per month use formula below
            // $fannum = $config['params']['dataparams']['interest']*12;
            // $fmons = ($config['params']['dataparams']['interest']/100)*pow((1+($config['params']['dataparams']['interest']/100)),$balmons);
            // $fannum = (pow((1+($config['params']['dataparams']['interest']/100)),$balmons))-1;
          }
      
          $this->coreFunctions->sbcupdate('eainfo', ['fmons'=>round($fmons,6),'fannum'=>round($fannum,6),'frate'=>round($frate,6),'amortization'=>round($amort,2)], ['trno' => $trno]); 
          $loanbal = $loanamt;
          for ($y = 1; $y <= $balmons; $y++) {
            $di['trno'] = $trno;
            $di['line'] = $y;
            $interest = $loanbal*($intrate/100);
            $principal = $amort - round($interest,2);
            $this->coreFunctions->LogConsole('int:'.$interest);
            $this->coreFunctions->LogConsole('prin:'.$principal);
            $di['interest'] = $interest;
            $di['principal'] = $principal;
            $di['pfnf'] = 0;
            $di['nf'] = 0;
            $di['dateid'] = $pdate;            
            $di['bal'] = $loanbal - $principal;
            
            $pdate = date("Y-m-d", strtotime("+$y month", $rdate));
            array_push($dinfo, $di);
            $loanbal = $loanbal - $principal;
          }
          
          $this->logger->sbcdel_log($trno, $config, 'LE','LOANCOMP');
          $this->coreFunctions->execqry("delete from tempdetailinfo where trno = " . $trno);
          $current_timestamp = $this->othersClass->getCurrentTimeStamp();
          foreach ($dinfo as $key => $value) {
            foreach ($value as $key2 => $value2) {
              $dinfo[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
            }
            $dinfo[$key]['editdate'] = $current_timestamp;
            $dinfo[$key]['editby'] = $config['params']['user'];
            $dinfo[$key]['interest'] = round($dinfo[$key]['interest'], 2);
            $dinfo[$key]['principal'] = round($dinfo[$key]['principal'], 2);
            $dinfo[$key]['pfnf'] = round($dinfo[$key]['pfnf'], 2);
            $dinfo[$key]['nf'] = round($dinfo[$key]['nf'], 2);
            $dinfo[$key]['bal'] = round($dinfo[$key]['bal'], 2);
          }
          
          $this->logger->sbcwritelog($trno, $config, 'LOANCOMP', 'interest: '.$dinfo[$key]['interest'].', '.'principal: '.$dinfo[$key]['principal'].', '.'pfnf: '.$dinfo[$key]['pfnf'].', '.'nf: '.$dinfo[$key]['nf'].', '.'dst: '.$dinfo[$key]['dst'].', '.'mri: '.$dinfo[$key]['mri']);
          if (!$this->coreFunctions->sbcinsert('tempdetailinfo', $dinfo)) {
            $status = false;
            $msg = 'Error in Creating Loan Schedule';
          }

        }
        
      }else{
        $msg = 'Loan amount is zero';
      }

      $qry = $this->get_head($config);
      $txtdata = $this->coreFunctions->opentable($qry);

      $data = $this->data($config);

      return ['status' => true, 'msg' => $msg, 'txtdata' => $txtdata,'data' => $data,'reloadtableentry'=>true];
  }





  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      
      case 'lookuplogs':
        return $this->lookuplogs($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup'];
        break;
    }
  }

  
  public function lookuplogs($config)
  {
    $main_doc = $config['params']['doc'];
    $doc = strtoupper("PX");
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'List of Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    if ($main_doc == "CUSTOMER" || $main_doc == "FINANCINGPARTNER") {
      $qry = "
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from " . $this->tablelogs . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno2 = '" . $trno . "' OR log.trno = '" . $trno . "'
      union all
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from  " . $this->tablelogs_del . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno2 = '" . $trno . "' OR log.trno = '" . $trno . "'";

      $qry = $qry . " order by dateid desc";
    } else {
      $qry = "
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from " . $this->tablelogs . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
      union all
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from  " . $this->tablelogs_del . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

      $qry = $qry . " order by dateid desc";
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, $qry];
  }

} //end class
