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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class entryagentquota
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AGENTQUOTA';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $logger;
  private $table = 'agentquota';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['clientid', 'amount', 'projectid', 'yr', 'janamt', 'febamt', 'maramt', 'apramt', 'mayamt', 'junamt', 'julamt', 'augamt', 'sepamt', 'octamt', 'novamt', 'decamt'];
  public $showclosebtn = false;
  private $reporter;
  //Declare the constants up here


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
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
    $companyid = $config['params']['companyid'];
    if (isset($config['params']['tableid'])) {
      $clientid = $config['params']['tableid'];
      $customername = $this->coreFunctions->datareader("select clientname as value from client where clientid = ? ", [$clientid]);
      $this->modulename = $this->modulename . ' - ' . $customername;
    }

    $action = 0;
    $project = 1;
    $yr = 2;
    $jan = 3;
    $feb = 4;
    $mar = 5;
    $apr = 6;
    $may = 7;
    $jun = 8;
    $jul = 9;
    $aug = 10;
    $sep = 11;
    $oct = 12;
    $nov = 13;
    $dec = 14;
    $amount = 15;


    // This is for the columns in the grid when it first loads
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'project', 'yr', 'janamt', 'febamt', 'maramt', 'apramt', 'mayamt', 'junamt', 'julamt', 'augamt', 'sepamt', 'octamt', 'novamt', 'decamt', 'amount']]];

    // The buttons that will go under the action column
    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // styling or setting up functions and types of columns(lookup, search)
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:100px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$project]['style'] = "width:100px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$amount]['style'] = "width:100px;whiteSpace: normal;min-width:150px;";

    $obj[0][$this->gridname]['columns'][$project]['action'] = "lookupsetup";

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $obj[0][$this->gridname]['columns'][$jan]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$feb]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$mar]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$apr]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$may]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$jun]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$jul]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$aug]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$sep]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$oct]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$nov]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$dec]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$yr]['type'] = 'coldel';
        break;
      case 32: //3m
        $obj[0][$this->gridname]['columns'][$project]['type'] = 'coldel';
        $obj[0][$this->gridname]['columns'][$amount]['type'] = 'coldel';
        break;
    }
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  // the buttons above the grid
  public function createtabbutton($config)
  {
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  // function called when clicking the add button above the grid
  public function add($config)
  {
    $data = [];

    // Initializing the row data that will be used
    $data['line'] = 0;
    $data['clientid'] = $config['params']['tableid'];
    $data['amount'] = '0.00';
    $data['projectid'] = 0;
    $data['project'] = '';
    $data['yr'] = 0;
    $data['janamt'] = 0;
    $data['febamt'] = 0;
    $data['maramt'] = 0;
    $data['apramt'] = 0;
    $data['mayamt'] = 0;
    $data['junamt'] = 0;
    $data['julamt'] = 0;
    $data['augamt'] = 0;
    $data['sepamt'] = 0;
    $data['octamt'] = 0;
    $data['novamt'] = 0;
    $data['decamt'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  //Saves all rows
  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    $msg = 'All saved successfully.';
    $stat = true;
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        if ($data[$key]['line'] == 0) {
          $chk = $this->checkduplicate($data2['clientid'], $data2['projectid'], $data2['yr']);
          if ($chk != 0) {
            $status = $this->coreFunctions->insertGetId($this->table, $data2);
            if ($companyid == 10 || $companyid == 12) { //afti, afti usd
              $this->logger->sbcmasterlog(
                $data2['clientid'],
                $config,
                'CREATE-        
             PROJECTID: ' . $data2['projectid'] .
                  ' AMOUNT: ' . $data2['amount']
              );
            } else {
              $this->logger->sbcmasterlog(
                $data2['clientid'],
                $config,
                'CREATE-YEAR: ' . $data2['yr'] .
                  ' JANUARY: ' . $data2['janamt'] .
                  ' FEBRUARY: ' . $data2['febamt'] .
                  ' MARCH: ' . $data2['maramt'] .
                  ' APRIL: ' . $data2['apramt'] .
                  ' MAY: ' . $data2['mayamt'] .
                  ' JUNE: ' . $data2['junamt'] .
                  ' JULY: ' . $data2['julamt'] .
                  ' AUGUST: ' . $data2['augamt'] .
                  ' SEPTEMBER: ' . $data2['sepamt'] .
                  ' OCTOBER: ' . $data2['octamt'] .
                  ' NOVEMBER: ' . $data2['novamt'] .
                  ' DECEMBER: ' . $data2['decamt']
              );
            }
          } else {
            $status = 0;
            $msg = 'Qouta Setup Already Exist. Save all unsuccessful.';
            $stat = false;
          }
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => $stat, 'msg' => $msg, 'data' => $returndata];
  } // end function



  // Saves per row
  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if ($row['line'] == 0) {

      $chk = $this->checkduplicate($data['clientid'], $data['projectid'], $data['yr']);
      if ($chk != 0) {
        $line = $this->coreFunctions->insertGetId($this->table, $data);
        if ($companyid == 10 || $companyid == 12) { //afti, afti usd
          $this->logger->sbcmasterlog(
            $data['clientid'],
            $config,
            'CREATE-        
        PROJECTID: ' . $data['projectid'] .
              ' AMOUNT: ' . $data['amount']
          );
        } else {
          $this->logger->sbcmasterlog(
            $data['clientid'],
            $config,
            'CREATE-
           YEAR: ' . $data['yr'] .
              ' JANUARY: ' . $data['janamt'] .
              ' FEBRUARY: ' . $data['febamt'] .
              ' MARCH: ' . $data['maramt'] .
              ' APRIL: ' . $data['apramt'] .
              ' MAY: ' . $data['mayamt'] .
              ' JUNE: ' . $data['junamt'] .
              ' JULY: ' . $data['julamt'] .
              ' AUGUST: ' . $data['augamt'] .
              ' SEPTEMBER: ' . $data['sepamt'] .
              ' OCTOBER: ' . $data['octamt'] .
              ' NOVEMBER: ' . $data['novamt'] .
              ' DECEMBER: ' . $data['decamt']

          );
        }
      } else {
        $line = 0;
      }

      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($config, $line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed. Qouta setup had already existed.'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($config, $row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  // Check for duplicates
  public function checkduplicate($client, $project, $yr = 0)
  {
    if ($yr != 0) {
      $chkqry = "select clientid,projectid from " . $this->table . " where clientid=" . $client . " and projectid=" . $project . " and yr = " . $yr;
    } else {
      $chkqry = "select clientid,projectid from " . $this->table . " where clientid=" . $client . " and projectid=" . $project . " ";
    }
    $chkdeets = $this->coreFunctions->opentable($chkqry);
    if (empty($chkdeets)) {
      return 1;
    } else {
      return 0;
    }
  }

  // Handled by the delete button, deletes a row
  public function delete($config)
  {

    $tableid = $config['params']['tableid'];
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];
    $data = $this->loaddataperrecord($config, $row['clientid']);


    $qry = "delete from " . $this->table . " where clientid=? and projectid=? and line = ?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['clientid'], $row['projectid'], $row['line']]);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $this->logger->sbcdelmaster_log($row['clientid'], $config, 'REMOVE - ' . $row['code'] . ' - ' . $row['name']);
    } else {
      $this->logger->sbcdelmaster_log($row['clientid'], $config, 'REMOVE - ' . $row['yr']);
    }
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }

  // Function that reloads the grid when an action is done(saving/delete)
  private function loaddataperrecord($config, $line)
  {
    $tableid = $config['params']['tableid'];

    $qry = "select t.line,c.clientname, c.client, c.clientid, t.amount, p.name as project, ifnull(p.line,0) as projectid,t.yr,t.janamt,t.febamt,t.maramt,t.apramt,t.mayamt,t.junamt,t.julamt,t.augamt,t.sepamt,t.octamt,t.novamt,t.decamt, '' as bgcolor
    from " . $this->table . "  as t
    left join client as c on t.clientid = c.clientid
    left join projectmasterfile as p on p.line = t.projectid
    where t.line = " . $line . " order by t.line";
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  // the first function called when opening the tab, responsible for displaying the data in the grid
  public function loaddata($config)
  {
    $tableid = $config['params']['tableid'];

    $qry = "select t.line,c.clientname, c.client, c.clientid, t.amount, p.name as project, ifnull(p.line,0) as projectid,t.yr,t.janamt,t.febamt,t.maramt,t.apramt,t.mayamt,t.junamt,t.julamt,t.augamt,t.sepamt,t.octamt,t.novamt,t.decamt, '' as bgcolor
    from " . $this->table . "  as t
    left join client as c on t.clientid = c.clientid
    left join projectmasterfile as p on p.line = t.projectid
    where t.clientid = " . $tableid . " order by t.line";
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }


  // function called in the action setup in createtab
  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'dproject':
        return $this->lookupproject($config);
        break;
    }
  }

  // Modal shown when clicking on the magnifying glass
  public function lookupproject($config)
  {

    $title = 'List of Project';
    // Where the data, once selected, will be plotted
    $plotting = [
      'projectid' => 'line',
      'project' => 'name'
    ];

    // required to plot data in grid and intialized values
    $plottype = 'plotgrid';
    //grid ->plotgrid
    //head ->plothead

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns

    // Name and field should be the same in the qry
    $cols = [
      ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select line,code,name from projectmasterfile order by line";
    $data = $this->coreFunctions->opentable($qry);


    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function

  public function lookuplogs($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Agent Qouta Logs',
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

  // -> Print Function
  public function reportsetup($config)
  {
    return [];
  }


  public function createreportfilter()
  {
    return [];
  }

  public function reportparamsdata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    return [];
  }
} //end class
