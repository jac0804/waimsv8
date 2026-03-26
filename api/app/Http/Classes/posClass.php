<?php

namespace App\Http\Classes;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use App\Http\Classes\Logger;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

use Illuminate\Support\Facades\Storage;
use Datetime;
use DateInterval;
use Exception;
use Illuminate\Support\Str;

class posClass
{
  private $othersClass;
  private $coreFunctions;
  private $logger;
  private $companysetup;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->logger = new Logger;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
  } //end fn


  public function ftpwritefile($filename, $content)
  {
    Storage::disk('ftp')->put($filename . '.tmp', $content);
    if (is_array($this->ftpfilecheckendfile($filename . '.tmp'))) {
      Storage::disk('ftp')->move($filename . '.tmp', $filename . '.sbc');
    } else {
      Storage::disk('ftp')->delete($filename . '.tmp');
    }
    return ['status' => true];
  } //end function

  public function ftpcreatefolder($branch, $station, $folder)
  {
    $path = '/' . $branch . '/' . $station . '/' . $folder . '/';
    if (!(Storage::disk('ftp')->exists($path) && Storage::disk('ftp')->getMetadata($path)['type'] === 'dir')) {
      $this->coreFunctions->LogConsole("path '$path' is not a directory");
      Storage::disk('ftp')->makeDirectory($path);
    } else {
      // $this->coreFunctions->LogConsole("path '$path' exists");
    }
    return ['status' => true];
  }


  public function ftpdeletefile($filename, $mirror = false)
  {
    if ($mirror) {
      if (Storage::disk('ftpmirror')->exists($filename)) {
        Storage::disk('ftpmirror')->delete($filename);
      }
    } else {
      if (Storage::disk('ftp')->exists($filename)) {
        Storage::disk('ftp')->delete($filename);
      }
    }

    return ['status' => true];
  } //end function


  public function itemlist()
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $starttime = $this->coreFunctions->datareader('select dlock as value from itemdlock order by dlock desc limit 1');
    if ($starttime != '') {
      $sql = "
        SELECT item.itemid,barcode,REPLACE(itemname,'\r\n','') as itemname,ifnull(stockgrp.stockgrp_name,'') as groupid,ifnull(pmaster.part_name,'') as part,model,ifnull(brand.brand_desc,'') as brand,ifnull(itemclass.cl_name,'') as class,body,sizeid,ifnull(cat.name,'') as category,cur,supp,acost,bcost,cost,avecost,uom,pc,bin,qty,minimum,maximum,reo,oqty,tqty,amt,asset,liability,country,
        disc,caption,isinactive,amt3,shortname,critical,hierarchy,ispositem,color,points,
        isvat as taxable,itemrem,issenior,iszerorated,isdisplay,isprintable,partno,suppname as supplier,subcode,isgc,amt2,disc2,famt,disc3,amt4,disc4,amt5,disc5,amt6,disc6,subcode,gender,`channel`
        FROM itemdlock AS id  LEFT JOIN item ON item.itemid=id.itemid 
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
        left join part_masterfile as pmaster on pmaster.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join item_class as itemclass on itemclass.cl_id = item.class
        left join itemcategory as cat on cat.line = item.category
        WHERE item.barcode<>'' and item.ispositem=1 and id.dlock<=?
      ";
      $item = $this->coreFunctions->opentable($sql, [$starttime]);

      $sql = "select uom.itemid, uom.uom, uom.factor, uom.isinactive, uom.isdefault2 as isdefault, uom.amt FROM itemdlock AS id  LEFT JOIN uom on uom.itemid=id.itemid where uom.itemid is not null";
      $uom = $this->coreFunctions->opentable($sql);

      $batchSize = 10000;

      //creating csv files for item
      $totalRowsItem = count($item);

      $result = true;

      $this->coreFunctions->LogConsole('items: ' . $totalRowsItem);

      $counter = 1;
      for ($offset = 0; $offset < $totalRowsItem; $offset += $batchSize) {
        $batch = array_slice($item, $offset, $batchSize);

        $this->coreFunctions->LogConsole('creating item csv batch ' . $counter);

        $csv = '';
        $csv = $this->createcsv($batch, 1);

        $qry = "select clientid,client from client where isbranch=1 and isinactive=0";
        $branches = $this->coreFunctions->opentable($qry);
        foreach ($branches as $branch => $value) {
          $qry = "select station from branchstation where clientid =? and isinactive=0";
          $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
          foreach ($stations as $station => $value2) {
            $result = $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'item', 1, ".b" . $counter);
            $this->ftpcreatefolder($value->client, $value2->station, 'upload');
          }
        }

        $counter += 1;
      }



      //creating csv files for uom
      $totalRowsUOM = count($uom);

      $this->coreFunctions->LogConsole('uom: ' . $totalRowsUOM);

      $counter = 1;
      for ($offset = 0; $offset < $totalRowsUOM; $offset += $batchSize) {
        $batch = array_slice($uom, $offset, $batchSize);

        $this->coreFunctions->LogConsole('creating uom csv batch ' . $counter);

        $csvuom = '';
        $csvuom = $this->createcsv($batch, 1);

        $qry = "select clientid,client from client where isbranch=1 and isinactive=0";
        $branches = $this->coreFunctions->opentable($qry);
        foreach ($branches as $branch => $value) {
          $qry = "select station from branchstation where clientid =? and isinactive=0";
          $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
          foreach ($stations as $station => $value2) {
            $result = $this->ftpcreatefile($csvuom, $value->client, $value2->station, 'download', 'uom', 1, ".b" . $counter);
          }
        }

        $counter += 1;
      }

      if (boolval($result)) {
        $this->coreFunctions->execqry('delete from itemdlock where dlock<=?', 'delete', [$starttime]);
      }
    }
  } //end function

  public function pricelist($dlock)
  {
    $batchSize = 50000;

    $sql = "select pl.line, pl.itemid, pl.amount, pl.amount2, pl.clientid, pl.cost, date(pl.startdate) as startdate, date(pl.enddate) as enddate 
          from pricelist as pl " . ($dlock != '' ? " where ifnull(pl.dlock,now())>'" . $dlock . "'" : "") . " order by pl.line";
    $pricelist = $this->coreFunctions->opentable($sql);

    //creating csv files for pricelist
    $totalRowsPrice = count($pricelist);

    $this->coreFunctions->LogConsole('pricelist: ' . $totalRowsPrice);

    $now = $this->othersClass->getCurrentTimeStamp();

    $counter = 1;
    for ($offset = 0; $offset < $totalRowsPrice; $offset += $batchSize) {
      $batch = array_slice($pricelist, $offset, $batchSize);

      $this->coreFunctions->LogConsole('creating pricelist csv batch ' . $counter);

      $csvprice = '';
      $csvprice = $this->createcsv($batch, 1);

      $qry = "select clientid,client from client where isbranch=1 and isinactive=0";
      $branches = $this->coreFunctions->opentable($qry);
      foreach ($branches as $branch => $value) {
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        foreach ($stations as $station => $value2) {
          $result = $this->ftpcreatefile($csvprice, $value->client, $value2->station, 'download', 'pricelist', 1, ".b" . $counter);
        }
      }

      // foreach ($batch as $key => $val) {
      //   $this->coreFunctions->execqry("update pricelist set dlock='" . $now . "' where dlock is null and line=" . $val['line']);
      // }

      $counter += 1;
    }
  }

  public function itemlist_bk()
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $starttime = $this->coreFunctions->datareader('select dlock as value from itemdlock order by dlock desc limit 1');
    if ($starttime != '') {
      $sql = "
        SELECT item.itemid,barcode,REPLACE(itemname,'\r\n','') as itemname,ifnull(stockgrp.stockgrp_name,'') as groupid,ifnull(pmaster.part_name,'') as part,model,ifnull(brand.brand_desc,'') as brand,ifnull(itemclass.cl_name,'') as class,body,sizeid,ifnull(cat.name,'') as category,cur,supp,acost,bcost,cost,avecost,uom,pc,bin,qty,minimum,maximum,reo,oqty,tqty,amt,asset,liability,country,
        disc,caption,isinactive,amt3,shortname,critical,hierarchy,ispositem,color,points,
        isvat as taxable,itemrem,issenior,iszerorated,isdisplay,isprintable,partno,suppname as supplier,subcode,isgc,amt2,disc2,famt,disc3,amt4,disc4,amt5,disc5,amt6,disc6
        FROM itemdlock AS id  LEFT JOIN item ON item.itemid=id.itemid 
        left join stockgrp_masterfile as stockgrp on stockgrp.stockgrp_id = item.groupid
        left join part_masterfile as pmaster on pmaster.part_id = item.part
        left join frontend_ebrands as brand on brand.brandid = item.brand
        left join item_class as itemclass on itemclass.cl_id = item.class
        left join itemcategory as cat on cat.line = item.category
        WHERE item.barcode<>'' and item.ispositem=1 and id.dlock<=?
      ";
      $item = $this->coreFunctions->opentable($sql, [$starttime]);
      $csv = '';
      $csv = $this->createcsv($item, 1);

      $sql = "select uom.itemid, uom.uom, uom.factor, uom.isinactive, uom.isdefault2 as isdefault, uom.amt FROM itemdlock AS id  LEFT JOIN uom on uom.itemid=id.itemid where uom.itemid is not null";
      $uom = $this->coreFunctions->opentable($sql);
      $csvuom = '';
      $csvuom = $this->createcsv($uom, 1);

      $qry = "select clientid,client from client where isbranch=1 and isinactive=0";
      $branches = $this->coreFunctions->opentable($qry);
      foreach ($branches as $branch => $value) {
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        foreach ($stations as $station => $value2) {
          $result = $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'item');
          $result = $this->ftpcreatefile($csvuom, $value->client, $value2->station, 'download', 'uom');
          $this->ftpcreatefolder($value->client, $value2->station, 'upload');
        }
      }

      if (boolval($result)) {
        $this->coreFunctions->execqry('delete from itemdlock where dlock<=?', 'delete', [$starttime]);
      }
    }
  } //end function

  public function clientlist($params)
  {
    $starttime = $this->coreFunctions->datareader('select dlock as value from clientdlock order by dlock desc limit 1');
    if ($starttime != '') {
      $sql = "
        SELECT client.clientid,client.client,client.clientname,client.email,REPLACE(client.addr,'\r\n','') as addr,client.tel,client.tel2,client.bday,client.fax,client.contact,client.terms,client.rem,client.tin,client.isagent,client.iscustomer,client.issupplier,client.iswarehouse,client.isbranch,client.area,client.birthday,client.isinactive,
        client.isallitem,client.start,client.agent,client.issynced,client.ismain,client.uv_ispicker as ispicker, client.uv_ischecker as ischecker, client.password as pword, client.class
        FROM clientdlock left join client on client.clientid=clientdlock.clientid where clientdlock.clientid<>0 and client.issynced=1
      ";
      $client = $this->coreFunctions->opentable($sql);

      if (isset($params['pos'])) {
        if ($params['pos']) {
          foreach ($client as $key => $value) {
            switch ($client[$key]->class) {
              case "R":
                $client[$key]->class = "A";
                break;
              case "W":
                $client[$key]->class = "B";
                break;
              case "A":
                $client[$key]->class = "C";
                break;
              case "B":
                $client[$key]->class = "D";
                break;
              case "C":
                $client[$key]->class = "E";
                break;
              case "D":
                $client[$key]->class = "F";
                break;
            }
          }
        }
      }

      $csv = '';
      $csv = $this->createcsv($client, 1);
      $qry = "select clientid,client from client where isbranch=1 and isinactive=0";
      $branches = $this->coreFunctions->opentable($qry);
      foreach ($branches as $branch => $value) {
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        foreach ($stations as $station => $value2) {
          $result = $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'client');
          $this->ftpcreatefolder($value->client, $value2->station, 'upload');
        }
      }
      if (boolval($result)) {
        $this->coreFunctions->execqry('delete from clientdlock where dlock<=?', 'delete', [$starttime]);
      }
    }
  } //end function

  public function pospaymentsetup($dlock)
  {
    $sql = "select line, cardtype, dlock from cardtype" . ($dlock != '' ? " where ifnull(dlock,now())>'" . $dlock . "'" : "");
    $cardtype = $this->coreFunctions->opentable($sql);
    $this->coreFunctions->sbclogger("Creating cardtype (" . count($cardtype) . ") file..." . $dlock, 'DLOCK');
    $csv3 = '';
    $csv3 = $this->createcsv($cardtype, 1);

    $sql = "select line, type, dlock, inactive from checktypes" . ($dlock != '' ? " where ifnull(dlock,now())>'" . $dlock . "'" : "");
    $checktypes = $this->coreFunctions->opentable($sql);
    $this->coreFunctions->sbclogger("Creating checktypes (" . count($checktypes) . ")  file..." . $dlock, 'DLOCK');
    $csv4 = '';
    $csv4 = $this->createcsv($checktypes, 1);

    $branches = $this->getactivebranch();
    foreach ($branches as $branch => $value) {
      $sql = "select line, clientid, terminalid, bank, isinactive from branchbank where clientid=" . $value->clientid . ($dlock != '' ? " and ifnull(dlock,now())>'" . $dlock . "'" : "");
      $data = $this->coreFunctions->opentable($sql);
      $this->coreFunctions->sbclogger("Creating branchbank (" . count($data) . ")  file " . $value->client . "..." . $dlock, 'DLOCK');
      $csv = '';
      $csv = $this->createcsv($data, 1);

      $sql = "select bc.line, bb.terminalid, bc.rate, bc.type, bc.dlock, bc.inactive from bankcharges as bc left join branchbank as bb on bb.terminalid=bc.terminalid 
          where bb.clientid=" . $value->clientid . ($dlock != '' ? " and ifnull(bc.dlock,now())>'" . $dlock . "'" : "");;
      $bankcharges = $this->coreFunctions->opentable($sql);
      $this->coreFunctions->sbclogger("Creating bankcharges (" . count($bankcharges) . ")  file " . $value->client . "..." . $dlock, 'DLOCK');
      $csv2 = '';
      $csv2 = $this->createcsv($bankcharges, 1);

      $qry = "select station from branchstation where clientid =? and isinactive=0";
      $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);

      foreach ($stations as $station => $value2) {
        //$this->coreFunctions->LogConsole("Creating bankcharges file..." . $value->client . ' ' . $value2->station);
        $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'branchbank');
        $this->ftpcreatefile($csv2, $value->client, $value2->station, 'download', 'bankcharges');
        $this->ftpcreatefile($csv3, $value->client, $value2->station, 'download', 'cardtype');
        $this->ftpcreatefile($csv4, $value->client, $value2->station, 'download', 'checktypes');
      }
    }
  }

  public function getpromotion()
  {
    $branches = $this->getactivebranch();
    foreach ($branches as $branch => $value) {
      $this->coreFunctions->LogConsole("Creating pricescheme branch " . $value->client);

      $qry = "select num.trno, num.docno from transnum as num left join hpahead as h on h.trno=num.trno 
      where num.doc='PA' and num.postdate is not null and num.isok=0 and (h.branchid=? or h.isall=1)";
      $transnums = $this->coreFunctions->opentable($qry, [$value->clientid]);

      foreach ($transnums as $transnum => $transval) {
        $this->coreFunctions->LogConsole("Checking pricescheme " . $transval->docno);

        $qry = " select num.trno, num.docno, h.dateid, h.due, h.rem, h.yourref, h.ourref, s.line, s.itemid, item.barcode, item.itemname, s.uom, s.isqty, s.iss,s.isamt, s.amt, s.ext, s.disc
            from transnum as num left join hpahead as h on h.trno=num.trno left join hpastock as s on s.trno=h.trno left join item on item.itemid=s.itemid
            where num.doc='PA' and num.postdate is not null and num.isok=0 and num.trno=?";
        $promos = $this->coreFunctions->opentable($qry, [$transval->trno]);

        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        $sucess = true;
        try {
          foreach ($stations as $station => $value2) {
            $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " " . $value2->station);
            $csv = $this->createcsv($promos, 1);
            $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'pricescheme');
          }
        } catch (Exception $e) {
          $sucess = false;
        }
        if ($sucess) {
          $this->coreFunctions->sbcupdate("transnum", ["isok" => 1], ["trno" => $transval->trno]);
          $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
          $this->coreFunctions->sbclogger("Pricescheme file " . $transval->docno . " created.", 'DLOCK');
        }
      }
    }
  }

  public function getpromotion_homeworks()
  {
    $branches = $this->getactivebranch();


    foreach ($branches as $branch => $value) {
      $this->coreFunctions->sbclogger("Creating pricescheme branch " . $value->client, "DLOCK");

      $qry = "select num.trno, num.docno from transnum as num left join hpahead as h on h.trno=num.trno left join ppbranch as pp on pp.trno=h.trno 
              where num.doc='PA' and num.postdate is not null and pp.isok=0 and pp.clientid=?";
      $transnums = $this->coreFunctions->opentable($qry, [$value->clientid]);

      foreach ($transnums as $transnum => $transval) {
        $this->coreFunctions->sbclogger("Checking pricescheme " . $transval->docno, "DLOCK");

        $qry = " select num.trno, num.doc, num.docno, h.dateid, h.due, h.rem, h.yourref, h.ourref, s.line, s.itemid, item.barcode, item.itemname, s.uom, s.isqty, s.iss,s.isamt, s.amt, s.ext, s.disc
            from transnum as num left join hpahead as h on h.trno=num.trno left join hpastock as s on s.trno=h.trno left join item on item.itemid=s.itemid
            where num.doc='PA' and num.postdate is not null and num.trno=?";
        $promos = $this->coreFunctions->opentable($qry, [$transval->trno]);

        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        $sucess = true;
        try {
          foreach ($stations as $station => $value2) {
            $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " " . $value2->station, "DLOCK");
            $csv = $this->createcsv($promos, 1);
            $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'pricescheme');
            $this->coreFunctions->sbclogger("Promo per item file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
          }
        } catch (Exception $e) {
          $sucess = false;
        }
        if ($sucess) {
          $this->coreFunctions->sbcupdate("ppbranch", ["isok" => 1], ["trno" => $transval->trno, "clientid" => $value->clientid]);
          $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
          $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
        }
      }


      //void promotions
      $qry2 = "select head.trno, head.doc, head.docno, head.voiddate, head.voidby, head.due from transnum as num left join hpahead as head on head.trno=num.trno left join ppbranch as pp on pp.trno=head.trno
              where num.doc='PA' and num.postdate is not null and pp.isokvoid=0 and head.voiddate is not null and pp.clientid=?";
      $voidtransnums = $this->coreFunctions->opentable($qry2, [$value->clientid]);

      foreach ($voidtransnums as $transnum => $transval) {
        $voidtrans = [];
        array_push($voidtrans, $transval);

        foreach ($branches as $branch => $value) {
          $qry = "select station from branchstation where clientid =? and isinactive=0";
          $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
          $sucess = true;
          try {
            foreach ($stations as $station => $value2) {
              $this->coreFunctions->LogConsole("Creating void price schem file " . $transval->docno . " " . $value2->station);
              $csv = $this->createcsv($voidtrans, 1);
              $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'voidpricescheme');
              $this->coreFunctions->sbclogger("Price Scheme file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
            }
          } catch (Exception $e) {
            $sucess = false;
          }
        }

        if ($sucess) {
          $this->coreFunctions->sbcupdate("ppbranch", ["isokvoid" => 1], ["trno" => $transval->trno, "clientid" => $value->clientid]);
          $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
          $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
        }
      }
    }
  }

  public function getpromotion_homeworks_all()
  {
    $branches = $this->getactivebranch();

    $qry = "select num.trno, num.docno from transnum as num left join hpahead as h on h.trno=num.trno left join ppbranch as pp on pp.trno=h.trno 
              where num.doc='PA' and num.postdate is not null and num.isok=0 and h.isall=1";
    $transnums = $this->coreFunctions->opentable($qry);

    foreach ($transnums as $transnum => $transval) {
      $qry = " select num.trno, num.doc, num.docno, h.dateid, h.due, h.rem, h.yourref, h.ourref, s.line, s.itemid, item.barcode, item.itemname, s.uom, s.isqty, s.iss,s.isamt, s.amt, s.ext, s.disc
            from transnum as num left join hpahead as h on h.trno=num.trno left join hpastock as s on s.trno=h.trno left join item on item.itemid=s.itemid
            where num.doc='PA' and num.postdate is not null and num.trno=?";
      $promos = $this->coreFunctions->opentable($qry, [$transval->trno]);

      $sucess = true;

      if (!empty($promos)) {
        foreach ($branches as $branch => $value) {
          $qry = "select station from branchstation where clientid =? and isinactive=0";
          $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
          try {
            foreach ($stations as $station => $value2) {
              $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " " . $value2->station);
              $csv = $this->createcsv($promos, 1);
              $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'pricescheme');
              $this->coreFunctions->sbclogger("Promo per item file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
            }
          } catch (Exception $e) {
            $sucess = false;
          }
        }
      }

      if ($sucess) {
        $this->coreFunctions->sbcupdate("transnum", ["isok" => 1], ["trno" => $transval->trno]);
        $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
        $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
      }
    }


    //void promotions
    $qry2 = "select head.trno, head.doc, head.docno, head.voiddate, head.voidby, head.due from transnum as num left join hpahead as head on head.trno=num.trno 
              where num.doc='PA' and num.postdate is not null and num.isokvoid=0 and head.voiddate is not null and head.isall=1";
    $voidtransnums = $this->coreFunctions->opentable($qry2);

    foreach ($voidtransnums as $transnum => $transval) {
      $voidtrans = [];
      array_push($voidtrans, $transval);

      foreach ($branches as $branch => $value) {
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        $sucess = true;
        try {
          foreach ($stations as $station => $value2) {
            $this->coreFunctions->LogConsole("Creating void pricescheme file " . $transval->docno . " " . $value2->station);
            $csv = $this->createcsv($voidtrans, 1);
            $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'voidpricescheme');
            $this->coreFunctions->sbclogger("Pricescheme file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
          }
        } catch (Exception $e) {
          $sucess = false;
        }
      }

      if ($sucess) {
        $this->coreFunctions->sbcupdate("transnum", ["isokvoid" => 1], ["trno" => $transval->trno]);
        $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
        $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
      }
    }
  }


  public function getpromotion_pp_homeworks()
  {
    $branches = $this->getactivebranch();
    foreach ($branches as $branch => $value) {
      $this->coreFunctions->LogConsole("Creating promo per item branch " . $value->client);

      $qry = "select num.trno, num.docno from transnum as num left join hpphead as h on h.trno=num.trno left join ppbranch as pp on pp.trno=h.trno 
              where num.doc='PP' and num.postdate is not null and num.isok=0 and pp.clientid=?";
      $transnums = $this->coreFunctions->opentable($qry, [$value->clientid]);

      foreach ($transnums as $transnum => $transval) {
        $this->coreFunctions->LogConsole("Checking promo per item " . $transval->docno);

        $qry = "select num.trno, num.doc, num.docno, h.dateid, h.due, h.rem, h.yourref, h.ourref, h.isqty, h.isamt, h.isbuy1, s.line, s.itemid, item.barcode, s.pstart, s.pend
            from transnum as num left join hpphead as h on h.trno=num.trno left join hppstock as s on s.trno=h.trno left join item on item.itemid=s.itemid
            where num.doc='PP' and num.postdate is not null and num.trno=?";
        $promos = $this->coreFunctions->opentable($qry, [$transval->trno]);

        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        $sucess = true;
        try {
          foreach ($stations as $station => $value2) {
            $this->coreFunctions->LogConsole("Creating promo per item file " . $transval->docno . " " . $value2->station);
            $csv = $this->createcsv($promos, 1);
            $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'promoperitem');
            $this->coreFunctions->sbclogger("Promo per item file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
          }
        } catch (Exception $e) {
          $sucess = false;
        }
        if ($sucess) {
          $this->coreFunctions->sbcupdate("ppbranch", ["isok" => 1], ["trno" => $transval->trno, "clientid" => $value->clientid]);
          $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
          $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
        }
      }


      //void promotions
      $qry2 = "select head.trno, head.doc, head.docno, head.voiddate, head.voidby, head.due from transnum as num left join hpphead as head on head.trno=num.trno left join ppbranch as pp on pp.trno=head.trno
              where num.doc='PP' and num.postdate is not null and num.isokvoid=0 and head.voiddate is not null and pp.clientid=?";
      $voidtransnums = $this->coreFunctions->opentable($qry2, [$value->clientid]);

      foreach ($voidtransnums as $transnum => $transval) {
        $voidtrans = [];
        array_push($voidtrans, $transval);

        foreach ($branches as $branch => $value) {
          $qry = "select station from branchstation where clientid =? and isinactive=0";
          $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
          $sucess = true;
          try {
            foreach ($stations as $station => $value2) {
              $this->coreFunctions->LogConsole("Creating void promo per item file " . $transval->docno . " " . $value2->station);
              $csv = $this->createcsv($voidtrans, 1);
              $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'voidpromoperitem');
              $this->coreFunctions->sbclogger("Promo per item file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
            }
          } catch (Exception $e) {
            $sucess = false;
          }
        }

        if ($sucess) {
          $this->coreFunctions->sbcupdate("ppbranch", ["isokvoid" => 1], ["trno" => $transval->trno, "clientid" => $value->clientid]);
          $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
          $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
        }
      }
    }
  }

  public function getpromotion_pp_homeworks_all()
  {
    $branches = $this->getactivebranch();

    $qry = "select num.trno, num.docno from transnum as num left join hpphead as h on h.trno=num.trno left join ppbranch as pp on pp.trno=h.trno 
              where num.doc='PP' and num.postdate is not null and num.isok=0 and h.isall=1";
    $transnums = $this->coreFunctions->opentable($qry);

    foreach ($transnums as $transnum => $transval) {

      $qry = "select num.trno, num.doc, num.docno, h.dateid, h.due, h.rem, h.yourref, h.ourref, h.isqty, h.isamt, h.isbuy1, s.line, s.itemid, item.barcode, s.pstart, s.pend
            from transnum as num left join hpphead as h on h.trno=num.trno left join hppstock as s on s.trno=h.trno left join item on item.itemid=s.itemid
            where num.doc='PP' and num.postdate is not null and num.trno=?";
      $promos = $this->coreFunctions->opentable($qry, [$transval->trno]);

      foreach ($branches as $branch => $value) {
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        $sucess = true;
        try {
          foreach ($stations as $station => $value2) {
            $this->coreFunctions->LogConsole("Creating promo per item file " . $transval->docno . " " . $value2->station);
            $csv = $this->createcsv($promos, 1);
            $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'promoperitem');
            $this->coreFunctions->sbclogger("Promo per item file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
          }
        } catch (Exception $e) {
          $sucess = false;
        }
      }

      if ($sucess) {
        $this->coreFunctions->sbcupdate("transnum", ["isok" => 1], ["trno" => $transval->trno]);
        $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
        $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
      }
    }


    //void promotions
    $qry2 = "select head.trno, head.doc, head.docno, head.voiddate, head.voidby, head.due from transnum as num left join hpphead as head on head.trno=num.trno left join ppbranch as pp on pp.trno=head.trno
              where num.doc='PP' and num.postdate is not null and num.isokvoid=0 and head.voiddate is not null and head.isall=1";
    $voidtransnums = $this->coreFunctions->opentable($qry2);

    foreach ($voidtransnums as $transnum => $transval) {
      $voidtrans = [];
      array_push($voidtrans, $transval);

      foreach ($branches as $branch => $value) {
        $qry = "select station from branchstation where clientid =? and isinactive=0";
        $stations = $this->coreFunctions->opentable($qry, [$value->clientid]);
        $sucess = true;
        try {
          foreach ($stations as $station => $value2) {
            $this->coreFunctions->LogConsole("Creating void promo per item file " . $transval->docno . " " . $value2->station);
            $csv = $this->createcsv($voidtrans, 1);
            $this->ftpcreatefile($csv, $value->client, $value2->station, 'download', 'voidpromoperitem');
            $this->coreFunctions->sbclogger("Promo per item file " . $transval->docno . " created. (" . $value2->station . ")", 'DLOCK');
          }
        } catch (Exception $e) {
          $sucess = false;
        }
      }

      if ($sucess) {
        $this->coreFunctions->sbcupdate("transnum", ["isokvoid" => 1], ["trno" => $transval->trno]);
        $this->coreFunctions->LogConsole("Creating pricescheme file " . $transval->docno . " is finished");
        $this->coreFunctions->sbclogger("Creating pricescheme file " . $transval->docno . " is finished", 'DLOCK');
      }
    }
  }

  public function getactivebranch()
  {
    $qry = "select clientid,client from client where isbranch=1 and isinactive=0";
    return  $this->coreFunctions->opentable($qry);
  }


  public function ftpextractfiles()
  {
    $branches = $this->coreFunctions->opentable("select clientid,client from client where isbranch=1 and isinactive=0");
    foreach ($branches as $branch => $b) {

      $stations = $this->coreFunctions->opentable("select station from branchstation where clientid=? and isinactive=0", [$b->clientid]);
      foreach ($stations as $station => $s) {
        $this->coreFunctions->LogConsole($s->station);
        $this->ftpcheckfiletoextract($b->client, $s->station, "upload");
      }
    }
  }

  public function extracttransactions($params)
  {
    $data = $this->coreFunctions->opentable("select branch, station, trno, isextracted from head where isok2=0 and isextracted=1");
    foreach ($data as $key => $value) {
      // $this->coreFunctions->sbclogger("pending extraction " . $value->station . ' TrNo.:' . $value->trno);

      switch ($params['companyid']) {
        case 56:
          $result = $this->generatelatrans_homeworks($value->branch, $value->station, $value->trno, $params);
          break;
        default:
          $result = $this->generatelatrans($value->branch, $value->station, $value->trno);
          break;
      }
    }
  }

  public function ftpcheckfiletoextract($branch, $station, $folder)
  {
    $status = false;
    try {
      date_default_timezone_set('Asia/Singapore');

      $this->coreFunctions->LogConsole('Checking directory ' . $branch . '/' . $station . '/' . $folder);
      $this->coreFunctions->sbclogger('Checking directory ' . $branch . '/' . $station . '/' . $folder, 'DLOCK');
      foreach (Storage::disk('ftp')->files($branch . '/' . $station . '/' . $folder) as $filename) {
        $this->coreFunctions->LogConsole('Found ' . $filename);

        $status = false;
        if (Str::substr($filename, -3) === 'sbc') {
          $arrline = $this->ftpfilecheckendfile($filename);
          if (is_array($arrline)) {

            $a = explode('/', $filename);
            $b =  explode('~', $a[3]);
            //doc~station~docno~trno    

            $this->coreFunctions->LogConsole('Analyzing ' . $filename);

            $this->coreFunctions->sbclogger('Extracting file ' . $filename, 'DLOCK');
            switch ($b[0]) {
              case 'BP':
                $c = explode('.', $b[3]);
                $trno = $c[0];
                $isok = $this->coreFunctions->datareader('select ifnull(isok2,0) as value from head where trno=? and station=? and docno=?', [$trno, $b[1], $b[2]], '', true);
                if ($isok != 0) {
                  $this->coreFunctions->sbclogger("LA extracted " . $filename);
                  $this->ftpdeletefile($branch . '/' . $station . '/error/' . Str::substr($a[2], 0, Str::length($a[2]) - 3) . 'err');
                  Storage::disk('ftp')->copy($filename, $branch . '/' . $station . '/error/' . Str::substr($a[2], 0, Str::length($a[2]) - 3) . 'err');
                  // Storage::disk('ftp')->delete($filename);
                  $this->ftpdeletefile($filename);
                } else {
                  if ($isok == 0) {
                    $this->coreFunctions->execqry('delete from stock where trno=? and station=?', 'delete', [$trno, $b[1]]);
                    $this->coreFunctions->execqry('delete from head where trno=? and station=? and docno=?', 'delete', [$trno, $b[1], $b[2]]);
                  } else {
                    $this->coreFunctions->LogConsole("Already extracted.");
                    $this->coreFunctions->sbclogger("Already extracted." . $filename);
                  }
                  if ($this->bpextraction($arrline)) {
                    $this->coreFunctions->LogConsole('Extracted ' . $filename);
                    $this->coreFunctions->execqry('update head set isextracted=1 where trno=? and station=? and docno=?', 'delete', [$trno, $b[1], $b[2]]);

                    try {
                      // Storage::disk('ftp')->delete($filename);
                      $this->ftpdeletefile($filename);
                      $status = true;
                      $this->coreFunctions->LogConsole("File deleted.");
                    } catch (Exception $ex) {
                      $status = false;
                      $this->coreFunctions->sbclogger("deleting failed " . $filename . ' ' . substr($ex, 0, 1000));
                    }

                    //$result = $this->generatelatrans($branch, $b[1], $trno);

                  }
                }
                break;

              case 'READING':
                $c = explode('.', $b[2]);
                $date = $c[0];
                $isok = $this->coreFunctions->datareader('select ifnull(isok2,0) as value from journal where date(dateid)=? and station=?', [$date, $b[1]], '', true);
                $this->coreFunctions->LogConsole($isok);
                if ($isok == 0) {
                  $this->coreFunctions->execqry('delete from journal where date(dateid)=? and station=?', 'delete', [$date, $b[1]]);
                }
                if ($this->bpextraction($arrline)) {
                  $this->coreFunctions->LogConsole('extracted ' . $filename);
                  $this->coreFunctions->sbclogger('Extracting file ' . $filename, 'DLOCK');

                  $current_timestamp = date('Y-m-d H:i:s');

                  $amt = $this->coreFunctions->datareader('select amt as value from journal where date(dateid)=? and station=?', [$date, $b[1]], '', true);
                  $retamt = $this->coreFunctions->datareader('select returnamt+voidamt as value from journal where date(dateid)=? and station=?', [$date, $b[1]], '', true);
                  if ($amt == 0 && $retamt == 0) {
                    $hamt = $this->coreFunctions->datareader('select sum(amt) as value from head where date(dateid)=? and station=?', [$date, $b[1]], '', true);
                    if ($hamt == 0) {
                      $this->coreFunctions->execqry("update journal set isok=1,isok2=1,extractdate='" . $current_timestamp . "' where date(dateid)=? and station=?", 'update', [$date, $b[1]]);
                    }
                  } else {
                    $this->coreFunctions->execqry("update journal set isok=1,extractdate='" . $current_timestamp . "' where date(dateid)=? and station=?", 'update', [$date, $b[1]]);
                  }
                  Storage::disk('ftp')->delete($filename);
                  $status = true;
                } else {
                  $this->coreFunctions->LogConsole('Failed to extract ' . $filename);
                  $status = false;
                }
                break;

              case 'LAYAWAY':
                $c = explode('.', $b[3]);
                $trno = $c[0];
                $isok = $this->coreFunctions->datareader('select ifnull(isok2,0) as value from layaway where line=? and station=? and docno=?', [$trno, $b[1], $b[2]], '', true);
                if ($isok != 0) {
                  $this->ftpdeletefile($branch . '/' . $station . '/error/' . Str::substr($a[2], 0, Str::length($a[2]) - 3) . 'err');
                  Storage::disk('ftp')->copy($filename, $branch . '/' . $station . '/error/' . Str::substr($a[2], 0, Str::length($a[2]) - 3) . 'err');
                  $this->ftpdeletefile($filename);
                } else {
                  if ($isok == 0) {
                    $this->coreFunctions->execqry('delete from layaway where line=? and station=? and docno=?', 'delete', [$trno, $b[1], $b[2]]);
                  } else {
                    $this->coreFunctions->LogConsole("Already extracted.");
                    $this->coreFunctions->sbclogger("Already extracted." . $filename);
                  }
                  if ($this->bpextraction($arrline)) {
                    $this->coreFunctions->LogConsole('Extracted ' . $filename);

                    $current_timestamp = date('Y-m-d H:i:s');
                    $this->coreFunctions->execqry("update layaway set isextracted=1,extractdate='" . $current_timestamp . "' where line=? and station=? and docno=?", 'delete', [$trno, $b[1], $b[2]]);

                    try {
                      $this->ftpdeletefile($filename);
                      $status = true;
                      $this->coreFunctions->LogConsole("File deleted.");
                    } catch (Exception $ex) {
                      $status = false;
                      $this->coreFunctions->sbclogger("deleting failed " . $filename . ' ' . substr($ex, 0, 1000));
                    }
                  }
                }
                break;
            }
          }
        }
      }
    } catch (Exception $ex) {
      $status = false;
      $this->coreFunctions->sbclogger('ftpcheckfiletoextract - ' . substr($ex, 0, 1000));
    }

    return ['status' => $status];
  }

  public function createtranscsv($data)
  {
    $qlist = $finallist = '';
    foreach ($data as $d) {
      $qlist .= $d . "\n";
    }
    if ($qlist != '') $finallist = $qlist . 'ENDFILE';
    return $finallist;
  }

  public function createcsv($data, $isfieldname)
  {
    //========================================
    // Important Comments, do not remove
    // October 5, 2025
    // Files can have different line endings: (Deepseek reference)
    // \n (Linux/macOS)
    // \r\n (Windows)
    // \r (Old Mac)
    // PHP_EOL is the line ending for the current OS the file might have been created.
    // Always use \n for file storage
    //========================================
    try {
      $itemlist = '';
      $itemline = '';
      $fieldname = '';
      $tmpfield = '';
      $finallist = '';
      foreach ($data as $row => $value) {
        //'~'
        $itemline = '';
        foreach ($value as $row2 => $value2) {
          if ($fieldname == '' && $isfieldname == 1) {
            if ($tmpfield == '') {
              $tmpfield = $row2;
            } else {
              $tmpfield = $tmpfield . '~' . $row2;
            }
          }
          if ($itemline == '') {
            $itemline = $this->removeNewlines(trim($value2));
          } else {
            $itemline = $itemline . '~' . $this->removeNewlines(trim($value2));
          }
        }
        $itemlist = $itemlist . $itemline . "\n"; //PHP_EOL: previously used
        $fieldname = $tmpfield;
      }
      if ($itemlist != '') {
        if ($isfieldname == 1) {
          $finallist = $fieldname . "\n" . $itemlist . 'ENDFILE'; ////PHP_EOL: previously used
        } else {
          $finallist = $itemlist . 'ENDFILE';
        }
      }
      return $finallist;
    } catch (Exception $ex) {
      throw new \Exception('Exception message (createcsv) => ' . $ex);
    }
  }

  function removeNewlines($string, $replaceWith = ' ')
  {
    //Æ
    $string1 = preg_replace('/[\r\n]+/', $replaceWith, $string);
    $newstring = str_replace("~", "", $string1);

    return $newstring;
  }

  public function ftpcreatefiletrans($csv, $branch, $station, $folder, $doc, $docno, $trno)
  {
    date_default_timezone_set('Asia/Singapore');
    $current_timestamp = date('Y-m-dH.i.s');
    if ($csv != '') {
      $this->ftpwritefile('/' . $branch . '/' . $station . '/' . $folder . '/trans~' . $doc . '~' . $docno . '~' . $trno . '~' . $current_timestamp, $csv);
    }
    return 'true';
  }

  public function ftpcreatefile($csv, $branch, $station, $folder, $type, $iscurtime = 1, $batch = '')
  {
    date_default_timezone_set('Asia/Singapore');
    $current_timestamp = date('Y-m-dH.i.s');
    if ($csv != '') {
      if ($iscurtime == 1) {
        $this->ftpwritefile('/' . $branch . '/' . $station . '/' . $folder . '/' . $type . '~' . $current_timestamp . $batch, $csv);
      } else {
        $this->ftpdeletefile('/' . $branch . '/' . $station . '/' . $folder . '/' . $type . '.sbc');
        $this->ftpwritefile('/' . $branch . '/' . $station . '/' . $folder . '/' . $type, $csv);
      }
    }
    return 'true';
  }


  public function ftpcreatefile2($type, $csv, $iscurtime = 1)
  {
    try {
      date_default_timezone_set('Asia/Singapore');
      $current_timestamp = date('Y-m-dH.i.s');
      if ($csv != '') {
        if ($iscurtime == 1) {
          $this->ftpwritefile('/download/' . $type . '~' . $current_timestamp, $csv);
        } else {
          $this->ftpdeletefile('/download/' . $type . '.sbc');
          $this->ftpwritefile('/download/' . $type, $csv);
        }
      }
      return 'true';
    } catch (Exception $ex) {
      throw new \Exception('Exception message (ftpcreatefile2) ' . $type . ' => ' . $ex);
    }
  }


  public function ftpfilecheckendfile($path, $mirror = false)
  {
    try {
      $arrline = $this->ftpgetarrayfromfile($path, $mirror);
      if (!empty($arrline)) {
        if (count($arrline) == 1) {
          $this->coreFunctions->sbclogger('INVALID TXTFILE: ' . $path);
          return '';
        }
        for ($i = count($arrline) - 1; $i <= count($arrline) - 1; $i--) {
          if (trim($arrline[$i]) == 'ENDFILE') {
            return $arrline;
          }
        }
      }
      return false;
    } catch (Exception $ex) {
      throw new \Exception('Exception message (ftpfilecheckendfile) => ' . $ex);
    }
  }

  public function ftpgetarrayfromfile($path, $mirror)
  {
    //========================================
    // Important Comments, do not remove
    // October 5, 2025
    // Files can have different line endings: (Deepseek reference)
    // \n (Linux/macOS)
    // \r\n (Windows)
    // \r (Old Mac)
    // PHP_EOL is the line ending for the current OS the file might have been created.
    // Always use \n for file storage
    //========================================
    if ($mirror) {
      $file = Storage::disk('ftpmirror')->get($path);
    } else {
      $file = Storage::disk('ftp')->get($path);
    }
    $arrline = explode("\n", $file); //PHP_EOL: previously used
    return $arrline;
  }


  private function bpextraction($arr)
  {
    foreach ($arr as $row) {
      if (trim($row) != 'ENDFILE' && trim($row) != '') {
        if ($this->coreFunctions->execqry($row) == 0) {
          return false;
        }
      }
    }
    return true;
  }

  public function getdataforextraction($pbranch, $pstation)
  {
    $status = true;
    $msg = 'Success';

    try {
      $heads = $this->coreFunctions->opentable("select trno, docno, branch, station from head where branch=? and station=? and isok2=0 and isextracted=1", [$pbranch, $pstation]);
      foreach ($heads as $head => $h) {
        $this->generatelatrans($h->branch, $h->station, $h->trno);
      }
    } catch (Exception $e) {
      $status = false;
      $msg = $e;
      $this->coreFunctions->sbclogger('getdataforextraction - ' . substr($e, 0, 1000));
    }

    return ['status' => $status, 'msg' => $msg];
  }


  public function generatelatrans($pbranch, $pstation, $ptrno)
  {
    $status = false;
    $msg = 'Failed to extract head';

    $sidocno = '';
    $trno = 0;
    $arr_line = [];
    $pickerid = 0;
    $checkerid = 0;

    $isExpiry = true;

    try {

      $data = $this->coreFunctions->opentable("select docno, clientid, clientname, date(dateid) as dateid, wh, transtype, amt, address, rem, cur, dateid, picker, checker
      from head where branch=? and station=? and trno=? and isok2=0", [$pbranch, $pstation, $ptrno]);
      if ($data) {

        $doc = 'SJ';
        $pref = 'SJS';
        $path = 'App\Http\Classes\modules\sales\sj';
        $referencemodule = ''; //MOBILE APP

        if ($data[0]->amt < 0) {
          $doc = 'CM';
          $pref = 'SRS';
          $path = 'App\Http\Classes\modules\sales\cm';
        }

        $branchid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$pbranch]);
        $client = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$data[0]->clientid]);
        $projectid = $this->coreFunctions->getfieldvalue("branchstation", "projectid", "station=?", [$pstation], '', true);

        $pickerid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data[0]->picker], '', true);
        $checkerid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data[0]->checker], '', true);

        $salestype = 'REGULAR';
        switch ($data[0]->transtype) {
          case 'S':
          case 'ST':
            $salestype = 'SENIOR';
            break;
          case 'P':
          case 'PT':
            $salestype = 'PWD';
            break;
          case 'D':
          case 'DT':
            $salestype = 'DIPLOMAT';
            break;
        }

        $data[0]->dateid = date('Y-m-d', strtotime($data[0]->dateid));

        $exist = $this->coreFunctions->datareader("select c.trno as value from lahead as h left join cntnum as c on c.trno=h.trno 
        where c.doc=? and h.client=? and h.dateid=? and h.branch=? and c.station=? and h.salestype=?", [$doc, $client, $data[0]->dateid, $branchid, $pstation, $salestype]);

        if ($client == '') {
          $msg = 'generatelatrans - Missing clientid ' . $data[0]->clientid;
          $this->coreFunctions->sbclogger($msg);
          return ['status' => $status, 'msg' => $msg];
        }

        if ($exist) {
          $trno = $exist;
          goto insertstockhere;
        } else {
          $config = [];
          $config['params']['center'] = '001';
          $config['params']['user'] = 'AUTO';
          $config['params']['station'] = $pstation;
          $config['params']['doc'] = $doc;

          $trno = $this->othersClass->generatecntnum($config, app($path)->tablenum, $doc, $pref, $this->companysetup->documentlength, 0, '', true);

          if ($trno != -1) {
            $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$trno]);
            $contra = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AR1']);

            $head = [
              'trno' => $trno,
              'doc' => $doc,
              'docno' => $docno,
              'client' => $client,
              'clientname' => $data[0]->clientname,
              'address' => $data[0]->address,

              'cur' => $data[0]->cur,
              'forex' => 1,
              'dateid' => $data[0]->dateid,
              'due' => $data[0]->dateid,
              'terms' => '',
              'wh' => $data[0]->wh,
              'branch' => $branchid,
              'contra' => $contra,
              'vattype' => 'NON-VATABLE',
              'projectid' => $projectid,
              'salestype' => $salestype
            ];

            $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $head);
            if ($inserthead) {
              $this->logger->sbcwritelog2($trno, 'EXTRACTION', 'CREATE', $docno . ' - EXTRACTED ' . $referencemodule, app($path)->tablelogs);

              insertstockhere:
              $stockdata = $this->coreFunctions->opentable("
              select itemid, itemname, uom, wh, disc, rem, rrcost, cost, sum(rrqty) as rrqty, abs(sum(qty)) as qty, isamt, amt, sum(isqty) as isqty, abs(sum(iss)) as iss, sum(ext) as ext, ref,
              sum(nvat) as nvat, sum(vatamt) as vatamt, sum(vatex) as vatex, sum(sramt) as sramt, sum(pwdamt) as pwdamt, sum(lessvat) as lessvat, sum(discamt) as discamt,
              sum(vipdisc) as vipdisc, sum(empdisc) as empdisc, sum(oddisc) as oddisc, sum(smacdisc) as smacdisc,
              isdiplomat, issenior2, qa, iscomp, iscomponent, agentid
              from stock where station=? and trno=? group by  itemid, itemname, uom, wh, disc, rem, rrcost, cost, isamt, amt, ref, isdiplomat, issenior2, qa, iscomp, iscomponent, agentid", [$pstation, $ptrno]);

              $arr_line = [];
              if ($stockdata) {
                $cur = $this->coreFunctions->getfieldvalue(app($path)->head, 'cur', 'trno=?', [$trno]);

                //2024.04.29 - FMM - checking if items exist
                checklastockexist:
                $lastockexist = $this->coreFunctions->opentable("select s.trno, s.line from lastock as s where s.trno=" . $trno . " and s.ref='" . $data[0]->docno . "'");
                if (!empty($lastockexist)) {
                  $this->coreFunctions->sbclogger('deleted existing ref ' . $data[0]->docno);

                  foreach ($lastockexist as $key => $val) {
                    $this->coreFunctions->execqry("delete from lastock where trno=" . $trno . " and line=" . $val->line);
                    $this->coreFunctions->execqry("delete from costing where trno=" . $trno . " and line=" . $val->line);
                    $this->coreFunctions->execqry("delete from stockinfo where trno=" . $trno . " and line=" . $val->line);
                  }

                  $this->coreFunctions->sbclogger('checking existing ref ' . $data[0]->docno);
                  goto checklastockexist;
                }

                $status = true;

                foreach ($stockdata as $key => $value) {

                  $qry = "select ifnull(max(line),0)+1 as value from " . app($path)->stock . " where trno=?";
                  $line = $this->coreFunctions->datareader($qry, [$trno]);

                  $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$value->wh]);

                  $rrqty = $value->rrqty;
                  $ext = $value->ext;
                  if ($value->isqty < 0) {
                    $rrqty = $value->isqty * -1;
                    $ext = $value->ext * -1;
                  }

                  $sidocno = $data[0]->docno;
                  $current_timestamp = $this->othersClass->getCurrentTimeStamp();

                  $stock = [
                    'trno' => $trno,
                    'line' => $line,
                    'itemid' => $value->itemid,
                    'uom' => $value->uom,
                    'whid' => $whid,
                    'disc' => $value->disc,
                    'rem' => $value->rem,
                    'rrcost' => $value->rrcost,
                    'cost' => $value->cost,
                    'rrqty' => $rrqty,
                    'qty' => $value->qty,
                    'isamt' => $value->isamt,
                    'amt' => $value->amt,
                    'isqty' => $value->isqty,
                    'isqty2' => 0,
                    'original_qty' => $value->isqty,
                    'iss' => $value->iss,
                    'ext' => $ext,
                    'ref' => $data[0]->docno,
                    'loc' => '',
                    'expiry' => '',
                    'encodeddate' => $current_timestamp,
                    'encodedby' => 'EXTRACTED',
                    'agentid' => $value->agentid,
                    'posqty' => $value->isqty
                  ];

                  if ($stock['qty'] <> 0) {
                    $stock['cost'] = $this->othersClass->getlatestcost($stock['itemid'], $data[0]->dateid, null, $value->wh);
                    $this->coreFunctions->LogConsole("Line:" . $line . ". Cost:  " . $stock['cost']);
                  }

                  $factor = $this->coreFunctions->getfieldvalue("uom", "factor", "itemid=? and uom=?", [$value->itemid, $value->uom]);
                  if ($factor == '') {
                    $factor = 1;
                  }

                  $noninventory = $this->coreFunctions->getfieldvalue("item", "isnoninv", "itemid=?", [$value->itemid]);
                  if ($noninventory == "1") {
                    $this->coreFunctions->LogConsole("Line:" . $line . ". Non-Inv");
                    goto insertstocklinehere;
                  }

                  if ($isExpiry) {
                    $this->coreFunctions->LogConsole('Expiry Setup');
                    if ($value->isqty > 0) {
                      $total_orig_qty = $value->isqty * $factor;

                      $sql = "select rrstatus.expiry,rrstatus.loc,rrstatus.whid,ifnull(sum(rrstatus.bal),0) as bal from rrstatus
	                      left join item on item.itemid = rrstatus.itemid where rrstatus.itemid = " . $value->itemid . " and rrstatus.whid = '" . $whid . "' and rrstatus.bal <> 0 
	                      group by rrstatus.expiry,rrstatus.loc,rrstatus.whid order by rrstatus.expiry,rrstatus.loc,rrstatus.whid asc";
                      $invdata = $this->coreFunctions->opentable($sql);
                      if (!empty($invdata)) {

                        foreach ($invdata as $key => $inv) {
                          $qry = "select ifnull(max(line),0)+1 as value from " . app($path)->stock . " where trno=?";
                          $line = $this->coreFunctions->datareader($qry, [$trno]);

                          $stock["line"] = $line;
                          $stock["expiry"] = $inv->expiry;
                          $stock["loc"] = $inv->loc;

                          $out_qty = 0;
                          if ($total_orig_qty >  $inv->bal) {
                            $out_qty = $inv->bal / $factor;
                            $stock["original_qty"] = $out_qty;
                            $stock["isqty"] = $out_qty;
                            $stock["isqty2"] = ($out_qty * $factor) - $inv->bal;
                            $stock["iss"] = $inv->bal;

                            $computedata = $this->othersClass->computestock($stock["isamt"], $stock["disc"], $stock["isqty"], $factor, 0, $cur, 0, 0, 1);
                            $stock["ext"] = $computedata['ext'];

                            $total_orig_qty = $total_orig_qty -  $inv->bal;

                            array_push($arr_line, $line);
                            $insertstock = $this->insertstock($trno, $line, $stock, $arr_line, $path, $value, $doc, $pbranch, $pstation, $pickerid, $checkerid, $factor, $noninventory);
                            if ($insertstock['status']) {
                              $this->coreFunctions->LogConsole("Inserted line:" . $line);
                              // $status = true;
                            } else {
                              $status = false;
                              $msg = $msg . ' / ' . $insertstock['msg'];
                            }
                          } else {
                            $out_qty = $total_orig_qty / $factor;
                            $stock["original_qty"] = $out_qty;
                            $stock["isqty"] = $out_qty;
                            $stock["iss"] = $total_orig_qty;

                            $computedata = $this->othersClass->computestock($stock["isamt"], $stock["disc"], $stock["isqty"], $factor, 0, $cur, 0, 0, 1);
                            $stock["ext"] = $computedata['ext'];
                            goto insertstocklinehere;
                            break;
                          }
                        } //end for loop
                        if ($total_orig_qty > 0) {
                          $qry = "select ifnull(max(line),0)+1 as value from " . app($path)->stock . " where trno=?";
                          $line = $this->coreFunctions->datareader($qry, [$trno]);
                          $this->coreFunctions->LogConsole("End loop Line:" . $line . ". Qty:" . $total_orig_qty);
                          $out_qty = $total_orig_qty / $factor;
                          $stock["original_qty"] = $out_qty;
                          $stock["isqty2"] = $out_qty;
                          $stock["isqty"] = 0;
                          $stock["iss"] = 0;
                          $stock["line"] = $line;
                          $computedata = $this->othersClass->computestock($stock["isamt"], $stock["disc"], $stock["isqty"], $factor, 0, $cur, 0, 0, 1);
                          $stock["ext"] = $computedata['ext'];
                          goto insertstocklinehere;
                        }
                      } else {
                        $stock["isqty2"] = $stock["original_qty"];
                        $stock["isqty"] = 0;
                        $stock["iss"] = 0;
                        $stock["ext"] = 0;

                        array_push($arr_line, $line);
                        $insertstock = $this->insertstock($trno, $line, $stock, $arr_line, $path, $value, $doc, $pbranch, $pstation, $pickerid, $checkerid, $factor, $noninventory);
                        if ($insertstock['status']) {
                          $this->coreFunctions->LogConsole("Inserted line:" . $line);
                          // $status = true;
                        } else {
                          $status = false;
                          $msg = $msg . ' / ' . $insertstock['msg'];
                        }
                      }
                    }
                    if ($value->qty > 0) {
                      goto insertstocklinehere;
                    }
                  } else {
                    insertstocklinehere:
                    $this->coreFunctions->LogConsole("Line:" . $line . ". Qty:" . $stock['isqty']);
                    array_push($arr_line, $line);
                    $insertstock = $this->insertstock($trno, $line, $stock, $arr_line, $path, $value, $doc, $pbranch, $pstation, $pickerid, $checkerid, $factor, $noninventory);
                    if ($insertstock['status']) {
                      $this->coreFunctions->LogConsole("Inserted line:" . $line);
                      // $status = true;
                    } else {
                      $status = false;
                      $msg = $msg . ' / ' . $insertstock['msg'];
                      $arr_line = $insertstock['arrline'];
                      goto exitHere;
                    }
                  }
                } //end loop stock                
              } else { //no stock
                $status = true;
              }
            } else {
              $status = false;
              $msg = 'Failed to extract head ' . $data[0]->docno . ' - ' . $pbranch . '-' . $pstation;
              $this->coreFunctions->sbclogger($msg);
            } //end of insert stock
          }
        }
      }
      exitHere:
      if ($status) {
        $lastockqty = $this->coreFunctions->datareader("select IFNULL(sum(s.original_qty),0) as value from lastock as s where s.trno=" . $trno . " and s.ref='" . $data[0]->docno . "'", [], '', true);
        $tempstock = $this->coreFunctions->datareader("select IFNULL(sum(s.isqty),0) as value from stock as s where trno=" . $ptrno . " and station='" . $pstation . "'", [], '', true);
        if ($lastockqty != $tempstock) {
          $this->coreFunctions->sbclogger('generatelatrans - qty not balance ' . $data[0]->docno . ' ' . $pstation . ' - STOCK:' . $tempstock . ' - LA:' . $lastockqty);
          $status = false;
        } else {
          $this->coreFunctions->sbcupdate("head", ['isok2' => 1, 'webtrno' => $trno, 'extractdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $ptrno, 'station' => $pstation, 'branch' => $pbranch]);
          $this->coreFunctions->sbcupdate("stock", ['isok2' => 1], ['trno' => $ptrno, 'station' => $pstation]);
        }
      }
    } catch (Exception $e) {
      $status = false;
      $msg = substr($e, 0, 1000);
      $this->coreFunctions->sbclogger('generatelatrans - ' . $msg);
      $this->coreFunctions->LogConsole($msg);
    }

    if (!$status) {
      $linefilter = '';
      foreach ($arr_line as $key => $l) {
        if ($linefilter == '') {
          $linefilter = $l;
        } else {
          $linefilter = $linefilter . ',' .  $l;
        }
      }

      try {


        // 2024.06.18 - FMM - convert to looping
        if ($linefilter != '') {
          $this->coreFunctions->sbclogger("delete lines (trno:" . $trno . ". lines:" . $linefilter . ")");

          $deletestock = $this->coreFunctions->opentable("select s.trno, s.line from lastock as s where s.trno=" . $trno . " and s.ref='" . $sidocno . "'");
          if (!empty($deletestock)) {
            $this->coreFunctions->sbclogger('deleting stock ' . $sidocno);

            foreach ($lastockexist as $key => $val) {
              $this->coreFunctions->execqry("delete from lastock where trno=" . $trno . " and line=" . $val->line);
              $this->coreFunctions->execqry("delete from costing where trno=" . $trno . " and line=" . $val->line);
              $this->coreFunctions->execqry("delete from stockinfo where trno=" . $trno . " and line=" . $val->line);
            }
          }
        }
      } catch (Exception $e) {

        $msg = substr($e, 0, 1000);
        $this->coreFunctions->sbclogger('deleting lastock - ' . $msg);
        $this->coreFunctions->LogConsole($msg);
      }

      $this->coreFunctions->sbcupdate("stock", ['isok2' => 0], ['trno' => $ptrno, 'station' => $pstation]);
      $this->coreFunctions->sbcupdate("head", ['isok2' => 0, 'webtrno' => $trno], ['trno' => $ptrno, 'station' => $pstation, 'branch' => $pbranch]);
    }

    return ['status' => $status, 'msg' => $msg];
  }

  private function insertstock($trno, $line, $stock, $arr_line, $path, $value, $doc, $pbranch, $pstation, $pickerid, $checkerid, $factor, $noninventory, $companyid = 0, $others = [])
  {
    $status = true;
    $msg = '';

    $pickerid = ($pickerid === '') ? 0 : $pickerid;
    $checkerid = ($checkerid === '') ? 0 : $checkerid;
    $posqty = 0;

    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $barcode = $this->coreFunctions->getfieldvalue("item", "barcode", "itemid=?", [$value->itemid]);

    try {
      foreach ($stock as $key => $v) {
        $stock[$key] = $this->othersClass->sanitizekeyfield($key, $stock[$key]);
      }
      $this->coreFunctions->LogConsole("insertstock Line:" . $line . ". Qty:" . $stock['isqty'] . ". POS Qty:" . $stock['posqty']);
      $posqty = $stock['posqty'];
      unset($stock['posqty']);
      $return = $this->coreFunctions->sbcinsert(app($path)->stock, $stock);
      if ($return) {
        $this->logger->sbcwritelog2($trno, 'EXTRACTION', 'STOCK', "ADD Line:" . $line . ". Barcode:" . $barcode . ". Qty:" . $stock['isqty'] . ". POS Qty:" . $posqty . ' - ' . $stock['ref'], app($path)->tablelogs);

        if ($noninventory == "0") {
          if ($stock['iss'] > 0) {
            checkCostingHere:
            $cost = $this->othersClass->computecosting($stock['itemid'], $stock['whid'], $stock['loc'], $stock['expiry'], $trno, $line, $stock['iss'], $doc, $companyid);
            if ($cost != -1) {

              if ($companyid == 56) {
                $dateid = $this->coreFunctions->getfieldvalue("lahead", "dateid", "trno=?", [$trno]);
                $cost = $this->getDefaultCost($stock['itemid'], $stock['whid'], $dateid);
              }

              $this->coreFunctions->sbcupdate(app($path)->stock, ['cost' => $cost], ['trno' => $trno, 'line' => $line]);
            } else {
              $this->coreFunctions->sbcupdate(app($path)->stock, ['isqty' => 0, 'iss' => 0, 'ext' => 0, 'editby' => 'OUT_STOCK', 'editdate' => $current_timestamp], ['trno' => $trno, 'line' => $line]);
              $this->coreFunctions->execqry('delete from costing where trno=? and line=?', 'delete', [$trno, $line]);
              $this->logger->sbcwritelog2($trno, 'EXTRACTION', 'STOCK', 'OUT OF STOCK - Line:' . $line . ' barcode:' . $barcode . ' Qty' . $stock['isqty'] . ' Amt:' . $stock['isamt'], app($path)->tablelogs);

              $filter = '';
              if ($stock['expiry'] == '1900-01-01' || $stock['expiry'] == '' || $stock['expiry'] == null) {
              } else {
                $filter =  " and rrstatus.expiry = '" . $stock['expiry'] . "'";
              }

              $availableBal = $this->coreFunctions->datareader(
                "select ifnull(sum(rrstatus.bal),0) as value  from rrstatus where rrstatus.itemid =? and rrstatus.whid =? and rrstatus.loc=? and rrstatus.bal <> 0 " . $filter,
                [$stock['itemid'], $stock['whid'], $stock['loc']],
                '',
                true
              );
              if ($availableBal > 0) {
                $stock['isqty'] = $availableBal / $factor;
                $stock['isqty2'] = $stock['original_qty'] - ($availableBal / $factor);
                $stock['iss'] = $availableBal;

                $computedata = $this->othersClass->computestock($stock["isamt"], $stock["disc"], $stock["isqty"], $factor, 0, 'P', 0, 0, 1);
                $stock['ext'] = $computedata['ext'];
                $this->coreFunctions->sbcupdate("lastock", ['isqty' => $stock['isqty'], 'isqty2' => $stock['isqty2'], 'iss' => $stock['iss'], 'ext' => $stock['ext']], ['trno' => $trno, 'line' => $line]);
                goto checkCostingHere;
              } else {
                $stock['isqty'] = 0;
                $stock['isqty2'] = $stock['original_qty'];
                $stock['iss'] = 0;
                $stock['ext'] = 0;
                $this->coreFunctions->sbcupdate("lastock", ['isqty' => $stock['isqty'], 'isqty2' => $stock['isqty2'], 'iss' => $stock['iss'], 'ext' => $stock['ext']], ['trno' => $trno, 'line' => $line]);
              }
            }
          } // end of iss>0
        } // end of noninventory

        $vat = 1.12;
        $ext = 0;
        $isqty = $stock['original_qty'];
        $nvat = $value->nvat;
        $vatamt = $value->vatamt;
        $vatex = $value->vatex;
        $lessvat = $value->lessvat;
        $sramt = $value->sramt;
        $pwdamt = $value->pwdamt;
        $discamt = $value->discamt;

        $this->coreFunctions->LogConsole("POS:" . $posqty . ', ISQTY:' . $isqty);

        if ($posqty > $isqty) {
          $computedata = $this->othersClass->computestock($value->isamt, $value->disc, $isqty, $factor, 0, 'P', 0, 0, 1);
          $ext = $computedata['ext'];
          $discamt = ($value->isamt * $isqty) - $ext;
          if ($value->vatamt == 0) {
            if ($value->lessvat != 0) {
              $vatex = round($ext / $vat, 4);
              $lessvat = round(($ext / $vat) * ($vat - 1), 4);

              if ($value->sramt != 0) {
                $sramt = round((($ext / $vat)) * 0.2, 4);
              }
              if ($value->pwdamt != 0) {
                $pwdamt = round((($ext / $vat)) * 0.2, 4);
              }
            } else {
              $vatex = $ext;
              $lessvat = 0;

              if ($value->sramt != 0) {
                $sramt = round($ext * 0.2, 4);
              }
              if ($value->pwdamt != 0) {
                $pwdamt = round($ext * 0.2, 4);
              }
            }
          } else {
            $nvat = round($ext / $vat, 4);
            $vatamt = round(($ext / $vat) * ($vat - 1), 4);
            if ($value->sramt != 0) {
              $sramt = round((($ext / $vat)) * 0.2, 4);
            }
            if ($value->pwdamt != 0) {
              $pwdamt = round((($ext / $vat)) * 0.2, 4);
            }
          }
        }

        $stockinfo = [
          'trno' => $trno,
          'line' => $line,
          'nvat' => $nvat,
          'vatamt' => $vatamt,
          'vatex' => $vatex,
          'discamt' => $discamt,
          'sramt' => $sramt,
          'pwdamt' => $pwdamt,
          'lessvat' => $lessvat,
          'vipdisc' => $value->vipdisc,
          'empdisc' => $value->empdisc,
          'oddisc' => $value->oddisc,
          'smacdisc' => $value->smacdisc,
          'pickerid' => $pickerid,
          'checkerid' => $checkerid
        ];

        switch ($companyid) {
          case 56: //homeworks
            if (count($others) > 0) {
              foreach ($others as $okey => $ovalue) {
                $stockinfo[$okey] = $ovalue;
              }
            }
            break;
        }

        foreach ($stockinfo as $key => $vs) {
          $stockinfo[$key] = $this->othersClass->sanitizekeyfield($key, $stockinfo[$key]);
        }

        $this->coreFunctions->execqry("delete from stockinfo where trno=" . $trno . " and line=" . $line);

        $return = $this->coreFunctions->sbcinsert('stockinfo', $stockinfo);
        if ($return) {
          $status = true;
        } else {
          $status = false;
          $msg = 'Failed to extract stockinfo ' . $stock['ref'] . ' - ' . $pbranch . '-' . $pstation;
          $this->coreFunctions->sbclogger($msg);
        }
      } else {
        $status = false;
        $msg = 'Failed to insert stocks ' . $stock['ref'] . ' - ' . $pbranch . '-' . $pstation;
        $this->coreFunctions->sbclogger($msg);
      } //end of insert stock
    } catch (Exception $ex) {
      $status = false;
      $msg = substr($ex, 0, 500);
      $this->coreFunctions->sbclogger($msg);
    }

    return ['status' => $status, 'msg' => $msg, 'arrline' => $arr_line];
  }

  public function generatelatrans_homeworks($pbranch, $pstation, $ptrno, $params)
  {

    $center = $this->coreFunctions->datareader("select c.code AS value FROM center AS c LEFT JOIN client ON client.clientid=c.branchid WHERE client.client='" . $pbranch . "'");
    if ($center == '') {
      $this->coreFunctions->LogConsole('Please setup valid center for this branch ' . $pbranch);
      return ['status' => false, 'msg' => 'Please setup valid center for this branch ' . $pbranch];
    }

    $status = false;
    $msg = 'Failed to extract head';

    $sidocno = '';
    $trno = 0;
    $arr_line = [];
    $pickerid = 0;
    $checkerid = 0;

    try {

      $data = $this->coreFunctions->opentable("select docno, clientid, clientname, date(dateid) as dateid, wh, transtype, amt, address, rem, cur, dateid, picker, checker, terminalid
      from head where branch=? and station=? and trno=? and isok2=0", [$pbranch, $pstation, $ptrno]);
      if ($data) {

        $doc = 'SJ';
        $pref = 'SJS';
        $path = 'App\Http\Classes\modules\sales\sj';
        $referencemodule = ''; //MOBILE APP

        if ($data[0]->amt < 0) {
          $doc = 'CM';
          $pref = 'SRS';
          $path = 'App\Http\Classes\modules\sales\cm';
        }

        $branchid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$pbranch]);
        $client = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$data[0]->clientid]);
        $projectid = $this->coreFunctions->getfieldvalue("branchstation", "projectid", "station=?", [$pstation]);

        $pickerid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data[0]->picker]);
        $checkerid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$data[0]->checker]);

        $salestype = 'REGULAR';
        switch ($data[0]->transtype) {
          case 'S':
          case 'ST':
            $salestype = 'SENIOR';
            break;
          case 'P':
          case 'PT':
            $salestype = 'PWD';
            break;
          case 'D':
          case 'DT':
            $salestype = 'DIPLOMAT';
            break;
        }

        $data[0]->dateid = date('Y-m-d', strtotime($data[0]->dateid));

        $exist = $this->coreFunctions->datareader("select c.trno as value from lahead as h left join cntnum as c on c.trno=h.trno 
        where c.doc=? and h.client=? and h.dateid=? and h.branch=? and c.station=? and h.salestype=?", [$doc, $client, $data[0]->dateid, $branchid, $pstation, $salestype]);

        if ($client == '') {
          $msg = 'generatelatrans - Missing clientid ' . $data[0]->clientid;
          $this->coreFunctions->sbclogger($msg);
          return ['status' => $status, 'msg' => $msg];
        }

        if ($exist) {
          $trno = $exist;
          goto insertstockhere;
        } else {
          $config = [];
          $config['params']['center'] = $center;
          $config['params']['user'] = 'AUTO';
          $config['params']['station'] = $pstation;
          $config['params']['doc'] = $doc;
          $config['params']['companyid'] = $params['companyid'];

          $trno = $this->othersClass->generatecntnum($config, app($path)->tablenum, $doc, $pref, $this->companysetup->getdocumentlength($config['params']), 0, '', true);

          if ($trno != -1) {
            $docno =  $this->coreFunctions->getfieldvalue(app($path)->tablenum, 'docno', "trno=?", [$trno]);
            $contra = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['AR1']);

            $head = [
              'trno' => $trno,
              'doc' => $doc,
              'docno' => $docno,
              'client' => $client,
              'clientname' => $data[0]->clientname,
              'address' => $data[0]->address,

              'cur' => $data[0]->cur,
              'forex' => 1,
              'dateid' => $data[0]->dateid,
              'due' => $data[0]->dateid,
              'terms' => '',
              'wh' => $data[0]->wh,
              'branch' => $branchid,
              'contra' => $contra,
              'vattype' => 'NON-VATABLE',
              'projectid' => $projectid,
              'salestype' => $salestype
            ];

            if ($client == 'WALK-IN') {
              $head['clientname'] = '';
            }

            $inserthead = $this->coreFunctions->sbcinsert(app($path)->head, $head);
            if ($inserthead) {
              $this->logger->sbcwritelog2($trno, 'EXTRACTION', 'CREATE', $docno . ' - EXTRACTED ' . $referencemodule, app($path)->tablelogs);

              insertstockhere:
              $stockdata = $this->coreFunctions->opentable("
              select itemid, itemname, uom, wh, disc, rem, rrcost, cost, sum(rrqty) as rrqty, abs(sum(qty)) as qty, isamt, amt, sum(isqty) as isqty, abs(sum(iss)) as iss, sum(ext) as ext, ref,
              sum(nvat) as nvat, sum(vatamt) as vatamt, sum(vatex) as vatex, sum(sramt) as sramt, sum(pwdamt) as pwdamt, sum(lessvat) as lessvat, sum(discamt) as discamt,
              sum(vipdisc) as vipdisc, sum(empdisc) as empdisc, sum(oddisc) as oddisc, sum(smacdisc) as smacdisc,
              isdiplomat, issenior2, qa, iscomp, iscomponent, agentid, supplierid, sum(cash) as cash, sum(card) as card, sum(debit) as debit, sum(cheque) as cheque, sum(voucher) as voucher, sum(deposit) as deposit,
              promoby, promodesc, gcno, promoref, pricetype, ispromo, ispa, isbuy1, isoverride, overrideby, prodcycle, serial
              from stock where station=? and trno=? group by  itemid, itemname, uom, wh, disc, rem, rrcost, cost, isamt, amt, ref, isdiplomat, issenior2, qa, iscomp, iscomponent, agentid, supplierid, 
              promoby, promodesc, gcno, promoref, pricetype, ispromo, ispa, isbuy1, isoverride, overrideby, prodcycle, serial", [$pstation, $ptrno]);

              $arr_line = [];
              if ($stockdata) {
                $cur = $this->coreFunctions->getfieldvalue(app($path)->head, 'cur', 'trno=?', [$trno]);

                //2024.04.29 - FMM - checking if items exist
                checklastockexist:
                $lastockexist = $this->coreFunctions->opentable("select s.trno, s.line from lastock as s where s.trno=" . $trno . " and s.ref='" . $data[0]->docno . "'");
                if (!empty($lastockexist)) {
                  $this->coreFunctions->sbclogger('deleted existing ref ' . $data[0]->docno);

                  foreach ($lastockexist as $key => $val) {
                    $this->coreFunctions->execqry("delete from lastock where trno=" . $trno . " and line=" . $val->line);
                    $this->coreFunctions->execqry("delete from costing where trno=" . $trno . " and line=" . $val->line);
                    $this->coreFunctions->execqry("delete from stockinfo where trno=" . $trno . " and line=" . $val->line);
                  }

                  $this->coreFunctions->sbclogger('checking existing ref ' . $data[0]->docno);
                  goto checklastockexist;
                }

                $status = true;

                foreach ($stockdata as $key => $value) {

                  $qry = "select ifnull(max(line),0)+1 as value from " . app($path)->stock . " where trno=?";
                  $line = $this->coreFunctions->datareader($qry, [$trno]);

                  $whid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$value->wh]);

                  $rrqty = $value->rrqty;
                  $ext = $value->ext;
                  if ($value->isqty < 0) {
                    $rrqty = $value->isqty * -1;
                    $ext = $value->ext * -1;
                  }

                  $sidocno = $data[0]->docno;
                  $current_timestamp = $this->othersClass->getCurrentTimeStamp();

                  $stock = [
                    'trno' => $trno,
                    'line' => $line,
                    'itemid' => $value->itemid,
                    'uom' => $value->uom,
                    'whid' => $whid,
                    'disc' => $value->disc,
                    'rem' => $value->rem,
                    'rrcost' => $value->rrcost,
                    'cost' => $value->cost,
                    'rrqty' => $rrqty,
                    'qty' => $value->qty,
                    'isamt' => $value->isamt,
                    'amt' => $value->amt,
                    'isqty' => $value->isqty,
                    'isqty2' => 0,
                    'original_qty' => $value->isqty,
                    'iss' => $value->iss,
                    'ext' => number_format($ext, 2, '.', ''),
                    'ref' => $data[0]->docno,
                    'loc' => '',
                    'expiry' => '',
                    'encodeddate' => $current_timestamp,
                    'encodedby' => 'EXTRACTED',
                    'agentid' => $value->agentid,
                    'posqty' => $value->isqty,
                    'suppid' => $value->supplierid
                  ];

                  if ($stock['qty'] <> 0) {
                    $stock['cost'] = $this->othersClass->getlatestcost($stock['itemid'], $data[0]->dateid, null, $value->wh);
                    $this->coreFunctions->LogConsole("Line:" . $line . ". Cost:  " . $stock['cost']);
                  }

                  $factor = $this->coreFunctions->getfieldvalue("uom", "factor", "itemid=? and uom=?", [$value->itemid, $value->uom]);
                  if ($factor == '') {
                    $factor = 1;
                  }

                  $channel = $commrate = '';
                  $comm1 = $comm2 = 0;
                  $comap1 = $comap2 = 0;

                  $iteminfo = $this->coreFunctions->opentable("select isnoninv, channel from item where itemid=?", [$value->itemid]);
                  if (!empty($iteminfo)) {
                    $noninventory = $iteminfo[0]->isnoninv;
                    $channel = $iteminfo[0]->channel;
                  }

                  if ($value->supplierid == 0) {
                    $value->supplierid = $this->coreFunctions->datareader("select clientid as value FROM supplierlist WHERE itemid=" . $value->itemid . " AND '" . $data[0]->dateid . "' BETWEEN startdate AND enddate ORDER BY startdate LIMIT 1", [], '', true);
                    if ($value->supplierid == 0) {
                      $value->supplierid = $this->coreFunctions->datareader("select supplier as value FROM item WHERE itemid=" . $value->itemid, [], '', true);
                    }
                    $stock['suppid'] = $value->supplierid;
                  }

                  if ($value->supplierid != 0) {
                    $comm1 = $this->coreFunctions->datareader("select cl.comm1 as value from commissionlist as cl where cl.clientid=? and '" . $data[0]->dateid . "' between startdate and enddate order by cl.startdate limit 1", [$value->supplierid], '', true);
                    $comm2 = $this->coreFunctions->datareader("select cl.comm2 as value from commissionlist as cl where cl.clientid=? and '" . $data[0]->dateid . "' between startdate and enddate order by cl.startdate limit 1", [$value->supplierid], '', true);
                  } else {
                    $status = false;
                    $msg = $msg . ' / ' . ' Missing supplier for item ' . $value->itemname;
                    goto exitHere;
                  }

                  if ($channel == 'CONCESSION') {
                    if ($comm1 != 0) {
                      $commrate = $comm1;
                      $commamt = 0;

                      if (abs($stock['ext']) > 0) {
                        $commamt = number_format(abs($stock['ext']) *  ($commrate / 100), 2, '.', '') * -1;
                        $comap1 = abs($stock['ext']) + $commamt;
                      }
                    }
                  } else {
                    $defaultcost = $this->getDefaultCost($stock['itemid'], $stock['whid'], $data[0]->dateid);
                    if ($stock['iss'] > 0) {
                      if (abs($stock['ext']) > 0) {
                        $comap1 = number_format($stock['iss'] *  $defaultcost, 2, '.', '');
                      }
                    }
                    if ($stock['qty'] > 0) {
                      $comap1 = number_format($stock['qty'] *  $defaultcost, 2, '.', '');
                    }
                  }

                  if ($comm2 != 0) {
                    if (abs($stock['ext']) > 0) {
                      $comap2 = number_format(abs($comap1) *  ($comm2 / 100), 2, '.', '');
                    }
                  }

                  $cardamt = 0;
                  $terminal = '';
                  $banktype = '';
                  $bankrate = '';
                  $cardcharge = 0;
                  $cardamtitem = 0;

                  if ($data[0]->terminalid != '') {
                    $tid = explode(",", $data[0]->terminalid);
                    for ($x = 0; $x < count($tid); $x++) {

                      $cardtype = substr($tid[$x], 0, 1);

                      switch ($cardtype) {
                        //credit card
                        case 'C':
                          $k = explode("~", $tid[$x]);
                          $cardamt = $cardamt + $k[1];
                          $terminal = substr($k[0], 1, strlen($k[0]) - 1);
                          $banktype = $this->coreFunctions->getfieldvalue("bankcharges", "type", "type=? and terminalid=?", [$k[2], $terminal]);
                          $bankrate = $this->coreFunctions->getfieldvalue("bankcharges", "rate", "type=? and terminalid=?", [$k[2], $terminal], '', true);

                          if ($value->card != 0) {
                            switch ($terminal) {
                              case 'HOMECREDIT':
                              case 'SKYRO':
                              case 'BILLEASE':
                                CardExtBasishere:
                                $cardcharge = $cardcharge + number_format(abs($stock['ext']) * ((float) str_replace("%", "", $bankrate) / 100), 2, '.', '');
                                break;
                              default:
                                $cardcharge = $cardcharge + number_format(abs($value->card) * ((float) str_replace("%", "", $bankrate) / 100), 2, '.', '');
                                break;
                            }
                            $cardamtitem =   $cardamtitem + abs($value->card);
                          } else {
                            switch ($terminal) {
                              case 'HOMECREDIT':
                              case 'SKYRO':
                              case 'BILLEASE':
                                $cardcharge = $cardcharge + number_format(abs($stock['ext']) * ((float) str_replace("%", "", $bankrate) / 100), 2, '.', '');
                                $cardamtitem =   $cardamtitem + abs($value->card);
                                break;
                            }
                          }
                          break;
                        //debit card
                        case 'D':
                          $k = explode("~", $tid[$x]);
                          $cardamt = $cardamt + $k[1];
                          $terminal = substr($k[0], 1, strlen($k[0]) - 1);
                          $banktype = "DEBIT";
                          $bankrate = "3%";

                          if ($value->debit != 0) {
                            $cardcharge = $cardcharge + number_format(abs($value->debit) * ((float) str_replace("%", "", $bankrate) / 100), 2, '.', '');
                            $cardamtitem =   $cardamtitem + abs($value->debit);
                          }
                          break;
                      }
                    }
                  }

                  $this->coreFunctions->LogConsole("Line:" . $line . ". Qty:" . $stock['isqty']);
                  array_push($arr_line, $line);

                  $others = [
                    'comm1' => $comm1,
                    'comm2' => $comm2,
                    'comap' => $comap1,
                    'comap2' => $comap2,
                    'comrate' => $commrate,
                    'banktype' => $banktype,
                    'bankrate' => $bankrate,
                    'cardcharge' => $cardcharge,
                    'netap' => $comap1 - $comap2 - $cardcharge,
                    'terminalid' => $data[0]->terminalid,
                    'ispa' => $value->ispa,
                    'ispromo' => $value->ispromo,
                    'isbuy1' => $value->isbuy1,
                    'promoby' => $value->promoby,
                    'promodesc' => $value->promodesc,
                    'pricetype' => $value->pricetype,
                    'gcno' => $value->gcno,
                    'isoverride' => $value->isoverride,
                    'overrideby' => $value->overrideby,
                    'serialno' => $value->serial
                  ];

                  $insertstock = $this->insertstock($trno, $line, $stock, $arr_line, $path, $value, $doc, $pbranch, $pstation, $pickerid, $checkerid, $factor, $noninventory, $params["companyid"], $others);
                  if ($insertstock['status']) {
                    $this->coreFunctions->LogConsole("Inserted line:" . $line);
                    // $status = true;
                  } else {
                    $status = false;
                    $msg = $msg . ' / ' . $insertstock['msg'];
                    $arr_line = $insertstock['arrline'];
                    goto exitHere;
                  }
                } //end loop stock                
              } else { //no stock
                $status = true;
              }
            } else {
              $status = false;
              $msg = 'Failed to extract head ' . $data[0]->docno . ' - ' . $pbranch . '-' . $pstation;
              $this->coreFunctions->sbclogger($msg);
            } //end of insert stock
          }
        }
      }
      exitHere:
      if ($status) {
        $lastockqty = $this->coreFunctions->datareader("select IFNULL(sum(s.original_qty),0) as value from lastock as s where s.trno=" . $trno . " and s.ref='" . $data[0]->docno . "'", [], '', true);
        $tempstock = $this->coreFunctions->datareader("select IFNULL(sum(s.isqty),0) as value from stock as s where trno=" . $ptrno . " and station='" . $pstation . "'", [], '', true);
        if ($lastockqty != $tempstock) {
          $this->coreFunctions->sbclogger('generatelatrans - qty not balance ' . $data[0]->docno . ' ' . $pstation . ' - STOCK:' . $tempstock . ' - LA:' . $lastockqty);
          $status = false;
        } else {
          $this->coreFunctions->sbcupdate("head", ['isok2' => 1, 'webtrno' => $trno, 'extractdate' => $this->othersClass->getCurrentTimeStamp()], ['trno' => $ptrno, 'station' => $pstation, 'branch' => $pbranch]);
          $this->coreFunctions->sbcupdate("stock", ['isok2' => 1], ['trno' => $ptrno, 'station' => $pstation]);
        }
      }
    } catch (Exception $e) {
      $status = false;
      $msg = substr($e, 0, 1000);
      $this->coreFunctions->sbclogger('generatelatrans - ' . $msg);
      $this->coreFunctions->LogConsole($msg);
    }

    if (!$status) {
      $linefilter = '';
      foreach ($arr_line as $key => $l) {
        if ($linefilter == '') {
          $linefilter = $l;
        } else {
          $linefilter = $linefilter . ',' .  $l;
        }
      }

      try {


        // 2024.06.18 - FMM - convert to looping
        if ($linefilter != '') {
          $this->coreFunctions->sbclogger("delete lines (trno:" . $trno . ". lines:" . $linefilter . ")");

          $deletestock = $this->coreFunctions->opentable("select s.trno, s.line from lastock as s where s.trno=" . $trno . " and s.ref='" . $sidocno . "'");
          if (!empty($deletestock)) {
            $this->coreFunctions->sbclogger('deleting stock ' . $sidocno);

            foreach ($lastockexist as $key => $val) {
              $this->coreFunctions->execqry("delete from lastock where trno=" . $trno . " and line=" . $val->line);
              $this->coreFunctions->execqry("delete from costing where trno=" . $trno . " and line=" . $val->line);
              $this->coreFunctions->execqry("delete from stockinfo where trno=" . $trno . " and line=" . $val->line);
            }
          }
        }
      } catch (Exception $e) {

        $msg = substr($e, 0, 1000);
        $this->coreFunctions->sbclogger('deleting lastock - ' . $msg);
        $this->coreFunctions->LogConsole($msg);
      }

      $this->coreFunctions->sbcupdate("stock", ['isok2' => 0], ['trno' => $ptrno, 'station' => $pstation]);
      $this->coreFunctions->sbcupdate("head", ['isok2' => 0, 'webtrno' => $trno], ['trno' => $ptrno, 'station' => $pstation, 'branch' => $pbranch]);
    }

    return ['status' => $status, 'msg' => $msg];
  }

  private function getDefaultCost($itemid, $whid, $dateid)
  {

    $plbcost = $plcost = $icost = 0;

    $plbcost = $this->coreFunctions->datareader("select pl.cost as value from pricelist as pl where itemid=" . $itemid . " and '" . $dateid . "' between startdate and enddate and clientid=" . $whid . " order by pl.createdate desc limit 1", [], '', true);
    $plcost = $this->coreFunctions->datareader("select pl.cost as value from pricelist as pl where itemid=" . $itemid . " and '" . $dateid . "' between startdate and enddate and clientid=0 order by pl.createdate desc limit 1", [], '', true);;
    $icost = $this->coreFunctions->getfieldvalue("item", "avecost", "itemid=?", [$itemid], '', true);

    if ($plbcost != 0) {
      return $plbcost;
    } elseif ($plcost != 0) {
      return $plcost;
    } else {
      return $icost;
    }
  }

  public function gettransdoc($doc, $table)
  {
    $qry = "select trno, doc, docno from " . $table . " where doc='" . $doc . "' and postdate is not null and iscsv=0 order by trno";
    return $this->coreFunctions->opentable($qry);
  }

  public function gettransactionsqry($table, $trno)
  {
    $qry = "select COLUMN_NAME FROM information_schema.columns WHERE table_schema = '" . env('DB_DATABASE') . "' and table_name = '" . $table . "' ORDER BY ordinal_position";
    $a = $this->coreFunctions->opentable($qry);
    $selects = '';
    $ins = '"insert into ' . $table . '(';
    if (!empty($a)) {
      foreach ($a as $akey => $aa) {
        if ($akey == 0) {
          $selects .= $aa->COLUMN_NAME;
        } else {
          $selects .= ',' . $aa->COLUMN_NAME;
        }
      }
      $ins .= $selects . ')values';
      $selects = 'select ' . $selects . ' from ' . $table . ' where trno=' . $trno;
      $arr1 = [];
      $nums = $this->coreFunctions->opentable($selects);
      if (!empty($nums)) {
        foreach ($nums as $nkey => $nn) {
          $arr1 = (array)$nn;
          foreach ($arr1 as $arrkey => $arr) {
            $arr1[$arrkey] = $this->removeNewlines(trim($arr1[$arrkey]));
            $arr1[$arrkey] = $this->othersClass->sanitizekeyfield($arrkey, $arr1[$arrkey]);
            if ($arr1[$arrkey] === null) $arr1[$arrkey] = "NULL";
          }
          if ($nkey == 0) {
            $ins .= '(' . "'" . implode("','", $arr1) . "')";
          } else {
            $ins .= ',(' . "'" . implode("','", $arr1) . "')";
          }
        }
        return $ins . '"';
      }
    }
    return '';
  }

  public function transactionsmirror($doc)
  {
    $this->coreFunctions->LogConsole("Mirror - Creating " . $doc . " transactions file");
    $this->coreFunctions->sbclogger("Mirror - Creating " . $doc . " transactions file", "DLOCK");

    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $numtable = '';
    $tables = [];

    try {
      switch ($doc) {
        case 'sj':
          $numtable = 'cntnum';
          $tables = ['cntnum', 'glhead', 'glstock', 'gldetail', 'arledger', 'costing', 'cntnuminfo', 'hstockinfo'];
          break;
      }
      $docs = $this->gettransdoc($doc, $numtable);
      if (!empty($docs)) {
        foreach ($docs as $dkey => $doc1) {
          $queries = [];
          foreach ($tables as $t) {
            $qry = $this->gettransactionsqry($t, $doc1->trno);
            array_push($queries, $qry);
          }
          $csv = $this->createtranscsv($queries);
          $this->coreFunctions->LogConsole('creating transaction csv doc:' . $doc1->doc . ', docno:' . $doc1->docno . ', trno:' . $doc1->trno);
          $this->ftpcreatefiletrans($csv, "MIRROR", "MIRROR1", 'download', $doc1->doc, $doc1->docno, $doc1->trno);
          $this->coreFunctions->sbcupdate($numtable, ['iscsv' => 1], ['trno' => $doc1->trno]);
        }
      } else {
        $this->coreFunctions->LogConsole('No transaction(s) found.');
      }
    } catch (Exception $e) {
      $msg = substr($e, 0, 1000);
      $this->coreFunctions->sbclogger('transactionsmirror - ' . $msg);
      $this->coreFunctions->LogConsole($msg);
    }
  }

  public function masterfilemirror($table, $uniquefield)
  {
    $this->coreFunctions->LogConsole("Mirror - Creating " . $table . " file");
    $this->coreFunctions->sbclogger("Mirror - Creating " . $table . " file", 'MIRROR');


    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    try {
      $sql = "select * from " . $table . " where ismirror=0 order by " . $uniquefield[0];
      $item = $this->coreFunctions->opentable($sql);
      $item2 = json_decode(json_encode($item), true);

      foreach ($item2 as $key => $value) {
        $filter = "";
        foreach ($uniquefield as $uniquef) {
          if ($filter == "") {
            $filter = $uniquef . " = " . $item2[$key][$uniquef];
          } else {
            $filter .= " and " . $uniquef . " = '" . $item2[$key][$uniquef] . "'";
          }
        }

        $qryupdate = "update " . $table . " set ismirror=1 where " . $filter;
        $this->coreFunctions->execqry($qryupdate);
      }

      $batchSize = 10000;

      //creating csv files for item
      $totalRowsItem = count($item);

      $this->coreFunctions->LogConsole($table . ': ' . $totalRowsItem);

      $counter = 1;
      for ($offset = 0; $offset < $totalRowsItem; $offset += $batchSize) {
        $batch = array_slice($item, $offset, $batchSize);

        $this->coreFunctions->LogConsole('creating ' . $table . ' csv batch ' . $counter);

        $csv = '';
        $csv = $this->createcsv($batch, 1);
        $this->ftpcreatefile($csv, "MIRROR", "MIRROR1", 'download', $table, 1, ".b" . $counter);

        $counter += 1;
      }
    } catch (Exception $e) {
      $msg = substr($e, 0, 1000);
      $this->coreFunctions->sbclogger('masterfilemirror - ' . $msg);
      $this->coreFunctions->LogConsole($msg);
    }
  } //end function

  public function ftpextractmirrorfiles()
  {
    $this->ftpcheckmirrorfiletoextract("MIRROR", "MIRROR1", "download");
  }

  public function ftpcheckmirrorfiletoextract($branch, $station, $folder)
  {
    $status = false;
    try {
      date_default_timezone_set('Asia/Singapore');

      $ftpHost = config('filesystems.disks.ftpmirror.host');
      $this->coreFunctions->sbclogger('ftp host: ' . $ftpHost, 'DLOCK');
      $this->coreFunctions->LogConsole('Checking directory ' . $branch . '/' . $station . '/' . $folder);
      $this->coreFunctions->sbclogger('Checking directory ' . $branch . '/' . $station . '/' . $folder, 'DLOCK');
      foreach (Storage::disk('ftpmirror')->files($branch . '/' . $station . '/' . $folder) as $filename) {
        $this->coreFunctions->LogConsole('Found ' . $filename);

        $status = false;
        if (Str::substr($filename, -3) === 'sbc') {
          $arrline = $this->ftpfilecheckendfile($filename, true);
          // $this->coreFunctions->LogConsole(json_encode($arrline));

          if (is_array($arrline)) {

            $a = explode('/', $filename);
            $b =  explode('~', $a[3]);
            //table~date    
            if ($this->extractionlinerecord($filename, $b[0], true)) {
              try {
                $this->ftpdeletefile($filename, true);
                $status = true;
                $this->coreFunctions->sbclogger("MIRROR - file deleted " . $filename, 'DLOCK');
                $this->coreFunctions->LogConsole("File deleted " . $filename);
              } catch (Exception $ex) {
                $status = false;
                $this->coreFunctions->sbclogger("MIRROR - deleting failed " . $filename . ' ' . substr($ex, 0, 1000));
              }
            }
          }
        }
      }
    } catch (Exception $ex) {
      $status = false;
      $this->coreFunctions->sbclogger('ftpcheckmirrorfiletoextract - ' . substr($ex, 0, 1000));
    }

    return ['status' => $status];
  }

  private function extractionlinerecord($path, $table, $mirror)
  {
    $status = true;
    try {
      if ($table == 'trans') {
        $file = Storage::disk('ftp')->get($path);
        $qrys = explode("\n", $file);
        $f = explode('/', $path);
        $f = explode('~', $f[3]);
        $trno = $f[3];
        $doc = $f[1];
        $numtable = '';
        $tables = [];
        switch ($doc) {
          case 'SJ':
            $numtable = 'cntnum';
            $tables = ['cntnum', 'glhead', 'gldetail', 'glstock', 'arledger', 'costing', 'cntnuminfo', 'hstockinfo'];
            break;
        }
        $rec = $this->coreFunctions->opentable("select trno from $numtable where trno=" . $trno);
        if (!empty($rec)) {
          foreach ($tables as $t) {
            $this->coreFunctions->execqry("delete from $t where trno=" . $trno, 'delete');
          }
        }
        foreach ($qrys as $qry) {
          if ($qry != 'ENDFILE' && $qry != '') {
            $qry = str_replace("'NULL'", "NULL", $qry);
            if ($this->coreFunctions->execqry(str_replace('"', '', $qry))) {
              $this->coreFunctions->LogConsole("success insert");
            } else {
              $this->coreFunctions->LogConsole("failed insert");
              $status = false;
            }
          }
        }
      } else {
        $data = $this->parseStringToArray($path, $mirror);

        $this->coreFunctions->LogConsole(count($data));
        // $this->coreFunctions->LogConsole(json_encode($data));

        if (!empty($data)) {
          $uniquefield = [];
          switch ($table) {
            case 'item':
              $uniquefield = ['itemid'];
              break;
            case 'uom':
              $uniquefield = ['itemid', 'uom'];
              break;
            case 'client':
            case 'clientinfo':
              $uniquefield = ['clientid'];
              break;
            case 'item_class':
              $uniquefield = ['cl_id'];
              break;
            case 'itemcategory':
              $uniquefield = ['line'];
              break;
            case 'frontend_ebrands':
              $uniquefield = ['brandid'];
              break;
          }

          foreach ($data as $row) {
            // $id = $row[$uniquefield];

            $formattedValues = [];

            $filter = "";
            foreach ($uniquefield as $uniquef) {
              if ($filter == "") {
                $filter = $uniquef . " = " .  $row[$uniquef];
              } else {
                $filter .= " and " . $uniquef . " = '" . $row[$uniquef] . "'";
              }
            }

            $selectQueries = "SELECT " . $uniquefield[0] . " as value FROM " . $table . " WHERE " .  $filter;
            $exists =  $this->coreFunctions->datareader($selectQueries, [], '', true);
            if ($exists == 0) {

              $columns = implode(', ', array_map(function ($column) {
                return "`$column`";
              }, array_keys($data[0])));

              $values = "";

              $escapedValues = array_map('addslashes', array_values($row));
              $values = "('" . implode("', '", $escapedValues) . "')";

              foreach ($row as $column => $value) {
                if ($this->isDateField($column)) {
                  $formattedValues[] = $this->getDateSQLValue($value);
                } else {
                  $formattedValues[] = "'" . addslashes($value) . "'";
                }
              }

              $values = "(" . implode(", ", $formattedValues) . ")";

              $insertqry = "INSERT INTO " . $table . " ($columns) VALUES " . $values;
              $this->coreFunctions->LogConsole($insertqry);
              if ($this->coreFunctions->execqry($insertqry)) {
                $this->coreFunctions->LogConsole("success insert");
              } else {
                $this->coreFunctions->LogConsole("failed insert");
                $status = false;
              }
            } else {
              $updates = [];
              foreach ($row as $column => $value) {
                if ($column !== $uniquefield) {
                  $escapedValue = addslashes($value);

                  if ($this->isDateField($column)) {
                    $sqlValue = $this->getDateSQLValue($value);
                    $updates[] = "`$column` = $sqlValue";
                  } else {
                    $escapedValue = addslashes($value);
                    $updates[] = "`$column` = '$escapedValue'";
                  }
                }
              }
              $setClause = implode(', ', $updates);
              $updateqry = "UPDATE " . $table . " SET $setClause WHERE " .  $filter;

              if ($updateqry != "") {
                $this->coreFunctions->LogConsole($updateqry);
                if ($this->coreFunctions->execqry($updateqry)) {
                  $this->coreFunctions->LogConsole("success update");
                } else {
                  $this->coreFunctions->LogConsole("failed update");
                  $status = false;
                }
              }
            }
          }
        }
      }
    } catch (Exception $ex) {
      $status = false;
      $this->coreFunctions->LogConsole('ftpcheckmirrorfiletoextract - ' . substr($ex, 0, 1000));
      $this->coreFunctions->sbclogger('ftpcheckmirrorfiletoextract - ' . substr($ex, 0, 1000));
    }

    return $status;
  }

  function parseStringToArray($path, $mirror)
  {
    if ($mirror) {
      $file = Storage::disk('ftpmirror')->get($path);
    } else {
      $file = Storage::disk('ftp')->get($path);
    }

    $lines = explode("\n", $file);
    $result = [];

    // Prevent automatic escaping of forward slashes
    $string = json_encode($lines, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Clean and parse
    $cleaned = trim($string, '[]');
    $items = explode('","', $cleaned);

    $headers = explode('~', trim($items[0], '"'));
    $result = array();

    for ($i = 1; $i < count($items); $i++) {
      $item = trim($items[$i], '"');
      if ($item === 'ENDFILE') continue;

      $values = explode('~', $item);
      $result[] = array_combine($headers, $values);
    }

    // $this->coreFunctions->LogConsole(count($result));
    // $this->coreFunctions->LogConsole(json_encode($result));

    return $result;
  }

  function isDateField($columnName)
  {
    $dateFields = ['dateid', 'promostart', 'promoend', 'effectdate', 'dateupdated', 'warranty', 'bday', 'lock', 'hired', 'resigned', 'enddate', 'editdate', 'voiddate', 'bday2', 'viewdate', 'start', 'dlock', 'lasttrans'];
    return in_array(strtolower($columnName), $dateFields);
  }

  function getDateSQLValue($value)
  {
    // Common invalid date representations
    $invalidDates = ['',  '0', '0.000000', '0000-00-00', '0000-00-00 00:00:00', 'NULL', 'null'];

    if (in_array($value, $invalidDates)) {
      return 'NULL';
    }

    // Try to parse the date
    $timestamp = strtotime($value);
    if ($timestamp === false) {
      return 'NULL';
    }

    // Format for MySQL
    $mysqlDate = date('Y-m-d H:i:s', $timestamp);
    return "'$mysqlDate'";
  }
}// end class 