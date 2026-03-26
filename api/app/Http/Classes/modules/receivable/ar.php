<?php

namespace App\Http\Classes\modules\receivable;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

use Exception;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class ar
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AR SETUP';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $defaultContra = 'AR1';

  private $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'forex', 'cur', 'address', 'projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'];
  private $except = ['trno', 'dateid'];
  private $acctg = [];
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
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 240,
      'edit' => 241,
      'new' => 242,
      'save' => 243,
      // 'change' => 244, remove change doc
      'delete' => 245,
      'print' => 246,
      'lock' => 247,
      'unlock' => 248,
      'post' => 249,
      'unpost' => 250,
      'additem' => 251,
      'edititem' => 252,
      'deleteitem' => 253
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $listclientname = 4;
    $yourref = 5;
    $ourref = 6;
    $postdate = 7;

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:350px;whiteSpace: normal;min-width:350px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
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
    $limit = '';


    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $dateid = "date_format(head.dateid,'%m-%d-%Y') as dateid";
        if ($searchfilter == "") $limit = 'limit 25';
        $orderby =  "order by  dateid desc, docno desc";
        break;
      case 19: //housegem
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by docno desc, dateid desc";
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby =  "order by  dateid desc, docno desc";
        break;
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref        
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
       head.yourref, head.ourref        
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . " " . $filtersearch . "
    $orderby $limit";

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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnaddaccount', 'db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step4 = $this->helpClass->getFields(['db', 'cr', 'rem', 'btnstocksaveaccount', 'btnsaveaccount']);
    $step5 = $this->helpClass->getFields(['btnstockdeleteaccount', 'btndeleteallaccount']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add account/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit account details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete account/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    // switch ($config['params']['companyid']) {
    //   case 40: //cdo
    $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Accounts', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'save']];
    //     break;
    // }

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf', 'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $financing_access = $this->othersClass->checkAccess($config['params']['user'], 4633);

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);
    $recons = ['customform' => ['action' => 'customform', 'lookupclass' => 'reconstruct']];

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($financing_access != 0) {
      $return['Reconstruct'] = ['icon' => 'fa fa-coins', 'customform' => $recons];
    }

    $return['Reconstruct'] = ['icon' => 'fa fa-coins', 'customform' => $recons];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    if ($config['params']['companyid'] == 60) { //transpower      
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5501);
      if ($changecode) {
        $changecode = ['customform' => ['action' => 'customform', 'lookupclass' => 'changebarcode']];
        $return['CHANGE CODE'] = ['icon' => 'fa fa-qrcode', 'customform' => $changecode];
      }
    }



    return $return;
  }


  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $systype = $this->companysetup->getsystemtype($config['params']);

    $action = 0;
    $db = 1;
    $cr = 2;
    $postdate = 3;
    $client = 4;
    $acnoname = 5;
    $ref = 6;
    $rem = 7;
    $agentname = 8;
    $poref = 9;
    $podate = 10;
    $stock_projectname = 11;

    $columns = ['action', 'db', 'cr', 'postdate', 'client', 'acnoname', 'ref', 'rem',  'agentname', 'poref', 'podate', 'stock_projectname'];

    switch ($systype) {
      case 'REALESTATE':
        $project = 12;
        $phasename = 13;
        $housemodel = 14;
        $blk = 15;
        $lot = 16;
        $amenityname = 17;
        $subamenityname = 18;
        array_push($columns, 'project', 'phasename', 'housemodel', 'blk', 'lot', 'amenityname', 'subamenityname');

        break;
    }

    switch ($companyid) {
      case 3: // conti
        $tab = [
          $this->gridname => [
            'gridcolumns' => $columns,
            'headgridbtns' => ['viewref', 'viewacctginfo', 'viewdiagram']
          ],
          'skilldesctab' => ['action' => 'tableentry', 'lookupclass' => 'entryservices', 'label' => 'SERVICES']
        ];
        break;

      case 39: //cbbsi
        $tab = [
          $this->gridname => [
            'gridcolumns' => $columns,
            'headgridbtns' => ['viewref', 'viewacctginfo', 'viewdiagram']
          ],
        ];
        $tab['multigrid'] = ['action' => 'tableentry', 'lookupclass' => 'entryparticulars', 'label' => 'Particulars'];
        break;
      case 43: // mighty
        $tab = [
          $this->gridname => [
            'gridcolumns' => $columns,
            'headgridbtns' => ['viewref', 'viewacctginfo', 'viewdiagram']
          ],

          'adddocument' => ['event' => ['lookupclass' => 'entrycntnumpicture', 'action' => 'documententry', 'access' => 'view']],
          'skilldesctab' => ['action' => 'tableentry', 'lookupclass' => 'entryservices', 'label' => 'SERVICES']
        ];
        break;
      default:
        $tab = [
          $this->gridname => [
            'gridcolumns' => $columns,
            'headgridbtns' => ['viewref', 'viewdiagram']
          ],
          'adddocument' => ['event' => ['lookupclass' => 'entrycntnumpicture', 'action' => 'documententry', 'access' => 'view']]
        ];
        break;
    }

    $stockbuttons = ['save', 'delete'];
    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }
    array_push($stockbuttons, 'detailinfo');

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['accounting']['columns'][$client]['label'] = 'Customer';
    $obj[0]['accounting']['columns'][$client]['lookupclass'] = 'customerdetail';

    $obj[0]['accounting']['columns'][$ref]['type'] = 'input';
    $obj[0]['accounting']['columns'][$ref]['readonly'] = false;


    switch ($companyid) {
      case 10: //afti
        $obj[0]['accounting']['columns'][$postdate]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0]['accounting']['columns'][$poref]['type'] = 'input';
        $obj[0]['accounting']['columns'][$poref]['readonly'] = false;
        $obj[0]['accounting']['columns'][$poref]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0]['accounting']['columns'][$podate]['style'] = 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;';
        $obj[0]['accounting']['columns'][$agentname]['action'] = 'lookupclient';
        $obj[0]['accounting']['columns'][$agentname]['type'] = 'lookup';
        $obj[0]['accounting']['columns'][$agentname]['lookupclass'] = 'lookupagent_detail';
        $obj[0]['accounting']['columns'][$agentname]['style'] = 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;';
        $obj[0]['accounting']['columns'][$rem]['label'] = 'Notes/SI Ref.';
        break;
      case '19':
        $obj[0]['accounting']['columns'][$rem]['style'] = 'text-align: left; width: 300px;whiteSpace: normal;min-width:300px;max-width:450px;';
        $obj[0]['accounting']['columns'][$rem]['type'] = 'textarea';
    }

    $obj[0]['accounting']['columns'][$stock_projectname]['label'] = 'Item Group';
    if ($companyid != 10) { //not afti
      $obj[0]['accounting']['columns'][$stock_projectname]['type'] = 'coldel';
      $obj[0]['accounting']['columns'][$poref]['type'] = 'coldel';
      $obj[0]['accounting']['columns'][$podate]['type'] = 'coldel';
      $obj[0]['accounting']['columns'][$agentname]['type'] = 'coldel';
    }

    switch ($systype) {
      case 'REALESTATE':
        $obj[0]['accounting']['columns'][$blk]['readonly'] = true;
        $obj[0]['accounting']['columns'][$lot]['readonly'] = true;
        break;
    }

    $obj[0]['accounting']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "ADD ACCOUNT";
    $obj[0]['action'] = "adddetail";
    $obj[1]['label'] = "SAVE ACCOUNT";
    $obj[2]['label'] = "DELETE ACCOUNT";
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype  = $this->companysetup->getsystemtype($config['params']);

    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);

    if ($systemtype == 'MMS') {
      data_set($col1, 'client.lookupclass', 'allclienthead');
    } else {
      data_set($col1, 'client.lookupclass', 'customer');
    }

    data_set($col1, 'docno.label', 'Transaction#');

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col1, 'clientname.type', 'textarea');
      data_set($col1, 'businesstype.type', 'textarea');
    }

    $fields = ['dateid', 'dprojectname', ['yourref', 'ourref'], ['cur', 'forex']];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['rem'];
    if ($this->companysetup->getistodo($config['params'])) {
      array_push($fields, 'donetodo');
    }

    $col3 = $this->fieldClass->create($fields);

    if ($systemtype == 'REALESTATE') {
      $fields = ['dprojectname', 'phase', 'housemodel', ['blklot', 'lot'], 'amenityname', 'subamenityname'];
      $col4 = $this->fieldClass->create($fields);
      data_set($col4, 'dprojectname.lookupclass', 'project');
      data_set($col4, 'phase.addedparams', ['projectid']);
      data_set($col4, 'housemodel.addedparams', ['projectid']);
      data_set($col4, 'blklot.addedparams', ['projectid', 'phaseid', 'modelid', 'fpricesqm']);
      data_set($col4, 'subamenityname.addedparams', ['amenityid']);

      return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
    } else {
      return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
    }
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['client'] = '';
    $data[0]['clientname'] = '';
    $data[0]['yourref'] = '';
    $data[0]['address'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['projectid'] = '0';
    $data[0]['projectcode'] = '';
    $data[0]['projectname'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);

    $data[0]['phaseid'] = 0;
    $data[0]['phase'] = '';
    $data[0]['modelid'] = 0;
    $data[0]['housemodel'] = '';
    $data[0]['blklotid'] = 0;
    $data[0]['blklot'] = '';
    $data[0]['lot'] = '';
    $data[0]['amenityid'] = 0;
    $data[0]['amenityname'] = '';
    $data[0]['subamenityid'] = 0;
    $data[0]['subamenityname'] = '';

    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      } else {
        $t = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where trno = ? and center=? order by trno desc limit 1", [$trno, $center]);
        if ($t == '') {
          $trno = 0;
        }
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $center = $config['params']['center'];

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $qryselect = "select 
        num.center,
        head.trno, 
        head.docno,
        client.client,
        head.terms,
        head.cur,
        head.forex,
        head.yourref,
        head.ourref,
        head.contra,
        coa.acnoname,
        '' as dacnoname,
        left(head.dateid,10) as dateid, 
        head.clientname,
        head.address,
        date_format(head.createdate,'%Y-%m-%d') as createdate,
        head.rem,
        head.tax,
        head.vattype,
        '' as dvattype,
        left(head.due,10) as due, 
        head.projectid,
        ifnull(project.name,'') as projectname,
        '' as dprojectname,
        client.groupid,ifnull(project.code,'') as projectcode,
        
        head.phaseid, 
        ph.code as phase,

        head.modelid, 
        hm.model as housemodel, 
        
        head.blklotid, 
        bl.blk as blklot, 
        bl.lot,
        
        amh.line as amenityid,
        amh.description as amenityname,
        subamh.line as subamenityid,
        subamh.description as subamenityname  ";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 
            
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid

        where head.trno = ? and num.doc=? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join coa on coa.acno=head.contra 
        left join projectmasterfile as project on project.line=head.projectid         
        
        left join phase as ph on ph.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid

        left join amenities as amh on amh.line= head.amenityid
        left join subamenities as subamh on subamh.line=head.subamenityid and subamh.amenityid=head.amenityid
        
        where head.trno = ? and num.doc=? and num.center=? ";
    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $detail = $this->opendetail($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }
      return  ['head' => $head, 'griddata' => ['accounting' => $detail], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['accounting' => []], 'msg' => 'Data Head Fetched Failed, either somebody already deleted the transaction or modified...'];
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
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['client'] . ' - ' . $head['clientname']);
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
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function




  public function posttrans($config)
  {
    return $this->othersClass->posttransacctg($config);
  } //end function

  public function unposttrans($config)
  {
    return $this->othersClass->unposttransacctg($config);
  } //end function


  private function getdetailselect($config)
  {
    $qry = " head.trno,left(head.dateid,10) as dateid,d.ref,d.line,d.sortline,coa.acnoid,coa.acno,coa.acnoname,
    client.client,client.clientname,d.rem,
    FORMAT(d.db,2) as db,FORMAT(d.cr,2) as cr,d.fdb,d.fcr,d.refx,d.linex,
    left(d.postdate,10) as postdate,d.checkno,coa.alias,d.pdcline,
    d.project,ifnull(proj.name,'') as projectname, ifnull(proj.name,'') as stock_projectname, d.projectid,
    d.cur,d.forex,
    ag.clientname as agentname, d.agentid as agentid,
    d.poref, left(d.podate, 10) as podate,
    case d.isewt when 0 then 'false' else 'true' end as isewt,case d.isvat when 0 then 'false' else 'true' end as isvat,case d.isvewt when 0 then 'false' else 'true' end as isvewt,d.ewtcode,d.ewtrate,d.damt,'' as bgcolor,'' as 
    errcolor,
    
    d.phaseid, ph.code as phasename,

    d.modelid, hm.model as housemodel, 

    d.blklotid, bl.blk, bl.lot,
    
    am.line as amenity,
    am.description as amenityname,
    subam.line as subamenity,
    subam.description as subamenityname  ";
    return $qry;
  }


  public function opendetail($trno, $config)
  {
    $sqlselect = $this->getdetailselect($config);

    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    
    left join phase as ph on ph.line = d.phaseid
    left join housemodel as hm on hm.line = d.modelid
    left join blklot as bl on bl.line = d.blklotid
    left join amenities as am on am.line= d.amenityid
    left join subamenities as subam on subam.line=d.subamenityid and subam.amenityid=d.amenityid

    left join coa on coa.acnoid=d.acnoid
    left join client as ag on ag.clientid = d.agentid
    where d.trno=?
    union all
    select " . $sqlselect . "  
    from " . $this->hdetail . " as d
    left join " . $this->hhead . " as head on head.trno=d.trno
    left join client on client.clientid=d.clientid
    left join projectmasterfile as proj on proj.line = d.projectid
    
    left join phase as ph on ph.line = d.phaseid
    left join housemodel as hm on hm.line = d.modelid
    left join blklot as bl on bl.line = d.blklotid
    left join amenities as am on am.line= d.amenityid
    left join subamenities as subam on subam.line=d.subamenityid and subam.amenityid=d.amenityid

    left join coa on coa.acnoid=d.acnoid
    left join client as ag on ag.clientid = d.agentid
    where d.trno=? order by sortline,line
  ";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $detail;
  }


  public function opendetailline($config)
  {
    $sqlselect = $this->getdetailselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "select " . $sqlselect . " 
    from " . $this->detail . " as d
    left join " . $this->head . " as head on head.trno=d.trno
    left join client on client.client=d.client
    left join projectmasterfile as proj on proj.line = d.projectid
    
    left join phase as ph on ph.line = d.phaseid
    left join housemodel as hm on hm.line = d.modelid
    left join blklot as bl on bl.line = d.blklotid
    left join amenities as am on am.line= d.amenityid
    left join subamenities as subam on subam.line=d.subamenityid and subam.amenityid=d.amenityid

    left join coa on d.acnoid=coa.acnoid
    left join client as ag on ag.clientid = d.agentid
    where d.trno=? and d.line=?";
    $detail = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $detail;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'adddetail':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
        break;
      case 'deleteitem':
        return $this->deleteitem($config);
        break;
      case 'saveitem': //save all detail edited
        return $this->updateitem($config);
        break;
      case 'saveperitem':
        return $this->updateperitem($config);
        break;
      case 'getunpaidselected':
        return $this->getunpaidselected($config);
        break;
      case 'generateewt':
        return $this->generateewt($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->opendetailline($config);
    if (!$isupdate) {
      $data[0]->errcolor = 'bg-red-2';
      return ['row' => $data, 'status' => true, 'msg' => 'Payment amount is greater than setup amount.'];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $isupdate = $this->additem('update', $config);
      if ($isupdate['status'] == false) {
        break;
      }
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key]['db'] == 0 && $data2[$key]['cr'] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Some entries have zero value both debit and credit ';
        } else {
          $msg2 = ' Reference Amount is lower than encoded amount ';
        }
      }
    }
    if ($isupdate['status']) {
      return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      if ($isupdate['msg'] == '') {
        return ['accounting' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
      } else {
        return ['accounting' => $data, 'status' => $isupdate['status'], 'msg' => $isupdate['msg']];
      }
    }
  } //end function

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }
    $data = $this->opendetail($config['params']['trno'], $config);
    return ['accounting' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function


  // insert and update detail
  public function additem($action, $config)
  {
    $acno = $config['params']['data']['acno'];
    $acnoname = $config['params']['data']['acnoname'];
    $trno = $config['params']['trno'];
    $db = $config['params']['data']['db'];
    $cr = $config['params']['data']['cr'];
    $fdb = $config['params']['data']['fdb'];
    $fcr = $config['params']['data']['fcr'];
    $postdate = $config['params']['data']['postdate'];
    $rem = $config['params']['data']['rem'];
    $project = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]); // $config['params']['data']['projectid'];
    $client = $config['params']['data']['client'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $refx = 0;
    $linex = 0;
    $ref = '';
    $checkno = '';
    $isewt = false;
    $isvat = false;
    $isvewt = false;
    $ewtcode = '';
    $ewtrate = '';
    $damt = 0;
    $agentid = 0;
    $poref = '';
    $podate = '';

    $projectid = 0;
    $phaseid = 0;
    $modelid = 0;
    $blklotid = 0;
    $amenityid = 0;
    $subamenityid = 0;

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    if (isset($config['params']['data']['checkno'])) {
      $checkno = $config['params']['data']['checkno'];
    }
    if (isset($config['params']['data']['isvat'])) {
      $isvat = $config['params']['data']['isvat'];
    }
    if (isset($config['params']['data']['isewt'])) {
      $isewt = $config['params']['data']['isewt'];
    }
    if (isset($config['params']['data']['ewtcode'])) {
      $ewtcode = $config['params']['data']['ewtcode'];
    }
    if (isset($config['params']['data']['ewtrate'])) {
      $ewtrate = $config['params']['data']['ewtrate'];
    }

    if (isset($config['params']['data']['isvewt'])) {
      $isvewt = $config['params']['data']['isvewt'];
    }

    if (isset($config['params']['data']['agentid'])) {
      $agentid = $config['params']['data']['agentid'];
    }

    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['podate'])) {
      $podate = $config['params']['data']['podate'];
    }

    if (isset($config['params']['data']['projectid'])) {
      $project = $config['params']['data']['projectid'];
    }

    if ($systype == 'REALESTATE') {

      if (isset($config['params']['data']['projectid'])) {
        $projectid = $config['params']['data']['projectid'];
      }
      if (isset($config['params']['data']['phaseid'])) {
        $phaseid = $config['params']['data']['phaseid'];
      }
      if (isset($config['params']['data']['modelid'])) {
        $modelid = $config['params']['data']['modelid'];
      }
      if (isset($config['params']['data']['blklotid'])) {
        $blklotid = $config['params']['data']['blklotid'];
      }
      if (isset($config['params']['data']['amenityid'])) {
        $amenityid = $config['params']['data']['amenityid'];
      }
      if (isset($config['params']['data']['subamenityid'])) {
        $subamenityid = $config['params']['data']['subamenityid'];
      }

      if ($projectid == 0) {
        $projectid = $this->coreFunctions->getfieldvalue($this->head, "projectid", "trno=?", [$trno]);
      }
      if ($phaseid == 0) {
        $phaseid = $this->coreFunctions->getfieldvalue($this->head, "phaseid", "trno=?", [$trno]);
      }
      if ($modelid == 0) {
        $modelid = $this->coreFunctions->getfieldvalue($this->head, "modelid", "trno=?", [$trno]);
      }
      if ($blklotid == 0) {
        $blklotid = $this->coreFunctions->getfieldvalue($this->head, "blklotid", "trno=?", [$trno]);
      }
      if ($amenityid == 0) {
        $amenityid = $this->coreFunctions->getfieldvalue($this->head, "amenityid", "trno=?", [$trno]);
      }
      if ($subamenityid == 0) {
        $subamenityid = $this->coreFunctions->getfieldvalue($this->head, "subamenityid", "trno=?", [$trno]);
      }
    }


    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      if ($db != 0) {
        $damt = $db;
      } else {
        $damt = $cr;
      }
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $config['params']['line'] = $line;

      if ($db != 0) {
        $ddb = $this->coreFunctions->getfieldvalue($this->detail, 'db', 'trno=? and line =?', [$trno, $line]);

        if ($db != number_format($ddb, 2)) {
          $damt = $db;
        } else {
          $damt = $config['params']['data']['damt'];
        }
      } else {
        $dcr = $this->coreFunctions->getfieldvalue($this->detail, 'cr', 'trno=? and line =?', [$trno, $line]);
        if ($cr != number_format($dcr, 2)) {
          $damt = $cr;
        } else {
          $damt = $config['params']['data']['damt'];
        }
      }
    }
    $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$acno]);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'acnoid' => $acnoid,
      'client' => $client,
      'db' => $db,
      'cr' => $cr,
      'fdb' => $fdb,
      'fcr' => $fcr,
      'postdate' => $postdate,
      'rem' => $rem,
      'projectid' => $project,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'checkno' => $checkno,
      'isewt' => $isewt,
      'isvat' => $isvat,
      'isvewt' => $isvewt,
      'ewtcode' => $ewtcode,
      'ewtrate' => $ewtrate,
      'damt' => $damt
    ];


    if ($systype == 'REALESTATE') {
      $data['projectid'] = $projectid;
      $data['phaseid'] = $phaseid;
      $data['modelid'] = $modelid;
      $data['blklotid'] = $blklotid;
      $data['amenityid'] = $amenityid;
      $data['subamenityid'] = $subamenityid;
    }

    if ($config['params']['companyid'] == 10) { //afti
      $data['agentid'] = $agentid;
      $data['poref'] = $poref;

      if ($podate == '') {
        $podate = date('Y-m-d');
      }

      $data['podate'] = $podate;
    }

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    $msg = '';

    if ($isvewt == "true" && ($isewt == "true" || $isvat == "true")) {
      $msg = 'Already tagged as VEWT, remove tagging for EWT/VAT';
      return ['status' => false, 'msg' => $msg];
    }

    if ($action == 'insert') {
      $data['encodedby'] = $config['params']['user'];
      $data['encodeddate'] = $current_timestamp;
      $data['sortline'] =  $data['line'];
      if ($this->coreFunctions->sbcinsert($this->detail, $data) == 1) {
        $msg = 'Account was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'ADD - Line:' . $line . ' Code:' . $acno . ' DB:' . $db . ' CR:' . $cr . ' Client:' . $client . ' Date:' . $postdate);
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $msg = "Payment Amount is greater than Amount Setup";
          }
        }
        $row = $this->opendetailline($config);
        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add Account Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      if ($this->coreFunctions->sbcupdate($this->detail, $data, ['trno' => $trno, 'line' => $line]) == 1) {
        if ($refx != 0) {
          if (!$this->sqlquery->setupdatebal($refx, $linex, $acno, $config)) {
            $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $line]);
            $this->sqlquery->setupdatebal($refx, $linex, $acno, $config);
            $return = false;
          }
        }
      } else {
        $return = false;
      }
      return ['status' => $return, 'msg' => ''];
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select coa.acno,t.refx,t.linex from ' . $this->detail . ' as t left join coa on coa.acnoid=t.acnoid where t.trno=? and t.refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->sqlquery->setupdatebal($data[$key]->refx, $data[$key]->linex, $data[$key]->acno, $config);
    }
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'DELETED ALL ACCTG ENTRIES');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'accounting' => []];
  }



  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->opendetailline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->detail . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    if ($data[0]->refx != 0) {
      $this->sqlquery->setupdatebal($data[0]->refx, $data[0]->linex, $data[0]->acno, $config);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'DETAILINFO',
      'DELETE - Line:' . $line
        . ' Notes:' . $config['params']['row']['rem']
    );
    $this->logger->sbcwritelog($trno, $config, 'ACCTG', 'REMOVED - Line:' . $line . ' Code:' . $data[0]['acno'] . ' DB:' . $data[0]['db'] . ' CR:' . $data[0]['cr'] . ' Client:' . $data[0]['client'] . ' Date:' . $data[0]['postdate'] . ' Ref:' . $data[0]['ref']);
    return ['status' => true, 'msg' => 'Account was successfully deleted.'];
  } // end function

  public function getunpaidselected($config)
  {
    $trno = $config['params']['trno'];
    $systype = $this->companysetup->getsystemtype($config['params']);
    $rows = [];
    $data = $config['params']['rows'];
    foreach ($data as $key => $value) {
      $config['params']['data']['acno'] = $data[$key]['acno'];
      $config['params']['data']['acnoname'] = $data[$key]['acnoname'];
      if ($data[$key]['db'] != 0) {
        $config['params']['data']['db'] = 0;
        $config['params']['data']['cr'] = $data[$key]['bal'];
        $config['params']['data']['fdb'] = 0;
        $config['params']['data']['fcr'] = abs($data[$key]['fdb']);
      } else {
        $config['params']['data']['db'] = $data[$key]['bal'];
        $config['params']['data']['cr'] = 0;
        $config['params']['data']['fdb'] = $data[$key]['fdb'];
        $config['params']['data']['fcr'] = 0;
      }
      $config['params']['data']['postdate'] = $data[$key]['dateid'];
      $config['params']['data']['rem'] = $data[$key]['rem'];
      $config['params']['data']['projectid'] = $data[$key]['projectid'];
      $config['params']['data']['client'] = $data[$key]['client'];
      $config['params']['data']['refx'] = $data[$key]['trno'];
      $config['params']['data']['linex'] = $data[$key]['line'];
      $config['params']['data']['ref'] = $data[$key]['docno'];


      if ($systype == 'REALESTATE') {

        $config['params']['data']['projectid'] = $data[$key]['projectid'];
        $config['params']['data']['phaseid'] = $data[$key]['phaseid'];
        $config['params']['data']['modelid'] = $data[$key]['modelid'];
        $config['params']['data']['blklotid'] = $data[$key]['blklotid'];
        $config['params']['data']['amenityid'] = $data[$key]['amenityid'];
        $config['params']['data']['subamenityid'] = $data[$key]['subamenityid'];
      }
      $return = $this->additem('insert', $config);
      if ($return['status']) {
        array_push($rows, $return['row'][0]);
      }
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Added Accounts Successfull...'];
  } //end function

  public function generateewt($config)
  {
    $trno = $config['params']['trno'];
    $data = $config['params']['row'];
    $status = true;
    $msg = '';
    $entry = [];
    $vatrate = 0;
    $vatrate2 = 0;
    $vatvalue = 0;
    $ewtvalue = 0;
    $dbval = 0;
    $crval = 0;
    $db = 0;
    $cr = 0;
    $damt = 0;
    $line = 0;
    $forex = $data[0]['forex'];
    $cur = $data[0]['cur'];
    $ap = 0;
    $exp = 0;
    $ewtacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['WT1']);
    $taxacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX1']);

    if (empty($ewtacno) || empty($taxacno)) {
      $status = false;
      $msg = "Please setup account for EWT and Input VAT";
    } else {

      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $ewtacno, "delete");
      $this->coreFunctions->execqry("delete from ladetail where trno = " . $trno . " and acnoid =" . $taxacno, "delete");

      foreach ($data as $key => $value) {
        if ($value['isvat'] == true or $value['isewt'] == true or $value['isvewt'] == true) {
          $damt   = $value['damt'];

          if ($value['isvewt'] == 'true') { //for vewt
            if (floatval($value['db']) != 0) {
              $dbval = $damt;
              $ewtvalue = $ewtvalue + (($dbval / 1.12) * ($value['ewtrate'] / 100));
            } else {
              $crval = $damt;
              $ewtvalue = $ewtvalue + ((($crval / 1.12) * ($value['ewtrate'] / 100)) * -1);
            }
          }

          if ($value['isvat']  == 'true') { //for vat computation
            $vatrate = 1.12;
            $vatrate2 = .12;

            if (floatval($value['db']) != 0) {
              $dbval = $damt / $vatrate;
              $vatvalue = $vatvalue + ($dbval * $vatrate2);
            } else {
              $crval = $damt / $vatrate;
              $vatvalue =  $vatvalue + (($crval * $vatrate2) * -1);
            }
          }

          if ($value['isewt']  == 'true') { //for ewt
            if (floatval($value['db']) != 0) {
              if ($value['isvat'] == 'true') {
                $dbval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
              } else {
                $dbval = $damt;
                $ewtvalue = $ewtvalue + ($dbval * ($value['ewtrate'] / 100));
              }
            } else {
              if ($value['isvat'] == 'true') {
                $crval = $damt / $vatrate;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              } else {
                $crval = $damt;
                $ewtvalue = $ewtvalue + (($crval * ($value['ewtrate'] / 100)) * -1);
              }
            }
          }

          $exp = $exp + ($dbval - $crval);

          $ret = $this->coreFunctions->execqry("update ladetail set db = " . round($dbval, 2) . ",cr=" . round($crval, 2) . ",fdb=" . round($dbval * $value['forex'], 2) . ",fcr=" . round($crval * $value['forex'], 2) . " where trno = " . $trno . " and line = " . $value['line'], "update");
          if ($value['refx'] != 0) {
            if (!$this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config)) {
              $this->coreFunctions->sbcupdate($this->detail, ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $trno, 'line' => $value['line']]);
              $this->sqlquery->setupdatebal($value['refx'], $value['linex'], $value['acno'], $config);
              $msg = "Payment Amount is greater than Amount Setup";
              $status = false;
              $vatvalue = 0;
              $ewtvalue = 0;
            }
          }
        }
      }

      $qry = "select line as value from " . $this->detail . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;

      if ($vatvalue != 0) {
        $entry = [
          'line' => $line,
          'acnoid' => $taxacno,
          'client' => $data[0]['client'],
          'cr' => ($vatvalue < 0 ? abs(round($vatvalue, 2)) : 0),
          'db' => ($vatvalue < 0 ? 0 : abs(round($vatvalue, 2))),
          'postdate' => $data[0]['dateid'],
          'fdb' => ($vatvalue < 0 ? 0 : abs($vatvalue)) * $forex,
          'fcr' => ($vatvalue < 0 ? abs($vatvalue) : 0) * $forex,
          'rem' => "Auto entry",
          'cur' => $cur,
          'forex' => $forex
        ];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }

      if ($ewtvalue != 0 && $status == true) {
        $entry = ['line' => $line, 'acnoid' => $ewtacno, 'client' => $data[0]['client'], 'cr' => ($ewtvalue < 0 ? 0 : abs(round($ewtvalue, 2))), 'db' => ($ewtvalue < 0 ? abs(round($ewtvalue, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ewtvalue > 0 ? 0 : abs($ewtvalue)) * $forex, 'fcr' => ($ewtvalue > 0 ? abs($ewtvalue) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        $line = $line + 1;
      }

      $ap = ($exp + $vatvalue) - $ewtvalue;


      if ($ap != 0 && $status == true) {
        $apacno = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AP2']);
        $entry = ['line' => $line, 'acnoid' => $apacno, 'client' => $data[0]['client'], 'cr' => ($ap < 0 ? 0 : abs(round($ap, 2))), 'db' => ($ap < 0 ? abs(round($ap, 2)) : 0), 'postdate' => $data[0]['dateid'], 'fdb' => ($ap > 0 ? 0 : abs($ap)) * $forex, 'fcr' => ($ap > 0 ? abs($ap) : 0) * $forex, 'rem' => "Auto entry", 'cur' => $cur, 'forex' => $forex];

        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      if (!empty($this->acctg)) {
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        foreach ($this->acctg as $key => $value) {
          foreach ($value as $key2 => $value2) {
            $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
          }

          $this->acctg[$key]['editdate'] = $current_timestamp;
          $this->acctg[$key]['editby'] = $config['params']['user'];
          $this->acctg[$key]['encodeddate'] = $current_timestamp;
          $this->acctg[$key]['encodedby'] = $config['params']['user'];
          $this->acctg[$key]['trno'] = $config['params']['trno'];
        }

        if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY SUCCESS');
          $msg = "AUTOMATIC ACCOUNTING ENTRY SUCCESS";
          $status = true;
        } else {
          $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING ENTRY FAILED');
          $msg = "AUTOMATIC ACCOUNTING ENTRY FAILED";
          $status = false;
        }
      }
    }

    $data = $this->opendetail($trno, $config);
    return ['accounting' => $data, 'status' => $status, 'msg' => $msg];
  } //end function

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    //AR
    $crqry = "
    select  head.docno, date(head.dateid) as dateid, head.trno,
    CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
    from glhead as head
    left join gldetail as detail on head.trno = detail.trno
    where head.trno = ?";
    $crdata = $this->coreFunctions->opentable($crqry, [$config['params']['trno']]);
    if (!empty($crdata)) {
      $startx = 550;
      $a = 0;
      foreach ($crdata as $key2 => $value2) {
        data_set(
          $nodes,
          'cr',
          [
            'align' => 'left',
            'x' => $startx + 400,
            'y' => 100,
            'w' => 250,
            'h' => 80,
            'type' => $crdata[$key2]->docno,
            'label' => $crdata[$key2]->rem,
            'color' => 'red',
            'details' => [$crdata[$key2]->dateid]
          ]
        );
        array_push($links, ['from' => 'cr', 'to' => 'sj']);

        //CR
        $qry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ?
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem, detail.refx
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ?";
        $t = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
        if (!empty($t)) {
          data_set(
            $nodes,
            'sj',
            [
              'align' => 'left',
              'x' => $startx,
              'y' => 100,
              'w' => 250,
              'h' => 80,
              'type' => $t[0]->docno,
              'label' => $t[0]->rem,
              'color' => 'green',
              'details' => [$t[0]->dateid]
            ]
          );
        }
      }
    }
    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'uploadexcel':
        return $this->uploadexcelaccouting($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function uploadexcelaccouting($config)
  {
    $rawdata = $config['params']['data'];
    $trno = $config['params']['dataparams']['trno'];

    $msg = '';
    $status = true;

    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'Uploading accounts...');

    foreach ($rawdata as $key => $value) {
      $config['params']['data'] = [];
      try {
        if (isset($rawdata[$key]['AccntCode'])) {

          $acno = str_replace("\\", "", $rawdata[$key]['AccntCode']);
          if ($acno != '') {

            $sql = "select acnoid as value from coa where acno='\\\\" . $acno . "'";
            $acnoid = $this->coreFunctions->datareader($sql, [], '', true);

            if ($acnoid == 0) {
              $msg .= 'Failed to upload ' . $acno . '  does not exist. ';
              continue;
            }

            $acnoname = $this->coreFunctions->getfieldvalue("coa", "acnoname", "acnoid = '" . $acnoid . "'");

            $db = 0;
            $cr = 0;

            isset($rawdata[$key]['Debit']) ? (is_numeric($rawdata[$key]['Debit']) ? $db = number_format($rawdata[$key]['Debit'], 2, '.', '') : $db = 0) : $db = 0;
            isset($rawdata[$key]['Credit']) ? (is_numeric($rawdata[$key]['Credit']) ? $cr = number_format($rawdata[$key]['Credit'], 2, '.', '') : $cr = 0) : $cr = 0;

            $config['params']['trno'] =  $trno;
            $config['params']['data']['acnoid'] = $acnoid;
            $config['params']['data']['acno'] = "\\" . $acno;
            $config['params']['data']['acnoname'] = $acnoname;
            $config['params']['data']['db']  = $db;
            $config['params']['data']['cr']  = $cr;
            $config['params']['data']['fdb']  = 0;
            $config['params']['data']['fcr']  = 0;
            $config['params']['data']['rem']  = isset($rawdata[$key]['Remarks']) ? $rawdata[$key]['Remarks'] : '';
            $config['params']['data']['ref']  = isset($rawdata[$key]['Reference']) ? $rawdata[$key]['Reference'] : '';
            $config['params']['data']['client'] = '';

            $postdate = '';
            if (isset($rawdata[$key]['Date'])) {
              $postdate = $rawdata[$key]['Date'];
              if ($postdate != '') {
                if (is_numeric($postdate)) {
                  $UNIX_DATE = ($postdate - 25569) * 86400;
                  $postdate = gmdate("Y-m-d", $UNIX_DATE);
                }
                $config['params']['data']['postdate'] = $postdate;
              }
            }

            if (isset($rawdata[$key]['ClientCode'])) {
              if ($rawdata[$key]['ClientCode'] != '') {
                $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['ClientCode'] . "'", [], '', true);
                if ($clientid == 0) {
                  if (!str_contains($msg, $rawdata[$key]['ClientCode'])) $msg .= 'Failed to upload ' . $rawdata[$key]['ClientCode'] . '  doesn`t exist. ';
                  continue;
                }
                $config['params']['data']['client'] = $rawdata[$key]['ClientCode'];
              }
            }
          }

          $return = $this->additem('insert', $config);
          if (!$return['status']) {
            $status = false;
            $msg .= 'Failed to insert ' . $rawdata[$key]['AccntCode']  . ' (' . $rawdata[$key]['ClientCode'] . '). ' . $return['msg'];
            goto exithere;
          }
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
        goto exithere;
      }
    }

    exithere:
    if ($msg == '') {
      $msg = 'Successfully uploaded.';
    }

    $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'Finished uploading');

    $config['params']['trno'] =  $trno;
    $this->loadheaddata($config);
    return ['status' => $status, 'msg' => $msg, 'reloadhead' => true, 'trno' => $trno];
  }
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
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 39: //cbbsi
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);
        break;
      case 40: //cdo
        $dataparams = $config['params']['dataparams'];
        if (isset($dataparams['prepared'])) $this->othersClass->writeSignatories($config, 'prepared', $dataparams['prepared']);
        if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'approved', $dataparams['approved']);
        if (isset($dataparams['recieved'])) $this->othersClass->writeSignatories($config, 'recieved', $dataparams['checked']);
        break;
    }

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
