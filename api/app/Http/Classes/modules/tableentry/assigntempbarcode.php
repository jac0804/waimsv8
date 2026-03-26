<?php

namespace App\Http\Classes\modules\tableentry;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;

class assigntempbarcode
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Item Barcode Assigning';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = '';
  private $othersClass;
  public $style = 'width:100%;max-width:100%;height:100%';
  private $fields = ['barcode', 'itemname', 'specs', 'itemid', 'uom', 'docno', 'category', 'subcat', 'isgeneric', 'isdisable', 'othcode', 'rem'];
  public $issearchshow = true;
  public $showclosebtn = true;
  private $logger;
  public $rowperpage = 0;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $action = 0;
    $otapproved = 1;
    $ismain = 2;
    $isdisable = 3;
    $ctrlno = 4;
    $barcode = 5;
    $othcode = 6;
    $itemname = 7;
    $specs = 8;
    $uom = 9;
    $docno = 10;
    $cat = 11;
    $subcat = 12;
    $isgeneric = 13;
    $isasset = 14;
    $rem = 15;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'otapproved', 'ismain', 'isdisable', 'ctrlno', 'barcode', 'othcode', 'itemname', 'specs', 'uom', 'docno', 'cat_name', 'subcat_name', 'isgeneric', 'isasset', 'rem'],
        'sortbuttons' => ['new', 'iteminfo', 'save', 'delete']
      ]
    ];

    $access = $this->othersClass->checkAccess($config['params']['user'], 4388);
    if (!$access) {
      $stockbuttons = ['new', 'iteminfo', 'save', 'delete'];
    } else {
      $stockbuttons = [];
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

    $obj[0][$this->gridname]['columns'][$ismain]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$ismain]['label'] = 'Copy Barcode';

    $obj[0][$this->gridname]['columns'][$otapproved]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$otapproved]['label'] = 'Select';

    $obj[0][$this->gridname]['columns'][$isdisable]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$isdisable]['label'] = 'Locked';

    $obj[0][$this->gridname]['columns'][$ctrlno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ctrlno]['style'] = "width:90px;whiteSpace: normal;min-width:90px;";

    $obj[0][$this->gridname]['columns'][$barcode]['label'] = 'SBC Barcode';
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$barcode]['class'] = 'csbarcode sbccsenablealways';
    $obj[0][$this->gridname]['columns'][$barcode]['lookupclass'] = 'barcode';
    $obj[0][$this->gridname]['columns'][$barcode]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$barcode]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:190px;whiteSpace: normal;min-width:190px;";

    $obj[0][$this->gridname]['columns'][$othcode]['label'] = "Barcode Name";
    $obj[0][$this->gridname]['columns'][$othcode]['style'] = "width:190px;whiteSpace: normal;min-width:190px;";
    $obj[0][$this->gridname]['columns'][$othcode]['type'] = 'textarea';

    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = 'width:200px;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = false;
    $obj[0][$this->gridname]['columns'][$specs]['type'] = 'textarea';
    $obj[0][$this->gridname]['columns'][$specs]['style'] = 'width:200px;min-width:200px;';
    $obj[0][$this->gridname]['columns'][$specs]['readonly'] = false;

    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'textarea';

    $obj[0][$this->gridname]['columns'][$uom]['style'] = 'width:100px;min-width:100px;';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'editlookup';
    $obj[0][$this->gridname]['columns'][$uom]['action'] = 'lookupsetup';

    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:150px;min-width:150px;';

    $obj[0][$this->gridname]['columns'][$isasset]['type'] = 'label';


    $obj[0][$this->gridname]['columns'][$cat]['style'] = 'min-width:100px';
    $obj[0][$this->gridname]['columns'][$cat]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$cat]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$cat]['lookupclass'] = 'category';
    $obj[0][$this->gridname]['columns'][$subcat]['style'] = 'min-width:100px';
    $obj[0][$this->gridname]['columns'][$subcat]['type'] = 'lookup';
    $obj[0][$this->gridname]['columns'][$subcat]['action'] = 'lookupsetup';
    $obj[0][$this->gridname]['columns'][$subcat]['lookupclass'] = 'subcategory';
    $obj[0][$this->gridname]['columns'][$isgeneric]['style'] = "width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$itemname]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$othcode]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$specs]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$uom]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$docno]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$cat]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$subcat]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$isgeneric]['checkfield'] = 'isdisable';
    $obj[0][$this->gridname]['columns'][$rem]['checkfield'] = 'isdisable';

    $obj[0][$this->gridname]['columns'][$action]['btns'][3]['confirm'] = true;
    $obj[0][$this->gridname]['columns'][$action]['btns'][3]['confirmlabel'] = 'Are you sure you want to delete this item?';
    return $obj;
  }

  public function createHeadField($config)
  {
    return [];
  }

  public function paramsdata($config)
  {
    return [];
  }

  public function data()
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $access = $this->othersClass->checkAccess($config['params']['user'], 4388);
    if (!$access) {
      $tbuttons = ['markall', 'applybarcode', 'deleteallitem', 'saveallentry', 'saveandclose', 'cancel'];
    } else {
      $tbuttons = [];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    if (!$access) {
      $obj[2]['lookupclass'] = 'loaddata';
      $obj[3]['confirm'] = true;
      $obj[3]['confirmlabel'] = 'Save all changes?';
    }
    return $obj;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];

    foreach ($this->fields as $key => $value) {

      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value], '', $config['params']['companyid']);
    }

    $data2 = [];
    $data2['itemid'] = $data['itemid'];

    $blnUpdateStockcard = false;
    if ($data['isdisable'] == 'true') {

      $iteminfo = $this->coreFunctions->opentable("select itemname, shortname, category, subcat, uom, isgeneric, othcode from item where itemid=" . $data['itemid']);
      if (empty($iteminfo)) {
      } else {
        $uomexist = $this->coreFunctions->getfieldvalue("uom", "itemid", "itemid=? and uom=?", [$data['itemid'], $data['uom']], '', true);
        if ($uomexist == 0) {
          $data2['uom'] = $iteminfo[0]->uom;
        } else {
          $data2['uom'] = $data['uom'];
        }

        $data2['itemname'] = $iteminfo[0]->itemname;
        $data2['specs'] = $iteminfo[0]->shortname;

        $data2['category'] = $iteminfo[0]->category == '' ? '0' : $iteminfo[0]->category;
        $data2['subcat'] = $iteminfo[0]->subcat == '' ? '0' : $iteminfo[0]->subcat;
        $data2['othcode'] = $iteminfo[0]->othcode;
        $data2['isgeneric'] = $iteminfo[0]->isgeneric;
      }
    } else {
      $data2['itemname'] = $data['itemname'];
      $data2['specs'] = $data['specs'];
      $data2['uom'] = $data['uom'];
      $data2['category'] = $data['category'];
      $data2['subcat'] = $data['subcat'];
      $data2['othcode'] = $data['othcode'];
      $data2['isgeneric'] = $data['isgeneric'];

      $blnUpdateStockcard = true;
    }
    $data2['rem'] = $data['rem'];

    $data2['bgcolor'] = '';

    $this->coreFunctions->LogConsole(json_encode($data2));

    if ($this->coreFunctions->sbcupdate('tempitem', $data2, ['trno' => $row['trno'], 'line' => $row['line'], 'doc' => $row['doc']]) == 1) {
      if ($blnUpdateStockcard) {

        $item = [
          'itemname' => $data2['itemname'],
          'shortname' => $data2['specs'],
          'category' => $data2['category'],
          'subcat' => $data2['subcat'],
          'othcode' => $data2['othcode'],
          'isgeneric' => $data2['isgeneric'],
          'editby' => $config['params']['user'],
          'editdate' => $this->othersClass->getCurrentTimeStamp(),
        ];
        $this->coreFunctions->sbcupdate("item", $item, ['itemid' => $data['itemid']]);
      }
      $returnrow = $this->loaddataperrecord($row['trno'], $row['line'], $row['doc'], ['itemid' => $row['hitemid'], 'barcode' => $row['hbarcode'], 'moduledoc' => $row['hmoduledoc'], 'start' => $row['hstart'], 'end' => $row['hend'], 'rem' => $row['hrem'], 'moduletype' => $row['hmoduletype']]);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  }

  public function newitem($config)
  {
    $row = $config['params']['row'];

    if ($row['uom'] == '') {
      return ['status' => false, 'msg' => 'Please input valid uom.'];
    }

    if ($row['itemname'] == '') {
      return ['status' => false, 'msg' => 'Please input valid itemname.'];
    }

    $barcode = $this->othersClass->generatebarcode($config, 'masterfile');
    $data = [];
    $data['barcode'] = $barcode;
    if ($row['othcode'] == '') {
      $data['othcode'] = $barcode;
    } else {
      $data['othcode'] = $row['othcode'];
    }
    $data['itemname'] = $row['itemname'];
    $data['shortname'] = $row['specs'];
    $data['uom'] = $row['uom'];
    $data['category'] = $row['category'];
    $data['subcat'] = $row['subcat'];
    if ($row['isgeneric'] == 'true') {
      $data['isgeneric'] = 1;
    } else {
      $data['isgeneric'] = 0;
    }
    if ($this->coreFunctions->sbcinsert('item', $data) == 1) {
      $itemid = $this->coreFunctions->getfieldvalue('item', 'itemid', 'barcode=?', [$barcode]);

      $this->logger->sbcwritelog($itemid, $config, 'CREATE', $itemid . ' - ' . $data['barcode'] . ' - ' . $data['itemname'] . ' (Item Barcode Assigning)', 'item_log');

      if ($data['uom'] != '') {
        $this->coreFunctions->sbcinsert('uom', ['itemid' => $itemid, 'uom' => $row['uom'], 'factor' => 1]);
      }
      unset($data['barcode']);
      unset($data['shortname']);
      $data['specs'] = $row['specs'];
      $data['itemid'] = $itemid;
      $data2['isasset'] = $row['isasset'];
      $data['isnew'] = 1;
      $data['origitemid'] = $itemid;
      $this->coreFunctions->sbcupdate('tempitem', $data, ['trno' => $row['trno'], 'line' => $row['line'], 'doc' => $row['doc']]);
      $returnrow = $this->loaddataperrecord($row['trno'], $row['line'], $row['doc'], ['itemid' => $row['hitemid'], 'barcode' => $row['hbarcode'], 'moduledoc' => $row['hmoduledoc'], 'start' => $row['hstart'], 'end' => $row['hend'], 'rem' => $row['hrem'], 'moduletype' => $row['hmoduletype']]);
      return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
    } else {
      return ['status' => false, 'msg' => 'Saving failed.'];
    }
  }

  public function delete($config)
  {
    $row = $config['params']['row'];
    $this->coreFunctions->execqry("delete from tempitem where trno=? and line=? and doc=?", 'delete', [$row['trno'], $row['line'], $row['doc']]);
    switch ($row['doc']) {
      case 'PR':
      case 'CD':
      case 'PO':
        $this->coreFunctions->sbcupdate('stockinfotrans', ['isselected' => 0], ['trno' => $row['trno'], 'line' => $row['line']]);
        $this->coreFunctions->sbcupdate('hstockinfotrans', ['isselected' => 0], ['trno' => $row['trno'], 'line' => $row['line']]);
        break;
      case 'RR':
        $this->coreFunctions->sbcupdate('stockinfo', ['isselected' => 0], ['trno' => $row['trno'], 'line' => $row['line']]);
        break;
      case 'RR2':
        $this->coreFunctions->sbcupdate('stockinfo', ['isselected2' => 0], ['trno' => $row['trno'], 'line' => $row['line']]);
        break;
    }
    $headdata = ['barcode' => $row['hbarcode'], 'itemid' => $row['hitemid'], 'moduledoc' => $row['hmoduledoc'], 'rem' => $row['hrem'], 'start' => $row['hstart'], 'end' => $row['hend'], 'moduletype' => $row['hmoduletype']];
    return ['status' => true, 'msg' => 'Successfully deleted.', 'isreloadgrid' => true, 'dataparams' => $headdata];
  }

  public function deleteallitem($config)
  {
    $data = $config['params']['data'][0];
    $headdata = [
      'barcode' => $data['hbarcode'],
      'itemid' => $data['hitemid'],
      'moduledoc' => $data['hmoduledoc'],
      'rem' => $data['hrem'],
      'start' => $data['hstart'],
      'end' => $data['hend'],
      'moduletype' => $data['hmoduletype']
    ];

    $deleted = false;
    foreach ($config['params']['data'] as $d) {
      if ($d['otapproved'] == 'true') {
        switch ($d['doc']) {
          case 'PR':
          case 'CD':
          case 'PO':
            $this->coreFunctions->sbcupdate('stockinfotrans', ['isselected' => 0], ['trno' => $d['trno'], 'line' => $d['line']]);
            $this->coreFunctions->sbcupdate('hstockinfotrans', ['isselected' => 0], ['trno' => $d['trno'], 'line' => $d['line']]);
            break;
          case 'RR':
            $this->coreFunctions->sbcupdate('stockinfo', ['isselected' => 0], ['trno' => $d['trno'], 'line' => $d['line']]);
            break;
          case 'RR2':
            $this->coreFunctions->sbcupdate('stockinfo', ['isselected2' => 0], ['trno' => $d['trno'], 'line' => $d['line']]);
            break;
        }
        $this->coreFunctions->execqry("delete from tempitem where trno=? and line=? and doc=?", 'delete', [$d['trno'], $d['line'], $d['doc']]);
        $deleted = true;
      }
    }

    $returndata = $this->loaddata($config);
    if ($deleted) {
      return ['status' => true, 'msg' => 'Items deleted.', 'data' => $returndata, 'isreloadgrid' => true, 'dataparams' => $headdata];
    } else {
      return ['status' => true, 'msg' =>  'Please select item to delete.', 'data' => $returndata, 'dataparams' => $headdata];
    }
  }

  public function saveallentry($config)
  {
    foreach ($config['params']['data'] as $d) {
      if ($d['isdisable'] == 'false') {
        return ['status' => false, 'msg' => 'Save all failed. All items must be locked.'];
      }
    }

    $this->savealldata($config);

    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  }

  private function savealldata($config)
  {
    $data = $config['params']['data'];
    if (!empty($data)) {
      foreach ($data as $d) {
        $data2 = [];
        if ($d['bgcolor'] != '') {
          if ($d['isdisable'] == 'false') continue;

          $check = $this->coreFunctions->opentable("select itemid from tempitem where trno=? and line=? and doc=?", [$d['trno'], $d['line'], $d['doc']]);
          if ($check) {
            $blnUpdateStockcard = false;
            $data2['itemid'] = $d['itemid'];

            if ($d['isdisable'] == 'true') {
              $iteminfo = $this->coreFunctions->opentable("select itemname, shortname, category, subcat, uom, isgeneric,  othcode from item where itemid=" . $d['itemid']);
              if (empty($iteminfo)) {
              } else {
                $data2['itemname'] = $iteminfo[0]->itemname;
                $data2['othcode'] = $iteminfo[0]->othcode;
                $data2['specs'] = $iteminfo[0]->shortname;
                $data2['uom'] = $iteminfo[0]->uom;
                $data2['category'] = $iteminfo[0]->category == '' ? '0' : $iteminfo[0]->category;
                $data2['subcat'] = $iteminfo[0]->subcat == '' ? '0' : $iteminfo[0]->subcat;
                $data2['isgeneric'] = $iteminfo[0]->isgeneric;
              }
            } else {
              $data2['itemname'] = $d['itemname'];
              $data2['specs'] = $d['specs'];
              $data2['othcode'] = $d['othcode'];
              $data2['uom'] = $d['uom'];
              $data2['category'] = $d['category'];
              $data2['subcat'] = $d['subcat'];
              $data2['isgeneric'] = $d['isgeneric'];

              $blnUpdateStockcard = true;
            }
            $data2['rem'] = $d['rem'];
            $data2['bgcolor'] = '';

            if ($this->coreFunctions->sbcupdate('tempitem', $data2, ['trno' => $d['trno'], 'line' => $d['line'], 'doc' => $d['doc']])) {
              if ($blnUpdateStockcard) {
                $item = [
                  'itemname' => $data2['itemname'],
                  'shortname' => $data2['specs'],
                  'category' => $data2['category'],
                  'subcat' => $data2['subcat'],
                  'othcode' => $data2['othcode'],
                  'isgeneric' => $data2['isgeneric'],
                  'editby' => $config['params']['user'],
                  'editdate' => $this->othersClass->getCurrentTimeStamp(),
                ];
                $this->coreFunctions->sbcupdate("item", $item, ['itemid' => $d['itemid']]);
              }
            }
          }
        }
      }
    }
  }

  public function saveandclose($config)
  {
    $action = $config['params']['lookupclass2'];
    foreach ($config['params']['data'] as $d) {
      if ($d['isdisable'] == 'false') {
        return ['status' => false, 'msg' => 'Save all failed. All items must be locked.'];
      }
    }

    switch ($action) {
      case 'saveandclose':
        if (!empty($config['params']['data'])) {
          $this->savealldata($config);
          foreach ($config['params']['data'] as $d) {

            if ($d['itemid'] != 0) {
              if ($d['barcode'] == $d['othcode']) {
                continue;
              }

              $stockinsert = ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']];

              switch ($d['doc']) {
                case 'PR':
                  $this->coreFunctions->sbcupdate('' . $d['doc'] . 'stock', $stockinsert, ['trno' => $d['trno'], 'line' => $d['line']]);
                  $this->coreFunctions->sbcupdate('h' . $d['doc'] . 'stock', $stockinsert, ['trno' => $d['trno'], 'line' => $d['line']]);

                  $this->coreFunctions->sbcupdate('cdstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);
                  $this->coreFunctions->sbcupdate('hcdstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);

                  $this->coreFunctions->sbcupdate('postock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);
                  $this->coreFunctions->sbcupdate('hpostock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);

                  $this->coreFunctions->execqry("update lastock as s left join lahead as h on h.trno=s.trno set s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where h.doc='SS' and s.reqtrno=" . $d['reqtrno'] . " and s.reqline=" . $d['reqline']);
                  $this->coreFunctions->execqry("update lastock as s left join lahead as h on h.trno=s.trno set s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where h.doc='RR' and s.reqtrno=" . $d['reqtrno'] . " and s.reqline=" . $d['reqline']);
                  break;

                case 'CD':
                  $this->coreFunctions->sbcupdate('' . $d['doc'] . 'stock', $stockinsert, ['trno' => $d['trno'], 'line' => $d['line']]);
                  $this->coreFunctions->sbcupdate('h' . $d['doc'] . 'stock', $stockinsert, ['trno' => $d['trno'], 'line' => $d['line']]);

                  $this->coreFunctions->sbcupdate('hprstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['trno' => $d['reqtrno'], 'line' => $d['reqline']]);

                  $this->coreFunctions->sbcupdate('postock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);
                  $this->coreFunctions->sbcupdate('hpostock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);

                  $this->coreFunctions->execqry("update lastock as s left join lahead as h on h.trno=s.trno set s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where h.doc='RR' and s.reqtrno=" . $d['reqtrno'] . " and s.reqline=" . $d['reqline']);
                  break;

                case 'PO':
                  $stock = $this->coreFunctions->opentable("select rrqty, rrcost, disc from " . ($d['posted'] == 1 ? 'h' : '') . "postock where trno=? and line=?", [$d['trno'], $d['line']]);
                  if ($stock) {
                    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                    $item = $this->coreFunctions->opentable($qry, [$d['uom'], $d['itemid']]);
                    $factor = 1;
                    if (!empty($item)) {
                      $item[0]->factor = $this->othersClass->val($item[0]->factor);
                      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                    }
                    $forex = $this->coreFunctions->getfieldvalue(($d['posted'] == 1 ? 'h' : '') . 'pohead', 'forex', 'trno=?', [$d['trno']]);
                    $computedata = $this->othersClass->computestock($stock[0]->rrcost, $stock[0]->disc, $stock[0]->rrqty, $factor);
                    $stockinsert['cost'] = $computedata['amt'] * $forex;
                    $stockinsert['qty'] = $computedata['qty'];
                  }

                  $this->coreFunctions->LogConsole(json_encode($stockinsert));

                  $this->coreFunctions->sbcupdate('' . $d['doc'] . 'stock', $stockinsert, ['trno' => $d['trno'], 'line' => $d['line']]);
                  $this->coreFunctions->sbcupdate('h' . $d['doc'] . 'stock', $stockinsert, ['trno' => $d['trno'], 'line' => $d['line']]);

                  $this->coreFunctions->sbcupdate('hcdstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);
                  $this->coreFunctions->sbcupdate('hprstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['trno' => $d['reqtrno'], 'line' => $d['reqline']]);

                  $this->coreFunctions->execqry("update lastock as s left join lahead as h on h.trno=s.trno set s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where h.doc='RR' and s.reqtrno=" . $d['reqtrno'] . " and s.reqline=" . $d['reqline']);
                  break;

                case 'RR':
                case 'RR2':
                  $this->coreFunctions->sbcupdate($d['posted'] == 0 ? 'lastock' : 'glstock', $stockinsert, ['trno' => $d['trno'], 'line' => $d['line']]);
                  if ($d['posted'] == 1) {
                    $this->coreFunctions->sbcupdate('rrstatus', ['itemid' => $d['itemid'], 'uom' => $d['uom']], ['trno' => $d['trno'], 'line' => $d['line']]);
                    $this->coreFunctions->sbcupdate('hprstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['trno' => $d['reqtrno'], 'line' => $d['reqline']]);

                    $this->coreFunctions->execqry("update lastock as s left join lahead as h on h.trno=s.trno set s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where h.doc='SS' and s.reqtrno=" . $d['reqtrno'] . " and s.reqline=" . $d['reqline']);
                    $this->coreFunctions->execqry("update glstock as s left join glhead as h on h.trno=s.trno set s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where h.doc='SS' and s.reqtrno=" . $d['reqtrno'] . " and s.reqline=" . $d['reqline']);

                    $this->coreFunctions->execqry("update costing as c left join lastock as s on s.trno=c.trno and s.line=c.line set c.itemid=" . $d['itemid'] . ", s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where c.refx=" . $d['trno'] . " and c.linex=" . $d['line'] . " and s.trno is not null");
                    $this->coreFunctions->execqry("update costing as c left join glstock as s on s.trno=c.trno and s.line=c.line set c.itemid=" . $d['itemid'] . ", s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where c.refx=" . $d['trno'] . " and c.linex=" . $d['line'] . " and s.trno is not null");
                  }
                  $this->coreFunctions->sbcupdate('hpostock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);
                  $this->coreFunctions->sbcupdate('hcdstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['reqtrno' => $d['reqtrno'], 'reqline' => $d['reqline']]);
                  $this->coreFunctions->sbcupdate('hprstock', ['itemid' => $d['itemid'], 'uom' => $d['uom'], 'editdate' => $this->othersClass->getCurrentTimeStamp(), 'editby' => $config['params']['user']], ['trno' => $d['reqtrno'], 'line' => $d['reqline']]);
                  $this->coreFunctions->execqry("update lastock as s left join lahead as h on h.trno=s.trno set s.itemid=" . $d['itemid'] . ", s.uom='" . $d['uom'] . "', s.editdate='" . $this->othersClass->getCurrentTimeStamp() . "', s.editby = '" . $config['params']['user'] . "' where h.doc='SS' and s.reqtrno=" . $d['reqtrno'] . " and s.reqline=" . $d['reqline']);
                  break;
              }
              $this->coreFunctions->execqry("delete from tempitem where trno=? and line=? and doc=?", 'delete', [$d['trno'], $d['line'], $d['doc']]);
            }
          }
        }
        break;
    }

    return ['status' => true, 'msg' => 'All saved successfully.'];
  }

  public function markall($config)
  {

    $markall = false;
    if ($config['params']['data'][0]['otapproved'] == 'true') {
      $markall = false;
    } else {
      $markall = true;
    }

    $returndata = [];
    foreach ($config['params']['data'] as $d => $value) {
      if ($markall) {
        $value['otapproved'] = 'true';
      } else {
        $value['otapproved'] = 'false';
      }
      $value['bgcolor'] = 'bg-blue-2';
      array_push($returndata, $value);
    }

    return ['status' => true, 'msg' => $markall ? 'All items selected' : 'All items unselected', 'data' => $returndata];
  }

  public function applybarcode($config)
  {
    $data = $config['params']['data'];

    $ctrmain = 0;
    $barcode = '';
    $itemid = 0;
    $trno = 0;
    $line = 0;
    $doc = '';

    foreach ($data as $d) {
      if ($d['ismain'] == 'true') {
        $ctrmain += 1;
        $barcode = $d['barcode'];
        $itemid = $d['itemid'];
        $trno = $d['trno'];
        $line = $d['line'];
        $doc = $d['doc'];
      }
    }

    $msg = '';
    if ($ctrmain == 0) {
      $msg = 'Please select one barcode to be used in all items.';
    } else {
      if ($ctrmain > 1) {
        $msg = 'Please select one barcode to be used in all items.';
      }
    }

    $returndata = [];
    if ($ctrmain == 1) {
      foreach ($data as $d => $value) {

        if ($value['trno'] == $trno && $value['line'] == $line && $value['doc'] == $doc) {
          $value['bgcolor'] = 'bg-blue-2';
          $value['ismain'] = 'false';
        } else {
          if ($value['otapproved'] == 'true') {
            $value['barcode'] = $barcode;
            $value['itemid'] = $itemid;
            $value['bgcolor'] = 'bg-blue-2';

            $value['otapproved'] = 'false';
          }
        }

        array_push($returndata, $value);
      }
    }

    if ($msg  == '') $msg = 'Barcode applied';

    return ['status' => true, 'msg' => $msg, 'data' => $returndata];
  }

  private function selectqry()
  {
    return '';
  }

  private function loaddataperrecord($trno, $line, $doc, $headdata = [])
  {
    $qry = "";
    switch ($doc) {
      case 'PR':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name, isgeneric,
          category, subcat, bgcolor, ctrlno, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend, isasset,qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
          select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, info.ctrlno, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
            ti.uom, info.isasset, dept.clientname as deptname, h.clientname, cat.name as cat_name, subc.name as subcat_name,
            case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, '' as bgcolor, '' as errcolor, ti.category, ti.subcat,
            '" . $headdata['moduletype'] . "' as hmoduletype, '" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['start'] . "' as hstart,
            '" . $headdata['end'] . "' as hend, '" . $headdata['rem'] . "' as hrem,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode, 0 as posted, s.trno as reqtrno, s.line as reqline, ti.rem
          from tempitem as ti
            left join prhead as h on h.trno=ti.trno
            left join prstock as s on s.trno=h.trno and s.line=ti.line
            left join stockinfotrans as info on info.trno=s.trno and info.line=s.line
            left join item on item.itemid=ti.itemid
            left join client as dept on dept.clientid=h.deptid
            left join itemcategory as cat on cat.line=ti.category
            left join itemsubcategory as subc on subc.line=ti.subcat
            where s.trno=? and s.line=? and h.doc=?
          union all
          select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, info.ctrlno, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
            ti.uom, info.isasset, dept.clientname as deptname, h.clientname, cat.name as cat_name, subc.name as subcat_name,
            case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, '' as bgcolor, '' as errcolor, ti.category, ti.subcat,
            '" . $headdata['moduletype'] . "' as hmoduletype, '" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['start'] . "' as hstart,
            '" . $headdata['end'] . "' as hend, '" . $headdata['rem'] . "' as hrem,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode, 1 as posted, s.trno as reqtrno, s.line as reqline, ti.rem
          from tempitem as ti
            left join hprhead as h on h.trno=ti.trno
            left join hprstock as s on s.trno=h.trno and s.line=ti.line
            left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line
            left join item on item.itemid=ti.itemid
            left join client as dept on dept.clientid=h.deptid
            left join itemcategory as cat on cat.line=ti.category
            left join itemsubcategory as subc on subc.line=ti.subcat
            where s.trno=? and s.line=? and h.doc=?
          ) as t where trno is not null";

        return $this->coreFunctions->opentable($qry, [$trno, $line, $doc, $trno, $line, $doc]);
        break;
      case 'CD':
      case 'PO':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name, isgeneric,
          category, subcat, bgcolor, ctrlno, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend,qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
          select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, info.ctrlno, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
            ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
            case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, '' as bgcolor, '' as errcolor, ti.category, ti.subcat,
            '" . $headdata['moduletype'] . "' as hmoduletype, '" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['start'] . "' as hstart,
            '" . $headdata['end'] . "' as hend, '" . $headdata['rem'] . "' as hrem,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode, 0 as posted, s.reqtrno, s.reqline, ti.rem
          from tempitem as ti
            left join " . $doc . "head as h on h.trno=ti.trno
            left join " . $doc . "stock as s on s.trno=h.trno and s.line=ti.line
            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
            left join item on item.itemid=ti.itemid
            left join hprhead as hpr on hpr.trno=info.trno
            left join client as dept on dept.clientid=hpr.deptid
            left join itemcategory as cat on cat.line=ti.category
            left join itemsubcategory as subc on subc.line=ti.subcat
            where s.trno=? and s.line=? and h.doc=?
          union all
          select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, info.ctrlno, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
            ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
            case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, '' as bgcolor, '' as errcolor, ti.category, ti.subcat,
            '" . $headdata['moduletype'] . "' as hmoduletype, '" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['start'] . "' as hstart,
            '" . $headdata['end'] . "' as hend, '" . $headdata['rem'] . "' as hrem,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode, 1 as posted, s.reqtrno, s.reqline, ti.rem
          from tempitem as ti
            left join h" . $doc . "head as h on h.trno=ti.trno
            left join h" . $doc . "stock as s on s.trno=h.trno and s.line=ti.line
            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
            left join item on item.itemid=ti.itemid
            left join hprhead as hpr on hpr.trno=info.trno
            left join client as dept on dept.clientid=hpr.deptid
            left join itemcategory as cat on cat.line=ti.category
            left join itemsubcategory as subc on subc.line=ti.subcat
            where s.trno=? and s.line=? and h.doc=?
          ) as t where trno is not null";

        return $this->coreFunctions->opentable($qry, [$trno, $line, $doc, $trno, $line, $doc]);
        break;

      case 'RR':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name, isgeneric,
          category, subcat, bgcolor, ctrlno, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend,qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
          select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, info.ctrlno, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
            ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
            case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, '' as bgcolor, '' as errcolor, ti.category, ti.subcat,
            '" . $headdata['moduletype'] . "' as hmoduletype, '" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['start'] . "' as hstart,
            '" . $headdata['end'] . "' as hend, '" . $headdata['rem'] . "' as hrem,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode, 0 as posted, s.reqtrno, s.reqline, ti.rem
          from tempitem as ti
            left join lahead as h on h.trno=ti.trno
            left join lastock as s on s.trno=h.trno and s.line=ti.line
            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
            left join item on item.itemid=ti.itemid
            left join hprhead as hpr on hpr.trno=info.trno
            left join client as dept on dept.clientid=hpr.deptid
            left join itemcategory as cat on cat.line=ti.category
            left join itemsubcategory as subc on subc.line=ti.subcat
            where s.trno=? and s.line=? and h.doc=?
          ) as t where trno is not null";

        return $this->coreFunctions->opentable($qry, [$trno, $line, $doc]);
        break;

      case 'RR2':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name, isgeneric,
          category, subcat, bgcolor, ctrlno, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend,qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
          select s.trno, s.line, 'RR2' as doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, info.ctrlno, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
            ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
            case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, '' as bgcolor, '' as errcolor, ti.category, ti.subcat,
            '" . $headdata['moduletype'] . "' as hmoduletype, '" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['start'] . "' as hstart,
            '" . $headdata['end'] . "' as hend, '" . $headdata['rem'] . "' as hrem,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode, 0 as posted, s.reqtrno, s.reqline, ti.rem
          from tempitem as ti
            left join lahead as h on h.trno=ti.trno
            left join lastock as s on s.trno=h.trno and s.line=ti.line
            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
            left join item on item.itemid=ti.itemid
            left join hprhead as hpr on hpr.trno=info.trno
            left join client as dept on dept.clientid=hpr.deptid
            left join itemcategory as cat on cat.line=ti.category
            left join itemsubcategory as subc on subc.line=ti.subcat
            where ti.trno=? and ti.line=? and ti.doc=?
            union all
            select s.trno, s.line, 'RR2' as doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, info.ctrlno, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
            ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
            case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, '' as bgcolor, '' as errcolor, ti.category, ti.subcat,
            '" . $headdata['moduletype'] . "' as hmoduletype, '" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['start'] . "' as hstart,
            '" . $headdata['end'] . "' as hend, '" . $headdata['rem'] . "' as hrem,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode, 1 as posted, s.reqtrno, s.reqline, ti.rem
          from tempitem as ti
            left join glhead as h on h.trno=ti.trno
            left join glstock as s on s.trno=h.trno and s.line=ti.line
            left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
            left join item on item.itemid=ti.itemid
            left join hprhead as hpr on hpr.trno=info.trno
            left join client as dept on dept.clientid=hpr.deptid
            left join itemcategory as cat on cat.line=ti.category
            left join itemsubcategory as subc on subc.line=ti.subcat
            where ti.trno=? and ti.line=? and ti.doc=?
          ) as t where trno is not null";

        return $this->coreFunctions->opentable($qry, [$trno, $line, $doc, $trno, $line, $doc]);
        break;
    }
  }

  public function loaddata($config)
  {
    $headdata = [];
    $moduletype = '';

    $this->coreFunctions->execqry("update tempitem set bgcolor=''");

    $selecteddoc =  isset($headdata['moduledoc']) ? $headdata['moduledoc'] : '';

    if (!empty($config['params']['row'])) {
      $item = [];
      $itemid = 0;
      $barcode = '';
      $uom = '';
      $category = '';
      $subcat = '';
      $bgcolor = '';
      $moduletype = $config['params']['moduletype'];
      $headdata = $config['params']['headdata'];

      $selecteddoc =  isset($headdata['moduledoc']) ? $headdata['moduledoc'] : '';

      if ($headdata['barcode'] != '') {
        $barcode = $headdata['barcode'];
        $itemid = $headdata['itemid'];
        $uom = $this->coreFunctions->getfieldvalue('item', 'uom', 'barcode=?', [$barcode]);
        $bgcolor = 'bg-blue-2';
      }
      foreach ($config['params']['row'] as $row) {
        if ($row['otapproved'] == 'true') {
          if ($row['itemdesc'] == null) $row['itemdesc'] = '';
          if ($row['specs'] == null) $row['specs'] = '';
          if ($row['othcode'] == null) $row['othcode'] = '';
          if ($category == '' || $category == null) $category = 0;
          if ($subcat == '' || $subcat == null) $subcat = 0;
          if ($headdata['barcode'] == '') $uom = $row['unit'];

          if ($row['doc'] != 'RR2') {
            $row['itemid'] = $itemid;
          }

          switch ($row['doc']) {
            case 'PR':
            case 'CD':
            case 'PO':
              $this->coreFunctions->sbcupdate('stockinfotrans', ['isselected' => 1], ['trno' => $row['trno'], 'line' => $row['line']]);
              $this->coreFunctions->sbcupdate('hstockinfotrans', ['isselected' => 1], ['trno' => $row['trno'], 'line' => $row['line']]);
              break;
            case 'RR':
              $this->coreFunctions->sbcupdate('stockinfo', ['isselected' => 1], ['trno' => $row['trno'], 'line' => $row['line']]);
              break;
            case 'RR2':
              $this->coreFunctions->sbcupdate($row['status'] == 'P' ? 'hstockinfo' : 'stockinfo', ['isselected2' => 1], ['trno' => $row['trno'], 'line' => $row['line']]);
              break;
          }

          $item = [
            'trno' => $row['trno'], 'line' => $row['line'], 'doc' => $row['doc'], 'itemid' => $row['itemid'], 'itemname' => $row['itemdesc'], 'specs' => $row['specs'], 'uom' => $uom,
            'category' => $category, 'subcat' => $subcat, 'createby' => $config['params']['user'], 'createdate' => $this->othersClass->getCurrentTimeStamp(), 'bgcolor' => $bgcolor, 'othcode' => $row['othcode'], 'reqtrno' => $row['reqtrno'], 'reqline' => $row['reqline']
          ];
          $existtempitem = $this->coreFunctions->opentable("select trno from tempitem where reqtrno=" . $row['reqtrno'] . " and reqline=" . $row['reqline']);
          if (empty($existtempitem)) {
            $this->coreFunctions->sbcinsert('tempitem', $item);
          }
        }
      }
    } else {
      if (empty($config['params']['data'])) {
        $headdata = [
          'barcode' => '',
          'itemid' => '',
          'moduledoc' => '',
          'rem' => '',
          'start' => '',
          'end' => ''
        ];
        $moduletype = '';
      } else {
        $row1 = $config['params']['data'][0];
        $headdata = [
          'barcode' => $row1['hbarcode'],
          'itemid' => $row1['hitemid'],
          'moduledoc' => $row1['hmoduledoc'],
          'rem' => $row1['hrem'],
          'start' => $row1['hstart'],
          'end' => $row1['hend']
        ];
        $moduletype = $row1['hmoduletype'];
      }

      $selecteddoc =  isset($row1['hmoduledoc']) ? $row1['hmoduledoc'] : '';
    }

    $qry = "";

    switch ($selecteddoc) {
      case 'RR':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name,
      isgeneric, category, subcat, ctrlno, bgcolor, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend, isasset, qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
      select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 0 as posted, s.reqtrno, s.reqline, ti.rem
      from tempitem as ti
        left join lahead as h on h.trno=ti.trno
        left join lastock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
        left join item on item.itemid=ti.itemid
        left join hprhead as hpr on hpr.trno=info.trno
        left join client as dept on dept.clientid=hpr.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
        left join cntnum as num on num.trno=h.trno
        where ti.doc='RR' and num.postdate is null ) as t where trno is not null order by createdate,barcode";
        break;

      case 'RR2':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name,
      isgeneric, category, subcat, ctrlno, bgcolor, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend, isasset, qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
      select s.trno, s.line, 'RR2' as doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 0 as posted, s.reqtrno, s.reqline, ti.rem
      from tempitem as ti
        left join lahead as h on h.trno=ti.trno
        left join lastock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
        left join item on item.itemid=ti.itemid
        left join hprhead as hpr on hpr.trno=info.trno
        left join client as dept on dept.clientid=hpr.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
        left join cntnum as num on num.trno=h.trno
        where ti.doc='RR2' and num.postdate is null 
        union all
        select s.trno, s.line, 'RR2' as doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 1 as posted, s.reqtrno, s.reqline, ti.rem
      from tempitem as ti
        left join glhead as h on h.trno=ti.trno
        left join glstock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
        left join item on item.itemid=ti.itemid
        left join hprhead as hpr on hpr.trno=info.trno
        left join client as dept on dept.clientid=hpr.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
        left join cntnum as num on num.trno=h.trno
        where ti.doc='RR2' and num.postdate is not null ) as t where trno is not null order by createdate,barcode";
        break;

      case 'CD':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name,
      isgeneric, category, subcat, ctrlno, bgcolor, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend, isasset, qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
        select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 0 as posted, s.reqtrno, s.reqline, ti.rem
      from tempitem as ti
        left join cdhead as h on h.trno=ti.trno
        left join cdstock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
        left join item on item.itemid=ti.itemid
        left join hprhead as hpr on hpr.trno=info.trno
        left join client as dept on dept.clientid=hpr.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
        left join transnum as num on num.trno=h.trno
        where ti.doc='CD' and num.postdate is null
      union all
      select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 1 as posted, s.reqtrno, s.reqline, ti.rem
      from tempitem as ti
        left join hcdhead as h on h.trno=ti.trno
        left join hcdstock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
        left join item on item.itemid=ti.itemid
        left join hprhead as hpr on hpr.trno=info.trno
        left join client as dept on dept.clientid=hpr.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
        left join transnum as num on num.trno=h.trno
        where ti.doc='CD' and num.postdate is not null ) as t where trno is not null order by createdate,barcode";
        break;

      case 'PO':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name,
      isgeneric, category, subcat, ctrlno, bgcolor, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend, isasset, qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
        select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 0 as posted, s.reqtrno, s.reqline, ti.rem
      from tempitem as ti
        left join pohead as h on h.trno=ti.trno
        left join postock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
        left join item on item.itemid=ti.itemid
        left join hprhead as hpr on hpr.trno=info.trno
        left join client as dept on dept.clientid=hpr.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
         left join transnum as num on num.trno=h.trno
        where ti.doc='PO' and num.postdate is null
      union all
      select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, hpr.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 1 as posted, s.reqtrno, s.reqline, ti.rem
      from tempitem as ti
        left join hpohead as h on h.trno=ti.trno
        left join hpostock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.reqtrno and info.line=s.reqline
        left join item on item.itemid=ti.itemid
        left join hprhead as hpr on hpr.trno=info.trno
        left join client as dept on dept.clientid=hpr.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
         left join transnum as num on num.trno=h.trno
        where ti.doc='PO' and num.postdate is not null ) as t where trno is not null order by createdate,barcode";
        break;

      case 'PR':
        $qry = "select trno, line, doc, docno, itemid, barcode, itemname, specs, uom, isasset, deptname, clientname, cat_name, subcat_name,
      isgeneric, category, subcat, ctrlno, bgcolor, isdisable, hmoduletype, hbarcode, hitemid, hmoduledoc, hrem, hstart, hend, isasset, qacolor,othcode, 'false' as ismain, 'false' as otapproved, posted, reqtrno, reqline, rem from(
        select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, h.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 0 as posted, s.trno as reqtrno, s.line as reqline, ti.rem
      from tempitem as ti
        left join prhead as h on h.trno=ti.trno
        left join prstock as s on s.trno=h.trno and s.line=ti.line
        left join stockinfotrans as info on info.trno=s.trno and info.line=s.line
        left join item on item.itemid=ti.itemid
        left join client as dept on dept.clientid=h.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
        left join transnum as num on num.trno=h.trno
        where ti.doc='PR' and num.postdate is null
      union all
      select s.trno, s.line, h.doc, h.docno, ti.itemid, item.barcode, ti.itemname, ti.specs, case when ti.isdisable=1 then 'true' else 'false' end as isdisable,
        ti.uom, info.isasset, dept.clientname as deptname, h.clientname, cat.name as cat_name, subc.name as subcat_name,
        case ti.isgeneric when 1 then 'true' else 'false' end as isgeneric, ti.bgcolor, '' as errcolor, ti.category, ti.subcat, info.ctrlno,
        '" . $moduletype . "' as hmoduletype,'" . $headdata['barcode'] . "' as hbarcode, '" . $headdata['itemid'] . "' as hitemid, '" . $headdata['moduledoc'] . "' as hmoduledoc, '" . $headdata['rem'] . "' as hrem,
        '" . $headdata['start'] . "' as hstart, '" . $headdata['end'] . "' as hend,if(ti.isnew=1,'bg-yellow-2','') as qacolor,ti.othcode,ti.createdate, 0 as posted, s.trno as reqtrno, s.line as reqline, ti.rem
      from tempitem as ti
        left join hprhead as h on h.trno=ti.trno
        left join hprstock as s on s.trno=h.trno and s.line=ti.line
        left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line
        left join item on item.itemid=ti.itemid
        left join client as dept on dept.clientid=h.deptid
        left join itemcategory as cat on cat.line=ti.category
        left join itemsubcategory as subc on subc.line=ti.subcat
        left join transnum as num on num.trno=h.trno
        where ti.doc='PR'  and num.postdate is not null
    ) as t where trno is not null order by createdate,barcode";
        break;

      default:
        return [];
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  public function lookupsetup($config)
  {
    $lookupclass = $config['params']['lookupclass2'];
    switch ($lookupclass) {
      case 'barcode':
        return $this->lookupbarcode($config);
        break;
      case 'category':
        return $this->lookupcategory($config);
        break;
      case 'subcategory':
        return $this->lookupsubcategory($config);
        break;
      case 'uomstock':
        return $this->lookupuomstock($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Action ' . $config['params']['action'] . ' is not yet in Lookupsetup under ' . $config['params']['lookupclass'] . ' documents'];
        break;
    }
  }

  public function lookupcategory($config)
  {
    $rowindex = $config['params']['index'];
    $lookupsetup = [
      'type' => 'single',
      'title' => 'List of Categories',
      'style' => 'width:100%;max-width:100%;'
    ];
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => ['category' => 'catid', 'cat_name' => 'category']
    );
    $cols = [
      ['name' => 'category', 'label' => 'Category', 'align' => 'left', 'field' => 'category', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $data = $this->coreFunctions->opentable("select '0' as catid, '' as category union all select line as catid, name as category from itemcategory");
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupsubcategory($config)
  {
    $rowindex = $config['params']['index'];
    $lookupsetup = [
      'type' => 'single',
      'title' => 'List of Sub-Categories',
      'style' => 'width:100%;max-width:100%'
    ];
    $plotsetup = [
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => ['subcat' => 'subcatid', 'subcat_name' => 'subcatname']
    ];
    $cols = [
      ['name' => 'subcatname', 'label' => 'Sub-Category', 'align' => 'left', 'field' => 'subcatname', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    $data = $this->coreFunctions->opentable("select '0' as subcatid, '' as subcatname union all select line as subcatid, name as subcatname from itemsubcategory");
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupbarcode($config)
  {
    $rowindex = $config['params']['index'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $lookupsetup = array(
      'type' => 'singlesearch',
      'actionsearch' => 'searchitem',
      'title' => 'List of Products',
      'style' => 'width:100%;max-width:100%;height:90%'
    );
    $data = [];
    $cols = [];
    array_push($cols, array('name' => 'barcode', 'label' => 'SBC Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'othcode', 'label' => 'Barcode Name', 'align' => 'left', 'field' => 'othcode', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'shortname', 'label' => 'Specifications', 'align' => 'left', 'field' => 'shortname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'categoryname', 'label' => 'Category', 'align' => 'left', 'field' => 'categoryname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'subcatname', 'label' => 'Sub-Category', 'align' => 'left', 'field' => 'subcatname', 'sortable' => true, 'style' => 'font-size:16px;'));

    $itemLookupFilter = $this->itemLookupFilterSetup($config);

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => ['barcode' => 'barcode', 'uom' => 'uom', 'itemid' => 'itemid'],
      'confirm' => true,
      'confirmlabel' => 'Are you sure want to select this item?'
    );
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'itemlookupfilter' => $itemLookupFilter, 'index' => $rowindex];
  }

  public function lookupuomstock($config)
  {
    $rowindex = $config['params']['index'];
    $lookupsetup = array(
      'type' => 'singlesearch',
      'title' => 'UOM',
      'style' => 'width:100%;max-width:100%;height:90%'
    );
    $cols = [];
    array_push($cols, array('name' => 'uom', 'label' => 'UOM', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'factor', 'label' => 'Factor', 'align' => 'left', 'field' => 'factor', 'sortable' => true, 'style' => 'font-size:16px;'));

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => ['uom' => 'uom']
    );
    $data = $this->coreFunctions->opentable("select uom, factor from uom where itemid=" . $config['params']['row']['itemid']);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  private function itemLookupFilterSetup($config)
  {
    $fields = ['itemname', 'uom'];
    $col = $this->fieldClass->create($fields);
    data_set($col, 'itemname.style', 'font-size:100%;');
    data_set($col, 'uom.style', 'font-size:100%;');

    $fields = ['specs'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'specs.type', 'ctextarea');
    data_set($col2, 'specs.readonly', true);
    data_set($col2, 'specs.style', 'font-size:100%;');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    $itemfield = $this->coreFunctions->opentable("select '" . $config['params']['row']['itemname'] . "' as itemname,'" . $config['params']['row']['specs'] . "' as specs,'" . $config['params']['row']['uom'] . "' as uom");
    $itemform = array('col' => $col, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4, 'itemfield' => $itemfield[0], 'title' => 'Enter Item Entry', 'style' => 'width:100%;max-width:100%;');
    return $itemform;
  }
} //end class
