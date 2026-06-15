<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class autoserv
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

    public function tableupdateserv($config)
    {
        ini_set('max_execution_time', 0);

        $qry = "CREATE TABLE `cmake` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `carname` varchar(150) NOT NULL DEFAULT '',
        `picture` VARCHAR(255) NOT NULL DEFAULT '',
        `createby` varchar(150) NOT NULL DEFAULT '',
        `createdate` datetime DEFAULT NULL,
        `editby` varchar(150) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
        )ENGINE=MyISAM DEFAULT CHARSET=latin1";
        $this->coreFunctions->sbccreatetable("cmake", $qry);

        $qry = "CREATE TABLE `cmodel` (
        `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
        `carid` INTEGER(11) UNSIGNED NOT NULL DEFAULT 0,
        `year` INTEGER UNSIGNED NOT NULL DEFAULT 0,
        `model` VARCHAR(150) NOT NULL DEFAULT '',
        `type` VARCHAR(150) NOT NULL DEFAULT '',
        `sub_model` VARCHAR(150) NOT NULL DEFAULT '',
        `other_info` VARCHAR(150) NOT NULL DEFAULT '',
        `createby` varchar(150) NOT NULL DEFAULT '',
        `createdate` datetime DEFAULT NULL,
        `editby` varchar(150) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        PRIMARY KEY (`line`),
        INDEX `Index_Carid`(`carid`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
        $this->coreFunctions->sbccreatetable("cmodel", $qry);


        $qry = " CREATE TABLE  `pthead` (
         `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
         `doc` VARCHAR(20) NOT NULL DEFAULT '',
         `docno` VARCHAR(20) NOT NULL DEFAULT '',
         `description` varchar(200) NOT NULL DEFAULT '',
         `rem` text,
         `dateid` datetime DEFAULT NULL,
         `createdate` datetime DEFAULT NULL,
         `createby` varchar(100) NOT NULL DEFAULT '',
         `editdate` datetime DEFAULT NULL,
         `editby` varchar(100) NOT NULL DEFAULT '',
         `viewdate` datetime DEFAULT NULL,
         `viewby` varchar(100) NOT NULL DEFAULT '',
          PRIMARY KEY (`trno`) USING BTREE
         ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("pthead", $qry);


        $qry = " CREATE TABLE  `ptjobs` (
        `line` int(10) unsigned NOT NULL DEFAULT '0',
        `jobid` int(10) unsigned NOT NULL DEFAULT '0',
        `trno` bigint(10) unsigned NOT NULL DEFAULT '0',
        `rem` varchar(200) NOT NULL DEFAULT '',
        `encodeddate` datetime DEFAULT NULL,
        `encodedby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `editby` varchar(100) NOT NULL DEFAULT '',
         PRIMARY KEY (`trno`,`line`) USING BTREE
         ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("ptjobs", $qry);

        $qry = " CREATE TABLE  `pttask` (
        `line` int(10) unsigned NOT NULL DEFAULT '0',
        `trno` bigint(10) unsigned NOT NULL DEFAULT '0',
        `jobline` int(10) unsigned NOT NULL DEFAULT '0',
        `laborline` int(10) unsigned NOT NULL DEFAULT '0',
        `mecline` int(10) unsigned NOT NULL DEFAULT '0',
        `cost` decimal(18,2) NOT NULL DEFAULT '0.00',
        `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
        `rem` varchar(200) NOT NULL DEFAULT '',
        `encodeddate` datetime DEFAULT NULL,
        `encodedby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,   
        `editby` varchar(100) NOT NULL DEFAULT '',
        PRIMARY KEY (`trno`,`line`) USING BTREE
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("pttask", $qry);

        $qry = "CREATE TABLE ptstock like sostock";
        $this->coreFunctions->sbccreatetable("ptstock", $qry);


        // $this->coreFunctions->sbcdroptable("ptjobs");
        // $this->coreFunctions->sbcdroptable("pttask");

        $qry = "CREATE TABLE `jobtask` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `code` varchar(20) NOT NULL DEFAULT '',
            `jobcode` varchar(20) NOT NULL DEFAULT '',
            `description` varchar(500) NOT NULL DEFAULT '',
            `labor1` decimal(18,2) NOT NULL DEFAULT '0.00',
            `labor2` decimal(18,2) NOT NULL DEFAULT '0.00',
            `labor3` decimal(18,2) NOT NULL DEFAULT '0.00',
            `labor4` decimal(18,2) NOT NULL DEFAULT '0.00',
            `labor5` decimal(18,2) NOT NULL DEFAULT '0.00',
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            `encodedby` varchar(100) NOT NULL DEFAULT '',
            `encodeddate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`), 
            KEY `IndexLine` (`line`))
            ENGINE = MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("jobtask", $qry);

        $this->coreFunctions->sbcaddcolumngrp(['client'], ["ismechanic"], "TINYINT(1) NOT NULL DEFAULT '0'", 0);
        $this->coreFunctions->sbcaddcolumngrp(["lahead", "glhead"], ["carid"], "int(11) NOT NULL DEFAULT '0'", 0);

        $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["recomm", "complaints"], "varchar(300) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(["cntnuminfo", "hcntnuminfo"], ["kmno"], "varchar(100) NOT NULL DEFAULT ''", 0);



        $this->coreFunctions->sbcdropcolumngrp(["jobthead"], ['isjobdt']);
        $this->coreFunctions->sbcdropcolumngrp(["jobtask"], ['istlabor']);

        $this->coreFunctions->sbcaddcolumngrp(["pttask"], ["laborline"], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
        $this->coreFunctions->sbcaddcolumngrp(["ptstock"], ["jobline", "taskline"], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
        $this->coreFunctions->sbcaddcolumngrp(["ptstock"], ["extension"], "varchar(200) NOT NULL DEFAULT ''", 0);



        $qry = " CREATE TABLE  `amjobs` (
        `line` int(10) unsigned NOT NULL DEFAULT '0',
        `jobid` int(10) unsigned NOT NULL DEFAULT '0',
        `trno` bigint(10) unsigned NOT NULL DEFAULT '0',
        `packageline` bigint(10) unsigned NOT NULL DEFAULT '0',
        `rem` varchar(200) NOT NULL DEFAULT '',
        `encodeddate` datetime DEFAULT NULL,
        `encodedby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `editby` varchar(100) NOT NULL DEFAULT '',
         PRIMARY KEY (`trno`,`line`) USING BTREE
         ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("amjobs", $qry);

        $qry = " CREATE TABLE  `hamjobs` (
        `line` int(10) unsigned NOT NULL DEFAULT '0',
        `jobid` int(10) unsigned NOT NULL DEFAULT '0',
        `trno` bigint(10) unsigned NOT NULL DEFAULT '0',
        `packageline` bigint(10) unsigned NOT NULL DEFAULT '0',
        `rem` varchar(200) NOT NULL DEFAULT '',
        `encodeddate` datetime DEFAULT NULL,
        `encodedby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        `editby` varchar(100) NOT NULL DEFAULT '',
         PRIMARY KEY (`trno`,`line`) USING BTREE
         ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("hamjobs", $qry);


        $qry = " CREATE TABLE  `amtask` (
        `line` int(10) unsigned NOT NULL DEFAULT '0',
        `trno` bigint(10) unsigned NOT NULL DEFAULT '0',
        `jobline` int(10) unsigned NOT NULL DEFAULT '0',
        `laborline` int(10) unsigned NOT NULL DEFAULT '0',
        `mecline` int(10) unsigned NOT NULL DEFAULT '0',
        `cost` decimal(18,2) NOT NULL DEFAULT '0.00',
        `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
        `rem` varchar(200) NOT NULL DEFAULT '',
        `encodeddate` datetime DEFAULT NULL,
        `encodedby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,   
        `editby` varchar(100) NOT NULL DEFAULT '',
        PRIMARY KEY (`trno`,`line`) USING BTREE
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("amtask", $qry);


        $qry = " CREATE TABLE  `hamtask` (
        `line` int(10) unsigned NOT NULL DEFAULT '0',
        `trno` bigint(10) unsigned NOT NULL DEFAULT '0',
        `jobline` int(10) unsigned NOT NULL DEFAULT '0',
        `laborline` int(10) unsigned NOT NULL DEFAULT '0',
        `mecline` int(10) unsigned NOT NULL DEFAULT '0',
        `cost` decimal(18,2) NOT NULL DEFAULT '0.00',
        `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
        `rem` varchar(200) NOT NULL DEFAULT '',
        `encodeddate` datetime DEFAULT NULL,
        `encodedby` varchar(100) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,   
        `editby` varchar(100) NOT NULL DEFAULT '',
        PRIMARY KEY (`trno`,`line`) USING BTREE
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
        $this->coreFunctions->sbccreatetable("hamtask", $qry);

        $qry = "CREATE TABLE awhead like sohead";
        $this->coreFunctions->sbccreatetable("awhead", $qry);

        $this->coreFunctions->execqrynolog("ALTER TABLE cmodel CHANGE year cryear INTEGER UNSIGNED NOT NULL DEFAULT 0");
        $this->coreFunctions->execqrynolog("ALTER TABLE cmodel CHANGE type crtype VARCHAR(150) NOT NULL DEFAULT ''");

        $qry = "CREATE TABLE `cvehicle` (
        `line` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
        `clientid` INTEGER(11) UNSIGNED NOT NULL DEFAULT 0,
        `cmodelline` INTEGER(11) UNSIGNED NOT NULL DEFAULT 0,
        `carid` INTEGER(11) UNSIGNED NOT NULL DEFAULT 0,
        `cmake` VARCHAR(150) not null default '',
        `licenseno` VARCHAR(50) NOT NULL DEFAULT '',
        `mileage` DECIMAL(10,2) DEFAULT 0,
        `carengine` VARCHAR(150) not null default '',
        `transmission` VARCHAR(150) not null default '',
        `motorno` VARCHAR(50) not null default '',
        `chassis` VARCHAR(50) not null default '',
        `mvno` VARCHAR(50) not null default '',
        `insurance` VARCHAR(150) not null default '',
        `labor` DECIMAL(10,2) DEFAULT 0,
        `createby` VARCHAR(150) NOT NULL DEFAULT '',
        `createdate` datetime DEFAULT NULL,
        `editby` VARCHAR(150) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        PRIMARY KEY (`line`),
        INDEX `Index_Carid`(`carid`),
        INDEX `Index_Cmodelline`(`cmodelline`)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
        $this->coreFunctions->sbccreatetable("cvehicle", $qry);

        $this->coreFunctions->sbcaddcolumn("cmake", "carcode", "varchar(20) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['client'], "varchar(50) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['clientname'], "varchar(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['address'], "varchar(255) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['ref'], "varchar(100) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['kmno'], "varchar(50) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['recommend'], "varchar(500) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['cryear'], "int(10) unsigned NOT NULL DEFAULT 0", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['licenseno'], "varchar(50) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['make'], "varchar(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['modelname'], "varchar(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['crtype'], "varchar(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['submodel'], "varchar(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['carengine'], "varchar(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['transmission'], "varchar(150) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['mvno'], "varchar(50) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['mileage'], "decimal(10,2) DEFAULT 0", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['manufacturer'], "varchar(100) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumngrp(['awhead'], ['chassisno'], "varchar(50) NOT NULL DEFAULT ''", 0);

        $this->coreFunctions->sbcaddcolumngrp(["lastock", "glstock"], ["taskline", "jobline"], "INT(11) NOT NULL DEFAULT '0'", 0);
        $this->coreFunctions->sbcaddcolumngrp(['ptjobs'], ['packagetrno'], "bigint(20) unsigned NOT NULL DEFAULT 0", 0);
    } //end function

} // end class