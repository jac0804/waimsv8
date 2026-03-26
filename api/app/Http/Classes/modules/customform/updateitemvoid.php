<?php

namespace App\Http\Classes\modules\customform;

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

class updateitemvoid
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'List of Items';
  public $gridname = 'voidgrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function createTab($config)
  {
    $barcode = 0;
    $itemname = 1;
    $itemdesc = 2;
    $specs = 3;
    $uom = 4;
    $wh = 5;
    $isqty = 6;
    $qa = 7;
    $tab = [$this->gridname => ['gridcolumns' => ['barcode', 'itemname', 'itemdesc', 'specs', 'uom', 'wh', 'isqty', 'qa']]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    if ($config['params']['doc'] == 'PA') {
      $obj[0][$this->gridname]['columns'][$wh]['type'] = 'coldel';
      $obj[0][$this->gridname]['columns'][$qa]['type'] = 'coldel';
    }

    if ($config['params']['doc'] != 'CD') {
      $obj[0][$this->gridname]['columns'][$specs]['type'] = 'coldel';
    }

    switch ($config['params']['doc']) {
      case 'PR':
      case 'CD':
        $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = 'Item Name (PR)';
        break;

      default:
        $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'coldel';
        break;
    }



    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Item description';

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    return [];
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable('select 0.0 as bal');
  }

  public function data($config)
  {
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $qry = '';
    switch ($doc) {
      case 'PO':
        if ($config['params']['companyid'] == 16) { //ati
          $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,ifnull(info.itemdesc,'') as itemname,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,stock.sorefx,stock.solinex,stock.cvtrno,stock.cdrefx,stock.cdlinex,stock.reqtrno,stock.reqline from hpostock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline where stock.trno =? and stock.qa<>stock.qty and stock.void=0";
        } else {
          $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,stock.sorefx,stock.solinex,stock.refx,stock.linex from hpostock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and stock.qa<>stock.qty and stock.void<>1";
        }
        break;
      case 'JO':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa from hjostock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and stock.qa<>stock.qty and stock.void<>1";
        break;
      case 'JB':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,stock.refx,stock.linex from hjostock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and stock.qa<>stock.qty and stock.void<>1";
        break;
      case 'SO':
      case 'BQ':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.isqty as isqty,round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa from hsostock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and qa<>iss and void<>1 ";
        break;
      case 'SA':
      case 'SB':
      case 'SC':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.isqty as isqty,round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa from h" . strtolower($doc) . "stock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and qa<>iss and void<>1 ";
        break;
      case 'CD':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa,ifnull(info.itemdesc,'') as itemname, ifnull(info.itemdesc2,'') as itemdesc,ifnull(info.specs,'') as specs from hcdstock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline where stock.trno =? and stock.void<>1 and stock.qa<>stock.qty ";
        break;
      case 'RQ':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-(stock.qa+stock.cdqa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa from hprstock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and (stock.qa+stock.cdqa)<>stock.qty and stock.void<>1 ";
        break;
      case 'PR':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname, info.itemdesc,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-(stock.qa))/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa ,stock.cdqa,stock.poqa
        from hprstock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid left join hstockinfotrans as info on info.trno=stock.trno and info.line=stock.line where stock.trno =? and stock.qa<>stock.qty and stock.void<>1 ";

        break;
      case 'PA':
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,stock.isqty as isqty from hpastock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom where stock.trno =? and void<>1 ";
        break;
      case 'WA': // warranty request
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.rrqty as isqty,round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa from hwastock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and stock.qa<>stock.qty and stock.void<>1";
        break;
      case 'SG': // special part request
        $qry = "select concat(stock.trno,stock.line) as keyid,stock.trno,stock.line,item.barcode,item.itemname,stock.uom,wh.client as wh,stock.isqty as isqty,round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as qa from hsgstock as stock left join item on item.itemid = stock.itemid left join uom on uom.itemid = item.itemid and uom.uom = stock.uom left join client as wh on wh.clientid=stock.whid where stock.trno =? and qa<>iss and void<>1 ";
        break;
    }

    if ($qry == '') {
      return [];
    } else {
      $data = $this->coreFunctions->opentable($qry, [$trno]);
      return $data;
    }
  } //end function

  public function loaddata($config)
  {
    $trno = $config['params']['trno'];
    $rows = $config['params']['rows'];
    $doc = $config['params']['doc'];
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $path = '';
    switch ($doc) {
      case 'PO':
        if ($systemtype == 'ATI') {
          $path = 'App\Http\Classes\modules\ati\\' . strtolower($doc);
        } else {
          $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        }
        $table = 'hpostock';
        break;
      case 'JO':
        $path = 'App\Http\Classes\modules\construction\\' . strtolower($doc);
        $table = 'hjostock';
        break;
      case 'JB':
        $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        $table = 'hjostock';
        break;
      case 'SO':
        $path = 'App\Http\Classes\modules\sales\\' . strtolower($doc);
        $table = 'hsostock';
        break;
      case 'BQ':
        $path = 'App\Http\Classes\modules\construction\\' . strtolower($doc);
        $table = 'hsostock';
        break;
      case 'CD':
        if ($systemtype == 'ATI') {
          $path = 'App\Http\Classes\modules\ati\\' . strtolower($doc);
        } else {
          $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        }
        $table = 'hcdstock';
        break;
      case 'PR':
        if ($systemtype == 'ATI') {
          $path = 'App\Http\Classes\modules\ati\\' . strtolower($doc);
        } else {
          $path = 'App\Http\Classes\modules\purchase\\' . strtolower($doc);
        }
        $table = 'hprstock';
        break;
      case 'RQ':
        $path = 'App\Http\Classes\modules\construction\\' . strtolower($doc);
        $table = 'hprstock';
        break;
      case 'PA':
        $path = 'App\Http\Classes\modules\pos\\' . strtolower($doc);
        $table = 'hpastock';
        break;
      case 'SA':
      case 'SB':
      case 'SC':
      case 'WA':
      case 'SG':
        $path = 'App\Http\Classes\modules\warehousing\\' . strtolower($doc);
        $table = 'h' . strtolower($doc) . 'stock';
        break;
    }

    foreach ($rows as $key) {
      if ($doc == 'CD') {
        $this->coreFunctions->execqry('update ' . $table . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
        $this->coreFunctions->execqry('update oqstock set void=1 where cdrefx=? and cdlinex=?', 'update', [$key['trno'], $key['line']]);
        $this->coreFunctions->execqry('update hoqstock set void=1 where cdrefx=? and cdlinex=?', 'update', [$key['trno'], $key['line']]);

        if ($companyid == 16) { //ati
          $cdstock = $this->coreFunctions->opentable("select refx,linex from hcdstock where approveddate is not null and trno=" . $key['trno'] . " and line=" . $key['line']);
          $this->coreFunctions->LogConsole("select refx,linex from hcdstock where approveddate is not null and trno=" . $key['trno'] . " and line=" . $key['line']);
          if (!empty($cdstock)) {
            $qry1 = "";
            $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $cdstock[0]->refx . " and stock.linex=" . $cdstock[0]->linex;
            $qry1 = $qry1 . " union all select stock.qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $cdstock[0]->refx . " and stock.linex=" . $cdstock[0]->linex;
            $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
            $cdqa = $this->coreFunctions->datareader($qry2);
            if ($cdqa == '') {
              $cdqa = 0;
            }
            $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . " where trno=" . $cdstock[0]->refx . " and line=" . $cdstock[0]->linex);
            $this->logger->sbcwritelog($cdstock[0]->refx, $config, 'STOCK', 'VOID CD' . ' CDLine:' . $key['line'] . ' Item Name:' . $key['itemname'] . ' isQTY:' . $key['isqty'], app($path)->tablelogs);

            $chkcd = $this->coreFunctions->opentable("select stock.trno
                                                        from cdstock as stock
                                                        where stock.refx = " . $cdstock[0]->refx . " and stock.linex = " . $cdstock[0]->linex . " and stock.void=0
                                                        union all
                                                        select stock.trno
                                                        from hcdstock as stock
                                                        where stock.refx = " . $cdstock[0]->refx . " and stock.linex = " . $cdstock[0]->linex . " and stock.void=0");

            if (empty($chkcd)) {

              $this->coreFunctions->execqry("update hprstock set iscanvass= 0 where trno=" . $cdstock[0]->refx . " and line=" . $cdstock[0]->linex);
              $this->logger->sbcwritelog($cdstock[0]->refx, $config, 'STOCK', 'Remove tagging of Canvass.' . ' CDLine:' . $key['line'] . ' Item Name:' . $key['itemname'] . ' isQTY:' . $key['isqty'], app($path)->tablelogs);
            }
          }
        }
      } else {
        if ($companyid == 16) { //ati
          if ($doc == 'PR') {
            if ($key['cdqa'] > 0) {
              $data = $this->data($config);
              return ['row' => $key, 'status' => false, 'msg' => "Can't void " . $key['itemname'] . ". Item already served in Canvass Sheet module. ", 'trno' => $key['trno'], 'showmsg' => true, 'msgcolor' => 'negative', 'data' => $data];
            }

            if ($key['poqa'] > 0) {
              $data = $this->data($config);
              return ['row' => $key, 'status' => false, 'msg' => "Can't void " . $key['itemname'] . ". Item already served in Purchase Order module. ", 'trno' => $key['trno'], 'showmsg' => true, 'msgcolor' => 'negative', 'data' => $data];
            }
          }
          if ($doc == 'PO') {
            $release = $this->coreFunctions->getfieldvalue("cntnuminfo", "releasedate", "trno=?", [$key['cvtrno']]);
            if ($release != '') {
              $data = $this->data($config);
              return ['row' => $key, 'status' => false, 'msg' => "Can't void " . $key['itemname'] . ". Payment already released in Cash/Check Voucher.", 'trno' => $key['trno'], 'showmsg' => true, 'msgcolor' => 'negative', 'data' => $data];
            } else {
              $this->coreFunctions->execqry('update hcdstock set void=1 where trno=? and line=?', 'update', [$key['cdrefx'], $key['cdlinex']]);
              $this->coreFunctions->execqry('update hprstock set poqa=0 where trno=? and line=?', 'update', [$key['reqtrno'], $key['reqline']]);

              $cdstock = $this->coreFunctions->opentable("select refx,linex from hcdstock where trno=" . $key['cdrefx'] . " and line=" . $key['cdlinex']);
              if (!empty($cdstock)) {
                $qry1 = "";
                $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $cdstock[0]->refx . " and stock.linex=" . $cdstock[0]->linex;
                $qry1 = $qry1 . " union all select stock.qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $cdstock[0]->refx . " and stock.linex=" . $cdstock[0]->linex;
                $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";

                $cdqa = $this->coreFunctions->datareader($qry2);
                if ($cdqa == '') {
                  $cdqa = 0;
                }
                $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . " where trno=" . $cdstock[0]->refx . " and line=" . $cdstock[0]->linex);
                $this->logger->sbcwritelog($cdstock[0]->refx, $config, 'STOCK', 'VOID PO' . ' CDLine:' . $key['cdlinex'] . ' Item Name:' . $key['itemname'] . ' isQTY:' . $key['isqty'], app($path)->tablelogs);

                $chkcd = $this->coreFunctions->opentable("select stock.trno
                                                        from cdstock as stock
                                                        where stock.refx = " . $cdstock[0]->refx . " and stock.linex = " . $cdstock[0]->linex . " and stock.void=0
                                                        union all
                                                        select stock.trno
                                                        from hcdstock as stock
                                                        where stock.refx = " . $cdstock[0]->refx . " and stock.linex = " . $cdstock[0]->linex . " and stock.void=0");

                if (empty($chkcd)) {
                  $this->coreFunctions->execqry("update hprstock set iscanvass= 0 where trno=" . $cdstock[0]->refx . " and line=" . $cdstock[0]->linex);
                  $this->logger->sbcwritelog($cdstock[0]->refx, $config, 'STOCK', 'Remove tagging of Canvass.' . ' CDLine:' . $key['cdlinex'] . ' Item Name:' . $key['itemname'] . ' isQTY:' . $key['isqty'], app($path)->tablelogs);
                }
              }
            }
          }
        }
        $this->coreFunctions->execqry('update ' . $table . ' set void=1 where trno=? and line=?', 'update', [$key['trno'], $key['line']]);
      }

      if ($doc == 'PO') {
        $pending = $this->coreFunctions->opentable("select trno from " . $table . " where trno=" . $key['trno'] . " and qty>qa and void=0");
        if (empty($pending)) {
          $this->coreFunctions->sbcupdate("transnum", ['statid' => 7], ['trno' => $key['trno']]);
        }
      }

      if ($doc == 'SO') {
        if ($key['trno'] != 0) {
          app("App\Http\Classes\modules\sales\\sj")->setserveditems($key['trno'], $key['line']);
        }
      }

      if ($doc == 'JB') {
        if ($key['refx'] != 0) {
          app($path)->setserveditems($key['refx'], $key['linex']);
        }
      }


      if ($companyid == 10 || $companyid == 12) { //afti, afti usd
        if ($doc == 'PO') {
          if ($key['sorefx'] != 0) {
            app($path)->setservedsqitems($key['sorefx'], $key['solinex']);
          }
        }
      }

      if ($companyid == 8) { //maxipro
        if ($doc == 'PO') {
          if ($key['refx'] != 0) {
            app($path)->setserveditems($key['refx'], $key['linex'], 1);
          }
        }
      }

      if ($doc == 'PA') {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'VOID - Doc:' . $doc . ' Line:' . $key['line'] . ' Barcode:' . $key['barcode'] . ' isQTY:' . $key['isqty'], app($path)->tablelogs);
      } else {

        if ($companyid == 16 && $doc == 'PO') { //ati
          $this->logger->sbcwritelog($key['cdrefx'], $config, 'STOCK', 'VOID - Doc:' . $doc . ' Line:' . $key['cdlinex'] . ' Item Name:' . $key['itemname'] . ' WH:' . $key['wh'] . ' isQTY:' . $key['isqty'], app($path)->tablelogs);
        }

        if ($companyid == 16) { //ati
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'VOID - Doc:' . $doc . ' Line:' . $key['line'] . ' Item Name:' . $key['itemname'] . ' WH:' . $key['wh'] . ' isQTY:' . $key['isqty'] . ' QA:' . $key['qa'], app($path)->tablelogs);
        } else {
          $this->logger->sbcwritelog($trno, $config, 'STOCK', 'VOID - Doc:' . $doc . ' Line:' . $key['line'] . ' Barcode:' . $key['barcode'] . ' WH:' . $key['wh'] . ' isQTY:' . $key['isqty'] . ' QA:' . $key['qa'], app($path)->tablelogs);
        }
      }
    }


    $data = $this->data($config);

    return ['status' => true, 'msg' => 'Successfully updated.', 'showmsg' => true, 'msgcolor' => 'positive', 'data' => $data];
  } //end function
































} //end class
