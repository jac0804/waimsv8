<?php

namespace App\Http\Classes\modules\enrollment;

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
use App\Http\Classes\SBCPDF;

class et
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Assesment Setup';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'en_athead';
  public $hhead = 'en_glhead';
  public $stock = 'en_atfees';
  public $hstock = 'en_glfees';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  private $fields = ['trno', 'docno', 'dateid', 'periodid', 'syid'];
  private $except = ['trno', 'dateid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 962,
      'edit' => 961,
      'new' => 963,
      'save' => 964,
      'change' => 966,
      'delete' => 965,
      'print' => 969,
      'lock' => 969,
      'unlock' => 970,
      'post' => 967,
      'unpost' => 968,

      'additem' => 1318, //no attributes yet
      'edititem' => 1319,
      'deleteitem' => 1320
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listperiod', 'listsy'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $limit = "limit 150";

    $searchfield = [];
    $filtersearch = "";
    $search = $config['params']['search'];

    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'p.code', 's.sy'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $qry = "select head.trno, head.docno, left(head.dateid,10) as dateid, 'DRAFT' as status, p.code as period, s.sy
      from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
      left join en_period as p on p.line=head.periodid left join en_schoolyear as s on s.line=head.syid
      where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
      union all
      select head.trno, head.docno, left(head.dateid,10) as dateid, 'POSTED' as status, p.code as period, s.sy
      from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
      left join en_period as p on p.line=head.periodid left join en_schoolyear as s on s.line=head.syid
      where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
      order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'post',
      'unpost',
      'lock',
      'unlock',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton



  public function createTab($access, $config)
  {

    $tab = [$this->gridname => [
      'gridcolumns' => [
        'action', 'levels', 'deptcode', 'coursecode', 'year', 'term', 'section', 'subjectcode', 'sex', 'feescode', 'scheme',
        'rate', 'isnew', 'isforeign', 'istransferee', 'islateenrollee', 'iscrossenrollee', 'isadddrop'
      ],
    ]];
    $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][0]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][1]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][2]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][2]['lookupclass'] = "lookupdeptgrid"; //lookupcoursegrid
    $obj[0][$this->gridname]['columns'][2]['label'] = "Department";

    $obj[0][$this->gridname]['columns'][3]['lookupclass'] = "lookupcoursegrid";
    $obj[0][$this->gridname]['columns'][3]['label'] = "Course";
    $obj[0][$this->gridname]['columns'][3]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][4]['style'] = 'width:60px;whiteSpace: normal;min-width:80px;';
    $obj[0][$this->gridname]['columns'][4]['label'] = "Year/Grade";

    $obj[0][$this->gridname]['columns'][5]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][5]['action'] = "lookupsemester";
    $obj[0][$this->gridname]['columns'][5]['lookupclass'] = "lookupsemestergrid";
    $obj[0][$this->gridname]['columns'][5]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][5]['label'] = "Semester";

    $obj[0][$this->gridname]['columns'][6]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][7]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][7]['lookupclass'] = "lookupsubjectgrid";
    $obj[0][$this->gridname]['columns'][7]['label'] = "Subject";
    $obj[0][$this->gridname]['columns'][7]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][8]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][9]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][9]['action'] = "lookupfeesgrid";
    $obj[0][$this->gridname]['columns'][9]['lookupclass'] = "lookupfeesgrid";
    $obj[0][$this->gridname]['columns'][9]['label'] = "Fees";
    $obj[0][$this->gridname]['columns'][9]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][10]['type'] = "lookup";
    $obj[0][$this->gridname]['columns'][10]['action'] = "lookupschemegrid";
    $obj[0][$this->gridname]['columns'][10]['lookupclass'] = "lookupschemegrid";
    $obj[0][$this->gridname]['columns'][10]['style'] = 'width:40px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][11]['label'] = "Rate";
    $obj[0][$this->gridname]['columns'][11]['style'] = 'width:60px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['addrow', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[1]['label'] = "SAVE ALL";
    $obj[2]['label'] = "DELETE ALL";
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'period'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'period.label', 'Period (SY & Grade/Year) Ex.19-1');

    $fields = ['dateid', 'sy'];
    $col2 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $schoolyear  = $this->coreFunctions->getfieldvalue('en_period', 'sy', 'isactive=1');
    $data[0]['syid'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'line', 'sy=?', [$schoolyear]);
    $data[0]['sy'] = $this->coreFunctions->getfieldvalue('en_schoolyear', 'sy', 'sy=?', [$schoolyear]);
    $data[0]['periodid'] = $this->coreFunctions->getfieldvalue('en_period', 'line', 'isactive=1');
    $data[0]['period'] = $this->coreFunctions->getfieldvalue('en_period', 'code', 'isactive=1');
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select head.trno, head.docno, head.dateid as dateid, head.periodid, p.code as period, head.syid, s.sy ";

    $qry = $qryselect . " from " . $table . " as head
        left join " . $tablenum . " as num on num.trno = head.trno left join en_period as p on p.line=head.periodid
        left join en_schoolyear as s on s.line=head.syid
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from " . $htable . " as head
        left join " . $tablenum . " as num on num.trno = head.trno left join en_period as p on p.line=head.periodid
        left join en_schoolyear as s on s.line=head.syid
        where head.trno = ? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);

    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }


  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields['docno']);
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }

    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['period'] . ' - ' . $head['sy']);
    }
  } // end function



  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

    $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate)
      SELECT trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate FROM " . $this->head . "
      where trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      $qry = "insert into " . $this->hstock . "(trno, line, subjectid, feesid, schemeid, rate, isnew, isold, isforeign, isadddrop, iscrossenrollee, islateenrollee, istransferee, periodid, levelid, departid, courseid, sex, rooms, yr, semid, section)
        SELECT trno, line, subjectid, feesid, schemeid, rate, isnew, isold, isforeign, isadddrop, iscrossenrollee, islateenrollee, istransferee, periodid, levelid, departid, courseid, sex, rooms, yr, semid, section from " . $this->stock . " where trno =? ";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
      //if($posthead){      
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate)
    SELECT trno, doc, docno, dateid, periodid, syid, createby, createdate, editby, editdate, viewby, viewdate, lockuser, lockdate FROM " . $this->hhead . " where trno=?  limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(trno, line, subjectid, feesid, schemeid, rate, isnew, isold, isforeign, isadddrop, iscrossenrollee, islateenrollee, istransferee, periodid, levelid, departid, courseid,  sex, rooms, yr, semid, section)
        SELECT trno, line, subjectid, feesid, schemeid, rate, isnew, isold, isforeign, isadddrop, iscrossenrollee, islateenrollee, istransferee, periodid, levelid, departid, courseid, sex, rooms, yr, semid, section FROM " . $this->hstock . " where trno =?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select s.trno, s.line, s.rate, 
    (case when s.isnew=0 then 'false' else 'true' end) as isnew, 
    (case when s.isold=0 then 'false' else 'true' end) as isold, 
    (case when s.isforeign=0 then 'false' else 'true' end) as isforeign, 
    (case when s.isadddrop=0 then 'false' else 'true' end) as isadddrop, 
    (case when s.iscrossenrollee=0 then 'false' else 'true' end) as iscrossenrollee, 
    (case when s.islateenrollee=0 then 'false' else 'true' end) as islateenrollee,
    (case when s.istransferee=0 then 'false' else 'true' end) as istransferee,
    l.levels, s.levelid, 
    s.departid, d.client as deptcode, 
    s.courseid, c.coursecode, s.yr as year,
    s.semid, t.term, s.section,
    s.subjectid, sub.subjectcode, s.sex,
    s.feesid, f.feescode,
    s.schemeid, sc.scheme,
    '' as bgcolor,'' as errcolor ";
    return $sqlselect;
  }

  private function getstocktablesleftjoin()
  {
    return "
    left join en_levels as l on l.line=s.levelid
    left join client as d on d.clientid = s.departid
    left join en_course as c on c.line = s.courseid
    left join en_term as t on t.line=s.semid
    left join en_subject as sub on sub.trno = s.subjectid
    left join en_fees as f on f.line = s.feesid
    left join en_scheme as sc on sc.line = s.schemeid";
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM " . $this->stock . " as s left join " . $this->tablenum . " as num on num.trno=s.trno 
    " . $this->getstocktablesleftjoin() . "
    where s.trno = ? and num.postdate is null
    UNION ALL
    " . $sqlselect . "  
    FROM " . $this->hstock . " as s left join " . $this->tablenum . " as num on num.trno=s.trno
    " . $this->getstocktablesleftjoin() . "
    where s.trno = ?  and num.postdate is not null";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . " from " . $this->stock . " as s 
    " . $this->getstocktablesleftjoin() . "
    where s.trno = ? and s.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function



  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];
    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['levels'] = '';
    $data['levelid']  = 0;
    $data['deptcode'] = '';
    $data['departid'] = 0;
    $data['coursecode'] = '';
    $data['courseid'] = 0;
    $data['year'] = 0;
    $data['term'] = '';
    $data['semid'] = 0;
    $data['section'] = '';
    $data['subjectcode'] = '';
    $data['subjectid'] = 0;
    $data['sex'] = '';
    $data['feescode'] = '';
    $data['feesid'] = 0;
    $data['rate'] = 0;
    $data['scheme'] = '';
    $data['schemeid'] = 0;
    $data['isnew'] = 'false';
    $data['isforeign'] = 'false';
    $data['isadddrop'] = 'false';
    $data['iscrossenrollee'] = 'false';
    $data['islateenrollee'] = 'false';
    $data['istransferee'] = 'false';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function  


  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }

  public function additem($action, $config)
  {
    $trno = $config['params']['trno'];
    $line = $config['params']['data']['line'];
    if ($line == 0) {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $action = 'insert';
    }
    $config['params']['trno'] = $trno;
    $config['params']['line'] = $line;

    $rate = $this->othersClass->sanitizekeyfield('rate', $config['params']['data']['rate']);
    if ($config['params']['data']['isnew'] == 'true') {
      $config['params']['data']['isold'] = 'false';
    } else {
      $config['params']['data']['isold'] = 'true';
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'levelid' => $config['params']['data']['levelid'],
      'departid' => $config['params']['data']['departid'],
      'courseid' => $config['params']['data']['courseid'],
      'yr' => $config['params']['data']['year'],
      'semid' => $config['params']['data']['semid'],
      'section' => $config['params']['data']['section'],
      'subjectid' => $config['params']['data']['subjectid'],
      'sex' => $config['params']['data']['sex'],
      'feesid' => $config['params']['data']['feesid'],
      'schemeid' => $config['params']['data']['schemeid'],
      'rate' => $rate,
      'isnew' => $config['params']['data']['isnew'],
      'isold' => $config['params']['data']['isold'],
      'isforeign' => $config['params']['data']['isforeign'],
      'isadddrop' => $config['params']['data']['isadddrop'],
      'iscrossenrollee' => $config['params']['data']['iscrossenrollee'],
      'istransferee' => $config['params']['data']['istransferee'],
      'islateenrollee' => $config['params']['data']['islateenrollee'],
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    if ($action == 'insert') {
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'ASSESSMENT', 'ADD - Line:' . $line . ' Department:' . $data['departid'] . ' Course:' . $data['courseid']);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item failed'];
      }
    } else {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
    }

    return true;
  }

  public function deleteitem($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->logger->sbcwritelog($trno, $config, 'ASSESSMENT', 'REMOVED - Line: ' . $line);
    return ['status' => true, 'msg' => 'Delete subject successfully.'];
  }

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ASSESSMENT');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  // start
  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter($config);
    // $txtdata = $this->reportparamsdata($config);
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
