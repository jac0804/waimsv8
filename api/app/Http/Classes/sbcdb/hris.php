<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;

class hris
{

  private $coreFunctions;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
  } //end fn



  public function tableupdatehris()
  {
    ini_set('max_execution_time', 0);
    //FMM 5.3.2021 - moved here because all alter tables with this field got an error incorrect default value '0000-00-00'.
    //Must alter this fields first    
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "posteddate", "DATE DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("clearance", "hired", "DATE DEFAULT NULL", 1);
    //end of FMM 5.3.2021

    $qry = "CREATE TABLE `app` (
      `empid` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `empcode` varchar(15) NOT NULL DEFAULT '',
      `emplast` varchar(30) NOT NULL DEFAULT '',
      `empfirst` varchar(30) NOT NULL DEFAULT '',
      `empmiddle` varchar(30) NOT NULL DEFAULT '',
      `address` varchar(250) NOT NULL DEFAULT '',
      `city` varchar(500) NOT NULL DEFAULT '',
      `country` varchar(14) NOT NULL DEFAULT '',
      `zipcode` varchar(6) NOT NULL DEFAULT '',
      `telno` varchar(50) NOT NULL DEFAULT '',
      `mobileno` varchar(25) NOT NULL DEFAULT '',
      `email` varchar(50) NOT NULL DEFAULT '',
      `citizenship` varchar(15) NOT NULL DEFAULT '',
      `religion` varchar(30) NOT NULL DEFAULT '',
      `alias` varchar(25) NOT NULL DEFAULT '',
      `bday` datetime DEFAULT NULL,
      `jobtitle` varchar(100) NOT NULL DEFAULT '',
      `jobcode` varchar(10) NOT NULL DEFAULT '',
      `jobdesc` varchar(1500) NOT NULL,
      `maidname` varchar(30) NOT NULL DEFAULT '',
      `appdate` datetime DEFAULT NULL,
      `remarks` varchar(500) NOT NULL,
      `type` varchar(45) DEFAULT NULL,
      `jstatus` varchar(45) DEFAULT NULL,
      `mapp` varchar(300) DEFAULT NULL,
      `bplace` varchar(200) DEFAULT NULL,
      `child` decimal(8,0) DEFAULT '0',
      `status` varchar(45) DEFAULT NULL,
      `gender` varchar(45) DEFAULT NULL,
      `ishired` tinyint(1) unsigned DEFAULT '0',
      `hired` datetime DEFAULT NULL,
      `idno` varchar(45) DEFAULT NULL,
      PRIMARY KEY (`empid`,`empcode`) USING BTREE,
      KEY `Index_2` (`empcode`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";

    $this->coreFunctions->sbccreatetable("app", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["app"], ["jobdesc"], "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["app"], ["remarks"], "text default null", 1);
    $this->coreFunctions->sbcaddcolumn("app", "jobid", "INTEGER NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["app"], ["jobcode", "password"], "VARCHAR(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["app", "employee"], ["telno"], "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("app", "center", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["app"], ["createby", "viewby", "editby", "username"], "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["app"], ["createdate", "viewdate", "editdate"], "datetime DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumngrp(["app"], ["statid"], "INT(2) NOT NULL DEFAULT '0'");

    $this->coreFunctions->sbcaddcolumngrp(["app", "employee"], ["city"], "varchar(500) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `acontacts` (
      `empcode` varchar(15) NOT NULL DEFAULT '',
      `contact1` varchar(60) NOT NULL DEFAULT '',
      `relation1` varchar(25) NOT NULL DEFAULT '',
      `addr1` varchar(250) NOT NULL DEFAULT '',
      `homeno1` varchar(30) NOT NULL DEFAULT '',
      `mobileno1` varchar(30) NOT NULL DEFAULT '',
      `officeno1` varchar(30) NOT NULL DEFAULT '',
      `ext1` varchar(6) NOT NULL DEFAULT '',
      `notes1` varchar(250) NOT NULL DEFAULT '',
      `contact2` varchar(60) NOT NULL DEFAULT '',
      `relation2` varchar(25) NOT NULL DEFAULT '',
      `addr2` varchar(250) NOT NULL DEFAULT '',
      `homeno2` varchar(30) NOT NULL DEFAULT '',
      `mobileno2` varchar(30) NOT NULL DEFAULT '',
      `officeno2` varchar(30) NOT NULL DEFAULT '',
      `ext2` varchar(6) NOT NULL DEFAULT '',
      `notes2` varchar(250) NOT NULL DEFAULT ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("acontacts", $qry);

    $this->coreFunctions->sbcaddcolumn("acontacts", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER contact1", 0);
    $this->coreFunctions->sbcdropcolumn("acontacts", "empcode");

    $qry = "CREATE TABLE `adependents` (
      `empcode` varchar(15) NOT NULL DEFAULT '',
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `name` varchar(50) NOT NULL DEFAULT '',
      `relation` varchar(50) NOT NULL DEFAULT '',
      `bday` datetime DEFAULT NULL,
      `taxin` tinyint(1) NOT NULL DEFAULT '1',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("adependents", $qry);

    $this->coreFunctions->sbcaddcolumn("adependents", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER line", 0);
    $this->coreFunctions->sbcdropcolumn("adependents", "empcode");

    $qry = "CREATE TABLE `aeducation` (
      `empcode` varchar(15) NOT NULL DEFAULT '',
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `school` varchar(100) NOT NULL DEFAULT '',
      `address` varchar(50) NOT NULL DEFAULT '',
      `course` varchar(80) NOT NULL DEFAULT '',
      `sy` varchar(20) NOT NULL DEFAULT '',
      `gpa` varchar(5) NOT NULL DEFAULT '',
      `honor` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("aeducation", $qry);

    $this->coreFunctions->sbcaddcolumn("aeducation", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER line", 0);
    $this->coreFunctions->sbcdropcolumn("aeducation", "empcode");

    $qry = "CREATE TABLE `aemployment` (
      `empcode` varchar(15) NOT NULL DEFAULT '',
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `company` varchar(100) NOT NULL DEFAULT '',
      `jobtitle` varchar(50) NOT NULL DEFAULT '',
      `period` varchar(50) NOT NULL DEFAULT '',
      `address` varchar(250) NOT NULL DEFAULT '',
      `salary` decimal(9,2) NOT NULL DEFAULT '0.00',
      `reason` varchar(250) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("aemployment", $qry);

    $this->coreFunctions->sbcaddcolumn("aemployment", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER line", 0);
    $this->coreFunctions->sbcdropcolumn("aemployment", "empcode");

    $qry = "CREATE TABLE `arequire` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `reqs` varchar(100) NOT NULL,
      `submitdate` datetime DEFAULT NULL,
      `notes` varchar(500) NOT NULL,
      `empcode` varchar(45) NOT NULL,
      `issubmitted` tinyint(1) NOT NULL DEFAULT '0',
      `pin` varchar(45) NOT NULL,
      PRIMARY KEY (`line`) USING BTREE
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("arequire", $qry);

    $this->coreFunctions->sbcaddcolumn("arequire", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER line", 0);
    $this->coreFunctions->sbcaddcolumn("arequire", "reqid", "INT(11) NOT NULL DEFAULT '0' AFTER pin", 0);
    $this->coreFunctions->sbcdropcolumn("arequire", "empcode");

    $this->coreFunctions->sbcaddcolumn("arequire", "reqs", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("arequire", "notes", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("arequire", "empcode", "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("arequire", "pin", "varchar(45) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `apreemploy` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `preemptest` varchar(45) NOT NULL,
      `result` varchar(45) NOT NULL,
      `notes` varchar(500) NOT NULL,
      `empcode` varchar(45) NOT NULL,
      `pin` varchar(45) NOT NULL,
      PRIMARY KEY (`line`) USING BTREE
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("apreemploy", $qry);

    $this->coreFunctions->sbcaddcolumn("apreemploy", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER line", 0);
    $this->coreFunctions->sbcdropcolumn("apreemploy", "empcode");

    $this->coreFunctions->sbcaddcolumn("apreemploy", "preemptest", "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("apreemploy", "result", "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("apreemploy", "notes", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("apreemploy", "empcode", "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("apreemploy", "pin", "varchar(45) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `apppics` (
      `empcode` varchar(15) NOT NULL DEFAULT '',
      `picture` mediumblob,
      `picture2` mediumblob,
      `picture3` mediumblob
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("apppics", $qry);

    $this->coreFunctions->sbcaddcolumn("apppics", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER picture", 0);
    $this->coreFunctions->sbcdropcolumn("apppics", "empcode");

    $qry = "CREATE TABLE `traindev` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL DEFAULT '',
      `jobtitle` varchar(45) NOT NULL DEFAULT '',
      `department` varchar(45) NOT NULL DEFAULT '',
      `type` varchar(45) NOT NULL DEFAULT '',
      `title` varchar(60) NOT NULL DEFAULT '',
      `venue` varchar(60) NOT NULL DEFAULT '',
      `date1` datetime DEFAULT NULL,
      `date2` datetime DEFAULT NULL,
      `purpose` varchar(60) NOT NULL DEFAULT '',
      `budget` decimal(19,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("traindev", $qry);

    $this->coreFunctions->sbcaddcolumn("traindev", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("traindev", "deptid", "INT(11) NOT NULL DEFAULT '0'",  0);
    $this->coreFunctions->sbcdropcolumn("traindev", "empcode");

    $this->coreFunctions->sbcaddcolumn("traindev", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traindev", "createdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traindev", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traindev", "editdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traindev", "lockdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traindev", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traindev", "viewdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traindev", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traindev", "doc", "VARCHAR(2) NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("traindev", "reqtrain", "INT(11) NOT NULL DEFAULT '0'",  0);

    $this->coreFunctions->sbcaddcolumn("traindev", "purpose", "varchar(500) NOT NULL DEFAULT ''",  1);



    $qry = "CREATE TABLE `htraindev` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL DEFAULT '',
      `jobtitle` varchar(45) NOT NULL DEFAULT '',
      `department` varchar(45) NOT NULL DEFAULT '',
      `type` varchar(45) NOT NULL DEFAULT '',
      `title` varchar(60) NOT NULL DEFAULT '',
      `venue` varchar(60) NOT NULL DEFAULT '',
      `date1` datetime DEFAULT NULL,
      `date2` datetime DEFAULT NULL,
      `purpose` varchar(60) NOT NULL DEFAULT '',
      `budget` decimal(19,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("htraindev", $qry);

    $this->coreFunctions->sbcaddcolumn("htraindev", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("htraindev", "deptid", "INT(11) NOT NULL DEFAULT '0'",  0);
    $this->coreFunctions->sbcdropcolumn("htraindev", "empcode");

    $this->coreFunctions->sbcaddcolumn("htraindev", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraindev", "createdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraindev", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraindev", "editdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraindev", "lockdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraindev", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraindev", "viewdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraindev", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraindev", "doc", "VARCHAR(2) NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("htraindev", "reqtrain", "INT(11) NOT NULL DEFAULT '0'",  0);

    $this->coreFunctions->sbcaddcolumn("htraindev", "purpose", "varchar(500) NOT NULL DEFAULT ''",  1);

    $qry = "CREATE TABLE `traininghead` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `title` varchar(60) NOT NULL DEFAULT '',
      `ttype` varchar(45) NOT NULL DEFAULT '',
      `venue` varchar(60) NOT NULL DEFAULT '',
      `tdate1` datetime DEFAULT NULL,
      `tdate2` datetime DEFAULT NULL,
      `speaker` varchar(60) NOT NULL DEFAULT '',
      `amt` decimal(19,2) NOT NULL,
      `cost` decimal(19,2) NOT NULL,
      `attendees` varchar(70) NOT NULL DEFAULT '',
      `remarks` text NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("traininghead", $qry);

    $this->coreFunctions->sbcaddcolumn("traininghead", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traininghead", "createdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traininghead", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traininghead", "editdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traininghead", "lockdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traininghead", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traininghead", "viewdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("traininghead", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("traininghead", "doc", "varchar(3) NOT NULL DEFAULT ''",  0);

    $this->coreFunctions->sbcaddcolumn("traininghead", "amt", "decimal(19,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("traininghead", "cost", "decimal(19,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("traininghead", "remarks", "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("traininghead", "reqtrain", "INT(11) NOT NULL DEFAULT '0'",  0);

    $qry = "CREATE TABLE `trainingdetail` (
      `trno` int(10) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL DEFAULT '',
      `notes` text NOT NULL,
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("trainingdetail", $qry);

    $this->coreFunctions->sbcaddcolumn("trainingdetail", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER line", 0);
    $this->coreFunctions->sbcdropcolumn("trainingdetail", "empcode");

    $this->coreFunctions->sbcaddcolumn("trainingdetail", "trno", "int(10) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("trainingdetail", "line", "int(10) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("trainingdetail", "notes", "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("trainingdetail", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("trainingdetail", "editdate", "DATETIME DEFAULT NULL", 0);

    $qry = "CREATE TABLE `htraininghead` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `title` varchar(60) NOT NULL DEFAULT '',
      `ttype` varchar(45) NOT NULL DEFAULT '',
      `venue` varchar(60) NOT NULL DEFAULT '',
      `tdate1` datetime DEFAULT NULL,
      `tdate2` datetime DEFAULT NULL,
      `speaker` varchar(60) NOT NULL DEFAULT '',
      `amt` decimal(19,2) NOT NULL,
      `cost` decimal(19,2) NOT NULL,
      `attendees` varchar(70) NOT NULL DEFAULT '',
      `remarks` text NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("htraininghead", $qry);

    $this->coreFunctions->sbcaddcolumn("htraininghead", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "createdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "editdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "lockdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "viewdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "doc", "varchar(3) NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("htrainingdetail", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("htrainingdetail", "editdate", "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("htraininghead", "amt", "decimal(19,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "cost", "decimal(19,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "remarks", "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("htraininghead", "reqtrain", "INT(11) NOT NULL DEFAULT '0'",  0);

    $qry = "CREATE TABLE `htrainingdetail` (
      `trno` int(10) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL DEFAULT '',
      `notes` text NOT NULL,
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("htrainingdetail", $qry);

    $this->coreFunctions->sbcaddcolumn("htrainingdetail", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER line", 0);
    $this->coreFunctions->sbcdropcolumn("htrainingdetail", "empcode");

    $this->coreFunctions->sbcaddcolumn("htrainingdetail", "trno", "int(10) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("htrainingdetail", "line", "int(10) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("htrainingdetail", "notes", "varchar(200) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `turnoveritemhead` (
      `docno` varchar(45) NOT NULL,
      `doc` varchar(2) NOT NULL,
      `empid` INT(11) NOT NULL DEFAULT '0',
      `employee` varchar(100) NOT NULL,
      `jobtitle` varchar(45) NOT NULL,
      `deptid` INT(11) NOT NULL DEFAULT '0',
      `rem` varchar(150) NOT NULL,
      `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `dateid` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`) USING BTREE,
      KEY `Index_2` (`trno`,`empid`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("turnoveritemhead", $qry);

    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "deptid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcdropcolumn("turnoveritemhead", "empcode");

    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "createdby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "createdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "editdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "lockdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "viewdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "doc", "varchar(2) NOT NULL DEFAULT ''",  1);

    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "doc", "varchar(2) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "employee", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "jobtitle", "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "rem", "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "dept", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("turnoveritemhead", "postedby", "varchar(100) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `hturnoveritemhead` (
      `docno` varchar(45) NOT NULL,
      `doc` varchar(2) NOT NULL,
      `empid` INT(11) NOT NULL DEFAULT '0',
      `employee` varchar(100) NOT NULL,
      `jobtitle` varchar(45) NOT NULL,
      `deptid` INT(11) NOT NULL DEFAULT '0',
      `rem` varchar(150) NOT NULL,
      `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `dateid` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`) USING BTREE,
      KEY `Index_2` (`trno`,`empid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hturnoveritemhead", $qry);

    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "deptid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcdropcolumn("hturnoveritemhead", "empcode");

    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "createdby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "createdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "editdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "lockdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "viewdate", "datetime DEFAULT NULL",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "doc", "varchar(2) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "posteddate", "datetime NULL",  1);

    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "doc", "varchar(2) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "employee", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "jobtitle", "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "rem", "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "dept", "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemhead", "postedby", "varchar(100) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `turnoveritemdetail` (
      `line` int(11) unsigned NOT NULL ,
      `trno` int(11) unsigned NOT NULL,
      `itemname` varchar(100) NOT NULL,
      `amt` decimal(18,2) NOT NULL,
      `rem` varchar(100) NOT NULL,
      PRIMARY KEY (`line`,`trno`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("turnoveritemdetail", $qry);

    $this->coreFunctions->sbcaddcolumn("turnoveritemdetail", "line", "int(11) unsigned NOT NULL AUTO_INCREMENT ", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemdetail", "trno", "int(11) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemdetail", "amt", "decimal(18,2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemdetail", "rem", "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("turnoveritemdetail", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("turnoveritemdetail", "editdate", "DATETIME DEFAULT NULL", 0);

    $qry = "CREATE TABLE `hturnoveritemdetail` (
      `line` int(11) unsigned NOT NULL,
      `trno` int(11) unsigned NOT NULL,
      `itemname` varchar(100) NOT NULL,
      `amt` decimal(18,2) NOT NULL,
      `rem` varchar(100) NOT NULL,
      `refx` int(4) unsigned DEFAULT '0',
      `linex` int(4) unsigned DEFAULT '0',
      `qa` int(4) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`,`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("hturnoveritemdetail", $qry);

    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "qa", "int(4) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "line", "int(11) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "trno", "int(11) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "itemname", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "amt", "decimal(18,2) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "rem", "varchar(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hturnoveritemdetail", "editdate", "DATETIME DEFAULT NULL", 0);

    $qry = "CREATE TABLE `returnitemhead` (
      `docno` varchar(45) NOT NULL,
      `empcode` varchar(45) NOT NULL,
      `employee` varchar(100) NOT NULL,
      `jobtitle` varchar(45) NOT NULL,
      `dept` varchar(45) NOT NULL,
      `rem` varchar(150) NOT NULL,
      `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `dateid` datetime DEFAULT NULL,
      `createdby` varchar(100) NOT NULL,
      `postedby` varchar(100) DEFAULT NULL,
      `posteddate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`) USING BTREE,
      KEY `Index_2` (`trno`,`empcode`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("returnitemhead", $qry);

    $this->coreFunctions->sbcaddcolumn("returnitemhead", "empid", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "deptid", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn("returnitemhead", "empcode");

    $this->coreFunctions->sbcaddcolumn("returnitemhead", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "createdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "editdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "lockdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "viewdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "doc", "varchar(2) NOT NULL DEFAULT ''",  0);

    $this->coreFunctions->sbcaddcolumn("returnitemhead", "employee", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "dept", "varchar(45) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "createdby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("returnitemhead", "postedby", "varchar(100) NOT NULL DEFAULT ''",  1);

    $qry = "CREATE TABLE `hreturnitemhead` (
      `docno` varchar(45) NOT NULL,
      `empcode` varchar(45) NOT NULL,
      `employee` varchar(100) NOT NULL,
      `jobtitle` varchar(45) NOT NULL,
      `dept` varchar(45) NOT NULL,
      `rem` varchar(150) NOT NULL,
      `trno` int(4) unsigned NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `posteddate` datetime NULL,
      `postedby` varchar(100) NOT NULL,
      `createdby` varchar(100) NOT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_2` (`trno`,`empcode`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hreturnitemhead", $qry);

    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "empid", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "deptid", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcdropcolumn("hreturnitemhead", "empcode");

    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "createby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "createdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "editby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "editdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "lockdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "lockuser", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "viewdate", "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "viewby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "doc", "varchar(2) NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "posteddate", "datetime DEFAULT NULL",  1);

    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "employee", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "dept", "varchar(45) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "createdby", "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("hreturnitemhead", "postedby", "varchar(100) NOT NULL DEFAULT ''",  1);

    $qry = "CREATE TABLE `returnitemdetail` (
      `line` int(4) unsigned NOT NULL,
      `trno` int(4) unsigned NOT NULL,
      `itemname` varchar(100) NOT NULL,
      `amt` decimal(18,2) NOT NULL,
      `rem` varchar(100) NOT NULL,
      `refx` int(4) unsigned DEFAULT '0',
      `linex` int(4) unsigned DEFAULT '0',
      `ref` varchar(45) DEFAULT NULL,
      PRIMARY KEY (`line`,`trno`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("returnitemdetail", $qry);

    $this->coreFunctions->sbcaddcolumn("returnitemdetail", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("returnitemdetail", "editdate", "DATETIME DEFAULT NULL", 0);

    $qry = "CREATE TABLE `hreturnitemdetail` (
      `line` int(4) unsigned NOT NULL,
      `trno` int(4) unsigned NOT NULL,
      `itemname` varchar(100) NOT NULL,
      `amt` decimal(18,2) NOT NULL,
      `rem` varchar(100) NOT NULL,
      `refx` int(4) unsigned DEFAULT '0',
      `linex` int(4) unsigned DEFAULT '0',
      `ref` varchar(45) DEFAULT NULL,
      PRIMARY KEY (`line`,`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("hreturnitemdetail", $qry);

    $this->coreFunctions->sbcaddcolumn("hreturnitemdetail", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("hreturnitemdetail", "editdate", "DATETIME DEFAULT NULL", 0);

    $qry = "CREATE TABLE `eschange` (
      `trno` int(10) unsigned NOT NULL,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `effdate` datetime NOT NULL,
      `statcode` varchar(45) NOT NULL DEFAULT '',
      `description` varchar(45) NOT NULL DEFAULT '',
      `hired` datetime DEFAULT NULL,
      `constart` datetime DEFAULT NULL,
      `conend` datetime DEFAULT NULL,
      `resigned` datetime DEFAULT NULL,
      `remarks` varchar(45) NOT NULL DEFAULT '',
      `ftype` varchar(45) NOT NULL DEFAULT '',
      `flevel` varchar(45) NOT NULL DEFAULT '',
      `fjobcode` varchar(45) NOT NULL DEFAULT '',
      `fempstatcode` varchar(45) NOT NULL DEFAULT '',
      `frank` varchar(45) NOT NULL DEFAULT '',
      `fjobgrade` varchar(45) NOT NULL DEFAULT '',
      `fdeptcode` varchar(45) NOT NULL DEFAULT '',
      `flocation` varchar(45) NOT NULL DEFAULT '',
      `fpaymode` varchar(45) NOT NULL DEFAULT '',
      `fgroupcode` varchar(45) NOT NULL DEFAULT '',
      `fpayrate` varchar(45) NOT NULL DEFAULT '',
      `tjobcode` varchar(45) NOT NULL DEFAULT '',
      `ttype` varchar(45) NOT NULL DEFAULT '',
      `tlevel` varchar(45) NOT NULL DEFAULT '',
      `tempstatcode` varchar(45) NOT NULL DEFAULT '',
      `tjobgrade` varchar(45) NOT NULL DEFAULT '',
      `tlocation` varchar(45) NOT NULL DEFAULT '',
      `tgroupcode` varchar(45) NOT NULL DEFAULT '',
      `tbasicrate` decimal(9,2) NOT NULL DEFAULT '0.00',
      `trank` varchar(45) NOT NULL,
      `tdeptcode` varchar(45) NOT NULL,
      `tpaymode` varchar(45) NOT NULL,
      `tpayrate` varchar(45) NOT NULL,
      `tallowrate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `fpaygroup` varchar(45) NOT NULL,
      `tpaygroup` varchar(45) NOT NULL,
      `createdby` varchar(45) NOT NULL,
      `fbasicrate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `fallowrate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `employee` varchar(100) DEFAULT NULL,
      `jobtitle` varchar(100) DEFAULT NULL,
      `dept` varchar(100) DEFAULT NULL,
      `emphired` datetime DEFAULT NULL,
      `empstart` datetime DEFAULT NULL,
      `stat` varchar(100) DEFAULT NULL,
      `scode` varchar(45) DEFAULT NULL,
      `statdesc` varchar(200) DEFAULT NULL,
      `deptcode` varchar(45) DEFAULT NULL,
      `jobcode` varchar(45) DEFAULT NULL,
      `jobdesc` varchar(300) DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("eschange", $qry);


    $qry = "CREATE TABLE `heschange` (
      `trno` int(10) unsigned NOT NULL,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `effdate` datetime NOT NULL,
      `statcode` varchar(45) NOT NULL DEFAULT '',
      `description` varchar(45) NOT NULL DEFAULT '',
      `hired` datetime DEFAULT NULL,
      `constart` datetime DEFAULT NULL,
      `conend` datetime DEFAULT NULL,
      `remarks` varchar(45) NOT NULL DEFAULT '',
      `ftype` varchar(45) NOT NULL DEFAULT '',
      `flevel` varchar(45) NOT NULL DEFAULT '',
      `fjobcode` varchar(45) NOT NULL DEFAULT '',
      `fempstatcode` varchar(45) NOT NULL DEFAULT '',
      `frank` varchar(45) NOT NULL DEFAULT '',
      `fjobgrade` varchar(45) NOT NULL DEFAULT '',
      `fdeptcode` varchar(45) NOT NULL DEFAULT '',
      `flocation` varchar(45) NOT NULL DEFAULT '',
      `fpaymode` varchar(45) NOT NULL DEFAULT '',
      `fgroupcode` varchar(45) NOT NULL DEFAULT '',
      `fpayrate` varchar(45) NOT NULL DEFAULT '',
      `tjobcode` varchar(45) NOT NULL DEFAULT '',
      `ttype` varchar(45) NOT NULL DEFAULT '',
      `tlevel` varchar(45) NOT NULL DEFAULT '',
      `tempstatcode` varchar(45) NOT NULL DEFAULT '',
      `tjobgrade` varchar(45) NOT NULL DEFAULT '',
      `tlocation` varchar(45) NOT NULL DEFAULT '',
      `tgroupcode` varchar(45) NOT NULL DEFAULT '',
      `tbasicrate` decimal(9,2) NOT NULL DEFAULT '0.00',
      `trank` varchar(45) NOT NULL,
      `tdeptcode` varchar(45) NOT NULL,
      `tpaymode` varchar(45) NOT NULL,
      `tpayrate` varchar(45) NOT NULL,
      `tallowrate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `fpaygroup` varchar(45) NOT NULL,
      `tpaygroup` varchar(45) NOT NULL,
      `createdby` varchar(45) NOT NULL,
      `fbasicrate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `fallowrate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `employee` varchar(100) DEFAULT NULL,
      `jobtitle` varchar(100) DEFAULT NULL,
      `dept` varchar(100) DEFAULT NULL,
      `emphired` datetime DEFAULT NULL,
      `empstart` datetime DEFAULT NULL,
      `stat` varchar(100) DEFAULT NULL,
      `scode` varchar(45) DEFAULT NULL,
      `statdesc` varchar(200) DEFAULT NULL,
      `posteddate` datetime DEFAULT NULL,
      `postedby` varchar(100) DEFAULT NULL,
      `deptcode` varchar(45) DEFAULT NULL,
      `jobcode` varchar(45) DEFAULT NULL,
      `jobdesc` varchar(300) DEFAULT NULL,
      `resigned` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("heschange", $qry);


    $this->coreFunctions->sbcdropcolumn("eschange", "empcode");
    $this->coreFunctions->sbcdropcolumn("heschange", "empcode");

    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['isactive'], "INT(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], [
      'empid',
      'deptid',
      'attentionid',
      'fdivid',
      'fsectid',
      'tdivid',
      'tsectid',
      'froleid',
      'troleid',
      'toprojectid',
      'totrucknameid',
      'ftrucknameid',
      'frprojectid'
    ], "INT(11) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['doc'], "VARCHAR(2) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], [
      'createby',
      'editby',
      'lockuser',
      'viewby',
      'statcode',
      'description',
      'remarks',
      'ftype',
      'flevel',
      'fjobcode',
      'fempstatcode',
      'frank',
      'fjobgrade',
      'fdeptcode',
      'flocation',
      'fpaymode',
      'fgroupcode',
      'fpayrate',
      'tjobcode',
      'ttype',
      'tlevel',
      'tempstatcode',
      'tjobgrade',
      'tlocation',
      'tgroupcode',
      'trank',
      'tdeptcode',
      'tpaymode',
      'tpayrate',
      'fpaygroup',
      'tpaygroup',
      'createdby',
      'scode',
      'deptcode',
      'jobcode'
    ], "VARCHAR(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['employee', 'jobtitle', 'dept', 'stat'], "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['statdesc'], "VARCHAR(200) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['jobdesc'], "VARCHAR(300) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['createdate', 'editdate', 'lockdate', 'viewdate', 'dateid', 'effdate'], "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['tbasicrate'], "DECIMAL(9,2) DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['tallowrate', 'fbasicrate', 'fallowrate'], "DECIMAL(18,2) DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['salarytype', 'hsperiod', 'fhsperiod'], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['eschange', 'heschange'], ['chkcopy'], "TINYINT(2) NOT NULL DEFAULT 0", 0);

    $qry = "CREATE TABLE `incidenthead` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `doc` varchar(2) NOT NULL DEFAULT '',
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `tempid` int(10) unsigned NOT NULL DEFAULT '0',
      `fempid` int(10) unsigned NOT NULL DEFAULT '0',
      `idescription` varchar(60) NOT NULL DEFAULT '',
      `idate` datetime NOT NULL,
      `iplace` varchar(30) NOT NULL DEFAULT '',
      `idetails` text NOT NULL,
      `icomments` text NOT NULL,
      `tempjobid` int(10) unsigned NOT NULL DEFAULT '0',
      `fempjobid` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("incidenthead", $qry);

    $qry = "CREATE TABLE `incidentdtail` (
      `trno` int(10) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `empid` int(10) unsigned NOT NULL DEFAULT '0',
      `jobid` int(10) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`) USING BTREE,
      KEY `Index_2` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("incidentdtail", $qry);

    $qry = "CREATE TABLE `hincidenthead` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `doc` varchar(2) NOT NULL DEFAULT '',
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `tempid` int(10) unsigned NOT NULL DEFAULT '0',
      `fempid` int(10) unsigned NOT NULL DEFAULT '0',
      `idescription` varchar(60) NOT NULL DEFAULT '',
      `idate` datetime NOT NULL,
      `iplace` varchar(30) NOT NULL DEFAULT '',
      `idetails` text NOT NULL,
      `icomments` text NOT NULL,
      `tempjobid` int(10) unsigned NOT NULL DEFAULT '0',
      `fempjobid` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hincidenthead", $qry);

    $qry = "CREATE TABLE `hincidentdtail` (
      `trno` int(10) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `empid` int(10) unsigned NOT NULL DEFAULT '0',
      `jobid` int(10) NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`) USING BTREE,
      KEY `Index_2` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hincidentdtail", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["incidenthead", "hincidenthead"], ["createdate", "editdate", "lockdate", "viewdate"], "datetime DEFAULT NULL",  0);
    $this->coreFunctions->sbcaddcolumngrp(["incidenthead", "hincidenthead"], ["tempid", "fempid", "tempjobid", "fempjobid", "artid", "sectid", "notedid"], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["incidenthead", "hincidenthead"], ["doc"], "varchar(2) NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumngrp(["incidenthead", "hincidenthead"], ["status1"], "varchar(1) NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumngrp(["incidenthead", "hincidenthead"], ["createby", "editby", "lockuser", "viewby"], "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumngrp(["incidenthead", "hincidenthead"], ["fempname", "fjobtitle"], "varchar(150) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumngrp(["incidentdtail", "hincidentdtail"], ["empid", "jobid"], "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcdropcolumngrp(["incidenthead", "hincidenthead"], ["tempcode", "fempcode", "tempname", "tempjob", "fempjob"]);
    $this->coreFunctions->sbcdropcolumngrp(["incidentdtail", "hincidentdtail"], ["empcode", "empname", "jobtitle"]);

    $qry = "CREATE TABLE `notice_explain` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL DEFAULT '',
      `empjob` varchar(100) NOT NULL,
      `fempcode` varchar(45) NOT NULL DEFAULT '',
      `fempname` varchar(100) NOT NULL,
      `fempjob` varchar(100) NOT NULL,
      `artcode` varchar(100) NOT NULL,
      `line` int(10) NOT NULL DEFAULT '0',
      `refx` int(10) NOT NULL DEFAULT '0',
      `ddate` datetime NOT NULL,
      `hdatetime` datetime DEFAULT NULL,
      `hplace` varchar(100) NOT NULL,
      `explanation` text NOT NULL,
      `comments` text NOT NULL,
      `article` varchar(100) NOT NULL,
      `section` varchar(100) NOT NULL,
      `remarks` varchar(200) NOT NULL,
      `htime` varchar(20) DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("notice_explain", $qry);

    $qry = "CREATE TABLE `hnotice_explain` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL DEFAULT '',
      `empjob` varchar(100) NOT NULL,
      `fempcode` varchar(45) NOT NULL DEFAULT '',
      `fempname` varchar(100) NOT NULL,
      `fempjob` varchar(100) NOT NULL,
      `artcode` varchar(100) NOT NULL,
      `line` int(10) NOT NULL DEFAULT '0',
      `refx` int(10) NOT NULL DEFAULT '0',
      `ddate` datetime NOT NULL,
      `hdatetime` datetime DEFAULT NULL,
      `hplace` varchar(100) NOT NULL,
      `explanation` text NOT NULL,
      `comments` text NOT NULL,
      `article` varchar(100) NOT NULL,
      `section` varchar(100) NOT NULL,
      `remarks` varchar(200) NOT NULL,
      `htime` varchar(20) DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hnotice_explain", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["notice_explain", "hnotice_explain"], ["createdate", "editdate", "lockdate", "viewdate"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["notice_explain", "hnotice_explain"], ["iswithhearing"], "INT(1) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["notice_explain", "hnotice_explain"], ["empid", "deptid", "artid", "fempid", "violationno", "numdays"], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["notice_explain", "hnotice_explain"], ["doc"], "VARCHAR(2) NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumngrp(["notice_explain", "hnotice_explain"], ["createby", "editby", "lockuser", "viewby", "fempname", "penalty"], "varchar(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumngrp(["notice_explain", "hnotice_explain"], ["fempname", "fjobtitle"], "varchar(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["notice_explain"], ["article", "section", "empjob", "fempjob", "artcode"], "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["notice_explain", "hnotice_explain"], ["remarks", "comments"], "varchar(1000) NOT NULL DEFAULT ''", 1);


    $this->coreFunctions->sbcdropcolumngrp(["hnotice_explain", "notice_explain"], ["empcode", "fempjob"]);


    $qry = "CREATE TABLE `disciplinary` (
      `trno` int(10) unsigned NOT NULL,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` date NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `artid` int(10) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `violationno` int(10) unsigned NOT NULL DEFAULT '0',
      `startdate` date NOT NULL,
      `enddate` date NOT NULL,
      `amt` decimal(9,2) NOT NULL DEFAULT '0.00',
      `detail` text NOT NULL,
      `empname` varchar(30) NOT NULL DEFAULT '',
      `jobtitle` varchar(45) NOT NULL DEFAULT '',
      `department` varchar(45) NOT NULL DEFAULT '',
      `articlename` varchar(60) NOT NULL DEFAULT '',
      `sectionname` varchar(100) NOT NULL DEFAULT '',
      `penalty` varchar(45) NOT NULL DEFAULT '',
      `numdays` int(10) unsigned NOT NULL DEFAULT '0',
      `posteddate` date NOT NULL,
      `postedby` varchar(45) NOT NULL,
      `createdby` varchar(45) NOT NULL,
      `refx` int(10) unsigned NOT NULL,
      `irdesc` varchar(45) NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("disciplinary", $qry);


    $qry = "CREATE TABLE `hdisciplinary` (
      `trno` int(10) unsigned NOT NULL,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` date NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `artid` int(10) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `violationno` int(10) unsigned NOT NULL DEFAULT '0',
      `startdate` date NOT NULL,
      `enddate` date NOT NULL,
      `amt` decimal(9,2) NOT NULL DEFAULT '0.00',
      `detail` text NOT NULL,
      `empname` varchar(30) NOT NULL DEFAULT '',
      `jobtitle` varchar(45) NOT NULL DEFAULT '',
      `department` varchar(45) NOT NULL DEFAULT '',
      `articlename` varchar(60) NOT NULL DEFAULT '',
      `sectionname` varchar(100) NOT NULL DEFAULT '',
      `penalty` varchar(45) NOT NULL DEFAULT '',
      `numdays` int(10) unsigned NOT NULL DEFAULT '0',
      `posteddate` date NOT NULL,
      `postedby` varchar(45) NOT NULL DEFAULT '',
      `createdby` varchar(45) NOT NULL DEFAULT '',
      `refx` int(10) unsigned NOT NULL,
      `irdesc` varchar(45) NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hdisciplinary", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["disciplinary", "hdisciplinary"], [
      "createdate",
      "editdate",
      "lockdate",
      "viewdate",
      "posteddate"
    ], "datetime DEFAULT NULL", 1);

    $this->coreFunctions->sbcaddcolumngrp(["disciplinary", "hdisciplinary"], ["isuspended"], "tinyint(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["disciplinary", "hdisciplinary"], [
      "empid",
      "deptid",
      "refx"
    ], "INT(11) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumngrp(["disciplinary", "hdisciplinary"], ["doc"], "VARCHAR(2) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(
      ["disciplinary", "hdisciplinary"],
      [
        "irdesc",
        "createby",
        "editby",
        "lockuser",
        "viewby",
        "postedby"
      ],
      "varchar(100) NOT NULL DEFAULT ''",
      1
    );

    $this->coreFunctions->sbcaddcolumngrp(["disciplinary", "hdisciplinary"], ["findings"], "VARCHAR(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["disciplinary", "hdisciplinary"], ["explanation"], " text NOT NULL",  0);

    $this->coreFunctions->sbcdropcolumn("disciplinary", "empcode");
    $this->coreFunctions->sbcdropcolumn("hdisciplinary", "empcode");


    $qry = "CREATE TABLE `clearance` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `doc` varchar(2) NOT NULL DEFAULT '',
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `jobtitle` varchar(45) NOT NULL DEFAULT '',
      `dept` varchar(45) NOT NULL DEFAULT '',
      `hired` datetime NULL,
      `lastdate` datetime NOT NULL,
      `cause` varchar(100) NOT NULL DEFAULT '',
      `emphead` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";

    $this->coreFunctions->sbccreatetable("clearance", $qry);
    $this->coreFunctions->sbcaddcolumn("clearance", "doc", "VARCHAR(2) NOT NULL DEFAULT ''",  0);

    $this->coreFunctions->sbcdropcolumn("clearance", "empcode");
    $this->coreFunctions->sbcdropcolumn("clearance", "empname");
    $this->coreFunctions->sbcdropcolumn("clearance", "emphead");
    $this->coreFunctions->sbcdropcolumn("clearance", "dept");

    $qry = "CREATE TABLE `hclearance` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `doc` varchar(2) NOT NULL DEFAULT '',
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `jobtitle` varchar(45) NOT NULL DEFAULT '',
      `dept` varchar(45) NOT NULL DEFAULT '',
      `hired` datetime NOT NULL,
      `lastdate` datetime NOT NULL,
      `cause` varchar(100) NOT NULL DEFAULT '',
      `emphead` varchar(45) NOT NULL DEFAULT '',
      `empname` varchar(45) NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("hclearance", $qry);
    $this->coreFunctions->sbcaddcolumn("hclearance", "doc", "VARCHAR(2) NOT NULL DEFAULT ''",  0);

    $this->coreFunctions->sbcaddcolumngrp(["clearance", "hclearance"], ["createby", "editby", "lockuser", "lockby"], "VARCHAR(100) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumngrp(["clearance", "hclearance"], ["createdate", "lockdate", "editdate", "resigned", "hired"], "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clearance", "hclearance"], ["empid", "empheadid", "deptid"], "INT(11) NOT NULL DEFAULT '0'",  0);
    $this->coreFunctions->sbcaddcolumngrp(["clearance", "hclearance"], ["status"], "VARCHAR(45) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcdropcolumn("hclearance", "empcode");
    $this->coreFunctions->sbcdropcolumn("hclearance", "empname");
    $this->coreFunctions->sbcdropcolumn("hclearance", "emphead");
    $this->coreFunctions->sbcdropcolumn("hclearance", "dept");

    $qry = "CREATE TABLE `personreq` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL,
      `dateid` datetime NOT NULL,
      `dept` varchar(45) NOT NULL,
      `personnel` varchar(45) NOT NULL,
      `dateneed` datetime NOT NULL,
      `job` varchar(45) NOT NULL,
      `class` varchar(45) NOT NULL,
      `headcount` int(10) unsigned NOT NULL,
      `hpref` varchar(45) NOT NULL,
      `agerange` varchar(45) NOT NULL,
      `gpref` varchar(45) NOT NULL,
      `rank` varchar(45) NOT NULL,
      `empstatus` varchar(45) NOT NULL,
      `reason` varchar(45) NOT NULL,
      `remark` varchar(500) NOT NULL,
      `refx` int(10) unsigned NOT NULL,
      `createdby` varchar(45) NOT NULL,
      `qualification` varchar(500) NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("personreq", $qry);

    $qry = "CREATE TABLE `hpersonreq` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL,
      `dateid` datetime NOT NULL,
      `dept` varchar(45) NOT NULL,
      `personnel` varchar(45) NOT NULL,
      `dateneed` datetime NOT NULL,
      `job` varchar(45) NOT NULL,
      `class` varchar(45) NOT NULL,
      `headcount` int(10) unsigned NOT NULL,
      `hpref` varchar(45) NOT NULL,
      `agerange` varchar(45) NOT NULL,
      `gpref` varchar(45) NOT NULL,
      `rank` varchar(45) NOT NULL,
      `empstatus` varchar(45) NOT NULL,
      `reason` varchar(45) NOT NULL,
      `remark` varchar(500) NOT NULL,
      `refx` int(10) NOT NULL,
      `createdby` varchar(45) NOT NULL,
      `postedby` varchar(45) NOT NULL,
      `posteddate` datetime NOT NULL,
      `qualification` varchar(500) NOT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("hpersonreq", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['createby', 'editby', 'lockuser', 'viewby', 'createdby'], "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['createdate', 'editdate', 'lockdate', 'viewdate', 'startdate', 'enddate', 'prdstart', 'prdend'], "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['refx', 'qa'], "int(10) unsigned NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ["branchid"], "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['empid'], "INT(11) UNSIGNED NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['empstatusid', 'notedid', 'recappid', 'appdisid'], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ["amount"], "decimal(18,6) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['empstatus', 'civilstatus', 'empmonths', 'empdays'], "varchar(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['hirereason',], "varchar(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['qualification', 'skill', 'jobsumm', 'educlevel', 'disapproved_remarks'], "varchar(500) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['manpower'], "TEXT NOT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(['hpersonreq'], ['posteddate'], "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("hpersonreq", "postedby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['status1', 'status2', 'status3'], "char(1) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['personreq', 'hpersonreq'], ['isapplied'], "TINYINT(2) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `joboffer` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `emptitle` varchar(45) NOT NULL DEFAULT '',
      `effectdate` datetime NOT NULL,
      `classrate` varchar(45) NOT NULL DEFAULT '',
      `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `empstat` varchar(45) NOT NULL DEFAULT '',
      `monthsno` int(10) NOT NULL DEFAULT '0',
      `empname` varchar(100) DEFAULT NULL,
      `jobtitle` varchar(45) DEFAULT NULL,
      `empno` varchar(100) DEFAULT NULL,
      `nodep` decimal(8,0) DEFAULT '0',
      `dcode` varchar(45) DEFAULT NULL,
      `dname` varchar(100) DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("joboffer", $qry);

    $this->coreFunctions->sbcaddcolumn("joboffer", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcdropcolumn("joboffer", "empcode");

    $this->coreFunctions->sbcaddcolumn("joboffer", "createby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("joboffer", "createdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("joboffer", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("joboffer", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("joboffer", "lockdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("joboffer", "lockuser", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("joboffer", "viewdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("joboffer", "viewby", "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("joboffer", "sectid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("joboffer", "paygroupid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("joboffer", "deptid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("joboffer", "roleid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);

    $qry = "CREATE TABLE `hjoboffer` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `docno` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime NOT NULL,
      `empcode` varchar(45) NOT NULL DEFAULT '',
      `emptitle` varchar(45) NOT NULL DEFAULT '',
      `effectdate` datetime NOT NULL,
      `classrate` varchar(45) NOT NULL DEFAULT '',
      `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `empstat` varchar(45) NOT NULL DEFAULT '',
      `monthsno` int(10) NOT NULL DEFAULT '0',
      `empname` varchar(100) DEFAULT NULL,
      `jobtitle` varchar(100) DEFAULT NULL,
      `empno` varchar(100) DEFAULT NULL,
      `nodep` decimal(8,0) DEFAULT '0',
      `dcode` varchar(45) DEFAULT NULL,
      `dname` varchar(100) DEFAULT NULL,
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("hjoboffer", $qry);

    $this->coreFunctions->sbcaddcolumn("hjoboffer", "empid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcdropcolumn("hjoboffer", "empcode");

    $this->coreFunctions->sbcaddcolumn("hjoboffer", "createby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "createdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "editby", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "lockdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "lockuser", "varchar(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "viewdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "viewby", "varchar(100) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("hjoboffer", "sectid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "paygroupid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "deptid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "roleid", "INT(11) NOT NULL DEFAULT '0' AFTER docno", 0);

    $this->coreFunctions->sbcaddcolumngrp(['joboffer', 'hjoboffer'], ['supervisorid'], "INT(11) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `codehead` (
      `artid` int(10) unsigned NOT NULL,
      `code` varchar(60) NOT NULL,
      `description` varchar(60) NOT NULL DEFAULT '',
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      PRIMARY KEY (`artid`,`code`,`line`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("codehead", $qry);

    $this->coreFunctions->sbcaddcolumn("codehead", "createby", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("codehead", "createdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("codehead", "editby", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("codehead", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("codehead", "lockdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("codehead", "lockuser", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("codehead", "viewdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("codehead", "viewby", "varchar(45) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `codedetail` (
      `line` int(10) unsigned NOT NULL,
      `d1a` varchar(45) NOT NULL DEFAULT '',
      `d1b` int(8) unsigned NOT NULL DEFAULT '0',
      `d2a` varchar(45) NOT NULL DEFAULT '',
      `d2b` int(8) unsigned NOT NULL DEFAULT '0',
      `d3a` varchar(45) NOT NULL DEFAULT '',
      `d3b` int(8) unsigned NOT NULL DEFAULT '0',
      `d4a` varchar(45) NOT NULL DEFAULT '',
      `d4b` int(8) unsigned NOT NULL DEFAULT '0',
      `d5a` varchar(45) NOT NULL DEFAULT '',
      `d5b` int(8) unsigned NOT NULL DEFAULT '0',
      `section` varchar(300) NOT NULL,
      `description` varchar(300) NOT NULL,
      `artid` int(10) unsigned NOT NULL,
      PRIMARY KEY (`line`,`artid`) USING BTREE
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("codedetail", $qry);

    $qry = "CREATE TABLE `empstatentry` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45) NOT NULL DEFAULT '',
      `empstatus` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("empstatentry", $qry);

    $qry = "CREATE TABLE `statchange` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL DEFAULT '',
      `stat` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("statchange", $qry);

    $qry = "CREATE TABLE `skillrequire` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL DEFAULT '',
      `skill` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("skillrequire", $qry);

    $qry = "CREATE TABLE `jobthead` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `trno` int(4) unsigned NOT NULL,
      `docno` varchar(45) NOT NULL,
      `jobtitle` varchar(100) DEFAULT NULL,
      `createdby` varchar(45) NOT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("jobthead", $qry);

    $this->coreFunctions->sbcaddcolumn("jobthead", "createby", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("jobthead", "createdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("jobthead", "editby", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("jobthead", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("jobthead", "lockdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("jobthead", "lockuser", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("jobthead", "viewdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("jobthead", "viewby", "varchar(45) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("jobthead", "trno", "INT(4) UNSIGNED DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("jobthead", "createdby", "varchar(45) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `jobtdesc` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `trno` int(4) unsigned NOT NULL,
      `description` varchar(1000) DEFAULT NULL,
      PRIMARY KEY (`line`,`trno`) USING BTREE
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("jobtdesc", $qry);

    $qry = "CREATE TABLE `jobtskills` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `trno` int(4) unsigned NOT NULL,
      `skills` varchar(200) DEFAULT NULL,
      PRIMARY KEY (`line`,`trno`) USING BTREE
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("jobtskills", $qry);

    $qry = "CREATE TABLE `emprequire` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(50) NOT NULL DEFAULT '',
      `req` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("emprequire", $qry);

    $qry = "CREATE TABLE `preemp` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45) NOT NULL DEFAULT '',
      `test` varchar(60) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("preemp", $qry);

    $qry = "CREATE TABLE `hrisnum` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `doc` varchar(2) NOT NULL DEFAULT '',
      `bref` varchar(3) NOT NULL DEFAULT '',
      `seq` int(10) unsigned NOT NULL DEFAULT '0',
      `docno` varchar(20) NOT NULL DEFAULT '',
      `center` varchar(20) NOT NULL DEFAULT '',
      `postdate` datetime,
      `postedby` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hrisnum", $qry);

    $this->coreFunctions->sbcaddcolumn("hrisnum", "postedby", "VARCHAR(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcdropcolumn("hrisnum", "postby");

    $qry = "CREATE TABLE `del_hrisnum_log` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `docno` varchar(20) NOT NULL DEFAULT '',
      `field` varchar(45) NOT NULL DEFAULT '',
      `code` varchar(45) NOT NULL DEFAULT '',
      `userid` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      KEY `Index_1` (`trno`),
      KEY `Index_2` (`docno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("del_hrisnum_log", $qry);

    $qry = "CREATE TABLE `hrisnum_log` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `field` varchar(45) NOT NULL DEFAULT '',
      `oldversion` varchar(900) NOT NULL,
      `userid` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      KEY `Index_1` (`trno`),
      KEY `Index_2` (`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrisnum_log", $qry);

    $this->coreFunctions->sbcaddcolumn("hrisnum_log", "userid", "VARCHAR(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("del_hrisnum_log", "userid", "VARCHAR(100) NOT NULL DEFAULT ''",  1);

    $this->coreFunctions->sbcaddcolumn("acontacts", "empid", "INTEGER NOT NULL DEFAULT '0', ADD PRIMARY KEY  USING BTREE( `empid`)", 0);

    $this->coreFunctions->sbcaddcolumn("adependents", "empid", "INTEGER NOT NULL DEFAULT '0',DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE( line,`empid`)", 0);

    $this->coreFunctions->sbcaddcolumn("aeducation", "empid", "INTEGER NOT NULL DEFAULT '0',DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE( line,`empid`)", 0);

    $this->coreFunctions->sbcaddcolumn("aemployment", "empid", "INTEGER NOT NULL DEFAULT '0',DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE( line,`empid`)", 0);


    $this->coreFunctions->sbcaddcolumn("apreemploy", "emptestid", "INT(11) NOT NULL DEFAULT '0'", 0);
    $qry = "CREATE TABLE `ssstab` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `bracket` int(4) unsigned NOT NULL DEFAULT '0',
      `range1` decimal(9,2) NOT NULL DEFAULT '0.00',
      `range2` decimal(9,2) NOT NULL DEFAULT '0.00',
      `sssee` decimal(9,2) NOT NULL DEFAULT '0.00',
      `ssser` decimal(9,2) NOT NULL DEFAULT '0.00',
      `eccer` decimal(9,2) NOT NULL DEFAULT '0.00',
      `ssstotal` decimal(9,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
    $this->coreFunctions->sbccreatetable("ssstab", $qry);

    $qry = "CREATE TABLE `hdmftab` (
      `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
      `bracket` int(4) unsigned NOT NULL,
      `range1` decimal(9,2) NOT NULL DEFAULT '0.00',
      `range2` decimal(9,2) NOT NULL DEFAULT '0.00',
      `hdmfmulti` decimal(9,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`bracket`) USING BTREE,
      KEY `Index_2` (`line`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
    $this->coreFunctions->sbccreatetable("hdmftab", $qry);

    $qry = "CREATE TABLE `rank` (
      line int(10) unsigned NOT NULL AUTO_INCREMENT,
      code varchar(45) NOT NULL,
      rank varchar(100) NOT NULL,
      PRIMARY KEY (line)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rank", $qry);

    $this->coreFunctions->sbcaddcolumn("rank", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("rank", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcdropcolumn('disciplinary', 'line');
    $this->coreFunctions->sbcaddcolumn("disciplinary", "sectionno", "int(10) unsigned NOT NULL", 0);
    $this->coreFunctions->sbcdropcolumn('hdisciplinary', 'line');
    $this->coreFunctions->sbcaddcolumn("hdisciplinary", "sectionno", "int(10) unsigned NOT NULL", 0);

    $this->coreFunctions->sbcdropcolumn('codehead', 'line');
    $this->coreFunctions->sbcaddcolumn("codehead", "artid", "int(10) unsigned NOT NULL auto_increment", 1);

    $this->coreFunctions->sbcaddcolumn("joboffer", "doc", "varchar(45) NOT NULL DEFAULT ''");
    $this->coreFunctions->sbcaddcolumn("hjoboffer", "doc", "varchar(45) NOT NULL DEFAULT ''");

    $this->coreFunctions->sbcaddcolumn("codedetail", "line", "int(10) unsigned NOT NULL auto_increment", 1);

    #GLEN 01.26.2021
    $this->coreFunctions->sbcaddcolumn("client", "tin", "VARCHAR(100) NOT NULL DEFAULT ''",  1);
    $this->coreFunctions->sbcaddcolumn("client", "bizstyle", "VARCHAR(500) NOT NULL DEFAULT ''",  1);

    //fpy 05.01.2021
    $this->coreFunctions->execqrynolog("update app set appdate=null where appdate='0000-00-00'");
    $this->coreFunctions->execqrynolog("update app set hired=null where hired='0000-00-00'");
    $this->coreFunctions->execqrynolog("update hturnoveritemhead set posteddate=null where posteddate='0000-00-00'");
    $this->coreFunctions->execqrynolog("update clearance set hired=null where hired='0000-00-00'");


    //KIM 11.11.2021
    $this->coreFunctions->sbcaddcolumn("adependents", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("adependents", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("aeducation", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("aeducation", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("aemployment", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("aemployment", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("arequire", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("arequire", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("apreemploy", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("apreemploy", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("codedetail", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("codedetail", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("empstatentry", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("empstatentry", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("statchange", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("statchange", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("skillrequire", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("skillrequire", "editdate", "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["skillrequire"], ["skill"], "VARCHAR(200) NOT NULL DEFAULT ''", 1);

    $this->coreFunctions->sbcaddcolumn("jobtdesc", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("jobtdesc", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("emprequire", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("emprequire", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("preemp", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("preemp", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(["hrisnum"], ["yr", "statid"], "int(11) NOT NULL DEFAULT '0'");


    $this->coreFunctions->sbcaddcolumngrp(["disciplinary", "hdisciplinary"], ['prepared', 'supervisor', 'notedby1', 'notedby2', 'notedby3', 'notedby4', 'position1', 'position2', 'position3', 'position4'], "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    // 2024.01.13 - FRED
    $this->coreFunctions->sbcaddcolumngrp(["leavetrans", "timecard"], ["isok"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["joboffer", "hjoboffer"], ["paymode"], "VARCHAR(45) NOT NULL DEFAULT ''",  1);

    $qry = "CREATE TABLE `hrisnum_picture` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(20) NOT NULL DEFAULT '0',
      `title` varchar(2000) NOT NULL DEFAULT '',
      `picture` varchar(300) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_hrisnum_picture` (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("hrisnum_picture", $qry);

    $qry = "CREATE TABLE `app_picture` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(20) NOT NULL DEFAULT '0',
      `title` varchar(2000) NOT NULL DEFAULT '',
      `picture` varchar(300) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_app_picture` (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("app_picture", $qry);


    $qry = "CREATE TABLE `violation` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `empid` int(11) unsigned NOT NULL DEFAULT '0',
      `remarks` varchar(500) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
   	  `createby` varchar(100) NOT NULL DEFAULT '',
			`createdate` DATETIME DEFAULT NULL,
      `lockuser` VARCHAR(100) NOT NULL DEFAULT '',
      `lockdate` DATETIME DEFAULT NULL,
			`closeby` varchar(100) NOT NULL DEFAULT '',
			`closedate` DATETIME DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_violation` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("violation", $qry);

    $qry = "CREATE TABLE `hviolation` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` VARCHAR(20) NOT NULL DEFAULT '',
      `docno` VARCHAR(20) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `empid` int(11) unsigned NOT NULL DEFAULT '0',
      `remarks` varchar(500) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) NOT NULL DEFAULT '',
   	  `createby` varchar(100) NOT NULL DEFAULT '',
			`createdate` DATETIME DEFAULT NULL,
      `lockuser` VARCHAR(100) NOT NULL DEFAULT '',
      `lockdate` DATETIME DEFAULT NULL,
      `closedate` DATETIME DEFAULT NULL,
			`closeby` varchar(100) NOT NULL DEFAULT '',
      `posteddate`DATETIME DEFAULT NULL,
      `postedby` varchar(100) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      KEY `Index_violation` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hviolation", $qry);

    $qry = "CREATE TABLE `hrisnum_stat` (
            `trno` int(10) unsigned NOT NULL DEFAULT '0',
            `field` varchar(45) NOT NULL DEFAULT '',
            `oldversion` varchar(900) NOT NULL DEFAULT '',
            `userid` varchar(100) NOT NULL DEFAULT '',
            `dateid` datetime DEFAULT NULL,
            KEY `Index_1` (`trno`),
            KEY `Index_2` (`dateid`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrisnum_stat", $qry);



    $qry = "CREATE TABLE  `rashead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `deptid` int(11) NOT NULL DEFAULT '0',
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
      KEY `Index_rashead` (`docno`,`dateid`)) 
      ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("rashead", $qry);


    $qry = "CREATE TABLE  `hrashead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `dateid` datetime DEFAULT NULL,
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `deptid` int(11) NOT NULL DEFAULT '0',
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
      KEY `Index_hrashead` (`docno`,`dateid`)) 
      ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hrashead", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["rashead", "hrashead"], ["branchid", "notedid", "ndesid", "supid", "manid", "category", "roleid", "divid", "sectid"], "int(10) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["rashead", "hrashead"], ["tdate1"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcdropcolumngrp(["rashead", "hrashead"], ["brname", "brcode", "notedby", "superior", "manager", "position", "catname", "catid", "effectdate"]);

    $qry = "CREATE TABLE  `rasstock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `empid` int(10) unsigned NOT NULL DEFAULT '0',
      `jobid` int(10) NOT NULL DEFAULT '0',
      `branchid` int(10) NOT NULL DEFAULT '0',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("rasstock", $qry);


    $qry = "CREATE TABLE  `hrasstock` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `empid` int(10) unsigned NOT NULL DEFAULT '0',
      `jobid` int(10) NOT NULL DEFAULT '0',
      `branchid` int(10) NOT NULL DEFAULT '0',
      `sortline` int(11) NOT NULL DEFAULT '0',
      `encodeddate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `encodedby` varchar(20) DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `editby` varchar(20) DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_trno` (`trno`),
      KEY `Index_line` (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hrasstock", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["rasstock", "hrasstock"], ["branchid", "supid", "category", "roleid", "divid", "deptid", "sectid", "ndesid", "tobranchid", "todeptid", "froleid"], "int(10) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["rasstock", "hrasstock"], ["tdate1"], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["rasstock", "hrasstock"], ["rem", "locname"], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["rasstock", "hrasstock"], ["supervisorid", "deptid"], "int(10) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `qnhead` (
        `qid` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
        `docno` VARCHAR(20) NOT NULL DEFAULT '',
        `qtype` VARCHAR(100) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',         
        `rem` VARCHAR(500) NOT NULL DEFAULT '',
        `instructions` VARCHAR(500) NOT NULL DEFAULT '',
        `startdate` DATETIME DEFAULT NULL,
        `enddate` DATETIME DEFAULT NULL,
        `gp` DECIMAL(10,2) NOT NULL DEFAULT 0,
        `runtime` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `points` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`qid`)
        ) ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("qnhead", $qry);

    $qry = "CREATE TABLE `qnstock` (
        `qid` INTEGER UNSIGNED NOT NULL DEFAULT 0,
        `line` INTEGER UNSIGNED NOT NULL DEFAULT 0,
        `sortline` INTEGER UNSIGNED NOT NULL DEFAULT 0,
        `question` VARCHAR(1000) NOT NULL DEFAULT '',
        `section` VARCHAR(100) NOT NULL DEFAULT '',
        `objtype` INT(2) NOT NULL DEFAULT 0,
        `a` VARCHAR(100) NOT NULL DEFAULT '',
        `b` VARCHAR(100) NOT NULL DEFAULT '',
        `c` VARCHAR(100) NOT NULL DEFAULT '',
        `d` VARCHAR(100) NOT NULL DEFAULT '',
        `e` VARCHAR(100) NOT NULL DEFAULT '',
        `ans` VARCHAR(10) NOT NULL DEFAULT '',
        `answord` VARCHAR(45) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',        
        `points` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `picture` VARCHAR(45) NOT NULL DEFAULT '',
        `isinactive` tinyint(1) unsigned DEFAULT '0',
        PRIMARY KEY (`qid`, `line`),
        KEY `Index_A` (`a`),
        KEY `Index_B` (`b`),
        KEY `Index_C` (`c`),
        KEY `Index_D` (`d`),
        KEY `Index_E` (`e`),
        KEY `Index_Ans` (`ans`),
        KEY `Index_AnsWord` (`answord`)
        ) ENGINE = MyISAM;";

    //objtype
    //0 - multiple choice (radio button)
    //1 - word answer (input)
    //2 - multiple select (checkbox)
    //3 - question with image (multiple choice)
    //4 - spelling (radio button only no question)
    $this->coreFunctions->sbccreatetable("qnstock", $qry);

    $this->coreFunctions->sbcaddcolumn("qnstock", "isinactive", "tinyint(1) unsigned DEFAULT '0'");

    $qry = "CREATE TABLE `examinees` (
        `clientid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `appid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `qid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `createby` VARCHAR(100) NOT NULL DEFAULT 0,
        `createdate` DATETIME DEFAULT NULL,
        INDEX `Index_ClientID`(`clientid`),
        INDEX `Index_AppID`(`appid`),
        INDEX `Index_QID`(`qid`)
      )ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("examinees", $qry);

    $qry = "CREATE TABLE `app_stat` (
            `trno` int(10) unsigned NOT NULL DEFAULT '0',
            `field` varchar(45) NOT NULL DEFAULT '',
            `oldversion` varchar(900) NOT NULL DEFAULT '',
            `userid` varchar(100) NOT NULL DEFAULT '',
            `dateid` datetime DEFAULT NULL,
            KEY `Index_1` (`trno`),
            KEY `Index_2` (`dateid`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("app_stat", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["app_stat"], ['dateid2', 'dateid3'], "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["app_stat"], ['remarks'], "varchar(500) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['app'], ['username', 'password'], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['app'], ['userid', "branchid", "hqtrno"], "int(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['qahead'], ['startdate', 'enddate'], "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(['qahead'], ['appid', 'empid', 'qid', 'total'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['qastock'], ['qid', 'qline'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['qastock'], ['ans', 'answord'], "varchar(45) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `regularization` (
        `line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `description` VARCHAR(500) NOT NULL DEFAULT '',
        `num` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `isdays` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
        `ishrs` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
        `sortline` INT(11) UNSIGNED NOT NULL DEFAULT 0,
        `createby` VARCHAR(100) NOT NULL DEFAULT '',
        `createdate` DATETIME DEFAULT NULL,
        `editby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
        `isinactive` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (`line`))";
    $this->coreFunctions->sbccreatetable("regularization", $qry);

    $qry = "CREATE TABLE `regprocess` (
          `regid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
          `empid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
          `expiration` DATETIME DEFAULT NULL,
          `createby` VARCHAR(100) NOT NULL DEFAULT '',
          `createdate` DATETIME DEFAULT NULL,
          `evaluated` DATETIME DEFAULT NULL,
          `evaluatedby` INT(11) UNSIGNED NOT NULL DEFAULT 0,
          PRIMARY KEY (`regid`, `empid`),
          INDEX `Index_Expiration`(`expiration`),
          INDEX `Index_Evaluated`(`evaluated`))
        ENGINE = MyISAM;";

    $this->coreFunctions->sbccreatetable("regprocess", $qry);

    $this->coreFunctions->sbcaddcolumn("regprocess", "reaccess", "DATETIME DEFAULT NULL", 0);

    $qry = "CREATE TABLE `generation` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `generation` varchar(50) NOT NULL DEFAULT '',
      `startyear` varchar(4) NOT NULL DEFAULT '',
      `endyear` varchar(4) NOT NULL DEFAULT '',
       `editby` VARCHAR(100) NOT NULL DEFAULT '',
        `editdate` DATETIME DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("generation", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['empstatentry'], ['sortline'], "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `hrisnumtodo` LIKE `transnumtodo`";
    $this->coreFunctions->sbccreatetable("hrisnumtodo", $qry);

    $qry = "CREATE TABLE `itinerary` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `dateid` DATETIME DEFAULT NULL,
      `empid` INT(11) NOT NULL DEFAULT '0',
      `startdate` DATETIME DEFAULT NULL,
      `enddate` DATETIME DEFAULT NULL,
      `remarks` VARCHAR(500) NOT NULL DEFAULT '',
      `status` VARCHAR(1) NOT NULL DEFAULT '',
      `submitdate` DATETIME DEFAULT NULL,
      `editby` VARCHAR(100) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL,
      `createby` VARCHAR(100) NOT NULL DEFAULT '',
      `createdate` DATETIME DEFAULT NULL,
      `approvedby` VARCHAR(100) NOT NULL DEFAULT '',
      `approvedate` DATETIME DEFAULT NULL,
      `approvedrem` VARCHAR(500) NOT NULL DEFAULT '',
      `disapprovedby` VARCHAR(100) NOT NULL DEFAULT '',
      `disapprovedate` DATETIME DEFAULT NULL,
      PRIMARY KEY (`trno`,`empid`)
    ) ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("itinerary", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["itinerary"], ["approvedbuddate", "disapprovedate"], "datetime DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumngrp(["itinerary"], ["approverem", "expensetype", "approvedbudby"], "VARCHAR(100) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["itinerary"], ["lengthstay"], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["itinerary"], ["mealamt", "mealnum", "texpense", "lodgeexp", "misc", "gas", "ext"], "decimal(19,2) NOT NULL DEFAULT '0.00'", 1);


    $this->coreFunctions->sbcaddcolumn("codedetail", "description", "VARCHAR(1000) NOT NULL DEFAULT ''", 1);

    $qry = "CREATE TABLE `emploc` (
      line int(10) unsigned NOT NULL AUTO_INCREMENT,
      locname varchar(100) NOT NULL,
      PRIMARY KEY (line)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("emploc", $qry);

    $this->coreFunctions->sbcaddcolumn("emploc", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("emploc", "editdate", "datetime DEFAULT NULL", 0);
    $qry = "CREATE TABLE `moduleapproval` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `modulename` VARCHAR(100) DEFAULT NULL,
      `labelname` VARCHAR(100) DEFAULT NULL,
      `countsupervisor` INT(10) unsigned NOT NULL DEFAULT 0,
      `countapprover` INT(10) unsigned NOT NULL DEFAULT 0,
      `approverseq` VARCHAR(100) DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("moduleapproval", $qry);

    $qry = "CREATE TABLE `approvers` (
      `trno` int(10) unsigned NOT NULL DEFAULT 0,
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `clientid` int(10) unsigned NOT NULL DEFAULT 0,
      `isapprover` tinyint(1) unsigned DEFAULT 0,
      `issupervisor` tinyint(1) unsigned DEFAULT 0,
      PRIMARY KEY (`trno`, `line`)
    ) ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("approvers", $qry);

    $qry = "CREATE TABLE `pendingapp` (
      `trno` int(10) unsigned NOT NULL DEFAULT 0,
      `line` int(10) unsigned NOT NULL DEFAULT 0,
      `doc` varchar(100) DEFAULT NULL,
      `clientid` int(10) unsigned NOT NULL DEFAULT 0
    ) ENGINE = MyISAM;";
    $this->coreFunctions->sbccreatetable("pendingapp", $qry);

    $this->coreFunctions->sbcaddcolumn("moduleapproval", "sbcpendingapp", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("pendingapp", "approver", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("undertime", "submitdate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("paccount", "isportalloan", "tinyint(1) unsigned DEFAULT '0'", 0);

    $qry = "CREATE TABLE  `designation` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `empid` int(10) unsigned NOT NULL DEFAULT '0',
      `branchid` int(10) NOT NULL DEFAULT '0',
      `roleid` int(10) NOT NULL DEFAULT '0',
      `jobid` int(10) NOT NULL DEFAULT '0',
      `effectdate` DATETIME DEFAULT NULL,
      `category` int(10) NOT NULL DEFAULT '0',
      `locid` int(10) NOT NULL DEFAULT '0',
      `notation` varchar(200) NOT NULL DEFAULT '',
      `encodeddate` DATETIME DEFAULT NULL,
      `encodedby` varchar(100) DEFAULT '',
      KEY `Index_trno` (`trno`,`line`,`empid`,`branchid`,`roleid`,`jobid`,`locid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("designation", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["designation"], ["linex", 'supervisorid', 'divid', 'deptid', 'sectid'], "int(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["designation"], ["isrole"], "tinyint(2) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["designation"], ["dateapplied"], "DATETIME DEFAULT NULL", 0);

    $qry = "CREATE TABLE `hpersonreqdetail` (
      `trno` int(10) unsigned NOT NULL,
      `line` int(10) unsigned NOT NULL,
      `appid` varchar(45) NOT NULL DEFAULT '',
      `createdate` DATETIME DEFAULT NULL,
      `createby` varchar(100) DEFAULT '',
      PRIMARY KEY (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hpersonreqdetail", $qry);

    $qry = "CREATE TABLE `cmevaluate` (
      `trno` int(10) unsigned NOT NULL,
      `empid` int(10) unsigned NOT NULL DEFAULT '0',
      `dateevaluated` DATETIME DEFAULT NULL,
      PRIMARY KEY (`trno`,`empid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("cmevaluate", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["regularization"], ["isevaluator"], "TINYINT(1) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("regprocess", "line", "int(11) unsigned NOT NULL AUTO_INCREMENT, ADD KEY(line)", 0);

    $this->coreFunctions->sbcaddcolumngrp(["eschange", "heschange"], ["fcola", "tcola"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eschange", "heschange"], ["fsalarytype"], "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["eschange", "heschange"], ["feffdate"], "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumngrp(["ratesetup"], ["hjtrno", "hstrno"], "bigint(20) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["itinerary"], ["approvedby2", "disapprovedby2"], "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["itinerary"], ["approvedrem2", "approverem2"], "VARCHAR(200) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["itinerary"], ["approvedate2", "disapprovedate2"], "DATETIME DEFAULT NULL", 0);

    $this->coreFunctions->sbcaddcolumn("itinerary", "status2", "VARCHAR(1) NOT NULL DEFAULT 'E'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clearance", "hclearance"], ["amount", "deduction"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clearance", "hclearance"], ["witness", "witness2"], "int(10) NOT NULL DEFAULT '0'", 1);

    $qry = "CREATE TABLE `datesuspension` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` INT(10) unsigned NOT NULL AUTO_INCREMENT,
      `empid` int(10) unsigned NOT NULL DEFAULT '0',
      `startdate` DATETIME DEFAULT NULL,
      `enddate` DATETIME DEFAULT NULL,
      `createdate` DATETIME DEFAULT NULL,
      `createby` VARCHAR(100) NOT NULL DEFAULT '',
      `editdate` DATETIME DEFAULT NULL,
      `editby` VARCHAR(100) NOT NULL DEFAULT '',
      KEY `Index_Line` (`line`),
      KEY `Index_trno` (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("datesuspension", $qry);
    $this->coreFunctions->sbcaddcolumngrp(["obapplication", "itinerary"], ["islatefilling"], "tinyint(1) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumn("app", "jdateid", "DATE DEFAULT NULL", 0);


    $qry = "CREATE TABLE  `cljobs` (
	  `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `clientid` int(10) NOT NULL DEFAULT 0,
    `jobid` int(10) NOT NULL DEFAULT 0,
	  `qty` int(10) NOT NULL DEFAULT 0,
    `editdate` DATETIME DEFAULT NULL,
    `editby` VARCHAR(100) NOT NULL DEFAULT '',	  
	  PRIMARY KEY (`line`) USING BTREE,
	  KEY `Index_ClientID` (`clientid`)
	) ENGINE=MyISAM;";
    $this->coreFunctions->sbccreatetable("cljobs", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["cljobs"], ["jobid"], "INT(10) NOT NULL DEFAULT '0'", 1);

    $this->coreFunctions->sbcaddcolumn("allowsetup", "editdate", "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumn("allowsetup", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
  } //end function

} // end class