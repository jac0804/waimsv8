<?php

namespace App\Http\Classes\modules\warehousing;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;

class pl
{

  public $modulename = 'PACKING LIST';
  public $gridname = 'inventory';

  private $btnClass;
  private $fieldClass;
  private $tabClass;

  private $companysetup;
  private $coreFunctions;
  private $othersClass;

  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];

  public $tablenum = 'transnum';
  public $head = 'plhead';
  public $hhead = 'hplhead';
  public $stock = 'plstock';
  public $hstock = 'hplstock';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';

  private $fields = ['trno', 'docno', 'dateid', 'rem', 'plno', 'shipmentno', 'invoiceno', 'yourref'];
  private $except = ['trno'];

  public $dqty = 'rrqty';
  public $hqty = 'qty';

  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;

  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 1860,
      'edit' => 1861,
      'new' => 1862,
      'save' => 1863,
      // 'change' => 1864, remove change doc
      'delete' => 1865,
      'print' => 1866,
      'lock' => 1867,
      'unlock' => 1868,
      'post' => 1870,
      'unpost' => 1871,
      'additem' => 1872,
      'edititem' => 1873,
      'deleteitem' => 1874
    );
    return $attrib;
  }

  public function createdoclisting()
  {
    $yourref = 6;
    $postdate = 7;
    $getcols = [
      'action', 'liststatus', 'listdocument', 'listdate', 'plno', 'shipmentno', 'yourref',
      'postdate', 'listpostedby', 'listcreateby', 'listeditby', 'listviewby'
    ];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';

    $cols[$yourref]['label'] = 'PO No.';
    $cols[$yourref]['align'] = 'text-left';
    $cols[$yourref]['name'] = 'yourref';
    $cols[$postdate]['label'] = 'Post Date';
    return $cols;
  }

  public function loaddoclisting($config)
  {

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $itemfilter = $config['params']['itemfilter'];
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $condition = '';
    $searchfilter = $config['params']['search'];
    $limit = "limit 150";

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.plno', 'head.shipmentno', 'head.yourref', 'num.postedby', 'head.createby', 'head.editby', 'head.viewby'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }

    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }
    $qry = "select head.trno,head.docno,left(head.dateid,10) as dateid, 'DRAFT' as status,
     head.createby,head.editby,head.viewby,num.postedby,
     head.plno, head.shipmentno, head.invoiceno, head.yourref, date(num.postdate) as postdate
     from " . $this->head . " as head 
     left join " . $this->tablenum . " as num on num.trno=head.trno 
     where head.doc=? and num.center=? and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? 
     " . $condition . " " . $filtersearch . "
     union all
     select head.trno,head.docno,left(head.dateid,10) as dateid,'POSTED' as status, 
     head.createby,head.editby,head.viewby, num.postedby,
     head.plno, head.shipmentno, head.invoiceno, head.yourref, date(num.postdate) as postdate
     from " . $this->hhead . " as head left join " . $this->tablenum . " as num on num.trno=head.trno 
     where head.doc=? and num.center=? and convert(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? 
     " . $condition . " " . $filtersearch . "
     order by dateid desc,docno desc " . $limit;

    $data = $this->coreFunctions->opentable($qry, [$doc, $center, $date1, $date2, $doc, $center, $date1, $date2]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'post',
      'unpost',
      'lock',
      'unlock',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown',
      'help'
    );

    $buttons = $this->btnClass->create($btns);
    $step1 = $this->helpClass->getFields(['btnnew', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step2 = $this->helpClass->getFields(['btnedit', 'supplier', 'dateid', 'terms', 'cswhname', 'yourref', 'cur', 'csrem', 'btnsave']);
    $step3 = $this->helpClass->getFields(['btnadditem', 'btnquickadd', 'rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step4 = $this->helpClass->getFields(['rrqty', 'uom', 'rrcost', 'disc', 'wh', 'rem', 'btnstocksave', 'btnsaveitem']);
    $step5 = $this->helpClass->getFields(['btnstockdelete', 'btndeleteallitem']);
    $step6 = $this->helpClass->getFields(['btndelete']);


    $buttons['help']['items'] = [
      'create' => ['label' => 'How to create New Document', 'action' => $step1],
      'edit' => ['label' => 'How to edit details from the header', 'action' => $step2],
      'additem' => ['label' => 'How to add item/s', 'action' => $step3],
      'edititem' => ['label' => 'How to edit item details', 'action' => $step4],
      'deleteitem' => ['label' => 'How to delete item/s', 'action' => $step5],
      'deletehead' => ['label' => 'How to delete whole transaction', 'action' => $step6]
    ];
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $tab = [
      $this->gridname => [
        'gridcolumns' => ['action', 'docno', 'dateid', 'clientname', 'rem'],
        'headgridbtns' => ['viewref', 'viewdiagram']
      ]
    ];

    $stockbuttons = ['delete', 'showpackinglist'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['inventory']['label'] = 'PO List';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][1]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][4]['type'] = 'label';

    $obj[0][$this->gridname]['headgridbtns']['viewref']['label'] = 'ITEM DETAILS';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['pendingpo'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {

    $fields = ['docno', 'dateid', 'yourref'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'yourref.label', 'PO No.');

    $fields = ['rem'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['plno', 'shipmentno', 'invoiceno'];
    $col3 = $this->fieldClass->create($fields);

    return ['col1' => $col1, 'col2' => $col2, 'col3' => $col3];
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['rem'] = '';
    $data[0]['plno'] = '';
    $data[0]['shipmentno'] = '';
    $data[0]['invoiceno'] = '';
    $data[0]['yourref'] = '';
    return $data;
  }

  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $center = $config['params']['center'];
    $trno = $config['params']['trno'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile('TRNO', $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile('TRNO', $trno, $config);
    }
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);
    $table = $this->head;
    $htable = $this->hhead;
    $tablenum = $this->tablenum;
    $qryselect = "select
     num.center,
     head.trno,
     head.docno,
     left(head.dateid,10) as dateid,
     date_format(head.createdate,'%Y-%m-%d') as createdate,
     head.rem,head.plno,head.shipmentno,head.invoiceno,
     head.yourref ";

    $qry = $qryselect . " from $table as head
      left join $tablenum as num on num.trno = head.trno
      where head.trno = ? and num.center = ?
      union all " . $qryselect . " from $htable as head
      left join $tablenum as num on num.trno = head.trno
      where head.trno = ? and num.center=? ";

    $head = $this->coreFunctions->opentable($qry, [$trno, $center, $trno, $center]);
    if (!empty($head)) {
      $stock = $this->openstock($trno, $config);
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $data = [];
    if ($isupdate) {
      unset($this->fields[1]);
      unset($head['docno']);
    }
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if
      }
    }
    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
    } else {
      $data['doc'] = $config['params']['doc'];
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];
      $this->coreFunctions->sbcinsert($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno']);
    }
  } // end function

  private function getstockselect($config)
  {
    $sqlselect = "select
          pl.trno, poh.docno, poh.clientname, date(poh.dateid) as dateid, poh.rem, pl.refx, pl.suppid, '' as bgcolor ";
    return $sqlselect;
  }

  public function openstock($trno, $config)
  {
    $sqlselect = $this->getstockselect($config);

    $qry = $sqlselect . "
          from $this->stock as pl 
          left join hpostock as po on po.trno=pl.refx and po.line=pl.linex left join 
          hpohead as poh on poh.trno=po.trno
          where pl.trno = ? group by poh.docno, poh.clientname, poh.dateid, poh.rem, pl.refx, pl.trno, pl.suppid
          UNION ALL
          " . $sqlselect . "
          from $this->hstock as pl left join hpostock as po on po.trno=pl.refx and po.line=pl.linex left join hpohead as poh on poh.trno=po.trno
          where pl.trno = ? group by poh.docno, poh.clientname, poh.dateid, poh.rem, pl.refx, pl.trno, pl.suppid";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $trno]);
    return $stock;
  } //end function

  public function stockstatus($config)
  {
    switch ($config['params']['action']) {
      case 'getposummary':
        return $this->getposummary($config);
        break;

      case 'getpodetails':
        return $this->getpodetails($config);
        break;

      case 'deleteitem':
        return $this->deleteitem($config);
        break;

      default:
        return ['status' => 'false', 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }

  private function getpendingpoquery($config)
  {
    return "
            select head.docno, head.clientname, date(head.dateid) as dateid, head.rem, item.itemid,stock.trno,
            stock.line, item.barcode,stock.uom, stock.cost,
            (stock.qty-stock.qa) as qty,stock.rrcost,
            round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
            stock.disc,stock.stageid,client.clientid as suppid
            FROM hpohead as head left join hpostock as stock on stock.trno=head.trno left join item on item.itemid=
            stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
            left join client on client.client = head.client
            where stock.trno = ? and stock.qty>stock.qa and stock.void=0
        ";
  }

  public function getposummary($config)
  {
    $trno = $config['params']['trno'];
    $rows = [];
    $return_rows = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = $this->getpendingpoquery($config);
      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno']]);
      if (!empty($data)) {
        $insert = [];
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['data']['trno'] = $trno;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['rrcost'] = $data[$key2]->rrcost;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['suppid'] = $data[$key2]->suppid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach

    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function getpodetails($config)
  {

    $trno = $config['params']['trno'];
    $rows = [];
    $return_data = [];
    foreach ($config['params']['rows'] as $key => $value) {
      $qry = "
      select head.docno, head.clientname, date(head.dateid) as dateid, head.rem, item.itemid,stock.trno,
      stock.line, item.barcode,stock.uom, stock.cost,
      (stock.qty-stock.qa) as qty,stock.rrcost,
      round((stock.qty-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end," . $this->companysetup->getdecimal('qty', $config['params']) . ") as rrqty,
      stock.disc,stock.stageid,client.clientid as suppid
      FROM hpohead as head left join hpostock as stock on stock.trno=head.trno left join item on item.itemid=
      stock.itemid left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
      left join client on client.client = head.client
      where stock.trno = ? and stock.line=? and stock.qty>stock.qa and stock.void=0
      ";

      $data = $this->coreFunctions->opentable($qry, [$config['params']['rows'][$key]['trno'], $config['params']['rows'][$key]['line']]);
      if (!empty($data)) {
        foreach ($data as $key2 => $value) {
          $config['params']['data']['uom'] = $data[$key2]->uom;
          $config['params']['data']['itemid'] = $data[$key2]->itemid;
          $config['params']['data']['trno'] = $trno;
          $config['params']['data']['qty'] = $data[$key2]->rrqty;
          $config['params']['data']['disc'] = $data[$key2]->disc;
          $config['params']['data']['rrcost'] = $data[$key2]->rrcost;
          $config['params']['data']['refx'] = $data[$key2]->trno;
          $config['params']['data']['linex'] = $data[$key2]->line;
          $config['params']['data']['ref'] = $data[$key2]->docno;
          $config['params']['data']['suppid'] = $data[$key2]->suppid;
          $return = $this->additem('insert', $config);
          if ($return['status']) {
            if ($this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty) == 0) {
              $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
              $line = $return['row'][0]->line;
              $config['params']['trno'] = $trno;
              $config['params']['line'] = $line;
              $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
              $this->othersClass->setserveditemsRR($data[$key2]->trno, $data[$key2]->line, $this->hqty);
              $row = $this->openstockline($config);
              $return = ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
            }
            array_push($rows, $return['row'][0]);
          }
        } // end foreach
      } //end if
    } //end foreach

    return ['row' => $rows, 'status' => true, 'msg' => 'Items were successfully added.'];
  } //end function

  public function additem($action, $config)
  {
    $uom = $config['params']['data']['uom'];
    $itemid = $config['params']['data']['itemid'];
    $trno = $config['params']['trno'];
    $disc = $config['params']['data']['disc'];
    $rrcost = $config['params']['data']['rrcost'];
    $suppid = $config['params']['data']['suppid'];
    $refx = 0;
    $linex = 0;
    $fcost = 0;
    $ref = '';
    $stageid = 0;
    if (isset($config['params']['data']['refx'])) {
      $refx = $config['params']['data']['refx'];
    }
    if (isset($config['params']['data']['linex'])) {
      $linex = $config['params']['data']['linex'];
    }
    if (isset($config['params']['data']['ref'])) {
      $ref = $config['params']['data']['ref'];
    }
    $line = 0;

    if ($action == 'insert') {
      $qry = "select line as value from " . $this->stock . " where trno=? order by line desc limit 1";
      $line = $this->coreFunctions->datareader($qry, [$trno]);

      if ($line == '') {
        $line = 0;
      }

      $line = $line + 1;
      $config['params']['line'] = $line;
      $config['params']['refx'] = $refx;
      $qty = $config['params']['data']['qty'];
    } elseif ($action == 'update') {
      $config['params']['line'] = $config['params']['data']['line'];
      $line = $config['params']['data']['line'];
      $qty = $config['params']['data'][$this->dqty];
      $config['params']['line'] = $line;
      $config['params']['refx'] = $refx;
    }

    $qry = "select item.barcode,item.itemname,ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
    $item = $this->coreFunctions->opentable($qry, [$uom, $itemid]);
    $qty = $this->othersClass->sanitizekeyfield('qty', $qty);
    $amt = $rrcost;
    $factor = 1;
    if (!empty($item)) {
      $item[0]->factor = $this->othersClass->val($item[0]->factor);
      if ($item[0]->factor !== 0) $factor = $item[0]->factor;
    }

    $computedata = $this->othersClass->computestock($amt, $disc, $qty, $factor);
    $data = [
      'trno' => $trno,
      'line' => $line,
      'itemid' => $itemid,
      $this->dqty => $qty,
      $this->hqty => $computedata['qty'],
      'rrcost' => $amt,
      'cost' => $computedata['amt'],
      'ext' => $computedata['ext'],
      'refx' => $refx,
      'linex' => $linex,
      'ref' => $ref,
      'uom' => $uom,
      'suppid' => $suppid
    ];

    foreach ($data as $key => $value) {
      $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
    }
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();
    $data['editdate'] = $current_timestamp;
    $data['editby'] = $config['params']['user'];

    if ($action == 'insert') {
      $data['encodeddate'] = $current_timestamp;
      $data['encodedby'] = $config['params']['user'];
      if ($this->coreFunctions->sbcinsert($this->stock, $data) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'STOCK', 'ADD - Line:' . $line . ' barcode:' . $item[0]->barcode);
        $row = $this->openstockline($config);

        return ['row' => $row, 'status' => true, 'msg' => 'Item was successfully added.'];
      } else {
        return ['status' => false, 'msg' => 'Add item Failed'];
      }
    } elseif ($action == 'update') {
      $return = true;
      $this->coreFunctions->sbcupdate($this->stock, $data, ['trno' => $trno, 'line' => $line]);
      if ($this->othersClass->setserveditemsRR($refx, $linex, $this->hqty) === 0) {
        $data2 = [$this->dqty => 0, $this->hqty => 0, 'ext' => 0];
        $this->coreFunctions->sbcupdate($this->stock, $data2, ['trno' => $trno, 'line' => $line]);
        $this->othersClass->setserveditemsRR($refx, $linex, $this->hqty);
        $return = false;
      }
      return $return;
    }
  } // end function

  public function openstockline($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['refx'];
    $qry = $sqlselect . "
      from $this->stock as pl left join hpostock as po on po.trno=pl.refx and po.line=pl.linex left join hpohead as poh on poh.trno=po.trno
      where pl.trno = ? and pl.refx = ? ";

    $stock = $this->coreFunctions->opentable($qry, [$trno, $line]);
    return $stock;
  } // end function

  public function openstockdetail($config)
  {
    $sqlselect = $this->getstockselect($config);
    $trno = $config['params']['trno'];
    $line = $config['params']['refx'];
    $qry = "select qty, refx, linex, trno, line, ref from $this->stock  where trno = ? and refx = ? ";
    return $this->coreFunctions->opentable($qry, [$trno, $line]);
  } // end function

  public function deleteitem($config)
  {
    $config['params']['trno'] = $config['params']['row']['trno'];
    $config['params']['refx'] = $config['params']['row']['refx'];
    $trno = $config['params']['trno'];

    $data = $this->openstockdetail($config);

    foreach ($data as $key => $value) {
      $qry = "delete from " . $this->stock . " where trno=? and line=?";
      $this->coreFunctions->execqry($qry, 'delete', [$value->trno, $value->line]);

      if ($value->refx !== 0) {
        $this->othersClass->setserveditemsRR($value->refx, $value->linex, $this->hqty);
      }

      $data = json_decode(json_encode($data), true);

      $this->logger->sbcwritelog($trno, $config, 'STOCK', 'REMOVED - Line:' . $value->line . ' PO#:' . $data[0]['ref']);
    }

    return ['status' => true, 'msg' => 'Successfully deleted.'];
  } // end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->stock . " where trno=? and qty=0 limit 1";
    $isitemzeroqty = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($isitemzeroqty)) {
      return ['status' => false, 'msg' => 'Posting failed. Check carefully, some items have zero quantity.'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    if ($this->othersClass->isposted($config)) {
      return ['status' => false, 'msg' => 'Posting failed. Transaction has already been posted.'];
    }
    //for head
    $qry = "insert into " . $this->hhead . "(trno,doc,docno,dateid,rem,createdate,createby,editby,editdate,lockdate,lockuser,plno,shipmentno,invoiceno, yourref)
          SELECT head.trno,head.doc, head.docno, head.dateid, head.rem,
          head.createdate,head.createby,head.editby,head.editdate, head.lockdate,head.lockuser,head.plno,head.shipmentno,head.invoiceno, head.yourref
          FROM " . $this->head . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
          where head.trno=? limit 1";
    $posthead = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($posthead) {
      // for stock
      $qry = "insert into " . $this->hstock . "(trno,line,itemid,uom,
            whid,loc,ref,disc,cost,qty,void,rrcost,rrqty,ext,
            encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,rem,suppid)
            SELECT trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void,rrcost, rrqty, ext,
            encodeddate,qa, encodedby,editdate,editby,sku,refx,linex,rem,suppid FROM " . $this->stock . " where trno =?";
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        //update transnum
        $date = $this->othersClass->getCurrentTimeStamp();
        $data = ['postdate' => $date, 'postedby' => $config['params']['user']];
        $this->coreFunctions->sbcupdate($this->tablenum, $data, ['trno' => $trno]);
        $this->coreFunctions->execqry("delete from " . $this->stock . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
        $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'Error on Posting stock'];
      }
    } else {
      return ['status' => false, 'msg' => 'Error on Posting Head'];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $qry = "select trno from " . $this->hstock . " where trno=? and (qa>0 or void<>0)";
    $data = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($data)) {
      return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, either already served or have item voided...'];
    }
    $docno = $this->coreFunctions->datareader('select docno as value from ' . $this->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . "(trno,doc,docno,dateid,rem,createdate,createby,editby,editdate,lockdate,lockuser,plno,shipmentno,invoiceno, yourref)
      select head.trno, head.doc, head.docno, head.dateid, head.rem, head.createdate,
      head.createby, head.editby, head.editdate, head.lockdate, head.lockuser,head.plno,head.shipmentno,head.invoiceno, head.yourref
      from " . $this->hhead . " as head left join " . $this->tablenum . " as cntnum on cntnum.trno=head.trno
      where head.trno=? limit 1";
    //head
    if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
      $qry = "insert into " . $this->stock . "(
          trno,line,itemid,uom,whid,loc,ref,disc,
          cost,qty,void,rrcost,rrqty,ext,rem,encodeddate,qa,encodedby,editdate,editby,sku,refx,linex,suppid)
          select trno, line, itemid, uom,whid,loc,ref,disc,cost, qty,void, rrcost, rrqty,
          ext,rem, encodeddate, qa, encodedby, editdate, editby,sku,refx,linex,suppid
          from " . $this->hstock . " where trno=?";
      //stock
      if ($this->coreFunctions->execqry($qry, 'insert', [$trno])) {
        $this->coreFunctions->execqry("update " . $this->tablenum . " set postdate=null where trno=?", 'update', [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hhead . " where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from " . $this->hstock . " where trno=?", "delete", [$trno]);
        $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
        return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
      } else {
        $this->coreFunctions->execqry("delete from " . $this->head . " where trno=?", 'delete', [$trno]);
        return ['trno' => $trno, 'status' => false, 'msg' => 'UNPOST FAILED, stock problems...'];
      }
    }
  } //end function

  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $table = $config['docmodule']->tablenum;
    $docno = $this->coreFunctions->datareader("select docno as value from " . $table . ' where trno=?', [$trno]);
    $qry = "select trno as value from " . $this->tablenum . " where doc=? and trno<? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$doc, $trno]);
    $this->deleteallitem($config);
    $this->coreFunctions->execqry('delete from ' . $this->head . " where trno=?", 'delete', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->tablenum . " where trno=?", 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function deleteallitem($config)
  {

    $trno = $config['params']['trno'];
    $data = $this->coreFunctions->opentable('select refx,linex from ' . $this->stock . ' where trno=? and refx<>0', [$trno]);
    $this->coreFunctions->execqry('delete from ' . $this->stock . ' where trno=?', 'delete', [$trno]);
    foreach ($data as $key => $value) {
      $this->othersClass->setserveditemsRR($value->refx,  $value->linex, $this->hqty);
    }
    $this->logger->sbcwritelog($trno, $config, 'STOCK', 'DELETED ALL ITEMS');
    return ['status' => true, 'msg' => 'Successfully deleted.', 'inventory' => []];
  }

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'diagram':
        return $this->diagram($config);
        break;
      case 'exportcsv':
        return ['status' => true, 'msg' => 'Successfully exported.', 'filename' => 'xxx', 'ext' => 'txt', 'csv' => 'abc' . "\t" . 'def' . "\t" . 'ghi' . "\t"];
        break;
      case 'print1':
        return $this->reportsetup($config);
        break;
      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }

  public function diagram($config)
  {

    $data = [];
    $nodes = [];
    $links = [];
    $data['width'] = 1500;
    $startx = 100;

    $qry = "select po.trno,po.docno,left(po.dateid,10) as dateid,
      CAST(concat('Total PO Amt: ',round(sum(s.ext),2)) as CHAR) as rem,s.refx
      from hpohead as po
      left join hpostock as s on s.trno = po.trno
      left join hplstock as plstock on plstock.refx = po.trno and plstock.linex = s.line
      where plstock.trno = ?
      group by po.trno,po.docno,po.dateid,s.refx";
    $t = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
    if (!empty($t)) {
      $startx = 550;
      $a = 0;
      foreach ($t as $key => $value) {
        //PO
        data_set(
          $nodes,
          $t[$key]->docno,
          [
            'align' => 'right',
            'x' => 200,
            'y' => 50 + $a,
            'w' => 250,
            'h' => 80,
            'type' => $t[$key]->docno,
            'label' => $t[$key]->rem,
            'color' => 'blue',
            'details' => [$t[$key]->dateid]
          ]
        );
        array_push($links, ['from' => $t[$key]->docno, 'to' => 'rr']);
        $a = $a + 100;

        //PL
        $qry = "select head.docno,left(head.dateid,10) as dateid,
          CAST(concat('Total PL Amt: ',round(sum(s.ext),2)) as CHAR) as rem
          from hplhead as head 
          left join hplstock as s on s.trno = head.trno
          where head.trno = ?
          group by head.docno,head.dateid";
        $x = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);
        $poref = $t[$key]->docno;
        if (!empty($x)) {
          foreach ($x as $key2 => $value1) {
            data_set(
              $nodes,
              $x[$key2]->docno,
              [
                'align' => 'left',
                'x' => 600,
                'y' => 50 + $a,
                'w' => 250,
                'h' => 80,
                'type' => $x[$key2]->docno,
                'label' => $x[$key2]->rem,
                'color' => 'yellow',
                'details' => [$x[$key2]->dateid]
              ]
            );
            array_push($links, ['from' => $x[$key2]->docno, 'to' => $poref]);
            $a = $a + 100;

            // RP
            $qry = "select head.docno,left(head.dateid,10) as dateid,
              CAST(concat('Total RP Amt: ',round(sum(s.ext),2)) as CHAR) as rem
              from lahead as head 
              left join lastock as s on s.trno = head.trno
              where s.refx = ?
              group by head.docno,head.dateid
              union all 
              select head.docno,left(head.dateid,10) as dateid,
              CAST(concat('Total RP Amt: ',round(sum(s.ext),2)) as CHAR) as rem
              from glhead as head 
              left join glstock as s on s.trno = head.trno
              where s.refx = ?
              group by head.docno,head.dateid";
            $rpdata = $this->coreFunctions->opentable($qry, [$config['params']['trno'], $config['params']['trno']]);
            if (!empty($rpdata)) {
              foreach ($rpdata as $key3 => $value2) {
                data_set(
                  $nodes,
                  $rpdata[$key3]->docno,
                  [
                    'align' => 'left',
                    'x' => 800,
                    'y' => 100 + $a,
                    'w' => 250,
                    'h' => 80,
                    'type' => $rpdata[$key3]->docno,
                    'label' => $rpdata[$key3]->rem,
                    'color' => 'green',
                    'details' => [$rpdata[$key3]->dateid]
                  ]
                );
                array_push($links, ['from' => $rpdata[$key3]->docno, 'to' => $x[$key2]->docno]);
                $a = $a + 100;
              }
            }
          }
        }
      }
    }

    $data['nodes'] = $nodes;
    $data['links'] = $links;

    return ['status' => true, 'msg' => 'Successfully fetched.', 'data' => $data];
  }

  public function reportsetup($config)
  {
    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    $this->logger->sbcviewreportlog($config);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }
}
