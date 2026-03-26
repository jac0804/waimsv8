<?php

namespace App\Http\Classes\lookup;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use App\Http\Classes\companysetup;

class warehousinglookup
{
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;
  private $companysetup;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
    $this->companysetup = new companysetup;
  }

  public function pendingplsummary($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'trno',
      'title' => 'List of Pending Packing List Summary',
      'btns' => [],
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('yourref' => 'docno', 'pltrno' => 'trno', 'plno' => 'plno', 'shipmentno' => 'shipmentno', 'invoiceno' => 'invoiceno')
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'plno', 'label' => 'Packing List No.', 'align' => 'left', 'field' => 'plno', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'shipmentno', 'label' => 'Shipment No.', 'align' => 'left', 'field' => 'shipmentno', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'invoiceno', 'label' => 'Proforma Invoice No.', 'align' => 'left', 'field' => 'invoiceno', 'sortable' => true, 'style' => 'font-size:16px;'));

    $data = $this->sqlquery->getpendingplsummary($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingwasummary($config)
  {
    $summary = ['summary' => ['label' => 'Show Details', 'lookupclass' => 'lookupsetup', 'action' => 'pendingwadetail']];

    if ($config['params']['doc'] == 'PL') {
      $summary = [];
    }

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'trno',
      'title' => 'List of Pending Warranty Request Summary',
      'btns' => $summary,
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getwasummary'
    );

    // lookup columns
    $cols = array();
    array_push($cols, array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'clientname', 'label' => 'Supplier Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'rem', 'label' => 'Notes', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;'));

    $data = $this->sqlquery->getpendingwasummary($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  //A
  public function pendingwadetail($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Pending Warrany Request Details',
      'btns' => [
        'summary' =>
        ['label' => 'Show Summary', 'lookupclass' => 'lookupsetup', 'action' => 'pendingwasummary']
      ],
      'style' => 'width:1200px;max-width:1200px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getwadetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrcost', 'label' => 'Amount', 'align' => 'left', 'field' => 'rrcost', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'disc', 'label' => 'Disc', 'align' => 'left', 'field' => 'disc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrqty', 'label' => 'OrderQty', 'align' => 'left', 'field' => 'rrqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'qa', 'label' => 'Served', 'align' => 'left', 'field' => 'qa', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pending', 'label' => 'Pending', 'align' => 'left', 'field' => 'pending', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $data = $this->sqlquery->getpendingwadetails($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingshsummary($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'trno',
      'title' => 'List of Special Parts Issuance Summary',
      'btns' => ['summary' =>
      ['label' => 'Show Details', 'lookupclass' => 'lookupsetup', 'action' => 'pendingshdetail']],
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getsjsummary'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $data = $this->sqlquery->getpendingshsummary($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingshdetail($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Special Parts Issuance Details',
      'btns' => ['summary' =>
      ['label' => 'Show Summary', 'lookupclass' => 'lookupsetup', 'action' => 'pendingshsummary']],
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getsjdetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'isamt', 'label' => 'Amount', 'align' => 'left', 'field' => 'isamt', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'disc', 'label' => 'Disc', 'align' => 'left', 'field' => 'disc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'isqty', 'label' => 'Quantity', 'align' => 'left', 'field' => 'isqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'loc', 'label' => 'Location', 'align' => 'left', 'field' => 'loc', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'expiry', 'label' => 'Expiration', 'align' => 'left', 'field' => 'expiry', 'sortable' => true, 'style' => 'font-size:16px;')
    );


    $data = $this->sqlquery->getpendingshdetails($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingpartssummary($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'trno',
      'title' => 'List of Special Parts Request Summary',
      'btns' => ['summary' =>
      ['label' => 'Show Details', 'lookupclass' => 'lookupsetup', 'action' => 'pendingpartsdetail']],
      'style' => 'width:800px;max-width:800px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getpartssummary'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $data = $this->sqlquery->getpendingpartssummary($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingpartsdetail($config)
  {
    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Special Parts Request Details',
      'btns' => ['summary' =>
      ['label' => 'Show Summary', 'lookupclass' => 'lookupsetup', 'action' => 'pendingpartssummary']],
      'style' => 'width:1200px;max-width:1200px;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getapartsdetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'dateid', 'label' => 'Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'rrqty', 'label' => 'Quantity', 'align' => 'left', 'field' => 'isqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pending', 'label' => 'Pending', 'align' => 'left', 'field' => 'pending', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $data = $this->sqlquery->getpendingpartsdetails($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingsplitqtydetail($config)
  {
    $trno = $config['params']['trno'];
    $client = $config['params']['client'];

    $whid = $this->coreFunctions->datareader("select ifnull(client.clientid,0) as value from lahead as h left join client on client.client=h.client where h.trno=? and client.client=?", [$trno, $client]);
    if ($whid == 0) {
      return ['status' => false, 'msg' => 'Invalid warehouse', 'data' => []];
    }

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Pending Split Quantity Details',
      'btns' => [],
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getsplitqtydetails'
    );

    // lookup columns
    $cols = array(
      array('name' => 'docno', 'label' => 'Document#', 'align' => 'left', 'field' => 'docno', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'splitqty', 'label' => 'Split Qty', 'align' => 'left', 'field' => 'splitqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'uom', 'label' => 'Unit', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pallet', 'label' => 'Source Pallet', 'align' => 'left', 'field' => 'pallet', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'location', 'label' => 'Source Location', 'align' => 'left', 'field' => 'location', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'location2', 'label' => 'Destination Location', 'align' => 'left', 'field' => 'location2', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $config['params']['whid'] = $whid;
    $data = $this->sqlquery->getpendingsplitqtydetails($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function pendingsplitqtypicker($config)
  {
    $trno = $config['params']['trno'];

    $whid = $this->coreFunctions->datareader("select ifnull(client.clientid,0) as value from lahead as h left join client on client.client=h.wh where h.trno=?", [$trno]);
    if ($whid == 0) {
      return ['status' => false, 'msg' => 'Invalid warehouse', 'data' => []];
    }

    $lookupsetup = array(
      'type' => 'multi',
      'rowkey' => 'keyid',
      'title' => 'List of Pending Picker For Return/Adjustments',
      'btns' => [],
      'style' => 'width:100%;max-width:100%;'
    );

    $plotsetup = array(
      'plottype' => 'callback',
      'action' => 'getpendingpickeradj'
    );

    // lookup columns
    $cols = array(
      array('name' => 'isqty', 'label' => 'Qty', 'align' => 'left', 'field' => 'isqty', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'uom', 'label' => 'Unit', 'align' => 'left', 'field' => 'uom', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'pallet', 'label' => 'Pallet', 'align' => 'left', 'field' => 'pallet', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'location', 'label' => 'Location', 'align' => 'left', 'field' => 'location', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'clientname', 'label' => 'Warehouse Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'ren', 'label' => 'Remarks', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $config['params']['whid'] = $whid;
    $data = $this->sqlquery->pendingsplitqtypicker($config);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuppallet($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'line',
      'title' => 'List of Pallets',
      'btns' => [],
      'style' => 'width:800px;max-width:800px;'
    );


    $cols = array();
    array_push($cols, array('name' => 'name', 'label' => 'Pallet', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'));

    $qry = "select line, name, locid from pallet order by name";
    $data = $this->coreFunctions->opentable($qry);

    $btnadd = $this->sqlquery->checksecurity($config, 1875, '/tableentries/warehousingentry/entrypallet');

    $plotting = array();
    $plottype = 'plotgrid';
    switch ($config['params']['lookupclass']) {
      case 'palletstock2':
        $plotting = array('pallet2' => 'name', 'palletid2' => 'line');
        break;

      case 'pallethead2':
        $plottype = 'plothead';
        $plotting = array('pallet2' => 'name', 'palletid2' => 'line');
        break;

      default:
        $plotting = array('pallet' => 'name', 'palletid' => 'line');
        break;
    }

    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => $plotting
    );
    switch ($plottype) {
      case 'plothead':
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'btnadd' => $btnadd];
        break;
      default:
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex, 'btnadd' => $btnadd];
        break;
    }
  }

  public function lookuplocation($config)
  {
    if (isset($config['params']['addedparams'])) {
      $whname = $config['params']['addedparams'][1];
      $whid = $config['params']['addedparams'][0];
    } else {
      $whname = $config['params']['row']['whname'];
      $whid = $config['params']['row']['whid'];
    }

    $trno = 0;
    $dwhid = 0;
    $dwhname = '';

    if (isset($config['params']['row']['trno'])) {
      $trno = $config['params']['row']['trno'];

      $dwhid = $this->coreFunctions->datareader("
      select wh.clientid as value from lahead as head
      left join client as wh on wh.client = head.client
      where trno = ?
      union all 
      select wh.clientid as value from glhead as head
      left join client as wh on wh.clientid = head.clientid
      where trno = ?", [$trno, $trno]);

      $dwhname = $this->coreFunctions->datareader("
      select wh.clientname as value from lahead as head
      left join client as wh on wh.client = head.client
      where trno = ?
      union all 
      select wh.clientname as value from glhead as head
      left join client as wh on wh.clientid = head.clientid
      where trno = ?", [$trno, $trno]);
    }

    switch ($config['params']['lookupclass']) {
      case 'locstock2':
        $whname = $dwhname;
        break;
    }

    $lookupsetup = array(
      'type' => 'single',
      'rowkey' => 'line',
      'title' => 'List of Locations (' . $whname . ')',
      'btns' => [],
      'style' => 'width:800px;max-width:800px;'
    );

    $plotting = array();
    $plottype = 'plotgrid';
    switch ($config['params']['lookupclass']) {
      case 'locstock2':
        $plotting = array('location2' => 'loc', 'locid2' => 'line');
        break;

      case 'locationhead2':
        $plottype = 'plothead';
        $plotting = array('location2' => 'loc', 'locid2' => 'line');
        break;

      default:
        $plotting = array('location' => 'loc', 'locid' => 'line');
        break;
    }

    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => $plotting
    );

    $cols = array();
    array_push($cols, array('name' => 'loc', 'label' => 'Location', 'align' => 'left', 'field' => 'loc', 'sortable' => true, 'style' => 'font-size:16px;'));


    $qry = "select line, floor, lane, level, loc, whid from location where whid=? order by loc";

    switch ($config['params']['lookupclass']) {
      case 'locstock2':
        $data = $this->coreFunctions->opentable($qry, [$dwhid]);
        break;

      default:
        $data = $this->coreFunctions->opentable($qry, [$whid]);
        break;
    }

    switch ($plottype) {
      case 'plothead':
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
        break;

      default:
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
        break;
    }
  }

  public function lookuplocationenterqty($config)
  {
    $plotting = array('location' => 'location', 'locid' => 'locid');
    $plottype = 'plotemit';
    $title = 'List of Location';
    switch ($config['params']['lookupclass']) {
      case 'palletstock':
        $plottype = 'plotgrid';
        break;
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'location', 'label' => 'Location', 'align' => 'left', 'field' => 'location', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'bal', 'label' => 'Balance', 'align' => 'left', 'field' => 'bal', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select location.line as locid,location.loc as location,sum(rrstatus.bal) as bal from location left join rrstatus on rrstatus.locid=location.line
            group by location.line,location.loc";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    // break;
  }

  public function lookuppalletenterqty($config)
  {
    $plotting = array('pallet' => 'pallet', 'palletid' => 'palletid');
    $plottype = 'plotemit';
    $title = 'List of Pallet';
    switch ($config['params']['lookupclass']) {
      case 'palletstock':
        $plottype = 'plotgrid';
        break;
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'pallet', 'label' => 'Pallet', 'align' => 'left', 'field' => 'pallet', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'loc', 'label' => 'Location', 'align' => 'left', 'field' => 'loc', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select pallet.line as palletid,pallet.name as pallet,ifnull(location.loc,'') as loc from pallet left join location on location.line=pallet.locid order by pallet.name";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
    // break;
  }


  public function lookuppalletbalance($config)
  {
    $plotting = array('location' => 'location', 'locid' => 'locid', 'palletid' => 'palletid', 'pallet' => 'pallet', 'wh' => 'wh');
    $plottype = 'plotemit';
    $title = 'List of Available Balance';
    switch ($config['params']['lookupclass']) {
      case 'palletstock':
      case 'locstock':
        $plottype = 'plotgrid';
        break;
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );
    // lookup columns
    $cols = [
      ['name' => 'wh', 'label' => 'Code', 'align' => 'left', 'field' => 'wh', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'pallet', 'label' => 'Pallet', 'align' => 'left', 'field' => 'pallet', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'location', 'label' => 'Location', 'align' => 'left', 'field' => 'location', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'bal', 'label' => 'Balance', 'align' => 'left', 'field' => 'bal', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select wh.client as wh,location.line as locid,location.loc as location,pallet.line as palletid,pallet.name as pallet,sum(rrstatus.bal) as bal from rrstatus left join client as wh on wh.clientid=rrstatus.whid
            left join location on location.line=rrstatus.locid left join pallet on pallet.line=rrstatus.palletid where rrstatus.itemid=? and rrstatus.bal>0 group by wh.client,location.line,location.loc,pallet.line,pallet.name";

    switch ($config['params']['lookupclass']) {
      case 'palletstock':
      case 'locstock':
        $itemid = $config['params']['itemid'];
        $data = $this->coreFunctions->opentable($qry, [$itemid]);
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
        break;
      default:
        $itemid = $config['params']['dataid'];
        $data = $this->coreFunctions->opentable($qry, [$itemid]);
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
        break;
    }
  } // end function

  public function lookuppicker($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Pickers',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array('pickerid' => 'pickerid', 'picker' => 'picker')
    );

    $cols = [
      ['name' => 'picker', 'label' => 'Picker Name', 'align' => 'left', 'field' => 'picker', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select clientid as pickerid, clientname as picker from client where uv_ispicker=1 and isinactive=0";
    $data = $this->coreFunctions->opentable($qry);

    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
  }

  public function lookupchecker($config)
  {

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Checkers',
      'style' => 'width:900px;max-width:900px;'
    );

    $plottype = 'plothead';

    $filter = '';

    switch ($config['params']['doc']) {
      case 'WAREHOUSECHECKER':
        $plotting = array('newcheckerid' => 'clientid', 'newchecker' => 'clientname');
        $filter = ' and c.clientid<>' . $config['params']['adminid'];
        break;

      default:
        $plotting = array('checkerid' => 'clientid', 'checker' => 'clientname');
        break;
    }

    $currentdate = $this->othersClass->getCurrentDate();
    switch ($config['params']['doc']) {
      case 'SO': //hgc
        $plottype = 'plotledger';
        $cols = [
          ['name' => 'clientname', 'label' => 'Checker Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $qry = "select c.clientid, c.clientname from client as c where c.uv_ischecker=1 and c.isinactive=0 " . $filter;
        break;

      case 'TM':
        $plottype = 'plothead';
        $cols = [
          ['name' => 'clientname', 'label' => 'Checker Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $qry = "select c.clientid, c.clientname from client as c where c.isemployee=1 and c.isinactive=0 " . $filter;
        break;

      default:
        $cols = [
          ['name' => 'clientname', 'label' => 'Checker Name', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'],
          ['name' => 'status', 'label' => 'Status', 'align' => 'left', 'field' => 'status', 'sortable' => true, 'style' => 'font-size:16px;'],
        ];
        $qry = "select c.clientid, c.clientname, (case when (select clientid from adminlog where clientid=c.clientid and date(dateid)='" . $currentdate . "' limit 1)<>0 then 'ONLINE' else 'OFFLINE' end) as status from client as c where c.uv_ischecker=1 and c.isinactive=0 " . $filter;
        break;
    }

    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupcheckerloc($config)
  {
    $checkid = $config['params']['addedparams'][0];


    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Checker Locations',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => array('checkerlocid' => 'line', 'checkerloc' => 'name')
    );

    $cols = [
      ['name' => 'name', 'label' => 'Location Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];


    $qry = " select cl.line,cl.name from checkerloc as cl";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuptruck($config)
  {
    $lookupclass = $config['params']['lookupclass'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Checkers',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotting = array('truckid' => 'clientid', 'truck' => 'clientname', 'plateno' => 'plateno');
    $plottype = 'plothead';
    switch ($config['params']['doc']) {
      case 'SO':
        $plottype = 'plotledger';
        break;
    }
    if ($lookupclass == 'lookupmitruck') {
      $plotting = array('truckid' => 'clientid', 'client' => 'client', 'clientname' => 'clientname');
    }
    $plotsetup = array(
      'plottype' => $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = array();

    if ($config['params']['lookupclass'] == 'lookupmitruck') {
      array_push($cols, array('name' => 'Client', 'label' => 'Truck Code', 'align' => 'left', 'field' => 'client', 'sortable' => true, 'style' => 'font-size:16px;'));
    }

    array_push($cols, array('name' => 'clientname', 'label' => 'Truck', 'align' => 'left', 'field' => 'clientname', 'sortable' => true, 'style' => 'font-size:16px;'));
    array_push($cols, array('name' => 'plateno', 'label' => 'Plate No.', 'align' => 'left', 'field' => 'plateno', 'sortable' => true, 'style' => 'font-size:16px;'));


    $qry = "select clientid,client, clientname, plateno 
            from client where istrucking=1 and isinactive=0 order by clientname";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupdeliverytypename($config)
  {

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Delivery Type',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => array('deliverytype' => 'line', 'deliverytypename' => 'name')
    );

    $cols = [
      ['name' => 'name', 'label' => 'Delivery Type', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select 0 as line, '' as name union all select line, name from deliverytype order by name";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookuppartreqtype($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Special Request Type',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => array('partreqtypeid' => 'line', 'partreqtype' => 'name')
    );

    $cols = [
      ['name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select line, name from partrequest order by name";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupcheckerbarcode($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Items',
      'style' => 'width:100%;max-width:90%;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => array('barcode' => 'barcode')
    );

    $cols = [
      ['name' => 'barcode', 'label' => 'Barcode', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'itemname', 'label' => 'Itemname', 'align' => 'left', 'field' => 'itemname', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'qty', 'label' => 'DR Quantity', 'align' => 'right', 'field' => 'qty', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    switch ($config['params']['lookupclass']) {
      case 'checkerbarcode':
      case 'reopenboxitems':
        array_push($cols, ['name' => 'pending', 'label' => 'Pending Quantity', 'align' => 'right', 'field' => 'pending', 'sortable' => true, 'style' => 'font-size:16px;']);
        break;
    }

    array_push(
      $cols,
      ['name' => 'subcode', 'label' => 'SKU', 'align' => 'left', 'field' => 'subcode', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'partno', 'label' => 'Part No.', 'align' => 'left', 'field' => 'partno', 'sortable' => true, 'style' => 'font-size:16px;']
    );

    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);
    switch ($config['params']['lookupclass']) {
      case 'checkerbarcode':
      case 'reopenboxitems':
        $qry = "select barcode, itemname, subcode, partno, FORMAT(qty, " . $qtydec . ") as qty, scanqty, FORMAT((qty-scanqty), " . $qtydec . ") as pending from (
          select item.barcode, item.itemname, item.subcode, item.partno, sum(stock.isqty) as qty,
          (select ifnull(sum(box.qty),0) from boxinginfo as box where box.trno=stock.trno and box.itemid=stock.itemid) as scanqty
          from lastock as stock left join item on item.itemid=stock.itemid 
          where stock.trno=? group by stock.itemid, item.barcode, item.itemname, item.subcode, item.partno, stock.trno
          ) as i where i.qty<>i.scanqty";
        break;

      default:
        $qry = "select item.barcode, item.itemname, item.subcode, item.partno, FORMAT(stock.iss, " . $qtydec . ") as qty 
        from lastock as stock left join item on item.itemid=stock.itemid where stock.trno=? group by item.barcode, item.itemname, item.subcode, item.partno, stock.iss";
        break;
    }


    $data = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupreopenbox($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Boxes',
      'style' => 'width:100%;max-width:90%;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => array('barcode' => 'barcode')
    );

    $cols = [
      ['name' => 'barcode', 'label' => 'Box No.', 'align' => 'left', 'field' => 'barcode', 'sortable' => true, 'style' => 'font-size:16px;']
    ];

    $qry = "select concat(h.docno,'-',b.boxno) as barcode from boxinginfo as b left join lahead as h on h.trno=b.trno where b.trno=? group by h.docno, b.boxno order by b.boxno";

    $data = $this->coreFunctions->opentable($qry, [$config['params']['trno']]);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupagrelease($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Remarks',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotledger',
      'action' => '',
      'plotting' => array('agrelease' => 'agrelease')
    );

    $cols = [
      ['name' => 'agrelease', 'label' => 'Released date', 'align' => 'left', 'field' => 'agrelease', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];


    switch ($config['params']['lookupclass']) {
      case 'agreleaseyr':
        $qry = "select '' as agrelease, 1 as srt union all select distinct date(agrelease) as agrelease, 2 as srt from incentivesyr where agrelease is not null order by srt, agrelease desc";
        break;

      default;
        $qry = "select '' as agrelease, 1 as srt union all
        select distinct agrelease , 2 as srt from (
        select distinct date(agrelease) as agrelease from incentives where agrelease is not null 
        union all 
        select distinct date(ag2release) from incentives where ag2release is not null
        ) as ag order by srt, agrelease desc";
        break;
    }



    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupwaybill($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Waybill',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plothead',
      'action' => '',
      'plotting' => array('waybill' => 'waybill')
    );

    $cols = [
      ['name' => 'waybill', 'label' => 'Waybill', 'align' => 'left', 'field' => 'waybill', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select distinct waybill from lahead where waybill<>'' order by waybill";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function lookupstatname($config)
  {
    $lookupclass = $config['params']['lookupclass'];
    switch ($lookupclass) {
      case 'lookupitemstatus':
        $plotting = array('statid' => 'line', 'statname' => 'stat');
        $title = 'Item Status';
        $plottype = 'plotgrid';
        $qry = "select 0 as line, '' as stat union all select line, status as stat from trxstatus where doc='ITEMS' or line=43 order by stat";
        $plotting = array('status' => 'line', 'stat' => 'stat');
        break;
      case 'statitemreqm':
        $plottype = 'plotledger';
        $plotting = array('statid' => 'line', 'statname' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where doc='ITEMREQM' order by line";
        $title = 'Status';
        break;
      case 'lookuprequestorstat':
        $plottype = 'plotledger';
        $plotting = array('requestorstatid' => 'line', 'requestorstat' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where doc='ITEMREQM' order by line";
        $title = 'Status';
        break;
      case 'lookupsubcategorystat':
        $plottype = 'plotledger';
        $plotting = array('substatid' => 'line', 'subcategorystat' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where doc='ITEMREQM2' order by line";
        $title = 'Sub Category Status';
        break;

      case 'lookup_procid':
        $plottype = 'plothead';
        $plotting = array('procid' => 'line', 'statname' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where doc='CDPROC' order by line";
        $title = 'Procurement Status';
        break;

      case 'lookup_sjtype':
        $plottype = 'plothead';
        $plotting = array('statid' => 'line', 'statname' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where line in (24, 40) order by line";
        $title = 'Type';
        break;

      case 'lookupstatus1':
        $plottype = 'plotgrid';
        $plotting = array('status1' => 'line', 'status1name' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where line in (5, 51, 52) order by line";
        $title = 'Receive Status 1';
        break;

      case 'lookupstatus2':
        $plottype = 'plotgrid';
        $plotting = array('status2' => 'line', 'status2name' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where line in (5, 53, 54) order by line";
        $title = 'Receive Status 2';
        break;

      case 'lookupcheckstat':
        $plottype = 'plotgrid';
        $plotting = array('checkstat' => 'line', 'checkstatname' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where line in (5, 55, 56) order by line";
        $title = 'Check Status';
        break;

      case 'lookupcheckstatATI':
        $plottype = 'plothead';
        $plotting = array('statid' => 'line', 'statname' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where line in (5, 36, 77) order by line";
        $title = 'Check Status';
        break;

      default:
        $plottype = 'plothead';
        $plotting = array('statid' => 'line', 'statname' => 'stat');
        $qry = "select line, status as stat, doc, psort from trxstatus where doc='SJ' order by psort";
        $title = 'Priority Type';
        break;
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' => $title,
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' =>  $plottype,
      'action' => '',
      'plotting' => $plotting
    );

    $cols = [
      ['name' => 'stat', 'label' => 'Status', 'align' => 'left', 'field' => 'stat', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];
    $data = $this->coreFunctions->opentable($qry);

    switch ($plottype) {
      case 'plotgrid':
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
        break;


      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
        break;
    }
  }


  public function lookupwhrem($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Remarks',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotsetup = array(
      'plottype' => 'plotgrid',
      'action' => '',
      'plotting' => array('whremid' => 'line', 'whrem' => 'rem')
    );

    $cols = [
      ['name' => 'rem', 'label' => 'Remarks', 'align' => 'left', 'field' => 'rem', 'sortable' => true, 'style' => 'font-size:16px;'],
    ];

    $qry = "select line, rem, forreturn from whrem order by rem";

    $data = $this->coreFunctions->opentable($qry);

    $rowindex = $config['params']['index'];
    switch ($config['params']['doc']) {
      case 'WAREHOUSECHECKER':
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
        break;

      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'index' => $rowindex];
        break;
    }
  }
}
