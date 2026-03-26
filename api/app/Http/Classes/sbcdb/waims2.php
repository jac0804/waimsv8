<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use Illuminate\Support\Str;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

use function PHPSTORM_META\type;

class waims2
{

  private $coreFunctions;
  private $companysetup;
  private $othersClass;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
  } //end fn


  public function tableupdatewaims2($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $this->coreFunctions->sbcaddcolumngrp(["cntnum", "transnum"], ["tmpuser", "appuser"], "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->execqrynolog("ALTER TABLE item CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE item DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");

    $this->coreFunctions->execqrynolog("ALTER TABLE execution_log CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE execution_log DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE execution_log CHANGE querystring querystring LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL");

    goto _20240917Here;

    $qry = " CREATE TABLE `qtinfo` (
            `trno` INT(11) NOT NULL DEFAULT '0',
            `itemid` INT(11) NOT NULL DEFAULT '0',
            `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
            `disc` varchar(40) NOT NULL DEFAULT '',
            `plateno` varchar(40) NOT NULL DEFAULT '',
            `leadtime` datetime DEFAULT NULL,
            `encodeddate` datetime DEFAULT NULL,
            `encodedby` varchar(20) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            `editby` varchar(20) NOT NULL DEFAULT '',
            `outdimlen` varchar(40) NOT NULL DEFAULT '',
            `outdimwd` varchar(40) NOT NULL DEFAULT '',
            `outdimht` varchar(40) NOT NULL DEFAULT '',
            `indimlen` varchar(40) NOT NULL DEFAULT '',
            `indimwd` varchar(40) NOT NULL DEFAULT '',
            `indimht` varchar(40) NOT NULL DEFAULT '',
            `chassiswd` varchar(100) NOT NULL DEFAULT '',
            `underchassis` varchar(100) NOT NULL DEFAULT '',
            `secchassisqty` varchar(40) NOT NULL DEFAULT '',
            `secchassissz` varchar(40) NOT NULL DEFAULT '',
            `secchassistk` varchar(40) NOT NULL DEFAULT '',
            `secchassismat` varchar(100) NOT NULL DEFAULT '',
            `flrjoistqty` varchar(40) NOT NULL DEFAULT '',
            `flrjoistqtysz` varchar(40) NOT NULL DEFAULT '',
            `flrjoistqtytk` varchar(40) NOT NULL DEFAULT '',
            `flrjoistqtymat` varchar(100) NOT NULL DEFAULT '',
            `flrtypework` varchar(100) NOT NULL DEFAULT '',
            `flrtypeworktk` varchar(40) NOT NULL DEFAULT '',
            `flrtypeworkty` varchar(40) NOT NULL DEFAULT '',
            `flrtypeworkmat` varchar(100) NOT NULL DEFAULT '',
            `exttypework` varchar(100) NOT NULL DEFAULT '',
            `exttypeworkqty` varchar(40) NOT NULL DEFAULT '',
            `exttypeworkty` varchar(100) NOT NULL DEFAULT '',
            `inwalltypework` varchar(100) NOT NULL DEFAULT '',
            `inwalltypeworkqty` varchar(40) NOT NULL DEFAULT '',
            `inwalltypeworktk` varchar(40) NOT NULL DEFAULT '',
            `inwalltypeworkty` varchar(100) NOT NULL DEFAULT '',
            `inceiltypework` varchar(100) NOT NULL DEFAULT '',
            `inceiltypeworkqty` varchar(40) NOT NULL DEFAULT '',
            `inceiltypeworktk` varchar(40) NOT NULL DEFAULT '',
            `inceiltypeworkty` varchar(100) NOT NULL DEFAULT '',
            `insultk` varchar(100) NOT NULL DEFAULT '',
            `insulty` varchar(100) NOT NULL DEFAULT '',
            `reardrstype` varchar(100) NOT NULL DEFAULT '',
            `reardrslock` varchar(40) NOT NULL DEFAULT '',
            `reardrshinger` varchar(40) NOT NULL DEFAULT '',
            `reardrsseals` varchar(100) NOT NULL DEFAULT '',
            `reardrsrem` varchar(100) NOT NULL DEFAULT '',
            `sidedrstype` varchar(100) NOT NULL DEFAULT '',
            `sidedrslock` varchar(40) NOT NULL DEFAULT '',
            `sidedrshinger` varchar(40) NOT NULL DEFAULT '',
            `sidedrsseals` varchar(100) NOT NULL DEFAULT '',
            `sidedrsrem` varchar(100) NOT NULL DEFAULT '',
            `normlights` varchar(40) NOT NULL DEFAULT '',
            `lightsrepair` varchar(40) NOT NULL DEFAULT '',
            `upclrlights` varchar(100) NOT NULL DEFAULT '',
            `lowclrlights` varchar(100) NOT NULL DEFAULT '',
            `clrlightsrepair` varchar(40) NOT NULL DEFAULT '',
            `paintcover` varchar(100) NOT NULL DEFAULT '',
            `bodycolor` varchar(100) NOT NULL DEFAULT '',
            `flrcolor` varchar(100) NOT NULL DEFAULT '',
            `unchassiscolor` varchar(100) NOT NULL DEFAULT '',
            `paintroof` varchar(100) NOT NULL DEFAULT '',
            `exterior` varchar(100) NOT NULL DEFAULT '',
            `interior` varchar(100) NOT NULL DEFAULT '',
            `sideguards` varchar(100) NOT NULL DEFAULT '',
            `reseal` varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY (trno),
            KEY IndexTrno (trno),
            KEY IndexItemID (itemid)
            ) ENGINE = MYISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("qtinfo", $qry);

    $qry = " CREATE TABLE `hqtinfo` (
            `trno` INT(11) NOT NULL DEFAULT '0',
            `itemid` INT(11) NOT NULL DEFAULT '0',
            `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
            `disc` varchar(40) NOT NULL DEFAULT '',
            `plateno` varchar(40) NOT NULL DEFAULT '',
            `leadtime` datetime DEFAULT NULL,
            `encodeddate` datetime DEFAULT NULL,
            `encodedby` varchar(20) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            `editby` varchar(20) NOT NULL DEFAULT '',
            `outdimlen` varchar(40) NOT NULL DEFAULT '',
            `outdimwd` varchar(40) NOT NULL DEFAULT '',
            `outdimht` varchar(40) NOT NULL DEFAULT '',
            `indimlen` varchar(40) NOT NULL DEFAULT '',
            `indimwd` varchar(40) NOT NULL DEFAULT '',
            `indimht` varchar(40) NOT NULL DEFAULT '',
            `chassiswd` varchar(100) NOT NULL DEFAULT '',
            `underchassis` varchar(100) NOT NULL DEFAULT '',
            `secchassisqty` varchar(40) NOT NULL DEFAULT '',
            `secchassissz` varchar(40) NOT NULL DEFAULT '',
            `secchassistk` varchar(40) NOT NULL DEFAULT '',
            `secchassismat` varchar(100) NOT NULL DEFAULT '',
            `flrjoistqty` varchar(40) NOT NULL DEFAULT '',
            `flrjoistqtysz` varchar(40) NOT NULL DEFAULT '',
            `flrjoistqtytk` varchar(40) NOT NULL DEFAULT '',
            `flrjoistqtymat` varchar(100) NOT NULL DEFAULT '',
            `flrtypework` varchar(100) NOT NULL DEFAULT '',
            `flrtypeworktk` varchar(40) NOT NULL DEFAULT '',
            `flrtypeworkty` varchar(40) NOT NULL DEFAULT '',
            `flrtypeworkmat` varchar(100) NOT NULL DEFAULT '',
            `exttypework` varchar(100) NOT NULL DEFAULT '',
            `exttypeworkqty` varchar(40) NOT NULL DEFAULT '',
            `exttypeworkty` varchar(100) NOT NULL DEFAULT '',
            `inwalltypework` varchar(100) NOT NULL DEFAULT '',
            `inwalltypeworkqty` varchar(40) NOT NULL DEFAULT '',
            `inwalltypeworktk` varchar(40) NOT NULL DEFAULT '',
            `inwalltypeworkty` varchar(100) NOT NULL DEFAULT '',
            `inceiltypework` varchar(100) NOT NULL DEFAULT '',
            `inceiltypeworkqty` varchar(40) NOT NULL DEFAULT '',
            `inceiltypeworktk` varchar(40) NOT NULL DEFAULT '',
            `inceiltypeworkty` varchar(100) NOT NULL DEFAULT '',
            `insultk` varchar(100) NOT NULL DEFAULT '',
            `insulty` varchar(100) NOT NULL DEFAULT '',
            `reardrstype` varchar(100) NOT NULL DEFAULT '',
            `reardrslock` varchar(40) NOT NULL DEFAULT '',
            `reardrshinger` varchar(40) NOT NULL DEFAULT '',
            `reardrsseals` varchar(100) NOT NULL DEFAULT '',
            `reardrsrem` varchar(100) NOT NULL DEFAULT '',
            `sidedrstype` varchar(100) NOT NULL DEFAULT '',
            `sidedrslock` varchar(40) NOT NULL DEFAULT '',
            `sidedrshinger` varchar(40) NOT NULL DEFAULT '',
            `sidedrsseals` varchar(100) NOT NULL DEFAULT '',
            `sidedrsrem` varchar(100) NOT NULL DEFAULT '',
            `normlights` varchar(40) NOT NULL DEFAULT '',
            `lightsrepair` varchar(40) NOT NULL DEFAULT '',
            `upclrlights` varchar(100) NOT NULL DEFAULT '',
            `lowclrlights` varchar(100) NOT NULL DEFAULT '',
            `clrlightsrepair` varchar(40) NOT NULL DEFAULT '',
            `paintcover` varchar(100) NOT NULL DEFAULT '',
            `bodycolor` varchar(100) NOT NULL DEFAULT '',
            `flrcolor` varchar(100) NOT NULL DEFAULT '',
            `unchassiscolor` varchar(100) NOT NULL DEFAULT '',
            `paintroof` varchar(100) NOT NULL DEFAULT '',
            `exterior` varchar(100) NOT NULL DEFAULT '',
            `interior` varchar(100) NOT NULL DEFAULT '',
            `sideguards` varchar(100) NOT NULL DEFAULT '',
            `reseal` varchar(100) NOT NULL DEFAULT '',
            PRIMARY KEY (trno),
            KEY IndexTrno (trno),
            KEY IndexItemID (itemid)
            ) ENGINE = MYISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hqtinfo", $qry);

    $this->coreFunctions->sbcaddcolumn("qtinfo", "leadtime2", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqtinfo", "leadtime2", "int(11) NOT NULL DEFAULT '0'");

    //addon type; 0 - Floor addons, 1 - interior, 2 - accessories, 2 - structural, 3 - notes
    $qry = "CREATE TABLE `qtaddons` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `trno` INT(11) NOT NULL DEFAULT '0',
            `addons` varchar(500) NOT NULL DEFAULT '',
            `rem` varchar(1000) NOT NULL DEFAULT '',
            `qty` varchar(50) NOT NULL DEFAULT '',
            `side` varchar(100) NOT NULL DEFAULT '',
            `parts` varchar(100) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `addontype` INT(2) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`),
            KEY `IndexTrnoLine` (`line`, `trno`),
            KEY `IndexAddonType` (`addontype`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("qtaddons", $qry);

    $qry = "CREATE TABLE `hqtaddons` (
            `line` int(11) unsigned NOT NULL,
            `trno` INT(11) NOT NULL DEFAULT '0',
            `addons` varchar(500) NOT NULL DEFAULT '',
            `rem` varchar(1000) NOT NULL DEFAULT '',
            `qty` varchar(50) NOT NULL DEFAULT '',
            `side` varchar(100) NOT NULL DEFAULT '',
            `parts` varchar(100) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `addontype` INT(2) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`),
            KEY `IndexTrnoLine` (`line`, `trno`),
            KEY `IndexAddonType` (`addontype`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hqtaddons", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["item"], ["noncomm", "isnsi"], "TINYINT(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["item"], ["isnonserial"], "TINYINT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['item'], ["mmtrno", "barcodeid", "clientid", "itemseq"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["item"], ["delcharge"], "DECIMAL(18,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['item'], ['amt16'], "decimal(19,6) not null default '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['item'], ['disc16', 'disc17', 'disc18', 'disc19', 'disc20', 'disc21', 'disc22'], "varchar(45) not null default ''", 1);

    if ($config['params']['companyid'] == 16) { //ati
      $this->coreFunctions->sbcaddcolumn("item", "subcode", "VARCHAR(1000) NOT NULL DEFAULT ''");
      $this->coreFunctions->sbcaddcolumngrp(["item", "tempitem"], ["othcode"], "VARCHAR(300) NOT NULL DEFAULT ''");
      $this->coreFunctions->sbcaddcolumngrp(["tempitem"], ["rem"], "VARCHAR(500) NOT NULL DEFAULT ''");
      $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["purpose"], "VARCHAR(1000) NOT NULL DEFAULT ''");
      $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["iscleared"], "TINYINT(2) NOT NULL DEFAULT '0'");
      $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans"], ["cvref", "osiref", "ocrref", "osiref2", "rrref"], "VARCHAR(100) NOT NULL DEFAULT ''");
      $this->coreFunctions->sbcaddcolumngrp(["hstockinfotrans"], ["cvref", "osiref", "ocrref", "osiref2", "rrref"], "VARCHAR(100) NOT NULL DEFAULT ''");
    }

    $this->coreFunctions->sbcaddcolumn("incentives", "podate", "datetime");
    $this->coreFunctions->sbcaddcolumn("incentives", "isusd", "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["incentives"], ["projectid", "usdline"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("incentives", "netamt", "DECIMAL(18,2) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["incentives"], ["usdamt", "rate", "gp"], "decimal(18,6) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["incentives"], ["ref", "poref", "invno"], "varchar(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["srstock", "hsrstock"], ["delcharge"], "DECIMAL(18,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["srstock", "hsrstock"], ["sortline"], "INT(11) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumngrp(["hqthead"], ["sotrno"], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["qthead", "hqthead"], ["markup", "tax"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["qthead", "hqthead"], ["model", "brand"], "varchar(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["qtstock", "hqtstock"], ["sortline"], "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["sohead", "hsohead"], ["statid"], "INT(3) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["sohead", "hsohead"], ['phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["sohead", "hsohead"], ["sano", "pono", "tax"], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["sohead", "hsohead"], ["mino", "mrno"], "VARCHAR(20) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("sohead", "shipto", "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["sostock", "hsostock"], ["noprint"], "tinyint(2) unsigned not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["sostock", "hsostock"], ['projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["sostock", "hsostock"], ["roqa", "tsqa", "weight", "weight2", "agentamt"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 1);
    $this->coreFunctions->sbcaddcolumn("hsostock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);

    if ($config['params']['companyid'] == 39) { //CBBSI
      $this->coreFunctions->sbcaddcolumngrp(["sostock", "hsostock"], ['amt'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 1);
    }

    $this->coreFunctions->sbcaddcolumngrp(
      ["cntnuminfo", "hcntnuminfo"],
      ["pdeadline", "packdate", "reservationdate", "sdate1", "sdate2", "tripdate", "expirydate", "shipdate", "ordate", "recondate", "printdate"],
      "DATETIME DEFAULT NULL",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["ispartial", "isapproved", "isreturned", "isrefunded", 'isdeductible'], "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["reportedby", "reportedby2"], "int(10) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(
      ["cntnuminfo", "hcntnuminfo"],
      ["itemid", "dropoffwh", "incidentid", "driverid", "helperid", "interestrate", "finterestrate", "termsmonth", "termspercentdp", "termsyear", "dueday", "cptrno", "jotrno", "depcr", "depdb", "whfromid", "whtoid"],
      "int(11) NOT NULL DEFAULT '0'",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(
      ["cntnuminfo", "hcntnuminfo"],
      ["weightin", "weightout", "haulerrate", "freight", "interestrate", "kilo", "commamt", "commvat", "weight", "valamt", "cumsmt", "delivery"],
      "DECIMAL(18,2) NOT NULL DEFAULT '0'",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["rebate", "bal"], "decimal(18,4) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(
      ["cntnuminfo", "hcntnuminfo"],
      ["batchsize", "yield", "downpayment", "termspercent", "reservationfee", "farea", "fpricesqm", "ftcplot", "ftcphouse", "fsellingpricegross", "fdiscount", "fsellingpricenet", "fcontractprice", "fmiscfee", "fmonthlydp", "fmonthlyamortization", "ffi", "fmri", "fma1", "fma2", "fma3", "loanamt", "mktg", "dc", "bo", "card", "openingintro", "e2e", "rebate", "rtv"],
      "decimal(18,6) NOT NULL DEFAULT '0.000000'",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["instructions"], "text NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["penalty"], "VARCHAR(5) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(
      ["cntnuminfo", "hcntnuminfo"],
      ["uom2", "batchno", "cwano", "cwatime", "weightintime", "weightouttime", "assignedlane"],
      "varchar(10) NOT NULL DEFAULT ''",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["plateno", "transtype"], "varchar(20) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["licenseno", "hauler", "voyageno", "sealno", "unit"], "varchar(30) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["lotno", "trnxtype", "orno", "odometer"], "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['cntnuminfo', 'hcntnuminfo'], ['strdate1', 'strdate2'], "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["carrier", "waybill"], "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["haulersupplier"], "varchar(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ['loadedby', 'vessel'], "varchar(300) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["rem2", "rem3"], "varchar(500) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["oqhead", "hoqhead"], ["invnotrequired", "subinv"], "TINYINT(2) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumngrp(["oqstock", "hoqstock"], ["isexisted"], "TINYINT(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["oqstock", "hoqstock"], ["ispa", "ispa2"], "TINYINT(1) NOT NULL DEFAULT '1'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["oqstock", "hoqstock"], ["priolvl"], "INT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["oqstock", "hoqstock"], ["reqtrno", "reqline"], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["oqstock", "hoqstock"], ["sono", "rtno"], "VARCHAR(30) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["omstock", "homstock"], ["rrdate"], "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumngrp(["omstock", "homstock"], ["priolvl", "statid"], "INT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["omstock", "homstock"], ["rrby"], "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["omstock", "homstock"], ["oraclecode"], "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ["isexpedite"], "tinyint(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ['phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ["pitrno"], "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ['itemid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ["uom"], "varchar(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ["qty"], "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ["color"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ["wh"], "VARCHAR(30) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["prhead", "hprhead"], ["qa"], "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["hprstock"], ["isoq", "isforrr", "isadv"], "tinyint(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prstock", "hprstock"], ['projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prstock", "hprstock"], ['sortline'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prstock", "hprstock"], ["voidqty", "oqqa"], "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["hprstock"], ["rrqa", "poqa", "tsqa"], "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["prstock", "hprstock"], ["maxqty"], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["vshead", "hvshead", "vthead", "hvthead"], ["newpo"], "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["vshead", "hvshead", "vthead", "hvthead"], ["reason"], "varchar(500) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE  `commdetails` (
          `ptrno` int(10) unsigned NOT NULL DEFAULT '0',
          `trno` int(10) unsigned NOT NULL DEFAULT '0',
          `line` int(10) unsigned NOT NULL DEFAULT '0',
          `projectid` int(10) unsigned NOT NULL DEFAULT '0',
          `agentid` int(10) unsigned NOT NULL DEFAULT '0',
          `overrideid` int(10) unsigned NOT NULL DEFAULT '0',
          `pheadid` int(10) unsigned NOT NULL DEFAULT '0',
          `gp` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `comrate` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `comamt` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `overridecomm` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `pheadcomm` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `releaseddate` datetime DEFAULT NULL,
          `releaseby` varchar(200) NOT NULL DEFAULT '',
          `delcharge` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `insurance` decimal(18,6) NOT NULL DEFAULT '0.000000',
          KEY `Index_1` (`ptrno`,`trno`,`line`),
          KEY `Index_2` (`projectid`,`agentid`,`overrideid`,`pheadid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("commdetails", $qry);

    $this->coreFunctions->sbcaddcolumn("commdetails", "usdline", "int(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE  othermaster (
          `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
          `clientid` int(10) UNSIGNED not null default '0',
          `businessnature` varchar(50) NOT NULL DEFAULT '',
          `isbusinessnature` TINYINT(1) NOT NULL DEFAULT '0',
          `paymenttype` varchar(50) NOT NULL DEFAULT '',
          `ispaymenttype` TINYINT(1) NOT NULL DEFAULT '0',
          `createdate` DATETIME DEFAULT NULL,
          `createby` varchar(50) NOT NULL DEFAULT '', 
          `editdate` DATETIME DEFAULT NULL,
          `editby` varchar(50) NOT NULL DEFAULT '', 
          PRIMARY KEY (`line`),
          KEY IndexLine (`line`),
          KEY IndexIsBusinessnature (`isbusinessnature`),
          KEY IndexIsPaymenttypee (`ispaymenttype`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("othermaster", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["cdhead", "hcdhead"], ["address"], "varchar(300) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["cdstock", "hcdstock"], ["isprefer", "waivedqty"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cdstock", "hcdstock"], ['catid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cdstock", "hcdstock"], ["rrqty2"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cdstock", "hcdstock"], ["oqqa", "rrqty3"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cdstock", "hcdstock"], ["rem"], "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["hcdstock"], ["voidqty", "oqpa"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 1);

    $this->coreFunctions->sbcaddcolumngrp(
      ["headinfotrans", "hheadinfotrans"],
      ["deadline", "pdeadline", "sentdate", "pickupdate", "printdate", "checkdate", "releasetoap", "loaddate"],
      "DATETIME DEFAULT NULL",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ['isinvoice', 'isro'], "TINYINT(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(
      ["headinfotrans", "hheadinfotrans"],
      ["itemid", "paymentid", "reqtypeid", "truckid", "driverid", "helperid", "checkerid", "assessedid", "classid", "brandid", "groupid", "categoryid", "partid", "subcategoryid", "clientid"],
      "INT(11) NOT NULL DEFAULT '0'",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(['headinfotrans', 'hheadinfotrans'], ['declaredval'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ["isshipmentnotif"], "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ["plateno"], "varchar(20) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ['trnxtype', "wh2", "carrier", "waybill"], "VARCHAR(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ["mop1", "mop2"], "varchar(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ["sizeid", "sdate1", "sdate2", "strdate1", "strdate2", "gendercaller"], "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ['approvalreason'], "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ["shipmentnotif", "nodays", "mileage"], "varchar(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ["rem2"], "varchar(500) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ['wbdate'], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["waivedspecs"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['stockinfo', 'hstockinfo'], ['isselected2'], "tinyint(2) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['stockinfo'], ['isselected'], "tinyint(2) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['stockinfo', 'hstockinfo'], ['isapproved'], "TINYINT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["isbo"], "tinyint(3) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['stockinfo', 'hstockinfo'], ['status1', 'status2', 'checkstat'], "int(4) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ['consignid', 'olditemid', 'paytrno'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["qty1", "qty2", "tqty", "weight", "weight2"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ['unit'], "varchar(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["ctrlno"], "varchar(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["serialno"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["payrem"], "varchar(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["itemdesc"], "varchar(1000) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["hstockinfotrans"], ["podeadline"], "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["reqdate", "releasedate", "prevdate"], "DATETIME DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["waivedqty", "waivedspecs"], "tinyint(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['stockinfotrans', 'hstockinfotrans'], ['isselected'], "tinyint(2) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['stockinfotrans', 'hstockinfotrans'], ['olditemid'], "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["prevamt"], "decimal(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["isasset"], "varchar(5) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["color", "ctrlno"], "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["itemdesc2", "specs2"], "varchar(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["hstockinfotrans"], ["carem", "acrem"], "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["specs", "specs2", "itemdesc"], "varchar(1000) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->execqrynolog("ALTER TABLE stockinfotrans CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE stockinfotrans DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE stockinfotrans CHANGE rem rem TEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL");
    $this->coreFunctions->execqrynolog("ALTER TABLE hstockinfotrans CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE hstockinfotrans DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE hstockinfotrans CHANGE rem rem TEXT CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL");

    $this->coreFunctions->sbcaddcolumngrp(["pohead", "hpohead"], ['phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pohead", "hpohead"], ["wh"], "VARCHAR(30) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["pohead", "hpohead"], ["ewt", "ewtrate"], "VARCHAR(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["pohead", "hpohead"], ["address", "addr"], "varchar(300) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["postock", "hpostock"], ["isadv", "isreturn"], "TINYINT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hpostock", "cvtrno", "int(10) unsigned NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["postock", "hpostock"], ['phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["postock", "hpostock"], ["rramt"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumn("hpostock", "paid", "decimal(18,4) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["hpostock"], ["rramt", "diqa", "voidqty"], "DECIMAL (18,6) NOT NULL DEFAULT '0.000000'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["postock", "hpostock"], ["rem"], "VARCHAR(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["postock", "hpostock"], ['sjrefx', 'sjlinex'], "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["uom"], ["isdefault2", "issales", "issalesdef"], "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["uom"], ["amt2", "famt"], "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'"); // prices based on uom price group

    $this->coreFunctions->sbcaddcolumngrp(["pchead", "hpchead"], ['projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["pcstock", "hpcstock"], ['projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pcstock", "hpcstock"], ['sortline'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pcstock", "hpcstock"], ['asofqty'], "decimal(18,2) NOT NULL DEFAULT '0.00'", 1);

    $this->coreFunctions->sbcaddcolumngrp(
      ["client"],
      ["ispickupdate", "nonsaleable", "nocomm", "isassetwh", "iscis", "isleader", "isfp", "industryid", "isbusiness", "isallowliquor", "issenior", "isdownloaded"],
      "TINYINT(1) NOT NULL DEFAULT '0'",
      1
    );
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['ewtrate'], "int(10) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['dropoffwh', 'customerid'], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("client", "areacode", "varchar(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['ewt'], "VARCHAR(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn('client', 'region', "varchar(60) not null default ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["client"], ["regnum", "mobile", "pword", "pemail", "shipto", "brgy"], "VARCHAR(100) NOT NULL DEFAULT ''", 1);


    switch ($config['params']['companyid']) {
      case 10: //afti
      case 12: //afti usd
        $this->coreFunctions->sbcaddcolumn("client", "rem", "mediumtext",  1);
        $this->coreFunctions->sbcaddcolumn("rfhead", "email", "varchar(150) NOT NULL DEFAULT ''", 1);
        $this->coreFunctions->sbcaddcolumn("hrfhead", "email", "varchar(150) NOT NULL DEFAULT ''", 1);
        break;
      case 19: //housegem
        $this->coreFunctions->sbcaddcolumn("client", "owner", "varchar(100) NOT NULL DEFAULT ''");
        break;
    }

    $this->coreFunctions->execqrynolog("ALTER TABLE client CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE client DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE client CHANGE clientname clientname VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE  `cvitems` (
          `trno` int(10) unsigned NOT NULL DEFAULT '0',
          `line` int(10) unsigned NOT NULL DEFAULT '0',
          `refx` int(10) unsigned NOT NULL DEFAULT '0',
          `linex` int(10) unsigned NOT NULL DEFAULT '0',
          KEY `Index_1` (`trno`,`line`),
          KEY `Index_2` (`refx`,`linex`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cvitems", $qry);

    $qry = "CREATE TABLE  `hcvitems` (
          `trno` int(10) unsigned NOT NULL DEFAULT '0',
          `line` int(10) unsigned NOT NULL DEFAULT '0',
          `refx` int(10) unsigned NOT NULL DEFAULT '0',
          `linex` int(10) unsigned NOT NULL DEFAULT '0',
          KEY `Index_1` (`trno`,`line`),
          KEY `Index_2` (`refx`,`linex`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hcvitems", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["cvitems", "hcvitems"], ['isapproved', 'ispartialpaid'], "TINYINT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cvitems", "hcvitems"], ["cdrefx", "cdlinex", "acnoid"], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cvitems", "hcvitems"], ["surcharge"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["qsstock", "hqsstock"], ["sortline"], "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "isdp", "tinyint(1) NOT NULL DEFAULT '0'"); // 2023.08.15 [FRED] - error in creating ladetail trigger missing isdp column
    $this->coreFunctions->sbcaddcolumngrp(["ladetail"], ["isar"], "tinyint(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["gldetail"], ["mctrno"], "int(11) not null default '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ["isexcept", "isnoedit"], "tinyint(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ["phaseid", "modelid", "blklotid"], "integer NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ['amenityid', 'subamenityid', 'rctrno', 'rcline'], "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ["isexcess", "mcrefx", "mclinex"], "TINYINT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ['appamt'], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'");
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ["type"], "VARCHAR(10) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ["rcchecks"], "VARCHAR(25) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `temptrans` (
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `amount` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `cost` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `dateid` DATETIME DEFAULT NULL,
      `client` VARCHAR(45) NOT NULL DEFAULT '',
      `tax` VARCHAR(25) NOT NULL DEFAULT '',
      `wtax` VARCHAR(15) NOT NULL DEFAULT '',
      `ref` VARCHAR(45) NOT NULL DEFAULT ''
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("temptrans", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["temptrans"], ["iscancel", "isupdate"], "tinyint NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["temptrans"], ["reftrno", "trno"], "int NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("temptrans", "doc", "varchar(5) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `tempclient` (
      `code` VARCHAR(45) NOT NULL DEFAULT '',
      `name` VARCHAR(150) NOT NULL DEFAULT '',
      `address` VARCHAR(250) NOT NULL DEFAULT '',
      `tin` VARCHAR(15) NOT NULL DEFAULT '',
      PRIMARY KEY (`code`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("tempclient", $qry);

    $qry = "CREATE TABLE  `bom` (
          `itemid` int(10) unsigned NOT NULL DEFAULT '0',
          `bclientid` int(10) unsigned NOT NULL DEFAULT '0',
          `bclientname` VARCHAR(45) NOT NULL DEFAULT '',
          `uom2` VARCHAR(10) NOT NULL DEFAULT '',
          `dateid` DATETIME DEFAULT NULL,
          `batchsize` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `yield` decimal(18,6) NOT NULL DEFAULT '0.000000',
          `rem` VARCHAR(1000) NOT NULL DEFAULT '',
          `editdate` DATETIME DEFAULT NULL,
          `editby` VARCHAR(45) NOT NULL DEFAULT '',
          KEY `Index_Itemid` (`itemid`),
          KEY `Index_Clientid` (`bclientid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("bom", $qry);

    $this->coreFunctions->sbcaddcolumn("bom", "bclientname", "varchar(150) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumngrp(["lqstock", "hlqstock"], ["deptid"], "INT NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lqstock", "hlqstock"], ["istranspo"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["projectmasterfile"], ["surcharge", "empid", "paygroupid"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["projectmasterfile"], ["rate", "minimum", "reconfee"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "tin", "varchar(15) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "groupid", "varchar(25) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "color", "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "address", "varchar(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "isinactive", "tinyint(1) NOT NULL DEFAULT '0'");

    // JIKS - 01.19.2023
    $qry = "CREATE TABLE `itemgroupqouta` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `yr` int(4) NOT NULL DEFAULT '0',
    `projectid` int(11) NOT NULL DEFAULT '0',
    `amt` decimal(19,6) unsigned NOT NULL DEFAULT '0.000000',
    `editby` varchar(15) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    PRIMARY KEY (`line`,`projectid`)
    )
    ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("itemgroupqouta", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["detailinfo", "hdetailinfo"], ["paymentdate"], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(["detailinfo", "hdetailinfo"], ["ortrno"], "integer NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(
      ["detailinfo", "hdetailinfo"],
      ["fi", "mri", "interest", "principal", "lotbal", "housebal", "hlbal", "payment", "principalcol", "percentage"],
      "decimal(18,6) NOT NULL DEFAULT '0'",
      0
    );
    $this->coreFunctions->sbcaddcolumngrp(["detailinfo", "hdetailinfo"], ["checkno"], "varchar(25) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["detailinfo", "hdetailinfo"], ["ref", "si1", "si2", "justify"], "varchar(45) NOT NULL DEFAULT ''", 0);

    // JIKS - 01.21.2023
    $qry = "CREATE TABLE `salesgroupqouta` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `yr` int(4) NOT NULL DEFAULT '0',
    `projectid` int(11) NOT NULL DEFAULT '0',
    `amt` decimal(19,6) unsigned NOT NULL DEFAULT '0.000000',
    `agentid` int(11) NOT NULL DEFAULT '0',
    `editby` varchar(15) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    PRIMARY KEY (`line`,`projectid`, `agentid`)
    )
    ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("salesgroupqouta", $qry);

    // JIKS [01.31.2023] add costcode masterfile
    $qry = "CREATE TABLE `costcode_masterfile` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(20) NOT NULL DEFAULT '',
      `name` varchar(150) NOT NULL DEFAULT '',
      `editby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("costcode_masterfile", $qry);

    $this->coreFunctions->execqrynolog("ALTER TABLE employee CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE employee DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE employee CHANGE emplast emplast VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");
    $this->coreFunctions->execqrynolog("ALTER TABLE employee CHANGE empfirst empfirst VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");
    $this->coreFunctions->execqrynolog("ALTER TABLE employee CHANGE empmiddle empmiddle VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["editdate", "voiddate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["issameadd", "isbene", "ispf", "isdp", "isotherid"], "TINYINT(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["planid", "sbuid"], "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["amount", "mincome", "mexp", "value", "monthly", "capacity"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("clientinfo", "zipcode", "VARCHAR(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["tin", "sssgsis"], "VARCHAR(15) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("clientinfo", "nationality", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("clientinfo", "contactno", "VARCHAR(25) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("clientinfo", "terms", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["bplace", "citizenship", "civilstatus", "father", "mother", "height", "weight", "editby"], "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    //  $this->coreFunctions->sbcaddcolumn("clientinfo", "purpose", "VARCHAR(50) NOT NULL DEFAULT ''",0);
    $this->coreFunctions->sbcaddcolumngrp(
      ["clientinfo"],
      ["purpose", "dependentsno", "country", "mmname", "sname", "ename", "yourref", "current1", "customername", "current2", "others2", "others1", "num", "pliss", "pcity", "bank1", "pcountry", "idno"],
      "VARCHAR(50) NOT NULL DEFAULT ''",
      0
    );
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["email", "pemail"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    //  $this->coreFunctions->sbcaddcolumn("clientinfo", "province", "VARCHAR(150) NOT NULL DEFAULT ''",0);
    $this->coreFunctions->sbcaddcolumngrp(
      ["clientinfo"],
      ["lname", "fname", "mname", "ext", "addressno", "street", "subdistown", "city", "zipcode", "address", "addressno", "province", "employer", "brgy", "subdistown", "city", "pprovince", "pob", "otherplan", "companyaddress", "street"],
      "VARCHAR(150) NOT NULL DEFAULT ''",
      1
    );

    $qry = "CREATE TABLE rohead like sohead";
    $this->coreFunctions->sbccreatetable("rohead", $qry);

    $qry = "CREATE TABLE hrohead like hsohead";
    $this->coreFunctions->sbccreatetable("hrohead", $qry);

    $qry = "CREATE TABLE rostock like sostock";
    $this->coreFunctions->sbccreatetable("rostock", $qry);

    $qry = "CREATE TABLE hrostock like hsostock";
    $this->coreFunctions->sbccreatetable("hrostock", $qry);

    $this->coreFunctions->sbcdropcolumn('rohead', 'truckid');
    $this->coreFunctions->sbcdropcolumn('rohead', 'plateno');
    $this->coreFunctions->sbcdropcolumn('rohead', 'helperid');

    $this->coreFunctions->sbcdropcolumn('hrohead', 'truckid');
    $this->coreFunctions->sbcdropcolumn('hrohead', 'plateno');
    $this->coreFunctions->sbcdropcolumn('hrohead', 'helperid');

    $this->coreFunctions->sbcaddcolumngrp(["rostock", "hrostock"], ["clientid"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["rostock", "hrostock"], ["weight"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["returndate", "refunddate", "checkdate", "refdate", "sdate1", "sdate2"], "DATETIME DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["isfinish", "istrip", "ista", "isnoentry"], "tinyint(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("lahead", "paytype", "tinyint(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumngrp(
      ["lahead", "glhead"],
      ["cmtrans", "excessrate", "fpid", "amenityid", "subamenityid", "cotrno", "aftrno"],
      "int(10) NOT NULL DEFAULT '0'",
      1
    );

    $this->coreFunctions->sbcaddcolumn("glhead", "catrno", "int NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["pdtrno", "costcodeid", "phaseid", "modelid", "blklotid", "rctrno", "rcline", "ajtrno", "empid", "pono", "modeofsales", "consigneeid", "shipperid", "petrno", "prdtrno"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["amount"], "decimal(18,6) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["whto"], "varchar(20) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["checkno"], "varchar(25) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["excess"], "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["freight", "agentfee", "strdate1", "strdate2"], "varchar(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(
      ["lahead", "glhead"],
      ["longitude", "latitude", "deviceid", "orderno", "rem2", "voidby"],
      "varchar(100) NOT NULL DEFAULT ''",
      1
    );
    $this->coreFunctions->sbcaddcolumn("lahead", "wh", "varchar(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ['crno',  'rfno',  'chsino', 'swsno'], "varchar(250) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ['conaddr', 'address'], "varchar(300) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcdropcolumngrp(["lahead", "glhead"], ['drno', 'csino']);

    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["isemail"], "tinyint(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ['projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["invid", "expid", "sorefx", "solinex", "ckrefx", "cklinex", "rtrefx", "rtlinex", "reasonid", "prrefx", "prlinex", "rrrefx", "rrlinex"], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["freight"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["isqty3", "prevqty", "ckqa", "poqa", "rrqa"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["color"], "varchar(20) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ['charges'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["color"], "varchar(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["johead", "hjohead"], ["stageid", "subproject"], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["johead", "hjohead"], ['sotrno', 'projectid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["johead", "hjohead"], ["wh"], "varchar(25) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["johead", "hjohead"], ["sku"], "varchar(30) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["johead", "hjohead"], ["terms"], "varchar(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["johead", "hjohead"], ["barcode"], "varchar(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["johead", "hjohead"], ["workloc"], "varchar(250) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["jostock", "hjostock"], ['prrefx', 'prlinex', 'sortline'], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["jostock", "hjostock"], ["rem"], "varchar(500) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `signatories` (
      `doc` varchar(100) NOT NULL DEFAULT '',
      `fieldname` varchar(50) NOT NULL DEFAULT '',
      `fieldvalue` varchar(50) NOT NULL DEFAULT '',
      `userid` varchar(50) NOT NULL DEFAULT '',
      KEY `Index_Doc` (`doc`),
      KEY `Index_UserID` (`userid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("signatories", $qry);

    $this->coreFunctions->sbcaddcolumn("phase", "projectid", "int(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `blklot` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `phaseid` int(11) NOT NULL DEFAULT '0',
      `projectid` int(11) NOT NULL DEFAULT '0',
      `blk` varchar(150) NOT NULL DEFAULT '',
      `lot` varchar(150) NOT NULL DEFAULT '',
      `isinactive` tinyint(1) NOT NULL DEFAULT '0',
      `clientid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("blklot", $qry);

    $this->coreFunctions->sbcaddcolumn("blklot", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("blklot", "modelid", "int(11) NOT NULL DEFAULT '0' after `projectid`", 1);
    $this->coreFunctions->sbcaddcolumn("blklot", "sqm", "decimal(19,6) NOT NULL DEFAULT '0.000000' after `lot` ", 1);
    $this->coreFunctions->sbcaddcolumn("blklot", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);

    /* JIKS 03.10.2023 */
    $qry = "CREATE TABLE `housemodel` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `phaseid` int(11) NOT NULL DEFAULT '0',
      `projectid` int(11) NOT NULL DEFAULT '0',
      `model` varchar(100) NOT NULL DEFAULT '',
      `price` decimal(19,6) NOT NULL DEFAULT '0.000000',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("housemodel", $qry);

    $this->coreFunctions->sbcaddcolumn("housemodel", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("housemodel", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);

    /* [JIKS] MARCH 20 2023 */
    $qry = "CREATE TABLE  `rchead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(20) DEFAULT NULL,
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(200) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `projectid` int(11) NOT NULL DEFAULT '0',
      `yourref` varchar(100) NOT NULL DEFAULT '',
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `checkno` varchar(25) NOT NULL DEFAULT '',
      `checkdate` datetime DEFAULT NULL,
      `agent` varchar(20) DEFAULT NULL,
      `amount` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_rchead` (`docno`,`client`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("rchead", $qry);

    $qry = "CREATE TABLE  `hrchead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(20) DEFAULT NULL,
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(200) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `projectid` int(11) NOT NULL DEFAULT '0',
      `yourref` varchar(100) NOT NULL DEFAULT '',
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `checkno` varchar(25) NOT NULL DEFAULT '',
      `checkdate` datetime DEFAULT NULL,
      `agent` varchar(20) DEFAULT NULL,
      `amount` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_hrchead` (`docno`,`client`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("hrchead", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["rchead", "hrchead"], ["phaseid", "modelid", "blklotid"], "int(11) NOT NULL DEFAULT '0'", 1);

    $qry = "CREATE TABLE  `rcdetail` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `checkno` varchar(20) NOT NULL DEFAULT '',
      `amount` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `checkdate` date DEFAULT NULL,
      `ortrno` bigint(20) NOT NULL DEFAULT '0',
      `orline` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(250) NOT NULL DEFAULT '',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE,
      KEY `Index_ortrno` (`ortrno`,`orline`),
      KEY `Index_checkdate` (`checkdate`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rcdetail", $qry);

    $qry = "CREATE TABLE  `hrcdetail` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `checkno` varchar(20) NOT NULL DEFAULT '',
      `amount` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `checkdate` date DEFAULT NULL,
      `ortrno` bigint(20) NOT NULL DEFAULT '0',
      `orline` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(250) NOT NULL DEFAULT '',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE,
      KEY `Index_ortrno` (`ortrno`,`orline`),
      KEY `Index_checkdate` (`checkdate`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrcdetail", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["hrcdetail", "rcdetail"], ["client", "bank", "branch"], "varchar(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["hrcdetail", "rcdetail"], ["rdtrno"], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["calllogs", "qscalllogs", "hqscalllogs"], ["status"], "varchar(50) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("agentquota", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("agentquota", "yr", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(
      ["agentquota"],
      ["janamt", "febamt", "maramt", "apramt", "mayamt", "junamt", "julamt", "augamt", "sepamt", "octamt", "novamt", "decamt"],
      "DECIMAL(18,6) NOT NULL DEFAULT '0'",
      0
    );
    $this->coreFunctions->sbcaddcolumn("agentquota", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);


    $this->coreFunctions->sbcaddcolumngrp(["bastock", "hbastock"], ["rrcost"], "DECIMAL(18,7) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["bastock", "hbastock"], ["ext"], "DECIMAL(18,7) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumngrp(["psubactivity"], ["rrcost"], "DECIMAL(18,7) NOT NULL DEFAULT '0'", 1);



    $this->coreFunctions->sbcaddcolumn("psubactivity", "ext", "DECIMAL(18,7) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "cost", "DECIMAL(18,7) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "totalcost", "DECIMAL(18,7) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumngrp(["issueitem"], ['numdays', 'month'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["issueitem"], ["ispermanent"], "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["issueitem"], ["returnby"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["issueitem"], ["returndate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["issueitem"], ["returnrem"], "varchar(1000) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["issueitem"], ["requesttype", "repairtype"], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("issueitem", "isrepair", "TINYINT(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["gphead", "hgphead"], ['deptid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["gphead", "hgphead"], ["isconsumable"], "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["gphead", "hgphead"], ["isrepair"], "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["standardsetup", "standardsetupadv"], ["camt"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'");

    $qry = "CREATE TABLE `assetgplogs` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `clientid` INT(11) NOT NULL DEFAULT '0',
            `itemid` INT(11) NOT NULL DEFAULT '0',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `dateid` datetime DEFAULT NULL,
            `type` varchar(3) NOT NULL DEFAULT '',
            PRIMARY KEY (`line`),
            KEY `IndexClientID` (`clientid`),
            KEY `IndexItemID` (`itemid`),
            KEY `IndexType` (`type`),
            KEY `IndexCreateBy` (`createby`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("assetgplogs", $qry);

    $this->coreFunctions->sbcaddcolumn("qshead", "terms", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqshead", "terms", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("component", "isloc", "TINYINT(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("cbledger", "releasedate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("cbledger", "releaseby", "VARCHAR(100) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE  `eahead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `fname` varchar(50) NOT NULL DEFAULT '',
      `mname` varchar(50) NOT NULL DEFAULT '',
      `lname` varchar(50) NOT NULL DEFAULT '',
      `ext` varchar(50) NOT NULL DEFAULT '',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',
      `addressno` varchar(50) NOT NULL DEFAULT '',
      `street` varchar(150) NOT NULL DEFAULT '',
      `subdistown` varchar(150) NOT NULL DEFAULT '',
      `city` varchar(50) NOT NULL DEFAULT '',
      `country` varchar(50) NOT NULL DEFAULT '',
      `zipcode` varchar(5) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `terms` varchar(30) NOT NULL DEFAULT '',
      `otherterms` varchar(30) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `voiddate` datetime DEFAULT NULL,
      `agent` varchar(20) NOT NULL DEFAULT '',
      `yourref` varchar(50) NOT NULL DEFAULT '', 
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `contactno` varchar(25) NOT NULL DEFAULT '',
      `contactno2` varchar(25) NOT NULL DEFAULT '',
      `email` varchar(50) NOT NULL DEFAULT '',
      `vattype` varchar(45) NOT NULL DEFAULT '',
      `lockuser` varchar(100) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(100) NOT NULL DEFAULT '',
      `users` varchar(100) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(100) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `planid` integer NOT NULL DEFAULT '0',
      `tax` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`client`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("eahead", $qry);


    $qry = "CREATE TABLE  `heahead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `fname` varchar(50) NOT NULL DEFAULT '',
      `mname` varchar(50) NOT NULL DEFAULT '',
      `lname` varchar(50) NOT NULL DEFAULT '',
      `ext` varchar(50) NOT NULL DEFAULT '',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',
      `addressno` varchar(50) NOT NULL DEFAULT '',
      `street` varchar(150) NOT NULL DEFAULT '',
      `subdistown` varchar(150) NOT NULL DEFAULT '',
      `city` varchar(50) NOT NULL DEFAULT '',
      `country` varchar(50) NOT NULL DEFAULT '',
      `zipcode` varchar(5) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `terms` varchar(30) NOT NULL DEFAULT '',
      `otherterms` varchar(30) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `voiddate` datetime DEFAULT NULL,
      `agent` varchar(20) NOT NULL DEFAULT '',
      `yourref` varchar(50) NOT NULL DEFAULT '', 
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `contactno` varchar(25) NOT NULL DEFAULT '',
      `contactno2` varchar(25) NOT NULL DEFAULT '',
      `email` varchar(50) NOT NULL DEFAULT '',
      `vattype` varchar(45) NOT NULL DEFAULT '',
      `lockuser` varchar(100) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(100) NOT NULL DEFAULT '',
      `users` varchar(100) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(100) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `planid` integer NOT NULL DEFAULT '0',
      `tax` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`client`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("heahead", $qry);

    $qry = "CREATE TABLE  `eainfo` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `fname` varchar(50) NOT NULL DEFAULT '',
      `mname` varchar(50) NOT NULL DEFAULT '',
      `lname` varchar(50) NOT NULL DEFAULT '',
      `ext` varchar(50) NOT NULL DEFAULT '',
      `isplanholder` tinyint(1) NOT NULL DEFAULT '0',
      `gender` varchar(10) NOT NULL DEFAULT '',
      `civilstat` varchar(15) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',
      `addressno` varchar(50) NOT NULL DEFAULT '',
      `street` varchar(150) NOT NULL DEFAULT '',
      `subdistown` varchar(150) NOT NULL DEFAULT '',
      `city` varchar(50) NOT NULL DEFAULT '',
      `country` varchar(50) NOT NULL DEFAULT '',
      `zipcode` varchar(5) NOT NULL DEFAULT '',      
      `paddress` varchar(150) NOT NULL DEFAULT '',
      `paddressno` varchar(50) NOT NULL DEFAULT '',
      `pstreet` varchar(150) NOT NULL DEFAULT '',
      `psubdistown` varchar(150) NOT NULL DEFAULT '',
      `pcity` varchar(50) NOT NULL DEFAULT '',
      `pcountry` varchar(50) NOT NULL DEFAULT '',
      `pzipcode` varchar(5) NOT NULL DEFAULT '',
      `bday` datetime default NULL,
      `pob` varchar(150) NOT NULL DEFAULT '',
      `nationality` varchar(20) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `ispassport` tinyint(1) NOT NULL DEFAULT '0',
      `isprc` tinyint(1) NOT NULL DEFAULT '0',
      `isdriverlisc` tinyint(1) NOT NULL DEFAULT '0',
      `isotherid` tinyint(1) NOT NULL DEFAULT '0',
      `idno` varchar(50) NOT NULL DEFAULT '',
      `expiration` varchar(10) NOT NULL DEFAULT '',
      `isemployment` tinyint(1) NOT NULL DEFAULT '0',
      `isinvestment` tinyint(1) NOT NULL DEFAULT '0',
      `isbusiness` tinyint(1) NOT NULL DEFAULT '0',
      `isothersource` tinyint(1) NOT NULL DEFAULT '0',
      `othersource` varchar(50) NOT NULL DEFAULT '',
      `isemployed` tinyint(1) NOT NULL DEFAULT '0',
      `isselfemployed` tinyint(1) NOT NULL DEFAULT '0',
      `isofw` tinyint(1) NOT NULL DEFAULT '0',
      `isretired` tinyint(1) NOT NULL DEFAULT '0',
      `iswife` tinyint(1) NOT NULL DEFAULT '0',
      `isnotemployed` tinyint(1) NOT NULL DEFAULT '0',
      `employer` varchar(150) NOT NULL DEFAULT '',
      `tin` varchar(15) NOT NULL DEFAULT '',
      `sssgsis` varchar(15) NOT NULL DEFAULT '',
      `lessten` tinyint(1) NOT NULL DEFAULT '0',
      `tenthirty` tinyint(1) NOT NULL DEFAULT '0',
      `thirtyfifty` tinyint(1) NOT NULL DEFAULT '0',
      `fiftyhundred` tinyint(1) NOT NULL DEFAULT '0',
      `hundredtwofifty` tinyint(1) NOT NULL DEFAULT '0',
      `twofiftyfivehundred` tinyint(1) NOT NULL DEFAULT '0',
      `fivehundredup` tinyint(1) NOT NULL DEFAULT '0',
      `otherplan` varchar(150) NOT NULL DEFAULT '',
      `amount` decimal(18,2) NOT NULL DEFAULT '0',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`clientname`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("eainfo", $qry);

    $qry = "CREATE TABLE  `heainfo` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `fname` varchar(50) NOT NULL DEFAULT '',
      `mname` varchar(50) NOT NULL DEFAULT '',
      `lname` varchar(50) NOT NULL DEFAULT '',
      `ext` varchar(50) NOT NULL DEFAULT '',
      `isplanholder` tinyint(1) NOT NULL DEFAULT '0',
      `gender` varchar(10) NOT NULL DEFAULT '',
      `civilstat` varchar(15) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',
      `addressno` varchar(50) NOT NULL DEFAULT '',
      `street` varchar(150) NOT NULL DEFAULT '',
      `subdistown` varchar(150) NOT NULL DEFAULT '',
      `city` varchar(50) NOT NULL DEFAULT '',
      `country` varchar(50) NOT NULL DEFAULT '',
      `zipcode` varchar(5) NOT NULL DEFAULT '',
      `paddress` varchar(150) NOT NULL DEFAULT '',
      `paddressno` varchar(50) NOT NULL DEFAULT '',
      `pstreet` varchar(150) NOT NULL DEFAULT '',
      `psubdistown` varchar(150) NOT NULL DEFAULT '',
      `pcity` varchar(50) NOT NULL DEFAULT '',
      `pcountry` varchar(50) NOT NULL DEFAULT '',
      `pzipcode` varchar(5) NOT NULL DEFAULT '',
      `bday` datetime default NULL,
      `pob` varchar(150) NOT NULL DEFAULT '',
      `nationality` varchar(20) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `ispassport` tinyint(1) NOT NULL DEFAULT '0',
      `isprc` tinyint(1) NOT NULL DEFAULT '0',
      `isdriverlisc` tinyint(1) NOT NULL DEFAULT '0',
      `isotherid` tinyint(1) NOT NULL DEFAULT '0',
      `idno` varchar(50) NOT NULL DEFAULT '',
      `expiration` varchar(10) NOT NULL DEFAULT '',
      `isemployment` tinyint(1) NOT NULL DEFAULT '0',
      `isinvestment` tinyint(1) NOT NULL DEFAULT '0',
      `isbusiness` tinyint(1) NOT NULL DEFAULT '0',
      `isothersource` tinyint(1) NOT NULL DEFAULT '0',
      `othersource` varchar(50) NOT NULL DEFAULT '',
      `isemployed` tinyint(1) NOT NULL DEFAULT '0',
      `isselfemployed` tinyint(1) NOT NULL DEFAULT '0',
      `isofw` tinyint(1) NOT NULL DEFAULT '0',
      `isretired` tinyint(1) NOT NULL DEFAULT '0',
      `iswife` tinyint(1) NOT NULL DEFAULT '0',
      `isnotemployed` tinyint(1) NOT NULL DEFAULT '0',
      `employer` varchar(150) NOT NULL DEFAULT '',
      `tin` varchar(15) NOT NULL DEFAULT '',
      `sssgsis` varchar(15) NOT NULL DEFAULT '',
      `lessten` tinyint(1) NOT NULL DEFAULT '0',
      `tenthirty` tinyint(1) NOT NULL DEFAULT '0',
      `thirtyfifty` tinyint(1) NOT NULL DEFAULT '0',
      `fiftyhundred` tinyint(1) NOT NULL DEFAULT '0',
      `hundredtwofifty` tinyint(1) NOT NULL DEFAULT '0',
      `twofiftyfivehundred` tinyint(1) NOT NULL DEFAULT '0',
      `fivehundredup` tinyint(1) NOT NULL DEFAULT '0',
      `otherplan` varchar(150) NOT NULL DEFAULT '',
      `amount` decimal(18,2) NOT NULL DEFAULT '0',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`clientname`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("heainfo", $qry);

    $qry = "CREATE TABLE  `beneficiary` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(10) NOT NULL DEFAULT '0',
      `name` varchar(100) NOT NULL,
      `age` int(10) NOT NULL,
      `address` varchar(150) NOT NULL,
      `relation` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("beneficiary", $qry);


    $qry = "CREATE TABLE `plantype` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45) NOT NULL DEFAULT '',
      `name` varchar(150) NOT NULL DEFAULT '',
      `amount` decimal(18,2) NOT NULL DEFAULT '0',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    )ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("plantype", $qry);

    $qry = "CREATE TABLE `powercat` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(150) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `groupid` varchar(100) NOT NULL DEFAULT '',
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    )ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("powercat", $qry);

    $qry = "CREATE TABLE `subpowercat` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `catid` int(10) unsigned DEFAULT '0',
      `name` varchar(150) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`),
      KEY `Index_CatID` (`catid`)
    )ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("subpowercat", $qry);

    $qry = "CREATE TABLE `subpowercat2` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `catid` int(10) unsigned DEFAULT '0',
      `subcatid` int(10) unsigned DEFAULT '0',
      `name` varchar(150) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`),
      KEY `Index_CatID` (`catid`),
      KEY `Index_SubCatID` (`subcatid`)
    )ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("subpowercat2", $qry);

    $this->coreFunctions->sbcaddcolumn("heahead", "catrno", "int NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ppio_series", "docno", "varchar(11) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("plantype", "editby", "varchar(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("plantype", "editdate", "DATETIME NULL ", 0);
    $this->coreFunctions->sbcaddcolumngrp(["plantype"], ['cash', 'annual', 'semi', 'monthly', 'quarterly', 'processfee'], "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `pwhead` (
          `trno` bigint(20) NOT NULL DEFAULT '0',
          `doc` char(2) NOT NULL DEFAULT '',
          `docno` char(20) NOT NULL,
          `dateid` datetime DEFAULT NULL,
          `rem` varchar(500) DEFAULT NULL,
          `voiddate` datetime DEFAULT NULL,
          `branch` varchar(30) DEFAULT NULL,
          `yourref` varchar(50) NOT NULL DEFAULT '',
          `ourref` varchar(25) NOT NULL DEFAULT '',
          `approvedby` varchar(100) NOT NULL DEFAULT '',
          `approveddate` datetime DEFAULT NULL,
          `printtime` datetime DEFAULT NULL,
          `lockuser` varchar(100) NOT NULL DEFAULT '',
          `lockdate` datetime DEFAULT NULL,
          `openby` varchar(100) NOT NULL DEFAULT '',
          `users` varchar(100) NOT NULL DEFAULT '',
          `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `createby` varchar(100) NOT NULL DEFAULT '',
          `editby` varchar(100) NOT NULL DEFAULT '',
          `editdate` datetime DEFAULT NULL,
          `viewby` varchar(100) NOT NULL DEFAULT '',
          `viewdate` datetime DEFAULT NULL,
          `pwrcat` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`trno`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pwhead", $qry);

    $qry = "CREATE TABLE `hpwhead` LIKE `pwhead`";
    $this->coreFunctions->sbccreatetable("hpwhead", $qry);

    $qry = "CREATE TABLE `pwstock` (
          `trno` bigint(20) NOT NULL DEFAULT '0',
          `line` int(11) NOT NULL,
          `uom` varchar(15) NOT NULL DEFAULT '',
          `disc` varchar(40) NOT NULL DEFAULT '',
          `rem` varchar(500) NOT NULL DEFAULT '',
          `amt` decimal(19,6) unsigned NOT NULL DEFAULT '0.000000',
          `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
          `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
          `void` tinyint(4) NOT NULL DEFAULT '0',
          `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `encodedby` varchar(100) NOT NULL DEFAULT '',
          `editdate` datetime DEFAULT NULL,
          `editby` varchar(100) NOT NULL DEFAULT '',
          `sortline` int(11) NOT NULL DEFAULT '0',
          `catid` int(11) NOT NULL DEFAULT '0',
          `subcat` int(11) NOT NULL DEFAULT '0',
          `subcat2` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`trno`,`line`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pwstock", $qry);

    $qry = "CREATE TABLE `hpwstock` LIKE `pwstock`";
    $this->coreFunctions->sbccreatetable("hpwstock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["pwstock", "hpwstock"], ["isqty2", "isqty3"], "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $qry = "CREATE TABLE  `plangrp` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `inactive` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(100) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("plangrp", $qry);

    $this->coreFunctions->sbcaddcolumn("plantype", "plangrpid", "INTEGER NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["issameadd", "isbene", "issenior", "isdp", "ispf", "isnf"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("plangrp", "bal", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["plangrpid"], "INTEGER NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbccreatetable("wnhead", "CREATE TABLE  wnhead LIKE pohead");
    $this->coreFunctions->sbccreatetable("hwnhead", "CREATE TABLE  hwnhead LIKE hpohead");
    $this->coreFunctions->sbcaddcolumngrp(["wnhead", "hwnhead"], ["disconndate", "conndate"], "datetime default NULL after dateid", 0);
    $this->coreFunctions->sbcaddcolumngrp(["wnhead", "hwnhead"], ["itemid"], "int(11) NOT NULL DEFAULT '0' after rem", 0);
    $this->coreFunctions->sbcaddcolumngrp(["wnhead", "hwnhead"], ["begqty"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumn("ratesetup", "doc", "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("ratesetup", "createdate", "datetime DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumngrp(["ratesetup"], ["createby", "remarks"], "varchar(150) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["province", "pprovince", "brgy", "pbrgy", "appref"], "varchar(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["province"], "varchar(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["email"], "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("center", "accountno", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("center", "billingclerk", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("eahead", "brgy", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("heahead", "brgy", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["dp", "pf", "nf"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ['entryfee', 'lrf', 'itfee', 'regfee', 'docstamp', 'nf2', 'nf3', 'ofee', 'annotationfee', 'docstamp1', 'articles', 'annotationexp', 'otransfer', 'rpt', 'handling', 'appraisal', 'filing', 'referral', 'cancellation4', 'cancellation7', 'annotationoc1', 'annotationoc2', 'cancellationu', 'mri'], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);

    $qry = "CREATE TABLE  `transref` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `rrtrno` int(10) unsigned NOT NULL DEFAULT '0',
      `rrline` int(10) unsigned NOT NULL DEFAULT '0',
      `rrqty` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      KEY `Index_TrNo` (`trno`),
      KEY `Index_Line` (`line`),
      KEY `Index_RRTrNo` (`rrtrno`),
      KEY `Index_RRLine` (`rrline`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("transref", $qry);

    $qry = "CREATE TABLE  `htransref` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `rrtrno` int(10) unsigned NOT NULL DEFAULT '0',
      `rrline` int(10) unsigned NOT NULL DEFAULT '0',
      `rrqty` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      KEY `Index_TrNo` (`trno`),
      KEY `Index_Line` (`line`),
      KEY `Index_RRTrNo` (`rrtrno`),
      KEY `Index_RRLine` (`rrline`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("htransref", $qry);


    $this->coreFunctions->sbcaddcolumngrp(["tenantinfo"], ["appdate", "postdate"], "DATETIME", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tenantinfo"], ["appby", "postedby"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tenantinfo"], ["trno"], "INT(10) NOT NULL DEFAULT 0", 0);


    $qry = "CREATE TABLE  `lphead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',      
      `dateid` datetime DEFAULT NULL,
      `start` datetime DEFAULT NULL,
      `enddate` datetime DEFAULT NULL,
      `category` varchar(30) NOT NULL DEFAULT '',
      `bstyle` varchar(30) NOT NULL DEFAULT '',
      `tin` varchar(15) NOT NULL DEFAULT '',
      `position` varchar(50) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `tel` varchar(25) NOT NULL DEFAULT '',
      `contact` varchar(150) NOT NULL DEFAULT '',
      `termcontract` varchar(250) NOT NULL DEFAULT '',
      `email` varchar(50) NOT NULL DEFAULT '',
      `escalation` varchar(150) NOT NULL DEFAULT '',
      `lockuser` varchar(100) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(100) NOT NULL DEFAULT '',
      `users` varchar(100) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(100) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `isnonvat` tinyint(1) NOT NULL DEFAULT '0',
      `locid` int(1) NOT NULL DEFAULT '0',
      `type` varchar(5) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`client`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("lphead", $qry);

    $qry = "CREATE TABLE  `hlphead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',   
      `dateid` datetime DEFAULT NULL,   
      `start` datetime DEFAULT NULL,
      `enddate` datetime DEFAULT NULL,
      `category` varchar(30) NOT NULL DEFAULT '',
      `bstyle` varchar(30) NOT NULL DEFAULT '',
      `tin` varchar(15) NOT NULL DEFAULT '',
      `position` varchar(50) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `tel` varchar(25) NOT NULL DEFAULT '',
      `contact` varchar(150) NOT NULL DEFAULT '',
      `termcontract` varchar(250) NOT NULL DEFAULT '',
      `email` varchar(50) NOT NULL DEFAULT '',
      `escalation` varchar(150) NOT NULL DEFAULT '',
      `lockuser` varchar(100) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(100) NOT NULL DEFAULT '',
      `users` varchar(100) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(100) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `isnonvat` tinyint(1) NOT NULL DEFAULT '0',
      `locid` int(1) NOT NULL DEFAULT '0',
      `type` varchar(5) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`client`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hlphead", $qry);

    $this->coreFunctions->sbcdropcolumn('lphead', 'termcontract');
    $this->coreFunctions->sbcdropcolumn('hlphead', 'termcontract');

    $this->coreFunctions->sbcaddcolumngrp(["lphead", "hlphead"], ["contract"], "varchar(250) NOT NULL DEFAULT ''", 0);


    $this->coreFunctions->sbcaddcolumngrp(["lphead", "hlphead"], ["contract"], "varchar(250) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lphead", "hlphead"], ["contract"], "varchar(250) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("escalation", "trno", "INT(10) DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcdropcolumngrp(["tenantinfo"], ["appdate", "postdate", "appby", "postedby"]);
    $this->coreFunctions->sbcaddcolumngrp(["arledger"], ["locid", "ka"], "INT(10) DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["apledger"], ["py"], "INT(10) DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["tehead", "htehead"], ["companyaddress"], "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["tehead", "htehead"], ["company"], "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("docunum", "yr", "INT(11) default '0'", 0);
    $this->coreFunctions->sbcaddcolumn("chargesbilling", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("chargesbilling", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(["rfstock", "hrfstock"], ["serialno"], "varchar(100) NOT NULL DEFAULT ''", 0);



    $qry = "CREATE TABLE  `omhead` LIKE `oqhead` ";
    $this->coreFunctions->sbccreatetable("omhead", $qry);

    $qry = "CREATE TABLE  `homhead` LIKE `hoqhead` ";
    $this->coreFunctions->sbccreatetable("homhead", $qry);

    $qry = "CREATE TABLE  `omstock` LIKE `oqstock` ";
    $this->coreFunctions->sbccreatetable("omstock", $qry);

    $qry = "CREATE TABLE  `homstock` LIKE `hoqstock` ";
    $this->coreFunctions->sbccreatetable("homstock", $qry);

    $this->coreFunctions->sbcaddcolumn("chargesbilling", "postby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("chargesbilling", "postdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("terms", "orderno", "int(2) NOT NULL DEFAULT '0'");

    // $this->coreFunctions->sbcaddcolumn("item", "subcode", "varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["waterreading", "hwaterreading", "electricreading", "helectricreading"], ["consump"], "DECIMAL(18,4) NOT NULL DEFAULT '0.0000'", 1);

    $qry = "CREATE TABLE  `detailrems` (
      `trno` int(11) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `rem` mediumtext,
      `createby` varchar(150) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("detailrems", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["rvoyage", "hrvoyage"], ["notes"], "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["exhibit", "seminar"], ["description"], "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["exhibit", "seminar"], ["title"], "varchar(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["rfhead", "hrfhead"], ["awb"], "varchar(30) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE  `issueitemstock` (
      `trno` int(11) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `createby` varchar(150) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `returnby` varchar(150) NOT NULL DEFAULT '',
      `returndate` datetime DEFAULT NULL,
      `returnrem` mediumtext,
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`),
      KEY `Index_itemid` (`itemid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("issueitemstock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["issueitemstock"], ["rem"], "varchar(200) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `tenantbal` (
      `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
      `clientid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `bmonth` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `byear` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `aramt` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `reimb` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt` DECIMAL(18,2) NOT NULL DEFAULT 0,
      PRIMARY KEY (`line`),
      INDEX `Index_2`(`clientid`),
      INDEX `Index_3`(`bmonth`, `byear`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("tenantbal", $qry);


    $qry = "CREATE TABLE `omso` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `soline` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `sono` VARCHAR(45) NOT NULL DEFAULT '',
      `rtno` VARCHAR(45) NOT NULL DEFAULT '',
      `qty` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `createby` varchar(150) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `editby` varchar(150) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      INDEX `Index_Trno`(`trno`),
      INDEX `Index_Line`(`line`),
      INDEX `Index_SOLine`(`soline`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("omso", $qry);

    $qry = "CREATE TABLE homso like omso";
    $this->coreFunctions->sbccreatetable("homso", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["omso", "homso"], ["rtno"], "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["omso", "homso"], ["rem"], "varchar(200) NOT NULL DEFAULT ''");


    _20240917Here:
    $qry = "CREATE TABLE `tenancystatus` (
      `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
      `clientid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `monthsno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `status` VARCHAR(45) NOT NULL DEFAULT '',
      `rem` VARCHAR(500) NOT NULL DEFAULT '',
      `effectdate` DATETIME DEFAULT NULL,
      `datefrom` DATETIME DEFAULT NULL,
      `dateto` DATETIME DEFAULT NULL,
      `dateapplied` DATETIME DEFAULT NULL,
      `inactive` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
      `applied` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
      `appliedby` VARCHAR(100) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("tenancystatus", $qry);



    $qry = "CREATE TABLE ckhead like sohead";
    $this->coreFunctions->sbccreatetable("ckhead", $qry);

    $qry = "CREATE TABLE hckhead like hsohead";
    $this->coreFunctions->sbccreatetable("hckhead", $qry);

    $qry = "CREATE TABLE ckstock like sostock";
    $this->coreFunctions->sbccreatetable("ckstock", $qry);

    $qry = "CREATE TABLE hckstock like hsostock";
    $this->coreFunctions->sbccreatetable("hckstock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["ckhead", "hckhead"], ["contra"], "varchar(30) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE kahead like krhead";
    $this->coreFunctions->sbccreatetable("kahead", $qry);

    $qry = "CREATE TABLE hkahead like hkrhead";
    $this->coreFunctions->sbccreatetable("hkahead", $qry);

    $qry = "CREATE TABLE `tacrf` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `clientid` int(10) unsigned NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `authrep` varchar(100) NOT NULL DEFAULT '',
      `refno` varchar(45) NOT NULL DEFAULT '',
      `orno` varchar(25) NOT NULL DEFAULT '',
      `isreleased` tinyint(3) unsigned NOT NULL DEFAULT '0',
      `reldate` datetime DEFAULT NULL,
      `isapproved` tinyint(3) unsigned NOT NULL DEFAULT '0',
      `appdate` datetime DEFAULT NULL,
      `appby` varchar(150) NOT NULL DEFAULT '',
      `relby` varchar(150) NOT NULL DEFAULT '',
      `cancelledby` varchar(25) NOT NULL DEFAULT '',
      `cancelleddate` datetime DEFAULT NULL,
      `rfpno` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      KEY `Index_2` (`clientid`),
      KEY `Index_3` (`dateid`),
      KEY `Index_4` (`refno`,`orno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("tacrf", $qry);

    $qry = "CREATE TABLE  `tacrfdet` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `division` varchar(150) NOT NULL DEFAULT '',
      `accountability` varchar(500) NOT NULL DEFAULT '',
      `clearedby` varchar(100) NOT NULL DEFAULT '',
      `cleareddate` datetime DEFAULT NULL,
      `clientid` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      KEY `Index_2` (`clientid`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("tacrfdet", $qry);

    $qry = "CREATE TABLE pyhead like krhead";
    $this->coreFunctions->sbccreatetable("pyhead", $qry);

    $qry = "CREATE TABLE hpyhead like hkrhead";
    $this->coreFunctions->sbccreatetable("hpyhead", $qry);

    $this->coreFunctions->sbcaddcolumn("tacrf", "seq", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["center"], ["project", "clprefix"], "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("transnum", "sitagging", "INT(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `tempitem` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `itemname` VARCHAR(500) NOT NULL DEFAULT '',
      `specs` VARCHAR(500) NOT NULL DEFAULT '',
      `othcode` VARCHAR(30) NOT NULL DEFAULT '',
      `uom` VARCHAR(10) NOT NULL DEFAULT '',
      `category` int(10) unsigned NOT NULL DEFAULT '0',
      `subcat` int(10) unsigned NOT NULL DEFAULT '0',
      `isgeneric` tinyint(2) NOT NULL DEFAULT '0',
      `isdisable` tinyint(2) NOT NULL DEFAULT '1',
      `isnew` tinyint(2) NOT NULL DEFAULT '0',
      `itemid` int(10) unsigned NOT NULL DEFAULT '0',
      `origitemid` int(10) unsigned NOT NULL DEFAULT '0',
      `createby` varchar(100) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("tempitem", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["tempitem"], ["bgcolor"], "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tempitem"], ["reqtrno", "reqline"], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cntnum", "dptrno", "INT(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `dphead` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` DATETIME DEFAULT NULL,

      `trnxtype` VARCHAR(100) NOT NULL DEFAULT '',
      `deldate` datetime DEFAULT NULL,
      `truckno` VARCHAR(100) NOT NULL DEFAULT '',
      `driver` VARCHAR(100) NOT NULL DEFAULT '',
      `rem` VARCHAR(200) NOT NULL DEFAULT '',

      `createby` varchar(100) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `lockdate` datetime DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("dphead", $qry);

    $qry = "CREATE TABLE `hdphead` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` DATETIME DEFAULT NULL,

      `trnxtype` VARCHAR(100) NOT NULL DEFAULT '',
      `deldate` datetime DEFAULT NULL,
      `truckno` VARCHAR(100) NOT NULL DEFAULT '',
      `driver` VARCHAR(100) NOT NULL DEFAULT '',
      `rem` VARCHAR(200) NOT NULL DEFAULT '',

      `createby` varchar(100) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `lockdate` datetime DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hdphead", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["arledger"], ["ref"], "VARCHAR(2000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["voiddetail", "hvoiddetail"], ['appamt'], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'");

    $qry = "CREATE TABLE `phhead` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` DATETIME DEFAULT NULL,
      `rem` VARCHAR(200) NOT NULL DEFAULT '',
      `createby` VARCHAR(100) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `editdate` datetime DEFAULT NULL,
      `editby` VARCHAR(20) NOT NULL DEFAULT '',
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `lockdate` datetime DEFAULT NULL
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("phhead", $qry);
    $qry = "CREATE TABLE `hphhead` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `rem` varchar(200) not null default '',
      `createby` varchar(100) not null default '',
      `createdate` datetime default null,
      `editdate` datetime default null,
      `editby` varchar(20) not null default '',
      `viewby` varchar(50) not null default '',
      `viewdate` datetime default null,
      `lockdate` datetime default null
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hphhead", $qry);
    $qry = "CREATE TABLE `phstock` (
      `trno` int(10) not null default '0',
      `line` int(11) NOT NULL,
      `barcode` varchar(30) NOT NULL DEFAULT '',
      `itemname` varchar(500) NOT NULL DEFAULT '',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `amt` decimal(19,6) unsigned NOT NULL DEFAULT '0.000000',
      `discr` varchar(40) NOT NULL DEFAULT '',
      `discws` varchar(40) NOT NULL DEFAULT '',
      `disca` varchar(40) not null default '',
      `discb` varchar(40) not null default '',
      `discc` varchar(40) not null default '',
      `discd` varchar(40) not null default '',
      `disce` varchar(40) not null default '',
      `cashamt` decimal(19,6) unsigned not null default '0.000000',
      `cashdisc` varchar(40) not null default '',
      `wsamt` decimal(19,6) unsigned not null default '0.000000',
      `wsdisc` varchar(40) not null default '',
      `amt1` decimal(19,6) unsigned not null default '0.000000',
      `disc1` varchar(40) not null default '',
      `amt2` decimal(19,6) unsigned not null default '0.000000',
      `disc2` varchar(40) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("phstock", $qry);
    $qry = "CREATE TABLE `hphstock` (
      `trno` int(10) not null default '0',
      `line` int(11) not null,
      `barcode` varchar(30) not null default '',
      `itemname` varchar(500) not null default '',
      `uom` varchar(15) not null default '',
      `amt` decimal(19,6) unsigned not null default '0.000000',
      `discr` varchar(40) not null default '',
      `discws` varchar(40) not null default '',
      `disca` varchar(40) not null default '',
      `discb` varchar(40) not null default '',
      `discc` varchar(40) not null default '',
      `discd` varchar(40) not null default '',
      `disce` varchar(40) not null default '',
      `cashamt` decimal(19,6) unsigned not null default '0.000000',
      `cashdisc` varchar(40) not null default '',
      `wsamt` decimal(19,6) unsigned not null default '0.000000',
      `wsdisc` varchar(40) not null default '',
      `amt1` decimal(19,6) unsigned not null default '0.000000',
      `disc1` varchar(40) not null default '',
      `amt2` decimal(19,6) unsigned not null default '0.000000',
      `disc2` varchar(40) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hphstock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['phstock', 'hphstock'], ['encodeddate'], 'timestamp not null default CURRENT_TIMESTAMP', 0);
    $this->coreFunctions->sbcaddcolumngrp(['phstock', 'hphstock', 'sistock'], ['editdate'], 'datetime default null', 0);
    $this->coreFunctions->sbcaddcolumngrp(['phstock', 'hphstock', 'sistock'], ['encodedby', 'editby'], "varchar(100) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['phhead', 'hphhead'], ['lockuser'], "varchar(50) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `dchead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `collector` varchar(150) DEFAULT NULL,
      `isinclude` tinyint(1) not null default '0',
      `createby` varchar(100) not null default '',
      `createdate` datetime default null,
      `editdate` datetime default null,
      `editby` varchar(20) not null default '',
      `viewby` varchar(50) not null default '',
      `viewdate` datetime default null,
      `lockdate` datetime default null,
      `lockuser` varchar(50) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("dchead", $qry);
    $qry = "CREATE TABLE `hdchead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `collector` varchar(150) DEFAULT NULL,
      `isinclude` tinyint(1) not null default '0',
      `createby` varchar(100) not null default '',
      `createdate` datetime default null,
      `editdate` datetime default null,
      `editby` varchar(20) not null default '',
      `viewby` varchar(50) not null default '',
      `viewdate` datetime default null,
      `lockdate` datetime default null,
      `lockuser` varchar(50) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hdchead", $qry);
    $qry = "CREATE TABLE `dcdetail` (
      `trno` int(10) unsigned not null default '0',
      `line` int(10) not null default '0',
      `amount` decimal(18,6) not null default '0.000000',
      `client` varchar(20) not null default '',
      `refx` int(10) unsigned NOT NULL DEFAULT '0',
      `linex` int(10) unsigned NOT NULL DEFAULT '0',
      `encodeddate` timestamp not null default CURRENT_TIMESTAMP,
      `encodedby` varchar(20) default '',
      `editdate` datetime default null,
      `editby` varchar(20) default '',
      PRIMARY KEY (`trno`,`line`),
      UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dcdetail", $qry);
    $qry = "CREATE TABLE `hdcdetail` (
      `trno` int(10) unsigned not null default '0',
      `line` int(10) not null default '0',
      `amount` decimal(18,6) not null default '0.000000',
      `client` varchar(20) not null default '',
      `refx` int(10) unsigned NOT NULL DEFAULT '0',
      `linex` int(10) unsigned NOT NULL DEFAULT '0',
      `encodeddate` timestamp not null default CURRENT_TIMESTAMP,
      `encodedby` varchar(20) default '',
      `editdate` datetime default null,
      `editby` varchar(20) default '',
      PRIMARY KEY (`trno`,`line`),
      UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hdcdetail", $qry);

    $this->coreFunctions->sbcdropcolumngrp(['dcdetail', 'hdcdetail'], ['refx', 'linex']);

    $this->coreFunctions->sbcaddcolumn('pshead', 'acnoid', "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['pshead'], ['yourref', 'ourref'], "varchar(25) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['pshead'], ['lockuser', 'createby', 'editby', 'viewby'], "varchar(100) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['pshead'], ['asofdate', 'lockdate', 'createdate', 'editdate', 'viewdate'], "datetime default null", 0);
    $this->coreFunctions->sbcaddcolumn('pshead', 'docno', "varchar(20) not null default ''", 1);

    $qry = "CREATE TABLE `hpshead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `acnoid` int(11) not null default '0',
      `asofdate` datetime default null,
      `yourref` varchar(25) not null default '',
      `ourref` varchar(25) not null default '',
      `rem` varchar(500) not null default '',
      `lockuser` varchar(100) not null default '',
      `lockdate` datetime default null,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `viewby` varchar(100) not null default '',
      `viewdate` datetime default null,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hpshead", $qry);

    $this->coreFunctions->sbcaddcolumn('transnum', 'pstrno', "int(10) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumn('transnum', 'cvtrno', "int(10) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['particulars', 'hparticulars'], ['quantity', 'checkno', 'rcchecks', 'bank', 'branch'], "varchar(50) not null default ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['particulars', 'hparticulars'], ['refx', 'linex', 'rctrno', 'rcline', 'acnoid', 'clientid'], "int(10) not null default '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['particulars', 'hparticulars'], ["checkdate"], "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(['gldetail', 'hrcdetail', 'hparticulars'], ['retrno'], "int(10) not null default '0'", 0);

    $qry = "CREATE TABLE `mmhead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `itemid` int(11) not null default '0',
      `rem` varchar(500) not null default '',
      `lockuser` varchar(100) not null default '',
      `lockdate` datetime default null,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `viewby` varchar(100) not null default '',
      `viewdate` datetime default null,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("mmhead", $qry);

    $qry = "CREATE TABLE `hmmhead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `itemid` int(11) not null default '0',
      `rem` varchar(500) not null default '',
      `lockuser` varchar(100) not null default '',
      `lockdate` datetime default null,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `viewby` varchar(100) not null default '',
      `viewdate` datetime default null,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hmmhead", $qry);

    $qry = "CREATE TABLE `mmstock` (
      `trno` int(10) unsigned not null default '0',
      `itemid` int(11) not null default '0',
      `line` int(11) not null default '0',
      `rem` varchar(500) not null default '',
      `encodeddate` datetime default null,
      `encodedby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `barcode` varchar(30) not null default '',
      `othcode` varchar(100) not null default '',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("mmstock", $qry);

    $qry = "CREATE TABLE `hmmstock` (
      `trno` int(10) unsigned not null default '0',
      `itemid` int(11) not null default '0',
      `line` int(11) not null default '0',
      `rem` varchar(500) not null default '',
      `encodeddate` datetime default null,
      `encodedby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `barcode` varchar(30) not null default '',
      `othcode` varchar(100) not null default '',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hmmstock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['mmstock', 'hmmstock'], ['line'], "int(10) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumn('terms', 'isnotallow', "tinyint(1) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['eainfo', 'heainfo'], ['isseniorid'], "tinyint(1) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['serialin', 'serialout'], ['chassis', 'color'], "varchar(45) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['qshead', 'hqshead'], ['industryid'], "int(10) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["iteminfo"], ["volume", "weight", "chassisno"], "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["iteminfo"], ["renewaldate", "warrantyend"], "datetime default null");
    $this->coreFunctions->sbcaddcolumngrp(["jchead"], ["printtime"], "datetime default null"); //2.14.2024 - FMM, error in creating triggers missing field
    $this->coreFunctions->sbcaddcolumn('headprrem', 'rrtrno', "int(11) unsigned not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumn('headprrem', 'rrline', "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['serialin', 'serialout'], ['pnp', 'csr'], "varchar(45) not null default ''", 0);

    $this->coreFunctions->sbcaddcolumn('headprrem', 'cvtrno', "int(11) unsigned not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumn('headprrem', 'cvline', "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['headprrem'], ['stockline', 'qty'], "int(11) not null default '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["voiddetail", "hvoiddetail"], ["poref"], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["voiddetail", "hvoiddetail"], ["podate"], "DATETIME default NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["voiddetail", "hvoiddetail"], ["qttrno"], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["voiddetail", "hvoiddetail"], ["lastdp"], "TINYINT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["trhead", "htrhead"], ["projectid", "deptid"], "int(11) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `snstock` (
      `trno` int(10) not null default '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` INT(11) NOT NULL DEFAULT '0',
      `rrqty` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `qty` varchar(50) NOT NULL DEFAULT '',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `lastcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `charges` varchar(45) NOT NULL DEFAULT '',
      `wh` varchar(15) NOT NULL DEFAULT '',
      `whid` int(11) NOT NULL DEFAULT '0',
      `refx` int(10) unsigned NOT NULL DEFAULT '0',
      `linex` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("snstock", $qry);

    $qry = "CREATE TABLE `hsnstock` (
      `trno` int(10) not null default '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` INT(11) NOT NULL DEFAULT '0',
      `rrqty` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `qty` varchar(50) NOT NULL DEFAULT '',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `lastcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `charges` varchar(45) NOT NULL DEFAULT '',
      `wh` varchar(15) NOT NULL DEFAULT '',
      `whid` int(11) NOT NULL DEFAULT '0',
      `refx` int(10) unsigned NOT NULL DEFAULT '0',
      `linex` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsnstock", $qry);
    $this->coreFunctions->sbcaddcolumn("rrstatus", "qa2", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `oihead` (
      `trno` int(10) unsigned not null default '0',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `start` datetime DEFAULT NULL,
      `enddate` datetime DEFAULT NULL,
      `createby` varchar(100) not null default '',
      `createdate` datetime default null,
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `viewby` varchar(100) not null default '',
      `viewdate` datetime default null,
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("oihead", $qry);


    $qry = "CREATE TABLE `hoihead` (
      `trno` int(10) unsigned not null default '0',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `start` datetime DEFAULT NULL,
      `enddate` datetime DEFAULT NULL,
      `createby` varchar(100) not null default '',
      `createdate` datetime default null,
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `viewby` varchar(100) not null default '',
      `viewdate` datetime default null,
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hoihead", $qry);

    $qry = "CREATE TABLE `eqhead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `itemid` int(11) not null default '0',
      `empid` int(11) not null default '0',
      `projectid` int(11) not null default '0',
      `opincentive` decimal(18,2) NOT NULL DEFAULT '0.00',
      `whid` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(500) not null default '',
      `lockuser` varchar(100) not null default '',
      `lockdate` datetime default null,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `viewby` varchar(100) not null default '',
      `viewdate` datetime default null,
      `oitrno` int(10) not null default '0',
      `batchid` int(11) not null default '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("eqhead", $qry);



    $qry = "CREATE TABLE `heqhead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `itemid` int(11) not null default '0',
      `empid` int(11) not null default '0',
      `projectid` int(11) not null default '0',
      `opincentive` decimal(18,2) NOT NULL DEFAULT '0.00',
      `whid` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(500) not null default '',
      `lockuser` varchar(100) not null default '',
      `lockdate` datetime default null,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      `viewby` varchar(100) not null default '',
      `viewdate` datetime default null,
      `oitrno` int(10) not null default '0',
      `batchid` int(11) not null default '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("heqhead", $qry);

    $qry = "CREATE TABLE `eqstock` (
      `trno` int(10) not null default '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `activityid` INT(11) NOT NULL DEFAULT '0',
      `starttime` datetime default null,
      `endtime` datetime default null,
      `duration` decimal(18,2) NOT NULL DEFAULT '0.00',
      `odostart` int(11) not null default '0',
      `odoend` int(11) not null default '0',
      `distance` decimal(18,2) NOT NULL DEFAULT '0.00',
      `fuelconsumption` decimal(18,2) NOT NULL DEFAULT '0.00',
      `encodeddate` datetime default null,
      `encodedby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("eqstock", $qry);

    $qry = "CREATE TABLE `heqstock` (
      `trno` int(10) not null default '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `activityid` INT(11) NOT NULL DEFAULT '0',
      `starttime` datetime default null ,
      `endtime` datetime default null ,
      `duration` decimal(18,2) NOT NULL DEFAULT '0.00',
      `odostart` int(11) not null default '0',
      `odoend` int(11) not null default '0',
      `distance` decimal(18,2) NOT NULL DEFAULT '0.00',
      `fuelconsumption` decimal(18,2) NOT NULL DEFAULT '0.00',
      `encodeddate` datetime default null,
      `encodedby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("heqstock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["snstock", "hsnstock"], ["ref"], "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["particulars", "hparticulars"], ["station", "serial", "remarks", "others"], "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["osstock", "hosstock"], ["sortline"], "int(10) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `projectroxas` (
      `compcode` varchar(15) not null default '',
      `line` INT(11) NOT NULL DEFAULT '0',
      `code` varchar(15) not null default '',
      `name` varchar(100) not null default '',
      `groupid` varchar(100) not null default '',
      `bank` varchar(100) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("projectroxas", $qry);

    $qry = "CREATE TABLE `subprojectroxas` (
      `compcode` varchar(15) not null default '',
      `line` INT(11) NOT NULL DEFAULT '0',
      `code` varchar(15) not null default '',
      `name` varchar(100) not null default '',
      `parent` varchar(15) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("subprojectroxas", $qry);

    $qry = "CREATE TABLE `blocklotroxas` (
      `compcode` varchar(15) not null default '',
      `line` INT(11) NOT NULL DEFAULT '0',
      `code` varchar(15) not null default '',
      `phase` varchar(100) not null default '',
      `block` varchar(100) not null default '',
      `lot` varchar(100) not null default '',
      `subprojectcode` varchar(15) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("blocklotroxas", $qry);

    $qry = "CREATE TABLE `amenityroxas` (
      `compcode` varchar(15) not null default '',
      `line` INT(11) NOT NULL DEFAULT '0',
      `code` varchar(15) not null default '',
      `name` varchar(100) not null default '',
      `groupid` varchar(100) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("amenityroxas", $qry);

    $qry = "CREATE TABLE `subamenityroxas` (
      `compcode` varchar(15) not null default '',
      `line` INT(11) NOT NULL DEFAULT '0',
      `code` varchar(15) not null default '',
      `name` varchar(100) not null default '',
      `parent` varchar(15) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("subamenityroxas", $qry);

    $qry = "CREATE TABLE `departmentroxas` (
      `compcode` varchar(15) not null default '',
      `line` INT(11) NOT NULL DEFAULT '0',
      `code` varchar(15) not null default '',
      `name` varchar(100) not null default '',
      `groupid` varchar(100) not null default ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("departmentroxas", $qry);


    $qry = "CREATE TABLE `changeshiftapp` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT, 
      `dateid` datetime default null,
      `empid` int(11) not null default '0',
      `status` INT(4) NOT NULL DEFAULT '0',
      `rem` VARCHAR(500) NOT NULL DEFAULT '',
      `schedin` datetime DEFAULT NULL,
      `schedout` datetime DEFAULT NULL,
      `originalin` datetime DEFAULT NULL,
      `originalout` datetime DEFAULT NULL,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editdate` datetime default null,
      `editby` varchar(100) not null default '',
      `approveddate` datetime DEFAULT NULL,
      `approvedby` varchar(100) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("changeshiftapp", $qry);


    $this->coreFunctions->sbcaddcolumngrp(["qshead", "hqshead"], ["revisionref"], "VARCHAR(200) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("changeshiftapp", "disapproveddate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("changeshiftapp", "disapprovedby", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("changeshiftapp", "approveddate2", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("changeshiftapp", "approvedby2", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("changeshiftapp", "status2", "int(4) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumngrp(['serialin'], ['remarks', 'editby'], "varchar(150) not null default ''", 1);
    $this->coreFunctions->sbcaddcolumn("serialin", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE `mchead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `clientid` int(11) not null default '0',
      `amount` DECIMAL(18,6) not null default 0,
      `checkinfo` varchar(100) not null default '',
      `yourref` varchar(100) not null default '',
      `ourref` varchar(100) not null default '',
      `rem` varchar(500) not null default '',
      `lockuser` varchar(100) not null default '',
      `lockdate` datetime default null,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("mchead", $qry);
    //  -- `address` VARCHAR(250) not null default '',
    $qry = "CREATE TABLE `hmchead` (
      `trno` int(10) unsigned not null default '0',
      `doc` varchar(20) not null default '',
      `docno` varchar(20) not null default '',
      `dateid` datetime default null,
      `clientid` int(11) not null default '0',
      `amount` DECIMAL(18,6) not null default 0,
      `checkinfo` varchar(100) not null default '',
      `yourref` varchar(100) not null default '',
      `ourref` varchar(100) not null default '',
      `rem` varchar(500) not null default '',
      `lockuser` varchar(100) not null default '',
      `lockdate` datetime default null,
      `createdate` datetime default null,
      `createby` varchar(100) not null default '',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hmchead", $qry);

    $this->coreFunctions->sbcaddcolumn("arledger", "mctrno", "int(11) not null default '0'");
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["address"], "VARCHAR(250) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["rfhead", "hrfhead"], ["reason", "others"], "VARCHAR(1000) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("cntnum", "recontrno", "int(11) not null default '0'");
    $this->coreFunctions->sbcaddcolumn("cntnum", "refrecon", "int(11) not null default '0'");

    $this->coreFunctions->sbcaddcolumn("head", "extractdate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pyhead", "hpyhead"], ["amt"], "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `mode_masterfile` (
      `line` int(10) unsigned not null AUTO_INCREMENT,
      `name` varchar(200) not null default '',
      `isenable` int(20) unsigned not null default '0',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("mode_masterfile", $qry);
    $this->coreFunctions->sbcaddcolumn("mode_masterfile", "ismc", "tinyint(1) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumn("mode_masterfile", "issp", "tinyint(1) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumn("mode_masterfile", "isactive", "tinyint(1) not null default '0'", 0);
    $this->coreFunctions->createindex("iteminfo", "Index_ItemID", ['itemid']);
    $this->coreFunctions->sbcaddcolumngrp(["headprrem"], ["jotrno", "joline", "empid"], "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumn("serialin", "dateid", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcdropcolumn("arledger", "mctrno");

    $qry = "CREATE TABLE `mcdetail` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `dateid` DATETIME DEFAULT NULL,
      `amount` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `interest` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `principal` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `penalty` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `refx` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `editby` VARCHAR(45) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL
    )
    ENGINE = MyISAM;";

    $this->coreFunctions->sbccreatetable("mcdetail", $qry);

    $qry = "CREATE TABLE `hmcdetail` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `dateid` DATETIME DEFAULT NULL,
      `amount` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `interest` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `principal` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `penalty` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `refx` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `editby` VARCHAR(45) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL
    )
    ENGINE = MyISAM;";

    $this->coreFunctions->sbccreatetable("hmcdetail", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['mcdetail', 'hmcdetail'], ['encodeddate'], 'timestamp not null default CURRENT_TIMESTAMP', 0);
    $this->coreFunctions->sbcaddcolumngrp(['mcdetail', 'hmcdetail'], ['encodedby'], "varchar(100) not null default ''", 0);

    $qry = "CREATE TABLE `area` (
      `line` int(10) unsigned not null auto_increment,
      `code` varchar(45) not null default '',
      `area` varchar(100) not null default '',
      `inactive` tinyint(1) not null default '0',
      `editby` varchar(100) not null default '',
      `editdate` datetime default null,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("area", $qry);

    $this->coreFunctions->sbcaddcolumn("center", "areaid", "int(11) not null default '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["coa"], ["isprojexp", "isinactive"], "TINYINT(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["crtrno", "jvtrno"], "int(11) not null default '0'");
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["isok", "isok"], "tinyint(1) not null default '0'");

    $qry = "CREATE TABLE  `cihead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(15) NOT NULL,
        `dateid` datetime DEFAULT NULL,
        `rem` varchar(500) NOT NULL DEFAULT '',
        `voiddate` datetime DEFAULT NULL,
        `yourref` varchar(100) NOT NULL DEFAULT '',
        `ourref` varchar(25) NOT NULL DEFAULT '',
        `lockuser` varchar(100) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(100) NOT NULL DEFAULT '',
        `users` varchar(100) NOT NULL DEFAULT '',
        `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `createby` varchar(100) NOT NULL DEFAULT '',
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(100) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        `itemid` int(10) NOT NULL DEFAULT '0',
        `housemodel` int(10) NOT NULL DEFAULT '0',
        `uom` varchar(20) NOT NULL DEFAULT '0',
        `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
        PRIMARY KEY (`trno`),
        KEY `Index_2head` (`docno`,`itemid`,`dateid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cihead", $qry);


    $qry = "CREATE TABLE  `hcihead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(15) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `rem` varchar(500) NOT NULL DEFAULT '',
      `voiddate` datetime DEFAULT NULL,
      `yourref` varchar(100) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `lockuser` varchar(100) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(100) NOT NULL DEFAULT '',
      `users` varchar(100) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(100) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `itemid` int(10) NOT NULL DEFAULT '0',
      `housemodel` int(10) NOT NULL DEFAULT '0',
      `uom` varchar(20) NOT NULL DEFAULT '0',
      `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`itemid`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hcihead", $qry);

    $qry = "CREATE TABLE  `cistock` (
      `trno` bigint(20) NOT NULL,
      `line` int(11) NOT NULL,
      `barcode` varchar(15) NOT NULL,
      `itemname` tinytext NOT NULL,
      `uom` varchar(15) NOT NULL,
      `rem` varchar(40) NOT NULL,
      `rrqty` decimal(19,6) NOT NULL,
      `qty` decimal(19,10) NOT NULL,
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `encodeddate` datetime DEFAULT NULL,
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `amenity` int(11) NOT NULL DEFAULT '0',
      `subamenity` int(11) NOT NULL DEFAULT '0',
      `housemodel` int(10) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_barcode` (`barcode`,`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cistock", $qry);


    $qry = "CREATE TABLE  `hcistock` (
      `trno` bigint(20) NOT NULL,
      `line` int(11) NOT NULL,
      `barcode` varchar(15) NOT NULL,
      `itemname` tinytext NOT NULL,
      `uom` varchar(15) NOT NULL,
      `rem` varchar(40) NOT NULL,
      `rrqty` decimal(19,6) NOT NULL,
      `qty` decimal(19,10) NOT NULL,
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `encodeddate` datetime DEFAULT NULL,
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `amenity` int(11) NOT NULL DEFAULT '0',
      `subamenity` int(11) NOT NULL DEFAULT '0',
      `housemodel` int(10) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_barcode` (`barcode`,`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hcistock", $qry);

    $qry = "CREATE TABLE `amenities` (
      `line` int(11) unsigned not null auto_increment,
      `code` varchar(45) not null default '',
      `description` varchar(200) NOT NULL,
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(100) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("amenities", $qry);

    $qry = "CREATE TABLE `subamenities` (
      `line` int(11) unsigned not null auto_increment,
      `amenityid` int(11) NOT NULL,
      `code` varchar(45) not null default '',
      `description` varchar(200) NOT NULL,
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(100) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("subamenities", $qry);


    $qry = "CREATE TABLE  `cohead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(15) NOT NULL,
        `client` varchar(15) NOT NULL,
        `clientname` varchar(150) NOT NULL,
        `address` varchar(300) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `wh` varchar(15) NOT NULL DEFAULT '',
        `yourref` varchar(100) NOT NULL DEFAULT '',
        `ourref` varchar(25) NOT NULL DEFAULT '',
        `projectid` int(10) NOT NULL DEFAULT '0',
        `phase` int(10) NOT NULL DEFAULT '0',
        `housemodel` int(10) NOT NULL DEFAULT '0',
        `blk` varchar(150) NOT NULL DEFAULT '',
        `lot` varchar(150) NOT NULL DEFAULT '',
        `rem` varchar(500) NOT NULL DEFAULT '',
        `citrno` int(10) NOT NULL DEFAULT '0',
        `lockuser` varchar(100) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(100) NOT NULL DEFAULT '',
        `users` varchar(100) NOT NULL DEFAULT '',
        `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `createby` varchar(100) NOT NULL DEFAULT '',
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(100) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        `voiddate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`),
        KEY `Index_2head` (`docno`,`dateid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cohead", $qry);

    $qry = "CREATE TABLE  `hcohead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(15) NOT NULL,
        `client` varchar(15) NOT NULL,
        `clientname` varchar(150) NOT NULL,
        `address` varchar(300) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `wh` varchar(15) NOT NULL DEFAULT '',
        `yourref` varchar(100) NOT NULL DEFAULT '',
        `ourref` varchar(25) NOT NULL DEFAULT '',
        `projectid` int(10) NOT NULL DEFAULT '0',
        `phase` int(10) NOT NULL DEFAULT '0',
        `housemodel` int(10) NOT NULL DEFAULT '0',
        `blk` varchar(150) NOT NULL DEFAULT '',
        `lot` varchar(150) NOT NULL DEFAULT '',
        `rem` varchar(500) NOT NULL DEFAULT '',
        `citrno` int(10) NOT NULL DEFAULT '0',
        `lockuser` varchar(100) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(100) NOT NULL DEFAULT '',
        `users` varchar(100) NOT NULL DEFAULT '',
        `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `createby` varchar(100) NOT NULL DEFAULT '',
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(100) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        `voiddate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`),
        KEY `Index_2head` (`docno`,`dateid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hcohead", $qry);

    $qry = "CREATE TABLE  `costock` (
      `trno` bigint(20) NOT NULL,
      `line` int(11) NOT NULL,
      `barcode` varchar(15) NOT NULL,
      `itemname` tinytext NOT NULL,
      `uom` varchar(15) NOT NULL,
      `rem` varchar(40) NOT NULL,
      `rrqty` decimal(19,6) NOT NULL,
      `qty` decimal(19,10) NOT NULL,
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(15) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `amenity` int(11) NOT NULL DEFAULT '0',
      `subamenity` int(11) NOT NULL DEFAULT '0',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_barcode` (`barcode`,`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("costock", $qry);

    $qry = "CREATE TABLE  `hcostock` (
      `trno` bigint(20) NOT NULL,
      `line` int(11) NOT NULL,
      `barcode` varchar(15) NOT NULL,
      `itemname` tinytext NOT NULL,
      `uom` varchar(15) NOT NULL,
      `rem` varchar(40) NOT NULL,
      `rrqty` decimal(19,6) NOT NULL,
      `qty` decimal(19,10) NOT NULL,
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(15) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `amenity` int(11) NOT NULL DEFAULT '0',
      `subamenity` int(11) NOT NULL DEFAULT '0',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_barcode` (`barcode`,`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hcostock", $qry);

    $this->coreFunctions->execqrynolog("ALTER TABLE cohead CHANGE phase phaseid int(10) NOT NULL DEFAULT '0'");
    $this->coreFunctions->execqrynolog("ALTER TABLE cohead CHANGE housemodel modelid int(10) NOT NULL DEFAULT '0'");
    $this->coreFunctions->execqrynolog("ALTER TABLE cohead CHANGE blk blklotid int(10) NOT NULL DEFAULT '0'");

    $this->coreFunctions->execqrynolog("ALTER TABLE hcohead CHANGE phase phaseid int(10) NOT NULL DEFAULT '0'");
    $this->coreFunctions->execqrynolog("ALTER TABLE hcohead CHANGE housemodel modelid int(10) NOT NULL DEFAULT '0'");
    $this->coreFunctions->execqrynolog("ALTER TABLE hcohead CHANGE blk blklotid int(10) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumngrp(["costock", "hcostock"], ['projectid', 'phaseid', 'modelid', "blklotid"], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["costock", "hcostock"], ["lot"], "varchar(150) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["mrhead", "hmrhead", "mrstock", "hmrstock"], ['projectid', 'blklotid', 'modelid', 'phaseid'], "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cohead", "hcohead"], ['cotrno'], "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mrstock", "hmrstock"], ['refx', 'linex'], "int(11) not null default '0'", 0);

    // $this->coreFunctions->execqrynolog("ALTER TABLE cohead CHANGE cotrno mrtrno int(11) not null default '0'");
    // $this->coreFunctions->execqrynolog("ALTER TABLE hcohead CHANGE cotrno mrtrno int(11) not null default '0'");



    $this->coreFunctions->sbcaddcolumn("mode_masterfile", "clientid", "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["mcdetail", "hmcdetail"], ["acnoid"], "int(10) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["modeofpayment"], "varchar(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["checkdate"], "datetime");

    $qry = "CREATE TABLE `guardtimerec` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL DEFAULT '',
      `timein` datetime DEFAULT NULL,
      `timeout` datetime DEFAULT NULL,
      `loginpic` varchar(100) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("guardtimerec", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["mrstock", "hmrstock"], ['amenity', 'subamenity'], "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["voiddetail", "hvoiddetail"], ['phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn('transnum', 'pctrno', "int(10) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mrstock", "hmrstock"], ['prqty', 'prqa'], "decimal(19,6) NOT NULL default '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["krhead", "hkrhead"], ['projectid', 'phaseid', 'modelid', 'blklotid', 'amenityid', 'subamenityid'], "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["otherterms"], "varchar(200) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["itemsubcategory"], ["name"], "varchar(100) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["trnxtype"], "varchar(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn('caledger', 'mctrno', "int(10) not null default '0'", 0);


    $this->coreFunctions->sbcaddcolumngrp(["mcdetail", "hmcdetail"], ["rem"], "varchar(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["mrhead", "hmrhead"], ['cotrno'], "int(11) not null default '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["rem2"], "VARCHAR(500) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["costock", "hcostock"], ['miqa'], "decimal(19,6) NOT NULL default '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["sicsino"], "VARCHAR(500) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["drno"], "VARCHAR(500) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["chsino"], "VARCHAR(500) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["swsno"], "VARCHAR(500) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mcdetail", "hmcdetail"], ["daysdue"], "int(10) NOT NULL DEFAULT '0'");


    $this->coreFunctions->sbcaddcolumngrp(["snstock", "hsnstock"], ['iss'], "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["clientname"], "varchar(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("serialout", "rem",  "varchar(500) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["pmhead", "hpmhead"], ["dollarprice"], "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["reportlog"], ['dateid'], "datetime DEFAULT NULL", 0);


    $qry = "CREATE TABLE `cntnum_stat` (
            `trno` int(10) unsigned NOT NULL DEFAULT '0',
            `field` varchar(45) NOT NULL DEFAULT '',
            `oldversion` varchar(900) NOT NULL DEFAULT '',
            `userid` varchar(100) NOT NULL DEFAULT '',
            `dateid` datetime DEFAULT NULL,
            KEY `Index_1` (`trno`),
            KEY `Index_2` (`dateid`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cntnum_stat", $qry);

    $qry = "CREATE TABLE `transnum_stat` (
            `trno` int(10) unsigned NOT NULL DEFAULT '0',
            `field` varchar(45) NOT NULL DEFAULT '',
            `oldversion` varchar(900) NOT NULL DEFAULT '',
            `userid` varchar(100) NOT NULL DEFAULT '',
            `dateid` datetime DEFAULT NULL,
            KEY `Index_1` (`trno`),
            KEY `Index_2` (`dateid`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("transnum_stat", $qry);


    $this->coreFunctions->sbcaddcolumngrp(["pihead", "hpihead"], ["forex"], "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pihead", "hpihead", "prhead", "hprhead"], ["weight"], "varchar(50) NOT NULL DEFAULT ''", 1);


    $this->coreFunctions->sbcaddcolumngrp(["pistock", "hpistock"], ["amenity", "subamenity"], "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["jcstock", "hjcstock"], ["rem"], "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["pistock", "hpistock"], ["maxqty"], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $qry = "CREATE TABLE `htransnum_log` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `field` varchar(45) NOT NULL DEFAULT '',
      `oldversion` varchar(900) NOT NULL,
      `userid` varchar(100) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      KEY `Index_1` (`trno`),
      KEY `Index_2` (`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("htransnum_log", $qry);

    $qry = "CREATE TABLE `htable_log` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `field` varchar(45) NOT NULL DEFAULT '',
      `oldversion` varchar(900) NOT NULL,
      `userid` varchar(100) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      KEY `Index_1` (`trno`),
      KEY `Index_2` (`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("htable_log", $qry);


    /////////////////////////

    $qry = "CREATE TABLE  `athead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(15) NOT NULL,
        `client` varchar(15) NOT NULL DEFAULT '',
        `clientname` varchar(150) NOT NULL DEFAULT '',
        `address` varchar(150) NOT NULL DEFAULT '',
        `shipto` varchar(150) NOT NULL DEFAULT '',
        `tel` varchar(50) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `wh` varchar(15) NOT NULL DEFAULT '',
        `terms` varchar(30) NOT NULL DEFAULT '',
        `rem` varchar(500) NOT NULL DEFAULT '',
        `cur` varchar(2) NOT NULL DEFAULT '',
        `forex` decimal(18,2) NOT NULL DEFAULT '0.00',
        `voiddate` datetime DEFAULT NULL,
        `branch` varchar(30) NOT NULL DEFAULT '',
        `agent` varchar(20) NOT NULL DEFAULT '',
        `isimport` tinyint(4) NOT NULL DEFAULT '0',
        `delby` datetime DEFAULT NULL,
        `yourref` varchar(50) NOT NULL DEFAULT '',
        `ourref` varchar(25) NOT NULL DEFAULT '',
        `vattype` varchar(45) NOT NULL DEFAULT '',
        `isapproved` tinyint(4) NOT NULL DEFAULT '0',
        `lockuser` varchar(100) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(100) NOT NULL DEFAULT '',
        `users` varchar(100) NOT NULL DEFAULT '',
        `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `createby` varchar(100) NOT NULL DEFAULT '',
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(100) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`),
        KEY `Index_2head` (`docno`,`dateid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("athead", $qry);

    $qry = "CREATE TABLE  `hathead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(15) NOT NULL,
        `client` varchar(15) NOT NULL DEFAULT '',
        `clientname` varchar(150) NOT NULL DEFAULT '',
        `address` varchar(150) NOT NULL DEFAULT '',
        `shipto` varchar(150) NOT NULL DEFAULT '',
        `tel` varchar(50) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `wh` varchar(15) NOT NULL DEFAULT '',
        `terms` varchar(30) NOT NULL DEFAULT '',
        `rem` varchar(500) NOT NULL DEFAULT '',
        `cur` varchar(2) NOT NULL DEFAULT '',
        `forex` decimal(18,2) NOT NULL DEFAULT '0.00',
        `voiddate` datetime DEFAULT NULL,
        `branch` varchar(30) NOT NULL DEFAULT '',
        `agent` varchar(20) NOT NULL DEFAULT '',
        `isimport` tinyint(4) NOT NULL DEFAULT '0',
        `delby` datetime DEFAULT NULL,
        `yourref` varchar(50) NOT NULL DEFAULT '',
        `ourref` varchar(25) NOT NULL DEFAULT '',
        `vattype` varchar(45) NOT NULL DEFAULT '',
        `isapproved` tinyint(4) NOT NULL DEFAULT '0',
        `lockuser` varchar(100) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(100) NOT NULL DEFAULT '',
        `users` varchar(100) NOT NULL DEFAULT '',
        `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `createby` varchar(100) NOT NULL DEFAULT '',
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(100) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`),
        KEY `Index_2head` (`docno`,`dateid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hathead", $qry);

    $qry = "CREATE TABLE `atstock` (
      `trno` bigint(20) NOT NULL,
      `line` int(11) NOT NULL,
      `uom` varchar(15) NOT NULL,
      `disc` varchar(40) NOT NULL,
      `rem` varchar(40) NOT NULL,
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `loc` varchar(45) NOT NULL DEFAULT '',
      `expiry` varchar(45) DEFAULT NULL,
      `oqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `projectid` int(3) NOT NULL DEFAULT '0',
      `consignee` varchar(100) NOT NULL DEFAULT '',
      `asofqty` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_barcode` (`itemid`,`whid`,`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("atstock", $qry);


    $qry = "CREATE TABLE  `hatstock` (
      `trno` bigint(20) NOT NULL,
      `line` int(11) NOT NULL,
      `uom` varchar(15) NOT NULL,
      `disc` varchar(40) NOT NULL,
      `rem` varchar(40) NOT NULL,
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `loc` varchar(45) NOT NULL DEFAULT '',
      `expiry` varchar(45) DEFAULT NULL,
      `oqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `projectid` int(3) NOT NULL DEFAULT '0',
      `consignee` varchar(100) NOT NULL DEFAULT '',
      `asofqty` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_barcode` (`itemid`,`whid`,`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hatstock", $qry);



    $this->coreFunctions->sbcaddcolumngrp(["atstock", "hatstock"], ['ispc'], "TINYINT(1) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `invbal` (
      `itemid` int(10) unsigned NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `bal` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `expiry` varchar(45) NOT NULL DEFAULT '',
      `dateid` date DEFAULT NULL,
      `loc` varchar(45) NOT NULL DEFAULT '',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      KEY `Index_ItemID` (`itemid`),
      KEY `Index_WhID` (`whid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("invbal", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["invbal"], ["dateid"], "date DEFAULT NULL", 1);

    //2025.01.20 - FMM
    $this->coreFunctions->sbcaddcolumngrp(["invbal"], ['cost'], "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["nationality"], "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["employer"], "varchar(250) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["purpose"], "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["civilstatus", "tin", "sssgsis"], "varchar(15) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead", "eainfo", "heainfo"], ["current1", "current2", "others1", "others2", "savings1", "savings2", "pliss", "num", "mmname", "sname", "ename", "dependentsno", "tct"], "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead", "eainfo", "heainfo"], ["mincome", "mexp"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["interest", "monthly"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["credits"], "varchar(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["bank1"], "varchar(50) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE  `rghead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(15) NOT NULL,
        `client` varchar(15) NOT NULL DEFAULT '',
        `clientname` varchar(150) NOT NULL DEFAULT '',
        `address` varchar(150) NOT NULL DEFAULT '',
        `shipto` varchar(150) NOT NULL DEFAULT '',
        `tel` varchar(50) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `wh` varchar(15) NOT NULL DEFAULT '',
        `terms` varchar(30) NOT NULL DEFAULT '',
        `rem` varchar(500) NOT NULL DEFAULT '',
        `cur` varchar(2) NOT NULL DEFAULT '',
        `forex` decimal(18,2) NOT NULL DEFAULT '0.00',
        `voiddate` datetime DEFAULT NULL,
        `branch` varchar(30) NOT NULL DEFAULT '',
        `agent` varchar(20) NOT NULL DEFAULT '',
        `isimport` tinyint(4) NOT NULL DEFAULT '0',
        `delby` datetime DEFAULT NULL,
        `yourref` varchar(50) NOT NULL DEFAULT '',
        `ourref` varchar(25) NOT NULL DEFAULT '',
        `vattype` varchar(45) NOT NULL DEFAULT '',
        `isapproved` tinyint(4) NOT NULL DEFAULT '0',
        `lockuser` varchar(100) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(100) NOT NULL DEFAULT '',
        `users` varchar(100) NOT NULL DEFAULT '',
        `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `createby` varchar(100) NOT NULL DEFAULT '',
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(100) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`),
        KEY `Index_2head` (`docno`,`dateid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("rghead", $qry);

    $qry = "CREATE TABLE  `hrghead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(15) NOT NULL,
        `client` varchar(15) NOT NULL DEFAULT '',
        `clientname` varchar(150) NOT NULL DEFAULT '',
        `address` varchar(150) NOT NULL DEFAULT '',
        `shipto` varchar(150) NOT NULL DEFAULT '',
        `tel` varchar(50) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `wh` varchar(15) NOT NULL DEFAULT '',
        `terms` varchar(30) NOT NULL DEFAULT '',
        `rem` varchar(500) NOT NULL DEFAULT '',
        `cur` varchar(2) NOT NULL DEFAULT '',
        `forex` decimal(18,2) NOT NULL DEFAULT '0.00',
        `voiddate` datetime DEFAULT NULL,
        `branch` varchar(30) NOT NULL DEFAULT '',
        `agent` varchar(20) NOT NULL DEFAULT '',
        `isimport` tinyint(4) NOT NULL DEFAULT '0',
        `delby` datetime DEFAULT NULL,
        `yourref` varchar(50) NOT NULL DEFAULT '',
        `ourref` varchar(25) NOT NULL DEFAULT '',
        `vattype` varchar(45) NOT NULL DEFAULT '',
        `isapproved` tinyint(4) NOT NULL DEFAULT '0',
        `lockuser` varchar(100) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(100) NOT NULL DEFAULT '',
        `users` varchar(100) NOT NULL DEFAULT '',
        `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `createby` varchar(100) NOT NULL DEFAULT '',
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(100) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`),
        KEY `Index_2head` (`docno`,`dateid`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hrghead", $qry);


    $this->coreFunctions->sbcdropcolumn('terms', 'interest_rate');
    $this->coreFunctions->sbcdropcolumn('terms', 'professional_fee_notarial_fee');

    $this->coreFunctions->sbcaddcolumngrp(["terms"], ["interest", "pfnf", "nf"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["loanlimit", "loanamt", "value", "value2", "pricesqm", "tcp", "disc", "outstanding", "penaltyamt"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["payrolltype", "employeetype", "blklot", "area"], "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["amortization", "penalty", "fmons", "fannum", "frate", "intannum"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo", "eahead", "heahead"], ["addressno", "city", "subdivision"], "varchar(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["vattype", "ourref"], "varchar(50) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["attorneyinfact", "attorneyaddress", "blklot"], "varchar(150) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `acctgbal` (
      `acnoid` int(11) not null default '0',
      `clientid` int(11) not null default '0',
      `projectid` int(10) NOT NULL DEFAULT '0',
      `db` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cr` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `dateid` date DEFAULT NULL,
      KEY `Index_Acnoid` (`acnoid`),
      KEY `Index_Clientid` (`clientid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("acctgbal", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["acctgbal"], ["branchid", "deptid"], "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("acctgbal", "center", "varchar(45) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE  `tempdetailinfo` (
        `trno` int(11) NOT NULL DEFAULT '0',
        `line` int(11) NOT NULL DEFAULT '0',
        `rem` text,
        `editby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `pfnf` decimal(18,6) NOT NULL DEFAULT '0.000000',
        `interest` decimal(18,6) NOT NULL DEFAULT '0.000000',
        `principal` decimal(18,6) NOT NULL DEFAULT '0.000000',
        KEY `Index_trno` (`trno`),
        KEY `Index_line` (`line`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("tempdetailinfo", $qry);

    $qry = "CREATE TABLE  `htempdetailinfo` (
      `trno` int(11) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `rem` text,
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `pfnf` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `interest` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `principal` decimal(18,6) NOT NULL DEFAULT '0.000000',
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("htempdetailinfo", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["pemail"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);




    // mmoq not in head nor in info
    // prref not in head nor in info
    // checkinfo  not in head nor in info
    // entryndiffot not in head nor in info
    // regnum not in head nor in info
    // purchaser not in head nor in info
    // registername not in head nor in info
    // shipto not in head nor in info
    // revisionref not in head nor in info
    // returndate not in head nor in info
    // mlcp_freight not in head nor in info
    $this->coreFunctions->sbcaddcolumngrp(["tempdetailinfo", "htempdetailinfo"], ["payment", "penalty", "bal", "dst", "mri"], "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["mri", "docstamp"], "decimal(18,4) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tempdetailinfo", "htempdetailinfo"], ["dateid"], "datetime", 0);

    $this->coreFunctions->sbcaddcolumngrp(["headrem", "hheadrem"], ["clientid"], "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["osstock", "hosstock"], ["pono"], "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("whdoc", "expiry2", "datetime DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("whdoc", "days", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["whdoc"], ["oic1", "oic2"], "VARCHAR(20) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("whdoc", "status", "VARCHAR(15) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("reqcategory", "description", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["reqcategory"], ["code", "position"], "VARCHAR(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["reqcategory"], ["acnoid", "sortline"], "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(['reqcategory'], ['isindustry', 'isactivity', 'isss', 'isorder', 'ischannel', 'isloantype', "isreasoncode", "inactive", "isttype", "ispaytype", "ispaymode", 'isreassigned', 'issbu', 'ispurpose', 'isbrgyoff', 'isreasonhiring', 'isempstatus', 'isdiminishing', 'ispexp', 'istasktype', 'iscomm', 'isdailytask', 'isrepacker', 'istaskcat'], "tinyint(1) not null default '0'", 1);

    $qry = "CREATE TABLE  `dxhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(100) NOT NULL DEFAULT '',
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_dxhead` (`docno`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("dxhead", $qry);

    $qry = "CREATE TABLE  `hdxhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(100) NOT NULL DEFAULT '',
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_hdxhead` (`docno`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("hdxhead", $qry);

    // 03.18.2025
    $qry = "CREATE TABLE `cehead` (
      `trno` INT(10) UNSIGNED NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` DATETIME DEFAULT NULL,
      `clientid` INT(11) NOT NULL DEFAULT '0',
      `clientname` VARCHAR(150) NOT NULL DEFAULT '',
      `address` VARCHAR(250) NOT NULL DEFAULT '',
      `amount` DECIMAL(18, 6) NOT NULL DEFAULT '0.000000',
      `bank` VARCHAR(100) NOT NULL DEFAULT '',
      `checkinfo` VARCHAR(100) NOT NULL DEFAULT '',
      `yourref` VARCHAR(100) NOT NULL DEFAULT '',
      `ourref` VARCHAR(100) NOT NULL DEFAULT '',
      `lockuser` VARCHAR(100) NOT NULL DEFAULT '',
      `lockdate` DATETIME DEFAULT NULL,
      `createdate` DATETIME DEFAULT NULL,
      `createby` VARCHAR(100) NOT NULL DEFAULT '',
      `editby` VARCHAR(100) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL,
      `modeofpayment` VARCHAR(20) NOT NULL DEFAULT '',
      `purposeofpayment` VARCHAR(150) NOT NULL DEFAULT '',
      `checkdate` DATETIME DEFAULT NULL,
      `trnxtype` VARCHAR(150) NOT NULL DEFAULT '',
      `rem` VARCHAR(500) NOT NULL DEFAULT '',
      `rem2` VARCHAR(500) NOT NULL DEFAULT '',
      `sicsino` VARCHAR(500) NOT NULL DEFAULT '',
      `drno` VARCHAR(500) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`)
    ) ENGINE=MYISAM DEFAULT CHARSET=LATIN1;";
    $this->coreFunctions->sbccreatetable("cehead", $qry);

    $qry = "CREATE TABLE `hcehead` (
      `trno` INT(10) UNSIGNED NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` DATETIME DEFAULT NULL,
      `clientid` INT(11) NOT NULL DEFAULT '0',
      `clientname` VARCHAR(150) NOT NULL DEFAULT '',
      `address` VARCHAR(250) NOT NULL DEFAULT '',
      `amount` DECIMAL(18, 6) NOT NULL DEFAULT '0.000000',
      `bank` VARCHAR(100) NOT NULL DEFAULT '',
      `checkinfo` VARCHAR(100) NOT NULL DEFAULT '',
      `yourref` VARCHAR(100) NOT NULL DEFAULT '',
      `ourref` VARCHAR(100) NOT NULL DEFAULT '',
      `lockuser` VARCHAR(100) NOT NULL DEFAULT '',
      `lockdate` DATETIME DEFAULT NULL,
      `createdate` DATETIME DEFAULT NULL,
      `createby` VARCHAR(100) NOT NULL DEFAULT '',
      `editby` VARCHAR(100) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL,
      `modeofpayment` VARCHAR(20) NOT NULL DEFAULT '',
      `purposeofpayment` VARCHAR(150) NOT NULL DEFAULT '',
      `checkdate` DATETIME DEFAULT NULL,
      `trnxtype` VARCHAR(150) NOT NULL DEFAULT '',
      `rem` VARCHAR(500) NOT NULL DEFAULT '',
      `rem2` VARCHAR(500) NOT NULL DEFAULT '',
      `sicsino` VARCHAR(500) NOT NULL DEFAULT '',
      `drno` VARCHAR(500) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`)
    ) ENGINE=MYISAM DEFAULT CHARSET=LATIN1;";
    $this->coreFunctions->sbccreatetable("hcehead", $qry);

    $qry = "CREATE TABLE `cedetail` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `dateid` DATETIME DEFAULT NULL,
      `amount` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `interest` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `principal` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `penalty` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `refx` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `editby` VARCHAR(45) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("cedetail", $qry);

    $qry = "CREATE TABLE `hcedetail` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `dateid` DATETIME DEFAULT NULL,
      `amount` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `interest` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `principal` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `penalty` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `refx` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `editby` VARCHAR(45) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL
    )
    ENGINE = MyISAM;";

    $this->coreFunctions->sbccreatetable("hcedetail", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["hcehead", "cehead"], ["dstrno"], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cehead", "hcehead", "mchead", "hmchead"], ["sicsino", "drno", "rslip", "contra"], "VARCHAR(30) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cehead", "hcehead", "mchead", "hmchead"], ["acnoname"], "VARCHAR(250) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["cehead", "hcehead", "dxhead", "hdxhead"], ["bank"], "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["cntclient", "hcntclient"], ['editdate'], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cntclient", "hcntclient"], ['editby'], "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["tempdetailinfo", "htempdetailinfo"], ["nf"], "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["cehead", "hcehead"], ["trnxtid", "mpid", "ppid"], "TINYINT(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["dxhead", "hdxhead"], ["amount"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["dxhead", "hdxhead"], ["mpid"], "TINYINT(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["dxhead", "hdxhead"], ['checkinfo'], "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcdropcolumngrp(["hmchead", "mchead", "hcehead", "cehead"], ["dstrno"]);

    $this->coreFunctions->sbcaddcolumngrp(["transnum"], ["dstrno"], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["transnum"], ["isdownloaded"], "tinyint(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["sicsino"], "VARCHAR(30) not null default ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["drno"], "VARCHAR(30) not null default ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["chsino"], "VARCHAR(30) not null default ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["mchead", "hmchead"], ["swsno"], "VARCHAR(30) not null default ''", 1);

    $this->coreFunctions->sbcdroptable("cedetail");
    $this->coreFunctions->sbcdroptable("hcedetail");

    $qry = "CREATE TABLE `tcoll` (
      `trno` INT(10) UNSIGNED NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` DATETIME DEFAULT NULL,
      `clientid` INT(11) NOT NULL DEFAULT '0',
      `clientname` VARCHAR(150) NOT NULL DEFAULT '',
      `address` VARCHAR(250) NOT NULL DEFAULT '',
      `amount` DECIMAL(18, 6) NOT NULL DEFAULT '0.000000',
      `checkinfo` VARCHAR(100) NOT NULL DEFAULT '',
      `yourref` VARCHAR(100) NOT NULL DEFAULT '',
      `ourref` VARCHAR(100) NOT NULL DEFAULT '',
      `createdate` DATETIME DEFAULT NULL,
      `createby` VARCHAR(100) NOT NULL DEFAULT '',
      `checkdate` DATETIME DEFAULT NULL,
      `trnxtype` VARCHAR(150) NOT NULL DEFAULT '',
      `rem` VARCHAR(500) NOT NULL DEFAULT '',
      `sicsino` VARCHAR(30) NOT NULL DEFAULT '',
      `drno` VARCHAR(30) NOT NULL DEFAULT '',
      `bank` VARCHAR(150) NOT NULL DEFAULT '',
      `center` VARCHAR(10) NOT NULL DEFAULT '',
      `trnxtid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
      `mpid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
      `ppid` INT(10) UNSIGNED NOT NULL DEFAULT '0',
      `dstrno` INT(10) UNSIGNED NOT NULL DEFAULT '0',
       KEY `Index_tcoll` (`docno`,`dateid`,`clientid`)
    ) ENGINE=MYISAM DEFAULT CHARSET=LATIN1;";
    $this->coreFunctions->sbccreatetable("tcoll", $qry);

    //   $qry = "CREATE TABLE  `tdepslip` (
    //   `trno` bigint(20) NOT NULL DEFAULT '0',
    //   `doc` char(2) NOT NULL DEFAULT '',
    //   `docno` char(20) NOT NULL,
    //   `dateid` datetime DEFAULT NULL,
    //   `yourref` varchar(100) NOT NULL DEFAULT '',
    //   `ourref` varchar(100) NOT NULL DEFAULT '',
    //   `rem` varchar(500) NOT NULL DEFAULT '',
    //   `users` varchar(50) NOT NULL DEFAULT '',
    //   `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    //   `createby` varchar(50) NOT NULL DEFAULT '',
    //   `bank` int(10) NOT NULL DEFAULT '0',
    //   `amount` decimal(18,6) NOT NULL DEFAULT '0.000000',
    //   KEY `Index_tdepslip` (`docno`,`dateid`,`acnoid`)
    // ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    //     $this->coreFunctions->sbccreatetable("tdepslip", $qry);


    $qry = "CREATE TABLE  `tchead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `amount` decimal(18,6) NOT NULL DEFAULT '0.00',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_tchead` (`docno`,`dateid`)) 
      ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("tchead", $qry);

    $qry = "CREATE TABLE  `htchead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `amount` decimal(18,6) NOT NULL DEFAULT '0.00',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_htchead` (`docno`,`dateid`)) 
      ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("htchead", $qry);

    $qry = "CREATE TABLE  `tcdetail` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(250) NOT NULL DEFAULT '',
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `amount` decimal(18,6) NOT NULL DEFAULT '0.00',
      `deduction` decimal(18,6) NOT NULL DEFAULT '0.00',
      `balance` decimal(18,6) NOT NULL DEFAULT '0.00',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("tcdetail", $qry);

    $qry = "CREATE TABLE  `htcdetail` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(250) NOT NULL DEFAULT '',
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `amount` decimal(18,6) NOT NULL DEFAULT '0.00',
      `deduction` decimal(18,6) NOT NULL DEFAULT '0.00',
      `balance` decimal(18,6) NOT NULL DEFAULT '0.00',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("htcdetail", $qry);

    $qry = "CREATE TABLE `eod` (
      `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
      `dateid` DATETIME DEFAULT NULL,
      `begbal` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `collection` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `deposit` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `endingbal` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `center` VARCHAR(45) NOT NULL,
      `closeby` VARCHAR(45) NOT NULL,
      PRIMARY KEY (`line`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("eod", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["htcdetail", "tcdetail"], ["isreplenish"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["htcdetail", "tcdetail"], ["replenishdate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["htchead", "tchead"], ["petty", "endingbal"], "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tcoll"], ["depodate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tcoll"], ["client"], "VARCHAR(30) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tcoll"], ["bank"], "VARCHAR(200) not null default ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["eod"], ["cash", "checks"], "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `supplieritem` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `clientid` int(10) UNSIGNED not null default '0',
      `itemid` INT(11) NOT NULL DEFAULT '0',
      `createby` varchar(45) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      PRIMARY KEY (line),
      KEY `Index_clientid` (`clientid`),
      KEY `Index_itemid` (`itemid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("supplieritem", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["item"], ["israwmat"], "tinyint(1) not null default '0'", 0);



    $this->coreFunctions->sbcaddcolumngrp(["arledger"], ["lpaydate"], "datetime DEFAULT NULL");

    $this->coreFunctions->sbcaddcolumngrp(["cehead", "hcehead"], ["rctrno", "rcline"], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("center", "petty", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["joboffer", "hjoboffer"], ["branchid"], "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["htcdetail", "tcdetail"], ["acnoid"], "int(11) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["htcdetail", "tcdetail"], ["empname"], "VARCHAR(200) not null default ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eainfo", "heainfo"], ["sbuid", "voidint"], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eahead", "heahead"], ["releasedate"], "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["ophead", "hophead"], ['email'], "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `street` (
        `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
         `code` VARCHAR(15) NOT NULL DEFAULT '',
        `street` VARCHAR(500) NOT NULL DEFAULT '',
        `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        PRIMARY KEY (`line`))";
    $this->coreFunctions->sbccreatetable("street", $qry);

    $qry = "CREATE TABLE `locclearance` (
        `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `clearance` VARCHAR(500) NOT NULL DEFAULT '',
        `price` DECIMAL(18,6) NOT NULL DEFAULT 0,
        `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        PRIMARY KEY (`line`))";
    $this->coreFunctions->sbccreatetable("locclearance", $qry);

    $qry = "CREATE TABLE `businesstype` (
        `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `businesstype` VARCHAR(500) NOT NULL DEFAULT '',
        `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        PRIMARY KEY (`line`))";
    $this->coreFunctions->sbccreatetable("businesstype", $qry);


    $qry = "CREATE TABLE rdhead like krhead";
    $this->coreFunctions->sbccreatetable("rdhead", $qry);

    $qry = "CREATE TABLE hrdhead like hkrhead";
    $this->coreFunctions->sbccreatetable("hrdhead", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["rdhead", "hrdhead"], ["acnoid"], "int(11) NOT NULL DEFAULT '0'", 0);

    // $this->coreFunctions->sbcdroptable("behead");
    // $this->coreFunctions->sbcdroptable("hbehead");
    // $this->coreFunctions->sbcdroptable("bedetail");
    // $this->coreFunctions->sbcdroptable("hbedetail");

    $qry = "CREATE TABLE  `loansum` (
        `trno` int(10) unsigned NOT NULL,
        `dateid` datetime DEFAULT NULL,
        `amount` decimal(18,2) NOT NULL DEFAULT '0.00',
        `loantype` int(10) unsigned NOT NULL,
        PRIMARY KEY (`trno`));";

    $this->coreFunctions->sbccreatetable("loansum", $qry);

    $this->coreFunctions->sbcaddcolumn('item', 'iswireitem', "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['item'], ['startwire', 'endwire'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['item'], ['namt', 'namt2', 'nfamt', 'namt4', 'namt5', 'namt6', 'namt7'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['lastock', 'glstock'], ['startwire', 'endwire', 'agentamt'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['lastock', 'glstock'], ['porefx', 'polinex', 'sjrefx', 'sjlinex'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['postock', 'hpostock'], ['sjqa'], "decimal(19,10) NOT NULL DEFAULT '0.0000000000'", 0);
    $this->coreFunctions->sbcaddcolumn('attendee', 'isinactive', "tinyint(1) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE  `pxhead` (
        `trno` int(10) unsigned NOT NULL,
        `doc` VARCHAR(5) NOT NULL DEFAULT '',
        `project` varchar(150) NOT NULL DEFAULT '',
        `projectid` varchar(50) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `docno` VARCHAR(20) NOT NULL DEFAULT '',        
        `dtcno` VARCHAR(25) NOT NULL DEFAULT '',
        `pcfno` VARCHAR(25) NOT NULL DEFAULT '',
        `poref` VARCHAR(50) NOT NULL DEFAULT '',
        `aftistock` TINYINT(1) NOT NULL DEFAULT '0',
        `fullcomm` TINYINT(1) NOT NULL DEFAULT '0',
        `rem` VARCHAR(250) NOT NULL DEFAULT '',
        `agentid` int(10) NOT NULL DEFAULT '0',
        `clientid` int(10) NOT NULL DEFAULT '0',
        `clientname` varchar(150) NOT NULL DEFAULT '',
        `oandaphpusd` decimal(18,6) NOT NULL DEFAULT '0.00',
        `oandausdphp` decimal(18,6) NOT NULL DEFAULT '0.00',
        `osphpusd` decimal(18,6) NOT NULL DEFAULT '0.00',
        `percentage` VARCHAR(5) NOT NULL DEFAULT '',
         `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        `viewby` varchar(100) not null default '',
        `viewdate` datetime default null,
        `lockuser` varchar(50) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`));";

    $this->coreFunctions->sbccreatetable("pxhead", $qry);


    $qry = "CREATE TABLE  `hpxhead` (
         `trno` int(10) unsigned NOT NULL,
         `doc` VARCHAR(5) NOT NULL DEFAULT '',
        `project` varchar(150) NOT NULL DEFAULT '',
        `projectid` varchar(50) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `docno` VARCHAR(20) NOT NULL DEFAULT '',        
        `dtcno` VARCHAR(25) NOT NULL DEFAULT '',
        `pcfno` VARCHAR(25) NOT NULL DEFAULT '',
        `poref` VARCHAR(50) NOT NULL DEFAULT '',
        `aftistock` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `fullcomm` TINYINT(1) unsigned NOT NULL DEFAULT '0',
        `rem` VARCHAR(250) NOT NULL DEFAULT '',
        `agentid` int(10) NOT NULL DEFAULT '0',
        `clientid` int(10) NOT NULL DEFAULT '0',
        `clientname` varchar(150) NOT NULL DEFAULT '',
        `oandaphpusd` decimal(18,6) NOT NULL DEFAULT '0.00',
        `oandausdphp` decimal(18,6) NOT NULL DEFAULT '0.00',
        `osphpusd` decimal(18,6) NOT NULL DEFAULT '0.00',
        `percentage` VARCHAR(5) NOT NULL DEFAULT '',
         `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        `viewby` varchar(100) not null default '',
        `viewdate` datetime default null,
        `lockuser` varchar(50) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        PRIMARY KEY (`trno`));";

    $this->coreFunctions->sbccreatetable("hpxhead", $qry);


    $qry = "CREATE TABLE  `pxstock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` varchar(250) NOT NULL DEFAULT '',
      `rrqty` decimal(18,6) NOT NULL DEFAULT '0.00',
      `rrcost` decimal(18,6) NOT NULL DEFAULT '0.00',
      `ext` decimal(18,6) NOT NULL DEFAULT '0.00',
      `srp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `totalsrp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `tp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `totaltp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(100) DEFAULT '',
      `sortline`  int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pxstock", $qry);


    $qry = "CREATE TABLE  `hpxstock` (
       `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` varchar(250) NOT NULL DEFAULT '',
      `rrqty` decimal(18,6) NOT NULL DEFAULT '0.00',
      `rrcost` decimal(18,6) NOT NULL DEFAULT '0.00',
      `ext` decimal(18,6) NOT NULL DEFAULT '0.00',
      `srp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `totalsrp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `tp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `totaltp` decimal(18,6) NOT NULL DEFAULT '0.00',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(100) DEFAULT '',
      `sortline`  int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hpxstock", $qry);

    $qry = "CREATE TABLE  `pxchecking` (
        `trno` bigint(20) unsigned NOT NULL,
        `line` int(10) unsigned NOT NULL,
        `expense` INT(11) NOT NULL DEFAULT '0',
        `budget` decimal(18,2) NOT NULL DEFAULT '0.00',
        `actual` decimal(18,2) NOT NULL DEFAULT '0.00',
        `rem` varchar(250) NOT NULL DEFAULT '',
        `reftrno` int(10) NOT NULL DEFAULT '0',
        PRIMARY KEY (`trno`,`line`),
        KEY `Index_trno` (`trno`),
        KEY `Index_line` (`line`)) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("pxchecking", $qry);

    $qry = "CREATE TABLE  `hpxchecking` (
      `trno` bigint(20) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `expense` INT(11) NOT NULL DEFAULT '0',
      `budget` decimal(18,2) NOT NULL DEFAULT '0.00',
      `actual` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rem` varchar(250) NOT NULL DEFAULT '',
      `reftrno` int(10) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hpxchecking", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["rostock", "hrostock"], ["iseq"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["dependents", "adependents"], ["schoollevel", "occupation"],  "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxchecking", "hpxchecking"], ["editdate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxchecking", "hpxchecking"], ["editby"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxstock", "hpxstock"], ["editby", "encodedby"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcdropcolumngrp(["pxchecking", "hpxchecking"], ['expense']);
    $this->coreFunctions->sbcaddcolumngrp(["pxchecking", "hpxchecking"], ["expenseid"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["potrno"], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["fullcomm"], "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['pxhead', 'hpxhead'], ['commamt'], "decimal(19,10) NOT NULL DEFAULT '0.0000000000'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["remarks"], "varchar(250) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["terms", "termsdetails"], "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["checkdate"], "datetime", 0);


    $qry = "CREATE TABLE `pcfcur` (
    `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `oandaphpusd` DECIMAL(18,6) NOT NULL,
    `oandausdphp` DECIMAL(18,6) NOT NULL,
    `osphpusd` DECIMAL(18,6) NOT NULL,
    `dateid` DATETIME,
    `createby` VARCHAR(100) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
    PRIMARY KEY (`line`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("pcfcur", $qry);

    $qry = "CREATE TABLE `roso` (
      `trno` bigint(20) unsigned NOT NULL,
      `sotrno` bigint(20) unsigned NOT NULL,
      `diesel` decimal(18,2) NOT NULL DEFAULT '0.00',
      `distance` decimal(18,2) NOT NULL DEFAULT '0.00',
      `iseq` INT(11) unsigned NOT NULL,
      PRIMARY KEY (`trno`,`sotrno`),
      KEY `Index_trno` (`trno`),
      KEY `Index_sotrno` (`sotrno`)) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("roso", $qry);

    $qry = "CREATE TABLE `hroso` LIKE `roso`";
    $this->coreFunctions->sbccreatetable("hroso", $qry);
    $this->coreFunctions->sbcaddcolumn("client", "ishold", "tinyint(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["item"], ["aveleadtime", "maxleadtime"], "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `rhhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(100) NOT NULL DEFAULT '',
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `agent` varchar(20) DEFAULT NULL,
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_rhhead` (`docno`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("rhhead", $qry);

    $qry = "CREATE TABLE  `hrhhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(100) NOT NULL DEFAULT '',
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `agent` varchar(20) DEFAULT NULL,
      `rem` varchar(500) NOT NULL DEFAULT '',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_hrhhead` (`docno`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("hrhhead", $qry);

    $qry = "CREATE TABLE  `rhdetail` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `amount` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `ortrno` bigint(20) NOT NULL DEFAULT '0',
      `orline` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(250) NOT NULL DEFAULT '',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `clientid` int(11) NOT NULL DEFAULT '0',
      `bank` varchar(50) NOT NULL DEFAULT '',
      `branch` varchar(50) NOT NULL DEFAULT '',
      `rdtrno` int(11) NOT NULL DEFAULT '0',
      `retrno` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rhdetail", $qry);

    $qry = "CREATE TABLE  `hrhdetail` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `amount` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `ortrno` bigint(20) NOT NULL DEFAULT '0',
      `orline` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(250) NOT NULL DEFAULT '',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `clientid` int(11) NOT NULL DEFAULT '0',
      `bank` varchar(50) NOT NULL DEFAULT '',
      `branch` varchar(50) NOT NULL DEFAULT '',
      `rdtrno` int(11) NOT NULL DEFAULT '0',
      `retrno` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrhdetail", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["headinfotrans", "hheadinfotrans"], ["dtctrno"], "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["users"], ["username"], "varchar(30) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["isreported"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["ied", "bankcharges", "interest", "brokerfee", "arrastre"], "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["bpo", "ctnsno"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `certrate` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `amt1` decimal(18,2) NOT NULL DEFAULT '0.00',
            `amt2` decimal(18,2) NOT NULL DEFAULT '0.00',
            `crate` decimal(18,2) NOT NULL DEFAULT '0.00',
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`), 
            KEY `IndexLine` (`line`))
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("certrate", $qry);

    $this->coreFunctions->sbcaddcolumn('item', 'isreversewireitem', "tinyint(1) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `waims_attachments` (
            `trno` INTEGER UNSIGNED NOT NULL,
            `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
            `doc` VARCHAR(45) NOT NULL DEFAULT '',
            `title` VARCHAR(100) NOT NULL DEFAULT '',
            `picture` VARCHAR(100) NOT NULL DEFAULT '',
            PRIMARY KEY (`line`, `trno`, `doc`)
          )
          ENGINE = InnoDB;";
    $this->coreFunctions->sbccreatetable("waims_attachments", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["item"], ['disc', 'disc2', 'disc3', 'disc4', 'disc5', 'disc7'], 'varchar(100) NOT NULL DEFAULT ""', 1);

    $this->coreFunctions->sbcaddcolumngrp(["prstock", "hprstock"], ["sku"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["rqtrno"], "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["invoicedate"], "DATETIME DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("cntnum", 'iscsv', "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["ewtrate"], "decimal(18,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["ewtlist"], ["rate"], "decimal(18,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["krhead", "hkrhead"], ["disc"], "VARCHAR(15) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE  `tmhead` (
    `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` int(10) unsigned NOT NULL,
    `systype` int(10) unsigned NOT NULL,
    `tasktype` int(10) unsigned NOT NULL,
    `rate` varchar(10) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `requestby` int(10) unsigned NOT NULL,
    `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `createby` varchar(100) NOT NULL,
    `editby` varchar(100) NOT NULL,
    `editdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`) USING BTREE,
    KEY `Index_2` (`clientid`),
    KEY `Index_3` (`systype`,`tasktype`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("tmhead", $qry);
    $this->coreFunctions->execqrynolog("ALTER TABLE tmhead CHANGE editby editby varchar(100) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE  `tmdetail` (
    `trno` int(10) unsigned NOT NULL,
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `task` mediumtext,
    `userid` int(10) unsigned NOT NULL,
    `startdate` datetime DEFAULT NULL,
    `enddate` datetime DEFAULT NULL,
    `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `encodedby` varchar(100) NOT NULL,
    `editby` varchar(100) NOT NULL,
    `editdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`,`line`),
    KEY `Index_2` (`userid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("tmdetail", $qry);
    $this->coreFunctions->execqrynolog("ALTER TABLE tmdetail CHANGE editby editby varchar(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->execqrynolog("ALTER TABLE tmdetail CHANGE encodedby encodedby varchar(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->execqrynolog("ALTER TABLE tmdetail CHANGE `line` `line` int(10) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("tmhead", "rem", "VARCHAR(250) NOT NULL DEFAULT ''", 0);
    // $this->coreFunctions->sbcaddcolumngrp(["tmdetail"], ["percentage"], "varchar(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tmdetail"], ["percentage"], "decimal(18,2) NOT NULL DEFAULT '0'", 1);
    // $this->coreFunctions->execqrynolog("ALTER TABLE tmdetail CHANGE percentage percentage decimal(18,2) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumngrp(["tmdetail"], ["title"], "varchar(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("waims_attachments", "tmline", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["headprrem"], ["tmtrno", "tmline"], "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("tmhead", "status", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("tmdetail", "status", "int(10) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `voidtm` (
    `trno` int(10) unsigned NOT NULL,
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `task` mediumtext,
    `title` varchar(150) not null default '',
    `userid` int(10) unsigned NOT NULL default 0,
    `startdate` datetime DEFAULT NULL,
    `enddate` datetime DEFAULT NULL,
    `voiddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `voidby` varchar(100) NOT NULL default '',
    `linex` int(10) unsigned NOT NULL default 0,
    `status` int(10) unsigned NOT NULL default 0,
    `percentage` decimal(18,2) not null default 0,
    PRIMARY KEY (`trno`,`line`),
    KEY `Index_2` (`userid`,`linex`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("voidtm", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["tmdetail"], ["acceptdate", "fcheckingdate"], "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(["headprrem"], ["seendate"], "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(["tmhead"], ["completedate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tmhead"], ["amount"], "decimal(18,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->execqrynolog("ALTER TABLE tmhead CHANGE rate rate decimal(18,2) NOT NULL DEFAULT '0'");


    $qry = " CREATE TABLE `sjexp` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `pxtrno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `pxline` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `amount` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `rem` varchar(200) NOT NULL default '',
      `editby` varchar(100) NOT NULL default '',
      `editdate` datetime DEFAULT NULL,
      INDEX `Index_1`(`trno`),
      INDEX `Index_2`(`pxtrno`, `pxline`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("sjexp", $qry);

    $qry = "CREATE TABLE  `hsistock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `itemname` varchar(500) NOT NULL DEFAULT '',
      `uom` varchar(20) NOT NULL DEFAULT '',
      `whid` int(10) NOT NULL DEFAULT '0',
      `disc` varchar(20) NOT NULL DEFAULT '',
      `rem` varchar(200) NOT NULL DEFAULT '',
      `amt` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `isqty` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `isamt` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `ext` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `void` tinyint(1) NOT NULL DEFAULT '0',
      `encodedby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `editdate` datetime DEFAULT NULL,
      `sortline` int(10) NOT NULL DEFAULT '0',
      `noprint` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_2` (`itemid`),
      KEY `Index_3` (`whid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsistock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["sistock", "hsistock"], ["sortline", "whid", "itemid"], "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["sistock", "hsistock"], ["noprint"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumngrp(["sistock"], ["wh", "barcode"]);

    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["cmtrno"], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["islost"], "tinyint(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['lastock', 'glstock'], ['rrfactor'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["lostdate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pxhead", "hpxhead"], ["reason"], "varchar(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("sku", "groupid", "varchar(250) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['sku'], ["issku", "issupplier"], "TINYINT(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn('item', 'iscomponent', "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['sku'], ["uom3", "uom2"], " VARCHAR(50) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(['lahead', 'glhead'], ['rrfactor'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['uom'], ["printuom"], " VARCHAR(25) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(['lahead', 'glhead'], ['rrfactor'], "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcdropcolumngrp(["lastock", "glstock"], ["rrfactor"]);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["cline"], "int(11) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE  `dailytask` (
      `trno` bigint(20) NOT NULL AUTO_INCREMENT,
      `tasktrno` int(11) NOT NULL DEFAULT '0',
      `taskline` int(11) NOT NULL DEFAULT '0',
      `reftrno` int(11) NOT NULL DEFAULT '0',
      `rem` text,
      `amt` decimal(18,2) NOT NULL DEFAULT '0.000000',
      `clientid` int(11) NOT NULL DEFAULT '0',
      `userid` int(11) NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `donedate` datetime DEFAULT NULL,
      `createdate` datetime DEFAULT NULL,
      `statid` int(2) NOT NULL DEFAULT '0',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(100) NOT NULL DEFAULT '',
      `apvtrno` int(11) NOT NULL DEFAULT '0',
      `jono` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      KEY `Index_Task` (`tasktrno`,`taskline`),
      KEY `Index_RefTrno` (`reftrno`),
      KEY `Index_Status` (`statid`),
      KEY `Index_Apv` (`apvtrno`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 ";
    $this->coreFunctions->sbccreatetable("dailytask", $qry);


    $qry = "CREATE TABLE  `hdailytask` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `tasktrno` int(11) NOT NULL DEFAULT '0',
      `taskline` int(11) NOT NULL DEFAULT '0',
      `reftrno` int(11) NOT NULL DEFAULT '0',
      `rem` text,
      `amt` decimal(18,2) NOT NULL DEFAULT '0.000000',
      `clientid` int(11) NOT NULL DEFAULT '0',
      `userid` int(11) NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `donedate` datetime DEFAULT NULL,
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(100) NOT NULL DEFAULT '',      
      `apvtrno` int(11) NOT NULL DEFAULT '0',
      `createdate` datetime DEFAULT NULL,
      `statid` int(2) NOT NULL DEFAULT '0',
      `jono` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      KEY `Index_Task` (`tasktrno`,`taskline`),
      KEY `Index_RefTrno` (`reftrno`),
      KEY `Index_Status` (`statid`),
      KEY `Index_Apv` (`apvtrno`)) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hdailytask", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask", "hdailytask"], ["isprev", "ischecker"], "tinyint(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask", "hdailytask"], ["encodeddate", "editdate"], "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask", "hdailytask"], ["createdate", "startchecker"], "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask", "hdailytask"], ["editby", "reseller"], "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["cntnum"], ["printcheck"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask", "hdailytask"], ["empid", "origtrno", "refx"], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["tmhead"], ["checkerid"], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["headprrem"], ["dytrno"], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["headprrem"], ["touser"], "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask", "hdailytask"], ["rem1"], "varchar(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask"], ["isneglect"], "tinyint(1) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `task_log` LIKE masterfile_log";
    $this->coreFunctions->sbccreatetable("task_log", $qry);

    $qry = "CREATE TABLE `del_task_log` LIKE del_masterfile_log";
    $this->coreFunctions->sbccreatetable("del_task_log", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["reqcategory"], ["isservice", "iscounter", "isinactive"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("reqcategory", "color", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["reqcategory"], ["picpath", "filename"], "varchar(250) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcdropcolumn('item', 'iscomponent');
    $this->coreFunctions->sbcaddcolumn('item', 'isfg', "tinyint(1) NOT NULL DEFAULT '0'", 0);


    $qry = " CREATE TABLE `counterservice` (
      `counterline` INT(11) NOT NULL DEFAULT 0,
      `serviceline` INT(11) NOT NULL DEFAULT 0,
      `encodedby` varchar(100) NOT NULL DEFAULT '',
      `encodedate` datetime DEFAULT NULL,
      KEY `Index_counter` (`counterline`),
      KEY `Index_service` (`serviceline`)
    )
    ENGINE = InnoDB;";
    $this->coreFunctions->sbccreatetable("counterservice", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["qshead", "hqshead"], ["projid"], "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["qshead", "hqshead"], ["address1"], "varchar(300) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["qshead", "hqshead"], ["cperson"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["qshead", "hqshead"], ["rem2"], "varchar(500) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["qshead", "hqshead"], ["contactno"], "varchar(25) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `currentservice` (
      line int(11) unsigned NOT NULL AUTO_INCREMENT,
      serviceline int(11) unsigned NOT NULL DEFAULT '0',
      ctr int(11) unsigned NOT NULL DEFAULT '0',
      counterline int(11) unsigned NOT NULL DEFAULT '0',
      isdone int(4) unsigned NOT NULL DEFAULT '0',
      ishold int(4) unsigned NOT NULL DEFAULT '0',
      iscancel int(4) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (line),
      KEY Index_currentservice (serviceline,`counterline`) USING BTREE
    ) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("currentservice", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["tmdetail", "dailytask", "hdailytask", "voidtm"], ['taskcatid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["tmdetail", "dailytask", "hdailytask", "voidtm"], ['complexity'], "varchar(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn('reqcategory', 'groupname', "varchar(250) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["glhead", "lahead"], ['isfee'], "tinyint(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['currentservice', 'hcurrentservice'], ['ispwd', 'isskip'], "int(4) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['currentservice'], ['dateid', 'startdate', 'enddate'], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(['currentservice', 'hcurrentservice'], ['users'], "varchar(100) NOT NULL DEFAULT ''", 0);

    $qry = "ALTER TABLE `currentservice` DROP INDEX `Index_currentservice`,
    ADD INDEX `Index_currentservice` USING BTREE(`serviceline`, `counterline`, `dateid`);";
    $this->coreFunctions->execqrynolog($qry);



    $qry = "CREATE TABLE  `credithead` (
    `trno` bigint(20) NOT NULL AUTO_INCREMENT,
    `userid` int(10) unsigned NOT NULL,
    `dateid` datetime DEFAULT NULL,
    `totalhrs` decimal(18,2) NOT NULL DEFAULT '0.00',
    `totalpts` decimal(18,2) NOT NULL DEFAULT '0.00',
    `totalrt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `createby` VARCHAR(150) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_trno` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("credithead", $qry);

    $qry = "CREATE TABLE  `creditdetail` (
    `trno` bigint(20) NOT NULL,
    `line` int(11) NOT NULL DEFAULT '0',
    `dytrno` int(11) NOT NULL DEFAULT '0',
    `dateid` datetime DEFAULT NULL,
    `totalhrs` decimal(18,2) NOT NULL DEFAULT '0.00',
    `totalpts` decimal(18,2) NOT NULL DEFAULT '0.00',
    `totalrt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `createby` VARCHAR(150) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
      KEY `Index_trno` (`trno`,`line`),
      KEY `Index_dytrno` (`dytrno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("creditdetail", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["credithead", "creditdetail"], ["createdate"], "datetime DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumngrp(["credithead", "creditdetail"], ["createby"], "varchar(150) NOT NULL DEFAULT ''");


    $this->coreFunctions->sbcdroptable("infra");

    $this->coreFunctions->sbcaddcolumngrp(["client"], ["isinfra"], "TINYINT(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['infratype'], "VARCHAR(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(['client'], ["regdate"], "datetime DEFAULT NULL");


    $qry = "CREATE TABLE `hcurrentservice` (
      `line` int unsigned NOT NULL DEFAULT '0',
      `serviceline` int unsigned NOT NULL DEFAULT '0',
      `ctr` int unsigned NOT NULL DEFAULT '0',
      `counterline` int unsigned NOT NULL DEFAULT '0',
      `isdone` int unsigned NOT NULL DEFAULT '0',
      `ishold` int unsigned NOT NULL DEFAULT '0',
      `iscancel` int unsigned NOT NULL DEFAULT '0',
      `ispwd` int NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `startdate` datetime DEFAULT NULL,
      `enddate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`),
      KEY `Index_hcurrentservice` (`serviceline`,`counterline`,`dateid`) USING BTREE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hcurrentservice", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["billingaddr"], ["address1"], "varchar(300) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["billingaddr"], ["cperson"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["billingaddr"], ["contactno2"], "varchar(25) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["contacts"], ["isownermember", "ishouseholdmm"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["dailytask", "hdailytask"], ["assignedid"], "int(10) unsigned NOT NULL", 0);
  }
}
