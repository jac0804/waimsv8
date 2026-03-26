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
use App\Http\Classes\moduleClass;

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

use Exception;
use Throwable;

class finedineappClass
{

  private $othersClass;
  private $coreFunctions;
  private $posClass;
  private $logger;
  private $moduleClass;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->posClass = new posClass;
    $this->logger = new Logger;
    $this->moduleClass = new moduleClass;
  }

  public function fdlogin($params)
  {
    $username = $params['username'];
    $password = $params['password'];
    $user = $this->coreFunctions->opentable("select userid, accessid, username, password, name, pincode, pincode2 from useraccess where md5(username)='" . $username . "' and inactive=0");
    if (!empty($user)) {
      foreach ($user as $ukey => $u) {
        $u->password = md5(md5($this->decodeBase64($u->password)));
        $u->pincode = $this->decodeBase64($u->pincode);
        $u->pincode2 = $this->decodeBase64($u->pincode2);
        if ($password == $u->password) {
          return json_encode(['status' => true, 'msg' => '', 'user' => $u]);
        }
        if (($ukey + 1) == count($user)) {
          return json_encode(['status' => false, 'msg' => 'Invalid username or password.', 'user' => ']']);
        }
      }
    } else {
      return json_encode(['status' => false, 'msg' => 'Invalid username or password.', 'user' => []]);
    }
  }

  public function checkFinedineOrders($params)
  {
    $busdate = $params['date'];
    $busdate = date_create($busdate);
    $busdate = date_format($busdate, 'Y-m-d');
    $order = $this->coreFunctions->opentable("select date(dateid) as dateid from salestran where date(dateid)<>'" . $busdate . "' order by line desc");
    if (!empty($order)) {
      return json_encode(['hasOrder' => true, 'date' => $order[0]->dateid]);
    } else {
      return json_encode(['hasOrder' => false, 'date' => '']);
    }
  }

  public function loadfinedineappusers($params)
  {
    $users = $this->coreFunctions->opentable("select userid, accessid, username, password, name, pincode, pincode2 from useraccess where inactive=0");
    if (!empty($users)) {
      foreach ($users as $u) {
        $u->password = $this->decodeBase64($u->password);
        $u->pincode = $this->decodeBase64($u->pincode);
        $u->pincode2 = $this->decodeBase64($u->pincode2);
      }
    }
    return $users;
  }

  public function decodeBase64($str)
  {
    $str = base64_decode($str);
    $str = utf8_decode($str);
    return $str;
  }

  public function sbcfinedineapp($params)
  {
    switch ($params['id']) {
      case md5('saveSubtotalDisc'):
        $tbl = $params['tbl'];
        $disc = $params['disc'];
        $username = $params['username'];
        $date = $this->othersClass->getCurrentDate();
        $orders = $this->coreFunctions->opentable("select * from salestran where client='" . $tbl['client'] . "' order by line asc");
        $totals = 0;
        if (!empty($orders)) {
          foreach ($orders as $o) {
            if ($o->disc != '') {
              $idisc = $this->Discount($o->isamt, $o->disc);
              $totals += $idisc * $o->isqty;
            } else {
              $totals += $o->isamt * $o->isqty;
            }
          }
        }
        $totDisc = $this->Discount($totals, $disc);
        $discount = ($totals - $totDisc) * -1;
        if ($this->coreFunctions->execqry("insert into salestran(client, clientname, barcode, itemname, isamt, isqty, uom, source, grp, status, waiter, encoded, dateid, osno, stdisc) values('" . $tbl['client'] . "', '" . $tbl['clientname'] . "', '***', 'Sub Total Discount -> " . $disc . "', '" . $discount . "', 1, 'pc', 'WH00001', 'A', 'P', '" . $username . "', '" . $date . "', '" . $date . "', 0, '" . $disc . "')", 'insert') > 0) {
          return json_encode(['status' => true, 'msg' => 'Subtotal discount saved.']);
        } else {
          return json_encode(['status' => false, 'msg' => 'An error occurred; please try again.']);
        }
        break;
      case md5('searchItems'):
        $str = $params['str'];
        $cat = $params['cat'];
        $strs = [];
        if ($str != '') {
          $strs = explode(',', $str);
        }
        $filters = '';
        if (count($strs) > 0) {
          foreach ($strs as $s) {
            $s = trim($s);
            if ($s != '') {
              if ($filters != '') {
                $filters .= " and ((itemname like '%" . $s . "%') or (barcode like '%" . $s . "%') or (shortname like '%" . $s . "%')) ";
              } else {
                $filters .= " ((itemname like '%" . $s . "%') or (barcode like '%" . $s . "%') or (shortname like '%" . $s . "%')) ";
              }
            }
          }
        }
        if ($filters != '') {
          $qry = "select * from item where class='" . $cat . "' and " . $filters . " order by itemname";
        } else {
          $qry = "select * from item where class='" . $cat . "' order by itemname";
        }
        $items = $this->coreFunctions->opentable($qry);
        if (!empty($items)) {
          foreach ($items as $i) {
            if ($i->groupid != '') {
              $i->hasItem = true;
            } else {
              $i->hasItem = false;
            }
          }
        }
        return $items;
        break;
      case md5('loadGuestCount'):
        $tbl = $params['tbl'];
        $gc = $this->coreFunctions->opentable("select pvalue from profile where doc='FD' and psection='" . $tbl['client'] . "'");
        if (!empty($gc)) {
          if ($gc[0]->pvalue != '') return $gc;
        }
        return [];
        break;
      case md5('loadFloors'):
        return $this->coreFunctions->opentable("select distinct(flr) as flr from client where isconsignee=1 and flr<>'' order by flr");
        break;
      case md5('loadCategories'):
        return $this->coreFunctions->opentable("select distinct(class) as class from item where class<>'' order by class");
        break;
      case md5('discItems'):
        $data = $params['data'];
        $x = 1;
        $xc = count($data);
        if (!empty($data)) {
          foreach ($data as $d) {
            $this->coreFunctions->execqry("update salestran set disc='" . $d['discdisplay'] . "' where line='" . $d['line'] . "'", 'update');
            if ($x == $xc) {
              return json_encode(['status' => true, 'msg' => 'Item discount saved']);
            }
            $x++;
          }
        } else {
          return json_encode(['status' => false, 'msg' => 'No items to update']);
        }
        break;
      case md5('voidItems'):
        $data = $params['data'];
        $username = $params['username'];
        $hasError = false;
        $x = 1;
        $xc = count($data);
        if (!empty($data)) {
          foreach ($data as $d) {
            if ($this->coreFunctions->execqry("insert into voiditems(line, client, clientname, barcode, itemname, isamt, isqty, uom, isprint, iscollect, isvoid, remarks, source, destination, voidby, issenior, grp, billnumber, isdiplomat, status,waiter, isvoidprint, disc, htable, screg, scsenior, pwddisc, pwd, isemployee, empcode, encoded, dateid, iscomp, ccode, osno, encodedby, issenior2, trno, ext, stdisc, setmenuref, gcount, agent, terminal, disctype) values('" . $d['line'] . "', '" . $d['client'] . "', '" . $d['clientname'] . "', '" . $d['barcode'] . "', '" . $d['itemname'] . "', '" . $d['isamt'] . "', '" . $d['isqty'] . "', '" . $d['uom'] . "', '" . $d['isprint'] . "', '" . $d['iscollect'] . "', 1, '" . $d['vremarks'] . "', '" . $d['source'] . "', '" . $d['destination'] . "', '" . $username . "', '" . $d['issenior'] . "', '" . $d['grp'] . "', '" . $d['billnumber'] . "', '" . $d['isdiplomat'] . "', '" . $d['status'] . "', '" . $d['waiter'] . "', '" . $d['isvoidprint'] . "', '" . $d['disc'] . "', '" . $d['htable'] . "', '" . $d['screg'] . "', '" . $d['scsenior'] . "', '" . $d['pwddisc'] . "', '" . $d['pwd'] . "', '" . $d['isemployee'] . "', '" . $d['empcode'] . "', '" . $d['encoded'] . "', '" . $d['dateid'] . "', '" . $d['iscomp'] . "', '" . $d['ccode'] . "', '" . $d['osno'] . "', '" . $d['encodedby'] . "', '" . $d['issenior2'] . "', '" . $d['trno'] . "', '" . $d['ext'] . "', '" . $d['stdisc'] . "', '" . $d['setmenuref'] . "', '" . $d['gcount'] . "', '" . $d['agent'] . "', '" . $d['terminal'] . "', '" . $d['disctype'] . "')", 'insert') == 0) {
              $hasError = true;
            }
            if ($x == $xc) {
              if ($hasError) {
                foreach ($data as $dd) {
                  $this->coreFunctions->execqry("delete from voiditems where line='" . $dd['line'] . "'", 'delete');
                  return json_encode(['status' => false, 'msg' => 'An error occurred; please try again.']);
                }
              } else {
                foreach ($data as $dd) {
                  $this->coreFunctions->execqry("delete from salestran where line='" . $dd['line'] . "'", 'delete');
                  return json_encode(['status' => true, 'msg' => 'Items tagged as void.']);
                }
              }
            }
            $x++;
          }
        }
        break;
      case md5('loadTableStatus'):
        $floor = $params['floor'];
        $ts = [];
        // $tables = $this->coreFunctions->opentable("select * from client where isconsignee=1 and flr='" . $floor . "'");
        $tables = $this->coreFunctions->opentable("select * from tblno where flr='".$floor."'");
        $nowaiter = $this->coreFunctions->datareader("select pvalue as value from profile where doc='POS' and psection='FINEDINENOWAITER'");
        if (!empty($tables)) {
          foreach ($tables as $t) {
            $orders = $this->coreFunctions->opentable("select * from salestran where client='" . $t->client . "'");
            if (!empty($orders)) {
              if ($orders[0]->iscollect == 1) {
                $t->tablestatus = 'billout';
              } else {
                $t->tablestatus = 'occupied';
              }
              array_push($ts, ['client' => $t->client, 'tablestatus' => $t->tablestatus]);
            }
          }
        }
        return json_encode(['ts'=>$ts, 'nowaiter'=>$nowaiter]);
        break;
      case md5('loadTables'):
        $floor = $params['floor'];
        // $tables = $this->coreFunctions->opentable("select * from client where isconsignee=1 and flr='" . $floor . "' order by clientname");
        $tables = $this->coreFunctions->opentable("select * from tblno where isinactive=0 and flr='".$floor."' order by clientname");
        if (!empty($tables)) {
          foreach ($tables as $t) {
            $orders = $this->coreFunctions->opentable("select * from salestran where client='" . $t->client . "'");
            if (!empty($orders)) {
              if ($orders[0]->iscollect == 1) {
                $t->tablestatus = 'billout';
              } else {
                $t->tablestatus = 'occupied';
              }
            } else {
              $t->tablestatus = 'vacant';
            }
          }
        }
        return $tables;
        break;
      case md5('loadOrders'):
        $tbl = $params['tableInfo'];
        $ordertotal = $scdisc = $grandtotal = 0;
        $orders = $this->coreFunctions->opentable("select st.line, st.client, st.clientname, st.barcode, st.itemname, st.isamt, st.isqty, st.uom,
              st.isprint,st.iscollect,st.voidby,st.remarks, st.`source`, st.destination, st.voidby,st.issenior,st.grp,st.billnumber,st.isdiplomat,st.`status`,st.start_time,st.end_time,
              st.waiter,st.isvoidprint,st.disc,st.htable,st.screg,st.scsenior,st.pwddisc,st.pwd,st.isemployee,st.empcode,st.dateid,st.iscomp,st.ccode,st.osno,st.issenior2,st.trno,st.setmenuref,
              st.ext,st.stdisc,st.gcount,st.`agent`,st.terminal,st.disctype,st.itemcount,st.isforextact,st.isor,st.isnosc,st.acddisc,st.acdisc,item.taxable
              from salestran as st left join item on item.barcode=st.barcode where st.client='" . $tbl['client'] . "' order by st.line");

        $GrossAmt = $dcDiscAmt = $dcSRDisc = $dcPWDisc = $dcLessVAT = $dcLess = $dcACDisc = $dcVATAmt = $dcServC = 0;

        if (!empty($orders)) {

          $dcNetVAT = 1.12;
          $RGuest = 1;
          $SCGuest = 0;
          $guest = $this->coreFunctions->datareader("select pvalue as value from profile where doc='FD' and psection='" . $tbl['client'] . "'");

          $SCDef = $this->coreFunctions->datareader("select pvalue as value from profile where doc='POS' and psection='SENIORPWDDISC'", [], '', true);
          if ($SCDef != 0) {
            $SCDef = $SCDef / 100;
          }

          $ACDiscDef = $this->coreFunctions->datareader("select pvalue as value from profile where doc='POS' and psection='ATHLETEDISC'", [], '', true);
          if ($ACDiscDef != 0) {
            $ACDiscDef = $ACDiscDef / 100;
          }

          if ($guest != '') {
            $arrguest =  explode("~",  $guest);
            $RGuest = $arrguest[0];
            $SCGuest = $arrguest[1];
          }

          $a = 0;
          foreach ($orders as $key => $order) {

            $dcVATLine = 0;
            $dcLess = 0;

            $discount = $total = $amtdisplay = $totaldisplay = 0;
            $bgcolor = '';
            if ($order->disc != '' && $order->disc != 0) {
              $discount = $this->othersClass->Discount($order->isamt, $order->disc);
              $total = number_format(($discount * $order->isqty), 2, '.', ',');
            } else {
              $total = number_format(($order->isamt * $order->isqty), 2, '.', ',');
            }
            $amtdisplay = number_format($order->isamt, 2, '.', ',');
            if ($order->isamt < 0) $amtdisplay = '(' . number_format(abs($order->isamt), 2, '.', ',') . ')';
            $totaldisplay = $total;
            if ($total < 0) $totaldisplay = '(' . number_format(abs($total), 2, '.', ',') . ')';
            if ($a == 0) {
              $bgcolor = 'bg-accent';
              $a = 0;
            } else {
              $bgcolor = '';
              $a++;
            }
            $orders[$key]->qty = number_format($order->isqty, 2, '.', ',');
            $orders[$key]->amt = number_format($order->isamt, 2, '.', ',');
            $orders[$key]->amtdisplay = $amtdisplay;
            $orders[$key]->discdisplay = $order->disc;
            $orders[$key]->total = $total;
            $orders[$key]->totaldisplay = $totaldisplay;
            $orders[$key]->bgcolor = $bgcolor;
            $orders[$key]->vremarks = '';
            $ordertotal += $total;

            $GrossAmt = $GrossAmt + ($order->isamt * $order->isqty);
            $netdisc = ($this->othersClass->Discount($order->isamt, $order->disc) * $order->isqty);
            $dcDiscAmt = $dcDiscAmt + (($order->isamt - $this->othersClass->Discount($order->isamt, $order->disc)) * $order->isqty);

            if ($SCGuest > 0) {
            } else {
              if ($order->taxable == 0) { //non-taxable
                if ($order->issenior == 1) {
                  $dcSRDisc = $dcSRDisc + number_format(($netdisc * $SCDef), 4, '.', '');
                  $dcLess =  $dcLess + number_format(($netdisc * $SCDef), 4, '.', '');
                } else if ($order->pwd == 1) {
                  $dcPWDisc = $dcPWDisc + number_format(($netdisc * $SCDef), 4, '.', '');
                  $dcLess =  $dcLess + number_format(($netdisc * $SCDef), 4, '.', '');
                } else if ($order->acddisc == 1) {
                  $dcACDisc = $dcACDisc + number_format(($netdisc * $ACDiscDef), 4, '.', '');
                }
              } else { //taxable
                if ($order->issenior == 1) {
                  $dcSRDisc = $dcSRDisc + number_format((($netdisc / $dcNetVAT) * $SCDef), 4, '.', '');
                  $dcLessVAT = $dcLessVAT + number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                  $dcLess =  $dcLess + number_format((($netdisc / $dcNetVAT) * $SCDef), 4, '.', '') + number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                } else if ($order->pwd == 1) {
                  $dcPWDisc = $dcPWDisc + number_format((($netdisc / $dcNetVAT) * $SCDef), 4, '.', '');
                  $dcLessVAT = $dcLessVAT + number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                  $dcLess =  $dcLess + number_format((($netdisc / $dcNetVAT) * $SCDef), 4, '.', '') + number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                } else if ($order->isdiplomat == 1) {
                  $dcLessVAT = $dcLessVAT + number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                  $dcLess =  $dcLess +  number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                } else if ($order->acddisc == 1) {
                  $dcACDisc = $dcACDisc + number_format((($netdisc / $dcNetVAT) * $ACDiscDef), 4, '.', '');
                  $dcVATLine = number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                  $dcVATAmt = $dcVATAmt + $dcVATLine;
                  $dcLess =  $dcLess +  number_format((($netdisc / $dcNetVAT) * $ACDiscDef), 4, '.', '');
                } else {
                  $dcVATLine = number_format((($netdisc / $dcNetVAT) * ($dcNetVAT - 1)), 4, '.', '');
                  $dcVATAmt = $dcVATAmt + $dcVATLine;
                }
              }
            }

            $ext = $netdisc - $dcLess;

            if ($order->isnosc == 0) {
              if ($order->screg != 0) {
                $dcSC1 = $order->screg / 100;

                $this->coreFunctions->sbclogger('EXT: ' . ($order->isqty * $order->isamt) . ' --- LESS: ' . $dcLess);
                $this->coreFunctions->sbclogger('SC AMT: ' . number_format(($ext - $dcVATLine) *  $dcSC1, 4, '.', ''));
                $dcServC = $dcServC + number_format(($ext - $dcVATLine) *  $dcSC1, 4, '.', '');
              }
            }
          }
        }

        $scpwddisc = $dcSRDisc + $dcPWDisc;
        $otherdisc = $dcDiscAmt + $dcACDisc;

        $grandtotal = ($GrossAmt - $scpwddisc - $otherdisc - $dcLessVAT) + $dcServC;

        return json_encode([
          'orders' => $orders,
          'total' => number_format($GrossAmt, 2),
          'lessvat' => number_format($dcLessVAT, 2),
          'scpwddisc' => number_format($scpwddisc, 2),
          'otherdisc' => number_format($otherdisc, 2),
          'scdisctotal' => number_format($dcServC, 2), //service charge
          'scdisc' => number_format($scdisc, 2),
          'grandtotal' => number_format($grandtotal, 2)
        ]);
        break;
      case md5('saveOrder'):
        $tableInfo = $params['tableinfo'];
        $data = $params['data'];
        $guestCount = $params['guestCount'];
        $screg = $params['screg'];
        $osnumber = $this->getLastOS($tableInfo);
        $this->saveGuestcount($guestCount, $tableInfo);
        $busdate = $params['busdate'];
        $waiter = $params['user'];
        $itemids = [];
        foreach ($data as $d) {
          $result = $this->insertOrder($d, $tableInfo, $osnumber, $screg, $busdate, $waiter);
          if (!$result['status']) {
            $this->coreFunctions->execqry("delete from salestran where osno='" . $osnumber . "'", 'delete');
            $this->coreFunctions->execqry("delete from osnumber where tableno='" . $osnumber . "'", 'delete');
            $this->coreFunctions->execqry("delete from profile where doc='FD' and psection='" . $tableInfo['client'] . "'", 'delete');
            return json_encode(['status' => false, 'msg' => 'There was an error saving the order; please try again.']);
          }
          array_push($itemids, $d['itemid']);
        }
        $printMsg = $this->printReceipt($data, $itemids, $tableInfo, $params['user'], $osnumber);
        return json_encode(['msg' => 'Order saved.', 'msgs' => $printMsg, 'status' => true]);
        break;
      case md5('loadCustomers2'):
        $date = $this->othersClass->getCurrentTimeStamp();
        // $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive, flr from client where isconsignee=1");
        $customers = $this->coreFunctions->opentable("select clientid, client, clientname, '' as addr, '' as tel, isinactive, flr from tblno");
        return json_encode(['customers' => $customers, 'dateid' => $date]);
        break;
      case md5('loadOtherCustomers'):
        $agent = $params['agent'];
        $dateid = $params['dateid'];
        $customers = [];
        if ($dateid != '' && $dateid != 'null') {
          $customers = $this->coreFunctions->opentable("select clientid from client where isconsignee=1 and date(dlock) >= date('" . $dateid . "')");
        }
        return json_encode(['customers' => $customers]);
        break;
      case md5('loadUpdatedCustomers'):
        $date = $this->othersClass->getCurrentTimeStamp();
        $dateid = $params['dateid'];
        $ccount = $params['ccount'];
        $isequal = true;
        $counts = $this->coreFunctions->opentable("select count(*) as ccount from client where isinactive=0");
        if (!empty($counts)) {
          if ($counts[0]->ccount != $ccount) $isequal = false;
        }
        if ($dateid == '-' || $dateid == 'null') {
          $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive, flr from client where isconsignee=1");
        } else {
          $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive, flr from client where isconsignee=1 and date(dlock) >= date('" . $dateid . "')");
        }
        return json_encode(['customers' => $customers, 'dateid' => $date, 'isequal' => $isequal]);
        break;
      case md5('printsample'):
        return $this->moduleClass->escposprintsample($params);
        break;
      case md5('downloadItimages'):
        $dateid = $this->othersClass->getCurrentTimeStamp();
        $itimages = $this->coreFunctions->opentable("select codeid, strpic as pic from itimages order by codeid");
        if (!empty($itimages)) {
          return json_encode(['status' => true, 'msg' => '', 'itimages' => $itimages, 'date' => $dateid]);
        } else {
          return json_encode(['status' => false, 'msg' => 'No image found.', 'itimages' => [], 'date' => $dateid]);
        }
        break;
      case md5('downloadUpdatedItimages'):
        $dateid = $this->othersClass->getCurrentTimeStamp();
        $date = $params['dateid'];
        $itimages = $this->coreFunctions->opentable("select codeid, strpic as pic from itimages where date(dlock) > '".$date."'");
        if (!empty($itimages)) {
          return json_encode(['status' => true, 'msg' => '', 'itimages' => $itimages, 'date' => $dateid]);
        } else {
          return json_encode(['status' => false, 'msg' => 'No image found.', 'itimages' => [], 'date' => $dateid]);
        }
        break;
      case md5('loadItimages2'):
        $itemids = $params['itemids'];
        $itimages = $this->coreFunctions->opentable("select codeid, strpic as pic from itimages where codeid in (" . $itemids . ") order by codeid");
        return json_encode(['itimages' => $itimages]);
        break;
      case md5('loadItimages'):
        $category = $params['category'];
        $items = $this->coreFunctions->opentable("select itemid from item where class='" . $category . "' order by itemname");
        $itemarr = [];
        $itemlist = '';
        if (!empty($items)) {
          foreach ($items as $i) {
            array_push($itemarr, $i->itemid);
          }
          $itemlist = implode(',', $itemarr);
        }
        if ($itemlist != '') {
          $itimages = $this->coreFunctions->opentable("select codeid, strpic as pic from itimages where codeid in (" . $itemlist . ") order by codeid");
          return json_encode(['status' => true, 'msg' => '', 'itimages' => $itimages]);
        } else {
          return json_encode(['status' => false, 'msg' => 'No items found.', 'itimages' => []]);
        }
        break;
      case md5('loadUpdatedItems'):
        $date = $this->othersClass->getCurrentTimeStamp();
        $dateid = $params['dateid'];
        if ($dateid == '-' || $dateid == 'null') {
          $items = $this->coreFunctions->opentable("select item.*, pic.strpic as picture from item left join itimages as pic on pic.codeid=item.itemid where item.isinactive=0 order by item.itemid");
        } else {
          $items = $this->coreFunctions->opentable("select item.*, pic.strpic as picture from item left join itimages as pic on pic.codeid=item.itemid where item.isinactive=0 and date(item.dlock) >= date('" . $dateid . "') order by item.itemid");
        }
        if (!empty($items)) {
          foreach ($items as $i) {
            if ($i->groupid != '') {
              $i->hasItem = true;
            } else {
              $i->hasItem = false;
            }
          }
        }
        return json_encode(['items' => $items, 'date' => $date]);
        return json_encode(['items' => $items, 'date' => $date]);
        break;
      case md5('loadItems2'):
        $class = $params['class'];
        $grp = $params['grp'];
        return $this->coreFunctions->opentable("select item.*, pic.strpic as picture from item left join itimages as pic on pic.codeid=item.itemid where item.class='" . $class . "' and item.groupid='" . $grp . "' order by item.itemname");
        break;
      case md5('loadItems'):
        $date = $this->othersClass->getCurrentTimeStamp();
        $items = $this->coreFunctions->opentable("select item.*, pic.strpic as picture from item left join itimages as pic on pic.codeid=item.itemid order by item.itemname");
        if (!empty($items)) {
          foreach ($items as $i) {
            if ($i->groupid != '') {
              $i->hasItem = true;
            } else {
              $i->hasItem = false;
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
          return json_encode(['msg' => 'Invalid Password.', 'status' => false]);
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
    }
  }

  public function saveGuestcount($gc, $tbl)
  {
    $p = $this->coreFunctions->opentable("select * from profile where doc='FD' and psection='" . $tbl['client'] . "'");
    $gcount = $gc['gcount'] . '~' . $gc['scount'];
    if (!empty($p)) {
      $this->coreFunctions->execqry("update profile set pvalue='" . $gcount . "' where doc='FD' and psection='" . $tbl['client'] . "'", 'update');
    } else {
      $this->coreFunctions->execqry("insert into profile(doc, psection, pvalue) values('FD', '" . $tbl['client'] . "', '" . $gcount . "')", 'insert');
    }
  }

  public function insertOrder($data, $tbl, $osnumber, $screg, $busdate, $waiter)
  {
    $msg = '';
    $status = false;
    $data['amt'] = str_replace(',', '', $data['amt']);
    $data['qty'] = str_replace(',', '', $data['qty']);
    if ($this->coreFunctions->execqry("insert into salestran(client, clientname, barcode, itemname, isamt, isqty, uom, isprint, source, grp, status, encoded, dateid, ccode, osno, screg, remarks, waiter) values('" . $tbl['client'] . "', '" . $tbl['clientname'] . "', '" . $data['barcode'] . "', '" . $data['itemname'] . "', '" . $data['amt'] . "', '" . $data['qty'] . "', '" . $data['uom'] . "', 1, '', 'A', 'P', '" . $data['dateid'] . "', '" . $busdate . "', 'WALK-IN', " . $osnumber . ", '" . $screg . "', '" . $data['rem'] . "', '" . $waiter . "')") > 0) {
      $msg = 'Item saved';
      $status = true;
    }
    return ['msg' => $msg, 'status' => $status];
  }

  public function getLastOS($tbl)
  {
    if ($this->coreFunctions->execqry("insert into osnumber(tableno) values('" . $tbl['client'] . "')", 'insert') > 0) {
      $os = $this->coreFunctions->opentable("select line from osnumber order by line desc limit 1");
      if (!empty($os)) return $os[0]->line;
    }
  }

  public function loaddocs($params)
  {
    if (isset($params['page'])) {
      $params['page'] = $this->othersClass->val($params['page']);
      if ($params['page'] == 0) {
        $params['page'] = 1;
      }
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
      // 'bcode' => $barcode,
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
      // 'bcode'=>$data['barcode'],
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

  public function Discount($Amt, $Discount)
  {
    if ($Discount != '') {
      $Disc = explode(',', $Discount);
    } else {
      $Disc = [];
    } //end if
    $DiscV = '';
    for ($a = 0; (count($Disc) - 1) >= $a; $a++) {
      $m = -1;
      $DiscV = $Disc[$a];
      if ($this->left($Disc[$a], 1) == '+') {
        $DiscV = substr($Disc[$a], 1);
        $m = 1;
      } //end if
      if ($this->right($DiscV, 1) == '%') {
        $AmountDisc = $Amt * floatval(($this->left($DiscV, strlen($DiscV) - 1)) / 100);
      } else {
        $AmountDisc = $DiscV;
      } //end if
      $Amt = $Amt + ($AmountDisc * $m);
    } //emd each
    return $Amt;
  }
  private function right($value, $count)
  {
    return substr($value, ($count * -1));
  } //end fn

  private function left($string, $count)
  {
    return substr($string, 0, $count);
  } //end fn

  private function printReceipt($items, $itemids, $tableInfo, $user, $osnumber)
  {
    // env printers list format
    // printername;pcname;type(network:ip)
    // PRINTERS="KITCHEN1;JADPC;NETWORK,KITCHEN;JADPC;NETWORK,SHAKER;JADPC;NETWORK,BAR1;127.0.0.1:80;IP,black;jad:jad@jad-pc;NETWORK,BAR BAR;JADPC;NETWORK"
    $printers = env('PRINTERS', '');
    $printerlist = [];
    $printersPrint = [];
    $printer = '';
    if ($printers != '') {
      $printers = explode(',', $printers);
      foreach ($printers as $p) {
        $printer = explode(';', $p);
        $printerlist[$printer[0]] = $printer[1];
        $printersPrint[$printer[0]] = ['printername'=>$printer[0], 'pcname'=>$printer[1], 'printertype'=>isset($printer[2]) ? $printer[2] : 'NETWORK', 'items'=>[]];
      }
      foreach ($items as $i) {
        $printers = $this->coreFunctions->opentable("select model, printer2, printer3, printer4, printer5 from item where itemid='".$i['itemid']."'");
        if (!empty($printers)) {
          if ($printers[0]->model != '') {
            if (isset($printersPrint[$printers[0]->model])) $printersPrint[$printers[0]->model]['items'][] = $i;
          }
          if ($printers[0]->printer2 != '') {
            if (isset($printersPrint[$printers[0]->printer2])) $printersPrint[$printers[0]->printer2]['items'][] = $i;
          }
          if ($printers[0]->printer3 != '') {
            if (isset($printersPrint[$printers[0]->printer3])) $printersPrint[$printers[0]->printer3]['items'][] = $i;
          }
          if ($printers[0]->printer4 != '') {
            if (isset($printersPrint[$printers[0]->printer4])) $printersPrint[$printers[0]->printer4]['items'][] = $i;
          }
          if ($printers[0]->printer5 != '') {
            if (isset($printersPrint[$printers[0]->printer5])) $printersPrint[$printers[0]->printer5]['items'][] = $i;
          }
        }
      }
      // $msg = "";
      $msg = [];
      foreach ($printersPrint as $ppkey => $pp) {
        if (!empty($pp['items'])) {
          if ($pp['printertype'] == 'IP') {
            array_push($msg, $this->printReceiptPerPrinter($pp['pcname'], $pp['items'], $tableInfo, $user, $osnumber, $pp['printertype']));
            // $msg .= $this->printReceiptPerPrinter($pp['pcname'], $pp['items'], $tableInfo, $user, $osnumber, $pp['printertype']);
          } else {
            array_push($msg, $this->printReceiptPerPrinter('//'.$pp['pcname'].'/'.$pp['printername'], $pp['items'], $tableInfo, $user, $osnumber, $pp['printertype']));
            // $msg .= $this->printReceiptPerPrinter('//'.$pp['pcname'].'/'.$pp['printername'], $pp['items'], $tableInfo, $user, $osnumber, $pp['printertype']);
          }
        }
      }
      return $msg;
    }
  }

  private function printReceiptPerPrinter($printername, $items, $tableInfo, $user, $osnumber, $printertype)
  {
    $company = $this->coreFunctions->getfieldvalue("profile", "pvalue", "psection=?", ["COMPANY"]);
    $datetime = $this->othersClass->getCurrentTimeStamp();
    $str = [];
    $str2 = '';
    array_push($str, [['OS #: ' . $osnumber, 'C', 2]]);
    array_push($str, [[$company, 'C', 2]]);
    array_push($str, [['?feed']]);
    array_push($str, [['ORDER SLIP', 'C', 2]]);
    array_push($str, [['?feed']]);
    array_push($str, [['Table ' . $tableInfo['clientname']], [$datetime, 'R']]);
    array_push($str, [["?="]]);
    array_push($str, [['DESC'], ['QTY', 'R']]);
    array_push($str, [["?="]]);
    foreach ($items as $item) {
      array_push($str, [[$item['itemname']], [$item['qty'], 'R']]);
    }
    array_push($str, [["?="]]);
    // array_push($str, [['Server: ' . $user], ['OS #: ' . $osnumber, 'R']]);
    array_push($str, [['Server: ' . $user]]);
    $str2 = $this->generateReceipt($str);

    $msg = '';
    $status = false;
    try {
      if ($printertype == 'IP') {
        $printername = explode(':', $printername);
        $connector = new NetworkPrintConnector($printername[0], $printername[1]);
      } else {
        $connector = new WindowsPrintConnector("smb:".$printername);
      }
      $printer = new Printer($connector);
      $printer->initialize();
      foreach ($str2 as $s) {
        if ($s['str'] == '?feed') {
          $printer->feed(1);
        } else {
          if ($s['size'] != '') {
            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
            switch (strtolower($s['align'])) {
              case 'r':
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                break;
              case 'c':
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                break;
            }
            $printer->text($s['str'] . "\n");
          } else {
            $printer->initialize();
            $printer->text($s['str'] . "\n");
          }
        }
      }
      $printer->feed(6);
      $printer->close();
      $msg = " Successfully printed to: ".$printername;
      $status = true;
    } catch (Exception $e) {
      $msg = "Couldn't print to this printer: " . $printername . '---' . $e->getMessage();
    }
    return ['status'=>$status, 'msg'=>$msg];
  }

  private function generateReceipt($str)
  {
    $printerLen = 40;
    $strs = [];
    $text1 = '';
    $text1align = '';
    $text1size = '';
    $text2 = '';
    $text2align = '';
    foreach ($str as $s) {
      $text1 = '';
      $text1align = '';
      $text1size = '';
      $text2 = '';
      $text2align = '';

      $text1 = $s[0][0];
      $text1align = isset($s[0][1]) ? $s[0][1] : '';
      $text1size = isset($s[0][2]) ? $s[0][2] : '';
      if (isset($s[1])) {
        $text2 = $s[1][0];
        $text2align = isset($s[1][1]) ? $s[1][1] : '';
      }
      if ($text1 == '?=') {
        $text1 = '';
        array_push($strs, ['str' => str_pad($text1, $printerLen, '='), 'size' => $text1size]);
      } else if ($text1 == '?feed') {
        array_push($strs, ['str' => '?feed', 'size' => '']);
      } else {
        if (strlen(($text1) + strlen($text2)) == $printerLen) {
          array_push($strs, ['str' => ($text1 . '' . $text2), 'size' => $text1size]);
        } else {
          if ($text2 == '') {
            switch (strtolower($text1align)) {
              case 'c':
              case 'r':
                if ($text1size == 2) {
                  array_push($strs, ['str' => $text1, 'size' => $text1size, 'align' => $text1align]);
                } else {
                  $len = strlen($printerLen - strlen($text1));
                  if (strtolower($text1align) == 'c') $len = floor(($printerLen - strlen($text1)) / 2);
                  array_push($strs, ['str' => str_pad($text1, ($len + strlen($text1)), ' ', STR_PAD_LEFT), 'size' => $text1size]);
                }
                break;
              default:
                array_push($strs, ['str' => $text1, 'size' => $text1size]);
                break;
            }
          } else {
            switch (strtolower($text2align)) {
              case 'c':
              case 'r':
                $len = floor($printerLen - (strlen($text1) + strlen($text2)));
                if (strtolower($text2align) == 'c') $len = floor(($printerLen - (strtolower($text1) + strtolower($text2))) / 2);
                array_push($strs, ['str' => str_pad($text1, ($len + strlen($text1)), ' ') . $text2, 'size' => $text1size]);
                break;
              default:
                array_push($strs, ['str' => $text1 . '' . $text2, 'size' => $text1size]);
                break;
            }
          }
        }
      }
    }
    return $strs;
  }
}
