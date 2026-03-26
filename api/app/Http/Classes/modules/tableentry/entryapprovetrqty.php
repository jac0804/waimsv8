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
use App\Http\Classes\sqlquery;

class entryapprovetrqty
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'STOCK REQUEST DETAILS';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $head = 'htrhead';
  private $stock = 'htrstock';
  private $othersClass;
  public $style = 'width:100%;';
  private $fields = ['itemname', 'rrqty'];
  public $showclosebtn = true;


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 0
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $itemname = 0;
    $rrqty = 1;
    $reqqty = 2;
    $qa = 3;
    $uom = 4;
    $rem = 5;
    $wh = 6;
    $tab = [$this->gridname => ['gridcolumns' => ['itemname', 'rrqty', 'reqqty', 'qa', 'uom',  'rem', 'wh']]]; //'action',

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;max-width:300px;";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;max-width:200px;";
    $obj[0][$this->gridname]['columns'][$wh]['style'] = "width:150px;whiteSpace: normal;min-width:150px;max-width:150px;";
    $obj[0][$this->gridname]['columns'][$uom]['style'] = "width:80px;whiteSpace: normal;min-width:80px;max-width:80px;";
    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;max-width:100px;";
    $obj[0][$this->gridname]['columns'][$reqqty]['style'] = "width:100px;whiteSpace: normal;min-width:100px;max-width:100px;";

    $obj[0][$this->gridname]['columns'][$itemname]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$uom]['type'] = 'input';
    $obj[0][$this->gridname]['columns'][$wh]['type'] = 'input';

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = 'Itemname';
    $obj[0][$this->gridname]['columns'][$rrqty]['label'] = 'Approve Qty';

    $obj[0][$this->gridname]['columns'][$itemname]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$reqqty]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$qa]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$uom]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$wh]['readonly'] = true;
    return $obj;
  }


  public function createtabbutton($config)
  {
    $tbuttons = ['saveall', 'approveallreq', 'approveall'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[2]['label'] = 'APPROVE ALL APPROVED QTY ONLY';
    return $obj;
  }

  public function getdata($config)
  {
    $trno = $config['params']['tableid'];
    $msg = '';
    $status = true;

    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $qty = $this->othersClass->sanitizekeyfield('rrqty', $value['rrqty']);
      $uom = $value['uom'];
      $itemid = $value['itemid'];
      $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
      $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
      $factor = 1;
      if (!empty($item)) {
        $item[0]->factor = $this->othersClass->val($item[0]->factor);
        if ($item[0]->factor !== 0) $factor = $item[0]->factor;
      }
      $vat = 0;
      $computedata = $this->othersClass->computestock(0, '', $qty, $factor, $vat);

      $var = [
        'rrqty' => $qty,
        'qty' => $computedata['qty']
      ];
      $this->coreFunctions->sbcupdate($this->stock, $var, ['trno' => $value['trno'], 'line' => $value['line']]);
    }
    $msg = 'Success...';

    $data = app('App\Http\Classes\modules\issuance\trapproval')->openstock($trno, $config);
    return ['status' => $status, 'msg' => $msg, 'data' => $data];
  } //end function

  public function approveall($config)
  {
    $status = true;
    $msg = '';
    $trno = $config['params']['tableid'];
    $action = $config['params']['action2'];

    switch ($action) {
      case 'approveall':
        if ($this->isapproved($config)) {
          $msg = 'Already approved.';
          $status = false;
        } else {
          $var = [
            'approvedate' => $this->othersClass->getCurrentTimeStamp(),
            'approved' => $config['params']['user']
          ];
          $this->coreFunctions->sbcupdate($this->head, $var, ['trno' => $trno]);
          $msg = 'Success...';
        }
        break;

      case 'approveallreq':
        if ($this->isapproved($config)) {
          $msg = 'Already approved.';
          $status = false;
        } else {
          $data = $config['params']['data'];
          foreach ($data as $key => $value) {
            $qty = $this->othersClass->sanitizekeyfield('rrqty', $value['reqqty']);
            $uom = $value['uom'];
            $itemid = $value['itemid'];
            $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
            $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
            $factor = 1;
            if (!empty($item)) {
              $item[0]->factor = $this->othersClass->val($item[0]->factor);
              if ($item[0]->factor !== 0) $factor = $item[0]->factor;
            }
            $vat = 0;
            $computedata = $this->othersClass->computestock(0, '', $qty, $factor, $vat);

            $var = [
              'rrqty' => $qty,
              'qty' => $computedata['qty']
            ];
            $this->coreFunctions->sbcupdate($this->stock, $var, ['trno' => $value['trno'], 'line' => $value['line']]);
          }

          $var = [
            'approvedate' => $this->othersClass->getCurrentTimeStamp(),
            'approved' => $config['params']['user']
          ];
          $this->coreFunctions->sbcupdate($this->head, $var, ['trno' => $trno]);
          $msg = 'All requested quantities were successfully approved.';
        }
        break;

      default:
        $msg = 'Invalid action.';
        $status = false;
        break;
    }
    $data = app('App\Http\Classes\modules\issuance\trapproval')->openstock($trno, $config);
    return ['status' => $status, 'msg' => $msg, 'data' => $data];
  } //end function

  public function loaddata($config)
  {
    $trno = $config['params']['tableid'];
    $data = app('App\Http\Classes\modules\issuance\trapproval')->openstock($trno, $config);
    return $data;
  }

  public function isapproved($config)
  {
    $trno = $config['params']['tableid'];
    $table = $this->head;
    $document = $this->coreFunctions->datareader("select approvedate as value from $table where trno = ? limit 1", [$trno]);
    if ($document === '' || $document === null) {
      return false;
    } else {
      return true;
    }
  } //end fn

  public function tableentrystatus($config)
  {

    return ['status' => true, 'msg' => 'test'];
  }
} //end class
