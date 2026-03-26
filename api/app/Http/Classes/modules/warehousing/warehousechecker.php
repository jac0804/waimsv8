<?php

namespace App\Http\Classes\modules\warehousing;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\SBCPDF;
use Milon\Barcode\DNS1D;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class warehousechecker
{

  public $modulename = 'WAREHOUSE CHECKER';
  public $gridname = 'inventory';

  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $stock = 'lastock';

  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';

  private $fields = ['checkerid', 'checkerlocid'];

  public $transdoc = "'SD', 'SE', 'SF', 'SH'";

  private $btnClass;
  private $fieldClass;
  private $tabClass;

  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;

  public $showfilteroption = true;
  public $showfilter = false;
  public $showcreatebtn = false;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
    ['val' => 'assigned', 'label' => 'Assigned', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Checked', 'color' => 'primary'],
    ['val' => 'complete', 'label' => 'Completed', 'color' => 'primary']
  ];

  private $reporter;
  private $barcode;

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
    $this->barcode = new  DNS1D;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2030,
      'view' => 2030,
      'edit' => 2030,
      'save' => 2030,
      'print' => 2030
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $lblstatus = 1;
    $statname = 2;
    $checkerloc = 3;
    $listdocument = 4;
    $clientname = 5;
    $agentname = 6;
    $rem = 7;
    $ref = 8;
    $transtype = 9;
    $pickerend = 10;


    $getcols = ['action', 'lblstatus', 'statname', 'checkerloc', 'listdocument', 'clientname', 'agentname', 'rem', 'ref', 'transtype', 'pickerend'];
    $stockbuttons = ['view', 'showcheckerreplacement', 'showstockitems_whchecker'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$lblstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$agentname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';

    $cols[$clientname]['label'] = 'Name';
    $cols[$rem]['label'] = 'Remarks';
    $cols[$ref]['label'] = 'SO #';

    $cols[$action]['btns']['showcheckerreplacement']['checkfield'] = "added";
    return $cols;
  }

  public function paramsdatalisting($config)
  {
    $fields = ['sjtype'];
    $col1 = $this->fieldClass->create($fields);

    $data = $this->coreFunctions->opentable("select '' as sjtype");
    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1]];
  }

  public function loaddoclisting($config)
  {
    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];

    if ($userid == 0) {
      return ['data' => [], 'status' => false, 'msg' => 'Sorry, you`re not allowed to create transaction. Please setup first your Employee Code.'];
    }

    $qry = $this->selectqry($config);

    $option = $config['params']['itemfilter'];
    switch ($option) {
      case 'assigned':
        $qry .= " and num.status='CHECKER: ON-PROCESS' and num.status<>'VOID' and ci.checkerid=" . $userid;
        break;

      case 'posted':
        $qry .= " and num.status='CHECKER: DONE' and num.status<>'VOID' and ci.checkerid=" . $userid;
        break;

      case 'complete':
        $qry .= " and num.postdate is not null and num.status<>'VOID' and ci.checkerid=" . $userid;
        break;

      default:
        $qry .= " and num.status='PICKED' and num.status<>'VOID' and (ifnull(ci.checkerid,0)=0 or ci.checkerid=" . $userid . ")";
        break;
    }

    $qry .= " order by ifnull(psort,99), stat, crtldate";


    $data = $this->coreFunctions->opentable($qry, [$center]);
    $data = $this->othersClass->updatetranstype($data);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.', 'qer' => $qry];
  }


  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'print',
      'edit',
      'save',
      'cancel',
      'post',
      'unpost',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createHeadField($config)
  {
    $fields = ['client', 'ourref'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.class', 'docno sbccsreadonly');
    data_set($col1, 'client.label', 'Document No.');
    data_set($col1, 'ourref.label', 'Ref No.');

    $fields = ['checkerloc', 'newchecker'];
    $col2 = $this->fieldClass->create($fields);


    $fields = ['rcvecheckerloc'];
    $col3 = $this->fieldClass->create($fields);


    $fields = ['checkerdone', 'postwhclr'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'postwhclr.label', 'FOR DISPATCHING');
    data_set($col4, 'postwhclr.confirmlabel', 'Proceed for Dispatching?');
    data_set($col4, 'postwhclr.action', 'post');
    data_set($col4, 'postwhclr.access', 'view');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }


  public function createTab($config)
  {
    $replaceqty = 0;
    $barcode = 1;
    $itemdesc = 2;
    $isqty = 3;
    $uom = 4;

    $tab = [
      'multigrid' => ['action' => 'warehousingentry', 'lookupclass' => 'entrywhchecker', 'label' => 'Items'],
      'multigrid2' => ['action' => 'warehousingentry', 'lookupclass' => 'viewboxdetail', 'label' => 'BOX DETAIL']
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    return $obj;
  }

  public function lookupsetup($config)
  {
    return $this->warehousinglookup->lookupwhrem($config);
  }

  public function createtabbutton($config)
  {

    $tbuttons = ['openbox', 'reopenbox'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['checker'] = '';
    $data[0]['checkerid'] = 0;
    $data[0]['checkerloc'] = '';
    $data[0]['checkerlocid'] = 0;
    $data[0]['newchecker'] = '';
    $data[0]['newcheckerid'] = 0;
    $data[0]['ourref'] = '';
    return $data;
  }

  private function selectqry($config)
  {
    $docfilter = $this->transdoc;
    $sjdoc = isset($config['params']['doclistingparam']['sjtype']['value']) ? $config['params']['doclistingparam']['sjtype']['value'] : "";

    if ($sjdoc != "") {
      $docfilter = "'" . $sjdoc . "'";
    }

    $tablehead = $this->head;
    $tablecninfo = 'cntnuminfo';
    $tablestock = 'lastock';
    $agentleftjoin = 'ag.client = head.agent';
    $checkerdate = ' and ci.checkerdate is null';

    $trno = isset($config['params']['clientid']) ? $config['params']['clientid'] : 0;
    if ($trno > 0) {
      $isposted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);
      if ($isposted) {
        goto postedhere;
      }
    }

    if (isset($config['params']['itemfilter'])) {
      if ($config['params']['itemfilter'] == 'complete') {
        postedhere:
        $tablehead = 'glhead';
        $tablecninfo = 'hcntnuminfo';
        $tablestock = 'glstock';
        $agentleftjoin = 'ag.clientid = head.agentid';
        $checkerdate = '';
      }
    }



    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'client.clientname'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select head.trno, head.doc, '' as transtype, head.trno as clientid, head.docno, head.docno as client,head.clientname, left(head.dateid,10) as dateid,
        ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, num.crtldate, num.status as stat, 0 as newcheckerid,
        ci.checkerlocid, ci.checkerid, concat(head.rem,'\\n',if(ifnull((select count(trno) from replacestock where trno=head.trno and isaccept=0), 0)<>0,'FOR REPLACEMENT','')) as rem,
        (case when ci.checkerdone is not null then 'true' when ci.checkerid<>0 then 'false' else 'true' end) as added, 
        head.ourref, ifnull((select group_concat(distinct ref) from lastock where lastock.trno=head.trno),'') as ref, ifnull(stat.status,'') as statname, stat.psort,
        ifnull(ag.clientname, '') as agentname, num.postdate,
        ifnull((select date_format(s.pickerend,'%m/%d/%Y %H:%i') from " . $tablestock . " as s where s.trno=head.trno order by pickerend desc limit 1),'') as pickerend
        from " . $tablehead . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join " . $tablecninfo . " as ci on ci.trno=head.trno 
        left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        left join trxstatus as stat on stat.line=head.statid
        left join client as ag on $agentleftjoin
        where  head.doc in (" . $docfilter . ") and num.center = ?
        and head.lockdate is not null and num.crtldate is not null " . $checkerdate . " " . $filtersearch;
    return $qry;
  }

  public function loadheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];

    $qry = $this->selectqry($config);
    $qry .= " and head.trno=?";

    $head = $this->coreFunctions->opentable($qry, [$center, $trno]);
    if (!empty($head)) {
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $hidetabbtn = ['btnopenbox' => false, 'btnreopenbox' => false];

      $isposted = false;
      $posted = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=?", [$trno]);
      if ($posted) {
        $isposted = true;
        $hideobj = ['rcvecheckerloc' => true, 'checkerdone' => true, 'postwhclr' => true];
        $hidetabbtn = ['btnopenbox' => true, 'btnreopenbox' => true];
      } else {

        $checkerlocdate = null;
        $checkerdone = null;
        $cntnuminfo = $this->coreFunctions->opentable("select checkerrcvdate, checkerdone from cntnuminfo where trno=?", [$trno]);
        if ($cntnuminfo) {
          $checkerlocdate = $cntnuminfo[0]->checkerrcvdate;
          $checkerdone = $cntnuminfo[0]->checkerdone;
        }

        if ($checkerlocdate) {
          $hideobj = ['rcvecheckerloc' => true];
        } else {
          $hideobj = ['rcvecheckerloc' => false];
        }

        if ($checkerdone) {
          $hideobj['checkerdone'] = true;
          $hideobj['postwhclr'] = false;
        } else {
          $hideobj['checkerdone'] = false;
          $hideobj['postwhclr'] = true;
        }
      }


      $stock = $this->openstock($config);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => $isposted, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj, 'hidetabbtn' => $hidetabbtn];
    } else {
      $hideobj = ['rcvecheckerloc' => true, 'postwhclr' => false];
      $head = $this->resetdata();
      return ['status' => false, 'griddata' => [], 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function openstock($config)
  {
    $trno = $config['params']['clientid'];
    $qry = "select item.barcode, item.itemname as itemdesc,
        round(stock.isqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
        stock.uom, 0 as whremid, '' as whrem, 0 as replaceqty, 
        '' as bgcolor
        from lastock as stock 
        left join item on item.itemid=stock.itemid 
        where stock.trno=? and stock.void=0";
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function updatehead($config, $udpate)
  {
    $head = $config['params']['head'];
    $trno  = $head['clientid'];
    $data = [];

    $msg = '';

    $data['checkerlocid'] = $head['checkerlocid'];

    if ($head['newcheckerid'] != 0) {
      $data['checkerid'] = $head['newcheckerid'];
    }

    $sjdoc = $this->coreFunctions->getfieldvalue("lahead", "doc", "trno=?", [$trno]);
    if ($sjdoc != 'SE') {
      if ($head['ourref'] == "") {
        return ['status' => false, 'msg' => 'Ref No. is Required', 'clientid' => $trno];
      }
    }

    $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);
    $this->coreFunctions->sbcupdate('lahead', ['ourref' => $head['ourref']], ['trno' => $trno]);

    return ['status' => true, 'msg' => 'Successfully updated', 'clientid' => $trno, 'backlisting' => true];
  } // end function

  public function stockstatusposted($config)
  {
    $userid = $config['params']['adminid'];

    switch ($config['params']['action']) {
      case 'getboxno':
        $ispick = $this->coreFunctions->datareader("select  ifnull(checkerrcvdate,'') as value from cntnuminfo where trno=?", [$config['params']['trno']]);
        if ($ispick === '') {
          return ['status' => false, 'msg' => 'Please click the PICK FROM LOCATION button first to proceed.'];
        }
        $pendingreplacement = $this->coreFunctions->datareader("select count(trno) as value from replacestock where isaccept=0 and trno=?", [$config['params']['trno']]);
        if ($pendingreplacement) {
          return ['status' => false, 'msg' => 'Please accept all items for replacement.'];
        }

        $ourref = $this->coreFunctions->getfieldvalue("lahead", "ourref", "trno=?", [$config['params']['trno']]);
        if ($ourref == '') {
          $sjdoc = $this->coreFunctions->getfieldvalue("lahead", "doc", "trno=?", [$config['params']['trno']]);
          if ($sjdoc != 'SE') {
            return ['status' => false, 'msg' => 'DR No. is required.'];
          }
        }

        $qry = "select sum(bal) as bal, sum(scanqty) as scanqty from ( select round(sum(stock.isqty),2) as bal,
                (select sum(box.qty) from boxinginfo as box where box.trno=stock.trno and box.itemid=stock.itemid) as scanqty
                from lastock as stock where stock.trno=? group by stock.trno,stock.itemid) as x";
        $item = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        if (!empty($item)) {
          if ($item[0]->bal == $item[0]->scanqty) {
            return ['status' => false, 'msg' => 'Cannot continue, all items for this transaction already tag in the box...'];
          }
        }

        $qry = "select ifnull(boxno,0) as boxno from boxinginfo where trno = ? order by boxno desc";
        $box = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        if (!empty($box)) {
          $no = $box[0]->boxno + 1;
          return ['status' => true, 'msg' => $no . ' Boxes', 'box' => $no];
        } else {
          return ['status' => true, 'msg' => '1 Box', 'box' => 1];
        }
        break;

      case 'checkerdone':
        $result = $this->checkifreceivedfromlocation($config);
        if (!$result['status']) {
          return $result;
        }

        $result = $this->checkreplacement($config);
        if (!$result['status']) {
          return $result;
        }

        $result = $this->checkpendingpicker($config);
        if (!$result['status']) {
          return $result;
        }

        $result = $this->checkitembox($config);
        if (!$result['status']) {
          return $result;
        }

        $trno = $config['params']['clientid'];
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['checkerdone'] = $current_timestamp;
        $data['status'] = 'CHECKER: DONE';
        $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);

        $this->coreFunctions->execqry("update cntnum set status='CHECKER: DONE' where trno=" . $trno);
        $hideobj = ['rcvecheckerloc' => true, 'checkerdone' => true, 'postwhclr' => false];

        return ['status' => true, 'msg' => 'Ready for dispatch', 'hideobj' => $hideobj];

        break;

      case 'scanbarcode':
        $qry = "select item.itemid,item.barcode,item.itemname,stock.uom,round(sum(stock.isqty),2) as bal,
                (select ifnull(sum(box.qty),0) from boxinginfo as box where box.trno=stock.trno and box.itemid=stock.itemid) as scanqty
                 from lastock as stock left join item on item.itemid=stock.itemid
                        where stock.trno=? and item.barcode=? group by item.itemid,item.barcode,item.itemname,stock.uom,stock.trno,stock.itemid";
        $item = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['barcode']]);
        if (!empty($item)) {
          return ['status' => true, 'msg' => 'Barcode Found...', 'data' => $item];
        } else {
          return ['status' => false, 'msg' => 'NO Barcode Found...'];
        }
        break;

      case 'receivecheckerloc':
        $trno = $config['params']['clientid'];

        $ispick = $this->coreFunctions->datareader("select ifnull(checkerrcvdate,'') as value from cntnuminfo where trno=?", [$trno]);
        if ($ispick !== '') {
          return ['status' => false, 'msg' => 'Already picked from location'];
        }

        $result = $this->checkpendingpicker($config);
        if (!$result['status']) {
          return $result;
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['checkerrcvdate'] = $current_timestamp;
        $data['status'] = 'CHECKER: ON-PROCESS';
        $data['checkerid'] = $userid;

        $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("update cntnum set status='CHECKER: ON-PROCESS' where trno=" . $trno);
        $hideobj = ['rcvecheckerloc' => true, 'checkerdone' => false, 'postwhclr' => true];

        return ['status' => true, 'msg' => 'Successfully updated.', 'hideobj' => $hideobj];
        break;

      case 'additeminbox':
        $qryboxcount = "select ifnull(max(boxno),0) as value from boxinginfo where trno=?";

        $config['params']['groupid'] = $this->othersClass->val($config['params']['groupid']);
        if ($config['params']['groupid'] == 0) {
          $data['trno'] = $config['params']['trno'];
          $data['itemid'] = $config['params']['itemid'];
          $data['qty'] = $config['params']['qty'];
          $data['boxno'] = $config['params']['boxno'];
          $data['groupid'] = $config['params']['boxno'];
          $data['groupid2'] = 1;
          $this->coreFunctions->sbcinsert('boxinginfo', $data);

          $box_count = $this->coreFunctions->datareader($qryboxcount, [$config['params']['trno']]);
          if ($box_count == '') {
            $box_count = 0;
          }

          $this->coreFunctions->sbcupdate("cntnuminfo", ['boxcount' => $box_count], ['trno' => $config['params']['trno']]);

          return ['status' => true, 'msg' => '1. Successfully updated.', 'boxno' => $config['params']['boxno'], 'boxcount' => $box_count];
        } else {

          $boxno = $config['params']['boxno'];

          $qtybal = $this->coreFunctions->datareader("select ifnull(sum(isqty),0) as value from lastock where trno=? and itemid=?", [$config['params']['trno'], $config['params']['itemid']]);
          $qtybox = $this->coreFunctions->datareader("select ifnull(sum(qty),0) as value from boxinginfo where trno=? and itemid=?", [$config['params']['trno'], $config['params']['itemid']]);

          $encodedqty = $config['params']['qty'] * $config['params']['groupid'];

          if (($qtybal - $qtybox) != $encodedqty) {
            return ['status' => false, 'msg' => 'Total quantity per box not tally. Available qty: ' . ($qtybal - $qtybox) . ', Encoded qty: ' . $encodedqty, 'boxno' => $boxno];
          }

          for ($i = 1; $i <= intval($config['params']['groupid']); $i++) {
            $qry = "select item.itemid,item.barcode,item.itemname,stock.uom,round(sum(stock.isqty),2) as bal,
                        (select sum(box.qty) from boxinginfo as box where box.trno=stock.trno and box.itemid=stock.itemid) as scanqty
                         from lastock as stock left join item on item.itemid=stock.itemid
                                where stock.trno=? and item.itemid=? group by item.itemid,item.barcode,item.itemname,stock.uom,stock.trno,stock.itemid";
            $item = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['itemid']]);
            if ($item[0]->bal == $item[0]->scanqty) {
              return ['status' => false, 'msg' => 'Cannot continue, all Qty of ' . $item[0]->itemname . ' for this transaction already tag in the box...', 'boxno' => $boxno];
            }
            $data['trno'] = $config['params']['trno'];
            $data['itemid'] = $config['params']['itemid'];
            $data['qty'] = $config['params']['qty'];
            $data['boxno'] = $boxno;
            $data['groupid'] = $config['params']['boxno'];
            $data['groupid2'] = $config['params']['groupid'];
            $this->coreFunctions->sbcinsert('boxinginfo', $data);
            $boxno = $boxno + 1;
          }

          $box_count = $this->coreFunctions->datareader($qryboxcount, [$config['params']['trno']]);
          if ($box_count == '') {
            $box_count = 0;
          }

          $this->coreFunctions->sbcupdate("cntnuminfo", ['boxcount' => $box_count], ['trno' => $config['params']['trno']]);

          return ['status' => true, 'msg' => '2. Successfully updated.', 'boxno' => $boxno, 'boxcount' => $box_count];
        }
        break;

      case 'post':
        $trno = $config['params']['clientid'];

        $result = $this->checkifreceivedfromlocation($config);
        if (!$result['status']) {
          return $result;
        }

        $result = $this->checkreplacement($config);
        if (!$result['status']) {
          return $result;
        }

        $result = $this->checkitembox($config);
        if (!$result['status']) {
          return $result;
        }

        $result = $this->checkpendingpicker($config);
        if (!$result['status']) {
          return $result;
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['checkerdate'] = $current_timestamp;
        $data['checkerby'] = $config['params']['user'];
        $data['status'] = 'FOR DISPATCH';
        $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);

        $this->coreFunctions->execqry("update cntnum set status='FOR DISPATCH' where trno=" . $trno);

        return ['status' => true, 'msg' => 'Proceed to Dispatching...'];
        break;

      case 'scanboxno':
        $trno = $config['params']['clientid'];
        if (count($config['params']['arrparams']) != 0) {
          if (count($config['params']['arrparams']) == 1) {
            $barcode = $config['params']['barcode'];
            $sanboxno_ = $config['params']['arrparams'][0];
            $reopnboxno = explode("-", $sanboxno_);

            if (count($reopnboxno) == 2) {
              $boxno = $reopnboxno[1];

              $box_exist =  $this->coreFunctions->datareader("select trno as value from boxinginfo where trno=? and boxno=? limit 1", [$trno, $boxno]);
              if ($box_exist) {

                $itemid = $this->coreFunctions->datareader("select itemid as value from item where barcode=?", [$barcode]);
                $qry = "select ifnull((qty-scanqty),0) as value from (
                  select stock.itemid, sum(stock.isqty) as qty,
                  (select ifnull(sum(box.qty),0) from boxinginfo as box where box.trno=stock.trno and box.itemid=stock.itemid) as scanqty
                  from lastock as stock left join item on item.itemid=stock.itemid 
                  where stock.trno=? and stock.itemid=? group by stock.itemid, stock.trno
                  ) as i where i.qty<>i.scanqty";

                $pendingqty = $this->coreFunctions->datareader($qry, [$trno, $itemid]);

                if ($pendingqty != 0) {
                  $boxinfo = $this->coreFunctions->opentable("select groupid, groupid2 from boxinginfo where trno=? and boxno=? limit 1", [$trno, $boxno]);
                  if ($boxinfo) {
                    $data = [
                      'trno' => $trno,
                      'itemid' => $itemid,
                      'qty' => $pendingqty,
                      'boxno' => $boxno,
                      'groupid' => $boxinfo[0]->groupid,
                      'groupid2' => $boxinfo[0]->groupid2
                    ];
                    $this->coreFunctions->sbcinsert("boxinginfo", $data);

                    $this->logger->sbcwritelog($trno, $config, 'CHECKER', 'Re-open Box ' . $sanboxno_ . ', Item: ' . $barcode);

                    return ['status' => true, 'msg' => 'Successfully add to box ' . $sanboxno_];
                  }
                } else {
                  return ['status' => false, 'msg' => 'Invalid quantity.'];
                }
              } else {
                return ['status' => false, 'msg' => 'Box no. does not exist.'];
              }
            } else {
              return ['status' => false, 'msg' => 'Invalid box no.'];
            }
          } else {
            return ['status' => false, 'msg' => 'Invalid scanned data.'];
          }
        } else {
          return ['status' => true, 'msg' => 'The box number was successfully scanned.', 'action' => 'rescan', 'title' => 'Scan Barcode', 'addedaction' => ['action' => 'lookupreopenboxitems', 'lookupclass' => 'reopenboxitems']];
        }
        break;

      case 'printing':
        return $this->reportboxsetup($config);
        break;
    }
  } // end function

  private function checkifreceivedfromlocation($config)
  {
    $trno = $config['params']['clientid'];
    $ispick = $this->coreFunctions->datareader("select  ifnull(checkerrcvdate,'') as value from cntnuminfo where trno=?", [$trno]);
    if ($ispick === '') {
      return ['status' => false, 'msg' => 'Please click the PICK FROM LOCATION button first to proceed.'];
    } else {
      return ['status' => true];
    }
  }

  private function checkreplacement($config)
  {
    $trno = $config['params']['clientid'];
    $isreplacement = $this->coreFunctions->datareader("select trno as value from replacestock where trno=? and isaccept=0 limit 1", [$trno]);
    if ($isreplacement) {
      return ['status' => false, 'msg' => 'Cannot proceed, items tagged for replacement are not yet accepted by the checker.'];
    } else {
      return ['status' => true];
    }
  }

  private function checkpendingpicker($config)
  {
    $trno = $config['params']['clientid'];
    $ispick = $this->coreFunctions->datareader("select trno as value from lastock where trno=? and pickerend is null limit 1", [$trno]);
    if ($ispick) {
      return ['status' => false, 'msg' => 'Cannot proceed, not yet complete by picker'];
    } else {
      return ['status' => true];
    }
  }

  private function checkitembox($config)
  {
    $trno = $config['params']['clientid'];
    $qry = "select sum(bal) as bal, sum(scanqty) as scanqty from ( select round(sum(stock.isqty),2) as bal,
    (select ifnull(sum(box.qty),0) from boxinginfo as box where box.trno=stock.trno and box.itemid=stock.itemid) as scanqty
    from lastock as stock where stock.trno=? group by stock.trno,stock.itemid) as x";
    $item = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($item)) {
      if ($item[0]->scanqty < $item[0]->bal) {
        return ['status' => false, 'msg' => 'Cannot continue, some items for this transaction not yet tag in the box...'];
      }
    }

    return ['status' => true];
  }

  public function reportboxsetup($config)
  {
    $txtfield = $this->createreportboxfilter($config);
    $txtdata = $this->reportparamsboxdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  } //end function

  public function reportsetup($config)
  {
    $txtfield = $this->createreportboxfilter($config);
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  } //end function

  public function createreportboxfilter($config)
  {
    $boxno = isset($config['params']['boxno']) ? $config['params']['boxno'] : 'default';

    $trno = $config['params']['trno'];
    $checkclient = $this->coreFunctions->datareader("select client as value from lahead where trno=?", [$trno]);

    switch ($boxno) {
      case 'default': // header button print

        $trno = $config['params']['trno'];
        $checkerdone = $this->coreFunctions->datareader("select ifnull(checkerdone,'') as value from cntnuminfo where trno=?", [$trno]);

        $fields = [['radioprint', 'radioreporttype'], 'approved', 'received', 'boxno', 'print'];

        $col1 = $this->fieldClass->create($fields);
        $arroption = [];

        if ($checkclient == "OS0000000000001") {
          array_push($arroption, ['label' => 'DR', 'value' => '0', 'color' => 'orange']);
          array_push($arroption, ['label' => 'CI', 'value' => 'ci', 'color' => 'orange']);
        } else {
          if ($checkerdone !== '') {
            array_push($arroption, ['label' => 'Default', 'value' => '0', 'color' => 'orange']);
          }
        }

        array_push($arroption, ['label' => 'Packing List Label', 'value' => '1', 'color' => 'orange']);
        array_push($arroption, ['label' => 'Print Label by Box No.', 'value' => 'boxno', 'color' => 'orange']);
        array_push($arroption, ['label' => 'Print Label by SKU', 'value' => 'sku', 'color' => 'orange']);


        data_set($col1, 'radioreporttype.options', $arroption);
        data_set($col1, 'boxno.type', 'input');
        data_set($col1, 'boxno.readonly', false);
        data_set($col1, 'boxno.label', 'BOX No. / SKU');

        break;
      default: // per box print
        $fields = ['radioreportlabeltype', 'boxno', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'boxno.type', 'input');
        data_set($col1, 'boxno.readonly', false);
        break;
    }

    return array('col1' => $col1);
  }

  public function reportparamsboxdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        'boxno' as printlabeltype,
        '" . $config['params']['boxno'] . "' as boxno"
    );
  }

  public function reportparamsdata()
  {
    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '' as approved,
        '' as received,
        '' as boxno,
        '0' as reporttype"
    );
  }

  public function reportdata($config)
  {
    // orientations: portrait=p, landscape=l
    // formats: letter, a4, legal
    // layoutsize: reportWidth
    $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

    $reporttype = '';

    if (isset($config['params']['dataparams']['reporttype'])) {
      $reporttype = $config['params']['dataparams']['reporttype'];
    } else {
      $reporttype = isset($config['params']['dataparams']['printlabeltype']) ? $config['params']['dataparams']['printlabeltype'] : '';
    }

    switch ($reporttype) {
      case '0':
      case 'ci':

        $checkingdoc = $this->coreFunctions->datareader("select doc as value from lahead where trno = '" . $config['params']['dataid'] . "' union all select doc as value from glhead where trno = '" . $config['params']['dataid'] . "'");

        switch ($checkingdoc) {
          case 'SD':
          case 'SE':
          case 'SF':
          case 'SH':
            $data = $this->report_default_print_query($config);
            break;
        }
        break;
      case '1':
        $data = $this->report_packing_list_query($config);
        break;

      case 'boxno':
      case 'sku':
        $data = $this->report_default_box_query($config);
        break;
    }

    $str = $this->buildboxreport($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $reportParams];
  }

  private function report_default_print_query($config)
  {
    $trno = $config['params']['dataid'];
    $checkingdoc = $this->coreFunctions->datareader("select doc as value from lahead where trno = '" . $config['params']['dataid'] . "' union all select doc as value from glhead where trno = '" . $config['params']['dataid'] . "'");

    if ($checkingdoc == 'SD') {
      $model = "left(model.model_name,10) as model_name";
    } else {
      $model = "model.model_name";
    }

    $query = "
        select head.trno, head.docno, item.itemname, item.barcode, stock.uom, 
        $model, stock.disc, stock.iss as qty, stock.ext, 
        stock.isamt as amt, cl.client, cl.clientname, cl.addr, cl.tel2, cl.bstyle, cl.tin,
        head.ourref, date(head.dateid) as dateid, head.address as shipto,
        TIME_FORMAT(head.createdate, '%h:%i') as time,
        ifnull(head.rem, '') as rem, item.partno, head.createby, item.color,
        head.terms, head.tax, head.customername, stock.isamt,
        ifnull(agent.client,'') as agent,
         ifnull(agent.clientname,'') as agentname,'' as dagentname,
        ifnull(forwarder.clientname, '') as forwarder,
         ifnull(numinfo.truckid, 0) as truckid
        from lahead as head
        left join lastock as stock on stock.trno = head.trno
        left join cntnuminfo as numinfo on numinfo.trno = head.trno
        left join item as item on item.itemid = stock.itemid
        left join client as cl on cl.client = head.client
        left join model_masterfile as model on model.model_id = item.model
        left join client as agent on agent.client = head.agent
        left join client as forwarder on forwarder.clientid = numinfo.truckid
        where head.trno = '$trno'
      ";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  private function report_default_box_query($config)
  {
    $trno = $config['params']['dataid'];
    $boxno = $config['params']['dataparams']['boxno'];

    $reporttype = '';

    if (isset($config['params']['dataparams']['reporttype'])) {
      $reporttype = $config['params']['dataparams']['reporttype'];
    } else {
      $reporttype = isset($config['params']['dataparams']['printlabeltype']) ? $config['params']['dataparams']['printlabeltype'] : '';
    }

    switch ($reporttype) {
      case 'boxno':
        $query = "
        select head.trno, head.docno, item.itemname, item.barcode, model.model_name, sum(boxinfo.qty) as qty, boxinfo.boxno, cl.client, cl.clientname, cl.addr, 
        cl.tel2, head.ourref, head.dateid, ci.checkerdone,
        ifnull(cl.area, '') as area, ifnull(cl.province, '') as province
        from lahead as head
        left join boxinginfo as boxinfo on boxinfo.trno = head.trno
        left join item as item on item.itemid = boxinfo.itemid
        left join client as cl on cl.client = head.client
        left join model_masterfile as model on model.model_id = item.model
        left join cntnuminfo as ci on ci.trno=head.trno
        where head.trno = '$trno' and boxinfo.boxno='" . $boxno . "'
        group by head.trno, head.docno, item.itemname, item.barcode, model.model_name, boxinfo.boxno, 
        cl.client, cl.clientname, cl.addr, ci.checkerdone, cl.tel2, head.ourref, head.dateid,
        cl.area, cl.province";
        break;

      case 'sku':
        $query = "
        select head.trno, head.docno, item.itemname, item.barcode, model.model_name, boxinfo.qty, count(boxinfo.boxno) as boxcount, cl.client, cl.clientname, cl.addr, 
        cl.tel2, head.ourref, head.dateid, ci.checkerdone,
        ifnull(cl.area, '') as area, ifnull(cl.province, '') as province
        from lahead as head
        left join boxinginfo as boxinfo on boxinfo.trno = head.trno
        left join item as item on item.itemid = boxinfo.itemid
        left join client as cl on cl.client = head.client
        left join model_masterfile as model on model.model_id = item.model
        left join cntnuminfo as ci on ci.trno=head.trno
        where head.trno = '$trno' and item.barcode='" . $boxno . "'
        group by head.trno, head.docno, item.itemname, item.barcode, boxinfo.qty,
        model.model_name, cl.client, cl.clientname, cl.addr, ci.checkerdone, cl.tel2, head.ourref, head.dateid,
        cl.area, cl.province";
        break;
    }
    return json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  } //end fn

  private function report_packing_list_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "
      select
        box.trno, box.boxno, item.itemname, box.qty, head.createby,model.model_name,item.barcode
      from boxinginfo as box
      left join lahead as head on head.trno = box.trno
      left join item as item on item.itemid = box.itemid
      left join model_masterfile as model on model.model_id=item.model
      where box.trno='$trno' order by box.boxno";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  private function buildboxreport($config, $data, $boxno_ = '')
  {
    $boxno = isset($config['params']['dataparams']['boxno']) ? $config['params']['dataparams']['boxno'] : '';
    $reporttype = '';

    if (isset($config['params']['dataparams']['reporttype'])) {
      $reporttype = $config['params']['dataparams']['reporttype'];
    } else {
      $reporttype = isset($config['params']['dataparams']['printlabeltype']) ? $config['params']['dataparams']['printlabeltype'] : '';
    }
    switch ($reporttype) {
      case '0':
        $trno = $config['params']['dataid'];
        $checkerdone = $this->coreFunctions->datareader("select checkerdone as value from cntnuminfo where trno=?", [$trno]);
        if (!$checkerdone) {
          return "<b>Checker is not yet done!</b>";
        }

        $checkingdoc = $this->coreFunctions->datareader("select doc as value from lahead where trno = '" . $config['params']['dataid'] . "'");
        switch ($checkingdoc) {
          case 'SD': // SJ Dealer
            $str = $this->report_default_print_plotting($config, $data);
            break;
          case 'SE': // SJ Branch
            $str = $this->report_HTR_plotting($config, $data);
            break;
          case 'SF': // SJ Online
            $str = $this->report_ONLINE_plotting($config, $data);
            break;
          case 'SH': // special parts issuance
            $str = $this->report_SPR_plotting($config, $data);
            break;
        }
        break;

      case '1':
        $str = $this->report_packing_list_plotting($config, $data);
        break;

      case 'boxno':
        $str = $this->reportboxplotting($config, $data);
        break;

      case 'sku':
        $str = $this->reportboxplottingsku($config, $data);
        break;

      case 'ci':
        $str = $this->report_CI_plotting($config, $data);
        break;
    }
    return $str;
  }

  public function perbox_header($params, $data, $sku = false)
  {
    $logo = URL::to('/images/reports/mitsukoshilogo.png');
    $companyid = $params['params']['companyid'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    if (empty($data)) {
      return 'No data found';
    }

    $str = '';
    $font =  "Arial";
    $fontsize = "12";
    $border = "1px solid ";
    $layoutsize = '480';


    if ($sku) {
      $barcode = $this->coreFunctions->datareader("select item.barcode as value from boxinginfo as b left join item on item.itemid=b.itemid where b.trno=? and b.boxno=?", [$data[0]['trno'], $params['params']['dataparams']['boxno']]);
      $docno_boxno = $params['params']['dataparams']['boxno']; //$barcode;
      $boxno_label = "";
    } else {
      $qry = "
      select head.docno as value
      from lahead as head
      left join boxinginfo as boxinfo on boxinfo.trno = head.trno
      left join item as item on item.itemid = boxinfo.itemid
      where head.trno = '" . $data[0]['trno'] . "' and boxinfo.boxno='" . $data[0]['boxno'] . "'";
      $docno = $this->coreFunctions->datareader($qry);

      $qry = "
      select boxinfo.boxno as value
      from lahead as head
      left join boxinginfo as boxinfo on boxinfo.trno = head.trno
      left join item as item on item.itemid = boxinfo.itemid
      where head.trno = '" . $data[0]['trno'] . "' and boxinfo.boxno='" . $data[0]['boxno'] . "'";
      $boxno = $this->coreFunctions->datareader($qry);
      $docno_boxno = $docno . '-' . $boxno;
      $boxno_label = "<div style='width: 100px; height: 85px; margin-left: 230px; margin-top: 20px; border: 1px solid;'><span style='font-size: 60px;'>$boxno</span></div>";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($sku) {
      $str .= $this->reporter->col("<div style='margin-left: 350px;'>" . QrCode::size(120)->generate($docno_boxno) . "</div>", '100', null, false, $border, '', 'C', $font, '13', '', '', '');
    } else {
      $str .= $this->reporter->col("<div style='margin-left: 350px;'>" . QrCode::size(120)->generate($docno_boxno) . "</div>", '100', null, false, $border, '', 'C', $font, '13', '', '', '');
    }

    $str .= $this->reporter->endtable();

    $str .= "<div style='position:relative; margin-top: 0px;'>";
    $str .= "<div style='position:absolute; top: -20px; left: 173px;'>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($docno_boxno, '100', null, false, $border, '', 'C', $font, "10", 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "<div style='position:absolute; top: -130px; left: 35px;'>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($boxno_label, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "<div style='position:absolute; top: -120px; left: -130px;'>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mitsukoshi" width="180px" height ="70px">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "</div>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '', null, false, $border, '', 'L', $font, '20', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['tel2']) ? $data[0]['tel2'] : ''), '', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('NOTE! Do not accept if', '195', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $address = $data[0]['area'] . ', ' . $data[0]['province'];
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($address, '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('sealed is broken', '195', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['checkerdone']) ? date_format(date_create($data[0]['checkerdone']), 'M d, Y') : ''), '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', $layoutsize, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($sku) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("BOX", '50', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("Qty", '35', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("", '5', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("Description", '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("Model", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("", '10', null, false, $border, 'R', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("SKU", '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("Description", '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("Model", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("Qty", '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("", '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    return $str;
  }

  public function reportboxplotting($params, $data, $boxno = '')
  {
    if (empty($data)) {
      return '<center><b>No Data found for BOX NO./SKU ' . $params['params']['dataparams']['boxno'] . '</b></center>';
    }

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Arial";
    $fontsize = "12";
    $border = "1px solid ";
    $layoutsize = '480';

    $str .= "<div style = 'margin-left: -80px;'>";
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->perbox_header($params, $data);

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();


      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['model_name'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("", '10', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->perbox_header($params, $data);
        $str .= $this->reporter->endrow();

        $page = $page + $count;
      } //end if
    }
    $str .= $this->reporter->endtable();
    $str .= "<div style='height: 50px;'></div>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    $str .= "</div>";
    return $str;
  }

  public function reportboxplottingsku($params, $data, $boxno = '')
  {
    if (empty($data)) {
      return '<center><b>No Data found for BOX NO./SKU ' . $params['params']['dataparams']['boxno'] . '</b></center>';
    }

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Arial";
    $fontsize = "12";
    $border = "1px solid ";
    $layoutsize = '480';

    $str .= "<div style = 'margin-left: -80px;'>";
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->perbox_header($params, $data, true);

    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data[$i]['boxcount'], '50', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '35', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("", '5', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['model_name'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("", '10', null, false, $border, 'R', 'R', $font, $fontsize, 'B', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->perbox_header($params, $data);
        $str .= $this->reporter->endrow();

        $page = $page + $count;
      } //end if
    }
    $str .= $this->reporter->endtable();
    $str .= "<div style='height: 50px; border-left: 1px solid; border-right: 1px solid;'></div>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    $str .= "</div>";
    return $str;
  }


  public function packing_list_header($params, $data)
  {
    $logo = URL::to('/images/reports/mitsukoshilogo.png');
    $companyid = $params['params']['companyid'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $trno = $data[0]['trno'];
    $qry = "
        select head.trno, head.docno, head.client, head.clientname,
        head.terms, head.address, date(head.dateid) as dateid,
        item.itemid, item.itemname, boxinfo.boxno, boxinfo.qty,
        head.ourref
        from lahead as head
        left join boxinginfo as boxinfo on boxinfo.trno = head.trno
        left join item as item on item.itemid = boxinfo.itemid
        where head.trno = '$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";


    $str .= "<div style='position:relative; margin-top: 0px;'>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mitsukoshi" width="180px" height="80px" style="margin-left:-200px;">', '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= "<div style='position:absolute; top: 20px; left: -15px;'>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='margin-left: 230px; margin-top: 20px; border: 1px solid; padding: 5px;'>NOTE! Do not accept if sealed is broken.</div>", '10', null, false, '1px solid ', '', 'C', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['clientname']) ? $result[0]['clientname'] : ''), '', null, false, $border, '', 'L', $font, 20, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['address']) ? $result[0]['address'] : ''), '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['docno']) ? $result[0]['docno'] : ''), '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['ourref']) ? $result[0]['ourref'] : ''), '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['dateid']) ? date('M d, Y', strtotime($result[0]['dateid'])) : ''), '', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Box", '30', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("SKU", '80', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Description", '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Model", '90', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Qty" . '&nbsp&nbsp', '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function report_packing_list_plotting($params, $data)
  {

    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";

    if (empty($data)) {
      return 'No data found';
    }

    $str .= $this->reporter->beginreport('400');
    $str .= $this->packing_list_header($params, $data);

    $totalbox = 0;
    $totalqty = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data[$i]['boxno'], '30', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '80', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '150', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col((isset($data[$i]['model_name']) ? $data[$i]['model_name'] : ''), '90', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0) . '&nbsp&nbsp&nbsp', '50', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->packing_list_header($params, $data);
        $str .= $this->reporter->endrow();

        $page = $page + $count;
      } //end if

      $totalbox += $data[$i]['boxno'];
      $totalqty += $data[$i]['qty'];
    }

    $str .= $this->reporter->endtable();
    $str .= "<div style='height: 50px;'></div>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp&nbsp TOTAL BOX', '100', null, false, $border, 'B', 'LT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL QTY &nbsp&nbsp', '100', null, false, $border, 'B', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($totalbox, '30', null, false, $border, '', 'CT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalqty . '&nbsp&nbsp&nbsp', '50', null, false, $border, '', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("PACKING LIST INSIDE", '400', null, false, $border, 'T', 'C', $font, '32', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_header($params, $data)
  {
    $companyid = $params['params']['companyid'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "13";
    $border = "1px solid ";
    $str .= '<br>';

    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $clientname = isset($data[0]['clientname']) ? $data[0]['clientname'] : '';
    $str .= $this->reporter->col("<div style='margin-left: -110px'>" . strtoupper($clientname) . "</div>", '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $dateid = isset($data[0]['dateid']) ? $data[0]['dateid'] : '';
    $str .= $this->reporter->col("<div style='margin-left: -140px'>" . date("F d,Y", strtotime($dateid)) . "</div>", '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br>";


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '');
    $tin = isset($data[0]['tin']) ? $data[0]['tin'] : '';
    $str .= $this->reporter->col("<div style='margin-left: -100px'>" . $tin . "</div>", '510', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '');
    $addr = isset($data[0]['addr']) ? $data[0]['addr'] : '';
    $str .= $this->reporter->col("<div style='margin-left: -100px'>" . $addr . "</div>", '510', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '');
    $bstyle = isset($data[0]['bstyle']) ? $data[0]['bstyle'] : '';
    $str .= $this->reporter->col("<div style='margin-left: -90px'>" . $bstyle . "</div>", '510', null, false, $border, '', 'L', $font, $fontsize, '', '30px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function report_default_print_plotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $str = '';
    $count = 35;
    $page = 35;
    $font =  "century gothic";
    $fontsize = "13";
    $border = "1px solid ";

    if (empty($data)) {
      return 'No data found';
    }

    $qry = "select cl.clientname as checkby, truck.clientname as deliveryby
        from cntnuminfo as cntinfo
        left join client as cl on cl.clientid = cntinfo.checkerid
        left join client as truck on truck.clientid = cntinfo.truckid
        where cntinfo.trno = '" . $data[0]['trno'] . "'";

    $otherinfo = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $str .= $this->reporter->beginreport();
    $str .= "<div style='position: relative;letter-spacing: .5px'>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<div style='position: absolute;top:60px;'>";
    $str .= $this->default_header($params, $data);
    $str .= "<br>";
    $str .= "<br>";
    $str .= "</div>";


    $grandtotal = 0;
    $totalqty = 0;
    $str .= "<div style='position: absolute;top:280px;'>";
    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("<div style='margin-left: -70px'>" . $data[$i]['barcode'] . "</div>", '10', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['model_name'], '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp' . $data[$i]['itemname'], '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data[$i]['disc'], '40', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '65', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '45', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal) . '&nbsp&nbsp&nbsp&nbsp&nbsp', '60', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();


      $grandtotal += $data[$i]['ext'];
      $totalqty += $data[$i]['qty'];
    }
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "<div style='position: absolute; top: 895px;'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("<div style='margin-left: -70px;'>" . '&nbsp&nbsp' . $data[0]['rem'] . "</div>", '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '40', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, $decimal) . '&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp', '65', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->col(number_format($totalqty, 0) . '&nbsp&nbsp&nbsp&nbsp&nbsp', '45', null, false, $border, '', 'R', $font, '13', 'B', '', '');
    $str .= $this->reporter->col("", '60', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .= "<div style='position: absolute; top: 985px'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '410', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['checkby']) ? $otherinfo[0]['checkby'] : "", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['deliveryby']) ? $otherinfo[0]['deliveryby'] : "", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '230', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= $this->reporter->endtable();
    $str .= "</div>";
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function HTR_header($params, $data)
  {
    $str = "";

    $logo = URL::to('/images/reports/mitsukoshilogo.png');
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = "850";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('HO Transfer Receipt', '450', null, false, $border, 'B', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('222 E. Rodriguez Sr. Ave., Brgy. Kalusugan, Quezon City Philippines 1102', '450', null, false, $border, '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SHIP To: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(isset($data[0]['shipto']) ? $data[0]['shipto'] : "", '450', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('HTR No.: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(isset($data[0]['docno']) ? $data[0]['docno'] : "", '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('Date: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(isset($data[0]['dateid']) ? $data[0]['dateid'] : "", '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '450', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('Time: ', '75', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(isset($data[0]['time']) ? $data[0]['time'] : "", '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part No.', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SKU', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Description', '200', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Model', '100', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Qty', '50', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Retail Price', '75', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total', '75', null, false, $border, 'BTLR', 'C', $font, $fontsize, 'B', '', '');

    return $str;
  }

  public function report_HTR_plotting($params, $data)
  {
    $logo = URL::to('/images/reports/mitsukoshilogo.png');
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];


    $str = '';
    $count = 35;
    $page = 35;
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = "850";

    if (empty($data)) {
      return 'No data found';
    }

    $qry = "select cntinfo.receiveby as receivedby, date(cntinfo.checkerrcvdate) as receiveddate,
        cl.clientname as checkby, date(cntinfo.checkerdate) as checkdate, 
        TIME_FORMAT(cntinfo.checkerdate, '%h:%i') as checktime, truck.clientname as deliveryby
        from cntnuminfo as cntinfo
        left join client as cl on cl.clientid = cntinfo.checkerid
        left join client as truck on truck.clientid = cntinfo.truckid
        where cntinfo.trno = '" . $data[0]['trno'] . "'";
    $otherinfo = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $str .= $this->reporter->beginreport();

    // ORIGINAL COPY
    $str .= "<div style='position: relative; border: 2px solid; width: 825px; padding: 10px;'>";

    $str .= "<div style='position:absolute; right: -210px; top: 0px;'>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mitsukoshi" width="180px" height ="80px">', '10', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "</div>";
    $str .= $this->HTR_header($params, $data);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['partno'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['model_name'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '75', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '75', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

      $totalext += $data[$i]['ext'];
    }
    $str .= $this->reporter->endtable();


    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("
        <input type='checkbox' checked disabled> <span>Original Copy &nbsp&nbsp&nbsp
        <input type='checkbox' disabled> <span>Duplicate Copy", '450', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Remarks:", '150', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(isset($data[0]['rem']) ? $data[0]['rem'] : "", '150', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Prepared By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['createby'], '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Delivered By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($otherinfo[0]['deliveryby'], '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Received By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['receivedby']) ? $otherinfo[0]['receivedby'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Checked By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['checkby']) ? $otherinfo[0]['checkby'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Date Checked: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['checkdate']) ? $otherinfo[0]['checkdate'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Date Received: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['receiveddate']) ? $otherinfo[0]['receiveddate'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Time Checked: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['checktime']) ? $otherinfo[0]['checktime'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "<br>";

    // DUPLICATE COPY
    $str .= "<div style='position: relative; border: 2px solid; width: 825px; padding: 10px;'>";

    $str .= "<div style='position:absolute; right: -210px; top: 0px;'>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mitsukoshi" width="180px" height ="80px">', '10', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "</div>";

    $str .= "<div style='margin-top: 15px'></div>";
    $str .= $this->HTR_header($params, $data);
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['partno'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['model_name'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '75', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '75', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');

      $totalext += $data[$i]['ext'];
    }
    $str .= $this->reporter->endtable();


    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("
        <input type='checkbox' disabled> <span>Original Copy &nbsp&nbsp&nbsp
        <input type='checkbox' checked disabled> <span>Duplicate Copy", '450', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Remarks:", '150', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(isset($data[0]['rem']) ? $data[0]['rem'] : "", '150', null, false, $border, 'T', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Prepared By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($data[0]['createby'], '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Delivered By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($otherinfo[0]['deliveryby'], '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Received By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['receivedby']) ? $otherinfo[0]['receivedby'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Checked By: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['checkby']) ? $otherinfo[0]['checkby'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Date Checked: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['checkdate']) ? $otherinfo[0]['checkdate'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Date Received: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['receiveddate']) ? $otherinfo[0]['receiveddate'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("Time Checked: ", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(!empty($otherinfo[0]['checktime']) ? $otherinfo[0]['checktime'] : "", '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col("", '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_ONLINE_plotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = "850";
    $str = "";

    if (empty($data)) {
      return 'No data found';
    }

    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->beginreport();

    $str .= "<div style='position: relative;'>";
    $str .= $this->reporter->begintable('900');
    $str .= $this->reporter->startrow();

    if ($data[0]['bstyle'] == "Online Sale") {
      $str .= $this->reporter->col(isset($data[0]['customername']) ? $data[0]['customername'] : "", '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    } else {
      $str .= $this->reporter->col(isset($data[0]['clientname']) ? $data[0]['clientname'] : "", '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col(isset($data[0]['tin']) ? $data[0]['tin'] : "", '120', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['terms']) ? $data[0]['terms'] : "", '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['dateid']) ? $data[0]['dateid'] : "", '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(isset($data[0]['addr']) ? $data[0]['addr'] : "", '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['bstyle']) ? $data[0]['bstyle'] : "", '120', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br>";
    $str .= $this->reporter->begintable('900');
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '50', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col($data[$i]['itemname'], '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '150', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();

      $totalext += $data[$i]['ext'];
    }
    $str .= $this->reporter->endtable();

    $vatsales = 0;
    $vat = 0;
    if ($data[0]['tax'] != 0) {
      $vat = ($totalext / 1.12) * 0.12;
      $vatsales = $totalext / 1.12;
    }

    $str .= "<div style='position: absolute; top:410px; left: 850px;'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(number_format($vat, $decimal), '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("&nbsp", '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("&nbsp", '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(number_format($vatsales, $decimal), '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("&nbsp", '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "</div>";
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_SPR_plotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";
    $layoutsize = "850";
    $str = "";

    if (empty($data)) {
      return 'No data found';
    }

    $qry = "select cntinfo.receiveby as receivedby, date(cntinfo.checkerrcvdate) as receiveddate,
        cl.clientname as checkby, date(cntinfo.checkerdate) as checkdate, 
        TIME_FORMAT(cntinfo.checkerdate, '%h:%i') as checktime, truck.clientname as deliveryby
        from cntnuminfo as cntinfo
        left join client as cl on cl.clientid = cntinfo.checkerid
        left join client as truck on truck.clientid = cntinfo.truckid
        where cntinfo.trno = '" . $data[0]['trno'] . "'";
    $otherinfo = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";


    $str .= $this->reporter->beginreport();
    $str .= "<div style='position:relative;'>";

    $str .= "<div style='position:absolute; top: 70px;margin-left:-30px'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col(isset($data[0]['dateid']) ? $data[0]['dateid'] : "", '350', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "</div>";

    $str .= "<div style='position:absolute; top: 90px;margin-left:-50px'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '50', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col(isset($data[0]['clientname']) ? $data[0]['clientname'] : "", '750', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '50', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col("", '750', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";
    $str .= "<div style='position:absolute; top: 190px;'>";
    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("<div style='margin-left: -130px;'>" . number_format($data[$i]['qty'], 0) . "</div>", '50', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col("<div style='margin-left: -30px;'>" . $data[$i]['uom'] . "</div>", '50', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col("<div style='margin-left: -10px;'>" . $data[$i]['itemname'] . "</div>", '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col("<div style='margin-left: 50px;'>" . $data[$i]['color'] . "</div>", '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col("", '260', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "<div style='position:absolute; top: 600px;'>";
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col("", '410', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";
    $str .= "</div>";

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function report_CI_plotting($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $params['params']);

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $font =  "Century Gothic";
    $fontsize = "12";
    $border = "1px solid ";
    $layoutsize = "850";
    $str = "";

    if (empty($data)) {
      return 'No data found';
    }

    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";
    $str .= "<br>";

    $str .= $this->reporter->beginreport();

    $str .= "<div style='position: relative;'>";

    $str .= "<div style='position: absolute; top:-40px; left: 10px;'>";
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    if ($data[0]['bstyle'] == "Online Sale") {
      $str .= $this->reporter->col(isset($data[0]['customername']) ? $data[0]['customername'] : "", '300', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    } else {
      $str .= $this->reporter->col(isset($data[0]['clientname']) ? $data[0]['clientname'] : "", '300', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    }
    $str .= $this->reporter->col(isset($data[0]['docno']) ? $data[0]['docno'] : "", '70', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col(isset($data[0]['dateid']) ? $data[0]['dateid'] : "", '280', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(isset($data[0]['addr']) ? $data[0]['addr'] : "", '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "<br>";
    $str .= "</div>";

    $str .= "<div style='position: absolute; top:30px; left: 10px;'>";
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '100', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col("", '400', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col("SRP" . '&nbsp&nbsp&nbsp', '120', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col("", '160', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col("", '120', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .= "<div style='position: absolute; top:55px; left: 10px;'>";
    $str .= $this->reporter->begintable('1000');
    $totalext = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("<div style='margin-left: -160px;'>" . number_format($data[$i]['qty'], 0) . "</div>", '100', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col("<div style='margin-left: -60px;'>" . $data[$i]['itemname'] . "</div>", '400', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[$i]['isamt'], $decimal), '120', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[$i]['amt'], $decimal), '160', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->col(number_format($data[$i]['ext'], $decimal), '120', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
      $str .= $this->reporter->endrow();

      $totalext += $data[$i]['ext'];
    }
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $vatsales = 0;
    $vat = 0;
    if ($data[0]['tax'] != 0) {
      $vat = ($totalext / 1.12) * 0.12;
      $vatsales = $totalext / 1.12;
    }

    $str .= "<div style='position: absolute; top:200px; left: 400px;'>";
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['agentname'], '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "<div style='position: absolute; top:220px; left: 400px;'>";
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($data[0]['forwarder'], '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .= "<div style='position: absolute; top:300px; left: 400px;'>";
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='margin-left: 80px;'>" . $params['params']['dataparams']['received'] . "</div>", '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->col("<div style='margin-left: -310px;'>" . $params['params']['dataparams']['approved'] . "</div>", '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= "</div>";


    $str .= "<div style='position: absolute; top:260px; left: 5px;'>";
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(number_format($vatsales, $decimal), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("0.00", '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("0.00", '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("&nbsp", '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(number_format($vat, $decimal), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= "</div>";

    $str .= "</div>";
    $str .= $this->reporter->endreport();

    return $str;
  }
} // end class
