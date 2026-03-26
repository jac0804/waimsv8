<?php

namespace App\Http\Classes\modules\t70e33c92835b1ef8cd37fb7d031d02db;

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
use App\Http\Classes\sbcscript\sbcscript;
use Exception;


class so
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SALES ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $statlogs = 'transnum_stat';
  public $head = 'sohead';
  public $hhead = 'hsohead';
  public $stock = 'sostock';
  public $hstock = 'hsostock';

  public $infohead = 'headinfotrans';
  public $hinfohead = 'hheadinfotrans';
  public $infostock = 'stockinfotrans';
  public $hinfostock = 'hstockinfotrans';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  private $sbcscript;
  public $fields = [
    'trno',
    'docno',
    'dateid',
    'due',
    'client',
    'clientname',
    'yourref',
    'ourref',
    'rem',
    'terms',
    'forex',
    'cur',
    'wh',
    'address',
    'agent',
    'creditinfo',
    'projectid',
    'ms_freight',
    'mlcp_freight',
    'shipto',
    'sano',
    'pono',
    'salestype',
    'statid',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid'
  ];

  private $infoheadfields = ['trnxtype', 'approvalreason'];

  public $except = ['trno', 'dateid', 'due'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'forapproval', 'label' => 'For Approval', 'color' => 'primary'],
    ['val' => 'approved', 'label' => 'Approved', 'color' => 'primary'],
    ['val' => 'locked', 'label' => 'Locked', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Posted', 'color' => 'primary'],
    ['val' => 'all', 'label' => 'All', 'color' => 'primary']
  ];

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
    $this->sbcscript = new sbcscript;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 152,
      'edit' => 153,
      'new' => 154,
      'save' => 155,
      // 'change' => 156, remove change doc
      'delete' => 157,
      'print' => 158,
      'lock' => 159,
      'unlock' => 160,
      'changeamt' => 161,
      'crlimit' => 162,
      'changedisc' => 4037,
      'post' => 163,
      'unpost' => 164,
      'additem' => 805,
      'edititem' => 806,
      'deleteitem' => 807,
      'postnoncash' => 2995,
      'whinfo' => 3889,
      'approved' => 3890,
      'revision' => 3891
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $companyid = $config['params']['companyid'];

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname','rem',  'yourref', 'ourref',  'listpostedby',  'listcreateby', 'listeditby', 'listviewby'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $stockbuttons = ['view'];

    if (!$this->companysetup->linearapproval($config['params'])) {
      unset($this->showfilterlabel[1]);
      unset($this->showfilterlabel[2]);
    }


    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$liststatus]['name'] = 'statuscolor';
    $cols[$rem]['type'] = 'label';
    $cols[$rem]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';
    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = [];
    $col1 = [];
    $allownew = $this->othersClass->checkAccess($config['params']['user'], 154);
    if ($allownew == '1') $fields = ['pickpo'];
   

    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => $col1]];
  }


  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $laext = '';
    $glext = '';

    $join = '';
    $hjoin = '';
    $addparams = '';

    $lfield = '';
    $gfield = '';
    $ljoin = '';
    $gjoin = '';
    $group = '';

    $user = $config['params']['user'];
    $userid = $this->coreFunctions->getfieldvalue("useraccess", "userid", "username=?", [$user]);

    $ustatus = "'Pending'";
    $orderby = "order by dateid desc, docno desc";

    $searchfilter = $config['params']['search'];
    $usrfilter = "";
    $lscolor = "'red'";
    $lstatus = " case ifnull(head.lockdate,'') when '' then  'DRAFT' else 'LOCKED' end";
    if ($this->companysetup->linearapproval($config['params'])) {
      $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : $itemfilter;

      if ($userid != 0) {
        $qry = "select s.isapprover as value
                from approversetup as s
                left join approverdetails as d on d.appline=s.line
                left join useraccess as u on u.username=d.approver
                where u.userid=? and s.doc=? ";

        $isapprover = $this->coreFunctions->datareader($qry, [$userid, $doc]);
        if ($isapprover == 1) {
          $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'forapproval';
        }
      }
      $usrfilter = " and num.appuser='" . $config['params']['user'] . "' ";
      $lstatus = " case when head.lockdate is not null then 'LOCKED' when num.postdate is null and head.lockdate is null and num.statid=10 then 'FOR APPROVAL' 
                   when num.postdate is null and num.statid=36 then 'APPROVED' else 'DRAFT' end";
      $lscolor = " case when num.postdate is null and num.statid=36 or num.statid=10 then 'grey' when head.lockdate is not null then 'green' else 'red' end ";
    }

    $leftjoin = "";
    $leftjoin_posted = "";
    switch ($itemfilter) {
      case 'draft':
        $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=0';
        
        break;

      case 'pending':
        $leftjoin = ' left join sostock as stock on stock.trno=head.trno';
        $leftjoin_posted = ' left join hsostock as stock on stock.trno=head.trno';
        $condition .= ' and stock.iss>stock.qa and stock.void=0 and num.postdate is not null ';
        break;

      case 'locked':
        $condition = ' and head.lockdate is not null and num.postdate is null ';
        $ustatus = "'Locked'";
        
        break;

      case 'forapproval':
        $ustatus = "'For Approval'";
        $condition .= " and num.postdate is null and head.lockdate is null and num.statid=10 $usrfilter ";
        break;

      case 'approved':
        $ustatus = "'Approved'";
        $condition .= ' and num.postdate is null and head.lockdate is null and num.statid=36';
        break;

      case 'doneloading':
        $ustatus = "'Done Loading'";
        $condition .= ' and num.postdate is null and head.lockdate is not null and num.statid=39';
        break;

      case 'complete':
        $condition .= ' and num.statid = 7 ';
        break;

      case 'posted':
        $condition .= ' and num.postdate is not null ';
        break;
    }

    $limit = "limit 150";

    // replace multisearch
    if (isset($searchfilter)) {
      if ($searchfilter != '') {
        $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.rem'];

        if ($searchfilter != "") {
          $condition .= $this->othersClass->multisearch($searchfield, $searchfilter);
        }
      }
    }


    $companyid = $config['params']['companyid'];

    $qry = "select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid, 
                  " . $lstatus . " as status,head.createby,head.editby,head.viewby,
                  num.postedby,head.createdate,num.postdate,
                  head.yourref, head.ourref,case ifnull(head.lockdate,'') when '' 
                  then $lscolor else 'green' end as statuscolor,head.shipto,head.terms,head.rem  $lfield
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            $ljoin
            where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " 
            $group
            union all
            select head.trno,head.docno,head.clientname,left(head.dateid,10) as dateid,'POSTED' as status,
                   head.createby,head.editby,head.viewby, num.postedby,head.createdate,num.postdate,
                   head.yourref, head.ourref,'grey' as statuscolor,head.shipto,head.terms,head.rem $gfield
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            $gjoin
            where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . " 
            $group
            $orderby " . $limit;

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

    $buttons['others']['items'] = [
      'first' => ['label' => 'First', 'todo' => ['action' => 'navigation', 'lookupclass' => 'first', 'access' => 'view', 'type' => 'navigation']],
      'prev' => ['label' => 'Previous', 'todo' => ['action' => 'navigation', 'lookupclass' => 'prev', 'access' => 'view', 'type' => 'navigation']],
      'next' => ['label' => 'Next', 'todo' => ['action' => 'navigation', 'lookupclass' => 'next', 'access' => 'view', 'type' => 'navigation']],
      'last' => ['label' => 'Last', 'todo' => ['action' => 'navigation', 'lookupclass' => 'last', 'access' => 'view', 'type' => 'navigation']],
    ];

    if ($this->companysetup->getisshowmanual($config['params'])) {
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'so', 'title' => 'SO_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }


    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    if ($this->companysetup->getistodo($config['params'])) {
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
      $objtodo = $this->tabClass->createtab($tab, []);
      $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];
    }

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

     if ($config['params']['companyid'] == 60) { //transpower      
      $changecode = $this->othersClass->checkAccess($config['params']['user'], 5489);
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
    $fields = ['creditinfo'];
    $col1 = $this->fieldClass->create($fields);
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    $so_btnvoid_access = $this->othersClass->checkAccess($config['params']['user'], 3593);
    $whinfo = $this->othersClass->checkAccess($config['params']['user'], 3889);
    $iskgs = $this->companysetup->getiskgs($config['params']);
    $changedisc = $this->othersClass->checkAccess($config['params']['user'], 4037);
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $allowviewbalance = $this->othersClass->checkAccess($config['params']['user'], 5451); //kinggeorge

    $column = ['action', 'isqty', 'uom', 'kgs', 'weight', 'isamt', 'disc','agentamt', 'ext', 'fstatus', 'wh', 'rem', 'loc', 'qa', 'roqa', 'void', 'ref', 'itemname', 'noprint', 'barcode'];
        $sortcolumn = ['action', 'isqty', 'uom', 'kgs', 'weight', 'isamt', 'disc','agentamt', 'ext', 'fstatus', 'wh', 'rem', 'loc', 'qa', 'roqa', 'void', 'ref', 'itemname', 'noprint', 'barcode'];


    foreach ($column as $key => $value) {
      $$value = $key;
    }

    $headgridbtns = ['itemvoiding', 'viewref', 'viewdiagram'];

     if ($config['params']['companyid'] == 60) { //transpower
        array_push($headgridbtns, 'viewitemstockinfo');
     }

    if ($so_btnvoid_access == 0) {
      unset($headgridbtns[0]);
    }
    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'];

    if ($iskgs) {
      $computefield['kgs'] = 'kgs';
    }

    foreach ($column as $key => $value) {
      $$value = $key;
    }

    foreach ($sortcolumn as $key => $value) {
      $$value = $key;
    }

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ],
      'multiinput1' => ['inputcolumn' => ['col1' => $col1], 'label' => 'CREDIT INFO'],

    ];
    

    $stockbuttons = ['save', 'delete', 'showbalance'];

    if ($this->companysetup->getiseditsortline($config['params'])) {
      array_push($stockbuttons, 'sortline');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['columns'][$disc]['style'] = 'text-align: left; width: 180px;whiteSpace: normal;min-width:180px;max-width:220px;';
    $obj[0]['inventory']['columns'][$kgs]['label'] = 'Selling Kgs';
    if (!$iskgs) {
      $obj[0]['inventory']['columns'][$kgs]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'][$weight]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$roqa]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
    $obj[0]['inventory']['columns'][$noprint]['type'] = 'coldel';

    if ($iscreateversion) {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
    } else {
      $obj[0]['inventory']['columns'][$loc]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$ref]['type'] = 'coldel';
      $obj[0]['inventory']['columns'][$fstatus]['type'] = 'coldel';
    }
    
    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    if (!$access['changeamt']) {
      $obj[0]['inventory']['columns'][$isamt]['readonly'] = true;
      $obj[0]['inventory']['columns'][$disc]['readonly'] = true;
    }
    $obj[0][$this->gridname]['addfieldtotal'] = [['field'=>'agentamt','fields'=>['isqty','*','agentamt'],'label'=>'Total Agent Amount:']];
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];
    $tbuttons = ['additem', 'quickadd', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $override = $this->othersClass->checkAccess($config['params']['user'], 1729);
    $noeditdate = $this->othersClass->checkAccess($config['params']['user'], 4850);
    // col 1
    $fields = ['docno', 'client', 'clientname', 'address'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');

    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'shipto.type', 'ctextarea');

    // col 2
    $fields = [['dateid', 'terms'], 'due', 'dwhname', 'dagentname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'ms_freight.label', 'Other Charges');

    // col 3
    $fields = [['yourref', 'ourref'], ['cur', 'forex'], 'dprojectname'];
    $col3 = $this->fieldClass->create($fields);

    // col 4
    $fields = ['rem'];
   
    if ($this->companysetup->getistodo($config['params'])) array_push($fields, 'donetodo');

    if ($this->companysetup->linearapproval($config['params'])) {
        array_push($fields, 'forapproval', 'doneapproved', 'lblapproved');
    }

    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'rem.required', false);
    
    data_set($col4, 'lblapproved.type', 'label');
    data_set($col4, 'lblapproved.label', 'APPROVED!');
    data_set($col4, 'lblapproved.style', 'font-weight:bold;font-family:Century Gothic;color: green;');
    
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
    $data[0]['yourref'] = '';
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['rem'] = '';
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';
    $data[0]['dagentname'] = '';
    $data[0]['terms'] = '';
    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['address'] = '';
    $data[0]['creditinfo'] = '';
    $data[0]['rem2'] = '';

    $data[0]['wh'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->datareader("select clientname as value from client where client='" . $data[0]['wh'] . "'");
    $data[0]['whname'] = $name;

    $data[0]['projectcode'] = '';
    $data[0]['projectid'] = 0;
    $data[0]['projectname'] = '';
    $data[0]['ms_freight'] = '0.00';
    $data[0]['mlcp_freight'] = '';
    $data[0]['sotype'] = 0;
    $data[0]['isro'] = '0';

    $data[0]['sano'] = '0';
    $data[0]['pono'] = '0';
    $data[0]['sadesc'] = '';
    $data[0]['podesc'] = '';
    $data[0]['salestype'] = '';
    $data[0]['statid'] = '0';

    $data[0]['trnxtype'] = '';
    $data[0]['approvalreason'] = '';

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

  public function openhead($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $tablenum = $this->tablenum;

    $table = $this->head;
    $htable = $this->hhead;
    $info = $this->infohead;
    $hinfo = $this->hinfohead;

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
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address,  head.shipto, 
         date_format(head.createdate,'%Y-%m-%d') as createdate,
         head.createby,
         head.rem,
         ifnull(head.agent, '') as agent, 
         ifnull(agent.clientname, '') as agentname,'' as dagentname,
         head.wh as wh,
         warehouse.clientname as whname,
         '' as dwhname, 
         left(head.due,10) as due, 
         client.groupid,head.creditinfo,
         head.sano, ifnull(sa.sano,'') as sadesc,
         head.pono,ifnull(po.sano,'') as podesc,
         head.projectid,ifnull(project.code,'') as projectcode,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,head.ms_freight,head.mlcp_freight,num.statid as numstatid,cast(ifnull(info.isro,0) as char) as isro, head.sotype, head.salestype,
         head.statid, ifnull(stat.status,'') as statname,ifnull(info.trnxtype,'') as trnxtype,ifnull(info.approvalreason,'') as approvalreason,ifnull(info.rem2,'') as rem2,
             head.phaseid, ps.code as phase,  head.modelid, hm.model as housemodel, head.blklotid, info.driverid, info.checkerid, info.truckid, info.helperid, info.plateno,
            bl.blk as blklot,  bl.lot, amen.line as amenityid, amen.description as amenityname, 
            subamen.line as subamenityid, subamen.description as subamenityname, info.tmpref";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid
        left join $info as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono
        left join trxstatus as stat on stat.line = head.statid 

        left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid
    
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join client as warehouse on warehouse.client = head.wh
        left join client as agent on agent.client = head.agent
        left join projectmasterfile as project on project.line=head.projectid
        left join $hinfo as info on info.trno=head.trno
        left join clientsano as sa on sa.line=head.sano
        left join clientsano as po on po.line=head.pono  
        left join trxstatus as stat on stat.line = head.statid 
         left join phase as ps on ps.line = head.phaseid
        left join housemodel as hm on hm.line = head.modelid
        left join blklot as bl on bl.line = head.blklotid
        left join amenities as amen on amen.line= head.amenityid
        left join subamenities as subamen on subamen.line=head.subamenityid and subamen.amenityid=head.amenityid
        where head.trno = ? and num.center=? ";
    return
      $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
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

    if ($this->companysetup->getistodo($config['params'])) {
      $this->othersClass->checkseendate($config, $tablenum);
    }

    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);

    $head = $this->openhead($config);

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

      // fortesting
      if ($this->companysetup->linearapproval($config['params'])) {
        switch ($head[0]->numstatid) {
          case 10: // forapproval
            $hideobj = ['forapproval' => true, 'doneapproved' => false, 'lblapproved' => true];
            break;
          case 36: // approved
            $hideobj = ['forapproval' => true, 'doneapproved' => true, 'lblapproved' => false];
            break;
          case 0: // draft
            $hideobj = ['forapproval' => false, 'doneapproved' => true, 'lblapproved' => true];
            break;
        }
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
    $companyid = $config['params']['companyid'];
    $head = $config['params']['head'];
    $data = [];
    $info = [];
    $datahere = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], '', $companyid);
        } //end if    
      }
    }

    if ($data['terms'] == '') {
      $data['due'] =  $data['dateid'];
    } else {
      $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['dateid'], $data['terms']);
    }
    //$data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    $info = [];
    $info['trno'] = $head['trno'];


    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $this->othersClass->getcreditinfo($config, $this->head);
    } else {

      if (isset($data['agent'])) {
        $inactive = $this->coreFunctions->getfieldvalue("client", "isinactive", "client=?", [$data['agent']], '', true);
        if ($inactive == 1) {
          $data['agent'] = '';
        }
      }

      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->othersClass->getcreditinfo($config, $this->head);

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
    $this->coreFunctions->execqry('delete from headinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $crlimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);

    if (floatval($crlimit) == 0) {
      $override = $this->othersClass->checkAccess($config['params']['user'], 1729);
      $cashterms = $this->coreFunctions->getfieldvalue($this->head, "terms", "trno=?", [$trno]);

      $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
      $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
      $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
      $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);

      if ($override == '0') {
        if ($cstatus <> 'ACTIVE') {
          $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Customer not active');
          return ['status' => false, 'msg' => 'Posting failed. Due to SO disapproval, transaction cannot be posted. Customer is not active.'];
        }

        if (floatval($crline) < floatval($totalso)) {
          $this->logger->sbcwritelog($trno, $config, 'POST', 'SO Disapproved, Above Credit limit');
          return ['status' => false, 'msg' => 'Posting failed. Due to SO disapproval, transaction cannot be posted. Above credit limit.'];
        }
      }
    }

    if (!$this->othersClass->checktotalext($trno,$this->stock)) {
      $count = $this->coreFunctions->datareader("select count(line) as value from ".$this->stock." where trno =?",[$trno],'',true);
      if($count !=0){
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Zero Amount, Not Allowed to Post.'];
      }      
    }

    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $addfield = "";
    $addfieldfilter = "";
    $addsfield = "";

    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,creditinfo,crline,overdue, projectid,mlcp_freight,ms_freight,sano,pono,statid,
       phaseid,modelid,blklotid,amenityid,subamenityid " . $addfield . ")
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.creditinfo,head.crline,head.overdue, head.projectid, 
      head.mlcp_freight,head.ms_freight,head.sano,head.pono,head.statid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid" . $addfieldfilter . "
      FROM " . $this->head . " as head left join cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock

      if (!$this->othersClass->postingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,expiry,disc,iss,void,isamt,amt,isqty,ext,kgs,
        encodeddate,encodedby,editdate,editby,refx,linex,rem,ref,weight,weight2,projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint,agentamt" . $addsfield . ")
        SELECT trno, line, itemid, uom,whid,loc,expiry,disc, iss,void,isamt,amt, isqty, ext,kgs,
        encodeddate, encodedby,editdate,editby,refx,linex,rem,ref,weight,weight2,projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint,agentamt " . $addsfield . " FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
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
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }


    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $addfield = "";
    $addfieldfilter = "";
    $addsfield = "";

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,creditinfo,crline,overdue,agent, projectid,mlcp_freight,ms_freight,sano,pono,statid,
    phaseid,modelid,blklotid,amenityid,subamenityid" . $addfield . ")
    select head.trno, head.doc, head.docno, client.client, head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.creditinfo,head.crline,head.overdue,head.agent,
    head.projectid,head.mlcp_freight,head.ms_freight,head.sano,head.pono,head.statid,head.phaseid,head.modelid,head.blklotid,head.amenityid,head.subamenityid " . $addfieldfilter . "
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {

      if (!$this->othersClass->unpostingheadinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,expiry,disc,
      amt,iss,void,isamt,isqty,ext,kgs,rem,encodeddate,encodedby,editdate,editby,refx,linex,ref,weight,weight2, projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint,agentamt " . $addsfield . ")
      select trno, line, itemid, uom,whid,loc,expiry,disc,amt, iss,void, isamt, isqty,
      ext,kgs,ifnull(rem,''), encodeddate,encodedby, editdate, editby,refx,linex,ref,weight,weight2, projectid,phaseid,modelid,blklotid,amenityid,subamenityid,noprint,agentamt" . $addsfield . "
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        if ($companyid != 39) { //not cbbsi
          $this->coreFunctions->execqry("update headinfotrans set approvalreason ='' where trno=?", 'update', [$trno]);
        }
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  public function getposummaryqry($config)
  {
    return "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.itemid,item.itemname,head.docno,left(head.dateid,10) as dateid,item.barcode,
              FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
              FORMAT(stock.iss," . $this->companysetup->getdecimal('qty', $config['params']) . ") as iss,
              FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,stock.disc,
              FORMAT(stock.amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
              FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,wh.client as wh,
              FORMAT((stock.qa / case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,head.client, head.clientname, 
              client.addr as address, client.terms, head.rem, 'P' as cur, 1 as forex,head.ourref,stock.uom,
              FORMAT(((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as pending,stock.loc,head.yourref,head.agent
              from hqthead as head
              right join hqtstock as stock on stock.trno = head.trno
              left join item on item.itemid=stock.itemid
              left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
              left join transnum on transnum.trno = head.trno
              left join client on client.client = head.client
              left join client as wh on wh.clientid=stock.whid
              where head.trno = ? and stock.iss>stock.qa
              and stock.void = 0 ";
  }

  private function getstockselect($config)
  {
    $companyid = $config['params']['companyid'];
    $itemname = 'item.itemname,';
    $itemdesc = '';

    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    item.itemid,
    stock.trno, 
    stock.line,
    stock.sortline,
    item.barcode, 
    $itemname
    stock.uom, 
    stock.kgs,
    stock.iss,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ") as isamt,
    FORMAT(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as isqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext, 
    left(stock.encodeddate,10) as encodeddate,
    stock.disc, 
    case when stock.void=0 then 'false' else 'true' end as void,
    round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    round((stock.iss-stock.roqa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as roqa
    " . $itemdesc . ",
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,stock.expiry,
    item.brand,
    stock.rem, stock.refx,stock.linex,stock.ref,
    ifnull(uom.factor,1) as uomfactor,stock.weight,stock.fstatus,stock.agentamt,

    stock.phaseid, ps.code as phasename,  stock.modelid, hm.model as housemodel,stock.blklotid, bl.blk, bl.lot,
    stock.projectid, proj.code as project,
     amen.line as amenity, amen.description as amenityname,  subamen.line as subamenity, subamen.description as subamenityname,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    case when stock.noprint=0 then 'false' else 'true' end as noprint";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $companyid = $config['params']['companyid'];

    $leftjoin = '';
    $hleftjoin = '';
    
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom left join client as warehouse on warehouse.clientid=stock.whid 
    
    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    
    $leftjoin
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 

    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    $hleftjoin
    where stock.trno =? order by sortline,line";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function openstockline($config)
  {
    $companyid = $config['params']['companyid'];

    $leftjoin = '';

    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . "  
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join phase as ps on ps.line = stock.phaseid
    left join housemodel as hm on hm.line = stock.modelid
    left join projectmasterfile as proj on proj.line = stock.projectid
    left join blklot as bl on bl.line = stock.blklotid
    left join amenities as amen on amen.line= stock.amenityid
    left join subamenities as subamen on subamen.line=stock.subamenityid and subamen.amenityid=stock.amenityid
    $leftjoin
    where stock.trno = ? and  stock.line = ?  ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'createversion':
        $return = $this->posttrans($config);
        if ($return['status']) {
          return $this->othersClass->createversion($config);
        } else {
          return $return;
        }
        break;
      case 'additem':
        $return =  $this->additem('insert', $config);
        if ($return['status'] == true) {
          $this->othersClass->getcreditinfo($config, $this->head);
        }
        return $return;
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
        break;
      case 'quickadd':
        return $this->quickadd($config);
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
      case 'getqtdetails':
        return $this->getqtdetails($config);
        break;
      case 'getqtsummary':
        return $this->getqtsummary($config);
        break;
      case 'geteggitems':
        return $this->geteggitems($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    $tablenum = $this->tablenum;
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;
      case 'donetodo':
        return $this->othersClass->donetodo($config, $tablenum);
        break;
      case 'uploadexcel':
        return $this->uploadexcel($config);
        break;
      case 'forapproval':
        if ($this->companysetup->linearapproval($config['params'])) {
          return $this->othersClass->forapproval($config, $tablenum);
        } else {
          return $this->forapproval($config);
        }
        break;
      case 'doneapproved':
        if ($this->companysetup->linearapproval($config['params'])) {
          return $this->othersClass->approvedsetup($config, $tablenum);
        } else {
          return $this->doneapproved($config);
        }
        break;
      case 'duplicatedoc':
        if (!isset($config['params']['row']['trno'])) {
          $config['params']['row']['trno'] = $config['params']['trno'];
        }
        return $this->othersClass->duplicateTransaction($config);
        break;
      case 'downloadexcel':
        return $this->othersClass->downloadexcel($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function doneapproved($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 36], ['trno' => $config['params']['trno']])) {
      $this->coreFunctions->sbcupdate($this->head, ['lockuser' => $config['params']['user'], 'lockdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $config['params']['trno']]);
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'APPROVED!');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag approved'];
    }
  }

  public function forapproval($config)
  {
    $posted = $this->othersClass->isposted($config);
    if ($posted) {
      return ['status' => false, 'msg' => 'Already posted'];
    }

    if ($this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 10], ['trno' => $config['params']['trno']])) {
      $this->logger->sbcwritelog($config['params']['trno'], $config, 'HEAD', 'Tag FOR APPROVAL');
      return ['status' => true, 'msg' => 'Successfully updated', 'backlisting' => true];
    } else {
      return ['status' => false, 'msg' => 'Failed to tag for approval'];
    }
  }

  public function uploadexcel($config)
  {
    $rawdata = $config['params']['data'];
    $companyid = $config['params']['companyid'];
    $trno = $config['params']['dataparams']['trno'];
    $msg = '';
    $status = true;
    $companylist = [56]; // cdohris
    if ($trno == 0) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Kindly create the document number first.'];
    }

    foreach ($rawdata as $key => $value) {
      try {
        if (isset($rawdata[$key]['itemcode'])) {
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode = '" . $rawdata[$key]['itemcode'] . "'");
          if ($itemid == '') {
            $status = false;
            $msg .= 'Failed to upload. ' . $rawdata[$key]['itemcode'] . ' doesn`t exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Item code is required. ';
          continue;
        }

        if (in_array($companyid, $companylist)) {
          $config['params']['barcode'] = $rawdata[$key]['itemcode'];
          $config['params']['client'] = '';
          goto checking;
        }

        if (isset($rawdata[$key]['driver'])) {
          $driverid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['driver'] . "'");
          if ($driverid == '') {
            $status = false;
            $msg .= 'Failed to upload. Driver ' . $rawdata[$key]['driver'] . ' doesn`t exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Driver is required. ';
          continue;
        }

        if (isset($rawdata[$key]['helper'])) {
          $helperid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['helper'] . "'");
          if ($helperid == '') {
            $status = false;
            $msg .= 'Failed to upload. Helper ' . $rawdata[$key]['helper'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Helper is required. ';
          continue;
        }

        if (isset($rawdata[$key]['truck'])) {
          $truckid = $this->coreFunctions->getfieldvalue("client", "clientid", "client = '" . $rawdata[$key]['truck'] . "'");
          if ($truckid == '') {
            $status = false;
            $msg .= 'Failed to upload. Helter ' . $rawdata[$key]['truck'] . ' does not exist. ';
            continue;
          }
        } else {
          $msg .= 'Failed to upload. Truck is required. ';
          continue;
        }

        $others = [
          'driverid' => $driverid,
          'helperid' => $helperid,
          'truckid' => $truckid,
          'plateno' => $rawdata[$key]['plateno'],
          'isro' => 1
        ];
        $this->coreFunctions->sbcupdate('headinfotrans', $others, ['trno' => $trno]);
        checking:
        $config['params']['trno'] = $trno;
        $config['params']['data']['ref'] = isset($rawdata[$key]['ref']);
        $config['params']['data']['uom'] = $rawdata[$key]['uom'];
        $config['params']['data']['itemid'] = $itemid;
        $config['params']['data']['qty'] = $rawdata[$key]['qty'];
        $config['params']['data']['wh'] =  $this->coreFunctions->getfieldvalue($this->head, "wh", "trno = ?", [$trno]);
        $config['params']['data']['amt'] = isset($rawdata[$key]['cost']) ? $rawdata[$key]['cost'] : $rawdata[$key]['amt'];
        $config['params']['data']['loc'] = isset($rawdata[$key]['location']) ? $rawdata[$key]['location'] : "";
        $config['params']['data']['disc'] = isset($rawdata[$key]['disc']) ? $rawdata[$key]['disc'] : "";

        if (isset($rawdata[$key]['kgs'])) {
          $config['params']['data']['kgs'] = $rawdata[$key]['kgs'];
        }
        if (isset($rawdata[$key]['weight'])) {
          $config['params']['data']['weight'] = $rawdata[$key]['weight'];
        }

        $return = $this->additem('insert', $config);
        if (!$return['status']) {
          $status = false;
          $msg .= 'Failed to upload. ' . $return['msg'];
          goto exithere;
        }
      } catch (Exception $e) {
        $status = false;
        $msg .= 'Failed to upload. Exception error ' . $e->getMessage();
        goto exithere;
      }
    }

    exithere:
    if ($msg == '') {
      $this->logger->sbcwritelog($trno, $config, 'IMPORT', 'UPLOAD EXCEL FILE');
      $msg = 'Successfully uploaded.';
    }

    if (!$status) {
      $this->coreFunctions->execqry("delete from lastock where trno=" . $trno);
    }

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'reloadhead' => true];
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
     CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hsohead as so 
     left join hsostock as s on s.trno = so.trno
     where so.trno = ? 
     group by so.trno,so.docno,so.dateid";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //SO            
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 200,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'sj']);
        $a = $a + 100;
      }
    }

    //SJ
    $qry = "
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem, 
    head.trno
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    left join arledger as ar on ar.trno = head.trno
    where stock.refx=? and head.doc = 'SJ'
    group by head.docno, head.dateid, head.trno, ar.bal
    union all 
    select head.docno,
    date(head.dateid) as dateid,
    CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(sum(stock.ext),2)) as CHAR) as rem, 
    head.trno
    from lahead as head
    left join lastock as stock on head.trno = stock.trno
    where stock.refx=? and head.doc = 'SJ'
    group by head.docno, head.dateid, head.trno";
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

      foreach ($t as $key => $value) {
        //CR
        $rrtrno = $t[$key]->trno;
        $apvqry = "
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'
        union all
        select  head.docno, date(head.dateid) as dateid, head.trno,
        CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        where detail.refx = ? and head.doc = 'CR'";
        $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
        if (!empty($apvdata)) {
          foreach ($apvdata as $key2 => $value2) {
            data_set(
              $nodes,
              'cr',
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 100,
                'w' => 250,
                'h' => 80,
                'type' => $apvdata[$key2]->docno,
                'label' => $apvdata[$key2]->rem,
                'color' => 'red',
                'details' => [$apvdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => 'cr']);
            $a = $a + 100;
          }
        }

        //CM
        $dmqry = "
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from glhead as head
        left join glstock as stock on stock.trno=head.trno 
        left join item on item.itemid = stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid
        union all
        select head.docno as docno,left(head.dateid,10) as dateid,
        CAST(concat('Total CM Amt: ', round(sum(stock.ext), 2)) as CHAR) as rem 
        from lahead as head
        left join lastock as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where stock.refx=? and head.doc = 'CM'
        group by head.docno, head.dateid";
        $dmdata = $this->coreFunctions->opentable($dmqry, [$rrtrno, $rrtrno]);
        if (!empty($dmdata)) {
          foreach ($dmdata as $key2 => $value2) {
            data_set(
              $nodes,
              $dmdata[$key2]->docno,
              [
                'align' => 'left',
                'x' => $startx + 400,
                'y' => 200,
                'w' => 250,
                'h' => 80,
                'type' => $dmdata[$key2]->docno,
                'label' => $dmdata[$key2]->rem,
                'color' => 'red',
                'details' => [$dmdata[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => 'sj', 'to' => $dmdata[$key2]->docno]);
            $a = $a + 100;
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function updateperitem($config)
  {
    $config['params']['data'] = $config['params']['row'];
    $this->additem('update', $config);
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  }


  public function updateitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('update', $config);
    }
    $this->othersClass->getcreditinfo($config, $this->head);
    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
  } //end function

  public function addallitem($config)
  {
    $msg = '';
    $status = true;
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result  = $this->additem('insert', $config);
      if (!$result['status']) {
        $msg .= ' ' . $result['msg'];
        $status = false;
      }
    }

    $data = $this->openstock($config['params']['trno'], $config);
    if ($msg == '') {
      $msg  = 'Successfully saved.';
    }
    return ['inventory' => $data, 'status' => $status, 'msg' => $msg];
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

    $item = $this->coreFunctions->opentable("select item.itemid,item.amt,item.disc,'' as loc,'" . $wh . "' as wh, 1 as qty, uom from item where barcode=?", [$barcode]);
    $item = json_decode(json_encode($item), true);

    if (!empty($item)) {
      $config['params']['barcode'] = $barcode;
      $lprice = $this->getlatestprice($config);
      $lprice = json_decode(json_encode($lprice), true);
      if (!empty($lprice['data'])) {
        $item[0]['amt'] = $lprice['data'][0]['amt'];
        $item[0]['disc'] = $lprice['data'][0]['disc'];
      }

      $config['params']['data'] = $item[0];
      return $this->additem('insert', $config);
    } else {
      return ['status' => false, 'msg' => 'Barcode not found.', ''];
    }
  }

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
    // var_dump($config['params']);
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = isset($config['params']['data']['disc']) ? $config['params']['data']['disc'] : "";
    $wh = isset($config['params']['data']['wh']) ? $config['params']['data']['wh'] : "";
    $loc = isset($config['params']['data']['loc']) ? $config['params']['data']['loc'] : "";
    $void = 'false';
    $rem = '';
    $ref = '';
    $expiry = '';
    $refx = 0;
    $linex = 0;
    $noprint = 'false';
    $agentamt  = 0;

    if ($this->companysetup->getiskgs($config['params'])) {
      $kgs = isset($config['params']['data']['kgs']) ? $config['params']['data']['kgs'] : 1;
    } else {
      $kgs = 0;
    }

    $weight = isset($config['params']['data']['weight']) ? $config['params']['data']['weight'] : 0;
    $itemdesc = isset($config['params']['data']['itemdesc']) ? $config['params']['data']['itemdesc'] : '';

    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }

    if (isset($config['params']['data']['agentamt'])) {
        $agentamt = $config['params']['data']['agentamt'];
      }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
    }

    if (isset($config['params']['data']['expiry'])) {
      $expiry = $config['params']['data']['expiry'];
    }
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    if (isset($config['params']['data']['noprint'])) {
      $noprint = $config['params']['data']['noprint'];
    }

    $fstatus = '';

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
    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $kgs = $this->othersClass->sanitizekeyfield('qty', $kgs);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,tqty from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      if ($companyid == 19) { //housegem
        $weight = $item[0]->tqty;
      }
    }

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $qty = round($qty, $this->companysetup->getdecimal('qty', $config['params']));
    
    if ($companyid == 60) { //TRANSPOWER
      if($disc!=""){
        $discper ="";
        if (!str_contains($disc, '%')) {
          $d = explode("/",$disc);
          foreach ($d as $k => $x) {
            if($discper !=""){
              $discper .="/";
            }

            $discper .= $x.'%';
            
          }
          $disc = $discper;
        }
      }     
    }
    
    if ($this->companysetup->getisdiscperqty($config['params'])) {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', $kgs, 0, 1);
    } else {
      $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, 'P', $kgs);
    }

    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);

    if (floatval($forex) == 0) {
      $forex = 1;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isamt' => $amt,
      'amt' => number_format($computedata['amt'] * $forex, $this->companysetup->getdecimal('price', $config['params']), '.', ''),
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'ext' => number_format($computedata['ext'], $this->companysetup->getdecimal('currency', $config['params']), '.', ''),
      'kgs' => $kgs,
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'void' => $void,
      'uom' => $uom,
      'rem' => $rem,
      'refx' => $refx,
      'linex' => $linex,
      'expiry' => $expiry,
      'ref' => $ref,
      'weight' => $weight,
      'fstatus' => $fstatus,
      'noprint' => $noprint,
      'agentamt' => $agentamt
    ];
  

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];
    if ($action == 'insert') {
      $msg = 'Item was successfully added.';
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];

      $errormsg = '';
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            $stockinfo_data = [];
            $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            break;
        }

        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:' . $computedata['ext'] . ' uom:' . $uom, $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        $this->othersClass->getcreditinfo($config, $this->head);
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") SO Qty is Greater than Quotation Qty.";
        }
        return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
      } else {
        $errormsg = $this->coreFunctions->errmsg;
        return ['status' => false, 'msg' => 'Add item Failed for ' . $item[0]->barcode . ' - ' . $errormsg];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';

      // $this->coreFunctions->execqry("update " . $this->stock . " set itemid='" . $data['itemid'] . "', isamt='" . $data['isamt'] . "', amt='" . $data['amt'] . "', isqty='" . $data['isqty'] . "', iss='" . $data['iss'] . "', ext='" . $data['ext'] . "', kgs='" . $data['kgs'] . "', disc='" . $data['disc'] . "', whid='" . $data['whid'] . "', loc='" . $data['loc'] . "', void='" . $data['void'] . "', uom='" . $data['uom'] . "', rem='" . $data['rem'] . "', refx='" . $data['refx'] . "', linex='" . $data['linex'] . "', expiry='" . $data['expiry'] . "', ref='" . $data['ref'] . "', fstatus='" . $data['fstatus'] . "',noprint='" . $data['noprint'] . "' where trno='" . $data['trno'] . "' and line='" . $data['line'] . "'", 'update');
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      

      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $return = false;
        $msg = "(" . $item[0]->barcode . ") SO Qty is Greater than Quotation Qty.";
      }
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function



  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0 ', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);
    $this->coreFunctions->sbcupdate("headinfotrans", ['isro' => 0], ['trno' => $trno]);

    foreach ($data as $key => $value) {
      if (floatval($data[$key]->refx) != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
    }
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }


  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);
    if (floatval($data[0]->refx) !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty . ' Amt:' . $data[0]->isamt . ' Disc:' . $data[0]->disc . ' wh:' . $data[0]->wh . ' ext:' . $data[0]->ext);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $companyid = $config['params']['companyid'];

    $pricetype = $this->companysetup->getpricetype($config['params']);
    $pricegrp = '';
    $data = [];
    $data2 = $this->coreFunctions->opentable("select 'Invoice Price' as pricegrp, amt5 as amt,disc5 as disc,namt5 as netamt from item where barcode =?
    union all 
    select 'DR Price' as pricegrp,amt7 as amt,disc7 as disc,namt7 as netamt from item where barcode =?
    union all
    select 'Wholesale Price' as pricegrp, amt2 as amt ,disc2 as disc, namt2 as netamt from item where barcode =?
    union all
    select 'Base Price' as pricegrp,amt, disc, namt as netamt  from item where barcode =?",[$barcode,$barcode,$barcode,$barcode]);

    $pricedec = $this->companysetup->getdecimal('price', $config['params']);

    switch ($pricetype) {
      case 'Stockcard':
        goto itempricehere;
        break;

      case 'CustomerGroup':
      case 'CustomerGroupLatest':
        $pricegrp = $this->coreFunctions->getfieldvalue("client", "class", "client=?", [$client]);
        if ($pricegrp != '') {
            $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
            $data = $this->coreFunctions->opentable("select '" . $pricefield['label'] . "' as docno, left(now(),10) as dateid, n" . $pricefield['amt'] . " as amt,n" . $pricefield['amt'] . " as defamt, '' as disc, uom, itemid,
            '' as status,'' as clientname,'' as yourref,'' as ourref,'' as rem,0 as agentamt,0 as qty from item where barcode=?
            union all
          select docno,left(dateid,10) as dateid,round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as amt,
            round(amt," . $this->companysetup->getdecimal('price', $config['params']) . ") as defamt,disc,uom,itemid,status,clientname,yourref,ourref,rem,format(agentamt,2) as agentamt,format(qty,2) as qty
            
            from(select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc,stock.itemid,
            '' as status,head.clientname, head.yourref,head.ourref,head.rem,stock.agentamt,stock.isqty as qty
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'SJ' 
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            UNION ALL
            select head.docno,head.dateid,stock.isamt as amt,
            stock.uom,stock.disc,stock.itemid,'Posted' as status,head.clientname,
            head.yourref,head.ourref,head.rem,stock.agentamt,stock.isqty as qty from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno
            where head.doc = 'SJ' 
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0 and cntnum.trno <> ?
            order by dateid desc limit 10) as tbl order by dateid desc", [$barcode,$barcode,$client,$trno,$barcode,$client,$trno]);
            if (!empty($data)) {
              if ($companyid == 60) { //transpower - round to whole
                $data[0]->amt =  round($data[0]->amt,0);
                $data[0]->defamt =  round($data[0]->defamt,0);
              }
              goto setpricehere;
            }
        } else {
          if ($pricetype == 'CustomerGroupLatest') {
            goto getCustomerLatestPriceHere;
          } else {
            goto itempricehere;
          }
        }
        break;

      default:
        getCustomerLatestPriceHere:
        $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,round(amt,2) as defamt,disc,uom from(
            select '' as docno,left(now(),10) as dateid,round(namt5,2) as amt,'' as disc,uom
            from item where barcode = ?
            union all
            select head.docno,head.dateid,
            stock.isamt as amt,stock.uom,stock.disc
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join cntnum on cntnum.trno=head.trno
            left join item on item.itemid = stock.itemid
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and head.client = ?
            and stock.isamt <> 0
            UNION ALL
            select head.docno,head.dateid,stock.isamt as amt,
            stock.uom,stock.disc from glhead as head
            left join glstock as stock on stock.trno = head.trno
            left join item on item.itemid = stock.itemid
            left join client on client.clientid = head.clientid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc = 'SJ' and cntnum.center = ?
            and item.barcode = ? and client.client = ?
            and stock.isamt <> 0
            order by dateid desc limit 10) as tbl order by dateid desc ";
            $data = $this->coreFunctions->opentable($qry, [$barcode,$center, $barcode, $client, $center, $barcode, $client]);
        
        break;
    }

    $defaultsameuom = " and uom.issalesdef=1"; //kinggeorge
    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
        itempricehere:
        $qry = "select 'Retail Price' as docno, amt,amt as defamt,disc,uom from item where barcode=?";
        $data = $this->coreFunctions->opentable($qry, [$barcode]);

      setpricehere:
      $usdprice = 0;
      $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
      $cur = $this->coreFunctions->getfieldvalue($this->head, 'cur', 'trno=?', [$trno]);
      $dollarrate = $this->coreFunctions->getfieldvalue('forex_masterfile', 'dollartocur', 'cur=?', [$cur]);
      $defuom = '';

      if ($this->companysetup->getisdefaultuominout($config['params'])) {
        $data[0]->docno = 'UOM';
        $defuom = $this->coreFunctions->datareader("select ifnull(uom.uom,'') as value from item left join uom on uom.itemid=item.itemid and uom.isdefault2 = 1 where item.barcode=?", [$barcode]);
        if ($defuom != "") {
          $data[0]->uom = $defuom;
          if ($this->companysetup->getisrecalcamtchangeuom($config['params'])) {
            if ($data[0]->amt != 0) {
              $data[0]->amt = $data[0]->amt * ($this->coreFunctions->datareader("select uom.factor as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]));
            } else {
              $data[0]->amt = $this->coreFunctions->datareader("select (item.amt*ifnull(uom.factor,1)) as value from item left join uom on uom.itemid=item.itemid and uom.uom = '" . $defuom . "' where item.barcode=?", [$barcode]);
            }
          }
        }
      } else {
        if ($this->companysetup->getisuomamt($config['params'])) {
          $pricefield = $this->othersClass->getamtfieldbygrp($pricegrp);
          $data[0]->docno = 'UOM';
          $data[0]->amt = $this->coreFunctions->datareader("select ifnull(uom." . $pricefield['amt'] . ",0) as value from item left join uom on uom.itemid=item.itemid and uom.uom=item.uom where item.barcode=?", [$barcode]);
        }
      }

      if (floatval($forex) <> 1) {
        $usdprice = $this->coreFunctions->getfieldvalue('item', 'foramt', 'barcode=?', [$barcode]);
        if ($cur == '$') {
          $data[0]->amt = $usdprice;
        } else {
          $data[0]->amt = round($usdprice * $dollarrate, 2);
        }
      }

      if (floatval($data[0]->amt) == 0) {
        return ['status' => true, 'msg' => 'No Latest price found...', 'data' => $data,'pricelevel'=>$data2];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data,'pricelevel'=>$data2];
      }
    }
   
  } // end function

  public function getqtsummary($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function

  public function getqtdetails($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
        select head.docno, item.itemid,stock.trno, 
        stock.line, item.barcode,stock.uom, stock.amt,
        (stock.iss-stock.qa) as iss,stock.isamt,
        round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty, 
        stock.disc,stock.loc,stock.expiry
        FROM hqthead as head left join hqtstock as stock on stock.trno=head.trno left join item on item.itemid=
        stock.itemid left join uom on uom.itemid=item.itemid and 
        uom.uom=stock.uom where stock.trno = ? and stock.line=? and stock.iss>stock.qa and stock.void=0
    ";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['expiry'] = $data[$key2]->expiry;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['amt'] = $data[$key2]->isamt;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => $msg];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => $msg];
  } //end function


  public function geteggitemsqry($config, $itemid)
  {
    return "select i.itemid,i.barcode,i.itemname,i.uom,i.disc
            from item as i
            left join itemcategory as cat on i.category= cat.line
            where cat.name = 'Egg' and i.itemid = " . $itemid . " ";
  }

  public function geteggitems($config)
  {
    $trno = $config['params']['trno'];
    $wh = $config['params']['wh'];
    $companyid = $config['params']['companyid'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $itemid = $config['params']['rows'][$key]['itemid'];
      $qry = $this->geteggitemsqry($config, $itemid);
      $data = $this->coreFunctions->opentable($qry);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['trno'] = $trno;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['qty'] = '';
          $config['params']['data']['wh'] = $wh;
          $config['params']['data']['loc'] = '';
          $config['params']['data']['expiry'] = '';
          $config['params']['data']['rem'] = '';
          $config['params']['data']['ref'] = '';
          $config['params']['data']['amt'] = '';
          $config['params']['data']['stageid'] = '';

          $return = $this->additem('insert', $config);
          if ($return['status']) {
            $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
            $line = $return['row'][0]->line;
            $config['params']['trno'] = $trno;
            $config['params']['line'] = $line;
            $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
            $row = $this->openstockline($config);
            $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='SO' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    return $this->coreFunctions->execqry("update hqtstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  public function sbcscript($config){
    return $this->sbcscript->loaditembal($config);  
  }

  // reports 

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $this->posttrans($config);
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false, 'reloadhead' => true];
  }


  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $this->logger->sbcviewreportlog($config);
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
