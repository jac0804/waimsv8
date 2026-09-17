<?php

namespace App\Http\Classes\modules\b937d22d7044b3dea38a2a3628b7d6d37;

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

class ue
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Produce Items';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => false];
  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'IS1';
  private $fields = ['trno', 'docno', 'dateid','yourref','ourref','rem','contra','projectid','client','clientname', 'pdtrno']; //'wh'
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
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 5992,
      'edit' => 5993,
      'new' => 5994,
      'save' => 5995,
      'delete' => 5996,
      'print' => 5997,
      'lock' => 5998,
      'unlock' => 5999,
      'post' => 6000,
      'unpost' => 6001,
      'acctg' => 6002,
      'additem' => 6003,
      'edititem' => 6004,
      'deleteitem' => 6005
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
  
    $getcols = ['action', 'liststatus','listdocument', 'listdate', 'listpddocno', 'itemname', 'yourref', 'ourref', 'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    // $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$listdate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$listpddocno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$postdate]['label'] = 'Post Date';
    $cols[$listpddocno]['label'] = 'JO Docno';
    $cols[$itemname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$itemname]['type'] = 'input';
    $cols[$itemname]['label'] = 'Item';
    // if ($companyid != 28) $cols[$rem]['type'] = 'coldel'; //not xcomp
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
        return [];
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');

    $companyid = $config['params']['companyid'];
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];

    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = '';
    $addparams = '';
    $join = '';
    $hjoin = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if ($test['selectprefix'] != "") {
        switch ($test['selectprefix']) {
          case 'Item Code':
            $addparams = " and (item.partno like '%" . $test['docno'] . "%')";
            break;
          case 'Item Name':
            $addparams = " and (item.itemname like '%" . $test['docno'] . "%' )";
            break;
          case 'Model':
            $addparams = " and (model.model_name like '%" . $test['docno'] . "%' )";
            break;
          case 'Brand':
            $addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%' )";
            break;
          case 'Item Group':
            $addparams = " and (p.name like '%" . $test['docno'] . "%')";
            break;
        }

        if (isset($test)) {
          $join = " left join " . $this->stock . " as stock on head.trno = stock.trno 
          left join item on item.itemid = stock.itemid 
          left join model_masterfile as model on model.model_id = item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join projectmasterfile as p on p.line = item.projectid ";

          $hjoin = " left join " . $this->hstock . " as stock on head.trno = stock.trno 
          left join item on item.itemid = stock.itemid 
          left join model_masterfile as model on model.model_id = item.model 
          left join frontend_ebrands as brand on brand.brandid = item.brand 
          left join projectmasterfile as p on p.line = item.projectid ";
          $limit = '';
        }
      }
    }

        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        $orderby = "order by dateid desc, docno desc";
    


    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'wh.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];
      if ($companyid == 28) array_push($searchfield, 'head.rem'); //xcomp
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "select head.trno,head.docno,wh.clientname,$dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
     head.yourref, head.ourref, head.rem, jo.docno as pddocno, item.itemname
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join client as wh on wh.client=head.wh 
     left join hpdhead as jo on jo.trno=head.pdtrno
      left join item on item.itemid=jo.itemid
     " . $join . " where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "
     " . $addparams . " " . $filtersearch . "
     union all
     select head.trno,head.docno,wh.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby, date(num.postdate) as postdate,
      head.yourref, head.ourref, head.rem, jo.docno as pddocno, item.itemname
     from " . $this->hhead . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     left join client as wh on wh.clientid=head.whid " . $hjoin . " 
     left join hpdhead as jo on jo.trno=head.pdtrno
      left join item on item.itemid=jo.itemid
     where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . "
     " . $addparams . " " . $filtersearch . "
     $orderby $limit";

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array('load', 'new', 'save', 'delete', 'cancel', 'print', 'post', 'unpost', 'lock', 'unlock', 'logs', 'edit', 'backlisting', 'toggleup', 'toggledown', 'help', 'others');
    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'cswhname', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'cswhname', 'dateid', 'yourref', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    $buttons['others']['items']['uploadexcel'] = ['label' => 'Upload Items', 'todo' => ['type' => 'uploadexcel', 'action' => 'uploadexcel', 'lookupclass' => 'uploadexcel', 'access' => 'view']];
    $buttons['others']['items']['downloadexcel'] = ['label' => 'Download AJ Template', 'todo' => ['type' => 'downloadexcel', 'action' => 'downloadexcel', 'lookupclass' => 'downloadexcel', 'access' => 'view']];
    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'aj', 'title' => 'AJ_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrycntnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrycntnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    return $return;
  }

  public function createTab($access, $config)
  {
    // $companyid = $config['params']['companyid'];
    $isexpiry = $this->companysetup->getisexpiry($config['params']);
    // $invonly = $this->companysetup->isinvonly($config['params']);
    $viewcost = $this->othersClass->checkAccess($config['params']['user'], 368);
    // $ispallet = $this->companysetup->getispallet($config['params']);
    // $iskgs = $this->companysetup->getiskgs($config['params']);
    // $systemtype = $this->companysetup->getsystemtype($config['params']);
    $islocation = $this->companysetup->getislocation($config['params']);
    $locname = $this->companysetup->getlocname($config['params']);
    $headgridbtns = [];

    $columns =  [
      'action',
      'barcode',
      'itemname',
      'isqty',
      'uom',
      'isamt',
      'ext',
      'wh',
      'ref',
      'loc',
      'expiry',
      'rem'
    ];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $periodic = $this->companysetup->getisperiodic($config['params']);
      if (!$periodic) {
        $headgridbtns = ['viewdistribution'];
      }
    
    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];
    $tab = [
      $this->gridname => [
        'gridcolumns' => $columns,
        'sortcolumns' => $columns,
        'computefield' =>  $computefield,
        'headgridbtns' => $headgridbtns
      ],
    ];

      $stockbuttons = ['save', 'delete'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    if ($viewcost == '0') {
        $obj[0]['inventory']['columns'][$rrcost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$cost]['type'] = 'coldel';
        $obj[0]['inventory']['columns'][$ext]['type'] = 'coldel';

    }

        if ($isexpiry) {
          $obj[0]['inventory']['columns'][$expiry]['type'] = 'date';
        }
  
    $obj[0]['inventory']['columns'][$loc]['label'] = $locname;

    if ($islocation) {
   
          $obj[0]['inventory']['columns'][$loc]['readonly'] = false;
          $obj[0]['inventory']['columns'][$loc]['type'] = 'editlookup';
    }

  
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'label';
    
    $obj[0]['inventory']['columns'][$itemname]['type'] = 'label';
    $obj[0]['inventory']['columns'][$itemname]['label'] = 'Itemname';
    $obj[0]['inventory']['columns'][$itemname]['style'] = 'text-align: center; width:200px;whiteSpace: normal;min-width:200px;';

    // $obj[0]['inventory']['columns'][$isqty]['type'] = 'label';
    // $obj[0]['inventory']['columns'][$isqty]['style'] = 'text-align: center; width:150px;whiteSpace: normal;min-width:150px;';

    $obj[0]['inventory']['columns'][$uom]['type'] = 'label';
    $obj[0]['inventory']['columns'][$isamt]['type'] = 'label';
    $obj[0]['inventory']['columns'][$wh]['type'] = 'label';
    $obj[0]['inventory']['columns'][$wh]['label'] = 'Source WH';
    $obj[0]['inventory']['columns'][$ref]['type'] = 'label';
    $obj[0]['inventory']['columns'][$loc]['type'] = 'label';
    $obj[0]['inventory']['columns'][$expiry]['type'] = 'label';
    // $obj[0]['inventory']['columns'][$rem]['type'] = 'label';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['additem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'JO';
    $obj[0]['lookupclass'] = 'pendingjobtndetail';
    $obj[0]['action'] = 'pendingjodetail';
    return $obj;
  }

  public function createHeadField($config)
  {
      $fields = ['docno', 'dwhname2', 'isacnoname','dateid']; // 'dwhname',
    $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'docno.label', 'Transaction#');
      // data_set($col1, 'dwhname.label', 'Source Warehouse');
      data_set($col1, 'dwhname2.required', true);
      data_set($col1, 'dwhname2.label', 'Destination Warehouse');
      data_set($col1, 'isacnoname.label', 'IS Account');
      $fields = ['sodocno', 'barcode', 'itemname'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'itemname.class', 'sbccsreadonly');
      data_set($col2, 'itemname.label', 'Itemname');
      data_set($col2, 'sodocno.required', true);
      data_set($col2, 'sodocno.type', 'lookup');
      data_set($col2, 'sodocno.label', 'Job Order #');
      data_set($col2, 'sodocno.class', 'cssodocno sbccsreadonly');
      data_set($col2, 'sodocno.action', 'pendingjodetail');
      data_set($col2, 'sodocno.lookupclass', 'pendingjoheaddetail');
      data_set($col2, 'sodocno.readonly', true);
      data_set($col2, 'barcode.class', 'sbccsreadonly');
      data_set($col2, 'barcode.type', 'input');
      $fields = [['qty', 'uom'],['yourref', 'ourref'],'dprojectname'];
      $col3 = $this->fieldClass->create($fields);
      data_set($col3, 'uom.class', 'sbccsreadonly');
      data_set($col3, 'qty.class', 'sbccsreadonly');
      $fields = ['rem'];
      $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['contra'] = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', [$this->defaultContra]);
    $data[0]['acnoname'] = $this->coreFunctions->getfieldvalue('coa', 'acnoname', 'acno=?', [$data[0]['contra']]);
    // $data[0]['wh'] = $this->companysetup->getwh($params);
    // $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh']]);
    // $data[0]['whname'] = $name;
    $data[0]['projectcode'] = '';
    $data[0]['projectid'] = 0;
    $data[0]['projectname'] = '';
    $data[0]['forex'] = 1;
    $data[0]['wh2'] = $this->companysetup->getwh($params);
    $name1 = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['wh2']]);
    $wh2id = $this->coreFunctions->getfieldvalue('client', 'client', 'client=?', [$data[0]['wh2']]);
    $data[0]['wh2name'] = $name1;
    $data[0]['whid2'] = $wh2id;
    $data[0]['client'] = $wh2id;
    $data[0]['clientname'] = $name1;

    // $data[0]['sodocno'] = '';
    $data[0]['pdtrno'] = 0;

    return $data;
  }

  public function loadheaddata($config)
  {
    ini_set('memory_limit', '-1');

    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    $tablenum = $this->tablenum;
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
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
         client.client as whid2,
         client.clientname as wh2name, 
         head.terms,
         head.cur,
         head.forex,
         head.yourref,
         head.ourref,
         head.contra,
         coa.acnoname,
         '' as dacnoname,
         left(head.dateid,10) as dateid,
         head.address,
         head.shipto,
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.rem,
         head.tax,
         head.vattype,
         '' as dvattype,
         warehouse.client as wh,
         warehouse.clientname as whname,
         '' as dwhname,
         head.projectid,
         ifnull(project.name,'') as projectname,ifnull(project.code,'') as projectcode,
         '' as dprojectname,
         left(head.due,10) as due,
         client.groupid, jo.docno as sodocno, jo.qty,jo.uom, it.itemname,it.barcode, head.pdtrno";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid 
        left join hpdhead as jo on jo.trno=head.pdtrno
        left join item as it on it.itemid=jo.itemid
        where head.trno = ? and num.doc=? and num.center = ?
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.clientid = client.clientid
        left join client as warehouse on warehouse.clientid = head.whid
        left join coa on coa.acno=head.contra
        left join projectmasterfile as project on project.line=head.projectid
        left join hpdhead as jo on jo.trno=head.pdtrno
        left join item as it on it.itemid=jo.itemid
        where head.trno = ? and num.doc=? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $doc, $center, $trno, $doc, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);

      $hideobj = [];
      if ($this->companysetup->getistodo($config['params'])) {
        $btndonetodo = $this->othersClass->checkdonetodo($config, $tablenum);
        $hideobj = ['donetodo' => !$btndonetodo];
      }

      return  [
        'head' => $head,
        'griddata' => ['inventory' => $stock],
        'islocked' => $islocked,
        'isposted' => $isposted,
        'isnew' => false,
        'status' => true,
        'msg' => $msg,
        'hideobj' => $hideobj
      ];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $companyid = $config['params']['companyid'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    if (isset($head['whid2'])) {
      $head['client'] = $head['whid2'];
    }

    if (isset($head['wh2name'])) {
      $head['clientname'] = $head['wh2name'];
    }

    $dateTables = ['lahead'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
        } //end if
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($isupdate) {

      $stock = $this->coreFunctions->opentable("select trno from " . $this->stock . " where trno=?", [$head['trno']]);
      if (!empty($stock)) {
        unset($data['pdtrno']); //di na maeedit o mababago yung JO sa head pag may laman yung stock
        // return ['trno' => $head['trno'], 'status' => false, 'msg' => 'Can\'t proceed. This transaction already has items.'];
       } else {
        $prevjotrno = $this->coreFunctions->getfieldvalue("lahead", 'pdtrno', 'trno=?', [$head['trno']]);
        $newjotrno = $head['pdtrno'];
        if ($newjotrno != $prevjotrno) {
          $updatejohead = $this->coreFunctions->sbcupdate('hpdhead', ['isproduce' => 0], ['trno' => $prevjotrno]);
          if ($updatejohead == 0) {
            return ['trno' => $head['trno'], 'status' => false, 'msg' => 'Saving failed. Problems in updating previous Job Order head.'];
          }
          $newupdate = $this->coreFunctions->sbcupdate('hpdhead', ['isproduce' => 1], ['trno' => $newjotrno]); //17258
        }
      }
      $headUpdate = $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->recomputecost($head, $config);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->coreFunctions->sbcupdate('hpdhead', ['isproduce'=> 1], ['trno' => $head['pdtrno']]);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' :  Destination WH - ' . $head['wh2name']);
    }
  } // end function


  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $jotrno = $this->coreFunctions->getfieldvalue("lahead", 'pdtrno', 'trno=?', [$trno]);
    $jodocno = $this->coreFunctions->datareader("select docno as value from hpdhead where trno=?", [$jotrno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry("update hpdhead set isproduce =0 where docno = ?", "update", [$jodocno]);

    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from rrstatus where trno= ? ", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $periodic = $this->companysetup->getisperiodic($config['params']);

    // $serial = $this->companysetup->getserial($config['params']);


    // if ($serial) {
    //   if (!$this->othersClass->checkserialin($config)) {
    //     return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. There are serialized items. To proceed, please encode the serial number.'];
    //   }
    // }

    if ($periodic) {
      return $this->othersClass->posttranstock($config);
    }

    if ($this->companysetup->isinvonly($config['params'])) {
      return $this->othersClass->posttranstock($config);
    } else {
      if ($companyid != 10) {
        $checkacct = $this->othersClass->checkcoaacct(['IN1', 'IS1']);

        if ($checkacct != '') {
          return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
        }
      }


      $stock = $this->openstock($trno, $config);
      $checkcosting = $this->othersClass->checkcosting($stock);
      if ($checkcosting != '') {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
      }

      if (!$this->createdistribution($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
      } else {
        return $this->othersClass->posttranstock($config);
      }
    }
  } //end function


  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];

    $result = $this->othersClass->unposttranstock($config);
    
    //dinelete sa unposttrans kaya binalik ko dito
    $qry = "select count(line) as value from lastock where trno=?";
    $countitem = $this->coreFunctions->datareader($qry, [$trno]);
      if ($countitem != 0) {
        $checkexist = $this->coreFunctions->getfieldvalue('rrstatus', 'trno', 'trno=?', [$trno]);
        if ($checkexist == 0) {
          $path = 'App\Http\Classes\modules\tableentry\issuemultipleexpiry';
          app($path)->transfertowh($config);
        }
      }

    return $result;
  } //end function


  private function getstockselect($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    $sqlselect = "select item.brand as brand,
    item.itemid,
    stock.trno,
    stock.line,
    stock.sortline,
    stock.refx,
    stock.linex,
    item.barcode,
    item.itemname,
    stock.uom,
    FORMAT(uom.factor*stock.cost,6) as cost,
    stock." . $this->hamt . ",
    stock." . $this->hqty . " as iss,
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as isqty,
    FORMAT(stock." . $this->dqty . "," . $qty_dec . ")  as qty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    stock.void,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    stock.expiry,
    item.brand,
    stock.rem,
    stock.locid,
    ifnull(location.loc,'') as location,
    ifnull(uom.factor,1) as uomfactor,
    round(case when (stock.Amt>0 and stock.iss>0) then (((((stock.Amt * stock.ISS) - (stock.Cost * stock.Iss)) / (stock.amt * stock.Iss))/head.forex)*100) else 0 end,2) as markup,
    stock.rebate,
    round(case when stock.Amt>0 then ((stock.amt-stock.cost)/head.forex) else 0 end,2) as gprofit,
    '' as bgcolor,
    '' as errcolor,
    case when stock.noprint=0 then 'false' else 'true' end as noprint";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);

    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
  
    where stock.trno =? and stock.sortline <>0
    group by item.brand,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, stock.uom,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.locid,
    location.loc,uom.factor,head.forex,stock.rebate,
    stock.noprint,stock.isqty
    

    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join $this->hhead as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join location on location.line=stock.locid
    left join client as warehouse on warehouse.clientid=stock.whid
 
    where stock.trno =?  and stock.sortline <>0
    group by item.brand,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, stock.uom,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.locid,
    location.loc,uom.factor,head.forex,stock.rebate,
    stock.noprint, stock.isqty
    order by sortline, line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join item on item.itemid=stock.itemid
    left join location on location.line=stock.locid
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    where stock.trno = ? and stock.line = ? 
    group by item.brand,item.itemid,stock.trno,stock.line,stock.sortline,
    stock.refx,stock.linex,item.barcode,item.itemname, stock.uom,
    stock.cost,stock." . $this->hamt . ",stock." . $this->hqty . ",
    FORMAT(stock." . $this->damt . "," . $this->companysetup->getdecimal('price', $config['params']) . "),
    FORMAT(stock." . $this->dqty . "," . $qty_dec . "),
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") ,
    stock.encodeddate,stock.disc,stock.void,stock.ref,stock.whid,warehouse.client,
    warehouse.clientname,stock.loc,stock.expiry,stock.rem,stock.locid,
    location.loc,uom.factor,head.forex,stock.rebate,
    stock.noprint, stock.isqty";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function


  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem':
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
        break;
      case 'deleteallitem':
        return $this->deleteallitem($config);
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
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'uploadexcel':
        return $this->othersClass->uploadexcel($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        $tablenum = $this->tablenum;
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'updatecost': //kinggeorge - used for not tally cost
        return $this->updatecost($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function updatecost($config)
  {
    ini_set('max_execution_time', 0);
    $trno = $config['params']['trno'];

    $data = $this->coreFunctions->opentable("select docno, doc, trno, line, iss, cost, actualcost,actualcost * iss
      from (
      select h.docno, h.doc, s.trno, s.line, s.iss, s.cost, 
      (  select round(ifnull(sum(rs.cost * c.served) / stock.iss,0), 6) from glstock as stock left join costing as c on c.trno=stock.trno and c.line=stock.line
          left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex 
          where stock.trno=s.trno and stock.line=s.line and stock.trno=" . $trno . " 
          group by stock.trno,stock.line,stock.iss) as actualcost
      from glstock as s left join glhead as h on h.trno=s.trno
      where s.iss<>0 and s.trno=" . $trno . "
      ) as x where round(cost,4)<>round(actualcost,4)");

    if (!empty($data)) {
      foreach ($data as $key => $value) {
        $this->coreFunctions->execqry("update glstock set cost=" . $value->actualcost . " where trno=" . $value->trno . " and line=" . $value->line);
        $this->coreFunctions->execqry("update glstock set ext=round(cost*iss,2)*-1 where trno=" . $value->trno . " and line=" . $value->line);
      }
      $this->coreFunctions->execqry("update glhead set isreentryinv=1 where trno=" . $trno);
      $this->coreFunctions->execqry("update cntnum set isok=2 where trno=" . $trno);
    }

    return ['status' => true, 'msg' => 'Finished'];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        $msg1 = ' Out of stock ';
      }

      if ($config['params']['companyid'] == 56) {
        if ($data2[$key]['reasonid'] == 0) {
          $data[$key]->errcolor = 'bg-red-2';
          $isupdate = false;
          $msg2 = ' / Reason cannot be blank';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . $msg2];
    } else {
      return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    }
  }

  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $isupdate = true;
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        $msg1 = ' Out of stock ';
      }

      if ($config['params']['companyid'] == 56) {
        if ($data2[$key]['reasonid'] == 0) {
          $data[$key]->errcolor = 'bg-red-2';
          $isupdate = false;
          $msg2 = ' / (Some reasons are blank)';
        }
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ') ' . $msg2];
    }
  } //end function


  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $msg = 'Successfully saved.';
      $config['params']['data'] = $value;
      $return = $this->additem('insert', $config);
      if ($return['status'] == false) {
        $msg = $return['msg'];
        break;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $msg2 = '';


    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $status = false;
        $msg = 'Please check; some items are out of stock.';
      }
      if ($config['params']['companyid'] == 56) {
        if ($data2[$key]['reasonid'] == 0) {
          $data[$key]->errcolor = 'bg-red-2';
          $status = false;
          $msg2 = ' / Some reasons are blank.';
        }
      }
    }
    return ['inventory' => $data, 'status' => true, 'msg' => $msg . $msg2];
  } //end function

  public function quickadd($config)
  {
    $barcodelength = $this->companysetup->getbarcodelength($config['params']);
    $config['params']['barcode'] = trim($config['params']['barcode']);
    if ($barcodelength == 0) {
      $barcode = $config['params']['barcode'];
    } else {
      $barcode = $this->othersClass->padj($config['params']['barcode'], $barcodelength);
    }
    $wh = $config['params']['wh'];
    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom,'' as expiry,'' as rem from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) {
        $item[0]['amt'] = $lprice['data'][0]['amt'];
      }

      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }


  public function additem($action, $config, $setlog = false)
  {
    $companyid = $config['params']['companyid'];
    $ispallet = $this->companysetup->getispallet($config['params']);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];

    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : "";
    $wh = $config['params']['data']['wh'];
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : '';
    $locid = isset($config['params']['data']['locid']) ? $config['params']['data']['locid'] : 0;
    $expiry = '';
    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }
      
    $kgs = 0;
   
    $refx = isset($config['params']['data']['refx']) ? $config['params']['data']['refx'] : 0;
    $linex = isset($config['params']['data']['linex']) ? $config['params']['data']['linex'] : 0;
    $ref = isset($config['params']['data']['ref']) ? $config['params']['data']['ref'] : '';
    $rebate = isset($config['params']['data']['rebate']) ? $config['params']['data']['rebate'] : 0;
    $noprint = isset($config['params']['data']['noprint']) ? $config['params']['data']['noprint'] : 'false';
    $rem = isset($config['params']['data']['rem']) ? $config['params']['data']['rem'] : '';

    $line = 0;

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
    
      $config['params']['line'] = $line;
      $amt = $config['params']['data']['amt'];
      $qty = $config['params']['data']['qty'];

    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
   
    }

    $dateTables = ['lastock', 'stockinfo'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    $amt = $this->othersClass->sanitizekeyfieldFast('amt', $amt, $lookups);
    $qty = $this->othersClass->sanitizekeyfieldFast('qty', $qty, $lookups);
    $kgs = $this->othersClass->sanitizekeyfieldFast('qty', $kgs, $lookups);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv,namt4,lastpr,defcost from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    $isnoninv = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $vat = $this->coreFunctions->getfieldvalue($this->head, 'tax', 'trno=?', [$trno]);
    $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
    $curtopeso = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur, $kgs);
 
    if (floatval($curtopeso) == 0) {
      $curtopeso = 1;
    }

    $hamt = $computedata['amt'] * $curtopeso;
 
    $hamt = $this->othersClass->sanitizekeyfieldFast('amt', $hamt, $lookups);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => $hamt,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $refx,
      'linex' => $linex,
      'rem' => $rem,
      'ref' => $ref,
      'loc' => $loc,
      'expiry' => $expiry,
      'uom' => $uom,
      'locid' => $locid,
      'rebate' => $rebate,
      'noprint' => $noprint
    ];


    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfieldFast($key, $data[$key], $lookups);
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($uom == '') {
      $msg = 'UOM cannot be blank -' . $item[0]->barcode;
      return ['status' => false, 'msg' => $msg];
    }

    //insert item
    if ($action == 'insert') {
   
      $data['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
      $data['encodedby'] = $config['params']['user'];
      if (isset($config['params']['data']['sortline'])) {
        $data['sortline'] =  $config['params']['data']['sortline'];
      } else {
        $data['sortline'] =  $data['line'];
      }

     
      $trno = $this->othersClass->val($trno);
      if ($trno == 0) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ZERO TRNO (UE)');
        return ['status' => false, 'msg' => 'Add item Failed. Zero trno generated'];
      }


      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $havestock = true;
        $msg = 'Item was successfully added.';
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' Uom:' . $uom . ' ext:' . $computedata['ext'], $setlog ? $this->tablelogs : '');
        if ($isnoninv == 0) {
          if ($ispallet) {
            $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
          } else {
            $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
          }
          if ($cost != -1) {
            $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
            //CHECK BELOW COST
            if ($this->companysetup->checkbelowcost($config['params'])) {
              $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
              if ($belowcost == 1) {
                $msg = '(' . $item[0]->barcode . ') Is this free of charge? Please check.';
              } elseif ($belowcost == 2) {
                $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
                $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
                $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
              }
            }
          } else {
            $havestock = false;
            $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0', $setlog ? $this->tablelogs : '');
          }
        }
      
          if ($this->setserveditems($refx, $linex, $companyid) == 0) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $this->setserveditems($refx, $linex, $companyid);
            $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
            $return = false;
            $msg = "(" . $item[0]->barcode . ") Qty Received is Greater than PD Qty.";
          }
        $this->othersClass->getcreditinfo($config, $this->head);
        $row = $this->openstockline($config);
        if (!$havestock) {
          $row[0]->errcolor = 'bg-red-2';
          $msg = '(' . $item[0]->barcode . ') Out of Stock.';
        }

        return ['row' => $row, 'status' => true, 'msg' => $msg];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($isnoninv == 0) {
        // if ($ispallet) {
        //   $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']);
        // } else {
          $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $config['params']['doc'], $config['params']['companyid']);
        // }
        if ($cost != -1) {
          $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);


          //CHECK BELOW COST
          if ($this->companysetup->checkbelowcost($config['params'])) {
            $belowcost = $this->othersClass->checkbelowcost($trno, $line, $config);
            if ($belowcost == 1) {
              $msg = '(' . $item[0]->barcode . ') Is this free if charge? Please check.';
            } elseif ($belowcost == 2) {
              $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
              $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
              $this->logger->sbcwritelog($trno, $config, 'STOCK', 'BELOW COST - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
              $msg = "(" . $item[0]->barcode . ") You can't issue this item/s because it's BELOW COST!!!";
              $return = false;
            }
          }
        } else {
          $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $trno, 'line' => $line]);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $this->setserveditems($refx, $linex, $companyid);
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Out of Stock.";
        }
      }

        if ($this->setserveditems($refx, $linex, $companyid) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex, $companyid);
          $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Qty Issued is Greater than SO Qty.";
        }

      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

  public function setserveditems($refx, $linex, $companyid = 0)
  {
    if ($refx == 0) {
      return 1;
    }

    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='UE' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='UE' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    $result = $this->coreFunctions->execqry("update hpdstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    return $result;
  }


  public function deleteallitem($config)
  {
    
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from costing where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry("delete from rrstatus where trno= ? ", 'delete', [$trno]);
 
  
    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      } 
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $companyid = $config['params']['companyid'];
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry("delete from costing where trno= ? and line= ? ", 'delete', [$trno, $line]);

    $qry = "select count(line) as value from lastock where trno=?";
    $countitem = $this->coreFunctions->datareader($qry, [$trno]);

    if($countitem == 1){
      $this->coreFunctions->execqry("delete from rrstatus where trno= ? ", 'delete', [$trno]);
    }

    if ($data[0]->refx !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }
    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0][$this->dqty] . ' Amt:' . $data[0][$this->damt] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $defuominout = $this->companysetup->getisdefaultuominout($config['params']);
    $data = [];
    $companyid = $config['params']['companyid'];

    $filter = '';
    switch ($config['params']['companyid']) {
      case 21: //kinggeorge
        $wh = $this->coreFunctions->getfieldvalue("lahead", "wh", "trno=?", [$trno]);
        $filter = " and wh.client='" . $wh . "'";
        break;
    }

    $costfilter = " and stock.rrcost <>0 ";
    $costfilter2 = " and stock.cost <>0 ";

    if ($companyid == 50) { //unitech
      $costfilter = "";
      $costfilter2 = "";
    }

    if ($defuominout) {
      $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from (select head.docno,head.dateid,
          stock.cost as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and stock.qty<>0
           $costfilter and cntnum.trno <>? " . $filter . "
          UNION ALL
          select head.docno,head.dateid,stock.rrcost as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and stock.qty<>0
           $costfilter and cntnum.trno <>? " . $filter . "
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
      $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
    } else {
      if ($companyid == 60) { //transpower
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(
        select '' as docno,now() as dateid,item.namt4 as amt,item.uom,'' as disc from item
        where item.barcode =? 
        union all 
        select head.docno,head.dateid,
          (stock.rrcost*if(head.forex=0,1,head.forex)) as amt,stock.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
          and item.barcode = ? and stock.qty<>0
          and stock.rrcost <> 0 and cntnum.trno <>?
          UNION ALL
          select head.docno,head.dateid,(stock.rrcost*if(head.forex=0,1,head.forex)) as amt,
          stock.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
          and item.barcode = ?  and stock.qty<>0
          and stock." . $this->damt . " <> 0 and cntnum.trno <>?
          order by dateid desc limit 5) as tbl ";
        $data = $this->coreFunctions->opentable($qry, [$barcode, $center, $barcode, $trno, $center, $barcode, $trno]);
      } else {
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
        (stock.rrcost*if(head.forex=0,1,head.forex)) as amt,stock.uom,stock.disc
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnum on cntnum.trno=head.trno
        left join item on item.itemid = stock.itemid
        left join client as wh on wh.clientid=stock.whid
        where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
        and item.barcode = ? and stock.qty<>0
         $costfilter and cntnum.trno <>?
        UNION ALL
        select head.docno,head.dateid,(stock.rrcost*if(head.forex=0,1,head.forex)) as amt,
        stock.uom,stock.disc from glhead as head
        left join glstock as stock on stock.trno = head.trno
        left join item on item.itemid = stock.itemid
        left join client on client.clientid = head.clientid
        left join cntnum on cntnum.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ? " . $filter . "
        and item.barcode = ?  and stock.qty<>0
         $costfilter and cntnum.trno <>?
        order by dateid desc limit 5) as tbl order by dateid desc limit 1";
        $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
      }
    }


    if (empty($data)) { // if walang data from filter ng rrcost latest transaction, requery sa cost na field 
      $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,'' as disc,uom from(select head.docno,head.dateid,
          stock.cost as amt,item.uom,stock.disc
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join cntnum on cntnum.trno=head.trno
          left join item on item.itemid = stock.itemid
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and stock.qty<>0 and cntnum.trno <>? $costfilter2 " . $filter . "
          UNION ALL
          select head.docno,head.dateid,stock.cost as amt,
          item.uom,stock.disc from glhead as head
          left join glstock as stock on stock.trno = head.trno
          left join item on item.itemid = stock.itemid
          left join client on client.clientid = head.clientid
          left join cntnum on cntnum.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          where head.doc in ('RR','CM','IS','AJ','TS') and cntnum.center = ?
          and item.barcode = ? and stock.qty<>0 and  cntnum.trno <>?  $costfilter2 " . $filter . "
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
      $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $trno, $center, $barcode, $trno]);
    }

    if (!empty($data)) {
      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $data[0]->docno = 'UOM';
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault = 1 where item.barcode=?", [$barcode]);
        $this->coreFunctions->LogConsole('Def' . $defuom);
        if ($defuom != "") {
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if ($data[0]->amt != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            }
          }
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $data[0]->docno = 'UOM';
          if ($companyid != 50) {
            $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
          }
        }
      }
    } else {
      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $this->coreFunctions->LogConsole("test 1");
        $data[0]['docno'] = 'UOM';

        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault = 1 where item.barcode=?", [$barcode]);
        $this->coreFunctions->LogConsole('Def(' . $barcode . ')' . $defuom);
        if ($defuom != "") {
          $data[0]['uom'] = $defuom;
          $data[0]['amt'] = 0;
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $data[0]['docno'] = 'UOM';
          if ($companyid != 50) {
            $data[0]['amt'] = $this->coreFunctions->datareader("select ifnull(uom.amt,0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
          }
        }
      }
    }


    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      return ['status' => false, 'msg' => 'No Latest price found...'];
    }
  } // end function



  public function createdistribution($config)
  {
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);
    // switch ($companyid) {
    //   case 10: //afti
    //     $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.disc,stock.rrqty,stock.projectid,head.branch,head.deptid
    //       from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
    //       left join client as wh on wh.clientid=stock.whid
    //       left join item on item.itemid=stock.itemid left join projectmasterfile as p on p.line = stock.projectid 
    //       left join coa as a on a.acnoid = p.assetid left join coa as r on r.acnoid = p.revenueid where head.trno=?';
    //     break;
    //   case 11: //summit
    //     $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.disc,stock.rrqty,head.projectid,head.whref
    //     from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
    //     left join client as wh on wh.clientid=stock.whid
    //     left join item on item.itemid=stock.itemid where head.trno=?';
    //     break;
    //   default:
        $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,round(((stock.qty-stock.iss) * stock.cost),2) as ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.rrcost,stock.disc,stock.rrqty,head.projectid
        from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
        left join client as wh on wh.clientid=stock.whid
        left join item on item.itemid=stock.itemid where head.trno=?';
    //     break;
    // }

    $dateTables = ['ladetail'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $vat = $stock[0]->tax;
      $tax1 = 0;
      $tax2 = 0;
      if ($vat != 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->rrcost - ($this->othersClass->discount($stock[$key]->rrcost, $stock[$key]->disc));
        if ($vat != 0) {
          $tax = round(($stock[$key]->ext / $tax1) * $tax2, 2);
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->rrqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'projectid' => $stock[$key]->projectid
        ];

        if ($companyid == 10) { //afti
          $params['branch'] = $stock[$key]->branch;
          $params['deptid'] = $stock[$key]->deptid;
        }

        if ($companyid == 11) { //summit
          $params['whref'] = $stock[$key]->whref;
        }

        $this->distribution($params, $config);
      }
    }
    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfieldFast($key2, $value2, $lookups);
        }
        if ($this->acctg[$key]['cr'] < 0) {
          $this->acctg[$key]['db'] =  ($this->acctg[$key]['cr']) * -1;
          $this->acctg[$key]['cr'] = 0;
        }

        if ($this->acctg[$key]['db'] < 0) {
          $this->acctg[$key]['cr'] =  ($this->acctg[$key]['db']) * -1;
          $this->acctg[$key]['db'] = 0;
        }

        $this->acctg[$key]['editdate'] = $current_timestamp;
        $this->acctg[$key]['editby'] = $config['params']['user'];
        $this->acctg[$key]['encodeddate'] = $current_timestamp;
        $this->acctg[$key]['encodedby'] = $config['params']['user'];
        $this->acctg[$key]['trno'] = $config['params']['trno'];
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status =  true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    return $status;
  } //end function

  public function distribution($params, $config)
  {
    //$doc,$trno,$client,$acno,$alias,$amt,$famt,$charge,$cogsamt,$wh,$date,$project='',$inventory='',$cogs='',$tax=0,$rem='',$revenue='',$disc='',$discamt=0
    $entry = [];
    $forex = $params['forex'];
    $companyid = $config['params']['companyid'];
    if ($forex == 0) {
      $forex = 1;
    }
    $cur = $params['cur'];
    $invamt = ($params['ext'] - $params['tax']);
    //AP
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => ($params['ext'] * $forex), 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => 0, 'fcr' => floatval($forex) == 1 ? 0 : $params['ext'], 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
        $entry['projectid'] = 0;
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }


    //INV
    if (floatval($invamt) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => ($invamt * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($invamt), 'projectid' => $params['projectid']];
      if ($companyid == 10) { //afti
        $entry['branch'] = $params['branch'];
        $entry['deptid'] = $params['deptid'];
      }
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $isreload = false;
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => $isreload];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function recomputecost($head, $config)
  {
    $companyid = $config['params']['companyid'];
    $data = $this->openstock($head['trno'], $config);
    $data2 = json_decode(json_encode($data), true);
    $exec = true;
    $dateTables = ['lastock'];
    $lookups = $this->othersClass->buildSanitizeLookups($config['params']['doc'], $companyid, [], false, $dateTables);
    foreach ($data2 as $key => $value) {
      $this->othersClass->logConsole(json_encode($value));

      $damt = $this->othersClass->sanitizekeyfieldFast('amt', $data2[$key][$this->damt], $lookups);
      $dqty =  round($this->othersClass->sanitizekeyfieldFast('qty', $data2[$key][$this->dqty], $lookups), $this->companysetup->getdecimal('qty', $config['params']));

      $computedata = $this->othersClass->computestock($damt, $data[$key]->disc, $dqty, $data[$key]->uomfactor);

      if ($config['params']['companyid'] == 23) { //labsol cebu
        if ($head['forex'] == 1 || $head['forex'] == 0) {
          $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.15 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        } else {
          $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.30 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
        }
      } elseif ($config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) { //labsol manila & technolab
        $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . "*1.05 where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      } else {
        $exec = $this->coreFunctions->execqry("update lastock set cost = " . $computedata['amt'] . " where trno = " . $head['trno'] . " and line=" . $data[$key]->line, "update");
      }
    }
    return $exec;
  }
} //end class
