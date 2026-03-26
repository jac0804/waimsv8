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

class canvassapproval
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'CANVASS SHEET APPROVAL';
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
      'load' => 1447,
      'save' => 1447,
      'view' => 1447
    );
    return $attrib;
  }

  public function createTab($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $companyid = $config['params']['companyid'];

    if ($companyid == 16) { // ati
      $fields = ['statrem'];
      $col1 = $this->fieldClass->create($fields);
      data_set(
        $col1,
        'statrem.options',
        array(
          ['label' => 'All'],
          ['label' => 'Canvass Sheet - Posted'],
          ['label' => 'Approved Canvass - Rejected']
        )
      );

      $fields = ['clientname'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, "clientname.label", "Search");
      data_set($col2, "clientname.readonly", false);
    } else {

      $fields = ['clientname'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, "clientname.label", "Search");
      data_set($col1, "clientname.readonly", false);

      $fields = [];
      $col2 = $this->fieldClass->create($fields);
    }

    $fields = ['refresh'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, "refresh.type", "actionbtn");

    $fields = [];
    $col4 = $this->fieldClass->create($fields);

    $gridheadinput = ['col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4];

    $action = 0;
    $controlno = 1;
    $otapproved = 2;
    $ref = 3;
    $barcode = 4;
    $itemname = 5;
    $itemdesc = 6;
    $specs = 7;
    $deadline = 8;
    $deptname = 9;
    $requestorname = 10;


    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'ctrlno', 'otapproved', 'ref', 'barcode', 'itemname', 'itemdesc', 'specs', 'deadline', 'deptname', 'requestorname'],
        'gridheadinput' => $gridheadinput,
      ]
    ];

    $stockbuttons = ['approvedcanvass'];
    if ($config['params']['companyid'] == 16) { //ATI
      array_push($stockbuttons, 'viewuomdet');
    }

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][$action]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$ref]['style'] = "width:170px;whiteSpace: normal;min-width:170px;";
    $obj[0][$this->gridname]['columns'][$ref]['type'] = "input";
    $obj[0][$this->gridname]['columns'][$barcode]['style'] = "width:170px;whiteSpace: normal;min-width:170px;";
    $obj[0][$this->gridname]['columns'][$deptname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    $obj[0][$this->gridname]['columns'][$requestorname]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name";
    $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
    $obj[0][$this->gridname]['columns'][$itemname]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$barcode]['type'] = "label";

    if ($systemtype == 'ATI') {

      $obj[0][$this->gridname]['columns'][$ref]['align'] = "left";
      $obj[0][$this->gridname]['columns'][$deptname]['align'] = "left";
      $obj[0][$this->gridname]['columns'][$requestorname]['align'] = "left";
      $obj[0][$this->gridname]['columns'][$specs]['align'] = "left";
      $obj[0][$this->gridname]['columns'][$barcode]['align'] = "left";
      $obj[0][$this->gridname]['columns'][$itemdesc]['align'] = "left";
      $obj[0][$this->gridname]['columns'][$itemname]['align'] = "left";
      $obj[0][$this->gridname]['columns'][$controlno]['align'] = "left";

      $obj[0][$this->gridname]['columns'][$itemdesc]['label'] = "Item name (Requestor)";
      $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = "width:300px;whiteSpace: normal;min-width:300px;";
      $obj[0][$this->gridname]['columns'][$deadline]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
      $obj[0][$this->gridname]['columns'][$specs]['style'] = "width:100px;whiteSpace: normal;min-width:100px;word-break:break-word;";

      $obj[0][$this->gridname]['columns'][$ref]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$deptname]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$specs]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$deadline]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$requestorname]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$controlno]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item name (Stockcard)";

      $obj[0][$this->gridname]['columns'][$otapproved]['label'] = "Select";
      $obj[0][$this->gridname]['columns'][$otapproved]['style'] = "width:50px;whiteSpace: normal;min-width:50px;";
    } else {
      $obj[0][$this->gridname]['columns'][$otapproved]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$specs]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$deptname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$requestorname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$deadline]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$controlno]['type'] = "coldel";
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }


  public function createtabbutton($config)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $tbuttons = [];
    if ($systemtype == 'ATI') {
      $tbuttons = ['saveallentry', 'unmarkall', 'downloadexcel', 'uploadexcel'];
    }
    $obj = $this->tabClass->createtabbutton($tbuttons);
    if ($systemtype == 'ATI') {
      $obj[0]['label'] = 'MARK ALL';
      $obj[0]['lookupclass'] = 'loaddata';
    }
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    $addedfields = '';
    $addedfieldsgrp = '';
    $addedleftjoin = '';
    $addedorderby = '';
    $filter = '';

    $center = $config['params']['center'];

    switch ($systemtype) {
      case "ATI":
        $statrem = '';

        if (isset($config['params']['gridheaddata']['statrem'])) {
          $b = '';
          if (isset($config['params']['gridheaddata']['statrem']['label'])) {
            if ($config['params']['gridheaddata']['statrem']['label'] != '') {
              $b = $config['params']['gridheaddata']['statrem']['label'];
            }
          } else {
            if ($config['params']['gridheaddata']['statrem'] != '') {
              $b = $config['params']['gridheaddata']['statrem'];
            }
          }
          switch ($b) {
            case 'Canvass Sheet - Posted':
              $filter .= " and pr.statrem = 'Canvass Sheet - Posted' ";
              break;
            case 'Approved Canvass - Rejected':
              $filter .= " and pr.statrem = 'Approved Canvass - Rejected' ";
              break;
          }
        }



        $addedfields = ", item.barcode, info.itemdesc,info.ctrlno, info.specs, stock.reqtrno, stock.reqline, date(info.deadline) as deadline, '" . $select . "' as otapproved, item.itemname, info.requestorname, dept.clientname as deptname,pr.statrem";
        $addedfieldsgrp = ', item.barcode, info.itemdesc,info.ctrlno, info.specs, stock.reqtrno, stock.reqline, info.deadline, item.itemname, info.requestorname, dept.clientname,pr.statrem';
        $addedleftjoin = ' left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline left join client as dept on dept.clientid=stock.deptid left join hprstock as pr on pr.trno=stock.refx and pr.line=stock.linex
        left join hstockinfotrans as sinfo on sinfo.trno=stock.trno and sinfo.line=stock.line';
        $addedorderby = ', info.itemdesc, info.specs, item.itemname';

        if (isset($config['params']['gridheaddata']['clientname'])) {
          if ($config['params']['gridheaddata']['clientname'] != '') {
            $a = $config['params']['gridheaddata']['clientname'];
            $filter = " and (
                      stock.ref like '%" . $a . "%' or
                      info.itemdesc like '%" . $a . "%' or
                      info.specs like '%" . $a . "%' or
                      info.deadline like '%" . $a . "%' or
                      item.itemname like '%" . $a . "%' or
                      info.requestorname like '%" . $a . "%' or
                      dept.clientname like '%" . $a . "%' 
                    )";
          }
        }
        $filter .= " and stock.reqtrno<>0 ";
        break;
      default:
        $addedfields = ', item.barcode,item.itemname ';
        $addedfieldsgrp = ', item.barcode,item.itemname ';
        $addedorderby = ', item.barcode,item.itemname';

        if (isset($config['params']['gridheaddata']['clientname'])) {
          if ($config['params']['gridheaddata']['clientname'] != '') {
            $a = $config['params']['gridheaddata']['clientname'];
            $filter = " and (
                      stock.ref like '%" . $a . "%' or
                      item.itemname like '%" . $a . "%' or
                      item.barcode like '%" . $a . "%'
                    )";
          }
        }
        break;
    }

    $qry = "select stock.ref,'' as bgcolor " . $addedfields . "
    from hcdhead as head left join hcdstock as stock on stock.trno=head.trno
    left join transnum as num on num.trno=head.trno
    left join item on item.itemid=stock.itemid " . $addedleftjoin . "
    where stock.void=0 and stock.status=0 and stock.ismanual=0 and num.center='" . $center . "'
    " . $filter . "
    group by stock.ref" . $addedfieldsgrp . " order by stock.ref" . $addedorderby;
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
    $statrem = '';
    if (isset($config['params']['gridheaddata']['sortby']['label'])) {
      $sortby = $config['params']['gridheaddata']['sortby']['label'];
    } else {
      if (isset($config['params']['gridheaddata']['sortby'])) {
        $sortby = $config['params']['gridheaddata']['sortby'];
      }
    }

    if (isset($config['params']['gridheaddata']['statrem']['label'])) {
      $statrem = $config['params']['gridheaddata']['statrem']['label'];
    } else {
      if (isset($config['params']['gridheaddata']['statrem'])) {
        $statrem = $config['params']['gridheaddata']['statrem'];
      }
    }
    $clientname = isset($config['params']['gridheaddata']['clientname']) ? $config['params']['gridheaddata']['clientname'] : '';

    return $this->coreFunctions->opentable("select '" . $statrem . "' as statrem, '" . $clientname . "' as clientname");
  }

  public function loadheaddata($config)
  {
  }

  public function tableentrystatus($config)
  {

    switch ($config['params']['action2']) {
      case 'unmarkall':

        foreach ($config['params']['data'] as $key => $value) {
          $config['params']['data'][$key]['otapproved'] = 'false';
        }

        $gridheaddata = $this->gridheaddata($config);

        return ['status' => true, 'msg' => '', 'action' => 'load', 'data' => $config['params']['data'], 'gridheaddata' => $gridheaddata];
        break;
      default:
        $supplier = $this->coreFunctions->opentable("select head.client, head.clientname
        from hcdhead as head left join hcdstock as stock on stock.trno=head.trno left join item on item.itemid=stock.itemid
        where stock.status=0 group by head.client, head.clientname order by head.clientname, head.client");

        $data = [];
        $col = [
          'REF' => '',
          'CODE' => '',
          'PRODUCT TYPE' => '',
          'QTY' => '',
          'UOM' => '',
          'ITEM' => '',
          'SPECIFICATION' => '',
          'BRAND' => '',
          'DESCRIPTION' => ''
        ];

        $items = [];

        foreach ($config['params']['data'] as $key => $value) {
          if ($value['otapproved'] == 'true') {
            $qry = "select ifnull(stock.ref,'') as ref,ifnull(info.itemdesc,'') as itemdesc, 
        stock.reqtrno, stock.reqline, round(ifnull(req.rrqty,0)) as reqqty, info.specs,info.unit,stock.uom,ifnull(b.brand_desc,'') as brand, ifnull(item.barcode,'') as barcode
        from hcdhead as head left join hcdstock as stock on stock.trno=head.trno
        left join item on item.itemid=stock.itemid left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
        left join hprstock as req on req.trno=info.trno and req.line=info.line left join frontend_ebrands as b on b.brandid=item.brand
        where stock.status=0 and stock.ismanual=0 and req.trno=" . $value['reqtrno'] . " and req.line=" . $value['reqline'] . " group by stock.ref,info.itemdesc, stock.reqtrno, stock.reqline, req.rrqty,info.specs,info.unit,stock.uom,b.brand_desc,item.barcode
        order by stock.ref,info.itemdesc";
            $itemdata = $this->coreFunctions->opentable($qry);
            array_push($items, $itemdata[0]);
          }
        }

        foreach ($items as $key => $value) {
          $qty = $value->reqqty;
          $uom = $value->unit;
          if ($qty == 0) {
            $qty = $value->cvqty;
            $uom = $value->uom;
          }

          $col = [
            'REF' => $value->ref,
            'CODE' => $value->barcode,
            'QTY' => $qty,
            'UOM' => $uom,
            'SPECIFICATION' => $value->specs,
            'BRAND' => $value->brand,
            'DESCRIPTION' => $value->itemdesc
          ];

          $suppliers = $this->coreFunctions->opentable("select ifnull(stock.ref,'') as ref,ifnull(info.itemdesc,'') as itemdesc, 
          stock.reqtrno, stock.reqline, round(ifnull(req.rrqty,0)) as reqqty, round(stock.rrqty) as cvqty,info.unit,stock.uom,head.docno,head.client,head.clientname,round(stock.rrcost,2) as cvcost,
          concat(stock.trno,'~',stock.line) as cvref, stock.line as cdline, stock.rem, ifnull(item.barcode,'') as barcode, stock.isprefer
          from hcdhead as head left join hcdstock as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
          left join hprstock as req on req.trno=info.trno and req.line=info.line left join frontend_ebrands as b on b.brandid=item.brand
          where stock.status=0 and stock.ref=? and stock.reqtrno=? and stock.reqline=? and stock.ismanual=0
          group by stock.ref,info.itemdesc, stock.reqtrno, stock.reqline, stock.rrqty, req.rrqty,info.unit,stock.uom,head.client, head.docno, head.clientname,stock.rrcost,stock.trno,stock.line, 
          stock.rem, stock.ismanual, item.barcode, stock.isprefer
          order by stock.ref,info.itemdesc, head.client", [$value->ref, $value->reqtrno, $value->reqline]);

          foreach ($suppliers as $supp => $s) {
            $col['SUPPLIER~' . $s->client] = $s->clientname;
            $col['REF~' . $s->client] = $s->cvref;
            $col['QTY~' . $s->client] = $s->cvqty;
            $col['AMT~' . $s->client] = $s->cvcost;
            $col['STATUS~' . $s->client] = '';
            $col['REM~' . $s->client] = '';
            $col['PREFERRED~' . $s->client] = $s->isprefer ? 'X' : '';
          }

          array_push($data,  $col);

          $suppliers = $this->coreFunctions->opentable("select ifnull(stock.ref,'') as ref,ifnull(info.itemdesc,'') as itemdesc, 
          stock.reqtrno, stock.reqline, round(ifnull(req.rrqty,0)) as reqqty, round(stock.rrqty) as cvqty,info.unit,stock.uom,head.docno,head.client,head.clientname,round(stock.rrcost,2) as cvcost,
          concat(stock.trno,'~',stock.line) as cvref, stock.line as cdline, stock.rem, ifnull(item.barcode,'') as barcode
          from hcdhead as head left join hcdstock as stock on stock.trno=head.trno
          left join item on item.itemid=stock.itemid left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline
          left join hprstock as req on req.trno=info.trno and req.line=info.line left join frontend_ebrands as b on b.brandid=item.brand
          where stock.status=0 and stock.ref=? and stock.reqtrno=? and stock.reqline=? and stock.ismanual=1
          group by stock.ref,info.itemdesc, stock.reqtrno, stock.reqline, stock.rrqty, req.rrqty,info.unit,stock.uom,head.client, head.docno, head.clientname,stock.rrcost,stock.trno,stock.line, stock.rem, 
          stock.ismanual, item.barcode
          order by stock.ref,info.itemdesc, head.client", [$value->ref, $value->reqtrno, $value->reqline]);

          if (!empty($suppliers)) {
            $col = [
              'REF' => $value->ref,
              'QTY' => $qty,
              'UOM' => $uom,
              'SPECIFICATION' => $value->specs,
              'BRAND' => $value->brand,
              'DESCRIPTION' => $value->itemdesc
            ];

            foreach ($suppliers as $supp => $s) {
              $col['SUPPLIER~' . $s->client] = $s->clientname;
              $col['REF~' . $s->client] = $s->cvref;
              $col['QTY~' . $s->client] = $s->cvqty;
              $col['AMT~' . $s->client] = $s->cvcost;
              $col['STATUS~' . $s->client] = '';
              $col['REM~' . $s->client] = $s->rem;
              $col['CODE'] = $s->barcode;
            }
            array_push($data,  $col);
          }
        }

        return ['status' => true, 'msg' => 'Downloading canvass data...', 'data' => $data, 'name' => 'Canvass'];
        break;
    }
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'uploadexcel':
        return $this->uploadcanvass($config);
        break;
    }
  }

  private function uploadcanvass($config)
  {
    $status = true;
    $msg = '';

    foreach ($config['params']['data'] as $key => $value) {

      $reqqty = $value['QTY'];
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

                if (isset($value["STATUS~" . $supplier])) {
                  switch ($value["STATUS~" . $supplier]) {
                    case 'A':
                      $amt = $value["AMT~" . $supplier];
                      $qty = $value["QTY~" . $supplier];
                      $uom = isset($value["UOM"]) ? $value["UOM"] : '';
                      $status = 1;

                      $amt = $this->othersClass->sanitizekeyfield('amt', $amt);
                      $qty = $this->othersClass->sanitizekeyfield('qty', $qty);

                      $itemid = $this->coreFunctions->getfieldvalue("hcdstock", "itemid", "trno=? and line=?", [$trno, $line]);
                      if ($itemid == '') {
                        $itemid = 0;
                      }

                      $stock = $this->coreFunctions->opentable("select s.reqtrno, s.reqline, uom3.factor, s.uom, s.rrqty2, s.waivedqty, ifnull(info.uom2,'') as uom2 from hcdstock as s left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line 
                      left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1 where s.trno=? and s.line=?", [$trno, $line]);

                      if (!empty($stock)) {
                        $basetotal = $stock[0]->rrqty2 * $stock[0]->factor;

                        if ($basetotal != 0) {
                          if ($stock[0]->waivedqty == 0) {
                            if ($qty > $basetotal) {
                              return ['status' => false, 'msg' => 'Approved quantity must not be greater than request base quantity of ' . number_format($basetotal, 2)];
                            }
                          }
                        } else {
                          // to approved previous canvass
                          $basetotal = $reqqty;
                        }

                        $approvedqty = $this->coreFunctions->datareader("select ifnull(sum(s.qty),0) as value from hcdstock as s where s.approveddate is not null and s.status=1 and s.void=0 and s.waivedqty=0 and s.reqtrno=? and s.reqline=?", [$stock[0]->reqtrno, $stock[0]->reqline], '', true);
                        if ($approvedqty != 0) {


                          if (($approvedqty + $qty) > $basetotal) {
                            return ['status' => false, 'msg' => 'Request quantity of ' . number_format($stock[0]->rrqty2, 2) . ' for item ' . $value['DESCRIPTION'] . ' has already been approved. You are not allow to approve another canvass sheet. 
                                Base quantity approved: ' . number_format($approvedqty, 2) . ' ' . $key['uom'] . ' For approval canvass based quantity: ' . number_format($qty, 2)];
                            goto nextloophere;
                          }
                        }
                      }

                      $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
                      $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
                      $factor = 1;
                      if (!empty($item)) {
                        $item[0]->factor = $this->othersClass->val($item[0]->factor);
                        if ($item[0]->factor !== 0) $factor = $item[0]->factor;
                      } else {
                        if ($stock[0]->uom2 != '') {
                          $factor = $this->coreFunctions->getfieldvalue("uomlist", "factor", "uom=? and isconvert=1", [$stock[0]->uom2], '', true);
                          if ($factor == 0) {
                            $factor = 1;
                          }
                        }
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
                } else {
                  continue;
                }


                $data['status'] = $status;

                $curstatus = $this->coreFunctions->getfieldvalue("hcdstock", "status", "trno=? and line=?", [$trno, $line]);
                if (floatval($curstatus) == 0) {
                  $this->coreFunctions->sbcupdate("hcdstock", $data, ['trno' => $trno, 'line' => $line]);

                  $prref = $this->coreFunctions->opentable("select s.reqtrno,s.reqline,s.refx,s.linex, info.uom2, info.uom3
                        from hcdstock as s left join hstockinfotrans as info on info.trno=s.trno and info.line=s.line where s.trno=? and s.line=? and s.reqtrno<>0", [$trno, $line]);

                  if ($status == 1) {
                    if (!empty($prref)) {
                      $this->coreFunctions->execqry("update hprstock set statrem='Canvass Sheet - Approved',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=?", 'update', [$prref[0]->reqtrno, $prref[0]->reqline]);

                      $this->coreFunctions->execqry("update hprstock set uom='" . $uom . "' where trno=" . $prref[0]->refx . " and line=" . $prref[0]->linex, 'update');
                      $this->coreFunctions->execqry("update hstockinfotrans set uom2='" . $prref[0]->uom2 . "',uom3='" . $prref[0]->uom3 . "' where trno=" . $prref[0]->refx . " and line=" . $prref[0]->linex, 'update');

                      //recompute qty of RR
                      $prdata = $this->coreFunctions->opentable("select pr.itemid, pr.rrqty, pr.rrcost, pr.uom, ifnull(uom.factor,0), info.uom2, info.uom3, ifnull(uom2.factor,0) as factor2, ifnull(uom3.factor,0) as factor3
                                                    from hprstock as pr left join uom on uom.itemid=pr.itemid and uom.uom=pr.uom 
                                                    left join hstockinfotrans as info on info.trno=pr.trno and info.line=pr.line
                                                    left join uomlist as uom2 on uom2.uom=info.uom2 and uom2.isconvert=1
                                                    left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1
                                                    where pr.trno=? and pr.line=?", [$prref[0]->refx, $prref[0]->linex]);
                      if (!empty($prdata)) {
                        if ($prdata[0]->uom3 != '') {
                          if ($prdata[0]->factor3 == 0) {
                            $prdata[0]->factor3 = 1;
                          }

                          $computeprdata = $this->othersClass->computestock($prdata[0]->rrcost, '', $prdata[0]->rrqty, $prdata[0]->factor3);
                          $prdataupdate = [
                            'editby' =>  $config['params']['user'],
                            'editdate' => $this->othersClass->getCurrentTimeStamp(),
                            'qty' => $computeprdata['qty'],
                            'cost' => $computeprdata['amt'],
                            'ext' =>  round($computeprdata['ext'], $this->companysetup->getdecimal('qty', $config['params']))
                          ];

                          $this->coreFunctions->sbcupdate("hprstock", $prdataupdate, ['trno' => $prref[0]->refx, 'line' => $prref[0]->linex]);
                        }
                      }
                    }

                    $qry1 = "";
                    $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $prref[0]->refx . " and stock.linex=" . $prref[0]->linex;
                    $qry1 = $qry1 . " union all select stock.qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $prref[0]->refx . " and stock.linex=" . $prref[0]->linex;
                    $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
                    $cdqa = $this->coreFunctions->datareader($qry2);
                    if ($cdqa == '') {
                      $cdqa = 0;
                    }
                    $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . " where trno=" . $prref[0]->refx . " and line=" . $prref[0]->linex);

                    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Line: ' . $line . ' - APPROVED item ' . $value['DESCRIPTION']);
                  } elseif ($status == 2) {
                    if (!empty($prref)) {
                      $this->coreFunctions->execqry("update hprstock set statrem='Canvass Sheet - Rejected',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=? and statrem<>'Canvass Sheet - Approved'", 'update', [$prref[0]->reqtrno, $prref[0]->reqline]);
                    }

                    $qry1 = "";
                    $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $prref[0]->refx . " and stock.linex=" . $prref[0]->linex;
                    $qry1 = $qry1 . " union all select stock.qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $prref[0]->refx . " and stock.linex=" . $prref[0]->linex;
                    $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
                    $cdqa = $this->coreFunctions->datareader($qry2);
                    if ($cdqa == '') {
                      $cdqa = 0;
                    }
                    $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . " where trno=" . $prref[0]->refx . " and line=" . $prref[0]->linex);

                    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'Line: ' . $line . ' - REJECTED item ' . $value['DESCRIPTION']);

                    $pending = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hcdstock where trno=? and status<>2", [$trno], '', true);
                    if ($pending == 0) $this->coreFunctions->execqry("update transnum set statid=77 where trno=" . $trno);
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
      nextloophere:
    }

    $griddata = [];
    if ($msg == '') {
      $status = true;
      $msg = 'Successfully uploaded.';
      $griddata = $this->loaddata($config);
    } else {
      $status = false;
    }

    return ['status' => $status, 'msg' => $msg, 'reloadtableentry' => true, 'griddata' => $griddata, 'closemodal' => true];
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
          if (isset($value["STATUS~" . $supplier])) {
            switch ($value["STATUS~" . $supplier]) {
              case 'A':
                $approved += 1;
                $qty += $value["QTY~" . $supplier];
                $this->othersClass->logConsole('CD qty: ' . $value["QTY~" . $supplier]);
                break;
            }
          } else {
            continue;
          }
        }
      }
    }

    if ($approved > 1) {

      if ($qty > $reqqty) {

        return ['status' => true, 'msg' => "Total approved quantity of " . $qty . " must not be greater than request quantity of " . $reqqty];
      }
    }

    return ['status' => false, 'msg' => ''];
  }
} //end class
