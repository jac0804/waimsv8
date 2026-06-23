<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class bms
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



  public function tableupdatebms($config)
  {
    ini_set('max_execution_time', 0);
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['isbrgy'], "tinyint(1) not null default '0'");
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ['hhold', 'settlertype', 'rvoter', 'skill1', 'skill2', 'purposedl', 'rcno', 'rcplace', 'brgcert', 'occupation1', 'occupation2', 'relation'], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ['attainment1', 'attainment2'], "VARCHAR(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ['precintno'], "VARCHAR(10) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ["bday2"], "datetime DEFAULT NULL");
    $this->coreFunctions->sbcaddcolumngrp(["contacts"], ['ownername', 'ownertype', 'editdate', 'editby'], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["contacts"],  ['line'], "INT(11) UNSIGNED NOT NULL", 0);
    $this->coreFunctions->execqrynolog("ALTER TABLE contacts CHANGE line line INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY");
    $this->coreFunctions->sbcaddcolumngrp(["glhead", "lahead"], ['purposeid'], "INT(11) NOT NULL DEFAULT '0'", 1);
    // $this->coreFunctions->sbcaddcolumngrp(["glhead", "lahead"], ['bstype', 'ownertype'], "VARCHAR(50) NOT NULL DEFAULT ''", 1); // used in MN/JU
    // $this->coreFunctions->sbcaddcolumngrp(["glhead", "lahead"], ['ownername', 'owneraddr', 'contact'], "VARCHAR(150) NOT NULL DEFAULT ''", 1); // used in MN/JU
    $this->coreFunctions->sbcaddcolumngrp(['glhead', 'lahead'], ['truid', 'bonafideid'], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['reqcategory'], ['istru', 'isbonafide'], "tinyint(1) not null default '0'");
    $this->coreFunctions->sbcaddcolumngrp(['reqcategory'], ['encodeddate'], 'timestamp not null default CURRENT_TIMESTAMP', 0);
    $this->coreFunctions->sbcaddcolumngrp(['clientinfo'], ['chassisno', 'sidecarno'], "VARCHAR(150) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ['sentence1', 'sentence2', 'sentence3', 'bullet1', 'bullet2', 'bullet3', 'bullet4', 'bullet5', 'bullet6', 'bullet7'], "VARCHAR(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['istru'], "tinyint(1) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["gldetail", "ladetail"], ['type'], "VARCHAR(45) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['isbrgyemp', 'isbrgywl'], "tinyint(1) not null default '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["mtsofh"], "varchar(1000) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcdropcolumngrp(["glhead", "lahead"], ["bstype", "ownertype", "ownername", "owneraddr", "contact"]); // mga ginamit sa MN/JU

    $qry = "CREATE TABLE mnhead like sohead";
    $this->coreFunctions->sbccreatetable("mnhead", $qry);

    $qry = "CREATE TABLE hmnhead like hsohead";
    $this->coreFunctions->sbccreatetable("hmnhead", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['bstype', 'ownertype'], "VARCHAR(50) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['ownername', 'owneraddr', 'contact'], "VARCHAR(150) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['purposeid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['orderno'],  "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['crno', 'conaddr'],   "varchar(300) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['refdate'],  "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['layref'],  "varchar(20) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE  `juhead` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `doc` char(2) NOT NULL DEFAULT '',
      `docno` char(20) NOT NULL,
      `clientname` varchar(150) DEFAULT NULL,
      `address` varchar(200) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `bstype` varchar(50) NOT NULL DEFAULT '',
      `contact` varchar(50) NOT NULL DEFAULT '',
      `ownername` varchar(150) NOT NULL DEFAULT '',
      `owneraddr` varchar(200) NOT NULL DEFAULT '',
      `orderno` varchar(50) NOT NULL DEFAULT '',
      `ourref` varchar(100) NOT NULL DEFAULT '',
      `crno` varchar(50) NOT NULL DEFAULT '',
      `conaddr` varchar(500) NOT NULL DEFAULT '',
      `creditinfo` varchar(1000) NOT NULL DEFAULT '',
      `petrno` int(11) NOT NULL DEFAULT '0',
      `lockuser` varchar(50) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,
      `createdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `createby` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(50) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      `viewby` varchar(50) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      KEY `Index_juhead` (`docno`,`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1
    ";
    $this->coreFunctions->sbccreatetable("juhead", $qry);

    $qry = "CREATE TABLE hjuhead like juhead";
    $this->coreFunctions->sbccreatetable("hjuhead", $qry);

    $qry = "CREATE TABLE mhhead like juhead";
    $this->coreFunctions->sbccreatetable("mhhead", $qry);

    $qry = "CREATE TABLE hmhhead like juhead";
    $this->coreFunctions->sbccreatetable("hmhhead", $qry);

    $this->coreFunctions->sbcaddcolumngrp(["mhhead", "hmhhead"], ['rem'],     "varchar(500) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mhhead", "hmhhead"], ['rem2'],    "varchar(500) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mhhead", "hmhhead"], ['refdate'], "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mhhead", "hmhhead"], ['layref'],  "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mhhead", "hmhhead"], ['mtsofh'],  "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(["mhhead", "hmhhead", "juhead", "hjuhead", "mnhead", "hmnhead"], ['isfinish'],  "tinyint(1) not null default '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["mnhead", "hmnhead"], ['petrno'],  "int(11) NOT NULL DEFAULT '0'", 0);
  } //end function
} // end class