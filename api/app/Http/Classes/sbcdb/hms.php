<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;

class hms
{

  private $coreFunctions;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
  } //end fn



  public function tableupdatehms()
  {
    $qry = "CREATE TABLE `tblroomtype` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `roomtype` char(30) NOT NULL DEFAULT '',
      `rate1` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
      `maxadult` int(11) unsigned NOT NULL DEFAULT '0',
      `maxchild` int(11) unsigned NOT NULL DEFAULT '0',
      `maxinfant` int(11) unsigned NOT NULL DEFAULT '0',
      `beds` int(11) unsigned NOT NULL DEFAULT '0',
      `additional` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
      `issmoking` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `category` char(8) NOT NULL DEFAULT '',
      `rate2` decimal(10,2) unsigned NOT NULL DEFAULT '0.00',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(45) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(45) NOT NULL DEFAULT '',
      `bfast` int(11) unsigned NOT NULL DEFAULT '0',
      `inactive` tinyint(2) NOT NULL DEFAULT '0',
      `asset` varchar(45) NOT NULL DEFAULT '',
      `revenue` varchar(45) NOT NULL DEFAULT '',
      `rem` varchar(1000) NOT NULL DEFAULT '',
      `typename` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("tblroomtype", $qry);

    $qry = "CREATE TABLE `hmsratesetup` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45) NOT NULL DEFAULT '',
      `description` varchar(45) NOT NULL,
      `isinactive` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hmsratesetup", $qry);

    $qry = "CREATE TABLE `hmscharges` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `description` varchar(100) NOT NULL DEFAULT '',
      `asset` varchar(20) NOT NULL DEFAULT '',
      `liabilities` varchar(20) NOT NULL DEFAULT '',
      `revenue` varchar(20) NOT NULL DEFAULT '',
      `expense` varchar(20) NOT NULL DEFAULT '',
      `factor` int(10) unsigned NOT NULL DEFAULT '0',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `groupid` varchar(45) NOT NULL DEFAULT '',
      `starttime` varchar(45) NOT NULL DEFAULT '',
      `nohrs` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
      `category` varchar(100) NOT NULL DEFAULT '',
      `subcat` varchar(100) NOT NULL DEFAULT '',
      `isadmin` tinyint(1) NOT NULL DEFAULT '0',
      `isnegative` tinyint(1) NOT NULL DEFAULT '0',
      `isinactive` tinyint(1) NOT NULL DEFAULT '0',
      `islookup` tinyint(1) NOT NULL DEFAULT '0',
      `barcode` varchar(20) NOT NULL DEFAULT '',
      `center` varchar(45) NOT NULL DEFAULT '',
      `isbanquet` tinyint(1) NOT NULL DEFAULT '0',
      `isdisc` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hmscharges", $qry);

    $qry = "CREATE TABLE `hmspackage` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `packname` varchar(100) NOT NULL DEFAULT '',
      `packamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `roomtype` varchar(45) NOT NULL DEFAULT '',
      `pax` int(11) NOT NULL DEFAULT '0',
      `hrs` int(11) NOT NULL DEFAULT '0',
      `days` int(11) NOT NULL DEFAULT '0',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `category` varchar(100) NOT NULL DEFAULT '',
      `subcategory` varchar(100) NOT NULL DEFAULT '',
      `isinactive` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hmspackage", $qry);

    $this->coreFunctions->sbcdroptable("hmsrates");
    $qry = "CREATE TABLE `hmsrates` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `roomtypeid` int(11) unsigned NOT NULL DEFAULT '0',
      `ratecodeid` int(11) unsigned NOT NULL DEFAULT '0',
      `rate` decimal(19,2) NOT NULL DEFAULT '0.00',
      `bfAdult` int(1) unsigned NOT NULL DEFAULT '0',
      `bfChild` int(1) unsigned NOT NULL DEFAULT '0',
      `lnAdult` int(1) unsigned NOT NULL DEFAULT '0',
      `lnChild` int(1) unsigned NOT NULL DEFAULT '0',
      `dnAdult` int(1) unsigned NOT NULL DEFAULT '0',
      `dnChild` int(1) unsigned NOT NULL DEFAULT '0',
      `hrs` int(11) unsigned NOT NULL DEFAULT '0',
      `gp` int(11) unsigned NOT NULL DEFAULT '0',
      `groupid` varchar(100) NOT NULL DEFAULT '',
      `days` int(11) NOT NULL DEFAULT '0',
      `sdate` datetime DEFAULT NULL,
      `edate` datetime DEFAULT NULL,
      `packagegroup` varchar(10) NOT NULL DEFAULT '',
      `rate2` decimal(10,2) NOT NULL DEFAULT '0.00',
      `rate3` decimal(10,2) NOT NULL DEFAULT '0.00',
      `isyearly` tinyint(1) NOT NULL DEFAULT '0',
      `packline` int(11) NOT NULL DEFAULT '0',
      `rate4` decimal(10,2) NOT NULL DEFAULT '0.00',
      `otwd` decimal(10,2) NOT NULL DEFAULT '0.00',
      `otwe` decimal(10,2) NOT NULL DEFAULT '0.00',
      `isdefault` tinyint(1) NOT NULL DEFAULT '0',
      `isinactive` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hmsrates", $qry);

    $qry = "CREATE TABLE `hms_log` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `field` varchar(45) NOT NULL DEFAULT '',
      `oldversion` varchar(900) NOT NULL DEFAULT '',
      `userid` varchar(45) NOT NULL DEFAULT '',
      `dateid` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `shift` varchar(45) NOT NULL DEFAULT '',
      KEY `Index_1` (`trno`),
      KEY `Index_2` (`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hms_log", $qry);


    $qry = "CREATE TABLE `hmsrooms` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `roomno` varchar(100) NOT NULL DEFAULT '',
      `roomtypeid` int(11) unsigned NOT NULL DEFAULT '0',
      `isinactive` tinyint(1) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hmsrooms", $qry);















  }
} // end class