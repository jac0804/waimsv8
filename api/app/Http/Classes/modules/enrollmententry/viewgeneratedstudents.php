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

class viewgeneratedstudents {
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STUDENT POINTS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'en_glgrades';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['trno', 'line', 'clientid', 'gcsubcode', 'gcsubtopic', 'gcsubnoofitems', 'points', 'ctrno', 'cline', 'scline'];
  public $showclosebtn = true;
 

  public function __construct() {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib(){
    $attrib = ['load'=>0];
    return $attrib;
  }

  public function createTab($config) {
    $tab = [$this->gridname=>['gridcolumns'=>['listclientname', 'gcsubcode', 'gcsubtopic', 'gcsubnoofitems', 'ehpoints']]];
    $stockbuttons = ['save'];
    $obj = $this->tabClass->createtab($tab,$stockbuttons);

    // clientname     
    $obj[0][$this->gridname]['columns'][0]['type'] = "label";
    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:150px;whiteSpace:normal;min-width:150px;';

    $style = 'width:100px;whiteSpace:normal;min-width:100px;';
    // gcsubcode
    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][1]['style'] = $style;

    // gcsubtopic
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['style'] = $style;

    // gcsubnoofitems
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['style'] = $style;

    // ehpoints
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['style'] = $style;

    $obj[0][$this->gridname]['visiblecol'][0] = 'clientname';
    $obj[0][$this->gridname]['visiblecol'][4] = 'points';
    return $obj;
  }

  public function createtabbutton($config){
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config){
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

  private function selectqry(){
    return "select s.trno, s.line, s.clientid, c.client, c.clientname, s.gcsubcode, s.topic as gcsubtopic, s.noofitems as gcsubnoofitems, s.points, s.ctrno, s.cline, s.scline";
    
  }

  public function updatetotal($data, $total) {
    $this->coreFunctions->execqry("update en_scurriculum set grade=? where trno=? and line=? and cline=?", 'update', [$total, $data['ctrno'], $data['scline'], $data['cline']]);
  }

  public function gettotalpoints($data) {
    $data = $this->coreFunctions->opentable("select sum(points) as points from ".$this->table." where trno=? and clientid=?", [$data['trno'], $data['clientid']]);
    if(!empty($data)) {
      return $data[0]->points;
    }
    return 0;
  }

  private function loaddataperrecord($trno, $line) {
    $select = $this->selectqry();
    $select = $select.",'' as bgcolor ";
    $qry = $select." from ".$this->table." as s left join client as c on c.clientid=s.clientid where s.trno=? and s.line=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $data;
  }

  public function loaddata($config){
    $trno = $config['params']['tableid'];
    $select = $this->selectqry();
    $select = $select. ",'' as bgcolor ";
    $qry = $select." from ".$this->table." as s left join client as c on c.clientid=s.clientid where s.trno=? order by c.clientname,s.gcsubcode";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    return $data;
  }
































} //end class
