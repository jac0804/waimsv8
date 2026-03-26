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

class entryempprojectlog
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'empprojdetail';
  private $othersClass;
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $style = 'width:100%;';
  private $fields = ['line', 'empid', 'dateid',  'tothrs', 'rem', 'dateno', 'compcode', 'pjroxascode1', 'subpjroxascode', 'blotroxascode', 'amenityroxascode', 'subamenityroxascode', 'departmentroxascode'];
  public $showclosebtn = false;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $row = $config['params']['row'];

    $this->modulename =  $row['emplast'] . ', ' . $row['empfirst'];
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'rem', 'tothrs', 'compcode', 'pjroxascode1', 'subpjroxascode', 'blotroxascode', 'amenityroxascode', 'subamenityroxascode', 'departmentroxascode']]];
    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][2]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";

    $obj[0][$this->gridname]['columns'][5]['type'] = "input";
    $obj[0][$this->gridname]['columns'][5]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][6]['type'] = "input";
    $obj[0][$this->gridname]['columns'][6]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][8]['type'] = "input";
    $obj[0][$this->gridname]['columns'][8]['readonly'] = true;


    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function add($config)
  {
    $row = $config['params']['sourcerow'];

    $data = [];
    $data['line'] = 0;
    $data['empid'] = $row['empid'];
    $data['dateid'] = $row['dateid'];
    $data['dateno'] = $row['dateno'];
    $data['tothrs'] = '';
    $data['rem'] = '';
    $data['compcode'] = '';
    $data['pjroxascode1'] = '';
    $data['code'] = '';
    $data['rxline'] = 0;
    $data['projcode'] = '';
    $data['subprojcode'] = '';
    $data['subpjroxascode'] = '';
    $data['amenityroxascode'] = '';
    $data['subamenityroxascode'] = '';
    $data['departmentroxascode'] = '';

    $data['bcode'] = '';
    $data['blotroxascode'] = '';
    $data['blkline'] = 0;
    $data['blocklotcode'] = '';
    $data['amntcode'] = '';
    $data['subamntcode'] = '';
    $data['deptcode'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $row['compcode'] = $row['compcode'];
    $row['pjroxascode1'] = $row['projcode'];
    $row['subpjroxascode'] = $row['subprojcode'];
    $row['blotroxascode'] = $row['blocklotcode'];
    $row['amenityroxascode'] = $row['amntcode'];
    $row['subamenityroxascode'] = $row['subamntcode'];
    $row['departmentroxascode'] = $row['deptcode'];

    if ($row['compcode'] == '') {
      return ['status' => false, 'msg' => 'Please select valid company.'];
    }

    if ($row['pjroxascode1'] == '') {
      return ['status' => false, 'msg' => 'Please select valid project.'];
    }

    if ($row['tothrs'] == '') {
      return ['status' => false, 'msg' => 'Please enter valid hours.'];
    }

    if ($row['rem'] == '') {
      return ['status' => false, 'msg' => 'Please enter valid remarks.'];
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }


    if ($row['line'] == 0) {

      $line = $this->coreFunctions->insertGetId($this->table, $data);

      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);

        $this->logger->sbcmasterlog($row['empid'], $config, ' CREATE - ' . $data['rem'], 0, 0, $row['dateno']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];

      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  private function selectqry()
  {
    $qry = " 
    empd.line, empd.empid, empd.dateid, empd.tothrs, empd.rem, empd.createby, empd.createdate, empd.editby,
    empd.editdate, empd.dateno, '' as bgcolor,
    comp.compcode,
    comp.name as pjroxascode1 ,comp.code as projcode,
    ifnull(subproj.name,'') as subpjroxascode ,ifnull(subproj.code,'') as subprojcode,
    concat(blocklot.block,' ',blocklot.lot,' ',blocklot.phase) as blotroxascode,ifnull(blocklot.code,'') as blocklotcode,
    ifnull(amnt.name,'') as amenityroxascode ,ifnull(amnt.code,'') as amntcode,
    ifnull(subamnt.name,'')  as subamenityroxascode ,ifnull(subamnt.code,'') as subamntcode,
    ifnull(dept.name,'') as departmentroxascode ,ifnull(dept.code,'') as deptcode,
    comp.line as projline,  
    subproj.line as subprojline,  
    blocklot.line as blocklotline,
    amnt.line as amntline,
    subamnt.line as subamntline,
    dept.line as deptline";

    return $qry;
  }

  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $qry = "select " . $select . "
    
    from empprojdetail as empd
    left join projectroxas as comp on comp.compcode=empd.compcode and comp.code=empd.pjroxascode1
    left join subprojectroxas as subproj on subproj.code=empd.subpjroxascode and subproj.compcode=empd.compcode
    left join blocklotroxas as blocklot on blocklot.code=empd.blotroxascode and blocklot.compcode=empd.compcode
    left join amenityroxas as amnt on amnt.code=empd.amenityroxascode and amnt.compcode=empd.compcode
    left join subamenityroxas as subamnt on subamnt.code=empd.subamenityroxascode and subamnt.compcode=empd.compcode
    left join departmentroxas as dept on dept.code=empd.departmentroxascode and dept.compcode=empd.compcode 

    where empd.line=?
    ";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {

    if (isset($config['params']['sourcerow'])) {
      $row = $config['params']['sourcerow'];
    } else {
      $row = $config['params']['row'];
    }

    $qry = "select 
    
    " . $this->selectqry() . " 

    from empprojdetail as empd
    
    left join projectroxas as comp on comp.compcode=empd.compcode and comp.code=empd.pjroxascode1
    left join subprojectroxas as subproj on subproj.code=empd.subpjroxascode and subproj.compcode=empd.compcode
    left join blocklotroxas as blocklot on blocklot.code=empd.blotroxascode and blocklot.compcode=empd.compcode
    left join amenityroxas as amnt on amnt.code=empd.amenityroxascode and amnt.compcode=empd.compcode
    left join subamenityroxas as subamnt on subamnt.code=empd.subamenityroxascode and subamnt.compcode=empd.compcode
    left join departmentroxas as dept on dept.code=empd.departmentroxascode and dept.compcode=empd.compcode 

    where empd.empid=" . $row['empid'] . " and date(empd.dateid)='" . $row['dateid'] . "'";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
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


        if ($data[$key]['compcode'] == '') {
          return ['status' => false, 'msg' => 'Please select valid company.'];
        }

        if ($data[$key]['projcode'] == '') {
          return ['status' => false, 'msg' => 'Please select valid project.'];
        }

        if ($data[$key]['tothrs'] == '') {
          return ['status' => false, 'msg' => 'Please enter valid hours.'];
        }

        if ($data[$key]['rem'] == '') {
          return ['status' => false, 'msg' => 'Please enter valid remarks.'];
        }

        if ($data[$key]['line'] == 0) {



          $data2['compcode'] = $data[$key]['compcode'];
          $data2['pjroxascode1'] = $data[$key]['projcode'];
          $data2['subpjroxascode'] = $data[$key]['subprojcode'];
          $data2['blotroxascode'] = $data[$key]['blocklotcode'];
          $data2['amenityroxascode'] = $data[$key]['amntcode'];
          $data2['subamenityroxascode'] = $data[$key]['subamntcode'];
          $data2['departmentroxascode'] = $data[$key]['deptcode'];
          $data2['createdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['createby'] = $config['params']['user'];

          $this->coreFunctions->sbcinsert($this->table, $data2);
          $this->logger->sbcmasterlog($data[$key]['empid'], $config, ' CREATE - ' . $data[$key]['rem'],  0, 0, $data[$key]['dateno']);
        } else {

          $data2['compcode'] = $data[$key]['compcode'];
          $data2['pjroxascode1'] = $data[$key]['projcode'];
          $data2['subpjroxascode'] = $data[$key]['subprojcode'];
          $data2['blotroxascode'] = $data[$key]['blocklotcode'];
          $data2['amenityroxascode'] = $data[$key]['amntcode'];
          $data2['subamenityroxascode'] = $data[$key]['subamntcode'];
          $data2['departmentroxascode'] = $data[$key]['deptcode'];
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];

          $data2['rem'] = $data[$key]['rem'];
          $data2['tothrs'] = $data[$key]['tothrs'];

          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    }

    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata, 'isreloadgrid' => true];
  } // end function


  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    $this->logger->sbcdelmaster_log($row['empid'], $config, "REMOVE Line " . $row['line'] . " - " . $row['rem'] . " - Hrs: " . $row['tothrs'], 0, $row['dateno']);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'lookuprxscompany':
        return $this->lookuprxscompany($config);
        break;
      case 'lookuprjroxas':
        return $this->lookuprjroxas($config);
        break;
      case 'lookupamenityroxascode':
        return $this->lookupamenityroxascode($config);
        break;

      case 'lookupdepartmentroxascode':
        return $this->lookupdepartmentroxascode($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }


  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'EWT Setup Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')

    );

    $trno = $config['params']['sourcerow']['empid'];
    $trno2 = $config['params']['sourcerow']['dateno'];


    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno=" . $trno . " and log.trno2=" . $trno2 . "
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "' and log.trno=" . $trno . " and log.trno2=" . $trno2 . "";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }


  //company

  public function lookuprxscompany($config)
  {
    //default
    $plotting = array();
    // $plottype = '';
    $title = 'Company Name';
    $plotting = array(
      'compcode' => 'compcode', 'pjroxascode1' => 'projcode',
      'subpjroxascode' => 'subprojcode', 'blotroxascode' => 'blocklotcode',
      'departmentroxascode' => 'deptcode',
      'amenityroxascode' => 'amntcode', 'subamenityroxascode' => 'subamntcode',
      'amntcode' => 'amntcode', 'subamntcode' => 'subamntcode',
      'deptcode' => 'deptcode'
    );
    $plottype = 'plotgrid';
    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [['name' => 'compcode', 'label' => 'Company Code', 'align' => 'left', 'field' => 'compcode', 'sortable' => true, 'style' => 'font-size:16px;']];
    $qry = "select distinct compcode,'' as projcode, '' as subprojcode, '' as blocklotcode,
      '' as deptcode,''  as amntcode, '' as subamntcode
       from projectroxas";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function


  //Project
  public function lookuprjroxas($config)
  {
    //default
    $row = $config['params']['row'];
    $plotting = array();
    // $plottype = '';
    $title = 'Project Name';

    $plotting = array(
      'subprojcode' => 'subcode',
      'subpjroxascode' => 'subname', 'subprojline' => 'spline', 'projcode' => 'prcode',
      'pjroxascode1' => 'prname', 'projline' => 'prline', 'blocklotcode' => 'blkcode',
      'blotroxascode' => 'blockname', 'blocklotline' => 'blkline'
    );

    $plottype = 'plotgrid';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:1500px;max-width:1500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'prcode', 'label' => 'Project Code', 'align' => 'left', 'field' => 'prcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'prname', 'label' => 'Project Name', 'align' => 'left', 'field' => 'prname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'subcode', 'label' => 'Subproject Code', 'align' => 'left', 'field' => 'subcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'subname', 'label' => 'Subproject Name', 'align' => 'left', 'field' => 'subname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'blkcode', 'label' => 'Blocklot Code', 'align' => 'left', 'field' => 'blkcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'blockname', 'label' => 'Blocklot Name', 'align' => 'left', 'field' => 'blockname', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select pr.code as prcode,pr.name as prname,pr.line as prline,ifnull(sp.code,'') as subcode,ifnull(sp.name,'') as subname,sp.line as spline,
      concat(blk.phase,', ',blk.block,' ',blk.lot) as blockname,ifnull(blk.code,'') as blkcode,blk.line as blkline
      from projectroxas as pr
      left join subprojectroxas as sp on sp.compcode=pr.compcode and sp.parent=pr.code
      left join  blocklotroxas as blk on blk.subprojectcode=sp.code where pr.compcode='" . $row['compcode'] . "'";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function


  public function lookupamenityroxascode($config)
  {
    //default
    $row = $config['params']['row'];
    $plotting = array();

    $title = 'Amenity';

    $plotting = array(
      'amntcode' => 'amntcode', 'amenityroxascode' => 'amname', 'amntline' => 'amline',
      'subamntcode' => 'subamntcode', 'subamenityroxascode' => 'saname', 'subamntline' => 'saline'
    );
    $plottype = 'plotgrid';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:1500px;max-width:1500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'amntcode', 'label' => 'Amenity Code', 'align' => 'left', 'field' => 'amntcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'amname', 'label' => 'Amenity Name', 'align' => 'left', 'field' => 'amname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'subamntcode', 'label' => 'Sub Amenity Code', 'align' => 'left', 'field' => 'subamntcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'saname', 'label' => 'Sub Amenity Name', 'align' => 'left', 'field' => 'saname', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select am.compcode,am.line as amline, am.code as amntcode, am.name as amname,
      ifnull(sa.code,'') as subamntcode, sa.line as saline, ifnull(sa.name,'') as saname
      from amenityroxas as am
      left join subamenityroxas as sa on sa.parent=am.code and sa.compcode=am.compcode where am.compcode='" . $row['compcode'] . "'";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function


  public function lookupdepartmentroxascode($config)
  {
    //default
    $row = $config['params']['row'];
    $plotting = array();

    $title = 'Department';
    $plotting = array('deptcode' => 'deptcode', 'departmentroxascode' => 'name', 'deptline' => 'line');
    $plottype = 'plotgrid';

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:500px;max-width:500px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array(
      array('name' => 'deptcode', 'label' => 'Code', 'align' => 'left', 'field' => 'deptcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "select compcode,line, code as deptcode, name from departmentroxas where compcode= '" . $row['compcode'] . "'";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

} //end class
