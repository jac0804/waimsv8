<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;

class customersupport
{

  private $coreFunctions;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
  } //end fn


  public function tableupdatecustomersupport()
  {
    $qry = "CREATE TABLE `csstickethead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `client` varchar(15) NOT NULL DEFAULT '',
      `dept` varchar(15) NOT NULL DEFAULT '',
      `dateid` date DEFAULT NULL,
      `rem` text DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `sitrno` bigint(20) NOT NULL DEFAULT '0',      
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
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("csstickethead", $qry);

    $this->coreFunctions->sbcaddcolumn("csstickethead", "sitrno", "bigint(20) NOT NULL DEFAULT '0'", 1);


    $qry = "CREATE TABLE `hcsstickethead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `clientid` int(4) unsigned NOT NULL DEFAULT 0,
      `deptid` int(4) unsigned NOT NULL DEFAULT 0,
      `dateid` date DEFAULT NULL,
      `rem` text DEFAULT NULL,
      `yourref` varchar(25) NOT NULL DEFAULT '',
      `ourref` varchar(25) NOT NULL DEFAULT '',
      `sitrno` bigint(20) NOT NULL DEFAULT '0',
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
      PRIMARY KEY (`trno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("hcsstickethead", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['csstickethead', 'hcsstickethead'], ['orderid', 'channelid', 'empid', 'branchid', 'compid'], "int(11) NOT NULL DEFAULT '0'");
    $this->coreFunctions->sbcaddcolumngrp(['csstickethead', 'hcsstickethead'], ['clienttype'], "varchar(150) NOT NULL DEFAULT ''", 0);
    $qry = "CREATE TABLE `csscomment` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `comment` text NOT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("csscomment", $qry);

    $qry = "CREATE TABLE `hcsscomment` (
      `line` int(10)  unsigned NOT NULL,
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `createdate` datetime DEFAULT NULL,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `comment` text NOT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("hcsscomment", $qry);
    $this->coreFunctions->sbcaddcolumngrp(['csscomment', 'hcsscomment'], ['ispa'], "tinyint(1) NOT NULL DEFAULT '1'", 0);
  }
} // end class