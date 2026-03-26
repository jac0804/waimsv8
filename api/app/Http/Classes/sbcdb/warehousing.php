<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use Illuminate\Support\Str;

use App\Http\Classes\coreFunctions;

class warehousing
{

  private $coreFunctions;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
  } //end fn



  public function tableupdatewarehousing()
  {
    $qry = "
    CREATE TABLE `pallet` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(50) NOT NULL DEFAULT '',
      `locid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("pallet", $qry);

    //Status
    //VACANT
    //OCCUPIED - with location
    //FORKLIFT - upon tagging of isextracted (not allow remove item from pallet, allow tagging mine)
    //DROP-OFF - by forklift (remove item from pallet only)
    $this->coreFunctions->sbcaddcolumn("pallet", "status", "VARCHAR(15) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("pallet", "type", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    $qry = "
    CREATE TABLE `floor` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `whid` int(11) NOT NULL DEFAULT '0',
      `floor` int(11) NOT NULL DEFAULT '0',
      `area` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("floor", $qry);

    $this->coreFunctions->sbcaddcolumn("location", "whid", "integer(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("location", "type", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `plhead` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `doc` varchar(2) NOT NULL DEFAULT '',
      `docno` varchar(20) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `rem` varchar(100) NOT NULL DEFAULT '',
      `createdate` datetime NOT NULL,
      `createby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `lockdate` datetime,
      `lockuser` varchar(20) NOT NULL DEFAULT '',
      `viewdate` datetime,
      `viewby` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("plhead", $qry);

    $qry = "CREATE TABLE `hplhead` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `doc` varchar(2) NOT NULL DEFAULT '',
      `docno` varchar(20) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `rem` varchar(100) NOT NULL DEFAULT '',
      `createdate` datetime NOT NULL,
      `createby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `lockdate` datetime,
      `lockuser` varchar(20) NOT NULL DEFAULT '',
      `viewdate` datetime,
      `viewby` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("hplhead", $qry);

    $qry = "CREATE TABLE `plstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(40) NOT NULL DEFAULT '',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(1) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `sku` varchar(45) DEFAULT NULL,
      `loc` varchar(20) NOT NULL DEFAULT '',
      `suppid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("plstock", $qry);

    $qry = "CREATE TABLE `hplstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(40) NOT NULL DEFAULT '',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(1) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `sku` varchar(45) DEFAULT NULL,
      `loc` varchar(20) NOT NULL DEFAULT '',
      `suppid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hplstock", $qry);

    $qry = "CREATE TABLE `wahead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `clientid` int(11) NOT NULL DEFAULT '0',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',
      `shipto` varchar(150) NOT NULL DEFAULT '',
      `tel` varchar(50) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `due` datetime DEFAULT NULL,
      `whid` int(11) NOT NULL DEFAULT '0',
      `terms` varchar(30) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `cur` varchar(3) NOT NULL DEFAULT '',
      `forex` decimal(18,2) NOT NULL DEFAULT '0.00',
      `voiddate` datetime DEFAULT NULL,
      `branch` varchar(30) NOT NULL DEFAULT '',
      `agent` varchar(20) NOT NULL DEFAULT '',
      `isimport` tinyint(4) NOT NULL DEFAULT '0',
      `delby` datetime DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `vattype` varchar(45) NOT NULL DEFAULT '',
      `isapproved` tinyint(4) NOT NULL DEFAULT '0',
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
      `projectid` int(11) NOT NULL DEFAULT '0',
      `subproject` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`clientid`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
    ";
    $this->coreFunctions->sbccreatetable("wahead", $qry);

    $qry = "CREATE TABLE `hwahead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `clientid` int(11) NOT NULL DEFAULT '0',
      `clientname` varchar(150) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',
      `shipto` varchar(150) NOT NULL DEFAULT '',
      `tel` varchar(50) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `due` datetime DEFAULT NULL,
      `whid` int(11) NOT NULL DEFAULT '0',
      `terms` varchar(30) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `cur` varchar(3) NOT NULL DEFAULT '',
      `forex` decimal(18,2) NOT NULL DEFAULT '0.00',
      `voiddate` datetime DEFAULT NULL,
      `branch` varchar(30) NOT NULL DEFAULT '',
      `agent` varchar(20) NOT NULL DEFAULT '',
      `isimport` tinyint(4) NOT NULL DEFAULT '0',
      `delby` datetime DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `vattype` varchar(45) NOT NULL DEFAULT '',
      `isapproved` tinyint(4) NOT NULL DEFAULT '0',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(50) NOT NULL DEFAULT '',
      `users` varchar(50) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `projectid` int(11) NOT NULL DEFAULT '0',
      `subproject` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`clientid`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hwahead", $qry);

    $qry = "CREATE TABLE `wastock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(40) NOT NULL DEFAULT '',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(1) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `sku` varchar(45) DEFAULT NULL,
      `loc` varchar(20) NOT NULL DEFAULT '',
      `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cdrefx` bigint(20) NOT NULL DEFAULT '0',
      `cdlinex` int(11) NOT NULL DEFAULT '0',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_2barcode` (`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("wastock", $qry);

    $qry = "CREATE TABLE `hwastock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(40) NOT NULL DEFAULT '',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `void` tinyint(1) NOT NULL DEFAULT '0',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `sku` varchar(45) DEFAULT NULL,
      `loc` varchar(20) NOT NULL DEFAULT '',
      `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cdrefx` bigint(20) NOT NULL DEFAULT '0',
      `cdlinex` int(11) NOT NULL DEFAULT '0',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_2barcode` (`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hwastock", $qry);

    $qry = "
    CREATE TABLE `checkerloc` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("checkerloc", $qry);

    $qry = "
    CREATE TABLE `splitqty` (
      `trno` int(11) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `qty` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `isqa` TINYINT(2) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`, `line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("splitqty", $qry);

    $qry = "CREATE TABLE `cntnuminfo` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `status` varchar(45) DEFAULT NULL,
      `checkerdate` datetime DEFAULT NULL,
      `checkerby` varchar(20) NOT NULL DEFAULT '',
      `dispatchdate` datetime DEFAULT NULL,
      `dispatchby` varchar(20) NOT NULL DEFAULT '',
      `logisticdate` datetime DEFAULT NULL,
      `logisticby` varchar(20) NOT NULL DEFAULT '',
      `checkerid` INT(11) NOT NULL DEFAULT '0',
      `checkerlocid` INT(11) NOT NULL DEFAULT '0',
      `truckid` INT(11) NOT NULL DEFAULT '0',
      `receivedate` datetime DEFAULT NULL,
      `receiveby` varchar(20) NOT NULL DEFAULT '',
      `scheddate` datetime DEFAULT NULL,
      `checkerrcvdate` datetime DEFAULT NULL,
      `forloaddate` datetime DEFAULT NULL,
      `forloadby` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cntnuminfo", $qry);

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "boxcount", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "rem2", "varchar(500) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "releasedate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->createindex("cntnuminfo", "Index_trno", ['trno']);
    $this->coreFunctions->createindex("cntnuminfo", "Index_date", ['checkerdate', 'dispatchdate', 'logisticdate', 'receivedate', 'scheddate', 'checkerrcvdate', 'forloaddate']);
    $this->coreFunctions->createindex("cntnuminfo", "Index_checkerid", ['checkerid']);
    $this->coreFunctions->createindex("cntnuminfo", "Index_checkerlocid", ['checkerlocid']);
    $this->coreFunctions->createindex("cntnuminfo", "Index_truckid", ['truckid']);
    $this->coreFunctions->createindex("cntnuminfo", "Index_status", ['status']);

    $qry = "CREATE TABLE `hcntnuminfo` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `status` varchar(45) DEFAULT NULL,
      `checkerdate` datetime DEFAULT NULL,
      `checkerby` varchar(20) NOT NULL DEFAULT '',
      `dispatchdate` datetime DEFAULT NULL,
      `dispatchby` varchar(20) NOT NULL DEFAULT '',
      `logisticdate` datetime DEFAULT NULL,
      `logisticby` varchar(20) NOT NULL DEFAULT '',
      `checkerid` INT(11) NOT NULL DEFAULT '0',
      `checkerlocid` INT(11) NOT NULL DEFAULT '0',
      `truckid` INT(11) NOT NULL DEFAULT '0',
      `receivedate` datetime DEFAULT NULL,
      `receiveby` varchar(20) NOT NULL DEFAULT '',
      `scheddate` datetime DEFAULT NULL,
      `checkerrcvdate` datetime DEFAULT NULL,
      `forloaddate` datetime DEFAULT NULL,
      `forloadby` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hcntnuminfo", $qry);

    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "boxcount", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "rem2", "varchar(500) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "releasedate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->createindex("hcntnuminfo", "Index_trno", ['trno']);
    $this->coreFunctions->createindex("hcntnuminfo", "Index_date", ['checkerdate', 'dispatchdate', 'logisticdate', 'receivedate', 'scheddate', 'checkerrcvdate', 'forloaddate']);
    $this->coreFunctions->createindex("hcntnuminfo", "Index_checkerid", ['checkerid']);
    $this->coreFunctions->createindex("hcntnuminfo", "Index_checkerlocid", ['checkerlocid']);
    $this->coreFunctions->createindex("hcntnuminfo", "Index_truckid", ['truckid']);
    $this->coreFunctions->createindex("hcntnuminfo", "Index_status", ['status']);

    $qry = "
    CREATE TABLE `deliverytype` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("deliverytype", $qry);

    $this->coreFunctions->sbcaddcolumn("client", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("lastock", "pickerid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "pickerstart", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "pickerend", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "forkliftid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "isforklift", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("lastock", "whmanid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "whmandate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("glstock", "pickerid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "pickerstart", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "pickerend", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "forkliftid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("glstock", "whmanid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "whmandate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "checkerid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "checkerlocid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "truckid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "scheddate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "receivedate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "receiveby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "checkerrcvdate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "forloadby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "forloaddate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "courier", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "courier", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "checkerdone", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "checkerdone", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "editby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "editdate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "editby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "editdate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcdropcolumn("cntnuminfo", "crtlby");
    $this->coreFunctions->sbcdropcolumn("cntnuminfo", "crtldate");

    $this->coreFunctions->sbcaddcolumn("cntnum", "crtlby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cntnum", "crtldate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("cntnum", "status", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    $qry = "
      CREATE TABLE `partrequest` (
        `line` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(50) NOT NULL DEFAULT '',
        `acnoid` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`line`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("partrequest", $qry);

    $this->coreFunctions->sbcaddcolumn("partrequest", "acnoid", "int(11) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("plhead", "plno", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("plhead", "shipmentno", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("plhead", "invoiceno", "VARCHAR(45) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("hplhead", "plno", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hplhead", "shipmentno", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hplhead", "invoiceno", "VARCHAR(45) NOT NULL DEFAULT ''", 0);

    // Special Parts Request
    $qry = "CREATE TABLE `sghead` (
  `trno` bigint(20) NOT NULL DEFAULT '0',
  `doc` char(2) NOT NULL DEFAULT '',
  `docno` char(20) NOT NULL,
  `client` varchar(15) NOT NULL DEFAULT '',
  `clientname` varchar(150) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `shipto` varchar(100) DEFAULT NULL,
  `customername` varchar(150) DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `dateid` date DEFAULT NULL,
  `due` date DEFAULT NULL,
  `wh` varchar(15) DEFAULT NULL,
  `terms` varchar(30) DEFAULT NULL,
  `rem` varchar(500) DEFAULT NULL,
  `cur` varchar(3) NOT NULL DEFAULT '',
  `forex` decimal(18,2) DEFAULT '0.00',
  `voiddate` datetime DEFAULT NULL,
  `branch` varchar(30) DEFAULT NULL,
  `agent` varchar(50) NOT NULL DEFAULT '',
  `yourref` varchar(25) NOT NULL DEFAULT '',
  `ourref` varchar(25) NOT NULL DEFAULT '',
  `approvedby` varchar(50) NOT NULL DEFAULT '',
  `approveddate` datetime DEFAULT NULL,
  `printtime` datetime DEFAULT NULL,
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
  `salestype` varchar(25) DEFAULT '',
  `deliverytype` int(11) NOT NULL DEFAULT '0',
  `partreqtypeid` int(11) NOT NULL DEFAULT '0',
  `trnx_type` varchar(45) NOT NULL DEFAULT '',
  `projectid` int(10) unsigned NOT NULL DEFAULT '0',
  `creditinfo` varchar(1000) NOT NULL DEFAULT '',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("sghead", $qry);

    $qry = "CREATE TABLE `sgstock` (
  `trno` bigint(20) NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL,
  `itemid` int(11) NOT NULL DEFAULT '0',
  `whid` int(11) NOT NULL DEFAULT '0',
  `uom` varchar(15) DEFAULT NULL,
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
  `encodedby` varchar(20) NOT NULL DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `editby` varchar(20) NOT NULL DEFAULT '',
  `loc` varchar(45) NOT NULL DEFAULT '',
  `expiry` varchar(45) DEFAULT NULL,
  `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
  PRIMARY KEY (`trno`,`line`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("sgstock", $qry);

    $qry = "CREATE TABLE `hsghead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) DEFAULT NULL,
    `address` varchar(150) DEFAULT NULL,
    `shipto` varchar(100) DEFAULT NULL,
    `customername` varchar(150) DEFAULT NULL,
    `tel` varchar(50) DEFAULT NULL,
    `dateid` date DEFAULT NULL,
    `due` date DEFAULT NULL,
    `wh` varchar(15) DEFAULT NULL,
    `terms` varchar(30) DEFAULT NULL,
    `rem` varchar(500) DEFAULT NULL,
    `cur` varchar(3) NOT NULL DEFAULT '',
    `forex` decimal(18,2) DEFAULT '0.00',
    `voiddate` datetime DEFAULT NULL,
    `branch` varchar(30) DEFAULT NULL,
    `agent` varchar(50) NOT NULL DEFAULT '',
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
    `approvedby` varchar(50) NOT NULL DEFAULT '',
    `approveddate` datetime DEFAULT NULL,
    `printtime` datetime DEFAULT NULL,
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
    `salestype` varchar(25) DEFAULT '',
    `deliverytype` int(11) NOT NULL DEFAULT '0',
    `partreqtypeid` int(11) NOT NULL DEFAULT '0',
    `trnx_type` varchar(45) NOT NULL DEFAULT '',
    `projectid` int(10) unsigned NOT NULL DEFAULT '0',
    `creditinfo` varchar(1000) NOT NULL DEFAULT '',
    `subproject` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsghead", $qry);

    $qry = "CREATE TABLE `hsgstock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL,
    `itemid` int(11) NOT NULL DEFAULT '0',
    `whid` int(11) NOT NULL DEFAULT '0',
    `uom` varchar(15) DEFAULT NULL,
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
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    `loc` varchar(45) NOT NULL DEFAULT '',
    `expiry` varchar(45) DEFAULT NULL,
    `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
    PRIMARY KEY (`trno`,`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsgstock", $qry);

    $qry = "CREATE TABLE `voidstock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `refx` bigint(20) unsigned NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ref` char(50) NOT NULL DEFAULT '',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `loc` varchar(45) NOT NULL DEFAULT '',
      `loc2` varchar(45) NOT NULL DEFAULT '',
      `sku` varchar(45) DEFAULT NULL,
      `tstrno` int(10) unsigned NOT NULL DEFAULT '0',
      `tsline` int(10) unsigned NOT NULL DEFAULT '0',
      `comm` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `icomm` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `expiry` varchar(45) DEFAULT NULL,
      `isqty2` decimal(19,6) DEFAULT '0.000000',
      `iscomponent` int(1) unsigned NOT NULL DEFAULT '0',
      `outputid` int(10) unsigned NOT NULL DEFAULT '0',
      `iss2` decimal(19,6) DEFAULT '0.000000',
      `agent` varchar(45) NOT NULL DEFAULT '',
      `agent2` varchar(45) NOT NULL DEFAULT '',
      `isextract` int(1) unsigned NOT NULL DEFAULT '0',
      `outputline` int(11) unsigned NOT NULL DEFAULT '0',
      `tsako` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `msako` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `itemcomm` varchar(45) NOT NULL DEFAULT '',
      `itemhandling` varchar(45) NOT NULL DEFAULT '',
      `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `isfromjo` int(1) NOT NULL DEFAULT '0',
      `original_qty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `jotrno` int(11) NOT NULL DEFAULT '0',
      `joline` int(11) NOT NULL DEFAULT '0',
      `fcost` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `rebate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `stageid` int(11) NOT NULL DEFAULT '0',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `palletid2` int(11) NOT NULL DEFAULT '0',
      `locid2` int(11) NOT NULL DEFAULT '0',
      `pickerid` int(11) NOT NULL DEFAULT '0',
      `pickerstart` datetime DEFAULT NULL,
      `pickerend` datetime DEFAULT NULL,
      `forkliftid` int(11) NOT NULL DEFAULT '0',
      `isforklift` tinyint(2) NOT NULL DEFAULT '0',
      `whmanid` int(11) NOT NULL DEFAULT '0',
      `whmandate` datetime DEFAULT NULL,
      `voidby` varchar(45) NOT NULL DEFAULT '',
      `voidddate` datetime DEFAULT NULL,
      KEY `Index_2stock` (`uom`,`loc`,`loc2`),
      KEY `Index_refx` (`refx`,`linex`),
      KEY `Index_loc` (`loc`),
      KEY `Index_trno` (`trno`,`void`,`loc`,`qty`,`iss`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("voidstock", $qry);

    $qry = "CREATE TABLE `hvoidstock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `refx` bigint(20) unsigned NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `ref` char(50) NOT NULL DEFAULT '',
      `void` tinyint(4) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `loc` varchar(45) NOT NULL DEFAULT '',
      `loc2` varchar(45) NOT NULL DEFAULT '',
      `sku` varchar(45) DEFAULT NULL,
      `tstrno` int(10) unsigned NOT NULL DEFAULT '0',
      `tsline` int(10) unsigned NOT NULL DEFAULT '0',
      `comm` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `icomm` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `expiry` varchar(45) DEFAULT NULL,
      `isqty2` decimal(19,6) DEFAULT '0.000000',
      `iscomponent` int(1) unsigned NOT NULL DEFAULT '0',
      `outputid` int(10) unsigned NOT NULL DEFAULT '0',
      `iss2` decimal(19,6) DEFAULT '0.000000',
      `agent` varchar(45) NOT NULL DEFAULT '',
      `agent2` varchar(45) NOT NULL DEFAULT '',
      `isextract` int(1) unsigned NOT NULL DEFAULT '0',
      `outputline` int(11) unsigned NOT NULL DEFAULT '0',
      `tsako` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `msako` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `itemcomm` varchar(45) NOT NULL DEFAULT '',
      `itemhandling` varchar(45) NOT NULL DEFAULT '',
      `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `isfromjo` int(1) NOT NULL DEFAULT '0',
      `original_qty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `jotrno` int(11) NOT NULL DEFAULT '0',
      `joline` int(11) NOT NULL DEFAULT '0',
      `fcost` decimal(18,6) NOT NULL DEFAULT '0.000000',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `rebate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `stageid` int(11) NOT NULL DEFAULT '0',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `palletid2` int(11) NOT NULL DEFAULT '0',
      `locid2` int(11) NOT NULL DEFAULT '0',
      `pickerid` int(11) NOT NULL DEFAULT '0',
      `pickerstart` datetime DEFAULT NULL,
      `pickerend` datetime DEFAULT NULL,
      `forkliftid` int(11) NOT NULL DEFAULT '0',
      `isforklift` tinyint(2) NOT NULL DEFAULT '0',
      `whmanid` int(11) NOT NULL DEFAULT '0',
      `whmandate` datetime DEFAULT NULL,
      `voidby` varchar(45) NOT NULL DEFAULT '',
      `voidddate` datetime DEFAULT NULL,
      `returnid` int(11) NOT NULL DEFAULT '0',
      `returndate` datetime DEFAULT NULL,
      KEY `Index_2stock` (`uom`,`loc`,`loc2`),
      KEY `Index_refx` (`refx`,`linex`),
      KEY `Index_loc` (`loc`),
      KEY `Index_trno` (`trno`,`void`,`loc`,`qty`,`iss`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hvoidstock", $qry);

    $this->coreFunctions->sbcaddcolumn("voidstock", "returnid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("voidstock", "returndate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("voidstock", "voidby", "varchar(45) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("hvoidstock", "returnid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hvoidstock", "returndate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hvoidstock", "voidby", "varchar(45) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE `whstatus` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `status` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("whstatus", $qry);

    $qry = "CREATE TABLE `trxstatus` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `status` varchar(50) NOT NULL DEFAULT '',
      `doc` varchar(5) NOT NULL DEFAULT '',
      `psort` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("trxstatus", $qry);

    $this->coreFunctions->sbcaddcolumn("trxstatus", "doc", "varchar(10) NOT NULL DEFAULT ''");

    // $exist = $this->coreFunctions->datareader("select ifnull(count(line),0) as value from trxstatus");
    // if (!$exist) {
    //   $this->coreFunctions->execqry("insert into trxstatus (`status`, doc, psort) values ('High', 'SJ', 1), ('Medium', 'SJ', 2), ('Low', 'SJ', 3), ('Not Priority', 'SJ', 4)");
    // }

    $trxstatus = [];
    array_push($trxstatus, array('line' => 1, 'status' => 'High', 'doc' => 'SJ', 'psort' => 1));
    array_push($trxstatus, array('line' => 2, 'status' => 'Medium', 'doc' => 'SJ', 'psort' => 2));
    array_push($trxstatus, array('line' => 3, 'status' => 'Low', 'doc' => 'SJ', 'psort' => 3));
    array_push($trxstatus, array('line' => 4, 'status' => 'Not Priority', 'doc' => 'SJ', 'psort' => 4));

    array_push($trxstatus, array('line' => 5, 'status' => 'Pending', 'doc' => 'SO'));
    array_push($trxstatus, array('line' => 6, 'status' => 'Partial', 'doc' => 'SO'));
    array_push($trxstatus, array('line' => 7, 'status' => 'Complete', 'doc' => 'SO'));
    array_push($trxstatus, array('line' => 8, 'status' => 'For DR/SI', 'doc' => 'SQ'));
    array_push($trxstatus, array('line' => 9, 'status' => 'Closed', 'doc' => 'SQ'));

    array_push($trxstatus, array('line' => 10, 'status' => 'For Approval', 'doc' => 'VR'));
    array_push($trxstatus, array('line' => 11, 'status' => 'Approved w/o vehicle', 'doc' => 'VR'));
    array_push($trxstatus, array('line' => 12, 'status' => 'POSTED', 'doc' => 'VR'));
    array_push($trxstatus, array('line' => 13, 'status' => 'Reschedule', 'doc' => 'VR'));
    array_push($trxstatus, array('line' => 14, 'status' => 'Disapproved', 'doc' => 'VR'));
    array_push($trxstatus, array('line' => 15, 'status' => 'Approved with vehicle', 'doc' => 'VR'));
    array_push($trxstatus, array('line' => 16, 'status' => 'For Revision', 'doc' => 'VR'));

    array_push($trxstatus, array('line' => 17, 'status' => 'For Clarification', 'doc' => 'ITEMS'));
    array_push($trxstatus, array('line' => 18, 'status' => 'With Stock', 'doc' => 'ITEMS'));

    array_push($trxstatus, array('line' => 19, 'status' => 'Locked', 'doc' => 'LOCKED'));

    //canvass ati
    array_push($trxstatus, array('line' => 20, 'status' => 'Pick-up - Logistics', 'doc' => 'CDPROC'));
    array_push($trxstatus, array('line' => 21, 'status' => 'Pick-up - Runner', 'doc' => 'CDPROC'));
    array_push($trxstatus, array('line' => 22, 'status' => 'Pick-up - Requestor', 'doc' => 'CDPROC'));
    array_push($trxstatus, array('line' => 23, 'status' => 'Pick-up - Courier', 'doc' => 'CDPROC'));
    array_push($trxstatus, array('line' => 24, 'status' => 'Delivery', 'doc' => 'CDPROC'));

    //item req monitoring
    array_push($trxstatus, array('line' => 25, 'status' => 'Hold', 'doc' => 'ITEMREQM'));
    array_push($trxstatus, array('line' => 26, 'status' => 'Void', 'doc' => 'ITEMREQM'));
    array_push($trxstatus, array('line' => 27, 'status' => 'Clarify', 'doc' => 'ITEMREQM'));
    array_push($trxstatus, array('line' => 28, 'status' => 'Concern', 'doc' => 'ITEMREQM'));

    //item req monitoring - sub category status
    array_push($trxstatus, array('line' => 29, 'status' => 'For sales verification', 'doc' => 'ITEMREQM2'));
    array_push($trxstatus, array('line' => 30, 'status' => 'Follow up', 'doc' => 'ITEMREQM2'));
    array_push($trxstatus, array('line' => 31, 'status' => 'Pending information', 'doc' => 'ITEMREQM2'));
    array_push($trxstatus, array('line' => 32, 'status' => 'Others', 'doc' => 'ITEMREQM2'));

    array_push($trxstatus, array('line' => 33, 'status' => 'For price verification', 'doc' => 'ITEMREQM2'));
    array_push($trxstatus, array('line' => 34, 'status' => 'Waiting for quotation', 'doc' => 'ITEMREQM2'));
    array_push($trxstatus, array('line' => 35, 'status' => 'For requestor`s approval', 'doc' => 'ITEMREQM2'));

    array_push($trxstatus, array('line' => 36, 'status' => 'Approved', 'doc' => 'OQ'));
    array_push($trxstatus, array('line' => 37, 'status' => 'Oracle Posted', 'doc' => 'OQ'));
    array_push($trxstatus, array('line' => 38, 'status' => 'With Receiving', 'doc' => 'OQ'));
    array_push($trxstatus, array('line' => 39, 'status' => 'For Posting', 'doc' => 'OQ'));

    array_push($trxstatus, array('line' => 40, 'status' => 'Pickup', 'doc' => 'SJ'));
    array_push($trxstatus, array('line' => 41, 'status' => 'Pack House Loaded', 'doc' => 'SJ'));
    array_push($trxstatus, array('line' => 42, 'status' => 'Released', 'doc' => 'SJ'));

    array_push($trxstatus, array('line' => 43, 'status' => 'Payment Only', 'doc' => 'ITEMS'));

    array_push($trxstatus, array('line' => 44, 'status' => 'For Receiving', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 45, 'status' => 'For Checking', 'doc' => 'RR'));

    array_push($trxstatus, array('line' => 46, 'status' => 'For SO', 'doc' => 'OQ'));
    array_push($trxstatus, array('line' => 47, 'status' => 'For Oracle Receiving', 'doc' => 'OQ'));

    array_push($trxstatus, array('line' => 48, 'status' => 'Items Collected', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 49, 'status' => 'Forwarded to OP', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 50, 'status' => 'Payment Released', 'doc' => 'CV'));

    array_push($trxstatus, array('line' => 51, 'status' => 'Partial - Initial', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 52, 'status' => 'Full - Initial', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 53, 'status' => 'Partial - Final', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 54, 'status' => 'Full - Final', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 55, 'status' => 'Partially Checked', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 56, 'status' => 'Fully Checked', 'doc' => 'RR'));

    array_push($trxstatus, array('line' => 57, 'status' => 'For Initial Checking', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 58, 'status' => 'For Final Checking', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 59, 'status' => 'Forwarded to Encoder', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 60, 'status' => 'Forwarded to Warehouse', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 61, 'status' => 'Forwarded to Asset Management', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 62, 'status' => 'For Liquidation', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 63, 'status' => 'Forwarded to Accounting', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 64, 'status' => 'For Checking', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 65, 'status' => 'Check Issued', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 66, 'status' => 'Paid', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 67, 'status' => 'Advances Cleared', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 68, 'status' => 'SOA Received', 'doc' => 'CV'));
    array_push($trxstatus, array('line' => 69, 'status' => 'Checked', 'doc' => 'CV'));

    array_push($trxstatus, array('line' => 70, 'status' => 'For Initial Receiving', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 71, 'status' => 'For Final Receiving', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 72, 'status' => 'Intransit', 'doc' => 'RR'));
    array_push($trxstatus, array('line' => 73, 'status' => 'Acknowledged', 'doc' => 'RR'));

    array_push($trxstatus, array('line' => 74, 'status' => 'For Weight Input', 'doc' => 'SJ'));

    array_push($trxstatus, array('line' => 75, 'status' => 'Inventory Usage', 'doc' => 'ITEMS'));

    array_push($trxstatus, array('line' => 76, 'status' => 'Disconnected', 'doc' => 'WN'));

    array_push($trxstatus, array('line' => 77, 'status' => 'Rejected', 'doc' => 'CD'));

    array_push($trxstatus, array('line' => 78, 'status' => 'Update Database', 'doc' => 'ITEMS'));

    array_push($trxstatus, array('line' => 90, 'status' => 'For Refund', 'doc' => 'INCIDENT'));
    array_push($trxstatus, array('line' => 91, 'status' => 'Deductible ', 'doc' => 'INCIDENT'));

    array_push($trxstatus, array('line' => 92, 'status' => 'Open ', 'doc' => 'CA'));
    array_push($trxstatus, array('line' => 93, 'status' => 'In Progress ', 'doc' => 'CA'));
    array_push($trxstatus, array('line' => 94, 'status' => 'Resolved ', 'doc' => 'CA'));

    array_push($trxstatus, array('line' => 95, 'status' => 'Submitted', 'doc' => 'TA'));
    array_push($trxstatus, array('line' => 96, 'status' => 'For Quotation', 'doc' => 'TA'));
    array_push($trxstatus, array('line' => 97, 'status' => 'Cancelled', 'doc' => 'TA'));

    array_push($trxstatus, array('line' => 98, 'status' => 'For Pre-Employment Exam', 'doc' => 'applicant'));
    array_push($trxstatus, array('line' => 99, 'status' => 'For Background Checking', 'doc' => 'applicant'));
    array_push($trxstatus, array('line' => 100, 'status' => 'For Final Interview', 'doc' => 'applicant'));
    array_push($trxstatus, array('line' => 101, 'status' => 'For Hiring & Pre-Employment Requirements', 'doc' => 'applicant'));
    array_push($trxstatus, array('line' => 102, 'status' => 'For Job Offer', 'doc' => 'applicant'));

    foreach ($trxstatus as $key => $val) {
      $statusexist = $this->coreFunctions->datareader("select line as value from trxstatus where line=? and doc=?", [$val['line'], $val['doc']]);
      if (!$statusexist) {
        $this->coreFunctions->sbcinsert("trxstatus", [$val]);
      } else {
        $this->coreFunctions->sbcupdate("trxstatus", $val, ['line' => $val['line']]);
      }
    }

    $qry = "CREATE TABLE `rppallet` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `user` varchar(20) NOT NULL DEFAULT '',
      `statid` int(2) NOT NULL DEFAULT '0',
      `forkliftid` int(11) NOT NULL DEFAULT '0',
      `forkliftminedate` datetime DEFAULT NULL,
      `forkliftdate` datetime DEFAULT NULL,
      `dropoffdate` datetime DEFAULT NULL,
      `whmanid` int(11) NOT NULL DEFAULT '0',
      `whmanminedate` datetime DEFAULT NULL,
      KEY `Index_trno` (`trno`),
      KEY `Index_palletid` (`palletid`),
      KEY `Index_statid` (`statid`),
      KEY `Index_user` (`user`),
      KEY `Index_forkliftid` (`forkliftid`),
      KEY `Index_whmanid` (`whmanid`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rppallet", $qry);

    $qry = "CREATE TABLE `hrppallet` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `user` varchar(20) NOT NULL DEFAULT '',
      `statid` int(2) NOT NULL DEFAULT '0',
      `forkliftid` int(11) NOT NULL DEFAULT '0',
      `forkliftminedate` datetime DEFAULT NULL,
      `forkliftdate` datetime DEFAULT NULL,
      `dropoffdate` datetime DEFAULT NULL,
      `whmanid` int(11) NOT NULL DEFAULT '0',
      `whmanminedate` datetime DEFAULT NULL,
      KEY `Index_trno` (`trno`),
      KEY `Index_palletid` (`palletid`),
      KEY `Index_statid` (`statid`),
      KEY `Index_user` (`user`),
      KEY `Index_forkliftid` (`forkliftid`),
      KEY `Index_whmanid` (`whmanid`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrppallet", $qry);

    // SPLIT QTY used in Warehouse picker
    $qry = "CREATE TABLE `splitstock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `splitdate` datetime DEFAULT NULL,
      `user` varchar(20) NOT NULL DEFAULT '',
      `pickerid` int(11) NOT NULL DEFAULT '0',
      `pickerstart` datetime DEFAULT NULL,
      `pickerend` datetime DEFAULT NULL,
      `remid` int(11) NOT NULL DEFAULT '0',
      `qatrno` bigint(20) NOT NULL DEFAULT '0',
      KEY `Index_trno` (`trno`),
      KEY `Index_locid` (`locid`),
      KEY `Index_palletid` (`palletid`),
      KEY `Index_remid` (`remid`),
      KEY `Index_user` (`user`),
      KEY `Index_pickerid` (`pickerid`),
      KEY `Index_qatrno` (`qatrno`)
      USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("splitstock", $qry);

    $qry = "CREATE TABLE `replacestock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `user` varchar(20) NOT NULL DEFAULT '',
      `pickerid` int(11) NOT NULL DEFAULT '0',
      `pickerstart` datetime DEFAULT NULL,
      `pickerend` datetime DEFAULT NULL,
      `remid` int(11) NOT NULL DEFAULT '0',
      `qa` bigint(20) NOT NULL DEFAULT '0',
      `isaccept` tinyint(2) NOT NULL DEFAULT '0',
      KEY `Index_trno` (`trno`),
      KEY `Index_locid` (`locid`),
      KEY `Index_palletid` (`palletid`),
      KEY `Index_remid` (`remid`),
      KEY `Index_user` (`user`),
      KEY `Index_pickerid` (`pickerid`)
      USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("replacestock", $qry);

    $qry = "CREATE TABLE `hreplacestock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `user` varchar(20) NOT NULL DEFAULT '',
      `pickerid` int(11) NOT NULL DEFAULT '0',
      `pickerstart` datetime DEFAULT NULL,
      `pickerend` datetime DEFAULT NULL,
      `remid` int(11) NOT NULL DEFAULT '0',
      `qa` bigint(20) NOT NULL DEFAULT '0',
      `isaccept` tinyint(2) NOT NULL DEFAULT '0',
      KEY `Index_trno` (`trno`),
      KEY `Index_locid` (`locid`),
      KEY `Index_palletid` (`palletid`),
      KEY `Index_remid` (`remid`),
      KEY `Index_user` (`user`),
      KEY `Index_pickerid` (`pickerid`)
      USING BTREE) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hreplacestock", $qry);

    $qry = "
    CREATE TABLE `whrem` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `rem` varchar(50) NOT NULL DEFAULT '',
      `forreturn` tinyint(2) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("whrem", $qry);

    $qry = "
    CREATE TABLE `replenishstock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `locid` int(11) NOT NULL DEFAULT '0',
      `palletid` int(11) NOT NULL DEFAULT '0',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `userid` int(11) NOT NULL DEFAULT '0',
      `validate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_trno` (`trno`),
      KEY `Index_locid` (`locid`),
      KEY `palletid` (`palletid`),
      KEY `itemid` (`itemid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("replenishstock", $qry);

    $this->coreFunctions->sbcaddcolumn("plstock", "suppid", "integer(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hplstock", "suppid", "integer(11) NOT NULL DEFAULT '0'", 0);

    $qry = "
    CREATE TABLE `reschedule` (
      `trno` int(11) NOT NULL DEFAULT '0',
      `dispatchdate` datetime DEFAULT NULL,
      `dispatchby` varchar(50) NOT NULL DEFAULT '',
      `scheddate` datetime DEFAULT NULL,
      `truckid` int(11) NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `userid` varchar(50) NOT NULL DEFAULT '',
      KEY `Index_trno` (`trno`),
      KEY `Index_truckid` (`truckid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("reschedule", $qry);

    $qry = "
    CREATE TABLE `incentives` (
      `ptrno` int(11) NOT NULL DEFAULT '0',
      `trno` int(11) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `depodate` datetime DEFAULT NULL,
      `acnoid` int(11) NOT NULL DEFAULT '0',
      `clientid` int(11) NOT NULL DEFAULT '0',
      `agentid` int(11) NOT NULL DEFAULT '0',
      `agentid2` int(11) NOT NULL DEFAULT '0',
      `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `clientcom` decimal(5,2) NOT NULL DEFAULT '0.00',
      `clientcomamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `clientquota` decimal(19,2) NOT NULL DEFAULT '0.00',      
      `agentcom` decimal(5,2) NOT NULL DEFAULT '0.00',      
      `agentquota` decimal(19,2) NOT NULL DEFAULT '0.00',
      `agentcomamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `agent2com` decimal(5,2) NOT NULL DEFAULT '0.00',
      `agent2quota` decimal(19,2) NOT NULL DEFAULT '0.00',
      `agent2comamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `agrelease` datetime DEFAULT NULL,
      `clientrelease` datetime DEFAULT NULL,
      KEY `Index_ptrno` (`ptrno`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`),
      KEY `Index_acnoid` (`acnoid`),
      KEY `Index_clientid` (`clientid`),
      KEY `Index_agentid` (`agentid`),
      KEY `Index_agentid2` (`agentid2`),
      KEY `Index_depodate` (`depodate`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("incentives", $qry);

    $this->coreFunctions->sbcaddcolumn("incentives", "clientcom", "DECIMAL(15,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "clientcomamt", "DECIMAL(15,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "clientquota", "DECIMAL(19,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "clientrelease", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "ag2release", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "agreleaseyr", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "ag2releaseyr", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("incentives", "agreleaseby", "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("incentives", "ag2releaseby", "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("incentives", "agreleaseyrby", "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("incentives", "ag2releaseyrby", "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("incentives", "clientreleaseby", "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("incentives", "doc", "varchar(3) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("incentivesyr", "doc", "varchar(3) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("incentivesyr", "sjagent", "INT(10) NOT NULL DEFAULT '0'", 0);

    // mitsukoshi
    $this->coreFunctions->sbcaddcolumn("client", "truckid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sahead", "truckid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsahead", "truckid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sbhead", "truckid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsbhead", "truckid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("schead", "truckid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hschead", "truckid", "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("plhead", "yourref", "VARCHAR(25) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hplhead", "yourref", "VARCHAR(25) NOT NULL DEFAULT ''", 0);

    $qry = "
    CREATE TABLE `incentivesyr` (
      `sdate` datetime DEFAULT NULL,
      `edate` datetime DEFAULT NULL,
      `agentid` int(11) NOT NULL DEFAULT '0',
      `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `agentcom` decimal(5,2) NOT NULL DEFAULT '0.00',
      `agentquota` decimal(19,2) NOT NULL DEFAULT '0.00',
      `agentcomamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `agrelease` datetime DEFAULT NULL,
      `agreleaseby` varchar(45) NOT NULL DEFAULT '',
      `agtype` int(11) NOT NULL DEFAULT '0',
      KEY `Index_sdate` (`sdate`),
      KEY `Index_edate` (`edate`),
      KEY `Index_agentid` (`agentid`),
      KEY `Index_agrelease` (`agrelease`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("incentivesyr", $qry);

    $this->coreFunctions->sbcaddcolumn("hsgstock", "waqa", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumn("lastock", "isapprove", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "isapprove", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("voidstock", "isapprove", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hvoidstock", "isapprove", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
  } //end function
} // end class
