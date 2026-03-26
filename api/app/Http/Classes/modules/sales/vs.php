<?php

namespace App\Http\Classes\modules\sales;

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

class vs
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'VOID SERVICE SALES ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => true, 'show' => true, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'vshead';
  public $hhead = 'hvshead';
  public $stock = 'vsstock';
  public $hstock = 'hvsstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $fields = ['trno', 'docno', 'dateid', 'client', 'clientname', 'yourref', 'ourref', 'rem', 'newpo'];
  public $except = ['trno', 'dateid'];
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
      'view' => 2781,
      'edit' => 2782,
      'new' => 2783,
      'save' => 2784,
      // 'change' => 2785, remove change doc
      'delete' => 2786,
      'print' => 2787,
      'lock' => 2788,
      'unlock' => 2789,
      'changeamt' => 2790,
      'crlimit' => 2791,
      'post' => 2792,
      'unpost' => 2793,
      'additem' => 2794,
      'edititem' => 2795,
      'deleteitem' => 2796
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

    $getcols = ['action', 'liststatus', 'listdocument', 'listdate', 'listclientname', 'yourref', 'ourref', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$listclientname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$ourref]['label'] = 'Control #';
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $fields = ['selectprefix', 'docno'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'docno.type', 'input');
        data_set($col1, 'docno.label', 'Search');
        data_set($col1, 'selectprefix.label', 'Search by');
        data_set($col1, 'selectprefix.type', 'lookup');
        data_set($col1, 'selectprefix.lookupclass', 'lookupsearchby');
        data_set($col1, 'selectprefix.action', 'lookupsearchby');
        $data = $this->coreFunctions->opentable("select '' as docno,'' as selectprefix");
        return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
        break;
      default:
        return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
        break;
    }
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

    $join = '';
    $hjoin = '';
    $addparams = '';

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
        break;
      default:
        $dateid = "left(head.dateid,10) as dateid";
        if ($searchfilter == "") $limit = 'limit 150';
        break;
    }

    if (isset($config['params']['doclistingparam'])) {
      $test = $config['params']['doclistingparam'];
      if (isset($test['selectprefix'])) {
        if ($test['selectprefix'] != "") {
          if ($test['docno'] != '') {
            switch ($test['selectprefix']) {
              case 'Item Code':
                $addparams = " and (item.partno like '%" . $test['docno'] . "%' or item2.partno like '%" . $test['docno'] . "%')";
                break;
              case 'Item Name':
                $addparams = " and (item.itemname like '%" . $test['docno'] . "%' or item2.itemname like '%" . $test['docno'] . "%')";
                break;
              case 'Model':
                $addparams = " and (model.model_name like '%" . $test['docno'] . "%' or model2.model_name like '%" . $test['docno'] . "%')";
                break;
              case 'Brand':
                $addparams = " and (brand.brand_desc like '%" . $test['docno'] . "%' or brand2.brand_desc like '%" . $test['docno'] . "%')";
                break;
              case 'Item Group':
                $addparams = " and (p.name like '%" . $test['docno'] . "%' or p2.name like '%" . $test['docno'] . "%')";
                break;
            }
          }

          if (isset($test)) {
            $join = " left join vsstock on vsstock.trno = head.trno
            left join item on item.itemid = vsstock.itemid left join item as item2 on item2.itemid = vsstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model 
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";

            $hjoin = " left join hvsstock on hvsstock.trno = head.trno
            left join item on item.itemid = hvsstock.itemid left join item as item2 on item2.itemid = hvsstock.itemid
            left join model_masterfile as model on model.model_id = item.model 
            left join model_masterfile as model2 on model2.model_id = item2.model
            left join frontend_ebrands as brand on brand.brandid = item.brand 
            left join frontend_ebrands as brand2 on brand2.brandid = item2.brand
            left join projectmasterfile as p on p.line = item.projectid 
            left join projectmasterfile as p2 on p2.line = item2.projectid ";
            $limit = '';
          }
        }
      }
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.createby', 'head.editby', 'head.viewby', 'num.postedby', 'head.yourref', 'head.ourref'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    } else {
      $limit = 'limit 25';
    }

    $qry = "select head.trno,head.docno,head.clientname,$dateid, 'DRAFT' as status,
    head.createby,head.editby,head.viewby,num.postedby,
     head.yourref, head.ourref  
     from " . $this->head . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno 
     " . $join . "
      where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     union all
     select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,
     head.createby,head.editby,head.viewby, num.postedby,
      head.yourref, head.ourref  
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num 
     on num.trno=head.trno 
     " . $hjoin . "
      where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " $filtersearch
     order by dateid desc,docno desc $limit";

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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => $config['params']['doc'], 'title' => strtoupper($this->modulename) . '_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }

    return $buttons;
  } // createHeadbutton


  public function createtab2($access, $config)
  {
    $companyid = $config['params']['companyid'];

    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];
      $instructiontab = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewinstructiontab']];
      $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrysqcomment', 'label' => 'Comments']];
      $comments = $this->tabClass->createtab($tab, []);
      $return['COMMENTS'] = ['icon' => 'fa fa-comment', 'tab' => $comments];

      $return['DEFAULT SHIPPING/BILLING ADDRESS'] = ['icon' => 'fa fa-map-marker-alt', 'customform' => $billshipdefault];
      $return['INSTRUCTION'] = ['icon' => 'fa fa-info', 'customform' => $instructiontab];
    }

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];
    return $return;
  }

  public function createTab($access, $config)
  {
    $companyid = $config['params']['companyid'];
    $iscreateversion = $this->companysetup->getiscreateversion($config['params']);
    $action = 0;
    $itemdesc = 1;
    $isqty = 2;
    $uom = 3;
    $isamt = 4;
    $ext = 5;
    $ref = 6;
    $itemname = 7;
    $barcode = 8;

    $column = ['action', 'itemdescription', 'isqty', 'uom', 'isamt', 'ext', 'ref', 'itemname', 'barcode'];
    $sortcolumn = ['action', 'itemdescription', 'isqty', 'uom', 'isamt', 'ext', 'ref', 'itemname', 'barcode'];

    $headgridbtns = [];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column, 'sortcolumns' => $sortcolumn,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt,  'total' => 'ext'],
        'headgridbtns' => $headgridbtns
      ],
    ];

    $stockbuttons = ['save', 'delete', 'showbalance'];
    switch ($this->companysetup->getsystemtype($config['params'])) {
      case 'AIMS':
        if ($companyid == 0 || $companyid == 10) { //main, afti
          array_push($stockbuttons, 'iteminfo');
        }
        break;
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0]['inventory']['columns'][$action]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$isqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$uom]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$ref]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0]['inventory']['columns'][$itemname]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0]['inventory']['columns'][$barcode]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0]['inventory']['columns'][$isamt]['type'] = 'label';

    $obj[0]['inventory']['columns'][$barcode]['type'] = 'hidden';
    $obj[0]['inventory']['columns'][$barcode]['label'] = '';

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $obj[0]['inventory']['descriptionrow'] = [];
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'textarea';
      $obj[0]['inventory']['columns'][$itemdesc]['readonly'] = true;
      $obj[0]['inventory']['columns'][$itemdesc]['style'] = 'text-align: left; width: 350px;whiteSpace: normal;min-width:350px;max-width:350px;';
    } else {
      $obj[0]['inventory']['columns'][$itemdesc]['type'] = 'coldel';
    }

    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $companyid = $config['params']['companyid'];

    $tbuttons = ['saveitem', 'deleteallitem', 'pendingsq'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($companyid == 10) { //afti
      $obj[2]['lookupclass'] = 'pendingssdetail';
      $obj[2]['action'] = 'pendingssdetail';
    }
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['docno', 'client', 'clientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.lookupclass', 'customer');
    data_set($col1, 'docno.label', 'Transaction#');

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col1, 'clientname.type', 'textarea');
      data_set($col1, 'businesstype.type', 'textarea');
    }

    $fields = ['dateid', ['deldate', 'podate'], 'yourref', 'ourref', 'newpo'];
    $col2 = $this->fieldClass->create($fields);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      data_set($col2, 'yourref.label', 'Customer PO');
      data_set($col2, 'ourref.label', 'Control #');
    } else {
      data_set($col2, 'yourref.label', 'PO#');
    }
    data_set($col2, 'yourref.type', 'lookup');
    data_set($col2, 'yourref.addedparams', ['client']);
    data_set($col2, 'yourref.lookupclass', 'lookupsoref_voidservice');
    data_set($col2, 'yourref.action', 'lookupsoref_voidservice');
    data_set($col2, 'yourref.class', 'csyourref sbccsreadonly');

    data_set($col2, 'deldate.class', 'csdeldate sbccsreadonly');
    data_set($col2, 'podate.class', 'cspodate sbccsreadonly');

    $fields = ['dwhname', 'dagentname', 'dbranchname', 'ddeptname'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'dwhname.class', 'csdwhname sbccsreadonly');
    data_set($col3, 'dwhname.type', 'input');

    data_set($col3, 'dagentname.class', 'csdagentname sbccsreadonly');
    data_set($col3, 'dagentname.type', 'input');

    data_set($col3, 'dbranchname.class', 'csdbranchname sbccsreadonly');
    data_set($col3, 'dbranchname.type', 'input');

    data_set($col3, 'ddeptname.label', 'Department');
    data_set($col3, 'ddeptname.class', 'csddeptname sbccsreadonly');
    data_set($col3, 'ddeptname.type', 'input');

    $fields = [['cur', 'forex'], 'rem'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'forex.class', 'csforex sbccsreadonly');
    data_set($col4, 'rem.maxlength', '250');

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
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
    $data[0]['shipto'] = '';
    $data[0]['ourref'] = '';
    $data[0]['address'] = '';
    $data[0]['rem'] = '';

    $data[0]['deldate'] = '';
    $data[0]['podate'] = '';

    $data[0]['dwhname'] = '';
    $data[0]['wh'] = '';
    $data[0]['whname'] = '';

    $data[0]['dagentname'] = '';
    $data[0]['agent'] = '';
    $data[0]['agentname'] = '';

    $data[0]['dbranchname'] = '';
    $data[0]['branchcode'] = '';
    $data[0]['branchname'] = '';

    $data[0]['ddeptname'] = '';
    $data[0]['dept'] = '';
    $data[0]['deptname'] = '';
    $data[0]['newpo'] = '';

    $data[0]['forex'] = 1;
    $data[0]['cur'] = $this->companysetup->getdefaultcurrency($params);
    $data[0]['sotrno'] = '0';
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
         head.yourref,
         head.ourref,
         left(head.dateid,10) as dateid, 
         head.clientname,
         head.address, 
         head.shipto, head.rem,
         head.projectid,
         ifnull(project.code,'') as projectcode,
         ifnull(project.name,'') as projectname,
         '' as dprojectname,
         ifnull(project.code,'') as projectcode,
        ifnull(project.name,'') as projectname,
        '' as dprojectname,
        ifnull(qt.wh, '') as wh,
        ifnull(warehouse.clientname, '') as whname,
        concat(qt.wh, '~', warehouse.clientname) as dwhname, 
        ifnull(qt.agent, '') as agent,
        ifnull(agent.clientname, '') as agentname,
        concat(qt.agent, '~', agent.clientname) as dagentname, 
        ifnull(b.client, '') as branchcode,
        ifnull(b.clientname,'') as branchname, 
        concat(b.client, '~', b.clientname) as dbranchname,
        ifnull(d.client,'') as dept,
        ifnull(d.clientname,'') as deptname,
        concat(d.client, '~', d.clientname) as ddeptname,
        ifnull(d.client,'') as dept,
        ifnull(d.clientname,'') as deptname,
        concat(d.client, '~', d.clientname) as ddeptname,
        head.sotrno,
        left(qt.due,10) as podate,
        qt.cur,
        qt.forex,head.newpo";

    $qry = $qryselect . " from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join projectmasterfile as project on project.line=head.projectid
        left join hsshead as sqhead on sqhead.trno = head.sotrno
        left join hsrhead as qt on qt.sotrno=sqhead.trno
        left join client as warehouse on warehouse.client = qt.wh
        left join client as agent on agent.client = qt.agent
        left join client as b on b.clientid = qt.branch
        left join client as d on d.clientid = qt.deptid 
        where head.trno = ? and num.center = ? 
        union all " . $qryselect . " from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join client on head.client = client.client
        left join projectmasterfile as project on project.line=head.projectid
        left join hsshead as sqhead on sqhead.trno = head.sotrno
        left join hsrhead as qt on qt.sotrno=sqhead.trno
        left join client as warehouse on warehouse.client = qt.wh
        left join client as agent on agent.client = qt.agent
        left join client as b on b.clientid = qt.branch
        left join client as d on d.clientid = qt.deptid 
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
    $data['sotrno'] = $head['sotrno'];
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
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and iss=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);

    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for glhead
    $qry = "insert into " . $this->hhead . "(trno, doc, docno, client, clientname, address, shipto, tel, dateid, yourref, ourref, lockuser, 
      lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, projectid,rem,sotrno,newpo)
      SELECT 
      head.trno, head.doc, head.docno, head.client, head.clientname, head.address, head.shipto, head.tel, head.dateid, head.yourref, head.ourref, head.lockuser, 
      head.lockdate, head.openby, head.users, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.projectid,head.rem,head.sotrno,head.newpo
      FROM " . $this->head . " as head 
      left join " . $this->tablenum . " as num on num.trno=head.trno
      where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock
      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into " . $this->hstock . "(trno, line, uom, rem, isqty, iss, encodeddate, encodedby, 
        editdate, editby, itemid, whid, linex, refx, ref, projectid,isamt,amt,ext)
        SELECT trno, line, uom, rem, isqty, iss, encodeddate, encodedby, editdate, editby, itemid, 
        whid, linex, refx, ref, projectid ,isamt,amt,ext
        FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
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
    $qry = "select trno from " . $this->hstock . " where trno=?";
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno, doc, docno, client, clientname, address, shipto, tel, dateid, yourref, ourref, lockuser, 
    lockdate, openby, users, createdate, createby, editby, editdate, viewby, viewdate, projectid,rem,sotrno,newpo)
    SELECT 
    head.trno, head.doc, head.docno, head.client, head.clientname, head.address, head.shipto, head.tel, head.dateid, head.yourref, head.ourref, head.lockuser, 
    head.lockdate, head.openby, head.users, head.createdate, head.createby, head.editby, head.editdate, head.viewby, head.viewdate, head.projectid,head.rem,head.sotrno,head.newpo
    FROM " . $this->hhead . " as head 
    left join " . $this->tablenum . " as num on num.trno=head.trno
    where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      if (!$this->othersClass->unpostingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
      }

      $qry = "insert into " . $this->stock . "(trno, line, uom, rem, isqty, iss, encodeddate, encodedby, 
      editdate, editby, itemid, whid, linex, refx, ref, projectid,isamt,amt,ext)
      SELECT trno, line, uom, rem, isqty, iss, encodeddate, encodedby, editdate, editby, itemid, 
      whid, linex, refx, ref, projectid ,isamt,amt,ext
      from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
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
    $companyid = $config['params']['companyid'];
    $qty_dec = $this->companysetup->getdecimal('qty', $config['params']);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $qty_dec = 0;
    }

    $sqlselect = "select
    item.itemid,
    stock.trno, 
    stock.line,
    item.barcode, 
    item.itemname,
    stock.uom, 
    stock.iss,
    FORMAT(stock.isqty," . $qty_dec . ")  as isqty,
    stock.amt,
    FORMAT(stock.isamt," . $this->companysetup->getdecimal('price', $config['params']) . ")  as isamt,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('price', $config['params']) . ")  as ext,
    left(stock.encodeddate,10) as encodeddate,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.rem, 
    stock.refx,
    stock.linex,
    stock.ref,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    concat(item.itemname,'\\n',ifnull(brand.brand_desc,''),'\\r\\n',ifnull(mm.model_name,''),'\\r\\n',ifnull(i.itemdescription,'')) as itemdescription
    ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . " 
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
    where stock.trno =? 
    UNION ALL  
    " . $sqlselect . "  
    FROM $this->hstock as stock 
    left join item on item.itemid=stock.itemid 
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join frontend_ebrands as brand on brand.brandid = item.brand
    left join iteminfo as i on i.itemid  = item.itemid 
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
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid 
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid 
    left join iteminfo as i on i.itemid  = item.itemid 
    left join frontend_ebrands as brand on brand.brandid = item.brand
    where stock.trno = ? and stock.line = ? ";
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
      case 'getsqdetails':
        return $this->getqsdetails($config);
        break;
      case 'getsqsummary':
        return $this->getaosummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $srtrno = $this->coreFunctions->datareader("select refx as value from hvsstock where trno = ? group by refx limit 1", [$config['params']['trno']]);
    $qstrno = $this->coreFunctions->datareader("select refx as value from hsrstock where trno = ? group by refx limit 1", [$srtrno]);


    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
     CAST(concat('Total QS Amt: ',round(sum(s.ext),2)) as CHAR) as rem
     from hqshead as head 
     left join hqsstock as s on s.trno = head.trno
     where head.trno = ? 
     group by head.trno,head.docno,head.dateid";
    $t = $this->coreFunctions->opentable($qry, [$qstrno]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        // QS            
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
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'SR']);
        $a = $a + 100;
      }

      // SR
      $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
      CAST(concat('Total SR Amt: ',round(sum(s.ext),2)) as CHAR) as rem
      from hsrhead as head 
      left join hsrstock as s on s.trno = head.trno
      where s.refx = ? 
      group by head.trno,head.docno,head.dateid";
      $sr_data = $this->coreFunctions->opentable($qry, [$qstrno]);
      if (!empty($sr_data)) {
        foreach ($sr_data as $k => $sr) {
          // SR            
          data_set(
            $nodes,
            'SR',
            [
              'align' => 'right',
              'x' => 400,
              'y' => 70 + $a,
              'w' => 250,
              'h' => 80,
              'type' => $sr->docno,
              'label' => $sr->rem,
              'color' => 'green',
              'details' => [$sr->dateid]
            ]
          );
          array_push($links, ['from' => 'SR', 'to' => 'SO']);
          $a = $a + 100;

          $sotrno = $this->coreFunctions->datareader("select sotrno as value from hsrhead where trno = ? limit 1", [$srtrno]);

          $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hsshead as head 
          left join hsrhead as qt on qt.sotrno=head.trno
          left join hsrstock as s on s.trno = qt.trno
          where head.trno = ? 
          group by head.trno,head.docno,head.dateid";
          $so_data = $this->coreFunctions->opentable($qry, [$sotrno]);
          if (!empty($so_data)) {
            foreach ($so_data as $kk => $so) {
              // SO        
              data_set(
                $nodes,
                'SO',
                [
                  'align' => 'right',
                  'x' => 600,
                  'y' => 70 + $a,
                  'w' => 250,
                  'h' => 80,
                  'type' => $so->docno,
                  'label' => $so->rem,
                  'color' => 'yellow',
                  'details' => [$so->dateid]
                ]
              );
              array_push($links, ['from' => 'SO', 'to' => 'VS']);
              $a = $a + 100;

              // VS
              $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid,
            CAST(concat('Total VS Amt: ',round(sum(s.ext),2)) as CHAR) as rem
            from hvshead as head
            left join hvsstock as s on head.trno=s.trno
            where head.trno = ?
            group by head.trno,head.docno,head.dateid";
              $vsdata = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);

              if (!empty($vsdata)) {
                foreach ($vsdata as $kk => $vs) {
                  data_set(
                    $nodes,
                    'VS',
                    [
                      'align' => 'right',
                      'x' => 800,
                      'y' => 70 + $a,
                      'w' => 250,
                      'h' => 80,
                      'type' => $vs->docno,
                      'label' => $vs->rem,
                      'color' => 'orange',
                      'details' => [$vs->dateid]
                    ]
                  );
                  array_push($links, ['from' => 'VT', 'to' => '']);
                  $a = $a + 100;
                }
              }
            }
          }
        }
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
    $data = $this->openstockline($config);
    return ['row' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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

  public function addallitem($config)
  {
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $this->additem('insert', $config);
    }

    $data = $this->openstock($config['params']['trno'], $config);
    return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
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
  public function additem($action, $config)
  {
    $companyid = $config['params']['companyid'];
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $rem = '';
    $ref = '';
    $refx = 0;
    $linex = 0;
    $amt = 0;
    $isamt = 0;
    $ext = 0;

    if (isset($config['params']['data']['amt'])) {
      $amt = $config['params']['data']['amt'];
    }

    if (isset($config['params']['data']['isamt'])) {
      $isamt = $config['params']['data']['isamt'];
    }

    if (isset($config['params']['data']['ext'])) {
      $ext = $config['params']['data']['ext'];
    }

    if (isset($config['params']['data']['rem'])) {
      $rem = $config['params']['data']['rem'];
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

    $line = 0;
    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);
      if ($line == '') {
        $line = 0;
      }
      $line = $line + 1;
      $config['params']['line'] = $line;
      $qty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor 
      from item 
      left join uom on uom.itemid = item.itemid and uom.uom=? 
      where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    $computedata = $this->othersClass->computestock(0, 0, $qty, $factor);

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'isqty' => $qty,
      'iss' => $computedata['qty'],
      'isamt' => $isamt,
      'amt' => $amt,
      'ext' => $ext,
      'uom' => $uom,
      'rem' => $rem,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref
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
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        switch ($this->companysetup->getsystemtype($config['params'])) {
          case 'AIMS':
            if ($companyid == 0) { //main
              $stockinfo_data = [
                'trno' => $trno,
                'line' => $line,
                'rem' => $rem
              ];
              $this->coreFunctions->sbcinsert('stockinfotrans', $stockinfo_data);
            }
            break;
        }
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty:' . $qty);
        $row = $this->openstockline($config);
        $this->loadheaddata($config);
        if ($this->setserveditems($refx, $linex) == 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
          $msg = "(" . $item[0]->barcode . ") Void Qty is Greater than SO Qty.";
        }
        return ['row' => $row, 'status' => true, 'msg' => $msg, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $msg = '';
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($this->setserveditems($refx, $linex) == 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->setserveditems($refx, $linex);
        $return = false;
        $msg = "(" . $item[0]->barcode . ") Void Qty is Greater than SO Qty.";
      }
      return ['status' => $return, 'msg' => $msg];
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select line, refx,linex from ' . $this->stock . ' where trno=? and refx<>0 ', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

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
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' barcode:' . $data[0]->barcode . ' Qty:' . $data[0]->isqty);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function getlatestprice($config)
  {
    $barcode = $config['params']['barcode'];
    $client = $config['params']['client'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];

    $qry = "select docno,left(dateid,10) as dateid,round(amt,2) as amt,disc,uom from(select head.docno,head.dateid,
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
          order by dateid desc limit 5) as tbl order by dateid desc limit 1";
    $data = $this->coreFunctions->opentable($qry, [$center, $barcode, $client, $center, $barcode, $client]);

    if (!empty($data)) {
      return ['status' => true, 'msg' => 'Found the latest purchase price...', 'data' => $data];
    } else {
      $qry = "select amt,disc,uom from item where barcode=?";
      $data = $this->coreFunctions->opentable($qry, [$barcode]);

      if (floatval($data[0]->amt) == 0) {
        return ['status' => false, 'msg' => 'No Latest price found...'];
      } else {
        return ['status' => true, 'msg' => 'Found the latest price...', 'data' => $data];
      }
    }
  } // end function

  public function getaosummary($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select srstock.trno, srstock.line, head.docno, head.dateid, item.barcode, 
      item.itemname, srstock.uom, (srstock.isqty-srstock.sjqa-srstock.voidqty)/uom.factor as isqty, srstock.iss,
      qtstock.isamt, qtstock.amt, qtstock.ext,
      srstock.itemid
      from hsshead as head
      left join hsrhead as srhead on srhead.sotrno = head.trno
      left join hsrstock as srstock on srstock.trno = srhead.trno
      left join hqtstock as qtstock on qtstock.trno = srhead.qtrno
      left join item as item on item.itemid = srstock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = srstock.uom
      where srhead.trno = ? and srstock.iss != (srstock.sjqa + srstock.voidqty) and srstock.void != 1";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['trno'] = $trno;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['isamt'] = $data[$key2]->isamt;
          $config['params']['data']['amt'] = $data[$key2]->amt;
          $config['params']['data']['ext'] = $data[$key2]->ext;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $return = $this->additem('insert', $config);

          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }

          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0];
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

  public function getqsdetails($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $msg = '';
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select srstock.trno, srstock.line, head.docno, head.dateid, item.barcode, 
      item.itemname, srstock.uom,  (srstock.isqty-srstock.sjqa-srstock.voidqty)/uom.factor as isqty
      , srstock.iss,
      qtstock.isamt, qtstock.amt, qtstock.ext,
      srstock.itemid
      from hsshead as head
      left join hsrhead as srhead on srhead.sotrno = head.trno
      left join hsrstock as srstock on srstock.trno = srhead.trno
      left join hqtstock as qtstock on qtstock.trno = srhead.qtrno
      left join item as item on item.itemid = srstock.itemid
      left join uom on uom.itemid = item.itemid and uom.uom = srstock.uom
      where srhead.trno = ? and srstock.line = ?
      and srstock.iss != (srstock.sjqa + srstock.voidqty) and srstock.void != 1";
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['trno'] = $trno;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['data']['qty'] = $data[$key2]->isqty;
          $config['params']['data']['isamt'] = $data[$key2]->isamt;
          $config['params']['data']['amt'] = $data[$key2]->amt;
          $config['params']['data']['ext'] = $data[$key2]->ext;
          $config['params']['data']['rem'] = '';
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $return = $this->additem('insert', $config);
          if ($msg = '') {
            $msg = $return['msg'];
          } else {
            $msg = $msg . $return['msg'];
          }
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0];
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

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from " . $this->head . " as head left join " . $this->stock . " as 
    stock on stock.trno=head.trno where head.doc='VS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from " . $this->hhead . " as head left join " . $this->hstock . " as stock on stock.trno=
    head.trno where head.doc='VS' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }
    $srqttrno = $this->coreFunctions->getfieldvalue("hsrstock", "refx", "trno=? and line =?", [$refx, $linex]);
    $srqtline = $this->coreFunctions->getfieldvalue("hsrstock", "linex", "trno=? and line =?", [$refx, $linex]);

    $this->coreFunctions->execqry("update hqtstock set voidqty=" . $qty . " where trno=" . $srqttrno . " and line=" . $srqtline, 'update');

    //update void=1
    $qtqty = $this->coreFunctions->getfieldvalue("hqtstock", "iss", "trno=? and line =?", [$srqttrno, $srqtline]);
    if ($qtqty == $qty) {
      $this->coreFunctions->execqry("update hqtstock set void=1  where  trno=" . $srqttrno . " and line=" . $srqtline, 'update');
    } else {
      $this->coreFunctions->execqry("update hqtstock set void=0  where  trno=" . $srqttrno . " and line=" . $srqtline, 'update');
    }

    $srqty = $this->coreFunctions->getfieldvalue("hsrstock", "iss", "trno=? and line =?", [$refx, $linex]);
    if ($srqty == $qty) {
      $this->coreFunctions->execqry("update hsrstock set void=1  where  trno=" . $refx . " and line=" . $linex, 'update');
      
    } else {
      $this->coreFunctions->execqry("update hsrstock set void=0  where  trno=" . $refx . " and line=" . $linex, 'update');
    }

    return $this->coreFunctions->execqry("update hsrstock set voidqty=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }

  // reports 

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
    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
