<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;

class documentmanagement {

  private $coreFunctions;

  public function __construct() {
    $this->coreFunctions = new coreFunctions;
  } //end fn

  public function tableupdatedocumentmanagement() {

    $qry = "CREATE TABLE `dt_issues` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `issues` varchar(255)  NOT NULL DEFAULT '',
      PRIMARY KEY (`id`),
      INDEX name (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_issues", $qry);

    $this->coreFunctions->execqry("drop table if exists dt_industry");
    $qry = "CREATE TABLE `dt_industry` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `industry` varchar(255) NOT NULL DEFAULT '',
      PRIMARY KEY (`id`),
      INDEX name (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_industry", $qry);

    $qry = "CREATE TABLE `dt_documenttype` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `documenttype` varchar(255) NOT NULL DEFAULT '',
      PRIMARY KEY (`id`),
      INDEX name (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_documenttype", $qry);

    $qry = "CREATE TABLE `dt_details` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `details` varchar(255) NOT NULL DEFAULT '',
      PRIMARY KEY (`id`),
      INDEX name (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_details", $qry);

    $this->coreFunctions->execqry("drop table if exists dt_division");
    $qry = "CREATE TABLE `dt_division` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `division` varchar(255) NOT NULL DEFAULT '',
      PRIMARY KEY (`id`),
      INDEX name (id)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_division", $qry);

    $qry = "CREATE TABLE `dt_status` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `userid` int(11) unsigned NOT NULL DEFAULT 0,
      `statusdoc` varchar(255) NOT NULL DEFAULT '',
      `statussort` int(11) unsigned NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      INDEX name (id)
    ) ENGINE MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_status", $qry);

    $qry = "CREATE TABLE `dt_statuslist` (
      `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `status` varchar(255) NOT NULL DEFAULT '',
      `alias` varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (`id`),
      INDEX name (id)
    ) ENGINE MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_statuslist", $qry);

    $qry = "CREATE TABLE dt_dtstock (
      trno int(10) unsigned NOT NULL DEFAULT '0',
      line int(11) NOT NULL DEFAULT '0',
      docstatusid integer NOT NULL DEFAULT '0',
      issueid integer NOT NULL DEFAULT '0',
      detailid integer NOT NULL DEFAULT '0',
      rem varchar(450) NOT NULL DEFAULT '',
      dateid datetime DEFAULT NULL,
      userid integer NOT NULL DEFAULT '0',
      usertypeid integer NOT NULL DEFAULT '0',
      PRIMARY KEY (trno),
      INDEX name (trno,line,docstatusid,issueid,detailid,userid,usertypeid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_dtstock", $qry);
    $qry = "ALTER TABLE dt_dtstock DROP PRIMARY KEY";
    $this->coreFunctions->execqry($qry, 'drop');
    $qry = "ALTER TABLE dt_dtstock ADD PRIMARY KEY USING BTREE(`trno`, `line`)";
    $this->coreFunctions->execqry($qry, 'add');

    $qry = "CREATE TABLE hdt_dtstock (
      trno int(10) unsigned NOT NULL DEFAULT '0',
      line int(11) NOT NULL DEFAULT '0',
      docstatusid integer NOT NULL DEFAULT '0',
      issueid integer NOT NULL DEFAULT '0',
      detailid integer NOT NULL DEFAULT '0',
      rem varchar(450) NOT NULL DEFAULT '',
      dateid datetime DEFAULT NULL,
      userid integer NOT NULL DEFAULT '0',
      usertypeid integer NOT NULL DEFAULT '0',
      PRIMARY KEY (trno),
      INDEX name (trno,line,docstatusid,issueid,detailid,userid,usertypeid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hdt_dtstock", $qry);
    $qry = "ALTER TABLE hdt_dtstock DROP PRIMARY KEY";
    $this->coreFunctions->execqry($qry, 'drop');
    $qry = "ALTER TABLE hdt_dtstock ADD PRIMARY KEY USING BTREE(`trno`, `line`)";
    $this->coreFunctions->execqry($qry, 'add');

    $qry = "CREATE TABLE dt_dthead (
      trno int(10) unsigned NOT NULL DEFAULT '0',
      doc varchar(2) NOT NULL DEFAULT '',
      docno varchar(20) NOT NULL DEFAULT '',
      dateid datetime DEFAULT NULL,
      terms varchar(45) NOT NULL DEFAULT '',
      isapproved tinyint(1)  NOT NULL DEFAULT '0',
      clientid integer  NOT NULL DEFAULT '0',
      invdate datetime DEFAULT NULL,
      divid integer NOT NULL DEFAULT '0',
      invoiceno varchar(45) NOT NULL DEFAULT '',
      due datetime DEFAULT NULL,
      docstatusid integer  NOT NULL DEFAULT '0',
      poref varchar(45) NOT NULL DEFAULT '',
      title varchar(450) NOT NULL DEFAULT '',
      forex varchar(45) NOT NULL DEFAULT '',
      amt decimal(18,2) NOT NULL DEFAULT '0.00',
      currentstatusid integer  NOT NULL DEFAULT '0',
      currentdate datetime DEFAULT NULL,
      currentuserid integer NOT NULL DEFAULT '0',
      currentusertypeid integer NOT NULL DEFAULT '0',
      createby varchar(45) NOT NULL DEFAULT '',
      createdate DATETIME DEFAULT NULL,
      editby varchar(45) NOT NULL DEFAULT '',
      editdate DATETIME DEFAULT NULL,
      viewby varchar(45) NOT NULL DEFAULT '',
      viewdate DATETIME DEFAULT NULL,
      lockdate DATETIME DEFAULT NULL,
      lockuser varchar(45) NOT NULL DEFAULT '',
      doctypeid integer NOT NULL DEFAULT '0',
      PRIMARY KEY (trno),
      INDEX name (docno,clientid,divid,docstatusid,currentstatusid,currentuserid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("dt_dthead", $qry);
    $this->coreFunctions->sbcaddcolumn("dt_dthead", "costcenter", "int(11) unsigned NOT NULL DEFAULT '0'", 1);  

    $qry = "CREATE TABLE hdt_dthead (
      trno int(10) unsigned NOT NULL DEFAULT '0',
      doc varchar(2) NOT NULL DEFAULT '',
      docno varchar(20) NOT NULL DEFAULT '',
      dateid datetime DEFAULT NULL,
      terms varchar(45) NOT NULL DEFAULT '',
      isapproved tinyint(1)  NOT NULL DEFAULT '0',
      clientid integer  NOT NULL DEFAULT '0',
      invdate datetime DEFAULT NULL,
      divid integer NOT NULL DEFAULT '0',
      invoiceno varchar(45) NOT NULL DEFAULT '',
      due datetime DEFAULT NULL,
      docstatusid integer  NOT NULL DEFAULT '0',
      poref varchar(45) NOT NULL DEFAULT '',
      title varchar(450) NOT NULL DEFAULT '',
      forex varchar(45) NOT NULL DEFAULT '',
      amt decimal(18,2) NOT NULL DEFAULT '0.00',
      currentstatusid integer  NOT NULL DEFAULT '0',
      currentdate datetime DEFAULT NULL,
      currentuserid integer NOT NULL DEFAULT '0',
      currentusertypeid integer NOT NULL DEFAULT '0',
      createby varchar(45) NOT NULL DEFAULT '',
      createdate DATETIME DEFAULT NULL,
      editby varchar(45) NOT NULL DEFAULT '',
      editdate DATETIME DEFAULT NULL,
      viewby varchar(45) NOT NULL DEFAULT '',
      viewdate DATETIME DEFAULT NULL,
      lockdate DATETIME DEFAULT NULL,
      lockuser varchar(45) NOT NULL DEFAULT '',
      doctypeid integer NOT NULL DEFAULT '0',
      PRIMARY KEY (trno),
      INDEX name (docno,clientid,divid,docstatusid,currentstatusid,currentuserid)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("hdt_dthead", $qry);
    $this->coreFunctions->sbcaddcolumn("hdt_dthead", "costcenter", "int(11) unsigned NOT NULL DEFAULT '0'", 1);  

    $qry = "CREATE TABLE docunum (
      trno bigint(20) NOT NULL AUTO_INCREMENT,
      seq int(11) NOT NULL,
      bref varchar(3) NOT NULL,
      doc char(2) NOT NULL DEFAULT '',
      docno char(20) NOT NULL,
      postdate datetime DEFAULT NULL,
      postedby varchar(50) NOT NULL DEFAULT '',
      center varchar(45) NOT NULL DEFAULT '',
      PRIMARY KEY (docno,`center`) USING BTREE,
      KEY Index_TrNo (trno,`doc`,`bref`) USING BTREE,
      KEY Index_3 (center),
      KEY Index_4 (bref)
    ) ENGINE=MyISAM AUTO_INCREMENT=211 DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("docunum", $qry);

    $qry = "CREATE TABLE `docunum_log` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `field` varchar(45) NOT NULL DEFAULT '',
      `oldversion` varchar(900) NOT NULL,
      `userid` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      KEY `Index_1` (`trno`),
      KEY `Index_2` (`dateid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("docunum_log", $qry);

    $qry = "CREATE TABLE `del_docunum_log` (
      `trno` int(10) unsigned NOT NULL DEFAULT '0',
      `docno` varchar(20) NOT NULL DEFAULT '',
      `field` varchar(45) NOT NULL DEFAULT '',
      `code` varchar(45) NOT NULL DEFAULT '',
      `userid` varchar(45) NOT NULL DEFAULT '',
      `dateid` datetime DEFAULT NULL,
      KEY `Index_1` (`trno`),
      KEY `Index_2` (`docno`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("del_docunum_log", $qry);

    $qry = "CREATE TABLE  `docunum_picture` (
      `trno` bigint(20) NOT NULL DEFAULT '0',
      `line` int(20) NOT NULL DEFAULT '0',
      `title` varchar(2000) NOT NULL DEFAULT '',
      `picture` varchar(300) NOT NULL DEFAULT '',
      `encodeddate` datetime DEFAULT NULL,
      `encodedby` varchar(15) NOT NULL DEFAULT '',
      PRIMARY KEY (`trno`,`line`),
      KEY `Index_transnum_picture` (`trno`,`line`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
    $this->coreFunctions->sbccreatetable("docunum_picture", $qry);

    $this->coreFunctions->sbcaddcolumn("dt_issues", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("dt_issues", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("dt_industry", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("dt_industry", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("dt_documenttype", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("dt_documenttype", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("dt_details", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("dt_details", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("dt_division", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("dt_division", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("dt_statuslist", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("dt_statuslist", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);
    $this->coreFunctions->sbcaddcolumn("dt_status", "editdate", " datetime",  0);
    $this->coreFunctions->sbcaddcolumn("dt_status", "editby", " varchar(80)  NOT NULL DEFAULT ''",  0);

  }//end function

} // end class