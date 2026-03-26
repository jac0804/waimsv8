<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewqtdetails
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;

  public $modulename = 'Details';
  public $gridname = 'tableentry';
  private $fields = [
    'outdimlen', 'outdimwd', 'outdimht',
    'indimlen', 'indimwd', 'indimht',
    'chassiswd', 'underchassis',
    'secchassisqty', 'secchassissz', 'secchassistk', 'secchassismat',
    'flrjoistqty', 'flrjoistqtysz', 'flrjoistqtytk', 'flrjoistqtymat',
    'flrtypework', 'flrtypeworktk', 'flrtypeworkty', 'flrtypeworkmat',
    'exttypework', 'exttypeworkqty', 'exttypeworkty',
    'inwalltypework', 'inwalltypeworkqty', 'inwalltypeworktk', 'inwalltypeworkty',
    'inceiltypework', 'inceiltypeworkqty', 'inceiltypeworktk', 'inceiltypeworkty',
    'insultk', 'insulty',
    'reardrstype', 'reardrslock', 'reardrshinger', 'reardrsseals', 'reardrsrem',
    'sidedrstype', 'sidedrslock', 'sidedrshinger', 'sidedrsseals', 'sidedrsrem',
    'normlights', 'lightsrepair',
    'upclrlights', 'lowclrlights', 'clrlightsrepair',
    'paintcover', 'bodycolor', 'flrcolor', 'unchassiscolor',
    'paintroof', 'exterior', 'interior',
    'sideguards', 'reseal'
  ];
  private $table = 'qtinfo';
  private $htable = 'hqtinfo';
  public $head = 'qthead';
  public $hhead = 'hqthead';

  public $tablelogs = 'item_log';
  public $tablelogs_del = 'del_item_log';

  public $style = 'width:100%;max-width:70%;';
  public $issearchshow = true;
  public $showclosebtn = true;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->coreFunctions = new coreFunctions;
    $this->companysetup = new companysetup;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 2133, 'save' => 2145);
    return $attrib;
  }

  public function createHeadField($config)
  {

    $trno = 0;
    if (isset($config['params']['clientid'])) {
      $trno = $config['params']['clientid'];
    } else {
      if (isset($config['params']['trno'])) {
        $trno = $config['params']['trno'];
      } else {
        return [];
      }
    }

    $isposted = $this->othersClass->isposted2($trno, 'transnum');
    $config['params']['trno'] = $trno;
    $islocked = $this->othersClass->islocked($config);

    $fields = ['outsidedimension', 'outdimlen', ['outdimwd', 'outdimht'], 'insidedimension', 'indimlen', ['indimwd', 'indimht'], 'chassis', 'chassiswd', 'underchassis', 'secchassisqty', ['secchassissz', 'secchassistk'], 'secchassismat', 'floorjoist', 'flrjoistqty', ['flrjoistqtysz', 'flrjoistqtytk'], 'flrjoistqtymat', 'flrtypework', ['flrtypeworktk', 'flrtypeworkty'], 'flrtypeworkmat', 'exttypework', ['exttypeworkqty', 'exttypeworkty']];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['interiorwalls', 'inwalltypework', ['inwalltypeworkqty', 'inwalltypeworktk'], 'inwalltypeworkty', 'interiorceiling', 'inceiltypework', ['inceiltypeworkqty', 'inceiltypeworktk'], 'inceiltypeworkty', 'insultk', 'insulty', 'reardoors', 'reardrstype', ['reardrslock', 'reardrshinger'], 'reardrsseals', 'reardrsrem',  'sidedoors',  'sidedrstype', ['sidedrslock', 'sidedrshinger'], 'sidedrsseals', 'sidedrsrem'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['lights', ['normlights', 'lightsrepair'], 'upclrlights', ['lowclrlights', 'clrlightsrepair'], 'paints', 'paintcover', 'bodycolor', 'flrcolor', 'unchassiscolor', 'paintroof', ['exterior', 'interior'], 'sideguards', 'reseal'];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    if (!$isposted && !$islocked) array_push($fields, 'refresh');
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'refresh.label', 'Save Details Changes');
    if ($isposted || $islocked) {

      // // col1
      data_set($col1, 'outdimlen.readonly', true);
      data_set($col1, 'outdimlen.type', 'input');
      data_set($col1, 'outdimwd.readonly', true);
      data_set($col1, 'outdimwd.type', 'input');
      data_set($col1, 'outdimht.readonly', true);
      data_set($col1, 'outdimht.type', 'input');

      data_set($col1, 'paintcover.readonly', true);
      data_set($col1, 'paintcover.type', 'input');


      data_set($col1, 'indimlen.readonly', true);
      data_set($col1, 'indimlen.type', 'input');
      data_set($col1, 'indimwd.readonly', true);
      data_set($col1, 'indimwd.type', 'input');
      data_set($col1, 'indimht.readonly', true);
      data_set($col1, 'indimht.type', 'input');


      data_set($col1, 'secchassisqty.readonly', true);
      data_set($col1, 'secchassisqty.type', 'input');
      data_set($col1, 'underchassis.readonly', true);
      data_set($col1, 'underchassis.type', 'input');

      data_set($col1, 'flrjoistqty.readonly', true);
      data_set($col1, 'flrjoistqty.type', 'input');

      data_set($col1, 'flrtypework.readonly', true);
      data_set($col1, 'flrtypework.type', 'input');

      data_set($col1, 'exttypework.readonly', true);
      data_set($col1, 'exttypework.type', 'input');

      data_set($col1, 'inwalltypework.readonly', true);
      data_set($col1, 'inwalltypework.type', 'input');

      data_set($col1, 'inceiltypework.readonly', true);
      data_set($col1, 'inceiltypework.type', 'input');



      data_set($col1, 'paintroof.readonly', true);
      data_set($col1, 'paintroof.type', 'input');

      data_set($col1, 'sideguards.readonly', true);
      data_set($col1, 'sideguards.type', 'input');

      data_set($col1, 'chassiswd.readonly', true);
      data_set($col1, 'chassiswd.type', 'input');

      data_set($col1, 'insultk.readonly', true);
      data_set($col1, 'insultk.type', 'input');



      // col2

      data_set($col2, 'reardrstype.readonly', true);
      data_set($col2, 'reardrstype.type', 'input');
      data_set($col2, 'sidedrstype.readonly', true);
      data_set($col2, 'sidedrstype.type', 'input');
      data_set($col2, 'reardrslock.readonly', true);
      data_set($col2, 'reardrslock.type', 'input');

      data_set($col2, 'reardrshinger.readonly', true);
      data_set($col2, 'reardrshinger.type', 'input');

      data_set($col2, 'sidedrslock.readonly', true);
      data_set($col2, 'sidedrslock.type', 'input');

      data_set($col2, 'sidedrshinger.readonly', true);
      data_set($col2, 'sidedrshinger.type', 'input');

      data_set($col2, 'bodycolor.readonly', true);
      data_set($col2, 'bodycolor.type', 'input');




      data_set($col2, 'secchassissz.readonly', true);
      data_set($col2, 'secchassissz.type', 'input');

      data_set($col2, 'secchassistk.readonly', true);
      data_set($col2, 'secchassistk.type', 'input');

      data_set($col2, 'flrjoistqtysz.readonly', true);
      data_set($col2, 'flrjoistqtysz.type', 'input');

      data_set($col2, 'flrjoistqtytk.readonly', true);
      data_set($col2, 'flrjoistqtytk.type', 'input');

      data_set($col2, 'flrtypeworktk.readonly', true);
      data_set($col2, 'flrtypeworktk.type', 'input');

      data_set($col2, 'flrtypeworkty.readonly', true);
      data_set($col2, 'flrtypeworkty.type', 'input');

      data_set($col2, 'exttypeworkqty.readonly', true);
      data_set($col2, 'exttypeworkqty.type', 'input');

      data_set($col2, 'inwalltypeworkqty.readonly', true);
      data_set($col2, 'inwalltypeworkqty.type', 'input');

      data_set($col2, 'inwalltypeworktk.readonly', true);
      data_set($col2, 'inwalltypeworktk.type', 'input');

      data_set($col2, 'inceiltypeworkqty.readonly', true);
      data_set($col2, 'inceiltypeworkqty.type', 'input');

      data_set($col2, 'inceiltypeworktk.readonly', true);
      data_set($col2, 'inceiltypeworktk.type', 'input');



      data_set($col2, 'exterior.readonly', true);
      data_set($col2, 'exterior.type', 'input');

      data_set($col2, 'reseal.readonly', true);
      data_set($col2, 'reseal.type', 'input');



      data_set($col2, 'insulty.readonly', true);
      data_set($col2, 'insulty.type', 'input');



      // col3
      data_set($col3, 'normlights.readonly', true);
      data_set($col3, 'normlights.type', 'input');
      data_set($col3, 'upclrlights.readonly', true);
      data_set($col3, 'upclrlights.type', 'input');
      data_set($col3, 'lowclrlights.readonly', true);
      data_set($col3, 'lowclrlights.type', 'input');
      data_set($col3, 'lightsrepair.readonly', true);
      data_set($col3, 'lightsrepair.type', 'input');

      data_set($col3, 'reardrsseals.readonly', true);
      data_set($col3, 'reardrsseals.type', 'input');

      data_set($col3, 'sidedrsseals.readonly', true);
      data_set($col3, 'sidedrsseals.type', 'input');

      data_set($col3, 'flrcolor.readonly', true);
      data_set($col3, 'flrcolor.type', 'input');



      data_set($col3, 'secchassismat.readonly', true);
      data_set($col3, 'secchassismat.type', 'input');

      data_set($col3, 'flrjoistqtymat.readonly', true);
      data_set($col3, 'flrjoistqtymat.type', 'input');

      data_set($col3, 'flrtypeworkmat.readonly', true);
      data_set($col3, 'flrtypeworkmat.type', 'input');

      data_set($col3, 'exttypeworkty.readonly', true);
      data_set($col3, 'exttypeworkty.type', 'input');

      data_set($col3, 'inwalltypeworkty.readonly', true);
      data_set($col3, 'inwalltypeworkty.type', 'input');

      data_set($col3, 'inceiltypeworkty.readonly', true);
      data_set($col3, 'inceiltypeworkty.type', 'input');

      data_set($col3, 'clrlightsrepair.readonly', true);
      data_set($col3, 'clrlightsrepair.type', 'input');

      data_set($col3, 'interior.readonly', true);
      data_set($col3, 'interior.type', 'input');

      // col4
      data_set($col4, 'reardrsrem.readonly', true);
      data_set($col4, 'reardrsrem.type', 'input');

      data_set($col4, 'sidedrsrem.readonly', true);
      data_set($col4, 'sidedrsrem.type', 'input');

      data_set($col4, 'unchassiscolor.readonly', true);
      data_set($col4, 'unchassiscolor.type', 'input');
    }
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  private function selectqry()
  {
    $qry = "trno";
    foreach ($this->fields as $key => $value) {
      $qry = $qry . ',' . $value;
    }
    return $qry;
  }

  public function paramsdata($config)
  {

    $trno = 0;
    if (isset($config['params']['clientid'])) {
      $trno = $config['params']['clientid'];
    } else {
      if (isset($config['params']['dataparams']['trno'])) {
        $trno = $config['params']['dataparams']['trno'];
      } else {
        if (isset($config['params']['trno'])) {
          $trno = $config['params']['trno'];
        } else {
          return [];
        }
      }
    }

    $qry = $this->selectqry();
    $qry2 = $trno . " as trno ";
    $qry = "select " . $qry . " from " . $this->table . " where trno = ? union all select " . $qry . " from " . $this->htable . " where trno=?";
    $data = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    if (empty($data)) {
      foreach ($this->fields as $key => $value) {
        $qry2 = $qry2 . ', "" as ' . $value;
      }
      $qry2 = "select " . $qry2;
      $data = $this->coreFunctions->opentable($qry2);
    }
    return $data;
  }

  public function data()
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function loaddata($config)
  {
    $data = [];
    $row = $config['params']['dataparams'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    $c = $this->coreFunctions->opentable("select trno from qtinfo where trno=?", [$config['params']['dataparams']['trno']]);
    if (!empty($c)) {
      $this->coreFunctions->sbcupdate($this->table, $data, ['trno' => $config['params']['dataparams']['trno']]);
    } else {
      $data['trno'] = $row['trno'];
      $this->coreFunctions->sbcinsert($this->table, $data);
    }
    $txtdata = $this->paramsdata($config);
    return ['status' => true, 'msg' => 'Save Quotation Details Success', 'data' => [], 'txtdata' => $txtdata];
  }
}
