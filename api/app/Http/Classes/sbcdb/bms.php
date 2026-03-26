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
    $this->coreFunctions->sbcaddcolumngrp(["glhead", "lahead"], ['bstype', 'ownertype'], "VARCHAR(50) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(["glhead", "lahead"], ['ownername', 'owneraddr', 'contact'], "VARCHAR(150) NOT NULL DEFAULT ''", 1);
    $this->coreFunctions->sbcaddcolumngrp(['glhead', 'lahead'], ['truid', 'bonafideid'], "INT(11) NOT NULL DEFAULT '0'", 1);
    $this->coreFunctions->sbcaddcolumngrp(['reqcategory'], ['istru', 'isbonafide'], "tinyint(1) not null default '0'");
    $this->coreFunctions->sbcaddcolumngrp(['reqcategory'], ['encodeddate'], 'timestamp not null default CURRENT_TIMESTAMP', 0);
    $this->coreFunctions->sbcaddcolumngrp(['clientinfo'], ['chassisno', 'sidecarno'], "VARCHAR(150) NOT NULL DEFAULT ''", 0);

    $this->coreFunctions->sbcaddcolumngrp(["clientinfo"], ['sentence1', 'sentence2', 'sentence3', 'bullet1', 'bullet2', 'bullet3', 'bullet4', 'bullet5', 'bullet6', 'bullet7'], "VARCHAR(1000) NOT NULL DEFAULT ''", 0);
    $this->coreFunctions->sbcaddcolumngrp(['client'], ['istru'], "tinyint(1) not null default '0'", 0);
    $this->coreFunctions->sbcaddcolumngrp(["gldetail", "ladetail"], ['type'], "VARCHAR(45) NOT NULL DEFAULT ''", 1);
    // $this->coreFunctions->sbcdropcolumngrp(["glhead", "lahead"], ["contact"]);
  } //end function
} // end class