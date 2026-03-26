<?php

namespace App\Http\Classes\modules\construction;

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
use App\Http\Classes\builder\helpClass;

class pm
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PROJECT MANAGEMENT';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pmhead';
  public $hhead = 'hpmhead';
  public $stock = 'subproject';
  public $hstock = 'hsubproject';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  private $fields = ['trno', 'docno', 'dateid', 'due', 'client', 'clientname', 'rem', 'address', 'tcp', 'projectid', 'dp', 'retention', 'cost', 'completed', 'closedate', 'wh', 'ocp', 'conduration', 'dollarprice'];
  private $except = ['trno', 'dateid', 'due', 'closedate'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1777,
      'edit' => 1778,
      'new' => 1779,
      'save' => 1780,
      // 'change'=>1781, remove change doc
      'delete' => 1782,
      'print' => 1783,
      'lock' => 1784,
      'unlock' => 1785,
      'post' => 1786,
      'unpost' => 1787,
      'additem' => 1788,
      'edititem' => 1789,
      'deleteitem' => 1790,
      'refdoc' => 1791,
      'attachment' => 1792,
      'summary' => 1793
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'listprojectname', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[4]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[5]['style'] = 'width:500px;whiteSpace: normal;min-width:500px;';
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
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'p.name', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
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
    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 'DRAFT' as status,head.createby,head.editby,head.viewby,num.postedby,p.name as projectname
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join projectmasterfile as p on p.line = head.projectid where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,head.createby,head.editby,head.viewby, num.postedby,p.name as projectname  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno left join projectmasterfile as p on p.line = head.projectid where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
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
      'toggledown',
      'help',
      'others'
    );
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['isqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add item/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'pm', 'title' => 'Project Management Manual', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $action = 0;
    $subproject = 1;
    $projpercent = 2;
    $completed = 3;
    $gridcolumn = ['action', 'subproject', 'projpercent', 'completed'];

    $tab = [
      $this->gridname => ['gridcolumns' => $gridcolumn],
    ];


    $stockbuttons = ['addstages', 'save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$subproject]['style'] = "width:650px;whiteSpace: normal;min-width:650px;";
    $obj[0][$this->gridname]['columns'][$projpercent]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$completed]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";


    $obj[0]['inventory']['label'] = 'Subproject';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'viewprojref', 'label' => 'Reference']];
    $ref = $this->tabClass->createtab($tab, []);

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entryvariation', 'label' => 'Variations']];
    $var = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    $return['Reference'] = ['icon' => 'fa fa-envelope', 'tab' => $ref];
    $return['Variations'] = ['icon' => 'fa fa-envelope', 'tab' => $var];
    return $return;
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
    $fields = ['docno', 'client', 'clientname', 'address', 'dwhname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'dwhname.required', true);

    $fields = [['dateid', 'due'], 'closedate', 'completed', ['retention', 'dp']];
    $col2 = $this->fieldClass->create($fields);

    $fields = [['tcp', 'cost'], 'dprojectname', 'rem'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dprojectname.condition', ['checkstock']);
    data_set($col3, 'dprojectname.required', true);
    data_set($col3, 'dprojectname.lookupclass', 'pmproject');
    data_set($col3, 'rem.required', false);
    data_set($col3, 'cost.label', 'Estimated Cost');
    data_set($col3, 'cost.required', true);
    data_set($col3, 'tcp.label', 'Latest Contract Price');

    $fields = ['ocp', 'conduration', 'dollarprice'];
    $col4 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }



  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['due'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['rem'] = '';
    $data[0]['tcp'] = 0;
    $data[0]['cost'] = 0;
    $data[0]['projectid'] = 0;
    $data[0]['dp'] = '';
    $data[0]['retention'] = '';
    $data[0]['completed'] = '';
    $data[0]['closedate'] = null;
    $data[0]['address'] = '';
    $data[0]['project'] = '';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['dprojectname'] = '';
    $data[0]['wh'] = '';
    $data[0]['whname'] = '';
    $data[0]['dwhname'] = '';
    $data[0]['ocp'] = 0;
    $data[0]['conduration'] = '';
    $data[0]['dollarprice'] = '';
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
    $qryselect = "select 
         num.center,
         head.trno, 
         head.docno,
         client.client,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,'' as dprojectname,project.code as projectcode,project.name as projectname,
         left(head.due,10) as due, head.closedate,head.completed,
         FORMAT(head.tcp," . $this->companysetup->getdecimal('price', $config['params']) . ") as tcp,
         FORMAT(head.cost," . $this->companysetup->getdecimal('price', $config['params']) . ") as cost,
         FORMAT(head.ocp," . $this->companysetup->getdecimal('price', $config['params']) . ") as ocp,
         FORMAT(head.dollarprice," . $this->companysetup->getdecimal('price', $config['params']) . ") as dollarprice,
         head.dp,head.retention,head.projectid ,warehouse.client as wh,
         warehouse.clientname as whname, 
         '' as dwhname, head.conduration";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join projectmasterfile as project on project.line = head.projectid
        where head.trno = ? and num.center = ? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $ref = ''; //for refences docs data
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock, 'reference' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => [], 'reference' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }




  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if    
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->coreFunctions->execqry("update projectmasterfile set pmtrno = " . $head['trno'] . " where line = ?", "update", [$head['projectid']]);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
    }
  } // end function



  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;

    $stat = $this->coreFunctions->getfieldvalue("pmhead", "completed", "trno=?", [$trno]);
    $projectid = $this->coreFunctions->getfieldvalue("pmhead", "projectid", "trno=?", [$trno]);
    $boqqry = "select trno from sohead where projectid=?
            union all select trno from hsohead where projectid=?";
    $boq = $this->coreFunctions->opentable($boqqry, [$projectid, $projectid]);
    if (!empty($boq)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Delete failed, already processed.'];
    }
    if (strlen($stat) > 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Delete failed, already processed.'];
    } else {
      $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
      $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
      $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);

      $this->coreFunctions->execqry('delete from ' . $this->stock . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
      $this->coreFunctions->execqry('delete from stages where trno=?', 'delete', [$trno]);
      $this->coreFunctions->execqry('update projectmasterfile set pmtrno =0 where pmtrno=?', 'update', [$trno]);
      $this->othersClass->deleteattachments($config);
      $this->logger->sbcdel_log($trno, $config, $docno);
      return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
    }
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];

    $completion = $this->coreFunctions->getfieldvalue($this->head, "completed", "trno=?", [$trno]);


    if ($completion <> '100%') {
      return ['status' => false, 'msg' => 'Posting failed. Please check, the project has not yet been completed.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for hpmhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,dateid,wh,rem,due,closedate,completed,tcp,cost,ocp,dp,retention,projectid,conduration,dollarprice,createdate,createby,editby,editdate,lockdate,lockuser)
      SELECT trno,doc,docno,client,clientname,address,dateid,rem,due,closedate,completed,tcp,cost,ocp,dp,retention,projectid,conduration,dollarprice,createdate,createby,editby,editdate,lockdate,lockuser FROM " . $this->head . " as head 
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for subproject
      $qry = "insert into " . $this->hstock . "(trno,line,subproject,projpercent,completed,projectid,encodeddate, encodedby,editdate,editby)
        SELECT trno,line,subproject,projpercent,completed,projectid,
        encodeddate, encodedby,editdate,editby FROM " . $this->stock . " where trno =?";
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
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,dateid,wh,rem,due,
          closedate,completed,tcp,cost,ocp,dp,retention,projectid,conduration,dollarprice,createdate,
          createby,editby,editdate,lockdate,lockuser)
        select head.trno, head.doc, head.docno, client.client, head.clientname, head.address,head.dateid,head.wh, head.rem, head.due,
          head.closedate, head.completed, head.tcp,head.cost,head.ocp,head.dp,head.retention,head.projectid,head.conduration,head.dollarprice,head.createdate,
          head.createby, head.editby, head.editdate, head.lockdate, head.lockuser
        from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)
        left join client on client.client=head.client
        where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,expiry,disc,
      amt,iss,void,isamt,isqty,ext,rem,encodeddate,encodedby,editdate,editby)
      select trno, line, itemid, uom,whid,loc,expiry,disc,amt, iss,void, isamt, isqty,
      ext,rem, encodeddate,encodedby, editdate, editby
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
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
    $sqlselect = "select stock.trno,stock.line,stock.subproject,stock.projpercent,stock.completed,stock.projectid,
    '' as bgcolor,
    '' as errcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    where stock.trno =? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
   FROM $this->stock as stock where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        $return =  $this->additem('insert', $config);

        return $return;
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all item edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'addrow':
        return $this->addrow($config);
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
    $data['projectid'] = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=" . $trno);
    $data['projpercent']  = '';
    $data['completed'] = '';
    $data['subproject'] = '';
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

    if ($config['params']['line'] != 0) {
      $this->additem('update', $config);
      $data = $this->openstockline($config);
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      $data = $this->additem('update', $config);
      if ($data['status'] == true) {
        return ['row' => $data['data'], 'status' => true, 'msg' => 'Successfully saved.'];
      } else {
        return ['row' => $data['data'], 'status' => false, 'msg' => $data['msg']];
      }
    }
  }


  // insert and update item
  public function additem($action, $config)
  {
    $trno = $config['params']['trno'];

    $data = [
      'trno'            => $config['params']['trno'],
      'line'            => $config['params']['data']['line'],
      'projectid'       => $config['params']['data']['projectid'],
      'projpercent'     => $config['params']['data']['projpercent'],
      'subproject'      => $config['params']['data']['subproject'],
    ];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $subproject = $config['params']['data']['subproject'];
    if ($data['line'] == 0) {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $line = $this->coreFunctions->insertGetId($this->stock, $data);
      if ($line != 0) {
        $config['params']['line'] = $line;
        $data =  $this->openstockline($config);
        $this->logger->sbcwritelog($trno, $config, 'SUBPROJECT', 'ADD - Line: ' . $line . ' Subproject: ' . $subproject);
        return ['status' => true, 'msg' => 'Successfully saved.', 'data' => $data];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.', 'data' => []];
      }
    } else if ($action == 'update') {
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $data['line']]);
    }
    return true;
  }

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];

    $stat = $this->coreFunctions->getfieldvalue("subproject", "completed", "completed <>'' and trno=?", [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=? and completed=""', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stages where trno=? and subproject not in (select line from subproject)', 'delete', [$trno]);
    if (strlen($stat) > 0) {
      $data = $this->openstock($trno, $config);
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED SUBPROJECT');
      return ['status' => true, 'msg' => 'Successfully deleted.. Some subproject already procesed.', 'inventory' => $data];
    } else {
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL SUBPROJECT');
      return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
    }
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];

    if ($config['params']['row']['completed'] != '') {
      $data = $this->openstock($config['params']['trno'], $config);
      return ['status' => false, 'msg' => 'Cannot delete, already procesed.'];
    } else {
      $trno = $config['params']['trno'];
      $line = $config['params']['line'];

      $qry = "delete from " . $this->stock . " where trno=? and line=?";
      $qry2 = "delete from stages where trno=? and subproject=?";
      $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
      $this->coreFunctions->execqry($qry2, 'delete', [$trno, $line]);
      $data = $this->openstock($config['params']['trno'], $config);
      $this->logger->sbcwritelog($trno, $config, 'SUBPROJECT', 'REMOVED - Line:' . $line);
      return ['status' => true, 'msg' => 'Item was successfully deleted.'];
    }
  } // end function


  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
