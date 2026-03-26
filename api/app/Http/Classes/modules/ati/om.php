<?php

namespace App\Http\Classes\modules\ati;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;


use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class om
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'O S I';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'omhead';
  public $hhead = 'homhead';
  public $stock = 'omstock';
  public $hstock = 'homstock';
  public $tablelogs = 'transnum_log';
  public $statlogs = 'transnum_stat';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = [
    'trno', 'docno', 'dateid', 'rem'
  ];
  private $otherfields = ['trno', 'trnxtype'];
  private $except = ['trno', 'dateid'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [];

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
      'view' => 4177,
      'edit' => 4178,
      'new' => 4179,
      'save' => 4179,
      'delete' => 4181,
      'print' => 4182,
      'lock' => 4183,
      'unlock' => 4184,
      // 'changeamt' => 3612,
      'post' => 4185,
      'unpost' => 4186,
      'additem' => 4187,
      'edititem' => 4188,
      'deleteitem' => 4189,
      'forreceiving' => 4197,
      'forso' => 4198,
      'forposting' => 4120
    );
    return $attrib;
  }


  public function createdoclisting($config)
  {
    $allowso = $this->othersClass->checkAccess($config['params']['user'], 4121);

    $action = 0;
    $liststatus = 1;
    $listdocument = 2;
    $listdate = 3;
    $ctrlno = 4;
    $itemdesc = 5;
    $pono = 6;
    $ref = 7;
    $oraclecode = 8;
    $rrqty = 9;
    $sodetails =  10;

    $getcols = ['action', 'lblstatus', 'listdocument', 'listdate', 'ctrlno', 'itemdesc', 'pono', 'ref', 'oraclecode', 'rrqty', 'sodetails'];
    $stockbuttons = ['view']; //, 'diagram'

    // if ($allowso) {
    //   array_push($stockbuttons, 'showsobreakdown');
    // }
    array_push($stockbuttons, 'pickerdrop', 'postomitem', 'customformrevisionom');

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:180px;whiteSpace: normal;min-width:180px;';
    $cols[$liststatus]['style'] = 'width:120px;whiteSpace: normal;min-width:120px;';
    $cols[$itemdesc]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $cols[$oraclecode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;';
    $cols[$rrqty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';


    $cols[$ref]['type'] = 'input';

    $cols[$rrqty]['align'] = 'left';

    $cols[$action]['btns']['pickerdrop']['label'] = 'Done SO';

    $cols[$action]['btns']['pickerdrop']['checkfield'] = 'isposted';
    $cols[$action]['btns']['postomitem']['checkfield'] = 'isposted';
    $cols[$action]['btns']['customformrevisionom']['checkfield'] = 'forrevision';
    return $cols;
  }

  public function paramsdatalisting($config)
  {

    $fields = ['stat'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'stat.label', 'Status');
    data_set($col1, 'stat.type', 'lookup');
    data_set($col1, 'stat.action', 'lookupomtransstatus');
    data_set($col1, 'stat.lookupclass', 'lookupomtransstatus');

    $fields = [];
    $col2 = $this->fieldClass->create($fields);

    $data = $this->coreFunctions->opentable("SELECT 'For Oracle Receiving' as stat, 'forreceiving' as typecode");

    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = isset($config['params']['doclistingparam']['typecode']) ? $config['params']['doclistingparam']['typecode'] : 'draft';
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $adminid = $config['params']['adminid'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['docno', 'clientname', 'yourref', 'ourref', 'postedby', 'createby', 'editby', 'viewby', 'inspo', 'rem', 'pono'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }
    $dateid = "left(head.dateid,10) as dateid";
    $status = "'DRAFT'";
    $leftjoin = "";
    $leftjoin_posted = "";

    $status = "ifnull(stat2.status,'For Receiving')";
    switch ($itemfilter) {
      case 'draft':
      case 'forreceiving':
        $condition = ' and num.postdate is null and head.lockdate is null and stock.statid=0';
        break;

        // case 'forreceiving':
        //   $condition = ' and num.postdate is null and head.lockdate is null and stock.statid=47';
        //   $status = "ifnull(stat2.status,'DRAFT')";
        //   break;

      case 'forso':
        $condition = ' and num.postdate is null and head.lockdate is null and stock.statid=46';
        $status = "ifnull(stat2.status,'DRAFT')";
        break;

      case 'forposting':
        $condition = ' and num.postdate is null and head.lockdate is null and stock.statid=39';
        $status = "ifnull(stat2.status,'DRAFT')";
        break;

      case 'posted':
        $condition = ' and stock.statid=12 ';
        $status = "'Posted'";
        break;
    }

    $trnxx = '';
    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      if ($trnx != '') $trnxx = " and info.trnxtype='" . $trnx . "' ";
    }

    $qry = "select trno,docno,clientname,dateid,stat,createby,editby,viewby,postedby,postdate,yourref,ourref,inspo,rem,itemdesc,
                   oraclecode,rrqty,line,sodetails,isposted,ref,pono,forrevision,ctrlno from (
                   select head.trno,head.docno,head.clientname,$dateid,
                          concat(" . $status . ",if(info.instructions='For Revision',' (For Revision)','')) as stat,
                          head.createby,head.editby,head.viewby,num.postedby, date(num.postdate) as postdate,
                          head.yourref, head.ourref, info.inspo , head.rem, pr.itemdesc, stock.oraclecode, stock.rrqty, stock.line,
                          (select group_concat('SO#:',sono,' (Qty:',qty,')' SEPARATOR '\n\r') 
                          from omso where omso.trno=stock.trno and omso.line=stock.line) as sodetails, 'false' as isposted,stock.ref,
                          (select group_concat(distinct yourref separator ', ')
                          from (select yourref,pos.reqtrno,pos.reqline
                                from hpohead as po
                                left join hpostock as pos on pos.trno=po.trno) as k
                                where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono,
                          (case when stock.statid in (0,12) then 'true' else 'false' end) as forrevision, pr.ctrlno
                    from " . $this->head . " as head 
                    left join " . $this->tablenum . " as num on num.trno=head.trno 
                    left join " . $this->stock . " as stock on stock.trno=head.trno
                    left join trxstatus as stat on stat.line=num.statid
                    left join trxstatus as stat2 on stat2.line=stock.statid 
                    left join headinfotrans as info on info.trno=head.trno
                    left join hstockinfotrans as pr on pr.trno=stock.reqtrno and pr.line=stock.reqline
                    " . $leftjoin . "
                    where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and 
                          CONVERT(head.dateid,DATE)<=?  $trnxx" . $condition . " 
                    union all
                    select head.trno,head.docno,head.clientname,$dateid," . $status . " as stat,head.createby,head.editby,head.viewby, 
                          num.postedby, date(num.postdate) as postdate,head.yourref, head.ourref, info.inspo, head.rem, pr.itemdesc, 
                          stock.oraclecode, stock.rrqty, stock.line,
                          (select group_concat('SO#:',sono,' (Qty:',qty,')' SEPARATOR '\n\r') 
                          from homso where homso.trno=stock.trno and homso.line=stock.line) as sodetails, 'true' as isposted,stock.ref,
                          (select group_concat(distinct yourref separator ', ')
                          from (select yourref,pos.reqtrno,pos.reqline
                                from hpohead as po
                                left join hpostock as pos on pos.trno=po.trno) as k
                                where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono,
                          (case when stock.statid in (0,12) then 'true' else 'false' end) as forrevision, pr.ctrlno
                    from " . $this->hhead . " as head 
                    left join " . $this->tablenum . " as num on num.trno=head.trno 
                    left join " . $this->hstock . " as stock on stock.trno=head.trno
                    left join trxstatus as stat on stat.line=num.statid
                    left join trxstatus as stat2 on stat2.line=stock.statid 
                    left join hheadinfotrans as info on info.trno=head.trno
                    left join hstockinfotrans as pr on pr.trno=stock.reqtrno and pr.line=stock.reqline
                    " . $leftjoin_posted . "
                    where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? 
                          and CONVERT(head.dateid,DATE)<=?  $trnxx" . $condition . "  ) as k
            where 1=1 " . $filtersearch . "
            group by trno,docno,clientname,dateid,stat,createby,editby,viewby,postedby,postdate,yourref,ourref,inspo,rem,itemdesc,
                   oraclecode,rrqty,line,sodetails,isposted,ref,pono,forrevision,ctrlno
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
      // 'others'
    );

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
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


    return $buttons;
  } // createHeadbutton

  public function createtab2($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'documententry', 'lookupclass' => 'entrytransnumpicture', 'label' => 'Attachment', 'access' => 'view']];
    $obj = $this->tabClass->createtab($tab, []);

    $return['Attachment'] = ['icon' => 'fa fa-envelope', 'tab' => $obj];

    $tab = ['tableentry' => ['action' => 'tableentry', 'lookupclass' => 'entrytransnumtodo', 'label' => 'To Do', 'access' => 'view']];
    $objtodo = $this->tabClass->createtab($tab, []);
    $return['To Do'] = ['icon' => 'fa fa-list', 'tab' => $objtodo];

    return $return;
  }


  public function createTab($access, $config)
  {
    $viewrrcost = $this->othersClass->checkAccess($config['params']['user'], 843);
    $isproject = $this->companysetup->getisproject($config['params']);
    $editprice = $this->othersClass->checkAccess($config['params']['user'], 4030);

    $allowso = $this->othersClass->checkAccess($config['params']['user'], 4121);
    $allowupdate = $this->othersClass->checkAccess($config['params']['user'], 4508);

    $action = 0;
    $ctrlno = 1;
    $isrr = 2;
    $priolvl = 3;
    $isexisted = 4;
    $oraclecode = 5;
    $rrqty = 6;
    $unit = 7;
    $rrcost = 8;
    $ispa = 9;
    $disc = 10;
    $surcharge = 11;
    $ext = 12;
    $sodetails = 13;
    $itemdesc = 14;
    $specs = 15;
    $rem = 16;
    $requestorname = 17;
    $department = 18;
    $ref = 19;
    $supplier = 20;
    $customer = 21;
    $svsnum = 22;
    $sanodesc = 23;
    $poref = 24;
    $pono = 25;
    $itemname = 26;
    $barcode = 27;

    $column = [
      'action', 'ctrlno', 'isrr', 'priolvl', 'isexisted', 'oraclecode', 'rrqty', 'unit', 'rrcost', 'ispa', 'disc', 'surcharge', 'ext', 'sodetails', 'itemdesc',
      'specs', 'rem', 'requestorname', 'department', 'ref', 'supplier', 'customer', 'svsnum', 'sanodesc', 'poref', 'pono', 'itemname',  'barcode'
    ];

    $tab = [
      $this->gridname => [
        'gridcolumns' => $column,
        'computefield' => ['dqty' => $this->dqty, 'hqty' => $this->hqty, 'damt' => $this->damt, 'hamt' => $this->hamt, 'disc' => 'disc', 'total' => 'ext'],
      ]
    ];

    // if ($allowso) {
    //   $tab['tableentry'] = ['action' => 'tableentry', 'lookupclass' => 'entrysooq', 'label' => 'SO'];
    // }
    if ($allowupdate) {
      $tab['multigrid'] = ['action' => 'tableentry', 'lookupclass' => 'tabstocksoinfo', 'label' => 'UPDATE DETAILS', 'checkchanges' => 'tableentry'];
    }

    $stockbuttons = ['save', 'delete', 'showsobreakdown'];
    if ($allowso) {
      array_push($stockbuttons, 'showsobreakdown');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];

    $obj[0][$this->gridname]['columns'][$department]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$supplier]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$customer]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ref]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$specs]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$disc]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$sanodesc]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$requestorname]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Description';
    $obj[0][$this->gridname]['columns'][$unit]['label'] = 'UOM';
    $obj[0][$this->gridname]['columns'][$ref]['label'] = 'Reference';
    $obj[0][$this->gridname]['columns'][$unit]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$itemdesc]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$specs]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$surcharge]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$pono]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$surcharge]['label'] = 'Surcharge';

    if ($editprice != "1") {
      $obj[0][$this->gridname]['columns'][$ispa]['type'] = 'coldel';
    }

    $obj[0][$this->gridname]['columns'][$oraclecode]['style'] = 'width:250px;whiteSpace: normal;min-width:250px;';

    $obj[0][$this->gridname]['columns'][$oraclecode]['field'] = 'oraclecode';
    $obj[0][$this->gridname]['columns'][$oraclecode]['field'] = 'text-left';
    $obj[0][$this->gridname]['columns'][$oraclecode]['align'] = 'left';

    $obj[0][$this->gridname]['columns'][$oraclecode]['class'] = 'sbccsenablealways';
    $obj[0][$this->gridname]['columns'][$oraclecode]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = 'text-align:right;width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$rrcost]['style'] = 'text-align:right;width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$disc]['style'] = 'width:60px;whiteSpace: normal;min-width:60px;';
    $obj[0][$this->gridname]['columns'][$surcharge]['style'] = 'text-align:right;width:150px;whiteSpace: normal;min-width:150px;';
    $obj[0][$this->gridname]['columns'][$ext]['style'] = 'text-align:right;width:100px;whiteSpace: normal;min-width:100px;';

    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $obj[0][$this->gridname]['columns'][$specs]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $obj[0][$this->gridname]['columns'][$rem]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';

    $obj[0][$this->gridname]['columns'][$customer]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$supplier]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;';
    $obj[0][$this->gridname]['columns'][$requestorname]['style'] = 'width:200px;whiteSpace: normal;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$svsnum]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$sanodesc]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$sanodesc]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$ext]['align'] = 'text-align';

    $obj[0]['inventory']['columns'][$rrcost]['checkfield'] = 'ispa';

    //temporary hide - 
    $obj[0][$this->gridname]['columns'][$ispa]['type'] = 'coldel';
    $obj[0][$this->gridname]['columns'][$isexisted]['type'] = 'coldel';

    $obj[0][$this->gridname]['columns'][$oraclecode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$unit]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$specs]['type'] = 'label';
    // $obj[0][$this->gridname]['columns'][$priolvl]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$poref]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$poref]['label'] = 'Type';

    $obj[0]['inventory']['columns'][$action]['checkfield'] = 'isposted';
    $obj[0]['inventory']['columns'][$rrqty]['checkfield'] = 'isposted';
    $obj[0]['inventory']['columns'][$isrr]['checkfield'] = 'isposted';
    $obj[0]['inventory']['columns'][$rem]['checkfield'] = 'isposted';

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'hidden';
    $obj[0][$this->gridname]['columns'][$barcode]['label'] = '';

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingoq', 'saveitem', 'deleteallitem'];
    $obj = $this->tabClass->createtabbutton($tbuttons);

    return $obj;
  }

  public function createHeadField($config)
  {
    $allowrevision = $this->othersClass->checkAccess($config['params']['user'], 3620);

    $fields = ['docno', 'dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'docno.label', 'Transaction#');
    data_set($col1, 'ourref.label', 'Serialized');
    data_set($col1, 'ourref.type', 'lookup');
    data_set($col1, 'ourref.action', 'lookuprandom');
    data_set($col1, 'ourref.lookupclass', 'lookupserializeoption');
    data_set($col1, 'ourref.readonly', true);
    data_set($col1, 'ourref.class', 'sbccsreadonly');

    $fields = ['rem'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'remarks.type', 'ctextarea');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];
  }


  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['rem'] = '';
    $data[0]['trnxtype'] = '';
    if ($params['adminid'] != 0) {
      $data[0]['trnxtype'] = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$params['adminid']]);
    }


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
    $statid = $this->othersClass->getstatid($config);

    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;

    $this->othersClass->checkseendate($config, $tablenum);

    $adminid = $config['params']['adminid'];
    $trnxx = '';
    if ($adminid != 0) {
      $trnx = $this->coreFunctions->getfieldvalue("client", "deptcode", "clientid=?", [$adminid]);
      if ($trnx != '') $trnxx = " and info.trnxtype='" . $trnx . "' ";
    }
    $qryselect = "select
        num.center,
        head.trno,
        head.docno,
        left(head.dateid,10) as dateid,
        date_format(head.createdate,'%Y-%m-%d') as createdate,
        head.rem, head.yourref, head.rqtrno, left(head.deldate, 10) as deldate, head.deladdress";

    $qry = $qryselect . " 
        from $table as head
        left join $tablenum as num on num.trno = head.trno
        left join headinfotrans as info on info.trno=head.trno
        where head.trno = ? and num.center = ?  $trnxx

        union all 

        " . $qryselect . " 
        from $htable as head
        left join $tablenum as num on num.trno = head.trno
        left join hheadinfotrans as info on info.trno=head.trno
        where head.trno = ? and num.center=?  $trnxx ";

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
      $hideobj = [];
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg, 'hideobj' => $hideobj];
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

    $dataother = [];
    foreach ($this->otherfields as $key) {
      $dataother[$key] = $head[$key];
      if (!in_array($key, $this->except)) {
        $dataother[$key] = $this->othersClass->sanitizekeyfield($key, $dataother[$key], '', $companyid);
      } //end if
    }

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];

    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $insert = $this->coreFunctions->sbcinsert($this->head, $data);

      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);

      $info = [];
      $info['trno'] = $head['trno'];
    }

    $infotransexist = $this->coreFunctions->getfieldvalue("headinfotrans", "trno", "trno=?", [$head['trno']]);
    if ($infotransexist == '') {
      $this->coreFunctions->sbcinsert("headinfotrans", $dataother);
    } else {
      $this->coreFunctions->sbcupdate("headinfotrans", $dataother, ['trno' => $head['trno']]);
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

    $stock = $this->coreFunctions->opentable("select trno from omstock where trno=?", [$trno]);
    if (!empty($stock)) {
      return ['trno' => $trno2, 'status' => false, 'msg' => 'Cant delete this document, some items already processed.'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
    $this->othersClass->deleteattachments($config);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }

    $qry = "select trno from " . $this->stock . " where trno=? and statid<>12 limit 1";
    $pendingitem = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($pendingitem)) {
      return ['status' => false, 'msg' => 'Post failed. Please check; all items must be tagged as posted.'];
    }

    // for glhead
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,client,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,agent,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,
      revision, rqtrno, deldate, deladdress,serialized, invnotrequired, subinv)
      SELECT head.trno,head.doc, head.docno,head.client, head.clientname, head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.agent,head.wh,
      head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,
      head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
      head.revision, head.rqtrno, head.deldate, head.deladdress,head.serialized,head.invnotrequired, head.subinv
      FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";

    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for glstock

      if (!$this->othersClass->postingheadinfotrans($config)) {
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting head data.'];
      }

      if (!$this->othersClass->postingstockinfotrans($config)) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while posting stock/s.'];
      }

      $qry = "insert into homso (trno, line, soline, sono, qty, createby, createdate, editby, editdate, rtno)
            select trno, line, soline, sono, qty, createby, createdate, editby, editdate, rtno from omso where trno=?";
      if (!$this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on posting SO details.'];
      }


      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
        whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
        encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline, deptid, suppid, oraclecode, sono,rtno,isexisted,priolvl,rrby,rrdate,statid)
        SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
        encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,rem,stageid, projectid ,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline, deptid, suppid, oraclecode, sono,rtno,isexisted,priolvl,rrby,rrdate,statid
        FROM " . $this->stock . " where trno =?";


      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update osiref
        $this->coreFunctions->execqry("update homstock as hs
        left join hstockinfotrans as prs on prs.trno=hs.reqtrno and prs.line=hs.reqline
        set prs.osiref='" . $docno . " - Posted',prs.otherleadtime='" . $this->othersClass->getCurrentTimeStamp() . "' 
        where hs.refx<>0 and  hs.trno=" . $trno, 'update');

        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user'], 'statid' => 5];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from headinfotrans where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from omso where trno=?", "delete", [$trno]);

        // $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->logger->sbcstatlog($trno, $config, 'HEAD', 'POSTED');

        $reqdata = $this->coreFunctions->opentable('select reqtrno,reqline from ' . $this->hstock . ' where trno=?', [$trno]);

        foreach ($reqdata as $key => $value) {
          $osiref = $this->coreFunctions->datareader(
            "select group_concat(docno,'\r (',sono,')') as value
                        from (select concat(h.docno,' - Draft') as docno, ifnull(group_concat(so.sono),'') as sono
                              from omstock as s 
                              left join omso as so on so.trno=s.trno and so.line=s.line 
                              left join omhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno
                              union all
                              select concat(h.docno,' - Posted') as docno, ifnull(group_concat(so.sono),'') as sono 
                              from homstock as s 
                              left join homso as so on so.trno=s.trno and so.line=s.line 
                              left join homhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno) as so",
            [$reqdata[$key]->reqtrno, $reqdata[$key]->reqline, $reqdata[$key]->reqtrno, $reqdata[$key]->reqline]
          );
          $this->coreFunctions->execqry("update hstockinfotrans set osiref2='" . $osiref . "'  where trno=" . $reqdata[$key]->reqtrno . " and line=" . $reqdata[$key]->reqline);
        }

        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on posting stock.'];
      }
    } else {
      return ['status' => false, 'msg' => 'An error occurred while posting head data.'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unpost failed, either the item was already served or it was voided.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,client,clientname,address,shipto,dateid,terms,rem,forex,
    yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,wh,due,cur,projectid,subproject,branch,deptid,sotrno,billid,shipid,vattype,tax,empid,billcontactid,shipcontactid,
    revision, rqtrno, deldate, deladdress,serialized,invnotrequired,subinv)
    select head.trno, head.doc, head.docno, ifnull(client.client,''), head.clientname, head.address, head.shipto,
    head.dateid as dateid, head.terms, head.rem, head.forex, head.yourref, head.ourref, head.createdate,
    head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.wh,head.due,head.cur,head.projectid,head.subproject,head.branch,head.deptid,head.sotrno,head.billid,head.shipid,head.vattype,head.tax,head.empid,head.billcontactid,head.shipcontactid,
    head.revision, head.rqtrno, head.deldate, head.deladdress,head.serialized,head.invnotrequired,head.subinv
    from (" . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno)left join client on client.client=head.client
    where head.trno=? limit 1";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result) {
      $qry = "insert into headrem(line, trno, rem, createby, createdate, remtype) 
            select line, trno, rem, createby, createdate, remtype from hheadrem where  trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    }
    //head


    if (!$this->othersClass->unpostingheadinfotrans($config)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'An error occurred while unposting head data.'];
    }

    if (!$this->othersClass->unpostingstockinfotrans($config)) {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unposting failed. There are issues with inventory.'];
    }

    $qry = "insert into omso (trno, line, soline, sono, qty, createby, createdate, editby, editdate, rtno,rem)
            select trno, line, soline, sono, qty, createby, createdate, editby, editdate, rtno,rem from homso where trno=?";
    if (!$this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from stockinfotrans where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, so details problem.'];
    }

    $qry = "insert into " . $this->stock . "(
      trno,line,itemid,uom,whid,loc,ref,disc,
      cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline, deptid, suppid, oraclecode, sono,rtno,isexisted,priolvl,rrby,rrdate,statid)
      select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
      ext,rem, encodeddate, qa, encodedby, editdate, editby,sku,refx,linex,cdrefx,cdlinex,stageid, projectid,sorefx,solinex,osrefx,oslinex,sgdrate,poref,reqtrno,reqline, deptid, suppid, oraclecode, sono,rtno,isexisted,priolvl,rrby,rrdate,statid
      from " . $this->hstock . " where trno=?";

    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result) {
      $qry = "insert into headrem(line, trno, rem, createby, createdate, remtype) 
            select line, trno, rem, createby, createdate, remtype from hheadrem where   trno=?";
      $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    }
    //stock
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null, postedby='', statid=39 where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("update " . $this->stock . " set statid=39 where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hstockinfotrans where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from hheadinfotrans where trno=?", "delete", [$trno]);
      $this->coreFunctions->execqry("delete from homso where trno=?", "delete", [$trno]);
      // $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      $this->logger->sbcstatlog($trno, $config, 'HEAD', 'FOR POSTING');

      $reqdata = $this->coreFunctions->opentable('select reqtrno,reqline from ' . $this->stock . ' where trno=?', [$trno]);

      foreach ($reqdata as $key => $value) {
        $osiref = $this->coreFunctions->datareader(
          "select group_concat(docno,'\r (',sono,')') as value
                        from (select concat(h.docno,' - Draft') as docno, ifnull(group_concat(so.sono),'') as sono
                              from omstock as s 
                              left join omso as so on so.trno=s.trno and so.line=s.line 
                              left join omhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno
                              union all
                              select concat(h.docno,' - Posted') as docno, ifnull(group_concat(so.sono),'') as sono 
                              from homstock as s 
                              left join homso as so on so.trno=s.trno and so.line=s.line 
                              left join homhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno) as so",
          [$reqdata[$key]->reqtrno, $reqdata[$key]->reqline, $reqdata[$key]->reqtrno, $reqdata[$key]->reqline]
        );
        $this->coreFunctions->execqry("update hstockinfotrans set osiref2='" . $osiref . "'  where trno=" . $reqdata[$key]->reqtrno . " and line=" . $reqdata[$key]->reqline);
      }


      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
    }




    //}
  } //end function

  private function getstockselect($config)
  {
    $sqlselect = "select item.brand as brand,
    ifnull(mm.model_name,'') as model,
    ifnull(item.itemid,0) as itemid,
    stock.trno,
    stock.line,
    stock.refx,
    stock.linex,
    stock.cdrefx,
    stock.cdlinex,
    stock.sorefx,
    stock.solinex,
    item.barcode,
    item.itemname,
    stock.uom,
    stock.cost,
    stock.qty as qty,
    FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
    FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
    FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
    ifnull((select sum(scamt) as surcharge
     from (select cvs.scamt,cvs.reqtrno,cvs.reqline,cvs.cdrefx,cvs.cdlinex
            from hcvitems as cvs
            union all
            select cvs.scamt,cvs.reqtrno,cvs.reqline,cvs.cdrefx,cvs.cdlinex
            from cvitems as cvs) as a
     where a.reqtrno=stock.reqtrno and a.reqline=stock.reqline),0) as surcharge,
    left(stock.encodeddate,10) as encodeddate,
    stock.disc,
    case when stock.void=0 then 'false' else 'true' end as void,
    case when stock.isexisted=0 then 'false' else 'true' end as isexisted,
    case when stock.ispa=1 then 'true' else 'false' end as ispa,
    if(stock.rrdate is null,'false','true') as isrr,
    round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
    stock.ref,
    stock.whid,
    warehouse.client as wh,
    warehouse.clientname as whname,
    stock.loc,
    item.brand,
    stock.deptid,
    stock.suppid,
    stock.priolvl,
    stock.poref,
    stock.rrby,
    stock.rrdate,
    xinfo.rem, stock.stageid,st.stage,
    ifnull(uom.factor,1) as uomfactor,
    '' as bgcolor,
    case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
    prj.name as stock_projectname,
    stock.projectid as projectid,
    item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.osrefx,stock.oslinex,stock.sgdrate,stock.poref,
    ifnull(info.itemdesc,'') as itemdesc, ifnull(xinfo.unit,'') as unit, ifnull(xinfo.specs,'') as specs, ifnull(info.purpose,'') as purpose,ifnull(info.requestorname,'') as requestorname,stock.reqtrno,stock.reqline,
    ifnull(dept.clientname,'') as department, ifnull(sup.clientname,'') as supplier, stock.oraclecode, pr.clientname as customer, ifnull(svs.sano,'') as svsnum, stock.svsno, ifnull(sa.sano,'') as sanodesc,
    if(stock.statid=12,'true','false') as isposted, (select group_concat(distinct yourref separator ', ')
         from (select yourref,pos.reqtrno,pos.reqline
               from hpohead as po
               left join hpostock as pos on pos.trno=po.trno where pos.void=0) as k
         where k.reqtrno=stock.reqtrno and k.reqline=stock.reqline and reqtrno <> 0) as pono,info.ctrlno,xinfo.uom2";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . ", (select group_concat('SO#:',sono,' (Qty:',qty,')' SEPARATOR '\n\r') from omso where omso.trno=stock.trno and omso.line=stock.line) as sodetails
    FROM $this->stock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join stockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
     left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
    left join hprhead as pr on pr.trno=prs.trno
    left join clientsano as svs on svs.line=stock.svsno
    left join clientsano as sa on sa.line=pr.sano
    left join client as dept on dept.clientid=stock.deptid
    left join client as sup on sup.clientid=stock.suppid
    left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
    where stock.trno =?
    UNION ALL
    " . $sqlselect . ", (select group_concat('SO#:',sono,' (Qty:',qty,')' SEPARATOR '\n\r') from homso where homso.trno=stock.trno and homso.line=stock.line) as sodetails
    FROM $this->hstock as stock
    left join item on item.itemid=stock.itemid
    left join model_masterfile as mm on mm.model_id = item.model
    left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
    left join client as warehouse on warehouse.clientid=stock.whid
    left join stagesmasterfile as st on st.line = stock.stageid
    left join projectmasterfile as prj on prj.line = stock.projectid
    left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
    left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
    left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
    left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
    left join hprhead as pr on pr.trno=prs.trno
    left join clientsano as svs on svs.line=stock.svsno
    left join clientsano as sa on sa.line=pr.sano
    left join client as dept on dept.clientid=stock.deptid
    left join client as sup on sup.clientid=stock.suppid
    left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
    where stock.trno =?";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);

    foreach ($stock as $key => $value) {
      $value->ext = str_replace(',', '', $value->ext);
      $value->surcharge = str_replace(',', '', $value->surcharge);
      $stock[$key]->ext  = number_format($value->ext + $value->surcharge, 2);
    }

    return $stock;
  } //end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = $sqlselect . ", (select group_concat('SO#:',sono,' (Qty:',qty,')' SEPARATOR '\n\r') from omso where omso.trno=stock.trno and omso.line=stock.line) as sodetails
      FROM $this->stock as stock
      left join item on item.itemid=stock.itemid
      left join model_masterfile as mm on mm.model_id = item.model
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom 
      left join client as warehouse on warehouse.clientid=stock.whid
      left join stagesmasterfile as st on st.line = stock.stageid
      left join projectmasterfile as prj on prj.line = stock.projectid
      left join stockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
      left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
      left join hoqstock as oq on oq.trno=stock.refx and oq.line=stock.linex
      left join hprstock as prs on prs.trno=oq.reqtrno and prs.line=oq.reqline
      left join hprhead as pr on pr.trno=prs.trno
      left join clientsano as svs on svs.line=stock.svsno
      left join clientsano as sa on sa.line=pr.sano
      left join client as dept on dept.clientid=stock.deptid
      left join client as sup on sup.clientid=stock.suppid
      left join uomlist as uom2 on uom2.uom=xinfo.uom2 and uom2.isconvert=1
      where stock.trno = ? and stock.line = ? ";
    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);

    foreach ($stock as $key => $value) {
      $value->ext = str_replace(',', '', $value->ext);
      $value->surcharge = str_replace(',', '', $value->surcharge);
      $stock[$key]->ext  = number_format($value->ext + $value->surcharge, 2);
    }
    return $stock;
  } // end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'additem':
        return $this->additem('insert', $config);
        break;
      case 'addallitem': // save all item selected from lookup
        return $this->addallitem($config);
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
      case 'getoqsummary':
      case 'getoqdetail':
        return $this->getoqsummary($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'forreceiving':
        return $this->forreceiving($config);
        break;
      case 'forso':
        return $this->forso($config);
        break;
      case 'forposting':
        return $this->forposting($config);
        break;
      case 'pickerdrop':
        return $this->doneso($config);
        break;
      case 'postomitem':
        return $this->postomitem($config);
        break;
      case 'customformrevisionom':
        return $this->customformrevisionom($config);
        break;
      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function postomitem($config)
  {
    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $statid = $this->coreFunctions->getfieldvalue("omstock", "statid", "trno=? and line=?", [$trno, $line], '', true);
    if ($statid != 39) {
      return ['trno' => $trno, 'status' => true, 'msg' => 'Only tagged as for posting is allowed to post.', 'backlisting' => true];
    }

    // $printed = $this->coreFunctions->getfieldvalue("headinfotrans", "printdate", "trno=?", [$trno]);
    // if ($printed == '') {
    //   return ['trno' =>  $trno, 'status' => false, 'msg' => 'Please print OSI first.'];
    // }

    // $qry = "select reqtrno,reqline from " . $this->stock . " where trno=?";
    // $getpr = $this->coreFunctions->opentable($qry, [$trno]);

    // $qry = "select statid,docno,surcharge 
    //         from (select c.statid,h.docno,(select sum(det.payment) from detailinfo as det where det.trno=s.trno) as surcharge
    //               from ladetail as s
    //               left join lahead as h on h.trno=s.trno
    //               left join glstock as rr on rr.trno=s.refx
    //               left join cntnum as c on c.trno=h.trno
    //               left join coa on coa.acnoid=s.acnoid
    //               where h.doc='CV' and rr.reqtrno = " . $getpr[0]->reqtrno . " and rr.reqline = " . $getpr[0]->reqline . "
    //               group by statid,docno,s.trno
    //               union all
    //               select c.statid,h.docno,(select sum(det.payment) from hdetailinfo as det where det.trno=s.trno) as surcharge
    //               from gldetail as s
    //               left join glhead as h on h.trno=s.trno
    //               left join cntnum as num on num.trno=h.trno
    //               left join glstock as rr on rr.trno=s.refx
    //               left join cntnum as c on c.trno=h.trno
    //               left join coa on coa.acnoid=s.acnoid
    //               where h.doc='CV' and rr.reqtrno = " . $getpr[0]->reqtrno . " and rr.reqline = " . $getpr[0]->reqline . "
    //               group by statid,docno,s.trno
    //               union all
    //               select c.statid,h.docno,
    //                     (select sum(det.payment) from detailinfo as det where det.trno=d.trno) as surcharge
    //               from cvitems as cv
    //               left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
    //               left join lahead as h on h.trno=cv.trno
    //               left join ladetail as d on d.trno=h.trno and d.line=cv.line
    //               left join cntnum as c on c.trno=h.trno
    //               left join coa on coa.acnoid=d.acnoid
    //               where po.reqtrno=" . $getpr[0]->reqtrno . " and po.reqline=" . $getpr[0]->reqline . "
    //               group by statid,docno,d.trno
    //               union all
    //               select c.statid,h.docno,(select sum(det.payment) from hdetailinfo as det where det.trno=d.trno) as surcharge
    //               from hcvitems as cv
    //               left join hpostock as po on po.trno=cv.refx and po.line=cv.linex
    //               left join glhead as h on h.trno=cv.trno
    //               left join gldetail as d on d.trno=h.trno and d.line=cv.line
    //               left join cntnum as c on c.trno=h.trno
    //               left join coa on coa.acnoid=d.acnoid
    //               where po.reqtrno=" . $getpr[0]->reqtrno . " and po.reqline=" . $getpr[0]->reqline . "
    //               group by statid,docno,d.trno) as k where surcharge <> 0";
    // $getstatid = $this->coreFunctions->opentable($qry);

    // if (!empty($getstatid)) {
    //   if ($getstatid[0]->statid == 39) {
    //     goto postitem;
    //   } else {
    //     return ['trno' =>  $trno, 'status' => false, 'msg' => 'Status of CV should be For Posting.' . ' - ' . $getstatid[0]->docno];
    //   }
    // } else {
    //   postitem:
    //   $this->coreFunctions->execqry("update omstock set statid=12, editby='" . $config['params']['user'] . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $trno . " and line=" . $line);

    //   $pending = $this->coreFunctions->opentable("select trno from omstock where statid<>12 and trno=?", [$trno]);
    //   if (empty($pending)) {
    //     $config['params']['trno'] = $trno;
    //     $this->posttrans($config);
    //   }
    // }

    $this->coreFunctions->execqry("update omstock set statid=12, editby='" . $config['params']['user'] . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $trno . " and line=" . $line);

    $oraclecode = $this->coreFunctions->getfieldvalue('omstock', 'oraclecode', 'trno=? and line=?', [$trno, $line], 'line desc');
    $this->logger->sbcstatlog($trno, $config, 'STOCK',   'POSTED - Oracle Code: ' . $oraclecode);

    $pending = $this->coreFunctions->opentable("select trno from omstock where statid<>12 and trno=?", [$trno]);
    if (empty($pending)) {
      $config['params']['trno'] = $trno;
      $this->posttrans($config);
    }

    return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
  }


  // public function customformrevisionom($config)
  // {
  //   $trno = $config['params']['dataparams']['trno'];
  //   $rem = $config['params']['dataparams']['rem'];
  //   $this->logger->sbcwritelog($trno, $config, 'REVISION', $rem);
  // }

  public function doneso($config)
  {

    $trno = $config['params']['row']['trno'];
    $line = $config['params']['row']['line'];

    $omqty = $this->coreFunctions->datareader("select rrqty as value from omstock where trno=? and line=?", [$trno, $line], '', true);
    $soqty = $this->coreFunctions->datareader("select ifnull(sum(qty),0) as value from omso where trno=? and line=?", [$trno, $line], '', true);

    if ($soqty == 0) return ['trno' => $trno, 'status' => true, 'msg' => 'Please input SO#.'];

    if ($soqty < $omqty) {
      return ['trno' => $trno, 'status' => true, 'msg' => 'There are pending item quantities without SO#.', 'backlisting' => true];
    } else {
      $this->coreFunctions->execqry("update omstock set statid=39, editby='" . $config['params']['user'] . "', editdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $trno . " and line=" . $line);
      $oraclecode = $this->coreFunctions->getfieldvalue('omstock', 'oraclecode', 'trno=? and line=?', [$trno, $line], 'line desc');
      $this->logger->sbcstatlog($trno, $config, 'STOCK',   'TAGGED FOR POSTING - Oracle Code: ' . $oraclecode);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully updated.', 'backlisting' => true];
    }
  }



  public function getoqsummary($config)
  {
    $trno = $config['params']['trno'];
    $docno = $this->coreFunctions->getfieldvalue("omhead", "docno", "trno=?", [$trno]);
    $rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getoqqry($config, $config['params']['rows'][$key]['refx'], $config['params']['rows'][$key]['linex']);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);

      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['trno'] = $trno;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['rrcost'] = $data[$key2]->rrcost;
          $config['params']['data']['cost'] = $data[$key2]->cost;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['ext'] = $data[$key2]->ext;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['whid'] = $data[$key2]->whid;
          $config['params']['data']['wh'] = $data[$key2]->wh;
          $config['params']['data']['loc'] = $data[$key2]->loc;
          $config['params']['data']['void'] = $data[$key2]->void;
          $config['params']['data']['cdrefx'] = $data[$key2]->cdrefx;
          $config['params']['data']['cdlinex'] = $data[$key2]->cdlinex;

          $config['params']['data']['sorefx'] = $data[$key2]->sorefx;
          $config['params']['data']['solinex'] = $data[$key2]->solinex;

          $config['params']['data']['osrefx'] = $data[$key2]->osrefx;
          $config['params']['data']['oslinex'] = $data[$key2]->oslinex;

          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['stageid'] = $data[$key2]->stageid;

          $config['params']['data']['deptid'] = $data[$key2]->deptid;
          $config['params']['data']['suppid'] = $data[$key2]->suppid;

          $config['params']['data']['oraclecode'] = $data[$key2]->oraclecode;
          $config['params']['data']['svsno'] = $data[$key2]->svsno;

          $config['params']['data']['isexisted'] = $data[$key2]->isexisted;
          $config['params']['data']['ispa'] = $data[$key2]->ispa;

          $config['params']['data']['rem'] = $data[$key2]->rem;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['reqtrno'] = $data[$key2]->reqtrno;
          $config['params']['data']['reqline'] = $data[$key2]->reqline;

          $config['params']['data']['priolvl'] = $data[$key2]->priolvl;
          $config['params']['data']['poref'] = $data[$key2]->inspo;
          $config['params']['data']['rrdate'] = null;
          $config['params']['data']['isrr'] = 'false';
          $config['params']['data']['uom2'] = $data[$key2]->uom2;
          $config['params']['data']['ctrlno'] = $data[$key2]->ctrlno;
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

            $osiref = $this->coreFunctions->datareader(
              "select group_concat(docno,'\r (',sono,')') as value
                        from (select concat(h.docno,' - Draft') as docno, ifnull(group_concat(so.sono),'') as sono
                              from omstock as s 
                              left join omso as so on so.trno=s.trno and so.line=s.line 
                              left join omhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno
                              union all
                              select concat(h.docno,' - Posted') as docno, ifnull(group_concat(so.sono),'') as sono 
                              from homstock as s 
                              left join homso as so on so.trno=s.trno and so.line=s.line 
                              left join homhead as h on h.trno=s.trno 
                              where s.reqtrno=? and s.reqline=? 
                              group by h.docno) as so",
              [$data[$key2]->reqtrno, $data[$key2]->reqline, $data[$key2]->reqtrno, $data[$key2]->reqline]
            );

            $this->coreFunctions->execqry("update hstockinfotrans set osiref2='" . $osiref . "'  where trno=" . $data[$key2]->reqtrno . " and line=" . $data[$key2]->reqline);
            array_push($rows, $return['row'][0]);

            $this->coreFunctions->execqry("update hstockinfotrans set osiref='" . $docno . " - Draft', otherleadtime='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $data[$key2]->reqtrno . " and line=" . $data[$key2]->reqline);
          }
        } // end foreach
      } //end if
    } //end foreach
    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.', 'reloadhead' => true];
  } //end function

  public function getoqqry($config, $trno, $line)
  {
    $joins = "";
    $fields = "";

    $filter = '';
    if ($line != 0) {
      $filter .= ' and stock.line=' . $line;
    }


    return "select 
      ifnull(item.itemid,0) as itemid,
      stock.trno,
      stock.line,
      stock.refx,
      stock.linex,
      stock.cdrefx,
      stock.cdlinex,
      stock.sorefx,
      stock.solinex,
      item.barcode,
      item.itemname,
      stock.uom,
      stock.cost,
      stock.qty as qty,
      FORMAT(stock.rrcost," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrcost,
      FORMAT(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ")  as rrqty,
      FORMAT(stock.ext," . $this->companysetup->getdecimal('currency', $config['params']) . ") as ext,
      left(stock.encodeddate,10) as encodeddate,
      stock.disc,
      case when stock.void=0 then 'false' else 'true' end as void,
      case when stock.isexisted=0 then 'false' else 'true' end as isexisted,
      case when stock.ispa=1 then 'true' else 'false' end as ispa,
      round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
      stock.ref,
      stock.whid,
      warehouse.client as wh,
      warehouse.clientname as whname,
      stock.loc,
      item.brand,
      stock.deptid,
      stock.suppid,
      stock.priolvl,
      hinfo.inspo,
      xinfo.rem, stock.stageid,st.stage,info.ctrlno,
      ifnull(uom.factor,1) as uomfactor,
      '' as bgcolor,
      case when stock.void=0 then '' else 'bg-red-2' end as errcolor,
      prj.name as stock_projectname,
      stock.projectid as projectid,
      item.subcode, item.partno, round(item.dqty, " . $this->companysetup->getdecimal('qty', $config['params']) . ") as boxcount,stock.osrefx,stock.oslinex,stock.sgdrate,stock.poref,
      ifnull(xinfo.itemdesc,'') as itemdesc, ifnull(xinfo.unit,'') as unit, ifnull(xinfo.specs,'') as specs, ifnull(info.purpose,'') as purpose,ifnull(info.requestorname,'') as requestorname,stock.reqtrno,stock.reqline,
      ifnull(dept.clientname,'') as department, ifnull(sup.clientname,'') as supplier, 
      stock.oraclecode, pr.clientname as customer, ifnull(svs.sano,'') as svsnum, 
      stock.svsno, ifnull(sa.sano,'') as sanodesc,head.docno,xinfo.uom2
      FROM hoqhead as head
      left join hheadinfotrans as hinfo on hinfo.trno=head.trno
      left join hoqstock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join client as warehouse on warehouse.clientid=stock.whid
      left join stagesmasterfile as st on st.line = stock.stageid
      left join projectmasterfile as prj on prj.line = stock.projectid
      left join hstockinfotrans as xinfo on xinfo.trno=stock.trno and xinfo.line=stock.line
      left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
      left join hprhead as pr on pr.trno=stock.reqtrno
      left join clientsano as svs on svs.line=stock.svsno
      left join clientsano as sa on sa.line=pr.sano
      left join client as dept on dept.clientid=stock.deptid
      left join client as sup on sup.clientid=stock.suppid
      left join uomlist as u on u.uom=xinfo.uom2 and u.isconvert=1
      where stock.trno =$trno $filter and stock.qty>stock.qa and stock.void=0
    ";
  }

  private function updateitemvoid($config)
  {
    $trno = $config['params']['trno'];
    $rows = $config['params']['rows'];
    foreach ($rows as $key) {
      $this->coreFunctions->execqry('update ' . $this->hstock . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
    }
  } //end function

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
        if ($data[$key]->refx == 0) {
          $msg1 = ' Qty Received is Greater than CD Qty ';
        } else {
          $msg2 = ' Qty Received is Greater than PR Qty ';
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
    $msg = '';
    $isupdate = true;
    foreach ($config['params']['row'] as $key => $value) {
      $config['params']['data'] = $value;
      $result = $this->additem('update', $config);
      if (!$result['status']) {
        $msg .= $result['msg'] . '. ';
        $isupdate = false;
      }
    }
    $data = $this->openstock($config['params']['trno'], $config);
    $data2 = json_decode(json_encode($data), true);

    $msg1 = '';
    $msg2 = '';
    foreach ($data2 as $key => $value) {
      if ($data2[$key][$this->dqty] == 0) {
        $data[$key]->errcolor = 'bg-red-2';
        $isupdate = false;
        $msg2 = ' Qty Received is Greater than OQ Qty ';
      }
    }
    if ($isupdate) {
      return ['inventory' => $data, 'status' => true, 'msg' => 'Successfully saved.'];
    } else {
      if ($msg != '') {
        return ['inventory' => $data, 'status' => true, 'msg' => $msg];
      } else {
        return ['inventory' => $data, 'status' => true, 'msg' => 'Please check some items have zero qty (' . $msg1 . ' / ' . $msg2 . ')'];
      }
    }
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

  // insert and update item
  public function additem($action, $config)
  {
    $msg = '';
    $status = true;

    $isproject = $this->companysetup->getisproject($config['params']);
    $uom = $config['params']['data']['uom'];
    $barcode = '';
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $wh = $config['params']['data']['wh'];
    $loc = $config['params']['data']['loc'];

    $ref = '';
    $void = 'false';
    if (isset($config['params']['data']['void'])) {
      $void = $config['params']['data']['void'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }

    $refx = 0;
    $linex = 0;
    $cdrefx = 0;
    $cdlinex = 0;
    $sorefx = 0;
    $solinex = 0;
    $osrefx = 0;
    $oslinex = 0;
    $rem = '';
    $stageid = 0;
    $projectid = 0;
    $poref = '';
    $sgdrate = 0;
    $reqtrno = 0;
    $reqline = 0;
    $svsno = 0;

    $reqtrno =  isset($config['params']['data']['reqtrno']) ? $config['params']['data']['reqtrno'] : 0;
    $reqline =  isset($config['params']['data']['reqline']) ? $config['params']['data']['reqline'] : 0;
    $deptid =  isset($config['params']['data']['deptid']) ? $config['params']['data']['deptid'] : 0;
    $suppid =  isset($config['params']['data']['suppid']) ? $config['params']['data']['suppid'] : 0;
    $svsno =  isset($config['params']['data']['svsno']) ? $config['params']['data']['svsno'] : 0;
    $isexisted =  isset($config['params']['data']['isexisted']) ? $config['params']['data']['isexisted'] : 0;
    $ispa =  isset($config['params']['data']['ispa']) ? $config['params']['data']['ispa'] : 1;
    $priolvl =  isset($config['params']['data']['priolvl']) ? $config['params']['data']['priolvl'] : 0;

    $oraclecode =  isset($config['params']['data']['oraclecode']) ? $config['params']['data']['oraclecode'] : '';
    $itemdesc =  isset($config['params']['data']['itemdesc']) ? $config['params']['data']['itemdesc'] : '';
    $specs =  isset($config['params']['data']['specs']) ? $config['params']['data']['specs'] : '';
    $unit =  isset($config['params']['data']['unit']) ? $config['params']['data']['unit'] : '';
    $poref =  isset($config['params']['data']['poref']) ? $config['params']['data']['poref'] : '';
    $uom2 = isset($config['params']['data']['uom2']) ? $config['params']['data']['uom2'] : '';
    $ctrlno = isset($config['params']['data']['ctrlno']) ? $config['params']['data']['ctrlno'] : '';

    if (isset($config['params']['data']['rrcost'])) {
      $rrcost = $config['params']['data']['rrcost'];
    }

    if (isset($config['params']['data']['cost'])) {
      $cost = $config['params']['data']['cost'];
    }
    if (isset($config['params']['data']['qty'])) {
      $qty = $config['params']['data']['qty'];
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
    if (isset($config['params']['data']['cdrefx'])) {
      $cdrefx = $config['params']['data']['cdrefx'];
    }
    if (isset($config['params']['data']['cdlinex'])) {
      $cdlinex = $config['params']['data']['cdlinex'];
    }

    if (isset($config['params']['data']['stageid'])) {
      $stageid = $config['params']['data']['stageid'];
    }

    if (isset($config['params']['data']['solinex'])) {
      $solinex = $config['params']['data']['solinex'];
    }

    if (isset($config['params']['data']['sorefx'])) {
      $sorefx = $config['params']['data']['sorefx'];
    }

    if (isset($config['params']['data']['oslinex'])) {
      $oslinex = $config['params']['data']['oslinex'];
    }

    if (isset($config['params']['data']['osrefx'])) {
      $osrefx = $config['params']['data']['osrefx'];
    }
    if (isset($config['params']['data']['poref'])) {
      $poref = $config['params']['data']['poref'];
    }

    if (isset($config['params']['data']['sgdrate'])) {
      $sgdrate = $config['params']['data']['sgdrate'];
    } else {
      $sgdrate = $this->othersClass->getexchangerate('PHP', 'SGD');
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

      $amt = $config['params']['data']['rrcost'];
      $qty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];

      $amt = $config['params']['data'][$this->damt];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
    }

    $amt = $this->othersClass->sanitizekeyfield('rrcost', $amt);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $factor = 1;
    if (!empty($item)) {
      $barcode = $item[0]->barcode;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    } else {
      if ($uom2 != '') {
        $factor = $this->coreFunctions->getfieldvalue("uomlist", "factor", "uom=? and isconvert=1", [$uom2], '', true);
        if ($factor == 0) {
          $factor = 1;
        }
      }
    }

    $forex = $this->coreFunctions->getfieldvalue($this->head, 'forex', 'trno=?', [$trno]);
    $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);

    if ($forex == 0) {
      $forex = 1;
    }

    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      'rrcost' => $rrcost,
      'cost' => $computedata['amt'] * $forex,
      'rrqty' => $qty,
      'qty' => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'loc' => $loc,
      'uom' => $uom,
      'void' => $void,
      'refx' => $refx,
      'linex' => $linex,
      'cdrefx' => $cdrefx,
      'cdlinex' => $cdlinex,
      'ref' => $ref,
      'stageid' => $stageid,
      'reqtrno' => $reqtrno,
      'reqline' => $reqline,
      'deptid' => $deptid,
      'suppid' => $suppid,
      'oraclecode' => $oraclecode,
      'svsno' => $svsno,
      'isexisted' => $isexisted,
      'ispa' => $ispa,
      'priolvl' => $priolvl,
      'poref' => $poref
    ];

    $datainfo = [
      'trno' => $trno,
      'line' => $line,
      'itemdesc' => $itemdesc,
      'specs' => $specs,
      'unit' =>  $unit,
      'rem' => $rem,
      'uom2' => $uom2,
      'ctrlno' => $ctrlno
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }

    foreach ($datainfo as $key => $value) {
      $datainfo[$key] = $this->othersClass->sanitizekeyfield($key, $datainfo[$key]);
    }

    if ($config['params']['data']['rrdate'] == null) {
      if ($config['params']['data']['isrr'] == 'true') {

        if ($priolvl > 0) {
          $prioom = $this->coreFunctions->opentable("select h.docno from omstock as s left join omhead as h on h.trno=s.trno where s.oraclecode='" . $oraclecode . "' and s.statid<>12 and s.priolvl<" . $priolvl . " order by s.priolvl limit 1");
          if (empty($prioom)) {
            $data['rrby'] = $config['params']['user'];
            $data['rrdate'] = $this->othersClass->getCurrentTimeStamp();
          } else {
            return ['status' => false, 'msg' => "Unable to mark receive for item " . $itemdesc . ", please check the priority for code " . $oraclecode . " in " .   $prioom[0]->docno];
          }
        } else {
          $data['rrby'] = $config['params']['user'];
          $data['rrdate'] = $this->othersClass->getCurrentTimeStamp();
        }

        $data['statid'] = 46;
      }
    }


    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    $datainfo['editdate'] = $current_timestamp;
    $datainfo['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];

      if ($isproject) {
        if ($data['stageid'] == 0) {
          $msg = 'Stage cannot be blank -' . $item[0]->barcode;
          return ['status' => false, 'msg' => $msg];
        }
      }

      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' Barcode:' .  $barcode .  ' Disc:' . $disc . ' WH:' . $wh . ' Ext:' . $ext);
        if ($data['refx'] != 0) {
          $this->setserveditems($data['refx'], $data['linex']);
        }

        $this->coreFunctions->sbcinsert('stockinfotrans', $datainfo);

        $this->loadheaddata($config);
        $row = $this->openstockline($config);
        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.', 'line' => $line, 'reloaddata' => true];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      $this->coreFunctions->sbcupdate('stockinfotrans', $datainfo, ['trno' => $trno, 'line' => $line]);


      if ($refx != 0) {
        if ($this->setserveditems($refx, $linex) === 0) {
          $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
          $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
          $this->setserveditems($refx, $linex);
          $return = false;
        }
      }


      return $return;
    }
  } // end function

  public function deleteallitem($config)
  {
    $isallow = true;
    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex,cdrefx,cdlinex,stageid,sorefx,solinex,osrefx,oslinex,reqtrno,reqline from ' . $this->stock . ' where trno=? and (refx<>0 or cdrefx<>0 or sorefx<>0 or osrefx<>0)', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=?', 'delete', [$trno]);

    foreach ($data as $key => $value) {
      if ($data[$key]->refx != 0) {
        $this->setserveditems($data[$key]->refx, $data[$key]->linex);
      }
      $this->coreFunctions->execqry("update hstockinfotrans set osiref2='' where trno=" . $data[$key]->reqtrno . " and line=" . $data[$key]->reqline);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['line'] = $config['params']['row']['line'];
    $config['params']['stageid'] = $config['params']['row']['stageid'];
    $data = $this->openstockline($config);

    $trno = $config['params']['trno'];
    $line = $config['params']['line'];
    $qry = "delete from " . $this->stock . " where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$trno, $line]);
    $this->coreFunctions->execqry('delete from stockinfotrans where trno=? and line=?', 'delete', [$trno, $line]);

    if ($data[0]->refx !== 0) {
      $this->setserveditems($data[0]->refx, $data[0]->linex);
    }

    $data = json_decode(json_encode($data), true);
    $this->coreFunctions->execqry("update hstockinfotrans set osiref2='' where trno=" . $data[0]['reqtrno'] . " and line=" . $data[0]['reqline']);
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $line . ' Barcode:' . $data[0]['barcode'] . ' Qty:' . $data[0]['rrqty'] . ' Amt:' . $data[0]['rrcost'] . ' Disc:' . $data[0]['disc'] . ' WH:' . $data[0]['wh'] . ' Ext:' . $data[0]['ext']);
    return ['status' => true, 'msg' => 'Item was successfully deleted.'];
  } // end function

  public function setserveditems($refx, $linex)
  {
    $qry1 = "select stock." . $this->hqty . " from omhead as head left join omstock as
    stock on stock.trno=head.trno where head.doc='om' and stock.refx=" . $refx . " 
    and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select stock." . $this->hqty . " from homhead as head
    left join homstock as stock on stock.trno=head.trno 
    where head.doc='om' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty === '') {
      $qty = 0;
    }

    return $this->coreFunctions->execqry("update hoqstock set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');

    return true;
  }



  // start
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
    $config['params']['trno'] = $config['params']['dataid'];
    $isposted = $this->othersClass->isposted($config);
    $printed = $this->coreFunctions->getfieldvalue($isposted ? "hheadinfotrans" :  "headinfotrans", "printdate", "trno=?", [$config['params']['dataid']]);
    $statid = $this->othersClass->getstatid($config);

    $this->logger->sbcviewreportlog($config);
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    $this->coreFunctions->execqry("update headinfotrans set printdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=" . $config['params']['dataid'] . " and printdate is null");
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
  // end


  public function forreceiving($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 47], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD',   'TAGGED FOR RECEIVING');
    $this->logger->sbcstatlog($trno, $config, 'HEAD',   'TAGGED FOR RECEIVING');

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function forso($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
    }

    $pending = $this->coreFunctions->opentable("select trno from omstock where trno=? and rrdate is null", [$trno]);
    if (!empty($pending)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Please mark all items as received.'];
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 46], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD',   'TAGGED FOR SO');
    $this->logger->sbcstatlog($trno, $config, 'HEAD',   'TAGGED FOR SO');

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }

  public function forposting($config)
  {
    $trno = $config['params']['trno'];
    $msg = "";
    $status = true;

    if ($this->othersClass->isposted2($trno, $this->tablenum)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Transaction has already been posted.'];
    }

    $pendingso = $this->coreFunctions->opentable("select trno from omstock where trno=? and sono=''", [$trno]);
    if (!empty($pendingso)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Please input SO#'];
    }

    $this->coreFunctions->sbcupdate($this->tablenum, ['statid' => 39], ['trno' => $trno]);
    // $this->logger->sbcwritelog($trno, $config, 'HEAD',   'TAGGED FOR POSTING');
    $this->logger->sbcstatlog($trno, $config, 'HEAD',   'TAGGED FOR POSTING');

    return ['trno' => $trno, 'status' => $status, 'msg' => $msg, 'backlisting' => true];
  }
} //end class
