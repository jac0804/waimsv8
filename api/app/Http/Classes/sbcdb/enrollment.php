<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;

class enrollment
{

  private $coreFunctions;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
  } //end fn



  public function tableupdateenrollment()
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    //FMM 5.3.2021 - moved here because all alter tables with this field got an error incorrect default value '0000-00-00'.
    //Must alter this fields first
    $this->coreFunctions->execqrynolog("update en_period set estart=null where estart='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set eend=null where eend='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set eext=null where eext='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set sstart=null where sstart='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set send=null where send='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set sext=null where sext='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set astart=null where astart='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set aend=null where aend='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_period set aext=null where aext='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_glsubject set schedstarttime=null where schedstarttime='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_glsubject set schedendtime=null where schedendtime='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_scsubject set schedstarttime=null where schedstarttime='0000-00-00 00:00:00'");
    $this->coreFunctions->execqrynolog("update en_scsubject set schedendtime=null where schedendtime='0000-00-00 00:00:00'");

    $this->coreFunctions->sbcaddcolumn("en_period", "estart", "DATE DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("en_glsubject", "schedstarttime", "DATE DEFAULT NULL", 1);
    $this->coreFunctions->sbcaddcolumn("en_glsubject", "schedendtime", "DATE DEFAULT NULL", 1);
    //end of FMM 5.3.2021

    $qry = "CREATE TABLE `en_schoolyear` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `sy` varchar(45)  NOT NULL DEFAULT '',
      `issy` int(10) unsigned NOT NULL DEFAULT '0',
      `code` varchar(45)  NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (code)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbcaddcolumn("en_schoolyear", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_schoolyear", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbccreatetable("en_schoolyear", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_schoolyear CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_scheme` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `scheme` varchar(45)  NOT NULL DEFAULT '',
      `orderscheme` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      INDEX name (scheme)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_scheme", $qry);
    $this->coreFunctions->sbcaddcolumn("en_scheme", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_scheme", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_scheme CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_section` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `section` varchar(45)  NOT NULL DEFAULT '',
      `isterms` int(10) unsigned NOT NULL DEFAULT '0',
      `coursecode` varchar(55)  NOT NULL DEFAULT '',
      `coursename` varchar(450)  NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (section)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_section", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_section CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumn("en_section", "courseid", " int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_section", "isinactive", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_section", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_section", "editdate", "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE `en_attendancetype` (
      `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `type` varchar(45) NOT NULL DEFAULT '',
      `color` varchar(45) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      INDEX name (line)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_attendancetype", $qry);
    $this->coreFunctions->sbcaddcolumn("en_attendancetype", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_attendancetype", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->execqry("ALTER TABLE en_attendancetype CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_levels` (
      `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `levels` varchar(45) NOT NULL DEFAULT '',
      `orderlevels` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      INDEX name (levels)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_levels", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_levels CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_levels'], ['isgradeschool', 'ishighschool', 'isenconvertgrade', 'ischiconvertgrade'], "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_levels", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_levels", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_period` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45)  NOT NULL DEFAULT '',
      `name` varchar(450)  NOT NULL DEFAULT '',
      `terms` varchar(45)  NOT NULL DEFAULT '',
      `year` varchar(45)  NOT NULL DEFAULT '',
      `estart` datetime DEFAULT NULL,
      `eend` datetime DEFAULT NULL,
      `eext` datetime DEFAULT NULL,
      `sstart` datetime DEFAULT NULL,
      `send` datetime DEFAULT NULL,
      `sext` datetime DEFAULT NULL,
      `astart` datetime DEFAULT NULL,
      `aend` datetime DEFAULT NULL,
      `aext` datetime DEFAULT NULL,
      `sy` varchar(45)  NOT NULL DEFAULT '',
      `isactive` tinyint(4) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      INDEX name (code)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_period", $qry);
    $this->coreFunctions->sbcaddcolumn("en_period", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_period", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_period CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_period'], ['semid', 'principalid'], "int(11) unsigned NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `en_term` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `term` varchar(45) NOT NULL DEFAULT '',
      `orderterm` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      INDEX name (term)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_term", $qry);
    $this->coreFunctions->sbcaddcolumn("en_term", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_term", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_term CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_dept` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `deptcode` varchar(45) NOT NULL DEFAULT '',
      `deptname` varchar(100) NOT NULL DEFAULT '',
      `signatory` varchar(45) NOT NULL DEFAULT '',
      `parentcode` varchar(45)  NOT NULL DEFAULT '',
      `parentname` varchar(45) NOT NULL DEFAULT '',
      `deancode` varchar(45)  NOT NULL DEFAULT '',
      `deanname` varchar(45)  NOT NULL DEFAULT '',
      `fund` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `orderdept` int(11) unsigned NOT NULL DEFAULT '0',
      `level` varchar(45)  NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (deptcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_dept", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_dept CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_rooms` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `buildingcode` varchar(45)  NOT NULL DEFAULT '',
      `buildingname` varchar(450)  NOT NULL DEFAULT '',
      `roomcode` varchar(45)  NOT NULL DEFAULT '',
      `roomname` varchar(450)  NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (buildingcode,roomcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_rooms", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_rooms CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumn("en_rooms", "bldgid", " int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_rooms", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_rooms", "editdate", "datetime DEFAULT NULL", 0);

    $key = $this->coreFunctions->opentable("SHOW KEYS FROM  en_rooms WHERE Key_name='room_line_index'");
    if (empty($key)) {
      $this->coreFunctions->execqry("ALTER TABLE `en_rooms` ADD INDEX `room_line_index` (`line`)", 'add');
    }

    $qry = "ALTER TABLE en_rooms DROP PRIMARY KEY";
    $this->coreFunctions->execqry($qry, 'drop');

    $qry = "ALTER TABLE en_rooms ADD PRIMARY KEY USING BTREE(`bldgid`, `roomcode`)";
    $this->coreFunctions->execqry($qry, 'add');

    $qry = "CREATE TABLE `en_course` (
      `line` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450)  NOT NULL DEFAULT '',
      `tfaccount` varchar(45) NOT NULL DEFAULT '',
      `isinactive` tinyint(4) NOT NULL DEFAULT '0',
      `level` varchar(45)  NOT NULL DEFAULT '',
      `deanname` varchar(450) NOT NULL DEFAULT '',
      `deptcode` varchar(45) NOT NULL DEFAULT '',
      `isdegree` tinyint(4) NOT NULL DEFAULT '0',
      `isundergraduate` tinyint(4) NOT NULL DEFAULT '0',
      PRIMARY KEY (`line`),
      INDEX name (coursecode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_course", $qry);
    $this->coreFunctions->sbcaddcolumn("en_course", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_course", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_course CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_course", "tfaccount", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_course", "levelid", " int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_course", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_instructor` (
      `line` int(11) unsigned unsigned NOT NULL AUTO_INCREMENT,
      `teachercode` varchar(45) NOT NULL DEFAULT '',
      `teachername` varchar(450) NOT NULL DEFAULT '',
      `department` varchar(45) NOT NULL DEFAULT '',
      `deptcode` varchar(45) NOT NULL DEFAULT '',
      `address` varchar(450) NOT NULL DEFAULT '',
      `telno` varchar(45) NOT NULL DEFAULT '',
      `callname` varchar(450) NOT NULL DEFAULT '',
      `deancode` varchar(45) NOT NULL DEFAULT '',
      `deanname` varchar(450) NOT NULL DEFAULT '',
      `rank` varchar(45) NOT NULL DEFAULT '',
      `levels` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (teachercode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_instructor", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_instructor CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");


    $qry = "CREATE TABLE `en_modeofpayment` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45) NOT NULL DEFAULT '',
      `deductpercent` decimal(18,2) NOT NULL DEFAULT '0.00',
      `modeofpayment` varchar(450) NOT NULL DEFAULT '',
      `months` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc1` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc2` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc3` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc4` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc5` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc6` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc7` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc8` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc9` decimal(18,2) NOT NULL DEFAULT '0.00',
      `perc10` decimal(18,2) NOT NULL DEFAULT '0.00',
      `date1` datetime DEFAULT NULL,
      `date2` datetime DEFAULT NULL,
      `date3` datetime DEFAULT NULL,
      `date4` datetime DEFAULT NULL,
      `date5` datetime DEFAULT NULL,
      `date6` datetime DEFAULT NULL,
      `date7` datetime DEFAULT NULL,
      `date8` datetime DEFAULT NULL,
      `date9` datetime DEFAULT NULL,
      `date10` datetime DEFAULT NULL,
      PRIMARY KEY (`line`),
      INDEX name (code)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_modeofpayment", $qry);
    $this->coreFunctions->sbcaddcolumn("en_modeofpayment", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_modeofpayment", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_modeofpayment CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_credentials` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `credentials` varchar(150) NOT NULL DEFAULT '',
      `amt` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `particulars` varchar(450) NOT NULL DEFAULT '',
      `percentdisc` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `credentialcode` varchar(45) NOT NULL DEFAULT '',
      `acno` varchar(45) NOT NULL DEFAULT '',
      `acnoname` varchar(450) NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(450) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (credentialcode,`acno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_credentials", $qry);
    $this->coreFunctions->sbcaddcolumn("en_credentials", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_credentials", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_credentials CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_credentials'], ['acnoid', 'feesid', 'subjectid', 'schemeid'], "int(11) NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `en_requirements` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `requirements` varchar(45)  NOT NULL DEFAULT '',
      `studenttype` varchar(450)  NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbcaddcolumn("en_requirements", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_requirements", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbccreatetable("en_requirements", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_requirements CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_fees` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `feesdesc` varchar(45) NOT NULL DEFAULT '',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      `acno` varchar(45) NOT NULL DEFAULT '',
      `vat` decimal(18,2) NOT NULL DEFAULT '0.00',
      `amount` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`line`),
      INDEX name (feescode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_fees", $qry);
    $this->coreFunctions->sbcaddcolumn("en_fees", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_fees", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_fees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_fees", "acnoid", "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_fees", "scheme", "varchar(50) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_quartersetup` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `code` varchar(45) NOT NULL DEFAULT '',
      `name` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_quartersetup", $qry);
    $this->coreFunctions->sbcaddcolumn("en_quartersetup", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_quartersetup", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_quartersetup", "chinesecode", "varchar(255) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_quartersetup CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");


    $qry = "CREATE TABLE `en_honorrollcriteria` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `rankcriteria` decimal(18,2) NOT NULL DEFAULT '0.00',
      `title` varchar(250) NOT NULL DEFAULT '',
      `lowgrade` decimal(18,2) NOT NULL DEFAULT '0.00',
      `highgrade` decimal(18,2) NOT NULL DEFAULT '0.00',
      `encodedby` varchar(250) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_honorrollcriteria", $qry);
    $this->coreFunctions->sbcaddcolumn("en_honorrollcriteria", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_honorrollcriteria", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_honorrollcriteria CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_conductgrade` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `conductenglish` varchar(150) NOT NULL DEFAULT '',
      `conductchinese` varchar(150) NOT NULL DEFAULT '',
      `lowgrade` decimal(18,2) NOT NULL DEFAULT '0.00',
      `highgrade` decimal(18,2) NOT NULL DEFAULT '0.00',
      `encodedby` varchar(90) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      PRIMARY KEY (`line`)
      ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $this->coreFunctions->sbccreatetable("en_conductgrade", $qry);
    $this->coreFunctions->sbcaddcolumn("en_conductgrade", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_conductgrade", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_conductgrade CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_gradecomponent` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcname` varchar(450) NOT NULL DEFAULT '',
      `gcpercent` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`line`),
      INDEX name (gccode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gradecomponent", $qry);
    $this->coreFunctions->sbcaddcolumn("en_gradecomponent", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_gradecomponent", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_gradecomponent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_gradecomponent", "isconduct", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_gradeequivalent` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `range1` decimal(18,2) NOT NULL DEFAULT '0.00',
      `range2` decimal(18,2) NOT NULL DEFAULT '0.00',
      `equivalent` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gradeequivalent", $qry);
    $this->coreFunctions->sbcaddcolumn("en_gradeequivalent", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_gradeequivalent", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_gradeequivalent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->execqry("ALTER TABLE en_gradeequivalent modify column equivalent decimal(18,2) NOT NULL DEFAULT  '0.00'");
    $this->coreFunctions->sbcaddcolumngrp(['en_gradeequivalent'], ['gradeequivalent', 'chineseequivalent', 'actiontaken'], "varchar(45) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_gradeequivalentletters` (
      `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
      `range1` decimal(18,2) NOT NULL DEFAULT '0.00',
      `range2` decimal(18,2) NOT NULL DEFAULT '0.00',
      `equivalent` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gradeequivalentletters", $qry);
    $this->coreFunctions->sbcaddcolumngrp(['en_gradeequivalentletters'], ['gradeequivalent', 'chineseequivalent', 'actiontaken'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_gradeequivalentletters", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_gradeequivalentletters", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_studentinfo` (
      `clientid` int(10) unsigned NOT NULL DEFAULT '0',
      `client` varchar(15) NOT NULL DEFAULT '',
      `fname` varchar(45) NOT NULL DEFAULT '',
      `lname` varchar(45) NOT NULL DEFAULT '',
      `mname` varchar(45) NOT NULL DEFAULT '',
      `studentid` varchar(45) NOT NULL DEFAULT '',
      `course` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `major` varchar(45) NOT NULL DEFAULT '',
      `majorname` varchar(450) NOT NULL DEFAULT '',
      `isnew` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `isold` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `iscrossenrollee` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `isforeign` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `isadddrop` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `islateenrollee` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `istransferee` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      `isdept` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `branch` varchar(45) NOT NULL DEFAULT '',
      `gender` varchar(45) NOT NULL DEFAULT '',
      `civilstatus` varchar(45) NOT NULL DEFAULT '',
      `bplace` varchar(450) NOT NULL DEFAULT '',
      `studenttype` varchar(45) NOT NULL DEFAULT '',
      `city` varchar(450) NOT NULL DEFAULT '',
      `relation` varchar(45) NOT NULL DEFAULT '',
      `gaddr` varchar(250) NOT NULL DEFAULT '',
      `batch` varchar(45) NOT NULL DEFAULT '',
      `nationality` varchar(45) NOT NULL DEFAULT '',
      `isregular` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `isirregular` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `isextramural` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `extramural` varchar(45) NOT NULL DEFAULT '',
      `haddr` varchar(450) NOT NULL DEFAULT '',
      `baddr` varchar(450) NOT NULL DEFAULT '',
      `guardian` varchar(45) NOT NULL DEFAULT '',
      `htel` varchar(45) NOT NULL DEFAULT '',
      `btel` varchar(45) NOT NULL DEFAULT '',
      `gtel` varchar(45) NOT NULL DEFAULT '',
      `elementary` varchar(450) NOT NULL DEFAULT '',
      `highschool` varchar(450) NOT NULL DEFAULT '',
      `college` varchar(450) NOT NULL DEFAULT '',
      `postschool` varchar(450) NOT NULL DEFAULT '',
      `eyear` decimal(18,0) NOT NULL DEFAULT '0.00',
      `hyear` decimal(18,0) NOT NULL DEFAULT '0.00',
      `cyear` decimal(18,0) NOT NULL DEFAULT '0.00',
      `pyear` decimal(18,0) NOT NULL DEFAULT '0.00',
      `company` varchar(450) NOT NULL DEFAULT '',
      PRIMARY KEY (`clientid`),
      INDEX name (client)
      ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $this->coreFunctions->sbccreatetable("en_studentinfo", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_studentinfo CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_studentinfo'], ['curriculumtrno', 'courseid', 'sectionid', 'schedtrno', 'regtrno', 'assesstrno', 'chinesecourseid', 'chineselevelup'], "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_studentinfo'], ['yr', 'levelup'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_studentinfo", "chinesename", "VARCHAR(450) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_studentinfo MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_subject` (
      `trno` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(450) NOT NULL DEFAULT '',
      `isinactive` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `units` decimal(18,2) NOT NULL DEFAULT '0.00',
      `ismajor` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `lecture` decimal(18,2) NOT NULL DEFAULT '0.00',
      `laboratory` decimal(18,2) NOT NULL DEFAULT '0.00',
      `hours` decimal(18,2) NOT NULL DEFAULT '0.00',
      `level` varchar(45) NOT NULL DEFAULT '',
      `prereq1` varchar(45) NOT NULL DEFAULT '',
      `prereq2` varchar(45) NOT NULL DEFAULT '',
      `prereq3` varchar(45) NOT NULL DEFAULT '',
      `prereq4` varchar(45) NOT NULL DEFAULT '',
      `tf` decimal(18,2) NOT NULL DEFAULT '0.00',
      `loadx` decimal(18,2) NOT NULL DEFAULT '0.00',
      `coreq` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      INDEX name (subjectcode)
      ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";

    $this->coreFunctions->sbccreatetable("en_subject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_subject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_subject", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_subject", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_subject", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_subjectcurriculum` (
      `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `units` decimal(18,2) NOT NULL DEFAULT '0.00',
      `subjectname` varchar(45) NOT NULL DEFAULT '',
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (subjectcode,curriculumcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_subjectcurriculum", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_subjectcurriculum CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_subjectequivalent` (
      `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `subjectcode` varchar(80) NOT NULL DEFAULT '',
      `subjectname` varchar(450) NOT NULL DEFAULT '',
      `units` decimal(18,2) NOT NULL DEFAULT '0.00',
      `hours` decimal(18,2) NOT NULL DEFAULT '0.00',
      `load` decimal(18,2) NOT NULL DEFAULT '0.00',
      `laboratory` decimal(18,2) NOT NULL DEFAULT '0.00',
      `lecture` decimal(18,2) NOT NULL DEFAULT '0.00',
      `subjectmain` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`line`),
      INDEX name (subjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_subjectequivalent", $qry);

    $this->coreFunctions->sbcaddcolumn("en_subjectequivalent", "editby", "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_subjectequivalent", "editdate", "datetime DEFAULT NULL", 0);

    $this->coreFunctions->execqry("ALTER TABLE en_subjectequivalent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_subjectequivalent", "subjectid", "INT(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_cchead` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `docno` varchar(45) NOT NULL DEFAULT '',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `curriculumname` varchar(450) NOT NULL DEFAULT '',
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `effectfromdate` datetime DEFAULT NULL,
      `effecttodate` datetime DEFAULT NULL,
      `level` varchar(45) NOT NULL DEFAULT '',
      `sy` varchar(45) NOT NULL DEFAULT '',
      `terms` varchar(45) NOT NULL DEFAULT '',
      `doc` varchar(45) NOT NULL DEFAULT '',
      `yr` varchar(45) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      INDEX name (docno,curriculumcode,coursecode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_cchead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_cchead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_cchead'], ['createby', 'editby', 'viewby', 'lockuser'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_cchead'], ['editdate', 'createdate', 'viewdate', 'lockdate', 'dateid'], "DATETIME DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_cchead'], ['periodid', 'syid', 'courseid', 'levelid', 'semid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_cchead", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_cchead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");

    $qry = "CREATE TABLE `en_ccsubject` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `yearnum` varchar(45) NOT NULL DEFAULT '',
      `terms` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(450) NOT NULL DEFAULT '',
      `units` decimal(18,2) NOT NULL DEFAULT '0.00',
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `pre1` varchar(45) NOT NULL DEFAULT '',
      `pre2` varchar(45) NOT NULL DEFAULT '',
      `pre3` varchar(45) NOT NULL DEFAULT '',
      `pre4` varchar(45) NOT NULL DEFAULT '',
      `pre5` varchar(45) NOT NULL DEFAULT '',
      `lecture` decimal(18,2) NOT NULL DEFAULT '0.00',
      `laboratory` decimal(18,2) NOT NULL DEFAULT '0.00',
      `coreq` varchar(45) NOT NULL DEFAULT '',
      `hours` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (curriculumcode,coursecode,subjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_ccsubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_ccsubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_ccsubject'], ['subjectid', 'courseid', 'semid', 'pre1id', 'pre2id', 'pre3id', 'pre4id', 'pre5id', 'coreqid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_ccsubject", "cline", "INT(11) NOT NULL DEFAULT '0', DROP PRIMARY KEY, ADD PRIMARY KEY USING BTREE(`trno`, `line`, `cline`)", 0);

    $qry = "CREATE TABLE `en_ccyear`
      (`trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `year` varchar(45) NOT NULL DEFAULT '',
      `semid` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`, `line`),
      INDEX name (`year`, `semid`))
      ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_ccyear", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_ccyear CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_ccyear', 'en_glyear'], ['levelup'], "varchar(75) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_ccbooks`
      (`trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `cline` int(11) unsigned NOT NULL DEFAULT '0',
      `itemid` int(11) unsigned NOT NULL DEFAULT '0',
      `semid` int(11) unsigned NOT NULL DEFAULT '0',
      `isqty` decimal(18,2) NOT NULL DEFAULT '0.00',
      `isamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `ext` decimal(18,2) NOT NULL DEFAULT '0.00',
      `disc` varchar(45) NOT NULL DEFAULT '',
      `uom` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`, `line`, `cline`),
      INDEX name (`cline`, `itemid`))
      ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_ccbooks", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_ccbooks CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_glbooks`
    (`trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL DEFAULT '0',
    `cline` int(11) unsigned NOT NULL DEFAULT '0',
    `itemid` int(11) unsigned NOT NULL DEFAULT '0',
    `semid` int(11) unsigned NOT NULL DEFAULT '0',
    `isqty` decimal(18,2) NOT NULL DEFAULT '0.00',
    `isamt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
    `ext` decimal(18,2) NOT NULL DEFAULT '0.00',
    `disc` varchar(45) NOT NULL DEFAULT '',
    `uom` varchar(45) NOT NULL DEFAULT '',
    PRIMARY KEY (`trno`, `line`, `cline`),
    INDEX name (`cline`, `itemid`))
    ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_glbooks", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glbooks CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_glyear`
      (`trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `year` varchar(45) NOT NULL DEFAULT '',
      `semid` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`, `line`),
      INDEX name (`year`, `semid`))
      ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_glyear", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glyear CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_scurriculum`
      (`trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `cline` int(11) unsigned NOT NULL DEFAULT '0',
      `clientid` int(11) unsigned NOT NULL DEFAULT '0',
      `grade` int(11) unsigned NOT NULL DEFAULT '0',
      `subjectid` int(11) unsigned NOT NULL DEFAULT '0',
      `courseid` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`, `line`, `cline`, `clientid`))
      ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_scurriculum", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_scurriculum CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumn("en_scurriculum", "quarterid", "INT(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_sarchive`
      (`trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `cline` int(11) unsigned NOT NULL DEFAULT '0',
      `clientid` int(11) unsigned NOT NULL DEFAULT '0',
      `grade` int(11) unsigned NOT NULL DEFAULT '0',
      `subjectid` int(11) unsigned NOT NULL DEFAULT '0',
      `courseid` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`, `line`, `cline`, `clientid`))
      ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_sarchive", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sarchive CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");


    $qry = "CREATE TABLE `en_sohead` (
      `TrNo` int(10) unsigned NOT NULL DEFAULT '0',
      `DOC` varchar(2)  NOT NULL DEFAULT '',
      `DocNo` varchar(20)  NOT NULL DEFAULT '',
      `CLIENT` varchar(15)  NOT NULL DEFAULT '',
      `ClientName` varchar(150) NOT NULL DEFAULT '',
      `ADDRESS` varchar(150) NOT NULL DEFAULT '',
      `ShipTo` varchar(150) NOT NULL DEFAULT '',
      `Tel` varchar(50) NOT NULL DEFAULT '',
      `DateID` datetime DEFAULT NULL,
      `DUE` datetime DEFAULT NULL,
      `WH` varchar(15) NOT NULL DEFAULT '',
      `TERMS` varchar(30) NOT NULL DEFAULT '',
      `REM` varchar(500) NOT NULL DEFAULT '',
      `Cur` varchar(2) NOT NULL DEFAULT '',
      `Forex` decimal(18,2) NOT NULL DEFAULT '0.00',
      `VoidDate` datetime  DEFAULT NULL,
      `Branch` varchar(30) NOT NULL DEFAULT '',
      `Agent` varchar(50)  NOT NULL DEFAULT '',
      `YourRef` varchar(25)  NOT NULL DEFAULT '',
      `OurRef` varchar(25)  NOT NULL DEFAULT '',
      `ApprovedBy` varchar(50)  NOT NULL DEFAULT '',
      `ApprovedDate`  datetime DEFAULT NULL,
      `PrintTime` datetime DEFAULT NULL,
      `SettleTime` datetime DEFAULT NULL,
      `coursecode` varchar(45)  NOT NULL DEFAULT '',
      `coursename` varchar(450)  NOT NULL DEFAULT '',
      `curriculumcode` varchar(45)  NOT NULL DEFAULT '',
      `totalunit` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `tuitionfee` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `tuitionperunit` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `tuitorial` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `registration` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `misc` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `sy` varchar(45)  NOT NULL DEFAULT '',
      `others` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `type` varchar(45)  NOT NULL DEFAULT '',
      `extramural` varchar(45)  NOT NULL DEFAULT '',
      `majorcode` varchar(45)  NOT NULL DEFAULT '',
      `majorname` varchar(450)  NOT NULL DEFAULT '',
      `modularfee` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `inventory` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `totalbalance` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `isenrolled` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `curriculumname` varchar(450)  NOT NULL DEFAULT '',
      `credentials` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `additionaldisc` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `section` varchar(45)  NOT NULL DEFAULT '',
      `sotrno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `reservationfee` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `period` varchar(45)  NOT NULL DEFAULT '',
      `schedcode` varchar(45)  NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45)  NOT NULL DEFAULT '',
      `level` varchar(45)  NOT NULL DEFAULT '',
      `deptcode` varchar(45)  NOT NULL DEFAULT '',
      `sex` varchar(45)  NOT NULL DEFAULT '',
      `yr` varchar(45)  NOT NULL DEFAULT '',
      `modeofpayment` varchar(45)  NOT NULL DEFAULT '',
      `contra` varchar(45)  NOT NULL DEFAULT '',
      `interestamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totalamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totallec` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `totallab` decimal(18,2)  NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`),
      INDEX name (DocNo,client,wh)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sohead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sohead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sohead'], ['courseid', 'semid', 'periodid', 'deptid', 'syid', 'levelid', 'curriculumtrno', 'sectionid'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sohead', 'en_glhead', 'en_sjhead', 'glhead'], ['disc'], "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glhead'], ['deptid', 'syid'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glhead', 'glhead'], ['isdropped'], "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['glhead'], ['syid', 'periodid', 'courseid', 'semid', 'sectionid', 'yr', 'levelid', 'deptid', 'sotrno'], "INT(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['glhead'], ['assessref', 'encodedby'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['glhead', 'en_sohead'], ['ischinese'], "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("glhead", "encodeddate", "datetime", 0);

    $this->coreFunctions->execqry("ALTER TABLE glhead modify column yr varchar(45) NOT NULL DEFAULT  ''");
    $this->coreFunctions->execqry("ALTER TABLE en_sohead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_socredentials` (
      `trno` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `line` int(11) NOT NULL DEFAULT '0',
      `credentials` varchar(45) NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `particulars` varchar(450) NOT NULL DEFAULT '',
      `percentdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `acno` varchar(45) NOT NULL DEFAULT '',
      `acnoname` varchar(45) NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(45) NOT NULL DEFAULT '',
      `credentialcode` varchar(45) NOT NULL DEFAULT '',
      `camt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acno,feescode,subjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_socredentials", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_socredentials CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_socredentials'], ['credentialid', 'acnoid', 'feesid', 'subjectid', 'schemeid'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glcredentials'], ['credentialid', 'feesid'], "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_sodetail` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `acno` varchar(15) NOT NULL DEFAULT '',
      `client` varchar(20) NOT NULL DEFAULT '',
      `acnoname` varchar(250) NOT NULL DEFAULT '',
      `db` decimal(20,6) NOT NULL DEFAULT '0.00',
      `cr` decimal(20,6) NOT NULL DEFAULT '0.00',
      `fdb` decimal(20,6)  NOT NULL DEFAULT '0.00',
      `fcr` decimal(20,6)  NOT NULL DEFAULT '0.00',
      `rem` varchar(255) NOT NULL DEFAULT '',
      `Agent` varchar(50) NOT NULL DEFAULT '',
      `CheckNo` varchar(50) NOT NULL DEFAULT '',
      `CheckDate` datetime DEFAULT NULL,
      `Postdate` datetime DEFAULT NULL,
      `interest` decimal(19,2)  NOT NULL DEFAULT '0.00',
      `ref` varchar(20) NOT NULL DEFAULT '',
      INDEX name (acno,client)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sodetail", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sodetail CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sodetail', 'en_gldetail'], ['editdate', 'encodeddate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sodetail', 'en_gldetail'], ['editby', 'encodedby', 'cur', 'ewtcode'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sodetail', 'en_gldetail'], ['acnoid', 'forex', 'refx', 'linex', 'clearday', 'pdcline'], "INT(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sodetail', 'en_gldetail'], ['isewt', 'isvat'], "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sodetail', 'en_gldetail'], ['ewtrate'], "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_sootherfees` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `barcode` varchar(45) NOT NULL DEFAULT '',
      `itemname` varchar(450) NOT NULL DEFAULT '',
      `isamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rem` varchar(450) NOT NULL DEFAULT '',
      `feescode` varchar(50) NOT NULL DEFAULT '',
      `feestype` varchar(50) NOT NULL DEFAULT '',
      `scheme` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (barcode,feescode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sootherfees", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sootherfees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sootherfees'], ['feesid', 'acnoid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->execqry("drop table if exists en_sosubjects");

    $qry = "CREATE TABLE `en_sosubject` (
      `TrNo` bigint(20) unsigned NOT NULL DEFAULT '0',
      `LINE` int(11) unsigned NOT NULL DEFAULT '0',
      `subjectcode` varchar(30) NOT NULL DEFAULT '',
      `subjectname` varchar(500) NOT NULL DEFAULT '',
      `schedday` varchar(50) NOT NULL DEFAULT '',
      `schedtime` varchar(50) NOT NULL DEFAULT '',
      `rooms` varchar(40) NOT NULL DEFAULT '',
      `instructorcode` varchar(40) NOT NULL DEFAULT '',
      `units` decimal(19,6) NOT NULL DEFAULT '0.00',
      `lecture` decimal(19,6) NOT NULL DEFAULT '0.00',
      `laboratory` decimal(19,6) NOT NULL DEFAULT '0.00',
      `schedref` varchar(45) NOT NULL DEFAULT '',
      `refx` bigint(20) unsigned NOT NULL DEFAULT '0',
      `linex` int(11) unsigned NOT NULL DEFAULT '0',
      `instructorname` varchar(450) NOT NULL DEFAULT '',
      `hours` decimal(18,2) NOT NULL DEFAULT '0.00',
      `origtrno` int(11) unsigned NOT NULL DEFAULT '0',
      `origline` int(11) unsigned NOT NULL DEFAULT '0',
      `origdocno` varchar(45) NOT NULL DEFAULT '',
      `origsubjectcode` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (subjectcode,instructorcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sosubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sosubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sosubject'], ['subjectid', 'roomid', 'bldgid', 'instructorid', 'ctrno', 'cline', 'scline', 'semid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glsubject'], ['instructorid', 'semid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sosubject'], ['schedstarttime', 'schedendtime'], "datetime", 0);

    $qry = "CREATE TABLE `en_sosummary` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `ref` varchar(45)  NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45)  NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (feescode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sosummary", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sosummary CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_sosummary', 'en_glsummary'], ['feesid', 'schemeid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_sjcredentials` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `credentials` varchar(45) NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `particulars` varchar(450) NOT NULL DEFAULT '',
      `percentdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `ref` varchar(45) NOT NULL DEFAULT '',
      `refx` bigint(20) unsigned NOT NULL DEFAULT '0',
      `linex` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (credentials)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sjcredentials", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sjcredentials CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sjcredentials'], ['credentialid', 'acnoid', 'feesid', 'subjectid', 'schemeid'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_sjcredentials", "camt", "decimal(18,2) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_sjcredentials", "scheme", "varchar(50) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_sjdetail` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `LINE` int(11) unsigned NOT NULL DEFAULT '0',
      `acno` varchar(15) NOT NULL DEFAULT '',
      `client` varchar(20) NOT NULL DEFAULT '',
      `acnoname` varchar(250) NOT NULL DEFAULT '',
      `db` decimal(20,6) NOT NULL DEFAULT '0.00',
      `cr` decimal(20,6) NOT NULL DEFAULT '0.00',
      `fdb` decimal(20,6) NOT NULL DEFAULT '0.00',
      `fcr` decimal(20,6) NOT NULL DEFAULT '0.00',
      `rem` varchar(255) NOT NULL DEFAULT '',
      `Agent` varchar(50) NOT NULL DEFAULT '',
      `CheckNo` varchar(50) NOT NULL DEFAULT '',
      `CheckDate` datetime DEFAULT NULL,
      `Postdate` datetime DEFAULT NULL,
      `interest` decimal(19,2) NOT NULL DEFAULT '0.00',
      `ref` varchar(20) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acno,client)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sjdetail", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sjdetail CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_sjhead` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `DOC` varchar(2) NOT NULL DEFAULT '',
      `DocNo` varchar(20) NOT NULL DEFAULT '',
      `CLIENT` varchar(15) NOT NULL DEFAULT '',
      `clientname` varchar(255) NOT NULL DEFAULT '',
      `ADDRESS` varchar(150) NOT NULL DEFAULT '',
      `ShipTo` varchar(150) NOT NULL DEFAULT '',
      `Tel` varchar(50) NOT NULL DEFAULT '',
      `DateID` datetime DEFAULT NULL,
      `DUE` datetime DEFAULT NULL,
      `WH` varchar(15) NOT NULL DEFAULT '',
      `TERMS` varchar(30) NOT NULL DEFAULT '',
      `REM` varchar(500) NOT NULL DEFAULT '',
      `Cur` varchar(2) NOT NULL DEFAULT '',
      `forex` decimal(19,6) NOT NULL DEFAULT '0.00',
      `VoidDate` datetime DEFAULT NULL,
      `Branch` varchar(30) NOT NULL DEFAULT '',
      `Contra` varchar(30) NOT NULL DEFAULT '',
      `Agent` varchar(15) NOT NULL DEFAULT '',
      `YourRef` varchar(50) NOT NULL DEFAULT '',
      `OurRef` varchar(50) NOT NULL DEFAULT '',
      `tax` decimal(19,6) NOT NULL DEFAULT '0.00',
      `Prepared` varchar(50) NOT NULL DEFAULT '',
      `Comm` varchar(10) NOT NULL DEFAULT '',
      `DelBy` varchar(250) NOT NULL DEFAULT '',
      `DateFrom` datetime DEFAULT NULL,
      `Charge` decimal(18,2) NOT NULL DEFAULT '0.00',
      `price` decimal(18,2) NOT NULL DEFAULT '0.00',
      `lessp` decimal(18,2) NOT NULL DEFAULT '0.00',
      `lessamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `netprice` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rf` decimal(18,2) NOT NULL DEFAULT '0.00',
      `dp` decimal(18,2) NOT NULL DEFAULT '0.00',
      `bal` decimal(18,2) NOT NULL DEFAULT '0.00',
      `terms1` decimal(18,2) NOT NULL DEFAULT '0.00',
      `terms2` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rfamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `dpamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `pf` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rebate` decimal(18,0) NOT NULL DEFAULT '0',
      `plus` decimal(18,2) NOT NULL DEFAULT '0.00',
      `interest` decimal(18,2) NOT NULL DEFAULT '0.00',
      `lessa` decimal(18,2) NOT NULL DEFAULT '0.00',
      `amt` decimal(19,6) NOT NULL DEFAULT '0.00',
      `approved` varchar(255) NOT NULL DEFAULT '',
      `sy` varchar(45) NOT NULL DEFAULT '',
      `extramural` varchar(45) NOT NULL DEFAULT '',
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `majorcode` varchar(45) NOT NULL DEFAULT '',
      `majorname` varchar(450) NOT NULL DEFAULT '',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `curriculumname` varchar(450) NOT NULL DEFAULT '',
      `tuitionfee` decimal(18,2) NOT NULL DEFAULT '0.00',
      `tuitionperunit` decimal(18,2) NOT NULL DEFAULT '0.00',
      `tuitorial` decimal(18,2) NOT NULL DEFAULT '0.00',
      `misc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `registration` decimal(18,2) NOT NULL DEFAULT '0.00',
      `others` decimal(18,2) NOT NULL DEFAULT '0.00',
      `modularfee` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totalunit` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totalbalance` decimal(18,2) NOT NULL DEFAULT '0.00',
      `credentials` decimal(18,2) NOT NULL DEFAULT '0.00',
      `additionaldisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `section` varchar(45) NOT NULL DEFAULT '',
      `modeofpayment` varchar(45) NOT NULL DEFAULT '',
      `total_balance` decimal(18,2) NOT NULL DEFAULT '0.00',
      `sotrno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `reservationfee` decimal(18,2) NOT NULL DEFAULT '0.00',
      `recdate` datetime DEFAULT NULL,
      `period` varchar(45) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      `level` varchar(45) NOT NULL DEFAULT '',
      `yr` varchar(45) NOT NULL DEFAULT '',
      `totallec` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totallab` decimal(18,2) NOT NULL DEFAULT '0.00',
      `reqby` varchar(50) NOT NULL DEFAULT '',
      `reqdept` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      INDEX name (client,docno,coursecode,curriculumcode,curriculumdocno)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sjhead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sjhead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sjhead'], ['periodid', 'levelid', 'adviserid', 'courseid', 'syid', 'semid', 'deptid', 'sectionid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_sjhead", "section", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_sjhead", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_sjhead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_sjotherfees` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `acno` varchar(45) NOT NULL DEFAULT '',
      `acnoname` varchar(450) NOT NULL DEFAULT '',
      `db` decimal(18,2) NOT NULL DEFAULT '0.00',
      `cr` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rem` varchar(450) NOT NULL DEFAULT '',
      `ref` varchar(45) NOT NULL DEFAULT '',
      `refx` bigint(20) unsigned NOT NULL DEFAULT '0',
      `linex` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acno)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sjotherfees", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sjotherfees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");


    $qry = "CREATE TABLE `en_sjsubject` (
      `TrNo` int(10) unsigned NOT NULL DEFAULT '0',
      `LINE` int(11) unsigned NOT NULL DEFAULT '0',
      `LINEX` int(11) unsigned NOT NULL DEFAULT '0',
      `REFX` int(10) unsigned NOT NULL DEFAULT '0',
      `subjectcode` varchar(30) NOT NULL DEFAULT '',
      `subjectname` varchar(500) NOT NULL DEFAULT '',
      `UOM` varchar(15) NOT NULL DEFAULT '',
      `WH` varchar(15) NOT NULL DEFAULT '',
      `DISC` varchar(40) NOT NULL DEFAULT '',
      `Rem` varchar(250) NOT NULL DEFAULT '',
      `ISAmt` decimal(19,6) NOT NULL DEFAULT '0.00',
      `AMT` decimal(19,6) NOT NULL DEFAULT '0.00',
      `ISQTY` decimal(19,6) NOT NULL DEFAULT '0.00',
      `ISS` decimal(19,10) NOT NULL DEFAULT '0.00',
      `Ext` decimal(19,6) NOT NULL DEFAULT '0.00',
      `Ref` varchar(20) NOT NULL DEFAULT '',
      `Cost` decimal(19,6) NOT NULL DEFAULT '0.00',
      `Void` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `EncodedDate` datetime DEFAULT NULL,
      `isChange` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `roomstatusid` int(10) unsigned NOT NULL DEFAULT '0',
      `origdocno` varchar(45) NOT NULL DEFAULT '',
      `origsubjectcode` varchar(45) NOT NULL DEFAULT '',
      `origtrno` int(11) unsigned NOT NULL DEFAULT '0',
      `origline` int(11) unsigned NOT NULL DEFAULT '0',
      `screfx` bigint(20) unsigned NOT NULL DEFAULT '0',
      `sclinex` int(11) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (subjectcode,refx,linex,origdocno,screfx,sclinex,origsubjectcode,origtrno,origline)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sjsubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sjsubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_sjsubject'], ['ctrno', 'cline', 'scline', 'semid', 'instructorid', 'roomid', 'bldgid', 'origsubjectid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sjhead', 'en_glhead'], ['bldgid', 'roomid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sjhead', 'en_glhead'], ['assessref'], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_adsubject', 'en_sjsubject'], ['subjectid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sjsubject'], ['units', 'laboratory', 'lecture', 'hours'], "decimal(18,2) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_sjsubject", "schedday", "varchar(100)  NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sjsubject'], ['schedstarttime', 'schedendtime'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_sjsubject", "schedref", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_glsubject", "origsubjectid", "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['en_sjotherfees'], ['feestype', 'scheme'], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sjotherfees'], ['feesid', 'acnoid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_sjotherfees", "isamt", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_sjsummary` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `ref` varchar(45) NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45) NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `linex` int(10) unsigned NOT NULL DEFAULT '0',
      `refx` int(10) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (feescode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sjsummary", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sjsummary CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sjsummary'], ['feesid', 'schemeid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_adhead` (
      `TrNo` int(10) unsigned NOT NULL DEFAULT '0',
      `DOC` varchar(2) NOT NULL DEFAULT '',
      `DocNo` varchar(20) NOT NULL DEFAULT '',
      `CLIENT` varchar(15) NOT NULL DEFAULT '',
      `ClientName` varchar(150) NOT NULL DEFAULT '',
      `ADDRESS` varchar(150) NOT NULL DEFAULT '',
      `ShipTo` varchar(150) NOT NULL DEFAULT '',
      `Tel` varchar(50) NOT NULL DEFAULT '',
      `DateID` datetime DEFAULT NULL,
      `DUE` datetime DEFAULT NULL,
      `WH` varchar(15) NOT NULL DEFAULT '',
      `TERMS` varchar(30) NOT NULL DEFAULT '',
      `REM` varchar(500) NOT NULL DEFAULT '',
      `Cur` varchar(2) NOT NULL DEFAULT '',
      `Forex` decimal(18,2) NOT NULL DEFAULT '0.00',
      `VoidDate` datetime DEFAULT NULL,
      `Branch` varchar(30) NOT NULL DEFAULT '',
      `Agent` varchar(50) NOT NULL DEFAULT '',
      `YourRef` varchar(25) NOT NULL DEFAULT '',
      `OurRef` varchar(25) NOT NULL DEFAULT '',
      `ApprovedBy` varchar(50) NOT NULL DEFAULT '',
      `ApprovedDate` datetime DEFAULT NULL,
      `RSNo` bigint(20) NOT NULL DEFAULT '0',
      `PrintTime` datetime DEFAULT NULL,
      `SettleTime` datetime DEFAULT NULL,
      `project` varchar(45) NOT NULL DEFAULT '',
      `subproject` varchar(45) NOT NULL DEFAULT '',
      `blklot` varchar(45) NOT NULL DEFAULT '',
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `totalunit` decimal(18,2) NOT NULL DEFAULT '0.00',
      `tuitionfee` decimal(18,2) NOT NULL DEFAULT '0.00',
      `tuitionperunit` decimal(18,2) NOT NULL DEFAULT '0.00',
      `tuitorial` decimal(18,2) NOT NULL DEFAULT '0.00',
      `registration` decimal(18,2) NOT NULL DEFAULT '0.00',
      `misc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `sy` varchar(45) NOT NULL DEFAULT '',
      `others` decimal(18,2) NOT NULL DEFAULT '0.00',
      `type` varchar(45) NOT NULL DEFAULT '',
      `extramural` varchar(45) NOT NULL DEFAULT '',
      `majorcode` varchar(45) NOT NULL DEFAULT '',
      `majorname` varchar(60) NOT NULL DEFAULT '',
      `modularfee` decimal(18,2) NOT NULL DEFAULT '0.00',
      `inventory` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totalbalance` decimal(18,2) NOT NULL DEFAULT '0.00',
      `isenrolled` tinyint(4) NOT NULL DEFAULT '0',
      `curriculumname` varchar(450) NOT NULL DEFAULT '',
      `credentials` decimal(18,2) NOT NULL DEFAULT '0.00',
      `additionaldisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `section` varchar(45) NOT NULL DEFAULT '',
      `sotrno` bigint(20) NOT NULL DEFAULT '0',
      `reservationfee` decimal(18,2) NOT NULL DEFAULT '0.00',
      `period` varchar(45) NOT NULL DEFAULT '',
      `schedcode` varchar(45) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      `level` varchar(45) NOT NULL DEFAULT '',
      `deptcode` varchar(45) NOT NULL DEFAULT '',
      `sex` varchar(45) NOT NULL DEFAULT '',
      `yr` varchar(45) NOT NULL DEFAULT '',
      `modeofpayment` varchar(45) NOT NULL DEFAULT '',
      `contra` varchar(45) NOT NULL DEFAULT '',
      `droppercent` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totalunitadd` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`),
      INDEX name (docno,client,coursecode,curriculumcode,majorcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_adhead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_adhead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_adhead'], ['periodid', 'levelid', 'courseid', 'syid', 'semid', 'deptid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_adhead'], ['lockdate', 'editdate', 'createdate', 'viewdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_adhead'], ['editby', 'createby', 'viewby'], "varchar(80) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_adhead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");


    $qry = "CREATE TABLE `en_adsubject` (
      `TrNo` bigint(20) unsigned NOT NULL DEFAULT '0',
      `LINE` int(11) unsigned NOT NULL DEFAULT '0',
      `subjectcode` varchar(30) NOT NULL DEFAULT '',
      `subjectname` varchar(500) NOT NULL DEFAULT '',
      `schedday` varchar(50) NOT NULL DEFAULT '',
      `schedtime` varchar(50) NOT NULL DEFAULT '',
      `rooms` varchar(40) NOT NULL DEFAULT '',
      `instructorcode` varchar(40) NOT NULL DEFAULT '',
      `units` decimal(19,6) NOT NULL DEFAULT '0.00',
      `lecture` decimal(19,6) NOT NULL DEFAULT '0.00',
      `laboratory` decimal(19,6) NOT NULL DEFAULT '0.00',
      `schedref` varchar(45) NOT NULL DEFAULT '',
      `refx` bigint(20) NOT NULL DEFAULT '0',
      `linex` int(11) NOT NULL DEFAULT '0',
      `instructorname` varchar(450) NOT NULL DEFAULT '',
      `hours` decimal(18,2) NOT NULL DEFAULT '0.00',
      `origtrno` int(11) NOT NULL DEFAULT '0',
      `origline` int(11) NOT NULL DEFAULT '0',
      `origdocno` varchar(45) NOT NULL DEFAULT '',
      `origsubjectcode` varchar(45) NOT NULL DEFAULT '',
      `isadd` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `isdrop` tinyint(5) unsigned NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (subjectcode,rooms,instructorcode,origtrno,origline,origdocno,origsubjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_adsubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_adsubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_adsubject'], ['instructorid', 'roomid', 'bldgid', 'courseid', 'semid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_adsubject'], ['schedstarttime', 'schedendtime'], "datetime", 0);

    $qry = "CREATE TABLE `en_addetail` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `LINE` int(11) unsigned NOT NULL DEFAULT '0',
      `ACNO` varchar(15) NOT NULL DEFAULT '',
      `client` varchar(20) NOT NULL DEFAULT '',
      `acnoname` varchar(250) NOT NULL DEFAULT '',
      `db` decimal(20,6) NOT NULL DEFAULT '0.00',
      `cr` decimal(20,6) NOT NULL DEFAULT '0.00',
      `fdb` decimal(20,6) NOT NULL DEFAULT '0.00',
      `fcr` decimal(20,6) NOT NULL DEFAULT '0.00',
      `rem` varchar(255) NOT NULL DEFAULT '',
      `Agent` varchar(50) NOT NULL DEFAULT '',
      `CheckNo` varchar(50) NOT NULL DEFAULT '',
      `CheckDate` datetime DEFAULT NULL,
      `Postdate` datetime DEFAULT NULL,
      `interest` decimal(19,2) NOT NULL DEFAULT '0.00',
      `ref` varchar(20) NOT NULL DEFAULT '',
      `project` varchar(45) NOT NULL DEFAULT '',
      `subproject` varchar(45) NOT NULL DEFAULT '',
      `blklot` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acno,client)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_addetail", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_addetail CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_addetail'], ['editdate', 'encodeddate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_addetail'], ['editby', 'encodedby', 'cur', 'ewtcode'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_addetail'], ['acnoid', 'forex', 'refx', 'linex', 'pdcline'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_addetail'], ['isewt', 'isvat'], "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_addetail", "ewtrate", "decimal(18,2) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_adotherfees` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `acno` varchar(45) NOT NULL DEFAULT '',
      `acnoname` varchar(450) NOT NULL DEFAULT '',
      `isamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rem` varchar(450) NOT NULL DEFAULT '',
      `feescode` varchar(50) NOT NULL DEFAULT '',
      `feestype` varchar(50) NOT NULL DEFAULT '',
      `scheme` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acno)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_adotherfees", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_adotherfees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_adotherfees'], ['feesid', 'acnoid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_adcredentials` (
      `trno` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `line` int(11) NOT NULL DEFAULT '0',
      `credentials` varchar(45) NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `particulars` varchar(450) NOT NULL DEFAULT '',
      `percentdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `acno` varchar(45) NOT NULL DEFAULT '',
      `acnoname` varchar(45) NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(45) NOT NULL DEFAULT '',
      `credentialcode` varchar(45) NOT NULL DEFAULT '',
      `camt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acno,feescode,subjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_adcredentials", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_adcredentials CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_adcredentials'], ['credentialid', 'acnoid', 'feesid', 'subjectid', 'schemeid'], "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_adsummary` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `ref` varchar(45)  NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45)  NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (feescode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_adsummary", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_adsummary CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(["en_adsummary"], ["feesid", "schemeid"], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_schead` (
      `TrNo` int(10) unsigned  NOT NULL DEFAULT '0',
      `DOC` varchar(2) NOT NULL DEFAULT '',
      `DocNo` varchar(20) NOT NULL DEFAULT '',
      `DateID` datetime DEFAULT NULL,
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `curriculumname` varchar(450) NOT NULL DEFAULT '',
      `sy` varchar(45) NOT NULL DEFAULT '',
      `section` varchar(45) NOT NULL DEFAULT '',
      `terms` varchar(45) NOT NULL DEFAULT '',
      `period` varchar(45) NOT NULL DEFAULT '',
      `yr` decimal(18,0) NOT NULL DEFAULT '0.00',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `advisercode` varchar(45) NOT NULL DEFAULT '',
      `advisername` varchar(450) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      INDEX name (docno,coursecode,curriculumcode,advisercode,curriculumdocno)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_schead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_schead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(["en_schead"], ["periodid", "adviserid", "courseid", "periodid", "syid", "semid", "sectionid"], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_schead", "rem", "varchar(500) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_schead", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->execqry("ALTER TABLE en_schead modify column yr varchar(45) NOT NULL DEFAULT  ''");
    $this->coreFunctions->execqry("ALTER TABLE en_schead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_scsubject` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `subjectcode` varchar(30) NOT NULL DEFAULT '',
      `subjectname` varchar(500) NOT NULL DEFAULT '',
      `units` decimal(18,2) NOT NULL DEFAULT '0.00',
      `laboratory` decimal(18,2) NOT NULL DEFAULT '0.00',
      `lecture` decimal(18,2) NOT NULL DEFAULT '0.00',
      `hours` decimal(18,2) NOT NULL DEFAULT '0.00',
      `instructorcode` varchar(45) NOT NULL DEFAULT '',
      `instructorname` varchar(45) NOT NULL DEFAULT '',
      `roomcode` varchar(45) NOT NULL DEFAULT '',
      `schedday` varchar(45) NOT NULL DEFAULT '',
      `schedtime` varchar(45) NOT NULL DEFAULT '',
      `schedstarttime` datetime,
      `schedendtime` datetime,
      `minslot` decimal(18,2) NOT NULL DEFAULT '0.00',
      `maxslot` decimal(18,2) NOT NULL DEFAULT '0.00',
      `asqa` decimal(18,2) NOT NULL DEFAULT '0.00',
      `astempqa` decimal(18,2) NOT NULL DEFAULT '0.00',
      `qa` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (subjectcode,instructorcode,roomcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_scsubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_scsubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(["en_scsubject"], ["subjectid", "instructorid", "roomid", "bldgid", "rctrno", "rcline"], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "alter table en_scsubject modify column schedstarttime  datetime";
    $this->coreFunctions->execqry($qry);
    $qry = "alter table en_scsubject modify column schedendtime  datetime";
    $this->coreFunctions->execqry($qry);
    $qry = "alter table en_glsubject modify column schedstarttime  datetime";
    $this->coreFunctions->execqry($qry);
    $qry = "alter table en_glsubject modify column schedendtime  datetime";
    $this->coreFunctions->execqry($qry);

    $qry = "CREATE TABLE `en_gehead` (
      `TrNo` int(10) unsigned NOT NULL DEFAULT '0',
      `DOC` varchar(2) NOT NULL DEFAULT '',
      `DocNo` varchar(20) NOT NULL DEFAULT '',
      `DateID` datetime DEFAULT NULL,
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `curriculumname` varchar(45) NOT NULL DEFAULT '',
      `sy` varchar(45) NOT NULL DEFAULT '',
      `section` varchar(45) NOT NULL DEFAULT '',
      `terms` varchar(45) NOT NULL DEFAULT '',
      `period` varchar(45) NOT NULL DEFAULT '',
      `yr` decimal(18,0)  NOT NULL DEFAULT '0',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `advisercode` varchar(45) NOT NULL DEFAULT '',
      `advisername` varchar(450) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      `sheddocno` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(450) NOT NULL DEFAULT '',
      `room` varchar(45) NOT NULL DEFAULT '',
      `schedday` varchar(45) NOT NULL DEFAULT '',
      `schedtime` varchar(45) NOT NULL DEFAULT '',
      `scheddocno` varchar(45) NOT NULL DEFAULT '',
      `gstrno` bigint(20)  NOT NULL DEFAULT '0',
      `gsdocno` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      INDEX name (docno,coursecode,curriculumcode,advisercode,scheddocno,curriculumdocno,subjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gehead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gehead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_gehead'], ['courseid', 'adviserid', 'syid', 'periodid', 'sectionid', 'semid', 'bldgid', 'roomid', 'subjectid', 'quarterid', 'schedtrno', 'schedline'], "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_gehead'], ['lockdate', 'viewdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_gehead'], ['lockuser', 'viewby'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_gehead", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->execqry("ALTER TABLE en_gehead modify column yr varchar(45) NOT NULL DEFAULT  ''");
    $this->coreFunctions->execqry("ALTER TABLE en_gehead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_gegrades` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `client` varchar(45) NOT NULL DEFAULT '',
      `clientname` varchar(450) NOT NULL DEFAULT '',
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcsubcode` varchar(45) NOT NULL DEFAULT '',
      `components` varchar(45) NOT NULL DEFAULT '',
      `topic` varchar(45) NOT NULL DEFAULT '',
      `noofitems` decimal(18,2) NOT NULL DEFAULT '0.00',
      `points` decimal(18,2) NOT NULL DEFAULT '0.00',
      `gstrno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `gsdocno` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (client,gccode,gstrno,gsdocno)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gegrades", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gegrades CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_gegrades'], ['refx', 'linex', 'clientid', 'ctrno', 'cline', 'scline', 'gsline'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_glgrades", "gsline", " integer NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_gesubcomponent` (
      `trno` bigint(20)  NOT NULL DEFAULT '0',
      `line` int(11)  NOT NULL DEFAULT '0',
      `compline` bigint(20)  NOT NULL DEFAULT '0',
      `linex` int(11)  NOT NULL DEFAULT '0',
      `component` varchar(45) NOT NULL DEFAULT '',
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcsubcode` varchar(45) NOT NULL DEFAULT '',
      `topic` varchar(45) NOT NULL DEFAULT '',
      `noofitems` decimal(18,2) NOT NULL DEFAULT '0.00',
      `compid` bigint(20)  NOT NULL DEFAULT '0',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (gccode,gcsubcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gesubcomponent", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gesubcomponent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_gesubcomponent'], ['quarterid'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_gesubcomponent', 'en_glsubcomponent'], ['dategiven'], "datetime DEFAULT NULL", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_gesubcomponent', 'en_glsubcomponent'], ['getrno'], "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_glgrades` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `client` varchar(45) NOT NULL DEFAULT '',
      `clientname` varchar(450) NOT NULL DEFAULT '',
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcsubcode` varchar(45) NOT NULL DEFAULT '',
      `components` varchar(45) NOT NULL DEFAULT '',
      `topic` varchar(45) NOT NULL DEFAULT '',
      `noofitems` decimal(18,2) NOT NULL DEFAULT '0.00',
      `points` decimal(18,2) NOT NULL DEFAULT '0.00',
      `gstrno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `gsdocno` varchar(45) NOT NULL DEFAULT ''
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glgrades", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glgrades CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_glgrades'], ['refx', 'linex', 'clientid', 'ctrno', 'cline', 'scline'], "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_gshead` (
      `TrNo` int(10) unsigned NOT NULL DEFAULT '0',
      `DOC` varchar(2) NOT NULL DEFAULT '',
      `DocNo` varchar(20) NOT NULL DEFAULT '',
      `DateID` datetime DEFAULT NULL,
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `curriculumname` varchar(45) NOT NULL DEFAULT '',
      `sy` varchar(45) NOT NULL DEFAULT '',
      `section` varchar(45) NOT NULL DEFAULT '',
      `terms` varchar(45) NOT NULL DEFAULT '',
      `period` varchar(45) NOT NULL DEFAULT '',
      `yr` decimal(18,0)  NOT NULL DEFAULT '0',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `advisercode` varchar(45) NOT NULL DEFAULT '',
      `advisername` varchar(450) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      `sheddocno` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(450) NOT NULL DEFAULT '',
      `room` varchar(45) NOT NULL DEFAULT '',
      `schedday` varchar(45) NOT NULL DEFAULT '',
      `schedtime` varchar(45) NOT NULL DEFAULT '',
      `scheddocno` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`),
      INDEX name (docno,coursecode,curriculumcode,advisercode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gshead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gshead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumn("en_gshead", "viewdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_gshead", "viewby", "varchar(50) DEFAULT ''", 0);

    $this->coreFunctions->execqry("ALTER TABLE en_gshead modify column yr varchar(45) NOT NULL DEFAULT  ''");
    $this->coreFunctions->sbcaddcolumngrp(['en_gshead'], ['schedtrno', 'schedline'], "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("en_gshead", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_gshead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_gscomponent` (
      `trno` bigint(20)  NOT NULL DEFAULT '0',
      `line` int(11)  NOT NULL DEFAULT '0',
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcname` varchar(450) NOT NULL DEFAULT '',
      `gcpercent` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (gccode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gscomponent", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gscomponent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_gscomponent", "compid", "int(10) unsigned NOT NULL DEFAULT '0'", 0);


    $qry = "CREATE TABLE `en_gssubcomponent` (
      `trno` bigint(20)  NOT NULL DEFAULT '0',
      `line` int(11)  NOT NULL DEFAULT '0',
      `compline` bigint(20)  NOT NULL DEFAULT '0',
      `linex` int(11)  NOT NULL DEFAULT '0',
      `component` varchar(45) NOT NULL DEFAULT '',
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcsubcode` varchar(45) NOT NULL DEFAULT '',
      `topic` varchar(45) NOT NULL DEFAULT '',
      `noofitems` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (gccode,gcsubcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gssubcomponent", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gssubcomponent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumn("en_gssubcomponent", "compid", "int(10) unsigned NOT NULL DEFAULT '0', DROP PRIMARY KEY, ADD PRIMARY KEY USING BTREE(`trno`, `line`, `compid`)", 0);

    $qry = "CREATE TABLE `en_sgshead` (
      `trno` bigint(20)  NOT NULL DEFAULT '0',
      `docno` varchar(45) NOT NULL DEFAULT '',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `curriculumname` varchar(450) NOT NULL DEFAULT '',
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `coursename` varchar(450) NOT NULL DEFAULT '',
      `level` varchar(45) NOT NULL DEFAULT '',
      `sy` varchar(45) NOT NULL DEFAULT '',
      `terms` varchar(45) NOT NULL DEFAULT '',
      `doc` varchar(45) NOT NULL DEFAULT '',
      `yr` varchar(45) NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45) NOT NULL DEFAULT '',
      `client` varchar(45) NOT NULL DEFAULT '',
      `clientname` varchar(450) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      PRIMARY KEY (`trno`),
      INDEX name (docno,curriculumcode,coursecode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sgshead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sgshead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sgshead'], ['courseid', 'levelid', 'syid', 'semid', 'yrid', 'clientid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_sgshead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");


    $qry = "CREATE TABLE `en_sgssubject` (
      `trno` bigint(20)  NOT NULL DEFAULT '0',
      `line` int(11)  NOT NULL DEFAULT '0',
      `curriculumcode` varchar(45) NOT NULL DEFAULT '',
      `yearnum` varchar(45)  NOT NULL DEFAULT '0',
      `terms` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(450) NOT NULL DEFAULT '',
      `units` decimal(18,2) NOT NULL DEFAULT '0.00',
      `coursecode` varchar(45) NOT NULL DEFAULT '',
      `pre1` varchar(45) NOT NULL DEFAULT '',
      `pre2` varchar(45) NOT NULL DEFAULT '',
      `pre3` varchar(45) NOT NULL DEFAULT '',
      `pre4` varchar(45) NOT NULL DEFAULT '',
      `pre5` varchar(45) NOT NULL DEFAULT '',
      `lecture` decimal(18,2) NOT NULL DEFAULT '0.00',
      `laboratory` decimal(18,2) NOT NULL DEFAULT '0.00',
      `coreq` varchar(45) NOT NULL DEFAULT '',
      `grade` decimal(18,2) NOT NULL DEFAULT '0.00',
      `equivalent` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (curriculumcode,coursecode,subjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_sgssubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_sgssubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_sgssubject'], ['subjectid', 'semid'], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "select  count(trno) as value  from en_athead";
    if ($this->coreFunctions->datareader($qry) == 0) {
      $this->coreFunctions->execqry("drop table if exists en_athead");
    }

    $qry = "CREATE TABLE `en_athead` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `doc` varchar(2) NOT NULL DEFAULT '',
      `docno` varchar(20) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      `periodid` int(10) unsigned NOT NULL DEFAULT '0',
      `syid` int(10) unsigned NOT NULL DEFAULT '0',   
      `createby` varchar(20) NOT NULL DEFAULT '',  
      `createdate` datetime DEFAULT NULL,  
      `editby` varchar(20) NOT NULL DEFAULT '',
      `editdate` datetime DEFAULT NULL,   
      `viewby` varchar(20) NOT NULL DEFAULT '',
      `viewdate` datetime DEFAULT NULL,  
      `lockuser` varchar(20) NOT NULL DEFAULT '',
      `lockdate` datetime DEFAULT NULL,  
      PRIMARY KEY (`trno`),
      INDEX name (trno,docno,periodid,syid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_athead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_athead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(['en_athead'], ['periodid', 'syid', 'sectionid', 'adviserid', 'subjectid', 'roomid', 'courseid', 'yr', 'schedtrno', 'schedline'], "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_athead'], ['schedday', 'schedtime', 'scheddocno', 'curriculumcode', 'curriculumdocno'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_athead", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_athead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';");

    $qry = "CREATE TABLE `en_atfees` (
        `trno` bigint(20) NOT NULL DEFAULT '0',
        `line` int(11) NOT NULL DEFAULT '0',
        `subjectid` int(11) NOT NULL DEFAULT '0',
        `feesid` int(11) NOT NULL DEFAULT '0',
        `schemeid` int(11) NOT NULL DEFAULT '0',
        `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
        `isnew` tinyint(4) NOT NULL DEFAULT '0',
        `isold` tinyint(4) NOT NULL DEFAULT '0',
        `isforeign` tinyint(4) NOT NULL DEFAULT '0',
        `isadddrop` tinyint(4) NOT NULL DEFAULT '0',
        `iscrossenrollee` tinyint(4) NOT NULL DEFAULT '0',
        `islateenrollee` tinyint(4) NOT NULL DEFAULT '0',
        `istransferee` tinyint(4) NOT NULL DEFAULT '0',
        `periodid` int(11) NOT NULL DEFAULT '0',
        `levelid` int(11) NOT NULL DEFAULT '0',
        `departid` int(11) NOT NULL DEFAULT '0',
        `courseid` int(11) NOT NULL DEFAULT '0',
        `sectionid` int(11) NOT NULL DEFAULT '0',
        `sex` varchar(45) NOT NULL DEFAULT '',
        `rooms` varchar(45) NOT NULL DEFAULT '',
        `yrid` int(11) NOT NULL DEFAULT '0',
        `semid` int(11) NOT NULL DEFAULT '0',
        `section` varchar(45) NOT NULL DEFAULT '',
          PRIMARY KEY (`trno`,`line`),
          INDEX name (trno,line,subjectid,feesid,schemeid,periodid,levelid,departid,courseid,sectionid,yrid,semid)
          ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_atfees", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_atfees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_atfees", "section", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_atfees", "yr", "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->execqry("ALTER TABLE en_atfees MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_glfees` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(11) NOT NULL DEFAULT '0',
      `subjectid` int(11) NOT NULL DEFAULT '0',
      `feesid` int(11) NOT NULL DEFAULT '0',
      `schemeid` int(11) NOT NULL DEFAULT '0',
      `rate` decimal(18,2) NOT NULL DEFAULT '0.00',
      `isnew` tinyint(4) NOT NULL DEFAULT '0',
      `isold` tinyint(4) NOT NULL DEFAULT '0',
      `isforeign` tinyint(4) NOT NULL DEFAULT '0',
      `isadddrop` tinyint(4) NOT NULL DEFAULT '0',
      `iscrossenrollee` tinyint(4) NOT NULL DEFAULT '0',
      `islateenrollee` tinyint(4) NOT NULL DEFAULT '0',
      `istransferee` tinyint(4) NOT NULL DEFAULT '0',
      `periodid` int(11) NOT NULL DEFAULT '0',
      `levelid` int(11) NOT NULL DEFAULT '0',
      `departid` int(11) NOT NULL DEFAULT '0',
      `courseid` int(11) NOT NULL DEFAULT '0',
      `sectionid` int(11) NOT NULL DEFAULT '0',
      `sex` varchar(45) NOT NULL DEFAULT '',
      `rooms` varchar(45) NOT NULL DEFAULT '',
      `yrid` int(11) NOT NULL DEFAULT '0',
      `semid` int(11) NOT NULL DEFAULT '0',
      `section` varchar(45) NOT NULL DEFAULT '',
        PRIMARY KEY (`trno`,`line`),
        INDEX name (trno,line,subjectid,feesid,schemeid,periodid,levelid,departid,courseid,sectionid,yrid,semid)
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glfees", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glfees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_glfees", "section", "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_glfees", "yr", "int(10) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_glfees MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_atstudents` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0.00',
      `line` int(10) unsigned NOT NULL DEFAULT '0.00',
      `client` varchar(45) NOT NULL DEFAULT '',
      `clientname` varchar(45) NOT NULL DEFAULT '',
      `atdate` datetime DEFAULT NULL,
      `status` varchar(500) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (client)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_atstudents", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_atstudents CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_atstudents", "clientid", "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_glstudents` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0.00',
      `line` int(10) unsigned NOT NULL DEFAULT '0.00',
      `client` varchar(45) NOT NULL DEFAULT '',
      `clientname` varchar(45) NOT NULL DEFAULT '',
      `atdate` datetime DEFAULT NULL,
      `status` varchar(500) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (client)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glstudents", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glstudents CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_glstudents", "clientid", "int(10) unsigned NOT NULL DEFAULT '0'", 0);



    //GL TABLES
    $qry = "CREATE TABLE `en_glhead` (
      `TrNo` int(10) unsigned NOT NULL DEFAULT '0',
      `DOC` varchar(2)  NOT NULL DEFAULT '',
      `DocNo` varchar(20)  NOT NULL DEFAULT '',
      `CLIENTID` varchar(15)  NOT NULL DEFAULT '',
      `ClientName` varchar(150) NOT NULL DEFAULT '',
      `ADDRESS` varchar(150) NOT NULL DEFAULT '',
      `ShipTo` varchar(150) NOT NULL DEFAULT '',
      `Tel` varchar(50) NOT NULL DEFAULT '',
      `DateID` datetime DEFAULT NULL,
      `DUE` datetime DEFAULT NULL,
      `WH` varchar(15) NOT NULL DEFAULT '',
      `TERMS` varchar(30) NOT NULL DEFAULT '',
      `REM` varchar(500) NOT NULL DEFAULT '',
      `Cur` varchar(2) NOT NULL DEFAULT '',
      `Forex` decimal(18,2) NOT NULL DEFAULT '0.00',
      `VoidDate` datetime DEFAULT NULL,
      `Branch` varchar(30) NOT NULL DEFAULT '',
      `Agent` varchar(50)  NOT NULL DEFAULT '',
      `YourRef` varchar(25)  NOT NULL DEFAULT '',
      `OurRef` varchar(25)  NOT NULL DEFAULT '',
      `ApprovedBy` varchar(50)  NOT NULL DEFAULT '',
      `ApprovedDate` datetime DEFAULT NULL,
      `PrintTime` datetime DEFAULT NULL,
      `SettleTime` datetime DEFAULT NULL,
      `project` varchar(45)  NOT NULL DEFAULT '',
      `subproject` varchar(45)  NOT NULL DEFAULT '',
      `blklot` varchar(45)  NOT NULL DEFAULT '',
      `coursecode` varchar(45)  NOT NULL DEFAULT '',
      `coursename` varchar(450)  NOT NULL DEFAULT '',
      `curriculumcode` varchar(45)  NOT NULL DEFAULT '',
      `totalunit` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `tuitionfee` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `tuitionperunit` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `tuitorial` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `registration` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `misc` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `sy` varchar(45)  NOT NULL DEFAULT '',
      `others` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `type` varchar(45)  NOT NULL DEFAULT '',
      `extramural` varchar(45)  NOT NULL DEFAULT '',
      `majorcode` varchar(45)  NOT NULL DEFAULT '',
      `majorname` varchar(450)  NOT NULL DEFAULT '',
      `modularfee` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `inventory` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `totalbalance` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `isenrolled` tinyint(4) unsigned NOT NULL DEFAULT '0',
      `curriculumname` varchar(450)  NOT NULL DEFAULT '',
      `credentials` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `additionaldisc` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `section` varchar(45)  NOT NULL DEFAULT '',
      `sotrno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `reservationfee` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `period` varchar(45)  NOT NULL DEFAULT '',
      `schedcode` varchar(45)  NOT NULL DEFAULT '',
      `curriculumdocno` varchar(45)  NOT NULL DEFAULT '',
      `level` varchar(45)  NOT NULL DEFAULT '',
      `deptcode` varchar(45)  NOT NULL DEFAULT '',
      `sex` varchar(45)  NOT NULL DEFAULT '',
      `yr` varchar(45)  NOT NULL DEFAULT '',
      `modeofpayment` varchar(45)  NOT NULL DEFAULT '',
      `contra` varchar(45)  NOT NULL DEFAULT '',
      `interestamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totalamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `totallec` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `totallab` decimal(18,2)  NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`),
      INDEX name (DocNo,clientid,wh)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glhead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glhead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(["en_glhead"], ["syid", "adviserid", "semid", "levelid", "curriculumtrno", "quarterid", "schedtrno", "schedline", "adviserid", "courseid", "majorid"], "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("en_glhead", "lockuser", "VARCHAR(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_glhead", "periodid", "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("en_glhead", "ischinese", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_glhead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_glcredentials` (
      `trno` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `line` int(11) NOT NULL DEFAULT '0',
      `credentials` varchar(45) NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `particulars` varchar(450) NOT NULL DEFAULT '',
      `percentdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
      `acnoid` varchar(45) NOT NULL DEFAULT '',
      `acnoname` varchar(45) NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45) NOT NULL DEFAULT '',
      `subjectcode` varchar(45) NOT NULL DEFAULT '',
      `subjectname` varchar(45) NOT NULL DEFAULT '',
      `credentialcode` varchar(45) NOT NULL DEFAULT '',
      `camt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acnoid,feescode,subjectcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glcredentials", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glcredentials CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");


    $qry = "CREATE TABLE `en_gldetail` (
      `trno` int(11) unsigned NOT NULL DEFAULT '0',
      `LINE` int(11) unsigned NOT NULL DEFAULT '0',
      `acnoid` varchar(15) NOT NULL DEFAULT '',
      `clientid` varchar(20) NOT NULL DEFAULT '',
      `acnoname` varchar(250) NOT NULL DEFAULT '',
      `db` decimal(20,6) NOT NULL DEFAULT '0.00',
      `cr` decimal(20,6) NOT NULL DEFAULT '0.00',
      `fdb` decimal(20,6) unsigned NOT NULL DEFAULT '0.00',
      `fcr` decimal(20,6) unsigned NOT NULL DEFAULT '0.00',
      `rem` varchar(255) NOT NULL DEFAULT '',
      `Agent` varchar(50) NOT NULL DEFAULT '',
      `CheckNo` varchar(50) NOT NULL DEFAULT '',
      `CheckDate` datetime DEFAULT NULL,
      `Postdate` datetime DEFAULT NULL,
      `interest` decimal(19,2) NOT NULL DEFAULT '0.00',
      `ref` varchar(20) NOT NULL DEFAULT '',
      `project` varchar(45) NOT NULL DEFAULT '',
      `subproject` varchar(45) NOT NULL DEFAULT '',
      `blklot` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (acnoid,clientid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_gldetail", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gldetail CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");


    $qry = "CREATE TABLE `en_glotherfees` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `barcode` varchar(45) NOT NULL DEFAULT '',
      `itemname` varchar(450) NOT NULL DEFAULT '',
      `isamt` decimal(18,2) NOT NULL DEFAULT '0.00',
      `rem` varchar(450) NOT NULL DEFAULT '',
      `feescode` varchar(50) NOT NULL DEFAULT '',
      `feestype` varchar(50) NOT NULL DEFAULT '',
      `scheme` varchar(50) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (barcode,feescode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glotherfees", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glotherfees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_glsubject` (
      `TrNo` int(10) unsigned NOT NULL DEFAULT '0',
      `LINE` int(11) unsigned NOT NULL DEFAULT '0',
      `LINEX` int(11)  NOT NULL DEFAULT '0',
      `REFX` int(10) unsigned  NOT NULL DEFAULT '0',
      `Rem` varchar(250)  NOT NULL DEFAULT '',
      `ISAmt` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `AMT` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `ISQTY` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `ISS` decimal(19,10)  NOT NULL DEFAULT '0.00',
      `Ext` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `Ref` varchar(20)  NOT NULL DEFAULT '',
      `Cost` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `Void` tinyint(4)  NOT NULL DEFAULT '0',
      `Commission` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `Comm` varchar(50)  NOT NULL DEFAULT '',
      `btn` varchar(10)  NOT NULL DEFAULT '',
      `SR` varchar(50)  NOT NULL DEFAULT '',
      `Serial` varchar(50)  NOT NULL DEFAULT '',
      `EncodedDate` datetime,
      `DRA` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `isChange` tinyint(4)  NOT NULL DEFAULT '0',
      `SubCode` varchar(50)  NOT NULL DEFAULT '',
      `Loc` varchar(45)  NOT NULL DEFAULT '',
      `DRRefx` int(10) unsigned  NOT NULL DEFAULT '0',
      `DRLinex` int(10) unsigned  NOT NULL DEFAULT '0',
      `project` varchar(45)  NOT NULL DEFAULT '',
      `subproject` varchar(45)  NOT NULL DEFAULT '',
      `blklot` varchar(45)  NOT NULL DEFAULT '',
      `roomstatusid` int(10) unsigned  NOT NULL DEFAULT '0',
      `subjectcode` varchar(45)  NOT NULL DEFAULT '',
      `subjectname` varchar(2000)  NOT NULL DEFAULT '',
      `units` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `curriculumcode` varchar(45)  NOT NULL DEFAULT '',
      `coursecode` varchar(45)  NOT NULL DEFAULT '',
      `terms` varchar(45)  NOT NULL DEFAULT '',
      `yearnum` varchar(45)  NOT NULL DEFAULT '',
      `pre1` varchar(45)  NOT NULL DEFAULT '',
      `pre2` varchar(45)  NOT NULL DEFAULT '',
      `pre3` varchar(45)  NOT NULL DEFAULT '',
      `pre4` varchar(45)  NOT NULL DEFAULT '',
      `laboratory` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `lecture` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `pre5` varchar(45)  NOT NULL DEFAULT '',
      `instructorcode` varchar(45)  NOT NULL DEFAULT '',
      `instructorname` varchar(450)  NOT NULL DEFAULT '',
      `roomcode` varchar(45)  NOT NULL DEFAULT '',
      `schedday` varchar(45)  NOT NULL DEFAULT '',
      `schedtime` varchar(45)  NOT NULL DEFAULT '',
      `schedstarttime` varchar(45)  datetime,
      `schedendtime` varchar(45) datetime,
      `minslot` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `maxslot` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `hours` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `asqa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `astempqa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `qa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `origdocno` varchar(45)  NOT NULL DEFAULT '',
      `origsubjectcode` varchar(45)  NOT NULL DEFAULT '',
      `origtrno` int(11)  NOT NULL DEFAULT '0',
      `origline` int(11)  NOT NULL DEFAULT '0',
      `rooms` varchar(45)  NOT NULL DEFAULT '',
      `schedref` varchar(45)  NOT NULL DEFAULT '',
      `coreq` varchar(45)  NOT NULL DEFAULT '',
      `scheddocno` varchar(45)  NOT NULL DEFAULT '',
      `screfx` bigint(20)  NOT NULL DEFAULT '0',
      `sclinex` int(11)  NOT NULL DEFAULT '0',
      `isadd` tinyint(4)  NOT NULL DEFAULT '0',
      `isdrop` tinyint(4)  NOT NULL DEFAULT '0',
      `adqa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `grade` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `equivalent` decimal(18,2)  NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (subjectcode,instructorcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glsubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glsubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(["en_glsubject"], ["subjectid", "instructorid", "roomid", "bldgid", "courseid", "semid", "pre1id", "pre2id", "pre3id", "pre4id", "pre5id", "coreqid", "ctrno", "cline", "scline", "rctrno", "rcline"], "INT(11) NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumn("en_glsubject", "cline", "INT(11) NOT NULL DEFAULT '0', DROP PRIMARY KEY, ADD PRIMARY KEY USING BTREE(`trno`, `line`, `cline`)", 1);

    $qry = "CREATE TABLE `en_glsummary` (
      `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
      `line` int(10) unsigned NOT NULL DEFAULT '0',
      `ref` varchar(45) NOT NULL DEFAULT '',
      `feescode` varchar(45) NOT NULL DEFAULT '',
      `feestype` varchar(45) NOT NULL DEFAULT '',
      `scheme` varchar(45) NOT NULL DEFAULT '',
      `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (feescode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_glsummary", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glsummary CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_studentcredentials` (
      `line` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      `credentialid` int(10)  NOT NULL DEFAULT '0',
      `amt` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `clientid` int(10) unsigned NOT NULL DEFAULT '0',
      `percentdisc` decimal(18,2)   NOT NULL DEFAULT '0.00',
      `ref` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (line)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("en_studentcredentials", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_studentcredentials CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "alter table en_instructor modify column line  int(10) NOT NULL AUTO_INCREMENT'";
    $this->coreFunctions->sbccreatetable("en_instructor", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['en_glhead'], ['effectfromdate', 'effecttodate', 'lockdate', 'editdate', 'createdate', 'viewdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glhead'], ['editby', 'createby', 'viewby'], "varchar(80) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_schead'], ['editdate', 'createdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_schead'], ['editby', 'createby'], "varchar(80) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["en_glcredentials"], ["subjectid", "credentialid"], " integer  NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(["en_glotherfees"], ["feesid", "acnoid"], " integer  NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glotherfees', 'en_sootherfees'], ['acnoname'], "varchar(600) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumn("en_sootherfees", "acno", " integer  NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["en_glsubject"], ["subjectid", "courseid", "instructorid"], " integer  NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->sbcaddcolumngrp(['en_glsubject'], ['EncodedDate', 'editdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glsubject'], ['EncodedBy', 'editby'], "varchar(80) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(['en_scsubject'], ['encodeddate', 'editdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_scsubject'], ['encodedBy', 'editby'], "varchar(80) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_schead'], ['lockDate', 'viewdate', 'encodeddate', 'editdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_schead'], ['viewby', 'encodedBy'], "varchar(80) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcdropcolumngrp(['en_glhead'], ['advisername', 'advisercode', 'coursecode', 'coursename', 'majorcode', 'majorname']);
    $this->coreFunctions->sbcdropcolumngrp(['en_glcredentials'], ['subjectcode', 'subjectname', 'subjectname']);
    $this->coreFunctions->sbcdropcolumngrp(['en_glotherfees'], ['feescode', 'barcode', 'itemname']);

    $this->coreFunctions->sbcdropcolumngrp(['en_sootherfees'], ['barcode', 'itemname']);

    $this->coreFunctions->sbcdropcolumngrp(['en_glsubject'], ['subjectcode', 'subjectname', 'coursecode', 'coursename', 'intructorcode', 'intructorname']);

    $this->coreFunctions->sbcaddcolumn("en_instructor", "instructorid", " integer  NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("client", "isinstructor", " tinyint(1)  NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("client", "createby", " varchar(80)  NOT NULL DEFAULT ''",  0);

    $this->coreFunctions->sbcaddcolumngrp(['en_sohead', 'en_adhead', 'en_sjhead'], ['createdate', 'encodeddate', 'editdate', 'lockdate', 'viewdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sohead', 'en_adhead', 'en_sjhead'], ['createby', 'encodedBy', 'editby', 'viewby'], "varchar(80) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumn("en_glhead", "encodeddate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_glhead", "encodedBy", " varchar(80)  NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(['en_sgshead', 'en_gshead', 'en_glhead'], ['lockdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sgshead', 'en_gshead', 'en_glhead'], ['lockuser'], "varchar(45) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(['en_gshead'], ['courseid', 'adviserid', 'syid', 'periodid', 'sectionid', 'semid', 'bldgid', 'roomid', 'subjectid'], "int(11) NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glhead'], ['scheddocno', 'schedday', 'schedtime'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_glhead'], ['subjectid', 'sectionid'], "int(11) NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE en_bldg (
      line int(11) unsigned NOT NULL AUTO_INCREMENT,
      bldgcode varchar(45)  NOT NULL DEFAULT '',
      bldgname varchar(450)  NOT NULL DEFAULT '',
      PRIMARY KEY (line),
      INDEX name (bldgcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_bldg", $qry);
    $this->coreFunctions->sbcaddcolumn("en_bldg", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_bldg", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_bldg CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_glcomponent` (
      `trno` bigint(20)  NOT NULL DEFAULT '0',
      `line` int(11)  NOT NULL DEFAULT '0',
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcname` varchar(450) NOT NULL DEFAULT '',
      `gcpercent` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (gccode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_glcomponent", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glcomponent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_glcomponent", "compid", "int(10) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_glsubcomponent` (
      `trno` bigint(20)  NOT NULL DEFAULT '0',
      `line` int(11)  NOT NULL DEFAULT '0',
      `compline` bigint(20)  NOT NULL DEFAULT '0',
      `linex` int(11)  NOT NULL DEFAULT '0',
      `component` varchar(45) NOT NULL DEFAULT '',
      `gccode` varchar(45) NOT NULL DEFAULT '',
      `gcsubcode` varchar(45) NOT NULL DEFAULT '',
      `topic` varchar(45) NOT NULL DEFAULT '',
      `noofitems` decimal(18,2) NOT NULL DEFAULT '0.00',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (gccode,gcsubcode)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_glsubcomponent", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glsubcomponent CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumn("en_glsubcomponent", "compid", "int(10) unsigned NOT NULL DEFAULT '0', DROP PRIMARY KEY, ADD PRIMARY KEY USING BTREE(`trno`, `line`, `compid`)", 0);

    //cntnum gltables
    $qry = "CREATE TABLE `glsubject` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `line` int(11) unsigned NOT NULL DEFAULT '0',
      `linex` int(11)  NOT NULL DEFAULT '0',
      `refx` int(10) unsigned  NOT NULL DEFAULT '0',
      `rem` varchar(250)  NOT NULL DEFAULT '',
      `isamt` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `amt` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `isqty` decimal(19,6)  NOT NULL DEFAULT '0.00',
      `iss` decimal(19,10)  NOT NULL DEFAULT '0.00',
      `ref` varchar(20)  NOT NULL DEFAULT '',
      `void` tinyint(4)  NOT NULL DEFAULT '0',
      `ischange` tinyint(4)  NOT NULL DEFAULT '0',
      `subjectid` int unsigned  NOT NULL DEFAULT 0,
      `units` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `curriculumcode` varchar(45)  NOT NULL DEFAULT '',
      `courseid` int unsigned  NOT NULL DEFAULT 0,
      `semid` int unsigned  NOT NULL DEFAULT 0,
      `yearnum` varchar(45)  NOT NULL DEFAULT '',
      `laboratory` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `lecture` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `instructorid` int unsigned  NOT NULL DEFAULT 0,
      `roomid` int unsigned  NOT NULL DEFAULT 0,
      `bldgid` int unsigned  NOT NULL DEFAULT 0,
      `schedday` varchar(45)  NOT NULL DEFAULT '',
      `schedtime` varchar(45)  NOT NULL DEFAULT '',
      `schedstarttime` datetime,
      `schedendtime` datetime,
      `minslot` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `maxslot` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `hours` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `asqa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `astempqa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `qa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `origdocno` varchar(45)  NOT NULL DEFAULT '',
      `origsubjectid` int(11) unsigned  NOT NULL DEFAULT 0,
      `origtrno` int(11)  NOT NULL DEFAULT '0',
      `origline` int(11)  NOT NULL DEFAULT '0',
      `rooms` varchar(45)  NOT NULL DEFAULT '',
      `schedref` varchar(45)  NOT NULL DEFAULT '',
      `scheddocno` varchar(45)  NOT NULL DEFAULT '',
      `screfx` bigint(20)  NOT NULL DEFAULT '0',
      `sclinex` int(11)  NOT NULL DEFAULT '0',
      `isadd` tinyint(4)  NOT NULL DEFAULT '0',
      `isdrop` tinyint(4)  NOT NULL DEFAULT '0',
      `adqa` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `grade` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `equivalent` decimal(18,2)  NOT NULL DEFAULT '0.00',
      `cline` int(11) unsigned NOT NULL DEFAULT '0',
      `ctrno` int(11) unsigned NOT NULL DEFAULT '0',
      `scline` int(11) unsigned NOT NULL DEFAULT '0',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(45)  NOT NULL DEFAULT '',
      `EditDate` datetime DEFAULT NULL,
      `editby` varchar(45)  NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      INDEX name (subjectid,instructorid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("glsubject", $qry);
    $this->coreFunctions->execqry("ALTER TABLE glsubject CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `glsummary` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(10) unsigned NOT NULL DEFAULT '0',
  `ref` varchar(45) NOT NULL DEFAULT '',
  `feesid`  int(11) unsigned NOT NULL DEFAULT '0',
  `feestype` varchar(45) NOT NULL DEFAULT '',
  `schemeid`  int(11) unsigned NOT NULL DEFAULT '0',
  `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`trno`,`line`),
  INDEX name (feesid,schemeid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("glsummary", $qry);
    $this->coreFunctions->execqry("ALTER TABLE glsummary CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `glcredentials` (
  `trno` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `line` int(11) NOT NULL DEFAULT '0',
  `credentials` varchar(45) NOT NULL DEFAULT '',
  `amt` decimal(18,2) NOT NULL DEFAULT '0.00',
  `particulars` varchar(450) NOT NULL DEFAULT '',
  `percentdisc` decimal(18,2) NOT NULL DEFAULT '0.00',
  `acnoid`  int(11) NOT NULL DEFAULT '0',
  `acnoname` varchar(45) NOT NULL DEFAULT '',
  `feesid`  int(11) NOT NULL DEFAULT '0',
  `scheme` varchar(45) NOT NULL DEFAULT '',
  `subjectid`  int(11) NOT NULL DEFAULT '0',
  `credentialid`  int(11) NOT NULL DEFAULT '0',
  `camt` decimal(18,2) NOT NULL DEFAULT '0.00',
  `feestype` varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (`trno`,`line`),
  INDEX name (acnoid,feesid,subjectid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("glcredentials", $qry);
    $this->coreFunctions->execqry("ALTER TABLE glcredentials CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `glotherfees` (
  `trno` bigint(20) unsigned NOT NULL DEFAULT '0',
  `line` int(11) unsigned NOT NULL DEFAULT '0',
  `acnoid`  int(11) NOT NULL DEFAULT '0',
  `itemname` varchar(450) NOT NULL DEFAULT '',
  `isamt` decimal(18,2) NOT NULL DEFAULT '0.00',
  `rem` varchar(450) NOT NULL DEFAULT '',
  `feesid`  int(11) NOT NULL DEFAULT '0',
  `feestype` varchar(50) NOT NULL DEFAULT '',
  `scheme`  varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`trno`,`line`),
  INDEX name (acnoid,feesid)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;";

    $this->coreFunctions->sbccreatetable("glotherfees", $qry);
    $this->coreFunctions->execqry("ALTER TABLE glotherfees CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_cardremarks` (
    `line` int(10)  unsigned NOT NULL AUTO_INCREMENT,
    `remarks` varchar(600) NOT NULL DEFAULT '',
    `ischinese` tinyint(4) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`line`)
    ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $this->coreFunctions->sbccreatetable("en_cardremarks", $qry);
    $this->coreFunctions->sbcaddcolumn("en_cardremarks", "editdate", "datetime", 0);
    $this->coreFunctions->sbcaddcolumn("en_cardremarks", "editby", "VARCHAR(100) NOT NULL DEFAULT ''", 0);

    $qry = "CREATE TABLE `en_rchead` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `docno` varchar(45) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `courseid` int(11) NOT NULL DEFAULT '0',
    `levelid` int(11) NOT NULL DEFAULT '0',
    `ischinese` tinyint(4) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`),
    INDEX name (docno,courseid,levelid)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_rchead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_rchead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_rchead'], ['createby', 'editby', 'viewby', 'lockuser'], "varchar(45) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_rchead'], ['editdate', 'createdate', 'viewdate', 'lockdate'], "datetime DEFAULT NULL", 0);

    $qry = "CREATE TABLE `en_rcdetail` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL DEFAULT '0',
    `code` varchar(45) NOT NULL DEFAULT '',
    `title` varchar(450) NOT NULL DEFAULT '',
    `yr` varchar(45) NOT NULL DEFAULT '',
    `sectionid` int(11) unsigned NOT NULL DEFAULT '0',
    `times` decimal(18,2)  NOT NULL DEFAULT '0.00',
    `order` int(11) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`, `line`),
    INDEX name (code)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("en_rcdetail", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_rcdetail CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->execqry("ALTER TABLE en_rcdetail MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_gecomponentgrade` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` int(11) unsigned NOT NULL DEFAULT '0',
    `componentcode` int(11) unsigned NOT NULL DEFAULT '0',
    `scoregrade` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `totalgrade` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `percentgrade` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `quarterid` int(11) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`, `line`),
    INDEX name (line)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("en_gecomponentgrade", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gecomponentgrade CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_gecomponentgrade'], ['schedtrno', 'schedline', 'subjectid', 'getrno'], "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_gecomponentgrade", "gcpercent", "decimal(18,2) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->execqry("ALTER TABLE en_gecomponentgrade modify column componentcode varchar(45) NOT NULL DEFAULT  ''");
    $this->coreFunctions->sbcaddcolumn("en_gecomponentgrade", "isconduct", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_gequartergrade` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` int(11) unsigned NOT NULL DEFAULT '0',
    `scoregrade` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `totalgrade` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `percentgrade` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `quarterid` int(11) unsigned NOT NULL DEFAULT '0',
    `tentativetotal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `finaltotal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    `rcardtotal` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`trno`, `line`),
    INDEX name (line)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("en_gequartergrade", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_gequartergrade CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->sbcaddcolumngrp(['en_gequartergrade'], ['schedtrno', 'schedline', 'subjectid'], "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumn("en_gequartergrade", "isconduct", "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_srchead` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `doc` varchar(45) NOT NULL DEFAULT '',
    `docno` varchar(45) NOT NULL DEFAULT '',
    `dateid` datetime DEFAULT NULL,
    `periodid` int(11) unsigned NOT NULL DEFAULT '0',
    `syid` int(11) unsigned NOT NULL DEFAULT '0',
    `levelid` int(11) unsigned NOT NULL DEFAULT '0',
    `courseid` int(11) unsigned NOT NULL DEFAULT '0',
    `yr` int(11) unsigned NOT NULL DEFAULT '0',
    `adviserid` int(11) unsigned NOT NULL DEFAULT '0',
    `sectionid` int(11) unsigned NOT NULL DEFAULT '0',
    `rem` varchar(500) NOT NULL DEFAULT '',
    `schedtrno` int(11) unsigned NOT NULL DEFAULT '0',
    `createby` varchar(45) NOT NULL DEFAULT '',
    `createdate` datetime DEFAULT NULL,
    `editby` varchar(45) NOT NULL DEFAULT '',
    `editdate` datetime DEFAULT NULL,
    `viewby` varchar(45) NOT NULL DEFAULT '',
    `viewdate` datetime DEFAULT NULL,
    `lockuser` varchar(45) NOT NULL DEFAULT '',
    `lockdate` datetime DEFAULT NULL,
    PRIMARY KEY (`trno`),
    INDEX name (docno)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("en_srchead", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_srchead CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");
    $this->coreFunctions->execqry("ALTER TABLE en_srchead modify column yr varchar(45) NOT NULL DEFAULT  ''");
    $this->coreFunctions->execqry("ALTER TABLE en_srchead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");

    $qry = "CREATE TABLE `en_srcattendance` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` int(11) unsigned NOT NULL DEFAULT '0',
    `month` int(11) unsigned NOT NULL DEFAULT '0',
    `dayspresent` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`trno`, `line`),
    INDEX name (line)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("en_srcattendance", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_srcattendance CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $qry = "CREATE TABLE `en_glsrcattendance` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL DEFAULT '0',
    `clientid` int(11) unsigned NOT NULL DEFAULT '0',
    `month` int(11) unsigned NOT NULL DEFAULT '0',
    `dayspresent` decimal(18,2) unsigned NOT NULL DEFAULT '0.00',
    PRIMARY KEY (`trno`, `line`),
    INDEX name (line)
  ) ENGINE=MyISAM DEFAULT CHARSET=latin1";
    $this->coreFunctions->sbccreatetable("en_glsrcattendance", $qry);
    $this->coreFunctions->execqry("ALTER TABLE en_glsrcattendance CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;");

    $this->coreFunctions->sbcaddcolumngrp(["en_srcattendance", "en_glsrcattendance"], ["attrno", "jan", "feb", "mar", "apr", "may", "jun", "jul", "aug", "sep", "oct", "nov", "dec", "syid", "levelid"], "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["en_srcattendance", "en_glsrcattendance"], ["islate"], "tinyint(4) unsigned NOT NULL DEFAULT '0'", 0);

    $qry = "CREATE TABLE `en_srcremarks` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `clientid` int(11) unsigned NOT NULL DEFAULT '0',
    `quarterid` int(11) unsigned NOT NULL DEFAULT '0',
    `remarks` varchar(500) NOT NULL DEFAULT '',
    `semid` int(11) unsigned NOT NULL DEFAULT '0',
    `ischinese` tinyint(4) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`, `line`),
    INDEX name (line)
  ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $this->coreFunctions->sbccreatetable("en_srcremarks", $qry);

    $qry = "CREATE TABLE `en_glsrcremarks` (
    `trno` int(10) unsigned NOT NULL DEFAULT '0',
    `line` int(11) unsigned NOT NULL DEFAULT '0',
    `clientid` int(11) unsigned NOT NULL DEFAULT '0',
    `quarterid` int(11) unsigned NOT NULL DEFAULT '0',
    `remarks` varchar(500) NOT NULL DEFAULT '',
    `semid` int(11) unsigned NOT NULL DEFAULT '0',
    `ischinese` tinyint(4) unsigned NOT NULL DEFAULT '0',
    PRIMARY KEY (`trno`, `line`),
    INDEX name (line)
  ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $this->coreFunctions->sbccreatetable("en_glsrcremarks", $qry);

    $qry = "CREATE TABLE `en_attendancesetup` (
    `line` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `syid` int(11) unsigned NOT NULL DEFAULT '0',
    `levelid` int(11) unsigned NOT NULL DEFAULT '0',
    `jan` int(11) unsigned NOT NULL DEFAULT '0',
    `feb` int(11) unsigned NOT NULL DEFAULT '0',
    `mar` int(11) unsigned NOT NULL DEFAULT '0',
    `apr` int(11) unsigned NOT NULL DEFAULT '0',
    `may` int(11) unsigned NOT NULL DEFAULT '0',
    `jun` int(11) unsigned NOT NULL DEFAULT '0',
    `jul` int(11) unsigned NOT NULL DEFAULT '0',
    `aug` int(11) unsigned NOT NULL DEFAULT '0',
    `sep` int(11) unsigned NOT NULL DEFAULT '0',
    `oct` int(11) unsigned NOT NULL DEFAULT '0',
    `nov` int(11) unsigned NOT NULL DEFAULT '0',
    `dec` int(11) unsigned NOT NULL DEFAULT '0',
    `totaldays` int(11) unsigned NOT NULL DEFAULT '0',
    `startmonth` DATETIME DEFAULT NULL,
    `endmonth` DATETIME DEFAULT NULL,
    PRIMARY KEY (`line`),
    INDEX name (line)
  ) ENGINE=MyISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $this->coreFunctions->sbccreatetable("en_attendancesetup", $qry);

    $this->coreFunctions->sbcaddcolumngrp(['en_attendancesetup', 'en_studentinfo', 'en_gshead', 'en_gscomponent', 'en_ccyear', 'en_glyear', 'en_atfees', 'en_glfees', 'en_sosubject', 'en_instructor', 'en_gehead', 'en_gesubcomponent', 'en_glsubcomponent', 'en_sjsubject', 'en_adsubject', 'en_sgshead', 'en_sgssubject', 'en_atstudents', 'en_rcdetail'], ['editdate'], "datetime", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_attendancesetup', 'en_studentinfo', 'en_gshead', 'en_gscomponent', 'en_ccyear', 'en_glyear', 'en_atfees', 'en_glfees', 'en_sosubject', 'en_instructor', 'en_gehead', 'en_gesubcomponent', 'en_glsubcomponent', 'en_sjsubject', 'en_adsubject', 'en_sgshead', 'en_sgssubject', 'en_atstudents', 'en_rcdetail'], ['editby'], "varchar(100) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['en_sosubject', 'en_sjsubject'], ['schedstarttime', 'schedendtime'], "DATE DEFAULT NULL", 1);

    $this->coreFunctions->sbcaddcolumngrp(["en_atstudents", "en_glstudents"], ["jan", "feb", "mar", "apr", "may", "jun", "aug", "sep", "oct", "nov", "dec", "schedtrno"], "int(11) unsigned NOT NULL DEFAULT '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["en_atstudents", "en_glstudents"], ["tjan", "tfeb", "tmar", "tapr", "tmay", "tjun", "tjul", "taug", "tsep", "toct", "tnov", "tdec"], "int(11) unsigned NOT NULL DEFAULT '0'", 0);

    $this->coreFunctions->execqry("ALTER TABLE en_sgshead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';", 1);
    $this->coreFunctions->execqry("ALTER TABLE en_glhead MODIFY COLUMN `yr` VARCHAR(45) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';", 1);
  } //end function


} // end class