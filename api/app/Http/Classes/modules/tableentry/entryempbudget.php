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
use App\Http\Classes\builder\lookupclass;
use App\Http\Classes\modules\inventory\va;

class entryempbudget
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Employee/Agent';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'empbudget';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['client', 'clientid', 'branchid', 'deptid', 'projectid', 'acnoid', 'janamt', 'febamt', 'maramt', 'apramt', 'mayamt', 'junamt', 'julamt', 'augamt', 'sepamt', 'octamt', 'novamt', 'decamt', 'total', 'budgetline', 'year'];
  public $showclosebtn = true;
  private $lookupclass;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->lookupclass = new lookupclass;
    $this->logger = new Logger;
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
    $action = 0;
    $name = 1;
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'client', 'janamt', 'febamt', 'maramt', 'apramt', 'mayamt', 'junamt', 'julamt', 'augamt', 'sepamt', 'octamt', 'novamt', 'decamt', 'total']]];

    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][1]['label'] = "Employee";
    $obj[0][$this->gridname]['columns'][1]['action'] = "lookupsetup";
    $obj[0][$this->gridname]['columns'][1]['lookupclass'] = "emplookup";
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';


    return $obj;
  }

  public function createtabbutton($config)
  {
    $addemployeeindex = 0;
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[$addemployeeindex]['label'] = "Add Employee";

    return $obj;
  }

  public function loaddata($config)
  {
    $filter = $config['params']['row'];
    $select = "line,year,client,clientid,branchid,deptid,projectid,acnoid,janamt,febamt,maramt,apramt,mayamt,junamt,julamt,augamt,sepamt,octamt,novamt,decamt,total,budgetline";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from $this->table
    where budgetline=" . $filter['line'] . " and year=" . $filter['year'] . " and branchid=" . $filter['branch'] . " and deptid=" . $filter['deptid'] . " and projectid=" . $filter['projectid'] . " and acnoid=" . $filter['acnoid'] . "
    order by line";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  }
  public function saveallloaddata($config)
  {

    $filter = $config[0];

    $select = "line,year,client,clientid,branchid,deptid,projectid,acnoid,janamt,febamt,maramt,apramt,mayamt,junamt,julamt,augamt,sepamt,octamt,novamt,decamt,total,budgetline";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from $this->table
    where budgetline=" . $filter['budgetline'] . " and year=" . $filter['year'] . " and branchid=" . $filter['branchid'] . " and deptid=" . $filter['deptid'] . " and projectid=" . $filter['projectid'] . " and acnoid=" . $filter['acnoid'] . "
    order by line";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  }

  private function loaddataperrecord($line)
  {
    $select = "line,year,client,clientid,branchid,deptid,projectid,acnoid,janamt,febamt,maramt,apramt,mayamt,junamt,julamt,augamt,sepamt,octamt,novamt,decamt,total,budgetline";
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . "
    from $this->table
    where line= $line
    order by line";

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function add($config)
  {
    $data = [];
    $id = $config['params']['sourcerow']['line'];
    $data['line'] = 0;
    $data['year'] = $config['params']['sourcerow']['year'];
    $data['clientid'] = '';
    $data['branchid'] = $config['params']['sourcerow']['branch'];
    $data['deptid'] = $config['params']['sourcerow']['deptid'];
    $data['projectid'] = $config['params']['sourcerow']['projectid'];
    $data['acnoid'] = $config['params']['sourcerow']['acnoid'];
    $data['janamt'] = '0.000000';
    $data['febamt'] = '0.000000';
    $data['maramt'] = '0.000000';
    $data['apramt'] = '0.000000';
    $data['mayamt'] = '0.000000';
    $data['junamt'] = '0.000000';

    $data['julamt'] = '0.000000';
    $data['augamt'] = '0.000000';
    $data['sepamt'] = '0.000000';
    $data['octamt'] = '0.000000';
    $data['novamt'] = '0.000000';
    $data['decamt'] = '0.000000';
    $data['total'] = '0.000000';
    $data['bgcolor'] = 'bg-blue-2';
    $data['budgetline'] = $id;

    return $data;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $params = $config;
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $data['total'] = $data['janamt'] + $data['febamt'] + $data['maramt'] + $data['apramt'] + $data['mayamt'] + $data['junamt'] + $data['julamt'] + $data['augamt'] + $data['sepamt'] + $data['octamt'] + $data['novamt'] + $data['decamt'];
    if ($row['line'] == 0) {


      $line = $this->coreFunctions->insertGetId($this->table, $data);

      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);


        $this->logger->sbcmasterlog($row['line'], $params, ' CREATE - EMPLOYEE CODE: ' . $row['client'] . ', 
        JAN AMT:' . $row['janamt'] . ', FEB AMT:' . $row['febamt'] . ', MAR AMT:' . $row['maramt'] . ', APR AMT:' . $row['apramt'] . ', 
        MAY AMT:' . $row['mayamt'] . ', JUN AMT:' . $row['junamt'] . ', JUL AMT:' . $row['julamt'] . ', AUG AMT:' . $row['augamt'] . ', 
        SEP AMT:' . $row['sepamt'] . ', OCT AMT:' . $row['octamt'] . ', NOV AMT:' . $row['novamt'] . ', DEC AMT:' . $row['decamt'] . ', TOTAL AMT:' . $row['total']);


        $returnrow = $this->loaddataperrecord($line);

        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {

        $this->logger->sbcmasterlog($row['line'], $params, ' UPDATE - EMPLOYEE CODE: ' . $row['client'] . ', 
            JAN AMT:' . $row['janamt'] . ', FEB AMT:' . $row['febamt'] . ', MAR AMT:' . $row['maramt'] . ', APR AMT:' . $row['apramt'] . ', 
            MAY AMT:' . $row['mayamt'] . ', JUN AMT:' . $row['junamt'] . ', JUL AMT:' . $row['julamt'] . ', AUG AMT:' . $row['augamt'] . ', 
            SEP AMT:' . $row['sepamt'] . ', OCT AMT:' . $row['octamt'] . ', NOV AMT:' . $row['novamt'] . ', DEC AMT:' . $row['decamt'] . ', TOTAL AMT:' . $row['total']);

        $returnrow = $this->loaddataperrecord($row['line'], $config['params']['doc']);
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
        $data2['total'] = $data2['janamt'] + $data2['febamt'] + $data2['maramt'] + $data2['apramt'] + $data2['mayamt'] + $data2['junamt'] + $data2['julamt'] + $data2['augamt'] + $data2['sepamt'] + $data2['octamt'] + $data2['novamt'] + $data2['decamt'];
        if ($data[$key]['line'] == 0) {
          $this->coreFunctions->insertGetId($this->table, $data2);
          $params = $config;
          $params['params']['doc'] = strtoupper("entryattendee");

          $this->logger->sbcmasterlog($data[$key]['line'], $params, ' CREATE - EMPLOYEE CODE: ' . $data[$key]['client'] . ', 
            JAN AMT:' . $data[$key]['janamt'] . ', FEB AMT:' . $data[$key]['febamt'] . ', MAR AMT:' . $data[$key]['maramt'] . ', APR AMT:' . $data[$key]['apramt'] . ', 
            MAY AMT:' . $data[$key]['mayamt'] . ', JUN AMT:' . $data[$key]['junamt'] . ', JUL AMT:' . $data[$key]['julamt'] . ', AUG AMT:' . $data[$key]['augamt'] . ', 
            SEP AMT:' . $data[$key]['sepamt'] . ', OCT AMT:' . $data[$key]['octamt'] . ', NOV AMT:' . $data[$key]['novamt'] . ', DEC AMT:' . $data[$key]['decamt'] . ', TOTAL AMT:' . $data[$key]['total']);
        } else {
          $params = $config;
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);

          $this->logger->sbcmasterlog($data[$key]['line'], $params, ' UPDATE - EMPLOYEE CODE: ' . $data[$key]['client'] . ', 
            JAN AMT:' . $data[$key]['janamt'] . ', FEB AMT:' . $data[$key]['febamt'] . ', MAR AMT:' . $data[$key]['maramt'] . ', APR AMT:' . $data[$key]['apramt'] . ', 
            MAY AMT:' . $data[$key]['mayamt'] . ', JUN AMT:' . $data[$key]['junamt'] . ', JUL AMT:' . $data[$key]['julamt'] . ', AUG AMT:' . $data[$key]['augamt'] . ', 
            SEP AMT:' . $data[$key]['sepamt'] . ', OCT AMT:' . $data[$key]['octamt'] . ', NOV AMT:' . $data[$key]['novamt'] . ', DEC AMT:' . $data[$key]['decamt'] . ', TOTAL AMT:' . $data[$key]['total']);
        }
      } // end if
    } // foreach
    $returndata = $this->saveallloaddata($data);

    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);

    $returnrow = $this->loaddataperrecord($row['line']);

    $params = $config;
    $this->logger->sbcmasterlog($row['line'], $params, ' DELETE - EMPLOYEE CODE: ' . $row['client'] . ', 
            JAN AMT:' . $row['janamt'] . ', FEB AMT:' . $row['febamt'] . ', MAR AMT:' . $row['maramt'] . ', APR AMT:' . $row['apramt'] . ', 
            MAY AMT:' . $row['mayamt'] . ', JUN AMT:' . $row['junamt'] . ', JUL AMT:' . $row['julamt'] . ', AUG AMT:' . $row['augamt'] . ', 
            SEP AMT:' . $row['sepamt'] . ', OCT AMT:' . $row['octamt'] . ', NOV AMT:' . $row['novamt'] . ', DEC AMT:' . $row['decamt'] . ', TOTAL AMT:' . $row['total']);

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];

    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;

      case 'emplookup': // na deretso po dito
        return $this->lookupemployee($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }

  public function lookupemployee($config)
  {

    $title = 'List of Employee';


    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array(
        'clientid' => 'clientid',
        'client' => 'clientname'
      )
    );


    $cols = [
      ['name' => 'client', 'label' => 'Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;']

    ];

    $qry = "select clientid,client,clientname from client where isemployee=1 order by client";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function



  public function lookuplogs($config)
  {
    $doc = strtoupper('ENTRYBUDGET');
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Employee Budget Master Logs',
      'style' => 'width:1000px;max-width:1000px;'
    );

    // lookup columns
    $cols = array(
      array('name' => 'user', 'label' => 'User', 'align' => 'left', 'field' => 'user', 'sortable' => true, 'style' => 'font-size:16px;'),
      // array('name' => 'doc', 'label' => 'Doc', 'align' => 'left', 'field' => 'doc', 'sortable' => true, 'style' => 'font-size:16px;'),
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
