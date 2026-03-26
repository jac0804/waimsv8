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

class waims
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


  public function dataupdatewaims()
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', -1);

    // return;

    //jac 05.19.2022 - sort queries per table
    //FMM 5.3.2021 - moved here because all alter tables with this field got an error incorrect default value '0000-00-00'.
    //Must alter this fields first
    $this->coreFunctions->sbcdropcolumn('centeraccess', 'name');
    $this->coreFunctions->execqrynolog("update rrstatus set receiveddate=null where receiveddate='0000-00-00 00:00:00'");

    $this->coreFunctions->execqrynolog("update useraccess set createdate=null where createdate='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update useraccess set editdate=null where editdate='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update useraccess set viewdate=null where viewdate='0000-00-00 00:00:00'");

    $this->coreFunctions->execqrynolog("update users set viewdate=null where viewdate='0000-00-00 00:00:00'");

    $this->coreFunctions->execqrynolog("update glhead set waybilldate=null where waybilldate='0000-00-00'");
    $this->coreFunctions->execqrynolog("update glhead set due=null where due='0000-00-00'");
    $this->coreFunctions->execqrynolog("update glhead set deliverytype=0 where deliverytype=''");

    $this->coreFunctions->execqrynolog("update lahead set waybilldate=null where waybilldate='0000-00-00'");
    $this->coreFunctions->execqrynolog("update lahead set due=null where due='0000-00-00'");
    $this->coreFunctions->execqrynolog("update lahead set deliverytype=0 where deliverytype=''");

    $this->coreFunctions->execqrynolog("update employee set regular=null where regular='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update employee set resigned=null where resigned='0000-00-00 00:00:00'");

    $this->coreFunctions->execqrynolog("update terms set editdate=null where editdate='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update terms set viewdate=null where viewdate='0000-00-00 00:00:00'");

    $this->coreFunctions->execqrynolog("update stockinfo set leadto=0 where leadto=''");
    $this->coreFunctions->execqrynolog("update stockinfo set leadfrom=0 where leadfrom=''");

    $this->coreFunctions->execqrynolog("update hstockinfo set leadto=0 where leadto=''");
    $this->coreFunctions->execqrynolog("update hstockinfo set leadfrom=0 where leadfrom=''");

    $this->coreFunctions->execqrynolog("update head set postdate=null where postdate='0000-00-00 00:00:00'");

    $this->coreFunctions->execqrynolog("update stock set dateid=null where dateid='0000-00-00 00:00:00'");
    $this->coreFunctions->LogConsole("Update table data random");

    $this->coreFunctions->execqrynolog("update client set `start`=null where `start`='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update client set `bday`=null where `bday`='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update client set start=null where start='0000-00-00'");
    $this->coreFunctions->execqrynolog("update client set bday=null where bday='0000-00-00'");
    $this->coreFunctions->execqrynolog("update client set industry = '' where industry is null");
    $this->coreFunctions->LogConsole("Update table data client");

    $this->coreFunctions->execqrynolog("update item set effectdate=null where effectdate='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update item set dateupdated=null where dateupdated='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update item set `supplier`=0 where `supplier`=''");
    $this->coreFunctions->execqrynolog("update item set `points`=0 where `points`=''");
    $this->coreFunctions->execqrynolog("update item set groupid=0 where groupid=''");
    $this->coreFunctions->execqrynolog("update item set model=0 where model=''");
    $this->coreFunctions->execqrynolog("update item set part=0 where part=''");
    $this->coreFunctions->execqrynolog("update item set brand=0 where brand=''");
    $this->coreFunctions->execqrynolog("update item set class=0 where class=''");
    $this->coreFunctions->execqrynolog("update item set effectdate=null where effectdate='0000-00-00'");
    $this->coreFunctions->execqrynolog("update item set dateupdated=null where dateupdated='0000-00-00'");
    $this->coreFunctions->LogConsole("Update table data item");

    $this->coreFunctions->execqrynolog("update lahead set sdate1=null where sdate1='0000-00-00 00:00:00' or sdate1=''");
    $this->coreFunctions->execqrynolog("update lahead set sdate2=null where sdate2='0000-00-00 00:00:00' or sdate2=''");
    $this->coreFunctions->execqrynolog("update glhead set sdate1=null where sdate1='0000-00-00 00:00:00' or sdate1=''");
    $this->coreFunctions->execqrynolog("update glhead set sdate2=null where sdate2='0000-00-00 00:00:00' or sdate2=''");
    $this->coreFunctions->execqrynolog("update cntnuminfo set sdate1=null where sdate1='0000-00-00 00:00:00' or sdate1=''");
    $this->coreFunctions->execqrynolog("update cntnuminfo set sdate2=null where sdate2='0000-00-00 00:00:00' or sdate2=''");
    $this->coreFunctions->execqrynolog("update hcntnuminfo set sdate1=null where sdate1='0000-00-00 00:00:00' or sdate1=''");
    $this->coreFunctions->execqrynolog("update hcntnuminfo set sdate2=null where sdate2='0000-00-00 00:00:00' or sdate2=''");
  }


  public function tableupdatewaims($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');
    // return;

    // 7/9/2021 FPY
    $this->coreFunctions->sbcaddcolumn("item", "isnoninv", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("item", "isgeneric", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("item", "isserial", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("client", "userid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "waybilldate", "DATE DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("glhead", "waybilldate", "DATE DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("item", "effectdate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("item", "dateupdated", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("app", "appdate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("client", "start", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("rrstatus", "receiveddate", "DATETIME DEFAULT NULL");
    //end of FMM 5.3.2021

    $this->coreFunctions->sbcaddcolumn("client", "isdepartment", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("trstock", "reqqty", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("htrstock", "reqqty", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("htrstock", "prqty", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("htrstock", "prqa", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("htrhead", "approved", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htrhead", "approvedate", "DATETIME DEFAULT NULL ", 0);
    $this->coreFunctions->sbcaddcolumn("htrhead", "prby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htrhead", "prdate", "DATETIME DEFAULT NULL ", 0);

    //10/27/2020 fpy
    $this->coreFunctions->sbcaddcolumn("center", "tin", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("center", "zipcode", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("center", "ismain", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    //10.29.2020 fmm - used in stock transfer module
    $this->coreFunctions->sbcaddcolumn("lahead", "dept", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "dept", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("client", "wh", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    //JAC 11102020
    $this->coreFunctions->sbcaddcolumn("rrstatus", "forex", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("rrstatus", "cur", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("rrstatus", "receiveddate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("glstock", "fcost", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "fcost", "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("item", "foramt", "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    // JIKS [11.11.2020] add forex masterfile and add column forexid
    $qry = "CREATE TABLE `forex_masterfile` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `cur` varchar(3) NOT NULL DEFAULT '',
      `curtopeso` decimal(18,2) NOT NULL DEFAULT '0.00',
      `dollartocur` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("forex_masterfile", $qry);

    $this->coreFunctions->sbcaddcolumn("client", "forexid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("glhead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);

    // fpy [11.20.2020] for canvass sheet posted
    $qry = "
    CREATE TABLE `cdhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
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
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `vattype` varchar(45) NOT NULL DEFAULT '',
      `isapproved` tinyint(4) NOT NULL DEFAULT '0',
      `lockuser` varchar(15) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(15) NOT NULL DEFAULT '',
      `users` varchar(15) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(15) NOT NULL DEFAULT '',
      `editby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(15) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_cdhead` (`docno`,`client`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("cdhead", $qry);

    $qry = "
    CREATE TABLE `hcdhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
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
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `vattype` varchar(45) NOT NULL DEFAULT '',
      `isapproved` tinyint(4) NOT NULL DEFAULT '0',
      `lockuser` varchar(15) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(15) NOT NULL DEFAULT '',
      `users` varchar(15) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(15) NOT NULL DEFAULT '',
      `editby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(15) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_hcdhead` (`docno`,`client`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("hcdhead", $qry);

    $qry = "
    CREATE TABLE `cdstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `barcode` varchar(30) NOT NULL DEFAULT '',
      `itemname` varchar(500) NOT NULL DEFAULT '',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `wh` varchar(15) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(250) NOT NULL DEFAULT '',
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
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(15) NOT NULL DEFAULT '',
      `loc` varchar(20) NOT NULL DEFAULT '',
      `approveddate` datetime DEFAULT NULL,
      `approvedby` varchar(15) NOT NULL DEFAULT '',
      `status` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_cdstock` (`barcode`,`uom`,`wh`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("cdstock", $qry);

    $qry = "
    CREATE TABLE `hcdstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `barcode` varchar(30) NOT NULL DEFAULT '',
      `itemname` varchar(500) NOT NULL DEFAULT '',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `wh` varchar(15) NOT NULL DEFAULT '',
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
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(15) NOT NULL DEFAULT '',
      `loc` varchar(20) NOT NULL DEFAULT '',
      `approveddate` datetime DEFAULT NULL,
      `approvedby` varchar(15) NOT NULL DEFAULT '',
      `status` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_hcdstock` (`barcode`,`uom`,`wh`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("hcdstock", $qry);

    $this->coreFunctions->sbcaddcolumn("prstock", "cdqa", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprstock", "cdqa", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("postock", "cdrefx", "bigint (20) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "cdlinex", "int (11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "cdrefx", "bigint (20) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "cdlinex", "int (11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pohead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hpohead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("sohead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hsohead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("ladetail", "isvewt", "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("gldetail", "isvewt", "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("joservice", "editby", "varchar(15) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("joservice", "editdate", "datetime DEFAULT NULL");


    //fpy 12.09.2020
    $this->coreFunctions->sbcaddcolumn("item", "picture", "VARCHAR(300) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("client", "picture", "VARCHAR(300) NOT NULL DEFAULT ''", 1);


    $qry = "SHOW INDEXES FROM attributes WHERE Key_name = ?";
    if ($this->coreFunctions->opentable($qry, ['PRIMARY'])) {
      $qry = "ALTER TABLE attributes DROP PRIMARY KEY";
      $this->coreFunctions->execqry($qry, 'drop');
    }

    //fpy 12.10.2020
    $qry = "
    CREATE TABLE `cntnum_picture` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(20) NOT NULL DEFAULT '0',
      `title` varchar(2000) NOT NULL DEFAULT '',
      `picture` varchar(300) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_cntnum_picture` (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("cntnum_picture", $qry);

    $qry = "
    CREATE TABLE `transnum_picture` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(20) NOT NULL DEFAULT '0',
      `title` varchar(2000) NOT NULL DEFAULT '',
      `picture` varchar(300) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_transnum_picture` (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("transnum_picture", $qry);


    //fpy 12.12.2020
    $qry = "
    CREATE TABLE `conversation_msg` (
      `msg_id` int(11) NOT NULL AUTO_INCREMENT,
      `conversation_id` int(11) NOT NULL DEFAULT '0',
      `user_id` int(11) NOT NULL DEFAULT '0',
      `user_type` int(11) NOT NULL DEFAULT '0',
      `subject` varchar(300) NOT NULL DEFAULT '',
      `msg` TEXT NULL,
      `attach` varchar(300) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `start` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`msg_id`),
      KEY `Index_conversation_msg` (`msg_id`,`conversation_id`,`user_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("conversation_msg", $qry);

    $qry = "
    CREATE TABLE `conversation_msg_info` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `status` int(11) NOT NULL DEFAULT '0',
      `draft` int(11) NOT NULL DEFAULT '0',
      `fav_status` int(11) NOT NULL DEFAULT '0',
      `createdate` datetime DEFAULT NULL,
      `modifydate` datetime DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `Index_conversation_msg_info` (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("conversation_msg_info", $qry);

    $qry = "
    CREATE TABLE `conversation_user` (
      `conversation_id` int(11) NOT NULL DEFAULT '0',
      `user_id` int(11) NOT NULL DEFAULT '0',
      `user_type` int(11) NOT NULL DEFAULT '0',
      `is_sender` int(11) NOT NULL DEFAULT '0',
      `trash` int(11) NOT NULL DEFAULT '0',
      `is_view` int(11) NOT NULL DEFAULT '0',
      KEY `Index_conversation_user` (`conversation_id`,`user_id`,`user_type`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("conversation_user", $qry);

    $this->coreFunctions->sbcaddcolumn("client", "isadmin", "int(2) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "isnocrlimit", "tinyint(1) NOT NULL DEFAULT '0'");


    //fpy 12.14.2020
    $this->coreFunctions->sbcaddcolumn("postock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('postock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('hpostock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('postock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('hpostock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('postock', 'wh');
    $this->coreFunctions->sbcdropcolumn('hpostock', 'wh');


    $this->coreFunctions->sbcaddcolumn("cdstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hcdstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cdstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hcdstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('cdstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('hcdstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('cdstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('hcdstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('cdstock', 'wh');
    $this->coreFunctions->sbcdropcolumn('hcdstock', 'wh');

    $this->coreFunctions->sbcaddcolumn("prstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("prstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('prstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('hprstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('prstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('hprstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('prstock', 'wh');
    $this->coreFunctions->sbcdropcolumn('hprstock', 'wh');


    $this->coreFunctions->sbcaddcolumn("sostock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sostock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('sostock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('hsostock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('sostock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('hsostock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('sostock', 'wh');
    $this->coreFunctions->sbcdropcolumn('hsostock', 'wh');

    $this->coreFunctions->sbcaddcolumn("pcstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pcstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('pcstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('hpcstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('pcstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('hpcstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('pcstock', 'wh');
    $this->coreFunctions->sbcdropcolumn('hpcstock', 'wh');

    $this->coreFunctions->sbcaddcolumn("trstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("htrstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("trstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("htrstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('trstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('htrstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('trstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('htrstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('trstock', 'wh');
    $this->coreFunctions->sbcdropcolumn('htrstock', 'wh');


    $this->coreFunctions->sbcaddcolumn("lastock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('lastock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('lastock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('glstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('lastock', 'wh');

    $this->coreFunctions->sbcaddcolumn("ladetail", "acnoid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lbdetail", "acnoid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lcdetail", "acnoid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('ladetail', 'acno');
    $this->coreFunctions->sbcdropcolumn('ladetail', 'acnoname');
    $this->coreFunctions->sbcdropcolumn('gldetail', 'acnoname');

    $this->coreFunctions->sbcaddcolumn("sohead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hsohead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("sohead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sohead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsohead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsohead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("sahead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hsahead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("sahead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sahead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsahead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsahead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sbhead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hsbhead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("sbhead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sbhead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsbhead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsbhead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("schead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hschead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("schead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("schead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hschead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hschead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    //fpy 12.29.2020
    $this->coreFunctions->sbcdropcolumn('item', 'srpa');
    $this->coreFunctions->sbcdropcolumn('item', 'srpb');
    $this->coreFunctions->sbcdropcolumn('item', 'srpc');
    $this->coreFunctions->sbcdropcolumn('item', 'srpd');
    $this->coreFunctions->sbcdropcolumn('item', 'srpe');
    $this->coreFunctions->sbcdropcolumn('item', 'srpf');
    $this->coreFunctions->sbcdropcolumn('item', 'srpg');
    $this->coreFunctions->sbcdropcolumn('item', 'srph');
    $this->coreFunctions->sbcdropcolumn('item', 'srpi');
    $this->coreFunctions->sbcdropcolumn('item', 'srpj');
    $this->coreFunctions->sbcdropcolumn('item', 'srpk');
    $this->coreFunctions->sbcdropcolumn('item', 'srpl');
    $this->coreFunctions->sbcdropcolumn('item', 'srpm');
    $this->coreFunctions->sbcdropcolumn('item', 'srpn');
    $this->coreFunctions->sbcdropcolumn('item', 'srpo');
    $this->coreFunctions->sbcdropcolumn('item', 'srpp');
    $this->coreFunctions->sbcdropcolumn('item', 'srpq');
    $this->coreFunctions->sbcdropcolumn('item', 'srpr');
    $this->coreFunctions->sbcdropcolumn('item', 'srps');
    $this->coreFunctions->sbcdropcolumn('item', 'srpt');
    $this->coreFunctions->sbcdropcolumn('item', 'srpu');
    $this->coreFunctions->sbcdropcolumn('item', 'srpv');
    $this->coreFunctions->sbcdropcolumn('item', 'srpw');
    $this->coreFunctions->sbcdropcolumn('item', 'srpx');
    $this->coreFunctions->sbcdropcolumn('item', 'srpy');
    $this->coreFunctions->sbcdropcolumn('item', 'srpz');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa1');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa2');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa3');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa4');
    $this->coreFunctions->sbcdropcolumn('item', 'comma');
    $this->coreFunctions->sbcdropcolumn('item', 'commb');
    $this->coreFunctions->sbcdropcolumn('item', 'commc');
    $this->coreFunctions->sbcdropcolumn('item', 'commd');
    $this->coreFunctions->sbcdropcolumn('item', 'comme');
    $this->coreFunctions->sbcdropcolumn('item', 'commf');
    $this->coreFunctions->sbcdropcolumn('item', 'commg');
    $this->coreFunctions->sbcdropcolumn('item', 'commh');
    $this->coreFunctions->sbcdropcolumn('item', 'commi');
    $this->coreFunctions->sbcdropcolumn('item', 'commj');
    $this->coreFunctions->sbcdropcolumn('item', 'commk');
    $this->coreFunctions->sbcdropcolumn('item', 'comml');
    $this->coreFunctions->sbcdropcolumn('item', 'commm');
    $this->coreFunctions->sbcdropcolumn('item', 'commn');
    $this->coreFunctions->sbcdropcolumn('item', 'commo');
    $this->coreFunctions->sbcdropcolumn('item', 'commp');
    $this->coreFunctions->sbcdropcolumn('item', 'commq');
    $this->coreFunctions->sbcdropcolumn('item', 'commr');
    $this->coreFunctions->sbcdropcolumn('item', 'comms');
    $this->coreFunctions->sbcdropcolumn('item', 'commt');
    $this->coreFunctions->sbcdropcolumn('item', 'commu');
    $this->coreFunctions->sbcdropcolumn('item', 'commv');
    $this->coreFunctions->sbcdropcolumn('item', 'commw');
    $this->coreFunctions->sbcdropcolumn('item', 'commx');
    $this->coreFunctions->sbcdropcolumn('item', 'commy');
    $this->coreFunctions->sbcdropcolumn('item', 'commz');
    $this->coreFunctions->sbcdropcolumn('item', 'comma1');
    $this->coreFunctions->sbcdropcolumn('item', 'comma2');
    $this->coreFunctions->sbcdropcolumn('item', 'comma3');
    $this->coreFunctions->sbcdropcolumn('item', 'comma4');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma');
    $this->coreFunctions->sbcdropcolumn('item', 'icommb');
    $this->coreFunctions->sbcdropcolumn('item', 'icommc');
    $this->coreFunctions->sbcdropcolumn('item', 'icommd');
    $this->coreFunctions->sbcdropcolumn('item', 'icomme');
    $this->coreFunctions->sbcdropcolumn('item', 'icommf');
    $this->coreFunctions->sbcdropcolumn('item', 'icommg');
    $this->coreFunctions->sbcdropcolumn('item', 'icommh');
    $this->coreFunctions->sbcdropcolumn('item', 'icommi');
    $this->coreFunctions->sbcdropcolumn('item', 'icommj');
    $this->coreFunctions->sbcdropcolumn('item', 'icommk');
    $this->coreFunctions->sbcdropcolumn('item', 'icomml');
    $this->coreFunctions->sbcdropcolumn('item', 'icommm');
    $this->coreFunctions->sbcdropcolumn('item', 'icommn');
    $this->coreFunctions->sbcdropcolumn('item', 'icommo');
    $this->coreFunctions->sbcdropcolumn('item', 'icommp');
    $this->coreFunctions->sbcdropcolumn('item', 'icommq');
    $this->coreFunctions->sbcdropcolumn('item', 'icommr');
    $this->coreFunctions->sbcdropcolumn('item', 'icomms');
    $this->coreFunctions->sbcdropcolumn('item', 'icommt');
    $this->coreFunctions->sbcdropcolumn('item', 'icommu');
    $this->coreFunctions->sbcdropcolumn('item', 'icommv');
    $this->coreFunctions->sbcdropcolumn('item', 'icommw');
    $this->coreFunctions->sbcdropcolumn('item', 'icommx');
    $this->coreFunctions->sbcdropcolumn('item', 'icommy');
    $this->coreFunctions->sbcdropcolumn('item', 'icommz');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma1');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma2');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma3');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma4');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpa');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpb');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpc');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpd');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpe');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpf');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpg');
    $this->coreFunctions->sbcdropcolumn('item', 'isrph');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpi');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpj');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpk');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpl');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpm');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpn');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpo');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpp');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpq');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpr');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpx');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpy');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpz');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpa1');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpa2');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpa3');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpa4');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa5');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa6');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa7');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa8');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa9');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa10');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa11');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa12');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa13');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa14');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa15');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa16');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa17');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa18');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa19');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa20');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa21');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa22');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa23');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa24');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa25');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa26');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa27');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa28');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa29');
    $this->coreFunctions->sbcdropcolumn('item', 'srpa30');
    $this->coreFunctions->sbcdropcolumn('item', 'comma5');
    $this->coreFunctions->sbcdropcolumn('item', 'comma6');
    $this->coreFunctions->sbcdropcolumn('item', 'comma7');
    $this->coreFunctions->sbcdropcolumn('item', 'comma8');
    $this->coreFunctions->sbcdropcolumn('item', 'comma9');
    $this->coreFunctions->sbcdropcolumn('item', 'comma10');
    $this->coreFunctions->sbcdropcolumn('item', 'comma11');
    $this->coreFunctions->sbcdropcolumn('item', 'comma12');
    $this->coreFunctions->sbcdropcolumn('item', 'comma13');
    $this->coreFunctions->sbcdropcolumn('item', 'comma14');
    $this->coreFunctions->sbcdropcolumn('item', 'comma15');
    $this->coreFunctions->sbcdropcolumn('item', 'comma16');
    $this->coreFunctions->sbcdropcolumn('item', 'comma17');
    $this->coreFunctions->sbcdropcolumn('item', 'comma18');
    $this->coreFunctions->sbcdropcolumn('item', 'comma19');
    $this->coreFunctions->sbcdropcolumn('item', 'comma20');
    $this->coreFunctions->sbcdropcolumn('item', 'comma21');
    $this->coreFunctions->sbcdropcolumn('item', 'comma22');
    $this->coreFunctions->sbcdropcolumn('item', 'comma23');
    $this->coreFunctions->sbcdropcolumn('item', 'comma24');
    $this->coreFunctions->sbcdropcolumn('item', 'comma25');
    $this->coreFunctions->sbcdropcolumn('item', 'comma26');
    $this->coreFunctions->sbcdropcolumn('item', 'comma27');
    $this->coreFunctions->sbcdropcolumn('item', 'comma28');
    $this->coreFunctions->sbcdropcolumn('item', 'comma29');
    $this->coreFunctions->sbcdropcolumn('item', 'comma30');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma5');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma6');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma7');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma8');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma9');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma10');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma11');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma12');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma13');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma14');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma15');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma16');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma17');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma18');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma19');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma20');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma21');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma22');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma23');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma24');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma25');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma26');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma27');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma28');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma29');
    $this->coreFunctions->sbcdropcolumn('item', 'icomma30');
    $this->coreFunctions->sbcdropcolumn('item', 'expiryday');
    $this->coreFunctions->sbcdropcolumn('item', 'f_type');
    $this->coreFunctions->sbcdropcolumn('item', 'f_mainmaterial');
    $this->coreFunctions->sbcdropcolumn('item', 'f_highlights');
    $this->coreFunctions->sbcdropcolumn('item', 'f_proddesc');
    $this->coreFunctions->sbcdropcolumn('item', 'f_whatsbox');
    $this->coreFunctions->sbcdropcolumn('item', 'f_freeitems');
    $this->coreFunctions->sbcdropcolumn('item', 'f_videourl');
    $this->coreFunctions->sbcdropcolumn('item', 'f_notes');
    $this->coreFunctions->sbcdropcolumn('item', 'f_delivopt');
    $this->coreFunctions->sbcdropcolumn('item', 'f_shippingmin');
    $this->coreFunctions->sbcdropcolumn('item', 'f_shippingmax');
    $this->coreFunctions->sbcdropcolumn('item', 'f_dimensions');
    $this->coreFunctions->sbcdropcolumn('item', 'f_proweight');
    $this->coreFunctions->sbcdropcolumn('item', 'f_packheight');
    $this->coreFunctions->sbcdropcolumn('item', 'f_packlength');
    $this->coreFunctions->sbcdropcolumn('item', 'f_packweight');
    $this->coreFunctions->sbcdropcolumn('item', 'f_packwidth');
    $this->coreFunctions->sbcdropcolumn('item', 'f_warrantytype');
    $this->coreFunctions->sbcdropcolumn('item', 'f_warrantyperiod');
    $this->coreFunctions->sbcdropcolumn('item', 'f_warrantypolicy');
    $this->coreFunctions->sbcdropcolumn('item', 'f_cattagging');
    $this->coreFunctions->sbcdropcolumn('item', 'fdiscounted');
    $this->coreFunctions->sbcdropcolumn('item', 'invbal_uom');
    $this->coreFunctions->sbcdropcolumn('item', 'defaultwh');
    $this->coreFunctions->sbcdropcolumn('item', 'isdownloaded');
    $this->coreFunctions->sbcdropcolumn('item', 'grp');
    $this->coreFunctions->sbcdropcolumn('item', 'gm_printuom');
    $this->coreFunctions->sbcdropcolumn('item', 'uv_principal');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_customer');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_numcolors');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_diameter');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_prodtype');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_combi');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_transform');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_addspecs');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_punchholesize');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_sealing');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_plasticcolor');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_bfilmdet');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_treatment');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_rate');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_quantity');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_revision');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_templateno');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_updated');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_effective');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_jowidth');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_jowidthuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_jolength');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_jolengthuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_thickness');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_thicknessuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_actualwidth');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_actualwidthuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_actuallength');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_actuallengthuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_colornum');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_repeatlength');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_repeatlengthuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_outnum');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_outnumuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_bfilmwidth');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_bfilmwidthuom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_thickness2');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_thickness2uom');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_gramppiece1');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_gramppiece2');
    $this->coreFunctions->sbcdropcolumn('item', 'purchase_uom');
    $this->coreFunctions->sbcdropcolumn('item', 'itemhandling2');
    $this->coreFunctions->sbcdropcolumn('item', 'fg_serial');
    $this->coreFunctions->sbcdropcolumn('item', 'fgunit_diameter');
    $this->coreFunctions->sbcdropcolumn('item', 'fgunit_width');
    $this->coreFunctions->sbcdropcolumn('item', 'fgunit_length');
    $this->coreFunctions->sbcdropcolumn('item', 'fgunit_thickness');
    $this->coreFunctions->sbcdropcolumn('item', 'isrps');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpt');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpu');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpv');
    $this->coreFunctions->sbcdropcolumn('item', 'isrpw');
    $this->coreFunctions->sbcdropcolumn('item', 'specs');
    $this->coreFunctions->sbcdropcolumn('item', 'f_prodweight');
    $this->coreFunctions->sbcdropcolumn('item', 'f_warrantperiod');
    $this->coreFunctions->sbcdropcolumn('item', 'f_warrantpolicy');
    $this->coreFunctions->sbcdropcolumn('item', 'setfrontend');
    $this->coreFunctions->sbcdropcolumn('item', 'sc_subcatid');
    $this->coreFunctions->sbcdropcolumn('item', 'sc_commgrpid');
    $this->coreFunctions->sbcdropcolumn('item', 'kds');
    $this->coreFunctions->sbcdropcolumn('item', 'cooking_time');
    $this->coreFunctions->sbcdropcolumn('item', 'prep_time');
    $this->coreFunctions->sbcdropcolumn('item', 'itemcomm');
    $this->coreFunctions->sbcdropcolumn('item', 'itemhandling');
    $this->coreFunctions->sbcdropcolumn('item', 'uv_priority');
    $this->coreFunctions->sbcdropcolumn('item', 'uv_department');
    $this->coreFunctions->sbcdropcolumn('item', 'uv_suppitemcode');
    $this->coreFunctions->sbcaddcolumn("item", "foramt", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("item", "fg_isfinishedgood", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("item", "fg_isequipmenttool", "int(11) NOT NULL DEFAULT '0'", 0);

    //JAC 01052021
    $this->coreFunctions->sbcaddcolumn("lahead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "creditinfo", "varchar(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    //VITALINE ADDONS
    $this->coreFunctions->sbcaddcolumn("lastock", "rebate", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "rebate", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    //menuseq JAC 01082021
    $this->coreFunctions->sbcaddcolumn("left_menu", "seq", "integer NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("iplog", "doc", "VARCHAR(50) NOT NULL DEFAULT ''", 1);


    //FRED 01.13.2021
    $qry = "
    CREATE TABLE `serialin` (
      `sline` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
      `trno` INT(11) NOT NULL DEFAULT '0',
      `line` INT(11) NOT NULL DEFAULT '0',
      `outline` BIGINT(11) NOT NULL DEFAULT '0',
      `serial` varchar(45) NOT NULL DEFAULT '',
      KEY `Index_1` (`sline`, `trno`, `line`, `outline`, `serial`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("serialin", $qry);

    $qry = "
    CREATE TABLE `serialout` (
      `sline` BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
      `trno` INT(11) NOT NULL DEFAULT '0',
      `line` INT(11) NOT NULL DEFAULT '0',
      `serial` varchar(45) NOT NULL DEFAULT '',
      KEY `Index_1` (`sline`, `trno`, `line`, `serial`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("serialout", $qry);



    #GLEN 01.22.2021
    $this->coreFunctions->sbcaddcolumn("profile", "doc", "varchar(25) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("attributes", "description", "varchar(250) NOT NULL DEFAULT ''", 1);

    //FPY 1.25.2021
    $this->coreFunctions->sbcaddcolumn("client", "isstudent", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    #GLEN 01.26.2020
    $this->coreFunctions->sbcaddcolumn("item", "groupid", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("item", "part", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("item", "model", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("item", "brand", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("item", "class", "int(11) NOT NULL DEFAULT '0'", 1);

    //JAC 02082021
    $this->coreFunctions->sbcaddcolumn("sostock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("lastock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("glstock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("prhead", "project", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("prhead", "subproject", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "project", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "subproject", "VARCHAR(30) NOT NULL DEFAULT ''", 0);


    $qry = "CREATE TABLE  `pmhead` (
        `trno` int(10) unsigned NOT NULL DEFAULT '0',
        `docno` varchar(45) NOT NULL DEFAULT '',
        `client` varchar(45) NOT NULL DEFAULT '',
        `projectid` int(10) NOT NULL DEFAULT '0',
        `tcp` decimal(18,2) NOT NULL DEFAULT '0.00',
        `cost` decimal(18,2) NOT NULL DEFAULT '0.00',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `closedate` datetime DEFAULT NULL,
        `completed` varchar(45) NOT NULL DEFAULT '',
        `retention` varchar(45) NOT NULL DEFAULT '',
        `dp` varchar(45) NOT NULL DEFAULT '',
        `rem` varchar(45) NOT NULL DEFAULT '',
        `clientname` varchar(100) NOT NULL DEFAULT '',
        `address` varchar(150) NOT NULL DEFAULT '',
        `doc` varchar(4) NOT NULL DEFAULT '',
        `createdate` datetime DEFAULT NULL,
        `createby` varchar(45) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `editby` varchar(45) NOT NULL DEFAULT '',
        `viewby` varchar(45) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        `lockuser` varchar(45) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("pmhead", $qry);

    $qry = "CREATE TABLE  `hpmhead` (
        `trno` int(10) unsigned NOT NULL DEFAULT '0',
        `docno` varchar(45) NOT NULL DEFAULT '',
        `client` varchar(45) NOT NULL DEFAULT '',
        `projectid` int(10) NOT NULL DEFAULT '0',
        `tcp` decimal(18,2) NOT NULL DEFAULT '0.00',
        `cost` decimal(18,2) NOT NULL DEFAULT '0.00',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `closedate` datetime DEFAULT NULL,
        `completed` varchar(45) NOT NULL DEFAULT '',
        `retention` varchar(45) NOT NULL DEFAULT '',
        `dp` varchar(45) NOT NULL DEFAULT '',
        `rem` varchar(45) NOT NULL DEFAULT '',
        `clientname` varchar(100) NOT NULL DEFAULT '',
        `address` varchar(150) NOT NULL DEFAULT '',
        `doc` varchar(4) NOT NULL DEFAULT '',
        `createdate` datetime DEFAULT NULL,
        `createby` varchar(45) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `editby` varchar(45) NOT NULL DEFAULT '',
        `viewby` varchar(45) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        `lockuser` varchar(45) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hpmhead", $qry);

    $qry = "CREATE TABLE  `subproject` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `subproject` varchar(150) NOT NULL DEFAULT '',
      `projpercent` varchar(45) NOT NULL DEFAULT '',
      `completed` varchar(45) NOT NULL DEFAULT '',
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(45) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`subproject`,`projectid`) USING BTREE,
      KEY `Index_2` (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("subproject", $qry);


    $qry = "CREATE TABLE  `hsubproject` (
      `line` int(10) unsigned NOT NULL,
      `subproject` varchar(150) NOT NULL DEFAULT '',
      `projpercent` varchar(45) NOT NULL DEFAULT '',
      `completed` varchar(45) NOT NULL DEFAULT '',
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(45) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`subproject`,`projectid`) USING BTREE,
      KEY `Index_2` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hsubproject", $qry);

    $qry = "CREATE TABLE `stages` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `stage` int(10) unsigned NOT NULL DEFAULT '0',
      `cost` decimal(18,2) NOT NULL DEFAULT '0.00',
      `ar` decimal(18,2) NOT NULL DEFAULT '0.00',
      `ap` decimal(18,2) NOT NULL DEFAULT '0.00',
      `projpercent` varchar(45) NOT NULL DEFAULT '',
      `completed` varchar(45) NOT NULL DEFAULT '',
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      `subproject` int(10) unsigned NOT NULL DEFAULT '0',
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `boq` decimal(18,2) NOT NULL DEFAULT '0.00',
      `paid` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pr` decimal(18,2) NOT NULL DEFAULT '0.00',
      `po` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rr` decimal(18,2) NOT NULL DEFAULT '0.00',
      `jo` decimal(18,2) NOT NULL DEFAULT '0.00',
      `jc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `editby` varchar(45) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`stage`,`subproject`,`projectid`),
      KEY `Index_1` (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("stages", $qry);

    $this->coreFunctions->sbcaddcolumn("hpostock", 'paid', "decimal(18,6) NOT NULL DEFAULT '0.000000'", 1);

    $this->coreFunctions->sbcaddcolumn("sohead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsohead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sohead", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsohead", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sostock", "disc", "varchar(40) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hsostock", "disc", "varchar(40) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("prhead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("prhead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("prstock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprstock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("prstock", "rqty", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprstock", "rqty", "decimal(18,2) NOT NULL DEFAULT '0'", 0);


    //FPY 5.1.2021
    $this->coreFunctions->sbcaddcolumn("useraccess", "project", "varchar(40) NOT NULL DEFAULT ''", 0);

    //JAC 05.01.2021
    $this->coreFunctions->sbcaddcolumn("pmhead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpmhead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pohead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pohead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("lahead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ladetail", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);

    //JAC 05.08.2021
    $qry = "CREATE TABLE  `stagesmasterfile` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `stage` varchar(100) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("stagesmasterfile", $qry);

    $this->coreFunctions->sbcaddcolumn("stagesmasterfile", "description", "varchar(100) NOT NULL DEFAULT ''", 0);



    //JAC 05.15.2021
    $qry = "CREATE TABLE  `johead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
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
      `workloc` varchar(250) NOT NULL DEFAULT '',
      `workdesc` varchar(500) NOT NULL DEFAULT '',
      `stageid` int(11) NOT NULL DEFAULT '0',
      `printtime` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_2head` (`docno`,`client`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("johead", $qry);

    $qry = "CREATE TABLE  `jostock` (
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
    $this->coreFunctions->sbccreatetable("jostock", $qry);

    $qry = "CREATE TABLE  `hjohead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
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
    `workloc` varchar(250) NOT NULL DEFAULT '',
    `workdesc` varchar(500) NOT NULL DEFAULT '',
    `stageid` int(11) NOT NULL DEFAULT '0',
    `printtime` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    KEY `Index_2head` (`docno`,`client`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hjohead", $qry);

    $qry = "CREATE TABLE `hjostock` (
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
    `length` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `width` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `wt` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `iqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `encodeddate` datetime DEFAULT NULL,
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

    $this->coreFunctions->sbccreatetable("hjostock", $qry);

    //FPY 5.16.2021
    $this->coreFunctions->sbcaddcolumn("rrstatus", "palletid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("rrstatus", "locid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "palletid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "locid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "palletid2", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "locid2", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("glstock", "palletid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "locid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "palletid2", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "locid2", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pcstock", "palletid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pcstock", "locid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "palletid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "locid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pcstock", "palletid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pcstock", "locid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "palletid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "locid", "int(11) NOT NULL DEFAULT '0'", 0);

    // FPY 5.18.2021 fields to be removed in client table
    //confi,type,acct,acquireddate,warrantexpiry,servicedate,solddisposeddate,year,make,model,color,motorno,serialno,renewaldate,insurer,
    //insurancepol,customerdisc,vendorname,attention,sex,sss,pib,jobdesc,citizen,religion,hired,resigned,rate,philhealth,class1,code,smpkid,
    //pkid,clientpref,floor,building,registeredfrom,rem1,rem2,rem3

    //FMM 5.1.2021
    $this->coreFunctions->sbcaddcolumn("lahead", "pltrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "pltrno", "int(11) NOT NULL DEFAULT '0'", 0);

    //GLEN 05.18.2021
    $qry = "CREATE TABLE `cmodels` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `brand` varchar(50) NOT NULL DEFAULT '',
      `model` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("cmodels", $qry);

    $qry = "CREATE TABLE `itemcmodels` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `itemid` int(10) NOT NULL DEFAULT '0',
      `cmodelid` int(10) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("itemcmodels", $qry);

    //JAC 05182021
    $qry = "CREATE TABLE  `jchead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
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
    `workloc` varchar(250) NOT NULL DEFAULT '',
    `workdesc` varchar(500) NOT NULL DEFAULT '',
    `stageid` int(11) NOT NULL DEFAULT '0',
    `printtime` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    KEY `Index_head` (`docno`,`client`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("jchead", $qry);

    $qry = "CREATE TABLE  `hjchead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
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
    `workloc` varchar(250) NOT NULL DEFAULT '',
    `workdesc` varchar(500) NOT NULL DEFAULT '',
    `stageid` int(11) NOT NULL DEFAULT '0',
    `printtime` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    KEY `Index_head` (`docno`,`client`,`dateid`,`due`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hjchead", $qry);

    $qry = "CREATE TABLE  `jcstock` (
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
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_barcode` (`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("jcstock", $qry);

    $qry = "CREATE TABLE  `hjcstock` (
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
      `length` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `width` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `wt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `sku` varchar(45) DEFAULT NULL,
      `loc` varchar(20) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_2barcode` (`uom`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hjcstock", $qry);

    $this->coreFunctions->sbcaddcolumn("jchead", "retention", "varchar(15) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hjchead", "retention", "varchar(15) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("jchead", "contra", "varchar(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hjchead", "contra", "varchar(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("jchead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjchead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);

    // FPY 5.19.2021
    // Sales Order Agent
    $qry = "CREATE TABLE `sahead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(100) DEFAULT NULL,
      `customername` varchar(150) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `due` datetime DEFAULT NULL,
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
      `trnx_type` varchar(45) NOT NULL DEFAULT '',
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      `creditinfo` varchar(1000) NOT NULL DEFAULT '',
      `subproject` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("sahead", $qry);

    $qry = "CREATE TABLE `sastock` (
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
    $this->coreFunctions->sbccreatetable("sastock", $qry);

    $qry = "CREATE TABLE `hsahead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) DEFAULT NULL,
    `address` varchar(150) DEFAULT NULL,
    `shipto` varchar(100) DEFAULT NULL,
    `customername` varchar(150) DEFAULT NULL,
    `tel` varchar(50) DEFAULT NULL,
    `dateid` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
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
    `createdate` datetime DEFAULT NULL,
    `createby` varchar(50) NOT NULL DEFAULT '',
    `editby` varchar(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `salestype` varchar(25) DEFAULT '',
    `deliverytype` int(11) NOT NULL DEFAULT '0',
    `trnx_type` varchar(45) NOT NULL DEFAULT '',
    `projectid` int(10) unsigned NOT NULL DEFAULT '0',
    `creditinfo` varchar(1000) NOT NULL DEFAULT '',
    `subproject` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsahead", $qry);

    $this->coreFunctions->sbcaddcolumn("sahead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hsahead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("sahead", "customername", "varchar(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hsahead", "customername", "varchar(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "customername", "varchar(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "customername", "varchar(150) NOT NULL DEFAULT ''", 0);


    $qry = "CREATE TABLE `hsastock` (
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
    `encodeddate` datetime DEFAULT NULL,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    `loc` varchar(45) NOT NULL DEFAULT '',
    `expiry` varchar(45) DEFAULT NULL,
    `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
    PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hsastock", $qry);

    $qry = "CREATE TABLE `hscstock` (
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
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `loc` varchar(45) NOT NULL DEFAULT '',
      `expiry` varchar(45) DEFAULT NULL,
      `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hscstock", $qry);

    //FPY 5.20.2021
    $qry = "CREATE TABLE `sbhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(100) DEFAULT NULL,
      `customername` varchar(150) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `due` datetime DEFAULT NULL,
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
      `trnx_type` varchar(45) NOT NULL DEFAULT '',
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      `creditinfo` varchar(1000) NOT NULL DEFAULT '',
      `subproject` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("sbhead", $qry);

    $qry = "CREATE TABLE `sbstock` (
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
    $this->coreFunctions->sbccreatetable("sbstock", $qry);

    $qry = "CREATE TABLE `hsbhead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(20) NOT NULL,
        `client` varchar(15) NOT NULL DEFAULT '',
        `clientname` varchar(150) DEFAULT NULL,
        `address` varchar(150) DEFAULT NULL,
        `shipto` varchar(100) DEFAULT NULL,
        `customername` varchar(150) DEFAULT NULL,
        `tel` varchar(50) DEFAULT NULL,
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
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
        `createdate` datetime DEFAULT NULL,
        `createby` varchar(50) NOT NULL DEFAULT '',
        `editby` varchar(50) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `viewby` varchar(50) NOT NULL DEFAULT '',
        `viewdate` datetime DEFAULT NULL,
        `salestype` varchar(25) DEFAULT '',
        `deliverytype` int(11) NOT NULL DEFAULT '0',
        `trnx_type` varchar(45) NOT NULL DEFAULT '',
        `projectid` int(10) unsigned NOT NULL DEFAULT '0',
        `creditinfo` varchar(1000) NOT NULL DEFAULT '',
        `subproject` int(11) NOT NULL DEFAULT '0',
        `stageid` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`trno`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsbhead", $qry);

    $qry = "CREATE TABLE `hsbstock` (
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
        `encodeddate` datetime DEFAULT NULL,
        `encodedby` varchar(20) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `editby` varchar(20) NOT NULL DEFAULT '',
        `loc` varchar(45) NOT NULL DEFAULT '',
        `expiry` varchar(45) DEFAULT NULL,
        `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
        PRIMARY KEY (`trno`,`line`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hsbstock", $qry);

    $qry = "CREATE TABLE `schead` (
          `trno` bigint(20) NOT NULL DEFAULT '0',
          `doc` char(2) NOT NULL DEFAULT '',
          `docno` char(20) NOT NULL,
          `client` varchar(15) NOT NULL DEFAULT '',
          `clientname` varchar(150) DEFAULT NULL,
          `address` varchar(150) DEFAULT NULL,
          `shipto` varchar(100) DEFAULT NULL,
          `customername` varchar(150) DEFAULT NULL,
          `tel` varchar(50) DEFAULT NULL,
          `dateid` datetime DEFAULT NULL,
          `due` datetime DEFAULT NULL,
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
          `trnx_type` varchar(45) NOT NULL DEFAULT '',
          `projectid` int(10) unsigned NOT NULL DEFAULT '0',
          `creditinfo` varchar(1000) NOT NULL DEFAULT '',
          `subproject` int(11) NOT NULL DEFAULT '0',
          `stageid` int(11) NOT NULL DEFAULT '0',
          PRIMARY KEY (`trno`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("schead", $qry);

    $qry = "CREATE TABLE `scstock` (
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
    $this->coreFunctions->sbccreatetable("scstock", $qry);

    $qry = "CREATE TABLE `hschead` (
            `trno` bigint(20) NOT NULL DEFAULT '0',
            `doc` char(2) NOT NULL DEFAULT '',
            `docno` char(20) NOT NULL,
            `client` varchar(15) NOT NULL DEFAULT '',
            `clientname` varchar(150) DEFAULT NULL,
            `address` varchar(150) DEFAULT NULL,
            `shipto` varchar(100) DEFAULT NULL,
            `customername` varchar(150) DEFAULT NULL,
            `tel` varchar(50) DEFAULT NULL,
            `dateid` datetime DEFAULT NULL,
            `due` datetime DEFAULT NULL,
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
            `createdate` datetime DEFAULT NULL,
            `createby` varchar(50) NOT NULL DEFAULT '',
            `editby` varchar(50) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            `viewby` varchar(50) NOT NULL DEFAULT '',
            `viewdate` datetime DEFAULT NULL,
            `salestype` varchar(25) DEFAULT '',
            `deliverytype` int(11) NOT NULL DEFAULT '0',
            `trnx_type` varchar(45) NOT NULL DEFAULT '',
            `projectid` int(10) unsigned NOT NULL DEFAULT '0',
            `creditinfo` varchar(1000) NOT NULL DEFAULT '',
            `subproject` int(11) NOT NULL DEFAULT '0',
            `stageid` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`trno`)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hschead", $qry);

    $qry = "CREATE TABLE `hcbstock` (
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
            `encodeddate` datetime DEFAULT NULL,
            `encodedby` varchar(20) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            `editby` varchar(20) NOT NULL DEFAULT '',
            `loc` varchar(45) NOT NULL DEFAULT '',
            `expiry` varchar(45) DEFAULT NULL,
            `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
            PRIMARY KEY (`trno`,`line`)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hcbstock", $qry);

    $qry = "CREATE TABLE `boxinginfo` (
        `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `itemid` int(11) NOT NULL DEFAULT '0',
        `qty` decimal(19,6) NOT NULL DEFAULT '0.000000',
        `boxno` int(11) NOT NULL DEFAULT '0',
        `groupid` int(11) NOT NULL DEFAULT '0',
        `groupid2` int(11) NOT NULL DEFAULT '0',
        `scandate` datetime DEFAULT NULL,
        `scanby` varchar(20) NOT NULL DEFAULT '',
        PRIMARY KEY (`line`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("boxinginfo", $qry);

    $this->coreFunctions->createindex("boxinginfo", "Index_trno", ['trno']);
    $this->coreFunctions->createindex("boxinginfo", "Index_itemid", ['itemid']);
    $this->coreFunctions->createindex("boxinginfo", "Index_boxno", ['boxno']);

    $qry = "CREATE TABLE `hboxinginfo` (
        `line` int(10) NOT NULL DEFAULT '0',
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `itemid` int(11) NOT NULL DEFAULT '0',
        `qty` decimal(19,6) NOT NULL DEFAULT '0.000000',
        `boxno` int(11) NOT NULL DEFAULT '0',
        `groupid` int(11) NOT NULL DEFAULT '0',
        `groupid2` int(11) NOT NULL DEFAULT '0',
        `scandate` datetime DEFAULT NULL,
        `scanby` varchar(20) NOT NULL DEFAULT '',
        PRIMARY KEY (`line`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hboxinginfo", $qry);

    $this->coreFunctions->createindex("boxinginfo", "Index_trno", ['trno']);
    $this->coreFunctions->createindex("boxinginfo", "Index_itemid", ['itemid']);
    $this->coreFunctions->createindex("boxinginfo", "Index_boxno", ['boxno']);

    $this->coreFunctions->sbcaddcolumn("boxinginfo", "groupid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("boxinginfo", "groupid2", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("boxinginfo", "scandate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("boxinginfo", "scanby", "varchar(20) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("transnum", "ltrno", "bigint(20) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cntnum", "ltrno", "bigint(20) NOT NULL DEFAULT '0'", 0);

    //glen 5.19.2021

    $this->coreFunctions->sbcaddcolumn("client", "prefix", "varchar(4) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("item", "supplier", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("lahead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("glhead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);

    //JAC 052621
    $qry = "CREATE TABLE  `pqhead` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `doc` char(2) NOT NULL DEFAULT '',
  `docno` char(20) NOT NULL DEFAULT '',
  `client` varchar(20) NOT NULL DEFAULT '',
  `clientname` varchar(150) NOT NULL DEFAULT '',
  `address` varchar(150) NOT NULL DEFAULT '',
  `shipto` varchar(150) NOT NULL DEFAULT '',
  `dateid` date DEFAULT NULL,
  `due` date DEFAULT NULL,
  `terms` varchar(30) NOT NULL DEFAULT '',
  `wh` varchar(20) NOT NULL DEFAULT '',
  `rem` varchar(500) NOT NULL DEFAULT '',
  `cur` varchar(3) NOT NULL DEFAULT '',
  `forex` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `voiddate` datetime DEFAULT NULL,
  `contra` varchar(100) NOT NULL DEFAULT '',
  `ourref` varchar(50) NOT NULL DEFAULT '',
  `yourref` varchar(50) NOT NULL DEFAULT '',
  `prepared` varchar(50) NOT NULL DEFAULT '',
  `approved` varchar(45) NOT NULL DEFAULT '',
  `checked` varchar(45) NOT NULL DEFAULT '',
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
  `projectid` int(10) DEFAULT '0',
  PRIMARY KEY (`trno`),
  KEY `Index_4` (`client`),
  KEY `Index_6` (`doc`),
  KEY `Index_dateid` (`dateid`) USING BTREE,
  KEY `Index_doc` (`doc`) USING BTREE,
  KEY `Index_5` (`docno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("pqhead", $qry);

    $qry = "CREATE TABLE  `pqdetail` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL DEFAULT '0',
  `client` varchar(20) NOT NULL DEFAULT '',
  `rem` varchar(250) NOT NULL DEFAULT '',
  `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `postdate` date DEFAULT NULL,
  `ref` varchar(50) NOT NULL DEFAULT '',
  `refx` bigint(20) NOT NULL DEFAULT '0',
  `linex` int(11) NOT NULL DEFAULT '0',
  `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `encodedby` varchar(20) DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `editby` varchar(20) DEFAULT '',
  `isewt` int(2) NOT NULL DEFAULT '0',
  `isvat` int(2) NOT NULL DEFAULT '0',
  `ewtcode` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` varchar(45) NOT NULL DEFAULT '',
  `isvewt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `acnoid` int(11) NOT NULL DEFAULT '0',
  `projectid` int(11) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`,`line`),
  UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE,
  KEY `Index_refx` (`refx`,`linex`),
  KEY `Index_postdate` (`postdate`),
  KEY `Index_client` (`client`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("pqdetail", $qry);

    $qry = "CREATE TABLE  `hpqhead` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `doc` char(2) NOT NULL DEFAULT '',
  `docno` char(20) NOT NULL DEFAULT '',
  `client` varchar(20) NOT NULL DEFAULT '',
  `clientname` varchar(150) NOT NULL DEFAULT '',
  `address` varchar(150) NOT NULL DEFAULT '',
  `shipto` varchar(150) NOT NULL DEFAULT '',
  `dateid` date DEFAULT NULL,
  `due` date DEFAULT NULL,
  `terms` varchar(30) NOT NULL DEFAULT '',
  `wh` varchar(20) NOT NULL DEFAULT '',
  `rem` varchar(500) NOT NULL DEFAULT '',
  `cur` varchar(3) NOT NULL DEFAULT '',
  `forex` decimal(18,4) NOT NULL DEFAULT '0.0000',
  `voiddate` datetime DEFAULT NULL,
  `contra` varchar(100) NOT NULL DEFAULT '',
  `ourref` varchar(50) NOT NULL DEFAULT '',
  `yourref` varchar(50) NOT NULL DEFAULT '',
  `prepared` varchar(50) NOT NULL DEFAULT '',
  `approved` varchar(45) NOT NULL DEFAULT '',
  `checked` varchar(45) NOT NULL DEFAULT '',
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
  `projectid` int(10) DEFAULT '0',
  PRIMARY KEY (`trno`),
  KEY `Index_4` (`client`),
  KEY `Index_6` (`doc`),
  KEY `Index_dateid` (`dateid`) USING BTREE,
  KEY `Index_doc` (`doc`) USING BTREE,
  KEY `Index_5` (`docno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hpqhead", $qry);

    $qry = "CREATE TABLE  `hpqdetail` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL DEFAULT '0',
  `client` varchar(20) NOT NULL DEFAULT '',
  `rem` varchar(250) NOT NULL DEFAULT '',
  `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `postdate` date DEFAULT NULL,
  `ref` varchar(50) NOT NULL DEFAULT '',
  `refx` bigint(20) NOT NULL DEFAULT '0',
  `linex` int(11) NOT NULL DEFAULT '0',
  `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `encodedby` varchar(20) DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `editby` varchar(20) DEFAULT '',
  `isewt` int(2) NOT NULL DEFAULT '0',
  `isvat` int(2) NOT NULL DEFAULT '0',
  `ewtcode` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` varchar(45) NOT NULL DEFAULT '',
  `isvewt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `acnoid` int(11) NOT NULL DEFAULT '0',
  `projectid` int(11) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`,`line`),
  UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE,
  KEY `Index_refx` (`refx`,`linex`),
  KEY `Index_postdate` (`postdate`),
  KEY `Index_client` (`client`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hpqdetail", $qry);

    // FRED 5.26.2021
    $this->coreFunctions->sbcaddcolumn("users", "attributes", "varchar(10000) NOT NULL DEFAULT ''", 1);

    //JAC 05.27.2021
    $qry = "CREATE TABLE  `svhead` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `doc` char(2) NOT NULL DEFAULT '',
  `docno` char(20) NOT NULL DEFAULT '',
  `client` varchar(20) NOT NULL DEFAULT '',
  `clientname` varchar(150) NOT NULL DEFAULT '',
  `address` varchar(150) NOT NULL DEFAULT '',
  `shipto` varchar(150) NOT NULL DEFAULT '',
  `dateid` date DEFAULT NULL,
  `rem` varchar(500) NOT NULL DEFAULT '',
  `voiddate` datetime DEFAULT NULL,
  `contra` varchar(100) NOT NULL DEFAULT '',
  `ourref` varchar(50) NOT NULL DEFAULT '',
  `yourref` varchar(50) NOT NULL DEFAULT '',
  `tax` decimal(18,0) NOT NULL DEFAULT '0',
  `vattype` varchar(45) NOT NULL DEFAULT '',
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
  `projectid` int(10) DEFAULT '0',
  `ewt` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` int(10) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `cvtrno` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`),
  KEY `Index_4` (`client`),
  KEY `Index_6` (`doc`),
  KEY `Index_dateid` (`dateid`) USING BTREE,
  KEY `Index_doc` (`doc`) USING BTREE,
  KEY `Index_5` (`docno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("svhead", $qry);

    $qry = "CREATE TABLE  `svdetail` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL DEFAULT '0',
  `client` varchar(20) NOT NULL DEFAULT '',
  `rem` varchar(250) NOT NULL DEFAULT '',
  `db` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `cr` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `postdate` date DEFAULT NULL,
  `ref` varchar(50) NOT NULL DEFAULT '',
  `refx` bigint(20) NOT NULL DEFAULT '0',
  `linex` int(11) NOT NULL DEFAULT '0',
  `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `encodedby` varchar(20) DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `editby` varchar(20) DEFAULT '',
  `cvtrno` int(10) unsigned NOT NULL DEFAULT '0',
  `isewt` int(2) NOT NULL DEFAULT '0',
  `isvat` int(2) NOT NULL DEFAULT '0',
  `ewtcode` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` varchar(45) NOT NULL DEFAULT '',
  `isvewt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `acnoid` int(11) NOT NULL DEFAULT '0',
  `projectid` int(11) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`,`line`),
  UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE,
  KEY `Index_refx` (`refx`,`linex`),
  KEY `Index_postdate` (`postdate`),
  KEY `Index_client` (`client`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("svdetail", $qry);

    $qry = "CREATE TABLE  `hsvhead` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `doc` char(2) NOT NULL DEFAULT '',
  `docno` char(20) NOT NULL DEFAULT '',
  `client` varchar(20) NOT NULL DEFAULT '',
  `clientname` varchar(150) NOT NULL DEFAULT '',
  `address` varchar(150) NOT NULL DEFAULT '',
  `shipto` varchar(150) NOT NULL DEFAULT '',
  `dateid` date DEFAULT NULL,
  `rem` varchar(500) NOT NULL DEFAULT '',
  `voiddate` datetime DEFAULT NULL,
  `contra` varchar(100) NOT NULL DEFAULT '',
  `ourref` varchar(50) NOT NULL DEFAULT '',
  `yourref` varchar(50) NOT NULL DEFAULT '',
  `tax` decimal(18,0) NOT NULL DEFAULT '0',
  `vattype` varchar(45) NOT NULL DEFAULT '',
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
  `projectid` int(10) DEFAULT '0',
  `ewt` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` int(10) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`),
  KEY `Index_4` (`client`),
  KEY `Index_6` (`doc`),
  KEY `Index_dateid` (`dateid`) USING BTREE,
  KEY `Index_doc` (`doc`) USING BTREE,
  KEY `Index_5` (`docno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hsvhead", $qry);

    $this->coreFunctions->sbcaddcolumn("hsvhead", "cvtrno", "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `hsvdetail` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL DEFAULT '0',
  `client` varchar(20) NOT NULL DEFAULT '',
  `rem` varchar(250) NOT NULL DEFAULT '',
  `db` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `cr` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `postdate` date DEFAULT NULL,
  `ref` varchar(50) NOT NULL DEFAULT '',
  `refx` bigint(20) NOT NULL DEFAULT '0',
  `linex` int(11) NOT NULL DEFAULT '0',
  `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `encodedby` varchar(20) DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `editby` varchar(20) DEFAULT '',
  `isewt` int(2) NOT NULL DEFAULT '0',
  `isvat` int(2) NOT NULL DEFAULT '0',
  `ewtcode` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` varchar(45) NOT NULL DEFAULT '',
  `isvewt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `acnoid` int(11) NOT NULL DEFAULT '0',
  `projectid` int(11) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  `cvtrno` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`,`line`),
  UNIQUE KEY `Index_trno` (`trno`,`line`) USING BTREE,
  KEY `Index_refx` (`refx`,`linex`),
  KEY `Index_postdate` (`postdate`),
  KEY `Index_client` (`client`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hsvdetail", $qry);

    $this->coreFunctions->sbcaddcolumn("pqdetail", "isok", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpqdetail", "isok", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ladetail", "pcvtrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "pcvtrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("prstock", "siqa", "decimal(19,6) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumn("hprstock", "siqa", "decimal(19,6) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "refx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "linex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sostock", "refx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sostock", "linex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sostock", "poqa", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "ref", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("sostock", "ref", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "poqa", "decimal(19,6) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("cmodels", "classification", "varchar(100) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE  `qthead` (
  `trno` bigint(20) NOT NULL DEFAULT '0',
  `doc` char(2) NOT NULL DEFAULT '',
  `docno` char(20) NOT NULL,
  `client` varchar(15) NOT NULL DEFAULT '',
  `clientname` varchar(150) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `shipto` varchar(100) DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `dateid` datetime DEFAULT NULL,
  `due` datetime DEFAULT NULL,
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
  `projectid` int(10) unsigned NOT NULL DEFAULT '0',
  `vattype` varchar(25) NOT NULL DEFAULT '',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("qthead", $qry);

    $qry = "CREATE TABLE  `qtstock` (
  `trno` bigint(20) NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL DEFAULT '0',
  `uom` varchar(15) DEFAULT '',
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
  `expiry` varchar(45) DEFAULT '',
  `fstatus` varchar(45) NOT NULL DEFAULT '',
  `wh_currentqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `mrsqa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
  `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `itemid` int(11) NOT NULL DEFAULT '0',
  `whid` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`,`line`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("qtstock", $qry);


    $qry = "CREATE TABLE  `hqthead` (
  `trno` bigint(20) NOT NULL DEFAULT '0',
  `doc` char(2) NOT NULL DEFAULT '',
  `docno` char(20) NOT NULL,
  `client` varchar(15) NOT NULL DEFAULT '',
  `clientname` varchar(150) DEFAULT NULL,
  `address` varchar(150) DEFAULT NULL,
  `shipto` varchar(150) DEFAULT NULL,
  `tel` varchar(50) DEFAULT NULL,
  `dateid` datetime DEFAULT NULL,
  `due` datetime DEFAULT NULL,
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
  `createdate` datetime DEFAULT NULL,
  `createby` varchar(50) NOT NULL DEFAULT '',
  `editby` varchar(50) NOT NULL DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `viewby` varchar(50) NOT NULL DEFAULT '',
  `viewdate` datetime DEFAULT NULL,
  `projectid` int(10) unsigned NOT NULL DEFAULT '0',
  `vattype` varchar(25) NOT NULL DEFAULT '',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hqthead", $qry);

    $qry = "CREATE TABLE  `hqtstock` (
  `trno` bigint(20) NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL,
  `uom` varchar(15) DEFAULT NULL,
  `disc` varchar(40) NOT NULL DEFAULT '',
  `rem` varchar(40) DEFAULT NULL,
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
  `fstatus` varchar(45) NOT NULL DEFAULT '',
  `wh_currentqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `mrsqa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
  `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `itemid` int(11) NOT NULL DEFAULT '0',
  `whid` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`trno`,`line`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hqtstock", $qry);

    $this->coreFunctions->sbcaddcolumn("svhead", "amt", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("svhead", "ref", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hsvhead", "amt", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsvhead", "ref", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("pmhead", "wh", "varchar(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hpmhead", "wh", "varchar(30) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `arservicedetail` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `description` varchar(45) NOT NULL DEFAULT '',
    `qty` varchar(20) NOT NULL DEFAULT '',
    `amt` varchar(20) NOT NULL DEFAULT '',
    `createdate` datetime DEFAULT NULL,
    `createby` varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("arservicedetail", $qry);
    $this->coreFunctions->sbcaddcolumn("arservicedetail", "description", "VARCHAR(300) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("sbhead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hsbhead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("schead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hschead", "deliverytype", "int(11) NOT NULL DEFAULT '0'", 1);

    // JIKS 6.07.2021
    $qry = "CREATE TABLE  `whdoc` (
  `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `docno` varchar(20) NOT NULL,
  `issued` datetime DEFAULT NULL,
  `expiry` datetime DEFAULT NULL,
  `dateid` datetime DEFAULT NULL,
  `oic1` varchar(20) NOT NULL,
  `oic2` varchar(20) NOT NULL,
  `rem` varchar(1000) NOT NULL,
  `status` varchar(15) NOT NULL,
  `whid` int(11) unsigned NOT NULL DEFAULT '0',
  `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createby` varchar(20) NOT NULL DEFAULT '',
  `editby` varchar(20) NOT NULL DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `viewby` varchar(50) NOT NULL DEFAULT '',
  `viewdate` datetime DEFAULT NULL,
  PRIMARY KEY (`line`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("whdoc", $qry);

    $this->coreFunctions->sbcaddcolumn("whdoc", "docno", "varchar(500) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE  `whnods` (
  `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `docno` varchar(20) NOT NULL,
  `issued` datetime DEFAULT NULL,
  `expiry` datetime DEFAULT NULL,
  `dateid` datetime DEFAULT NULL,
  `oic1` varchar(20) NOT NULL,
  `oic2` varchar(20) NOT NULL,
  `rem` varchar(1000) NOT NULL,
  `status` varchar(15) NOT NULL,
  `whid` int(11) unsigned NOT NULL DEFAULT '0',
  `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createby` varchar(20) NOT NULL DEFAULT '',
  `editby` varchar(20) NOT NULL DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `viewby` varchar(50) NOT NULL DEFAULT '',
  `viewdate` datetime DEFAULT NULL,
  PRIMARY KEY (`line`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("whnods", $qry);

    $this->coreFunctions->sbcaddcolumn("whnods", "docno", "varchar(500) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE  `whjobreq` (
  `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `docno` varchar(20) NOT NULL,
  `issued` datetime DEFAULT NULL,
  `expiry` datetime DEFAULT NULL,
  `dateid` datetime DEFAULT NULL,
  `oic1` varchar(20) NOT NULL,
  `oic2` varchar(20) NOT NULL,
  `rem` varchar(1000) NOT NULL,
  `status` varchar(15) NOT NULL,
  `whid` int(11) unsigned NOT NULL DEFAULT '0',
  `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createby` varchar(20) NOT NULL DEFAULT '',
  `editby` varchar(20) NOT NULL DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `viewby` varchar(50) NOT NULL DEFAULT '',
  `viewdate` datetime DEFAULT NULL,
  PRIMARY KEY (`line`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("whjobreq", $qry);

    $this->coreFunctions->sbcaddcolumn("whjobreq", "docno", "varchar(500) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE  `wh_log` LIKE `client_log` ";
    $this->coreFunctions->sbccreatetable("wh_log", $qry);

    $qry = "CREATE TABLE  `del_wh_log` LIKE `del_client_log` ";
    $this->coreFunctions->sbccreatetable("del_wh_log", $qry);

    //FRED 06.08.2021 - alter for set default values
    $this->coreFunctions->sbcaddcolumn("item", "asset", "VARCHAR(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("item", "liability", "VARCHAR(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("item", "revenue", "VARCHAR(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("item", "expense", "VARCHAR(45) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("client_log", "field", "VARCHAR(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("client_log", "oldversion", "VARCHAR(900) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("client_log", "userid", "VARCHAR(45) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE `viberid` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `id` varchar(50) NOT NULL,
    `name` varchar(200) NOT NULL,
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("viberid", $qry);

    $qry = "CREATE TABLE `rvoyage` (
    `trno` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `docno` char(20) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `whid` int(11) unsigned NOT NULL DEFAULT '0',
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
    `notes` varchar(200) NOT NULL DEFAULT '',
    `port` varchar(25) NOT NULL DEFAULT '',
    `arrival` varchar(25) NOT NULL DEFAULT '',
    `departure` varchar(25) NOT NULL DEFAULT '',
    `enginerpm` varchar(25) NOT NULL DEFAULT '',
    `timeatsea` varchar(25) NOT NULL DEFAULT '',
    `avespeed` varchar(25) NOT NULL DEFAULT '',
    `enginefueloil` varchar(25) NOT NULL DEFAULT '',
    `cylinderoil` varchar(25) NOT NULL DEFAULT '',
    `enginelubeoil` varchar(25) NOT NULL DEFAULT '',
    `hiexhaust` varchar(25) NOT NULL DEFAULT '',
    `loexhaust` varchar(25) NOT NULL DEFAULT '',
    `exhaustgas` varchar(25) NOT NULL DEFAULT '',
    `hicoolwater` varchar(25) NOT NULL DEFAULT '',
    `locoolwater` varchar(25) NOT NULL DEFAULT '',
    `lopress` varchar(25) NOT NULL DEFAULT '',
    `fwpress` varchar(25) NOT NULL DEFAULT '',
    `airpress` varchar(25) NOT NULL DEFAULT '',
    `airinletpress` varchar(25) NOT NULL DEFAULT '',
    `coolerin` varchar(25) NOT NULL DEFAULT '',
    `coolerout` varchar(25) NOT NULL DEFAULT '',
    `coolerfwin` varchar(25) NOT NULL DEFAULT '',
    `coolerfwout` varchar(25) NOT NULL DEFAULT '',
    `seawatertemp` varchar(25) NOT NULL DEFAULT '',
    `engroomtemp` varchar(25) NOT NULL DEFAULT '',

    `begcash` decimal(18,2) NOT NULL DEFAULT '0.00',
    `addcash` decimal(18,2) NOT NULL DEFAULT '0.00',

    `usagefeeamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `usagefee` VARCHAR(25) NOT NULL DEFAULT '',
    `mooringamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `mooring` VARCHAR(25) NOT NULL DEFAULT '',
    `coastguardclearanceamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `coastguardclearance` VARCHAR(25) NOT NULL DEFAULT '',
    `pilotageamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `pilotage` VARCHAR(25) NOT NULL DEFAULT '',
    `lifebouyamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `lifebouy` VARCHAR(25) NOT NULL DEFAULT '',
    `bunkeringamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `bunkering` VARCHAR(25) NOT NULL DEFAULT '',
    `sopamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `sop` VARCHAR(25) NOT NULL DEFAULT '',
    `othersamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `others` VARCHAR(25) NOT NULL DEFAULT '',

    `purchaseamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `purchase` VARCHAR(25) NOT NULL DEFAULT '',
    `crewsubsistenceamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `crewsubsistence` VARCHAR(25) NOT NULL DEFAULT '',
    `waterexpamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `waterexp` VARCHAR(25) NOT NULL DEFAULT '',
    `localtranspoamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `localtranspo` VARCHAR(25) NOT NULL DEFAULT '',
    `others2amt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `others2` VARCHAR(25) NOT NULL DEFAULT '',
    `reqcash` decimal(18,2) NOT NULL DEFAULT '0.00',

    `lockuser` varchar(25) NOT NULL DEFAULT '',
    `lockdate` DATETIME DEFAULT NULL,
    `createdate` DATETIME DEFAULT NULL,
    `createby` varchar(25) NOT NULL DEFAULT '',
    `viewdate` DATETIME DEFAULT NULL,
    `viewby` varchar(25) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rvoyage", $qry);

    $this->coreFunctions->sbcaddcolumn("rvoyage", "editby", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("rvoyage", "editdate", "DATETIME DEFAULT NULL", 1);

    $qry = "CREATE TABLE `hrvoyage` (
    `trno` bigint(20) unsigned NOT NULL,
    `docno` char(20) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `whid` int(11) unsigned NOT NULL DEFAULT '0',
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
    `notes` varchar(200) NOT NULL DEFAULT '',

    `port` varchar(25) NOT NULL DEFAULT '',
    `arrival` varchar(25) NOT NULL DEFAULT '',
    `departure` varchar(25) NOT NULL DEFAULT '',
    `enginerpm` varchar(25) NOT NULL DEFAULT '',
    `timeatsea` varchar(25) NOT NULL DEFAULT '',
    `avespeed` varchar(25) NOT NULL DEFAULT '',
    `enginefueloil` varchar(25) NOT NULL DEFAULT '',
    `cylinderoil` varchar(25) NOT NULL DEFAULT '',
    `enginelubeoil` varchar(25) NOT NULL DEFAULT '',
    `hiexhaust` varchar(25) NOT NULL DEFAULT '',
    `loexhaust` varchar(25) NOT NULL DEFAULT '',
    `exhaustgas` varchar(25) NOT NULL DEFAULT '',
    `hicoolwater` varchar(25) NOT NULL DEFAULT '',
    `locoolwater` varchar(25) NOT NULL DEFAULT '',
    `lopress` varchar(25) NOT NULL DEFAULT '',
    `fwpress` varchar(25) NOT NULL DEFAULT '',
    `airpress` varchar(25) NOT NULL DEFAULT '',
    `airinletpress` varchar(25) NOT NULL DEFAULT '',
    `coolerin` varchar(25) NOT NULL DEFAULT '',
    `coolerout` varchar(25) NOT NULL DEFAULT '',
    `coolerfwin` varchar(25) NOT NULL DEFAULT '',
    `coolerfwout` varchar(25) NOT NULL DEFAULT '',
    `seawatertemp` varchar(25) NOT NULL DEFAULT '',
    `engroomtemp` varchar(25) NOT NULL DEFAULT '',

    `begcash` decimal(18,2) NOT NULL DEFAULT '0.00',
    `addcash` decimal(18,2) NOT NULL DEFAULT '0.00',

    `usagefeeamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `usagefee` VARCHAR(25) NOT NULL DEFAULT '',
    `mooringamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `mooring` VARCHAR(25) NOT NULL DEFAULT '',
    `coastguardclearanceamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `coastguardclearance` VARCHAR(25) NOT NULL DEFAULT '',
    `pilotageamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `pilotage` VARCHAR(25) NOT NULL DEFAULT '',
    `lifebouyamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `lifebouy` VARCHAR(25) NOT NULL DEFAULT '',
    `bunkeringamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `bunkering` VARCHAR(25) NOT NULL DEFAULT '',
    `sopamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `sop` VARCHAR(25) NOT NULL DEFAULT '',
    `othersamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `others` VARCHAR(25) NOT NULL DEFAULT '',

    `purchaseamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `purchase` VARCHAR(25) NOT NULL DEFAULT '',
    `crewsubsistenceamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `crewsubsistence` VARCHAR(25) NOT NULL DEFAULT '',
    `waterexpamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `waterexp` VARCHAR(25) NOT NULL DEFAULT '',
    `localtranspoamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `localtranspo` VARCHAR(25) NOT NULL DEFAULT '',
    `others2amt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `others2` VARCHAR(25) NOT NULL DEFAULT '',
    `reqcash` decimal(18,2) NOT NULL DEFAULT '0.00',

    `lockuser` varchar(25) NOT NULL DEFAULT '',
    `lockdate` DATETIME DEFAULT NULL,
    `createdate` DATETIME DEFAULT NULL,
    `createby` varchar(25) NOT NULL DEFAULT '',
    `viewdate` DATETIME DEFAULT NULL,
    `viewby` varchar(25) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrvoyage", $qry);

    $this->coreFunctions->sbcaddcolumn("hrvoyage", "editby", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hrvoyage", "editdate", "DATETIME DEFAULT NULL", 1);

    //JAC 06092021
    $this->coreFunctions->sbcaddcolumn("lahead", "projectto", "int NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "projectto", "int NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("lahead", "subprojectto", "int NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "subprojectto", "int NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("cntnum", "receivedby", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cntnum", "receiveddate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("stages", "mi", "decimal(19,6) NOT NULL DEFAULT '0'", 0);

    //FRED 06.16.2021
    $this->coreFunctions->sbcaddcolumn("lahead", "partreqtypeid", "int NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "partreqtypeid", "int NOT NULL DEFAULT '0'");

    //JAC 06242021
    $this->coreFunctions->sbcaddcolumn("stages", "isbilled", "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("item", "isfa", "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("item", "loa", "integer(10) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("item", "warranty", "DATETIME DEFAULT NULL");


    // JAD (chat) 06-24-2021
    $qry = "CREATE TABLE `groupchat` (
    `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `roomname` VARCHAR(45) NOT NULL,
    `username` VARCHAR(45) NOT NULL,
    `userid` VARCHAR(45) NOT NULL,
    `msg` MEDIUMTEXT NOT NULL,
    `dateid` DATETIME NOT NULL,
    PRIMARY KEY (`line`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("groupchat", $qry);

    $qry = "CREATE TABLE `privatechat` (
    `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `from` VARCHAR(45) NOT NULL,
    `to` VARCHAR(45) NOT NULL,
    `usernamefrom` VARCHAR(45) NOT NULL,
    `msg` MEDIUMTEXT NOT NULL,
    `dateid` DATETIME NOT NULL,
    PRIMARY KEY (`line`)
  )
  ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("privatechat", $qry);
    $this->coreFunctions->sbcaddcolumn("privatechat", "isSeen", "tinyint(1) NOT NULL DEFAULT '0'");

    //JAC 06262021
    $qry = "CREATE TABLE `fasched` (
  `rrtrno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `clientid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `itemid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `rrline` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `dateid` DATETIME DEFAULT NULL,
  `amt` decimal(18,2) NOT NULL DEFAULT 0,
  `jvtrno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `createby` VARCHAR(45) NOT NULL DEFAULT '',
  `createdate` DATETIME DEFAULT NULL,
  `postedby` VARCHAR(45) NOT NULL DEFAULT '',
  `posteddate` DATETIME DEFAULT NULL,
  `projectid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `subproject` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  `stageid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`line`, `rrtrno`, `itemid`, `rrline`)
)
ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("fasched", $qry);

    $this->coreFunctions->sbcaddcolumn("ladetail", "fatrno", "INTEGER UNSIGNED NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("gldetail", "fatrno", "INTEGER UNSIGNED NOT NULL DEFAULT 0");


    $qry = "CREATE TABLE  `brhead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `address` varchar(150) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `start` datetime DEFAULT NULL,
    `end` datetime DEFAULT NULL,
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
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
    `subproject` varchar(45) NOT NULL DEFAULT '',
    `projectid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_2head` (`docno`,`projectid`,`dateid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("brhead", $qry);

    $qry = "CREATE TABLE  `hbrhead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `address` varchar(150) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `start` datetime DEFAULT NULL,
    `end` datetime DEFAULT NULL,
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
    `isapproved` tinyint(4) NOT NULL DEFAULT '0',
    `lockuser` varchar(50) NOT NULL DEFAULT '',
    `lockdate` datetime DEFAULT NULL,
    `openby` varchar(50) NOT NULL DEFAULT '',
    `users` varchar(50) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
    `createby` varchar(50) NOT NULL DEFAULT '',
    `editby` varchar(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `subproject` varchar(45) NOT NULL DEFAULT '',
    `projectid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_2head` (`docno`,`projectid`,`dateid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hbrhead", $qry);

    $qry = "CREATE TABLE `brstock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `particulars` varchar(250) NOT NULL DEFAULT '',
    `rem` varchar(250) NOT NULL DEFAULT '',
    `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `amount` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `uom` varchar(15) NOT NULL DEFAULT '',
    `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `qa` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `void` tinyint(1) NOT NULL DEFAULT '0',
    `refx` bigint(20) NOT NULL DEFAULT '0',
    `linex` int(11) NOT NULL DEFAULT '0',
    `ref` varchar(50) NOT NULL DEFAULT '',
    `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`,`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("brstock", $qry);

    $qry = "CREATE TABLE `hbrstock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `particulars` varchar(250) NOT NULL DEFAULT '',
    `rem` varchar(250) NOT NULL DEFAULT '',
    `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `amount` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `uom` varchar(15) NOT NULL DEFAULT '',
    `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `qa` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `void` tinyint(1) NOT NULL DEFAULT '0',
    `refx` bigint(20) NOT NULL DEFAULT '0',
    `linex` int(11) NOT NULL DEFAULT '0',
    `ref` varchar(50) NOT NULL DEFAULT '',
    `encodeddate` DATETIME DEFAULT NULL,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`,`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hbrstock", $qry);

    //JAC 07012021
    $this->coreFunctions->sbcaddcolumn("brstock", "approvedby", "varchar(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hbrstock", "approvedby", "varchar(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("brstock", "approveddate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hbrstock", "approveddate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("brhead", "bltrno", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hbrhead", "bltrno", "int(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE  `blhead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `dateid` datetime DEFAULT NULL,
    `address` varchar(150) NOT NULL DEFAULT '',
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
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
    `subproject` varchar(45) NOT NULL DEFAULT '',
    `projectid` int(11) NOT NULL DEFAULT '0',
    `brtrno` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_2head` (`docno`,`projectid`,`dateid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("blhead", $qry);

    $qry = "CREATE TABLE  `hblhead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `dateid` datetime DEFAULT NULL,
    `address` varchar(150) NOT NULL DEFAULT '',
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
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
    `subproject` varchar(45) NOT NULL DEFAULT '',
    `projectid` int(11) NOT NULL DEFAULT '0',
    `brtrno` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_2head` (`docno`,`projectid`,`dateid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hblhead", $qry);

    $qry = "CREATE TABLE  `blstock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `particulars` varchar(250) NOT NULL DEFAULT '',
    `rem` varchar(250) NOT NULL DEFAULT '',
    `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `uom` varchar(15) NOT NULL DEFAULT '',
    `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `qa` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `void` tinyint(1) NOT NULL DEFAULT '0',
    `refx` bigint(20) NOT NULL DEFAULT '0',
    `linex` int(11) NOT NULL DEFAULT '0',
    `ref` varchar(50) NOT NULL DEFAULT '' ,
    `dateid` datetime default NULL,
    `ordate` datetime default NULL,
    `location` varchar(150) not null default '',
    `supplier` varchar(150) not null default '',
    `address` varchar(150) not null default '',
    `tin` varchar(150) not null default '',
    `isvat` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
    `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`,`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("blstock", $qry);

    $qry = "CREATE TABLE  `hblstock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `particulars` varchar(250) NOT NULL DEFAULT '',
    `rem` varchar(250) NOT NULL DEFAULT '',
    `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `uom` varchar(15) NOT NULL DEFAULT '',
    `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `qa` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `void` tinyint(1) NOT NULL DEFAULT '0',
    `refx` bigint(20) NOT NULL DEFAULT '0',
    `linex` int(11) NOT NULL DEFAULT '0',
    `ref` varchar(50) NOT NULL DEFAULT '' ,
    `dateid` datetime default NULL,
    `ordate` datetime default NULL,
    `location` varchar(150) not null default '',
    `supplier` varchar(150) not null default '',
    `address` varchar(150) not null default '',
    `tin` varchar(150) not null default '',
    `isvat` tinyint(1) UNSIGNED NOT NULL DEFAULT '0',
    `encodeddate` DATETIME DEFAULT NULL,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`,`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hblstock", $qry);

    //FMM 07.02.2021
    $this->coreFunctions->sbcaddcolumn("mrhead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hmrhead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("mrstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("mrstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("mrstock", "disc", "VARCHAR(40) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE  `wchead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) NOT NULL DEFAULT '',
    `address` varchar(150) NOT NULL DEFAULT '',
    `tel` varchar(50) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
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
    `stageid` int(11) NOT NULL DEFAULT '0',
    `printtime` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    KEY `Index_2head` (`docno`,`client`,`dateid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("wchead", $qry);

    $qry = "CREATE TABLE  `hwchead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) NOT NULL DEFAULT '',
    `address` varchar(150) NOT NULL DEFAULT '',
    `tel` varchar(50) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
    `lockuser` varchar(50) NOT NULL DEFAULT '',
    `lockdate` datetime DEFAULT NULL,
    `openby` varchar(50) NOT NULL DEFAULT '',
    `users` varchar(50) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
    `createby` varchar(50) NOT NULL DEFAULT '',
    `editby` varchar(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `projectid` int(11) NOT NULL DEFAULT '0',
    `subproject` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    `printtime` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    KEY `Index_2head` (`docno`,`client`,`dateid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hwchead", $qry);

    $qry = "CREATE TABLE  `wcstock` (
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
    `itemid` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`),
    KEY `Index_2barcode` (`uom`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("wcstock", $qry);

    $qry = "CREATE TABLE  `hwcstock` (
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
    `encodeddate` datetime default null,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    `itemid` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`),
    KEY `Index_2barcode` (`uom`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hwcstock", $qry);
    $this->coreFunctions->sbcaddcolumn("wchead", "pbtrno", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hwchead", "pbtrno", "int(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("hmrstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hmrstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hmrstock", "disc", "VARCHAR(40) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcdropcolumn("mrstock", "barcode");
    $this->coreFunctions->sbcdropcolumn("mrstock", "itemname");
    $this->coreFunctions->sbcdropcolumn("mrstock", "wh");

    $this->coreFunctions->sbcdropcolumn("hmrstock", "barcode");
    $this->coreFunctions->sbcdropcolumn("hmrstock", "itemname");
    $this->coreFunctions->sbcdropcolumn("hmrstock", "wh");

    //JAC 07052021
    $this->coreFunctions->sbcaddcolumn("client", "iscontractor", "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "name", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("brstock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hbrstock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("blstock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hblstock", "rem", "varchar(500) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `sku` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `itemid` int(11) NOT NULL DEFAULT '0',
    `clientid` int(11) NOT NULL DEFAULT '0',
    `sku` varchar(40) NOT NULL DEFAULT '',
    `disc` varchar(40) NOT NULL DEFAULT '',
    `amt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
    PRIMARY KEY (`line`,`itemid`,`clientid`,`sku`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("sku", $qry);

    //FMM 07.07.2021
    $qry = "CREATE TABLE  `pahead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `dateid` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
    `whid` int(11) NOT NULL DEFAULT '0',
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
    `lockuser` varchar(50) NOT NULL DEFAULT '',
    `lockdate` datetime DEFAULT NULL,
    `openby` varchar(50) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
    `createby` varchar(50) NOT NULL DEFAULT '',
    `editby` varchar(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `isall` tinyint(2) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_trno` (`trno`),
    KEY `Index_docno` (`docno`),
    KEY `Index_whid` (`whid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pahead", $qry);

    $qry = "CREATE TABLE  `hpahead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `dateid` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
    `whid` int(11) NOT NULL DEFAULT '0',
    `rem` varchar(500) NOT NULL DEFAULT '',
    `voiddate` datetime DEFAULT NULL,
    `yourref` varchar(25) NOT NULL DEFAULT '',
    `ourref` varchar(25) NOT NULL DEFAULT '',
    `lockuser` varchar(50) NOT NULL DEFAULT '',
    `lockdate` datetime DEFAULT NULL,
    `openby` varchar(50) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
    `createby` varchar(50) NOT NULL DEFAULT '',
    `editby` varchar(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `isall` tinyint(2) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_trno` (`trno`),
    KEY `Index_docno` (`docno`),
    KEY `Index_whid` (`whid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hpahead", $qry);

    $qry = "CREATE TABLE  `pastock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `itemid` int(11) NOT NULL DEFAULT '0',
    `uom` varchar(15) NOT NULL DEFAULT '',
    `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `iss` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `disc` varchar(40) NOT NULL DEFAULT '',
    `rem` varchar(40) NOT NULL DEFAULT '',
    `void` tinyint(2) NOT NULL DEFAULT '0',
    `encodeddate` datetime default null,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`, `line`),
    KEY `Index_trno` (`trno`),
    KEY `Index_itemid` (`itemid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pastock", $qry);

    $qry = "CREATE TABLE  `hpastock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `itemid` int(11) NOT NULL DEFAULT '0',
    `uom` varchar(15) NOT NULL DEFAULT '',
    `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `iss` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `disc` varchar(40) NOT NULL DEFAULT '',
    `rem` varchar(40) NOT NULL DEFAULT '',
    `void` tinyint(2) NOT NULL DEFAULT '0',
    `encodeddate` datetime default null,
    `encodedby` varchar(20) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(20) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`, `line`),
    KEY `Index_trno` (`trno`),
    KEY `Index_itemid` (`itemid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hpastock", $qry);

    $qry = "CREATE TABLE  `cnhead` LIKE `sohead` ";
    $this->coreFunctions->sbccreatetable("cnhead", $qry);

    $qry = "CREATE TABLE  `hcnhead` LIKE `hsohead` ";
    $this->coreFunctions->sbccreatetable("hcnhead", $qry);

    $qry = "CREATE TABLE  `cnstock` LIKE `sostock` ";
    $this->coreFunctions->sbccreatetable("cnstock", $qry);

    $qry = "CREATE TABLE  `hcnstock` LIKE `hsostock` ";
    $this->coreFunctions->sbccreatetable("hcnstock", $qry);

    // JIKS 07.10.2020 - add isconsign tagging
    $this->coreFunctions->sbcaddcolumn("client", "isconsign", "int(1) unsigned DEFAULT '0'", 0);

    //JAC 07122021
    $qry = "CREATE TABLE `budget` (
      `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
      `year` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `projectid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `acnoid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `amt1` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt2` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt3` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt4` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt5` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt6` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt7` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt8` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt9` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt10` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt11` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `amt12` DECIMAL(18,2) NOT NULL DEFAULT 0,
      PRIMARY KEY (`year`, `projectid`,`acnoid`),
      INDEX `Index_2`(`line`)
    )
    ENGINE = MyISAM;";

    $this->coreFunctions->sbccreatetable("budget", $qry);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "isho", "int(1) unsigned DEFAULT '0'", 0);

    // GLEN 07.12.2021
    $this->coreFunctions->sbcaddcolumn("client", "plateno", "VARCHAR(15) NOT NULL DEFAULT ''", 0);


    // JIKS [07.12.2021] item category masterfile for MITSUKOSHI warehouse w/ subcategory
    $qry = "CREATE TABLE `itemcategory` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL DEFAULT '',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("itemcategory", $qry);

    $qry = "CREATE TABLE `itemsubcategory` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `categoryid` int(11) unsigned NOT NULL DEFAULT 0,
    `name` varchar(50) NOT NULL DEFAULT '',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("itemsubcategory", $qry);

    $qry = "CREATE TABLE `masterfile_log` (
    `trno` BIGINT(20) NOT NULL DEFAULT '0',
    `doc` VARCHAR(50) NOT NULL DEFAULT '',
    `task` varchar(500) NOT NULL DEFAULT '',
    `dateid` DATETIME DEFAULT NULL,
    `user` varchar(50) NOT NULL DEFAULT ''
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("masterfile_log", $qry);

    $this->coreFunctions->sbcaddcolumn("masterfile_log", "task", "VARCHAR(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("masterfile_log", "doc", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("masterfile_log", "editby", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("masterfile_log", "editdate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("masterfile_log", "trno2", "INT(11) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `payroll_log` (
    `trno` BIGINT(20) NOT NULL DEFAULT '0',
    `doc` VARCHAR(50) NOT NULL DEFAULT '',
    `task` varchar(500) NOT NULL DEFAULT '',
    `dateid` DATETIME DEFAULT NULL,
    `user` varchar(50) NOT NULL DEFAULT ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("payroll_log", $qry);

    $this->coreFunctions->sbcaddcolumn("payroll_log", "editby", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("payroll_log", "editdate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("brstock", "status", "integer DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("hbrstock", "status", "integer DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("blhead", "bal", "decimal(18,2) DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("hblhead", "bal", "decimal(18,2) DEFAULT 0", 0);

    $qry = "CREATE TABLE `del_masterfile_log` (
    `trno` BIGINT(20) NOT NULL DEFAULT '0',
    `doc` VARCHAR(50) NOT NULL DEFAULT '',
    `task` varchar(500) NOT NULL DEFAULT '',
    `dateid` DATETIME DEFAULT NULL,
    `user` varchar(50) NOT NULL DEFAULT ''
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("del_masterfile_log", $qry);
    $this->coreFunctions->sbcaddcolumn("del_masterfile_log", "trno2", "INT(11) NOT NULL DEFAULT '0'", 0);

    //jac 07102021
    $this->coreFunctions->sbcaddcolumn("prstock", "status", "integer DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("hprstock", "status", "integer DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("stages", "wac", "decimal(18,2) DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "brtrno", "integer DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "brtrno", "integer DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("brhead", "cvtrno", "integer DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("hbrhead", "cvtrno", "integer DEFAULT 0", 0);

    $qry = "CREATE TABLE `rolesetup` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `name` varchar(20) NOT NULL DEFAULT '',
    `divid` int(11) NOT NULL DEFAULT '0',
    `deptid` int(11) NOT NULL DEFAULT '0',
    `sectionid` int(11) NOT NULL DEFAULT '0',
    `supervisorid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rolesetup", $qry);

    $qry = "CREATE TABLE  `lead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) NOT NULL DEFAULT '',
    `address` varchar(150) NOT NULL DEFAULT '',
    `tel` varchar(50) NOT NULL DEFAULT '',
    `contact` varchar(50) NOT NULL DEFAULT '',
    `dateid` DATETIME DEFAULT NULL,
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
    PRIMARY KEY (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("lead", $qry);

    $qry = "CREATE TABLE  `hlead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) NOT NULL DEFAULT '',
    `address` varchar(150) NOT NULL DEFAULT '',
    `tel` varchar(50) NOT NULL DEFAULT '',
    `contact` varchar(50) NOT NULL DEFAULT '',
    `dateid` DATETIME DEFAULT NULL,
    `lockuser` varchar(50) NOT NULL DEFAULT '',
    `lockdate` datetime DEFAULT NULL,
    `openby` varchar(50) NOT NULL DEFAULT '',
    `users` varchar(50) NOT NULL DEFAULT '',
    `createdate` DATETIME DEFAULT NULL,
    `createby` varchar(50) NOT NULL DEFAULT '',
    `editby` varchar(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hlead", $qry);

    //JAC
    $this->coreFunctions->sbcaddcolumn("item", "islabor", "tinyint(1) NOT NULL DEFAULT '0'");

    // JIKS - Opportunity module
    $qry = "CREATE TABLE  `ophead` LIKE `sohead` ";
    $this->coreFunctions->sbccreatetable("ophead", $qry);

    $qry = "CREATE TABLE  `hophead` LIKE `hsohead` ";
    $this->coreFunctions->sbccreatetable("hophead", $qry);

    $qry = "CREATE TABLE  `opstock` LIKE `sostock` ";
    $this->coreFunctions->sbccreatetable("opstock", $qry);

    $qry = "CREATE TABLE  `hopstock` LIKE `hsostock` ";
    $this->coreFunctions->sbccreatetable("hopstock", $qry);

    // JIKS [08.07.2021] - CHECK SERIES SETUP 
    $qry = "CREATE TABLE `checksetup` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `acnoid` int(11) NOT NULL DEFAULT '0',
    `start` int(11) NOT NULL DEFAULT '0',
    `end` int(11) NOT NULL DEFAULT '0',
    `current` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("checksetup", $qry);

    // JIKS [08.09.2021] - Exchange Rate Setup
    $qry = "CREATE TABLE `exchangerate` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `curfrom` varchar(5) NOT NULL DEFAULT '',
    `curto` varchar(5) NOT NULL DEFAULT '',
    `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("exchangerate", $qry);

    // JIKS [08.09.2021] - Billing/Shipping Setup
    $qry = "CREATE TABLE `billingaddr` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` int(11) NOT NULL DEFAULT 0,
    `addr` text COLLATE utf8_unicode_ci NOT NULL,
    `isbilling` tinyint(1) NOT NULL DEFAULT 0,
    `isshipping` tinyint(1) NOT NULL DEFAULT 0,
    `isinactive` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
    $this->coreFunctions->sbccreatetable("billingaddr", $qry);


    //jac 08092021
    $this->coreFunctions->sbcaddcolumn("stages", "jr", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sostock", "oqty", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "oqty", "decimal(19,6) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `projectvar` (
    `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `projectid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `variation` VARCHAR(50) NOT NULL DEFAULT '',
    `amount` DECIMAL(18,6) NOT NULL DEFAULT 0,
    PRIMARY KEY (`line`)
    )ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("projectvar", $qry);
    $this->coreFunctions->sbcaddcolumn("projectvar", "editdate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("projectvar", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    //fmm 8.10.2021      
    $this->coreFunctions->sbcaddcolumn("cntnum", "ardate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("item_log", "oldversion", "VARCHAR(900) NOT NULL DEFAULT ''", 1);


    // JIKS [08.10.2021] - for Role Tab 
    $qry = "CREATE TABLE `emprole` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `empid` int(11) NOT NULL DEFAULT 0,
    `roleid` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("emprole", $qry);

    //fmm 8.10.2021  
    $this->coreFunctions->sbcaddcolumn("client", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("lahead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("lahead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "billid", "INT(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `stockinfo` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `line` int(11) NOT NULL DEFAULT 0,
    `rem` text DEFAULT NULL,
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("stockinfo", $qry);

    $qry = "CREATE TABLE `hstockinfo` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `line` int(11) NOT NULL DEFAULT 0,
    `rem` text DEFAULT NULL,
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hstockinfo", $qry);

    $qry = "CREATE TABLE `stockinfotrans` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `line` int(11) NOT NULL DEFAULT 0,
    `rem` text DEFAULT NULL,
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("stockinfotrans", $qry);

    $qry = "CREATE TABLE `hstockinfotrans` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `line` int(11) NOT NULL DEFAULT 0,
    `rem` text DEFAULT NULL,
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hstockinfotrans", $qry);
    //end of fmm 8.10.2021 

    //JAC 20210816
    $qry = "CREATE TABLE  `voiddetail` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL DEFAULT '0',
  `client` varchar(20) NOT NULL DEFAULT '',
  `rem` varchar(250) NOT NULL DEFAULT '',
  `db` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `cr` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `fdb` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `fcr` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `forex` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `agent` varchar(20) NOT NULL DEFAULT '',
  `postdate` date DEFAULT NULL,
  `ref` varchar(50) NOT NULL DEFAULT '',
  `checkno` varchar(200) NOT NULL DEFAULT '',
  `duedate` datetime DEFAULT NULL,
  `refx` bigint(20) NOT NULL DEFAULT '0',
  `linex` int(11) NOT NULL DEFAULT '0',
  `clearday` datetime DEFAULT NULL,
  `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `encodedby` varchar(20) DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `editby` varchar(20) DEFAULT '',
  `pdcline` int(10) unsigned NOT NULL DEFAULT '0',
  `project` varchar(45) NOT NULL DEFAULT '',
  `isewt` int(2) NOT NULL DEFAULT '0',
  `isvat` int(2) NOT NULL DEFAULT '0',
  `ewtcode` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` varchar(45) NOT NULL DEFAULT '',
  `damt` decimal(18,2) NOT NULL DEFAULT '0.00',
  `cur` varchar(5) NOT NULL DEFAULT '',
  `isvewt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `acnoid` int(11) NOT NULL DEFAULT '0',
  `projectid` int(11) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  `pcvtrno` int(11) NOT NULL DEFAULT '0',
  `fatrno` int(10) unsigned NOT NULL DEFAULT '0',
  `void` tinyint(1) unsigned NOT NULL DEFAULT '0',
  KEY `Index_trno` (`trno`,`line`) USING BTREE,
  KEY `Index_refx` (`refx`,`linex`),
  KEY `Index_postdate` (`postdate`),
  KEY `Index_client` (`client`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("voiddetail", $qry);

    $qry = "CREATE TABLE  `hvoiddetail` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(11) NOT NULL DEFAULT '0',
  `acnoid` int(11) NOT NULL DEFAULT '0',
  `clientid` int(11) NOT NULL DEFAULT '0',
  `rem` varchar(250) NOT NULL DEFAULT '',
  `db` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `cr` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `fdb` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `fcr` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `forex` decimal(19,6) NOT NULL DEFAULT '0.000000',
  `agentid` int(11) NOT NULL DEFAULT '0',
  `postdate` datetime DEFAULT NULL,
  `ref` varchar(50) NOT NULL DEFAULT '',
  `checkno` varchar(200) NOT NULL DEFAULT '',
  `duedate` datetime DEFAULT NULL,
  `refx` bigint(20) NOT NULL DEFAULT '0',
  `linex` int(11) NOT NULL DEFAULT '0',
  `clearday` datetime DEFAULT NULL,
  `encodeddate` datetime DEFAULT NULL,
  `encodedby` varchar(20) NOT NULL DEFAULT '',
  `editdate` datetime DEFAULT NULL,
  `editby` varchar(20) NOT NULL DEFAULT '',
  `pdcline` int(10) unsigned NOT NULL DEFAULT '0',
  `project` varchar(45) NOT NULL DEFAULT '',
  `isewt` int(2) NOT NULL DEFAULT '0',
  `isvat` int(2) NOT NULL DEFAULT '0',
  `ewtcode` varchar(45) NOT NULL DEFAULT '',
  `ewtrate` varchar(45) NOT NULL DEFAULT '',
  `damt` decimal(18,2) NOT NULL DEFAULT '0.00',
  `cur` varchar(5) NOT NULL DEFAULT '',
  `isvewt` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `projectid` int(11) NOT NULL DEFAULT '0',
  `subproject` int(11) NOT NULL DEFAULT '0',
  `stageid` int(11) NOT NULL DEFAULT '0',
  `pcvtrno` int(11) NOT NULL DEFAULT '0',
  `fatrno` int(10) unsigned NOT NULL DEFAULT '0',
  `void` tinyint(1) unsigned NOT NULL DEFAULT '0',
  KEY `Index_trno` (`trno`,`line`) USING BTREE,
  KEY `Index_refx` (`refx`,`linex`),
  KEY `Index_acnoid` (`acnoid`),
  KEY `Index_postdate` (`postdate`),
  KEY `Index_clientid` (`clientid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hvoiddetail", $qry);

    $this->coreFunctions->sbcaddcolumn("ladetail", "void", "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("gldetail", "void", "TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'");



    $qry = "CREATE TABLE  `qshead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) DEFAULT NULL,
    `address` varchar(150) DEFAULT NULL,
    `shipto` varchar(100) DEFAULT NULL,
    `tel` varchar(50) DEFAULT NULL,
    `dateid` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
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
    `projectid` int(10) unsigned NOT NULL DEFAULT '0',
    `vattype` varchar(25) NOT NULL DEFAULT '',
    `subproject` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_trno` (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("qshead", $qry);

    $qry = "CREATE TABLE  `qsstock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `uom` varchar(15) DEFAULT '',
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
    `expiry` varchar(45) DEFAULT '',
    `fstatus` varchar(45) NOT NULL DEFAULT '',
    `wh_currentqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `mrsqa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `itemid` int(11) NOT NULL DEFAULT '0',
    `whid` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`),
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`),
    KEY `Index_itemid` (`itemid`),
    KEY `Index_whid` (`whid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("qsstock", $qry);


    $qry = "CREATE TABLE  `hqshead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) DEFAULT NULL,
    `address` varchar(150) DEFAULT NULL,
    `shipto` varchar(150) DEFAULT NULL,
    `tel` varchar(50) DEFAULT NULL,
    `dateid` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
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
    `createdate` datetime DEFAULT NULL,
    `createby` varchar(50) NOT NULL DEFAULT '',
    `editby` varchar(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `projectid` int(10) unsigned NOT NULL DEFAULT '0',
    `vattype` varchar(25) NOT NULL DEFAULT '',
    `subproject` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    `sotrno` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    KEY `Index_trno` (`trno`),
    KEY `Index_sotrno` (`sotrno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hqshead", $qry);

    $qry = "CREATE TABLE  `hqsstock` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL,
    `uom` varchar(15) DEFAULT NULL,
    `disc` varchar(40) NOT NULL DEFAULT '',
    `rem` varchar(40) DEFAULT NULL,
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
    `fstatus` varchar(45) NOT NULL DEFAULT '',
    `wh_currentqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `mrsqa` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `kgs` decimal(19,6) NOT NULL DEFAULT '0.000000',
    `itemid` int(11) NOT NULL DEFAULT '0',
    `whid` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`),
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`),
    KEY `Index_itemid` (`itemid`),
    KEY `Index_whid` (`whid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hqsstock", $qry);

    $qry = "CREATE TABLE  `sqhead` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `doc` char(2) NOT NULL DEFAULT '',
        `docno` char(20) NOT NULL,
        `dateid` datetime DEFAULT NULL,
        `voiddate` datetime DEFAULT NULL,
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
        PRIMARY KEY (`trno`),
        KEY `Index_trno` (`trno`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("sqhead", $qry);


    $qry = "CREATE TABLE  `hsqhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `voiddate` datetime DEFAULT NULL,
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
      PRIMARY KEY (`trno`),
      KEY `Index_trno` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsqhead", $qry);

    $this->coreFunctions->sbcaddcolumn("hophead", "qtrno", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("qsstock", "ref", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "ref", "VARCHAR(20) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("qsstock", "refx", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qsstock", "linex", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("hqsstock", "refx", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "linex", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "sjqa", "DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "poqa", "DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "iscanvass", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("cdstock", "sorefx", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("cdstock", "solinex", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("hcdstock", "sorefx", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hcdstock", "solinex", "INT(11) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("item", "isdisplay", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("item", "isgc", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("item", "points", "DECIMAL(18,2) UNSIGNED NOT NULL DEFAULT '0.00'", 1);

    $this->coreFunctions->sbcaddcolumn("client", "isallitem", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "issynced", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "ismain", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");




    //JAC 08192021
    $qry = "CREATE TABLE `detailinfo` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `line` int(11) NOT NULL DEFAULT 0,
    `rem` text DEFAULT NULL,
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("detailinfo", $qry);
    $this->coreFunctions->sbcaddcolumn("detailinfo", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("detailinfo", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE `hdetailinfo` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `line` int(11) NOT NULL DEFAULT 0,
    `rem` text DEFAULT NULL,
    KEY `Index_trno` (`trno`),
    KEY `Index_line` (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hdetailinfo", $qry);
    $this->coreFunctions->sbcaddcolumn("hdetailinfo", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hdetailinfo", "editdate", "datetime DEFAULT NULL", 0);


    //fmm 08.24.2021
    $this->coreFunctions->sbcdropcolumn('cntnum', 'fromfrontend');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'is_sp');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'txno');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'screceivedate');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'screceivenotes');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'sctruck');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scshippingline');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scdestination');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'sccheckerdriver');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'screceivedby');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scpostdevnotes');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scdiscrepancytype');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scdiscrepancydetails');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scdiscrepancynotes');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scconfirmationdate');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scconfirmationnotes');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scsettleddate');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'scsettlednotes');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'sbill');
    $this->coreFunctions->sbcdropcolumn('cntnum', 'shandling');

    $this->coreFunctions->sbcdropcolumn('transnum', 'fromfrontend');
    $this->coreFunctions->sbcdropcolumn('transnum', 'fppayment');
    $this->coreFunctions->sbcdropcolumn('transnum', 'op_payref');
    $this->coreFunctions->sbcdropcolumn('transnum', 'op_ord');
    $this->coreFunctions->sbcdropcolumn('transnum', 'op_sourceip');
    $this->coreFunctions->sbcdropcolumn('transnum', 'op_paymethod');
    $this->coreFunctions->sbcdropcolumn('transnum', 'op_txtime');
    $this->coreFunctions->sbcdropcolumn('transnum', 'txdocno');


    $qry = "CREATE TABLE  `itemdlock` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `itemid` int(10) unsigned NOT NULL DEFAULT '0',
      `dlock` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("itemdlock", $qry);

    $qry = "CREATE TABLE  `head` (
        `docno` varchar(20) NOT NULL DEFAULT '',
        `postdate` datetime DEFAULT NULL,
        `station` varchar(30) NOT NULL DEFAULT '',
        `center` varchar(45) NOT NULL DEFAULT '',
        `fullp` decimal(19,6) NOT NULL DEFAULT '0.000000',
        `trno` int(11) unsigned NOT NULL DEFAULT '0',
        `seq` int(11) unsigned NOT NULL DEFAULT '0',
        `bref` varchar(5) NOT NULL DEFAULT '',
        `doc` char(5) NOT NULL DEFAULT '',
        `lockuser` varchar(50) NOT NULL DEFAULT '',
        `lockdate` datetime DEFAULT NULL,
        `openby` varchar(50) NOT NULL DEFAULT '',
        `voiddate` datetime DEFAULT NULL,
        `loss` varchar(150) NOT NULL DEFAULT '',
        `pos` tinyint(4) unsigned NOT NULL DEFAULT '0',
        `amt` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `cash` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `cheque` decimal(18,2) NOT NULL DEFAULT '0.00',
        `card` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `nvat` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `vatamt` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `vatex` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `cr` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `transtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `change` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `tendered` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `sramt` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `discamt` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `ordertype` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `gcount` int(10) unsigned NOT NULL DEFAULT '0',
        `lp` decimal(18,2) NOT NULL DEFAULT '0.00',
        `transtype` varchar(40) NOT NULL DEFAULT '',
        `dldate` datetime DEFAULT NULL,
        `uploaddate` datetime DEFAULT NULL,
        `clientid` int(11) unsigned NOT NULL DEFAULT '0',
        `clientname` varchar(255) NOT NULL DEFAULT '',
        `address` varchar(150) NOT NULL DEFAULT '',
        `shipto` varchar(150) NOT NULL DEFAULT '',
        `tel` varchar(50) NOT NULL DEFAULT '',
        `dateid` datetime DEFAULT NULL,
        `due` datetime DEFAULT NULL,
        `terms` varchar(50) NOT NULL DEFAULT '',
        `wh` varchar(50) NOT NULL DEFAULT '',
        `rem` varchar(1000) NOT NULL DEFAULT '',
        `cur` varchar(4) NOT NULL DEFAULT '',
        `forex` decimal(19,6) NOT NULL DEFAULT '0.000000',
        `branch` varchar(30) NOT NULL DEFAULT '',
        `contra` varchar(30) NOT NULL DEFAULT '',
        `ourref` varchar(1000) NOT NULL DEFAULT '',
        `agentid` int(11) unsigned NOT NULL DEFAULT '0',
        `yourref` varchar(1000) NOT NULL DEFAULT '',
        `waybill` varchar(50) NOT NULL DEFAULT '',
        `deldate` datetime DEFAULT NULL,
        `billdate` datetime DEFAULT NULL,
        `billway` varchar(200) NOT NULL DEFAULT '',
        `prepared` varchar(50) NOT NULL DEFAULT '',
        `approved` varchar(50) NOT NULL DEFAULT '',
        `down` decimal(19,6) NOT NULL DEFAULT '0.000000',
        `datefull` datetime DEFAULT NULL,
        `pmode` varchar(50) NOT NULL DEFAULT '',
        `acctno` varchar(1000) NOT NULL DEFAULT '',
        `approval` varchar(45) NOT NULL DEFAULT '',
        `batch` varchar(50) NOT NULL DEFAULT '',
        `stockcount` decimal(19,4) NOT NULL DEFAULT '0.0000',
        `isok` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `isok2` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `pointsrate` varchar(45) NOT NULL DEFAULT '',
        `client` varchar(15) NOT NULL DEFAULT '',
        `voucher` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `userid` int(11) unsigned NOT NULL DEFAULT '0',
        `pwdamt` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `empdisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `srcount` int(11) unsigned NOT NULL DEFAULT '0',
        `debit` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `eplus` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `smac` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `onlinedeals` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `vipdisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `oddisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `smacdisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
        `billnumber` varchar(250) NOT NULL DEFAULT '',
        `htable` varchar(45) NOT NULL DEFAULT '',
        `bankname` varchar(45) NOT NULL DEFAULT '',
        `empid` int(11) NOT NULL DEFAULT '0',
        `timein` datetime DEFAULT NULL,
        `printtime` datetime DEFAULT NULL,
        `cid` int(11) NOT NULL DEFAULT '0',
        `isreprinted` tinyint(1) unsigned NOT NULL DEFAULT '0',
        `receivedby` varchar(30) NOT NULL DEFAULT '',
        `deliveredby` varchar(30) NOT NULL DEFAULT '',
        `terminalid` varchar(200) NOT NULL DEFAULT '',
        `voucherno` varchar(1000) NOT NULL DEFAULT '',
        `checktype` varchar(1000) NOT NULL DEFAULT '',
        `postedby` varchar(50) NOT NULL DEFAULT '',
        `gcamt` decimal(18,2) NOT NULL DEFAULT '0.00',
        `itemcount` int(11) NOT NULL DEFAULT '0',
        `invdate` varchar(45) NOT NULL DEFAULT '',
        PRIMARY KEY (`docno`,`center`,`station`),
        KEY `Index_Trno` (`trno`),
        KEY `Index_Doc` (`doc`),
        KEY `Index_Bref` (`bref`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("head", $qry);

    $this->coreFunctions->sbcaddcolumn("head", "postdate", "datetime DEFAULT NULL", 1);

    $qry = "CREATE TABLE  `stock` (
          `trno` int(11) unsigned NOT NULL DEFAULT '0',
          `line` int(11) unsigned NOT NULL DEFAULT '0',
          `station` varchar(30) NOT NULL DEFAULT '',
          `linex` int(11) unsigned NOT NULL DEFAULT '0',
          `refx` int(11) unsigned NOT NULL DEFAULT '0',
          `itemid` int(11) unsigned NOT NULL DEFAULT '0',
          `bcode` varchar(50) NOT NULL DEFAULT '',
          `itemname` varchar(255) NOT NULL DEFAULT '',
          `uom` varchar(15) NOT NULL DEFAULT '',
          `wh` varchar(30) NOT NULL DEFAULT '',
          `disc` varchar(40) NOT NULL DEFAULT '',
          `rem` varchar(255) NOT NULL DEFAULT '',
          `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `qty` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `iss` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `qa` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `ref` varchar(20) NOT NULL DEFAULT '',
          `putrno` int(11) unsigned NOT NULL DEFAULT '0',
          `puline` int(11) unsigned NOT NULL DEFAULT '0',
          `void` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `commission` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `comm` varchar(50) NOT NULL DEFAULT '',
          `expiration` datetime DEFAULT NULL,
          `rtrno` int(11) unsigned NOT NULL DEFAULT '0',
          `rline` int(11) unsigned NOT NULL DEFAULT '0',
          `consummable` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `agentid` int(11) unsigned NOT NULL DEFAULT '0',
          `btn` varchar(50) NOT NULL DEFAULT '',
          `expi` varchar(20) NOT NULL DEFAULT '',
          `lot` varchar(20) NOT NULL DEFAULT '',
          `nvat` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `vatamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `vatex` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `sramt` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `discamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
          `serial` varchar(45) NOT NULL DEFAULT '',
          `isok` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `isok2` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `dateid` datetime DEFAULT NULL,
          `iscomponent` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `loc` varchar(45) NOT NULL DEFAULT '',
          `freight` varchar(45) NOT NULL DEFAULT '',
          `status` char(1) NOT NULL DEFAULT 'P',
          `start_time` time DEFAULT NULL,
          `end_time` time DEFAULT NULL,
          `pwdamt` decimal(18,2) NOT NULL DEFAULT '0.00',
          `isemployee` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `iscomp` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `screg` varchar(45) NOT NULL DEFAULT '',
          `scsenior` varchar(45) NOT NULL DEFAULT '',
          `isdiplomat` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `issenior2` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `userid` int(11) unsigned NOT NULL DEFAULT '0',
          `createdate` datetime DEFAULT NULL,
          `lessvat` decimal(18,2) NOT NULL DEFAULT '0.00',
          `vipdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
          `empdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
          `oddisc` decimal(18,2) NOT NULL DEFAULT '0.00',
          `smacdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
          `issm` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `htrno` int(11) unsigned NOT NULL DEFAULT '0',
          `hline` int(11) unsigned NOT NULL DEFAULT '0',
          `isoverride` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `namt` decimal(18,2) NOT NULL DEFAULT '0.00',
          `orno` varchar(45) NOT NULL DEFAULT '',
          `promodesc` varchar(255) NOT NULL DEFAULT '',
          `promoby` varchar(15) NOT NULL DEFAULT '',
          `gcno` varchar(25) NOT NULL DEFAULT '',
          `prodcycle` varchar(15) NOT NULL DEFAULT '',
          `rqtrno` int(10) unsigned NOT NULL DEFAULT '0',
          `rqline` int(10) unsigned NOT NULL DEFAULT '0',
          `markup` varchar(45) NOT NULL DEFAULT '',
          `isfree` tinyint(1) unsigned NOT NULL DEFAULT '0',
          `srp` decimal(18,6) NOT NULL DEFAULT '0.000000',
          PRIMARY KEY (`trno`,`line`,`station`),
          KEY `Index_ItemID_WHID` (`itemid`,`wh`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("stock", $qry);

    $this->coreFunctions->sbcaddcolumn("stock", "dateid", "datetime DEFAULT NULL", 1);

    $qry = "CREATE TABLE  `journal` (
            `station` varchar(30) NOT NULL DEFAULT '',
            `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
            `amt2` varchar(30) NOT NULL DEFAULT '',
            `cash` decimal(18,2) NOT NULL DEFAULT '0.00',
            `cheque` decimal(18,2) NOT NULL DEFAULT '0.00',
            `card` decimal(18,2) NOT NULL DEFAULT '0.00',
            `nvat` decimal(18,2) NOT NULL DEFAULT '0.00',
            `vatamt` decimal(18,2) NOT NULL DEFAULT '0.00',
            `vatex` decimal(18,2) NOT NULL DEFAULT '0.00',
            `voidamt` decimal(18,2) NOT NULL DEFAULT '0.00',
            `ctrvoid` int(11) NOT NULL DEFAULT '0',
            `returnamt` decimal(18,2) NOT NULL DEFAULT '0.00',
            `ctrreturn` int(11) NOT NULL DEFAULT '0',
            `disc` decimal(18,2) NOT NULL DEFAULT '0.00',
            `cr` decimal(18,2) NOT NULL DEFAULT '0.00',
            `discsr` decimal(18,2) NOT NULL DEFAULT '0.00',
            `dateid` datetime DEFAULT NULL,
            `lp` decimal(18,2) NOT NULL DEFAULT '0.00',
            `voucher` decimal(18,2) NOT NULL DEFAULT '0.00',
            `printdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `pwdamt` decimal(18,2) NOT NULL DEFAULT '0.00',
            `empdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
            `sc` decimal(18,2) NOT NULL DEFAULT '0.00',
            `isok` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `rlcctr` int(11) unsigned NOT NULL DEFAULT '0',
            `svccharge` decimal(18,2) NOT NULL DEFAULT '0.00',
            `debit` decimal(18,2) NOT NULL DEFAULT '0.00',
            `eplus` decimal(18,2) NOT NULL DEFAULT '0.00',
            `smac` decimal(18,2) NOT NULL DEFAULT '0.00',
            `onlinedeals` decimal(18,2) NOT NULL DEFAULT '0.00',
            `vipdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
            `oddisc` decimal(18,2) NOT NULL DEFAULT '0.00',
            `smacdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
            `localtax` decimal(18,2) NOT NULL DEFAULT '0.00',
            `isok2` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `vatdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
            `deposit` decimal(18,2) NOT NULL DEFAULT '0.00',
            `gross` decimal(18,2) NOT NULL DEFAULT '0.00',
            `loadwallet` decimal(18,2) NOT NULL DEFAULT '0.00',
            `loadamt` decimal(18,2) NOT NULL DEFAULT '0.00',
            `branch` varchar(30) NOT NULL DEFAULT ''
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("journal", $qry);

    $this->coreFunctions->sbcdropcolumn('branchwh', 'wh');
    $this->coreFunctions->sbcaddcolumn("branchwh", "whid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $qry = "ALTER TABLE branchwh DROP PRIMARY KEY, 
    ADD PRIMARY KEY  USING BTREE(`line`,`clientid`, `whid`);";
    $this->coreFunctions->execqry($qry, 'drop');

    $qry = "CREATE TABLE  `clientdlock` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `clientid` int(10) unsigned NOT NULL DEFAULT '0',
      `dlock` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("clientdlock", $qry);
    $this->coreFunctions->sbcaddcolumn("client", "dlock", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcdropcolumn('branchbrand', 'brand');
    $this->coreFunctions->sbcaddcolumn("branchbrand", "brandid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $qry = "ALTER TABLE branchbrand DROP PRIMARY KEY,     
    ADD PRIMARY KEY  USING BTREE(`line`,`clientid`, `brandid`);";
    $this->coreFunctions->execqry($qry, 'drop');

    // if (!$this->companysetup->getmultibranch($config['params'])) {
    //   $this->coreFunctions->execqry("delete from center where code<>'001'");
    // }
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "assetid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "liabilityid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "revenueid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "expenseid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "agentid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "comrate", "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("cdstock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hcdstock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcdropcolumn('stockgrp_masterfile', 'assetid');
    $this->coreFunctions->sbcdropcolumn('stockgrp_masterfile', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('stockgrp_masterfile', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('stockgrp_masterfile', 'expenseid');

    $this->coreFunctions->sbcdropcolumn('cdstock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('cdstock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('cdstock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('cdstock', 'expenseid');

    $this->coreFunctions->sbcdropcolumn('hcdstock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('hcdstock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('hcdstock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('hcdstock', 'expenseid');

    $this->coreFunctions->sbcaddcolumn("postock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sostock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sostock", "stageid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "stageid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcdropcolumn('postock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('postock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('postock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('postock', 'expenseid');

    $this->coreFunctions->sbcdropcolumn('hpostock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('hpostock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('hpostock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('hpostock', 'expenseid');

    $this->coreFunctions->sbcaddcolumn("lastock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcdropcolumn('lastock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('lastock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('lastock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('lastock', 'expenseid');

    $this->coreFunctions->sbcdropcolumn('glstock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('glstock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('glstock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('glstock', 'expenseid');

    $this->coreFunctions->sbcdropcolumn('branchagent', 'client');
    $this->coreFunctions->sbcdropcolumn('branchagent', 'isinactive');
    $this->coreFunctions->sbcdropcolumn('branchagent', 'clientname');
    $this->coreFunctions->sbcaddcolumn("branchagent", "agentid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("branchagent", "isinactive", "tinyint(1) unsigned NOT NULL DEFAULT '0'", 0);
    $qry = "ALTER TABLE branchagent DROP PRIMARY KEY,     
      ADD PRIMARY KEY  USING BTREE(`line`,`clientid`, `agentid`);";
    $this->coreFunctions->execqry($qry, 'drop');


    $this->coreFunctions->sbcdropcolumn('branchusers', 'clientname');
    $this->coreFunctions->sbcaddcolumn("branchusers", "userid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $qry = "ALTER TABLE branchusers DROP PRIMARY KEY,       
      ADD PRIMARY KEY  USING BTREE(`line`,`clientid`, `userid`)";
    $this->coreFunctions->execqry($qry, 'drop');

    $this->coreFunctions->sbcaddcolumn("qsstock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqsstock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcdropcolumn('qsstock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('qsstock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('qsstock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('qsstock', 'expenseid');

    $this->coreFunctions->sbcdropcolumn('hqsstock', 'assetid');
    $this->coreFunctions->sbcdropcolumn('hqsstock', 'liabilityid');
    $this->coreFunctions->sbcdropcolumn('hqsstock', 'revenueid');
    $this->coreFunctions->sbcdropcolumn('hqsstock', 'expenseid');


    $this->coreFunctions->sbcaddcolumn("arledger", "depodate", "DATETIME DEFAULT NULL");

    $this->coreFunctions->sbcaddcolumn("pcstock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("item", "projectid", "INT(3) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcdropcolumn('cdstock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('hcdstock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('qshead', 'groupid');
    $this->coreFunctions->sbcdropcolumn('hqshead', 'groupid');
    $this->coreFunctions->sbcdropcolumn('pcstock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('hpcstock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('qsstock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('hqsstock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('lastock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('glstock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('postock', 'groupid');
    $this->coreFunctions->sbcdropcolumn('hpostock', 'groupid');

    //JAC 09072021
    $qry = "CREATE TABLE `pricelist` (
      `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
      `itemid` INTEGER NOT NULL DEFAULT 0,
      `amount` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `type` INTEGER NOT NULL DEFAULT 0,
      PRIMARY KEY (`itemid`, `type`),
      INDEX `Index_2`(`line`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("pricelist", $qry);

    $qry = "CREATE TABLE `salesgroup` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `groupname` VARCHAR(50) NOT NULL DEFAULT '',
      `leader` VARCHAR(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) 
    ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("salesgroup", $qry);

    $qry = "CREATE TABLE `seminar` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `title` VARCHAR(50) NOT NULL DEFAULT '',
      `description` VARCHAR(50) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `product` VARCHAR(50) NOT NULL DEFAULT '',
      `location` VARCHAR(50) NOT NULL DEFAULT '',
      `presenter` VARCHAR(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) 
    ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("seminar", $qry);

    //JLY 2021.12.13
    $this->coreFunctions->sbcaddcolumn("seminar", "semtime", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("seminar", "attendeecount", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("seminar", "semtype", "VARCHAR(30) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("seminar", "remarks", "VARCHAR(250) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcdropcolumn("seminar", "clientstatus");

    $this->coreFunctions->sbcaddcolumn("seminar", "endsemtime", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("seminar", "enddate", "DATETIME DEFAULT NULL");

    $qry = "CREATE TABLE `exhibit` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `title` VARCHAR(50) NOT NULL DEFAULT '',
      `description` VARCHAR(50) NOT NULL DEFAULT '',
      `startdate` datetime DEFAULT NULL,
      `enddate` datetime DEFAULT NULL,
      `product` VARCHAR(50) NOT NULL DEFAULT '',
      `location` VARCHAR(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) 
    ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("exhibit", $qry);
    $this->coreFunctions->sbcdropcolumn("exhibit", "clientstatus");

    $this->coreFunctions->sbcaddcolumn("exhibit", "remarks", "VARCHAR(250) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE `source` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `title` VARCHAR(50) NOT NULL DEFAULT '',
      `description` VARCHAR(100) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) 
    ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("source", $qry);
    $this->coreFunctions->sbcdropcolumn("source", "clientstatus");

    $this->coreFunctions->execqry("update lahead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update glhead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update lahead set branch =0 where branch is null", 'update');
    $this->coreFunctions->execqry("update glhead set branch =0 where branch is null", 'update');
    $this->coreFunctions->sbcaddcolumn("lahead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("glhead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->execqry("update pohead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update hpohead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update pohead set branch =0 where branch is null", 'update');
    $this->coreFunctions->execqry("update hpohead set branch =0 where branch is null", 'update');
    $this->coreFunctions->sbcaddcolumn("pohead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hpohead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("ophead", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("opstock", "projectid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hopstock", "projectid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sqhead", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsqhead", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("update qshead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update hqshead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update qshead set branch =0 where branch is null", 'update');
    $this->coreFunctions->execqry("update hqshead set branch =0 where branch is null", 'update');
    $this->coreFunctions->sbcaddcolumn("qshead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hqshead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumn("sqhead", "pdate", "DATE DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hsqhead", "pdate", "DATE DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("qshead", "pdate", "DATE DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "pdate", "DATE DEFAULT NULL", 0);

    //JAC 09162021
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "pmtrno", "INT(10) NOT NULL DEFAULT '0'", 1);
    //JAC 09172021
    $this->coreFunctions->sbcdropcolumn("lahead", "dept");
    $this->coreFunctions->sbcdropcolumn("glhead", "dept");
    $this->coreFunctions->sbcaddcolumn("lahead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("cdhead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hcdhead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("update cdhead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update hcdhead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update cdhead set branch =0 where branch is null", 'update');
    $this->coreFunctions->execqry("update hcdhead set branch =0 where branch is null", 'update');
    $this->coreFunctions->sbcaddcolumn("cdhead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hcdhead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("pohead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ladetail", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ladetail", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("voiddetail", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hvoiddetail", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("voiddetail", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hvoiddetail", "branch", "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "agentid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ladetail", "podate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("ladetail", "poref", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "agentid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "podate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "poref", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("useraccess", "pincode", "VARCHAR(25) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("useraccess", "pincode2", "VARCHAR(25) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("qshead", "position", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqshead", "position", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("qshead", "agentcno", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "agentcno", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqshead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qshead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqshead", "billid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("cdhead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hcdhead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);

    //JIKS 09242021
    $qry = "CREATE TABLE `iteminfo` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `itemid` int(11) unsigned NOT NULL DEFAULT 0,
      `itemdescription` varchar(200) NOT NULL DEFAULT '',
      `accessories` varchar(200) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("iteminfo", $qry);

    $qry = "CREATE TABLE `ddrlogs` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `trno` int(11) unsigned NOT NULL DEFAULT 0,
      `deliverydate` DATETIME DEFAULT NULL,
      `reason` varchar(200) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL,
      PRIMARY KEY (`line`, `trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("ddrlogs", $qry);

    //JIKS 10-02-2021
    $this->coreFunctions->sbcaddcolumn("jbhead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbhead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbhead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbhead", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbhead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbhead", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "refx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "refx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "linex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "linex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "cdrefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "cdrefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "cdlinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "cdlinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "cost", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "cost", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "qty", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "qty", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "rrcost", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "rrcost", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "rrqty", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "rrqty", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "ref", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "ref", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "itemid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "whid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('jbstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('hjbstock', 'barcode');
    $this->coreFunctions->sbcdropcolumn('jbstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('hjbstock', 'itemname');
    $this->coreFunctions->sbcdropcolumn('jbstock', 'wh');
    $this->coreFunctions->sbcdropcolumn('hjbstock', 'wh');
    $this->coreFunctions->sbcaddcolumn("jbstock", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbstock", "sku", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hjbstock", "sku", "varchar(50) NOT NULL DEFAULT ''", 0);

    //JIKS 10-04-2021
    $this->coreFunctions->sbcaddcolumn("johead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjohead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jostock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjostock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jostock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjostock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jostock", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjostock", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `substages` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `substage` varchar(150) NOT NULL DEFAULT '',
      `stage` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("substages", $qry);

    $qry = "CREATE TABLE `subitems` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `itemid` int(10) unsigned NOT NULL DEFAULT '0',
      `substage` int(10) unsigned NOT NULL DEFAULT '0',
      `qty` decimal(18,2) NOT NULL DEFAULT '0.00',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`itemid`,`substage`),
      KEY `Index_2` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("subitems", $qry);


    $this->coreFunctions->sbcaddcolumn("sostock", "substage", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "substage", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qthead", "deptid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqthead", "deptid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("update qthead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update hqthead set branch =0 where branch =''", 'update');
    $this->coreFunctions->execqry("update qthead set branch =0 where branch is null", 'update');
    $this->coreFunctions->execqry("update hqthead set branch =0 where branch is null", 'update');
    $this->coreFunctions->sbcaddcolumn("qthead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hqthead", "branch", "INT(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("qtstock", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqtstock", "projectid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qtstock", "refx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqtstock", "refx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qtstock", "linex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqtstock", "linex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "strno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "strno", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ophead", "participantid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "participantid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ophead", "address", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hophead", "address", "varchar(500) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("qshead", "address", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqshead", "address", "varchar(500) NOT NULL DEFAULT ''", 1);


    //JAC 10/12/2021
    $qry = "CREATE TABLE  `srhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(100) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `due` datetime DEFAULT NULL,
      `wh` varchar(15) DEFAULT NULL,
      `terms` varchar(30) DEFAULT NULL,
      `rem` varchar(500) DEFAULT NULL,
      `cur` varchar(3) NOT NULL DEFAULT '',
      `forex` decimal(18,2) DEFAULT '0.00',
      `voiddate` datetime DEFAULT NULL,
      `branch` int(10) NOT NULL DEFAULT '0',
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
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      `vattype` varchar(25) NOT NULL DEFAULT '',
      `deptid` int(10) NOT NULL DEFAULT '0',
      `qtrno` int(11) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`),
      KEY `Index_trno` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("srhead", $qry);

    $qry = "CREATE TABLE  `hsrhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(150) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `due` datetime DEFAULT NULL,
      `wh` varchar(15) DEFAULT NULL,
      `terms` varchar(30) DEFAULT NULL,
      `rem` varchar(500) DEFAULT NULL,
      `cur` varchar(3) NOT NULL DEFAULT '',
      `forex` decimal(18,2) DEFAULT '0.00',
      `voiddate` datetime DEFAULT NULL,
      `branch` int(10) NOT NULL DEFAULT '0',
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
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      `vattype` varchar(25) NOT NULL DEFAULT '',
      `sotrno` int(11) NOT NULL DEFAULT '0',
      `qtrno` int(11) NOT NULL DEFAULT '0',
      `deptid` int(10) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`),
      KEY `Index_trno` (`trno`),
      KEY `Index_sotrno` (`sotrno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsrhead", $qry);

    $qry = "CREATE TABLE  `srstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) DEFAULT '',
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
      `expiry` varchar(45) DEFAULT '',
      `fstatus` varchar(45) NOT NULL DEFAULT '',
      `wh_currentqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(20) NOT NULL DEFAULT '',
      `refx` int(11) unsigned NOT NULL DEFAULT '0',
      `linex` int(11) unsigned NOT NULL DEFAULT '0',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`),
      KEY `Index_itemid` (`itemid`),
      KEY `Index_whid` (`whid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("srstock", $qry);

    $qry = "CREATE TABLE  `hsrstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL,
      `uom` varchar(15) DEFAULT NULL,
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(40) DEFAULT NULL,
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
      `fstatus` varchar(45) NOT NULL DEFAULT '',
      `wh_currentqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `stageid` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(20) NOT NULL DEFAULT '',
      `refx` int(11) unsigned NOT NULL DEFAULT '0',
      `linex` int(11) unsigned NOT NULL DEFAULT '0',
      `sjqa` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `poqa` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `iscanvass` tinyint(2) unsigned NOT NULL DEFAULT '0',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`),
      KEY `Index_itemid` (`itemid`),
      KEY `Index_whid` (`whid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hsrstock", $qry);
    $this->coreFunctions->sbcaddcolumn("hqthead", "srtrno", "INT(10) NOT NULL DEFAULT '0'", 1);

    //JIKS 10-12-2021 -- added fields for crm sales activity
    $this->coreFunctions->sbcaddcolumn("ophead", "compname", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "compname", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "designation", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "designation", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "contactname", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "contactname", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "contactno", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "contactno", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "email", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "email", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "source", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "source", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ophead", "sourceid", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "sourceid", "varchar(50) NOT NULL DEFAULT ''", 0);


    $qry = "CREATE TABLE `calllogs` (
      `trno` bigint(20) NOT NULL DEFAULT 0,
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `dateid` DATETIME DEFAULT NULL,
      `starttime` VARCHAR(50) NOT NULL DEFAULT '',
      `endtime` VARCHAR(50) NOT NULL DEFAULT '',
      `rem` text DEFAULT NULL,
      `calltype` VARCHAR(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("calllogs", $qry);

    //JAC 10142021
    $qry = "CREATE TABLE  `sshead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `voiddate` datetime DEFAULT NULL,
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
      PRIMARY KEY (`trno`),
      KEY `Index_trno` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("sshead", $qry);


    $qry = "CREATE TABLE  `hsshead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `dateid` datetime DEFAULT NULL,
    `voiddate` datetime DEFAULT NULL,
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
    PRIMARY KEY (`trno`),
    KEY `Index_trno` (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hsshead", $qry);

    $this->coreFunctions->sbcaddcolumn("ophead", "industry", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hophead", "industry", "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("billingaddr", "contact", "VARCHAR(250) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "contactno", "VARCHAR(250) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "addrtype", "VARCHAR(250) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "addrline1", "VARCHAR(1000) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "addrline2", "VARCHAR(1000) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "city", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "province", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "country", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "email", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "fax", "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("qthead", "tin", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqthead", "tin", "VARCHAR(30) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("qthead", "position", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqthead", "position", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("qthead", "agentcno", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqthead", "agentcno", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("qthead", "industry", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqthead", "industry", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("qthead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqthead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qthead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqthead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "leaddur", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "leaddur", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "advised", "int(1) unsigned DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "advised", "int(1) unsigned DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "leadfrom", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "leadto", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "leaddur", "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "advised", "int(1) unsigned DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "leadfrom", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "leadto", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "leaddur", "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "advised", "int(1) unsigned DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("qtstock", "ref", "varchar(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hqtstock", "ref", "varchar(150) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("qsstock", "ref", "varchar(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "ref", "varchar(150) NOT NULL DEFAULT ''");


    $this->coreFunctions->sbcaddcolumn("client", "territory", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "activity", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "crtype", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "crdays", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "vattype", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "registername", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcdropcolumn('client', 'taxncharges');
    $this->coreFunctions->sbcdropcolumn('client', 'billingcur');
    $this->coreFunctions->sbcaddcolumn("client", "salesgroupid", "INT(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `tehead` (
    `trno` int(11) NOT NULL AUTO_INCREMENT,
    `doc` VARCHAR(20) NOT NULL DEFAULT '',
    `docno` VARCHAR(20) NOT NULL DEFAULT '',
    `clientid` int(11) NOT NULL DEFAULT 0,
    `errandtype` VARCHAR(50) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `datereq` datetime DEFAULT NULL,
    `dateneed` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
    `tasktitle` VARCHAR(50) NOT NULL DEFAULT '',
    `instruction` VARCHAR(50) NOT NULL DEFAULT '',
    `assignto` VARCHAR(50) NOT NULL DEFAULT '',
    `users` VARCHAR(50) NOT NULL DEFAULT '',
    `createdate` datetime DEFAULT NULL,
    `createby` VARCHAR(50) NOT NULL DEFAULT '',
    `editby` VARCHAR(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` VARCHAR(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `lockdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    KEY `Index_clientid` (`clientid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("tehead", $qry);

    $this->coreFunctions->sbcaddcolumn("tehead", "errandtype", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "assignid", "INT(11) NOT NULL DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "ppio", "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "datefiled", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "datereturn", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "preparedbyid", "INT(11) NOT NULL DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "contactperson", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "companyaddress", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "rem", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "company", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "contact", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("tehead", "rem", "varchar(1000) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `htehead` (
    `trno` int(11) NOT NULL,
    `doc` VARCHAR(20) NOT NULL DEFAULT '',
    `docno` VARCHAR(20) NOT NULL DEFAULT '',
    `clientid` int(11) NOT NULL DEFAULT 0,
    `errandtype` VARCHAR(50) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `datereq` datetime DEFAULT NULL,
    `dateneed` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
    `tasktitle` VARCHAR(50) NOT NULL DEFAULT '',
    `instruction` VARCHAR(50) NOT NULL DEFAULT '',
    `assignto` VARCHAR(50) NOT NULL DEFAULT '',
    `users` VARCHAR(50) NOT NULL DEFAULT '',
    `createdate` datetime DEFAULT NULL,
    `createby` VARCHAR(50) NOT NULL DEFAULT '',
    `editby` VARCHAR(50) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` VARCHAR(50) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `lockdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    KEY `Index_clientid` (`clientid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("htehead", $qry);

    $this->coreFunctions->sbcaddcolumn("htehead", "errandtype", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "assignid", "INT(11) NOT NULL DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "ppio", "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "datefiled", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "datereturn", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "preparedbyid", "INT(11) NOT NULL DEFAULT 0", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "contactperson", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "companyaddress", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "rem", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "company", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "contact", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htehead", "rem", "varchar(1000) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `testock` (
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
    `sorefx` int(11) NOT NULL DEFAULT '0',
    `solinex` int(11) NOT NULL DEFAULT '0',
    `projectid` int(3) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("testock", $qry);

    $this->coreFunctions->sbcaddcolumn("testock", "itemname", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("testock", "brand", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("testock", "model", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("testock", "serialno", "varchar(50) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `htestock` (
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
    `sorefx` int(11) NOT NULL DEFAULT '0',
    `solinex` int(11) NOT NULL DEFAULT '0',
    `projectid` int(3) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("htestock", $qry);

    $this->coreFunctions->sbcaddcolumn("htestock", "itemname", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htestock", "brand", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htestock", "model", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htestock", "serialno", "varchar(50) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `ppio_series` (
      `trno` int(10) unsigned NOT NULL,
      `seq` int(10) unsigned NOT NULL DEFAULT '0',
      `year` int(10) unsigned NOT NULL DEFAULT '0',
      `docno` varchar(10) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      PRIMARY KEY (`year`,`seq`),
      KEY `Index_2` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("ppio_series", $qry);

    $qry = "CREATE TABLE `contactperson` (
    `line` int(11) NOT NULL AUTO_INCREMENT,
    `clientid` int(11) NOT NULL DEFAULT 0,
    `salutation` VARCHAR(10) NOT NULL DEFAULT '',
    `fname` VARCHAR(50) NOT NULL DEFAULT '',
    `mname` VARCHAR(50) NOT NULL DEFAULT '',
    `lname` VARCHAR(50) NOT NULL DEFAULT '',
    `email` VARCHAR(50) NOT NULL DEFAULT '',
    `contactno` VARCHAR(100) NOT NULL DEFAULT '',
    `bday` datetime DEFAULT NULL,
    `deptid` int(11) unsigned DEFAULT NULL,
    `designation` VARCHAR(100) NOT NULL DEFAULT '',
    PRIMARY KEY (`line`),
    KEY `Index_clientid` (`clientid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("contactperson", $qry);

    $this->coreFunctions->sbcaddcolumn("contactperson", "department", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("contactperson", "designation", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "contactno", "VARCHAR(250) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "email", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "lname", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "fname", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "mname", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "salutation", "VARCHAR(10) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "activity", "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `headinfotrans` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `inspo` VARCHAR(50) NOT NULL DEFAULT '',
    `deldate` datetime DEFAULT NULL,
    `ispartial` int(1) unsigned DEFAULT NULL,
    `instructions` text DEFAULT NULL,
    `period` VARCHAR(20) NOT NULL DEFAULT '',
    `isvalid` int(1) unsigned DEFAULT NULL,
    `ovaliddate` datetime DEFAULT NULL,
    `taxesandcharge` VARCHAR(20) NOT NULL DEFAULT '',
    `terms` varchar(30) NOT NULL DEFAULT '',
    `termsdetails` varchar(100) NOT NULL DEFAULT '',
    `proformainvoice` varchar(50) NOT NULL DEFAULT '',
    `proformadate` datetime DEFAULT NULL,
    KEY `Index_trno` (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("headinfotrans", $qry);

    $qry = "CREATE TABLE `hheadinfotrans` (
    `trno` int(11) NOT NULL DEFAULT 0,
    `inspo` VARCHAR(50) NOT NULL DEFAULT '',
    `deldate` datetime DEFAULT NULL,
    `ispartial` int(1) unsigned DEFAULT NULL,
    `instructions` text DEFAULT NULL,
    `period` VARCHAR(20) NOT NULL DEFAULT '',
    `isvalid` int(1) unsigned DEFAULT NULL,
    `ovaliddate` datetime DEFAULT NULL,
    `taxesandcharge` VARCHAR(20) NOT NULL DEFAULT '',
    `terms` varchar(30) NOT NULL DEFAULT '',
    `termsdetails` varchar(100) NOT NULL DEFAULT '',
    `proformainvoice` varchar(50) NOT NULL DEFAULT '',
    `proformadate` datetime DEFAULT NULL,
    KEY `Index_trno` (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hheadinfotrans", $qry);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "leadfrom", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "leaddur", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "advised", "int(1) unsigned DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "leadfrom", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "leaddur", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "advised", "int(1) unsigned DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "tax", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "tax", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('headinfotrans', 'taxesandcharge');
    $this->coreFunctions->sbcdropcolumn('hheadinfotrans', 'taxesandcharge');
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "vattype", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "vattype", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hqshead", "optrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "optrno", "int(11) NOT NULL DEFAULT '0'", 0);

    //JIKS 10212021 - update column
    $this->coreFunctions->sbcaddcolumn("iteminfo", "itemdescription", "text DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "accessories", "text DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "leadfrom", "int(11) NOT NULL DEFAULT 0", 1);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "leadto", "int(11) NOT NULL DEFAULT 0", 1);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "leadfrom", "int(11) NOT NULL DEFAULT 0", 1);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "leadto", "int(11) NOT NULL DEFAULT 0", 1);
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "leadfrom", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "leadto", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "leadfrom", "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "leadto", "int(11) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumn("opstock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hopstock", "stageid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt1", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt2", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt3", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt4", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt5", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt1", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt2", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt3", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt4", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt5", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt1", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt2", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt3", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt4", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt5", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt1", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt2", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt3", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt4", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt5", "decimal(18, 6) NOT NULL DEFAULT '0'", 0);

    // JIKS
    $this->coreFunctions->sbcaddcolumn("item", "isoutsource", "tinyint(1) NOT NULL DEFAULT '0'");

    //jac
    $qry = "CREATE TABLE  `proformainv` (
    `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `seq` int(10) unsigned NOT NULL DEFAULT '0',
    `year` int(10) unsigned NOT NULL DEFAULT '0',
    `docno` varchar(10) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    PRIMARY KEY (`year`,`seq`),
    KEY `Index_2` (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("proformainv", $qry);

    // jiks10252021 - outsource module
    $qry = "CREATE TABLE  `oshead` LIKE `pohead` ";
    $this->coreFunctions->sbccreatetable("oshead", $qry);

    $qry = "CREATE TABLE  `hoshead` LIKE `hpohead` ";
    $this->coreFunctions->sbccreatetable("hoshead", $qry);

    $qry = "CREATE TABLE  `osstock` LIKE `postock` ";
    $this->coreFunctions->sbccreatetable("osstock", $qry);

    $qry = "CREATE TABLE  `hosstock` LIKE `hpostock` ";
    $this->coreFunctions->sbccreatetable("hosstock", $qry);

    $this->coreFunctions->sbcaddcolumn("oshead", "telesales", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("oshead", "lineitem", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "crossref", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "nooffertotal", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "nobidtotal", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "datesent", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("oshead", "datequote", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("oshead", "dateforward", "DATETIME DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("oshead", "ostech", "VARCHAR(50) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("hoshead", "telesales", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hoshead", "lineitem", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "crossref", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "nooffertotal", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "nobidtotal", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "datesent", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hoshead", "datequote", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hoshead", "dateforward", "DATETIME DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("hoshead", "ostech", "VARCHAR(50) NOT NULL DEFAULT ''");

    //janx2 08052022
    $this->coreFunctions->sbcaddcolumn("oshead", "telesalesid", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "telesalesid", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "ostechid", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "ostechid", "int(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("item", "moq", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("item", "mmoq", "int(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("client", "position", "VARCHAR(50) NOT NULL DEFAULT ''", 1);

    //KIM 10262021
    $this->coreFunctions->sbcaddcolumn("pohead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);


    // JIKS 10272021
    $this->coreFunctions->sbcaddcolumn("qshead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqshead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qshead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqshead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qthead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqthead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qthead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqthead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("pohead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hpohead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("pohead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hpohead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("ophead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hophead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("ophead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hophead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `qscalllogs` (
    `trno` bigint(20) NOT NULL DEFAULT 0,
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `dateid` DATETIME DEFAULT NULL,
    `starttime` VARCHAR(50) NOT NULL DEFAULT '',
    `endtime` VARCHAR(50) NOT NULL DEFAULT '',
    `rem` text DEFAULT NULL,
    `calltype` VARCHAR(50) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("qscalllogs", $qry);

    $qry = "CREATE TABLE `hqscalllogs` (
    `trno` bigint(20) NOT NULL DEFAULT 0,
    `line` int(11) unsigned NOT NULL ,
    `dateid` DATETIME DEFAULT NULL,
    `starttime` VARCHAR(50) NOT NULL DEFAULT '',
    `endtime` VARCHAR(50) NOT NULL DEFAULT '',
    `rem` text DEFAULT NULL,
    `calltype` VARCHAR(50) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hqscalllogs", $qry);

    //KIM 10272021
    $this->coreFunctions->sbcaddcolumn("pohead", "empid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "empid", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("client", "deptid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "empid", "INT(11) NOT NULL DEFAULT '0'");

    //JAC 10282021
    $this->coreFunctions->sbcaddcolumn("lastock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "sorefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "solinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pohead", "sotrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "sotrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qtstock", "sjqa", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqtstock", "sjqa", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);

    // 11022021 - update rate set to 4 decimal
    $this->coreFunctions->sbcaddcolumn("exchangerate", "rate", "decimal(18,4) NOT NULL DEFAULT '0.0000'", 1);


    $this->coreFunctions->sbcaddcolumn("qshead", "deldate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hqshead", "deldate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("srhead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hsrhead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("srhead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hsrhead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("srhead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hsrhead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("srhead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hsrhead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("item", "inhouse", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "validity", "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "validity", "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "validity", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "validity", "VARCHAR(50) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("oshead", "vendor", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("oshead", "customer", "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hoshead", "vendor", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hoshead", "customer", "VARCHAR(100) NOT NULL DEFAULT ''");

    //JAC11052021
    $this->coreFunctions->sbcaddcolumn("postock", "osrefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "osrefx", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "oslinex", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "oslinex", "int(11) NOT NULL DEFAULT '0'", 0);

    //JAC 11082021
    $this->coreFunctions->sbcdropcolumn('billingaddr', 'email');
    $this->coreFunctions->sbcaddcolumn("billingaddr", "zipcode", "varchar(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "activity", "varchar(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "tax", "INT(5) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqshead", "tax", "INT(5) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcdropcolumn('headinfotrans', 'vattype');
    $this->coreFunctions->sbcdropcolumn('headinfotrans', 'terms');
    $this->coreFunctions->sbcdropcolumn('headinfotrans', 'tax');
    $this->coreFunctions->sbcdropcolumn('hheadinfotrans', 'vattype');
    $this->coreFunctions->sbcdropcolumn('hheadinfotrans', 'terms');
    $this->coreFunctions->sbcdropcolumn('hheadinfotrans', 'tax');

    //FMM - 2021.11.08
    $qry = "CREATE TABLE  `prchange` (
    `itemid` int(10) unsigned NOT NULL DEFAULT '0',
    `dateid` datetime DEFAULT NULL,
    `prgroup` VARCHAR(10) NOT NULL DEFAULT '',
    `price` DECIMAL(18, 6) NOT NULL DEFAULT '0.000000',
    `oldprice` DECIMAL(18, 6) NOT NULL DEFAULT '0.000000',
    `userid` VARCHAR(50) NOT NULL DEFAULT '',
    KEY `Index_itemid` (`itemid`),
    KEY `Index_prgroup` (`prgroup`),
    KEY `Index_userid` (`userid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("prchange", $qry);

    $qry = "CREATE TABLE  `priceupdate` (
    `itemid` int(10) unsigned NOT NULL DEFAULT '0',
    `dateid` datetime DEFAULT NULL,
    KEY `Index_itemid` (`itemid`),
    KEY `Index_prgroup` (`dateid`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("priceupdate", $qry);


    //JIKS 11-08-2021
    $this->coreFunctions->sbcaddcolumn("center", "email", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("calllogs", "starttime", "VARCHAR(12) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("calllogs", "endtime", "VARCHAR(12) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("qscalllogs", "starttime", "VARCHAR(12) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("qscalllogs", "endtime", "VARCHAR(12) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqscalllogs", "starttime", "VARCHAR(12) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqscalllogs", "endtime", "VARCHAR(12) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("calllogs", "contact", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("qscalllogs", "contact", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqscalllogs", "contact", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("pohead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hpohead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("pohead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hpohead", "billid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("client", "tax", "INT(11) NOT NULL DEFAULT '0'", 1);

    //JAC 11102021
    $this->coreFunctions->sbcaddcolumn("qshead", "probability", "VARCHAR(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "probability", "VARCHAR(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "taxdef", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "taxdef", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("client", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("lahead", "taxdef", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "taxdef", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("client", "industry", "VARCHAR(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("client", "agentcode", "VARCHAR(5) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("client", "branchid", "INT(5) NOT NULL DEFAULT '0'", 0);

    //GLEN 11.24.2021
    $this->coreFunctions->sbcaddcolumn("client", "ewtid", "INT(5) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("client", "orgstructure", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    //jiks 11.12.2021
    $this->coreFunctions->sbcaddcolumn("lahead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("lahead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");

    //jac11122021
    $this->coreFunctions->sbcaddcolumn("opstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hopstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qsstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqsstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qtstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqtstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "poref", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("glstock", "poref", "VARCHAR(50) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("srstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsrstock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jostock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjostock", "sgdrate", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jostock", "poref", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hjostock", "poref", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("postock", "poref", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "poref", "VARCHAR(30) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("qshead", "creditinfo", "VARCHAR(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "creditinfo", "VARCHAR(1000) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("useraccess", "pincode", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("useraccess", "pincode2", "VARCHAR(30) NOT NULL DEFAULT ''", 0);

    $qry = "ALTER TABLE `subitems` DROP PRIMARY KEY,
    ADD PRIMARY KEY  USING BTREE(`itemid`, `substage`, `subactivity`, `stage`);";
    $this->coreFunctions->execqry($qry, 'drop');

    $qry = "CREATE TABLE  `wrhead` LIKE `pohead` ";
    $this->coreFunctions->sbccreatetable("wrhead", $qry);

    $qry = "CREATE TABLE  `hwrhead` LIKE `hpohead` ";
    $this->coreFunctions->sbccreatetable("hwrhead", $qry);

    $qry = "CREATE TABLE  `wrstock` LIKE `postock` ";
    $this->coreFunctions->sbccreatetable("wrstock", $qry);

    $qry = "CREATE TABLE  `hwrstock` LIKE `hpostock` ";
    $this->coreFunctions->sbccreatetable("hwrstock", $qry);



    $qry = "CREATE TABLE  `subactivity` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `substage` int(10) NOT NULL DEFAULT '0',
    `stage` int(10) unsigned NOT NULL DEFAULT '0',
    `subactivity` varchar(150) NOT NULL DEFAULT '',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("subactivity", $qry);

    $this->coreFunctions->sbcaddcolumn("subactivity", "description", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("subitems", "subactivity", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("subitems", "stage", "INT(11) NOT NULL DEFAULT '0'");


    $qry = "CREATE TABLE `activity` (
    `line` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `trno` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `subproject` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `stage` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`)
  )
  ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("activity", $qry);

    $qry = "CREATE TABLE `psubactivity` (
    `line` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `trno` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `substage` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `stage` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`,`line`)
  )
  ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("psubactivity", $qry);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "subactid", "VARCHAR(100) NOT NULL DEFAULT ''", 1);

    $qry = "ALTER TABLE `psubactivity` DROP PRIMARY KEY, 
    ADD PRIMARY KEY USING BTREE(`trno`, `line`, `subactid`,`subproject`, `stage`);";
    $this->coreFunctions->execqry($qry, 'drop');

    $qry = "CREATE TABLE  `bahead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) DEFAULT NULL,
    `address` varchar(150) DEFAULT NULL,
    `dateid` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
    `rem` varchar(500) DEFAULT NULL,
    `voiddate` datetime DEFAULT NULL,
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
    `projectid` int(10) unsigned NOT NULL DEFAULT '0',
    `subproject` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("bahead", $qry);

    $qry = "CREATE TABLE `bastock` (
    `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `rrqty` DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT 0,
    `rrcost` DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT 0,
    `ext` DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT 0,
    `stage` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `activity` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `subactivity` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`trno`, `line`)
  )
  ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("bastock", $qry);

    $qry = "CREATE TABLE  `hbahead` (
    `trno` bigint(20) NOT NULL DEFAULT '0',
    `doc` char(2) NOT NULL DEFAULT '',
    `docno` char(20) NOT NULL,
    `client` varchar(15) NOT NULL DEFAULT '',
    `clientname` varchar(150) DEFAULT NULL,
    `address` varchar(150) DEFAULT NULL,
    `dateid` datetime DEFAULT NULL,
    `due` datetime DEFAULT NULL,
    `rem` varchar(500) DEFAULT NULL,
    `voiddate` datetime DEFAULT NULL,
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
    `projectid` int(10) unsigned NOT NULL DEFAULT '0',
    `subproject` int(11) NOT NULL DEFAULT '0',
    `stageid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hbahead", $qry);

    $qry = "CREATE TABLE `hbastock` (
    `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `rrqty` DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT 0,
    `rrcost` DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT 0,
    `ext` DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT 0,
    `stage` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `activity` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    `subactivity` INTEGER UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`trno`, `line`)
  )
  ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("hbastock", $qry);

    $this->coreFunctions->sbcdropcolumn('client', 'orgstructure');
    $this->coreFunctions->sbcaddcolumn("qshead", "yourref", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqshead", "yourref", "VARCHAR(50) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("jbhead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjbhead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("jbhead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjbhead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("jbhead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjbhead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("jbhead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjbhead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `agentquota` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `amount` DECIMAL(18,6) UNSIGNED NOT NULL DEFAULT 0,
    `projectid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    PRIMARY KEY (`line`,`clientid`,`projectid`)
  )
  ENGINE = MyISAM;";

    $this->coreFunctions->sbccreatetable("agentquota", $qry);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "rrqty", "decimal(18,6) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("psubactivity", "rrcost", "decimal(18,6) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("psubactivity", "ext", "decimal(18,6) NOT NULL DEFAULT '0'");
    if ($config['params']['companyid'] == 8) {
      $this->coreFunctions->sbcaddcolumn("psubactivity", "uom", "varchar(15) NOT NULL DEFAULT ''", 1);
    } else {
      $this->coreFunctions->sbcaddcolumn("psubactivity", "uom", "varchar(5) NOT NULL DEFAULT ''", 1);
    }


    $this->coreFunctions->sbcaddcolumn("psubactivity", "cost", "decimal(18,6) NOT NULL DEFAULT '0' AFTER `rrcost`", 0);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "description", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "totalcost", "decimal(18,6) NOT NULL DEFAULT '0' AFTER `ext`", 0);


    $this->coreFunctions->sbcaddcolumn("ophead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("ophead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hophead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hophead", "shipid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("johead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjohead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("johead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjohead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("johead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjohead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("johead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjohead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("budget", "deptid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("budget", "branch", "INT(11) NOT NULL DEFAULT '0'");

    //$this->coreFunctions->sbcaddcolumn("contactperson", "contactno", "varchar(100) NOT NULL DEFAULT ''", 1); afti uses more char
    $this->coreFunctions->sbcaddcolumn("contactperson", "mobile", "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("bastock", "encodedby", "varchar(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("bastock", "encodeddate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("bastock", "editby", "VARCHAR(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("bastock", "editdate", "DATETIME  DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hbastock", "encodedby", "varchar(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hbastock", "encodeddate", "DATETIME  DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hbastock", "editby", "VARCHAR(25) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hbastock", "editdate", "DATETIME  DEFAULT NULL");

    $this->coreFunctions->sbcaddcolumn("oshead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("oshead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `attendee` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `exhibitid` int(11) unsigned NOT NULL,
    `clientid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
    `companyname` varchar(150) DEFAULT NULL,
    `contactname` varchar(150) DEFAULT NULL,
    `contactno` VARCHAR(50) NOT NULL DEFAULT '',
    `department` varchar(150) DEFAULT NULL,
    `designation` varchar(150) DEFAULT NULL,
    `email` varchar(150) DEFAULT NULL,
    PRIMARY KEY (`line`,`exhibitid`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("attendee", $qry);

    $this->coreFunctions->sbcaddcolumn("attendee", "isexhibit", "int(1) unsigned DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "isseminar", "int(1) unsigned DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "issource", "int(1) unsigned DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "client", "varchar(25) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("attendee", "dateid", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "optrno", "int(3) unsigned DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "contactid", "int(3) unsigned DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("attendee", "companyname", "VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("attendee", "contactname", "VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("attendee", "contactno", "VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("attendee", "department", "VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("attendee", "designation", "VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("attendee", "email", "VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("attendee", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "clientstatus", "varchar(30) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("attendee", "mrktremarks", "VARCHAR(250) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("attendee", "saleremarks", "VARCHAR(250) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("attendee", "salesperson", "VARCHAR(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("attendee", "salesid", "int(3) unsigned DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("attendee", "status", "varchar(50) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `empbudget` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `year` int(10) unsigned NOT NULL DEFAULT '0',
      `client` varchar(25) NOT NULL DEFAULT '',
      `clientid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
      `branchid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
      `deptid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
      `projectid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
      `acnoid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
      `janamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `febamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `maramt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `apramt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `mayamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `junamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',

      `julamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `augamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `sepamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `octamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `novamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `decamt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      `total` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
      PRIMARY KEY (`line`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("empbudget", $qry);

    $this->coreFunctions->sbcaddcolumn("empbudget", "budgetline", "INT(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `sqcomments` (
    `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `trno` int(11) NOT NULL DEFAULT 0,
    `userid` int(11) unsigned NOT NULL DEFAULT '0',
    `createdate` DATETIME DEFAULT NULL,
    `comment` VARCHAR(200) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("sqcomments", $qry);

    //2021.12.02 - FMM
    $this->coreFunctions->sbcaddcolumn("transnum", "statid", "INT(3) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("cntnum", "statid", "INT(3) NOT NULL DEFAULT '0'");

    //KIM 2021.12.02
    $this->coreFunctions->sbcaddcolumn("qsstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qtstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqtstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("lastock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("lastock", "podate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "podate", "DATETIME DEFAULT NULL", 0);

    //KIM 2021.12.02
    $this->coreFunctions->sbcaddcolumn("qsstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqsstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("qtstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hqtstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("lastock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glstock", "noprint", "TINYINT(2) UNSIGNED NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("hbahead", "pbtrno", "int(10) NOT NULL DEFAULT '0'");

    // JIKS 2021.12.04
    $this->coreFunctions->sbcaddcolumn("johead", "vattype", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hjohead", "vattype", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("johead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjohead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("srhead", "vattype", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hsrhead", "vattype", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("srhead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsrhead", "tax", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "invoiceno", "varchar(25) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("glhead", "invoiceno", "varchar(25) NOT NULL DEFAULT ''", 1);

    //KIM 2021.12.07
    $this->coreFunctions->sbcaddcolumn("rvoyage", "totalcash", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("rvoyage", "totalexpenses", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("rvoyage", "cashbalance", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("hrvoyage", "totalcash", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hrvoyage", "totalexpenses", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hrvoyage", "cashbalance", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    //JIKS 2021.12.09
    //$this->coreFunctions->sbcaddcolumn("cntnum", "seq", "bigint (20) NOT NULL DEFAULT '0'", 1);
    //$this->coreFunctions->sbcaddcolumn("transnum", "seq", "bigint (20) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("apledger", "ref", "VARCHAR(1000) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("arledger", "ref", "VARCHAR(1000) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("testock", "editby", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htestock", "editby", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "subproject", "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "SHOW INDEXES FROM activity WHERE Key_name = ?";
    if ($this->coreFunctions->opentable($qry, ['PRIMARY'])) {
      $qry = "ALTER TABLE activity DROP PRIMARY KEY";
      $this->coreFunctions->execqry($qry, 'drop');
    }

    $this->coreFunctions->sbcaddcolumn("lahead", "qttrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "sotrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "qttrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "crtrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "crtrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "termsdetails", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "termsdetails", "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "qttrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "qttrno", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("prhead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hprhead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("johead", "terms", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hjohead", "terms", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("johead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hjohead", "cur", "VARCHAR(3) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("johead", "forex", "decimal(18,2) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjohead", "forex", "decimal(18,2) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("johead", "shipto", "VARCHAR(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hjohead", "shipto", "VARCHAR(150) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("johead", "wh", "VARCHAR(20) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hjohead", "wh", "VARCHAR(20) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("sostock", "subactivity", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "subactivity", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->execqrynolog("ALTER TABLE iteminfo CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE iteminfo DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE iteminfo CHANGE itemdescription itemdescription LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE iteminfo CHANGE accessories accessories TEXT CHARACTER SET utf8 COLLATE utf8_general_ci");

    $this->coreFunctions->sbcaddcolumn("hqsstock", "voidqty", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qsstock", "voidqty", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqtstock", "voidqty", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qtstock", "voidqty", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("srstock", "voidqty", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsrstock", "voidqty", "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `vthead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(100) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
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
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("vthead", $qry);

    $qry = "CREATE TABLE  `hvthead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(100) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
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
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hvthead", $qry);

    $qry = "CREATE TABLE  `vtstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `refx` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("vtstock", $qry);

    $this->coreFunctions->sbcaddcolumn("vtstock", "isamt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("vtstock", "amt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("vtstock", "ext", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $qry = "CREATE TABLE  `hvtstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `refx` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hvtstock", $qry);

    $this->coreFunctions->sbcaddcolumn("hvtstock", "isamt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hvtstock", "amt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hvtstock", "ext", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $qry = "CREATE TABLE  `vshead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(100) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
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
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("vshead", $qry);

    $qry = "CREATE TABLE  `hvshead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(150) DEFAULT NULL,
      `shipto` varchar(100) DEFAULT NULL,
      `tel` varchar(50) DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
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
      `projectid` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hvshead", $qry);

    $qry = "CREATE TABLE  `vsstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `refx` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("vsstock", $qry);

    $this->coreFunctions->sbcaddcolumn("vsstock", "isamt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("vsstock", "amt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("vsstock", "ext", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $qry = "CREATE TABLE  `hvsstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `refx` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hvsstock", $qry);

    $this->coreFunctions->sbcaddcolumn("hvsstock", "isamt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hvsstock", "amt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hvsstock", "ext", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumn("subproject", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("subproject", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("vthead", "rem", " varchar(250)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("vthead", "sotrno", " int(11)  NOT NULL DEFAULT '0'",  0);
    $this->coreFunctions->sbcaddcolumn("hvthead", "rem", " varchar(250)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("hvthead", "sotrno", " int(11)  NOT NULL DEFAULT '0'",  0);
    $this->coreFunctions->sbcaddcolumn("vshead", "rem", " varchar(250)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("vshead", "sotrno", " int(11)  NOT NULL DEFAULT '0'",  0);
    $this->coreFunctions->sbcaddcolumn("hvshead", "rem", " varchar(250)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("hvshead", "sotrno", " int(11)  NOT NULL DEFAULT '0'",  0);
    $this->coreFunctions->sbcaddcolumn("terms", "terms", " varchar(50)  NOT NULL DEFAULT ''",  1);

    $this->coreFunctions->sbcaddcolumn("client", "activity", "mediumtext",  1);


    $qry = "CREATE TABLE  `delstatus` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `modeofdelivery` varchar(100) DEFAULT '',
      `driver` varchar(300) NOT NULL DEFAULT '',
      `receiveby` varchar(100) DEFAULT '',
      `receivedate` datetime DEFAULT NULL,
      `remarks` varchar(300) NOT NULL DEFAULT '',
      `couriername` varchar(300) NOT NULL DEFAULT '',
      `trackingno` varchar(50) NOT NULL DEFAULT '',
      `releaseby` varchar(100) DEFAULT '',
      `releasedate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("delstatus", $qry);

    $this->coreFunctions->sbcaddcolumn("delstatus", "delcharge", "varchar(20)  NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("client", "groupid", "varchar(250)",  1);

    $this->coreFunctions->sbcaddcolumn("prhead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("prhead", "purtype", "varchar(20)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("prhead", "cname", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("prhead", "budgetreqno", "varchar(50)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hprhead", "deptid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "purtype", "varchar(20)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hprhead", "cname", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hprhead", "budgetreqno", "varchar(50)  NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("prhead", "requestor", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "requestor", "INT(10) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("prhead", "subcontractorid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "subcontractorid", "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pohead", "revision", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "revision", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("johead", "revision", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hjohead", "revision", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("prhead", "revision", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hprhead", "revision", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE  `rfhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `erpno` char(20) NOT NULL,
      `itemid` int(11) NOT NULL DEFAULT '0',
      `invno` varchar(100) DEFAULT NULL,
      `shipdate` datetime DEFAULT NULL,
      `shipreceived` datetime DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `complain` varchar(100) DEFAULT NULL,
      `recommend` varchar(100) DEFAULT NULL,
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
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rfhead", $qry);

    $qry = "CREATE TABLE  `hrfhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `erpno` char(20) NOT NULL,
      `itemid` int(11) NOT NULL DEFAULT '0',
      `invno` varchar(100) DEFAULT NULL,
      `shipdate` datetime DEFAULT NULL,
      `shipreceived` datetime DEFAULT NULL,
      `dateid` datetime DEFAULT NULL,
      `complain` varchar(100) DEFAULT NULL,
      `recommend` varchar(100) DEFAULT NULL,
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
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrfhead", $qry);


    $qry = "CREATE TABLE  `rfstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `refx` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rfstock", $qry);

    $qry = "CREATE TABLE  `hrfstock` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `uom` varchar(15) DEFAULT '',
      `rem` varchar(500) NOT NULL DEFAULT '',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
      `itemid` int(11) NOT NULL DEFAULT '0',
      `whid` int(11) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `refx` int(11) NOT NULL DEFAULT '0',
      `ref` varchar(50) NOT NULL DEFAULT '',
      `projectid` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrfstock", $qry);

    $this->coreFunctions->execqry("ALTER TABLE model_masterfile CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqry("ALTER TABLE model_masterfile DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqry("ALTER TABLE model_masterfile CHANGE model_name model_name mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci");

    $this->coreFunctions->execqry("ALTER TABLE itemcategory CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqry("ALTER TABLE itemcategory DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqry("ALTER TABLE itemcategory CHANGE `name` `name` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci");

    $this->coreFunctions->sbcaddcolumn("rfhead", "reason", "varchar(500)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "reason", "varchar(500)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("rfhead", "yourref", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "yourref", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "ourref", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "ourref", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "sotrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "sotrno", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn('rfhead', 'erpno');
    $this->coreFunctions->sbcdropcolumn('hrfhead', 'erpno');
    $this->coreFunctions->sbcdropcolumn('rfhead', 'itemid');
    $this->coreFunctions->sbcdropcolumn('hrfhead', 'itemid');
    $this->coreFunctions->sbcdropcolumn('rfhead', 'invno');
    $this->coreFunctions->sbcdropcolumn('hrfhead', 'invno');

    $this->coreFunctions->sbcaddcolumn("rfstock", "amt", "decimal(19,6) unsigned NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hrfstock", "amt", "decimal(19,6) unsigned NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("rfstock", "isamt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hrfstock", "isamt", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("rfstock", "ext", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hrfstock", "ext", "decimal(19,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("rfstock", "disc", "varchar(40) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hrfstock", "disc", "varchar(40) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("rfhead", "returndate_supby", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "returndate_sup", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "returndate_supby", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "returndate_sup", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("rfhead", "returndate_custby", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "returndate_cust", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "returndate_custby", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "returndate_cust", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("rfhead", "supplierid", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "supplierid", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("rfhead", "fileby", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "fileby", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "cperson", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "cperson", "varchar(30)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "shipaddress", "varchar(200)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "shipaddress", "varchar(200)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("rfhead", "email", "varchar(150)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "email", "varchar(150)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("rfhead", "clientname", "varchar(150)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "clientname", "varchar(150)  NOT NULL DEFAULT ''");

    // 02.02.2022 - additional details rf
    $this->coreFunctions->sbcaddcolumn("rfhead", "awb", "varchar(15)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "awb", "varchar(15)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "action", "varchar(200)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "action", "varchar(200)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("rfhead", "dateclose", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "dateclose", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("rfhead", "empid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "empid", "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("rfhead", "tel", "varchar(50)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "tel", "varchar(50)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("rfhead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "shipcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("rfhead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "shipid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("rfhead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "billcontactid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("rfhead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "billid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("rfhead", "client", "varchar(15)  NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hrfhead", "client", "varchar(15)  NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("waims_notice", "roleid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("billingaddr", "billdefault", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("billingaddr", "shipdefault", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("contactperson", "billdefault", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("contactperson", "shipdefault", "INT(11) NOT NULL DEFAULT '0'");

    // GLEN 02.14.2022
    $qry = "ALTER TABLE `head` DROP PRIMARY KEY, 
    ADD PRIMARY KEY USING BTREE(`docno`, `station`, `center`);";
    $this->coreFunctions->execqry($qry, 'drop');
    $this->coreFunctions->sbcaddcolumn("pschemehead", "createdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "postedby", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("pschemestock", "encodeddate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("hpschemestock", "encodeddate", "datetime DEFAULT NULL", 1);




    //jac 02142022
    $this->coreFunctions->sbcaddcolumn("qshead", "industry", "varchar(250)  NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqshead", "industry", "varchar(250)  NOT NULL DEFAULT ''", 1);

    //for summit AJ
    $this->coreFunctions->sbcaddcolumn("lahead", "whref", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "whref", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    //for mall
    $this->coreFunctions->sbcaddcolumn("client", "locid", "integer  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "isvat", "tinyint(1)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "istenant", "tinyint(1)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "istmptenant", "tinyint(1)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "enddate", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("client", "isnonvat", "tinyint(1)  NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE  `tenantinfo` (
      `clientid` int(10) unsigned NOT NULL DEFAULT '0',
      `leaserate` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `acrate` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `cusarate` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `drent` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `dac` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `dcusa` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `billtype` varchar(25) NOT NULL DEFAULT '',
      `rentcat` varchar(25) NOT NULL DEFAULT '',
      `emulti` decimal(18,2) NOT NULL DEFAULT '0.00',
      `semulti` decimal(18,2) NOT NULL DEFAULT '0.00',
      `wmulti` decimal(18,2) NOT NULL DEFAULT '0.00',
      `penalty` decimal(18,2) NOT NULL DEFAULT '0.00',
      `mcharge` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `percentsales` int(10) unsigned NOT NULL DEFAULT '0',
      `msales` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `elecrate` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `selecrate` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `waterrate` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `classification` varchar(25) NOT NULL DEFAULT '',
      `eratecat` int(10) unsigned NOT NULL DEFAULT '0',
      `wratecat` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`clientid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("tenantinfo", $qry);

    $this->coreFunctions->sbcaddcolumn("tenantinfo", "tenanttype", "VARCHAR(3) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE  `ratecategory` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `category` varchar(150) NOT NULL DEFAULT '',
      `iselec` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `iswater` tinyint(1) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("ratecategory", $qry);

    $this->coreFunctions->sbcaddcolumn("ratecategory", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ratecategory", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE `loc` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(30) NOT NULL DEFAULT '',
    `name` varchar(150) NOT NULL DEFAULT '',
    `emeter` varchar(25) NOT NULL DEFAULT '',
    `wmeter` varchar(25) NOT NULL DEFAULT '',
    `semeter` varchar(25) NOT NULL DEFAULT '',
    `area` decimal(18,2) NOT NULL DEFAULT '0.00',
    `phase` int(10) unsigned NOT NULL DEFAULT '0',
    `section` int(10) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`code`) USING BTREE,
    KEY `Index_2` (`line`,`phase`,`section`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("loc", $qry);

    $this->coreFunctions->sbcaddcolumn("loc", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("loc", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE  `phase` (
     `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `code` varchar(25) NOT NULL DEFAULT '',
     `name` varchar(150) NOT NULL DEFAULT '',
     PRIMARY KEY (`code`),
     KEY `Index_2` (`line`)
   ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("phase", $qry);

    $this->coreFunctions->sbcaddcolumn("phase", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("phase", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE  `locsection` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(25) NOT NULL DEFAULT '',
    `name` varchar(150) NOT NULL DEFAULT '',
    `phaseid` int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY (`phaseid`,`code`) USING BTREE,
    KEY `Index_2` (`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("locsection", $qry);

    $this->coreFunctions->sbcaddcolumn("locsection", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("locsection", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE  `ocharges` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `description` varchar(50) DEFAULT '',
    `asset` INTEGER DEFAULT 0,
    `liabilities` INTEGER DEFAULT 0,
    `revenue` INTEGER DEFAULT 0,
    `expense` INTEGER DEFAULT 0,
    `isvat` tinyint(1) unsigned DEFAULT '0',
    `amt` decimal(19,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("ocharges", $qry);

    $this->coreFunctions->sbcaddcolumn("ocharges", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("ocharges", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE  `electricrate` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `username` varchar(45) NOT NULL DEFAULT '',
    `dateid` datetime NOT NULL,
    `center` varchar(20) NOT NULL DEFAULT '',
    `categoryid` integer not null default 0,
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("electricrate", $qry);

    $qry = "CREATE TABLE  `selectricrate` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `username` varchar(45) NOT NULL DEFAULT '',
    `dateid` datetime NOT NULL,
    `center` varchar(20) NOT NULL DEFAULT '',
    `categoryid` integer not null default 0,
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("selectricrate", $qry);

    $qry = "CREATE TABLE  `waterrate` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `username` varchar(45) NOT NULL DEFAULT '',
    `dateid` datetime NOT NULL,
    `center` varchar(20) NOT NULL DEFAULT '',
    `categoryid` integer not null default 0,
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("waterrate", $qry);

    $qry = "CREATE TABLE  `escalation` (
    `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `dateid` datetime DEFAULT NULL,
    `remarks` varchar(600) NOT NULL DEFAULT '',
    `isapplied` varchar(25) NOT NULL DEFAULT '',
    `clientid` int(10) unsigned NOT NULL,
    `rate` varchar(50) NOT NULL DEFAULT '',
    `dateapplied` datetime DEFAULT NULL,
    `oldrate` decimal(18,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`line`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("escalation", $qry);

    $this->coreFunctions->sbcaddcolumn("escalation", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("escalation", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("tenantinfo", "secdep", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "secdepmos", "INTEGER NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "ewcharges", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "concharges", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "fencecharge", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "powercharges", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "watercharges", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "housekeeping", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "docstamp", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "consbond", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "emeterdep", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "servicedep", "DECIMAL(18,4) NOT NULL DEFAULT 0");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "rem", "VARCHAR(250) NOT NULL DEFAULT ''");

    $qry = "CREATE TABLE  `electricreading` (
    `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` varchar(45) NOT NULL DEFAULT '',
    `estart` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `eend` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `erate` decimal(19,2) NOT NULL DEFAULT '0.00',
    `bmonth` int(10) unsigned NOT NULL DEFAULT '0',
    `byear` int(10) unsigned NOT NULL DEFAULT '0',
    `center` varchar(45) NOT NULL DEFAULT '',
    `readstart` datetime DEFAULT NULL,
    `readend` datetime DEFAULT NULL,
    `isposted` tinyint(4) unsigned NOT NULL DEFAULT '0',
    `consump` decimal(19,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`line`),
    KEY `Index_client` (`clientid`),
    KEY `Index_3` (`bmonth`,`byear`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("electricreading", $qry);

    $qry = "CREATE TABLE  `helectricreading` (
    `line` bigint(20) unsigned NOT NULL DEFAULT 0,
    `clientid` varchar(45) NOT NULL DEFAULT '',
    `estart` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `eend` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `erate` decimal(19,2) NOT NULL DEFAULT '0.00',
    `bmonth` int(10) unsigned NOT NULL DEFAULT '0',
    `byear` int(10) unsigned NOT NULL DEFAULT '0',
    `center` varchar(45) NOT NULL DEFAULT '',
    `readstart` datetime DEFAULT NULL,
    `readend` datetime DEFAULT NULL,
    `isposted` tinyint(4) unsigned NOT NULL DEFAULT '0',
    `consump` decimal(19,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`line`),
    KEY `Index_client` (`clientid`),
    KEY `Index_3` (`bmonth`,`byear`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("helectricreading", $qry);

    $qry = "CREATE TABLE  `waterreading` (
    `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` varchar(45) NOT NULL DEFAULT '',
    `wstart` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `wend` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `wrate` decimal(19,2) NOT NULL DEFAULT '0.00',
    `bmonth` int(10) unsigned NOT NULL DEFAULT '0',
    `byear` int(10) unsigned NOT NULL DEFAULT '0',
    `center` varchar(45) NOT NULL DEFAULT '',
    `readstart` datetime DEFAULT NULL,
    `readend` datetime DEFAULT NULL,
    `isposted` tinyint(4) unsigned NOT NULL DEFAULT '0',
    `consump` decimal(19,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`line`),
    KEY `Index_client` (`clientid`),
    KEY `Index_3` (`bmonth`,`byear`)
  ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("waterreading", $qry);

    $qry = "CREATE TABLE  `hwaterreading` (
    `line` bigint(20) unsigned NOT NULL DEFAULT 0,
    `clientid` varchar(45) NOT NULL DEFAULT '',
    `wstart` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `wend` decimal(19,4) NOT NULL DEFAULT '0.0000',
    `wrate` decimal(19,2) NOT NULL DEFAULT '0.00',
    `bmonth` int(10) unsigned NOT NULL DEFAULT '0',
    `byear` int(10) unsigned NOT NULL DEFAULT '0',
    `center` varchar(45) NOT NULL DEFAULT '',
    `readstart` datetime DEFAULT NULL,
    `readend` datetime DEFAULT NULL,
    `isposted` tinyint(4) unsigned NOT NULL DEFAULT '0',
    `consump` decimal(19,2) NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`line`),
    KEY `Index_client` (`clientid`),
    KEY `Index_3` (`bmonth`,`byear`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hwaterreading", $qry);

    $qry = "CREATE TABLE  chargesbilling (
      line int(10) unsigned NOT NULL AUTO_INCREMENT,
      cline int(10) unsigned DEFAULT '0',
      amt decimal(19,2) DEFAULT '0.00',
      bmonth int(10) unsigned DEFAULT '0',
      byear int(10) unsigned DEFAULT '0',
      center varchar(45) DEFAULT '',
      clientid int(5) unsigned DEFAULT '0',
      rem varchar(100) DEFAULT '',
      isposted tinyint(4) unsigned DEFAULT '0',
      PRIMARY KEY (line)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("chargesbilling", $qry);

    $qry = "CREATE TABLE  hchargesbilling (
      line int(10) unsigned NOT NULL AUTO_INCREMENT,
      cline int(10) unsigned DEFAULT '0',
      amt decimal(19,2) DEFAULT '0.00',
      bmonth int(10) unsigned DEFAULT '0',
      byear int(10) unsigned DEFAULT '0',
      center varchar(45) DEFAULT '',
      clientid int(5) unsigned DEFAULT '0',
      rem varchar(100) DEFAULT '',
      isposted tinyint(4) unsigned DEFAULT '0',
      PRIMARY KEY (line)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hchargesbilling", $qry);
    //end for mall


    //FMM 03.05.2022
    $this->coreFunctions->sbcaddcolumn("stockinfo", "nvat", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "vatamt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "vatex", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "sramt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "pwdamt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "lessvat", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "discamt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "vipdisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "empdisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "oddisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "smacdisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "pickerid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("stockinfo", "checkerid", "INT(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("hstockinfo", "nvat", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "vatamt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "vatex", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "sramt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "pwdamt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "lessvat", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "discamt", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "vipdisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "empdisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "oddisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "smacdisc", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "pickerid", "INT(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "checkerid", "INT(11) NOT NULL DEFAULT '0'");

    $qry = "CREATE TABLE `qtybracket` (
          `line` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(45) DEFAULT '',
          `minimum` int(11) DEFAULT '0',
          `maximum` int(11) DEFAULT '0',
          `createby` varchar(100) NOT NULL DEFAULT '',
		      `createdate` datetime DEFAULT NULL,
          `editby` varchar(100) NOT NULL DEFAULT '',
		      `editdate` datetime DEFAULT NULL,          
          PRIMARY KEY (`line`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("qtybracket", $qry);

    $this->coreFunctions->sbcaddcolumn("qtybracket", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("qtybracket", "editdate", "datetime DEFAULT NULL", 0);

    $pricebracket = [];
    array_push($pricebracket, array('line' => 1, 'name' => 'R'));
    array_push($pricebracket, array('line' => 2, 'name' => 'W'));
    array_push($pricebracket, array('line' => 3, 'name' => 'A'));
    array_push($pricebracket, array('line' => 4, 'name' => 'B'));
    array_push($pricebracket, array('line' => 5, 'name' => 'C'));
    array_push($pricebracket, array('line' => 6, 'name' => 'D'));
    array_push($pricebracket, array('line' => 7, 'name' => 'E'));
    array_push($pricebracket, array('line' => 8, 'name' => 'F'));
    array_push($pricebracket, array('line' => 9, 'name' => 'G'));

    foreach ($pricebracket as $key => $val) {
      $priceexist = $this->coreFunctions->datareader("select line as value from qtybracket where line=?", [$val['line']]);
      if (!$priceexist) {
        $this->coreFunctions->sbcinsert("qtybracket", [$val]);
      }
    }

    $qry = "CREATE TABLE `pricebracket` (
          `itemid` int(11) NOT NULL DEFAULT '0',
          `groupid` int(11) NOT NULL DEFAULT '0',
          `r` decimal(18,2) NOT NULL DEFAULT '0.00',
          `w` decimal(18,2) NOT NULL DEFAULT '0.00',
          `a` decimal(18,2) NOT NULL DEFAULT '0.00',
          `b` decimal(18,2) NOT NULL DEFAULT '0.00',
          `c` decimal(18,2) NOT NULL DEFAULT '0.00',
          `d` decimal(18,2) NOT NULL DEFAULT '0.00',
          `e` decimal(18,2) NOT NULL DEFAULT '0.00',
          `f` decimal(18,2) NOT NULL DEFAULT '0.00',
          `g` decimal(18,2) NOT NULL DEFAULT '0.00',
          `createby` varchar(100) NOT NULL DEFAULT '',
		      `createdate` datetime DEFAULT NULL,
          `editby` varchar(100) NOT NULL DEFAULT '',
		      `editdate` datetime DEFAULT NULL,
          PRIMARY KEY (`itemid`, `groupid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("pricebracket", $qry);

    $qry = "CREATE TABLE  `poterms` (
      `line` int(11) NOT NULL AUTO_INCREMENT,
      `poterms` MEDIUMTEXT NOT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("poterms", $qry);
    $this->coreFunctions->sbcaddcolumn("poterms", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("poterms", "editdate", "datetime DEFAULT NULL", 0);

    // JIKS 03.07.2022
    $this->coreFunctions->sbcaddcolumn("client", "escalation", "VARCHAR(40) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "contract", "VARCHAR(40) NOT NULL DEFAULT ''", 0);


    $this->coreFunctions->sbcaddcolumn("tenantinfo", "isinactive", "tinyint(1)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("tenantinfo", "isspecialrate", "tinyint(1)  NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("johead", "workdesc", "MEDIUMTEXT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("johead", "rem", "MEDIUMTEXT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("hjohead", "workdesc", "MEDIUMTEXT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("hjohead", "rem", "MEDIUMTEXT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("pohead", "rem", "MEDIUMTEXT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("hpohead", "rem", "MEDIUMTEXT NULL", 1);

    $this->coreFunctions->sbcaddcolumn("pohead", "deldate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "deldate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("pohead", "deladdress", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "deladdress", "VARCHAR(200) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("pohead", "rqtrno", "INT(11)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hpohead", "rqtrno", "INT(11)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("johead", "jrtrno", "INT(11)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hjohead", "jrtrno", "INT(11)  NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("model_masterfile", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("model_masterfile", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("uom", "isinactive", "tinyint(1)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("cmodels", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("cmodels", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("deliverytype", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("deliverytype", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("whrem", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("whrem", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("rolesetup", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("rolesetup", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("rolesetup", "name", "varchar(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("contactperson", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("contactperson", "editdate", "datetime DEFAULT NULL", 0);

    // JIKS [03.15.2022] - Add Ediyby and editdate masterfile
    $this->coreFunctions->sbcaddcolumn("part_masterfile", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("part_masterfile", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("stockgrp_masterfile", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("stockgrp_masterfile", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("frontend_ebrands", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("frontend_ebrands", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("item_class", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("item_class", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("category_masterfile", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("category_masterfile", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("itemcategory", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("itemcategory", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("itemsubcategory", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("itemsubcategory", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("checksetup", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("checksetup", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("exchangerate", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("exchangerate", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("ewtlist", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("ewtlist", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("forex_masterfile", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("forex_masterfile", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("uom", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("uom", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("component", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("component", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("sku", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("sku", "editdate", "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("billingaddr", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("salesgroup", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("salesgroup", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("seminar", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("seminar", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("exhibit", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("exhibit", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("source", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("source", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("empbudget", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("empbudget", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hmsratesetup", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hmsratesetup", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hmscharges", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hmscharges", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hmspackage", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hmspackage", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hmsrooms", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hmsrooms", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("stagesmasterfile", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stagesmasterfile", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("stagesmasterfile", "description", "varchar(1500) not null DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("budget", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("budget", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("stages", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stages", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("substages", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("substages", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("subactivity", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("subactivity", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("subitems", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("subitems", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("activity", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("activity", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("activity", "stageid", "integer NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "editdate", "datetime DEFAULT NULL", 0);

    // 04.11.2022
    $this->coreFunctions->sbcaddcolumn("head", "picker", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("head", "checker", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "agentid", "INT(11) NOT NULL DEFAULT '0'", 0);

    // JIKS - 04.12.2022 
    $qry = "CREATE TABLE `itemgroup` (
		`line` int(11) unsigned NOT NULL AUTO_INCREMENT,
		`clientid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
		`projectid` INTEGER UNSIGNED NOT NULL DEFAULT '0',
		`editby` varchar(15) NOT NULL DEFAULT '',
		`editdate` datetime DEFAULT NULL,
	PRIMARY KEY (`line`,`clientid`,`projectid`)
	)
	ENGINE = MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("itemgroup", $qry);

    $this->coreFunctions->sbcaddcolumn("pahead", "branchid", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpahead", "branchid", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("branchbank", "acnoid", "INT(11) DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn("branchbank", "acno");



    $this->coreFunctions->sbcaddcolumn("blstock", "vat", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hblstock", "vat", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("blstock", "purchase", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hblstock", "purchase", "DECIMAL(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumn("ophead", "tel", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hophead", "tel", "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("calllogs", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("calllogs", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("osstock", "currency", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hosstock", "currency", "varchar(5) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("client", "accountname", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "accountnum", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("client", "isdriver", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("client", "ispassenger", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "storetrno", "INT(11) DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "storetrno", "INT(11) DEFAULT '0'", 0);

    //FRED 4.25.2025
    $this->coreFunctions->sbcaddcolumn("iteminfo", "subgroup", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "company", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "serialno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "icondition", "VARCHAR(100) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "disposaldate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "insurance", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "startinsured", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "endinsured", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "dateacquired", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "purchaserid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "invoiceno", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "invoicedate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "pono", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "podate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "leasedate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "depreyrs", "DECIMAL(10,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "empid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "locid", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "plateno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "vinno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "manufacturer", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "fyear", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "fueltype", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "engine", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "tranfserdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "issuedate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("clientinfo", "room", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("stages", "projectprice", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stages", "completedar", "decimal(19,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "station", "VARCHAR(15) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "station", "VARCHAR(15) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE  `hstock` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `station` varchar(30) NOT NULL DEFAULT '',
      `linex` int(11) unsigned NOT NULL DEFAULT '0',
      `refx` int(11) unsigned NOT NULL DEFAULT '0',
      `itemid` int(11) unsigned NOT NULL DEFAULT '0',
      `bcode` varchar(50) NOT NULL DEFAULT '',
      `itemname` varchar(255) NOT NULL DEFAULT '',
      `uom` varchar(15) NOT NULL DEFAULT '',
      `wh` varchar(30) NOT NULL DEFAULT '',
      `disc` varchar(40) NOT NULL DEFAULT '',
      `rem` varchar(255) NOT NULL DEFAULT '',
      `rrcost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `cost` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `rrqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `isamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `amt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `isqty` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `iss` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `ext` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `qa` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `ref` varchar(20) NOT NULL DEFAULT '',
      `putrno` int(11) unsigned NOT NULL DEFAULT '0',
      `puline` int(11) unsigned NOT NULL DEFAULT '0',
      `void` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `commission` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `comm` varchar(50) NOT NULL DEFAULT '',
      `expiration` datetime DEFAULT NULL,
      `rtrno` int(11) unsigned NOT NULL DEFAULT '0',
      `rline` int(11) unsigned NOT NULL DEFAULT '0',
      `consummable` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `agentid` int(11) unsigned NOT NULL DEFAULT '0',
      `btn` varchar(50) NOT NULL DEFAULT '',
      `expi` varchar(20) NOT NULL DEFAULT '',
      `lot` varchar(20) NOT NULL DEFAULT '',
      `nvat` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `vatamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `vatex` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `sramt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `discamt` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `serial` varchar(45) NOT NULL DEFAULT '',
      `isok` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `isok2` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `dateid` datetime DEFAULT NULL,
      `iscomponent` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `loc` varchar(45) NOT NULL DEFAULT '',
      `freight` varchar(45) NOT NULL DEFAULT '',
      `status` char(1) NOT NULL DEFAULT 'P',
      `start_time` time DEFAULT NULL,
      `end_time` time DEFAULT NULL,
      `pwdamt` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `isemployee` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `iscomp` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `screg` varchar(45) NOT NULL DEFAULT '',
      `scsenior` varchar(45) NOT NULL DEFAULT '',
      `isdiplomat` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `issenior2` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `userid` varchar(100) NOT NULL DEFAULT '',
      `createdate` datetime DEFAULT NULL,
      `lessvat` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `vipdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `empdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `oddisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `smacdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `issm` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `htrno` int(11) unsigned NOT NULL DEFAULT '0',
      `hline` int(11) unsigned NOT NULL DEFAULT '0',
      `isoverride` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `namt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `orno` varchar(45) NOT NULL DEFAULT '',
      `promodesc` varchar(255) NOT NULL DEFAULT '',
      `promoby` varchar(15) NOT NULL DEFAULT '',
      `gcno` varchar(25) NOT NULL DEFAULT '',
      `prodcycle` varchar(15) NOT NULL DEFAULT '',
      `rqtrno` int(10) unsigned NOT NULL DEFAULT '0',
      `rqline` int(10) unsigned NOT NULL DEFAULT '0',
      `markup` varchar(45) NOT NULL DEFAULT '',
      `isfree` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `srp` decimal(18,6) NOT NULL DEFAULT '0.000000',
      PRIMARY KEY (`trno`,`line`,`station`) USING BTREE,
      KEY `Index_ItemID_WHID` (`itemid`,`wh`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hstock", $qry);

    $qry = "CREATE TABLE  `hhead` (
      `docno` varchar(20) NOT NULL DEFAULT '',
      `postdate` datetime DEFAULT NULL,
      `station` varchar(30) NOT NULL DEFAULT '',
      `center` varchar(45) NOT NULL DEFAULT '',
      `fullp` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `seq` int(11) unsigned NOT NULL DEFAULT '0',
      `bref` varchar(5) NOT NULL DEFAULT '',
      `doc` char(5) NOT NULL DEFAULT '',
      `lockuser` varchar(100) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `openby` varchar(100) NOT NULL DEFAULT '',
      `voiddate` datetime DEFAULT NULL,
      `loss` varchar(150) NOT NULL DEFAULT '',
      `pos` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `amt` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `cash` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `cheque` decimal(18,2) NOT NULL DEFAULT '0.00',
      `card` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `nvat` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `vatamt` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `vatex` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `cr` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `transtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `change` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `tendered` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `sramt` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `discamt` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `ordertype` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `gcount` int(10) unsigned NOT NULL DEFAULT '0',
      `lp` decimal(18,2) NOT NULL DEFAULT '0.00',
      `transtype` varchar(40) NOT NULL DEFAULT '',
      `dldate` datetime DEFAULT NULL,
      `uploaddate` datetime DEFAULT NULL,
      `clientid` int(11) unsigned NOT NULL DEFAULT '0',
      `clientname` varchar(255) NOT NULL DEFAULT '',
      `address` varchar(150) NOT NULL DEFAULT '',
      `shipto` varchar(150) NOT NULL DEFAULT '',
      `tel` varchar(50) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `due` datetime DEFAULT NULL,
      `terms` varchar(50) NOT NULL DEFAULT '',
      `wh` varchar(50) NOT NULL DEFAULT '',
      `rem` varchar(1000) NOT NULL DEFAULT '',
      `cur` varchar(4) NOT NULL DEFAULT '',
      `forex` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `branch` varchar(30) NOT NULL DEFAULT '',
      `contra` varchar(30) NOT NULL DEFAULT '',
      `ourref` varchar(1000) NOT NULL DEFAULT '',
      `agentid` int(11) unsigned NOT NULL DEFAULT '0',
      `yourref` varchar(1000) NOT NULL DEFAULT '',
      `waybill` varchar(50) NOT NULL DEFAULT '',
      `deldate` datetime DEFAULT NULL,
      `billdate` datetime DEFAULT NULL,
      `billway` varchar(200) NOT NULL DEFAULT '',
      `prepared` varchar(50) NOT NULL DEFAULT '',
      `approved` varchar(50) NOT NULL DEFAULT '',
      `down` decimal(19,6) NOT NULL DEFAULT '0.000000',
      `datefull` datetime DEFAULT NULL,
      `pmode` varchar(50) NOT NULL DEFAULT '',
      `acctno` varchar(1000) NOT NULL DEFAULT '',
      `approval` varchar(45) NOT NULL DEFAULT '',
      `batch` varchar(50) NOT NULL DEFAULT '',
      `stockcount` decimal(19,4) NOT NULL DEFAULT '0.0000',
      `isok` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `isok2` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `pointsrate` varchar(45) NOT NULL DEFAULT '',
      `client` varchar(15) NOT NULL DEFAULT '',
      `voucher` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `userid` varchar(100) NOT NULL DEFAULT '',
      `pwdamt` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `empdisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `srcount` int(11) unsigned NOT NULL DEFAULT '0',
      `debit` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `eplus` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `smac` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `onlinedeals` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `vipdisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `oddisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `smacdisc` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `billnumber` varchar(250) NOT NULL DEFAULT '',
      `htable` varchar(45) NOT NULL DEFAULT '',
      `bankname` varchar(45) NOT NULL DEFAULT '',
      `empid` int(11) NOT NULL DEFAULT '0',
      `timein` datetime DEFAULT NULL,
      `printtime` datetime DEFAULT NULL,
      `cid` int(11) NOT NULL DEFAULT '0',
      `isreprinted` tinyint(1) unsigned NOT NULL DEFAULT '0',
      `receivedby` varchar(30) NOT NULL DEFAULT '',
      `deliveredby` varchar(30) NOT NULL DEFAULT '',
      `terminalid` varchar(200) NOT NULL DEFAULT '',
      `voucherno` varchar(1000) NOT NULL DEFAULT '',
      `checktype` varchar(1000) NOT NULL DEFAULT '',
      `postedby` varchar(100) NOT NULL DEFAULT '',
      `gcamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `itemcount` int(11) NOT NULL DEFAULT '0',
      `invdate` varchar(45) NOT NULL DEFAULT '',
      `lessvat` decimal(18,4) NOT NULL DEFAULT '0.0000',
      `cardtype` varchar(1000) NOT NULL DEFAULT '',
      `isextracted` tinyint(2) NOT NULL DEFAULT '0',
      `voidby` varchar(50) NOT NULL DEFAULT '',
      `webtrno` int(11) NOT NULL DEFAULT '0',
      `picker` varchar(45) NOT NULL DEFAULT '',
      `checker` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`docno`,`station`,`center`) USING BTREE,
      KEY `Index_Trno` (`trno`),
      KEY `Index_Doc` (`doc`),
      KEY `Index_Bref` (`bref`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hhead", $qry);

    $this->coreFunctions->sbcaddcolumn("stages", "completedar", "varchar(10) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("psubactivity", "qty", "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfo", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("proformainv", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("proformainv", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("qscalllogs", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("qscalllogs", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hqscalllogs", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqscalllogs", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("sqcomments", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("sqcomments", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("delstatus", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("delstatus", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "department", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "prepared", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "tmpref", "varchar(45) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "department", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "prepared", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "tmpref", "varchar(45) NOT NULL DEFAULT ''", 0);


    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "itemdesc", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "unit", "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "purpose", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "requestorname", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "dateneeded", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "specs", "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "itemdesc", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "unit", "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "purpose", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "requestorname", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "dateneeded", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "specs", "varchar(100) NOT NULL DEFAULT ''", 0);

    //jan2x 07/13/2022
    $this->coreFunctions->sbcaddcolumn("client", "collectorid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("sqhead", "businesstype", "varchar(250) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hsqhead", "businesstype", "varchar(250) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("sqhead", "tin", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hsqhead", "tin", "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("item_log", "doc", "varchar(45) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "otherleadtime", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "otherleadtime", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "otherleadtime", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "otherleadtime", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("oshead", "deadline", "DATETIME DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumn("hoshead", "deadline", "DATETIME DEFAULT NULL");

    $this->coreFunctions->sbcaddcolumn("sqhead", "delcharge", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsqhead", "delcharge", "decimal(19,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sshead", "delcharge", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsshead", "delcharge", "decimal(19,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "isencashment", "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("lahead", "isonlineencashment", "tinyint(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("glhead", "isencashment", "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "isonlineencashment", "tinyint(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("client", "isvatzerorated", "tinyint(1)  NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("client", "isnotarizedcert", "tinyint(1)  NOT NULL DEFAULT '0'");


    // JIKS 05-18-2022
    $this->coreFunctions->sbcaddcolumn("iteminfo", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "editdate", "datetime DEFAULT NULL", 0);


    $this->coreFunctions->sbcaddcolumn("sohead", "mlcp_freight", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hsohead", "mlcp_freight", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("sohead", "ms_freight", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsohead", "ms_freight", "decimal(19,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pohead", "whreceiver", "int(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "whreceiver", "int(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("johead", "whreceiver", "int(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjohead", "whreceiver", "int(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "crref", "varchar(50)  NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "crref", "varchar(50)  NOT NULL DEFAULT ''", 0);

    $qry = "
    CREATE TABLE `client_picture` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(20) NOT NULL DEFAULT '0',
      `title` varchar(2000) NOT NULL DEFAULT '',
      `picture` varchar(300) NOT NULL DEFAULT '',
      `editby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_client_picture` (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("client_picture", $qry);

    $this->coreFunctions->sbcaddcolumn("cdhead", "prtrno", "INT(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hcdhead", "prtrno", "INT(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("projectmasterfile", "comrate", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    //JAC 06022022
    $this->coreFunctions->sbcdropcolumn("sqhead", "businesstype");
    $this->coreFunctions->sbcdropcolumn("hsqhead", "businesstype");
    $this->coreFunctions->sbcdropcolumn("sqhead", "tin");
    $this->coreFunctions->sbcdropcolumn("hsqhead", "tin");

    $this->coreFunctions->sbcaddcolumn("client", "isoverride", "TINYINT(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "delcharge", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("delstatus", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("salesgroup", "agentid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("incentives", "phagent", "int(10) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `pheadincentive` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `agentid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `projectid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `delcharge` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `insurance` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `comamt` DECIMAL(18,2) NOT NULL DEFAULT 0,
      `releaseby` VARCHAR(45) NOT NULL DEFAULT '',
      `releasedate` DATETIME DEFAULT NULL
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("pheadincentive", $qry);

    $this->coreFunctions->sbcaddcolumn("oshead", "customerid", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hoshead", "customerid", "int(11) NOT NULL DEFAULT '0'");

    $systemtype = $this->companysetup->getsystemtype($config['params']);
    if ($systemtype == 'CAIMS') {
      $this->coreFunctions->sbcaddcolumn("sostock", "uom", "varchar(15) NOT NULL DEFAULT ''", 1);
      $this->coreFunctions->sbcaddcolumn("hsostock", "uom", "varchar(15) NOT NULL DEFAULT ''", 1);

      $this->coreFunctions->sbcaddcolumn("sostock", "subactid", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
      $this->coreFunctions->sbcaddcolumn("hsostock", "subactid", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

      $this->coreFunctions->sbcaddcolumn("johead", "start", "DATETIME DEFAULT NULL", 1);
      $this->coreFunctions->sbcaddcolumn("hjohead", "start", "DATETIME DEFAULT NULL", 1);

      $this->coreFunctions->sbcaddcolumn("bastock", "subactid", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
      $this->coreFunctions->sbcaddcolumn("hbastock", "subactid", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    }

    $this->coreFunctions->sbcaddcolumn("lahead", "hacno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "hacnoname", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("glhead", "hacno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "hacnoname", "VARCHAR(100) NOT NULL DEFAULT ''", 1);


    $this->coreFunctions->sbcaddcolumn("pmhead", "ocp", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpmhead", "ocp", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("terms", "isdp", "tinyint(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("cntnum", "yr", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("transnum", "yr", "int(11) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("pohead", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpohead", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("jbhead", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjbhead", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn("delstatus", "insurance");

    // jiks 06.22.2022
    $qry = "CREATE TABLE `itemprice` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `itemid` int(11) NOT NULL DEFAULT '0',
    `startqty` decimal(18,6) NOT NULL DEFAULT '0',
    `endqty` decimal(18,6) NOT NULL DEFAULT '0',
    `amt` decimal(18,6) unsigned NOT NULL DEFAULT '0.000000',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(100) NOT NULL DEFAULT '',
    PRIMARY KEY (`line`,`itemid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("itemprice", $qry);

    // jiks 06.29.2022
    $qry = " CREATE TABLE `clientsano` (
  `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `clientid` INT(11) NOT NULL DEFAULT '0',
  `sano` VARCHAR(45) NOT NULL DEFAULT '',
  `createby` VARCHAR(100) NOT NULL DEFAULT '',
  `createdate` DATETIME DEFAULT NULL,
  `editby` VARCHAR(100) NOT NULL DEFAULT '',
  `editdate` DATETIME DEFAULT NULL,
  PRIMARY KEY (line),
  KEY IndexLine (line),
  KEY IndexClientid (clientid)
  ) ENGINE = MYISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("clientsano", $qry);

    $this->coreFunctions->sbcaddcolumn("lahead", "sano", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("glhead", "sano", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "sono", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "sono", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("serialout", "rftrno", "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("serialout", "rfline", "int(11) NOT NULL DEFAULT '0'");


    $this->coreFunctions->sbcaddcolumn("johead", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hjohead", "insurance", "decimal(19,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("rfhead", "others", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "others", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("rfhead", "rfnno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "rfnno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("rfstock", "cost", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hrfstock", "cost", "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `particulars` (
    `trno` int(11) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `rem` mediumtext,
    `amount` decimal(18,2) NOT NULL DEFAULT '0.00',
    `createby` varchar(150) NOT NULL DEFAULT '',
    `createdate` datetime DEFAULT NULL,
    `editby` varchar(150) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`,`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("particulars", $qry);

    $qry = "CREATE TABLE  `hparticulars` (
    `trno` int(11) NOT NULL DEFAULT '0',
    `line` int(11) NOT NULL DEFAULT '0',
    `rem` mediumtext,
    `amount` decimal(18,2) NOT NULL DEFAULT '0.00',
    `createby` varchar(150) NOT NULL DEFAULT '',
    `createdate` datetime DEFAULT NULL,
    `editby` varchar(150) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`,`line`)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hparticulars", $qry);

    // JIKS - 07.14.2022
    $this->coreFunctions->sbcaddcolumn("headinfotrans", "dp", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "dp", "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "cod", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "cod", "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "outstanding", "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "outstanding", "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    #kim: 2022.07.20
    $this->coreFunctions->sbcaddcolumn("lahead", "paymode", "CHAR(1) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "paymode", "CHAR(1) NOT NULL DEFAULT ''", 0);

    //jac 07212022
    $qry = "CREATE TABLE `pvitem` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `itemid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `amt` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `poref` VARCHAR(45) NOT NULL DEFAULT '',
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `refx` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `linex` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `createby` VARCHAR(200) NOT NULL,
      `createdate` DATETIME DEFAULT NULL,
      `editby` VARCHAR(200) NOT NULL,
      `editdate` DATETIME DEFAULT NULL,
      PRIMARY KEY (`trno`, `line`),
      INDEX `Index_2`(`itemid`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("pvitem", $qry);

    $qry = "CREATE TABLE `hpvitem` (
      `trno` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `itemid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `amt` DECIMAL(18,6) NOT NULL DEFAULT 0,
      `poref` VARCHAR(45) NOT NULL DEFAULT '',
      `ref` VARCHAR(45) NOT NULL DEFAULT '',
      `refx` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `linex` INTEGER UNSIGNED NOT NULL DEFAULT 0,
      `createby` VARCHAR(200) NOT NULL,
      `createdate` DATETIME DEFAULT NULL,
      `editby` VARCHAR(200) NOT NULL,
      `editdate` DATETIME DEFAULT NULL,
      PRIMARY KEY (`trno`, `line`),
      INDEX `Index_2`(`itemid`)
    )
    ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("hpvitem", $qry);


    // JIKS [07.22.2022] - oracle code request

    $qry = "CREATE TABLE  `oqhead` LIKE `pohead` ";
    $this->coreFunctions->sbccreatetable("oqhead", $qry);

    $qry = "CREATE TABLE  `hoqhead` LIKE `hpohead` ";
    $this->coreFunctions->sbccreatetable("hoqhead", $qry);

    $qry = "CREATE TABLE  `oqstock` LIKE `postock` ";
    $this->coreFunctions->sbccreatetable("oqstock", $qry);

    $qry = "CREATE TABLE  `hoqstock` LIKE `hpostock` ";
    $this->coreFunctions->sbccreatetable("hoqstock", $qry);

    $this->coreFunctions->sbcaddcolumn("hcdstock", "isoq", "tinyint(1) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("branchstation", "projectid", "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `piprocess` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `stageid` int(10) unsigned NOT NULL,
      `percentage` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`,`stageid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("piprocess", $qry);

    $qry = "CREATE TABLE  `hpiprocess` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `stageid` int(10) unsigned NOT NULL,
      `percentage` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`,`stageid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hpiprocess", $qry);

    $this->coreFunctions->sbcdropcolumn("pihead", "address");
    $this->coreFunctions->sbcdropcolumn("pihead", "shipto");
    $this->coreFunctions->sbcdropcolumn("pihead", "tel");
    $this->coreFunctions->sbcdropcolumn("pihead", "terms");
    $this->coreFunctions->sbcdropcolumn("pihead", "cur");
    $this->coreFunctions->sbcdropcolumn("pihead", "forex");
    $this->coreFunctions->sbcdropcolumn("pihead", "branch");
    $this->coreFunctions->sbcdropcolumn("pihead", "agent");
    $this->coreFunctions->sbcdropcolumn("pihead", "isimport");
    $this->coreFunctions->sbcdropcolumn("pihead", "delby");
    $this->coreFunctions->sbcdropcolumn("pihead", "vattype");
    $this->coreFunctions->sbcdropcolumn("pihead", "isapproved");
    $this->coreFunctions->sbcdropcolumn("pihead", "labor");
    $this->coreFunctions->sbcdropcolumn("pihead", "overhead");

    $this->coreFunctions->sbcdropcolumn("hpihead", "address");
    $this->coreFunctions->sbcdropcolumn("hpihead", "shipto");
    $this->coreFunctions->sbcdropcolumn("hpihead", "tel");
    $this->coreFunctions->sbcdropcolumn("hpihead", "terms");
    $this->coreFunctions->sbcdropcolumn("hpihead", "cur");
    $this->coreFunctions->sbcdropcolumn("hpihead", "forex");
    $this->coreFunctions->sbcdropcolumn("hpihead", "branch");
    $this->coreFunctions->sbcdropcolumn("hpihead", "agent");
    $this->coreFunctions->sbcdropcolumn("hpihead", "isimport");
    $this->coreFunctions->sbcdropcolumn("hpihead", "delby");
    $this->coreFunctions->sbcdropcolumn("hpihead", "vattype");
    $this->coreFunctions->sbcdropcolumn("hpihead", "isapproved");
    $this->coreFunctions->sbcdropcolumn("hpihead", "labor");
    $this->coreFunctions->sbcdropcolumn("hpihead", "overhead");

    $this->coreFunctions->sbcdropcolumn("pdhead", "address");
    $this->coreFunctions->sbcdropcolumn("pdhead", "shipto");
    $this->coreFunctions->sbcdropcolumn("pdhead", "tel");
    $this->coreFunctions->sbcdropcolumn("pdhead", "terms");
    $this->coreFunctions->sbcdropcolumn("pdhead", "cur");
    $this->coreFunctions->sbcdropcolumn("pdhead", "forex");
    $this->coreFunctions->sbcdropcolumn("pdhead", "branch");
    $this->coreFunctions->sbcdropcolumn("pdhead", "agent");
    $this->coreFunctions->sbcdropcolumn("pdhead", "isimport");
    $this->coreFunctions->sbcdropcolumn("pdhead", "delby");
    $this->coreFunctions->sbcdropcolumn("pdhead", "vattype");
    $this->coreFunctions->sbcdropcolumn("pdhead", "isapproved");
    $this->coreFunctions->sbcdropcolumn("pdhead", "delivdate");
    $this->coreFunctions->sbcdropcolumn("pdhead", "pi");
    $this->coreFunctions->sbcdropcolumn("pdhead", "prc");

    $this->coreFunctions->sbcdropcolumn("hpdhead", "address");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "shipto");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "tel");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "terms");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "cur");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "forex");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "branch");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "agent");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "isimport");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "delby");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "vattype");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "isapproved");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "delivdate");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "pi");
    $this->coreFunctions->sbcdropcolumn("hpdhead", "prc");


    $this->coreFunctions->sbcaddcolumn("pihead", "itemid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pihead", "uom", "varchar(20) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("pihead", "qty", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpihead", "itemid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpihead", "uom", "varchar(20) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hpihead", "qty", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpihead", "refx", "int(11) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("pdhead", "itemid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pdhead", "uom", "varchar(20) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pdhead", "qty", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpdhead", "itemid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpdhead", "uom", "varchar(20) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpdhead", "qty", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pdhead", "pitrno", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpdhead", "pitrno", "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pistock", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpistock", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pdstock", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpdstock", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "isvalid", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "ovaliddate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "isvalid", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "ovaliddate", "datetime", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfo", "isvalid", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "ovaliddate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "isvalid", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "ovaliddate", "datetime", 0);

    $this->coreFunctions->sbcaddcolumn("qscalllogs", "probability", "VARCHAR(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hqscalllogs", "probability", "VARCHAR(10) NOT NULL DEFAULT ''", 0);

    // FMM 08.08.2022
    $qry = " CREATE TABLE `cntnumtodo` (
      `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `trno` INT(11) NOT NULL DEFAULT '0',
      `createby` INT(11) NOT NULL DEFAULT '0',
      `createdate` DATETIME DEFAULT NULL,
      `clientid` INT(11) NOT NULL DEFAULT '0',
      `userid` INT(11) NOT NULL DEFAULT '0',
      `seendate` DATETIME DEFAULT NULL,
      `donedate` DATETIME DEFAULT NULL,
      PRIMARY KEY (line),
      KEY IndexLine (line),
      KEY IndexTrno (trno),
      KEY IndexCreateby (createby),
      KEY IndexClientid (clientid),
      KEY IndexUserid (userid)
      ) ENGINE = MYISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("cntnumtodo", $qry);

    $qry = " CREATE TABLE `transnumtodo` (
      `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `trno` INT(11) NOT NULL DEFAULT '0',
      `createby` INT(11) NOT NULL DEFAULT '0',
      `createdate` DATETIME DEFAULT NULL,
      `clientid` INT(11) NOT NULL DEFAULT '0',
      `userid` INT(11) NOT NULL DEFAULT '0',
      `seendate` DATETIME DEFAULT NULL,
      `donedate` DATETIME DEFAULT NULL,
      PRIMARY KEY (line),
      KEY IndexLine (line),
      KEY IndexTrno (trno),
      KEY IndexCreateby (createby),
      KEY IndexClientid (clientid),
      KEY IndexUserid (userid)
      ) ENGINE = MYISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("transnumtodo", $qry);

    $this->coreFunctions->sbcaddcolumn("cntnumtodo", "createby", "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("transnumtodo", "createby", "VARCHAR(50) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("lahead", "pdtrno", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "pdtrno", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("oshead", "yourref", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hoshead", "yourref", "VARCHAR(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("trstock", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("htrstock", "stageid", "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sostock", "pdqa", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "pdqa", "DECIMAL (18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sohead", "sotype", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsohead", "sotype", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pdhead", "sotrno", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("pdhead", "soline", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpdhead", "sotrno", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpdhead", "soline", "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("piprocess", "itemid", "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpiprocess", "itemid", "int(10) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->createindex("billingaddr", "Index_clientid", ['clientid']);
    $this->coreFunctions->createindex("contactperson", "Index_clientid", ['clientid']);

    $this->coreFunctions->sbcaddcolumn("pmhead", "conduration", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hpmhead", "conduration", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("pohead", "yourref", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hpohead", "yourref", "VARCHAR(50) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("sohead", "yourref", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hsohead", "yourref", "VARCHAR(50) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("lastock", "kgs", "DECIMAL (18,10) NOT NULL DEFAULT '0.0000000000'");
    $this->coreFunctions->sbcaddcolumn("glstock", "kgs", "DECIMAL (18,10) NOT NULL DEFAULT '0.0000000000'");
    $this->coreFunctions->sbcaddcolumn("sostock", "kgs", "DECIMAL (18,10) NOT NULL DEFAULT '0.0000000000'");
    $this->coreFunctions->sbcaddcolumn("hsostock", "kgs", "DECIMAL (18,10) NOT NULL DEFAULT '0.0000000000'");

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "leadtimesettings", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "leadtimesettings", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfo", "leadtimesettings", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "leadtimesettings", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("ophead", "ourref", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hophead", "ourref", "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("qshead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "crline", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hqshead", "overdue", "decimal(18,2) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcdropcolumn("lahead", "isconfirmed");
    $this->coreFunctions->sbcdropcolumn("glhead", "isconfirmed");

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "isconfirmed", "TINYINT(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "isconfirmed", "TINYINT(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "isacknowledged", "TINYINT(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "isacknowledged", "TINYINT(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcdropcolumn("stockinfo", "intransit");
    $this->coreFunctions->sbcdropcolumn("hstockinfo", "intransit");

    $this->coreFunctions->sbcaddcolumn("stockinfo", "intransit", "TINYINT(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "intransit", "TINYINT(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "ischqreleased", "TINYINT(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "ischqreleased", "TINYINT(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("cntnuminfo", "ispaid", "TINYINT(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("hcntnuminfo", "ispaid", "TINYINT(1) NOT NULL DEFAULT '0'");


    $qry = "CREATE TABLE  `ipsetup` (
      `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
      `ipaddress` varchar(50) NOT NULL DEFAULT '',
      `createdate` DATETIME DEFAULT NULL,
      `createby` varchar(50) NOT NULL DEFAULT '', 
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("ipsetup", $qry);

    $this->coreFunctions->sbcaddcolumn("lahead", "sidate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "sidate", "DATETIME DEFAULT NULL", 0);

    // RFG [09.06.2022] - Cash Liquidation Form Module

    $qry = "CREATE TABLE  `lqhead` LIKE `oqhead` ";
    $this->coreFunctions->sbccreatetable("lqhead", $qry);

    $qry = "CREATE TABLE  `hlqhead` LIKE `hoqhead` ";
    $this->coreFunctions->sbccreatetable("hlqhead", $qry);

    $qry = "CREATE TABLE  `lqstock` LIKE `oqstock` ";
    $this->coreFunctions->sbccreatetable("lqstock", $qry);

    $qry = "CREATE TABLE  `hlqstock` LIKE `hoqstock` ";
    $this->coreFunctions->sbccreatetable("hlqstock", $qry);

    //jac - remove fields added by jiks
    $this->coreFunctions->sbcdropcolumn("lahead", "sidate");
    $this->coreFunctions->sbcdropcolumn("glhead", "sidate");

    $this->coreFunctions->sbcaddcolumn("ladetail", "lastdp", "TINYINT(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "lastdp", "TINYINT(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "dp", "DECIMAL(18,2) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "dp", "DECIMAL(18,2) unsigned NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "cod", "DECIMAL(18,2) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "cod", "DECIMAL(18,2) unsigned NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumn("headinfotrans", "outstanding", "DECIMAL(18,2) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "outstanding", "DECIMAL(18,2) unsigned NOT NULL DEFAULT '0'", 1);


    // JAN 10/04/2022
    $this->coreFunctions->sbcaddcolumn("component", "amount", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);


    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "customercur", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "vendorcur", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "vendorcostprice", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "quantity", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "freight", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "markup", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "exchangerate", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "customercur", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "vendorcur", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "vendorcostprice", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "quantity", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "freight", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "markup", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "exchangerate", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumn("iteminfo", "customercur", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "vendorcur", "varchar(5) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "vendorcostprice", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "quantity", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "freight", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "markup", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);
    $this->coreFunctions->sbcaddcolumn("iteminfo", "exchangerate", "decimal(18,6) NOT NULL DEFAULT '0.000000'", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "driver", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("lahead", "plateno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "driver", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "plateno", "VARCHAR(50) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("item", "insurance", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("srstock", "insurance", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsrstock", "insurance", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "insurance", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "insurance", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("attendee", "officialwebsite", "VARCHAR(300) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("attendee", "officialemail", "VARCHAR(300) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("client", "officialemail", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("client", "officialwebsite", "varchar(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("coa", "incomegrp", "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("coa", "isshow", "TINYINT(1) NOT NULL DEFAULT '1'");
    $this->coreFunctions->sbcaddcolumn("coa", "iscompute", "TINYINT(1) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumn("coa", "isparenttotal", "TINYINT(1) NOT NULL DEFAULT '1'");


    $this->coreFunctions->sbcaddcolumn("item", "isofficesupplies", "TINYINT(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("prstock", "rqty", "DECIMAL (19,6) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hprstock", "rqty", "DECIMAL (19,6) NOT NULL DEFAULT '0'", 1);


    $this->coreFunctions->execqrynolog("ALTER TABLE lahead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE lahead DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE lahead CHANGE rem rem VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci");

    $this->coreFunctions->execqrynolog("ALTER TABLE glhead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE glhead DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci");
    $this->coreFunctions->execqrynolog("ALTER TABLE glhead CHANGE rem rem VARCHAR(500) CHARACTER SET utf8 COLLATE utf8_general_ci");


    //FMM - for reentry inv aaccounts
    $this->coreFunctions->sbcaddcolumn("glhead", "isreentryinv", "TINYINT(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("hqshead", "sgdrate", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("qshead", "sgdrate", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsrhead", "sgdrate", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("srhead", "sgdrate", "decimal(18,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("qshead", "agentcno", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hqshead", "agentcno", "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("ophead", "designation", "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hophead", "designation", "VARCHAR(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("lastock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("ladetail", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("gldetail", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("voiddetail", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hvoiddetail", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);


    $this->coreFunctions->sbcaddcolumn("postock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpostock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("sostock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hsostock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("pcstock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hpcstock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("cdstock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hcdstock", "sortline", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("item", "salesreturn", "varchar(45) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("rfhead", "invoiceno", "VARCHAR(30) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hrfhead", "invoiceno", "VARCHAR(30) NOT NULL DEFAULT ''", 0);


    $this->coreFunctions->sbcaddcolumn("uom", "isdefault", "TINYINT(1) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumn("clientsano", "issa", "tinyint(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("clientsano", "issvs", "tinyint(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("clientsano", "ispo", "tinyint(1) NOT NULL DEFAULT '0'", 1);


    $this->coreFunctions->sbcaddcolumn("prhead", "svsno", "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hprhead", "svsno", "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("prhead", "pono", "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hprhead", "pono", "INT(11) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumn("prhead", "potype", "VARCHAR(10) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hprhead", "potype", "VARCHAR(10) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("lahead", "rem", "VARCHAR(1000) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("glhead", "rem", "VARCHAR(1000) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("lahead", "cur2", "VARCHAR(3) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "cur2", "VARCHAR(3) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "forex2", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "forex2", "decimal(18,6) NOT NULL DEFAULT '0'", 0);
  } //end function



  //FRED 05.01.2021
  public function lowecasefieldname()
  {

    //FRED 01.14.2021
    $tables = ["rrstatus", "lahead", "client"];
    foreach ($tables as $k => $val) {
      $table = $this->coreFunctions->opentable("show full columns FROM " . $val);
      foreach ($table as $t => $v) {
        if ($v->Field != strtolower($v->Field)) {
          $default = $v->Default;
          if (Str::contains($v->Type, ['varchar'])) {
            $default = "''";
          } elseif (Str::contains($v->Type, ['datetime'])) {
            $default = " null";
          } elseif (Str::contains($v->Type, ['char'])) {
            $default = "''";
          }
          $nullval = '';
          if ($v->Null == 'NO') {
            $nullval = ' not null';
          }
          $this->coreFunctions->execqry("ALTER TABLE " . $val . " CHANGE COLUMN " . $v->Field . " " . strtolower($v->Field) . " " . $v->Type . $nullval . " default " . $default);
        }
      }
    }
  }

  // GLEN 02.08.22
  public function modifyLengthField()
  {
    return; //2025.06.25

    ini_set('max_execution_time', 0);
    $tables = $this->coreFunctions->opentable("show tables"); // get all table names
    foreach ($tables as $key => $table) {
      foreach ($table as $k => $tablename) {

        switch ($tablename) {
          case 'useraccess':
            $structure =  $this->coreFunctions->opentable("show full columns FROM " . $tablename . "
              where Field = 'userid'"); // get all table structure
            foreach ($structure as $skey => $sval) {
              $this->coreFunctions->execqry("update $tablename set $sval->Field = 0 WHERE $sval->Field is null OR $sval->Field = ''", "update");
              if ($sval->Field == "userid") {
                $qry = "ALTER TABLE $tablename DROP PRIMARY KEY";
                $this->coreFunctions->execqry($qry, 'drop');
                $qry = "ALTER TABLE $tablename ADD PRIMARY KEY USING BTREE(`userid`, `username`);";
                $this->coreFunctions->execqry($qry, 'addkey');
                $this->coreFunctions->sbcaddcolumn($tablename, $sval->Field, "INT(11) NOT NULL AUTO_INCREMENT", 1);
              }
            }
            break;

          default:
            $structure =  $this->coreFunctions->opentable("show full columns FROM " . $tablename . "
              where field = 'viewby' or Field = 'editby'
              or Field = 'createby' or Field = 'createdby'
              or Field = 'openby' or Field = 'lockuser'
              or Field = 'postedby' or Field = 'encodedby'
              or Field = 'users' or Field = 'user'
              or Field = 'userid' or Field = 'approvedby' or Field = 'puser' or Field = 'yourref' or Field = 'address'
              "); // get all table structure

            foreach ($structure as $skey => $sval) {
              // $sval->Field == "userid"
              if (strtolower(substr($sval->Type, 0, 7)) == "varchar") {
                $this->coreFunctions->execqry("update $tablename set $sval->Field = '' WHERE $sval->Field is null", "update");
                switch ($sval->Field) {
                  case 'address':
                    $this->coreFunctions->sbcaddcolumn($tablename, $sval->Field, "varchar(300) NOT NULL DEFAULT ''", 1); //match length from client table
                    break;
                  default:
                    $this->coreFunctions->sbcaddcolumn($tablename, $sval->Field, "varchar(100) NOT NULL DEFAULT ''", 1);
                    break;
                }
              }
            }

            $structure =  $this->coreFunctions->opentable("show full columns FROM " . $tablename . " where field = 'docno'"); // get all table structure
            foreach ($structure as $skey => $sval) {
              $this->coreFunctions->sbcaddcolumn($tablename, $sval->Field, "VARCHAR(20) NOT NULL DEFAULT ''", 1);
            }

            $structure =  $this->coreFunctions->opentable("show full columns FROM " . $tablename . " where field = 'client'"); // get all table structure
            foreach ($structure as $skey => $sval) {
              $this->coreFunctions->sbcaddcolumn($tablename, $sval->Field, "VARCHAR(30) NOT NULL DEFAULT ''", 1);
            }
            break;
        }
      }
    }
  }
} // end class