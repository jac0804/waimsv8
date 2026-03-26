<?php

namespace App\Http\Classes\modules\enrollmententry;

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
use App\Http\Classes\lookup\enrollmentlookup;

class entrycredentials
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CREDENTIALS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_socredentials';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'credentialid', 'acnoid', 'amt', 'camt', 'feesid', 'percentdisc'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
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
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'EA':
      case 'ED':
      case 'EI':
        $tab = [$this->gridname => ['gridcolumns' => ['action', 'credentialcode', 'credentials', 'particulars', 'amt', 'percentdisc', 'camt', 'acno', 'feescode']]];
        break;
      default:
        $tab = [$this->gridname => ['gridcolumns' => ['credentialcode', 'credentials', 'particulars', 'amt', 'percentdisc', 'camt', 'acno', 'feescode']]];
        break;
    }

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;"; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; //subjectcode
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; //subjectname
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //units
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true; //lecture

    return $obj;
  }


  public function createtabbutton($config)
  {
    $doc = $config['params']['doc'];
    switch ($doc) {
      case 'EA':
      case 'ED':
      case 'EI':
        $tbuttons = ['addcredentials'];
        break;
      default:
        $tbuttons = [];
        break;
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'lookupaddcredentials':
        return $this->enrollmentlookup->lookupaddcredentials($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['rows'];
    $doc = $config['params']['doc'];
    $htable = "en_glcredentials";

    switch ($doc) {
      case 'ER':
        $table = "en_sjcredentials";
        break;
      case 'EA':
      case 'EI':
        $table = "en_socredentials";
        break;
      case 'ED':
        $table = "en_adcredentials";
        break;
    }

    $data = [];

    foreach ($row  as $key2 => $value) {

      $qry = "select line as value from " . $table . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$id]);
      if ($line == '') $line = 0;
      $line  += 1;

      $config['params']['row']['line'] = $line;
      $config['params']['row']['trno'] = $id;
      $config['params']['row']['credentialid'] = $value['line'];
      $config['params']['row']['acnoid'] = $value['acnoid'];
      $config['params']['row']['acno'] =  $this->coreFunctions->getfieldvalue('coa', 'acno', 'acnoid=?', [$value['acnoid']]);
      $config['params']['row']['feesid'] = $value['feesid'];
      $config['params']['row']['feescode'] = $value['feesid'];
      $config['params']['row']['amt'] = $value['amt'];
      $config['params']['row']['percentdisc'] = $value['percentdisc'];
      $config['params']['row']['bgcolor'] = 'bg-blue-2';

      if ($value['percentdisc'] > 0) {
        if ($value['feesid'] > 0) {
          $qry = "select sum(amt) as value from (select s.feesid,f.feestype,s.amt,f.feescode
                from en_sosummary as s left join  en_fees as f on f.line=s.feesid where s.trno=? and f.line=?
                union all
                select s.feesid,f.feestype,s.isamt,f.feescode
                from en_sootherfees as s left join  en_fees as f on f.line=s.feesid where s.trno=? and f.line=?) as v ";
          $camt = $this->coreFunctions->datareader($qry, [$id, $value['feesid'], $id, $value['feesid']]);
        } else {
          $qry = "select sum(amt) as value from (select s.feesid,f.feestype,s.amt,f.feescode
                from en_sosummary as s left join  en_fees as f on f.line=s.feesid where s.trno=? 
                union all
                select s.feesid,f.feestype,s.isamt,f.feescode
                from en_sootherfees as s left join  en_fees as f on f.line=s.feesid where s.trno=?) as v ";
          $camt = $this->coreFunctions->datareader($qry, [$id, $id]);
        }

        $config['params']['row']['camt'] = $camt * ($value['percentdisc'] / 100);
      } else {
        $config['params']['row']['camt'] = $value['camt'];
      }

      $return = $this->insertcredentials($config, $table);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }

    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  } // end function

  public function insertcredentials($config, $table)
  {
    $data = [];
    $row = $config['params']['row'];

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    $line = $this->coreFunctions->insertGetId($table, $data);
    if ($row['line'] != 0) {
      $returnrow = $this->loaddataperrecord($row['trno'], $row['line'], $table);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Insert failed.'];
    }
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];

    switch ($doc) {
      case 'ER':
        $table = "en_sjcredentials";
        break;
      case 'EA':
      case 'EI':
        $table = "en_socredentials";
        break;
      case 'ED':
        $table = "en_adcredentials";
        break;
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['trno'], $line, $table);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Insert failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($table, $data, ['trno' => $row['trno'], 'line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['trno'], $row['line'], $table);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Update failed.'];
      }
    }
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $doc = $config['params']['doc'];

    switch ($doc) {
      case 'ER':
        $table = "en_sjcredentials";
        break;
      case 'EA':
      case 'EI':
        $table = "en_socredentials";
        break;
      case 'ED':
        $table = "en_adcredentials";
        break;
    }
    $qry = "delete from " . $table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($trno, $line, $table)
  {
    $htable = "en_glcredentials";


    $select = $this->selectqry();
    $qry = $select . " FROM " . $table . " as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? and soc.line=? 
      union all 
      " . $select . " FROM  " . $htable . " as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? and soc.line=?  ";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $data;
  }

  private  function selectqry()
  {
    return  "select soc.trno, soc.line, soc.credentialid,soc.amt,soc.particulars,soc.percentdisc,soc.acnoid,soc.feesid,coa.acno,coa.acnoname,c.credentials,c.credentialcode,s.subjectcode,s.subjectname,soc.camt,f.feescode as feescode,soc.scheme,f.feestype,
    '' as bgcolor,
    '' as errcolor ";
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $doc = $config['params']['doc'];
    $htable = "en_glcredentials";

    switch ($doc) {
      case 'ER':
        $table = "en_sjcredentials";
        break;
      case 'EA':
      case 'EI':
        $table = "en_socredentials";
        break;
      case 'ED':
        $table = "en_adcredentials";
        break;
    }

    $select = $this->selectqry();
    $qry =  $select . " FROM  " . $table . " as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? 
      union all 
      " . $select . " FROM  " . $htable . " as soc left join transnum as num on num.trno=soc.trno  left join coa on coa.acnoid=soc.acnoid left join en_credentials as c on c.line=soc.credentialid left join en_fees as f on f.line=soc.feesid left join en_subject as s on s.trno=soc.subjectid   where soc.trno=? ";
    $data = $this->coreFunctions->opentable($qry, [$tableid, $tableid]);
    return $data;
  }
} //end class
