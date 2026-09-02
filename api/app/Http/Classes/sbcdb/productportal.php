<?php

namespace App\Http\Classes\sbcdb;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;

class productportal
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

    public function tableupdate($config)
    {
        ini_set('max_execution_time', 0);

        $qry = "CREATE TABLE `carbrand` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `brand` varchar(150) NOT NULL DEFAULT '',
        `createby` varchar(150) NOT NULL DEFAULT '',
        `createdate` datetime DEFAULT NULL,
        `editby` varchar(150) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
        )ENGINE=MyISAM DEFAULT CHARSET=latin1";
        $this->coreFunctions->sbccreatetable("carbrand", $qry);

        $qry = "CREATE TABLE `positions` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `positions` varchar(150) NOT NULL DEFAULT '',
        `createby` varchar(150) NOT NULL DEFAULT '',
        `createdate` datetime DEFAULT NULL,
        `editby` varchar(150) NOT NULL DEFAULT '',
        `editdate` datetime DEFAULT NULL,
        PRIMARY KEY (`id`)
        )ENGINE=MyISAM DEFAULT CHARSET=latin1";
        $this->coreFunctions->sbccreatetable("positions", $qry);
        $this->coreFunctions->sbcaddcolumn("iteminfo", "kind", "varchar(200) NOT NULL DEFAULT ''", 0);
        $this->coreFunctions->sbcaddcolumn("item", "carid", "int(11) NOT NULL DEFAULT '0'", 0);
        $this->coreFunctions->sbcaddcolumn("iteminfo", "positionid", "int(11) NOT NULL DEFAULT '0'", 0);
        $this->coreFunctions->sbcaddcolumngrp(["item"], ["model2"],  "INT(11) NOT NULL DEFAULT '0'", 0);
    }
}
