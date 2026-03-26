<?php

namespace App\Http\Classes\modules\taskentry;

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

class entrytask
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'TASK';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'tmdetail';
  private $othersClass;
  public $style = 'width:100%;';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  private $fields = ['task', 'title', 'userid', 'startdate', 'enddate', 'percentage', 'taskcatid'];
  public $showclosebtn = false;
  private $reporter;
  public $logger;
  private $reportheader;


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
    $attrib = array(
      'load' => 5465
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $viewrate = $this->othersClass->checkAccess($config['params']['user'], 5480);

    $columns = ['action', 'title', 'user', 'taskcategory', 'startdate', 'enddate'];

    if ($viewrate != '0') {
      $columns = ['action', 'title', 'user', 'percentage',  'taskcategory', 'startdate', 'enddate'];
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns
      ]
    ];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $addtaskdetails = $this->othersClass->checkAccess($config['params']['user'], 5470);
    $viewattachment = $this->othersClass->checkAccess($config['params']['user'], 5472);
    $reassign = $this->othersClass->checkAccess($config['params']['user'], 5478);
    $delete = $this->othersClass->checkAccess($config['params']['user'], 5572);

    $stockbuttons = ['save', 'reassign', 'viewhistoricalcomments'];

    if ($addtaskdetails == '1') {
      array_push($stockbuttons, 'viewtaskinfo');
    }

    if ($viewattachment == '1') {
      array_push($stockbuttons, 'addattachments');
    }

    if ($reassign == '1') {
      array_push($stockbuttons, 'reassign');
    }

    if ($delete == '1') {
      array_push($stockbuttons, 'delete');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['checkfield'] = 'void';
    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0][$this->gridname]['columns'][$title]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';

    $obj[0][$this->gridname]['columns'][$user]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][$title]['label'] = 'Task';
    $obj[0][$this->gridname]['columns'][$title]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$user]['label'] = 'Assigned to';
    $obj[0][$this->gridname]['columns'][$user]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$user]['lookupclass'] = 'lookupusers';
    $obj[0][$this->gridname]['columns'][$user]['action'] = 'lookupsetup';


    $obj[0][$this->gridname]['columns'][$startdate]['label'] = 'Start Date';
    $obj[0][$this->gridname]['columns'][$enddate]['label'] = 'End Date';
    $obj[0][$this->gridname]['columns'][$startdate]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$enddate]['readonly'] = true;
    // $obj[0][$this->gridname]['columns'][$action]['btns']['viewhistoricalcomments']['checkfield'] = 'iscomment';

    if ($viewrate != '0') {
      $obj[0][$this->gridname]['columns'][$percentage]['style'] = 'width:90px;whiteSpace: normal;min-width:90;';
      $obj[0][$this->gridname]['columns'][$percentage]['label'] = 'Percentage';
    }
    if ($addtaskdetails == '1') {
      $obj[0][$this->gridname]['columns'][$action]['btns']['viewtaskinfo']['checkfield'] = 'istaskdetails';
    }

    if ($viewattachment == '1') {
      $obj[0][$this->gridname]['columns'][$action]['btns']['addattachments']['checkfield'] = 'isattachment';
    }

    if ($reassign == '1') {
      $obj[0][$this->gridname]['columns'][$action]['btns']['reassign']['checkfield'] = 'isreassign';
    }

    if ($delete == '1') {
      $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['label'] = ' Delete Task';
      $obj[0][$this->gridname]['columns'][$action]['btns']['delete']['checkfield'] = 'isdelete';
    }


    $obj[0][$this->gridname]['columns'][$startdate]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$enddate]['type'] = 'label';


    return $obj;
  }


  public function createtabbutton($config)
  {
    $tableid = $config['params']['tableid'];
    $tbuttons = ['addrecord', 'saveallentry', 'whlog'];
    $isclose = $this->isclose($config);
    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($isclose || $tableid == 0) {
      $obj[0]['visible'] = false;
      $obj[1]['visible'] = false;
    }
    return $obj;
  }


  public function add($config)
  {
    $trno = $config['params']['tableid'];

    $data = [];
    $data['trno'] = $trno;
    $data['line'] = 0;
    $data['task'] = '';
    $data['title'] = '';
    $data['percentage'] = 0;
    $data['userid'] = 0;
    $data['user'] = '';
    $data['startdate'] = '';
    $data['enddate'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    // $data['iscomment'] = 'true';
    $data['istaskdetails'] = 'true';
    $data['isattachment'] = 'true';
    $data['isreassign'] = 'true';
    $data['isdelete'] = 'true';
    $data['taskcatid'] = 0;
    $data['taskcategory'] = '';
    return $data;
  }

  private function selectqry()
  {
    $qry = "r.trno,r.line,c.client,c.clientname as user,'false' as istaskdetails,'false' as isattachment,'false' as isreassign,'false' as isdelete, req.category as taskcategory "; //'false' as iscomment,
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',r.' . $value;
    }
    return $qry;
  }

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    // unset($data['iscomment']);
    unset($data['istaskdetails']);
    unset($data['isattachment']);
    unset($data['isreassign']);
    unset($data['isdelete']);
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['tableid'];
    $isopen = $this->coreFunctions->getfieldvalue("tmhead", "status", "trno=?", [$trno]);
    $tasktype = $this->coreFunctions->getfieldvalue("tmhead", "tasktype", "trno=?", [$trno]);
    $isdailytask = $this->coreFunctions->getfieldvalue("reqcategory", "isdailytask", "line=?", [$tasktype]);


    foreach ($data as $key => $value) {
      $data2 = [];

      if ($data[$key]['bgcolor'] != '' && $data[$key]['bgcolor'] != 'bg-red-2') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }

        if ($data[$key]['taskcatid'] == 0) {
          return ['status' => false, 'msg' => 'Task category is empty'];
        }


        if (trim($data[$key]['title'] == '')) {
          return ['status' => false, 'msg' => 'Task is empty'];
        }

        if ($data[$key]['line'] == 0) {
          $data2['trno'] = $trno;
          $data2['status'] = $isopen;
          $data2['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['encodedby'] = $config['params']['user'];
          if ($isdailytask == 1) {
            $data2['userid'] = $config['params']['adminid'];
          }

          $line = $this->coreFunctions->datareader("select max(line) as value from " . $this->table . " where trno = ?", [$trno]);
          if ($line == '') {
            $line = 0;
          }

          $line = $line + 1;
          $data2['line'] = $line;

          $insert = $this->coreFunctions->sbcinsert($this->table, $data2);

          //$line = $this->coreFunctions->insertGetId($this->table, $data2);
          if ($isdailytask == 0) {
            if ($isopen == 1) {
              if ($data2['userid'] != 0) {
                $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
                $this->othersClass->insertUpdatePendingapp($trno, $line, 'TM', [], $url, $config, $data2['userid'], false, true); //insert sa pendingapp
              }
            }
          }

          $config['params']['doc'] = 'ENTRYTASK';
          $this->logger->sbcmasterlog($trno, $config, ' CREATE - TASK: ' . $data[$key]['title']
            . ' , Percentage: ' . $data[$key]['percentage']
            . ' , Assigned: ' . $data[$key]['user']
            . ' , Start: ' . $data[$key]['startdate']
            . ' , End: ' . $data[$key]['enddate']);
        } else {
          $data2['editdate'] = $this->othersClass->getCurrentTimeStamp();
          $data2['editby'] = $config['params']['user'];
          if ($data2['startdate'] != null) {
            unset($data2['userid']);
          } else {
            if ($isopen == 1) {
              //check kung may laman ang acceptdate 
              $acceptdate = $this->coreFunctions->datareader("select  acceptdate as value from tmdetail where trno = ? and line=?", [$trno, $data[$key]['line']]);
              //check kung nasa pending app na ang task
              $pending = $this->coreFunctions->opentable("select trno, line from pendingapp where trno='" . $trno . "' and line='" . $data[$key]['line'] . "'");

              if (!empty($pending)) {
                //dalete acceptdate nung dating nag accept pero hindi nag start
                if ($acceptdate != null) {
                  $this->coreFunctions->sbcupdate('tmdetail', ['acceptdate' => null], ['trno' => $trno, 'line' => $line]);
                }
                $this->coreFunctions->sbcupdate('pendingapp', ['clientid' => $data2['userid']], ['trno' => $trno, 'line' => $data[$key]['line']]);
              } else { //wala pa sa pending app
                if ($data2['userid'] != 0) {
                  $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
                  $this->othersClass->insertUpdatePendingapp($trno, $data[$key]['line'], 'TM', [], $url, $config, $data2['userid'], false, true); //insert sa pendingapp
                }
              }
            }
          }
          $this->coreFunctions->sbcupdate($this->table, $data2, ['trno' => $trno, 'line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function

  public function save($config, $void = false)
  {
    $data = [];
    $tbl = $this->table;
    $row = $config['params']['row'];
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['tableid'];
    $isopen = $this->coreFunctions->getfieldvalue("tmhead", "status", "trno=?", [$trno]);
    $tasktype = $this->coreFunctions->getfieldvalue("tmhead", "tasktype", "trno=?", [$trno]);
    $isdailytask = $this->coreFunctions->getfieldvalue("reqcategory", "isdailytask", "line=?", [$tasktype]);


    if ($void) {
      $tbl = "voidtm";
    }

    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }

    if (trim($row['title']) == '') {
      return ['status' => false, 'msg' => 'Task is empty'];
    }

    if ($row['taskcatid'] == 0) {
      return ['status' => false, 'msg' => 'Task category is empty'];
    }

    if ($row['line'] == 0) {
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      $data['status'] = $isopen;
      $data['trno'] = $trno;

      if ($isdailytask == 1) {
        $data['userid'] = $config['params']['adminid'];
      }

      $line = $this->coreFunctions->datareader("select max(line) as value from $tbl where trno = ?", [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $data['line'] = $line;
      $insert = $this->coreFunctions->sbcinsert($tbl, $data);
      if ($insert) {
        if ($isdailytask == 0) {
          if ($isopen == 1) {
            if ($data['userid'] != 0) {
              $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
              $this->othersClass->insertUpdatePendingapp($trno, $line, 'TM', [], $url, $config, $data['userid'], false, true); //insert sa pendingapp
            }
          }
        }

        $returnrow = $this->loaddataperrecord($trno, $line);
        // $this->logger->sbcmasterlog($line, $config, ' CREATE - ' . $data['title']);
        $config['params']['doc'] = 'ENTRYTASK';
        $this->logger->sbcmasterlog($trno, $config, ' CREATE - TASK :' . $data['title']
          . ' , Percentage: ' . $data['percentage']
          . ' , Assigned: ' . $row['user']
          . ' , Start: ' . $data['startdate']
          . ' , End: ' . $data['enddate']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($void) {
        if (!empty($row['fcheckingdate'])) {
          return ['status' => false, 'msg' => 'Already tagged as for checking'];
        }

        if ($row['enddate'] != '') {
          return ['status' => false, 'msg' => 'Already completed'];
        }

        $data['voiddate'] = $this->othersClass->getCurrentTimeStamp();
        $data['voidby'] = $config['params']['user'];
        $data['linex'] = $row['line'];
        $data['trno'] = $trno;
        $line = $this->coreFunctions->insertGetId($tbl, $data);
        if ($line != 0) {
          $this->coreFunctions->sbcupdate($this->table, ['userid' => 0, 'startdate' => null, 'status' => 1], ['trno' => $trno, 'line' => $row['line']]);
          $returndata = $this->loaddata($config);
          return ['status' => true, 'msg' => 'Successfully reassign.', 'data' => $returndata, 'backlisting' => true];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      } else {
        // var_dump($data);
        $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['editby'] = $config['params']['user'];
        if ($data['startdate'] != null) {
          unset($data['userid']);
        } else {
          if ($isopen == 1) {
            //check kung may laman ang acceptdate 
            $acceptdate = $this->coreFunctions->datareader("select  acceptdate as value from tmdetail where trno = ? and line=?", [$trno, $row['line']]);
            //check kung nasa pending app na ang task
            $pending = $this->coreFunctions->opentable("select trno, line from pendingapp where trno='" . $trno . "' and line='" . $row['line'] . "'");

            if (!empty($pending)) {
              //dalete acceptdate nung dating nag accept pero hindi nag start
              if ($acceptdate != null) {
                $this->coreFunctions->sbcupdate('tmdetail', ['acceptdate' => null], ['trno' => $trno, 'line' => $row['line']]);
              }
              $this->coreFunctions->sbcupdate('pendingapp', ['clientid' => $data['userid']], ['trno' => $trno, 'line' => $row['line']]);
            } else { //wala pa sa pending app
              if ($data['userid'] != 0) {
                $url = 'App\Http\Classes\modules\taskmonitoring\\' . 'tm';
                $this->othersClass->insertUpdatePendingapp($trno, $row['line'], 'TM', [], $url, $config, $data['userid'], false, true); //insert sa pendingapp
              }
            }
          }
        }
        if ($this->coreFunctions->sbcupdate($tbl, $data, ['trno' => $trno, 'line' => $row['line']]) == 1) {
          $returnrow = $this->loaddataperrecord($trno, $row['line']);
          // $this->logger->sbcmasterlog($row['line'], $config, ' UPDATE - ' . $data['title']);
          return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
        } else {
          return ['status' => false, 'msg' => 'Saving failed.'];
        }
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $startdate = $config['params']['row']['startdate'];

    if ($startdate == null) {
      $qry = "delete from " . $this->table . " where trno= ? and line=?";
      $try =  $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
      if ($try == 1) {
        $config['params']['doc'] = 'ENTRYTASK';
        $this->logger->sbcdelmaster_log($row['trno'], $config, 'REMOVE - Line : ' . $row['line'] . ' , Task : ' . $row['title']);
        $this->coreFunctions->execqry('delete from headprrem where tmtrno=? and tmline=?', 'delete', [$row['trno'], $row['line']]);
        $this->coreFunctions->execqry('delete from waims_attachments where trno=? and tmline=?', 'delete', [$row['trno'], $row['line']]);
        $this->coreFunctions->execqry('delete from pendingapp where trno=? and line=?', 'delete', [$row['trno'], $row['line']]);
      }
      return ['status' => true, 'msg' => 'Successfully deleted.'];
    } else {
      return ['status' => false, 'msg' => 'Delete Failed. This task has already been started.'];
    }
  }


  private function loaddataperrecord($trno, $line)
  {
    $select = $this->selectqry();
    $qry = "select " . $select . ",'' as bgcolor,'false' as void 
      from tmdetail as r 
      left join useraccess as u on u.userid = r.userid
      left  join client as c on c.clientid = r.userid
      left join reqcategory as req on req.line=r.taskcatid
       where r.trno = ? and r.line=?
      union all
      select " . $select . ",'bg-red-2' as bgcolor,'true' as void 
      from voidtm as r 
      left  join client as c on c.clientid = r.userid
      left join reqcategory as req on req.line=r.taskcatid
      where r.trno =? and r.line=?  ";
    // var_dump($qry);
    $data = $this->coreFunctions->opentable($qry, [$trno, $line, $trno, $line]);
    return $data;
  }


  public function loaddata($config)
  {
    // var_dump($config);
    $select = $this->selectqry();
    //$select = $select . ",'' as bgcolor ";
    $company = $config['params']['companyid'];
    $trno = $config['params']['tableid'];
    $limit = '';
    $filtersearch = "";
    $searcfield = ['r.title', 'c.clientname'];
    $search = '';

    if (isset($config['params']['filter'])) {
      $search = $config['params']['filter'];
      foreach ($searcfield as $key => $sfield) {
        if ($filtersearch == "") {
          $filtersearch .= " and (" . $sfield . " like '%" . $search . "%'";
        } else {
          $filtersearch .= " or " . $sfield . " like '%" . $search . "%'";
        } //end if
      }
      $filtersearch .= ")";
    }

    if ($search != "") {
      $l = '';
    } else {
      $l = $limit;
    }
    $qry = "select " . $select . ",'' as bgcolor,'false' as void,'a' as sort from " . $this->table . " as r 
      left  join client as c on c.clientid = r.userid
      left join reqcategory as req on req.line=r.taskcatid
      where r.trno =?  " . $filtersearch . "
      union all
      select " . $select . ",'bg-red-2' as bgcolor,'true' as void,'z' as sort from voidtm as r 
      left  join client as c on c.clientid = r.userid
      left join reqcategory as req on req.line=r.taskcatid
      where r.trno =?  " . $filtersearch . " order by sort $l";
    // var_dump($qry);
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass2 = $config['params']['lookupclass2'];
    switch ($lookupclass2) {
      case 'whlog':
        return $this->lookuplogs($config);
        break;
      case 'lookupusers':
        return $this->lookupusers($config);
        break;
      case 'lookupcomplex';
        return $this->lookupcomplexity($config);
        break;
      case 'lookuptaskcategory';
        return $this->lookuptaskcategory($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under WH documents'];
        break;
    }
  }


  public function lookupusers($config)
  {
    //default
    $plotting = array('userid' => 'userid', 'user' => 'name');
    $plottype = 'plotgrid';
    $title = 'List of Users';
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
    $cols = [
      ['name' => 'username', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select '0' as userid,'0' as accessid, '' as username,'' as name,'' as project
    union all
    select clientid as userid, 0 as accessid, email as username, clientname as name, '' as project
    from client where isemployee=1 and email <> ''";
    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } // end function


  public function lookuplogs($config)
  {
    $doc = 'ENTRYTASK';
    $lookupsetup = array(
      'type' => 'show',
      'title' => 'Task Master Logs',
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
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'
      union all
      select trno, doc, task, log.user, dateid, 
      if(pic='','blank_user.png',pic) as pic
      from  " . $this->tablelogs_del . " as log
      left join useraccess as u on u.username=log.user
      where log.doc = '" . $doc . "' and log.trno = '" . $trno . "'";

    $qry = $qry . " order by dateid desc";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols];
  }

  public function lookupcomplexity($config)
  {
    //default
    $plotting = array();
    $plottype = '';
    $title = 'Task Complexity';
    $plotting = array('complexity' => 'field1');
    $plottype = 'plotgrid';

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
    $cols = [
      ['name' => 'complexity', 'label' => 'Task complexity', 'align' => 'left', 'field' => 'field1', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = " 
        select 'Very low' as field1
        union all 
        select 'Low' as field1
        union all 
        select 'Medium' as field1
        union all 
        select 'High' as field1
        union all 
        select 'Critical' as field1";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function


  public function lookuptaskcategory($config)
  {
    //default
    $plotting = array();
    $plottype = '';
    $title = 'Task Category';
    $plotting = array('taskcatid' => 'line', 'taskcategory' => 'category');
    $plottype = 'plotgrid';

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
    $cols = [
      ['name' => 'category', 'label' => 'Task category', 'align' => 'left', 'field' => 'category', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select line,category from reqcategory where istaskcat=1";

    $data = $this->coreFunctions->opentable($qry);
    $index = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $index];
  } //end function




  public function tableentrystatus($config)
  {
    return $this->save($config, true);
  }

  // // -> Print Function
  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }


  public function createreportfilter($config)
  {
    $fields = ['prepared', 'approved', 'received', 'print'];
    $col1 = $this->fieldClass->create($fields);

    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    $user = $config['params']['user'];
    $username = $this->coreFunctions->datareader("select name as value from useraccess where username =?", [$config['params']['user']]);
    $paramstr = "select 
          'PDFM' as print,
          '' as prepared,
          '' as approved,
          '' as received";
    if ($config['params']['companyid'] == 8) { //maxipro
      $paramstr .= " , '$username' as prepared ";
    } else {
      $paramstr .= " ,'' as prepared ";
    }
    return $this->coreFunctions->opentable($paramstr);
  }

  private function report_default_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "select line, category, reqtype from reqcategory
        order by line";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn


  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $data = $this->report_default_query($config);
    if ($config['params']['dataparams']['print'] == "default") {
      $str = $this->rpt_loantype_masterfile_layout($data, $config);
    } else if ($config['params']['dataparams']['print'] == "PDFM") {
      $str = $this->rpt_loantype_PDF($data, $config);
    }

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  private function rpt_default_header($data, $filters)
  {

    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TASK TYPE MASTERFILE', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'R', 'Century Gothic', '10', '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CODE', '400', null, false, '1px solid ', 'B', 'L', 'Century Gothic', '12', 'B', '', '2px');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function rpt_loantype_masterfile_layout($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;

    $str .= $this->reporter->beginreport();
    $str .= $this->rpt_default_header($data, $filters);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data[$i]['category'], '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '11', '', '', '3px');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->rpt_default_header($data, $filters);
        $str .= $this->reporter->printline();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->col('Received By :', '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .=  '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, '1px solid ', '', 'L', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, '1px solid ', '', 'C', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, '1px solid ', '', 'R', 'Century Gothic', '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } //end fn

  private function rpt_class_PDF_header_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $font = "";
    $fontbold = "";
    $fontsize = 11;

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    PDF::SetTitle($this->modulename);
    PDF::SetAuthor('Solutionbase Corp.');
    PDF::SetCreator('Solutionbase Corp.');
    PDF::SetSubject($this->modulename . ' Module Report');
    PDF::setPageUnit('px');
    PDF::AddPage('p', [800, 1000]);
    PDF::SetMargins(20, 20);


    PDF::MultiCell(0, 0, $username . ' - ' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '  ' . strtoupper($headerdata[0]->name), '', 'L');
    PDF::MultiCell(0, 0, "\n");
    $this->reportheader->getheader($filters);
    PDF::MultiCell(0, 0, "\n");

    PDF::SetFont($fontbold, '', 14);
    PDF::MultiCell(800, 20, $this->modulename, '', 'L', false);

    PDF::SetFont($font, '', 9);
    PDF::MultiCell(800, 20, "Page " . PDF::PageNo() . "  ", '', 'L', false);

    PDF::SetFont($fontbold, '', 11);
    PDF::MultiCell(600, 20, "Code", '', 'L', false, 0);
    PDF::MultiCell(100, 20, "", '', 'L', false);

    PDF::SetFont($font, '', 5);
    PDF::MultiCell(600, 0, "", 'T', 'L', false, 0);
    PDF::MultiCell(100, 0, "", 'T', 'L', false);
  }

  private function rpt_loantype_PDF($data, $filters)
  {
    $companyid = $filters['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

    $center = $filters['params']['center'];
    $username = $filters['params']['user'];

    $count = 35;
    $page = 35;
    $font = "";
    $fontbold = "";
    $border = "1px solid ";
    $fontsize = "10";

    if (Storage::disk('sbcpath')->exists('/fonts/GOTHIC.TTF')) {
      $font = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHIC.TTF');
      $fontbold = TCPDF_FONTS::addTTFfont(database_path() . '/images/fonts/GOTHICB.TTF');
    }
    $this->rpt_class_PDF_header_PDF($data, $filters);

    for ($i = 0; $i < count($data); $i++) {
      PDF::SetFont($font, '', $fontsize);
      PDF::MultiCell(600, 10, $data[$i]['category'], '', 'L', 0, 0, '', '', true, 0, true, false);
      PDF::MultiCell(100, 10, "", '', 'L', 0, 1, '', '', true, 0, false, false);

      if (intVal($i) + 1 == $page) {
        $this->rpt_class_PDF_header_PDF($data, $filters);
        $page += $count;
      }
    }

    PDF::MultiCell(0, 0, "\n\n\n\n");

    PDF::MultiCell(266, 0, 'Prepared By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Approved By : ', '', 'L', false, 0);
    PDF::MultiCell(266, 0, 'Received By : ', '', 'L');

    PDF::MultiCell(0, 0, "\n");

    PDF::MultiCell(266, 0, $filters['params']['dataparams']['prepared'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['approved'], '', 'L', false, 0);
    PDF::MultiCell(266, 0, $filters['params']['dataparams']['received'], '', 'L');

    return PDF::Output($this->modulename . '.pdf', 'S');
  } //end fn

  public function isclose($config)
  {
    $tableid = $config['params']['tableid'];
    $status = $this->coreFunctions->datareader("select status as value from tmhead where trno= ?", [$tableid]);
    if ($status != 2) {
      return false;
    } else {
      return true;
    }
  }
} //end loantype
