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

class warehousechecker
{

  public $modulename = 'WAREHOUSE CHECKER';
  public $gridname = 'inventory';

  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $stock = 'lastock';

  private $fields = ['checkerid', 'checkerlocid'];

  public $transdoc = "'SD', 'SE', 'SF', 'SH'";

  private $btnClass;
  private $fieldClass;
  private $tabClass;

  private $companysetup;
  private $coreFunctions;
  private $othersClass;

  public $showfilteroption = true;
  public $showfilter = false;
  public $showcreatebtn = false;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Assigned', 'color' => 'primary']
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
    $getcols = ['action', 'lblstatus', 'checkerloc', 'listdocument', 'clientname', 'rem', 'ref', 'transtype'];
    $stockbuttons = ['view', 'showcheckerreplacement'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:60px;whiteSpace: normal;min-width:60px; max-width:60px;';
    $cols[1]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; max-width:80px;';
    $cols[4]['label'] = 'Name';
    $cols[5]['label'] = 'Remarks';
    $cols[6]['label'] = 'SO #';

    $cols[0]['btns']['showcheckerreplacement']['checkfield'] = "added";
    return $cols;
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
      case 'draft':
        $qry .= " and num.status='PICKED' and (ci.checkerid=0 or ci.checkerid=" . $userid . ")";
        break;

      default:
        $qry .= " and num.status='CHECKER: ON-PROCESS' and ci.checkerid=" . $userid;
        break;
    }

    $qry .= " order by stat, crtldate";

    $data = $this->coreFunctions->opentable($qry, [$center]);
    $data = $this->othersClass->updatetranstype($data);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }


  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'print',
      'edit',
      'save',
      'cancel',
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
    data_set($col1, 'ourref.label', 'DR No.');

    $fields = ['checkerloc', 'newchecker'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['rcvecheckerloc'];
    $col3 = $this->fieldClass->create($fields);


    $fields = ['postwhclr'];
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
    $tbuttons = ['openbox'];
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
    $qry = "select head.trno, head.doc, '' as transtype, head.trno as clientid, head.docno, head.docno as client,head.clientname, left(head.dateid,10) as dateid,
        ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, num.crtldate, num.status as stat, 0 as newcheckerid,
        ci.checkerlocid, ci.checkerid, if(ifnull((select count(trno) from replacestock where trno=head.trno and isaccept=0), 0)<>0,'FOR REPLACEMENT','') as rem,
        if(ci.checkerid<>0,'false','true') as added, head.ourref, ifnull((select group_concat(distinct ref) from lastock where lastock.trno=head.trno),'') as ref
        from " . $this->head . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
        left join cntnuminfo as ci on ci.trno=head.trno left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        where  head.doc in (" . $this->transdoc . ") and num.center = ?
        and head.lockdate is not null and num.crtldate is not null and ci.checkerdate is null ";
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
      $checkerlocdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerrcvdate", "trno=?", [$trno]);
      if ($checkerlocdate) {
        $hideobj = ['rcvecheckerloc' => true, 'postwhclr' => false];
      } else {
        $hideobj = ['rcvecheckerloc' => false, 'postwhclr' => true];
      }
      $stock = $this->openstock($config);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj];
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
        stock.uom, 0 as whremid, '' as whrem, 0 as replaceqty, '' as bgcolor
        from lastock as stock left join item on item.itemid=stock.itemid where stock.trno=?";
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

    $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);
    $this->coreFunctions->sbcupdate('lahead', ['ourref' => $head['ourref']], ['trno' => $trno]);

    return ['status' => false, 'msg' => 'Successfully updated', 'clientid' => $trno, 'backlisting' => true];
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
          return ['status' => false, 'msg' => 'DR No. is required'];
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
        $ispick = $this->coreFunctions->datareader("select trno as value from lastock where trno=? and pickerend is null limit 1", [$trno]);
        if ($ispick) {
          return ['status' => false, 'msg' => 'Cannot proceed, not yet complete by picker'];
        }
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['checkerrcvdate'] = $current_timestamp;
        $data['status'] = 'CHECKER: ON-PROCESS';
        $data['checkerid'] = $userid;

        $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);

        $this->coreFunctions->execqry("update cntnum set status='CHECKER: ON-PROCESS' where trno=" . $trno);

        $checkerlocdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerrcvdate", "trno=?", [$trno]);
        if ($checkerlocdate) {
          $hideobj = ['rcvecheckerloc' => true, 'postwhclr' => false];
        } else {
          $hideobj = ['rcvecheckerloc' => false, 'postwhclr' => true];
        }

        return ['status' => true, 'msg' => 'Successfully updated.', 'hideobj' => $hideobj];
        break;
      case 'additeminbox':

        $config['params']['groupid'] = $this->othersClass->val($config['params']['groupid']);
        if ($config['params']['groupid'] == 0) {
          $data['trno'] = $config['params']['trno'];
          $data['itemid'] = $config['params']['itemid'];
          $data['qty'] = $config['params']['qty'];
          $data['boxno'] = $config['params']['boxno'];
          $data['groupid'] = $config['params']['boxno'];
          $data['groupid2'] = 1;
          $this->coreFunctions->sbcinsert('boxinginfo', $data);
          return ['status' => true, 'msg' => 'Successfully updated.', 'boxno' => $config['params']['boxno']];
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
          return ['status' => true, 'msg' => 'Successfully updated.', 'boxno' => $boxno];
        }
        break;

      case 'post':
        $trno = $config['params']['clientid'];
        $ispick = $this->coreFunctions->datareader("select  ifnull(checkerrcvdate,'') as value from cntnuminfo where trno=?", [$trno]);
        if ($ispick === '') {
          return ['status' => false, 'msg' => 'Please click the PICK FROM LOCATION button first to proceed.'];
        }

        $isreplacement = $this->coreFunctions->datareader("select trno as value from replacestock where trno=? and isaccept=0 limit 1", [$trno]);
        if ($isreplacement) {
          return ['status' => false, 'msg' => 'Cannot proceed, items tagged for replacement are not yet accepted by the checker.'];
        }

        $qry = "select sum(bal) as bal, sum(scanqty) as scanqty from ( select round(sum(stock.isqty),2) as bal,
                (select ifnull(sum(box.qty),0) from boxinginfo as box where box.trno=stock.trno and box.itemid=stock.itemid) as scanqty
                from lastock as stock where stock.trno=? group by stock.trno,stock.itemid) as x";
        $item = $this->coreFunctions->opentable($qry, [$trno]);
        if (!empty($item)) {
          if ($item[0]->scanqty < $item[0]->bal) {
            return ['status' => false, 'msg' => 'Cannot continue, some items for this transaction not yet tag in the box...'];
          }
        }
        $ispick = $this->coreFunctions->datareader("select trno as value from lastock where trno=? and pickerend is null limit 1", [$trno]);
        if ($ispick) {
          return ['status' => false, 'msg' => 'Cannot proceed, not yet complete by picker'];
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $data['checkerdate'] = $current_timestamp;
        $data['checkerby'] = $config['params']['user'];
        $data['status'] = 'FOR DISPATCH';
        $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);

        $this->coreFunctions->execqry("update cntnum set status='FOR DISPATCH' where trno=" . $trno);

        return ['status' => true, 'msg' => 'Proceed to Dispatching...'];
        break;
      case 'printing':
        return $this->reportboxsetup($config);
        break;
    }
  } // end function


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

    switch ($boxno) {
      case 'default': // header button print
        $fields = ['radioprint', 'radioreporttype', 'prepared', 'approved', 'received', 'boxno', 'print'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'radioreporttype.options', [
          ['label' => 'Delivery Receipt', 'value' => '0', 'color' => 'orange'],
          ['label' => 'Packing List Label', 'value' => '1', 'color' => 'orange'],
          ['label' => 'Reprint BOX', 'value' => '2', 'color' => 'orange']
        ]);
        break;
      default: // per box print
        $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    return array('col1' => $col1);
  }

  public function reportparamsboxdata($config)
  {
    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '' as prepared,
        '' as approved,
        '' as received,
        " . $config['params']['boxno'] . " as boxno"
    );
  }

  public function reportparamsdata()
  {
    return $this->coreFunctions->opentable(
      "select
        'default' as print,
        '' as prepared,
        '' as approved,
        '' as received,
        '0' as reporttype"
    );
  }

  public function reportdata($config)
  {
    // orientations: portrait=p, landscape=l
    // formats: letter, a4, legal
    // layoutsize: reportWidth
    $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];
    $reporttype = isset($config['params']['dataparams']['reporttype']) ? $config['params']['dataparams']['reporttype'] : '';
    if ($reporttype != '') {
      switch ($config['params']['dataparams']['reporttype']) {
        case '0':
        case '1':
          $boxno = 'default';
          break;

        case '2':
          $boxno = isset($config['params']['dataparams']['boxno']) ? $config['params']['dataparams']['boxno'] : '';
          break;
      }
    } else {
      $boxno = isset($config['params']['dataparams']['boxno']) ? $config['params']['dataparams']['boxno'] : 'default';
    }

    switch ($boxno) {
      case 'default': // header button print
        switch ($config['params']['dataparams']['reporttype']) {
          case '0':
            $data = $this->report_default_print_query($config);
            break;
          case '1':
            $data = $this->report_packing_list_query($config);
            break;
        }
        break;
      default: // per box print
        $data = $this->report_default_box_query($config, $boxno);
        break;
    }

    $str = $this->buildboxreport($config, $data, $boxno);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $reportParams];
  }

  private function report_default_print_query($config)
  {
    $trno = $config['params']['dataid'];
    $query = "
      select distinct groupid2, groupid, trno from boxinginfo where trno='$trno'";

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  private function report_default_box_query($config, $boxno_ = '')
  {
    $trno = $config['params']['dataid'];
    if ($boxno_ != '') {
      $boxno = $boxno_;
    } else {
      $boxno = $config['params']['dataparams']['boxno'];
    }

    $query = "select head.trno, head.docno, item.itemname, item.barcode, stock.uom, 
              model.model_name, stock.iss as qty, stock.ext, boxinfo.boxno,
              cl.client, cl.clientname, cl.addr, cl.tel2,
              head.ourref
              from lahead as head
              left join boxinginfo as boxinfo on boxinfo.trno = head.trno
              left join lastock as stock on stock.trno = head.trno and boxinfo.itemid = stock.itemid
              left join item as item on item.itemid = boxinfo.itemid
              left join client as cl on cl.client = head.client
              left join model_masterfile as model on model.model_id = item.model
              where head.trno = '$trno' and boxinfo.boxno='$boxno'";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  private function report_packing_list_query($config)
  {
    $trno = $config['params']['dataid'];

    $query = "
      select
        box.trno, box.boxno, item.itemname, box.qty
      from boxinginfo as box
      left join item as item on item.itemid = box.itemid
      where box.trno='$trno' order by box.boxno";
    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $result;
  } //end fn

  private function buildboxreport($config, $data, $boxno_ = '')
  {
    if ($boxno_ != '') {
      $boxno = $boxno_;
    } else {
      $boxno = isset($config['params']['dataparams']['boxno']) ? $config['params']['dataparams']['boxno'] : 'default';
    }

    switch ($boxno) {
      case 'default': // header button print
        switch ($config['params']['dataparams']['reporttype']) {
          case '0':
            $str = $this->report_default_print_plotting($config, $data);
            break;
          case '1':
            $str = $this->report_packing_list_plotting($config, $data);
            break;
        }
        break;
      default: // per box print
        $str = $this->reportboxplotting($config, $data, $boxno_);
        break;
    }
    return $str;
  }

  public function perbox_header($params, $data)
  {
    $logo = URL::to('/images/reports/mitsukoshilogo.png');
    $companyid = $params['params']['companyid'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    if (empty($data)) {
      return 'No data found';
    }

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

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
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='margin-left: 75px; margin-top: 10px;'>" . DNS1D::getBarcodeHTML($docno_boxno, 'C39+', 1, 40, 'black', true) . "</div>", '100', null, false, $border, 'LTR', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($docno_boxno, '100', null, false, $border, 'LR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<div style='position:relative; margin-top: -10px;'>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<img src ="' . $logo . '" alt="mitsukoshi" width="180px" height ="80px">', '10', null, false, '1px solid ', 'LR', 'L', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "</div>";




    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NAME : ', '60', null, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['clientname']) ? $data[0]['clientname'] : ''), '235', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('REF NO. : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['ourref']) ? $data[0]['ourref'] : ''), '135', null, false, $border, 'BR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CONTACT : ', '60', null, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['tel2']) ? $data[0]['tel2'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS: ', '60', null, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($data[0]['address']) ? $data[0]['address'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '40', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportboxplotting($params, $data, $boxno = '')
  {
    if (empty($data)) {
      return 'No Data found';
    }

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

    // return $data;
    $str .= $this->reporter->beginreport('400');
    $str .= $this->perbox_header($params, $data);

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("SKU", '100', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Description", '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Model", '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Qty", '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '10', null, false, $border, 'R', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();


      $str .= $this->reporter->col($data[$i]['barcode'], '100', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['model_name'], '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], 0), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col("", '10', null, false, $border, 'R', 'R', $font, $fontsize, 'B', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->perbox_header($params, $data);

        // $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("SKU", '100', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col("Description", '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col("Model", '100', null, false, $border, 'R', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col("Qty", '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col("", '10', null, false, $border, 'R', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $page = $page + $count;
      } //end if
    }
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= "<div style='height: 50px; border-left: 1px solid; border-right: 1px solid;'></div>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br>';
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

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
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<img src ="' . $logo . '" alt="MDC" width="180px" height ="80px">', '10', null, false, '1px solid ', 'LTR', 'C', 'Century Gothic', '15', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('NAME : ', '50', null, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['clientname']) ? $result[0]['clientname'] : ''), '235', null, false, $border, 'BR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '55', null, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['address']) ? $result[0]['address'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'R', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DR NO. : ', '55', null, false, $border, 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['ourref']) ? $result[0]['ourref'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('DOC NO. : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['docno']) ? $result[0]['docno'] : ''), '90', null, false, $border, 'BR', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();
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
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport('400');
    $str .= $this->packing_list_header($params, $data);

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Box", '50', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Description", '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Qty", '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '50', null, false, $border, 'R', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data[$i]['boxno'], '50', null, false, $border, 'L', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($data[$i]['itemname'], '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($data[$i]['qty'], $decimal), '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '50', null, false, $border, 'R', 'R', $font, $fontsize, 'B', '', '');

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->packing_list_header($params, $data);

        // $str .= $this->reporter->endrow();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col("Box", '50', null, false, $border, 'C', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col("Description", '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col("Qty", '50', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col("", '50', null, false, $border, 'R', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $page = $page + $count;
      } //end if
    }

    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->printline();
    $str .= "<div style='height: 50px; border-left: 1px solid; border-right: 1px solid;'></div>";
    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("PACKING LIST INSIDE", '400', null, false, $border, 'LRBT', 'C', $font, '32', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br>';

    $str .= $this->reporter->begintable('400');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function default_header($params, $data)
  {
    $companyid = $params['params']['companyid'];

    $center = $params['params']['center'];
    $username = $params['params']['user'];

    $trno = $data[0]['trno'];
    $qry = "
          select head.trno, head.docno, head.client, head.clientname,
          head.terms, head.address, date(head.dateid) as dateid,
          item.itemid, item.itemname, boxinfo.boxno, boxinfo.qty
          from lahead as head
          left join boxinginfo as boxinfo on boxinfo.trno = head.trno
          left join item as item on item.itemid = boxinfo.itemid
          where head.trno = '$trno'";
    $result = json_decode(json_encode($this->coreFunctions->opentable($qry)), true);

    $str = '';
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br><br>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('DELIVERY RECEIPT', '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT # :', '120', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['docno']) ? $result[0]['docno'] : ''), '100', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]['clientname']) ? $result[0]['clientname'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('DATE : ', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['dateid']) ? $result[0]['dateid'] : ''), '150', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ADDRESS : ', '80', null, false, $border, '', 'L', $font, $fontsize, 'B', '30px', '4px');
    $str .= $this->reporter->col((isset($result[0]['address']) ? $result[0]['address'] : ''), '520', null, false, $border, 'B', 'L', $font, $fontsize, '', '30px', '4px');
    $str .= $this->reporter->col('TERMS : ', '60', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col((isset($result[0]['terms']) ? $result[0]['terms'] : ''), '140', null, false, $border, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
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
    $font =  "Century Gothic";
    $fontsize = "11";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport();
    $str .= $this->default_header($params, $data);
    $str .= $this->reporter->begintable('800');
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $groupid   = $data[$i]['groupid'];
      $groupid2  = $data[$i]['groupid2'];
      $data_trno = $data[$i]['trno'];

      $qry = "
            select item.itemname,boxinginfo.qty,item.uom,boxinginfo.boxno,boxinginfo.groupid2
            from boxinginfo
            left join item on item.itemid=boxinginfo.itemid
            where trno='$data_trno' and boxinginfo.groupid2='$groupid2' and boxinginfo.groupid='$groupid'
            order by boxinginfo.boxno";
      $result = $this->coreFunctions->opentable($qry);
      $data_count = count($result);

      $itemname = "";
      $res_grpid2 = "";

      $col1 = "";

      if ($result[0]->groupid2 != 1) {
        $str .= $this->reporter->col($groupid2, '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('box', '30', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('x', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($result[0]->qty, $decimal), '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($result[0]->uom, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($result[0]->itemname, '650', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      } else {
        foreach ($result as $key => $d) {
          if ($data_count > 1) {
            if ($res_grpid2 != $groupid2) {
              $str .= $this->reporter->col($data[$i]['groupid2'], '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('box', '30', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '650', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            }

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '30', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('x', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');

            if ($itemname != $d->itemname) {
              $str .= $this->reporter->col(number_format($d->qty, $decimal), '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col($d->uom, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col($d->itemname, '650', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            } else {
              $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
              $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            }
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->col($data[$i]['groupid2'], '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('box', '30', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('x', '20', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($d->qty, $decimal), '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($d->uom, '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col($d->itemname, '650', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          }

          $res_grpid2 = $groupid2;
          $itemname   = $d->itemname;
        }
      }

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_header($params, $data);

        $str .= $this->reporter->endrow();
        $page = $page + $count;
      } //end if
    }
    $str .= $this->reporter->endtable();


    $str .= '<br>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($params['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($params['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
} // end class
