<?php

namespace App\Http\Classes\modules\seastar;

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

class ll
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'LOADING LIST';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => true, 'showdate' => true];
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
  public $hqty = 'qty';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $defaultContra = 'AR1';

  private $fields = [
    'trno',
    'docno',
    'dateid',
    'rem',
    'yourref',
    'ourref'
  ];
  private $otherfields = ['trno', 'whfromid', 'whtoid', 'loadedby', 'vessel', 'voyageno', 'sealno', 'unit', 'plateno'];
  private $except = ['trno', 'dateid', 'due'];
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
      'view' => 4741,
      'edit' => 4742,
      'new' => 4743,
      'save' => 4744,
      'delete' => 4745,
      'print' => 4746,
      'lock' => 4747,
      'unlock' => 4748,
      'post' => 4749,
      'unpost' => 4750,
      'acctg' => 4751,
      'changeamt' => 4752,
      // 'changedisc' => 3303,
      'additem' => 4753,
      'edititem' => 4754,
      'deleteitem' => 4755
    );

    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $liststatus = 1;
    $listdocument = 2;

    $listdate = 3;
    $ourref = 4;
    $vessel = 5;
    $plateno = 6;
    $voyageno = 7;
    $sealno = 8;
    $postdate = 9;

    $getcols = [
      'action',
      'liststatus',
      'listdocument',
      'listdate',
      'ourref',
      'vessel',
      'plateno',
      'voyageno',
      'sealno',
      'postdate',
      'listpostedby',
      'listcreateby',
      'listeditby',
      'listviewby'
    ];

    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[$liststatus]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[$ourref]['align'] = 'text-left';
    $cols[$ourref]['label'] = 'Cargo Manifest No.';
    $cols[$postdate]['label'] = 'Post Date';


    $cols = $this->tabClass->delcollisting($cols);
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = [];

    return ['status' => true, 'data' => [], 'txtfield' => ['col1' => []]];
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

    $addparams = '';

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }


    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'info.vessel', 'info.plateno', 'info.voyageno', 'info.sealno', 'head.yourref', 'head.ourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby', 'head.rem'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $dateid = "left(head.dateid,10) as dateid";
    if ($searchfilter == "") $limit = 'limit 150';
    $orderby = "order by dateid desc, docno desc";


    $qry = "select head.trno,head.docno,head.clientname,$dateid, 'DRAFT' as status,
                    head.createby,head.editby,head.viewby,num.postedby, date(head.lockdate) as lockdate, 
                    date(num.postdate) as postdate,  head.yourref, head.ourref, head.rem,
                    info.vessel,info.plateno,info.voyageno,info.sealno
            from " . $this->head . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join cntnuminfo as info on info.trno = head.trno
            where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
                  and num.bref <> 'SRS'
            union all
            select head.trno,head.docno,head.clientname,$dateid,'POSTED' as status,
                    head.createby,head.editby,head.viewby, num.postedby, date(head.lockdate) as lockdate, 
                    date(num.postdate) as postdate,  head.yourref, head.ourref, head.rem,
                    info.vessel,info.plateno,info.voyageno,info.sealno
            from " . $this->hhead . " as head 
            left join " . $this->tablenum . " as num on num.trno=head.trno 
            left join hcntnuminfo as info on info.trno = head.trno
            where head.doc=? and num.center = ? and CONVERT(head.dateid,DATE)>=? 
                  and CONVERT(head.dateid,DATE)<=? " . $condition . $addparams . " " . $filtersearch . "
                  and num.bref <> 'SRS'
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
    $step1 = $this->helpClass->getFields(['btnnew', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'customer', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'isamt', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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
      $buttons['others']['items']['manual'] = ['label' => 'View Manual', 'todo' => ['lookupclass' => 'cm', 'title' => 'CM_MANUAL', 'action' => 'viewpdf',  'access' => 'view', 'type' => 'viewmanual']];
    }
    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $billshipdefault = ['customform' => ['action' => 'customform', 'lookupclass' => 'viewbillingshipping']];

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
    $action = 0;
    $waybill_docno = 1; //docno
    $waybill_date = 2; //dateid
    $declared_value = 3; //amount
    $quantity = 4; //rrqty
    $consignee = 5; //clientname

    $column = ['action', 'docno', 'dateid', 'isamt', 'isqty', 'consignee'];
    $sortcolumn = ['action', 'docno', 'dateid', 'isamt', 'isqty', 'consignee'];
    $headgridbtns = ['viewref', 'viewdiagram'];
    $computefield = ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'total' => 'ext'];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'sortcolumns' => $sortcolumn,
        'computefield' => $computefield,
        'headgridbtns' => $headgridbtns
      ],
    ];


    $stockbuttons = ['save', 'delete'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$waybill_docno]['style'] = 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$waybill_date]['style'] = 'width: 250px;whiteSpace: normal;min-width:250px;max-width:250px;';
    $obj[0][$this->gridname]['columns'][$declared_value]['style'] = 'width: 250px;whiteSpace: normal;min-width:250px;max-width:250px;';
    $obj[0][$this->gridname]['columns'][$quantity]['style'] = 'width: 130px;whiteSpace: normal;min-width:130px;max-width:130px;';
    $obj[0][$this->gridname]['columns'][$consignee]['style'] = 'width: 470px;whiteSpace: normal;min-width:470px;max-width:470px;';

    $obj[0]['inventory']['columns'][$waybill_docno]['label'] = 'Waybill No';
    $obj[0]['inventory']['columns'][$waybill_date]['label'] = 'Waybill Date';
    $obj[0]['inventory']['columns'][$declared_value]['label'] = 'Declared Value';
    $obj[0]['inventory']['columns'][$quantity]['label'] = 'Quantity';
    $obj[0]['inventory']['columns'][$consignee]['label'] = 'Consignee';

    $obj[0]['inventory']['columns'][$waybill_docno]['readonly'] = true;
    $obj[0]['inventory']['columns'][$waybill_date]['readonly'] = true;
    $obj[0]['inventory']['columns'][$declared_value]['readonly'] = true;
    $obj[0]['inventory']['columns'][$consignee]['readonly'] = true;

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0]['inventory']['totalfield'] = 'isamt';
    $obj[0]['inventory']['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {

    $tbuttons = ['pendingwb', 'saveitem', 'deleteallitem'];


    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'ourref', 'vessel', 'plateno'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'ourref.label', 'Cargo Manifest No:');
    data_set($col1, 'plateno.label', 'Van/Plate:');
    data_set($col1, 'docno.label', 'Transaction#');

    $fields = ['dateid', 'voyageno', 'sealno', 'yourref'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'yourref.label', 'B/L No.:');

    $fields = ['unit', 'loadedby', 'dwhfrom', 'dwhto'];
    $col3 = $this->fieldClass->create($fields);

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
    $data[0]['rem'] = '';
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';


    $data[0]['whfrom'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['whfrom']]);
    $data[0]['whfromname'] = $name;
    $fromid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data[0]['whfrom']]);
    $data[0]['whfromid'] = $fromid;

    $data[0]['whto'] = $this->companysetup->getwh($params);
    $name = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$data[0]['whto']]);
    $data[0]['whtoname'] = $name;
    $toid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data[0]['whto']]);
    $data[0]['whtoid'] = $toid;

    $data[0]['dwhfrom'] = $data[0]['whfrom'] . '~' . $name;
    $data[0]['dwhto'] = $data[0]['whto'] . '~' . $name;

    $data[0]['loadedby'] = '';
    $data[0]['vessel'] = '';
    $data[0]['voyageno'] = '';
    $data[0]['sealno'] = '';
    $data[0]['unit'] = '';
    $data[0]['plateno'] = '';

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
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " 
          where doc=? and center=? and num.bref <> 'SRS'
          order by trno desc 
          limit 1", [$doc, $center]);
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
         head.trno,
         head.docno,
         left(head.dateid,10) as dateid,
         head.rem,
         head.yourref,
         head.ourref,
         info.whfromid,
         info.whtoid,
         info.loadedby,
         info.vessel,
         info.voyageno,
         info.sealno,
         info.unit,
         info.plateno,
         whfrom.client as whfrom,
         whfrom.clientname as whfromname,
         whto.client as whto,
         whto.clientname as whtoname";

    $qry = $qryselect . " 
        from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join cntnuminfo as info on info.trno = head.trno 
        left join client as whfrom on whfrom.clientid=info.whfromid
        left join client as whto on whto.clientid=info.whtoid
        where head.trno = ? and num.doc=? and num.center = ? 

        union all 
        
        " . $qryselect . " 
        from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join hcntnuminfo as info on info.trno = head.trno 
        left join client as whfrom on whfrom.clientid=info.whfromid
        left join client as whto on whto.clientid=info.whtoid
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
    $dataother = [];

    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }

    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key], $config['params']['doc'], $companyid);
        } //end if
      }
    }


    foreach ($this->otherfields as $key) {
      if (array_key_exists($key, $head)) {
        $dataother[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], $config['params']['doc'], $companyid);
        } //end if
      }
    }

    // $data['due'] = $this->othersClass->computeterms($data['dateid'], $data['due'], $data['terms']);
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['rem'] . ' - ' . $head['yourref'] . ' - ' . $head['ourref']);
    }

    $infotransexist = $this->coreFunctions->getfieldvalue("cntnuminfo", "trno", "trno=?", [$head['trno']]);

    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("cntnuminfo", $dataother);
    } else {
      $this->coreFunctions->sbcupdate("cntnuminfo", $dataother, ['trno' => $head['trno']]);
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $result = $this->othersClass->posttranstock($config);
    if ($result['status']) {

      $this->coreFunctions->execqry("update glhead as h left join hcntnuminfo as info on info.trno=h.trno left join glstock as s on s.trno=h.trno left join glhead as gl on gl.trno=s.refx
                                      set gl.yourref=info.plateno, gl.editby='" . $config['params']['user'] . "', gl.editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where h.trno=? and gl.trno is not null", 'update', [$trno]);

      $this->coreFunctions->execqry("update glhead as h left join hcntnuminfo as info on info.trno=h.trno left join glstock as s on s.trno=h.trno left join lahead as gl on gl.trno=s.refx
                                      set gl.yourref=info.plateno, gl.editby='" . $config['params']['user'] . "', gl.editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where h.trno=? and gl.trno is not null", 'update', [$trno]);
    }
    return $result;
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $result = $this->othersClass->unposttranstock($config);
    if ($result['status']) {
      $this->coreFunctions->execqry("update lahead as h left join cntnuminfo as info on info.trno=h.trno left join lastock as s on s.trno=h.trno left join glhead as gl on gl.trno=s.refx
                                      set gl.yourref='', gl.editby='" . $config['params']['user'] . "', gl.editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where h.trno=? and gl.trno is not null", 'update', [$trno]);

      $this->coreFunctions->execqry("update lahead as h left join cntnuminfo as info on info.trno=h.trno left join lastock as s on s.trno=h.trno left join lahead as gl on gl.trno=s.refx
                                      set gl.yourref='', gl.editby='" . $config['params']['user'] . "', gl.editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where h.trno=? and gl.trno is not null", 'update', [$trno]);
    }

    return $result;
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select stock.trno,stock.line,stock.ref as docno,date(info.wbdate) as dateid,
    FORMAT(stock.isamt,2) as isamt,round(stock.isqty) as isqty,
       info.consignid,cs.clientname as consignee,stock.refx,stock.linex,stock.ref,'' as bgcolor ";

    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
    FROM $this->stock as stock
    left join $this->head as head on head.trno = stock.trno
    left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
    left join client as cs on cs.clientid=info.consignid
    where stock.trno =?
    UNION ALL
    " . $sqlselect . "
    FROM $this->hstock as stock
    left join $this->hhead as head on head.trno=stock.trno
    left join hstockinfo as info on info.trno=stock.trno and info.line=stock.line
    left join client as cs on cs.clientid=info.consignid
    where stock.trno =? order by line";

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
    left join $this->head as head on head.trno=stock.trno
    left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
    left join client as cs on cs.clientid=info.consignid
    where stock.trno = ? and stock.line = ? ";

    // $this->coreFunctions->LogConsole(
    //   $sqlselect . "
    // FROM $this->stock as stock
    // left join $this->head as head on head.trno=stock.trno
    // left join stockinfo as info on info.trno=stock.trno and info.line=stock.line
    // left join client as cs on cs.clientid=info.consignid
    // where stock.trno = $trno and stock.line = $line"
    // );
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'addrow':
        return $this->addrow($config);
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
      case 'getwbsummary':
        return $this->getpendingwbsummary($config);
        break;
      case 'getwbdetails':
        return $this->getpendingwbdetails($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    $action = $config['params']['action'];
    switch ($action) {
      case 'navigation':
        return $this->othersClass->navigatedocno($config);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }


  public function getwbsummaryqry($config)
  {
    return "
      select stock.trno,stock.line,head.docno, date(head.dateid) as dateid, head.consigneeid,con.clientname as consignee,(stock.isqty-stock.qa) as isqty, stock.isamt, 0 as posted
      FROM lahead as head left join lastock as stock on stock.trno=head.trno left join client as con on con.clientid=head.consigneeid
      where stock.trno = ? and stock.void=0 and stock.isqty>stock.qa 
      union all
      select stock.trno,stock.line,head.docno, date(head.dateid) as dateid, head.consigneeid, con.clientname as consignee,(stock.isqty-stock.qa) as isqty, stock.isamt, 1 as posted
      FROM glhead as head left join glstock as stock on stock.trno=head.trno left join client as con on con.clientid=head.consigneeid
      where stock.trno = ? and stock.void=0 and stock.isqty>stock.qa ";
  }

  public function getpendingwbsummary($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getwbsummaryqry($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['trno'] = $trno;
          $config['params']['data']['isqty'] = $data[$key2]->isqty;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['isamt'] = $data[$key2]->isamt;
          $config['params']['data']['wbdate'] = $data[$key2]->dateid;
          $config['params']['data']['consignid'] = $data[$key2]->consigneeid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  }

  public function getpendingwbdetails($config)
  {
    $trno = $config['params']['trno'];
    $forex = 1;
    $dateid = $this->coreFunctions->getfieldvalue($this->head, 'dateid', 'trno=?', [$trno]);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select stock.trno,stock.line,head.docno, date(head.dateid) as dateid, head.consigneeid, con.clientname as consignee,(stock.isqty-stock.qa) as isqty, stock.isamt
      FROM lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client as con on con.clientid=head.consigneeid
      where stock.trno = ? and stock.line = ? and stock.void=0 and stock.isqty>stock.qa
      union all
      select stock.trno,stock.line,head.docno, date(head.dateid) as dateid, head.consigneeid, con.clientname as consignee, (stock.isqty-stock.qa) as isqty, stock.isamt
      FROM glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client as con on con.clientid=head.consigneeid
      where stock.trno = ? and stock.line = ? and stock.void=0 and stock.isqty>stock.qa
    ";
      $data = $this->coreFunctions->opentable($qry, [
        $config['params']['rows'][$key]['trno'],
        $config['params']['rows'][$key]['line'],
        $config['params']['rows'][$key]['trno'],
        $config['params']['rows'][$key]['line']
      ]);

      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['trno'] = $trno;
          $config['params']['data']['isqty'] = $data[$key2]->isqty;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['isamt'] = $data[$key2]->isamt;
          $config['params']['data']['wbdate'] = $data[$key2]->dateid;
          $config['params']['data']['consignid'] = $data[$key2]->consigneeid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->setserveditems($data[$key2]->trno, $data[$key2]->line) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->setserveditems($data[$key2]->trno, $data[$key2]->line);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    //CM
    $qry = "
    select head.trno, head.docno, date(head.dateid) as dateid,
    CAST(concat('Total CM Amt: ',round(sum(stock.ext),2)) as CHAR) as rem, stock.refx
    from glhead as head
    left join glstock as stock on head.trno = stock.trno
    where head.trno = ?
    group by head.trno,head.docno,head.dateid,stock.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'left',
            'x' => $startx + 400,
            'y' => 200,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'red',
            'details' => [$t[$key]->dateid]
          ]
        );

        if (floatval($t[$key]->refx) != 0) {
          //SJ
          $qry = "
          select head.docno,
          date(head.dateid) as dateid,
          CAST(concat('Total SJ Amt: ', round(sum(stock.ext),2), ' - ', 'Balance: ', round(ar.bal, 2)) as CHAR) as rem,
          stock.refx, head.trno
          from glhead as head
          left join glstock as stock on head.trno = stock.trno
          left join arledger as ar on ar.trno = head.trno
          where head.trno=? and head.doc = 'SJ'
          group by head.docno, head.dateid, head.trno, ar.bal, stock.refx";
          $x = $this->coreFunctions->opentable($qry, [$t[$key]->refx]);
          if (!empty($x)) {
            foreach ($x as $key2 => $value1) {
              data_set(
                $nodes,
                $x[$key2]->docno,
                [
                  'align' => 'left',
                  'x' => $startx,
                  'y' => 100,
                  'w' => 250,
                  'h' => 80,
                  'type' => $x[$key2]->docno,
                  'label' => $x[$key2]->rem,
                  'color' => 'green',
                  'details' => [$x[$key2]->dateid]
                ]
              );
              array_push($links, ['from' => $x[$key2]->docno, 'to' => $t[$key]->docno]);

              //SO
              $qry = "select so.trno,so.docno,left(so.dateid,10) as dateid,
              CAST(concat('Total SO Amt: ',round(sum(s.ext),2)) as CHAR) as rem
              from hsohead as so
              left join hsostock as s on s.trno = so.trno
              where so.trno = ?
              group by so.trno,so.docno,so.dateid";
              $sodata = $this->coreFunctions->opentable($qry, [$x[$key2]->refx]);
              if (!empty($sodata)) {
                foreach ($sodata as $k => $v) {
                  data_set(
                    $nodes,
                    $sodata[$k]->docno,
                    [
                      'align' => 'right',
                      'x' => 200,
                      'y' => 50 + $a,
                      'w' => 250,
                      'h' => 80,
                      'type' => $sodata[$k]->docno,
                      'label' => $sodata[$k]->rem,
                      'color' => 'blue',
                      'details' => [$sodata[$k]->dateid]
                    ]
                  );
                  array_push($links, ['from' => $x[$key2]->docno, 'to' => $sodata[$k]->docno]);
                  $a = $a + 100;
                }
              }

              //APV
              $rrtrno = $x[$key2]->trno;
              $apvqry = "
              select  head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from glhead as head
              left join gldetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'
              union all
              select  head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from lahead as head
              left join ladetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'";
              $apvdata = $this->coreFunctions->opentable($apvqry, [$rrtrno, $rrtrno]);
              if (!empty($apvdata)) {
                foreach ($apvdata as $key3 => $value2) {
                  data_set(
                    $nodes,
                    'apv',
                    [
                      'align' => 'left',
                      'x' => $startx + 400,
                      'y' => 100,
                      'w' => 250,
                      'h' => 80,
                      'type' => $apvdata[$key3]->docno,
                      'label' => $apvdata[$key3]->rem,
                      'color' => 'red',
                      'details' => [$apvdata[$key3]->dateid]
                    ]
                  );
                  array_push($links, ['from' => $x[$key2]->docno, 'to' => 'apv']);
                  $a = $a + 100;
                }
              }

              //CV
              if (!empty($apvdata)) {
                $apvtrno = $apvdata[0]->trno;
              } else {
                $apvtrno = $rrtrno;
              }
              $cvqry = "
              select head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from glhead as head
              left join gldetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'
              union all
              select head.docno, date(head.dateid) as dateid, head.trno,
              CAST(concat('Applied Amount: ', round(detail.db+detail.cr,2)) as CHAR) as rem
              from lahead as head
              left join ladetail as detail on head.trno = detail.trno
              where detail.refx = ? and head.doc = 'AR'";
              $cvdata = $this->coreFunctions->opentable($cvqry, [$apvtrno, $apvtrno]);
              if (!empty($cvdata)) {
                foreach ($cvdata as $key2 => $value2) {
                  data_set(
                    $nodes,
                    $cvdata[$key2]->docno,
                    [
                      'align' => 'left',
                      'x' => $startx + 800,
                      'y' => 100,
                      'w' => 250,
                      'h' => 80,
                      'type' => $cvdata[$key2]->docno,
                      'label' => $cvdata[$key2]->rem,
                      'color' => 'red',
                      'details' => [$cvdata[$key2]->dateid]
                    ]
                  );
                  array_push($links, ['from' => 'apv', 'to' => $cvdata[$key2]->docno]);
                  $a = $a + 100;
                }
              }
            }
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
    $isupdate = $this->additem('update', $config);
    $data = $this->openstockline($config);
    $data2 = json_decode(json_encode($data), true);
    // if(!$isupdate){
    //   $data[0]->errcolor = 'bg-red-2';
    // }
    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than SJ Qty ';
        }
      }
    }

    if (!$isupdate) {
      return ['row' => $data, 'status' => true, 'msg' => $msg1 . '/' . $msg2];
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
        if ($data[$key]->refx == 0) {
          $msg1 = ' Out of stock ';
        } else {
          $msg2 = ' Qty Received is Greater than PO Qty ';
        }
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Please check, some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
    }
  } //end function


  public function addrow($config)
  {
    $data = [];
    $trno = $config['params']['trno'];

    $wh = $this->coreFunctions->getfieldvalue($this->head, "wh", "trno=?", [$trno]);
    $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$wh]);

    $data['line'] = 0;
    $data['trno'] = $trno;
    $data['isqty'] = 0;
    $data['iss'] = 0;
    $data['uom'] = '';
    $data['isamt'] = 0;
    $data['ref'] = '';
    $data['wbdate'] = '';
    $data['consignid'] = 0;
    $data['consignee'] = '';
    $data['docno'] = '';
    $data['dateid'] = '';
    $data['bgcolor'] = 'bg-blue-2';
    return ['row' => $data, 'status' => true, 'msg' => 'New row added'];
  }

  // insert and update item
  public function additem($action, $config, $setlog = false)
  {
    $classname = __NAMESPACE__ . '\\ll';
    $config['docmodule'] = new $classname;
    $trno = $config['params']['trno'];
    $qty = $config['params']['data']['isqty'];

    $refx = 0;
    $linex = 0;
    $ref = '';
    $consignid = 0;
    $wbdate = '';

    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }

    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    if (isset($config['params']['data']['consignid'])) {
      $consignid = $config['params']['data']['consignid'];
    }

    if (isset($config['params']['data']['wbdate'])) {
      $wbdate = $config['params']['data']['wbdate'];
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
      $amt = $config['params']['data']['isamt'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'isamt' => $amt,
      'isqty' => $qty,
      'ext' => 0,
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref
    ];

    $stockinfo = [
      'trno' => $trno,
      'line' => $line,
      'wbdate' => $wbdate,
      'consignid' => $consignid
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      $data['sortline'] =  $data['line'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Waybill No:' . $ref . ' Declared Value' . $amt, $setlog ? $this->tablelogs : '');
        $row = $this->openstockline($config);
        $this->coreFunctions->sbcinsert('stockinfo', $stockinfo);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      $this->coreFunctions->sbcupdate('stockinfo', $stockinfo, ['trno' => $trno, 'line' => $data['line']]);

      if ($this->setserveditems($refx, $linex) === 0) {
        $this->setserveditems($refx, $linex);
        $return = false;
      }

      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfo where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->setserveditems($data[$key]->refx, $data[$key]->linex);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function setserveditems($refx, $linex)
  {
    if ($refx == 0) {
      return 1;
    }

    $qry1 = "select stock." . $this->dqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc in ('LL') and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->dqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc in ('LL') and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->dqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    $result = $this->coreFunctions->execqry("update lastock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    if ($result) {
      $result = $this->coreFunctions->execqry("update glstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
    }
    return $result;
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
    $this->coreFunctions->execqry('delete from stockinfo where trno=? and line=?', 'delete', [$trno, $line]);
    $this->logger->sbcwritelog(
      $trno,
      $config,
      'STOCKINFO',
      'DELETE - Line:' . $line
        . ' Waybill No:' . $config['params']['row']['ref']
    );

    if ($data[0]->refx !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }

    $data = json_decode(json_encode($data), true);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function



  // reports starto

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

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
} //end class
