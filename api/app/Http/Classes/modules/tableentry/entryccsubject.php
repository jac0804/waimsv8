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

class entryccsubject
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CURRICULUM SUBJECTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_ccsubject';
  private $htable = 'en_glsubject';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'cline', 'subjectid', 'units', 'lecture', 'laboratory', 'hours', 'coreqid', 'pre1id', 'pre2id', 'pre3id', 'pre4id', 'yearnum', 'terms'];
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
    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'subjectcode', 'subjectname', 'units', 'lecture', 'laboratory', 'hours'
      ],
    ]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'SUBJECT';
    $obj[0][$this->gridname]['descriptionrow'] = ['subjectname', 'subjectcode', 'Subject'];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][7]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][8]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][9]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][10]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][11]['action'] = 'lookupsetup';

    $obj[0][$this->gridname]['columns'][12]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;'; //action
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:60px;whiteSpace: normal;min-width:100px;'; //action
    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:60px;whiteSpace: normal;min-width:180px;'; //action
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][2]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][3]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][4]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true; //action
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true; //action
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addsubject', 'saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'Add';
    $obj[0]['lookupclass'] = 'lookupsubject2';
    $obj[0]['lookupclass2'] = 'lookupsubject2';
    $obj[0]['action'] = 'lookupsetup';
    return $obj;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    $config['params']['trno'] = $config['params']['tableid'];
    $config['params']['table'] = 'en_subject';
    switch ($lookupclass) {
      case 'lookupsubject2':
      case 'lookupgridcoreq':
      case 'lookupgridpre1':
      case 'lookupgridpre2':
      case 'lookupgridpre3':
      case 'lookupgridpre4':
        return $this->enrollmentlookup->lookupsubject($config);
        break;
    }
  }

  public function lookupcallback($config)
  {
    $trno = $config['params']['tableid'];
    $cline = $config['params']['rows'][0]['line'];

    if ($cline == 0) {
      return ['status' => false, 'msg' => 'Cannot add subject save Year and Sem first...'];
    }


    $row = $config['params']['rows'];
    $doc = $config['params']['doc'];
    $data = [];
    foreach ($row as $key2 => $value) {
      $line = $this->coreFunctions->datareader("select line as value from " . $this->table . " where trno=? and cline=? order by line desc limit 1", [$trno, $cline]);
      if ($line == '') $line = 0;
      $line += 1;
      $config['params']['row']['line'] = $line;
      $config['params']['row']['trno'] = $trno;
      $config['params']['row']['cline'] = $cline;
      $config['params']['row']['subjectid'] = $value['trno'];
      $config['params']['row']['units'] = $value['units'];
      $config['params']['row']['lecture'] = $value['lecture'];
      $config['params']['row']['laboratory'] = $value['laboratory'];
      $config['params']['row']['hours'] = $value['hours'];

      $return = $this->insertsubjects($config);
      if ($return['status']) {
        array_push($data, $return['row'][0]);
      }
    }
    return ['status' => true, 'msg' => 'Successfully added.', 'data' => $data];
  }

  public function insertsubjects($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $row['coreqid'] = $row['pre1id'] = $row['pre2id'] = $row['pre3id'] = $row['pre4id'] = $row['yearnum'] = $row['terms'] = 0;
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcinsert($this->table, $data) == 1) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line'], $data['cline'], $this->table);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Insert failed.'];
      }
    } else {
      return ['status' => false, 'msg' => 'Insert failed.'];
    }
  }

  public function add($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $data = [];
    $data['trno'] = $trno;
    $data['compid'] = $line;
    $data['line'] = 0;
    $data['gcsubcode'] = '';
    $data['gcsubtopic'] = '';
    $data['gcsubnoofitems'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  private function selectqry()
  {
    $sqlselect = "select stock.trno, stock.line, stock.curriculumcode, stock.yearnum, term.term as terms, 
      stock.semid, s.subjectcode, stock.subjectid, s.subjectname, stock.units, c.coursecode, stock.courseid,
      p1.subjectcode as pre1, stock.pre1id, p2.subjectcode as pre2, stock.pre2id, p3.subjectcode as pre3,
      stock.pre3id, p4.subjectcode as pre4, stock.pre4id, p5.subjectcode as pre5, stock.pre5id, stock.lecture,
      stock.laboratory, coreq.subjectcode as coreq, stock.coreqid, stock.hours, '' as bgcolor, '' as errcolor ";
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
        if ($data2['line'] != 0) {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $data2['trno'], 'line' => $data2['line'], 'cline' => $data2['cline']]);
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
    if ($data['line'] != 0) {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $data['trno'], 'line' => $data['line'], 'cline' => $data['cline']]) == 1) {
        $returnrow = $this->loaddataperrecord($data['trno'], $data['line'], $data['cline']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where trno=? and line=? and cline=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line'], $row['cline']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  private function checkuomtransaction($itemid, $line, $uom)
  {
    $uom2 = $this->coreFunctions->getfieldvalue('uom', 'uom', 'itemid=? and line=?', [$itemid, $line]);
    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid=?', [$itemid]);
    $qry = "
         select stock.trno from lastock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from postock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from hpostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "' 
         union all
         select stock.trno from sostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from hsostock as stock left join item on item.itemid=stock.itemid where item.barcode='" . $barcode . "' and stock.uom='" . $uom2 . "'  
         union all
         select stock.trno from glstock as stock  where stock.itemid=" . $itemid . " and stock.uom='" . $uom2 . "'                                   
     ";
    $data = $this->coreFunctions->opentable($qry);
    if (!empty($data)) {
      return true;
    } else {
      return false;
    }
  }

  private function loaddataperrecord($trno, $line, $cline)
  {
    $sqlselect = "select " . $cline . " as cline, stock.trno, stock.line, stock.curriculumcode, stock.yearnum, term.term as terms, 
      stock.semid, s.subjectcode, stock.subjectid, s.subjectname, stock.units, c.coursecode, stock.courseid,
      p1.subjectcode as pre1, stock.pre1id, p2.subjectcode as pre2, stock.pre2id, p3.subjectcode as pre3,
      stock.pre3id, p4.subjectcode as pre4, stock.pre4id, p5.subjectcode as pre5, stock.pre5id, stock.lecture,
      stock.laboratory, coreq.subjectcode as coreq, stock.coreqid, stock.hours, '' as bgcolor, '' as errcolor ";
    $qry = $sqlselect . " FROM " . $this->table . " as stock left join en_subject as s on s.trno=stock.subjectid
     left join en_course as c on c.line=stock.courseid left join en_term as term on term.line=stock.semid
     left join en_subject as p1 on p1.trno=stock.pre1id left join en_subject as p2 on p2.trno=stock.pre2id
     left join en_subject as p3 on p3.trno=stock.pre3id left join en_subject as p4 on p4.trno=stock.pre4id
     left join en_subject as p5 on p5.trno=stock.pre5id left join en_subject as coreq on coreq.trno=stock.coreqid
      where stock.trno = ? and stock.line = ? and stock.cline =?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $cline]);
    return $data;
  }

  public function loaddata($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $sqlselect = "select " . $line . " as cline, stock.trno, stock.line, stock.curriculumcode, stock.yearnum, term.term as terms, 
    stock.semid, s.subjectcode, stock.subjectid, s.subjectname, stock.units, c.coursecode, stock.courseid,
    p1.subjectcode as pre1, stock.pre1id, p2.subjectcode as pre2, stock.pre2id, p3.subjectcode as pre3,
    stock.pre3id, p4.subjectcode as pre4, stock.pre4id, p5.subjectcode as pre5, stock.pre5id, stock.lecture,
    stock.laboratory, coreq.subjectcode as coreq, stock.coreqid, stock.hours, '' as bgcolor, '' as errcolor ";

    $qry = $sqlselect . " FROM " . $this->table . " as stock left join en_subject as s on s.trno=stock.subjectid
    left join en_course as c on c.line=stock.courseid left join en_term as term on term.line=stock.semid
    left join en_subject as p1 on p1.trno=stock.pre1id left join en_subject as p2 on p2.trno=stock.pre2id
    left join en_subject as p3 on p3.trno=stock.pre3id left join en_subject as p4 on p4.trno=stock.pre4id
    left join en_subject as p5 on p5.trno=stock.pre5id left join en_subject as coreq on coreq.trno=stock.coreqid
    where stock.trno=? and stock.cline=?
    union all 
    " . $sqlselect . " FROM " . $this->htable . " as stock left join en_subject as s on s.trno=stock.subjectid
    left join en_course as c on c.line=stock.courseid left join en_term as term on term.line=stock.semid
    left join en_subject as p1 on p1.trno=stock.pre1id left join en_subject as p2 on p2.trno=stock.pre2id
    left join en_subject as p3 on p3.trno=stock.pre3id left join en_subject as p4 on p4.trno=stock.pre4id
    left join en_subject as p5 on p5.trno=stock.pre5id left join en_subject as coreq on coreq.trno=stock.coreqid
    where stock.trno=? and stock.cline=?
    order by line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $stock;
  }
} //end class
