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

class gatepassreturn
{

  public $modulename = 'GATE PASS RETURN-ITEM';
  public $gridname = 'inventory';

  public $tablenum = 'cntnum';
  public $head = 'gphead';
  public $stock = 'gpstock';

  public $hhead = 'hgphead';
  public $hstock = 'hgpstock';

  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';

  public $expirystatus = ['readonly' => false, 'show' => false];

  private $fields = [];

  public $transdoc = "";

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
    ['val' => 'draft', 'label' => 'OUT', 'color' => 'primary'],
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
      'load' => 2944,
      'view' => 2945,
      'edit' => 2945
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $lblstatus = 1;
    $barcode = 2;
    $returndate = 3;
    $docno = 4;
    $empname = 5;
    $itemname = 6;
    $serialno = 7;

    $getcols = [
      'action', 'lblstatus', 'barcode', 'returndate', 'docno', 'empname', 'itemname', 'serialno'
    ];
    $stockbuttons = ['pickerdrop'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);

    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $cols[$lblstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$returndate]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$docno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$empname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$itemname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$serialno]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';

    $cols[$barcode]['label'] = 'Item Tag';
    $cols[$docno]['label'] = 'Gate Pass #';
    $cols[$empname]['label'] = 'Employee';
    $cols[$itemname]['label'] = 'Itemname';

    $cols[$action]['btns']['pickerdrop']['label'] = "Return Gate Pass Item";

    $cols[$action]['btns']['pickerdrop']['checkfield'] = "isreturn";

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];
    $option = $config['params']['itemfilter'];
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));

    $filter = '';
    switch ($option) {
      case 'draft':
        $filter = " and date(head.dateid) between '$date1' and '$date2' and stock.returndate is null";
        break;
      case 'returned':
        $filter = " and date(head.dateid) between '$date1' and '$date2' and stock.returndate is not null";
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
  }

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
        'head.trno', 'stock.line', 'head.docno',
        'stock.itemid', 'item.barcode', 'item.itemname', 'stock.isqty', 'stock.serialno',
        'emp.client', 'emp.clientname'
      ];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    $qry = "
      select head.trno, stock.line, head.docno, left(head.dateid, 10) as dateid,
      stock.itemid, item.barcode, item.itemname, stock.isqty, stock.serialno,
      left(stock.returndate, 10) as returndate,
      case
        when returndate is not null then 'RETURNED'
        else 'OUT'
      end as stat,
      if(stock.returndate is null,'false','true') as isreturn,
      emp.client as empcode, emp.clientname as empname
      from hgphead as head
      left join hgpstock as stock on stock.trno = head.trno
      left join item as item on item.itemid = stock.itemid
      left join client as emp on emp.client = head.client
      where 1 = 1 and head.isconsumable<>1 " . $filtersearch . "
      " . $addonfilter . "
    ";
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
        $trno = $config['params']['row']['trno'];
        $line = $config['params']['row']['line'];

        $data = [
          'returnby' => $user,
          'returndate' => $this->othersClass->getCurrentTimeStamp()
        ];

        $this->coreFunctions->sbcupdate($this->hstock, $data, ['trno' => $trno, 'line' => $line]);

        return ['status' => true, 'msg' => 'Item returned successfully.', 'action' => 'reloadlisting'];
        break;
    }
  }
}//end class
