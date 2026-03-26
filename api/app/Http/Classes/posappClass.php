<?php

namespace App\Http\Classes;

use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;
use App\Http\Classes\posClass;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

use Exception;
use Throwable;

class posappClass
{

  private $othersClass;
  private $coreFunctions;
  private $posClass;
  private $logger;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->posClass = new posClass;
    $this->logger = new Logger;
  }

  public function loadposappbranch()
  {
    $b = $this->coreFunctions->opentable("select client, clientname, clientid, addr from client where isbranch=1 and center='001'");
    return json_encode(['branches' => $b]);
  }

  public function loadposappstation($params)
  {
    $s = $this->coreFunctions->opentable("select line, clientid, station from branchstation where clientid=? order by line", [$params['clientid']]);
    return json_encode(['stations' => $s]);
  }

  public function loadposappusers($params)
  {
    $branch = $params['branch'];
    $u = $this->coreFunctions->opentable("select branchusers.line as userid, branchusers.type as usertype, CASE WHEN branchusers.type='Administrator' THEN 1 WHEN branchusers.type='Supervisor' THEN 3 ELSE 2 END AS accessid, branchusers.username, branchusers.password, 
      branchusers.name, branchusers.pincode, branchusers.pincode2, branchusers.isinactive, branchusers.dlock FROM branchusers
      WHERE branchusers.clientid=(select clientid FROM client WHERE clientid='" . $branch . "' AND isbranch=1)");
    return $u;
  }

  public function sbcposapp($params)
  {
    switch ($params['id']) {
      case md5('loadCustomers2'):
        $date = $this->othersClass->getCurrentTimeStamp();
        $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive from client where iscustomer = 1");
        return json_encode(['customers' => $customers, 'dateid' => $date]);
        break;
      case md5('loadUpdatedCustomers'):
        $date = $this->othersClass->getCurrentTimeStamp();
        $dateid = $params['dateid'];
        $ccount = $params['ccount'];
        $isequal = true;
        $counts = $this->coreFunctions->opentable("select count(*) as ccount from client where iscustomer=1 and isinactive=0");
        if (!empty($counts)) {
          if ($counts[0]->ccount != $ccount) $isequal = false;
        }
        if ($dateid == '-' || $dateid == 'null') {
          $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive from client where iscustomer = 1");
        } else {
          $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive from client where iscustomer = 1 and date(dlock) >= date('" . $dateid . "')");
        }
        return json_encode(['customers' => $customers, 'dateid' => $date, 'isequal' => $isequal]);
        break;
      case md5('loadUpdatedItems'):
        $date = $this->othersClass->getCurrentTimeStamp();
        if (isset($params['wh'])) {
          $wh = $params['wh'];
        } else {
          $wh = '';
        }
        $dateid = $params['dateid'];
        if ($dateid == '-' || $dateid == 'null') {
          $qry = "select itemid, barcode, itemname, amt, amt as newamt, uom, uom as newuom, 0 as qty, '' as rem, disc, isinactive, isvat as istaxable, amt2, famt, amt4, amt5, amt6 from item order by itemid";
        } else {
          $qry = "select itemid, barcode, itemname, amt, amt as newamt, uom, uom as newuom, 0 as qty, '' as rem, disc, isinactive, isvat as istaxable, amt2, famt, amt4, amt5, amt6 from item where date(dlock) >= date('" . $dateid . "') order by itemid";
        }
        $items = $this->coreFunctions->opentable($qry);
        if (count($items) > 0) {
          foreach ($items as $i) {
            if ($i->uom == '') {
              $i->factor = $i->newfactor = 1;
            } else {
              $uom = $this->coreFunctions->opentable("select factor from uom where itemid = '" . $i->itemid . "' and uom = '" . $i->uom . "'");
              if (empty($uom)) {
                $i->factor = $i->newfactor = 1;
              } else {
                $i->factor = $i->newfactor = $uom[0]->factor;
              }
            }
          }
        }
        return json_encode(['items' => $items, 'date' => $date]);
        break;
      case md5('loadItems'):
        $date = $this->othersClass->getCurrentTimeStamp();
        $qry = "select itemid, barcode, itemname, amt, amt as newamt, uom, uom as newuom, 0 as qty, '' as rem, disc, isinactive, isvat as istaxable, amt2, famt, amt4, amt5, amt6 from item order by itemid";
        $items = $this->coreFunctions->opentable($qry);
        if (count($items) > 0) {
          foreach ($items as $i) {
            if ($i->uom == '') {
              $i->factor = $i->newfactor = 1;
            } else {
              $uom = $this->coreFunctions->opentable("select factor from uom where itemid = '" . $i->itemid . "' and uom = '" . $i->uom . "'");
              if (empty($uom)) {
                $i->factor = $i->newfactor = 1;
              } else {
                $i->factor = $i->newfactor = $uom[0]->factor;
              }
            }
          }
        }
        return json_encode(['items' => $items, 'date' => $date]);
        break;
      case md5('loadUoms'):
        $date = $this->othersClass->getCurrentTimeStamp();
        $dateid = $params['dateid'];
        $uoms = $this->coreFunctions->opentable("select line, itemid, uom, factor, amt from uom");

        return json_encode(['uom' => $uoms, 'date' => $date]);
        break;
      case md5('loadWarehouse'):
        $branch = $params['branch'];
        $wh = $this->coreFunctions->opentable("select b.whid, c.client as wh, c.clientname as whname, c.addr as whaddr, b.isinactive, b.isdefault from branchwh as b left join client as c on c.clientid = b.whid where b.clientid ='" . $branch . "' order by line");
        return json_encode(['wh' => $wh]);
        break;
      case md5('checkPassword'):
        $username = $params['username'];
        $password = $params['password'];
        $u = $this->coreFunctions->opentable("select line from branchusers where username = '" . $username . "' and md5(md5(password)) = md5('" . $password . "')");
        if (empty($u)) {
          return json_encode(['msg' => 'Invalid Password', 'status' => false]);
        } else {
          return json_encode(['msg' => '', 'status' => true]);
        }
        break;
      case md5('saveTransactions'):
        $d = $params['order'];
        if (!isset($d['center'])) {
          $d['center'] = '001';
        }
        $params = [
          'trno' => $d['trno'],
          'userid' => $d['userid'],
          'doc' => $d['doc'],
          'orderno' => $d['orderno'],
          'dateid' => $d['dateid'],
          'center' => $d['center'],
          'username' => $d['username'],
          'client' => $d['client'],
          'clientname' => $d['clientname'],
          'addr' => $d['addr'],
          'rem' => $d['rem'],
          'station' => $d['station'],
          'branch' => $d['branch'],
          'cash' => $d['cash'],
          'card' => $d['card'],
          'credit' => $d['credit'],
          'debit' => $d['debit'],
          'cheque' => $d['cheque'],
          'eplus' => $d['eplus'],
          'online' => $d['online'],
          'loyaltypoints' => $d['loyaltypoints'],
          'smac' => $d['smac'],
          'voucher' => $d['voucher'],
          'tendered' => $d['tendered'],
          'change' => $d['change'],
          'transtype' => $d['transtype'],
          'wh' => $d['wh'],
          'itemcount' => $d['itemcount'],
          'stdiscamt' => $d['stdiscamt'],
          'total' => $d['total'],
          'clientname' => $d['clientname'],
          'acctno' => $d['acctno'],
          'cardtype' => $d['cardtype'],
          'batch' => $d['batch'],
          'approval' => $d['approval'],
          'checktype' => $d['checktype'],
          'bankname' => $d['bankname'],
          'discamt' => $d['discamt'],
          'nvat' => $d['nvat'],
          'vatamt' => $d['vatamt'],
          'vatex' => $d['vatex'],
          'lessvat' => $d['lessvat'],
          'sramt' => $d['sramt'],
          'pwdamt' => $d['pwdamt'],
          'voiddate' => $d['voiddate'],
          'voidby' => $d['voidby'],
          'transtime' => $d['transtime']
        ];
        $items = $d['items'];
        $head = $this->insertHead($params);
        $item = [];
        $itemcount = $gtotal = 0;
        if ($head['status']) {
          if (isset($d['stdiscamt'])) {
            if ($d['stdiscamt'] !== '' && $d['stdiscamt'] !== null && $d['stdiscamt'] !== 0 && $d['stdiscamt'] !== '0') {
              $this->saveSTDiscount($params, $head['data'][0]['trno']);
            }
          }
          foreach ($items as $i) {
            $stock = $this->insertStock($i, $head['data'][0], $params);
            if ($stock['status']) {
              $stock['data'][0]->ext = $i['total'];
              $itemcount = $stock['itemcount'];
              $gtotal = $stock['gtotal'];
              array_push($item, $stock);
            } else {
              return json_encode(['status' => false, 'head' => $head, 'stocks' => $item, 'itemcount' => $itemcount, 'gtotal' => $gtotal]);
            }
          }

          $result = $this->posClass->generatelatrans($params['branch'], $params['station'], $head['data'][0]['trno']);
          if ($result['status']) {
            return json_encode(['status' => true, 'head' => $head, 'stocks' => $item, 'itemcount' => $itemcount, 'gtotal' => $gtotal]);
          } else {
            return json_encode(['status' => false, 'head' => $head, 'stocks' => $item, 'itemcount' => $itemcount, 'gtotal' => $gtotal]);
          }
        } else {
          return json_encode(['status' => false, 'head' => $head, 'stocks' => $item, 'itemcount' => $itemcount, 'gtotal' => $gtotal]);
        }
        break;
      case md5('loadOrders'):
        $datefrom = $params['datefrom'];
        $dateto = $params['dateto'];
        $doc = $params['doc'];
        $username = $params['username'];
        $str = $params['str'];
        $page = $params['page'];
        $param = [
          'doc' => $doc,
          'txt' => $str,
          'date1' => $datefrom,
          'date2' => $dateto,
          'filter' => '',
          'ifilter' => '',
          'page' => $page
        ];
        $docs = json_decode($this->loaddocs($param), true);
        return json_encode(['docs' => $docs['docs'], 'total' => $docs['total'], 'ordercount' => $docs['ordercount']]);
        break;
    }
  }

  public function loaddocs($params)
  {
    if (isset($params['page'])) {
      $params['page'] = $this->othersClass->val($params['page']);
      if ($params['page'] == 0) $params['page'] = 1;
    } else {
      $params['page'] = 1;
    }
    $limitwaw = ($params['page'] - 1) * 20;
    if ($params['date1'] != '' && $params['date2'] != '') {
      $params['date2'] = date('Y-m-d', strtotime($params['date2'] . '+1 days'));
      $params['date1'] = date('Y-m-d', strtotime($params['date1']));
      $filter1 = " and head.dateid between '" . $params['date1'] . "' and '" . $params['date2'] . "' ";
    } else {
      $filter1 = "";
    } //end if

    if ($params['txt'] != '') {
      $params['filter'] = " where client.clientname like '%" . $params['txt'] . "%' " . $filter1;
    } else {
      if ($filter1 != '') {
        $params['filter'] = " where 1=1 " . $filter1;
      } else {
        $params['filter'] = " where 1=1 ";
      }
    } //end if

    $alldocs = $this->coreFunctions->opentable("select head.trno from head left join client on client.clientid=head.clientid " . $params['filter'] . " and head.doc='" . $params['doc'] . "' and head.postdate is null");
    $doc = $this->coreFunctions->opentable("select 'Draft' as docstat, head.trno, head.docno, client.clientname, client.client, client.addr as address, client.tel, head.dateid, head.postedby, head.postdate, head.yourref, head.ourref, head.rem from head left join client on client.clientid=head.clientid " . $params['filter'] . " and head.doc='" . $params['doc'] . "' and head.postdate is null order by head.docno desc limit " . $limitwaw . ", 20");
    $total = 0;
    $ordercount = 0;
    if (!empty($alldocs)) {
      $ordercount = count($alldocs);
      foreach ($alldocs as $d) {
        $grandtotal = $this->getgrandtotal($d->trno);
        if (!empty($grandtotal)) $total += $grandtotal[0]->gTotal;
      }
    }
    if (!empty($doc)) {
      foreach ($doc as $dd) {
        $grandtotal = $this->getgrandtotal($dd->trno);
        if (!empty($grandtotal)) {
          $dd->itemcount = $grandtotal[0]->itemcount;
          $dd->grandtotal = $grandtotal[0]->gTotal;
        } else {
          $dd->grandtotal = '0';
          $dd->itemcount = '0';
        }
      }
    }
    return json_encode(['docs' => $doc, 'total' => $total, 'ordercount' => $ordercount]);
  } //end fn

  public function saveSTDiscount($data, $trno)
  {
    $itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=?', ['*']);
    $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'barcode=?', ['*']);
    $itemname = $this->coreFunctions->getfieldvalue('item', 'itemname', 'barcode=?', ['*']);
    $last_line = $this->coreFunctions->getfieldvalue('stock', 'line', 'trno=?', [$trno], 'line desc');
    if ($last_line == '') $last_line = 0;
    $last_line += 1;
    switch ($data['transtype']) {
      case 'RT':
      case 'ST':
      case 'PT':
      case 'DT':
        $data['stdiscamt'] = $data['stdiscamt'] * -1;
        break;
    }
    $data2 = [
      'line' => $last_line,
      'trno' => $trno,
      'station' => $data['station'],
      'itemid' => $itemid,

      'itemname' => $itemname,
      'wh' => $data['wh'],
      'qty' => 1,
      'isamt' => $data['stdiscamt'],
      'amt' => $data['stdiscamt'],
      'iss' => 1,
      'isqty' => 1,
      'ext' => $data['stdiscamt']
    ];
    $this->coreFunctions->sbcinsert('stock', $data2);
  }

  public function insertStock($data, $head, $order)
  {
    $doc = $order['doc'];
    $msg = "";
    $status = $ins = false;
    $stock = [];
    $last_line = $this->coreFunctions->getfieldvalue('stock', 'line', 'trno=? and station=?', [$head['trno'], $head['station']], 'line desc');
    if ($last_line == '') $last_line = 0;
    $last_line += 1;

    $itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=?', [$data['barcode']]);

    $item = $this->coreFunctions->opentable("select itemname, uom from item where barcode = '" . $data['barcode'] . "'");
    $data['itemname'] = $item[0]->itemname;

    $data['vat'] = 0;
    $data['ext'] = $data['total'];
    if (!isset($data['ext'])) {
      $data['ext'] = "";
    }
    if (!isset($data['qty'])) {
      $data['qty'] = 0;
    }
    if (!isset($data['amt'])) {
      $data['amt'] = 0;
    }
    if (!isset($data['cost'])) {
      $data['cost'] = 0;
    }
    if (!isset($data['void'])) {
      $data['void'] = 0;
    }
    if (!isset($data['loc'])) {
      $data['loc'] = "";
    }
    if (!isset($data['ref'])) {
      $data['ref'] = "";
    }
    if (!isset($data['refx'])) {
      $data['refx'] = 0;
    }
    if (!isset($data['linex'])) {
      $data['linex'] = 0;
    }

    if (!isset($data['qa'])) {
      $data['qa'] = 0;
    }
    if (!isset($data['isdiplomat'])) {
      $data['isdiplomat'] = 0;
    }
    if (!isset($data['discamt'])) {
      $data['discamt'] = 0;
    }
    if (!isset($data['nvat'])) {
      $data['nvat'] = 0;
    }
    if (!isset($data['vatamt'])) {
      $data['vatamt'] = 0;
    }
    if (!isset($data['vatex'])) {
      $data['vatex'] = 0;
    }
    if (!isset($data['lessvat'])) {
      $data['lessvat'] = 0;
    }
    if (!isset($data['sramt'])) {
      $data['sramt'] = 0;
    }
    if (!isset($data['pwdamt'])) {
      $data['pwdamt'] = 0;
    }

    $data['isqty'] = $data['qty'];
    $data['isamt'] = str_replace(',', '', $data['amt']);

    if (!isset($data['disc'])) {
      $data['disc'] = 0;
    }
    if (!isset($data['factor'])) {
      $data['uomfactor'] = 1;
    } else {
      $data['uomfactor'] = $data['factor'];
    }
    if (!isset($data['vat'])) {
      $data['vat'] = 0;
    }
    if ($head['voiddate'] !== '' && $head['voiddate'] !== null) {
      $data['ref'] = $head['docno'];
    }

    $data2 = [
      'line' => $last_line,
      'trno' => $head['trno'],
      'station' => $head['station'],
      'itemid' => $itemid,
      'dateid' => $order['dateid'],

      'itemname' => $data['itemname'],
      'uom' => $data['uom'],
      'wh' => $head['wh'],
      'disc' => $data['disc'],
      'rem' => $data['rem'],
      'qty' => abs($data['isqty']),
      'isamt' => $data['isamt'],
      'amt' => $data['amt'],
      'iss' => abs($data['iss']),
      'isqty' => $data['isqty'],
      'qa' => $data['qa'],
      'isdiplomat' => $data['isdiplomat'],
      'discamt' => $data['discamt'],
      'nvat' => $data['nvat'],
      'vatamt' => $data['vatamt'],
      'vatex' => $data['vatex'],
      'lessvat' => $data['lessvat'],
      'sramt' => $data['sramt'],
      'pwdamt' => $data['pwdamt'],
      'ext' => $data['ext'],
      'ref' => $data['ref'],
      'createdate' => $data['createdate']
    ];
    switch ($head['transtype']) {
      case 'RT':
      case 'PT':
      case 'ST':
      case 'DT':
        $data2['iss'] = 0;
        break;

      default:
        $data2['qty'] = 0;
        break;
    }
    $ins = $this->coreFunctions->sbcinsert('stock', $data2);

    if ($ins == 1) {
      $msg = "Stock was successfully saved.";
      $status = true;
    } else {
      $msg = "Error occured while saving stock. [STOCK_ERR001]";
      $status = false;
    } //end if

    $stock = $this->openstockline($head['trno'], $last_line);
    $grandtotal = $this->getgrandtotal($head['trno']);
    if (!empty($grandtotal)) {
      $itemcount = $grandtotal[0]->itemcount;
      $gtotal = $grandtotal[0]->grandtotal;
    } else {
      $itemcount = $gtotal = '0';
    } //end if
    return array('msg' => $msg, 'status' => $status, 'data' => $stock, 'itemcount' => $itemcount, 'gtotal' => $gtotal);
  }

  public function openstockline($trno, $line)
  {
    $stock = $this->coreFunctions->opentable("select line, trno, station, itemid, bcode, itemname, uom, wh, disc, rem, qty, isamt,
      amt, iss, isqty, qa, isdiplomat, discamt, nvat, vatamt, vatex, lessvat, sramt, pwdamt, ref from stock where trno=? and line=?", [$trno, $line]);
    return $stock;
  }

  public function getgrandtotal($trno)
  {
    $qry = "select trno, sum(isqty) as kilototal, count(*) as itemcount, sum(ext) as grandtotal, sum(isamt * isqty) as gTotal, 0 as forexgrandtotal from stock where trno=? group by trno";
    $total = $this->coreFunctions->opentable($qry, [$trno]);
    return $total;
  }

  public function insertHead($data)
  {
    $msg = "";
    $status = false;
    $d = $newdata = [];
    $data = $this->othersClass->sanitize($data, 'ARRAY');
    $date = $this->othersClass->getCurrentTimeStamp();

    $trno = $data['trno'];


    $clientid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$data['client']]);

    if ($data['tendered'] == '') {
      $data['tendered'] = 0;
    }
    if ($data['change'] == '') {
      $data['change'] = 0;
    }
    $data2 = [
      'docno' => $data['orderno'],
      'station' => $data['station'],
      'center' => $data['center'],
      'trno' => $trno,
      'seq' => (substr($data['orderno'], $this->othersClass->SearchPosition($data['orderno']), strlen($data['orderno']))),
      'bref' => $data['doc'],
      'doc' => $data['doc'],
      'cash' => $data['cash'],
      'card' => $data['card'],
      'cr' => $data['credit'],
      'debit' => $data['debit'],
      'cheque' => $data['cheque'],
      'eplus' => $data['eplus'],
      'onlinedeals' => $data['online'],
      'smac' => $data['smac'],
      'voucher' => $data['voucher'],
      'lp' => $data['loyaltypoints'],
      'tendered' => $data['tendered'],
      'change' => $data['change'],
      'transtype' => $data['transtype'],
      'wh' => $data['wh'],
      'clientid' => $clientid,
      'uploaddate' => $date,
      'dateid' => $data['dateid'],
      'rem' => $data['rem'],
      'branch' => $data['branch'],
      'userid' => $data['userid'],
      'itemcount' => $data['itemcount'],
      'postdate' => null,
      'amt' => $data['total'],
      'clientname' => $data['clientname'],
      'acctno' => $data['acctno'],
      'cardtype' => $data['cardtype'],
      'batch' => $data['batch'],
      'approval' => $data['approval'],
      'checktype' => $data['checktype'],
      'bankname' => $data['bankname'],
      'discamt' => $data['discamt'],
      'nvat' => $data['nvat'],
      'vatamt' => $data['vatamt'],
      'vatex' => $data['vatex'],
      'lessvat' => $data['lessvat'],
      'sramt' => $data['sramt'],
      'pwdamt' => $data['pwdamt'],
      'voiddate' => $data['voiddate'] == '' ? null : $data['voiddate'],
      'voidby' => $data['voidby']
    ];
    $exist = $this->coreFunctions->opentable("select webtrno from head where docno=? and station=? and branch=?", [$data['orderno'], $data['station'], $data['branch']]);
    if (!empty($exist)) {
      if ($exist[0]->webtrno == 0) {
        $this->coreFunctions->execqry("delete from head where docno=? and station=? and branch=?", '', [$data['orderno'], $data['station'], $data['branch']]);
        $this->coreFunctions->execqry("delete from stock where trno=? and station=?", '', [$data['trno'], $data['station']]);
      }
    }
    $ins = $this->coreFunctions->sbcinsert('head', $data2);
    if ($ins > 0) {
      $msg = "New document saved. [" . $data['orderno'] . "]";
      $newdata = json_decode($this->loadheaddata($trno), true);
      $newdata = $newdata['head'];
      $status = true;
    } else {
      $msg = "Error saving new document";
    }
    return array('msg' => $msg, 'status' => $status, 'data' => $newdata);
  }

  public function loadheaddata($trno)
  {
    $head = $this->coreFunctions->opentable("select docno, station, center, trno, seq, bref, doc, cash, card, cr, debit, cheque, eplus,
      onlinedeals, smac, voucher, lp, tendered, `change`, transtype, wh, clientid, uploaddate, dateid, rem, branch, userid, itemcount, amt,
      clientname, acctno, cardtype, batch, approval, checktype, bankname, discamt, nvat, vatamt, vatex, lessvat, sramt, pwdamt, voiddate, voidby
      from head where trno=?", [$trno]);
    return json_encode(['head' => $head]);
  }
}
