<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class pos
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

  public function defaultposdata($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'AIMSPOS' || $systemtype == 'MISPOS') {
      $result = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", ['WALK-IN']);
      if (!$result) {
        $dlock = $this->othersClass->getCurrentTimeStamp();
        $this->coreFunctions->sbcinsert("client", ['client' => 'WALK-IN', 'issynced' => 1, 'iscustomer' => 1, 'status' => 'ACTIVE', 'dlock' => $dlock]);
      }

      $defaultitems = [
        '*', //Sub total discount
        '#', //AR payment
        '$', //service charge
        '**', //open item
        '***', //Line Subtotal discont
        '$$', //Delivery Charge
        '$$$',
        '##' // Promo - General Line item (MIS)
      ];

      foreach ($defaultitems as $key) {
        // $this->coreFunctions->LogConsole($key);

        $exist = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$key], '', true);
        if (!$exist) {
          $data  = [
            'barcode' => $key,
            'uom' => 'pc',
            'isinactive' => 1,
            'ispositem' => 1,
            'isvat' => 0,
            'isprintable' => 1,
            'isnoninv' => 1,
            'isreserved' => 1,
            'dlock' => $this->othersClass->getCurrentTimeStamp()
          ];
          $this->coreFunctions->sbcinsert("item", $data);
        }
      }

      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('#','PCS',1,1,0,1,1,now())
      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('$','PCS',1,1,0,0,1,now())
      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('*','PCS',1,1,0,1,1,now())
      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('**','PCS',1,1,0,1,1,now())
      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('***','PCS',1,1,0,1,1,now())
      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('$$','PCS',1,1,0,0,1,now())
      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('$$$','PCS',1,1,1,0,1,now())
      // INSERT INTO item (barcode,uom,isinactive,ispositem,isvat,isprintable,isnoninv,dlock) VALUES ('##','PCS',1,1,1,0,1,now())
    }
  }

  public function tableupdatepos($config)
  {
    ini_set('max_execution_time', 0);

    $this->coreFunctions->sbcaddcolumn("item", "channel", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("item", "isreserved", "TINYINT(2) NOT NULL DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("item", "aimsid", "INT(11) NOT NULL DEFAULT 0", 0);

    $this->coreFunctions->sbcaddcolumn("head", "webtrno", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("center", "branchid", "INT(11) NOT NULL DEFAULT '0'");

    $this->defaultposdata($config);

    $qry = "CREATE TABLE  `branchbank` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `clientid` int(10) NOT NULL DEFAULT '0',
      `terminalid` varchar(15) NOT NULL DEFAULT '',
      `bank` varchar(50) NOT NULL DEFAULT '',
      `dlock` datetime DEFAULT NULL,
      `isinactive` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `acnoID` varchar(35) NOT NULL DEFAULT '',
      `isok` tinyint(1) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`,`clientid`,`terminalid`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("branchbank", $qry);

    $this->coreFunctions->sbcaddcolumn("branchbank", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("branchbank", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE  `bankcharges` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `clientid` int(10) NOT NULL DEFAULT '0',
      `acnoid` int(10) NOT NULL DEFAULT '0',
      `terminalid` varchar(45) NOT NULL DEFAULT '',
      `rate` varchar(30) NOT NULL DEFAULT '',
      `type` varchar(50) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(25) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(25) NOT NULL DEFAULT '',
      `dlock` datetime DEFAULT NULL,
      `inactive` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `ewt` varchar(25) NOT NULL DEFAULT '',
      `monthstype` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("bankcharges", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["bankcharges"], ["monthstype", "terminalid"], "varchar(45) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE  `cardtype` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `cardtype` varchar(45) NOT NULL DEFAULT '',
      `dlock` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cardtype", $qry);

    $this->coreFunctions->sbcaddcolumn("cardtype", "isinactive", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cardtype", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cardtype", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE  `checktypes` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `type` varchar(50) NOT NULL DEFAULT '',
      `clientid` INTEGER(10) NOT NULL DEFAULT '0',
      `acnoid` INTEGER(10) NOT NULL DEFAULT '0',
      `dlock` datetime DEFAULT NULL,
      `inactive` tinyint(1) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("checktypes", $qry);

    $this->coreFunctions->sbcaddcolumn("checktypes", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("checktypes", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE `pphead` (
       `trno` int(11) unsigned NOT NULL DEFAULT '0',
       `doc` varchar(3) NOT NULL DEFAULT '',
       `docno` varchar(20) NOT NULL DEFAULT '',
       `dateid` datetime DEFAULT NULL,
       `due` datetime DEFAULT NULL,
       `rem` varchar(255) NOT NULL DEFAULT '',
       `branchid` int(11) unsigned NOT NULL DEFAULT '0',
       `yourref` varchar(50) NOT NULL DEFAULT '',
       `ourref` varchar(50) NOT NULL DEFAULT '',
       `issm` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isqty` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isamt` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isbuy1` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `createdate` datetime DEFAULT NULL,
       `createby` varchar(100) NOT NULL DEFAULT '',
       `editby` varchar(100) NOT NULL DEFAULT '',
       `editdate` datetime DEFAULT NULL,
       `viewby` varchar(100) NOT NULL DEFAULT '',
       `viewdate` datetime DEFAULT NULL,
       `lockuser` varchar(100) NOT NULL DEFAULT '',
       `lockdate` datetime DEFAULT NULL,
       PRIMARY KEY (trno),
       KEY Index_BranchID (branchid),
       KEY Index_DateID (dateid)
       ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pphead", $qry);

    $qry = "CREATE TABLE `ppstock` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `itemid` int(11) unsigned NOT NULL DEFAULT '0',
      `pstart` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pqty` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pend` decimal(18,2) NOT NULL DEFAULT '0.00',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (trno,line),
      KEY Index_Itemid (itemid)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("ppstock", $qry);

    $qry = "CREATE TABLE `hpphead` (
       `trno` int(11) unsigned NOT NULL DEFAULT '0',
       `doc` varchar(3) NOT NULL DEFAULT '',
       `docno` varchar(20) NOT NULL DEFAULT '',
       `dateid` datetime DEFAULT NULL,
       `due` datetime DEFAULT NULL,
       `rem` varchar(255) NOT NULL DEFAULT '',
       `branchid` int(11) unsigned NOT NULL DEFAULT '0',
       `yourref` varchar(50) NOT NULL DEFAULT '',
       `ourref` varchar(50) NOT NULL DEFAULT '',
       `issm` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isqty` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isamt` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isbuy1` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `createdate` datetime DEFAULT NULL,
       `createby` varchar(100) NOT NULL DEFAULT '',
       `editby` varchar(100) NOT NULL DEFAULT '',
       `editdate` datetime DEFAULT NULL,
       `viewby` varchar(100) NOT NULL DEFAULT '',
       `viewdate` datetime DEFAULT NULL,
       `lockuser` varchar(100) NOT NULL DEFAULT '',
       `lockdate` datetime DEFAULT NULL,
       PRIMARY KEY (trno),
       KEY Index_BranchID (branchid),
       KEY Index_DateID (dateid)
       ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hpphead", $qry);

    $qry = "CREATE TABLE `hppstock` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `itemid` int(11) unsigned NOT NULL DEFAULT '0',
      `pstart` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pqty` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pend` decimal(18,2) NOT NULL DEFAULT '0.00',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (trno,line),
      KEY Index_Itemid (itemid)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hppstock", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["pphead", "hpphead"], ["docno"], "varchar(20) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["pohead", "hpohead"], ["expiration"], "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE `ppbranch` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `clientid` int(11) unsigned NOT NULL DEFAULT '0',
      `isok` tinyint(1) unsigned NOT NULL DEFAULT '0',
      KEY `Index_TrNo` (`trno`),
      KEY `Index_ClientID` (`clientid`),
      KEY `Index_IsOK` (`isok`)
    ) ENGINE=MyISAM;";
    $this->coreFunctions->sbccreatetable("ppbranch", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["ppbranch"], ["isokvoid"], "TINYINT(2) NOT NULL DEFAULT 0", 0);

    $qry = "CREATE TABLE `pphead` (
       `trno` int(11) unsigned NOT NULL DEFAULT '0',
       `doc` varchar(3) NOT NULL DEFAULT '',
       `docno` varchar(20) NOT NULL DEFAULT '',
       `dateid` datetime DEFAULT NULL,
       `due` datetime DEFAULT NULL,
       `rem` varchar(255) NOT NULL DEFAULT '',
       `branchid` int(11) unsigned NOT NULL DEFAULT '0',
       `yourref` varchar(50) NOT NULL DEFAULT '',
       `ourref` varchar(50) NOT NULL DEFAULT '',
       `issm` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isqty` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isamt` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isbuy1` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `createdate` datetime DEFAULT NULL,
       `createby` varchar(100) NOT NULL DEFAULT '',
       `editby` varchar(100) NOT NULL DEFAULT '',
       `editdate` datetime DEFAULT NULL,
       `viewby` varchar(100) NOT NULL DEFAULT '',
       `viewdate` datetime DEFAULT NULL,
       `lockuser` varchar(100) NOT NULL DEFAULT '',
       `lockdate` datetime DEFAULT NULL,
       PRIMARY KEY (trno),
       KEY Index_BranchID (branchid),
       KEY Index_DateID (dateid)
       ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pphead", $qry);

    $qry = "CREATE TABLE `ppstock` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `itemid` int(11) unsigned NOT NULL DEFAULT '0',
      `pstart` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pqty` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pend` decimal(18,2) NOT NULL DEFAULT '0.00',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (trno,line),
      KEY Index_Itemid (itemid)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("ppstock", $qry);

    $qry = "CREATE TABLE `hpphead` (
       `trno` int(11) unsigned NOT NULL DEFAULT '0',
       `doc` varchar(3) NOT NULL DEFAULT '',
       `docno` varchar(20) NOT NULL DEFAULT '',
       `dateid` datetime DEFAULT NULL,
       `due` datetime DEFAULT NULL,
       `rem` varchar(255) NOT NULL DEFAULT '',
       `branchid` int(11) unsigned NOT NULL DEFAULT '0',
       `yourref` varchar(50) NOT NULL DEFAULT '',
       `ourref` varchar(50) NOT NULL DEFAULT '',
       `issm` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isqty` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isamt` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `isbuy1` tinyint(1) unsigned NOT NULL DEFAULT '0',
       `createdate` datetime DEFAULT NULL,
       `createby` varchar(100) NOT NULL DEFAULT '',
       `editby` varchar(100) NOT NULL DEFAULT '',
       `editdate` datetime DEFAULT NULL,
       `viewby` varchar(100) NOT NULL DEFAULT '',
       `viewdate` datetime DEFAULT NULL,
       `lockuser` varchar(100) NOT NULL DEFAULT '',
       `lockdate` datetime DEFAULT NULL,
       PRIMARY KEY (trno),
       KEY Index_BranchID (branchid),
       KEY Index_DateID (dateid)
       ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hpphead", $qry);

    $qry = "CREATE TABLE `hppstock` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `itemid` int(11) unsigned NOT NULL DEFAULT '0',
      `pstart` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pqty` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pend` decimal(18,2) NOT NULL DEFAULT '0.00',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (trno,line),
      KEY Index_Itemid (itemid)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hppstock", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["pphead", "hpphead"], ["docno"], "varchar(20) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["pphead", "hpphead"], ["isall"], "TINYINT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["pohead", "hpohead"], ["expiration"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("itemlevel", "branchid", "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `supplierlist` (
      `line` int(10) unsigned not null auto_increment,
      `itemid` int(11) unsigned NOT NULL DEFAULT '0',
      `clientid` int(11) unsigned NOT NULL DEFAULT '0',
      `remarks` varchar(150) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(100) NOT NULL DEFAULT '',
      `startdate` date DEFAULT NULL,
      `enddate` date DEFAULT NULL,
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(100) NOT NULL DEFAULT '',
      PRIMARY KEY (line,itemid,clientid),
      KEY Index_Itemid (itemid),
      KEY Index_Clientid (clientid)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("supplierlist", $qry);

    $qry = "CREATE TABLE  `commissionlist` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `clientid` int(11) unsigned NOT NULL DEFAULT '0',
      `comm1` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `comm2` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `comm3` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `startdate` datetime DEFAULT NULL,
      `enddate` datetime DEFAULT NULL,
      `createby` varchar(45) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `editby` varchar(45) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `remarks` varchar(100) NOT NULL DEFAULT '',
      PRIMARY KEY (line),
      KEY Index_clientid (clientid)
      ) ENGINE=MyISAM ;";
    $this->coreFunctions->sbccreatetable("commissionlist", $qry);

    // switch ($this->companysetup->getsystemtype($config['params'])) {
    //   case 'AIMSPOS':
    //   case 'MISPOS':
    //     $qry = "ALTER TABLE pricelist DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE (`line`);";
    //     $this->coreFunctions->execqry($qry, 'drop');
    //     break;
    // }

    $this->coreFunctions->sbcaddcolumn("pricelist", "clientid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pricelist"], ["cost", "amount2"], "decimal(18,6) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pricelist"], ["startdate", "enddate", "createdate", "editdate", "dlock"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pricelist"], ["disc"], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("pricelist", "remarks", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["pricelist"], ["createby", "editby"], "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["channel", "banktype", "bankrate", "modepayamt", "gcno", "prodcycle"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["comm1", "comap", "cardcharge", "comm2", "comap2", "netap"], "decimal(18,6) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["terminalid"], "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["pricetype", "comrate"], "varchar(20) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["item"], ["channel", "gender"], "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("head", "lessvat", "decimal(18,4) NOT NULL DEFAULT 0.0000", 0);
    $this->coreFunctions->sbcaddcolumn("head", "cardtype", "varchar(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stock", "lessvat", "decimal(18,4) NOT NULL DEFAULT 0.0000", 1);
    $this->coreFunctions->sbcaddcolumn("stock", "pwdamt", "decimal(18,4) NOT NULL DEFAULT 0.0000", 1);
    $this->coreFunctions->sbcaddcolumn("head", "voidby", "varchar(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("branchusers", "name", "varchar(50)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("branchusers", "password", "varchar(50)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("branchusers", "pincode", "varchar(50)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("branchusers", "pincode2", "varchar(50)  NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("head", "isextracted", "TINYINT(2) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `layaway` (
          `line` INT(10) UNSIGNED NOT NULL DEFAULT '0',
          `seq` INT(11) UNSIGNED NOT NULL DEFAULT '0',
          `bref` VARCHAR(3) NOT NULL DEFAULT '',
          `docno` VARCHAR(20) NOT NULL DEFAULT '',
          `paytype` VARCHAR(45) NOT NULL DEFAULT '',
          `client` VARCHAR(45) NOT NULL DEFAULT '',
          `clientname` VARCHAR(45) NOT NULL DEFAULT '',
          `amt` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
          `bal` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
          `dateid` DATETIME NULL DEFAULT NULL,
          `pickupdate` DATETIME NULL DEFAULT NULL,
          `rem` VARCHAR(100) NOT NULL DEFAULT '',
          `addr` VARCHAR(100) NOT NULL DEFAULT '',
          `dtype` VARCHAR(45) NOT NULL DEFAULT '',
          `cash` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
          `card` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
          `others` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
          `station` VARCHAR(45) NOT NULL DEFAULT '',
          `users` VARCHAR(45) NOT NULL DEFAULT '',
          `carddetail` VARCHAR(500) NOT NULL DEFAULT '',
          `otherdetail` VARCHAR(500) NOT NULL DEFAULT '',
          `createdate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `isok` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
          `siref` VARCHAR(45) NOT NULL DEFAULT '',
          `branch` VARCHAR(45) NOT NULL DEFAULT '',
          `debit` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
          `debitdetail` VARCHAR(500) NOT NULL DEFAULT '',
          `isok2` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
          `isextracted` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
          `webtrno` INT(11) UNSIGNED NOT NULL DEFAULT '0',
          PRIMARY KEY (`line`,`station`,`docno`) USING BTREE
        ) ENGINE=MyISAM ROW_FORMAT=Dynamic;";
    $this->coreFunctions->sbccreatetable("layaway", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["layaway"], ["webtrno"], "INT(11) NOT NULL DEFAULT 0", 0);

    $this->coreFunctions->sbcaddcolumngrp(["head", "journal"], ["deposit", "vatdisc"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["head"], ["returntype"], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["head"], ["depodetail"], "varchar(1000) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["stock", "stockinfo", "hstockinfo"], ["ispromo", "ispa", "isbuy1", "isoverride"], "TINYINT(2) NOT NULL DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stock"], ["cash", "card", "debit", "cheque", "voucher", "deposit"], "decimal(18,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stock", "stockinfo", "hstockinfo"], ["promoref", "overrideby"], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stock", "stockinfo", "hstockinfo"], ["pricetype", "promoby"], "varchar(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stock", "stockinfo", "hstockinfo"], ["promodesc"], "varchar(255) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stock"], ["supplierid"], "INT(11) NOT NULL DEFAULT 0", 0);

    $qry = "CREATE TABLE `expiration` (
          `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `expiry` int(11) unsigned NOT NULL DEFAULT '0',
          `days` varchar(50) NOT NULL DEFAULT '',
          `createby` varchar(45) NOT NULL DEFAULT '',
          `createdate` datetime DEFAULT NULL,
          `editby` varchar(45) NOT NULL DEFAULT '',
          `editdate` datetime DEFAULT NULL,
          PRIMARY KEY (`line`)
        ) ENGINE=MyISAM ROW_FORMAT=Dynamic;";
    $this->coreFunctions->sbccreatetable("expiration", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["pohead", "hpohead"], ["expiryid"], "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["layref"], "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["ladetail", "gldetail"], ["dpref"], "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["client"], ["savingsacct", "expense", "acctadvances"], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["client"], ["isnonbdo"], "TINYINT(2) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["rrtrno", "pvtrno"], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["city"], "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead", "pohead", "hpohead"], ["isfa"], "TINYINT(2) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE kphead like krhead";
    $this->coreFunctions->sbccreatetable("kphead", $qry);
    $qry = "CREATE TABLE hkphead like hkrhead";
    $this->coreFunctions->sbccreatetable("hkphead", $qry);

    $this->coreFunctions->sbcaddcolumn("apledger", "kp", "INT(10) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["serialno"], "varchar(45) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["hpahead", "hpphead"], ["voidby"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["hpahead", "hpphead"], ["voiddate"], "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(["transnum"], ["isokvoid"], "TINYINT(2) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["journal"], ["eod", "extractdate"], "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(["layaway"], ["extractdate"], "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE pos_log like execution_log";
    $this->coreFunctions->sbccreatetable("pos_log", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["item", "uom", "iteminfo", "client", "clientinfo", "coa", "model_masterfile", "part_masterfile", "stockgrp_masterfile", "frontend_ebrands"], ["ismirror"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["item_class", "category_masterfile", "projectmasterfile", "itemcategory", "itemsubcategory"], ["ismirror"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["acdisc", "valoramt", "soloamt"], "decimal(18,4) NOT NULL DEFAULT '0.0000'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["isdiplomat"], "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["journal"], ["maxsales", "excessgc", "acdisc"], "decimal(18,4) NOT NULL DEFAULT '0.0000'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stockinfo", "hstockinfo"], ["ordertype"], "tinyint(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["iscomp"], "tinyint(2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["head"], ["createdate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["head"], ["earnpts", "redeempts", "prevpts", "prevpts", "prevload", "loadwallet", "acdisc", "freight", "truckingcost"], "decimal(18,4) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["head"], ["rfid"], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["head"], ["fname", "mname", "lname"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["head"], ["schoolid"], "INT(11) NOT NULL DEFAULT 0", 0);

    $this->coreFunctions->sbcaddcolumngrp(["stock"], ["isconverted"], "tinyint(2) NOT NULL DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumngrp(["stock"], ["amt1", "amt2", "isqty2", "freight2", "srp"], "decimal(18,4) NOT NULL DEFAULT '0.0000'", 0);
  } //end function
} // end class