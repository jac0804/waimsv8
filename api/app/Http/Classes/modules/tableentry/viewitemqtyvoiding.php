<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\sqlquery;

class viewitemqtyvoiding
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'VOID ITEMS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'LIST';
  public $tablelogs = 'masterfile_log';
  public $tablelogs_del = 'del_masterfile_log';
  public $head = 'prhead';
  public $hhead = 'hprhead';
  private $logger;
  private $othersClass;
  public $style = 'width:100%;max-width:100%;';
  private $fields = ['voidqty'];
  public $showclosebtn = true;
  public $showsearch = false;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $oqty = 1;
    $isqty = 2;
    $qa = 3;
    $voidqty = 4;
    $barcode = 5;
    $itemname = 6;
    $itemdesc = 7;
    $specs = 8;
    $uom = 9;

    $gridcolumns = ['action', 'oqty', 'isqty', 'qa', 'voidqty', 'barcode', 'itemname', 'itemdesc', 'specs', 'uom'];
    $stockbuttons = ['save'];
    $tab = [$this->gridname => ['gridcolumns' => $gridcolumns]];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;';

    $obj[0][$this->gridname]['columns'][$oqty]['style'] = 'width: 80px;whiteSpace: normal;min-width:80px;max-width:80px;';
    $obj[0][$this->gridname]['columns'][$oqty]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$oqty]['label'] = 'Void Qty';

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Name';

    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item Name (PR)';

    $obj[0][$this->gridname]['columns'][$voidqty]['label'] = 'Voided Qty';
    $obj[0][$this->gridname]['columns'][$voidqty]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$voidqty]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$specs]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$isqty]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$isqty]['readonly'] = true;

    $obj[0][$this->gridname]['columns'][$qa]['type'] = 'label';

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from tripdetail where trno=? and line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['trno'], $row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.', 'reloadhead' => true];
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config, $line = 0)
  {
    $tableid = $config['params']['tableid'];

    $row = $config['params']['doc'];
    $addedfilter = '';
    if ($line != 0) {
      $addedfilter = ' and stock.line=' . $line;
    }

    switch ($row) {
      case 'PR':
        $select = ", stock.cdqa,stock.poqa,stock.rrqa, stock.oqqa";
        $table = "hprstock";
        $tblhead = "hprhead";
        $left = "left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line ";
        break;

      case 'CD':
        $select = " , stock.oqqa";
        $table = "hcdstock";
        $tblhead = "hcdhead";
        $left = "left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline";
        $addedfilter = ' and stock.status = 1';
        break;
      case 'PO':
        $select = ",stock.cdrefx, stock.cdlinex,stock.reqtrno,stock.reqline";
        $table = "hpostock";
        $tblhead = "hpohead";
        $left = "left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline";
        break;
    }

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,head.doc,head.docno,0 as oqty,stock.line,stock.itemid,
                   item.barcode,item.itemname, info.itemdesc,stock.uom,wh.client as wh,
                   round(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
                   round((stock.qty-stock.qa)/ case when ifnull(uom2.factor,0)<>0 then uom2.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                    round(ifnull(stock.voidqty/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as voidqty, 
                   '' as bgcolor,stock.qty $select
            from $table as stock left join item on item.itemid = stock.itemid 
            left join uom on uom.itemid = item.itemid and uom.uom = stock.uom 
            left join client as wh on wh.clientid=stock.whid 
            $left
            left join $tblhead as head on head.trno=stock.trno
            left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1
            left join uomlist as uom2 on uom2.uom=info.uom2 and uom2.isconvert=1
            where stock.trno =? and (stock.qa+stock.voidqty)<>stock.qty and stock.void<>1 " . $addedfilter;
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  }

  public function save($config)
  {
    $companyid = $config['params']['companyid'];
    $uom2 = '';
    $row = $config['params']['row'];

    if ($row['oqty'] != 0) {

      $tablestock = '';
      switch ($row['doc']) {
        case 'PR':
          $tablestock = 'hprstock';
          break;
        case 'CD':
          $tablestock = 'hcdstock';
          break;
        case 'PO':
          $tablestock = 'hpostock';
          break;
      }

      $stockinsert = ['editby' => $config['params']['user'], 'editdate' => $this->othersClass->getCurrentTimeStamp()];

      $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
      $item = $this->coreFunctions->opentable($qry, [$row['uom'], $row['itemid']]);
      $factor = 1;

      if (!empty($item)) {
        $item[0]->factor = $this->othersClass->val($item[0]->factor);
        if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      } else {
        $uom2 = $this->coreFunctions->getfieldvalue('hstockinfotrans', "uom2", "trno=? and line=?", [$row['trno'], $row['line']], '', true);
        if ($uom2 != '') {
          $factor = $this->coreFunctions->getfieldvalue("uomlist", "factor", "uom=? and isconvert=1", [$uom2], '', true);
          if ($factor == 0) {
            $factor = 1;
          }
        }
      }

      $voidqty = $this->coreFunctions->getfieldvalue($tablestock, "voidqty", "trno=? and line=?", [$row['trno'], $row['line']], '', true);
      $computedata = $this->othersClass->computestock(0, '',  $row['oqty'], $factor);

      $stockinsert['voidqty'] = $computedata['qty'] + $voidqty;

      $qa = $this->coreFunctions->getfieldvalue($tablestock, "qa", "trno=? and line=?", [$row['trno'], $row['line']], '', true);

      if (($qa + $stockinsert['voidqty']) > $row['qty']) {
        return ['status' => false, 'msg' => 'Void quantity must not be greater than pending qty.' . '</br>' . ' Served Quantity: ' . number_format($qa, 2) .  '</br>' .  '</br>' . ' Void Quantity: ' . number_format($voidqty, 2) .  '</br>' . ' Total Quantity: ' . number_format($row['qty'], 2)];
      }
      switch ($companyid) {
        case 16: //ati
          switch ($row['doc']) {
            case 'PR':
              if ($row['cdqa'] > 0) {
                if (($row['cdqa'] + $stockinsert['voidqty']) > $row['qty']) {
                  return ['status' => false, 'msg' => "Can't void " . $row['itemname'] . ". Item already served in Canvass Sheet module and void quantity must not be greater than pending qty." .
                    "Applied Canvass: " . $row['cdqa']];
                }
              }

              if ($row['poqa'] > 0) {
                if (($row['poqa'] + $stockinsert['voidqty']) > $row['qty']) {
                  return ['status' => false, 'msg' => "Can't void quantity of " . $stockinsert['voidqty'] . " for item " . $row['itemname'] . ". Item already served in Purchase Order module. " .
                    "Applied PO: " . $row['poqa']];
                }
              }

              if ($row['rrqa'] > 0) {
                if (($row['rrqa'] + $stockinsert['voidqty']) > $row['qty']) {
                  return ['status' => false, 'msg' => "Can't void quantity of " . $stockinsert['voidqty'] . " for item " . $row['itemname'] . ". Item already served in Receiving Report module. " .
                    "Applied RR: " . $row['rrqa']];
                }
              }

              if ($row['oqqa'] > 0) {
                if (($row['oqqa'] + $stockinsert['voidqty']) > $row['qty']) {
                  return ['status' => false, 'msg' => "Can't void quantity of " . $stockinsert['voidqty'] . " for item " . $row['itemname'] . ". Item already served in Oracle Code Request module. " .
                    "Applied OQ: " . $row['oqqa']];
                }
              }
              break;
            case 'CD':
              $qry = "select group_concat(k.docno) as docno, sum(qty) as qty from (
                      select s.trno,h.docno,s.qty from postock as s left join pohead as h on h.trno=s.trno where s.cdrefx=? and s.cdlinex= ?
                      union all
                      select s.trno,h.docno,s.qty from hpostock as s left join hpohead as h on h.trno=s.trno where s.cdrefx=? and s.cdlinex=?) as k";
              $po = json_decode(json_encode($this->coreFunctions->opentable($qry, [$row['trno'], $row['line'], $row['trno'], $row['line']])));

              if (($po[0]->qty + $stockinsert['voidqty']) > $row['qty']) {
                return ['status' => false, 'msg' => "Can't void quantity of " . $stockinsert['voidqty'] . " for item " . $row['itemname'] . ". Item already served in " . $po[0]->docno . " ."];
              }

              if ($row['oqqa'] > 0) {
                $qry = "select group_concat(distinct k.docno) as docno, sum(qty) as qty from (
                      select s.trno,h.docno,s.qty from oqstock as s left join oqhead as h on h.trno=s.trno where s.ref=?
                      union all
                      select s.trno,h.docno,s.qty from hoqstock as s left join hoqhead as h on h.trno=s.trno where s.ref=?) as k";
                $oq = json_decode(json_encode($this->coreFunctions->opentable($qry, [$row['docno'], $row['docno']])));
                if (($qa + $row['oqqa'] + $stockinsert['voidqty']) > $row['qty']) {
                  return ['status' => false, 'msg' => "Can't void quantity of " . $stockinsert['voidqty'] . " for item " . $row['itemname'] . ". Item already served in " . $oq[0]->docno . " ."];
                }
              }
              break;
            case 'PO':
              $qry = "select group_concat(k.docno) as docno, sum(qty) as qty from (
                      select s.trno,h.docno,s.qty from lastock as s left join lahead as h on h.trno=s.trno where h.doc='RR' and s.refx=? and s.linex= ?
                      union all
                      select s.trno,h.docno,s.qty from glstock as s left join glhead as h on h.trno=s.trno where h.doc='RR' and s.refx=? and s.linex=?) as k";
              $rr = json_decode(json_encode($this->coreFunctions->opentable($qry, [$row['trno'], $row['line'], $row['trno'], $row['line']])));

              if (($rr[0]->qty + $stockinsert['voidqty']) > $row['qty']) {
                return ['status' => false, 'msg' => "Can't void quantity of " . $stockinsert['voidqty'] . " for item " . $row['itemname'] . ". Item already served in " . $rr[0]->docno . " ."];
              }

              $qry = "select group_concat(k.docno) as docno
                      from (select s.trno,h.docno
                            from cvitems as s
                            left join lahead as h on h.trno=s.trno
                            where h.doc='CV' and s.refx=? and s.linex= ?
                            union all
                            select s.trno,h.docno
                            from hcvitems as s
                            left join glhead as h on h.trno=s.trno
                            where h.doc='CV' and s.refx=? and s.linex=?) as k";
              $cv = json_decode(json_encode($this->coreFunctions->opentable($qry, [$row['trno'], $row['line'], $row['trno'], $row['line']])));

              if (!empty($cv[0]->docno)) {
                if ($row['itemname'] == '') {
                  $row['itemname'] = $row['itemdesc'];
                }
                return ['status' => false, 'msg' => "Can't void quantity of " . $stockinsert['voidqty'] . " for item " . $row['itemname'] . ". Item has already been picked up in " . $cv[0]->docno . " ."];
              } else {
                $cdstock = $this->coreFunctions->opentable("select refx,linex from hcdstock where trno=" . $row['cdrefx'] . " and line=" . $row['cdlinex']);
                if (!empty($cdstock)) {
                  $this->coreFunctions->execqry('update hcdstock set voidqty=' . $stockinsert['voidqty'] . ',qa= (qty-' . $stockinsert['voidqty'] . ') where trno=? and line=?', 'update', [$row['cdrefx'], $row['cdlinex']]);
                  $this->coreFunctions->sbcupdate($tablestock, $stockinsert, ['trno' => $row['trno'], 'line' => $row['line']]);


                  $qry1 = "";
                  $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.status=1 and stock.refx=" . $cdstock[0]->refx . " and stock.linex=" . $cdstock[0]->linex;
                  $qry1 = $qry1 . " union all select (stock.qty-stock.voidqty) as qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.status=1 and stock.refx=" . $cdstock[0]->refx . " and stock.linex=" . $cdstock[0]->linex;
                  $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";

                  $cdqa = $this->coreFunctions->datareader($qry2);
                  if ($cdqa == '') {
                    $cdqa = 0;
                  }

                  $poqry = "";
                  $poqry = "select stock.qty from pohead as head left join postock as stock on stock.trno=head.trno where head.doc='PO' and stock.void=0 and stock.reqtrno=" . $cdstock[0]->refx . " and stock.reqline=" . $cdstock[0]->linex;
                  $poqry = $poqry . " union all select (stock.qty-stock.voidqty) as qty from hpohead as head left join hpostock as stock on stock.trno=head.trno where head.doc='PO' and stock.void=0 and stock.reqtrno=" . $cdstock[0]->refx . " and stock.reqline=" . $cdstock[0]->linex;
                  $poqry2 = "select ifnull(sum(qty),0) as value from (" . $poqry . ") as t";
                  $this->coreFunctions->LogConsole($poqry2);
                  $poqa = $this->coreFunctions->datareader($poqry2);
                  $this->coreFunctions->LogConsole($poqa);

                  if ($poqa == '') {
                    $poqa = 0;
                  }

                  $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . ",poqa=" . $poqa . " where trno=" . $cdstock[0]->refx . " and line=" . $cdstock[0]->linex);

                  $this->logger->sbcwritelog($cdstock[0]->refx, $config, 'STOCK', 'VOID PO' . ' CDLine:' . $row['cdlinex'] . ' Item Name:' . $row['itemname'] . ' isQTY:' . $row['isqty'], 'transnum_log');

                  $chkcd = $this->coreFunctions->opentable("select stock.trno
                                                        from cdstock as stock
                                                        where stock.refx = " . $cdstock[0]->refx . " and stock.linex = " . $cdstock[0]->linex . " and stock.void=0
                                                        union all
                                                        select stock.trno
                                                        from hcdstock as stock
                                                        where stock.refx = " . $cdstock[0]->refx . " and stock.linex = " . $cdstock[0]->linex . " and stock.void=0");

                  if (empty($chkcd)) {
                    $this->coreFunctions->execqry("update hprstock set iscanvass= 0 where trno=" . $cdstock[0]->refx . " and line=" . $cdstock[0]->linex);
                    $this->logger->sbcwritelog($cdstock[0]->refx, $config, 'STOCK', 'Remove tagging of Canvass.' . ' CDLine:' . $row['cdlinex'] . ' Item Name:' . $row['itemname'] . ' isQTY:' . $row['isqty'], 'transnum_log');
                  }
                }
              }

              break;
          }
          break;
      }

      // $this->coreFunctions->sbcupdate($tablestock, $stockinsert, ['trno' => $row['trno'], 'line' => $row['line']]);

      $this->logger->writelog($config['params']['doc'], $row['trno'],  'VOID', 'Void Qty: ' . $row['oqty'] . " " . $row['uom'], $config['params']['user']);

      switch ($row['doc']) {
        case 'CD':
        case 'PO':
          $checkqry = $this->coreFunctions->opentable("select qa,voidqty,qty from " . $tablestock . " where trno=? and line=?", [$row['trno'], $row['line']]);
          if (($checkqry[0]->qa + $checkqry[0]->voidqty) == $checkqry[0]->qty) {
            $this->coreFunctions->execqry("update " . $tablestock . " set void=1 where trno=? and line=?", 'update', [$row['trno'], $row['line']]);
            if ($row['doc'] == 'PO') {
              $this->coreFunctions->execqry('update hcdstock set void=1 where trno=? and line=?', 'update', [$row['cdrefx'], $row['cdlinex']]);
              $this->coreFunctions->execqry('update hprstock set poqa=0 where trno=? and line=?', 'update', [$row['reqtrno'], $row['reqline']]);
            }
          } else {
            if ($checkqry[0]->qa == 0 && $row['doc'] == 'PR') {
              $this->coreFunctions->execqry("update " . $tablestock . " set void=1, voidqty = " . $stockinsert['voidqty'] . " where trno=? and line=?", 'update', [$row['trno'], $row['line']]);
            }
          }
          break;
        case 'PR':
          $checkqry = $this->coreFunctions->opentable("select qa,voidqty,qty from " . $tablestock . " where trno=? and line=?", [$row['trno'], $row['line']]);
          if (($checkqry[0]->qa + $stockinsert['voidqty']) == $checkqry[0]->qty) {
            $this->coreFunctions->execqry("update " . $tablestock . " set void=1, voidqty = " . $stockinsert['voidqty'] . " where trno=? and line=?", 'update', [$row['trno'], $row['line']]);
          } else {
            $this->coreFunctions->execqry("update " . $tablestock . " set voidqty = " . $stockinsert['voidqty'] . " where trno=? and line=?", 'update', [$row['trno'], $row['line']]);
          }
          break;
      }

      if ($companyid == 16 && $row['doc'] == 'PO') { //ati
        $this->logger->sbcwritelog($row['cdrefx'], $config, 'STOCK', 'VOID - Doc:' . $row['doc'] . ' Line:' . $row['cdlinex'] . ' Item Name:' . $row['itemname'] . ' WH:' . $row['wh'] . ' isQTY:' . $row['isqty'], 'transnum_log');
      }

      $returnrow = $this->data($config, $row['line']);
      return ['status' => true, 'msg' => 'Successfully voided.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Please input valid quantity.'];
    }
  }

  public function data($config, $line)
  {
    $tableid = $config['params']['tableid'];

    $row = $config['params']['doc'];
    $addedfilter = '';
    if ($line != 0) {
      $addedfilter = ' and stock.line=' . $line;
    }

    switch ($row) {
      case 'PR':
        $select = ", stock.cdqa,stock.poqa,stock.rrqa, stock.oqqa";
        $table = "hprstock";
        $tblhead = "hprhead";
        $left = "left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line ";
        break;

      case 'CD':
        $select = " , stock.oqqa";
        $table = "hcdstock";
        $tblhead = "hcdhead";
        $left = "left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline";
        $addedfilter = ' and stock.status = 1';
        break;
      case 'PO':
        $select = ",stock.cdrefx, stock.cdlinex,stock.reqtrno,stock.reqline";
        $table = "hpostock";
        $tblhead = "hpohead";
        $left = "left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline";
        break;
    }

    $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,head.doc,head.docno,0 as oqty,stock.line,stock.itemid,
                   item.barcode,item.itemname, info.itemdesc,stock.uom,wh.client as wh,
                   round(stock.rrqty," . $this->companysetup->getdecimal('qty', $config['params']) . ") as isqty,
                   round((stock.qty-stock.qa)/ case when ifnull(uom2.factor,0)<>0 then uom2.factor when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,
                    round(ifnull(stock.voidqty/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,0)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as voidqty, 
                   '' as bgcolor,stock.qty $select
            from $table as stock left join item on item.itemid = stock.itemid 
            left join uom on uom.itemid = item.itemid and uom.uom = stock.uom 
            left join client as wh on wh.clientid=stock.whid 
            $left
            left join $tblhead as head on head.trno=stock.trno
            left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1
            left join uomlist as uom2 on uom2.uom=info.uom2 and uom2.isconvert=1
            where stock.trno =? 
            " . $addedfilter;
    $data = $this->coreFunctions->opentable($qry, [$tableid]);
    return $data;
  } //end function
} //end class
