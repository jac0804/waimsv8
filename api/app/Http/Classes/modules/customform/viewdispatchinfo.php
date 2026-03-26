<?php

namespace App\Http\Classes\modules\customform;

use App\Http\Classes\builder\tabClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\companysetup;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use Exception;

class viewdispatchinfo
{
  private $fieldClass;
  private $tabClass;
  private $coreFunctions;
  private $companysetup;
  private $othersClass;

  public $modulename = 'Dispatch';
  public $gridname = 'tableentry';
  public $style = 'width:50%;max-width:50%;';
  public $showclosebtn = true;
  public $issearchshow = false;

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
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createHeadField($config)
  {
    switch ($config['params']['doc']) {
      case 'SO':
      case 'DR':
        $fields = ['lbldpno', 'dpno', 'lblfreightno', 'freightno', 'lblfreightamt', 'freightamt', 'lblhandno', 'handno', 'lblhandnoamt', 'handnoamt', 'lblwharfno', 'wharfno', 'lblwharfnoamt', 'wharfnoamt', 'lblpermitfee', 'permitfee', 'lblvpassno', 'vpassno', 'lblvpassnoamt', 'vpassnoamt'];
        $col1 = $this->fieldClass->create($fields);

        $fields = ['lbldpdate', 'dpdate', 'lblmiscno', 'miscno', 'lblmiscnoamt', 'miscnoamt', 'lblvoyno', 'voyno', 'lblblno', 'blno', 'lblshipline', 'shipline', 'lblvessel', 'vessel'];
        $col2 = $this->fieldClass->create($fields);
        return ['col1' => $col1, 'col2' => $col2];
        break;
      default:
        $fields = ['lbldpno', 'dpno', 'lbldpdate', 'dpdate'];
        $col1 = $this->fieldClass->create($fields);
        return ['col1' => $col1];
        break;
    }
  }

  public function paramsdata($config)
  {
    return $this->getheaddata($config);
  }

  public function getheaddata($config)
  {
    $trno = $config['params']['trno'];
    $qry = "";
    $dptrno = '';
    switch ($config['params']['doc']) {
      case 'SO':
        $sodocno = $this->coreFunctions->getfieldvalue("transnum", "docno", "trno=?", [$trno]);
        $drtrno = $this->coreFunctions->opentable("select trno from (
          select h.trno from lahead as h left join lastock as s on s.trno = h.trno where h.doc='DR' and s.refx = " . $trno . "
          union all
          select h.trno from glhead as h left join glstock as s on s.trno = h.trno  where h.doc='DR' and s.refx=" . $trno . "
        ) as t order by trno limit 1");
        $trno = $drtrno[0]->trno;
        if (!empty($drtrno)) $dptrno = $this->coreFunctions->getfieldvalue('cntnum', 'dptrno', 'trno=?', [$drtrno[0]->trno]);
        break;
      default:
        $dptrno = $this->coreFunctions->getfieldvalue('cntnum', 'dptrno', 'trno=?', [$trno]);
        break;
    }

    if ($dptrno != '') {
      $qry = "select dpno,dpdate from (select i.trno,dp.docno as dpno, date(i.shipdate) as dpdate from cntnum as num left join cntnuminfo as i on i.trno = num.trno left join transnum as dp on dp.trno = num.dptrno where dp.trno = " . $dptrno . " and i.trno = " . $trno . "
      union all
      select i.trno,dp.docno as dpno, date(i.shipdate) as dpdate from cntnum as num left join hcntnuminfo as i on i.trno = num.trno left join transnum as dp on dp.trno = num.dptrno where dp.trno = " . $dptrno . " and i.trno = " . $trno . ") as d";

      $this->coreFunctions->LogConsole($qry);
    } else {
      $qry = "select '' as dpno, '' as dpdate";
    }
    return $this->coreFunctions->opentable($qry);
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
    return [];
  }
}
