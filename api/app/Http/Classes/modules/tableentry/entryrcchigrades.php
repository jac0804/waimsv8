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
use App\Http\Classes\lookup\enrollmentlookup;

class entryrcchigrades
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CHINESE GRADES';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_gequartergrade';
  private $htable = 'en_gequartergrade';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'gcpercentgrade', 'gcrcardtotal'];
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
    $attrib = ['load' => 0];
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'subjectname', 'quartername', 'gcscoregrade', 'gcsubnoofitems', 'gcpercentgrade', 'gcfinaltotal', 'gcrcardtotal']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'ENGLISH GRADES';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:50px;min-width:50px;max-width:50px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:175px;min-width:175px;max-width:175px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:75px;min-width:75px;max-width:75px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:75px;min-width:75px;max-width:75px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:100px;min-width:100px;max-width:100px;whiteSpace:normal;';
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function selectqry()
  {
    $sqlselect = "select stock.trno, stock.line, stock.itemid, item.barcode, item.itemname as description, 
      stock.uom, stock.isqty, stock.disc, stock.isamt, stock.amt, stock.ext, '' as bgcolor, '' as errcolor ";
    return $sqlselect;
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
          'finaltotal' => 0,
          'rcardtotal' => $data2['gcrcardtotal']
        ];
        $gdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GQC'");
        $rcdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
        if ($gdecimal == '') $gdecimal = 0;
        if ($rcdecimal == '') $rcdecimal = 0;

        $editedtotal = false;
        $tentativetotal = $this->coreFunctions->getfieldvalue('en_gequartergrade', 'tentativetotal', 'trno=? and line=?', [$data2['trno'], $data2['line']]);
        if ($data3['tentativetotal'] != $tentativetotal) $editedtotal = true;
        $data3['tentativetotal'] = round($data3['tentativetotal'], $gdecimal);
        $finaltotal = $this->coreFunctions->opentable("select equivalent from en_gradeequivalent where range1<=? and range2>=?", [$data3['tentativetotal'], $data3['tentativetotal']]);
        if (!empty($finaltotal)) {
          $data3['finaltotal'] = round($finaltotal[0]->equivalent, $gdecimal);
          if ($editedtotal) $data3['rcardtotal'] = round($finaltotal[0]->equivalent, $rcdecimal);
        }
        if ($data3['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data3, ['trno' => $data3['trno'], 'line' => $data3['line']]);
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
      'finaltotal' => 0,
      'rcardtotal' => $data['gcrcardtotal']
    ];
    $gdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GQC'");
    $rcdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
    if ($gdecimal == '') $gdecimal = 0;
    if ($rcdecimal == '') $rcdecimal = 0;

    $editedtotal = false;
    $tentativetotal = $this->coreFunctions->getfieldvalue('en_gequartergrade', 'tentativetotal', 'trno=? and line=?', [$data2['trno'], $data2['line']]);
    if ($data2['tentativetotal'] != $tentativetotal) $editedtotal = true;
    $data2['tentativetotal'] = round($data2['tentativetotal'], $gdecimal);
    $finaltotal = $this->coreFunctions->opentable("select equivalent from en_gradeequivalent where range1<=? and range2>=?", [$data2['tentativetotal'], $data2['tentativetotal']]);
    if (!empty($finaltotal)) {
      $data2['finaltotal'] = round($finaltotal[0]->equivalent, $gdecimal);
      if ($editedtotal) $data2['rcardtotal'] = round($finaltotal[0]->equivalent, $rcdecimal);
    }
    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data2['trno'], 'line' => $data2['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($data2['trno'], $data2['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  private function loaddataperrecord($trno, $line)
  {
    $gdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GQC'");
    $rcdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
    if ($gdecimal == '') $gdecimal = 0;
    if ($rcdecimal == '') $rcdecimal = 0;

    $qry = "select qg.trno, qg.line, qg.scoregrade as gcscoregrade, qg.totalgrade as gcsubnoofitems, qg.quarterid, round(qg.tentativetotal," . $gdecimal . ") as gcpercentgrade, round(qg.finaltotal," . $gdecimal . ") as gcfinaltotal, round(qg.rcardtotal," . $rcdecimal . ") as gcrcardtotal, sub.subjectname, q.name as quartername, '' as bgcolor from " . $this->table . " as qg left join en_subject as sub on sub.trno=qg.subjectid left join en_quartersetup as q on q.line=qg.quarterid where qg.trno=? and qg.line=? and sub.ischinese=1";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $clientid = $config['params']['row']['clientid'];

    $gdecimal  = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GQC'");
    $rcdecimal = $this->coreFunctions->getfieldvalue('profile', 'pvalue', "doc='ENG' and psection='GRC'");
    if ($gdecimal == '') $gdecimal = 0;
    if ($rcdecimal == '') $rcdecimal = 0;

    $qry = "select qg.trno, qg.line, qg.scoregrade as gcscoregrade, qg.totalgrade as gcsubnoofitems, qg.quarterid, round(qg.tentativetotal," . $gdecimal . ") as gcpercentgrade, round(qg.finaltotal," . $gdecimal . ") as gcfinaltotal, round(qg.rcardtotal," . $rcdecimal . ") as gcrcardtotal, sub.subjectname, q.name as quartername, '' as bgcolor from " . $this->table . " as qg left join en_subject as sub on sub.trno=qg.subjectid left join en_quartersetup as q on q.line=qg.quarterid where qg.clientid=? and sub.ischinese=1";
    $stock = $this->coreFunctions->opentable($qry, [$clientid]);
    return $stock;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }
} //end class
