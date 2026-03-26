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
use App\Http\Classes\SBCPDF;

class entrypcfexpenses
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'EXPENSES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'pxchecking';
  private $htable = 'hpxchecking';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['trno', 'line', 'expenseid', 'budget', 'actual','rem'];
  public $showclosebtn = false;
  private $reporter;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
    $this->logger = new Logger;
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
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 5379);
    $pcfadmin = $this->othersClass->checkAccess($config['params']['user'], 5389);
    $companyid = $config['params']['companyid'];
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $column =['action', 'expensename', 'budget', 'actual', 'rem'];

    if($doc =='SJ'){
      $isposted = $this->othersClass->isposted2($tableid,'cntnum');
    }else{
      $isposted = $this->othersClass->isposted2($tableid,'transnum');
    }    

    $tab = [
            $this->gridname => [
              'gridcolumns' => $column
            ]
          ];

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['save', 'delete'];

    if($isposted){
      $stockbuttons = [];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$expensename]['style'] = "width:100px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$budget]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$actual]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";
    $obj[0][$this->gridname]['columns'][$expensename]['readonly'] = true;

    if($isposted){
            //unset($obj[0][$this->gridname]['columns'][$action]['btns']['delete']);      
      $obj[0][$this->gridname]['columns'][$budget]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$actual]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
    }

    if($doc == 'SJ'){
      $obj[0][$this->gridname]['columns'][$expensename]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$budget]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$actual]['readonly'] = false;
      $obj[0][$this->gridname]['columns'][$rem]['readonly'] = false;
    }

    if ($pcfadmin == 0) {      
      if($doc <> 'SJ'){
        $obj[0][$this->gridname]['columns'][$actual]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = false;
      }else{
        $obj[0][$this->gridname]['columns'][$actual]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
        $obj[0][$this->gridname]['columns'][$action]['type'] = "coldel";
      }
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $allow_update = $this->othersClass->checkAccess($config['params']['user'], 4876);
    $doc = $config['params']['doc'];
    $tableid = $config['params']['tableid'];

    $tbuttons = ['addexpense', 'saveallentry', 'masterfilelogs'];
    

    if($doc =='SJ'){
      $tbuttons = ['saveallentry','masterfilelogs'];
    }else{
      $isposted = $this->othersClass->isposted2($tableid,'transnum');
      if($isposted){
        $tbuttons = ['masterfilelogs'];
      }
    }
    
    $obj = $this->tabClass->createtabbutton($tbuttons);
   
    return $obj;
  }


  public function add($config)
  {
    $data = [];
    $data['trno'] = $config['params']['tableid'];
    $data['line'] = 0;
    $data['budget'] = 0;
    $data['actual'] = 0;
    $data['rem'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function saveallentry($config)
  {
    $doc = $config['params']['doc'];
    if($doc == 'SJ'){
      return $this->saveallsj($config);
    }else{
      return $this->saveallpcf($config);
    }

    
  } // end function

  private function saveallpcf($config){
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($tableid,'transnum');
    $table = $this->table;

    if($isposted){
      $table = $this->htable;
    }   

    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        
        if ($data[$key]['line'] == 0) {
          $qry = "select line as value from pxchecking where trno=? order by line desc limit 1";
          $line = $this->coreFunctions->datareader($qry, [$tableid]);
          if ($line == '') {
            $line = 0;
          }
          $line = $line + 1;
          $data2['line'] = $line;
          if($data[$key]['actual'] == 0){
            $data2['actual'] = $data2['budget'];
          }
          $insert = $this->coreFunctions->sbcinsert($table, $data2);
          $this->logger->sbcmasterlog($tableid, $config, ' ADD - ' . $data[$key]['expensename']. '~BUDGET: ' . $data[$key]['budget']. '~ACTUAL: ' . $data[$key]['actual']. '~REMARKS:' . $data[$key]['rem']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $data2['actual'] = $data2['budget'];
          // $this->othersClass->logConsole($data2);
          unset($data2['trno']);
          unset($data2['line']);
          // $this->othersClass->logConsole($data2);
          $this->coreFunctions->sbcupdate($table, $data2, ['trno'=>$data[$key]['trno'],'line' => $data[$key]['line']]);
          $this->logger->sbcmasterlog($tableid, $config, ' Update - ' . $data[$key]['expensename']. '~ BUDGET: ' . $data[$key]['budget']. '~ACTUAL: ' . $data[$key]['actual']. '~REMARKS: ' . $data[$key]['rem']);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  }

  private function saveallsj($config){
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $table = "sjexp";

    foreach ($data as $key => $value) {
      $data2 = [];
      $d=[];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

          $d['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $d['editby'] = $config['params']['user'];
          $d['amount'] = $data2['actual'];
          $d['rem'] = $data2['rem'];

          $this->coreFunctions->sbcupdate($table, $d, ['trno'=>$tableid,'pxtrno'=>$data[$key]['trno'],'pxline' => $data[$key]['line']]);

          $exist = $this->coreFunctions->datareader("select trno as value from delstatus where trno = ?",[$tableid],'',true);
          if($exist){
            $delcharge = $this->coreFunctions->datareader("select ifnull(sum(amount),0) as value from sjexp where trno = ?",[$tableid],'',true);
            $this->coreFunctions->sbcupdate("delstatus",["delcharge" => $delcharge,'editby' =>  $config['params']['user'],'editdate'=> $this->othersClass->getCurrentTimeStamp()],["trno" => $tableid]); 
          }else{
            $delcharge = $this->coreFunctions->datareader("select ifnull(sum(amount),0) as value from sjexp where trno = ?",[$tableid],'',true);
            if($delcharge!=0){
              $this->coreFunctions->sbcinsert("delstatus",["trno"=>$tableid,"delcharge" => $delcharge]); 
            }
          }

          $this->logger->sbcmasterlog($tableid, $config, ' Update - ' . $data[$key]['expensename']. '~ACTUAL: ' . $data[$key]['actual']. '~REMARKS: ' . $data[$key]['rem']);
        
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  }

  public function save($config)
  {
    $doc = $config['params']['doc'];

    if($doc =='SJ'){
      return $this->savesj($config);
    }else{
      return $this->savepcf($config);
    }
    
  } //end function

  private function savepcf($config){
    $data = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    
    if($config['params']['doc'] == 'SJ'){
      $tableid = $row['trno'];
    }

    $isposted = $this->othersClass->isposted2($tableid,'transnum');
    $table = $this->table;

    if($isposted){
      $table = $this->htable;
    }

    if ($row['line'] == 0) {
        $qry = "select line as value from $table where trno=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$tableid]);
        if ($line == '') {
        $line = 0;
        }
        $line = $line + 1;
        $data['line'] = $line;
        $data['actual'] = $data['budget'];
        $insert = $this->coreFunctions->sbcinsert($table, $data);
      if ($insert != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);

        $this->logger->sbcmasterlog($tableid, $config, ' ADD - ' . $row['expensename']. '~BUDGET: ' . $row['budget']. '~ACTUAL: ' . $row['actual']. '~REMARKS: ' . $row['rem']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $data['actual'] = $data['budget'];
      if ($this->coreFunctions->sbcupdate($table, $data, ['trno'=>$tableid,'line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        $this->logger->sbcmasterlog($tableid, $config, ' Update - ' . $row['expensename']. '~BUDGET: ' . $row['budget']. '~ACTUAL: ' . $row['actual']. '~REMARKS: ' . $row['rem']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  }

  private function savesj($config){
    $data = [];
    $d = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
  
    $table ="sjexp";
    $d['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $d['editby'] = $config['params']['user'];
    $d['amount'] = $data['actual'];
    $d['rem'] = $data['rem'];
    
    if ($this->coreFunctions->sbcupdate($table, $d, ['trno'=>$tableid,'pxtrno' => $row['trno'],'pxline' => $row['line']]) == 1) {
      $exist = $this->coreFunctions->datareader("select trno as value from delstatus where trno = ?",[$tableid],'',true);
          if($exist){
            $delcharge = $this->coreFunctions->datareader("select ifnull(sum(amount),0) as value from sjexp where trno = ?",[$tableid],'',true);
            $this->coreFunctions->sbcupdate("delstatus",["delcharge" => $delcharge,'editby' =>  $config['params']['user'],'editdate'=> $this->othersClass->getCurrentTimeStamp()],["trno" => $tableid]); 
          }else{
            $delcharge = $this->coreFunctions->datareader("select ifnull(sum(amount),0) as value from sjexp where trno = ?",[$tableid],'',true);
            if($delcharge!=0){
              $this->coreFunctions->sbcinsert("delstatus",["trno"=>$tableid,"delcharge" => $delcharge]); 
            }
          }
      $returnrow = $this->loaddataperrecord($config, $row['line']);
      $this->logger->sbcmasterlog($tableid, $config, ' Update - ' . $row['expensename']. '~ACTUAL: ' . $row['actual']. '~REMARKS: ' . $row['rem']);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  }

  public function delete($config)
  {
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $row = $config['params']['row'];
    $data = $this->loaddataperrecord($config, $row['line']);

    if($doc=='SJ'){
      $qry = "delete from sjexp where trno =? and pxtrno =? and pxline=?";
      $this->coreFunctions->execqry($qry, 'delete', [$tableid,$row['trno'],$row['line']]);

      $params = $config;
      $this->logger->sbcmasterlog(
        $tableid,
        $params,
        ' DELETE - LINE: ' . $row['line'] . ''
          . ', EXPENSE: ' . $row['expensename']
          . ',ACTUAL: ' . $row['actual']
      );

    }else{
      $exist = $this->coreFunctions->getfieldvalue($this->table,"expenseid","trno=? and line =?",[$tableid,$row['line']],'',true);
      if($exist == 94){
        return ['status' => false, 'msg' => 'Not Allowed to delete Duty'];
      }else{
        $qry = "delete from " . $this->table . " where trno =? and line=?";
        $this->coreFunctions->execqry($qry, 'delete', [$tableid,$row['line']]);
    
        $params = $config;
        $params['params']['doc'] = strtoupper("px");
        $this->logger->sbcmasterlog(
          $tableid,
          $params,
          ' DELETE - LINE: ' . $row['line'] . ''
            . ', EXPENSE: ' . $row['expensename']
            . ', AMOUNT: ' . $row['budget']
            . ',ACTUAL: ' . $row['actual']
        );
      }
  
      
      return ['status' => true, 'msg' => 'Successfully deleted.'];
      }
      
  }


  private function loaddataperrecord($config, $line)
  {
    $doc = $config['params']['doc'];

    if($doc == 'SJ'){
      return $this->loaddataperrecordsj($config,$line);
    }else{
     return $this->loaddataperrecordpcf($config,$line);
    }

  }
  
  private function loaddataperrecordpcf($config, $line)
  {
    $tableid = $config['params']['tableid'];
   
    $qry = "select pc.trno,pc.line,pc.expenseid,pc.budget,r.category as expensename,pc.actual,pc.rem, '' as bgcolor
    from " . $this->table . " as pc left join reqcategory as r on r.line = pc.expenseid and r.ispexp =1
    where pc.trno = ". $tableid . " and pc.line=?
    union all
    select pc.trno,pc.line,pc.expenseid,pc.budget,r.category as expensename,pc.actual,pc.rem, '' as bgcolor
    from " . $this->htable . " as pc left join reqcategory as r on r.line = pc.expenseid and r.ispexp =1
    where pc.trno = ". $tableid . " and pc.line=?
    order by line";

    $data = $this->coreFunctions->opentable($qry, [$line,$line]);
    return $data;
  }

  private function loaddataperrecordsj($config, $line)
  {
    $trno = $config['params']['tableid'];
    $isposted = $this->othersClass->isposted2($trno,"cntnum");
    $head ="lahead";
    $headinfo = "hheadinfotrans";
    if($isposted){
      $head = "glhead";
    }

    $qstrno = $this->coreFunctions->getfieldvalue($head,"sotrno","trno=?",[$trno],'',true);
    $pxtrno = $this->coreFunctions->getfieldvalue($headinfo,"dtctrno","trno=?",[$qstrno],'',true);
   
    $qry = "select pc.trno,pc.line,pc.expenseid,pc.budget,r.category as expensename,sj.amount as actual,sj.rem, '' as bgcolor
    from sjexp as sj join hpxchecking as pc on pc.trno = sj.pxtrno and pc.line = sj.pxline 
    left join reqcategory as r on r.line = pc.expenseid and r.ispexp =1
    where sj.trno = ".$trno." and sj.pxtrno = " . $pxtrno . " and sj.pxline =".$line."
    order by r.sortline";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function loaddata($config)
  {
    $doc = $config['params']['doc'];

    if($doc == 'SJ'){
      return $this->loaddataforsj($config);
    }else{
     return $this->loaddatapcf($config);
    }
    
  }

  private  function loaddatapcf($config){
    $tableid = $config['params']['tableid'];

    $qry = "select pc.trno,pc.line,pc.expenseid,pc.budget,r.category as expensename,pc.actual,pc.rem, '' as bgcolor
    from " . $this->table . " as pc left join reqcategory as r on r.line = pc.expenseid and r.ispexp =1
    where pc.trno = " . $tableid . "
    union all
    select pc.trno,pc.line,pc.expenseid,pc.budget,r.category as expensename,pc.actual,pc.rem, '' as bgcolor
    from " . $this->htable . " as pc left join reqcategory as r on r.line = pc.expenseid and r.ispexp =1
    where pc.trno = " . $tableid . "
    order by line";

    $data = $this->coreFunctions->opentable($qry);
    $stock = $this->coreFunctions->datareader("select sum(totaltp) as value from pxstock where trno= ?",[$config['params']['tableid']]);
    if(empty($data)){      
      if($stock != 0){
        $os = $this->coreFunctions->getfieldvalue("pxhead","oandausdphp","trno=?",[$config['params']['tableid']]);
        $stock = $stock * $os;
        $i['trno'] = $config['params']['tableid'];
        $i['line'] = 1;
        $i['expenseid'] = 94;
        $i['budget'] = round($stock * .02,2);
        $i['actual'] = round($stock * .02,2);
        $i['rem'] = '';

        $this->coreFunctions->sbcinsert($this->table, $i);

        $data = $this->coreFunctions->opentable($qry);
      }else{
        $i['trno'] = $config['params']['tableid'];
        $i['line'] = 1;
        $i['expenseid'] = 94;
        $i['budget'] = 0;
        $i['actual'] = 0;
        $i['rem'] = '';

        $this->coreFunctions->sbcinsert($this->table, $i);

        $data = $this->coreFunctions->opentable($qry);
      }
      
      
    }else{
      $trno = $config['params']['tableid'];
      $exist = $this->coreFunctions->getfieldvalue("pxchecking","line","trno=? and expenseid =94",[$trno],'',true);      
      $line = $exist;
      if($stock != 0){
        $os = $this->coreFunctions->getfieldvalue("pxhead","oandausdphp","trno=?",[$config['params']['tableid']]);
        $stock = $stock * $os;
       
        $i['budget'] = round($stock * .02,2);
        $i['actual'] = round($stock * .02,2);
        $i['rem'] = '';

        if($exist !=0){
          $this->coreFunctions->sbcupdate($this->table, $i,["trno"=>$trno, "line" => $line]);
        }else{
          $i['trno'] = $config['params']['tableid'];
          $line =$this->coreFunctions->getfieldvalue("pxchecking","max(line)","trno=?",[$trno],'',true);
          $i['line'] = $line+1;
          $i['expenseid'] = 94;
          $this->coreFunctions->sbcinsert($this->table, $i);
        }       

        $data = $this->coreFunctions->opentable($qry);
      }
    }
    return $data;
  }

  private function loaddataforsj($config){
    $trno = $config['params']['tableid'];
    $currentuser = $config['params']['user'];
    $isposted = $this->othersClass->isposted2($trno,"cntnum");
    $head ="lahead";
    $headinfo = "hheadinfotrans";
    $currentdate = $this->othersClass->getCurrentTimeStamp();

    if($isposted){
      $head = "glhead";
    }

    $qstrno = $this->coreFunctions->getfieldvalue($head,"sotrno","trno=?",[$trno],'',true);
    $pxtrno = $this->coreFunctions->getfieldvalue($headinfo,"dtctrno","trno=?",[$qstrno],'',true);
    $exist = $this->coreFunctions->getfieldvalue("sjexp","trno","trno=?",[$trno],'',true);

    $mainqry = "select pc.trno,pc.line,pc.expenseid,pc.budget,r.category as expensename,sj.amount as actual,sj.rem, '' as bgcolor
    from sjexp as sj join hpxchecking as pc on pc.trno = sj.pxtrno and pc.line = sj.pxline 
    left join reqcategory as r on r.line = pc.expenseid and r.ispexp =1
    where sj.trno = " . $trno . "
    order by r.sortline";

    if($exist){
      goto loadhere;
      //return $this->coreFunctions->opentable($mainqry);
    }

    if($pxtrno != 0){
      $qry = "select pc.trno,pc.line,pc.expenseid,pc.budget,r.category as expensename,pc.actual,pc.rem, '' as bgcolor
      from hpxchecking as pc left join reqcategory as r on r.line = pc.expenseid and r.ispexp =1
      where r.iscomm = 1 and pc.trno = " . $pxtrno . "
      order by r.sortline";
      $data = $this->coreFunctions->opentable($qry);
      $exist = $this->coreFunctions->getfieldvalue("sjexp","trno","trno=?",[$trno],'',true);
      if($exist){
        //return $this->coreFunctions->opentable($mainqry);
        $this->coreFunctions->execqry("delete from sjexp where trno = ?",'delete',[$trno]);
      }
      $d=[];
      foreach($data as $k => $v){
        $d=[];
        $d['trno'] = $trno;
        $d['pxtrno'] = $data[$k]->trno;
        $d['pxline'] = $data[$k]->line;
        $d['amount'] = $data[$k]->actual;

        $this->coreFunctions->sbcinsert("sjexp", $d);
        $this->logger->sbcmasterlog($trno, $config, ' ADD - ' .  $data[$k]->expensename. '~ACTUAL: ' .  $data[$k]->actual);
      }

      $exist = $this->coreFunctions->datareader("select trno as value from delstatus where trno = ?",[$trno],'',true);
      if($exist){
        $delcharge = $this->coreFunctions->datareader("select ifnull(sum(amount),0) as value from sjexp where trno = ?",[$trno],'',true);
        $this->coreFunctions->sbcupdate("delstatus",["delcharge" => $delcharge,'editby' => $currentuser,'editdate'=> $currentdate],["trno" => $trno]); 
      }else{
        $delcharge = $this->coreFunctions->datareader("select ifnull(sum(amount),0) as value from sjexp where trno = ?",[$trno],'',true);
        if($delcharge!=0){
          $this->coreFunctions->sbcinsert("delstatus",["trno"=>$trno,"delcharge" => $delcharge]); 
        }
      }
    
      
    }else{
      return [];
    } 
  loadhere:  
    return $this->coreFunctions->opentable($mainqry);
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
        case 'addexpense':
            return $this->lookupitem($config);
            break;
      
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
      from " . $this->tablelogs . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = 'SJ' and log.trno = '" . $trno . "'
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

  public function lookupitem($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Expenses',
      'style' => 'width:900px;max-width:900px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'addtogrid'
    );

    $cols = array(
        array('name' => 'category', 'label' => 'Expense', 'align' => 'left', 'field' => 'category', 'sortable' => true, 'style' => 'font-size:16px;')
  
      );

    $qry = "select line,category from reqcategory where ispexp =1";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  } //end function

  public function lookupcallback($config)
  {
    switch ($config['params']['lookupclass2']) {
      case 'addtogrid':
        $trno = $config['params']['tableid'];
        $row = $config['params']['row'];
        $data = [];
        $data['line'] = 0;
        $data['trno'] = $trno;
        $data['expenseid'] = $row['line'];
        $data['expensename'] = $row['category'];
        $data['budget'] = 0;
        $data['actual'] = 0;
        $data['rem'] = '';
        $data['bgcolor'] = 'bg-blue-2';
        return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
        break;
    }
  } // end function

  

  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }


  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
