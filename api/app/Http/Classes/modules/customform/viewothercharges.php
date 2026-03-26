<?php

namespace App\Http\Classes\modules\customform;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class viewothercharges
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'OTHER CHARGES';
  private $head = 'lahead';
  private $hhead = 'glhead';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  public $style = 'width:1200px;max-width:1200px;';
  public $issearchshow = true;
  public $showclosebtn = true;




  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
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

  public function createHeadField($config)
  {
    $trno = $config['params']['clientid'];
    $isposted = $this->othersClass->isposted2($trno, "cntnum");
    $fields = ['ied', 'bankcharges', 'interest', 'brokerfee', 'arrastre'];
    $col1 = $this->fieldClass->create($fields);
    if ($isposted) {
      data_set($col1, 'ied.readonly', true);
      data_set($col1, 'bankcharges.readonly', true);
      data_set($col1, 'interest.readonly', true);
      data_set($col1, 'brokerfee.readonly', true);
      data_set($col1, 'arrastre.readonly', true);
      $fields = [];
    } else {
      data_set($col1, 'ied.readonly', false);
      data_set($col1, 'bankcharges.readonly', false);
      data_set($col1, 'interest.readonly', false);
      data_set($col1, 'brokerfee.readonly', false);
      data_set($col1, 'arrastre.readonly', false);
      $fields = ['refresh'];
    }
    data_set($col1, 'interest.label', 'Interest');
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.label', 'SAVE');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // return $this->coreFunctions->opentable('select adddate(left(now(),10),-360) as dateid');
    $trno = $config['params']['clientid'];
    return $this->coreFunctions->opentable("select trno, format(ied, 2) as ied, format(bankcharges, 2) as bankcharges, format(interest, 2) as interest, format(brokerfee, 2) as brokerfee, format(arrastre, 2) as arrastre from (
      select trno, ied, bankcharges, interest, brokerfee, arrastre from ".$this->head." where trno=".$trno."
      union all
      select trno, ied, bankcharges, interest, brokerfee, arrastre from ".$this->hhead." where trno=".$trno."
    ) as t limit 1");
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $dataparams = $config['params']['dataparams'];
    $isposted = $this->othersClass->isposted2($dataparams['trno'], "cntnum");
    if ($isposted) return ['status' => false, 'msg' => 'Transaction already posted.'];
    if ($this->coreFunctions->execqry("update ".$this->head." set ied='".$dataparams['ied']."', bankcharges='".$dataparams['bankcharges']."', interest='".$dataparams['interest']."', brokerfee='".$dataparams['brokerfee']."', arrastre='".$dataparams['arrastre']."' where trno=".$dataparams['trno'], 'update')) {
      return ['status' => true, 'msg' => 'Record updated', 'closecustomform' => true];
    }
    return ['status' => false, 'msg' => 'Error updating record'];
  }
} //end class
