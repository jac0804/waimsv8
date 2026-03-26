<?php

namespace App\Http\Classes\mobile;

use Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\Logger;
use App\Http\Classes\mobile\mobileCommonFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\moduleClass;
use Illuminate\Support\Facades\Storage;
use App\Http\Classes\modules\sales\sj; // remove this if not ordering app

use App\Http\Classes\mobile\ordering;
use App\Http\Classes\mobile\collection;
use App\Http\Classes\mobile\production;
use App\Http\Classes\mobile\itemscanner;
use App\Http\Classes\mobile\timeinapp;
use App\Http\Classes\mobile\sapint;
use App\Http\Classes\mobile\inventoryapp;
use App\Http\Classes\mobile\timeinadminapp;

use Exception;
use Throwable;

class mobileappv2Class
{
  protected $credentials;
  protected $projectId;
  private $timeinadminapp;
  private $inventoryapp;
  private $ordering;
  private $collection;
  private $production;
  private $itemscanner;
  private $timeinapp;
  private $sapint;
  private $othersClass;
  private $coreFunctions;
  private $logger;
  private $moduleClass;
  private $company;
  private $commonFunctions;
  private $companysetup;
  private $head;
  private $stock;
  private $config;
  private $acctg = [];
  private $sj;

  public function __construct()
  {
    $this->timeinadminapp = new timeinadminapp;
    $this->inventoryapp = new inventoryapp;
    $this->ordering = new ordering;
    $this->collection = new collection;
    $this->production = new production;
    $this->itemscanner = new itemscanner;
    $this->timeinapp = new timeinapp;
    $this->sapint = new sapint;
    $this->moduleClass = new moduleClass;
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->commonFunctions = new mobileCommonFunctions;
    $this->companysetup = new companysetup;
    $this->logger = new Logger;
    $this->sj = new sj; // remove this if not ordering app
    $this->company = env('appcompany', 'sbc');
    if ($this->company == 'sbc') {
      $this->head = 'glhead';
      $this->stock = 'glstock';
    } else {
      $this->head = 'lahead';
      $this->stock = 'lastock';
    }
  }

  public function getTemplate()
  {
    $appType = env('apptype', 'ordering');
    $settings = $this->$appType->getSettings();
    $downloads = $this->$appType->getDownloads();
    $menu = $this->$appType->getMainMenu();
    $layouts = $this->$appType->getLayouts($menu);
    $moduleTemplate = $this->commonFunctions->getModuleTemplate($layouts);
    $dbtables = $this->$appType->getDBTables();
    $commonfunc = $this->commonFunctions->getCommonFunc();
    $footerbuttons = $this->$appType->getFooterButtons();
    $addConfigCol = $this->$appType->getAddConfigCol();
    $loginTabs = [];
    if (method_exists($this->$appType, 'getLoginTabs')) $loginTabs = $this->$appType->getLoginTabs();
    $loginContent = ['fields' => [], 'buttons' => []];
    $loginFields = [];
    $loginFieldsPlot = [];
    $loginButtons = [];
    $manualLoginLayout = ['fields' => [], 'buttons' => []];
    $manualLoginFields = [];
    $manualLoginFieldsPlot = [];
    $manualLoginButtons = [];
    if (method_exists($this->$appType, 'manualLoginLayout')) {
      $manualLoginLayout = $this->$appType->manualLoginLayout();
      $plotfield = [];
      $plotfieldsmulti = "";
      if (isset($manualLoginLayout['fields'])) {
        foreach ($manualLoginLayout['fields'] as $mll) {
          foreach ($mll['fields'] as $mllkey => $mll2) {
            if ($mllkey == 'plot') {
              $plotfields = [];
              $plotfieldsmulti = "";
              foreach ($mll2 as $mlll2) {
                if (count($mlll2) > 1) {
                  foreach ($mlll2 as $mllll2) {
                    if ($plotfieldsmulti != "") {
                      $plotfieldsmulti .= "," . $mllll2;
                    } else {
                      $plotfieldsmulti = $mllll2;
                    }
                  }
                  array_push($plotfields, $plotfieldsmulti);
                  $plotfieldsmulti = "";
                } else {
                  array_push($plotfields, $mlll2);
                }
              }
              array_push($manualLoginFieldsPlot, ['fields' => $plotfields]);
              $plotfields = [];
            } else {
              array_push($manualLoginFields, $mll2);
            }
          }
        }
      }
      if (isset($manualLoginLayout['buttons'])) {
        foreach ($manualLoginLayout['buttons'] as $mllb) {
          foreach ($mllb['buttons'] as $mllbkey => $mllb2) {
            array_push($manualLoginButtons, $mllb2);
          }
        }
      }
    }
    if (method_exists($this->$appType, 'getLoginContent')) {
      $loginContent = $this->$appType->getLoginContent();
      $plotfields = [];
      $plotfieldsmulti = "";
      if (isset($loginContent['fields'])) {
        foreach ($loginContent['fields'] as $lc) {
          foreach ($lc['fields'] as $lckey => $lc2) {
            if ($lckey == 'plot') {
              $plotfields = [];
              $plotfieldsmulti = "";
              foreach ($lc2 as $lcc2) {
                if (count($lcc2) > 1) {
                  foreach ($lcc2 as $lccc2) {
                    if ($plotfieldsmulti != "") {
                      $plotfieldsmulti .= "," . $lccc2;
                    } else {
                      $plotfieldsmulti = $lccc2;
                    }
                  }
                  array_push($plotfields, $plotfieldsmulti);
                  $plotfieldsmulti = "";
                } else {
                  array_push($plotfields, $lcc2);
                }
              }
              array_push($loginFieldsPlot, ['doc' => '', 'form' => $lc['form'], 'fields' => $plotfields]);
              $plotfields = [];
            } else {
              $lc2['doc'] = '';
              $lc2['form'] = $lc['form'];
              array_push($loginFields, $lc2);
            }
          }
        }
      }
      if (isset($loginContent['buttons'])) {
        foreach ($loginContent['buttons'] as $lb) {
          foreach ($lb['buttons'] as $lbkey => $lb2) {
            $lb2['form'] = $lb['form'];
            array_push($loginButtons, $lb2);
          }
        }
      }
    }
    return ['settings' => $settings, 'downloads' => $downloads, 'menu' => $menu, 'moduletemplate' => $moduleTemplate, 'dbtables' => $dbtables, 'commonfunc' => $commonfunc, 'addconfigcol' => $addConfigCol, 'footerbuttons' => $footerbuttons, 'loginTabs' => $loginTabs, 'loginFields' => $loginFields, 'loginFieldsPlot' => $loginFieldsPlot, 'loginButtons' => $loginButtons, 'manualLoginFields' => $manualLoginFields, 'manualLoginFieldsPlot' => $manualLoginFieldsPlot, 'manualLoginButtons' => $manualLoginButtons];
  }

  public function download($params)
  {
    $type = '';
    $hasrecord = false;
    $date = '';
    if (isset($params['type'])) $type = $params['type'];
    if (isset($params['hasrecord'])) $hasrecord = $params['hasrecord'];
    if (isset($params['date'])) $date = $params['date'];
    $datenow = $this->othersClass->getCurrentTimeStamp();
    $filter = "";
    $users = [];
    $appType = env('apptype', 'ordering');
    switch ($type) {
      case 'items':
        $iend = $params['iend'];
        switch ($appType) {
          case 'production':
            if ($iend > 0) $filter .= " and itemid > " . $iend;
            $qry = "select itemid, barcode, itemname, groupid, class, `profile`, thickness, width, iscgl, isccl, iscba, isga, bmt from item where `category`<>'CRC'" . $filter . " order by itemid asc limit 5000";
            $items = $this->coreFunctions->opentable($qry);
            $icount = $this->coreFunctions->opentable("select count(*) as icount from item");
            if (count($items) > 0) {
              $iend = $items[count($items) - 1]->itemid;
            }
            break;
          default:
            if ($this->company == 'sbc') {
              if ($hasrecord) $filter .= " and date(dlock) >= date('" . $date . "') ";
            } else {
              if ($hasrecord) $filter .= " and date(uploaddate) >= date('" . $date . "') ";
            }
            if ($iend > 0) $filter .= " and itemid > " . $iend;
            $icount = $this->coreFunctions->opentable("select count(*) as icount from item");
            switch ($this->company) {
              case 'fastrax':
                $qry = "select itemid, barcode, itemname, amt, amt as newamt, uom, uom as newuom, 0 as qty, '' as rem, disc, isinactive from item " . $filter . " order by itemid asc limit 5000";
                $items = $this->coreFunctions->opentable($qry);
                if (count($items) > 0) {
                  foreach ($items as $i) {
                    $uom = $this->coreFunctions->opentable("select uom, amt, isdefault, factor from uom where itemid='" . $i->itemid . "' and isdefault=1");
                    if (!empty($uom)) {
                      $i->uom = $i->newuom = $uom[0]->uom;
                      $i->amt = $i->newamt = $uom[0]->amt;
                      $i->factor = $i->newfactor = $uom[0]->factor;
                    } else {
                      $uom = $this->coreFunctions->opentable("select uom, amt, factor from uom where itemid='" . $i->itemid . "' and factor=1");
                      if (!empty($uom)) {
                        $i->uom = $i->newuom = $uom[0]->uom;
                        $i->amt = $i->newamt = $uom[0]->amt;
                        $i->factor = $i->newfactor = $uom[0]->factor;
                      } else {
                        $i->factor = $i->newfactor = 1;
                      }
                    }
                  }
                  $iend = $items[count($items) - 1]->itemid;
                }
                break;
              case 'marswin':
                $qry = "select itemid, barcode, itemname, amt, amt as newamt, uom, uom as newuom, 0 as qty, '' as rem, disc, brand, part, plgrp, isinactive from item where 1=1 " . $filter . " order by itemid asc limit 5000";
                $items = $this->coreFunctions->opentable($qry);
                if (count($items) > 0) {
                  foreach ($items as $i) {
                    if ($i->uom = '') {
                      $i->factor = $i->newfactor = 1;
                    } else {
                      $uom = $this->coreFunctions->opentable("select factor from uom where itemid='" . $i->itemid . "' and uom='" . $i->uom . "'");
                      if (empty($uom)) {
                        $i->factor = $i->newfactor = 1;
                      } else {
                        $i->factor = $i->newfactor = $uom[0]->factor;
                      }
                    }
                  }
                  $iend = $items[count($items) - 1]->itemid;
                }
                break;
              case 'shinzen':
                $qry = "select itemid, barcode, itemname, amt, amt as newamt, uom, uom as newuom, 0 as qty, '' as rem, disc, isinactive, groupid, category, model, sizeid, country, brand, part from item " . $filter . " order by itemid asc limit 5000";
                $items = $this->coreFunctions->opentable($qry);
                if (count($items) > 0) {
                  foreach ($items as $i) {
                    if ($i->uom == '') {
                      $i->factor = $i->newfactor = 1;
                    } else {
                      $uom = $this->coreFunctions->opentable("select factor from uom where itemid='" . $i->itemid . "' and uom='" . $i->uom . "'");
                      if (empty($uom)) {
                        $i->factor = $i->newfactor = 1;
                      } else {
                        $i->factor = $i->newfactor = $uom[0]->factor;
                      }
                    }
                  }
                  $iend = $items[count($items) - 1]->itemid;
                }
                break;
              default:
                $qry = "select itemid, barcode, itemname, amt, amt as newamt, uom, uom as newuom, 0 as qty, '' as rem, disc, isinactive, groupid, category, model, sizeid, country, brand, part from item where 1=1 " . $filter . " order by itemid asc limit 5000";
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
                  $iend = $items[count($items) - 1]->itemid;
                }
                break;
            }
            break;
        }
        return json_encode(['items' => $items, 'date' => $datenow, 'iend' => $iend, 'icount' => $icount[0]->icount]);
        break;
      case "invItems":
        $iend = $params['iend'];
        $icount = 0;
        if ($iend == 0) {
          $icount = $this->coreFunctions->opentable("select count(*) as icount from item where isinactive=0");
          $icount = $icount[0]->icount;
        } else {
          $filter .= " and itemid > " . $iend;
        }
        $qry = "select itemid,barcode,partno,itemname,uom,brand,amt from item where isinactive=0 " . $filter . " order by itemid asc limit 5000";
        $items = $this->coreFunctions->opentable($qry);
        if (count($items) > 0) $iend = $items[count($items) - 1]->itemid;
        return json_encode(['items' => $items, 'iend' => $iend, 'icount' => $icount]);
        break;
      case "invItemBal":
        $whs = $params['whs'];
        // $ibend = $params['ibend'];
        $wh = "'" . implode("','", $whs) . "'";
        // $ibcount = 0;
        // if($ibend == 0) {
        //   $qry = $this->getItemBalQuery($wh, '', '', '', '');
        //   $data = $this->coreFunctions->opentable($qry);
        //   $ibcount = count($data);
        // } else {
        //   $filter = " where rownumber > ".$ibend;
        // }
        // $rownumberfilter = ", (select @rownumber := 0) as r";
        // $rownumberfield = " @rownumber := @rownumber + 1 as rownumber, ";
        // $rownumberorder = " order by rownumber";
        // $qry = $this->getItemBalQuery($wh, $rownumberfield, $rownumberfilter, $rownumberorder, $filter);
        // $itembal = $this->coreFunctions->opentable($qry);
        // $itembals = [];
        // if(count($itembal) > 0) {
        //   $ibend = $itembal[count($itembal) - 1]->rownumber;
        //   $itembal = json_decode(json_encode($itembal), true);
        //   foreach($itembal as $item) {
        //     unset($item['rownumber']);
        //     array_push($itembals, $item);
        //   }
        // }
        // return json_encode(['itembal'=>$itembals, 'ibend'=>$ibend, 'ibcount'=>$ibcount]);
        $qry = $this->getItemBalQuery($wh);
        $itembal = $this->coreFunctions->opentable($qry);
        return json_encode(['itembal' => $itembal]);
        break;
      case "invClientItem":
        $whs = $params['whs'];
        // $icend = $params['icend'];
        // $ibcount = 0;
        $clientitems = [];

        $wh = "'" . implode("','", $whs) . "'";
        $clientitem = $this->coreFunctions->opentable("select client.clientid, ci.barcode, ci.sku, client.client as wh from clientitem as ci left join client on client.clientid=ci.clientid where client.client in (" . $wh . ")");
        if (!empty($clientitem)) {
          $clientitems = $clientitem;
          // foreach($clientitem as $cli) {
          //   array_push($clientitems, $cli);
          // }
        } else {
          foreach ($whs as $w) {
            $parent = $this->coreFunctions->opentable("select parent from client where client='" . $w . "'");
            if (!empty($parent)) {
              $ci = $this->coreFunctions->opentable("select client.clientid, ci.barcode, ci.sku, '" . $w . "' as wh from clientitem as ci left join client on client.clientid=ci.clientid where client.client='" . $parent[0]->parent . "'");
              if (!empty($ci)) {
                foreach ($ci as $cci) {
                  array_push($clientitems, $ci);
                }
              }
            }
          }
          // $parent = $this->coreFunctions->opentable("select parent from client where client in (".$wh.")");
          // if (!empty($parent)) {
          //   foreach($parent as $p) {
          //     $ci = $this->coreFunctions->opentable("select client.clientid, ci.barcode, ci.sku, client.client as wh from clientitem as ci left join client on client.clientid=ci.clientid where client.client='".$p->parent."'");
          //     if(!empty($ci)) {
          //       array_push($clientitems, $ci);
          //     }
          //   }
          // }
        }
        return json_encode(['clientitem' => $clientitems]);


        // $wh = implode(', ', array_map(function($val){return sprintf("'%s'", $val);}, $whs));
        // $whid = array_map(function($val) {
        //   return $this->coreFunctions->datareader("select clientid as value from client where client='".$val."'");
        // }, $whs);
        // $whid = implode(', ', $whid);
        // $iccount = 0;
        // if($icend == 0) {
        //   $iccount = $this->coreFunctions->opentable("select count(*) as iccount from clientitem where clientid in (".$whid.")");
        //   $iccount = $iccount[0]->iccount;
        // } else {
        //   $filter .= " and clientitem.line > ".$icend;
        // }
        // $qry = "select line, client.client as wh, barcode, sku from clientitem left join client on client.clientid=clientitem.clientid where client.client in (".$wh.") ".$filter." order by line asc limit 5000";
        // $data = $this->coreFunctions->opentable($qry);
        // $clientitems = [];
        // if(count($data) > 0) {
        //   $icend = $data[count($data) - 1]->line;
        //   $data = json_decode(json_encode($data), true);
        //   foreach($data as $ic) {
        //     unset($ic['line']);
        //     array_push($clientitems, $ic);
        //   }
        // }
        // return json_encode(['clientitem'=>$clientitems, 'icend'=>$icend, 'iccount'=>$iccount]);

        return json_encode(['clientitem' => $clientitems]);
        break;
      case 'users':
        switch ($this->company) {
          case 'fastrax':
            $u = $this->coreFunctions->opentable("select clientid as userid, 0 as accessid, client as username, ppass as password, clientname as name from client where isagent=1 and isinactive2=0");
            $center = [];
            break;
          case 'marswin':
          case 'shinzen':
            $u = $this->coreFunctions->opentable("select clientid as userid, 0 as accessid, client as username, pword as password, clientname as name from client where isagent=1 and isinactive=0");
            $center = [];
            break;
          case 'sbc':
          case 'ulitc':
          case 'mbs':
            switch ($appType) {
              case 'production':
              case 'sapint':
              case 'inventoryapp':
                $u = $this->coreFunctions->opentable("select userid, accessid, username, password, name from useraccess");
                $center = [];
                $users = $this->coreFunctions->opentable("select idno, attributes from users");
                break;
              default:
                $u = $this->coreFunctions->opentable("select clientid as userid, 0 as accessid, client as username, pword as password, clientname as name, wh from client where isagent=1 and isinactive=0");
                $center = [];
                break;
            }
            break;
          case 'mcdeal':
            $u = $this->coreFunctions->opentable("select userid, accessid, username, password, name from useraccess");
            $center = [];
            $users = $this->coreFunctions->opentable("select idno, attributes from users");
            break;
          default:
            $u = $this->coreFunctions->opentable("select userid, accessid, username, password, name from useraccess where userid = '" . $user['userid'] . "'");
            $center = $this->coreFunctions->opentable("select ca.center as centercode, ca.userid, c.name as centername, c.warehouse, w.clientname as warehousename from centeraccess as ca left join center as c on c.code = ca.center left join client as w on w.client = c.warehouse where ca.userid = '" . $user['userid'] . "' group by ca.center, ca.userid, c.name, c.warehouse, w.clientname");
            break;
        }
        // $useraccess = $this->coreFunctions->opentable("select userid, accessid, username, password from useraccess");
        return json_encode(['users' => $users, 'useraccess' => $u, 'date' => $datenow]);
        break;
      case 'client':
        $user = $params['user'];
        $area = [];
        $areas = '';
        if (isset($params['area'])) {
          if (count($params['area']) > 0) {
            foreach ($params['area'] as $a) {
              // array_push($area, $a['area']);
              if ($areas != '') {
                $areas .= ",'" . $a['area'] . "'";
              } else {
                $areas = "'" . $a['area'] . "'";;
              }
            }
            // $areas = implode(',', $area);
          }
        }
        if ($this->company == 'sbc') {
          if (env('custDownloadType', 'area') == 'area') {
            $filter = "";
          } else {
            if ($hasrecord) $filter .= " and date(dlock) >= date('" . $date . "')";
          }
        } else {
          if ($hasrecord) $filter .= " and date(uploaddate) >= date('" . $date . "')";
        }
        switch ($this->company) {
          case 'fastrax':
            $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive2 as isinactive from client where iscustomer=1 and agent='" . $user['username'] . "'" . $filter);
            break;
          case 'sbc':
            if ($areas != '') {
              $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive, terms, brgy, area, province, region from client where iscustomer=1 and area in (" . $areas . ") " . $filter);
            } else {
              $customers = [];
            }
            break;
          default:
            $customers = $this->coreFunctions->opentable("select clientid, client, clientname, addr, tel, isinactive, terms from client where iscustomer=1 and agent='" . $user['username'] . "'" . $filter);
            break;
        }
        return json_encode(['client' => $customers, 'date' => $datenow]);
        break;
      case 'uom':
        $uend = $params['uend'];
        if ($hasrecord) $filter .= " and editdate >= '" . $date . "'";
        if ($uend > 0) $filter .= " and line > " . $uend;
        $ucount = $this->coreFunctions->opentable("select count(*) as ucount from uom");
        $qry = "select line, itemid, uom, factor, amt, isdefault2 as isdefault from uom where 1=1 " . $filter . " order by line asc limit 5000";
        $uom = $this->coreFunctions->opentable($qry);
        if (count($uom) > 0) $uend = $uom[count($uom) - 1]->line;
        return json_encode(['uom' => $uom, 'date' => $datenow, 'uend' => $uend, 'ucount' => $ucount[0]->ucount]);
        break;
      case 'terms':
        $tend = $params['tend'];
        if ($tend > 0) $filter .= " and line > " . $tend;
        $tcount = $this->coreFunctions->opentable("select count(*) as tcount from terms");
        $qry = "select line, terms, days from terms where 1=1 " . $filter . " order by line asc limit 5000";
        $terms = $this->coreFunctions->opentable($qry);
        if (count($terms) > 0) $tend = $terms[count($terms) - 1]->line;
        return json_encode(['terms' => $terms, 'tend' => $tend, 'tcount' => $tcount[0]->tcount]);
        break;
      case 'itembal':
        $ibend = $params['ibend'];
        $filter = '';
        switch ($this->company) {
          case 'sbc':
            $wh = $params['wh'];
            $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$wh]);
            if ($ibend > 0) $filter .= " and rrstatus.itemid > " . $ibend;
            $ibcount = $this->coreFunctions->opentable("select FORMAT(sum(rrstatus.bal),2) as bal, rrstatus.itemid from rrstatus where rrstatus.bal>0 and whid=" . $whid . " group by rrstatus.itemid");
            $ibcount = count($ibcount);
            $itembal = $this->coreFunctions->opentable("select FORMAT(sum(rrstatus.bal),2) as qty, rrstatus.itemid from rrstatus where rrstatus.bal>0 and whid=" . $whid . " " . $filter . " group by rrstatus.itemid");
            if (count($itembal) > 0) $ibend = $itembal[count($itembal) - 1]->itemid;
            return json_encode(['itembal' => $itembal, 'ibend' => $ibend, 'ibcount' => $ibcount]);
            break;
          default:
            if ($ibend > 0) $filter .= " and itemid > " . $ibend;
            $ibcount = $this->coreFunctions->opentable("select count(*) as ibcount from itemstat");
            $qry = "select itemid, qty from itemstat where 1=1 " . $filter . " order by itemid asc limit 5000";
            $itembal = $this->coreFunctions->opentable($qry);
            if (count($itembal) > 0) $ibend = $itembal[count($itembal) - 1]->itemid;
            return json_encode(['itembal' => $itembal, 'ibend' => $ibend, 'ibcount' => $ibcount[0]->ibcount]);
            break;
        }
        break;
      case 'area':
        $areas = $this->coreFunctions->opentable("select distinct area from client where area <> ''");
        return json_encode(['areas' => $areas]);
        break;
      case 'colors':
        $colors = $this->coreFunctions->opentable("select line, color from color");
        return json_encode(['colors' => $colors]);
        break;
      case 'designations':
        $designations = $this->coreFunctions->opentable("select line, designation from designation");
        return json_encode(['designations' => $designations]);
        break;
      case 'paintsuppliers':
        $paintsuppliers = $this->coreFunctions->opentable("select code, paintsupplier from paintsupplier");
        return json_encode(['paintsuppliers' => $paintsuppliers]);
        break;
      case 'downloadsapdoc':
        $doc = $params['doc'];
        $devid = $params['devid'];
        switch ($doc) {
          case 'tm':
            $head = $this->coreFunctions->opentable("select head.trno, head.docno, head.doc, head.dateid, head.client, head.clientname, head.yourref, head.ourref, head.wh from lahead as head left join ladetail as detail on detail.trno=head.trno left join cntnum as num on num.trno=head.trno where head.doc='TR' and num.uploaddate is not null and detail.downloaddate is null group by head.trno, head.docno, head.doc, head.dateid, head.client, head.clientname, head.yourref, head.ourref, head.wh");
            break;
          case 'rm':
            $head = $this->coreFunctions->opentable("select head.trno, head.docno, head.doc, head.dateid, head.client, head.clientname, head.yourref, head.ourref, head.wh from lahead as head left join ladetail as detail on detail.trno=head.trno left join cntnum as num on num.trno=head.trno where head.doc='RL' and num.uploaddate is not null and detail.downloaddate is null group by head.trno, head.docno, head.doc, head.dateid, head.client, head.clientname, head.yourref, head.ourref, head.wh");
            break;
          case 'rr':
          case 'fg':
            $head = $this->coreFunctions->opentable("select head.trno, head.docno, head.doc, date(head.dateid) as dateid, head.client, head.clientname, head.yourref, head.ourref, head.wh from lahead as head left join cntnum as num on num.trno=head.trno where head.doc='" . $doc . "' and num.uploaddate is null and num.devid='' and num.printdate is null");
            break;
          default:
            $head = $this->coreFunctions->opentable("select head.trno, head.docno, head.doc, date(head.dateid) as dateid, head.client, head.clientname, head.yourref, head.ourref, head.WH as wh from lahead as head left join cntnum as num on num.trno=head.trno where head.doc='" . $doc . "' and num.uploaddate is null and num.devid=''");
            break;
        }
        return json_encode(['head' => $head]);
        break;
      case 'downloaddoc':
        $doc = $params['doc'];
        $devid = $params['devid'];
        $access = $params['access'];
        $docbref = $this->getDocBref($access);
        $doc = $docbref['doc'];
        $bref = $docbref['bref'];
        $type = $docbref['type'];
        switch ($type) {
          case 'RR': // Receiving Report
            $head = $this->coreFunctions->opentable("select head.trno, head.doc, head.docno, head.client, head.clientname, head.wh, date(head.dateid) as dateid, head.lcno, head.ourref, cntnum.bref, '' as whname, head.yourref, '' as prdno, (select count(trno) from " . $doc . "stock where trno=head.trno and androidrem='' and isverified=0 and drno='') as ctr from " . $doc . "head as head left join cntnum on cntnum.trno=head.trno where cntnum.doc='" . $doc . "' and substring_index(cntnum.bref,'-',1)='" . $bref . "' and cntnum.lockdate is not null and cntnum.downloaddate is null and (cntnum.devid='' or cntnum.devid='" . $devid . "')");
            break;
          case 'Transfer': // Transfer CGL, CCL, GA, CBA
            $head = $this->coreFunctions->opentable("select head.trno, head.doc, head.docno, head.client, client.clientname, head.wh, wh.clientname as whname, date(head.dateid) as dateid, '' as lcno, head.ourref, cntnum.bref, head.yourref, head.prdno, (select count(trno) from tsstock where trno=head.trno and iss>0 and androidrem='' and isverified=0) as ctr from " . $doc . "head as head left join cntnum on cntnum.trno=head.trno left join client on client.client=head.client left join client as wh on wh.client=head.wh where cntnum.doc='" . $doc . "' and substring_index(cntnum.bref,'-',1)='" . $bref . "' and cntnum.lockdate is not null and cntnum.downloadby='' and (cntnum.devid='' or cntnum.devid='" . $devid . "')");
            break;
          case 'Entry': // CGL, CCL, GA, CBA Entry
            $head = $this->coreFunctions->opentable("select head.trno, head.doc, head.docno, client.client, client.clientname, wh.client as wh, wh.clientname as whname, date(head.dateid) as dateid, '' as lcno, head.ourref, cntnum.bref, head.prdno, count(stock.trno) as ctr from glhead as head left join glstock as stock on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno left join client on client.clientid=head.clientid left join client as wh on wh.clientid=head.whid where substring_index(cntnum.bref,'-',1)='" . $bref . "' and cntnum.postdate is not null and stock.linex=0 and stock.refx=0 and stock.isentry=0 and stock.isexit=0 and stock.ismanual=0 and entrydownloaddate is null and entrydownloadby='' and (stock.entrydevid='' or stock.entrydevid='" . $devid . "') group by head.doc, head.trno, head.docno, client.client, client.clientname, wh.clientname, wh.client, head.dateid, head.ourref, cntnum.bref, head.prdno");
            break;
          case 'Exit': // CGL Exit
            $head = $this->coreFunctions->opentable("select head.trno, head.doc, head.docno, client.client, client.clientname, wh.client as wh, wh.clientname as whname, date(head.dateid) as dateid, '' as lcno, head.ourref, cntnum.bref, count(stock.trno) as ctr from glhead as head left join glstock as stock on stock.trno=head.trno left join cntnum on cntnum.trno=head.trno left join client on client.clientid=head.clientid left join client as wh on wh.clientid=head.whid where substring_index(cntnum.bref,'-',1)='" . $bref . "' and cntnum.postdate is not null and stock.linex<>0 and stock.refx<>0 and stock.isentry=1 and stock.isexit=0 and exitdownloaddate is null and exitdownloadby='' and (stock.exitdevid='' or stock.exitdevid='" . $devid . "') group by head.doc, head.trno, head.docno, client.client, client.clientname, wh.clientname, wh.client, head.dateid, head.ourref, cntnum.bref");
            break;
        }
        return json_encode(['head' => $head]);
        break;
      case "timeinAccounts":
        switch ($this->company) {
          case 'sbc2':
            $users = $this->coreFunctions->opentable("select c.clientid as empcode, c.client, c.email, c.clientname as name, c.password, case c.isinactive when 1 then 0 else 1 end as isactive, emp.idbarcode from client as c left join employee as emp on emp.empid=c.clientid where c.isemployee=1 and emp.idbarcode<>0 order by c.clientid");
            break;
          default:
            $users = $this->coreFunctions->opentable("select c.clientid as empcode, c.client, c.email, c.clientname as name, c.password, case c.isinactive when 1 then 0 else 1 end as isactive, emp.idbarcode from client as c left join employee as emp on emp.empid=c.clientid where c.isemployee=1 order by c.clientid");
            break;
        }
        return json_encode(['users' => $users]);
        break;
      case "timeinUserImages":
        $qry = "select clientid as id, picture as img from client where isemployee=1 and picture<>'' order by clientid";
        if ($this->company == 'sbc2') $qry = "select client.clientid as id, client.picture as img from client left join employee as emp on emp.empid=client.clientid where client.isemployee=1 and emp.idbarcode<>0 and client.picture<>'' order by client.clientid";
        $pics = $this->coreFunctions->opentable($qry);
        if (!empty($pics)) {
          foreach ($pics as $p) {
            $filename = $p->img;
            $filename = str_replace('/images/', '', $filename);
            $ext = explode('.', $p->img);
            $ext = $ext[1];
            if (Storage::disk('public')->exists($filename)) {
              $data = Storage::disk('public')->get($filename);
              $p->img = 'data:image/' . $ext . ';base64,' . base64_encode($data);
            }
          }
        }
        return json_encode(['images' => $pics]);
        break;
      case 'wh':
        $qry = "select client,clientname from client where iswarehouse=1 order by clientname";
        $wh = $this->coreFunctions->opentable($qry);
        return json_encode(['wh' => $wh]);
        break;
      case 'mbsitems':
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 0);
        $iend = $params['iend'];
        $icount = 0;
        $filter = "";
        $wh = $this->coreFunctions->datareader("select wh as value from branchwh where isdefault=1");
        // $isallitems = $this->coreFunctions->datareader("select isallitem as value from client where client='".$wh."'");
        $branchstation = $this->coreFunctions->datareader("select wh as value from branchstation limit 1");
        $isallitems = 1;
        if ($branchstation != '') {
          $isallitems = $this->coreFunctions->datareader("select isallitem as value from client where client='".$branchstation."'");
        }
        if ($isallitems == 0) $filter = " and item.brand in (select brand from branchbrand) ";
        if ($iend == 0) {
          $icount = $this->coreFunctions->opentable("select count(*) as icount from item where isinactive=0 " . $filter);
          $icount = $icount[0]->icount;
        } else {
          $filter .= " and item.itemid > " . $iend;
        }
        // $items = $this->coreFunctions->opentable("select item.itemid, item.barcode, item.itemname, item.uom, ifnull(t.bal, 0) as bal,
        //   item.partno, item.brand, item.amt
        //   from item left join (
        //   select itemid, sum(bal) as bal from rrstatus left join client as wh on wh.clientid=rrstatus.whid where wh.client='".$wh."' group by itemid
        //   ) as t on t.itemid=item.itemid
        //   where item.barcode not in ('#', '$', '$$', '*', '**', '***') and item.isinactive=0 ".$filter." order by itemid asc limit 5000");
        $qry = "select item.itemid, item.barcode, item.itemname, item.uom, item.partno, item.brand, item.amt, sum(stock.qty-stock.iss) as bal
          from item
          left join glstock as stock on stock.itemid=item.itemid
          where item.barcode not in ('#', '$', '$$', '*', '**', '***') and item.isinactive=0 " . $filter . "
          group by item.itemid, item.barcode, item.itemname, item.uom, item.partno, item.brand, item.amt order by itemid asc limit 5000";
        $items = $this->coreFunctions->opentable($qry);
        if (!empty($items)) {
          foreach($items as $ikey => $i) {
            $bal = $this->coreFunctions->datareader("select sum(qty-iss) as value from glstock where itemid=".$i->itemid." and whid=14");
            $items[$ikey]->bal = $bal;
          }
        }
        if (count($items) > 0) $iend = $items[count($items) - 1]->itemid;
        $whs = $this->coreFunctions->opentable("select client.client as branch, br.wh, br.isdefault, brc.clientname as whname from branchwh as br left join client as brc on brc.client=br.wh left join client on client.clientid=br.clientid where client.clientid=br.clientid");
        $this->coreFunctions->execqry("delete from androidhead where dateid<date(now())");
        $this->coreFunctions->execqry("delete from androidstock where trno not in (select trno from androidhead)");
        return json_encode(['whs' => $whs, 'items' => $items, 'icount' => $icount, 'iend' => $iend, 'msg' => '']);
        break;
      case 'consolidatedItems':
        $dateid = $params['dateid'];
        $qry = "select stock.line, item.barcode, item.itemid, sum(stock.qty) as qty from androidhead as head
          left join androidstock as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid
          where date(head.dateid)='" . $dateid . "' and item.itemid is not null
          group by item.itemid, item.barcode, stock.line order by itemid";
        $data = $this->coreFunctions->opentable($qry);
        $items = [];
        if (!empty($data)) {
          foreach ($data as $key => $value) {
            array_push($items, ['line' => $key + 1, 'barcode' => $value->barcode, 'itemid' => $value->itemid, 'qty' => $value->qty]);
          }
        }
        return json_encode(['items' => $items]);
        break;
    }
  }

  public function download2()
  {
    $iend = 0;
    $icount = 0;
    $filter = "";
    $wh = $this->coreFunctions->datareader("select wh as value from branchwh where isdefault=1");
    // $isallitems = $this->coreFunctions->datareader("select isallitem as value from client where client='".$wh."'");
    $isallitems = $this->coreFunctions->datareader("select isallitem as value from client where client=(select wh from branchstation limit 1)");
    if ($isallitems == 0) $filter = " and item.brand in (select brand from branchbrand) ";
    if ($iend == 0) {
      $icount = $this->coreFunctions->opentable("select count(*) as icount from item where isinactive=0 " . $filter);
      $icount = $icount[0]->icount;
    } else {
      $filter .= " and item.itemid > " . $iend;
    }
    $items = $this->coreFunctions->opentable("select item.itemid, item.barcode, item.itemname, item.uom, ifnull(t.bal, 0) as bal,
      item.partno, item.brand, item.amt
      from item left join (
      select itemid, sum(bal) as bal from rrstatus left join client as wh on wh.clientid=rrstatus.whid where wh.client='" . $wh . "' group by itemid
      ) as t on t.itemid=item.itemid
      where item.barcode not in ('#', '$', '$$', '*', '**', '***') and item.isinactive=0 " . $filter . " order by itemid asc limit 5000");
    if (count($items) > 0) $iend = $items[count($items) - 1]->itemid;
    $whs = $this->coreFunctions->opentable("select client.client as branch, br.wh, br.isdefault, brc.clientname as whname from branchwh as br left join client as brc on brc.client=br.wh left join client on client.clientid=br.clientid");
    $this->coreFunctions->execqry("delete from androidhead where dateid<date(now())");
    $this->coreFunctions->execqry("delete from androidstock where trno not in (select trno from androidhead)");
    return json_encode(['whs' => $whs, 'items' => $items, 'icount' => $icount, 'iend' => $iend, 'msg' => '']);
  }

  public function getItemBalQuery($wh, $field = '', $filter = '', $order = '', $ifilter = '')
  {
    $qry = "select " . $field . " t.itemid, (sum(t.qty) - sum(t.iss)) as bal, t.wh from (
      select item.itemid, 0 AS qty, stock.iss, stock.wh, item.barcode, wh.clientid from dmhead as head
        left join dmstock as stock on stock.trno=head.trno
        left join item on item.barcode=stock.barcode
        left join client as wh on wh.client=head.wh
        LEFT JOIN cntnum ON cntnum.trno=stock.trno
      WHERE cntnum.voiddate IS NULL AND stock.void=0 AND stock.wh in ($wh)
      union all
      select item.itemid,0 AS qty,stock.iss,stock.wh,item.barcode,wh.clientid  from sjhead as head
        left join sjstock as stock on stock.trno=head.trno
        LEFT JOIN item ON item.barcode=stock.barcode
        left join client as wh on wh.client=head.wh
        LEFT JOIN cntnum ON cntnum.trno=stock.trno
      WHERE cntnum.voiddate IS NULL AND stock.void=0 AND stock.wh in ($wh)
      union all
      select item.itemid,0 AS qty,stock.iss,stock.wh,item.barcode,wh.clientid  from cmhead as head
        left join cmstock as stock on stock.trno=head.trno
        LEFT JOIN item ON item.barcode=stock.barcode
        left join client as wh on wh.client=head.wh
        LEFT JOIN cntnum ON cntnum.trno=stock.trno
      WHERE cntnum.voiddate IS NULL AND stock.void=0 AND stock.wh in ($wh)
      union all
      select item.itemid,stock.qty AS qty,stock.iss,stock.wh,item.barcode,wh.clientid  from ishead as head
        left join isstock as stock on stock.trno=head.trno
        LEFT JOIN item ON item.barcode=stock.barcode
        left join client as wh on wh.client=head.wh
        LEFT JOIN cntnum ON cntnum.trno=stock.trno
      WHERE cntnum.voiddate IS NULL AND stock.void=0 AND stock.wh in ($wh)
      union all
      select item.itemid,stock.qty AS qty,stock.iss,stock.wh,item.barcode,wh.clientid  from ajhead as head
        left join ajstock as stock on stock.trno=head.trno
        LEFT JOIN item ON item.barcode=stock.barcode
        left join client as wh on wh.client=head.wh
        LEFT JOIN cntnum ON cntnum.trno=stock.trno
      WHERE cntnum.voiddate IS NULL AND stock.void=0 AND stock.wh in ($wh)
      union all
      select item.itemid,stock.qty AS qty,stock.iss,stock.wh,item.barcode,wh.clientid  from tshead as head
        left join tsstock as stock on stock.trno=head.trno
        LEFT JOIN item ON item.barcode=stock.barcode
        left join client as wh on wh.client=head.wh
        LEFT JOIN cntnum ON cntnum.trno=stock.trno
      WHERE cntnum.voiddate IS NULL AND stock.void=0 AND stock.wh in ($wh)
      union all
      select item.itemid,stock.qty AS qty,stock.iss,wh.client,item.barcode,wh.clientid  from glhead as head
        left join glstock as stock on stock.trno=head.trno
        LEFT JOIN item ON item.itemid=stock.itemid
        LEFT JOIN cntnum ON cntnum.trno=stock.trno
        left join client as wh on wh.clientid=stock.whid
      WHERE cntnum.voiddate IS NULL AND stock.void=0 AND wh.client in ($wh)
      ) as t " . $filter . " " . $ifilter . "
      group by t.wh, t.itemid having (sum(t.qty) - sum(t.iss)) <> 0 " . $order;
    return $qry;
  }

  public function upload($params)
  {
    switch ($params['id']) {
      case md5('uploadTimeinoutLog'):
        $data = $params['data'];
        $msg = '';
        $status = false;
        $datenow = $this->othersClass->getCurrentTimeStamp();
        switch ($data['type']) {
          case 'timein':
            $data['mode'] = 'IN';
            break;
          case 'timeout':
            $data['mode'] = 'OUT';
            break;
        }
        if ($data['dateid'] != '') $data['dateid'] = date('Y-m-d', strtotime($data['dateid']));
        if ($data['time'] != '') $data['time'] = date('H:i:s', strtotime($data['time']));
        $data['idbarcode'] = $this->coreFunctions->getfieldvalue('employee', 'idbarcode', 'empid=?', [$data['id']]);
        $tr = $this->coreFunctions->opentable("select userid from timerec where userid='" . $data['idbarcode'] . "' and date(curdate)='" . $data['dateid'] . "' and timeinout='" . $data['dateid'] . " " . $data['time'] . "' and `mode`='" . $data['mode'] . "'");
        if (empty($tr)) {
          $tqry = "insert into timerec(userid, timeinout, `mode`, curdate) values(?, ?, ?, ?)";
          $tdata = [$data['idbarcode'], $data['dateid'] . ' ' . $data['time'], $data['mode'], $data['dateid']];
          if ($this->company == 'sbc2') {
            $tqry = "insert into timerec(userid, timeinout, `mode`, curdate, location) values(?, ?, ?, ?, ?)";
            $tdata = [$data['idbarcode'], $data['dateid'] . ' ' . $data['time'], $data['mode'], $data['dateid'], $data['siteLocation']];
          }
          if ($this->coreFunctions->execqry($tqry, 'insert', $tdata) > 0) {
            if ($data['pic'] != '') $this->saveTimeinoutImage($data);
            $msg = 'Record saved.';
            $status = true;
          } else {
            $msg = 'Error saving record.';
          }
        } else {
          $msg = 'Duplicate entry, Record not saved.';
        }
        return json_encode(['msg' => $msg, 'status' => $status, 'date' => $datenow]);
        break;
      case md5('uploadTimeinoutLogs'):
        $data = $params['data'];
        $res = [];
        $mode = '';
        $time = '';
        $pic = '';
        $datenow = $this->othersClass->getCurrentTimeStamp();
        foreach ($data as $key => $d) {
          $mode = '';
          $a = $this->othersClass->sanitize($d, 'ARRAY');
          $a['siteLocation'] = isset($data['siteLocation']) ? $data['siteLocation'] : '';
          if ($a['timein'] != '') {
            $return = $this->saveTimeinoutLog($a, 'IN');
            $res[$key]['success'] = $return['success'];
            $res[$key]['line'] = $return['line'];
            $res[$key]['msg'] = $return['msg'];
          }
          if ($a['timeout'] != '') {
            $return = $this->saveTimeinoutLog($a, 'OUT');
            $res[$key]['success'] = $return['success'];
            $res[$key]['line'] = $return['line'];
            $res[$key]['msg'] = $return['msg'];
          }
        }
        if ($this->company == 'sbc2') {
          $guardlogs = $params['guardlogs'];
          if (!empty($guardlogs)) {
            foreach ($guardlogs as $gkey => $glogs) {
              $timein = date('Y-m-d H:i:s', strtotime($glogs['timein']));
              $timeout = date('Y-m-d H:i:s', strtotime($glogs['timeout']));
              $filename = '';
              if ($glogs['loginPic'] !== '' && $glogs['loginPic'] !== null) {
                $gimage_64 = 'data:image/jpeg;base64,' . $glogs['loginPic'];
                $timestamp = strtotime($glogs['timein']);
                $filename = '/guardloginpics/' . $glogs['name'] . '-' . $timestamp . '.jpg';
                $img = substr($gimage_64, strpos($gimage_64, ',') + 1);
                $img = base64_decode($img);
                if (Storage::disk('public')->exists($filename)) {
                  Storage::disk('public')->delete($filename);
                }
                Storage::disk('public')->put($filename, $img);
              }
              $this->coreFunctions->execqry("insert into guardtimerec(name, timein, timeout, loginpic) values(?, ?, ?, ?)", 'insert', [$glogs['name'], $timein, $timeout, $filename]);
            }
          }
        }
        return json_encode(['data' => $res, 'date' => $datenow]);
        break;
    }
  }

  public function saveTimeinoutLog($data, $mode)
  {
    $time = '';
    switch ($mode) {
      case 'IN':
        $time = $data['timein'];
        $pic = $data['inPic'];
        break;
      case 'OUT':
        $time = $data['timeout'];
        $pic = $data['outPic'];
        break;
    }
    if ($data['dateid'] != '') $data['dateid'] = date('Y-m-d', strtotime($data['dateid']));
    if ($time != '') $time = date('H:i:s', strtotime($time));
    $data['idbarcode'] = $this->coreFunctions->getfieldvalue('employee', 'idbarcode', 'empid=?', [$data['id']]);
    $tr = $this->coreFunctions->opentable("select userid from timerec where userid='" . $data['idbarcode'] . "' and date(curdate)='" . $data['dateid'] . "' and timeinout='" . $data['dateid'] . ' ' . $time . "' and `mode`='" . $mode . "'");
    if (empty($tr)) {
      if ($this->coreFunctions->execqry("insert into timerec(userid, timeinout, `mode`, curdate) values(?, ?, ?, ?)", 'insert', [$data['idbarcode'], $data['dateid'] . ' ' . $time, $mode, $data['dateid']]) > 0) {
        if ($pic != '') $this->saveTimeinoutImage(['id' => $data['id'], 'mode' => $mode, 'dateid' => $data['dateid'], 'time' => $time, 'pic' => $pic]);
        return ['success' => true, 'line' => $data['line'], 'msg' => ''];
      } else {
        return ['success' => false, 'line' => $data['line'], 'msg' => 'Error saving record, try to reupload.'];
      }
    } else {
      return ['success' => false, 'msg' => 'Upload error: Duplicate record for ' . $data['dateid'], 'line' => ''];
    }
  }

  public function saveTimeinoutImage($data)
  {
    $image_64 = 'data:image/jpeg;base64,' . $data['pic'];
    $timestamp = strtotime($data['dateid'] . ' ' . $data['time']);
    $filename = '/loginpics/' . $data['id'] . '-' . $data['mode'] . '-' . $timestamp . '.jpg';
    $img = substr($image_64, strpos($image_64, ',') + 1);
    $img = base64_decode($img);
    if (Storage::disk('public')->exists($filename)) {
      Storage::disk('public')->delete($filename);
    }
    Storage::disk('public')->put($filename, $img);
    $this->coreFunctions->execqry("insert into loginpic(dateid, mode, idbarcode, picture) values(?, ?, ?, ?)", 'insert', [$data['dateid'] . ' ' . $data['time'], $data['mode'], $data['id'], $filename]);
  }

  public function getDocBref($access)
  {
    $doc = $bref = $type = '';
    switch (intval($access)) {
      case 305:
        $doc = 'RR';
        $bref = 'RRDOC';
        $type = 'RR';
        break; // Receiving Report
      case 557:
        $doc = 'TS';
        $bref = 'TSCGL';
        $type = 'Transfer';
        break; // Transfer to CGL
      case 568:
        $doc = 'TS';
        $bref = 'TSCCL';
        $type = 'Transfer';
        break; // Transfer to CCL
      case 597:
        $doc = 'TS';
        $bref = 'TSGA';
        $type = 'Transfer';
        break; // Transfer to GA
      case 579:
        $doc = 'TS';
        $bref = 'TSCBA';
        $type = 'Transfer';
        break; // Transfer to C&BA
      case 680:
        $doc = 'TS';
        $bref = 'TSCGL';
        $type = 'Entry';
        break; // CGL Entry
      case 682:
        $doc = 'TS';
        $bref = 'TSCCL';
        $type = 'Entry';
        break; // CCL Entry
      case 684:
        $doc = 'TS';
        $bref = 'TSGA';
        $type = 'Entry';
        break; // GA Entry
      case 686:
        $doc = 'TS';
        $bref = 'TSCBA';
        $type = 'Entry';
        break; // CBA Entry
      case 681:
        $doc = 'TS';
        $bref = 'TSCGL';
        $type = 'Exit';
        break; // CGL Exit
      case 683:
        $doc = 'TS';
        $bref = 'TSCCL';
        $type = 'Exit';
        break; // CCL Exit
      case 685:
        $doc = 'TS';
        $bref = 'TSGA';
        $type = 'Exit';
        break; // GA Exit
      case 687:
        $doc = 'TS';
        $bref = 'TSCBA';
        $type = 'Exit';
        break; // CBA Exit
      case 667:
        $doc = 'TS';
        $bref = 'TSDP';
        $type = 'Transfer';
        break; // Dispatch
      case 677:
        $doc = 'TS';
        $bref = 'TSDP';
        $type = 'Entry';
        break; // Dispatch Exit
    }
    return ['doc' => $doc, 'bref' => $bref, 'type' => $type];
  }

  public function loadcenters()
  {
    $centers = $this->coreFunctions->opentable("select * from center");
    return json_encode(['centers' => $centers]);
  }

  public function userLogin($params)
  {
    $msg = "";
    $status = false;
    $data = $log = [];
    $checkuser = $this->coreFunctions->opentable("select password from useraccess where md5(username) = '" . $params['params']['username'] . "'");
    if (count($checkuser) > 0) {
      if ($params['params']['pwd'] == $checkuser[0]->password) {
        $log = $this->coreFunctions->opentable("select userid, md5(username) as username, username as username2, password, name from useraccess where md5(username) = '" . $params['params']['username'] . "' and password = '" . $params['params']['pwd'] . "'");
        if (count($log) > 0) {
          $status = true;
          $msg = "Login Success";
        } else {
          $msg = "Login error";
        }
      } else {
        $msg = "Invalid Password";
      }
    } else {
      $msg = "Invalid Username";
    }
    $data['msg'] = $msg;
    $data['status'] = $status;
    $data['user'] = $log;
    return $data;
  }

  public function admin($params)
  {
    $id = $params['id'];
    switch ($id) {
      case md5('uploadSAPDoc'):
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $status = true;
        $msg = "";
        switch ($params['doc']) {
          case 'rm':
          case 'tm':
            if ($this->coreFunctions->execqry("update cntnum set ruploaddate='" . $datenow . "', ruploadby='" . $params['user'] . "' where trno=" . $params['stocks'][0]['trno'], 'update') > 0) {
              $msg = "Transaction saved";
              if (!empty($params['details'])) {
                foreach ($params['details'] as $detail) {
                  if ($detail['printdate'] == '') {
                    $detail['printdate'] = 'null';
                  } else {
                    $detail['printdate'] = "'" . $detail['printdate'] . "'";
                  }
                  $this->coreFunctions->execqry("update ladetail set isverified=1, printdate=" . $detail['printdate'] . ", printby='" . $detail['printby'] . "' where trno='" . $detail['trno'] . "' and line='" . $detail['dline'] . "' and sline='" . $detail['sline'] . "' and rtrno='" . $detail['rtrno'] . "' and rline='" . $detail['rline'] . "' and barcode='" . $detail['barcode'] . "' and batchcode='" . $detail['batchcode'] . "'", 'update');
                  // $this->coreFunctions->execqry("update ladetail set isverified=1 where trno='".$detail['trno']."' and line='".$detail['dline']."' and sline='".$detail['sline']."' and rtrno='".$detail['rtrno']."' and rline='".$detail['rline']."' and barcode='".$detail['barcode']."' and batchcode='".$detail['batchcode']."'", 'update');
                }
                $msg .= "Details updated";
              }
            } else {
              $status = false;
              $msg = "Error updating transaction, Please try agan";
            }
            break;
          case "rl":
          case "tr":
          case "dr":
            if ($this->coreFunctions->execqry("update cntnum set uploaddate='" . $datenow . "', uploadby='" . $params['user'] . "' where trno=" . $params['trno'], 'update') > 0) {
              $msg = "Transaction saved";
              if (!empty($params['stocks'])) {
                foreach ($params['stocks'] as $stock) {
                  if ($stock['printdate'] == '' || $stock['printdate'] == null) {
                    $stock['printdate'] = 'null';
                  } else {
                    $stock['printdate'] = "'" . $stock['printdate'] . "'";
                  }
                  if ($stock['printed'] == '' || $stock['printed'] == null) $stock['printed'] = 0;
                  $this->coreFunctions->execqry("update lastock set printdate=" . $stock['printdate'] . ", printby='" . $stock['printby'] . "', printed='" . $stock['printed'] . "' where trno=" . $params['trno'] . " and line=" . $stock['line'], 'update');
                }
                $pstock = $this->coreFunctions->opentable("select line from lastock where trno=" . $params['trno'] . " and printdate is null");
                if (empty($pstock)) {
                  $this->coreFunctions->execqry("update cntnum set printdate='" . $datenow . "', printby='" . $params['user'] . "' where trno=" . $params['trno'], 'update');
                }
                $msg .= ", Items uploaded";
                if (!empty($params['details'])) {
                  foreach ($params['details'] as $detail) {
                    if ($detail['isverified'] == '' || $detail['isverified'] == null) $detail['isverified'] = 0;
                    if ($detail['printed'] == '' || $detail['printed'] == null) $detail['printed'] = 0;
                    $this->coreFunctions->execqry("insert into ladetail(trno, sline, line, rtrno, rline, rrrefx, rrlinex, pickscanneddate, pickscannedby, qtyreleased, isverified, barcode, batchcode, printed) values('" . $detail['trno'] . "', '" . $detail['sline'] . "', '" . $detail['line'] . "', '" . $detail['rtrno'] . "', '" . $detail['rline'] . "', '" . $detail['rrrefx'] . "', '" . $detail['rrlinex'] . "', '" . $detail['pickscanneddate'] . "', '" . $detail['pickscannedby'] . "', '" . $detail['qtyreleased'] . "', '" . $detail['isverified'] . "', '" . $detail['barcode'] . "', '" . $detail['batchcode'] . "', '" . $detail['printed'] . "')", 'insert');
                  }
                  $msg .= ", Details uploaded";
                } else {
                  $msg .= ", No details to save";
                }
              }
            } else {
              $status = false;
              $msg = "Error updating transaction, Please try again.";
            }
            break;
          case "rr":
          case 'fg':
            if ($this->coreFunctions->execqry("update cntnum set uploaddate='" . $datenow . "', uploadby='" . $params['user'] . "' where trno=" . $params['stocks'][0]['trno'], 'update') > 0) {
              $msg = "Transaction saved";
              if (!empty($params['stocks'])) {
                foreach ($params['stocks'] as $stock) {
                  if ($stock['printdate'] == '' || $stock['printdate'] == null) {
                    $stock['printdate'] = 'null';
                  } else {
                    $stock['printdate'] = "'" . $stock['printdate'] . "'";
                  }
                  if ($stock['printed'] == '' || $stock['printed'] == null) $stock['printed'] = 0;
                  $this->coreFunctions->execqry("update lastock set printdate=" . $stock['printdate'] . ", printby='" . $stock['printby'] . "', printed='" . $stock['printed'] . "' where trno=" . $stock['trno'] . " and line=" . $stock['line'], 'update');
                }
                $msg .= ", Items uploaded";
                $pstock = $this->coreFunctions->opentable("select line from lastock where trno='" . $params['trno'] . "' and printdate is null");
                if (empty($pstock)) {
                  $this->coreFunctions->execqry("update cntnum set printdate='" . $datenow . "', printby='" . $params['user'] . "' where trno=" . $params['trno'], 'update');
                }
              } else {
                $msg .= ", No items to save";
              }
            } else {
              $status = false;
              $msg = "Error updating transaction, Please try again.";
            }
            break;
        }
        return json_encode(['status' => $status, 'msg' => $msg]);
        break;
      case md5('getLastOrderno'):
        $deviceid = $params['deviceid'];
        $data = $this->coreFunctions->opentable("select orderno from lahead where deviceid='" . $deviceid . "' union all select orderno from glhead where deviceid='" . $deviceid . "' order by orderno desc limit 1");
        if (!empty($data)) {
          return json_encode(['orderno' => $data[0]->orderno]);
        } else {
          return json_encode(['orderno' => '']);
        }
        break;
      case md5('uploadDocStocks'):
        $data = $params['doc'];
        $date = $this->othersClass->getCurrentTimeStamp();
        if ($data['clientid'] == '') $data['clientid'] = 0;
        if ($data['whid'] == '') $data['whid'] = 0;
        $insert = $this->coreFunctions->execqry("insert into androidhead(trno, clientid, whid, dateid, doc, devid, docno, station) values('" . $data['trno'] . "', '" . $data['clientid'] . "', '" . $data['whid'] . "', '" . $data['dateid'] . "', '" . $data['doc'] . "', '" . $data['devid'] . "', '" . $data['docno'] . "', '" . $data['station'] . "')", 'insert');
        if ($insert > 0) {

          if (count($data['stocks']) > 0) {
            foreach ($data['stocks'] as $key => $stock) {
              $this->coreFunctions->execqry("insert into androidstock(trno, line, itemid, qty, rrqty, uom, devid, station) values('" . $data['trno'] . "', '" . $stock['line'] . "', '" . $stock['itemid'] . "', '" . $stock['qty'] . "', '" . $stock['rrqty'] . "', '" . $stock['uom'] . "', '" . $stock['devid'] . "', '" . $stock['station'] . "')", 'insert');
              if ($key + 1 === count($data['stocks'])) {
                $this->coreFunctions->execqry("update androidhead set isok=1 where trno='" . $data['trno'] . "'", 'update');
                return json_encode(['status' => true, 'msg' => 'Document saved', 'date' => $date]);
              }
            }
          } else {
            $this->coreFunctions->execqry("update androidhead set isok=1 where trno='" . $data['trno'] . "'", 'update');
            return json_encode(['status' => true, 'msg' => 'Document saved', 'date' => $date]);
          }
        } else {
          return json_encode(['status' => false, 'msg' => 'Error saving Document', 'date' => '']);
        }
        break;
      case md5('searchItem'):
        $response["info"] = array();
        $barcode = $params['barcode'];
        $strSQL = "select itemid, barcode, itemname, bcode, barcode2, barcode3, barcode4, barcode5, barcode6, factor2, factor3, factor4, factor5, factor6, uom, uom2, uom3, uom4, uom5, uom6 from item where (barcode='" . $barcode . "' or bcode='" . $barcode . "' or barcode2='" . $barcode . "' or barcode3='" . $barcode . "' or barcode4='" . $barcode . "' or barcode5='" . $barcode . "' or barcode6='" . $barcode . "')";
        $data = $this->coreFunctions->opentable($strSQL);
        $divisor = 0;
        if (count($data) > 0) {
          if ($barcode == $data[0]->barcode or $barcode == $data[0]->bcode) {
            $divisor = 1;
            $response["uom"] = $data[0]->uom;
          } elseif ($barcode == $data[0]->barcode2) {
            $divisor = $data[0]->factor2;
            $response["uom"] = $data[0]->uom2;
          } elseif ($barcode == $data[0]->barcode3) {
            $divisor = $data[0]->factor3;
            $response['uom'] = $data[0]->uom3;
          } elseif ($barcode == $data[0]->barcode4) {
            $divisor = $data[0]->factor4;
            $response['uom'] = $data[0]->uom4;
          } elseif ($barcode == $data[0]->barcode5) {
            $divisor = $data[0]->factor5;
            $response['uom'] = $data[0]->uom5;
          } elseif ($barcode == $data[0]->barcode6) {
            $divisor = $data[0]->factor6;
            $response['uom'] = $data[0]->uom6;
          }
          $strSQL = "select client.client as wh, client.clientname as whname,ifnull(sum(qty-iss),0) bal from client left join (
            select head.wh, client.clientname as whname,stock.qty,0 as iss from rrhead as head left join rrstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.uom='" . $response['uom'] . "'
            union all
            select head.wh, client.clientname as whname,stock.qty,0 as iss from cmhead as head left join cmstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.uom='" . $response['uom'] . "'
            union all
            select head.wh, client.clientname as whname,stock.qty,stock.iss as iss from ishead as head left join isstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.uom='" . $response['uom'] . "'
            union all
            select head.wh, client.clientname as whname,stock.qty,stock.iss as iss from ajhead as head left join ajstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.uom='" . $response['uom'] . "'
            union all
            select head.wh, client.clientname as whname,stock.qty,0 as iss from tshead as head left join tsstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.refx<>0 and stock.uom='" . $response['uom'] . "'
            union all
            select head.wh, client.clientname as whname,0,stock.iss as iss from tshead as head left join tsstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.refx=0 and stock.uom='" . $response['uom'] . "'
            union all
            select head.wh, client.clientname as whname,0,stock.iss as iss from dmhead as head left join dmstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.uom='" . $response['uom'] . "'
            union all
            select head.wh, client.clientname as whname,0,stock.iss as iss from sjhead as head left join sjstock as stock on stock.trno=head.trno left join client on client.client=head.wh
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.uom='" . $response['uom'] . "'
            union all
            select wh.client as wh, wh.clientname as whname,stock.qty,stock.iss as iss from glhead as head left join glstock as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid
            left join client as wh on wh.clientid=head.whid
            where barcode='" . $data[0]->barcode . "' and stock.void=0 and stock.uom='" . $response['uom'] . "'
            ) as t on t.wh=client.client where client.iswarehouse=1 group by t.wh, client.client, t.whname, client.clientname";
          $data2 = $this->coreFunctions->opentable($strSQL);
          foreach ($data2 as $d) {
            if ($d->bal != 0) {
              $iitem = [];
              $iitem['wh'] = $d->wh;
              $iitem['whname'] = $d->whname;
              $iitem['bal'] = $d->bal / $divisor;
              array_push($response['info'], $iitem);
            }
          }
          $response['itemname'] = $data[0]->itemname;
          $response['barcode'] = $barcode;
          $response["factor"] = $divisor;
          if (count($response['info']) > 0) {
            $response['success'] = 1;
          } else {
            $response['success'] = 0;
            $response['msg'] = 'No record found for this item';
          }
        } else {
          $response['itemname'] = '';
          $response['barcode'] = '';
          $response["success"] = 0;
          $response['msg'] = 'Item not found.';
        }
        return json_encode($response);
        break;
      case md5('saveTransactions'):
        $d = $params['order'];
        $deviceid = $params['deviceid'];
        $center = '001';
        $warehousename = $address = '';
        $warehouse = env('DEFAULT_WAREHOUSE');
        if (isset($d['warehouse'])) $warehouse = $d['warehouse'];
        if (isset($d['warehousename'])) $warehousename = $d['warehousename'];
        if (isset($d['address'])) $address = $d['address'];
        $agent = $d['username'];
        $location = [];
        if (count($location) == 0) $location = ['longitude' => null, 'latitude' => null];
        switch ($this->company) {
          case 'marswin':
            if (!isset($d['rem'])) $d['rem'] = '';
            if (!isset($d['terms'])) $d['terms'] = '';
            if (!isset($d['shipto'])) $d['shipto'] = '';
            $params = ['userid' => $d['userid'], 'doc' => $d['doc'], 'dateid' => $d['dateid'], 'center' => $center, 'warehouse' => $warehouse, 'warehousename' => $warehousename, 'address' => $address, 'username' => $d['username'], 'client' => $d['client'], 'clientname' => $d['clientname'], 'addr' => $d['addr'], 'hasCustomer' => true, 'agent' => $agent, 'rem' => $d['rem'], 'terms' => $d['terms'], 'shipto' => $d['shipto'], 'deviceid' => $deviceid, 'orderno' => $d['orderno'], 'itemcount' => count($d['items'])];
            break;
          case 'sbc':
            $params = ['userid' => $d['userid'], 'doc' => $d['doc'], 'dateid' => $d['dateid'], 'center' => $center, 'warehouse' => $warehouse, 'warehousename' => $warehousename, 'address' => $address, 'username' => $d['username'], 'client' => $d['client'], 'clientname' => $d['clientname2'], 'addr' => $d['addr'], 'hasCustomer' => true, 'agent' => $agent, 'rem' => $d['rem'], 'terms' => '', 'shipto' => '', 'deviceid' => $deviceid, 'orderno' => $d['orderno'], 'itemcount' => count($d['items']), 'paymenttype' => $d['transtype'], 'payment' => $d['tendered'], 'change' => $d['change']];
            break;
          default:
            $params = ['userid' => $d['userid'], 'doc' => $d['doc'], 'dateid' => $d['dateid'], 'center' => $center, 'warehouse' => $warehouse, 'warehousename' => $warehousename, 'address' => $address, 'username' => $d['username'], 'client' => $d['client'], 'clientname' => $d['clientname'], 'addr' => $d['addr'], 'hasCustomer' => true, 'agent' => $agent, 'rem' => '', 'terms' => '', 'shipto' => '', 'deviceid' => $deviceid, 'orderno' => $d['orderno'], 'itemcount' => count($d['items'])];
            break;
        }
        $items = $d['items'];
        $this->config['params']['user'] = $agent;
        $checktrans = $this->checkTrans($params);
        if ($checktrans['status']) {
          $agentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$params['username']]);
          if ($this->company == 'sbc') {
            $head = $this->coreFunctions->opentable("select * from lahead where agent='" . $params['username'] . "' and dateid='" . $params['dateid'] . "' and deviceid='" . $params['deviceid'] . "' and orderno='" . $params['orderno'] . "' union all select * from glhead where agentid='" . $agentid . "' and dateid='" . $params['dateid'] . "' and deviceid='" . $params['deviceid'] . "' and orderno='" . $params['orderno'] . "'");
          } else {
            $head = $this->coreFunctions->opentable("select * from lahead where agent='" . $params['username'] . "' and dateid='" . $params['dateid'] . "' and deviceid='" . $params['deviceid'] . "' and orderno='" . $params['orderno'] . "' limit 1");
          }
          if ($checktrans['isposted']) {
            return json_encode(['status' => true, 'head' => $head, 'stocks' => $items, 'itemcount' => $itemcount, 'gtotal' => $gtotal]);
          } else {
            $checkitems = $this->checkItems($params, $items);
            if ($checkitems) {
              if ($this->updateItems($params, $items, $head)) {
                $grandtotal = $this->getgrandtotal($head[0]->trno, $d['doc']);
                if (!empty($grandtotal)) {
                  $itemcount = $grandtotal[0]->itemcount;
                  $gtotal = $grandtotal[0]->grandtotal;
                } else {
                  $itemcount = $gtotal = 0;
                }
                if ($this->company == 'sbc') {
                  $this->config['items'] = $items;
                  $this->config['params']['companyid'] = 32;
                  $this->config['params']['doc'] = 'SJ';
                  $this->config['params']['center'] = '001';
                  $classname = __NAMESPACE__ . '\\modules\\sales\\sj';
                  $this->config['docmodule'] = new $classname();
                  $this->config['params']['head'] = json_decode(json_encode($head), true);
                } else {
                  return json_encode(['status' => true, 'head' => $head, 'stocks' => $items, 'itemcount' => $itemcount, 'gtotal' => $gtotal]);
                }
              }
            } else {
              return json_encode(['status' => false, 'head' => [], 'stocks' => [], 'itemcount' => 0, 'gtotal' => 0]);
            }
          }
        } else {
          $s = $this->saveTransaction($params, $items);
          if ($s['status']) {
            $post = $this->postTrans();
            return json_encode(['status' => true, 'head' => $this->config['params']['head'], 'stocks' => $this->config['params']['items']]);
          } else {
            return json_encode(['status' => false, 'head' => $this->config['params']['head'], 'stocks' => $this->config['params']['items'], 'itemcount' => $d['itemcount'], 'gtotal' => 0]);
          }
        }
        break;
      case md5("loadOrders"):
        $data = ['doc' => $params['doc'], 'center' => '001', 'txt' => $params['str'], 'date1' => $params['datefrom'], 'date2' => $params['dateto'], 'agent' => $params['username'], 'filter' => '', 'ifilter' => ''];
        $docs = $this->loaddocs($data);
        return json_encode(['docs' => $docs['docs'], 'total' => $docs['total'], 'ordercount' => $docs['ordercount']]);
        break;
      case md5('loadOrderItems'):
        $doc = $params['doc'];
        $data = $params['data'];
        return $this->coreFunctions->opentable("select
          item.brand, stock.itemid, stock.trno, client.wh as whcode, stock.whid, client.clientname as wh, stock.line, item.barcode, item.itemname, stock.uom, stock.cost, stock.qty,
          round(stock.rrcost,2) as rrcost, round(stock.rrqty,2) as rrqty, round(stock.ext,2) as ext, left(stock.encodeddate,10) as encodeddate, round(stock.isqty,2) as isqty,
          stock.iss, stock.amt, round(stock.isamt,2) as isamt, stock.disc, stock.void, round((stock.iss-stock.qa) / case when ifnull(uom.factor,0) = 0 then 1 else uom.factor end, 2) as qa,
          stock.refx, stock.linex, stock.ref, stock.rem, stock.loc, stock.loc2, stock.expiry, ifnull(uom.factor,1) as uomfactor, stock.agent, stock.original_qty
        from lastock as stock
          left join client on client.clientid=stock.whid
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          where stock.trno=" . $data['trno'] . "
        union all
        select
          item.brand, stock.itemid, stock.trno, client.wh as whcode, stock.whid, client.clientname as wh, stock.line, item.barcode, item.itemname, stock.uom, stock.cost, stock.qty,
          round(stock.rrcost,2) as rrcost, round(stock.rrqty,2) as rrqty, round(stock.ext,2) as ext, left(stock.encodeddate,10) as encodeddate, round(stock.isqty,2) as isqty,
          stock.iss, stock.amt, round(stock.isamt,2) as isamt, stock.disc, stock.void, round((stock.iss-stock.qa) / case when ifnull(uom.factor,0) = 0 then 1 else uom.factor end, 2) as qa,
          stock.refx, stock.linex, stock.ref, stock.rem, stock.loc, stock.loc2, stock.expiry, ifnull(uom.factor,1) as uomfactor, ag.agent, stock.original_qty
        from glstock as stock
          left join client on client.clientid=stock.whid
          left join client as ag on ag.clientid=stock.agentid
          left join item on item.itemid=stock.itemid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          where stock.trno=" . $data['trno'] . " order by line");
        break;
      case md5('loadDailySales'):
        $month = $params['month'];
        $year = $params['year'];
        $agent = $params['username'];
        if ($month !== '') {
          switch ($month) {
            case 'January':
              $month = 1;
              break;
            case 'February':
              $month = 2;
              break;
            case 'March':
              $month = 3;
              break;
            case 'April':
              $month = 4;
              break;
            case 'May':
              $month = 5;
              break;
            case 'June':
              $month = 6;
              break;
            case 'July':
              $month = 7;
              break;
            case 'August':
              $month = 8;
              break;
            case 'September':
              $month = 9;
              break;
            case 'October':
              $month = 10;
              break;
            case 'November':
              $month = 11;
              break;
            case 'December':
              $month = 12;
              break;
            default:
              $month = 0;
              break;
          }
        }
        $qry = "select s.dateid, s.dayonly, sum(s.ext) as sales from (select date(head.dateid) as dateid, date_format(head.dateid,'%e') as dayonly, stock.ext from (lastock as stock left join lahead as head on head.trno=stock.trno) left join client as wh on wh.clientid=stock.whid where head.doc='SJ' and month(head.dateid)='" . $month . "' and year(head.dateid)='" . $year . "' and head.agent='" . $agent . "' union all select date(head.dateid) as dateid, date_format(head.dateid,'%e') as dayonly, stock.ext as sales from (glstock as stock left join glhead as head on head.trno=stock.trno) left join client as wh on wh.clientid=stock.whid left join client as c on c.clientid=head.agentid where head.doc='SJ' and month(head.dateid)='" . $month . "' and year(head.dateid)='" . $year . "' and c.client='" . $agent . "') as s group by s.dateid, s.dayonly";
        return $this->coreFunctions->opentable($qry);
        break;
      case md5('loadMonthlySales'):
        $year = $params['year'];
        $agent = $params['username'];
        $qry = "select mon, sum(ext) as sales from (select month(head.dateid) as mon, stock.ext from (lastock as stock left join lahead as head on head.trno=stock.trno) left join client as wh on wh.clientid=stock.whid where head.doc='SJ' and year(head.dateid)='" . $year . "' and head.agent='" . $agent . "' union all select month(head.dateid) as mon, stock.ext from (glstock as stock left join glhead as head on head.trno=stock.trno) left join client as wh on wh.clientid=stock.whid left join client as c on c.clientid=head.agentid where head.doc='SJ' and year(head.dateid)='" . $year . "' and c.client='" . $agent . "') as s group by s.mon";
        return $this->coreFunctions->opentable($qry);
        break;
      case md5('loadPrinters'):
        $printers = [
          ['name' => 'MPT-II', 'len' => 32],
          ['name' => 'Epson', 'len' => 42],
          ['name' => 'Bixolon', 'len' => 32],
          ['name' => 'DateCS', 'len' => 32],
          ['name' => 'XIN YE', 'len' => 32],
          ['name' => 'STAR', 'len' => 32]
        ];
        return json_encode($printers);
        break;
      case md5('getCollectorsList'):
        $collectors = $this->coreFunctions->opentable("select clientid, clientname, username, password from client where (iscollector=1 or isreader=1) and isinactive=0");
        return json_encode(['collectors' => $collectors]);
        break;
      case md5('getDailyCollectionCount'):
        $dc = $this->coreFunctions->opentable("select count(*) as dccount from dailycollection");
        if (!empty($dc)) {
          $dc = $dc[0]->dccount;
        } else {
          $dc = 0;
        }
        return json_encode(['dccount' => $dc]);
        break;
      case md5('getCollectionDate'):
        $center = $params['center'];
        $date = $this->coreFunctions->opentable("select pvalue from profile where doc='FY' and psection='COLLDATE' and pUser='" . $center . "'");
        if (!empty($date)) {
          return json_encode(['date' => $date[0]->pvalue]);
        } else {
          return json_encode(['date' => '']);
        }
        break;
      case md5('getCollectorsInfo'):
        $clientid = $params['clientid'];
        $coldate = $params['coldate'];
        $center = $params['center'];
        $colarea = $this->coreFunctions->opentable("select clientid, phase, sectionname, section, center from clientarea where center='" . $center . "' and clientid=" . $clientid);
        $tenants = $this->coreFunctions->opentable("
          select
            c.clientid, c.client, c.clientname, c.agent as loc, c.iscustomer, c.dblimit as dailyrent, c.dcusa as dailycusa, a.phase, a.section, c.center, 0 as outarrent, 0 as begbal, 0 as outelec, 0 as outwater, 0 as outcusa, c.category, ifnull((select e.amt from electricrate as e where e.center=c.center order by dateid desc limit 1), 0) as erate, ifnull((select w.amt from waterrate as w where w.center=c.center order by dateid desc limit 1), 0) as wrate, c.norent, c.nocusa
          from client as c
            left join client as a on a.client=c.agent
            left join clientarea as ca on ca.phase=a.phase and ca.section=a.section
          where
            c.isinactive=0 and
            c.iscustomer=1 and
            c.category='DAILY' and
            c.center='" . $center . "' and
            ca.clientid=" . $clientid . "
          group by c.client, c.clientid, c.clientname, c.agent, c.iscustomer, c.dblimit, c.dcusa, a.phase, a.section, c.center, c.category, c.norent, c.nocusa");
        if (!empty($tenants)) {
          foreach ($tenants as $t) {
            $outstandingAR = $this->coreFunctions->opentable("
              select
                (select ifnull(sum((case when ar.db<>0 then ar.bal else ar.bal * -1 end)), 0) as bal from arledger as ar left join coa on coa.acnoid=ar.acnoid where ar.clientid=" . $t->clientid . " and coa.alias in ('AR5','AR21') and ar.bal<>0 and ar.dateid<='" . $coldate . "') as outcusa,
                (select ifnull(sum((case when ar.db<>0 then ar.bal else ar.bal * -1 end)),0) as bal from arledger as ar left join coa on coa.acnoid=ar.acnoid where ar.clientid=" . $t->clientid . " and coa.alias='AR8' and ar.bal<>0 and ar.dateid<='" . $coldate . "') as outwater,
                (select ifnull(sum((case when ar.db<>0 then ar.bal else ar.bal * -1 end)),0) as bal from arledger as ar left join coa on coa.acnoid=ar.acnoid where ar.clientid=" . $t->clientid . " and coa.alias='AR7' and ar.bal<>0 and ar.dateid<='" . $coldate . "') as outelec,
                (select ifnull(sum((case when ar.bal<>0 then ar.bal else ar.bal * -1 end)), 0) as bal from arledger as ar left join coa on coa.acnoid=ar.acnoid where ar.clientid=" . $t->clientid . " and coa.alias in ('AR1','AR12') and ar.bal<>0 and ar.dateid='" . $coldate . "') as outarrent,
                ifnull((select ifnull(ending,0) from tablet_ereading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as ebeginning,
                ifnull((select ifnull(ending,0) from tablet_wreading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as wbeginning,
                ifnull((select ifnull(beginning,0) from tablet_ereading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as last_ebeginning,
                ifnull((select ifnull(ending,0) from tablet_ereading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as last_eending,
                ifnull((select ifnull(rate,0) from tablet_ereading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as last_erate,
                ifnull((select ifnull(beginning,0) from tablet_wreading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as last_wbeginning,
                ifnull((select ifnull(ending,0) from tablet_wreading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as last_wending,
                ifnull((select ifnull(rate,0) from tablet_wreading where clientid=" . $t->clientid . " and isok=1 order by dateid desc limit 1),0) as last_wrate");
            if (!empty($outstandingAR)) {
              $t->outcusa = $outstandingAR[0]->outcusa;
              $t->outwater = $outstandingAR[0]->outwater;
              $t->outelec = $outstandingAR[0]->outelec;
              $t->outarrent = $outstandingAR[0]->outarrent;
              $t->ebeginning = $outstandingAR[0]->ebeginning;
              $t->wbeginning = $outstandingAR[0]->wbeginning;
              $t->last_ebeginning = $outstandingAR[0]->last_ebeginning;
              $t->last_eending = $outstandingAR[0]->last_eending;
              $t->last_erate = $outstandingAR[0]->last_erate;
              $t->last_wbeginning = $outstandingAR[0]->last_wbeginning;
              $t->last_wending = $outstandingAR[0]->last_wending;
              $t->last_wrate = $outstandingAR[0]->last_wrate;
            } else {
              $t->outcusa = $t->outwater = $t->outelec = $t->outarrent = $t->ebeginning = $t->wbeginning = $t->last_ebeginning = $t->last_eending = $t->last_erate = $t->last_wbeginning = $t->last_wending = $t->last_wrate = 0;
            }
          }
        }
        return json_encode(['colarea' => $colarea, 'tenants' => $tenants]);
        break;
      case md5('uploadDailyReading'):
        $data = $params['data'];
        $collectorname = $params['collectorname'];
        $stationname = $params['stationname'];
        $datenow = $this->coreFunctions->getCurrentTimeStamp();
        if (!empty($data)) {
          $lines = [];
          foreach ($data as $d) {
            if ($d['beginning'] == '') $d['beginning'] = 0;
            if ($d['ending'] == '') $d['ending'] = 0;
            if ($d['consumption'] == '') $d['consumption'] = 0;
            if ($d['rate'] == '') $d['rate'] = 0;
            if ($d['type'] == 'E') {
              $tablename = 'tablet_ereading';
            } else {
              $tablename = 'tablet_wreading';
            }
            $check = $this->coreFunctions->opentable("select line from " . $tablename . " where clientid='" . $d['clientid'] . "' and date(dateid)=date('" . $d['dateid'] . "')");
            if (empty($check)) {
              $qry = "insert into " . $tablename . "(tb_line, beginning, ending, consumption, rate, clientid, phase, section, center, dateid, remarks, collectorid, uploadeddate, terminal, username, isok) values('" . $d['line'] . "', '" . $d['beginning'] . "', '" . $d['ending'] . "', '" . $d['consumption'] . "', '" . $d['rate'] . "', '" . $d['clientid'] . "', '" . $d['phase'] . "', '" . $d['section'] . "', '" . $d['center'] . "', '" . $d['dateid'] . "', '" . $d['remarks'] . "', '" . $d['collectorid'] . "', '" . $datenow . "', '" . $stationname . "', '" . $collectorname . "', 0)";
              if ($this->coreFunctions->execqry($qry, 'insert')) {
                array_push($lines, $this->coreFunctions->getLastInsertID());
              } else {
                $this->removeSavedReading($lines, $tablename);
                return json_encode(['msg' => 'Error saving reading', 'status' => false]);
              }
            }
          }
          return json_encode(['msg' => 'Reading saved', 'status' => true]);
        } else {
          return json_encode(['msg' => 'Nothing to save', 'status' => false]);
        }
        break;
      case md5('uploadDailyCollection'):
        $data = $params['data'];
        $collectorname = $params['collectorname'];
        $stationname = $params['stationname'];
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $datas = [];
        if (!empty($data)) {
          foreach ($data as $d) {
            if ($d['outar'] == '') $d['outar'] = 0;
            if ($d['outcusa'] == '') $d['outcusa'] = 0;
            if ($d['outelec'] == '') $d['outelec'] = 0;
            if ($d['outwater'] == '') $d['outwater'] = 0;
            if ($d['amount'] == '') {
              $d['amount'] = 0;
            } else {
              $d['amount'] = str_replace(',', '', $d['amount']);
            }
            $check = $this->coreFunctions->opentable("select line from dailycollection where clientid='" . $d['clientid'] . "' and type='" . $d['type'] . "' and date(dateid)=date('" . $d['dateid'] . "')");
            if (empty($check)) {
              $qry = "insert into dailycollection(clientid, amount, status, dateid, center, collectorid, terminal, line, username, uploadeddate, `phase`, section, outar, `type`, rem, outcusa, outelec, outwater, isnegative, transtime) values('" . $d['clientid'] . "', '" . $d['amount'] . "', '" . $d['status'] . "', '" . $d['dateid'] . "', '" . $d['center'] . "', '" . $d['collectorid'] . "', '" . $stationname . "', '" . $d['line'] . "', '" . $collectorname . "', '" . $datenow . "', '" . $d['phase'] . "', '" . $d['section'] . "', '" . $d['outar'] . "', '" . $d['type'] . "', '" . $d['remarks'] . "', '" . $d['outcusa'] . "', '" . $d['outelec'] . "', '" . $d['outwater'] . "', '" . $d['isNegative'] . "', '" . $d['transtime'] . "')";
              if ($this->coreFunctions->execqry($qry, 'insert') > 0) {
                array_push($datas, ['clientid' => $d['clientid'], 'type' => $d['type'], 'date' => $d['dateid']]);
              } else {
                $this->removeSavedDC($datas);
                return json_encode(['msg' => 'Error saving daily collections', 'status' => false]);
              }
            }
          }
          return json_encode(['msg' => 'Collection saved', 'status' => true]);
        } else {
          return json_encode(['msg' => 'Nothing to save', 'status' => false]);
        }
        break;
      case md5('loadSAPStocks'):
        $doc = $params['doc'];
        $trno = $params['trno'];
        $devid = $params['devid'];
        $user = $params['user'];
        $datenow = $this->othersClass->getCurrentTimeStamp();
        if ($doc == 'rr' || $doc == 'fg') {
          $stock = $this->coreFunctions->opentable("select stock.trno, stock.line, stock.rtrno, stock.rline, stock.rrrefx, stock.rrlinex, stock.barcode, stock.itemname,
            stock.batchcode, stock.qty, stock.uom, stock.printdate, stock.printby, stock.wht, '" . $doc . "' as doc from lastock as stock where stock.trno in (" . $trno . ") and printdate is null");
        } else {
          $stock = $this->coreFunctions->opentable("select stock.trno, stock.line, stock.rtrno, stock.rline, stock.rrrefx, stock.rrlinex, stock.barcode, stock.itemname,
            stock.batchcode, stock.qty, stock.uom, stock.printdate, stock.printby, stock.wht, '" . $doc . "' as doc from lastock as stock where stock.trno in (" . $trno . ")");
        }
        $detail = $this->coreFunctions->opentable("select trno, line, rtrno, rline, rrrefx, rrlinex, qtyreleased, isverified, pickscanneddate, pickscannedby, sline, barcode, batchcode, '" . $doc . "' as doc
          from ladetail where trno in (" . $trno . ") and downloaddate is null");
        if ($doc == 'rm' || $doc == 'tm') {
          $this->coreFunctions->execqry("update ladetail set downloaddate='" . $datenow . "', downloadby='" . $user . "' where trno in (" . $trno . ") and downloaddate is null", 'update');
          $this->coreFunctions->execqry("update cntnum set rdownloaddate='" . $datenow . "', rdownloadby='" . $user . "', rdevid='" . $devid . "' where trno in (" . $trno . ")", 'update');
        } else {
          $this->coreFunctions->execqry("update cntnum set downloaddate='" . $datenow . "', downloadby='" . $user . "', devid='" . $devid . "' where trno in (" . $trno . ")", 'update');
        }
        return json_encode(['stocks' => $stock, 'details' => $detail]);
        break;
      case md5('loadProdStocks'):
        $doc = $params['doc'];
        $trno = $params['trno'];
        $devid = $params['devid'];
        $access = $params['access'];
        $docbref = $this->getDocBref($access);
        $doc = $docbref['doc'];
        $bref = $docbref['bref'];
        $type = $docbref['type'];
        switch ($type) {
          case 'RR': // Receiving Report
            $stock = $this->coreFunctions->opentable("select trno, line, barcode, itemname, itemno, itemcoilcnt, bundleno, itemlen, itemnetweight, itemgrossweight, rrqty, 0 as frrefx, 0 as frlinex from rrstock as stock where trno in (" . $trno . ") and androidrem='' and isverified=0 and drno=''");
            break;
          case 'Transfer': // Transfer to CGL, CCL, GA, CBA
            $stock = $this->coreFunctions->opentable("select trno,line,barcode,itemname,childcode as bundleno,iss as rrqty,sorefx,solinex,ref,ifnull(stock.weight,0) as itemnetweight,frrefx,frlinex from tsstock as stock where trno in (" . $trno . ") and refx=0 and linex=0 and androidrem='' and isverified=0
            union all
            select stock.trno,stock.line,item.barcode,stock.itemname,stock.childcode as bundleno,stock.iss as rrqty,stock.sorefx,stock.solinex,stock.ref,ifnull(stock.weight,0) as itemnetweight,frrefx,frlinex from glstock as stock left join item on item.itemid=stock.itemid where stock.trno in (" . $trno . ") and refx=0 and linex=0 and androidrem='' and isverified=0");
            break;
          case 'Entry': // CGL, CCL, GA, CBA Entry
            $stock = $this->coreFunctions->opentable("select head.trno, stock.line, substring_index(cntnum.bref,'-',1) as bref, cntnum.docno, item.barcode, item.itemname, stock.childcode as bundleno, stock.frrefx, stock.frlinex from cntnum left join glhead as head on cntnum.trno=head.trno left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where cntnum.trno in (" . $trno . ") and substring_index(cntnum.bref,'-',1)='" . $bref . "' and cntnum.postdate is not null and stock.linex=0 and stock.refx=0 and stock.isentry=0 and stock.isexit=0 and stock.ismanual=0 and entrydownloaddate is null and entrydownloadby='' and (stock.entrydevid='' or stock.entrydevid='" . $devid . "')");
            break;
          case 'Exit': // CGL, CCL, GA, CBA Exit
            $stock = $this->coreFunctions->opentable("select head.trno, stock.line, substring_index(cntnum.bref,'-',1) as bref, cntnum.docno, item.barcode, item.itemname, stock.childcode as bundleno, stock.class, stock.thickness, stock.groupid, stock.coating, stock.iss as rrqty, stock.width, stock.designation, stock.color, stock.prd, stock.sc, stock.profile, stock.clientname, stock.gauge, stock.paintcode, stock.skbarcode from cntnum left join glhead as head on cntnum.trno=head.trno left join glstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid where cntnum.trno in (" . $trno . ") and substring_index(cntnum.bref,'-',1)='" . $bref . "' and cntnum.postdate is not null and stock.linex<>0 and stock.refx<>0 and stock.isentry=1 and stock.isexit=0 and exitdownloaddate is null and exitdownloadby='' and (stock.exitdevid='' or stock.exitdevid='" . $devid . "')");
            break;
        }
        return json_encode(['stocks' => $stock]);
        break;
      case md5('updateProdCntnum'):
        $doc = $params['doc'];
        $trno = $params['trno'];
        $date = $this->othersClass->getCurrentTimeStamp();
        $devid = $params['devid'];
        $user = $params['user'];
        $this->coreFunctions->execqry("update cntnum set downloadby='" . $user . "', downloaddate='" . $date . "', devid='" . $devid . "' where trno in (" . $trno . ")", 'update');
        break;
      case md5('updateProdGLStocks'):
        $trno = $params['trno'];
        $devid = $params['devid'];
        $user = $params['user'];
        $type = $params['type'];
        $dateid = $this->othersClass->getCurrentTimeStamp();
        if ($type == 'Entry') {
          $this->coreFunctions->execqry("update glstock set entrydevid='" . $devid . "', entrydownloaddate='" . $dateid . "', entrydownloadby='" . $user . "' where trno in (" . $trno . ")", 'update');
        } else {
          $this->coreFunctions->execqry("update glstock set exitdevid='" . $devid . "', exitdownloaddate='" . $dateid . "', exitdownloadby='" . $user . "' where trno in (" . $trno . ")", 'update');
        }
        break;
      case md5('uploadDocStocksProd'):
        $doc = $params['doc'];
        $stocks = $params['stocks'];
        $access = $params['access'];
        $docbref = $this->getDocBref($access);
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $user = $params['user'];
        $addeddata = $params['addeddata'];
        $selDoc = $params['selDoc'];
        $devid = $params['devid'];
        $addedfield = '';
        if (!empty($stocks)) {
          foreach ($stocks as $stock) {
            if ($docbref['type'] == 'RR') $addedfield = ", drno='" . $stock['dr'] . "'";
            switch ($docbref['type']) {
              case 'RR':
              case 'Transfer':
                if ($stock['isscanned'] == 0) {
                  $this->coreFunctions->execqry("update " . $doc . "stock set androidrem='" . $stock['rem'] . "' " . $addedfield . " where trno=" . $selDoc['trno'] . " and line='" . $stock['line'] . "'", 'update');
                  $this->coreFunctions->execqry("update cntnum set iswithissue=1, isforposting=1, scanneddate='" . $selDoc['scanneddate'] . "', scannedby='" . $selDoc['scannedby'] . "' where trno=" . $selDoc['trno'] . " and doc='" . $doc . "'", 'update');
                } else {
                  $this->coreFunctions->execqry("update " . $doc . "stock set isverified=1 " . $addedfield . " where trno=" . $selDoc['trno'] . " and line='" . $stock['line'] . "'", 'update');
                  $this->coreFunctions->execqry("update cntnum set isforposting=1, scanneddate='" . $selDoc['scanneddate'] . "', scannedby='" . $selDoc['scannedby'] . "' where trno=" . $selDoc['trno'] . " and doc='" . $doc . "'", 'update');
                }
                break;
              case 'Entry':
                $this->coreFunctions->execqry("update glstock set isentry=1, ismanual='" . $stock['ismanual'] . "', scandate='" . $stock['scandate'] . "', entrydate='" . $datenow . "', entryby='" . $user . "' where trno=" . $stock['trno'] . " and line='" . $stock['line'] . "'", 'update');
                $this->coreFunctions->execqry("update glstock set isentry=1, ismanual='" . $stock['ismanual'] . "', scandate='" . $stock['scandate'] . "', entrydate='" . $datenow . "', entryby='" . $user . "' where refx='" . $stock['trno'] . "' and linex='" . $stock['line'] . "'", 'update');
                break;
              case 'Exit':
                if (!empty($addeddata)) {
                  $thickness = '';
                  switch ($docbref['bref']) {
                    case 'TSCGL':
                      $thickness = $d['thickness'];
                      break;
                    default:
                      $thickness = $stock['thickness'];
                      break;
                  }
                  foreach ($addeddata as $d) {
                    $this->coreFunctions->execqry("insert into android(trno, line, docno, bref, barcode, designation, rrqty, childcode, groupid, thickness, coating, width, class, skbarcode, strtype, strshift, myline, devname, color, paintcode, length, uom, prd, qty, sc, clientname, fg, consumed, dpr, remarks) values('" . $stock['trno'] . "', '" . $stock['line'] . "', '" . $stock['docno'] . "', '" . $stock['bref'] . "', '" . $stock['barcode'] . "', '" . $stock['designation'] . "', '" . $d['weight'] . "', '" . $stock['bundleno'] . "', '" . $d['groupid'] . "', '" . $thickness . "', '" . $d['coating'] . "', '" . $d['width'] . "', '" . $d['class'] . "', '" . $d['barcode'] . "', '" . $d['strtype'] . "', '" . $d['strshift'] . "', '" . $d['myline'] . "', '" . $devid . "', '" . $d['color'] . "', '" . $d['paintcode'] . "', '" . $d['length'] . "', '" . $d['uom'] . "', '" . $d['prd'] . "', '" . $d['qty'] . "', '" . $d['sc'] . "', '" . $d['clientname'] . "', '" . $d['fg'] . "', '" . $d['consumed'] . "', '" . $d['dpr'] . "', '" . $d['remarks'] . "')", 'insert');
                  }
                  $this->coreFunctions->execqry("update glstock set exitdate='" . $stock['exitdate'] . "', exitby='" . $user . "' where trno=" . $stock['trno'] . " and line=" . $stock['line'], 'update');
                }
                break;
            }
          }
        } else {
          switch ($docbref['type']) {
            case 'RR':
            case 'Transfer':
              $this->coreFunctions->execqry("update cntnum set isforposting=1 where trno=" . $selDoc['trno'] . " and doc='" . $doc . "'", 'update');
              break;
          }
        }
        break;
      case md5('updateProdScanned'):
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $trno = $params['trno'];
        $line = $params['line'];
        $user = $params['user'];
        $scandate = $params['date'];
        $type = $params['stype'];
        $manual = '';
        if ($type == 'manual') $manual = ', ismanual=1';
        $this->coreFunctions->execqry("update glstock set isentry=1, scandate='" . $scandate . "', entrydate='" . $datenow . "', entryby='" . $user . "' " . $manual . " where trno=" . $trno . " and line=" . $line, 'update');
        $this->coreFunctions->execqry("update glstock set isentry=1, scandate='" . $scandate . "', entrydate='" . $datenow . "', entryby='" . $user . "' " . $manual . " where refx=" . $trno . " and linex=" . $line, 'update');
        break;
      case md5('uploadExitDataProd'):
        $access = $params['access'];
        $data = $params['data'];
        $selDoc = $params['selDoc'];
        $user = $params['user'];
        $devid = $params['devid'];
        $datenow = $this->othersClass->getCurrentTimeStamp();
        $docbref = $this->getDocBref($access);
        $thickness = '';
        if (!isset($selDoc['exitdate'])) $selDoc['exitdate'] = '';
        if (!empty($data)) {
          foreach ($data as $d) {
            switch ($docbref['bref']) {
              case 'TSCGL':
                $thickness = $d['thickness'];
                break;
              default:
                $thickness = $selDoc['thickness'];
                break;
            }
            if ($d['weight'] == '') $d['weight'] = 0;
            if (!isset($d['consumed'])) $d['consumed'] = '';
            if (!isset($d['dpr'])) $d['dpr'] = '';
            if (!isset($d['remarks'])) $d['remarks'] = '';
            $this->coreFunctions->execqry("insert into android(trno, line, docno, bref, barcode, designation, rrqty, childcode, groupid, thickness, coating, width, class, skbarcode, strtype, strshift, myline, devname, color, paintcode, length, uom, prd, qty, sc, clientname, fg, consumed, dpr, remarks) values('" . $selDoc['trno'] . "', '" . $selDoc['line'] . "', '" . $selDoc['docno'] . "', '" . $selDoc['bref'] . "', '" . $selDoc['barcode'] . "', '" . $d['designation'] . "', '" . $d['weight'] . "', '" . $selDoc['bundleno'] . "', '" . $d['groupid'] . "', '" . $thickness . "', '" . $d['coating'] . "', '" . $d['width'] . "', '" . $d['class'] . "', '" . $d['barcode'] . "', '" . $d['strtype'] . "', '" . $d['strshift'] . "', '" . $d['myline'] . "', '" . $devid . "', '" . $d['color'] . "', '" . $d['paintcode'] . "', '" . $d['length'] . "', '" . $d['uom'] . "', '" . $d['prd'] . "', '" . $d['qty'] . "', '" . $d['sc'] . "', '" . $d['clientname'] . "', '" . $d['fg'] . "', '" . $d['consumed'] . "', '" . $d['dpr'] . "', '" . $d['remarks'] . "')", 'insert');
          }
        }
        $this->coreFunctions->execqry("update glstock set isexit=1, exitdate='" . $selDoc['exitdate'] . "', exitby='" . $user . "' where trno=" . $selDoc['trno'] . " and line=" . $selDoc['line'], "update");
        break;
      case md5('uploadInventoryDoc'):
        $devid = $params['devid'];
        $reupload = isset($params['reupload']) ? $params['reupload'] : false;
        $whid = $this->coreFunctions->datareader("select clientid as value from client where client=?", [$params['wh']]);
        $branchid = 0;
        if (!empty($params['data'])) {
          if (isset($reupload)) {
            $this->coreFunctions->execqry("delete from android where whid=" . $whid . " and date(dateid)='" . $params['dateid'] . "' and devid='" . $devid . "'", 'delete');
          }
          foreach ($params['data'] as $item) {
            // $data = $this->coreFunctions->opentable("select whid from android where whid=".$whid." and date(dateid)='".$params['dateid']."' and devid='".$devid."' and itemid=".$item['itemid']);
            // if(!empty($data)) {
            //   $this->coreFunctions->execqry("delete from android where whid=".$whid." and date(dateid)='".$params['dateid']."' and devid='".$devid."' and itemid=".$item['itemid'], 'delete');
            // }
            $this->coreFunctions->execqry("insert into android(branchid, whid, dateid, line, itemid, qty, bal, sales, devid) values(?, ?, ?, ?, ?, ?, ?, ?, ?)", 'insert', [$branchid, $whid, $params['dateid'], $item['line'], $item['itemid'], $item['qty'], $item['syscount'], $item['sales'], $devid]);
          }
          $ctr = $this->coreFunctions->datareader("select count(line) as value from android where whid=? and date(dateid)=? and devid=?", [$whid, $params['dateid'], $devid]);
          if ($ctr === count($params['data'])) {
            return json_encode(['status' => true]);
          } else {
            $this->coreFunctions->execqry("delete from android where whid=" . $whid . " and date(dateid)='" . $params['dateid'] . "' and devid='" . $devid . "'", 'delete');
            return json_encode(['status' => false, 'msg' => 'Error1: stock count not match']);
          }
        } else {
          $this->coreFunctions->execqry("delete from android where whid=" . $whid . " and date(dateid)='" . $params['dateid'] . "' and devid='" . $devid . "'", 'delete');
          return json_encode(['status' => false, 'msg' => 'Error2: empty data']);
        }
        break;
      case md5('uploadInvMBSDoc'):
        $branch = $params['branch'];
        $dateid = $params['dateid'];
        $wh = $params['wh'];
        $items = $params['items'];
        $gtype = $params['gtype'];
        $devid = $params['devid'];

        $branchid = $this->coreFunctions->datareader("select clientid as value from client where client='" . $branch . "'");
        $whid = $this->coreFunctions->datareader("select clientid as value from client where client='" . $wh . "'");
        if ($gtype == "initial") {
          $trno = $this->coreFunctions->datareader("select trno as value from androidhead where branchid='" . $branchid . "' and whid='" . $whid . "' and dateid='" . $dateid . "'");
          if ($trno == "") {
            $data = ['branchid' => $branchid, 'whid' => $whid, 'dateid' => $dateid];
            $trno = $this->coreFunctions->insertGetId('androidhead', $data);
          }
          if ($trno != "" && $trno != 0) {
            if (!empty($items)) {
              foreach ($items as $key => $i) {
                // $this->coreFunctions->execqry("insert into androidstock(trno, line, itemid, qty) values('".$trno."', '".$key+1."', '".$i['itemid']."', '".$i['qty']."')", 'insert');
                if ($this->coreFunctions->execqry("insert into androidstock(trno, line, itemid, qty) values(?, ?, ?, ?)", 'insert', [$trno, $key + 1, $i['itemid'], $i['qty']]) === 0) {
                  $this->coreFunctions->execqry("delete from androidhead where trno=?", 'delete', [$trno]);
                  $this->coreFunctions->execqry("delete from androidstock where trno=?", 'delete', [$trno]);
                  return json_encode(['status' => true, 'msg' => 'err2: Error uploading items']);
                }
              }
            }
            return json_encode(['status' => true, 'msg' => 'Upload success']);
          } else {
            return json_encode(['status' => false, 'msg' => 'err1: Error uploading items']);
          }
        } else {
          if (!empty($items)) {
            foreach ($items as $key => $i) {
              $bal = $i['bal'];
              if ($bal == null || $bal == '') $bal = 0;
              if ($this->coreFunctions->execqry("insert into android(branchid, whid, dateid, line, itemid, qty, bal, devid) values(?, ?, ?, ?, ?, ?, ?, ?)", 'insert', [$branchid, $whid, $dateid, $i['line'], $i['itemid'], $i['qty'], $bal, $devid]) == 0) {
                foreach ($items as $key2 => $i2) {
                  $this->coreFunctions->execqry("delete from android where branchid=? and whid=? and dateid=? and line=? and itemid=? and qty=? and bal=?", 'delete', [$branchid, $whid, $dateid, $i2['line'], $i2['itemid'], $i2['qty'], $i2['bal']]);
                }
                return json_encode(['status' => false, 'msg' => 'err2: Error uploading items']);
              }
            }
            return json_encode(['status' => true, 'msg' => 'Upload success']);
          } else {
            return json_encode(['status' => false, 'msg' => 'err2: Error uploading final count']);
          }
        }
        break;
      case md5('getUserLogs'):
        $date = $params['date'];
        $date = str_replace('/', '-', $date);
        $logs = $this->coreFunctions->opentable("select c.clientid, c.email, c.clientname as name, time(t.timeinout) as timeinout, t.mode from timerec as t left join client as c on c.email=t.userid where date(t.timeinout)='".$date."'");
        return json_encode(['status'=>true, 'msg'=>'Logs loaded', 'logs'=>$logs]);
        break;
      case md5('getUserImageLog'):
        $user = $params['user'];
        $date = str_replace('/', '-', $params['date']);
        $msg = '';
        // $inpic = $this->coreFunctions->datareader("select picture as value from loginpic where date(dateid)='".$date."' and `mode`='IN' and idbarcode='".$user['clientid']."'");
        // $outpic = $this->coreFunctions->datareader("select picture as value from loginpic where date(dateid)='".$date."' and `mode`='OUT' and idbarcode='".$user['clientid']."'");
        $inpic = $this->coreFunctions->datareader("select picture as value from timerec where date(curdate)='".$date."' and `mode`='IN' and userid='".$user['id']."'");
        $outpic = $this->coreFunctions->datareader("select picture as value from timerec where date(curdate)='".$date."' and `mode`='OUT' and userid='".$user['id']."'");
        if ($inpic != '') {
          if (Storage::disk('public')->exists($inpic)) {
            $data = Storage::disk('public')->get($inpic);
            $inpic = 'data:image/jpg;base64,' . base64_encode($data);
          } else {
            $inpic = '';
            $msg = ' Time-in picture not found';
          }
        } else {
          $inpic = '';
          $msg .= ' Time-in picture not found';
        }
        if ($outpic != '') {
          if (Storage::disk('public')->exists($outpic)) {
            $data = Storage::disk('public')->get($outpic);
            $outpic = 'data:image/jpg;base64,' . base64_encode($data);
          } else {
            $outpic = '';
            $msg .= ' Time-out picture not found';
          }
        } else {
          $outpic = '';
          $msg .= ' Time-out picture not found';
        }
        return json_encode(['msg'=>$msg, 'inpic'=>$inpic, 'outpic'=>$outpic]);
        break;
    }
  }

  public function removeSavedDC($data)
  {
    if (!empty($data)) {
      foreach ($data as $d) {
        $this->coreFunctions->execqry("delete from dailycollection where clientid='" . $d['clientid'] . "' and type='" . $d['type'] . "' and dateid='" . $d['dateid'] . "'", 'delete');
      }
    }
  }

  public function removeSavedReading($lines, $tablename)
  {
    if (!empty($lines)) {
      foreach ($lines as $line) {
        $this->coreFunctions->execqry("delete from " . $tablename . " where line='" . $line . "'", 'delete');
      }
    }
  }

  public function loaddocs($params)
  {
    $filter1 = '';
    $agentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$params['agent']]);
    if ($params['date1'] != '' && $params['date2'] != '') {
      $params['date2'] = date('Y-m-d', strtotime($params['date2'] . '+1 days'));
      $params['date1'] = date('Y-m-d', strtotime($params['date1']));
      $filter1 = " and head.dateid between '" . $params['date1'] . "' and '" . $params['date2'] . "'";
    }
    if ($params['txt'] != '') {
      $params['filter'] = " where head.clientname like '%" . $params['txt'] . "%'" . $filter1;
      if ($this->company == 'sbc') $params['filter2'] = " where head.clientname like '%" . $params['txt'] . "%' " . $filter1;
    } else {
      if ($filter1 != '') {
        $params['filter'] = " where 1=1 " . $filter1;
      } else {
        $params['filter'] = " where 1=1 ";
      }
      if ($this->company == 'sbc') {
        if ($filter1 != '') {
          $params['filter2'] = " where 1=1 " . $filter1;
        } else {
          $params['filter2'] = " where 1=1 ";
        }
      }
    }
    if ($this->company == 'sbc') {
      if ($params['filter'] != '') {
        $params['filter'] .= " and head.agent='" . $params['agent'] . "'";
      } else {
        $params['filter'] = " where 1=1 and head.agent='" . $params['agent'] . "'";
      }
      if ($params['filter2'] != '') {
        $params['filter2'] .= " and head.agentid='" . $agentid . "'";
      } else {
        $params['filter2'] = " where 1=1 and head.agentid='" . $agentid . "'";
      }
      $alldocs = $this->coreFunctions->opentable("select head.trno from cntnum left join lahead as head on head.trno=cntnum.trno " . $params['filter'] . " and cntnum.doc='" . $params['doc'] . "' and cntnum.center='" . $params['center'] . "' and cntnum.postdate is null union all select head.trno from cntnum left join glhead as head on head.trno=cntnum.trno " . $params['filter2'] . " and cntnum.doc='" . $params['doc'] . "' and cntnum.center='" . $params['center'] . "' and cntnum.postdate is null");
      $doc = $this->coreFunctions->opentable("select 'Draft' as docstat, head.trno, head.docno, head.clientname, cl.client, head.address, cl.tel, head.dateid, cntnum.postedby, cntnum.postdate, head.yourref, head.ourref, head.rem, head.agent from cntnum left join lahead as head on head.trno=cntnum.trno left join client as cl on cl.client=head.client " . $params['filter'] . " and cntnum.doc='" . $params['doc'] . "' and cntnum.center='" . $params['center'] . "' and cntnum.postdate is null union all select 'Posted' as docstat, head.trno, head.docno, head.clientname, cl.client, head.address, cl.tel, head.dateid, cntnum.postedby, cntnum.postdate, head.yourref, head.ourref, head.rem, head.agentid from cntnum left join glhead as head on head.trno=cntnum.trno left join client as cl on cl.clientid=head.clientid " . $params['filter2'] . " and cntnum.doc='" . $params['doc'] . "' and cntnum.center='" . $params['center'] . "' and cntnum.postdate is not null order by docno desc");
    } else {
      if ($params['filter'] != '') {
        $params['filter'] .= " and head.agent='" . $params['agent'] . "'";
      } else {
        $params['filter'] = " where 1=1 and head.agent='" . $params['agent'] . "'";
      }
      $alldocs = $this->coreFunctions->opentable("select head.trno from cntnum left join lahead as head on head.trno=cntnum.trno " . $params['filter1'] . " and cntnum.doc='" . $params['doc'] . "' and cntnum.center='" . $params['center'] . "' and cntnum.postdate is null");
      $doc = $this->coreFunctions->opentable("select 'Draft' as docstat, head.trno, head.docno, head.clientname, cl.client, head.address, cl.tel, head.dateid, cntnum.postedby, cntnum.postdate, head.yourref, head.ourref, head.rem, head.agent from cntnum left join lahead as head on head.trno=cntnum.trno left join client as cl on cl.client=head.client " . $params['filter'] . " and cntnum.doc='" . $params['doc'] . "' and cntnum.center='" . $params['center'] . "' and cntnum.postdate is null order by head.docno desc");
    }
    $total = 0;
    $ordercount = 0;
    if (!empty($alldocs)) {
      $ordercount = count($alldocs);
      foreach ($alldocs as $d) {
        $grandtotal = $this->getgrandtotal($d->trno, $params['doc']);
        if (!empty($grandtotal)) $total += $grandtotal[0]->gTotal;
      }
    }
    if (!empty($doc)) {
      foreach ($doc as $dd) {
        $grandtotal = $this->getgrandtotal($dd->trno, $params['doc']);
        $dd->grandtotal = '0';
        $dd->itemcount = '0';
        if (!empty($grandtotal)) {
          $dd->itemcount = $grandtotal[0]->itemcount;
          $dd->grandtotal = $grandtotal[0]->grandtotal;
        }
      }
    }
    return ['docs' => $doc, 'total' => $total, 'ordercount' => $ordercount];
  }

  public function checkTrans($data)
  {
    if ($this->company == 'sbc') {
      $agentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['username']]);
      $c = $this->coreFunctions->opentable("select false as isposted, trno from lahead where agent='" . $data['username'] . "' and dateid='" . $data['dateid'] . "' and deviceid='" . $data['deviceid'] . "' and orderno='" . $data['orderno'] . "' union all select true as isposted, trno from glhead where agentid='" . $agentid . "' and dateid='" . $data['dateid'] . "' and deviceid='" . $data['deviceid'] . "' and orderno='" . $data['orderno'] . "'");
    } else {
      $c = $this->coreFunctions->opentable("select false as isposted, trno from " . $this->head . " where agent='" . $data['username'] . "' and dateid='" . $data['dateid'] . "' and deviceid='" . $data['deviceid'] . "' and orderno='" . $data['orderno'] . "'");
    }
    if (!empty($c)) {
      return ['status' => true, 'isposted' => $c[0]->isposted];
    } else {
      return ['status' => false, 'isposted' => false];
    }
  }
  public function checkItems($data, $items)
  {
    if ($this->company == 'sbc') {
      $agentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['username']]);
      $c = $this->coreFunctions->opentable("select trno from lahead where agent='" . $data['agent'] . "' and dateid='" . $data['dateid'] . "' and deviceid='" . $data['deviceid'] . "' and orderno='" . $data['orderno'] . "' union all select trno from glhead where agentid='" . $agentid . "' and dateid='" . $data['dateid'] . "' and deviceid='" . $data['deviceid'] . "' and orderno='" . $data['orderno'] . "'");
    } else {
      $c = $this->coreFunctions->opentable("select trno from lahead where agent='" . $data['username'] . "' and dateid='" . $data['dateid'] . "' and deviceid='" . $data['deviceid'] . "' and orderno='" . $data['orderno'] . "' limit 1");
    }
    if (!empty($c)) {
      $i = $this->coreFunctions->opentable("select count(*) as icount from (select trno from lastock where trno='" . $c[0]->trno . "' union all select trno from glstock where trno='" . $c[0]->trno . "') as t");
      if ($i[0]->icount == count($items)) {
        return false;
      } else {
        return true;
      }
    } else {
      return false;
    }
  }
  public function updateItems($data, $items, $head)
  {
    $item = [];
    if (!empty($head)) {
      foreach ($items as $i) {
        $item = $i;
        $last_line = $this->getLastLine($data['doc'], $head[0]->trno) + 1;
        if ($this->company == 'sbc') {
          if (!isset($item['isamt'])) $item['isamt'] = 0;
          if (!isset($item['amt'])) $item['amt'] = 0;
          if (!isset($item['isqty'])) $item['isqty'] = 0;
          if (!isset($item['iss'])) $item['iss'] = 0;
          if (!isset($item['total'])) $item['total'] = 0;
          if (!isset($item['uom'])) $item['uom'] = '';
          if (!isset($item['factor'])) $item['factor'] = 0;
          if (!isset($item['rem'])) $item['rem'] = '';
          if (!isset($item['disc'])) $item['disc'] = '';
          $item['warehouse'] = $head[0]->wh;
          $item['username'] = $data['username'];
          $agentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['username']]);
          $sql = "insert into lastock(line, trno, itemname, barcode, uom, wh, disc, rem, isamt, isqty, amt, iss, ext, void, encodedby, editby, ref, refx, linex, loc, expiry, iscomponent, outputid, itemcomm, itemhandling, agentid, original_qty) values('" . $last_line . "', '" . $head[0]->trno . "', '" . $item['itemname'] . "', '" . $item['barcode'] . "', '" . $item['uom'] . "', '" . $item['warehouse'] . "', '" . $item['disc'] . "', '" . $item['rem'] . "', '" . $item['isamt'] . "', '" . $item['isqty'] . "', '" . $item['amt'] . "', '" . $item['iss'] . "', '" . $item['total'] . "', 0, '" . $item['username'] . "', '', '', '0', '0', '', '', '0', '0', '0', '0', '" . $agentid . "', '" . $item['isqty'] . "')";
        } else {
          $ii = $this->coreFunctions->opentable("select itemname, uom from item where barcode='" . $i['barcode'] . "'");
          $item['itemname'] = $ii[0]->itemname;
          $item['warehouse'] = $data['warehouse'];
          $item['username'] = $data['username'];
          $item['total'] = $item['total'];
          if (!isset($item['ext'])) $item['ext'] = '';
          if (!isset($item['qty'])) $item['qty'] = 0;
          if (!isset($item['amt'])) $item['amt'] = 0;
          if (!isset($item['cost'])) $item['cost'] = 0;
          if (!isset($item['void'])) $item['void'] = 0;
          if (!isset($item['loc'])) $item['loc'] = '';
          if (!isset($item['ref'])) $item['ref'] = '';
          if (!isset($item['refx'])) $item['refx'] = 0;
          if (!isset($item['linex'])) $item['linex'] = 0;
          $item['isqty'] = $item['qty'];
          $item['isamt'] = str_replace(',', '', $item['amt']);
          if (!isset($item['disc'])) $item['disc'] = 0;
          if (!isset($item['factor'])) {
            $item['uomfactor'] = 1;
          } else {
            $item['uomfactor'] = $item['factor'];
          }
          if (!isset($item['vat'])) $item['vat'] = 0;
          $datum = $this->computeStock($item['isamt'], $item['disc'], $item['isqty'], $item['uomfactor'], $data['doc'], $item['vat']);
          $item['iss'] = $datum['iss'];
          $item['amt'] = $datum['amt'];
          $item['ext'] = $datum['ext'];
          $item['linex'] = '';
          $item['refx'] = '';
          $sql = "insert into lastock(line, trno, itemname, barcode, uom, wh, disc, rem, isamt, isqty, amt, iss, ext, void, encodedby, editby, ref, refx, linex, loc, expiry, iscomponent, outputid, itemcomm, itemhandling, agent, original_qty) values('" . $last_line . "', '" . $head[0]->trno . "', '" . $item['itemname'] . "', '" . $item['barcode'] . "', '" . $item['uom'] . "', '" . $item['warehouse'] . "', '" . $item['disc'] . "', '" . $item['rem'] . "', '" . $item['isamt'] . "', '" . $item['isqty'] . "', '" . $item['amt'] . "', '" . $item['iss'] . "', '" . $item['ext'] . "', 0, '" . $item['username'] . "', '', '', '0', '0', '', '', '0', '0', '0', '0', '', '" . $item['isqty'] . "')";
        }
        $ins = $this->coreFunctions->execqry($sql, 'insert');
        if ($ins) {
          $this->computecosting($item);
        } else {
          $this->coreFunctions->execqry("delete from cntnum where trno='" . $head[0]->trno . "'", 'delete');
          $this->coreFunctions->execqry("delete from lahead where trno='" . $head[0]->trno . "'", 'delete');
          $this->coreFunctions->execqry("delete from lastock where trno='" . $head[0]->trno . "'", 'delete');
          return false;
        }
      }
      return true;
    } else {
      return false;
    }
  }
  public function getLastLine($doc, $trno)
  {
    $stocks = $this->coreFunctions->opentable("select line from lastock where trno='" . $trno . "' order by line desc limit 1");
    if (count($stocks) == 0) {
      return 0;
    } else {
      return $stocks[0]->line;
    }
  }
  public function computeStock($amt, $disc, $qty, $uomfactor, $doc, $vat = 0)
  {
    if ($qty == 0) {
      $hiddenqty = $hiddenamt = 0;
    } else {
      $hiddenqty = abs($qty) * $uomfactor;
      $hiddenamt = ($this->Discount($amt * $qty, $disc) / $uomfactor) / $qty;
    }
    $ext = $this->Discount(floatval($amt), $disc);
    $ext = str_replace(',', '', $ext);
    $ext = floatval($ext) * floatval($qty);
    return ['iss' => $hiddenqty, 'amt' => $hiddenamt, 'ext' => $ext];
  }
  public function Discount($Amt, $Discount)
  {
    if ($Discount != '' && $Discount != 0) {
      $Disc = explode('/', $Discount);
    } else {
      $Disc = [];
    }
    $DiscV = '';
    for ($a = 0; (count($Disc) - 1) >= $a; $a++) {
      $m = -1;
      $DiscV = $Disc[$a];
      if ($this->left($Disc[$a], 1) == '+') {
        $DiscV = substr($Disc[$a], 1);
        $m = 1;
      }
      if ($this->right($DiscV, 1) == '%') {
        $AmountDisc = $Amt * floatval(($this->left($DiscV, strlen($DiscV) - 1)) / 100);
      } else {
        $AmountDisc = $Amt + ($AmountDisc * $m);
      }
    }
    return $Amt;
  }
  public function right($value, $count)
  {
    return substr($value, ($count * -1));
  }
  public function left($string, $count)
  {
    return substr($string, 0, $count);
  }
  public function getgrandtotal($trno, $doc)
  {
    $garray = $this->coreFunctions->opentable("select trno, sum(isqty) as kilototal, count(*) as itemcount, sum(ext) as grandtotal, sum(isamt * original_qty) as gTotal from " . $this->stock . " where trno='" . $trno . "' group by trno");
    if (!empty($garray)) {
      $garray[0]->forexgrandtotal = 0;
    }
    return $garray;
  }
  public function insertHead($data, $location)
  {
    $docnoLength = 15;
    $msg = '';
    $status = false;
    $d = $newdata = [];
    $data = $this->othersClass->sanitize($data, 'ARRAY');
    $clientcount = $this->coreFunctions->opentable("select client from client where client='" . $data['client'] . "'");
    $proceedtrans = false;
    if (!empty($clientcount)) $proceedtrans = true;
    if (!$proceedtrans) {
      $msg = "Invalid Customer/Supplier, Please try again.";
    } else {
      $pref = $this->last_bref($data['doc'], $data['center']);
      if (!$pref) {
        $prefixes = $this->getPrefixes($data['doc']);
        $pref = isset($prefixes[0]) ? $prefixes[0] : $data['doc'];
      }
      repeatcntnum:
      $seq = $this->getlastseq($pref, $data['doc'], $data['center']);
      $poseq = $pref . $seq;
      $data['docno'] = $this->PadJ($poseq, $docnoLength);
      $bref = $this->GetPrefix($data['docno']);
      $message = '';
      $docno = $data['docno'];
      $newdocno = $this->PadJ($poseq, $docnoLength);
      $insertcntnum = $this->insertcntnum($data['doc'], $data['center'], $newdocno, $seq, $bref, $data['username']);
      if ($insertcntnum > 0) {
        if ($data['docno'] != $newdocno && $insertcntnum != 0) {
          $docno = $newdocno;
          $data['docno'] = $newdocno;
          $message = "Your transaction has been saved under document # " . $newdocno;
        }
      } else {
        goto repeatcntnum;
      }
      $trno_ = $this->getTrnodocno($docno, $data['doc'], $data['center']);
      $trno = $trno_;

      $insert = $this->insertnewlahead($docno, $data['doc'], $trno, $data, $data['username'], $location);
      if ($insert) {
        if ($message != '') {
          $msg = $message;
        } else {
          $msg = "New document saved. [" . $newdocno . "]";
        }
        $newdata = json_decode($this->loadheaddata($data['doc'], $trno, $data['center']), true);
        $newdata = $newdata['head'];
        $status = true;
      } else {
        $msg = "Error saving new document";
      }
    }
    return ['msg' => $msg, 'status' => $status, 'data' => $newdata];
  }
  public function insertStock($data)
  {

    $head = $this->config['params']['head'];
    $doc = $head['doc'];
    $msg = '';
    $status = $ins = false;
    $stock = [];
    $last_line = $this->getLastLine($doc, $head['trno']) + 1;
    if ($this->company == 'sbc') {
      $itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=?', [$data['barcode']]);
      $whid = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$head['wh']]);
      $agentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$head['createby']]);
      $data['itemid'] = $itemid;
      $data['whid'] = $whid;
      $data['warehouse'] = $head['wh'];
      $data['line'] = $last_line;
      $data['agentid'] = $agentid;

      if (!isset($data['isamt'])) {
        $data['isamt'] = 0;
      } else {
        $data['isamt'] = str_replace(',', '', $data['isamt']);
      }
      if (!isset($data['amt'])) {
        $data['amt'] = 0;
      } else {
        $data['amt'] = str_replace(',', '', $data['amt']);
      }
      if (!isset($data['isqty'])) {
        $data['isqty'] = 0;
      } else {
        $data['isqty'] = str_replace(',', '', $data['isqty']);
      }
      if (!isset($data['iss'])) {
        $data['iss'] = 0;
      } else {
        $data['iss'] = str_replace(',', '', $data['iss']);
      }
      if (!isset($data['total'])) {
        $data['total'] = 0;
      } else {
        $data['total'] = str_replace(',', '', $data['total']);
      }
      if (!isset($data['uom'])) $data['uom'] = '';
      if (!isset($data['factor'])) $data['factor'] = 0;
      if (!isset($data['rem'])) $data['rem'] = '';
      if (!isset($data['disc'])) $data['disc'] = '';
      $data['trno'] = $head['trno'];

      $sql = "insert into lastock(line, trno, itemid, uom, disc, rem, isamt, isqty, amt, iss, ext, void, encodedby, editby, ref, refx, linex, loc, expiry, iscomponent, outputid, itemcomm, itemhandling, agentid, original_qty, whid) values('" . $data['line'] . "', '" . $head['trno'] . "', '" . $data['itemid'] . "', '" . $data['uom'] . "', '" . $data['disc'] . "', '" . $data['rem'] . "', '" . $data['isamt'] . "', '" . $data['isqty'] . "', '" . $data['amt'] . "', '" . $data['iss'] . "', '" . $data['total'] . "', '0', '" . $head['createby'] . "', '', '', '0', '0', '', '', '0', '0', '0', '0', '" . $data['agentid'] . "', '" . $data['isqty'] . "', '" . $data['whid'] . "')";
    } else {
      $item = $this->coreFunctions->opentable("select itemname, uom from item where barcode='" . $data['barcode'] . "'");
      $data['itemname'] = $item[0]->itemname;
      $data['warehouse'] = $head['wh'];
      $data['username'] = $head['createby'];
      $data['ext'] = $data['total'];
      if (!isset($data['ext'])) $data['ext'] = '';
      if (!isset($data['qty'])) $data['qty'] = 0;
      if (!isset($data['amt'])) $data['amt'] = 0;
      if (!isset($data['cost'])) $data['cost'] = 0;
      if (!isset($data['void'])) $data['void'] = 0;
      if (!isset($data['loc'])) $data['loc'] = '';
      if (!isset($data['ref'])) $data['ref'] = '';
      if (!isset($data['refx'])) $data['refx'] = 0;
      if (!isset($data['linex'])) $data['linex'] = 0;
      $data['isqty'] = $data['qty'];
      $data['isamt'] = str_replace(',', '', $data['amt']);
      if (!isset($data['disc'])) $data['disc'] = 0;
      if (!isset($data['factor'])) {
        $data['uomfactor'] = 1;
      } else {
        $data['uomfactor'] = $data['factor'];
      }
      if (!isset($data['vat'])) $data['vat'] = 0;
      $datum = $this->computeStock($data['isamt'], $data['disc'], $data['isqty'], $data['uomfactor'], $doc, $data['vat']);
      $data['iss'] = $datum['iss'];
      $data['amt'] = $datum['amt'];
      $data['ext'] = $datum['ext'];
      $data['linex'] = '';
      $data['refx'] = '';
      $data['trno'] = $head['trno'];
      $sql = "insert into lastock(line, trno, itemname, barcode, uom, wh, disc, rem, isamt, isqty, amt, iss, ext, void, encodedby, editby, ref, refx, linex, loc, expiry, iscomponent, outputid, itemcomm, itemhandling, agent, original_qty) values('" . $last_line . "', '" . $head['trno'] . "', '" . $data['itemname'] . "', '" . $data['barcode'] . "', '" . $data['uom'] . "', '" . $data['warehouse'] . "', '" . $data['disc'] . "', '" . $data['rem'] . "', '" . $data['isamt'] . "', '" . $data['isqty'] . "', '" . $data['amt'] . "', '" . $data['iss'] . "', '" . $data['ext'] . "', '0', '" . $data['username'] . "', '', '', '0', '0', '', '', '0', '0', '0', '0', '', '" . $data['isqty'] . "')";
    }
    $ins = $this->coreFunctions->execqry($sql, 'insert');
    if ($ins) {
      $this->computecosting($data);
      $msg = "Stock was successfully saved.";
      $status = true;
    } else {
      $msg = "An error occurred while saving the stock, [STOCK_ERR001]";
      $status = false;
      $this->coreFunctions->execqry("delete from cntnum where trno=" . $head['trno'], 'delete');
      $this->coreFunctions->execqry("delete from lahead where trno=" . $head['trno'], 'delete');
      $this->coreFunctions->execqry("delete from lastock where trno=" . $head['trno'], 'delete');
    }
    $stock = $this->openstockline($head['trno'], $last_line);
    $grandtotal = $this->getgrandtotal($head['trno'], $doc);
    if (!empty($grandtotal)) {
      $itemcount = $grandtotal[0]->itemcount;
      $gtotal = $grandtotal[0]->grandtotal;
    } else {
      $itemcount = $gtotal = 0;
    }
    return ['msg' => $msg, 'status' => $status, 'data' => $stock, 'itemcount' => $itemcount, 'gtotal' => $gtotal];
  }

  public function openstock($trno)
  {
    switch ($this->company) {
      case 'sbc':
        $stock = $this->coreFunctions->opentable("select item.brand as brand,
          item.itemid, stock.trno, stock.line, stock.refx, stock.linex, item.barcode,
          item.itemname, stock.uom, stock.cost, stock.qty, stock.amt, stock.iss, 
          round(stock.rrcost,2) as rrcost, round(stock.rrqty,2) as rrqty, 
          round(stock.isamt,2) as isamt, round(stock.isqty,2) as isqty, 
          round(stock.ext,2) as ext,left(stock.encodeddate,10) as encodeddate, stock.disc, stock.void,qa, 
          stock.ref, stock.loc,stock.loc2,stock.expiry,stock.agentid, a.client as agent,
          stock.rem, ifnull(uom.factor,1) as uomfactor, stock.original_qty from lastock as stock
          left join item on item.itemid = stock.itemid
          left join client as a on a.clientid=stock.agentid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where stock.trno = " . $trno);
        break;
      default:
        $stock = $this->coreFunctions->opentable("select item.brand as brand,
          item.itemid, lastock.trno, lastock.line, lastock.refx, lastock.linex, lastock.barcode,
          lastock.itemname, lastock.uom, lastock.cost, lastock.qty, lastock.amt, lastock.iss, 
          round(lastock.rrcost,2) as rrcost, round(lastock.rrqty,2) as rrqty, 
          round(lastock.isamt,2) as isamt, round(lastock.isqty,2) as isqty, 
          round(lastock.ext,2) as ext,left(lastock.encodeddate,10) as encodeddate, lastock.disc, lastock.void,qa, 
          lastock.ref, lastock.wh as whcode,warehouse.clientname as wh,
          lastock.loc,lastock.loc2,lastock.expiry,lastock.agent,
          lastock.rem, ifnull(uom.factor,1) as uomfactor, lastock.original_qty from lastock
          left join item on item.barcode = lastock.barcode
          left join uom on uom.itemid = item.itemid and uom.uom = lastock.uom
          left join client as warehouse on warehouse.client = lastock.wh 
          where lastock.trno = " . $trno);
        break;
    }
    return $stock;
  }
  public function openstockline($trno, $line)
  {
    switch ($this->company) {
      case 'sbc':
        $stock = $this->coreFunctions->opentable("select item.brand as brand,
          item.itemid, stock.trno, stock.line, stock.refx, stock.linex, item.barcode,
          item.itemname, stock.uom, stock.cost, stock.qty, stock.amt, stock.iss, 
          round(stock.rrcost,2) as rrcost, round(stock.rrqty,2) as rrqty, 
          round(stock.isamt,2) as isamt, round(stock.isqty,2) as isqty, 
          round(stock.ext,2) as ext,left(stock.encodeddate,10) as encodeddate, stock.disc, stock.void,qa, 
          stock.ref, stock.loc,stock.loc2,stock.expiry,stock.agentid, a.client as agent,
          stock.rem, ifnull(uom.factor,1) as uomfactor, stock.original_qty from lastock as stock
          left join item on item.itemid = stock.itemid
          left join client as a on a.clientid=stock.agentid
          left join uom on uom.itemid = item.itemid and uom.uom = stock.uom
          where stock.trno = " . $trno . " and stock.line = " . $line);
        break;
      default:
        $stock = $this->coreFunctions->opentable("select item.brand as brand,
          item.itemid, lastock.trno, lastock.line, lastock.refx, lastock.linex, lastock.barcode,
          lastock.itemname, lastock.uom, lastock.cost, lastock.qty, lastock.amt, lastock.iss, 
          round(lastock.rrcost,2) as rrcost, round(lastock.rrqty,2) as rrqty, 
          round(lastock.isamt,2) as isamt, round(lastock.isqty,2) as isqty, 
          round(lastock.ext,2) as ext,left(lastock.encodeddate,10) as encodeddate, lastock.disc, lastock.void,qa, 
          lastock.ref, lastock.wh as whcode,warehouse.clientname as wh,
          lastock.loc,lastock.loc2,lastock.expiry,lastock.agent,
          lastock.rem, ifnull(uom.factor,1) as uomfactor, lastock.original_qty from lastock
          left join item on item.barcode = lastock.barcode
          left join uom on uom.itemid = item.itemid and uom.uom = lastock.uom
          left join client as warehouse on warehouse.client = lastock.wh 
          where lastock.trno = " . $trno . " and lastock.line = " . $line);
        break;
    }
    return $stock;
  }
  public function getlastseq($prefix, $doc, $center)
  {
    $yr = floatval($this->coreFunctions->datareader("select ifnull(yr,0) as value from profile where psection='" . $doc . "' and doc='SED'"));
    if (floatval($yr) != 0) {
      $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from cntnum where bref='" . $prefix . "' and yr='" . $yr . "' and center='" . $center . "'");
    } else {
      $seq = $this->coreFunctions->opentable("select ifnull(max(seq),0) + 1 as seq from cntnum where bref='" . $prefix . "' and center='" . $center . "'");
    }
    return $seq[0]->seq;
  }
  public function last_bref($doc, $center)
  {
    $yr = floatval($this->coreFunctions->datareader("select yr as value from profile where psection='" . $doc . "' and doc='SED'"));
    if (floatval($yr) != 0) {
      $last = $this->coreFunctions->opentable("select bref from cntnum where doc='" . $doc . "' and yr='" . $yr . "' and center='" . $center . "' order by trno desc limit 1");
    } else {
      $last = $this->coreFunctions->opentable("select bref from cntnum where doc='" . $doc . "' and center='" . $center . "' order by trno desc limit 1");
    }
    if (!empty($last)) {
      return $last[0]->bref;
    } else {
      return '';
    }
  }
  public function getPrefixes($doc)
  {
    $prefixes = $this->Prefixes($doc);
    if (isset($prefixes[0]) && $prefixes[0] == '') {
      return empty($prefixes);
    } else {
      return $prefixes;
    }
  }
  public function Prefixes($pref)
  {
    $prefixes = "";
    $valid_pref = $this->coreFunctions->opentable("select pvalue from profile where psection='" . $pref . "' and doc='SED'");
    for ($i = 0; $i < count($valid_pref); $i++) {
      $prefixes = explode(',', $valid_pref[$i]->pvalue);
    }
    return $prefixes;
  }
  public function PadJ($PadString, $len, $yr = 0)
  {
    if ($len == 0) {
      return $PadString;
    }
    $suffix = $this->Getsuffix($PadString);
    $isno = $this->isnumber($suffix);
    $Prefix = strtoupper(substr($PadString, 0, $this->SearchPosition($PadString)));
    if ($Prefix == '') $Prefix = $PadString;
    $Number = floatval(substr($PadString, $this->SearchPosition($PadString), strlen($PadString)));
    if ($Number == 0) $Number = 1;
    $yr = floatval($yr);
    if ((strlen($Prefix) * strlen($Number)) < $len) {
      if ($isno) {
        if ($yr != 0) {
          $Return = strtoupper($Prefix) . $yr . str_pad($Number, $len - (strlen($Prefix) + 4), '0', STR_PAD_LEFT);
        } else {
          $Return = strtoupper($Prefix) . str_pad($Number, $len - (strlen($Prefix)), '0', STR_PAD_LEFT);
        }
      } else {
        if ($yr != 0) {
          $Return = strtoupper($Prefix) . $yr . str_pad($Number, $len - (strlen($Prefix) + 4), '0', STR_PAD_LEFT);
        } else {
          $Return = strtoupper($Prefix) . str_pad($Number, $len - (strlen($Prefix)), '0', STR_PAD_LEFT);
        }
      }
    } else {
      $Return = $PadString;
    }
    return $Return;
  }
  public function Getsuffix($PadString)
  {
    $Prefix = strtoupper(substr($PadString, -1));
    return $Prefix;
  }
  public function isnumber($prefix)
  {
    return (strspn($prefix, '1234567890'));
  }
  public function SearchPosition($search)
  {
    for ($i = 0; $i < strlen($search); $i++) {
      if (strspn(substr($search, $i, 1), '1234567890')) {
        return $i;
      }
    }
    return strlen($search);
  }
  public function GetPrefix($PadString)
  {
    $Prefix = strtoupper(substr($PadString, 0, $this->SearchPosition($PadString)));
    return $Prefix;
  }
  public function insertcntnum($doc, $center, $docno, $seq, $bref, $user)
  {
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    if ($this->company == 'sbc') {
      return $this->coreFunctions->execqry("insert into cntnum(doc, docno, seq, bref, center, postdate, postedby) values('" . $doc . "', '" . $docno . "', '" . $seq . "', '" . $bref . "', '" . $center . "', '" . $current_timestamp . "', '" . $user . "')", 'insert');
    } else {
      return $this->coreFunctions->execqry("insert into cntnum(doc, docno, seq, bref, center, postedby) values('" . $doc . "', '" . $docno . "', '" . $seq . "', '" . $bref . "', '" . $center . "', '')", 'insert');
    }
  }
  public function getTrnodocno($docno, $doc, $center)
  {
    return $this->coreFunctions->datareader("select trno as value from cntnum where doc='" . $doc . "' and docno='" . $docno . "' and center='" . $center . "'");
  }
  public function insertnewlahead($docno, $doc, $trno, $data, $user, $location)
  {
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $contra = $this->coreFunctions->opentable("select acno from coa where alias='AR1'");
    $data['contra'] = $contra[0]->acno;
    $client = $data['client'];
    $c = $this->coreFunctions->opentable("select clientname, addr from client where client='" . $client . "'");
    if (count($c) > 0) {
      $clientname = $c[0]->clientname;
      $addr = $c[0]->addr;
    } else {
      $clientname = $addr = '';
    }
    switch ($this->company) {
      case 'sbc':
        $agentid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['username']]);
        $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['client']]);
        $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data['warehouse']]);
        $data['agentid'] = $agentid;
        $data['clientid'] = $clientid;
        $data['whid'] = $whid;
        $insert = $this->coreFunctions->execqry("insert into " . $this->head . "(docno, doc, clientid, clientname, address, yourref, ourref, cur, forex, dateid, rem, shipto, terms, trno, whid, due, contra, vattype, salestype, tax, agentid, waybilldate, longitude, latitude, cmtrans, deviceid, orderno, isfinish, rem2) 
      values('" . $docno . "', '" . $doc . "', '" . $clientid . "', '" . $clientname . "', '" . $addr . "', '', '', 'P', '1', '" . $data['dateid'] . "', '" . $data['rem'] . "', '" . $data['shipto'] . "', '" . $data['terms'] . "', " . $trno . ", '" . $whid . "', '" . $data['dateid'] . "', '\\" . $data['contra'] . "', 'NON-VATABLE', '" . $data['paymenttype'] . "', '0', '" . $agentid . "', null, '" . $location['longitude'] . "', '" . $location['latitude'] . "', '" . $data['itemcount'] . "', '" . $data['deviceid'] . "', '" . $data['orderno'] . "', 0, 'Payment:" . $data['payment'] . " Change:" . $data['change'] . "')", 'insert');
        break;
      default:
        $insert = $this->coreFunctions->execqry("insert into lahead(docno, doc, client, clientname, address, yourref, ourref, cur, forex, dateid, rem, shipto, terms, trno, wh, due, contra, vattype, salestype, tax, agent, waybilldate, longitude, latitude, cmtrans, deviceid, orderno, isfinish) 
          values('" . $docno . "', '" . $doc . "', '" . $client . "', '" . $clientname . "', '" . $addr . "', '', '', 'P', '1', '" . $data['dateid'] . "', '', '" . $data['shipto'] . " " . $data['rem'] . "', '" . $data['terms'] . "', " . $trno . ", '" . $data['warehouse'] . "', '" . $data['dateid'] . "', '\\" . $data['contra'] . "', '', 'CHARGE', '0', '" . $data['agent'] . "', null, '" . $location['longitude'] . "', '" . $location['latitude'] . "', '" . $data['itemcount'] . "', '" . $data['deviceid'] . "', '" . $data['orderno'] . "', 0)", 'insert');
        break;
    }
    if ($insert == 1) {
      return true;
    } else {
      return false;
    }
  }
  public function loadheaddata($doc, $trno, $center)
  {
    $head = [];
    $qry = "select 
      cntnum.center,head.trno, head.docno,head.client,client.clientname, 
      head.terms,head.cur,head.forex, 
      head.yourref, head.ourref,head.wh as whid, warehouse.clientname as wh,warehouse.addr as wh_address,
      left(head.dateid,10) as dateid, head.clientname, address, head.shipto,
      DATE_FORMAT(head.createdate, '%Y-%m-%d') as createdate, 
      head.rem,head.tax,
      agent.client as agent,agent.clientname as agentname,left(head.due,10) as due,
      head.contra,coa.acnoname as contraname,
      head.vattype,head.salestype
      FROM lahead as head
      left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acno = head.contra
      left join client on head.client=client.client
      left join client as agent on agent.client=head.agent
      left join client as warehouse on warehouse.client=head.wh
      where head.trno=" . $trno . " and cntnum.center='" . $center . "'
      UNION ALL
      SELECT 
      cntnum.center,head.trno, head.docno,client.client,client.clientname, 
      head.terms,head.cur, head.forex, 
      head.yourref, head.ourref,warehouse.client as whid,warehouse.clientname as wh,
      warehouse.addr as wh_address,
      left(head.dateid,10) as dateid, head.clientname, address, head.shipto,
      DATE_FORMAT(head.createdate, '%Y-%m-%d') as createdate, 
      head.rem,head.tax,
      agent.client as agent,agent.clientname as agentname,
      left(head.due,10) as due,
      head.contra,coa.acnoname as contraname,
      head.vattype,head.salestype
      FROM glhead as head
      left join cntnum on cntnum.trno=head.trno
      left join coa on coa.acno = head.contra
      left join client on head.clientid=client.clientid
      left join client  as agent on agent.clientid=head.agentid
      left join client as warehouse on warehouse.clientid=head.whid
      where head.trno=" . $trno . " and cntnum.center='" . $center . "'";
    $head = $this->coreFunctions->opentable($qry);
    return json_encode(['head' => $head]);
  }

  public function computecosting($data)
  {
    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor,item.isnoninv from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$data['uom'], $data['itemid']]);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $ispallet = $this->companysetup->getispallet($this->config['params']);
    $factor = 1;
    $isnoninv = 0;
    if (!empty($item)) {
      $isnoninv = $item[0]->isnoninv;
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }
    if ($isnoninv == 0) {
      if ($ispallet) {
        $cost = $this->othersClass->computecostingpallet($data['itemid'], $data['whid'], 0, 0, $data['trno'], $data['line'], $data['iss'], 'SJ', $this->config['params']);
      } else {
        $cost = $this->othersClass->computecosting($data['itemid'], $data['whid'], '', '', $data['trno'], $data['line'], $data['iss'], 'SJ', $this->config['params']['companyid']);
      }
      if ($cost != -1) {
        $this->coreFunctions->sbcupdate('lastock', ['cost' => $cost], ['trno' => $data['trno'], 'line' => $data['line']]);

        //CHECK BELOW COST
        if ($this->companysetup->checkbelowcost($this->config['params'])) {
          $belowcost = $this->checkbelowcost($data['trno'], $data['line']);
          if ($belowcost == 1) {
            $this->coreFunctions->sbcupdate('lastock', ['isqty' => 0, 'iss' => 0, 'ext' => 0, 'editby' => 'BELOW COST', 'editdate' => $current_timestamp], ['trno' => $data['trno'], 'line' => $data['line']]);
            $this->coreFunctions->execqry("delete from costing where trno=? and line=?", "delete", [$data['trno'], $data['line']]);
            $this->logger->sbcwritelog($data['trno'], $this->config, 'STOCK', 'BELOW COST - Line:' . $data['line'] . ' barcode:' . $data['barcode'] . ' Qty:' . $data['isqty'] . ' Amt:' . $data['iss'] . ' Disc:' . $data['disc'] . ' wh:' . $data['warehouse'] . ' Ext:0.0');
            $msg = "(" . $item[0]->barcode . ") You cant't issue this item/s because it's BELOW COST!!!";
            $return = false;
          }
        }
      } else {
        $this->coreFunctions->sbcupdate('lastock', ['isqty' => 0, 'iss' => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $data['trno'], 'line' => $data['line']]);
        $this->coreFunctions->execqry("delete from costing where trno=? and line=?", 'delete', [$data['trno'], $data['line']]);
        $this->logger->sbcwritelog($data['trno'], $this->config, 'STOCK', 'OUT OF STOCK - Line:' . $data['line'] . ' barcode:' . $item[0]->barcode . ' Amt:' . $data['iss'] . ' Disc:' . $data['disc'] . ' wh:' . $data['warehouse'] . ' ext:0.0');
        $return = false;
        $msg = "(" . $item[0]->barcode . ") Out of Stock.";
      }
    }
  }

  public function checkbelowcost($trno, $line)
  {
    $belowcost = 0;
    $amt = $this->coreFunctions->getfieldvalue("lastock", "amt", "trno=? and line =?", [$trno, $line]);
    $qty = $this->coreFunctions->getfieldvalue("lastock", "iss", "trno=? and line =?", [$trno, $line]);
    $cost = $this->coreFunctions->getfieldvalue("lastock", "cost", "trno=? and line =?", [$trno, $line]);
    if (floatval($qty) != 0) {
      if (floatval($amt) == 0) {
        return 1;
      } else if (floatval($amt) < floatval($cost)) {
        if ($belowcost != '1') {
          return 2;
        }
      }
    }
  }

  public function postTrans()
  {
    $trno = $this->config['params']['head']['trno'];
    $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1', 'PC2']);
    if ($checkacct != '') return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    $stock = $this->openstock($trno);
    $checkcosting = $this->othersClass->checkcosting($stock);
    if ($checkcosting != '') return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
    if (!$this->createdistribution()) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed. Problems in creating accounting entries.'];
    } else {
      $post = $this->othersClass->posttranstock($this->config);
      if ($post['status']) {
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        return ['trno' => $trno, 'status' => false, 'msg' => 'Posting failed.'];
      }
    }
  }

  public function postingcbledger()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into gldetail(postdate,trno,line,acnoid,clientid,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
      editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void,branch,deptid,
      poref, podate, agentid,storetrno,station,qttrno,lastdp,sortline,isexcept,phaseid,modelid,blklotid)
      select d.postdate,d.trno,d.line,d.acnoid,
      ifNull(client.clientid,0),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
      d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
      d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void,d.branch,d.deptid,
      d.poref, d.podate, d.agentid,d.storetrno,d.station,d.qttrno,d.lastdp,d.sortline,d.isexcept,d.phaseid,d.modelid,d.blklotid
      from lahead as h
      left join ladetail as d on d.trno=h.trno
      left join client on client.client=d.client
      where  d.trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingcaledger()
  {
    $trno = $this->config['params']['trno'];
    $qry = "
      insert into caledger(dateid,trno,line,acnoid,clientid,db,cr,docno)
      select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),head.docno
      from lahead as head
      left join ladetail as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      left join client on client.client=d.client
      where left(coa.alias,2)='CA' and d.trno=? and d.refx=0";
    return $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingcrledger()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into crledger(checkdate,trno,line,acnoid,clientid,db,cr,docno,checkno)
      select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),head.docno,d.checkno
      from lahead as head
      left join ladetail as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      left join client on client.client=d.client
      where left(coa.alias,2)='CR' and d.trno=? and d.refx=0";
    return $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingarledger()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
      select d.postdate,d.trno,line,coa.acnoid,ifNull(client.clientid,0),round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal,
      head.docno,d.ref,ifnull(agent.clientid,0),d.fdb,d.fcr,d.forex
      from lahead as head
      left join ladetail as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      left join client on client.client=d.client
      left join client as agent on agent.client=head.agent
      where left(coa.alias,2)='AR' and d.trno=? and d.refx=0";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingapledger()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into apledger(dateid,trno,line,acnoid,clientid,db,cr,bal,fdb,fcr,docno,ref,cur,forex)
      select d.postdate,d.trno,line,d.acnoid,ifNull(client.clientid,0),round(db,2),
      round(cr,2),round(db,2)+round(cr,2) as bal,d.fdb,d.fcr,head.docno,d.ref,d.cur,d.forex
      from lahead as head
      left join ladetail as d on head.trno=d.trno
      left join coa on coa.acnoid=d.acnoid
      left join client on client.client=d.client
      where left(coa.alias,2)='AP' and d.trno=? and d.refx=0";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingdetail()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into gldetail (postdate,trno,line,acnoid,clientid,db,cr,fdb,fcr,refx,linex,encodeddate,encodedby,editdate,
      editby,ref,checkno,rem,clearday,pdcline,projectid,isewt,isvat,ewtcode,ewtrate,forex,isvewt,subproject,stageid,void,branch,deptid,
      poref, podate, agentid,storetrno,station,qttrno,lastdp,sortline,isexcept,phaseid,modelid,blklotid)
      select d.postdate,d.trno,d.line,d.acnoid,
      ifNull(client.clientid,0),d.db,d.cr,d.fdb,d.fcr,d.refx,d.linex,
      d.encodeddate,d.encodedby,d.editdate,d.editby,d.ref,d.checkno,d.rem,d.clearday,d.pdcline,d.projectid,
      d.isewt,d.isvat,d.ewtcode,d.ewtrate,d.forex,d.isvewt,d.subproject,d.stageid,d.void,d.branch,d.deptid,
      d.poref, d.podate, d.agentid,d.storetrno,d.station,d.qttrno,d.lastdp,d.sortline,d.isexcept,d.phaseid,d.modelid,d.blklotid
      from lahead as h
      left join ladetail as d on d.trno=h.trno
      left join client on client.client=d.client
      where  d.trno=?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingrrstatus()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into rrstatus(trno,line,clientid,itemid,cost,qty,bal,dateid,whid,uom,disc,docno,loc,expiry,cur,forex,receiveddate,locid,palletid)
      select stock.trno,stock.line,ifnull(client.clientid,0),stock.itemid,stock.cost,stock.qty,stock.qty,head.dateid,stock.whid,stock.uom,stock.disc,head.docno,ifnull(stock.loc,''),ifnull(stock.expiry,''),head.cur,head.forex,head.dateid,stock.locid,stock.palletid
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client on client.client=head.client
      where head.trno=? and stock.qty<>0 and stock.iss=0";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postingstock()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into glstock(trno,line,itemid,uom,whid,loc,loc2,expiry,ref,disc,cost,qty,void,rrcost,rrqty,ext,
      encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,isamt,amt,isqty,iss,tstrno,
      tsline,fcost,rebate,rem,stageid,locid,palletid,locid2,palletid2,isextract,pickerid,pickerstart,pickerend,whmanid,whmandate,forkliftid,suppid,itemstatus, 
      projectid,sorefx,solinex,sgdrate,poref, podate,isqty2,original_qty,reqtrno,reqline,agentid,kgs,insurance,sortline,freight)
      SELECT stock.trno, stock.line, item.itemid, stock.uom,stock.whid,stock.loc,stock.loc2,stock.expiry,stock.ref,stock.disc,stock.cost,
      stock.qty,stock.void,stock.rrcost, stock.rrqty, stock.ext, stock.encodeddate,stock.qa,
      stock.encodedby,stock.editdate,stock.editby,stock.sku,stock.refx,stock.linex,stock.isamt,
      stock.amt,stock.isqty,stock.iss ,stock.tstrno,stock.tsline,stock.fcost,stock.rebate,stock.rem,stock.stageid,
      stock.locid,stock.palletid,stock.locid2,stock.palletid2,stock.isextract,stock.pickerid,stock.pickerstart,stock.pickerend,
      stock.whmanid,stock.whmandate,stock.forkliftid,stock.suppid,stock.itemstatus, stock.projectid,stock.sorefx,stock.solinex,stock.sgdrate,stock.poref, 
      stock.podate,stock.isqty2,stock.original_qty,stock.reqtrno,stock.reqline,stock.agentid,stock.kgs,stock.insurance,stock.sortline,stock.freight
      FROM lastock as stock left join item on item.itemid=stock.itemid
      where stock.trno =?";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function postinghead()
  {
    $trno = $this->config['params']['trno'];
    $qry = "insert into glhead(trno,doc,docno,clientid,clientname,address,shipto,dateid,
      terms,rem,forex,yourref,ourref,createdate,createby,editby,editdate,lockdate,lockuser,whid,due,cur,tax,vattype,contra,deptid,project,ewt,ewtrate,agentid,creditinfo,crline,overdue,projectid,subproject,pltrno,
      deliverytype,customername,projectto,subprojectto,partreqtypeid,waybill,brtrno,shipid,billid,tel,branch,statid,taxdef,
      shipcontactid,billcontactid,invoiceno,invoicedate,qttrno,whref,ms_freight,mlcp_freight,salestype, sano, deldate, crref, returndate, cur2, forex2,sdate1,sdate2, empid)
      SELECT head.trno,head.doc, head.docno,ifnull(client.clientid,0), ifnull(head.clientname,''), head.address,head.shipto,
      head.dateid as dateid, head.terms, head.rem, head.forex,head.yourref, head.ourref,
      head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,ifnull(wh.clientid,0),
      head.due,head.cur,head.tax,head.vattype,head.contra,head.deptid,head.project,head.ewt,head.ewtrate,ifnull(agent.clientid,0),head.creditinfo,head.crline,head.overdue,head.projectid,head.subproject,head.pltrno,
      head.deliverytype,head.customername,head.projectto,head.subprojectto,head.partreqtypeid,head.waybill,head.brtrno,head.shipid,
      head.billid,head.tel,head.branch,head.statid,head.taxdef,head.shipcontactid,head.billcontactid,
      head.invoiceno,head.invoicedate,head.qttrno,head.whref,head.ms_freight,head.mlcp_freight,head.salestype,head.sano,head.deldate,head.crref,head.returndate,head.cur2,head.forex2,sdate1,sdate2, head.empid
      FROM lahead as head left join cntnum on
      cntnum.trno=head.trno left join client on client.client=head.client
      left join client as wh on wh.client=head.wh left join client as agent on agent.client = head.agent
      where head.trno=? limit 1";
    return  $this->coreFunctions->execqry($qry, 'insert', [$trno]);
  }

  public function isacctgbalance($trno)
  {
    $bal = $this->coreFunctions->getfieldvalue('ladetail', 'sum(db-cr)', 'trno=?', [$trno]);
    if ($bal == '' || $bal == null) $bal = 0;
    if ($bal == 0) {
      return true;
    } else {
      return false;
    }
  }

  public function isposted()
  {
    $document = $this->coreFunctions->datareader("select postdate as value from cntnum where trno=? limit 1", [$this->config['params']['trno']]);
    if ($document == '' || $document == null) {
      return false;
    } else {
      return true;
    }
  }

  public function createdistribution()
  {
    $trno = $this->config['params']['head']['trno'];
    $this->config['params']['trno'] = $trno;
    $status = true;
    $totalar = 0;
    $isvatexsales = $this->companysetup->getvatexsales($this->config['params']);
    $delcharge = $this->coreFunctions->getfieldvalue('lahead', 'ms_freight', 'trno=?', [$trno]);
    if ($delcharge == '') $delcharge = 0;
    $this->coreFunctions->execqry("delete from ladetail where trno=?", 'delete', [$trno]);
    $qry = "select head.dateid, head.client, head.tax, head.contra, head.cur, head.forex, stock.ext, wh.client as wh, ifnull(item.asset,'') as asset, ifnull(item.revenue,'') as revenue,
      item.expense, stock.isamt, stock.disc, stock.isqty, stock.cost, stock.iss, stock.fcost, head.projectid, client.rev, stock.rebate, head.taxdef
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join item on item.itemid=stock.itemid
      left join client on client.client=head.client
      left join client as wh on wh.clientid=stock.whid where head.trno=?";
    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    $tax = 0;
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->datareader("select acno as value from coa where alias='IN1'");
      $revacct = $this->coreFunctions->datareader("select acno as value from coa where alias='SA1'");
      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }
      $cur = $this->coreFunctions->getfieldvalue('lahead', 'cur', 'trno=?', [$trno]);
      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
        if ($vat != 0) {
          if ($isvatexsales) {
            $tax = number_format(($stock[$key]->ext * $tax2), 2, '.', '');
            $totalar = $totalar + $stock[$key]->ext;
          } else {
            $tax = number_format(($stock[$key]->ext / $tax1), 2, '.', '');
            $tax = number_format($stock[$key]->ext - $tax, 2, '.', '');
            $totalar = $totalar + number_format($stock[$key]->ext, 2, '.', '');
          }
        }
        if ($stock[$key]->revenue != '') {
          $revacct = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') $revacct = $stock[$key]->rev;
        }

        if (!isset($stock[$key]->taxdev)) $stock[$key]->taxdev = 0;
        if (!isset($stock[$key]->asset)) $stock[$key]->asset = '';
        if (!isset($stock[$key]->taxdef)) $stock[$key]->taxdef = 0;
        if (!isset($stock[$key]->fcost)) $stock[$key]->fcost = 0;
        if (!isset($stock[$key]->projectid)) $stock[$key]->projectid = 0;
        if (!isset($stock[$key]->rebate)) $stock[$key]->rebate = 0;

        $expense = isset($stock[$key]->expense) ? $stock[$key]->expense : '';
        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => number_format($stock[$key]->ext, 2, '.', ''),
          'ar' => $stock[$key]->taxdev == 0 ? number_format($stock[$key]->ext, 2, '.', '') : 0,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset != '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct,
          'expense' => $expense,
          'tax' => $stock[$key]->taxdef == 0 ? $tax : 0,
          'discamt' => number_format($disc * $stock[$key]->isqty, 2, '.', ''),
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => number_format($stock[$key]->cost * $stock[$key]->iss, 2, '.', ''),
          'fcost' => number_format($stock[$key]->fcost * $stock[$key]->iss, 2, '.', ''),
          'projectid' => $stock[$key]->projectid,
          'rebate' => $stock[$key]->rebate
        ];
        if ($isvatexsales) {
          $this->distributionvatex($params);
        } else {
          $this->distribution($params);
        }
      }
    }

    // entry ar and vat if with default tax
    $taxdef = $this->coreFunctions->getfieldvalue('lahead', 'taxdef', 'trno=?', [$trno]);
    if ($taxdef != 0) {
      $qry = "select client, forex, dateid, cur, branch, deptid, contra from lahead where trno=?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $d[0]->contra . "'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $d[0]->client,
        'db' => (($totalar + $taxdef) * $d[0]->forex),
        'cr' => 0,
        'postdate' => $d[0]->dateid,
        'cur' => $d[0]->cur,
        'forex' => $d[0]->forex,
        'fdb' => floatval($d[0]->forex) == 1 ? 0 : $totalar + $taxdef,
        'fcr' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);

      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where alias='TX2'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $d[0]->client,
        'cr' => ($taxdef * $d[0]->forex),
        'db' => 0,
        'postdate' => $d[0]->dateid,
        'cur' => $d[0]->cur,
        'forex' => $d[0]->forex,
        'fdb' => floatval($d[0]->forex) == 1 ? 0 : $taxdef,
        'fcr' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }

    if ($delcharge != 0) {
      $qry = "select client, forex, dateid, cur, branch, deptid, contra from lahead where trno=?";
      $d = $this->coreFunctions->opentable($qry, [$trno]);
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where alias='DC1'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $d[0]->client,
        'db' => 0,
        'cr' => $delcharge * $d[0]->forex,
        'postdate' => $d[0]->dateid,
        'cur' => $d[0]->cur,
        'forex' => $d[0]->forex,
        'fcr' => floatval($d[0]->forex) == 1 ? 0 : $delcharge,
        'fdb' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);

      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['acno'] . "'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $d[0]->client,
        'db' => ($delcharge * $d[0]->forex),
        'cr' => 0,
        'postdate' => $d[0]->dateid,
        'cur' => $d[0]->cur,
        'forex' => $d[0]->forex,
        'fdb' => floatval($d[0]->forex) == 1 ? 0 : $d[0]->dateid,
        'fcr' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }

    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        $this->acctg[$key]['editdate'] = $current_timestamp;
        $this->acctg[$key]['editby'] = $this->config['params']['user'];
        $this->acctg[$key]['encodeddate'] = $current_timestamp;
        $this->acctg[$key]['encodedby'] = $this->config['params']['user'];
        $this->acctg[$key]['trno'] = $this->config['params']['trno'];
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
      }
      if ($this->coreFunctions->sbcinsert('ladetail', $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $this->config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $this->config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }
    return $status;
  }

  public function distribution($params)
  {
    $periodic = $this->companysetup->getisperiodic($this->config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    if (floatval($forex) == 0) $forex = 1;
    // AR

    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['acno'] . "'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $params['client'],
        'db' => ($params['ar'] * $forex),
        'cr' => 0,
        'postdate' => $params['date'],
        'cur' => $cur,
        'forex' => $forex,
        'fdb' => floatval($forex) == 1 ? 0 : $params['ar'],
        'fcr' => 0,
        'projectid' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }

    //disc
    if (floatval($params['discamt']) != 0) {
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where alias='SD1'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $params['client'],
        'db' => ($params['discamt'] * $forex),
        'cr' => 0,
        'postdate' => $params['date'],
        'cur' => $cur,
        'forex' => $forex,
        'fcr' => 0,
        'fdb' => floatval($forex) == 1 ? 0 : $params['discamt'],
        'projectid' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }

    // inv
    if (!$periodic) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['inventory'] . "'");
        $entry = [
          'acnoid' => $acnoid,
          'client' => $params['wh'],
          'db' => 0,
          'cr' => $params['cost'],
          'postdate' => $params['date'],
          'cur' => $cur,
          'forex' => $forex,
          'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'],
          'fdb' => 0,
          'projectid' => 0
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);

        // cogs
        if ($params['expense'] == '') {
          $cogs = $this->coreFunctions->datareader("select acnoid as value from coa where alias='CG1'");
        } else {
          $cogs = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['expense'] . "'");
        }
        $entry = [
          'acnoid' => $cogs,
          'client' => $params['wh'],
          'db' => $params['cost'],
          'cr' => 0,
          'postdate' => $params['date'],
          'cur' => $cur,
          'forex' => $forex,
          'fcr' => 0,
          'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'],
          'projectid' => 0
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
      }
    }

    // rebate vitaline
    if (floatval($params['rebate']) != 0) {
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where alias='AR3'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $params['client'],
        'db' => 0,
        'cr' => $params['rebate'] * $forex,
        'postdate' => $params['date'],
        'cur' => $cur,
        'forex' => $forex,
        'fcr' => floatval($forex) == 1 ? 0 : $params['rebate'],
        'fdb' => 0,
        'projectid' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }

    if (floatval($params['tax']) != 0) {
      // sales
      $sales = ($params['ext'] - $params['rebate'] - $params['tax']);
      $sales = $sales + $params['discamt'];
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['revenue'] . "'");
        $entry = [
          'acnoid' => $acnoid,
          'client' => $params['client'],
          'cr' => ($sales * $forex),
          'db' => 0,
          'postdate' => $params['date'],
          'cur' => $cur,
          'forex' => $forex,
          'fcr' => floatval($forex) == 1 ? 0 : $sales,
          'fdb' => 0,
          'projectid' => 0
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
      }

      // output tax
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where alias='TX2'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $params['client'],
        'cr' => ($params['tax'] * $forex),
        'db' => 0,
        'postdate' => $params['date'],
        'cur' => $cur,
        'forex' => $forex,
        'fcr' => floatval($forex) == 1 ? 0 : $params['tax'],
        'fdb' => 0,
        'projectid' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    } else {
      // sales
      $sales = ($params['ext'] - $params['rebate']);
      $sales = round(($sales + $params['discamt']), 2);
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['revenue'] . "'");
        $entry = [
          'acnoid' => $acnoid,
          'client' => $params['client'],
          'cr' => ($sales * $forex),
          'db' => 0,
          'postdate' => $params['date'],
          'cur' => $cur,
          'forex' => $forex,
          'fcr' => floatval($forex) == 1 ? 0 : $sales,
          'fdb' => 0,
          'projectid' => 0
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
      }
    }
  }

  public function distributionvatex($params)
  {
    $periodic = $this->companysetup->getisperiodic($this->config['params']);
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;
    $this->acctg = [];
    if (floatval($forex) == 0) $forex = 1;

    // AR
    if (floatval($params['ar']) != 0) {
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['acno'] . "'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $params['client'],
        'db' => (($params['ar'] + $params['tax']) * $forex),
        'cr' => 0,
        'postdate' => $params['date'],
        'cur' => $cur,
        'forex' => $forex,
        'fdb' => floatval($forex) == 1 ? 0 : $params['ar'] + $params['tax'],
        'fcr' => 0,
        'projectid' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }

    // disc
    if ($this->companysetup->getissalesdisc($this->config['params'])) {
      if (floatval($params['discamt']) != 0) {
        $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where alias='SD1'");
        $entry = [
          'acnoid' => $acnoid,
          'client' => $params['client'],
          'db' => ($params['discamt'] * $forex),
          'cr' => 0,
          'postdate' => $params['date'],
          'cur' => $cur,
          'forex' => $forex,
          'fcr' => 0,
          'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']),
          'projectid' => 0
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
      }
    }

    // inv
    if (!isperiodi) {
      if (floatval($params['cost']) != 0) {
        $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['inventory'] . "'");
        $entry = [
          'acnoid' => $acnoid,
          'client' => $params['wh'],
          'db' => 0,
          'cr' => $params['cost'],
          'postdate' => $params['date'],
          'cur' => $cur,
          'forex' => $forex,
          'fcr' => floatval($forex) == 1 ? 0 : $params['fcost'],
          'fdb' => 0,
          'projectid' => 0
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);

        //cogs
        $cogs = $params['expense'] == 0 ? $this->coreFunctions->datareader("select acnoid as value from coa where alias='CG1'") : $params['expense'];
        $entry = [
          'acnoid' => $cogs,
          'client' => $params['wh'],
          'db' => $params['cost'],
          'cr' => 0,
          'postdate' => $params['date'],
          'cur' => $cur,
          'forex' => $forex,
          'fcr' => 0,
          'fdb' => floatval($forex) == 1 ? 0 : $params['fcost'],
          'projectid' => 0
        ];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
      }
    }

    // sales
    $sales = $params['ext'];
    if ($this->companysetup->getissalesdisc($this->config['params'])) $sales = round(($sales + $params['discamt']), 2);
    if (floatval($sales) != 0) {
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where acno='\\" . $params['revenue'] . "'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $params['client'],
        'cr' => ($sales * $forex),
        'db' => 0,
        'postdate' => $params['date'],
        'cur' => $cur,
        'forex' => $forex,
        'fcr' => floatval($forex) == 1 ? 0 : $sales,
        'fdb' => 0,
        'projectid' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }

    // output tax
    if ($params['tax'] != 0) {
      $acnoid = $this->coreFunctions->datareader("select acnoid as value from coa where alias='TX2'");
      $entry = [
        'acnoid' => $acnoid,
        'client' => $params['client'],
        'cr' => ($params['tax'] * $forex),
        'db' => 0,
        'postdate' => $params['date'],
        'cur' => $cur,
        'forex' => $forex,
        'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']),
        'fdb' => 0,
        'projectid' => 0
      ];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $this->config);
    }
  }

  public function saveTransaction($data, $items)
  {
    $this->config['items'] = $items;
    $this->config['params']['companyid'] = 32;
    $this->config['params']['doc'] = 'SJ';
    $this->config['params']['center'] = '001';

    $this->config['docmodule'] = $this->sj;
    $this->checkdocno('', 'GETLASTSEQ');
    $data['docno'] = $this->config['newdocno'];
    $data = $this->othersClass->sanitize($data, 'ARRAY');
    $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['createby'] = $data['username'];

    $insertcntnum = $this->insertcntnum2($data['doc'], $data['docno'], $this->config['seq'], $this->config['pref'], $this->config['params']['center'], 0);
    if ($insertcntnum == 0) {
      $i = 5;
      while ($insertcntnum == 0 && $i >= 1) {
        $i = $i - 1;
        $this->checkdocno($data['docno'], 'GETLASTSEQ');
        $this->isdocnoprefixvalid();
        $insertcntnum = $this->insertcntnum2($data['doc'], $this->config['newdocno'], $this->config['seq'], $this->config['pref'], $this->config['params']['center'], 0);
        if (($docno != $newdocno) && ($insertcntnum != 0)) {
          $docno = $newdocno;
          $this->config['params']['head']['docno'] = $newdocno;
          $this->config['msg'] = 'Your transaction has been saved under document # ' . $newdocno;
        }
      } //end white insertcntnum
    } //END insertcntnum 0

    if ($insertcntnum == 0) {
      $msg = '1.Error cannot create Document. Pls try Again...';
      $this->config['msg'] = $msg;
      $this->config['return'] = ['trno' => '', 'docno' => '', 'msg' => $msg, 'head' => [], 'type' => '', 'istransposted' => false];
    } else {
      $trno = $insertcntnum;
      $this->config['params']['head']['trno'] = $trno;
      // $this->config['params']['head'] = $data;
      $this->config['params']['head']['docno'] = $data['docno'];
      $this->config['params']['head']['doc'] = $data['doc'];
      $this->config['params']['head']['client'] = $data['client'];
      $this->config['params']['head']['clientname'] = $data['clientname'];
      $this->config['params']['head']['address'] = $data['address'];
      $this->config['params']['head']['cur'] = 'P';
      $this->config['params']['head']['forex'] = 1;
      $this->config['params']['head']['dateid'] = $data['dateid'];
      $this->config['params']['head']['rem'] = $data['rem'];
      $this->config['params']['head']['shipto'] = $data['shipto'];
      $this->config['params']['head']['terms'] = $data['terms'];
      $this->config['params']['head']['wh'] = $data['warehouse'];
      $this->config['params']['head']['due'] = $data['dateid'];
      $this->config['params']['head']['cmtrans'] = $data['itemcount'];
      $this->config['params']['head']['agent'] = $data['agent'];
      if ($data['paymenttype'] == 'CASH') {
        $contra = $this->coreFunctions->opentable("select acno from coa where alias='PC2'");
      } else {
        $contra = $this->coreFunctions->opentable("select acno from coa where alias='AR1'");
      }
      if (count($contra) > 0) {
        $this->config['params']['head']['contra'] = $contra[0]->acno;
      } else {
        $this->config['params']['head']['contra'] = '';
      }
      $this->config['params']['head']['vattype'] = 'NON-VATABLE';
      $this->config['params']['head']['salestype'] = $data['paymenttype'];
      $this->config['params']['head']['tax'] = 0;
      $this->config['params']['head']['deviceid'] = $data['deviceid'];
      $this->config['params']['head']['orderno'] = $data['orderno'];
      $this->config['params']['head']['isfinish'] = 0;
      $this->config['params']['head']['rem2'] = 'Payment:' . $data['payment'] . ' Change:' . $data['change'];
      $this->config['params']['head']['createby'] = $data['username'];
      $this->config['params']['head']['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $this->config['params']['head']['ourref'] = $data['orderno'];

      $this->config['params']['items'] = $items;

      if ($this->coreFunctions->sbcinsert('lahead', $this->config['params']['head']) == 1) {
        if (count($items) > 0) {
          foreach ($items as $ikey => $item) {
            $stock = $this->insertStock($item);
            if (!$stock['status']) {
              $this->coreFunctions->execqry("delete from cntnum where trno=" . $trno, 'delete');
              $this->coreFunctions->execqry("delete from lahead where trno=" . $trno, 'delete');
              $this->coreFunctions->execqry("delete from lastock where trno=" . $trno, 'delete');
              return ['status' => false, 'head' => $this->config['params']['head'], 'stock' => $items, 'itemcount' => count($items), 'gtotal' => 0, 'stat' => 2];
            }
            if (($ikey) + 1 == count($items)) {
              return ['status' => true, 'head' => $this->config['params']['head'], 'stocks' => $items, 'itemcount' => $data['itemcount'], 'gtotal' => 0, 'stat' => 1];
            }
          }
        } else {
          $this->coreFunctions->execqry("delete from cntnum where trno=" . $trno, 'delete');
          $this->coreFunctions->execqry("delete from lahead where trno=" . $trno, 'delete');
          return ['status' => false, 'head' => $this->config['params']['head'], 'stock' => $items, 'itemcount' => count($items), 'gtotal' => 0, 'stat' => 3];
        }
      } else {
        $this->coreFunctions->execqry("delete from cntnum where trno=" . $trno, 'delete');
        return ['status' => false, 'head' => $this->config['params']['head'], 'stock' => $items, 'itemcount' => count($items), 'gtotal' => 0, 'stat' => 4];
      }
    }
  }

  private function isdocnoprefixvalid()
  {
    $prefixes = $this->othersClass->getPrefixes($this->config['params']['doc'], $this->config);
    $blnExist = false;
    if (!empty($prefixes)) {
      for ($i = 0; $i < count($prefixes); $i++) {
        if ($this->config['pref'] == $prefixes[$i]) {
          $blnExist = true;
        } //END COMPARE
      } //END FOR LOOP
    } //END IF else
    $this->config['isdocnoprefixvalid'] = $blnExist;
    return $this;
  } //end function

  public function checkdocno($docno, $action)
  {
    $docnolength = $this->companysetup->getdocumentlength($this->config['params']);
    $pref = 'SJ';

    $seq = $this->othersClass->getlastseq($pref, $this->config);
    if ($seq == 0 || empty($pref)) {
      if (empty($pref)) $pref = strtoupper($docno);
      $seq = $this->othersClass->getlastseq($pref, $this->config);
    }
    $poseq = $pref . $seq;
    $yr = $this->coreFunctions->datareader("select yr as value FROM profile where psection ='" . $this->config['params']['doc'] . "' and doc ='SED'");
    $newdocno = $this->othersClass->PadJ($poseq, $docnolength, $yr);
    $this->config['pref'] = $pref;
    $this->config['seq'] = $seq;
    $this->config['newdocno'] = $newdocno;
    $this->config['yr'] = $yr;
    $this->config['docnolength'] = $docnolength;
  }

  private function insertcntnum2($doc, $docno, $seq, $bref, $center, $yr = 0)
  {
    if (!empty($center) || $center != '') {
      $col = [];
      $col = ['doc' => $doc, 'docno' => $docno, 'seq' => $seq, 'bref' => $bref, 'center' => $center, 'yr' => $yr];
      $table = $this->config['docmodule']->tablenum;
      return $this->coreFunctions->insertGetId($table, $col);
    } else {
      return -1;
    }
  }

  public function saveSignature($params)
  {
    $img = $params['img'];
    $filename = $params['filename'];
    $img = explode(',', $img);
    $resource = imagecreatefromstring(base64_decode($img[1]));
    $old_width = imagesx($resource);
    $old_height = imagesy($resource);
    $width = 150;
    $height = ($old_height / $old_width) * 150;
    $resource_copy  = imagecreatetruecolor($width, $height);
    imagealphablending($resource_copy, false);
    imagesavealpha($resource_copy, true);
    imagecopyresampled($resource_copy, $resource, 0, 0, 0, 0, $width, $height, $old_width, $old_height);
    imagepng($resource_copy, database_path() . '/' . $filename . '.png');
    imagedestroy($resource);
    imagedestroy($resource_copy);
    return ['status' => true, 'msg' => 'waw'];
  }

  public function updateEToken($params)
  {
    $msg = '';
    $status = false;
    if (isset($params['token'])) {
      $token = $params['token'];
      $user = $params['storage'];
      if ($this->coreFunctions->execqry("update employee set dtoken='".$token."' where empid='".$user['id']."'", 'update') == 1) {
        $status = true;
        $msg = 'Token updated';
      }
    }
    return json_encode(['status'=>$status, 'msg'=>$msg]);
  }

  public function sendSampleNotif($params)
  {
    $dtoken = $this->coreFunctions->datareader("select dtoken as value from employee where empid=182");
    return $this->othersClass->sendNotif($dtoken, ['title'=>'Employee Parnaso, Jad Oelzon Logged-in', 'body'=>'Date:2025-07-07 Time:08:00:00']);
  }
}
