<?php

namespace App\Http\Classes\modules\dashboard;

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

class todo
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'ASSIGNED TO DO';
  public $gridname = 'customformacctg';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:900px;max-width:900px;';
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
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'docno']
      ]
    ];

    $stockbuttons = ['jumpmodule'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][1]['readonly'] = true;
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = [];
    return $obj;
  }

  public function createHeadField($config)
  {
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $row = $config['params']['row'];
    $doc = $row['doc'];
    $user = $row['userid'];
    if ($user == 0) {
      $user = $row['clientid'];
    }


    switch ($doc) {
      case 'PO':
      case 'PR':
      case 'SO':
      case 'PC':
      case 'PQ':
      case 'SV':
      case 'KR':
      case 'OQ':
        $todo = 'transnumtodo';
        $num = 'transnum';
        $head = strtolower($doc . 'head');
        break;
      case 'SJ':
      case 'CM':
      case 'RR':
      case 'DM':
      case 'AJ':
      case 'TS':
      case 'IS':
      case 'AP':
      case 'PV':
      case 'CV':
      case 'AR':
      case 'CR':
      case 'GJ':
      case 'GD':
      case 'GC':
      case 'DS':
        $head = 'lahead';
        $todo = 'cntnumtodo';
        $num = 'cntnum';
        break;
      case 'HQ':
        $head = 'personreq';
        $todo = 'hrisnumtodo';
        $num = 'hrisnum';
        break;
    }
    $url = $this->checkdoc($doc, $companyid);

    $condition = '';
    switch ($config['params']['companyid']) {
      case 16: //ati
        $condition = " and t.appuser='" . $config['params']['user'] . "'";
        break;
    }

    $qry = "select todo.line,todo.trno,t.doc,ph.docno,'DBTODO' as tabtype, '$url' as url,
                  'module' as moduletype
            from $todo as todo
            left join $num as t on t.trno=todo.trno
            left join $head as ph on ph.trno=todo.trno
            where (todo.clientid = $user or todo.userid = $user) and t.doc='$doc' and t.postdate is null and todo.donedate is null" . $condition;
    return $this->coreFunctions->opentable($qry);
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function loaddata($config)
  {
  }


  public function checkdoc($doc, $companyid)
  {
    $url = '';
    switch (strtolower($doc)) {
      case 'dm':
      case 'rr':
      case 'po':
      case 'cd':
      case 'pr':
        $folderloc = 'purchase';
        if ($companyid == 16) $folderloc = 'ati'; //ati
        $url = "/module/" . $folderloc . "/";
        break;
      case 'oq':
        $url = "/module/ati/";
        break;
      case 'so':
      case 'sj':
      case 'cm':
        $url = "/module/sales/";
        break;
      case 'pc':
      case 'aj':
      case 'ts':
      case 'is':
        $url = "/module/inventory/";
        break;
      case 'pq':
      case 'sv':
      case 'ap':
      case 'pv':
      case 'cv':
        $folderloc = 'payable';
        if ($companyid == 16 && $doc == 'cv') $folderloc = 'ati'; //ati
        $url = "/module/" . $folderloc . "/";
        break;
      case 'ar':
      case 'cr':
      case 'kr':
        $url = "/module/receivable/";
        break;
      case 'gj':
      case 'gd':
      case 'gc':
      case 'ds':
        $url = "/module/accounting/";
        break;
      case 'hq':
        $url = "/module/hris/";
        break;
    }
    return $url;
  }
} //end class
