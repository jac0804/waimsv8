<?php

namespace App\Http\Classes\modules\actionlisting;

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

class canvassapproval2
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'APPROVE CANVASS SHEET - FINAL';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'hcdstock';
  public $tablelogs = 'transnum_log';
  private $othersClass;
  private $logger;
  public $style = 'width:100%;';
  private $fields = ['terms', 'days'];
  public $showclosebtn = false;
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
    $attrib = array(
      'load' => 4008,
      'save' => 4008,
      'view' => 4008
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $fields = ['sortby'];
    $col1 = $this->fieldClass->create($fields);
    data_set(
      $col1,
      'sortby.options',
      array(
        ['label' => ''],
        ['label' => 'Deadline'],
        ['label' => 'Department'],
        ['label' => 'Supplier']
      )
    );

    $fields = ['clientname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, "clientname.label", "Search");
    data_set($col2, "clientname.readonly", false);

    $fields = ['refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, "refresh.type", "actionbtn");

    $gridheadinput = ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];

    $action = 0;
    $otapproved = 1;
    $rem = 2;
    $ctrlno = 3;
    $itemname = 4;
    $itemdesc = 5;
    $specs = 6;
    $rrqty = 7;
    $rrcost = 8;
    $disc = 9;
    $ext = 10;
    $isinvoice = 11;
    $category = 12;
    $purpose = 13;
    $requestorname = 14;
    $deptname = 15;
    $clientname = 16;
    $deadline = 17;
    $rem1 = 18;
    $carem = 19;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'otapproved', 'rem', 'ctrlno', 'itemname', 'itemdesc', 'specs', 'rrqty', 'rrcost', 'disc', 'ext', 'isinvoice', 'category', 'purpose', 'requestorname', 'deptname', 'clientname', 'deadline', 'rem1', 'carem'],
        'gridheadinput' => $gridheadinput,
        'headgridbtns' => ['viewdistribution']
      ]
    ];

    $stockbuttons = ['approvedcanvass'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);


    $obj[0][$this->gridname]['columns'][$action]['btns']['approvedcanvass']['name'] = 'Disapproved Canvass List';
    $obj[0][$this->gridname]['columns'][$action]['btns']['approvedcanvass']['color'] = 'red';
    // action

    $obj[0][$this->gridname]['columns'][$clientname]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$deptname]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$requestorname]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$specs]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$itemdesc]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$itemname]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$rem1]['align'] = "left";
    $obj[0][$this->gridname]['columns'][$carem]['align'] = "left";

    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$ctrlno]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
    $obj[0][$this->gridname]['columns'][$clientname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$deptname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$requestorname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$specs]['style'] = "width:100px;whiteSpace: normal;min-width:100px;word-break:break-word;";
    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = "text-align:right;width:60px;whiteSpace: normal;min-width:60px;";
    $obj[0][$this->gridname]['columns'][$rrcost]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$disc]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
    $obj[0][$this->gridname]['columns'][$ext]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name (Stockcard)";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";
    $obj[0][$this->gridname]['columns'][$rem]['label'] = "Notes (Approved Canvass)";
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = "Item name (Requestor)";
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$clientname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$deptname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$specs]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$deadline]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$purpose]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$requestorname]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$rrqty]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$rrcost]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$disc]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$ext]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$rem1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$carem]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$category]['type'] = "label";

    $obj[0][$this->gridname]['columns'][$rrqty]['align'] = "right";
    $obj[0][$this->gridname]['columns'][$purpose]['align'] = "left";


    $obj[0][$this->gridname]['columns'][$deadline]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name (Stockcard)";

    $obj[0][$this->gridname]['columns'][$otapproved]['label'] = "Select";
    $obj[0][$this->gridname]['columns'][$otapproved]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";

    $obj[0][$this->gridname]['columns'][$rem1]['label'] = "Notes (Canvasser)";
    $obj[0][$this->gridname]['columns'][$rem1]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$carem]['style'] = "width:100px;whiteSpace: normal;min-width:100px;";

    $obj[0][$this->gridname]['columns'][$isinvoice]['type'] = "label";


    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $tbuttons = ['saveallentry', 'unmarkall', 'approved', 'disapproved']; //, 'downloadexcel', 'uploadexcel'
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'Select ALL';
    $obj[0]['lookupclass'] = 'loaddata';
    $obj[0]['icon'] = 'done_all';
    return $obj;
  }

  public function add($config)
  {
    $data = [];
    $data['line'] = 0;
    $data['terms'] = '';
    $data['days'] = 0;
    $data['bgcolor'] = 'bg-blue-2';
    return $data;
  }

  public  function saveallentry($config)
  {


    foreach ($config['params']['data'] as $key => $value) {
      $config['params']['data'][$key]['otapproved'] = 'true';
    }

    $gridheaddata =
      $this->gridheaddata($config);

    return ['status' => true, 'msg' => '', 'action' => 'load', 'data' => $config['params']['data'], 'gridheaddata' => $gridheaddata];
  }

  private function selectqry($config, $select = 'false')
  {

    $filter = '';
    $sortby = 'head.docno, info.deadline, info.itemdesc, item.itemname, info.specs';
    if (isset($config['params']['gridheaddata']['clientname'])) {
      if ($config['params']['gridheaddata']['clientname'] != '') {
        $a = $config['params']['gridheaddata']['clientname'];
        $filter = " and (
        head.clientname like '%" . $a . "%' or
        stock.ref like '%" . $a . "%' or
        info.itemdesc like '%" . $a . "%' or
        info.specs like '%" . $a . "%' or
        info.deadline like '%" . $a . "%' or
        item.itemname like '%" . $a . "%' or
        info.requestorname like '%" . $a . "%' or
        dept.clientname like '%" . $a . "%' or
        head.docno like '%" . $a . "%'
      )";
      }
    }

    if (isset($config['params']['gridheaddata']['sortby'])) {
      $b = '';
      if (isset($config['params']['gridheaddata']['sortby']['label'])) {
        if ($config['params']['gridheaddata']['sortby']['label'] != '') {
          $b = $config['params']['gridheaddata']['sortby']['label'];
        }
      } else {
        if ($config['params']['gridheaddata']['sortby'] != '') {
          $b = $config['params']['gridheaddata']['sortby'];
        }
      }

      switch (strtolower($b)) {
        case 'department':
          $sortby = 'dept.clientname, info.deadline';
          break;
        case 'deadline':
          $sortby = 'info.deadline desc, dept.clientname, info.itemdesc, item.itemname, info.specs';
          break;
        case 'supplier':
          $sortby = 'head.clientname, head.docno, info.deadline desc, dept.clientname, info.itemdesc, item.itemname, info.specs';
          break;
      }
    }


    $qry = "select stock.trno, stock.line, head.docno, head.clientname, stock.ref,'' as bgcolor, info.itemdesc, info.specs, stock.reqtrno, stock.reqline, date(info.deadline) as deadline, 
    '" . $select . "' as otapproved, item.itemname, info.requestorname, dept.clientname as deptname, FORMAT(stock.rrqty,2) as rrqty, FORMAT(stock.rrcost,2) as rrcost, stock.disc, FORMAT(stock.ext,2) as ext, info.ctrlno, stock.rem as rem1,
    case when stock.isprefer=0 then 'NO' else 'YES' end as isprefer, case when hinfo.isadv=0 then 'NO' else 'YES' end as isadv, sinfo.carem, ifnull(cat.category,'') as category, info.purpose,
    case when hinfo.isinvoice=0 then 'NO' else 'YES' end as isinvoice
    from hcdhead as head left join hcdstock as stock on stock.trno=head.trno
    left join item on item.itemid=stock.itemid left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline left join client as dept on dept.clientid=stock.deptid
    left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line left join hheadinfotrans as hinfo on hinfo.trno=head.trno
    left join hprhead as hpr on hpr.trno=info.trno left join reqcategory as cat on cat.line=hpr.ourref
    where stock.void=0 and stock.status=1 and stock.approveddate2 is null " . $filter . "
    group by stock.trno, stock.line, head.docno, head.clientname, stock.ref, info.itemdesc, info.specs, stock.reqtrno, stock.reqline, info.deadline, item.itemname, info.requestorname, dept.clientname,
    stock.rrqty, stock.rrcost, stock.disc, stock.ext, info.ctrlno, stock.rem, stock.isprefer, hinfo.isadv, sinfo.carem, cat.category, info.purpose, hinfo.isinvoice
    order by " . $sortby;
    return $qry;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line, $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line'], $config);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function delete($config)
  {
    $row = $config['params']['row'];
    $qry = "delete from " . $this->table . " where line=?";
    $this->coreFunctions->execqry($qry, 'delete', [$row['line']]);
    return ['status' => true, 'msg' => 'Successfully deleted.'];
  }


  private function loaddataperrecord($line, $config)
  {
    $select = $this->selectqry($config);
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {
    $qry = $this->selectqry($config);
    $data = $this->coreFunctions->opentable($qry);

    return $data;
  }

  public function gridheaddata($config)
  {
    $sortby = '';
    $clientname = '';
    if (isset($config['params']['gridheaddata']['sortby']['label'])) {
      $sortby = $config['params']['gridheaddata']['sortby']['label'];
    } else {
      if (isset($config['params']['gridheaddata']['sortby'])) {
        $sortby = $config['params']['gridheaddata']['sortby'];
      }
    }
    $clientname = isset($config['params']['gridheaddata']['clientname']) ? $config['params']['gridheaddata']['clientname'] : '';

    return $this->coreFunctions->opentable("select '" . $sortby . "' as sortby, '" . $clientname . "' as clientname");
  }

  public function loadheaddata($config)
  {
  }

  public function tableentrystatus($config)
  {

    $action = $config['params']['action2'];
    $data = [];

    $msg = 'Successfully updated.';

    switch ($action) {
      case 'approved':
        $blnApproved = false;
        foreach ($config['params']['data'] as $key => $value) {
          if ($value['otapproved'] == "true") {

            $this->coreFunctions->execqry("update hcdstock set approveddate2='" . $this->othersClass->getCurrentTimeStamp() . "',approvedby2='" . $config['params']['user'] . "'  where trno=" . $value['trno'] . " and line=" . $value['line']);
            $this->logger->sbcwritelog($value['trno'], $config, 'STOCK', 'Line: ' . $value['line'] . ' - APPROVED CANVASS item ' . $value['itemdesc']);
            if (isset($value['rem'])) {
              $this->coreFunctions->execqry("update hstockinfotrans set acrem='" . $value['rem'] . "',editdate='" . $this->othersClass->getCurrentTimeStamp() . "',editby='" . $config['params']['user'] . "' where trno=" . $value['trno'] . " and line=" . $value['line']);
            }
            $blnApproved = true;

            $prref = $this->coreFunctions->opentable("select reqtrno,reqline from hcdstock where trno=? and line=? and reqtrno<>0", [$value['trno'], $value['line']]);
            if (!empty($prref)) {
              $this->coreFunctions->execqry("update hprstock set statrem='Approved Canvass - Final Approved',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=?", 'update', [$prref[0]->reqtrno, $prref[0]->reqline]);
            }
          }
        }
        if (!$blnApproved) {
          $msg = 'Please select atleast item to approve';
        }
        $data = $this->loaddata($config);
        break;

      case 'disapproved':
        $blnApproved = false;
        foreach ($config['params']['data'] as $key => $value) {
          if ($value['otapproved'] == "true") {
            if ($value['rem'] == '') {
              $msg .= 'Please input valid notes. ';
            } else {
              $this->coreFunctions->execqry("update hcdstock set status=0,approveddate=null where trno=" . $value['trno'] . " and line=" . $value['line']);
              $this->logger->sbcwritelog($value['trno'], $config, 'STOCK', 'Line: ' . $value['line'] . ' - DISAPPROVED CANVASS item ' . $value['itemdesc']);

              $this->coreFunctions->execqry("update hstockinfotrans set acrem='" . $value['rem'] . "',editdate='" . $this->othersClass->getCurrentTimeStamp() . "',editby='" . $config['params']['user'] . "' where trno=" . $value['trno'] . " and line=" . $value['line']);
              $blnApproved = true;
              $remdata = [
                'trno' => $value['reqtrno'],
                'reqline' => $value['reqtrno'],
                'cdtrno' => $value['trno'],
                'cdline' => $value['line'],
                'rem' => $value['rem'],
                'createby' => $config['params']['user'],
                'createdate' => $this->othersClass->getCurrentTimeStamp()
              ];
              $this->coreFunctions->sbcinsert("headprrem", $remdata);

              $pending = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hcdstock where trno=? and status<>2", [$value['trno']], '', true);
              if ($pending == 0) $this->coreFunctions->execqry("update transnum set statid=77 where trno=" . $value['trno']);

              $prref = $this->coreFunctions->opentable("select reqtrno,reqline from hcdstock where trno=? and line=? and reqtrno<>0", [$value['trno'], $value['line']]);
              if (!empty($prref)) {
                $this->coreFunctions->execqry("update hprstock set statrem='Approved Canvass - Rejected',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=? ", 'update', [$prref[0]->reqtrno, $prref[0]->reqline]);
              }
            }
          }
        }
        if (!$blnApproved) {
          $msg .= 'Please select atleast item to disapprove';
        }
        $data = $this->loaddata($config);
        break;

      case 'unmarkall':
        foreach ($config['params']['data'] as $key => $value) {
          $config['params']['data'][$key]['otapproved'] = 'false';
        }

        $gridheaddata = $this->gridheaddata($config);

        return ['status' => true, 'msg' => '', 'action' => 'load', 'data' => $config['params']['data'], 'gridheaddata' => $gridheaddata];
        break;

      default:
        $msg = 'Please setup action in tableentrystatus.';
        break;
    }


    return ['status' => true, 'msg' => $msg, 'data' => $data, 'name' => 'Canvass'];
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'uploadexceltemplate':
        return $this->uploadcanvass($config);
        break;
    }

    return ['status' => true, 'msg' => 'Please setup ' . $config['params']['action'] . ' in stockstatusposted'];
  }

  private function uploadcanvass($config)
  {
    $status = true;
    $msg = '';

    foreach ($config['params']['data'] as $key => $value) {

      $checkmultiapproved = $this->checkmultiapproved($value);

      if ($checkmultiapproved['status']) {

        $msg .=   $checkmultiapproved['msg'] . " for " . $value['DESCRIPTION'] . ". ";
      } else {
        $arrcols = array_keys($value);
        foreach ($arrcols as $arrcol) {
          $arr = explode("~", $arrcol);
          if (count($arr) > 1) {
            if (strtolower($arr[0]) == 'ref') {
              $supplier = $arr[1];


              $arr_ref = explode("~", $value["REF~" . $supplier]);

              if (count($arr_ref) > 1) {
                $trno = $arr_ref[0];
                $line = $arr_ref[1];

                $status = 0;
                $data = [
                  'approveddate' => $this->othersClass->getCurrentTimeStamp(),
                  'approvedby' => $config['params']['user'],
                  'editdate' => $this->othersClass->getCurrentTimeStamp(),
                  'editby' => $config['params']['user'],
                  'status' =>  $status
                ];

                switch ($value["STATUS~" . $supplier]) {
                  case 'A':
                    $amt = $value["AMT~" . $supplier];
                    $qty = $value["QTY~" . $supplier];
                    $uom = $value["UOM"];
                    $status = 1;

                    $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
                    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

                    $itemid = $this->coreFunctions->getfieldvalue("hcdstock", "itemid", "trno=? and line=?", [$trno, $line]);
                    if ($itemid == '') {
                      $itemid = 0;
                    }

                    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
                    $factor = 1;
                    if (!empty($item)) {
                      $item[0]->factor = $this->othersClass->val($item[0]->factor);
                      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                    }

                    $computedata = $this->othersClass->computestock($amt, '', $qty, $factor);
                    $data['rrqty'] = $qty;
                    $data['qty'] = $computedata['qty'];
                    $data['rrcost'] = $amt;
                    $data['cost'] = $computedata['amt'];
                    $data['ext'] = $computedata['ext'];
                    break;
                  case 'R':
                    $status = 2;
                    break;
                }

                $data['status'] = $status;

                $curstatus = $this->coreFunctions->getfieldvalue("hcdstock", "status", "trno=? and line=?", [$trno, $line]);
                if (floatval($curstatus) == 0) {

                  $this->coreFunctions->sbcupdate("hcdstock", $data, ['trno' => $trno, 'line' => $line]);
                  if ($status == 1) {
                    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'APPROVED item ' . $value['DESCRIPTION']);
                  } elseif ($status == 2) {
                    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REJECTED item ' . $value['DESCRIPTION']);
                  }
                } else {
                  if (floatval($curstatus) == 1) {
                    $msg .= "Canvass for item " . $value['DESCRIPTION'] . " was already approved. ";
                  } else {
                    $msg .= "Canvass for item " . $value['DESCRIPTION'] . " was already rejected. ";
                  }
                }
              } else {
                $msg .= "Invalid canvass ref for " . $value['DESCRIPTION'] . ". ";
              }
            }
          }
        }
      }
    }

    if ($msg == '') {
      $msg = 'Successfully uploaded.';
    } else {
      $status = false;
    }

    return ['status' => $status, 'msg' => $msg];
  }

  private function checkmultiapproved($value)
  {
    $approved = 0;
    $arrcols = array_keys($value);

    $reqqty = $value['QTY'];
    $qty = 0;

    $this->othersClass->logConsole('RQ qty: ' . $reqqty);

    foreach ($arrcols as $arrcol) {
      $arr = explode("~", $arrcol);
      if (count($arr) > 1) {

        if (strtolower($arr[0]) == 'ref') {
          $supplier = $arr[1];


          switch ($value["STATUS~" . $supplier]) {
            case 'A':
              $approved += 1;
              $qty += $value["QTY~" . $supplier];
              $this->othersClass->logConsole('CD qty: ' . $value["QTY~" . $supplier]);
              break;
          }
        }
      }
    }

    if ($approved > 1) {

      if ($qty > $reqqty) {

        return ['status' => true, 'msg' => "Total approved quantity of " . $qty . " is greater than request quantity of " . $reqqty];
      }
    }

    return ['status' => false, 'msg' => ''];
  }
} //end class
