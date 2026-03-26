<?php

namespace App\Http\Classes\modules\payrollentry;

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

class entrytimecarddetails
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DETAILS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'timecard';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = [
    'dateid', 'daytype', 'shiftcode', 'schedin', 'schedout', 'schedbrkin',
    'schedbrkout', 'actualin', 'actualout', 'actualbrkin', 'actualbrkout',
    'reghrs', 'absdays', 'latehrs', 'underhrs', 'othrs', 'ndiffs', 'ndiffhrs'
    // , , 
    // , , brk1stin, brk1stout, brk2ndin, 
    // brk2ndout, abrk1stin, abrk1stout, abrk2ndin, abrk2ndout, 
    // divcode, ndiffot, otapproved, Undertime, Ndiffapproved, 
    // isprevwork, RDapprvd, RDOTapprvd, LEGapprvd, LEGOTapprvd,
    // SPapprvd, SPOTapprvd, , ndiffsapprvd
  ];
  public $showclosebtn = false;
  private $enrollmentlookup;



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
    $tab = [$this->gridname => ['gridcolumns' => [
      'action', 'dateid', 'daytype', 'shiftcode', 'schedin', 'schedbrkout', 'schedbrkin',
      'schedout', 'actualin', 'actualbrkout', 'actualbrkin', 'actualout', 'reghrs', 'absdays',
      'latehrs', 'underhrs', 'othrs', 'ndiffs', 'ndiffhrs'
    ]]];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][2]['label'] = 'DAY TYPE';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][6]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][7]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][8]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][9]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][10]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][11]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';

    $obj[0][$this->gridname]['columns'][12]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][12]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][13]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][13]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][14]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][14]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][15]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][15]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][16]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][16]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][17]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][17]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][18]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $obj[0][$this->gridname]['columns'][18]['type'] = 'label';



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
    $qry = "
      tc.line, date(tc.dateid) as dateid, tc.daytype, tc.shiftcode,
      tc.schedin, tc.schedbrkout, tc.schedout, tc.actualin,
      tc.actualbrkout, tc.actualbrkin, tc.schedbrkin, tc.actualout,
      tc.reghrs, tc.absdays, tc.latehrs, tc.underhrs, tc.othrs,
      tc.ndiffs, tc.ndiffhrs
    ";

    return $qry;
  }

  public function loaddata($config)
  {
    $empid = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . ",
    concat(e.emplast, ', ', e.empfirst, ' ', e.empmiddle) as empname
    from " . $this->table . " as tc
    left join employee as e on e.empid=tc.empid
    where tc.empid=?
    order by tc.line";
    $data = $this->coreFunctions->opentable($qry, [$empid]);
    return $data;
  }

  private function loaddataperrecord($line, $config = [])
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " as tc where tc.line=? and tc.empid = ? order by tc.line";
    $data = $this->coreFunctions->opentable($qry, [$line, $config['params']['tableid']]);
    return $data;
  }

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
        $returnrow = $this->loaddataperrecord($row['line'], $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line'], $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'Saved all Successfully', 'data' => $returndata];
  } // end function
































} //end class
