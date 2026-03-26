<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class login
{

  private $coreFunctions;
  private $othersClass;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  } //end fn


  public function onloadtableupdate()
  {
    //always update this version every changes made inside this function
    $dbversion = '2025-10-06 16:26';

    $dbupdate = $this->coreFunctions->datareader("select pvalue as value from profile where doc='SYS' and psection='DBUPDATE'");
    $this->coreFunctions->LogConsole($dbupdate);
    if ($dbupdate == '') {
      goto updatedbhere;
    } else {
      if ($dbversion > $dbupdate) {
        goto updatedbhere;
      } else {
        return;
      }
    }

    updatedbhere:
    $this->coreFunctions->sbcaddcolumn("useraccess", "picture", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("client", "isstudent", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("left_menu", "seq", "INTEGER NOT NULL DEFAULT '0'", 0);

    //FPY 11.22.2020
    $qry = "
    CREATE TABLE `iplog` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `doc` char(15) NOT NULL DEFAULT '',
      `ip` varchar(15) NOT NULL DEFAULT '',
      `username` varchar(40) NOT NULL DEFAULT '',
      `accessdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`),
      KEY `Index_iplog` (`doc`,`ip`,`username`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("iplog", $qry);

    //FPY
    //12.07.2020
    //announcement module
    //1.notice
    $qry = "
    CREATE TABLE `waims_notice` (
      `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `dateid` date DEFAULT NULL,
      `title` varchar(30) NOT NULL DEFAULT '',
      `rem` varchar(5000) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(15) NOT NULL DEFAULT '',
      `approveddate` datetime DEFAULT NULL,
      `approvedby` varchar(15) NOT NULL DEFAULT '',
      `status` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      KEY `Index_waims_notice` (`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("waims_notice", $qry);

    //2.event
    $qry = "
    CREATE TABLE `waims_event` (
      `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `datestart` date DEFAULT NULL,
      `dateend` date DEFAULT NULL,
      `title` varchar(30) NOT NULL DEFAULT '',
      `rem` varchar(5000) NOT NULL DEFAULT '',
      `icon` varchar(30) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(15) NOT NULL DEFAULT '',
      `approveddate` datetime DEFAULT NULL,
      `approvedby` varchar(15) NOT NULL DEFAULT '',
      `status` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      KEY `Index_waims_event` (`datestart`,`dateend`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("waims_event", $qry);

    $this->coreFunctions->sbcaddcolumn("waims_event", "color", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("waims_event", "datestart", "date DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("waims_event", "dateend", "date DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("waims_event", "icon", "varchar(30) NOT NULL DEFAULT ''", 0);

    //3.Holiday
    $qry = "
    CREATE TABLE `waims_holiday` (
      `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `datestart` date DEFAULT NULL,
      `dateend` date DEFAULT NULL,
      `title` varchar(30) NOT NULL DEFAULT '',
      `rem` varchar(5000) NOT NULL DEFAULT '',
      `icon` varchar(30) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(15) NOT NULL DEFAULT '',
      `approveddate` datetime DEFAULT NULL,
      `approvedby` varchar(15) NOT NULL DEFAULT '',
      `status` int(3) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      KEY `Index_waims_event` (`datestart`,`dateend`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("waims_holiday", $qry);

    $this->coreFunctions->sbcaddcolumn("waims_holiday", "color", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("waims_holiday", "datestart", "date DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("waims_holiday", "dateend", "date DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("waims_holiday", "icon", "varchar(30) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->execqrynolog("update glhead set waybilldate=null where waybilldate='0000-00-00'");

    //FMM 5.3.2021
    $this->coreFunctions->execqrynolog("update item set effectdate=null where effectdate='0000-00-00 00:00:00'");

    //FMM 6.11.2021
    $qry = "
    CREATE TABLE `adminlog` (
      `user` varchar(50) NOT NULL DEFAULT '',
      `clientid` int(11) NOT NULL DEFAULT '0',
      `dateid` DATETIME DEFAULT NULL,
      KEY `Index_user` (`user`),
      KEY `Index_clientid` (`clientid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("adminlog", $qry);

    //FMM 8.5.2021
    $this->coreFunctions->sbcaddcolumn("lastock", "suppid", "integer(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("lastock", "itemstatus", "varchar(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "suppid", "integer(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glstock", "itemstatus", "varchar(20) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "tel", "VARCHAR(50) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("glhead", "tel", "VARCHAR(50) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt1", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt2", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt3", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt4", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfo", "amt5", "decimal(19,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt1", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt2", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt3", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt4", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfo", "amt5", "decimal(19,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt1", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt2", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt3", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt4", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("stockinfotrans", "amt5", "decimal(19,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt1", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt2", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt3", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt4", "decimal(19,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "amt5", "decimal(19,6) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("lahead", "statid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "statid", "INT(3) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("profile", "yr", "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("center", "shortname", "VARCHAR(50) NOT NULL DEFAULT ''");

    if ('2023-08-22 00:59' >= $dbupdate) {
      $this->coreFunctions->sbcaddcolumn("standardtrans", "line", "INT(11) UNSIGNED NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`line`)", 1);
    }

    //FMM - 04.28.2023
    $users = $this->coreFunctions->opentable("select idno from users where length(attributes)<10000");
    foreach ($users as $key => $value) {
      $this->coreFunctions->execqry("update users as u set u.attributes=RPAD(u.attributes,10000,'0') where idno = '$value->idno'");
    }

    //FMM - 08.22.2023
    if ('2023-08-22 00:59' >= $dbupdate) {
      $this->coreFunctions->sbcaddcolumn("transnum", "seq", "bigint(13) NOT NULL DEFAULT '0'");
    }

    $success = true;
    //FMM - 03.18.2024
    $this->coreFunctions->sbcaddcolumngrp(["left_parent", "left_menu", "menu", "attributes"], ["levelid"], "integer(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn("left_menu", "id");
    if ('2024-03-19 19:03' > $dbupdate) {
      $this->coreFunctions->LogConsole('update navigator');
      $this->coreFunctions->sbcdroptableprimarykey("left_parent");
      if (!$this->coreFunctions->execqry("ALTER TABLE left_parent ADD PRIMARY KEY USING BTREE(`id`, `levelid`)")) $success = false;

      $this->coreFunctions->sbcdroptableprimarykey("left_menu");
      if (!$this->coreFunctions->execqry("ALTER TABLE left_menu ADD PRIMARY KEY USING BTREE(`access`, `levelid`)")) $success = false;

      $this->coreFunctions->sbcdroptableprimarykey("menu");
      if (!$this->coreFunctions->execqry("ALTER TABLE menu ADD PRIMARY KEY USING BTREE(`code`, `levelid`)")) $success = false;

      $this->coreFunctions->sbcdroptableprimarykey("attributes");
      $this->coreFunctions->sbcaddcolumn("attributes", "attribute", "INT(11) UNSIGNED NOT NULL DEFAULT '0'", 1);
      if (!$this->coreFunctions->execqry("ALTER TABLE attributes ADD PRIMARY KEY USING BTREE(`attribute`, `levelid`)")) $success = false;
    }

    //FMM - 10.06.2025
    if ('2025-10-06 16:26' >= $dbupdate) {
      $this->coreFunctions->sbcaddcolumn("item", "lock", "varchar(20) default null");
      $this->coreFunctions->execqrynolog("update item set lock=null where lock=''");
      $this->coreFunctions->execqrynolog("update item set lock=null where lock='0000-00-00 00:00:00'");
      $this->coreFunctions->sbcaddcolumn("item", "lock", "datetime default null");
    }

    //always at the end of this function
    if ($success) {
      if ($dbupdate != '') {
        $this->coreFunctions->execqry("delete from profile where doc='SYS' and psection='DBUPDATE'");
      }
      $this->coreFunctions->execqry("insert into profile (doc, psection, pvalue) values ('SYS', 'DBUPDATE', '" . $dbversion . "')");
    }
  } //end function


  public function adminlogs($user)
  {

    $clientid = 0;
    $code = $this->coreFunctions->datareader('select supplier as value from useraccess where username=?', [$user]);

    if ($code != '') {
      $clientid = $this->coreFunctions->datareader('select clientid as value from client where email=?', [$code]);
    }

    if (!$clientid) {
      return;
    }

    $log = $this->coreFunctions->opentable('select user, clientid, date(dateid) as dateid from adminlog where user=?', [$user]);
    if ($log) {
      $current_date = $this->othersClass->getCurrentDate();
      if ($log[0]->dateid != $current_date) {
        $this->coreFunctions->execqry("delete from adminlog where user=?", "delete", [$user]);
        goto insertloghere;
      } else {
        if ($log[0]->clientid == 0) {
          if ($clientid != 0) {
            $this->coreFunctions->execqry("update adminlog set clientid=" . $clientid . " where user=?", "update", [$user]);
          }
        }
      }
    } else {
      insertloghere:
      $data = [];
      $data['user'] = $user;
      $data['clientid'] = $clientid;
      $data['dateid'] = $this->othersClass->getCurrentTimeStamp();
      $this->coreFunctions->sbcinsert('adminlog', $data);
    }
  }

  public function updateyearprefix()
  {
    $currentyear =  date('y', strtotime($this->othersClass->getCurrentDate()));

    $year = $this->coreFunctions->datareader("select ifnull(COUNT(line),0) AS value FROM profile WHERE doc='SED' AND yr<>" . $currentyear, [], '', true);
    if ($year != 0) {
      $this->coreFunctions->LogConsole('update year ' . $currentyear);
      $this->coreFunctions->execqry("update profile set yr=" . $currentyear . " where doc='SED' AND yr<>" . $currentyear);
    }
  }
}//end class
