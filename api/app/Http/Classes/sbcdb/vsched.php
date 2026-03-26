<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;

class vsched
{
    private $coreFunctions;

    public function __construct()
    {
        $this->coreFunctions = new coreFunctions;
    } //end fn


    public function tableupdatevsched($config)
    {

        $qry = "CREATE TABLE vrhead (
    trno bigint(20) NOT NULL DEFAULT '0',
    doc char(2) NOT NULL DEFAULT '',
    docno char(20) NOT NULL,
    clientid INT(11) NOT NULL DEFAULT '0',
    deptid INT(11) NOT NULL DEFAULT '0',
    driverid INT(11) NOT NULL DEFAULT '0',
    vehicleid INT(11) NOT NULL DEFAULT '0',
    dateid datetime DEFAULT NULL,
    schedin datetime DEFAULT NULL,
    schedout datetime DEFAULT NULL,
    rem mediumtext NOT NULL,
    status varchar(45) NOT NULL DEFAULT '',
    lockuser varchar(100) NOT NULL DEFAULT '',
    lockdate datetime DEFAULT NULL,
    createdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createby varchar(100) NOT NULL DEFAULT '',
    editby varchar(100) NOT NULL DEFAULT '',
    editdate datetime DEFAULT NULL,
    viewby varchar(100) NOT NULL DEFAULT '',
    viewdate datetime DEFAULT NULL,
    approvedby varchar(100) NOT NULL DEFAULT '',
    approveddate datetime DEFAULT NULL,
    PRIMARY KEY (trno),
    KEY Index_head (docno,clientid,dateid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("vrhead", $qry);

        $qry = "CREATE TABLE hvrhead (
    trno bigint(20) NOT NULL DEFAULT '0',
    doc char(2) NOT NULL DEFAULT '',
    docno char(20) NOT NULL,
    clientid INT(11) NOT NULL DEFAULT '0',
    deptid INT(11) NOT NULL DEFAULT '0',
    driverid INT(11) NOT NULL DEFAULT '0',
    vehicleid INT(11) NOT NULL DEFAULT '0',
    dateid datetime DEFAULT NULL,
    schedin datetime DEFAULT NULL,
    schedout datetime DEFAULT NULL,
    rem mediumtext NOT NULL,
    status varchar(45) NOT NULL DEFAULT '',
    lockuser varchar(100) NOT NULL DEFAULT '',
    lockdate datetime DEFAULT NULL,
    createdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createby varchar(100) NOT NULL DEFAULT '',
    editby varchar(100) NOT NULL DEFAULT '',
    editdate datetime DEFAULT NULL,
    viewby varchar(100) NOT NULL DEFAULT '',
    viewdate datetime DEFAULT NULL,
    approvedby varchar(100) NOT NULL DEFAULT '',
    approveddate datetime DEFAULT NULL,
    PRIMARY KEY (trno),
    KEY Index_head (docno,clientid,dateid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("hvrhead", $qry);

        $qry = "CREATE TABLE vrstock (
    trno bigint(20) NOT NULL DEFAULT '0',
    line bigint(20) NOT NULL DEFAULT '0',
    clientid INT(11) NOT NULL DEFAULT '0',
    createdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createby varchar(100) NOT NULL DEFAULT '',
    KEY Index_stock (trno,clientid,line)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("vrstock", $qry);

        $qry = "CREATE TABLE hvrstock (
    trno bigint(20) NOT NULL DEFAULT '0',
    line bigint(20) NOT NULL DEFAULT '0',
    clientid INT(11) NOT NULL DEFAULT '0',
    createdate timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    createby varchar(100) NOT NULL DEFAULT '',
    KEY Index_stock (trno,clientid,line)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("hvrstock", $qry);

        $qry = "CREATE TABLE `vritems` (
    `pline` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `trno` INT(11) NOT NULL DEFAULT '0',
    `line` INT(11) NOT NULL DEFAULT '0',
    `itemid` int(11) unsigned NOT NULL DEFAULT 0,
    `uom` varchar(15) NOT NULL DEFAULT '',
    `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(100) NOT NULL DEFAULT '',
    KEY `Index_1` (`pline`, `trno`, `line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("vritems", $qry);

        $qry = "CREATE TABLE `hvritems` (
    `pline` int(11) unsigned NOT NULL,
    `trno` INT(11) NOT NULL DEFAULT '0',
    `line` INT(11) NOT NULL DEFAULT '0',
    `itemid` int(11) unsigned NOT NULL DEFAULT 0,
    `uom` varchar(15) NOT NULL DEFAULT '',
    `qty` decimal(19,10) NOT NULL DEFAULT '0.0000000000',
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(100) NOT NULL DEFAULT '',
    KEY `Index_1` (`pline`, `trno`, `line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("hvritems", $qry);

        $qry = "CREATE TABLE `vrpassenger` (
    `pline` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `trno` INT(11) NOT NULL DEFAULT '0',
    `line` INT(11) NOT NULL DEFAULT '0',
    `passengerid` int(11) unsigned NOT NULL DEFAULT 0,
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(100) NOT NULL DEFAULT '',
    KEY `Index_1` (`pline`, `trno`, `line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("vrpassenger", $qry);

        $qry = "CREATE TABLE `hvrpassenger` (
    `pline` int(11) unsigned NOT NULL,
    `trno` INT(11) NOT NULL DEFAULT '0',
    `line` INT(11) NOT NULL DEFAULT '0',
    `passengerid` int(11) unsigned NOT NULL DEFAULT 0,
    `editdate` datetime DEFAULT NULL,
    `editby` varchar(100) NOT NULL DEFAULT '',
    KEY `Index_1` (`pline`, `trno`, `line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("hvrpassenger", $qry);

        $this->coreFunctions->sbcaddcolumn("vritems", "itemname", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumn("hvritems", "itemname", "VARCHAR(200) NOT NULL DEFAULT ''", 0);


        $qry = "CREATE TABLE `purpose_masterfile` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `purpose` varchar(50) NOT NULL DEFAULT '',
      `editby` varchar(100) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    )
    ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("purpose_masterfile", $qry);

        // $this->coreFunctions->sbcaddcolumn("vrstock", "address", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
        // $this->coreFunctions->sbcaddcolumn("hvrstock", "address", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcdropcolumn('vrstock', 'address');
        $this->coreFunctions->sbcdropcolumn('hvrstock', 'address');

        $this->coreFunctions->sbcaddcolumn("vrstock", "shipid", "INT(11) NOT NULL DEFAULT '0' AFTER clientid", 1);
        $this->coreFunctions->sbcaddcolumn("hvrstock", "shipid", "INT(11) NOT NULL DEFAULT '0' AFTER clientid", 1);
        $this->coreFunctions->sbcaddcolumn("vrstock", "shipcontactid", "INT(11) NOT NULL DEFAULT '0' AFTER shipid", 1);
        $this->coreFunctions->sbcaddcolumn("hvrstock", "shipcontactid", "INT(11) NOT NULL DEFAULT '0' AFTER shipid", 1);

        $this->coreFunctions->sbcaddcolumn("vrstock", "schedin", "DATETIME DEFAULT NULL AFTER clientid", 1);
        $this->coreFunctions->sbcaddcolumn("hvrstock", "schedin", "DATETIME DEFAULT NULL AFTER clientid", 1);
        $this->coreFunctions->sbcaddcolumn("vrstock", "schedout", "DATETIME DEFAULT NULL AFTER schedin", 1);
        $this->coreFunctions->sbcaddcolumn("hvrstock", "schedout", "DATETIME DEFAULT NULL AFTER schedin", 1);
        $this->coreFunctions->sbcaddcolumn("vrstock", "purposeid", "int(11) unsigned NOT NULL DEFAULT '0' AFTER schedout", 1);
        $this->coreFunctions->sbcaddcolumn("hvrstock", "purposeid", "int(11) unsigned NOT NULL DEFAULT '0' AFTER schedout", 1);

        $this->coreFunctions->sbcaddcolumn("vrpassenger", "dropoff", "TINYINT(2) NOT NULL DEFAULT '0' AFTER passengerid", 1);
        $this->coreFunctions->sbcaddcolumn("hvrpassenger", "dropoff", "TINYINT(2) NOT NULL DEFAULT '0' AFTER passengerid", 1);

        $this->coreFunctions->sbcaddcolumn("vrstock", "editby", "varchar(100) NOT NULL DEFAULT '' AFTER createby", 1);
        $this->coreFunctions->sbcaddcolumn("hvrstock", "editby", "varchar(100) NOT NULL DEFAULT '' AFTER createby", 1);

        $this->coreFunctions->sbcaddcolumn("vrstock", "editdate", "datetime DEFAULT NULL AFTER editby", 1);
        $this->coreFunctions->sbcaddcolumn("hvrstock", "editdate", "datetime DEFAULT NULL AFTER editby", 1);


        $qry = "CREATE TABLE `daysched` (
            `clientid` int(11) NOT NULL DEFAULT '0',
            `ismon` TINYINT(2) NOT NULL DEFAULT '0',
            `ismon_am` TINYINT(2) NOT NULL DEFAULT '0',
            `ismon_pm` TINYINT(2) NOT NULL DEFAULT '0',
            `istue` TINYINT(2) NOT NULL DEFAULT '0',
            `istue_am` TINYINT(2) NOT NULL DEFAULT '0',
            `istue_pm` TINYINT(2) NOT NULL DEFAULT '0',
            `iswed` TINYINT(2) NOT NULL DEFAULT '0',
            `iswed_am` TINYINT(2) NOT NULL DEFAULT '0',
            `iswed_pm` TINYINT(2) NOT NULL DEFAULT '0',
            `isthu` TINYINT(2) NOT NULL DEFAULT '0',
            `isthu_am` TINYINT(2) NOT NULL DEFAULT '0',
            `isthu_pm` TINYINT(2) NOT NULL DEFAULT '0',
            `isfri` TINYINT(2) NOT NULL DEFAULT '0',
            `isfri_am` TINYINT(2) NOT NULL DEFAULT '0',
            `isfri_pm` TINYINT(2) NOT NULL DEFAULT '0',
            `issat` TINYINT(2) NOT NULL DEFAULT '0',
            `issat_am` TINYINT(2) NOT NULL DEFAULT '0',
            `issat_pm` TINYINT(2) NOT NULL DEFAULT '0',
            `issun` TINYINT(2) NOT NULL DEFAULT '0',
            `issun_am` TINYINT(2) NOT NULL DEFAULT '0',
            `issun_pm` TINYINT(2) NOT NULL DEFAULT '0',
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            PRIMARY KEY (`clientid`))
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("daysched", $qry);

        $this->coreFunctions->sbcaddcolumn("prstock", "rem", "varchar(500) NOT NULL DEFAULT ''");
        $this->coreFunctions->sbcaddcolumn("prstock", "suppid", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("hprstock", "rem", "varchar(500) NOT NULL DEFAULT ''");
        $this->coreFunctions->sbcaddcolumn("hprstock", "suppid", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("cdstock", "reqtrno", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("cdstock", "reqline", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("cdstock", "deptid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("cdstock", "suppid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("cdstock", "sano", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("hcdstock", "reqtrno", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hcdstock", "reqline", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hcdstock", "deptid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hcdstock", "suppid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hcdstock", "sano", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("postock", "reqtrno", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("postock", "reqline", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("hpostock", "reqtrno", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hpostock", "reqline", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("lastock", "reqtrno", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("lastock", "reqline", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("glstock", "reqtrno", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("glstock", "reqline", "INT(11) NOT NULL DEFAULT '0'");

        $qry = "CREATE TABLE `vehiclesched` (
            `dateid` datetime PRIMARY KEY,
            `amcount` INT(11) NOT NULL DEFAULT '0',
            `pmcount` INT(11) NOT NULL DEFAULT '0',
            `amused` INT(11) NOT NULL DEFAULT '0',
            `pmused` INT(11) NOT NULL DEFAULT '0',
            KEY `Index_dateid` (`dateid`)
            ) ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("vehiclesched", $qry);

        $this->coreFunctions->sbcaddcolumn("vrhead", "assigndriver", "DATETIME DEFAULT NULL");
        $this->coreFunctions->sbcaddcolumn("hvrhead", "assigndriver", "DATETIME DEFAULT NULL");

        $this->coreFunctions->sbcaddcolumn("vehiclesched", "dateid", "DATE DEFAULT NULL");

        $qry = "CREATE TABLE `headrem` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `trno` INT(11) NOT NULL DEFAULT '0',
            `rem` varchar(1000) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `remtype` INT(2) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`),
            KEY `IndexTrno` (`line`, `trno`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("headrem", $qry);

        $qry = "CREATE TABLE `hheadrem` (
            `line` int(11) NOT NULL DEFAULT '0',
            `trno` INT(11) NOT NULL DEFAULT '0',
            `rem` varchar(1000) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `remtype` INT(2) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`),
            KEY `IndexTrno` (`line`, `trno`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("hheadrem", $qry);

        $this->coreFunctions->sbcaddcolumn("prhead", "sano", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hprhead", "sano", "INT(11) NOT NULL DEFAULT '0'");


        $qry = "CREATE TABLE `duration` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `duration` varchar(45) NOT NULL DEFAULT '',
            `days` INT(11) NOT NULL DEFAULT '0',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`),
            KEY `IndexLine` (`line`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1";
        $this->coreFunctions->sbccreatetable("duration", $qry);

        $this->coreFunctions->sbcaddcolumn("stockinfotrans", "durationid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("stockinfotrans", "sono", "varchar(50) NOT NULL DEFAULT ''");

        $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "durationid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "sono", "varchar(50) NOT NULL DEFAULT ''");

        $qry = "CREATE TABLE `reqcategory` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `category` varchar(50) NOT NULL DEFAULT '',
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            `isoracle` TINYINT(2) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`), 
            KEY `IndexLine` (`line`))
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("reqcategory", $qry);

        $this->coreFunctions->sbcaddcolumn("lastock", "cdrefx", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("lastock", "cdlinex", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("glstock", "cdrefx", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("glstock", "cdlinex", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("headinfotrans", "isadv", "TINYINT(1) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hheadinfotrans", "isadv", "TINYINT(1) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("oqstock", "deptid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("oqstock", "suppid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("oqstock", "oraclecode", "varchar(50) NOT NULL DEFAULT ''");

        $this->coreFunctions->sbcaddcolumn("hoqstock", "deptid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hoqstock", "suppid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hoqstock", "oraclecode", "varchar(50) NOT NULL DEFAULT ''");

        $this->coreFunctions->sbcaddcolumn("billingaddr", "deptid", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("stockinfotrans", "deadline", "DATETIME DEFAULT NULL");
        $this->coreFunctions->sbcaddcolumn("stockinfotrans", "origdeadline", "DATETIME DEFAULT NULL");

        $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "deadline", "DATETIME DEFAULT NULL");
        $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "deadline2", "DATETIME DEFAULT NULL");
        $this->coreFunctions->sbcaddcolumn("hstockinfotrans", "origdeadline", "DATETIME DEFAULT NULL");

        $this->coreFunctions->sbcaddcolumn("prstock", "reqstat", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hprstock", "reqstat", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hprstock", "reqstat2", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hprstock", "iscanvass", "TINYINT(1) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hprstock", "reqstat3", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("hprstock", "deadline2", "DATETIME DEFAULT NULL");
        $this->coreFunctions->sbcaddcolumngrp(["prstock", "hprstock"], ["statrem"], "varchar(50) NOT NULL DEFAULT ''");
        $this->coreFunctions->sbcaddcolumn("hprstock", "statdate", "DATETIME DEFAULT NULL");

        $this->coreFunctions->sbcaddcolumn("prhead", "tax", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hprhead", "tax", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("cdhead", "procid", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hcdhead", "procid", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("cdstock", "ismanual", "TINYINT(1) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hcdstock", "ismanual", "TINYINT(1) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("cdhead", "iscanvassonly", "TINYINT(1) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hcdhead", "iscanvassonly", "TINYINT(1) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("oqhead", "serialized", "TINYINT(1) NOT NULL DEFAULT '0'", 1);
        $this->coreFunctions->sbcaddcolumn("hoqhead", "serialized", "TINYINT(1) NOT NULL DEFAULT '0'", 1);


        $qry = "CREATE TABLE `headprrem` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `trno` INT(11) NOT NULL DEFAULT '0',
            `rem` varchar(1000) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `remtype` INT(2) NOT NULL DEFAULT '0',
            `reqstat` INT(11) NOT NULL DEFAULT '0',
            `reqstat2` INT(11) NOT NULL DEFAULT '0',
            `deadline2` datetime DEFAULT NULL,
            `reqline` INT(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`),
            KEY `IndexTrno` (`line`, `trno`)
            )
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("headprrem", $qry);

        $this->coreFunctions->sbcaddcolumn("headprrem", "prref", "varchar(45) NOT NULL DEFAULT ''", 1);
        $this->coreFunctions->sbcaddcolumn("headprrem", "reqstat3", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumngrp(["headprrem"], ["cdtrno", "cdline"], "INT(11) NOT NULL DEFAULT '0'");

        // JIKS - 11-08-2022
        $this->coreFunctions->sbcaddcolumn("reqcategory", "reqtype", "varchar(50) NOT NULL DEFAULT '' AFTER category");
        $this->coreFunctions->sbcaddcolumn("reqcategory", "istype", "TINYINT(1) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("reqcategory", "iscat", "TINYINT(1) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("reqcategory", "iscldetails", "tinyint(1) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("oqstock", "svsno", "INT(11) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hoqstock", "svsno", "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("hcdstock", "approvedby2", "varchar(50) NOT NULL DEFAULT ''");
        $this->coreFunctions->sbcaddcolumn("hcdstock", "approveddate2", "DATETIME DEFAULT NULL");

        $qry = "CREATE TABLE `approversetup` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `doc` varchar(20) NOT NULL DEFAULT '',
            `editby` varchar(20) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            `checker` int(11) NOT NULL DEFAULT '0',
            `checkercount` int(11) NOT NULL DEFAULT '0',
            `ischecker` TINYINT(1) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`),
            KEY `IndexLine` (`line`),
            KEY `IndexDoc` (`doc`),
            KEY `IndexIsChecker` (`ischecker`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("approversetup", $qry);

        $this->coreFunctions->sbcaddcolumn("approversetup", "isapprover", "tinyint(1) NOT NULL DEFAULT '0'");

        $approversetup = [];
        if ($config['params']['companyid'] == 16) {
            array_push($approversetup, array('doc' => 'CV', 'ischecker' => 1, 'isapprover' => 0));
            array_push($approversetup, array('doc' => 'CV', 'ischecker' => 0, 'isapprover' => 1));
            array_push($approversetup, array('doc' => 'PO', 'ischecker' => 0, 'isapprover' => 1));
            array_push($approversetup, array('doc' => 'CD', 'ischecker' => 1, 'isapprover' => 0));
        }

        foreach ($approversetup as $key => $val) {
            $this->coreFunctions->LogConsole(json_encode($val));
            $valid = false;
            $statusexist = 0;

            if ($val['ischecker'] == 1) {
                $statusexist = $this->coreFunctions->datareader("select line as value from approversetup where ischecker=1 and doc=?", [$val['doc']]);
                $valid = true;
            }

            if ($val['isapprover'] == 1) {
                $statusexist = $this->coreFunctions->datareader("select line as value from approversetup where isapprover=1 and doc=?", [$val['doc']]);
                $valid = true;
            }

            if (!$statusexist) {
                if ($valid) {
                    $this->coreFunctions->sbcinsert("approversetup", [$val]);
                }
            }
        }

        // with users setup
        $qry = "CREATE TABLE `approverdetails` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `appline` int(11) NOT NULL DEFAULT '0',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `approver` varchar(100) NOT NULL DEFAULT '',
            `ordernum` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`line`),
            KEY `IndexLine` (`line`),
            KEY `IndexAppLine` (`appline`),
            KEY `IndexApprover` (`approver`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("approverdetails", $qry);

        $qry = "CREATE TABLE `approverinfo` (
            `trno` int(11) NOT NULL DEFAULT '0',
            `approver` varchar(100) NOT NULL DEFAULT '',
            `dateid` datetime DEFAULT NULL,
            KEY `IndexTrno` (`trno`),
            KEY `IndexApprover` (`approver`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("approverinfo", $qry);

        $qry = "CREATE TABLE `approverrcat` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `appid` int(11) unsigned DEFAULT '0',
            `catid` int(11) NOT NULL DEFAULT '0',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`),
            KEY `IndexLAppID` (`appid`),
            KEY `IndexCat` (`catid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("approverrcat", $qry);

        $qry = "CREATE TABLE `approverdept` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `appid` int(11) unsigned DEFAULT '0',
            `deptid` int(11) NOT NULL DEFAULT '0',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`),
            KEY `IndexLAppID` (`appid`),
            KEY `IndexDept` (`deptid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("approverdept", $qry);

        $this->coreFunctions->sbcaddcolumn("approverdetails", "iscat", "TINYINT(1) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("approverdetails", "isdept", "TINYINT(1) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumn("prstock", "ismanual", "TINYINT(1) NOT NULL DEFAULT '0'");
        $this->coreFunctions->sbcaddcolumn("hprstock", "ismanual", "TINYINT(1) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumngrp(["oqstock", "hoqstock"], ["ispartial"], "TINYINT(1) NOT NULL DEFAULT '0'");

        $qry = "CREATE TABLE `uomlist` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `uom` varchar(45) NOT NULL DEFAULT '',
            `isuom` int(11) unsigned DEFAULT '0',
            `isconvert` int(11) unsigned DEFAULT '0',
            `factor` decimal(18,2) not null default '0.00',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`),
            KEY `IndexUOM` (`uom`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("uomlist", $qry);

        $this->coreFunctions->sbcaddcolumngrp(["stockinfotrans", "hstockinfotrans"], ["uom2", "uom3"], "VARCHAR(45) NOT NULL DEFAULT ''");

        $this->coreFunctions->sbcaddcolumngrp(["cvitems", "hcvitems"], ["reqtrno", "reqline"], "INT(11) NOT NULL DEFAULT '0'");

        $this->coreFunctions->sbcaddcolumngrp(["cdstock", "hcdstock"], ["rqcd"], "DECIMAL(18,6) NOT NULL DEFAULT '0.0000000'");

        $this->coreFunctions->sbcaddcolumngrp(["cvitems", "hcvitems"], ["amt", "scamt"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'");

        $this->coreFunctions->sbcaddcolumngrp(["hstockinfotrans"], ["payreleased"], "DATETIME DEFAULT NULL");
        $this->coreFunctions->sbcaddcolumngrp(["hstockinfotrans"], ["isforpay"], "TINYINT(1) NOT NULL DEFAULT '0'");

        $qry = "CREATE TABLE cntclient like headrem";
        $this->coreFunctions->sbccreatetable("cntclient", $qry);

        $qry = "CREATE TABLE hcntclient like hheadrem";
        $this->coreFunctions->sbccreatetable("hcntclient", $qry);

        $this->coreFunctions->execqrynolog("ALTER TABLE oqstock CHANGE oraclecode oraclecode VARCHAR(100) NOT NULL DEFAULT ''");
        $this->coreFunctions->execqrynolog("ALTER TABLE hoqstock CHANGE oraclecode oraclecode VARCHAR(100) NOT NULL DEFAULT ''");

        $this->coreFunctions->execqrynolog("ALTER TABLE `cntclient` DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE(`line`, `trno`)");
        $this->coreFunctions->execqrynolog("ALTER TABLE `hcntclient` DROP PRIMARY KEY, ADD PRIMARY KEY  USING BTREE(`line`, `trno`)");

        $this->coreFunctions->sbcaddcolumngrp(["cntclient", "hcntclient"], ['ishelper'], "TINYINT(1) NOT NULL DEFAULT 0", 1);
    }
}
