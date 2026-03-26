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

class forklift
{
  public $modulename = 'FORKLIFT';
  public $gridname = 'inventory';

  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $stock = 'lastock';
  public $transdoc = "'RP', 'WB'";

  private $fields = ['checkerid', 'checkerlocid'];
  private $palletstatus = '';
  private $palletid = '';
  private $pallet = '';
  private $stockline = 0;

  private $btnClass;
  private $fieldClass;
  private $tabClass;

  private $companysetup;
  private $coreFunctions;
  private $othersClass;

  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = false;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2090,
      'view' => 2091,
      'additem' => 2092,
      'saveallentry' => 2092
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $added = 1;
    $barcode = 2;
    $itemdesc = 3;
    $brand_desc = 4;
    $partno = 5;
    $subcode = 6;
    $lblstatus = 7;
    $docno = 8;

    $getcols = [
      'action', 'added', 'barcode', 'itemdesc', 'brand_desc',
      'partno',  'subcode', 'lblstatus', 'docno'
    ];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px; max-width:40px;';
    $cols[$added]['style'] = 'width:40px;whiteSpace: normal;min-width:40px; max-width:40px;';
    $cols[$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$partno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$subcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';

    $cols[$partno]['align'] = "text-left";
    $cols[$partno]['label'] = "Part No.";
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];

    if ($userid == 0) {
      return ['data' => [], 'status' => false, 'msg' => 'Sorry, you`re not allowed to create transaction. Please setup first your Employee Code.'];
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'item.barcode', 'item.itemname', 'stock.itemstatus', 'brand.brand_desc', 'item.partno', 'item.subcode'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "select stock.trno, stock.trno as clientid, head.docno, item.barcode, item.itemname as itemdesc, 
        stock.itemstatus as stat, 'false' as added, stock.line,
        brand.brand_desc, item.partno, item.subcode
        from lastock as stock 
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join frontend_ebrands as brand on item.brand = brand.brandid
        where head.doc in (" . $this->transdoc . ") and stock.locid=0 and 
        stock.forkliftid=0 and stock.whmanid=0
        " . $filtersearch . "
        union all
        select stock.trno, stock.trno as clientid, head.docno, item.barcode, item.itemname as itemdesc, 
        stock.itemstatus as stat, 'true' as added, stock.line,
        brand.brand_desc, item.partno, item.subcode
        from lastock as stock 
        left join lahead as head on head.trno=stock.trno
        left join item on item.itemid=stock.itemid
        left join frontend_ebrands as brand on item.brand = brand.brandid
        where head.doc in (" . $this->transdoc . ") and stock.locid=0 and 
        stock.forkliftid=" . $userid . " and stock.whmanid=0
        " . $filtersearch . "
        ";

    $data = $this->coreFunctions->opentable($qry, [$userid]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createHeadField($config)
  {
    $fields = ['client', 'barcode', 'itemname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.class', 'docno sbccsreadonly');
    data_set($col1, 'client.label', 'Document No.');

    data_set($col1, 'barcode.type', 'input');
    data_set($col1, 'barcode.class', 'barcode sbccsreadonly');

    $fields = [['qty', 'uom'], 'subcode', 'partno'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'partno.label', 'Part No.');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['addtask'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'addtask.addedparams', ['line']);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }


  public function createTab($config)
  {
    $tab = [];
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['scanlocationrr', 'dropoff'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "ASSIGN LOCATION";
    $obj[0]['addedparams'] = ['line'];
    $obj[1]['addedparams'] = ['line'];
    return $obj;
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['itemid'] = 0;
    $data[0]['barcode'] = '';
    $data[0]['itemname'] = '';
    $data[0]['uom'] = '';
    $data[0]['qty'] = 0;
    $data[0]['line'] = 0;

    return $data;
  }

  private function selectqry($config)
  {
    $trno = $config['params']['clientid'];
    $qry = "select head.trno, head.trno as clientid, head.docno, head.docno as client,
        head.clientname,left(head.dateid,10) as dateid, stock.line,
        item.barcode, item.itemname, round(stock.rrqty,2) as qty, stock.uom,
        item.subcode, item.partno
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join " . $this->stock . " as stock on stock.trno=head.trno 
        left join item on item.itemid=stock.itemid
        where head.doc in (" . $this->transdoc . ") and 
        stock.trno=" . $trno . " and 
        stock.line=" . $this->stockline . " and 
        num.center = ?";
    return $qry;
  }

  public function loadheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];

    if (!isset($config['params']['row'])) {
      return ['status' => false, 'msg' => ''];
    }
    $this->stockline = $config['params']['row']['line'];

    $qry = $this->selectqry($config);
    $qry .= " and head.trno=?";
    $head = $this->coreFunctions->opentable($qry, [$center, $trno]);
    if (!empty($head)) {
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $stock = [];

      $line = $config['params']['row']['line'];
      $forkliftid = $this->coreFunctions->getfieldvalue("lastock", "forkliftid", "trno=? and line=?", [$trno, $line]);
      if ($forkliftid) {
        $hideobj = ['addtask' => true];
      } else {
        $hideobj = ['addtask' => false];
      }

      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'isnew' => false, 'status' => true, 'msg' => $msg,
        'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj
      ];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'griddata' => ['inventory' => []], 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function openstock($config)
  {
    $trno = $config['params']['clientid'];
    $userid = $config['params']['adminid'];
    $pallet = $config['params']['row']['palletid'];

    $qry = "select item.barcode, item.itemname as itemdesc,
        FORMAT(s.rrqty," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrqty,
        s.palletid, ifnull(pallet.name,'') as pallet,s.locid,ifnull(location.loc,'') as location,
        s.trno,s.line,s.whid,client.clientname as whname,'' as bgcolor
        from lastock as s left join item on item.itemid=s.itemid
        left join pallet on pallet.line=s.palletid
        left join location on location.line=s.locid
        left join client on client.clientid=s.whid
        where s.trno=" . $trno . " and s.palletid=" . $pallet . " and s.forkliftid=0
        union all
        select item.barcode, item.itemname as itemdesc,
        FORMAT(s.rrqty," . $this->companysetup->getdecimal('price', $config['params']) . ") as rrqty,
        s.palletid, ifnull(pallet.name,'') as pallet,s.locid, ifnull(location.loc,'') as location,
        s.trno,s.line,s.whid,client.clientname as whname,'' as bgcolor
        from lastock as s left join item on item.itemid=s.itemid
        left join pallet on pallet.line=s.palletid
        left join location on location.line=s.locid
        left join client on client.clientid=s.whid
        where s.trno=" . $trno . " and s.palletid=" . $pallet . " and s.forkliftid=" . $userid;
    return $this->coreFunctions->opentable($qry);
  }

  public function stockstatusposted($config)
  {
    $trno = $config['params']['clientid'];
    $userid = $config['params']['adminid'];
    if (isset($config['params']['addedparams'])) {
      if (isset($config['params']['addedparams'][0])) {
        $pallet = $config['params']['addedparams'][0];
      }
    }

    $line = 0;
    if (isset($config['params']['addedparams'])) {
      if (isset($config['params']['addedparams'][0])) {
        $line = $config['params']['addedparams'][0];
      }
    }

    switch ($config['params']['action']) {
      case 'addtask':
        $query = "select forkliftid, whmanid from lastock where trno=? and line=?";
        $taken = $this->coreFunctions->opentable($query, [$trno, $line]);

        if ($taken) {
          if ($taken[0]->forkliftid != 0) {
            if ($taken[0]->forkliftid != $userid) {
              return ['status' => false, 'msg' => 'Unable to add. Already taken by other forklift.'];
            }
          }

          if ($taken[0]->whmanid != 0) {
            if ($taken[0]->forkliftid != $userid) {
              return ['status' => false, 'msg' => 'Unable to add. Already taken by the warehouse man.'];
            } else {
              return ['status' => false, 'msg' => 'Already assigned'];
            }
          }
        }

        $current_timestamp = $this->othersClass->getCurrentTimeStamp();
        $query = "update lastock set forkliftid=" . $userid . ", itemstatus='FORKLIFT', whmandate = '" . $current_timestamp . "' where trno=" . $trno . " and line=" . $line;
        $this->coreFunctions->execqry($query, "update");

        $line = $config['params']['addedparams'][0];
        $forkliftid = $this->coreFunctions->getfieldvalue("lastock", "forkliftid", "trno=? and line=?", [$trno, $line]);
        if ($forkliftid) {
          $hideobj = ['addtask' => true];
        } else {
          $hideobj = ['addtask' => false];
        }

        return ['status' => true, 'msg' => 'Successfully added.', 'hideobj' => $hideobj];
        break;

      case 'perpalletloc':
        break;

      case 'scanlocation':
        return ['status' => true, 'msg' => 'scan location per item'];
        break;

      case 'scanlocationrr':
        $line = $config['params']['addedparams'][0];

        $result = $this->isAssigned($userid, $trno, $line);
        if (!$result['status']) {
          return ['status' => $result['status'], 'msg' => $result['msg']];
        }

        $barcode = '';
        $location = '';
        if (count($config['params']['arrparams']) != 0) {
          $location = $config['params']['arrparams'][0];
          $barcode = $config['params']['barcode'];

          $locid = $this->coreFunctions->getfieldvalue("location", "line", "loc=?", [$location]);
          if (!$locid) {
            return ['status' => false, 'msg' => 'The scanned location does not exist.'];
          }

          $exist = $this->coreFunctions->datareader("select item.itemid as value from lastock as s left join item on item.itemid=s.itemid where s.trno=? and s.line=? and item.barcode=?", [$trno, $line, $barcode]);
          if (!$exist) {
            return ['status' => false, 'msg' => 'The scanned barcode does not exist.'];
          }

          $this->coreFunctions->sbcupdate("lastock", ['locid' => $locid, 'itemstatus' => 'DONE'], ['trno' => $trno, 'line' => $line]);

          $this->tagForPosting($trno);

          return ['status' => true, 'msg' => 'The location was assigned succcessfully.', 'action' => 'backlisting'];
        }

        $location = $config['params']['barcode'];
        $exist = $this->coreFunctions->getfieldvalue("location", "line", "loc=?", [$location]);
        if (!$exist) {
          return ['status' => false, 'msg' => 'The scanned location does not exist.'];
        }

        return ['status' => true, 'msg' => 'The location was successfully scanned.', 'action' => 'rescan', 'title' => 'Scan Barcode'];
        break;

      case 'dropoff':
        $line = $config['params']['addedparams'][0];

        $result = $this->isAssigned($userid, $trno, $line);
        if (!$result['status']) {
          return ['status' => $result['status'], 'msg' => $result['msg']];
        }

        $this->coreFunctions->sbcupdate("lastock", ['forkliftid' => 0, 'itemstatus' => 'DROP-OFF'], ['trno' => $trno, 'line' => $line]);
        return ['status' => true, 'msg' => 'Successfully drop-off.'];
        break;

      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function tagForPosting($trno)
  {
    $pending = $this->coreFunctions->datareader("select count(locid) as value from lastock where trno=? and locid=0", [$trno]);
    if ($pending == 0) {
      $this->coreFunctions->execqry("update cntnum set status='FOR POSTING' where trno=" . $trno);
    }
  }


  private function isAssigned($userid, $trno, $line)
  {
    $qry = "select s.forkliftid as value from lastock as s where s.forkliftid=" . $userid . " and s.trno=" . $trno . " and s.line=" . $line . "";
    $assigned = $this->coreFunctions->datareader($qry);

    if ($assigned == 0) {
      return ['status' => false, 'msg' => 'Assigned this selected item first.'];
    } else {
      return ['status' => true, 'msg' => ''];
    }
  }

  private function isAssigned_perpallet($userid, $trno, $pallet)
  {
    $qry = "select s.forkliftid as value
        from lastock as s left join item on item.itemid=s.itemid
        left join pallet on pallet.line=s.palletid
        where s.forkliftid=" . $userid . " and s.trno=" . $trno . " and s.palletid=" . $pallet . "
        group by s.forkliftid limit 1";
    $assigned = $this->coreFunctions->datareader($qry);

    if ($assigned == 0) {
      return ['status' => false, 'msg' => 'Assigned this selected pallet/item first'];
    } else {
      return ['status' => true, 'msg' => ''];
    }
  }
}
