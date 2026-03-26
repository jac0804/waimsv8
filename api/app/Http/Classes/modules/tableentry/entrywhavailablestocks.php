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
use App\Http\Classes\lookup\warehousinglookup;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;

class entrywhavailablestocks
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AVAILABLE LOCATIONS';
  public $gridname = 'inventory';
  public $stock = 'lastock';
  public $dqty = 'isqty';
  public $hqty = 'iss';
  public $damt = 'isamt';
  public $hamt = 'amt';
  public $tablelogs = 'table_log';
  public $tablelogs_del = 'del_table_log';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:1100px;max-width:1100px;';
  private $fields = [];
  public $showclosebtn = false;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->warehousinglookup = new warehousinglookup;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2022
    );
    return $attrib;
  }


  public function createTab($config)
  {
    $type = $config['params']['ledgerdata']['type'];

    $action = 0;
    $qty = 1;
    $bal = 2;
    $whrem = 3;
    $location = 4;
    $clientname = 5;
    $tab = [
      $this->gridname => ['gridcolumns' => ['action', 'qty', 'bal', 'whrem', 'location', 'clientname']]
    ];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Warehouse Name';
    if ($type == 'changeloc') {
      $obj[0][$this->gridname]['columns'][$qty]['label'] = 'Transfer Qty.';
      $obj[0][$this->gridname]['columns'][$qty]['type'] = 'label';
    } else {
      $obj[0][$this->gridname]['columns'][$qty]['label'] = 'Split Qty.';
    }

    $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:40px;whiteSpace: normal;min-width:40px; max-width:40px;';
    $obj[0][$this->gridname]['columns'][$qty]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $obj[0][$this->gridname]['columns'][$bal]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; max-width:80px;';
    $obj[0][$this->gridname]['columns'][$location]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';

    $obj[0][$this->gridname]['columns'][$location]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$bal]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$action]['btns']['save']['label'] = 'Save Qty.';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function save($config)
  {

    $adminid = $config['params']['adminid'];
    $ispallet = $this->companysetup->getispallet($config['params']);

    $row = $config['params']['row'];
    $row['qty'] = $this->othersClass->sanitizekeyfield('qty', $row['qty']);
    $row['isqty'] = $this->othersClass->sanitizekeyfield('qty', $row['isqty']);
    $row['whrem'] = $this->othersClass->sanitizekeyfield('rem', $row['whrem']);
    $replaceqty = 0;

    $splittype = $row['type'];

    $isreplacement = false;
    if ($row['sjtype'] == 'REPLACEMENT') {
      $isreplacement = true;
      $replaceqty = $this->othersClass->sanitizekeyfield('qty', $row['replaceqty']);
    }

    if ($row['whrem'] == '') {
      return ['status' => false, 'msg' => 'Please input valid Remarks'];
    }

    if ($row['isqty'] == 0) {
      return ['status' => false, 'msg' => 'Invalid Quantity'];
    }

    if ($isreplacement) {
      if ($row['qty'] > $row['replaceqty']) {
        return ['status' => false, 'msg' => 'Invalid split qty (' . $row['qty'] . '), must not greater or equal from the replacement qty (' . $row['replaceqty'] . ')'];
      }
    }

    if ($row['qty'] >= $row['isqty']) {
      if ($splittype == 'changeloc') {
        if ($row['qty'] == $row['isqty']) {
          goto continuehere;
        } else {
          return ['status' => false, 'msg' => 'Quantity must be same in change location process'];
        }
      }
      return ['status' => false, 'msg' => 'Invalid split qty, must not greater or equal from the orginal qty'];
    } else {
      continuehere:
      $stock = $this->coreFunctions->opentable('select s.trno, s.line, s.refx, s.linex, s.uom, s.disc, s.rem, s.rrcost, s.cost, s.rrqty, s.qty,
      s.isamt, s.amt, s.isqty, s.iss, s.ext, s.qa, s.ref, s.void, s.encodeddate, s.encodedby, s.editdate, s.editby, s.loc, s.loc2, s.sku,
      s.expiry, s.itemid, s.whid, s.rebate, s.palletid, s.locid, s.palletid2, s.locid2, wh.client as wh, h.doc, item.barcode
      from lastock as s left join client as wh on wh.clientid=s.whid left join lahead as h on h.trno=s.trno
      left join item on item.itemid=s.itemid
      where s.trno=? and s.line=?', [$row['trno'], $row['line']]);

      if (!empty($stock)) {
        $bal = $this->coreFunctions->datareader(
          'select round(ifnull(sum(bal),0),' . $this->companysetup->getdecimal('qty', $config['params']) . ') as value from rrstatus where itemid=? and whid=? and locid=?',
          [$row['itemid'], $row['whid'], $row['locid']]
        );

        if ($bal != 0) {
          if ($row['qty'] > $bal) {
            return ['status' => false, 'msg' => 'Invalid split qty of ' . $row['qty'] . ', available balance is ' . $bal];
          } else {
            $trno = $row['trno'];
            $line = 0;
            $itemid = $row['itemid'];
            $whid = $row['whid'];
            $amt = $stock[0]->isamt;
            $qty = $row['qty'];
            $disc = $stock[0]->disc;
            $uom = $stock[0]->uom;

            $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
            $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
            $factor = 1;
            if (!empty($item)) {
              $item[0]->factor = $this->othersClass->val($item[0]->factor);
              if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            }

            $cur = $this->coreFunctions->getfieldvalue('lahead', 'cur', 'trno=?', [$trno]);
            $curtopeso = $this->coreFunctions->getfieldvalue('lahead', 'forex', 'trno=?', [$trno]);
            $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);

            if ($splittype == 'splitqty') {
              $qry = "select line as value from lastock where trno=? order by line desc limit 1";
              $line = $this->coreFunctions->datareader($qry, [$trno]);
              if ($line == '') {
                $line = 0;
              }
              $line = $line + 1;
            } else {
              $line = $row['line'];
            }

            $data = [
              'trno' => $trno,
              'line' => $line,
              'itemid' => $itemid,
              $this->damt => $amt,
              $this->hamt => round($computedata['amt'] * $curtopeso, 2),
              $this->dqty => $qty,
              $this->hqty => $computedata['qty'],
              'ext' => $computedata['ext'],
              'disc' => $disc,
              'whid' => $whid,
              'refx' => $stock[0]->refx,
              'linex' => $stock[0]->linex,
              'ref' => $stock[0]->ref,
              'loc' => $stock[0]->loc,
              'expiry' => $stock[0]->expiry,
              'uom' => $uom,
              'locid' => $row['locid'],
              'palletid' => 0,
              'rebate' => $stock[0]->rebate
            ];

            foreach ($data as $key => $value) {
              $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
            }
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            $data['editdate'] = $current_timestamp;
            $data['editby'] = $config['params']['user'];
            $data['encodeddate'] = $current_timestamp;
            $data['encodedby'] = $config['params']['user'];

            if ($splittype == 'changeloc') {

              if ($this->coreFunctions->execqry('update costing set isposted=1 where trno=? and line=?', 'update', [$trno, $line])) {

                if ($ispallet) {
                  $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $stock[0]->doc, $config['params']);
                } else {
                  $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $data['expiry'], $trno, $line, $data['iss'], $stock[0]->doc, $config['params']['companyid']);
                }

                if ($cost != -1) {
                  $dataupdate = [
                    'cost' => $cost,
                    'locid' => $data['locid'],
                    'editdate' => $current_timestamp,
                    'editby' => $config['params']['user'],
                    'encodeddate' => $current_timestamp,
                    'encodedby' => $config['params']['user']
                  ];
                  if ($this->coreFunctions->sbcupdate($this->stock, $dataupdate, ['trno' => $trno, 'line' => $line])) {
                    $this->coreFunctions->execqry('delete from costing where trno=? and line=? and isposted=1', 'delete', [$trno, $line]);

                    $this->insertwhrem($trno, $config['params']['user'], $row['whremid'], $stock, $data['iss']);

                    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'CHANGE LOCATION - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $stock[0]->wh . ' ext:' . $computedata['ext']);

                    if ($isreplacement) {
                      $this->coreFunctions->execqry("update lastock set pickerid=" . $adminid . " where trno=? and line=? and pickerid=0", 'update', [$trno, $line]);
                      $this->coreFunctions->execqry("update lastock set pickerstart='" . $current_timestamp . "' where trno=? and line=? and pickerstart is null", 'update', [$trno, $line]);
                      $this->coreFunctions->execqry("update replacestock set pickerid=" . $adminid . ", pickerstart='" . $current_timestamp . "', qa=qa+" . $data['iss'] . " where trno=? and line=?", 'update', [$trno, $line]);
                    }

                    return ['status' => true, 'msg' => 'Successfully change location', 'backlisting' => true, 'row' => $config['params']['row']];
                  } else {
                    return ['status' => false, 'msg' => 'Failed to update new location'];
                  }
                } else {
                  $this->coreFunctions->execqry('delete from costing where trno=? and line=? and isposted=0', 'delete', [$trno, $line]);
                  $this->coreFunctions->execqry('update costing set isposted=0 where trno=? and line=?', 'update', [$trno, $line]);
                  return ['status' => false, 'msg' => 'No available balance'];
                }
              } else {
                return ['status' => false, 'msg' => 'Failed to update costing table of original record'];
              }
            } else {

              //split qty
              if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
                $havestock = true;
                $msg = 'Item was successfully added.';
                $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $stock[0]->wh . ' ext:' . $computedata['ext']);

                if ($ispallet) {
                  $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $stock[0]->doc, $config['params']);
                } else {
                  $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $stock[0]->doc, $config['params']['companyid']);
                }

                if ($cost != -1) {
                  $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost, 'pickerid' => $adminid, 'pickerstart' => $current_timestamp], ['trno' => $trno, 'line' => $line]);

                  if ($this->updateorignalstock($config, $ispallet, $trno, $row['line'], $itemid, $whid, $uom, $stock[0]->iss - $row['qty'], $amt, $disc, $factor, $cur, $curtopeso, $stock)) {

                    if ($this->setserveditems($stock[0]->refx, $stock[0]->linex, $stock[0]->doc) == 0) {
                      $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
                      $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
                      $this->setserveditems($stock[0]->refx, $stock[0]->linex, $stock[0]->doc);
                      $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                      $return = false;
                      $msg = "(" . $stock[0]->barcode . ") Qty Received is Greater than RR Qty.";
                    } else {

                      $this->insertwhrem($trno, $config['params']['user'], $row['whremid'], $stock, $row['qty']);

                      if ($isreplacement) {
                        if ($row['qty'] < $replaceqty) {
                          $this->coreFunctions->execqry("update replacestock set isqty=isqty-" . $row['qty'] . " where trno=? and line=?", 'update', [$trno, $stock[0]->line]);
                        } else {
                          $this->coreFunctions->execqry("update replacestock set pickerid=" . $adminid . ", pickerstart='" . $current_timestamp . "', qa=qa+" . $row['qty'] . " where trno=? and line=?", 'update', [$trno, $stock[0]->line]);
                        }
                        $this->coreFunctions->execqry("update lastock set pickerid=" . $adminid . " where trno=? and line=? and pickerid=0", 'update', [$trno, $stock[0]->line]);
                        $this->coreFunctions->execqry("update lastock set pickerstart='" . $current_timestamp . "' where trno=? and line=? and pickerstart is null", 'update', [$trno, $stock[0]->line]);

                        $datareplacestock = [
                          'trno' => $trno,
                          'line' => $line,
                          'isqty' => $row['qty'],
                          'locid' => $stock[0]->locid,
                          'dateid' => $this->othersClass->getCurrentTimeStamp(),
                          'user' => $config['params']['user']
                        ];

                        $exist = $this->coreFunctions->opentable('select trno from replacestock where trno=? and line=?', [$trno, $line]);
                        if (!$exist) {
                          $this->coreFunctions->sbcinsert('replacestock', $datareplacestock);
                          if ($row['qty'] < $replaceqty) {
                            $this->coreFunctions->execqry("update replacestock set pickerid=" . $adminid . ", pickerstart='" . $current_timestamp . "', qa=qa+" . $row['qty'] . " where trno=? and line=?", 'update', [$trno, $line]);
                          }
                        }
                      }

                      return ['status' => true, 'msg' => 'Successfully saved.', 'backlisting' => true, 'row' => $config['params']['row']];
                    }
                  }
                } else {
                  $havestock = false;
                  $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
                  $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
                  $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Qty' . $qty . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $stock[0]->$wh . ' ext:0.0');
                  return ['status' => false, 'msg' => 'No available balance'];
                }
              }
              //end split qty

            }
          }
        } else {
          return ['status' => false, 'msg' => 'No available balance.'];
        }
      }
    }
  }

  public function insertwhrem($trno, $user, $whremid, $row, $qty)
  {
    $rem = [];
    $rem['trno'] = $trno;
    $rem['line'] = $row[0]->line;
    // $rem['palletid'] = $row[0]->palletid;
    $rem['locid'] = $row[0]->locid;
    $rem['splitdate'] = $this->othersClass->getCurrentTimeStamp();
    $rem['user'] = $user;
    $rem['remid'] = $whremid;
    $rem['isqty'] = $qty;
    $this->coreFunctions->sbcinsert('splitstock', $rem);
  }

  public function updateorignalstock($config, $ispallet, $trno, $line, $itemid, $whid, $uom, $qty, $amt, $disc, $factor, $cur, $curtopeso, $stock)
  {
    $return = true;
    $msg = '';

    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor, 0, $cur);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->damt => $amt,
      $this->hamt => round($computedata['amt'] * $curtopeso, 2),
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'ext' => $computedata['ext'],
      'disc' => $disc,
      'whid' => $whid,
      'refx' => $stock[0]->refx,
      'linex' => $stock[0]->linex,
      'ref' => $stock[0]->ref,
      'loc' => $stock[0]->loc,
      'expiry' => $stock[0]->expiry,
      'uom' => $uom,
      'locid' => $stock[0]->locid,
      'rebate' => $stock[0]->rebate
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);

    if ($ispallet) {
      $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], $data['locid'], $data['palletid'], $trno, $line, $data['iss'], $stock[0]->doc, $config['params']);
    } else {
      $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], $data['loc'], $expiry, $trno, $line, $data['iss'], $stock[0]->doc, $config['params']['companyid']);
    }

    if ($cost != -1) {
      $this->coreFunctions->sbcupdate($this->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
    } else {
      $this->coreFunctions->sbcupdate($this->stock, [$this->dqty => 0, $this->hqty => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
      $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $item[0]->barcode . ' Amt:' . $amt . ' Disc:' . $disc . ' wh:' . $wh . ' ext:0.0');
      $return = false;
      $msg = "(" . $stock[0]->barcode . ") Out of Stock.";
    }

    return ['status' => $return, 'msg' => $msg];
  }

  public function loaddata($config)
  {
    $itemid = $config['params']['ledgerdata']['itemid'];
    $whid = $config['params']['ledgerdata']['whid'];
    $qty = $config['params']['ledgerdata']['isqty'];
    $trno = $config['params']['ledgerdata']['trno'];
    $line = $config['params']['ledgerdata']['line'];
    $type = $config['params']['ledgerdata']['type'];
    $locid = $config['params']['ledgerdata']['locid'];
    $palletid = $config['params']['ledgerdata']['palletid'];
    $sjtype = $config['params']['ledgerdata']['sjtype'];
    $replaceqty = 0;
    if (isset($config['params']['ledgerdata']['replaceqty'])) {
      $replaceqty = $config['params']['ledgerdata']['replaceqty'];
    }

    $addonfilter = ' and s.locid<>' . $locid;

    $splitqty = 0;
    if ($type == 'changeloc') {
      $splitqty = $qty;
    }

    $qry = "select s.whid, s.itemid, client.clientname, s.locid, location.loc as location,
    " . $splitqty . " as qty, round(sum(s.bal)," . $this->companysetup->getdecimal('qty', $config['params']) . ") as bal,
    " . $qty . " as isqty, " . $trno . " as trno, " . $line . " as line, 0 as whremid, '' as whrem,
    '" . $type . "' as type, '" . $sjtype . "' as sjtype, " . $replaceqty . " as replaceqty, '' as bgcolor
    from rrstatus as s
    left join client on client.clientid=s.whid
    left join location on location.line=s.locid
    where s.itemid=? and s.whid=? and s.bal<>0 " . $addonfilter . "
    group by s.whid, s.itemid, client.clientname, s.locid, location.loc order by client.clientname, location.loc";

    $data = $this->coreFunctions->opentable($qry, [$itemid, $whid]);
    return $data;
  }

  public function lookupsetup($config)
  {
    return $this->warehousinglookup->lookupwhrem($config);
  }

  public function setserveditems($refx, $linex, $doc)
  {
    if ($refx == 0) {
      return 1;
    }
    $qry1 = "select stock." . $this->hqty . " from lahead as head left join lastock as
    stock on stock.trno=head.trno where head.doc='" . $doc . "' and stock.refx=" . $refx . " and stock.linex=" . $linex;

    $qry1 = $qry1 . " union all select glstock." . $this->hqty . " from glhead left join glstock on glstock.trno=
    glhead.trno where glhead.doc='" . $doc . "' and glstock.refx=" . $refx . " and glstock.linex=" . $linex;

    $qry2 = "select ifnull(sum(" . $this->hqty . "),0) as value from (" . $qry1 . ") as t";
    $qty = $this->coreFunctions->datareader($qry2);
    if ($qty == '') {
      $qty = 0;
    }

    $sotable = '';
    switch ($doc) {
      case 'SD':
        $sotable = 'hsastock';
        break;
    }
    return $this->coreFunctions->execqry("update " . $sotable . " set qa=" . $qty . " where trno=" . $refx . " and line=" . $linex, 'update');
  }
} //end class
