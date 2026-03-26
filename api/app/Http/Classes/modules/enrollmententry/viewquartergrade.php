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

class viewquartergrade
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'QUARTER GRADE';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_gequartergrade';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'gcpercentgrade', 'gcfinaltotal', 'gcrcardtotal'];
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
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'name', 'gcscoregrade', 'gcsubnoofitems', 'gcpercentgrade', 'gcfinaltotal', 'gcrcardtotal', 'type']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:120px;whiteSpace:normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:80px;whiteSpace:normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][3]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:100px;whiteSpace:normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][4]['label'] = "Tentative Total";
    $obj[0][$this->gridname]['columns'][7]['type'] = "label";
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        $data3 = [
          'trno' => $data2['trno'],
          'line' => $data2['line'],
          'tentativetotal' => $data2['gcpercentgrade'],
          'finaltotal' => $data2['gcfinaltotal'],
          'rcardtotal' => $data2['gcrcardtotal']
        ];

        $editedtotal = false;
        $gdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GQC'");
        $rcdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
        if ($gdecimal == '') $gdecimal = 0;
        if ($rcdecimal == '') $rcdecimal = 0;
        $tentativetotal = $this->coreFunctions->getfieldvalue('en_gequartergrade', 'tentativetotal', 'trno=? and line=?', [$data3['trno'], $data3['line']]);

        if ($data3['tentativetotal'] != $tentativetotal) $editedtotal = true;
        $data3['tentativetotal'] = round($data3['tentativetotal'], $gdecimal);
        $finaltotal = $this->coreFunctions->opentable("select equivalent from en_gradeequivalent where range1<=? and range2>=?", [$data3['tentativetotal'], $data3['tentativetotal']]);
        if (!empty($finaltotal)) {
          $data3['finaltotal'] = round($finaltotal[0]->equivalent, $gdecimal);
          if ($editedtotal) $data3['rcardtotal'] = round($finaltotal[0]->equivalent, $rcdecimal);
        }

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
    $data2 = [
      'trno' => $data['trno'],
      'line' => $data['line'],
      'tentativetotal' => $data['gcpercentgrade'],
      'finaltotal' => $data['gcfinaltotal'],
      'rcardtotal' => $data['gcrcardtotal']
    ];

    $editedtotal = false;
    $gdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GQC'");
    $rcdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
    if ($gdecimal == '') $gdecimal = 0;
    if ($rcdecimal == '') $rcdecimal = 0;
    $tentativetotal = $this->coreFunctions->getfieldvalue('en_gequartergrade', 'tentativetotal', 'trno=? and line=?', [$data2['trno'], $data2['line']]);

    if ($data2['tentativetotal'] != $tentativetotal) $editedtotal = true;
    $data2['tentativetotal'] = count($data2['tentativetotal'], $gdecimal);
    $finaltotal = $this->coreFunctions->opentable("select equivalent from en_gradeequivalent where range1<=? and range2>=?", [$data2['tentativetotal'], $data2['tentativetotal']]);
    if (!empty($finaltotal)) {
      $data2['finaltotal'] = round($finaltotal[0]->equivalent, $gdecimal);
      if ($editedtotal) $data2['rcardtotal'] = round($finaltotal[0]->equivalent, $rcdecimal);
    }
    if ($data2['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data2['trno'], 'line' => $data2['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($data2['trno'], $data2['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  private function selectqry()
  {
    // $gdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GQC'");
    // $rcdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
    $gdecimal = 0;
    $rcdecimal = 0;
    // if ($gdecimal == '') $gdecimal = 0;
    // if ($rcdecimal == '') $rcdecimal = 0;
    // return "select qg.trno, qg.line, qg.clientid, client.clientname as name, qg.scoregrade as gcscoregrade, round(qg.totalgrade,0) as gcsubnoofitems, round(qg.tentativetotal," . $gdecimal . ") as gcpercentgrade, round(qg.finaltotal," . $gdecimal . ") as gcfinaltotal, round(qg.rcardtotal," . $rcdecimal . ") as gcrcardtotal, case when isconduct=1 then 'Conduct' else '' end as type, '' as bgcolor ";
    return "select qg.trno, qg.line, qg.clientid, client.clientname as name, qg.scoregrade as gcscoregrade, round(qg.totalgrade,0) as gcsubnoofitems, round(qg.tentativetotal,2) as gcpercentgrade, round(qg.finaltotal," . $gdecimal . ") as gcfinaltotal, round(qg.rcardtotal,2) as gcrcardtotal, case when isconduct=1 then 'Conduct' else '' end as type, '' as bgcolor ";
  }

  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $qry = $select . " from en_gequartergrade as qg left join client on client.clientid=qg.clientid where qg.trno=? and qg.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $qry = $select . " from en_gequartergrade as qg left join client on client.clientid=qg.clientid where qg.trno=? order by qg.clientid";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  }
} //end class
