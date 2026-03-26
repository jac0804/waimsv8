<?php

namespace App\Http\Classes\modules\queuing;

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

class closequeue
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CLOSE QUEUING DAY';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $table = 'currentservice';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['dateid'];
  public $showclosebtn = false;
  private $enrollmentlookup;
  private $logger;

  public $issearchshow = false;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->enrollmentlookup = new enrollmentlookup;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 5624
  );
    return $attrib;
  }

  public function createHeadbutton($config)
    {
        return [];
    }
  
    public function createHeadField($config)
  {
    return [];
  }

  public function paramsdata($config)
    {

      return [];
    }

    public function data($config)
    {
        return $this->paramsdata($config);
    }

  public function createTab($config)
  {
    $columns = ['isselected', 'dateid', 'served','pwdamt','pending'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = [];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];


    $obj = $this->tabClass->createTab($tab, $stockbuttons);
    $obj[0][$this->gridname]['label'] = 'LIST';
    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$served]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$pending]['label'] = 'Cancel';
    $obj[0][$this->gridname]['columns'][$pwdamt]['label'] = 'Priority';
    $obj[0][$this->gridname]['columns'][$pwdamt]['align'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$pwdamt]['style'] = 'text-align:left;width:100px;whiteSpace: normal;min-width:100px;';
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];

    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "CLOSE DATE";
    return $obj;
  }

  public function loaddata($config)
  {
    $qry    = "select distinct date_format(dateid,'%m/%d/%Y') as dateid,sum(isdone) as served,sum(iscancel) as pending,sum(ispwd) as pwdamt,'false' as isselected from currentservice group by date_format(dateid,'%m/%d/%Y')";
    $data = $this->coreFunctions->opentable($qry);


    return $data;
  }

  private function selectqry()
  {
    return;
  }

  public function add($config)
  {
    $data = [];
    return $data;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      if($data[$key]['isselected'] == 'true'){
          $data[$key]['dateid'] = $this->othersClass->sanitizekeyfield('dateonly',$data[$key]['dateid']);
          $prevdate = $this->coreFunctions->getfieldvalue('currentservice','left(dateid,10)','left(dateid,10)<?',[$data[$key]['dateid']]);
        
          if ($prevdate !="") {
            return ['status' => false, 'msg' => 'Please close previous dates'];
          }
          $return =  $this->coreFunctions->execqry("insert into hcurrentservice(line,serviceline,ctr,counterline,isdone,ishold,iscancel,ispwd,isskip,dateid,startdate,enddate,users)
          select line,serviceline,ctr,counterline,isdone,ishold,iscancel,ispwd,isskip,dateid,startdate,enddate,users from currentservice where left(dateid,10) = ?",'insert',[$data[$key]['dateid']]);
          if($return){
              $this->coreFunctions->execqry("delete from currentservice where left(dateid,10) = ?",'delete',[$data[$key]['dateid']]);
          }
      }
      
    }
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  }

  public function save($config)
  {
    
  } // end function

  public function delete($config)
  {
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
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
      'title' => 'Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'task', 'label' => 'Task', 'align' => 'left', 'field' => 'task', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date Occured', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $trno = $config['params']['tableid'];

    $qry = "
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from " . $this->tablelogs . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'
    union all
    select trno, doc, task, log.user, dateid, 
    if(pic='','blank_user.png',pic) as pic
    from  " . $this->tablelogs_del . " as log
    left join useraccess as u on u.username=log.user
    where log.doc = '" . $doc . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }
} //end class
