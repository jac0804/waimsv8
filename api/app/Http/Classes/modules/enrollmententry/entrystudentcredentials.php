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

class entrystudentcredentials
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CREDENTIALS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_studentcredentials';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['line', 'credentialid', 'amt', 'clientid', 'percentdisc', 'ref'];
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
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'credentialcode', 'credentials', 'particulars', 'amt', 'percentdisc']]];

    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = "width:80px;whiteSpace: normal;min-width:80px;"; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; //credentialcode
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; //credentials
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //particulars

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addstudentcredential'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'addstudentcredential':
        return $this->enrollmentlookup->lookupcredential($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $id = $config['params']['tableid'];
    $row = $config['params']['row'];
    $data = [];
    $data['line'] = 0;
    $data['clientid'] = $id;
    $data['credentialid'] = $row['line'];
    $data['credentialcode'] = $row['credentialcode'];
    $data['credentials'] = $row['credentials'];
    $data['particulars'] = $row['particulars'];
    $data['amt'] = 0;
    $data['percentdisc'] = '';
    $data['ref'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return ['status' => true, 'msg' => 'Add Credentials success...', 'data' => $data];
  } // end function

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($row['clientid'], $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['clientid'], $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from en_studentcredentials where clientid=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['clientid'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($clientid, $line)
  {
    $select = $this->selectqry();
    $qry = $select . " where sc.clientid=? and sc.line=?";
    $data = $this->coreFunctions->opentable($qry, [$clientid, $line]);
    return $data;
  }

  private  function selectqry()
  {
    return "select sc.line, sc.credentialid, sc.amt, sc.clientid, sc.percentdisc, sc.ref, c.credentialcode, c.credentials, c.particulars,'' as bgcolor
    from en_studentcredentials as sc left join en_credentials as c on c.line=sc.credentialid";
  }

  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];
    $select = $this->selectqry();
    $qry = $select . " where sc.clientid=?";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }
} //end class
