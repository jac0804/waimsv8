<?php

namespace App\Http\Classes\modules\enrollmentgradeentry;

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

class en_gradeutility
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'GRADE UTILITY';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'profile';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['doc', 'psection', 'gudecimal'];
  public $showclosebtn = false;



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
      'load' => 2727
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'gudescription', 'gudecimal']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['defaults', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function adddefaults($config)
  {
    $defaults = ['GCC', 'GQC', 'GRC', 'GFC', 'GGC'];
    foreach ($defaults as $d) {
      $check = $this->coreFunctions->datareader("select line as value from profile where doc='ENG' and psection=" . $d);
      if (empty($check)) {
        $this->coreFunctions->execqry("insert into profile(doc, psection, pvalue, puser) values('ENG', '" . $d . "', 0, '" . $config['params']['user'] . "')", 'insert');
      }
    }
    $data = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Defaults saved', 'data' => $data];
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      $data3 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data3 = ['psection' => $data2['psection'], 'pvalue' => $data2['gudecimal']];
        if ($data[$key]['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data3, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data2 = ['psection' => $data['psection'], 'pvalue' => $data['gudecimal']];
    if ($row['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function loaddataperrecord($line)
  {
    $qry = "select line, doc, psection, case psection when 'GCC' then 'Component' when 'GQC' then 'Quarter' when 'GRC' then 'Report Card' when 'GFC' then 'Final' else 'General' end as gudescription, pvalue as gudecimal, '' as bgcolor from profile where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $qry = "select line, doc, psection, case psection when 'GCC' then 'Component' when 'GQC' then 'Quarter' when 'GRC' then 'Report Card' when 'GFC' then 'Final' else 'General' end as gudescription, pvalue as gudecimal, '' as bgcolor from profile where doc='ENG' order by line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
} //end class
