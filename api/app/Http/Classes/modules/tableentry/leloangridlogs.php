<?php

namespace App\Http\Classes\modules\tableentry;

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

class leloangridlogs
{
  private $fieldClass;
  private $tabClass;
  private $othersClass;
  public $modulename = 'Loan';
  public $tablenum = 'transnum';
  public $gridname = 'inventory';
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
  }

  
  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }


  public function createTab($config)
  {

    $gridcolumns = ['principal', 'interest','pfnf', 'nf','dst','mri', 'ext','bal'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => $gridcolumns
      ]
    ];


    foreach ($gridcolumns as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['totalfield'] = [];
    $obj[0][$this->gridname]['columns'][$interest]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$principal]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$pfnf]['label'] = 'PF';
    $obj[0][$this->gridname]['columns'][$ext]['label'] = 'Amortization';
    $obj[0][$this->gridname]['columns'][$bal]['label'] = 'Running Balance';
    $obj[0][$this->gridname]['columns'][$pfnf]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$nf]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$ext]['align'] = 'text-right';
    $obj[0][$this->gridname]['columns'][$interest]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$principal]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$pfnf]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$nf]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$ext]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$dst]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';
    $obj[0][$this->gridname]['columns'][$mri]['style'] = 'text-align:right;width:90px;whiteSpace: normal;min-width:90px;';

    $obj[0][$this->gridname]['columns'][$interest]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$principal]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$pfnf]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$nf]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$dst]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$mri]['type'] = 'label';

    // $trno = $config['params']['trno'];
    $trno = $config['params']['tableid'];
    $tblname = 'eahead';
    $isposted = $this->othersClass->isposted2($trno,"transnum");
    if($isposted){
      $tblname ='heahead';
    }
    $planid =$this->coreFunctions->getfieldvalue($tblname,"planid","trno = ?",[$trno]);
    $this->coreFunctions->LogConsole($planid.'----');
    $isdiminish = $this->coreFunctions->getfieldvalue("reqcategory","isdiminishing","line = ?",[$planid],'',true);
    $this->coreFunctions->LogConsole($isdiminish.'----');
    if($isdiminish ==0){
      $obj[0][$this->gridname]['columns'][$bal]['type']='coldel';
    }else{      
      $obj[0][$this->gridname]['columns'][$dst]['type']='coldel';
      $obj[0][$this->gridname]['columns'][$bal]['type']='coldel';
    }
    
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['masterfilelogs'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }


  public function get_head($config)
  {
    $doc = $config['params']['doc'];
    $trno = (isset($config['params']['trno'])) ? $config['params']['trno'] : $config['params']['ledgerdata']['trno'] ;
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
    format(info.penalty,2) as penalty,info.amount,terms.days as terms,head.planid,format(info.intannum,2) as intannum,info.voidint,format(info.mri,2) as mri,format(info.docstamp,2) as dst
    $source1
    where 1=1 $filter
    union all
    select $field format(head.interest,2) as interest,
    format(info.pf,2) as pf,
    format(info.nf,2) as nf,
    format(info.amortization,2) as amortization,
    format(info.penalty,2) as penalty,info.amount,terms.days as terms,head.planid,format(info.intannum,2) as intannum,info.voidint,format(info.mri,2) as mri,format(info.docstamp,2) as dst
    $source2
    where 1=1 $filter";
    return $qry;
    
  }

  public function data($config)
  {
    $doc = $config['params']['doc'];
    if($doc == 'LA'){
      $cptrno = (isset($config['params']['trno'])) ? $config['params']['trno'] : $config['params']['ledgerdata']['trno'];
      $qry = "select aftrno as value from lahead where trno=$cptrno
      union all
      select aftrno as value from glhead where trno=$cptrno";
      $trno = $this->coreFunctions->datareader($qry);
    }else{
      $trno = (isset($config['params']['trno'])) ? $config['params']['trno'] : $config['params']['ledgerdata']['trno'];
    }
    
    $qry = "select * from (select line,format(interest,2) as interest,format(principal,2) as principal,format(pfnf,2) as pfnf,format(nf,2) as nf,format(principal+interest+pfnf+nf+dst+mri,2) as ext,format(bal,2) as bal,format(dst,2) as dst ,format(mri,2) as mri  from tempdetailinfo where trno =  " . $trno .
    " union all
    select line,format(interest,2) as interest,format(principal,2) as principal,format(pfnf,2) as pfnf,format(nf,2) as nf,format(principal+interest+pfnf+nf+dst+mri,2) as ext,format(bal,2) as bal,format(dst,2) as dst ,format(mri,2) as mri from htempdetailinfo where trno =  " . $trno.") as a order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function loaddata($config)
  {
    
      $data = $this->data($config);

      return $data;
  } //end function





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
      array('name' => 'userid', 'label' => 'User', 'align' => 'left', 'field' => 'userid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'oldversion', 'label' => 'Old Version', 'align' => 'left', 'field' => 'oldversion', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'field', 'label' => 'Field', 'align' => 'left', 'field' => 'field', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, field, oldversion, log.userid, dateid,
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.userid
    where log.trno = '" . $trno . "' and log.field = 'LOANCOMP'
    union all
    select trno, field, '' as oldversion, log.userid, dateid,
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.userid
    where log.trno = '" . $trno . "' and log.field = 'LOANCOMP'";

    $qry = $qry . " order by dateid desc";
    

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, $qry];
  }


























} //end class
