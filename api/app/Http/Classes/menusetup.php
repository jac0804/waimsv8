<?php

namespace App\Http\Classes;

/*
use Session;*/

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Classes\othersClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\menus\setleftmenucdo;
use App\Http\Classes\setleftmenu;
use App\Http\Classes\setattributes;
use App\Http\Classes\setreportlist;
use App\Http\Classes\setreportlist2;
use Exception;
use Throwable;
use Session;



class menusetup
{


  private $othersClass;
  private $coreFunctions;
  private $setleftmenu;
  private $setleftmenucdo;
  private $setattributes;
  private $setreportlist;
  private $companysetup;

  public function __construct()
  {
    $this->othersClass = new othersClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->setleftmenu = new setleftmenu;
    $this->setleftmenucdo = new setleftmenucdo;
    $this->setattributes = new setattributes;
    $this->setreportlist = new setreportlist;
  }



  public function setupparentmenu($params)
  {
    // $this->coreFunctions->execqry('truncate left_parent', 'truncate');
    // $this->coreFunctions->execqry('truncate left_menu', 'truncate');
    // $this->coreFunctions->execqry('truncate attributes', 'truncate');

    $this->coreFunctions->execqry('delete from left_parent where levelid=0');
    $this->coreFunctions->execqry('delete from left_menu where levelid=0');
    $this->coreFunctions->execqry('delete from attributes where levelid=0');

    $this->coreFunctions->execqry('delete from left_parent where levelid=' . $params['levelid']);
    $this->coreFunctions->execqry('delete from left_menu where levelid=' . $params['levelid']);
    $this->coreFunctions->execqry('delete from attributes where levelid=' . $params['levelid']);

    $modules = $this->companysetup->getmodule($params);
    $companyid = $params['companyid'];

    $this->coreFunctions->LogConsole($companyid);

    $i = 1;
    foreach ($modules as $key => $value) {
      switch ($companyid) {
        case 58: // cdo-hris
          $menu = $this->setleftmenucdo->createmodule($params, $value, $i);
          break;
        default:
          $menu = $this->setleftmenu->createmodule($params, $value, $i);
          break;
      }


      $this->execute($menu);
      $i++;
    }
  } //end function


  private function execute($arr)
  {
    foreach ($arr as $key) {
      $key = str_replace("\\", '\\\\', $key);
      if ($key != '') {
        $this->coreFunctions->execqrynolog($key);
      }
    }
  } //end function

  // DEFAULT REPORTS
  public function setupreportmenulist($params)
  {
    return $this->setreportlist->reportlist($params);
  }

  public function generatereportlist($params)
  {
    $qryparent = "insert into `attributes` 
                (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
                values (3000,0,'REPORTS','',0,'\\\\9','\\\\',0,'0',0," . $params['levelid'] . ")";
    $s = $this->coreFunctions->execqry($qryparent, 'insert');


    switch ($this->companysetup->getsystemtype($params)) {
      case 'HRIS':
      case 'AIMSHRIS':
        $qryparent = "insert into `attributes` 
                    (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
                    values (3411,0,'HRIS REPORTS','',0,'\\\\A','\\\\',0,'0',0," . $params['levelid'] . ")";
        $s = $this->coreFunctions->execqry($qryparent, 'insert');
        break;
      case 'payrollsetup':
        $qryparent = "insert into `attributes` 
                    (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
                    values (3412,0,'PAYROLL REPORTS','',0,'\\\\B','\\\\',0,'0',0," . $params['levelid'] . ")";
        $s = $this->coreFunctions->execqry($qryparent, 'insert');
        break;
      case 'ALL':
        $qryparent = "insert into `attributes` 
                    (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
                    values (3411,0,'HRIS REPORTS','',0,'\\\\A','\\\\',0,'0',0," . $params['levelid'] . ")";
        $s = $this->coreFunctions->execqry($qryparent, 'insert');

        $qryparent = "insert into `attributes` 
                    (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
                    values (3412,0,'PAYROLL REPORTS','',0,'\\\\B','\\\\',0,'0',0," . $params['levelid'] . ")";
        $s = $this->coreFunctions->execqry($qryparent, 'insert');

        $qryparent = "insert into `attributes` 
          (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
          values (3446,0,'WAREHOUSING REPORTS','',0,'\\\\C','\\\\',0,'0',0," . $params['levelid'] . ")";
        $s = $this->coreFunctions->execqry($qryparent, 'insert');
        break;
      case 'WAIMS':
        // WAREHOUSING
        $qryparent = "insert into `attributes` 
          (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
          values (3446,0,'WAREHOUSING REPORTS','',0,'\\\\C','\\\\',0,'0',0," . $params['levelid'] . ")";
        $s = $this->coreFunctions->execqry($qryparent, 'insert');
        break;
      case 'CAIMS':
        $qryparent = "insert into `attributes` 
          (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
          values (3469,0,'CONSTRUCTION REPORTS','',0,'\\\\D','\\\\',0,'0',0," . $params['levelid'] . ")";
        $s = $this->coreFunctions->execqry($qryparent, 'insert');
        break;
    }

    $generalmenu = $this->setupreportmenulist($params);
    $this->coreFunctions->execqry('truncate menu', 'truncate');

    foreach ($generalmenu as $key => $value) {
      if ($value != "") {
        $nipps = explode(',', $value);

        $nipps[1] = str_replace("'", "", $nipps[1]);
        $nipps[9] = str_replace("'", "", $nipps[9]);
        $qry = "insert into `menu` 
                    (`menu`,`parent`,`title`,`alias`,`icon`,`isexpanded`,`seq`,`isok`,`description`,`code`,`attribute`,`ismodified`,`levelid`) 
                    values " . $nipps[0] . "," . "'\\" . $nipps[1] . "'" . "," . $nipps[2] . "," . $nipps[3] . "," . $nipps[4] . "," .
          $nipps[5] . "," . $nipps[6] . "," . $nipps[7] . "," . $nipps[8] . "," . "'\\" . $nipps[9] . "'" . "," . $nipps[10] . "," . $nipps[11] . "," . $nipps[12];

        $this->coreFunctions->execqry($qry, 'insert');

        $qry = "insert into `attributes` 
                  (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`,`levelid`) 
                  values (" . $nipps[10] . ",1," . $nipps[8] . "," . $nipps[3] . ",0," .
          "'\\" . $nipps[9] . "','\\" . $nipps[1] . "'" . ",0,0,0," . $nipps[12];

        $this->coreFunctions->execqry($qry, 'insert');
      } //end if
    } //end each
  }

  public function generateaccesslist($params)
  {
    $systype = $this->companysetup->getsystemtype($params);
    $accesslist = $this->setupaccesslist($params);
    $this->coreFunctions->execqry('truncate attributes', 'truncate');

    foreach ($accesslist as $key) {
      foreach ($key as $key2) {
        foreach ($key2 as $key3) {
          $key3 = str_replace("\\", '\\\\', $key3);
          $qry = "insert into `attributes` (`attribute`,`keyid`,`description`,`alias`,`allowed`,`code`,`parent`,`isexpanded`,`icon`,`parentid`) values " . $key3;
          $this->coreFunctions->execqry($qry, 'insert');
        }
      }
    }
  } //end function



  private function setupaccesslist($params)
  {
    $modules = $this->companysetup->getmodule($params);
    foreach ($modules as $key => $value) {
      if ($value != 'reportlist') {
        $attrmenu[$value] = $this->setattributes->$value($params);
      }
    }
    return $attrmenu;
  } // end function













} //end class

// JIKS [01.19.2021]
// ADD MONTHLY SUMMARY OF OUTPUT TAX REPORT - VITALINE