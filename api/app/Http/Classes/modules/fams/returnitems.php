<?php

namespace App\Http\Classes\modules\fams;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;
use App\Http\Classes\SBCPDF;

class returnitems
{

  public $modulename = 'Return Items';
  public $gridname = 'inventory';

  public $tablenum = 'cntnum';
  public $head = 'gphead';
  public $stock = 'gpstock';

  public $hhead = 'hgphead';
  public $hstock = 'hgpstock';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';

  private $fields = [];

  public $transdoc = "";

  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];

  private $btnClass;
  private $fieldClass;
  private $tabClass;

  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $reporter;

  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = false;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'ISSUED', 'color' => 'primary'],
    ['val' => 'returned', 'label' => 'RETURNED', 'color' => 'primary'],
  ];

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->helpClass = new helpClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 3965, //2944
      'view' => 3965, //2945
      'edit' => 3965 //2945
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {

    $getcols = ['action', 'lblstatus', 'listdate', 'returndate', 'empname', 'docno', 'barcode', 'itemdesc', 'serialno', 'rem'];
    $stockbuttons = ['customformrem'];

    foreach ($getcols as $key => $value) {
      $$value = $key;
    }

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $cols[$lblstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$listdate]['style'] = 'width:200px;whiteSpace: normal;min-width:200px; max-width:200px;';
    $cols[$returndate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$empname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$docno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$barcode]['style'] = 'width:250px;whiteSpace: normal;min-width:250px; max-width:250px;';
    $cols[$itemdesc]['style'] = 'width:250px;whiteSpace: normal;min-width:250px; max-width:250px;';
    $cols[$serialno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$rem]['style'] = 'width:350px;whiteSpace: normal;min-width:350px; max-width:350px;';
    $cols[$empname]['label'] = 'Employee';

    $cols[$listdate]['label'] = 'Issued Date';

    $cols[$action]['btns']['customformrem']['label'] = "Return Item";

    $cols[$action]['btns']['customformrem']['checkfield'] = "isreturn";

    return $cols;
  }

  public function loaddoclisting($config)
  {
    ini_set('memory_limit', '-1');

    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];
    $option = $config['params']['itemfilter'];

    if ($config['params']['date1'] == 'Invalid date') {
      $config['params']['date1'] =  $config['params']['date2'];
    }

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $filter = '';
    switch ($option) {
      case 'draft':
        $filter = " and date(i.dateid) between '$date1' and '$date2' and iss.returndate is null";
        break;
      case 'returned':
        $filter = " and date(i.dateid) between '$date1' and '$date2' and iss.returndate is not null";
        break;
    }

    $qry = $this->selectqry($config, $filter);

    $data = $this->coreFunctions->opentable($qry);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.', $qry];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createHeadField($config)
  {
    return [];
  }

  public function createTab($access, $config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($client = '')
  {
    $data = [];
    return $data;
  }

  private function selectqry($config, $addonfilter, $loadhead = false)
  {
    $config['params']['itemfilter'] == 'complete';

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = [
        'i.trno',
        'i.line',
        'i.itemid',
        'info.serialno',
        'iss.returndate',
        'i.docno',
        'emp.client',
        'emp.clientname',
        'item.itemname',
        'item.barcode'
      ];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry =
      "select i.trno ,iss.line, i.itemid, i.createdate as dateid, info.serialno, iss.returndate, iss.returnby,i.docno, i.rem, num.postdate,
          if(iss.returndate is null,'false','true') as isreturn,
          if(iss.returndate is null,'ISSUED','RETURNED') as stat,
          emp.client as empcode, emp.clientname as empname, item.itemname as itemdesc,item.barcode,info.serialno
          from issueitem as i
          left join client as emp on emp.clientid = i.clientid
          left join issueitemstock as iss on iss.trno=i.trno     
          left join transnum as num on num.trno=i.trno
          left join item on item.itemid=iss.itemid
          left join iteminfo as info on info.itemid=iss.itemid              
         where 1=1 and num.postdate is not null " . $filtersearch . " " . $addonfilter;
    return $qry;
  }

  public function loadheaddata($config)
  {

    if (isset($config['params']['clientid'])) {
      $trno = $config['params']['clientid'];
    } else {
      $trno = $config['params']['row']['clientid'];
    }
    $hidetabbtn = [];
    $isposted = false;

    return  ['head' => [], 'isnew' => false, 'status' => true, 'msg' => "", 'islocked' => false, 'isposted' => $isposted, 'qq' => $trno, 'hidetabbtn' => $hidetabbtn];
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'pickerdrop':

        $user = $config['params']['user'];
        $returnrem = $config['params']['returnrem'];
        $trno = $config['params']['row']['trno'];
        $itemid = $config['params']['row']['itemid'];
        $data = [
          'returnby' => $user,
          'returnrem' => $returnrem,
          'returndate' => $this->othersClass->getCurrentTimeStamp()
        ];
        $this->coreFunctions->sbcupdate("issueitemstock", $data, ['trno' => $trno]);
        $this->coreFunctions->sbcupdate("iteminfo", ['empid' => 0, 'locid' => 0], ['itemid' => $itemid]);
        return ['status' => true, 'msg' => 'Item returned successfully.', 'action' => 'reloadlisting'];
        break;
    }
  }
}//end class
