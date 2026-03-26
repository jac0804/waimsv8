<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;

class payroll
{

	private $coreFunctions;

	public function __construct()
	{
		$this->coreFunctions = new coreFunctions;
	} //end fn

	public function tableupdatepayroll()
	{
		//FMM 5.3.2021 - moved here because all alter tables with this field got an error incorrect default value '0000-00-00'.
		//Must alter this fields first
		//fpy 05.01.2021
		$this->coreFunctions->execqrynolog("update employee set bday=null where bday='0000-00-00'");
		$this->coreFunctions->execqrynolog("update employee set bday=null where bday='0000-00-00 00:00:00'");
		$this->coreFunctions->execqrynolog("update employee set hired=null where hired='0000-00-00'");
		$this->coreFunctions->execqrynolog("update employee set hired=null where hired='0000-00-00 00:00:00'");
		$this->coreFunctions->execqrynolog("update employee set regular=null where regular='0000-00-00'");
		$this->coreFunctions->execqrynolog("update employee set resigned=null where resigned='0000-00-00'");
		$this->coreFunctions->execqrynolog("update employee set prob=null where prob='0000-00-00'");
		$this->coreFunctions->execqrynolog("update employee set agency=null where agency='0000-00-00'");

		$this->coreFunctions->sbcaddcolumngrp(["employee"], [
			"empstatdate",
			"jobdate",
			"bday",
			"effectdate",
			"lockdate",
			"absdate",
			"agedate",
			"inteldate",
			"trainee",
			"prob",
			"probend",
			"agency",
			"createdate",
			"editdate"
		], "DATETIME DEFAULT NULL", 1);

		$this->coreFunctions->sbcaddcolumn("employee", "iswife", "int(1) unsigned DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["isapprover", "issupervisor", "isbudgetapprover"], "tinyint(1) NOT NULL DEFAULT '0'", 1);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["ismanualts", "isotapprover"], "TINYINT(2) NOT NULL DEFAULT '0'", 1);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["level"], "INT(10) NOT NULL DEFAULT '10'", 1);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["branchid", "contricompid"], "int(10) NOT NULL DEFAULT '0'", 1);
		$this->coreFunctions->sbcaddcolumn("employee", "aplid", "int(10) unsigned DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["obapp1", "obapp2"], "INT(11) UNSIGNED NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("employee", "shiftid", "int(11) unsigned DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], [
			"otsupervisorid",
			"approver1",
			"approver2",
			"empid",
			"branchid2",
			"roleid2",
			"jobid2",
			"supervisorid",
			"jobid",
			"divid",
			"sectid",
			"deptid",
			"contricomp",
			"workcatid",
			"biometricid",
			"projectid",
			"itemid",
			"roleid",
			"supervisorid2",
			"divid2",
			"deptid2",
			"sectid2"
		], "INT(11) NOT NULL DEFAULT '0'", 1);

		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["nochild"], "DECIMAL(8,0) DEFAULT '0'", 1);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["rate", "allow1", "allow2"], "DECIMAL (18,2) NOT NULL DEFAULT '0'", 0);

		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["bank"], "VARCHAR(100) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumn("employee", "zipcode2", "VARCHAR(6) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("employee", "country2", "VARCHAR(14) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["tin"], "VARCHAR(45) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["jobcode", "employee", "homeno3", "phic"], "VARCHAR(20) NOT NULL DEFAULT ''",  1);
		$this->coreFunctions->sbcaddcolumn("employee", "city2", "VARCHAR(25) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], [
			"supervisorcode",
			"aplcode",
			"empstatus",
			"emptype",
			"jgrade",
			"emppaygroup",
			"blood",
			"paygroup",
			"hrcode",
			"atmcard",
			"intelno"
		], "VARCHAR(45) NOT NULL DEFAULT ''",  0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], [
			"resignedtype",
			'status',
			'generation',
			'callsign',
			'religion',
			"idbarcode",
			"empnoref",
			"hmoaccno",
			"validity",
			"editby",
			"createby"
		], "VARCHAR(50) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["emploc2", "isbank", "location", "emprank", "encode", "hmoname", "salarytype"], "VARCHAR(100) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumn("employee", "emploc", "VARCHAR(200) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["permanentaddr"], "VARCHAR(250) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["token"], "VARCHAR(255) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["mapp"], "VARCHAR(300) NOT NULL DEFAULT ''",  1);

		$this->coreFunctions->sbcdropcolumngrp(["employee"], ["empcode"]); //code from client must be used


		$qry = "CREATE TABLE  `dependents` (
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `name` varchar(100) NOT NULL,
		  `relation` varchar(50) NOT NULL DEFAULT '',
		  `bday` datetime DEFAULT NULL,
		  `taxin` tinyint(1) NOT NULL DEFAULT '1',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("dependents", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["dependents"], ["editdate"], "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["dependents"], ["empid"], "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["dependents"], ["editby"], "varchar(100) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE  `education` (
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `school` varchar(100) NOT NULL DEFAULT '',
		  `address` varchar(50) NOT NULL DEFAULT '',
		  `course` varchar(80) NOT NULL DEFAULT '',
		  `sy` varchar(20) NOT NULL DEFAULT '',
		  `gpa` varchar(5) NOT NULL DEFAULT '',
		  `honor` varchar(50) NOT NULL DEFAULT '',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("education", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["education"], ["editdate"], "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["education"], ["empid"], "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["education"], ["editby"], "varchar(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["education"], ["course"], "VARCHAR(150) NOT NULL DEFAULT ''", 1);

		$qry = "CREATE TABLE `employment` (
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `company` varchar(100) NOT NULL DEFAULT '',
		  `jobtitle` varchar(50) NOT NULL DEFAULT '',
		  `period` varchar(50) NOT NULL DEFAULT '',
		  `address` varchar(250) NOT NULL DEFAULT '',
		  `salary` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `reason` varchar(250) NOT NULL DEFAULT '',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("employment", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["employment"], ["editdate"], "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employment"], ["empid"], "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employment"], ["editby"], "varchar(100) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE  `contracts` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `contractn` varchar(20) DEFAULT NULL,
		  `descr` varchar(60) NOT NULL DEFAULT '',
		  `datefrom` datetime DEFAULT NULL,
		  `dateto` datetime DEFAULT NULL,
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("contracts", $qry, 1);

		$this->coreFunctions->sbcaddcolumn("contracts", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("contracts", "empid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("contracts", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->sbcdropcolumn("contracts", "empcode");

		$qry = "CREATE TABLE `division` (
		  `divid` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `divcode` varchar(15) NOT NULL DEFAULT '',
		  `divname` varchar(50) NOT NULL DEFAULT '',
		  `address` varchar(80) NOT NULL DEFAULT '',
		  `tel` varchar(20) NOT NULL DEFAULT '',
		  `employer` varchar(30) NOT NULL DEFAULT '',
		  `tin` varchar(20) NOT NULL DEFAULT '',
		  `sss` varchar(20) NOT NULL DEFAULT '',
		  `hdmf` varchar(20) NOT NULL DEFAULT '',
		  `phic` varchar(20) NOT NULL DEFAULT '',
		  `bankacct` varchar(50) NOT NULL DEFAULT '',
		  `signatory` varchar(50) NOT NULL DEFAULT '',
		  `designation` varchar(30) NOT NULL DEFAULT '',
		  `attention` varchar(50) NOT NULL DEFAULT '',
		  `zipcode` varchar(20) NOT NULL DEFAULT '',
		  `cname` varchar(500) NOT NULL DEFAULT '',
		  `cbbc` varchar(50) NOT NULL DEFAULT '',
		  `bc` varchar(50) NOT NULL DEFAULT '',
		  `cc` varchar(50) NOT NULL DEFAULT '',
		  `pabc` varchar(50) NOT NULL DEFAULT '',
		  `email` varchar(100) NOT NULL DEFAULT '',
		  PRIMARY KEY (`divid`) USING BTREE,
		  KEY `Index_2` (`divid`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("division", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["division"], ["editdate"], "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["division"], ["picture"], "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["division"], ["editby", "divname"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE `department` (
		  `deptid` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `deptcode` varchar(15) NOT NULL DEFAULT '',
		  `deptname` varchar(30) NOT NULL DEFAULT '',
		  PRIMARY KEY (`deptcode`) USING BTREE,
		  KEY `Index_2` (`deptid`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("department", $qry);

		$qry = "CREATE TABLE `section` (
		  `sectid` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `sectcode` varchar(15) NOT NULL,
		  `sectname` varchar(30) NOT NULL DEFAULT '',
		  PRIMARY KEY (`section`) USING BTREE,
		  KEY `Index_2` (`sectid`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("section", $qry);

		$this->coreFunctions->sbcaddcolumn("section", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("section", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["section"], ["area"], "VARCHAR(300) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE `paygroup` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `paygroup` varchar(45) NOT NULL,
		  `code` varchar(15) NOT NULL,
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
		$this->coreFunctions->sbccreatetable("paygroup", $qry);

		$this->coreFunctions->sbcaddcolumn("paygroup", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("paygroup", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE `paccount` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `code` varchar(20) NOT NULL DEFAULT '',
		  `codename` varchar(50) NOT NULL DEFAULT '',
		  `alias` varchar(20) NOT NULL DEFAULT '',
		  `type` varchar(10) NOT NULL DEFAULT '',
		  `uom` varchar(20) NOT NULL DEFAULT '',
		  `seq` int(4) unsigned NOT NULL DEFAULT '0',
		  `qty` decimal(9,4) NOT NULL DEFAULT '0.0000',
		  `acno` varchar(30) NOT NULL DEFAULT '',
		  `acnoname` varchar(100) DEFAULT '',
		  `istax` tinyint(1) unsigned DEFAULT '0',
		  `pseq` decimal(3,0) DEFAULT '0',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("paccount", $qry);

		$this->coreFunctions->sbcaddcolumn("paccount", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["paccount"], ["penalty"], "DECIMAL(10,2) NOT NULL DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["paccount"], ["acnoid", "aaid"], "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("paccount", "alias2", "varchar(50) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumn("paccount", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		//RMG.8.24.2021
		$this->coreFunctions->execqrynolog("update paccount set alias='HDMFLOAN' where code='PT11'");
		$this->coreFunctions->execqrynolog("update paccount set alias='SSSLOAN',codename='SSS LOAN' where code='PT12'");

		$qry = "CREATE TABLE `annualtax` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `bracket` int(4) unsigned NOT NULL DEFAULT '0',
		  `range1` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `range2` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `amt` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `percentage` decimal(9,2) NOT NULL DEFAULT '0.00',
		  PRIMARY KEY (`bracket`) USING BTREE,
		  KEY `Index_2` (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("annualtax", $qry);

		$this->coreFunctions->sbcaddcolumn("annualtax", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("annualtax", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE  `phictab` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `bracket` int(4) unsigned NOT NULL,
		  `range1` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `range2` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `phicee` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `phicer` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `phictotal` decimal(9,2) NOT NULL DEFAULT '0.00',
		  PRIMARY KEY (`bracket`) USING BTREE,
		  KEY `Index_2` (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("phictab", $qry);

		$qry = "CREATE TABLE `taxtab` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `paymode` varchar(1) NOT NULL DEFAULT '',
		  `teu` varchar(1) NOT NULL DEFAULT '',
		  `depnum` int(4) unsigned NOT NULL DEFAULT '0',
		  `tax01` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax02` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax03` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax04` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax05` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax06` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax07` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax08` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax09` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax10` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax11` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax12` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax13` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax14` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `tax15` decimal(9,2) NOT NULL DEFAULT '0.00',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("taxtab", $qry);

		$qry = "CREATE TABLE `holiday` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `dateid` datetime DEFAULT NULL,
		  `description` varchar(100) NOT NULL DEFAULT '',
		  `daytype` varchar(15) NOT NULL DEFAULT '',
		  `divcode` varchar(15) NOT NULL DEFAULT '',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("holiday", $qry);

		$qry = "CREATE TABLE `leavesetup` (
		  `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `docno` varchar(15) NOT NULL DEFAULT '',
		  `dateid` datetime DEFAULT NULL,
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `remarks` varchar(150) NOT NULL DEFAULT '',
		  `acno` varchar(15) NOT NULL DEFAULT '',
		  `days` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `bal` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `prdstart` datetime DEFAULT NULL,
		  `prdend` datetime DEFAULT NULL,
		  PRIMARY KEY (`docno`) USING BTREE,
		  KEY `Index_2` (`trno`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("leavesetup", $qry);

		$qry = "CREATE TABLE `leavetrans` (
		  `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `refno` int(4) unsigned NOT NULL DEFAULT '0',
		  `dateid` datetime DEFAULT NULL,
		  `daytype` varchar(15) NOT NULL DEFAULT '',
		  `status` char(1) NOT NULL DEFAULT '',
		  `adays` decimal(5,2) NOT NULL DEFAULT '0.00',
		  `remarks` varchar(150) NOT NULL,
		  `batch` varchar(20) NOT NULL DEFAULT '',
		  `effectivity` datetime DEFAULT NULL,
		  PRIMARY KEY (`trno`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("leavetrans", $qry);

		$qry = "CREATE TABLE `loanapplication` (
			`trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
			`docno` varchar(15) NOT NULL,
			`dateid` datetime DEFAULT NULL,
			`empid` int(11) NOT NULL DEFAULT '0',
			`remarks` varchar(150) NOT NULL DEFAULT '',
			`acno` varchar(15) NOT NULL DEFAULT '',
			`amt` decimal(9,2) NOT NULL DEFAULT '0.00',
			`paymode` char(1) NOT NULL DEFAULT '',
			`w1` tinyint(1) NOT NULL DEFAULT '0',
			`w2` tinyint(1) NOT NULL DEFAULT '0',
			`w3` tinyint(1) NOT NULL DEFAULT '0',
			`w4` tinyint(1) NOT NULL DEFAULT '0',
			`w5` tinyint(1) NOT NULL DEFAULT '0',
			`halt` tinyint(1) unsigned NOT NULL DEFAULT '0',
			`priority` int(4) unsigned NOT NULL DEFAULT '0',
			`earnded` tinyint(1) NOT NULL DEFAULT '0',
			`amortization` decimal(9,2) NOT NULL DEFAULT '0.00',
			`effdate` datetime DEFAULT NULL,
			`balance` decimal(9,2) NOT NULL DEFAULT '0.00',
			`payment` decimal(9,2) NOT NULL DEFAULT '0.00',
			`w13` tinyint(1) unsigned DEFAULT '0',
			`acnoid` int(11) NOT NULL DEFAULT '0',
			`approvedby_disapprovedby` varchar(20) NOT NULL DEFAULT '',
			`date_approved_disapproved` datetime DEFAULT NULL,
			`disapproved_remarks` varchar(1000) NOT NULL DEFAULT '',
			`status` varchar(20) NOT NULL DEFAULT '',
			PRIMARY KEY (`trno`)
		) ENGINE=MyISAM AUTO_INCREMENT=22 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("loanapplication", $qry);
		$this->coreFunctions->sbcaddcolumn("loanapplication", "status2", "varchar(20) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("loanapplication", "approvedby_disapprovedby2", "varchar(20) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("loanapplication", "date_approved_disapproved2", "datetime DEFAULT NULL ");
		$this->coreFunctions->sbcaddcolumn("loanapplication", "isok", "TINYINT(2) NOT NULL DEFAULT '0'");

		$qry = "CREATE TABLE `ratesetup` (
		  `trno` int(9) unsigned NOT NULL AUTO_INCREMENT,
		  `dateid` datetime DEFAULT NULL,
		  `dateeffect` datetime DEFAULT NULL,
		  `dateend` datetime DEFAULT NULL,
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `remarks` varchar(150) NOT NULL,
		  `basicrate` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `type` char(1) NOT NULL DEFAULT 'A',
		  PRIMARY KEY (`trno`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("ratesetup", $qry, 1);

		$qry = "CREATE TABLE `tmshifts` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `shftcode` varchar(10) NOT NULL DEFAULT '',
		  `tschedin` datetime DEFAULT NULL,
		  `tschedout` datetime DEFAULT NULL,
		  `flexit` tinyint(1) NOT NULL DEFAULT '0',
		  `gtin` int(4) unsigned NOT NULL DEFAULT '0',
		  `gbrkin` int(4) unsigned NOT NULL DEFAULT '0',
		  `ndifffrom` datetime DEFAULT NULL,
		  `ndiffto` datetime DEFAULT NULL,
		  `elapse` tinyint(1) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("tmshifts", $qry);

		$qry = "CREATE TABLE `shiftdetail` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `dayn` int(4) unsigned NOT NULL DEFAULT '0',
		  `shftcode` varchar(10) NOT NULL,
		  `schedin` datetime DEFAULT NULL,
		  `schedout` datetime DEFAULT NULL,
		  `breakin` datetime DEFAULT NULL,
		  `breakout` datetime DEFAULT NULL,
		  `brk1stin` datetime DEFAULT NULL,
		  `brk1stout` datetime DEFAULT NULL,
		  `brk2ndin` datetime DEFAULT NULL,
		  `brk2ndout` datetime DEFAULT NULL,
		  `tothrs` decimal(5,2) NOT NULL DEFAULT '0.00',
		  `Shalfday` datetime DEFAULT NULL,
		  `Ehalfday` datetime DEFAULT NULL,
		  `undertime` datetime DEFAULT NULL,
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("shiftdetail", $qry);

		$qry = "CREATE TABLE  `batch` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `batch` varchar(15) NOT NULL DEFAULT '',
		  `dateid` datetime DEFAULT NULL,
		  `startdate` datetime DEFAULT NULL,
		  `enddate` datetime DEFAULT NULL,
		  `paymode` char(1) NOT NULL DEFAULT '',
		  `postdate` datetime DEFAULT NULL,
		  `sss` tinyint(1) NOT NULL DEFAULT '0',
		  `ph` tinyint(1) NOT NULL DEFAULT '0',
		  `hdmf` tinyint(1) NOT NULL DEFAULT '0',
		  `tax` tinyint(1) NOT NULL DEFAULT '0',
		  `adjustm` tinyint(1) NOT NULL DEFAULT '0',
		  `custcode` varchar(15) NOT NULL DEFAULT '',
		  `allow` tinyint(1) NOT NULL DEFAULT '0',
		  `pgroup` varchar(45) DEFAULT NULL,
		  `is13` tinyint(1) unsigned DEFAULT '0',
		  `13start` datetime DEFAULT NULL,
		  `13end` datetime DEFAULT NULL,
		  PRIMARY KEY (`line`,`batch`),
		  KEY `Index_2` (`batch`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("batch", $qry);

		$qry = "CREATE TABLE  `standardsetup` (
		  `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `docno` varchar(15) NOT NULL,
		  `dateid` datetime DEFAULT NULL,
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `remarks` varchar(150) NOT NULL DEFAULT '',
		  `acno` varchar(15) NOT NULL DEFAULT '',
		  `amt` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `paymode` char(1) NOT NULL DEFAULT '',
		  `w1` tinyint(1) NOT NULL DEFAULT '0',
		  `w2` tinyint(1) NOT NULL DEFAULT '0',
		  `w3` tinyint(1) NOT NULL DEFAULT '0',
		  `w4` tinyint(1) NOT NULL DEFAULT '0',
		  `w5` tinyint(1) NOT NULL DEFAULT '0',
		  `halt` tinyint(1) unsigned NOT NULL DEFAULT '0',
		  `priority` int(4) unsigned NOT NULL DEFAULT '0',
		  `earnded` tinyint(1) NOT NULL DEFAULT '0',
		  `amortization` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `effdate` datetime DEFAULT NULL,
		  `balance` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `payment` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `w13` tinyint(1) unsigned DEFAULT '0',
		  PRIMARY KEY (`trno`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("standardsetup", $qry);

		$qry = "CREATE TABLE `standardtrans` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `batch` varchar(15) NOT NULL DEFAULT '',
		  `dateid` datetime DEFAULT NULL,
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `acno` varchar(15) NOT NULL DEFAULT '',
		  `db` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `cr` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `docno` varchar(15) NOT NULL,
		  `ismanual` int(1) unsigned DEFAULT '0',
			PRIMARY KEY (`line`)
		) ENGINE=MyISAM  AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("standardtrans", $qry);

		$qry = "CREATE TABLE `leavetrans` (
		  `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `refno` int(4) unsigned NOT NULL DEFAULT '0',
		  `dateid` datetime DEFAULT NULL,
		  `daytype` varchar(15) NOT NULL DEFAULT '',
		  `status` char(1) NOT NULL DEFAULT '',
		  `adays` decimal(5,2) NOT NULL DEFAULT '0.00',
		  `remarks` varchar(150) NOT NULL,
		  `batch` varchar(20) NOT NULL DEFAULT '',
		  `effectivity` datetime DEFAULT NULL,
		  PRIMARY KEY (`trno`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("leavetrans", $qry);

		$qry = "CREATE TABLE `piecetrans` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `empname` varchar(100) NOT NULL,
		  `dcode` varchar(45) NOT NULL,
		  `dname` varchar(100) NOT NULL,
		  `drate` decimal(18,5) DEFAULT '0.00000',
		  `dqty` decimal(18,2) DEFAULT '0.00',
		  `daddon` decimal(18,2) DEFAULT '0.00',
		  `damt` decimal(18,2) DEFAULT '0.00',
		  `dateid` datetime DEFAULT NULL,
		  `rem` varchar(200) DEFAULT NULL,
		  `batch` varchar(30) DEFAULT NULL,
		  `diqty` decimal(18,2) DEFAULT '0.00',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1";
		$this->coreFunctions->sbccreatetable("piecetrans", $qry);

		$qry = "CREATE TABLE `timesched` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `dateid` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `empname` varchar(60) NOT NULL DEFAULT '',
		  `division` varchar(50) NOT NULL DEFAULT '',
		  `dept` varchar(50) NOT NULL DEFAULT '',
		  `orgsec` varchar(50) NOT NULL DEFAULT '',
		  `day1` varchar(15) NOT NULL DEFAULT '',
		  `day2` varchar(15) NOT NULL DEFAULT '',
		  `day3` varchar(15) NOT NULL DEFAULT '',
		  `day4` varchar(15) NOT NULL DEFAULT '',
		  `day5` varchar(15) NOT NULL DEFAULT '',
		  `day6` varchar(15) NOT NULL DEFAULT '',
		  `day7` varchar(15) NOT NULL DEFAULT '',
		  `day8` varchar(15) NOT NULL DEFAULT '',
		  `day9` varchar(15) NOT NULL DEFAULT '',
		  `day10` varchar(15) NOT NULL DEFAULT '',
		  `day11` varchar(15) NOT NULL DEFAULT '',
		  `day12` varchar(15) NOT NULL DEFAULT '',
		  `day13` varchar(15) NOT NULL DEFAULT '',
		  `day14` varchar(15) NOT NULL DEFAULT '',
		  `day15` varchar(15) NOT NULL DEFAULT '',
		  `day16` varchar(15) NOT NULL DEFAULT '',
		  `day17` varchar(15) NOT NULL DEFAULT '',
		  `day18` varchar(15) NOT NULL DEFAULT '',
		  `day19` varchar(15) NOT NULL DEFAULT '',
		  `day20` varchar(15) NOT NULL DEFAULT '',
		  `day21` varchar(15) NOT NULL DEFAULT '',
		  `day22` varchar(15) NOT NULL DEFAULT '',
		  `day23` varchar(15) NOT NULL DEFAULT '',
		  `day24` varchar(15) NOT NULL DEFAULT '',
		  `day25` varchar(15) NOT NULL DEFAULT '',
		  `day26` varchar(15) NOT NULL DEFAULT '',
		  `day27` varchar(15) NOT NULL DEFAULT '',
		  `day28` varchar(15) NOT NULL DEFAULT '',
		  `day29` varchar(15) NOT NULL DEFAULT '',
		  `day30` varchar(15) NOT NULL DEFAULT '',
		  `day31` varchar(15) NOT NULL DEFAULT '',
		  PRIMARY KEY (`line`,`dateid`,`empid`),
		  KEY `Index_2` (`empid`,`division`,`orgsec`,`dept`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("timesched", $qry);

		$qry = "CREATE TABLE `timerec` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `machno` int(4) unsigned NOT NULL DEFAULT '0',
		  `userid` int(4) unsigned NOT NULL DEFAULT '0',
		  `checktype` char(1) NOT NULL DEFAULT '',
		  `timeinout` datetime DEFAULT NULL,
		  `sensorid` int(4) unsigned NOT NULL DEFAULT '0',
		  `status` varchar(30) NOT NULL DEFAULT '',
		  `mode` varchar(20) NOT NULL DEFAULT '',
		  `machname` varchar(30) NOT NULL DEFAULT '',
		  `curdate` datetime DEFAULT NULL,
		  `isprevout` tinyint(1) unsigned DEFAULT '0',
		  `prevoutdate` datetime DEFAULT NULL,
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("timerec", $qry);

		$qry = "CREATE TABLE `timecard` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `dateid` date DEFAULT NULL,
		  `shiftcode` varchar(15) NOT NULL DEFAULT '',
		  `schedin` datetime DEFAULT NULL,
		  `schedout` datetime DEFAULT NULL,
		  `schedbrkin` datetime DEFAULT NULL,
		  `schedbrkout` datetime DEFAULT NULL,
		  `actualin` datetime DEFAULT NULL,
		  `actualout` datetime DEFAULT NULL,
		  `actualbrkin` datetime DEFAULT NULL,
		  `actualbrkout` datetime DEFAULT NULL,
		  `daytype` varchar(7) DEFAULT NULL,
		  `reghrs` decimal(5,2) DEFAULT '0.00',
		  `absdays` decimal(5,2) DEFAULT '0.00',
		  `latehrs` decimal(5,2) DEFAULT '0.00',
		  `underhrs` decimal(5,2) DEFAULT '0.00',
		  `othrs` decimal(5,2) DEFAULT '0.00',
		  `ndiffhrs` decimal(5,2) DEFAULT '0.00',
		  `brk1stin` datetime DEFAULT NULL,
		  `brk1stout` datetime DEFAULT NULL,
		  `brk2ndin` datetime DEFAULT NULL,
		  `brk2ndout` datetime DEFAULT NULL,
		  `abrk1stin` datetime DEFAULT NULL,
		  `abrk1stout` datetime DEFAULT NULL,
		  `abrk2ndin` datetime DEFAULT NULL,
		  `abrk2ndout` datetime DEFAULT NULL,
		  `divcode` varchar(15) NOT NULL DEFAULT '',
		  `ndiffot` decimal(5,2) NOT NULL DEFAULT '0.00',
		  `otapproved` tinyint(1) unsigned DEFAULT '0',
		  `Undertime` datetime DEFAULT NULL,
		  `Ndiffapproved` tinyint(1) unsigned DEFAULT '0',
		  `isprevwork` tinyint(1) unsigned DEFAULT '0',
		  `RDapprvd` tinyint(1) unsigned DEFAULT '0',
		  `RDOTapprvd` tinyint(1) unsigned DEFAULT '0',
		  `LEGapprvd` tinyint(1) unsigned DEFAULT '0',
		  `LEGOTapprvd` tinyint(1) unsigned DEFAULT '0',
		  `SPapprvd` tinyint(1) unsigned DEFAULT '0',
		  `SPOTapprvd` tinyint(1) unsigned DEFAULT '0',
		  `ndiffs` decimal(5,2) DEFAULT '0.00',
		  `ndiffsapprvd` tinyint(1) unsigned DEFAULT '0',
		  PRIMARY KEY (`line`),
		  KEY `Index_2` (`empid`,`shiftcode`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("timecard", $qry);

		$this->coreFunctions->sbcaddcolumn("timecard", "shiftid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timecard", "dateid", "date DEFAULT NULL", 1);

		$this->coreFunctions->sbcaddcolumn("timecard", "entryot", "decimal(5,2) DEFAULT '0.00'", 0);
		// $this->coreFunctions->sbcaddcolumn("timecard", "otstatus", "tinyint(1) unsigned DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["timecard"], ["entryndiffot", "sphrs"], "decimal(10,2) DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumn("timecard", "entryremarks", "VARCHAR(200) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->sbcaddcolumngrp(["timecard"], ['otstatus', "otstatus2"],  "tinyint(1) unsigned DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timecard", "approvedby_disapprovedby2", "varchar(20) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("timecard", "date_approved_disapproved2", "datetime DEFAULT NULL ");

		// $this->coreFunctions->sbcaddcolumn("timecard", "sundayot", "decimal(5,2) DEFAULT '0.00'", 0);
		// $this->coreFunctions->sbcaddcolumn("timecard", "specialot", "decimal(5,2) DEFAULT '0.00'", 0);
		// $this->coreFunctions->sbcaddcolumn("timecard", "legalot", "decimal(5,2) DEFAULT '0.00'", 0);

		$qry = "CREATE TABLE `timesheet` (
		  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
		  `batchid` INT(11) NOT NULL DEFAULT '0',
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `dateid` datetime DEFAULT NULL,
		  `acnoid` INT(11) NOT NULL DEFAULT '0',
		  `qty` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `uom` varchar(15) NOT NULL DEFAULT '',
		  `eorder` int(4) unsigned NOT NULL DEFAULT '0',
		  PRIMARY KEY (`line`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("timesheet", $qry);

		$qry = "CREATE TABLE `timesheethistory` (
		  `line` int(4) unsigned NOT NULL DEFAULT '0',
		  `batch` varchar(15) NOT NULL DEFAULT '',
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `dateid` datetime DEFAULT NULL,
		  `acno` varchar(15) NOT NULL DEFAULT '',
		  `acnoname` varchar(50) NOT NULL DEFAULT '',
		  `qty` decimal(9,0) NOT NULL DEFAULT '0',
		  `uom` varchar(15) NOT NULL DEFAULT '',
		  `eorder` int(4) unsigned NOT NULL DEFAULT '0'
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("timesheethistory", $qry);

		$qry = "CREATE TABLE `paytranhistory` (
		  `line` int(4) unsigned NOT NULL DEFAULT '0',
		  `batch` varchar(15) NOT NULL DEFAULT '',
		  `empid` INT(11) NOT NULL DEFAULT '0',
		  `dateid` datetime DEFAULT NULL,
		  `acno` varchar(15) NOT NULL DEFAULT '0',
		  `qty` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `unitamt` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `db` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `cr` decimal(9,2) NOT NULL DEFAULT '0.00',
		  `torder` int(4) unsigned NOT NULL DEFAULT '0',
		  `docno` varchar(15) DEFAULT '',
		  `type` char(1) DEFAULT '',
		  `uom` varchar(45) DEFAULT NULL,
		  `ltrno` int(10) unsigned DEFAULT '0',
		  `doc` varchar(45) DEFAULT NULL,
		  PRIMARY KEY (`line`),
		  KEY `Index_2` (`empid`,`batch`,`acno`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("paytranhistory", $qry);

		$qry = "CREATE TABLE  `standardsetupadv` (
	  `trno` int(4) unsigned NOT NULL AUTO_INCREMENT,
	  `docno` varchar(15) NOT NULL,
	  `dateid` datetime DEFAULT NULL,
	  `remarks` varchar(150) NOT NULL DEFAULT '',
	  `acno` varchar(15) NOT NULL DEFAULT '',
	  `amt` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `paymode` char(1) NOT NULL DEFAULT '',
	  `w1` tinyint(1) NOT NULL DEFAULT '0',
	  `w2` tinyint(1) NOT NULL DEFAULT '0',
	  `w3` tinyint(1) NOT NULL DEFAULT '0',
	  `w4` tinyint(1) NOT NULL DEFAULT '0',
	  `w5` tinyint(1) NOT NULL DEFAULT '0',
	  `halt` tinyint(1) NOT NULL DEFAULT '0',
	  `priority` int(4) unsigned NOT NULL DEFAULT '0',
	  `earnded` tinyint(1) unsigned NOT NULL DEFAULT '0',
	  `amortization` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `effdate` datetime DEFAULT NULL,
	  `balance` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `payment` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `w13` tinyint(1) unsigned DEFAULT '0',
	  `ispost` tinyint(1) unsigned DEFAULT '0',
	  `empid` int(1) NOT NULL DEFAULT '0',
	  PRIMARY KEY (`trno`)
		) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
		$this->coreFunctions->sbccreatetable("standardsetupadv", $qry);

		$this->coreFunctions->sbcaddcolumn("standardsetupadv", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetupadv", "approvedby_disapprovedby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetupadv", "disapproved_remarks", "VARCHAR(1000) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetupadv", "status", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetupadv", "date_approved_disapproved", "DATETIME DEFAULT NULL", 0);

		$this->coreFunctions->sbcaddcolumn("batch", "13start", "DATETIME DEFAULT NULL", 1);
		$this->coreFunctions->sbcaddcolumn("batch", "13end", "DATETIME DEFAULT NULL", 1);

		$qry = "CREATE TABLE  `allowsetup` (
	  `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
	  `Dateid` datetime NOT NULL,
	  `dateeffect` datetime NOT NULL,
	  `dateend` datetime NOT NULL,
	  `empid` int(10) NOT NULL DEFAULT 0,
	  `remarks` varchar(450) DEFAULT '',
	  `basicrate` decimal(18,2) DEFAULT '0.00',
	  `type` varchar(1) DEFAULT '',
	  `acno` varchar(45) DEFAULT NULL,
	  `allowance` decimal(18,2) DEFAULT '0.00',
	  PRIMARY KEY (`trno`) USING BTREE
	) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
		$this->coreFunctions->sbccreatetable("allowsetup", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["allowsetup", "allowsetuptemp"], ["refx", "acnoid", "rstrno", "hstrno"], "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["allowsetup", "allowsetuptemp"], ["allowance"], "decimal(18,2) DEFAULT '0.00'", 0);

		$qry = "CREATE TABLE  `paytrancurrent` (
	  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
	  `batch` varchar(15) NOT NULL DEFAULT '',
	  `empid` int(15) NOT NULL DEFAULT '0',    
	  `dateid` datetime DEFAULT NULL,
	  `acno` varchar(15) NOT NULL DEFAULT '',
	  `qty` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `unitamt` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `db` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `cr` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `torder` int(4) unsigned NOT NULL DEFAULT '0',
	  `docno` varchar(15) NOT NULL DEFAULT '',
	  `type` char(1) NOT NULL DEFAULT '',
	  `uom` varchar(15) NOT NULL DEFAULT '',
	  `ltrno` int(10) unsigned DEFAULT '0',
	  `doc` varchar(45) DEFAULT NULL,
	  PRIMARY KEY (`line`,`batch`,`empid`),
	  KEY `Index_2` (`empid`,`batch`,`acno`)
	) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
		$this->coreFunctions->sbccreatetable("paytrancurrent", $qry);

		$qry = "CREATE TABLE  `standardtransadv` (
	  `line` int(4) unsigned NOT NULL DEFAULT '0',
	  `batch` varchar(15) NOT NULL DEFAULT '',
	  `dateid` datetime DEFAULT NULL,
	  `empid` int(15) NOT NULL DEFAULT '0',
	  `acno` varchar(15) NOT NULL DEFAULT '',
	  `db` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `cr` decimal(9,2) NOT NULL DEFAULT '0.00',
	  `docno` varchar(15) NOT NULL,
	  `ismanual` int(1) unsigned DEFAULT '0'
	) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";

		$this->coreFunctions->sbccreatetable("standardtransadv", $qry);

		$this->coreFunctions->sbcaddcolumn("standardtransadv", "trno", "INT(11) NOT NULL DEFAULT '0' FIRST", 0);
		$this->coreFunctions->sbcaddcolumn("standardtransadv", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("standardtransadv", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);

		$primary = $this->coreFunctions->opentable("SHOW KEYS FROM standardtransadv WHERE Key_name = 'PRIMARY'");
		if (!$primary) {
			$this->coreFunctions->execqry("ALTER TABLE standardtransadv ADD PRIMARY KEY (line)");
			$this->coreFunctions->execqry("ALTER TABLE standardtransadv MODIFY line INT(11) UNSIGNED AUTO_INCREMENT");
		}


		$this->coreFunctions->sbcaddcolumngrp(["contacts", "ratesetup", "leavetrans"], ["empid"], "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("shiftdetail", "shiftsid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("standardtrans", "trno", "INT(11) NOT NULL DEFAULT '0' FIRST", 0);
		$this->coreFunctions->sbcaddcolumn("paytrancurrent", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("paytrancurrent", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("paytranhistory", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("paytranhistory", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetup", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timesheet", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timesheet", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timesheethistory", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timesheethistory", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);

		//jac 11222021
		$this->coreFunctions->sbcdropcolumn('leavesetup', 'acno');
		$this->coreFunctions->sbcaddcolumn("leavesetup", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);

		//fmm 5.4.2021
		$this->coreFunctions->sbcaddcolumn("leavetrans", "line", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "trno", "INT(11) NOT NULL DEFAULT '0'", 1);
		$primary = $this->coreFunctions->opentable("SHOW KEYS FROM leavetrans WHERE Key_name = 'PRIMARY'");
		if ($primary) {
			$this->coreFunctions->execqry("ALTER TABLE leavetrans DROP PRIMARY KEY");
		}

		//fmm 5.5.2021

		$this->coreFunctions->sbcdropcolumn("shiftdetail", "shftcode"); //no use


		//fmm 5.6.2021
		$this->coreFunctions->sbcdropcolumn("timecard", "sundayot");
		$this->coreFunctions->sbcdropcolumn("timecard", "specialot");
		$this->coreFunctions->sbcdropcolumn("timecard", "legalot");

		// fmm 5.7.2021
		$this->coreFunctions->sbcaddcolumn("leavetrans", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("standardtrans", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);


		// fmm 5.10.2021
		$this->coreFunctions->sbcaddcolumn("standardtrans", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcdropcolumn("standardtrans", "acno");

		// fmm 5.11.2021
		$this->coreFunctions->sbcdropcolumn("paytrancurrent", "acno");
		$this->coreFunctions->sbcdropcolumn("paytrancurrent", "batch");
		$this->coreFunctions->sbcdropcolumn("paytranhistory", "acno");
		$this->coreFunctions->sbcdropcolumn("paytranhistory", "batch");

		// fmm 5.14.2021
		$this->coreFunctions->sbcdropcolumn("piecetrans", "batch");
		$this->coreFunctions->sbcaddcolumn("piecetrans", "batchid", "INT(11) NOT NULL DEFAULT '0'", 0);

		$this->coreFunctions->sbcdropcolumn("timesheet", "batch");
		$this->coreFunctions->sbcdropcolumn("timesheet", "acno");
		$this->coreFunctions->sbcdropcolumn("timesheet", "acnoname");
		$this->coreFunctions->sbcdropcolumn("timesheethistory", "batch");
		$this->coreFunctions->sbcdropcolumn("timesheethistory", "acno");
		$this->coreFunctions->sbcdropcolumn("timesheethistory", "acnoname");

		$this->coreFunctions->sbcaddcolumn("batch", "postdate", "DATETIME", 0);
		$this->coreFunctions->sbcaddcolumn("batch", "postby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("batch", "batch", "VARCHAR(25) NOT NULL DEFAULT ''", 1);


		$primary = $this->coreFunctions->opentable("SHOW KEYS FROM paytrancurrent WHERE Key_name = 'PRIMARY'");
		if ($primary) {
			$this->coreFunctions->sbcaddcolumn("paytrancurrent", "empid", "INT(11) NOT NULL DEFAULT '0'");
			$this->coreFunctions->sbcaddcolumn("paytrancurrent", "line", "INT(11) NOT NULL DEFAULT '0'");
			$this->coreFunctions->execqry("ALTER TABLE paytrancurrent DROP PRIMARY KEY");
		}
		$this->coreFunctions->sbcdropcolumn("paytrancurrent", "line");


		$primary = $this->coreFunctions->opentable("SHOW KEYS FROM paytranhistory WHERE Key_name = 'PRIMARY'");
		if ($primary) {
			$this->coreFunctions->sbcaddcolumn("paytranhistory", "empid", "INT(11) NOT NULL DEFAULT '0'");
			$this->coreFunctions->sbcaddcolumn("paytranhistory", "line", "INT(11) NOT NULL DEFAULT '0'");
			$this->coreFunctions->execqry("ALTER TABLE paytranhistory DROP PRIMARY KEY");
		}
		$this->coreFunctions->sbcdropcolumn("paytranhistory", "line");

		//COMMENT - FMM - 6.14.2021 already exists in payroll account setup
		//rmg 5.31.2021
		//Payroll Accounts
		// $this->coreFunctions->execqrynolog("truncate table paccount");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT1','BASIC RATE','RATE','','PESO',1,0,0,1)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT10','CASH LOAN','LOAN','','PESO',32,-1,0,56)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT11','PAGIBIG LOAN','LOAN','','PESO',27,-1,0,55)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT12','CALAMITY LOAN','LOAN','','PESO',28,-1,0,64)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT13','SSS LOAN','LOAN','','PESO',26,-1,'SSS3',0,54)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT14','CANTEEN','CA','','PESO',25,-1,0,62)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT15','REGULAR OT','OTREG','MDS','HRS',6,1.25,0,5)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT16','RESTDAY-SUN','RESTDAY','MDS','HRS',8,1.3,0,8)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT17','SUNDAY OT','OTRES','MDS','HRS',8,1.69,0,21)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT18','LEGAL HOLIDAY','LEG','MDS','HRS',10,2,0,7)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT2','MONTHLY DUE','DUE','','PESO',2,0,0,70)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT29','ADJUSTMENT','ADJUSTMENT','','PESO',38,1,0,31)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT3','WORKING HRS','WORKING','MDS','HRS',3,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT30','OTHER EARNINGS','EARNINGS','MDS','PESO',1,1,0,29)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT31','ALLOWANCE','ALLOWANCE','','PESO',31,1,0,14)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT32','BACK PAY','BACKPAY','','PESO',39,1,0,32)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT33','OVERPAYMENT','OVERPAYMENT','','PESO',40,-1,0,69)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT34','13TH MONTH PAY','13PAY','MDS','PESO',41,1,0,33)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT35','OTHER DEDUCTION','DEDUCTION','MDS','PESO',1,-1,0,67)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT36','PO CARD','DEDUCTION','MDS','PESO',36,-1,0,60)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT37','HOSPITAL LOAN','DEDUCTION','MDS','PESO',1,-1,0,57)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT4','ALLOWANCE2','COLA1','','PESO',12,1,0,12)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT42','WITHHOLDING TAX PAYABLE','YWT','MDS','PESO',13,0,0,52)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT44','SSS-EMPLOYEE','YSE','','PESO',15,0,0,50)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT45','SSS-EMPLOYER','YSR','','PESO',16,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT46','EC-EMPLOYER','YER','','PESO',17,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT48','PHILHEALTH-EMPLOYEE','YME','','PESO',18,0,0,51)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT49','PHILHEALTH-EMPLOYER','YMR','','PESO',19,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT5','ABSENT','ABSENT','MDS','HRS',5,-1,1,2)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT51','PAG-IBIG EMPLOYEE','YPE','PESO',20,0,0,53)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT52','PAG-IBIG EMPLOYER','YPR','','PESO',21,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT53','SSS EMPLOYER SHARE','YIS','','PESO',22,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT54','PHILHEALTH EMPLOYER SHARE','YIM','','PESO',23,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT55','PAG-IBIG EMPLOYER SHARE','YIP','','PESO',24,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT56','PAYROLL PAYABLE','PPBLE','','PESO',44,0,0,0)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT57','BASIC SALARIES','BSA','','PESO',45,1,1,1)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT58','SERVICE INCENTIVE LEAVE','SIL','','PESO',33,1,0,23)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT6','LATE/TARDINESS','LATE','MDS','HRS',4,-1,1,3)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT64','SPECIAL HOL(30%)','SP','MDS','HRS',9,0.3,0,6)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT67','MEAL ALLOWANCE','ALLOWANCE3','','PESO',35,1,0,13)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT69','CASH ADVANCE','DEDUCTION','MDS','PESO',34,-1,0,61)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT7','UNDERTIME','UNDERTIME','MDS','HRS',4,-1,1,4)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT70','STOCKS VALE','DEDUCTION','PESO',37,-1,0,68)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT71','HMO/PENSION PLAN','DEDUCTION','','PESO',29,-1,0,65)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT76','NIGHT DIFF OT','NDIFF','MDS','HRS',7,0.1,1,11)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT8','SICK LEAVE','SL','','DAYS',42,1,1,22)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT9','VACATION LEAVE','VL','MDS','DAYS',11,1,1,24)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT79','COLA','COLA','','PESO',11,1,0,15)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT80','LEGAL OT','LEGALOT','MDS','HRS',10,1.69,0,17)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT81','SPECIAL OT','SPECIALOT','MDS','HRS',10,1.69,0,16)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT82','SPECIAL HOLIDAY UNWORK','SPUN','','DAYS',46,1,0,18)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT83','LEGAL HOLIDAY UNWORK','LEGUN','','DAYS',47,1,19)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT28','BONUS','BON','','PESO',48,1,0,30)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT85','EMERGENCY LEAVE','VIL','','PESO',33,1,1,26)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT86','BIRTHDAY LEAVE','BL','','DAYS',33,1,0,25)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT87','RESTDAY-SAT','RESTDAYSAT','MDS','HRS',8,1.25,0,9)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT88','SATURDAY OT','OTSAT','MDS','HRS',8,1.3,0,20)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT09','OTHER LOAN','DEDUCTION','','PESO',12,-1,0,59)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT89','OTHER DEDUCTION2','DEDUCTION','MDS','PESO',1,-1,0,66)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT90','OTHER INCOME TAXABLE','EARNINGS','','PESO',30,1,1,28)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT91','OTHER INCOME N-TAXABLE','EARNINGS1','','PESO',30,1,0,27)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT92','INSURANCE LOAN','DEDUCTION','','PESO',32,-1,'IL',0,58)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT93','PAG CALAMITY LOAN','DEDUCTION','','PESO',32,-1,1,63)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT100','YEARS OF SERVICE','YOS','','PESO',8,1,0,37)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT101','MONTHS OF SERVICE','MOS','','PESO',8,1,0,36)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT102','DAYS OF SERVICE','DOS','','PESO',8,1,0,35)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT103','LEAVE BALANCE','LB','','PESO',8,1,0,34)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT200','PIECE SALARY','PIECE','','PESO',1,1,1,1)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT104','NDIFF HRS','NDIFFS','MDS','HRS',7,0.1,1,10)");
		// $this->coreFunctions->execqrynolog("insert into paccount (code,codename,alias,type,uom,seq,qty,istax,pseq) values ('PT105','SPECIAL HOL(100%)','SP100','MDS','HRS',9,1,0,6)");

		//rmg 5.31.2021
		//Annual Tax
		$this->coreFunctions->execqrynolog("truncate table annualtax");
		$this->coreFunctions->execqrynolog("insert into annualtax (bracket,range1,range2,amt,percentage) values (1,0.00,250000.00,0.00,0.00)");
		$this->coreFunctions->execqrynolog("insert into annualtax (bracket,range1,range2,amt,percentage) values (2,250000.00,400000.00,0.00,0.20)");
		$this->coreFunctions->execqrynolog("insert into annualtax (bracket,range1,range2,amt,percentage) values (3,400000.00,800000.00,30000.00,0.25)");
		$this->coreFunctions->execqrynolog("insert into annualtax (bracket,range1,range2,amt,percentage) values (4,800000.00,2000000.00,130000.00,0.30)");
		$this->coreFunctions->execqrynolog("insert into annualtax (bracket,range1,range2,amt,percentage) values (5,2000000.00,8000000.00,490000.00,0.32)");
		$this->coreFunctions->execqrynolog("insert into annualtax (bracket,range1,range2,amt,percentage) values (6,8000000.00,9999999.99,2410000.00,0.35)");

		$this->coreFunctions->sbcaddcolumn("leavetrans", "approvedby_disapprovedby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "date_approved_disapproved", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["leavetrans"], ["remarks", "disapproved_remarks"], "text default null");

		$qry = "
			CREATE TABLE  `obapplication` (
			  `line` int(4) unsigned NOT NULL AUTO_INCREMENT,
			  `empid` int(11) NOT NULL DEFAULT '0',
			  `createdate` datetime DEFAULT NULL,
			  `dateid` datetime DEFAULT NULL,
			  `type` varchar(20) NOT NULL DEFAULT '',
			  `rem` varchar(50) NOT NULL DEFAULT '',
			  `status` varchar(20) NOT NULL DEFAULT '',
			  `approvedby` varchar(50) NOT NULL DEFAULT '',
			  `approvedate` datetime DEFAULT NULL,
			  `approverem` varchar(50) NOT NULL DEFAULT '',
			  PRIMARY KEY (`line`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("obapplication", $qry);

		$this->coreFunctions->sbcaddcolumn("obapplication", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "disapprovedate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "disapprovedby", "varchar(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "isok", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "isitinerary", "INT(11) NOT NULL DEFAULT '0'", 1);
		$this->coreFunctions->sbcaddcolumn("obapplication", "status2", "varchar(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "disapprovedate2", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "disapprovedby2", "varchar(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "approvedate2", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "approvedby2", "varchar(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetup", "approvedby_disapprovedby", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["standardsetup"], ["date_approved_disapproved", "enddate", "startdate"], "DATETIME DEFAULT NULL", 1);
		$this->coreFunctions->sbcaddcolumngrp(["standardsetup"], ["totalterms"], "INT(10) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetup", "disapproved_remarks", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetup", "status", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE `loginpic` (
			`line` int(4) unsigned NOT NULL AUTO_INCREMENT,
			`dateid` datetime DEFAULT NULL,
			`mode` varchar(45) NOT NULL DEFAULT '',
			`idbarcode` varchar(45) NOT NULL DEFAULT '',
			`picture` mediumtext NOT NULL,
			PRIMARY KEY (`line`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC";
		$this->coreFunctions->sbccreatetable("loginpic", $qry);
		$this->coreFunctions->execqry("ALTER TABLE loginpic MODIFY picture VARCHAR(255) NOT NULL DEFAULT ''");

		// FMM 01.08.2022
		$this->coreFunctions->sbcaddcolumngrp(["leavesetup"], ["isnopay", "isconvert"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("leavesetup", "leavebatch", "VARCHAR(20) NOT NULL DEFAULT ''", 0);

		// FMM 01.24.2022
		$this->coreFunctions->sbcaddcolumn("paytrancurrent", "db", "DECIMAL(19,6) NOT NULL DEFAULT '0.000000'");
		$this->coreFunctions->sbcaddcolumn("paytrancurrent", "cr", "DECIMAL(19,6) NOT NULL DEFAULT '0.000000'");
		$this->coreFunctions->sbcaddcolumn("paytranhistory", "db", "DECIMAL(19,6) NOT NULL DEFAULT '0.000000'");
		$this->coreFunctions->sbcaddcolumn("paytranhistory", "cr", "DECIMAL(19,6) NOT NULL DEFAULT '0.000000'");

		// FMM 01.24.2022
		$this->coreFunctions->sbcaddcolumn("timesheet", "editby", "VARCHAR(45) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("timesheet", "editdate", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("timesheethistory", "editby", "VARCHAR(45) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("timesheethistory", "editdate", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("phictab", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("phictab", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("hdmftab", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("hdmftab", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("ssstab", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("ssstab", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("taxtab", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("taxtab", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("holiday", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("holiday", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("leavesetup", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavesetup", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("tmshifts", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("tmshifts", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("shiftdetail", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("shiftdetail", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetup", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetup", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("standardtrans", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardtrans", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetupadv", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardsetupadv", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("standardtransadv", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardtransadv", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("acontacts", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("acontacts", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("emprole", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("emprole", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("loanapplication", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("loanapplication", "editdate", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("timerec", "iscomputed", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["leavetrans"], ["isok", "iswindows"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timecard", "isok", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("timecard", "line", "INT(11) AUTO_INCREMENT");

		$this->coreFunctions->sbcaddcolumn('timerec', 'location', "varchar(50) not null default ''");

		$qry = "CREATE TABLE `biometric` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `terminal` varchar(45) NOT NULL DEFAULT '',
            `loc` varchar(45) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`),
            KEY `IndexLine` (`line`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("biometric", $qry);

		$qry = "CREATE TABLE `empprojdetail` (
            `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`dateno` INT(11) NOT NULL DEFAULT '0',
			`empid` INT(11) NOT NULL DEFAULT '0',
			`dateid` DATETIME DEFAULT NULL,
			`tothrs` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
			`rem` varchar(100) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            PRIMARY KEY (`line`),
            KEY `IndexLine` (`line`),
			KEY `IndexEmpID` (`empid`),
			KEY `IndexDateID` (`dateid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("empprojdetail", $qry);

		$qry = "CREATE TABLE `hempprojdetail` (
            `line` int(11) unsigned NOT NULL DEFAULT '0',
			`dateno` INT(11) NOT NULL DEFAULT '0',
			`empid` INT(11) NOT NULL DEFAULT '0',
			`dateid` DATETIME DEFAULT NULL,
			`tothrs` DECIMAL(18,2) NOT NULL DEFAULT '0.00',
			`rem` varchar(100) NOT NULL DEFAULT '',
            `createby` varchar(100) NOT NULL DEFAULT '',
            `createdate` datetime DEFAULT NULL,
            `editby` varchar(100) NOT NULL DEFAULT '',
            `editdate` datetime DEFAULT NULL,
            KEY `IndexLine` (`line`),
			KEY `IndexEmpID` (`empid`),
			KEY `IndexDateID` (`dateid`)
            ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("hempprojdetail", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["empprojdetail"], ["compcode", "pjroxascode1", "subpjroxascode", "blotroxascode", "amenityroxascode", "subamenityroxascode", "departmentroxascode"], "VARCHAR(15) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("leavetrans", "status2", "varchar(10) NOT NULL DEFAULT 'E'", 1);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "date_approved_disapproved2", "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "approvedby_disapprovedby2", "varchar(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["empprojdetail", "hempprojdetail"], ["achrs", "rate", "othrs"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["empprojdetail", "hempprojdetail"], ["tothrs"], "DECIMAL(18,10) NOT NULL DEFAULT '0.00'", 1);
		$this->coreFunctions->sbcaddcolumngrp(["tmshifts"], ["breakinam", "breakoutam", "breakinpm", "breakoutpm"], "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["tmshifts"], ["isonelog", "isdefault", "isfixhrs"], "TINYINT(2) NOT NULL DEFAULT '0'", 1);
		$this->coreFunctions->sbcaddcolumn("tmshifts", "sig", "INT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["timecard"], ["latehrs2", "lateoffset", "earlyothrs"], "DECIMAL(15,2) NOT NULL DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["timesheet", "timesheethistory", "paytrancurrent", "paytranhistory"], ["qty2"], "DECIMAL(15,2) NOT NULL DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumn("timesheethistory", "qty", "DECIMAL(15,2) NOT NULL DEFAULT '0.00'");
		$this->coreFunctions->sbcaddcolumn("timerec", "machno", "varchar(30) NOT NULL DEFAULT ''");

		$qry = "CREATE TABLE `tihead` (
			`trno` int(10) unsigned not null default '0',
			`docno` VARCHAR(20) NOT NULL DEFAULT '',
			`start` datetime DEFAULT NULL,
			`enddate` datetime DEFAULT NULL,
			`createby` varchar(100) not null default '',
			`createdate` datetime default null,
			`editby` varchar(100) not null default '',
			`editdate` datetime default null,
			`viewby` varchar(100) not null default '',
			`viewdate` datetime default null,
			PRIMARY KEY (`trno`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("tihead", $qry);

		$qry = "CREATE TABLE `htihead` (
			`trno` int(10) unsigned not null default '0',
			`docno` VARCHAR(20) NOT NULL DEFAULT '',
			`start` datetime DEFAULT NULL,
			`enddate` datetime DEFAULT NULL,
			`createby` varchar(100) not null default '',
			`createdate` datetime default null,
			`editby` varchar(100) not null default '',
			`editdate` datetime default null,
			`viewby` varchar(100) not null default '',
			`viewdate` datetime default null,
			PRIMARY KEY (`trno`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("htihead", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["tihead", "htihead"], ["lockuser"], "varchar(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["tihead", "htihead"], ["lockdate"], "datetime DEFAULT NULL", 0);

		$qry = "CREATE TABLE `tripdetail` (
			`trno` int(10) unsigned not null default '0',
			`line` int(11) not null default '0',
			`itemid` int(11) not null default '0',
			`clientid` int(11) not null default '0',
			`activity` varchar(200) not null default '',
			`rate` decimal(18,2) not null default '0.00',
			`encodeddate` datetime default null,
			`encodedby` varchar(100) not null default '',
			`editby` varchar(100) not null default '',
			`editdate` datetime default null,
			PRIMARY KEY (`trno`,`line`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("tripdetail", $qry);

		$qry = "CREATE TABLE `htripdetail` (
			`trno` int(10) unsigned not null default '0',
			`line` int(11) not null default '0',
			`itemid` int(11) not null default '0',
			`clientid` int(11) not null default '0',
			`activity` varchar(200) not null default '',
			`rate` decimal(18,2) not null default '0.00',
			`encodeddate` datetime default null,
			`encodedby` varchar(100) not null default '',
			`editby` varchar(100) not null default '',
			`editdate` datetime default null,
			PRIMARY KEY (`trno`,`line`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("htripdetail", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["tripdetail", "htripdetail"], ["titrno", "batchid"], "INT(11) NOT NULL DEFAULT '0'", 0);

		$qry = "CREATE TABLE `holidayloc` (
			line int(4) unsigned NOT NULL AUTO_INCREMENT,
			dateid datetime DEFAULT NULL,
			description varchar(100) NOT NULL DEFAULT '',
			daytype varchar(15) NOT NULL DEFAULT '',
			divcode varchar(15) NOT NULL DEFAULT '',
			location varchar(45) NOT NULL DEFAULT '',
			branchid INT(11) NOT NULL DEFAULT '0',
			encodeddate datetime default null,
			encodedby varchar(100) not null default '',			
			editdate datetime default null,
			editby varchar(100) not null default '',
			PRIMARY KEY (line),
			KEY `IndexLocation` (`location`),
			KEY `IndexDivCode` (`divcode`),
			KEY `IndexBranch` (`branchid`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1";
		$this->coreFunctions->sbccreatetable("holidayloc", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["holidayloc"], ["encodedby", "editby"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["holidayloc"], ["encodeddate", "editdate"], "datetime DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["holidayloc"], ["branchid"], "INT(11) NOT NULL DEFAULT '0'", 0);

		$qry = "CREATE TABLE `aaccount` (
			`line` int(4) unsigned NOT NULL AUTO_INCREMENT,
			`code` varchar(45) NOT NULL DEFAULT '',
			`codename` varchar(100) NOT NULL  DEFAULT '',
			`seq` int(4) unsigned NOT NULL  DEFAULT '0',
			`type` varchar(1) NOT NULL DEFAULT '',
			`createby` varchar(100) not null default '',
			`createdate` datetime default null,
			`editby` varchar(100) not null default '',
			`editdate` datetime default null,
			PRIMARY KEY (`line`),
			KEY `Index_Code` (`code`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC;";
		$this->coreFunctions->sbccreatetable("aaccount", $qry);

		$qry = "CREATE TABLE `otapplication` (
			`line` int(11) unsigned not null auto_increment,
			`empid` int(11) not null default '0',
			`dateid` datetime default null,
			`othrs` decimal(5,2) DEFAULT '0.00',
			`apothrs` decimal(5,2) DEFAULT '0.00',
			`otstatus` tinyint(1) unsigned DEFAULT '0',
			`otstatus2` tinyint(1) unsigned DEFAULT '0',
			`batchid` int(11) not null default '0',
			`isadv` tinyint(1) unsigned not null default '0',
			`createby` varchar(100) not null default '',
			`createdate` datetime default null,
			`editby` varchar(100) not null default '',
			`editdate` datetime default null,
			`approvedate` datetime default null,
			`approvedby` varchar(100) not null default '',
			`disapprovedate` datetime default null,
			`disapprovedby` varchar(100) not null default '',
			`approvedate2` datetime default null,
			`approvedby2` varchar(100) not null default '',
			`disapprovedate2` datetime default null,
			`disapprovedby2` varchar(100) not null default '',
			`isok` tinyint(1) unsigned not null default '0',
			PRIMARY KEY (`line`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("otapplication", $qry);
		$this->coreFunctions->sbcaddcolumn("otapplication", "remarks", "varchar(150) NOT NULL DEFAULT ''");

		$qry = " CREATE TABLE `undertime` (
			`line` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
			`empid` INT(11) NOT NULL DEFAULT '0',
			`createby` varchar(100) NOT NULL DEFAULT '', 
			`createdate` DATETIME DEFAULT NULL,
			`dateid` DATETIME DEFAULT NULL,
			`type` varchar(20) NOT NULL DEFAULT '',
			`rem` varchar(100) NOT NULL DEFAULT '',
			`status` varchar(100) NOT NULL DEFAULT '',
			`approvedby` varchar(100) NOT NULL DEFAULT '',
			`approvedate` DATETIME DEFAULT NULL, 
			`approverem` varchar(100) NOT NULL DEFAULT '',
			`editby` varchar(100) NOT NULL DEFAULT '',
			`editdate` DATETIME DEFAULT NULL,
			`disapprovedate` DATETIME DEFAULT NULL,
			`disapprovedby` varchar(100) NOT NULL DEFAULT '',
			`isok` INT(11) NOT NULL DEFAULT '0',
			`status2` varchar(100) NOT NULL DEFAULT '',
			`disapprovedate2` DATETIME DEFAULT NULL,
			`disapprovedby2` varchar(100) NOT NULL DEFAULT '',
			`approvedby2` varchar(100) NOT NULL DEFAULT '',
			`approvedate2` DATETIME DEFAULT NULL,
			PRIMARY KEY (`line`)
			) ENGINE = MYISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("undertime", $qry);
		$this->coreFunctions->sbcaddcolumn("tmshifts", "shftcode", "varchar(45) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumngrp(["batch"], ["divid", "branchid"], "INT(11) NOT NULL DEFAULT '0'");
		$this->coreFunctions->sbcaddcolumngrp(["otapplication", "undertime"], ["rem"], "text default null");

		$qry = "CREATE TABLE paydeletelogs (
		line INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
		docno VARCHAR(100) NOT NULL,
		module VARCHAR(100) NOT NULL,
		isdelete TINYINT(1) UNSIGNED DEFAULT 0,
	    isdeleted TINYINT(1) UNSIGNED DEFAULT 0,
		PRIMARY KEY (line),
		KEY `Index_Module` (`module`),
		KEY `Index_IsDeleted` (`isdeleted`)
		) ENGINE = MyISAM;";
		$this->coreFunctions->sbccreatetable("paydeletelogs", $qry);

		$qry = "CREATE TABLE `ndiffapplication` (
			`line` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`empid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
			`dateid` DATETIME DEFAULT NULL,
			`ndiffhrs` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
			`apndiffhrs` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
			`status` VARCHAR(45) NOT NULL DEFAULT '',
			`status2` VARCHAR(45) NOT NULL DEFAULT '',
			`batchid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
			`createby` VARCHAR(100) NOT NULL DEFAULT '',
			`createdate` DATETIME DEFAULT NULL,
			`approvedby` VARCHAR(100) NOT NULL DEFAULT '',
			`approvedate` DATETIME DEFAULT NULL,
			`approvedby2` VARCHAR(100) NOT NULL DEFAULT '',
			`approvedate2` DATETIME DEFAULT NULL,
			`disapprovedby` VARCHAR(100) NOT NULL DEFAULT '',
			`disapprovedate` DATETIME DEFAULT NULL,
			`disapprovedby2` VARCHAR(100) NOT NULL DEFAULT '',
			`disapprovedate2` DATETIME DEFAULT NULL,
			`rem` VARCHAR(150) NOT NULL DEFAULT '',
			`rem2` VARCHAR(150) NOT NULL DEFAULT '',
			`isok` TINYINT(2) UNSIGNED NOT NULL DEFAULT 0,
			PRIMARY KEY (`line`),
			KEY `Index_EmpID` (`empid`),
			KEY `Index_DateID` (`dateid`),
			KEY `Index_Approvedby` (`approvedby`),
			KEY `Index_Approvedby2` (`approvedby2`),
			KEY `Index_IsOK` (`isok`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
		$this->coreFunctions->sbccreatetable("ndiffapplication", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["otapplication"], ["ottimein", "ottimeout"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["otapplication"], ["ndiffhrs", "apndiffhrs"], "DECIMAL(5, 2) NOT NULL DEFAULT '0.00'", 0);
		//2025.01.18 - FMM
		$this->coreFunctions->sbcaddcolumngrp(["obapplication"], ["scheddate"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["otapplication"], ["othrsextra", "apothrsextra", "ndiffothrs", "apndiffothrs"], "DECIMAL(5,2) NOT NULL DEFAULT 0.00 ", 0);
		$this->coreFunctions->sbcaddcolumngrp(["ndiffapplication"], ["ndiffothrs", "apndiffothrs"], "DECIMAL(5,2) NOT NULL DEFAULT 0.00 ", 0);
		$this->coreFunctions->sbcaddcolumn("otapplication", "daytype", "varchar(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["otapplication"], ["dateid2", "scheddate"], "DATETIME DEFAULT NULL", 0);

		$this->coreFunctions->sbcaddcolumngrp(["changeshiftapp"], ["disapproved_remarks", "disapproved_remarks2"], "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "disapproved_remarks2", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("otapplication", "disapproved_remarks2", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("undertime", "disapproved_remarks2", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "disapproved_remarks2", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("loanapplication", "disapproved_remarks2", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "disapproveddate2", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "disapprovedby2", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		// 03-03-2025
		$this->coreFunctions->sbcaddcolumngrp(["obapplication", "changeshiftapp", "loanapplication", "otapplication"], ["submitdate"], "DATETIME DEFAULT NULL", 1);

		$this->coreFunctions->sbcaddcolumngrp(["obapplication"], ["type"], "VARCHAR(50) NOT NULL DEFAULT ''", 1);

		$this->coreFunctions->sbcaddcolumngrp(["changeshiftapp"], ["isok"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("loanapplication", "enddate", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("waims_notice", "empid", "INT(11) UNSIGNED NOT NULL DEFAULT '0'", 0);
		// 03-13-2025
		$this->coreFunctions->sbcaddcolumn("obapplication", "location", "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["obapplication"], ["initialapp", "initialappdate"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "initialapprovedby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->sbcaddcolumngrp(["changeshiftapp"], ["daytype", "orgdaytype"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "shftcode", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "picture", "VARCHAR(255) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "initialstatus", "VARCHAR(1) NOT NULL DEFAULT ''", 0);
		// 03-18-2025
		$this->coreFunctions->sbcaddcolumngrp(["leavetrans", "loanapplication"], ["disapprovedby"], "VARCHAR(150) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["leavetrans", "loanapplication"], ["date_disapproved"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["leavetrans", "loanapplication", "obapplication", "changeshiftapp", "otapplication"], ["isok2"], "TINYINT(1) NOT NULL DEFAULT '0'", 0);

		$qry = "CREATE TABLE temptimecard like timecard";
		$this->coreFunctions->sbccreatetable("temptimecard", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["timecard"], ["ismactualin", "ismactualout", "isobactualin", "isobactualout", "ischangesched", "ismbrkin", "ismbrkout", "ismlunchin", "ismlunchout", "isnologin", "isnologout", "isnologbreak", "isnologunder", "ispbrkin", "ispbrkout"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["timecard"], ["isnombrkin", "isnombrkout", "isnolunchout", "isnolunchin", "isnopbrkin", "isnopbrkout", "isnologpin", "isitinerary", "earlyotapproved"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);

		$this->coreFunctions->sbcaddcolumngrp(["timecard"], ["logactualin", "logactualout", "loglunchin", "loglunchout"], "INT(4) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "isrestday", "tinyint(1) unsigned not null default '0'", 0);

		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "isword", "tinyint(1) unsigned not null default '0'", 0);
		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "reason", "varchar(255) not null default ''", 0);
		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "deptid", "int(11) unsigned not null default 0", 0);

		$this->coreFunctions->sbcaddcolumn("leavetrans", "cancelby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "canceldate", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "reason", "VARCHAR(500) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "forapproval", "DATETIME DEFAULT NULL", 0);

		$this->coreFunctions->sbcaddcolumn("undertime", "cancelby", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["undertime"], ["canceldate", "forapproval"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("undertime", "reason", "VARCHAR(500) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE `rateexempt` (
			`line` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`area` VARCHAR(100) NOT NULL DEFAULT '',
			`rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
			PRIMARY KEY (`line`),
			KEY `Index_Line` (`line`)
			) ENGINE=MyISAM;";
		$this->coreFunctions->sbccreatetable("rateexempt", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["obapplication"], ["canceldate", "forapproval"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "cancelby", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "reason", "VARCHAR(500) NOT NULL DEFAULT ''", 0);

		$qry = "CREATE TABLE `leavecategory` (
			`line` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`category` VARCHAR(150) NOT NULL DEFAULT '',
			`isinactive` tinyint(1) NOT NULL DEFAULT '0',
			`createby` VARCHAR(100) NOT NULL DEFAULT '',
			`createdate` DATETIME DEFAULT NULL,
			`editby` varchar(100) NOT NULL DEFAULT '',
			`editdate` DATETIME DEFAULT NULL,
			PRIMARY KEY (`line`),
			KEY `Index_Line` (`line`)
			) ENGINE=MyISAM;";
		$this->coreFunctions->sbccreatetable("leavecategory", $qry);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "catid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["leavecategory"], ["colorname", "colorcode"], "VARCHAR(500) not null default ''", 0);
		$this->coreFunctions->sbcaddcolumn("loanapplication", "edtrno", "INT(11) NOT NULL DEFAULT '0'");
		$this->coreFunctions->sbcaddcolumn("standardsetup", "loantrno", "INT(11) NOT NULL DEFAULT '0'");

		$this->coreFunctions->sbcaddcolumn("undertime", "catid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "ontrip", "VARCHAR(20) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["undertime", "obapplication"], ["dateid2"], "DATETIME DEFAULT NULL", 0);


		$qry = "CREATE TABLE timeadj (
			line int(10) unsigned NOT NULL AUTO_INCREMENT,
			dateid datetime DEFAULT NULL,
			empid int(10) DEFAULT NULL,
			acnoid int(10) DEFAULT NULL,
			batchid varchar(45) DEFAULT NULL,
			appbatchid int(10) DEFAULT NULL,
			createtime datetime DEFAULT NULL,
			createdby varchar(45) DEFAULT NULL,
			qty decimal(20,2) DEFAULT '0.00',
			amt decimal(20,2) DEFAULT '0.00',
			rem varchar(200) DEFAULT NULL,
			PRIMARY KEY (line),
			KEY Index_EmpID (empid),
			KEY Index_BatchID (batchid),
			KEY Index_AcnoID (acnoid),
			KEY Index_AppBatchID (appbatchid)
			) ENGINE=MyISAM ROW_FORMAT=DYNAMIC;";
		$this->coreFunctions->sbccreatetable("timeadj", $qry);
		$this->coreFunctions->sbcaddcolumn("timecard", "isuspended", "tinyint(1) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["clearance", "hclearance"], ["resignedtype"], "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("standardtrans", "manualref", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("changeshiftapp", "reghrs", "DECIMAL(5,2) NOT NULL DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "batchob", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "scheddate2", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("timerec", "picture", "VARCHAR(255) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("ratesetup", "allowance", "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 1);

		$qry = "CREATE TABLE allowsetuptemp like allowsetup";
		$this->coreFunctions->sbccreatetable("allowsetuptemp", $qry);

		$this->coreFunctions->sbcaddcolumngrp(["allowsetup", "allowsetuptemp"], ["isliquidation"], "TINYINT(1) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["allowsetup", "allowsetuptemp"], ["voiddate"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["allowsetup", "allowsetuptemp"], ["voidby"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "islatefilling", "TINYINT(1) NOT NULL DEFAULT '0'", 0);

		$qry = "CREATE TABLE `leavebatch` (
			`line` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`code` VARCHAR(20) NOT NULL DEFAULT '',
			`codename` VARCHAR(150) NOT NULL DEFAULT '',
			`entitled` INT(11) NOT NULL DEFAULT '0',
			PRIMARY KEY (`line`),
			KEY `Index_Line` (`line`)
			) ENGINE=MyISAM;";
		$this->coreFunctions->sbccreatetable("leavebatch", $qry);

		$qry = "CREATE TABLE `leaveentitled` (
				`trno` INT(10) unsigned NOT NULL DEFAULT '0',
				`line` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`first` INT(10) NOT NULL DEFAULT '0',
				`last` INT(10) NOT NULL DEFAULT '0',
				`days` INT(11) NOT NULL DEFAULT '0',
				PRIMARY KEY (`line`), 
				KEY `Index_Line` (`line`)
				) ENGINE = MyISAM;";
		$this->coreFunctions->sbccreatetable("leaveentitled", $qry);

		$this->coreFunctions->sbcaddcolumn("leavebatch", "isnopay", "TINYINT(1) NOT NULL DEFAULT '0'", 1);
		$this->coreFunctions->sbcaddcolumngrp(["leavebatch"], ["count", "numdays"], "INT(10) NOT NULL DEFAULT '0'", 1);


		$this->coreFunctions->sbcaddcolumngrp(["obapplication"], ["trackingtype"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->sbcaddcolumn("rolesetup", "arearateid", "INT(11) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "fillingtype", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->execqry("ALTER TABLE obapplication MODIFY approverem VARCHAR(1000) NOT NULL DEFAULT ''");
		$this->coreFunctions->execqry("ALTER TABLE obapplication MODIFY disapproved_remarks2 VARCHAR(1000) NOT NULL DEFAULT ''");

		$this->coreFunctions->sbcaddcolumn("loanapplication", "licenseno", "VARCHAR(100) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("loanapplication", "licensetype", "VARCHAR(100) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("loanapplication", "purpose", "VARCHAR(500) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("loanapplication", "purpose1", "VARCHAR(500) NOT NULL DEFAULT ''");
		// $this->coreFunctions->sbcaddcolumn("loanapplication", "purpose", "VARCHAR(500) NOT NULL DEFAULT ''");
		$qry = "CREATE TABLE `obdetail` (
				`trno` INT(10) unsigned NOT NULL DEFAULT '0',
				`line` INT(10) UNSIGNED NOT NULL DEFAULT '0',
				`purpose` VARCHAR(500) NOT NULL DEFAULT '',
				`destination` VARCHAR(500) NOT NULL DEFAULT '',
				`leadfrom` DATETIME DEFAULT NULL,
				`leadto` DATETIME DEFAULT NULL,
				`contact` VARCHAR(50) NOT NULL DEFAULT '',
				`editby` VARCHAR(100) NOT NULL DEFAULT '',
			    `editdate` DATETIME DEFAULT NULL,
                `encodedby` VARCHAR(100) NOT NULL DEFAULT '',
				`encodedate` DATETIME DEFAULT NULL,
				 KEY `Index_Trno` (`trno`),
				 KEY `Index_Line` (`line`)
				 ) ENGINE = MyISAM;";
		$this->coreFunctions->sbccreatetable("obdetail", $qry);
		$this->coreFunctions->sbcaddcolumn("obdetail", "encodedby", "VARCHAR(100) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("obdetail", "encodedate", "DATETIME DEFAULT NULL");
		$this->coreFunctions->sbcaddcolumn("obdetail", "editby", "VARCHAR(100) NOT NULL DEFAULT ''");
		$this->coreFunctions->sbcaddcolumn("batch", "deptid", "INT(11) NOT NULL DEFAULT '0'", 0);

		$qry = "CREATE TABLE `multiapprover` (
               `line` INT(10) unsigned NOT NULL AUTO_INCREMENT,
               `empid` INT(10) unsigned NOT NULL DEFAULT '0',
               `approverid` INT(10) unsigned NOT NULL DEFAULT '0',
               `doc` VARCHAR(50) NOT NULL DEFAULT '',
               `isapprover` TINYINT(2) NOT NULL DEFAULT '0',
               `issupervisor` TINYINT(2) NOT NULL DEFAULT '0',
               `editby` VARCHAR(100) NOT NULL DEFAULT '',
               `editdate` DATETIME DEFAULT NULL,
               `encodedby` VARCHAR(100) NOT NULL DEFAULT '',
               `encodedate` DATETIME DEFAULT NULL,
               PRIMARY KEY (`line`),
               KEY `Index_Empid` (`empid`),
               KEY `Index_Approverid` (`approverid`),
               KEY `Index_Doc` (`doc`)
               ) ENGINE=MyISAM;";
		$this->coreFunctions->sbccreatetable("multiapprover", $qry);
		$this->coreFunctions->sbcaddcolumngrp(["multiapprover"], ["editby", "encodedby"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["multiapprover"], ["editdate", "encodedate"], "DATETIME DEFAULT NULL", 0);

		$this->coreFunctions->sbcaddcolumn("obapplication", "dateid2", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("batch", "isportal", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

		$this->coreFunctions->sbcaddcolumn("paydeletelogs", "isnotexist", "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumn("waims_notice", "title", "VARCHAR(100) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumn("otapplication", "rem", "VARCHAR(250) NOT NULL DEFAULT ''", 1);
		$this->coreFunctions->sbcaddcolumn("obapplication", "initial_remarks", "VARCHAR(250) NOT NULL DEFAULT ''", 0);

		//used in cancelled application from windows payroll
		$this->coreFunctions->sbcaddcolumn("leavetrans", "cancelrem", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("leavetrans", "canceldate", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("otapplication", "issat", "TINYINT(2) NOT NULL DEFAULT '0'", 0);

		$this->coreFunctions->sbcaddcolumn("obapplication", "initialstatus2", "VARCHAR(1) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "initialapprovedby2", "VARCHAR(50) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "initialappdate2", "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "initial_remarks2", "VARCHAR(250) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->sbcaddcolumngrp(["loanapplication"], ["termfrom", "termto", "payrolldate"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["loanapplication"], ["apamt", "apamortization"], "decimal(19,2) NOT NULL DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumn("obapplication", "rem", "VARCHAR(500) NOT NULL DEFAULT ''", 1);

		$this->coreFunctions->sbcaddcolumngrp(["loanapplication"], ["cashadv", "saldedpurchase", "sssploan", "chgduelosses", "uniforms", "otherchgloan"], "decimal(19,2) NOT NULL DEFAULT '0.00'", 0);

		$qry = "CREATE TABLE `loan_picture` (
               `trno` bigint(20) NOT NULL DEFAULT '0',
               `line` int(20) NOT NULL DEFAULT '0',
               `title` varchar(2000) NOT NULL DEFAULT '',
               `picture` varchar(300) NOT NULL DEFAULT '',
               `encodeddate` datetime DEFAULT NULL,
               `encodedby` varchar(15) NOT NULL DEFAULT '',
               KEY `Index_loan_picture` (`trno`,`line`)
               ) ENGINE=MyISAM";
		$this->coreFunctions->sbccreatetable("loan_picture", $qry);

		$this->coreFunctions->sbcaddcolumn("undertime", "underhrs", "DECIMAL(5,2) NOT NULL DEFAULT '0.00'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["leavetrans", "obapplication", "otapplication", "loanapplication", "changeshiftapp"], ["void_remarks"], "VARCHAR(250) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->sbcaddcolumngrp(["moduleapproval"], ["encodedby", "editby"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumngrp(["moduleapproval"], ["encodeddate", "editdate"], "DATETIME DEFAULT NULL", 0);

		$this->coreFunctions->sbcaddcolumngrp(["timecard"], ["islatein", "isearlyout", "islatecusto"], "TINYINT(2) NOT NULL DEFAULT 0", 0);
		$this->coreFunctions->sbcaddcolumngrp(["obapplication", "otapplication", "changeshiftapp", "loanapplication", "leavetrans"], ["void_date"], "DATETIME DEFAULT NULL", 0);
		$this->coreFunctions->sbcaddcolumngrp(["obapplication", "otapplication", "changeshiftapp", "loanapplication", "leavetrans"], ["void_by"], "VARCHAR(100) NOT NULL DEFAULT ''", 0);

		$this->coreFunctions->sbcaddcolumngrp(["paccount"], ["is13th", "ispayroll"], "TINYINT(2) NOT NULL DEFAULT 0", 0);

		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["is13th", "isnobio"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);
		$this->coreFunctions->sbcaddcolumngrp(["employee"], ["mealdeduc"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);

		$this->coreFunctions->createindex("timecard", "Index_DateID", ["dateid"]);
		$this->coreFunctions->createindex("timerec", "Index_Timeinout", ["timeinout"]);

		$this->coreFunctions->createindex("timesheet", "Index_EmpID", ["empid"]);
		$this->coreFunctions->createindex("timesheet", "Index_DateID", ["dateid"]);
		$this->coreFunctions->createindex("timesheet", "Index_AcnoID", ["acnoid"]);
		$this->coreFunctions->createindex("timesheet", "Index_BatchID", ["batchid"]);

		$this->coreFunctions->createindex("paytrancurrent", "Index_DateID", ["dateid"]);
		$this->coreFunctions->createindex("paytrancurrent", "Index_AcnoID", ["acnoid"]);
		$this->coreFunctions->createindex("paytrancurrent", "Index_BatchID", ["batchid"]);

		$this->coreFunctions->createindex("paytranhistory", "Index_DateID", ["dateid"]);
		$this->coreFunctions->createindex("paytranhistory", "Index_AcnoID", ["acnoid"]);
		$this->coreFunctions->createindex("paytranhistory", "Index_BatchID", ["batchid"]);

		$this->coreFunctions->sbcaddcolumngrp(["leavesetup"], ["days", "bal"], "DECIMAL(10,2) NOT NULL DEFAULT '0.00'", 1);

		$this->coreFunctions->sbcaddcolumngrp(["ssstab"], ["mpfee", "mpfer"], "DECIMAL(18,2) NOT NULL DEFAULT '0.00'", 0);

		$qry = "CREATE TABLE `timesetup` (
                `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
                `times` datetime DEFAULT NULL,
                 PRIMARY KEY (`line`)
               ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
		$this->coreFunctions->sbccreatetable("timesetup", $qry);

		$this->coreFunctions->sbcaddcolumn("timesetup", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
		$this->coreFunctions->sbcaddcolumn("timesetup", "editdate", "DATETIME DEFAULT NULL", 0);

		$this->coreFunctions->sbcaddcolumngrp(["client_log", "item_log", "wh_log", "table_log", "transnum_log", "docunum_log", "masterfile_log", "payroll_log"], ["istemp"], "TINYINT(2) NOT NULL DEFAULT '0'", 0);

		$qry = "CREATE TABLE `leave_picture` (
               `trno` bigint(20) NOT NULL DEFAULT '0',
               `line` int(20) NOT NULL DEFAULT '0',
               `ltline` int(20) NOT NULL DEFAULT '0',
               `title` varchar(2000) NOT NULL DEFAULT '',
               `picture` varchar(300) NOT NULL DEFAULT '',
               `encodeddate` datetime DEFAULT NULL,
               `encodedby` varchar(15) NOT NULL DEFAULT '',
               KEY `Index_trno` (`trno`),
               KEY `Index_ltline` (`ltline`)
               ) ENGINE=MyISAM; ";
		$this->coreFunctions->sbccreatetable("leave_picture", $qry);
	} //end function
} // end class
