<?php

namespace App\Http\Classes\builder;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;

class mobiletxtFieldClass {
  private $style;
  private $fields = [];
  private $coreFunctions;
  private $othersClass;

  public function __construct() {
    $this->othersClass = new othersClass;
    $this->style = 'font-size:120%;';
    $this->fields = [
      'startdate'=>[
        'name'=>'startdate',
        'label'=>'Start Date',
        'type'=>'date'
      ],
      'enddate'=>[
        'name'=>'enddate',
        'label'=>'End Date',
        'type'=>'date'
      ],
      'docstatus'=>[
        'name'=>'docstatus',
        'label'=>'',
        'type'=>'option',
        'options'=>'[{ name: "draft", label: "Draft" }, { name: "posted", label: "Posted" }]'
      ],
      'docno'=>[
        'name'=>'docno',
        'label'=>'Document #',
        'type'=>'input',
        'readonly'=>true
      ],
      'clientname'=>[
        'name'=>'clientname',
        'label'=>'Supplier',
        'type'=>'lookup',
        'action'=>'supplookup',
        'fields'=>'clientid,clientname',
        'readonly'=>true
      ],
      'whname'=>[
        'name'=>'whname',
        'label'=>'Warehouse',
        'type'=>'lookup',
        'action'=>'whlookup',
        'fields'=>'whid,whname',
        'readonly'=>true
      ],
      'supaddr'=>[
        'name'=>'supaddr',
        'label'=>'Address',
        'type'=>'input',
        'readonly'=>true
      ],
      'addr'=>[
        'name'=>'addr',
        'label'=>'Address',
        'type'=>'input',
        'readonly'=>true
      ],
      'whaddr'=>[
        'name'=>'whaddr',
        'label'=>'Address',
        'type'=>'input',
        'readonly'=>true
      ],
      'dateid'=>[
        'name'=>'dateid',
        'label'=>'Date',
        'type'=>'date',
        'readonly'=>true
      ],
      'refresh'=>[
        'name'=>'refresh',
        'label'=>'Refresh',
        'type'=>'button',
        'action'=>'loadDocList'
      ],
      'barcode'=>[
        'name'=>'barcode',
        'label'=>'Barcode',
        'type'=>'input',
        'readonly'=>false
      ],
      'barcode1'=>[
        'name'=>'barcode1',
        'label'=>'Barcode: ',
        'type'=>'label',
        'readonly'=>false
      ],
      'batchcode'=>[
        'name'=>'batchcode',
        'label'=>'Batchcode: ',
        'type'=>'label',
        'readonly'=>false
      ],
      'rtrno'=>[
        'name'=>'rtrno',
        'label'=>'Docnum: ',
        'type'=>'label',
        'readonly'=>false
      ],
      'codeqty'=>[
        'name'=>'codeqty',
        'label'=>'Qty: ',
        'type'=>'label',
        'readonly'=>'false'
      ],
      'itemname'=>[
        'name'=>'itemname',
        'label'=>'Item Name',
        'type'=>'input',
        'readonly'=>false
      ],
      'uom'=>[
        'name'=>'uom',
        'label'=>'UOM',
        'type'=>'lookup',
        'action'=>'uomlookup',
        'fields'=>'uom',
        'readonly'=>'true'
      ],
      'factor'=>[
        'name'=>'factor',
        'label'=>'Factor',
        'type'=>'input',
        'readonly'=>'false'
      ],
      'amt'=>[
        'name'=>'amt',
        'label'=>'Amount',
        'type'=>'input',
        'readonly'=>'false'
      ],
      'isamt'=>[
        'name'=>'isamt',
        'type'=>'input',
        'label'=>'Amount',
        'readonly'=>'true'
      ],
      'ext'=>[
        'name'=>'ext',
        'type'=>'label',
        'field'=>'ext',
        'label'=>'Amount',
        'sortable'=>'false'
      ],
      'iamt'=>[
        'name'=>'iamt',
        'type'=>'label',
        'label'=>'Amount',
        'field'=>'iamt',
        'sortable'=>'false'
      ],
      'rrqty'=>[
        'name'=>'rrqty',
        'type'=>'label',
        'label'=>'Qty',
        'align'=>'left',
        'field'=>'rrqty',
        'sortable'=>true
      ],
      'uploaddate'=>[
        'name'=>'uploaddate',
        'type'=>'label',
        'label'=>'Upload Date',
        'align'=>'left',
        'field'=>'uploaddate',
        'sortable'=>true
      ],
      'wh'=>[
        'name'=>'wh',
        'label'=>'Name',
        'type'=>'label',
        'align'=>'left',
        'field'=>'wh',
        'sortable'=>true
      ],
      'bal'=>[
        'name'=>'bal',
        'type'=>'label',
        'label'=>'Bal',
        'align'=>'left',
        'field'=>'bal',
        'sortable'=>true
      ],
      'changeprinter'=>[
        'name'=>'changeprinter',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Change Printer',
        'func'=>'changePrinter',
        'functype'=>'module'
      ],
      'closecollection'=>[
        'name'=>'closecollection',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Close Collection',
        'func'=>'closeCollection',
        'functype'=>'module'
      ],
      'cleartrans'=>[
        'name'=>'cleartrans',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Clear Transaction',
        'func'=>'clearTrans',
        'functype'=>'module'
      ],
      'collectionreport'=>[
        'name'=>'collectionreport',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Collection Report',
        'func'=>'collectionReport',
        'functype'=>'module'
      ],
      'download'=>[
        'name'=>'download',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Download',
        'func'=>'downloadAdmin',
        'functype'=>'module'
      ],
      'operationtype'=>[
        'name'=>'operationtype',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Operation Type',
        'func'=>'operationType',
        'functype'=>'module'
      ],
      'printtype'=>[
        'name'=>'printtype',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Print Type',
        'func'=>'printType',
        'functype'=>'module'
      ],
      'reprintreading'=>[
        'name'=>'reprintreading',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Reprint Reading',
        'func'=>'reprintReading',
        'functype'=>'module'
      ],
      'reprinttrans'=>[
        'name'=>'reprinttrans',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Reprint Transaction',
        'func'=>'reprintTrans',
        'functype'=>'module'
      ],
      'upload'=>[
        'name'=>'upload',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Upload',
        'func'=>'uploadAdmin',
        'functype'=>'module'
      ],
      'viewtables'=>[
        'name'=>'viewtables',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'View Tables',
        'func'=>'viewTables',
        'functype'=>'module'
      ],
      'logout'=>[
        'name'=>'logout',
        'color'=>'primary',
        'type'=>'button',
        'label'=>'Logout',
        'func'=>'logoutAdmin',
        'functype'=>'module'
      ],
      'searchitem'=>[
        'name'=>'searchitem',
        'label'=>'Search Item',
        'type'=>'lookup',
        'action'=>'',
        'fields'=>'',
        'readonly'=>'false'
      ],
      'qty'=>[
        'name'=>'qty',
        'type'=>'label',
        'label'=>'Qty',
        'align'=>'center',
        'field'=>'qty',
        'sortable'=>false
      ],
      'qtyreleased'=>[
        'name'=>'qtyreleased',
        'type'=>'label',
        'label'=>'Qty Released',
        'field'=>'qtyreleased',
        'sortable'=>false
      ],
      'qtyneeded'=>[
        'name'=>'qtyneeded',
        'type'=>'label',
        'label'=>'Qty Needed: ',
        'field'=>'qtyneeded',
        'sortable'=>false
      ],
      'isqty'=>[
        'name'=>'isqty',
        'type'=>'label',
        'label'=>'Ordered Qty',
        'align'=>'center',
        'field'=>'isqty',
        'sortable'=>false
      ],
      'itembal'=>[
        'name'=>'itembal',
        'type'=>'label',
        'label'=>'Onhand Qty',
        'align'=>'left',
        'field'=>'itembal',
        'sortable'=>false
      ],
      'newitembal'=>[
        'name'=>'newitembal',
        'type'=>'input',
        'label'=>'Item Balance',
        'readonly'=>'true'
      ],
      'disc'=>[
        'name'=>'disc',
        'type'=>'label',
        'label'=>'Discount',
        'align'=>'left',
        'field'=>'disc',
        'sortable'=>false
      ],
      'newdisc'=>[
        'name'=>'newdisc',
        'type'=>'lookup',
        'label'=>'Discount',
        'action'=>'disclookup',
        'fields'=>'newdisc',
        'readonly'=>'true'
      ],
      'brand'=>[
        'name'=>'brand',
        'type'=>'label',
        'label'=>'Brand',
        'align'=>'left',
        'field'=>'brand',
        'sortable'=>false
      ],
      'newamt'=>[
        'name'=>'newamt',
        'type'=>'input',
        'label'=>'Amount',
        'readonly'=>false
      ],
      'newuom'=>[
        'name'=>'newuom',
        'type'=>'label',
        'label'=>'UOM',
        'align'=>'left',
        'field'=>'newuom',
        'sortable'=>false
      ],
      'part'=>[
        'name'=>'part',
        'type'=>'label',
        'label'=>'Part',
        'align'=>'left',
        'field'=>'part',
        'sortable'=>false
      ],
      'plgrp'=>[
        'name'=>'plgrp',
        'type'=>'label',
        'label'=>'PLGRP',
        'align'=>'left',
        'field'=>'plgrp',
        'sortable'=>false
      ],
      'groupid'=>[
        'name'=>'groupid',
        'type'=>'label',
        'label'=>'Group',
        'align'=>'left',
        'field'=>'groupid',
        'sortable'=>false
      ],
      'model'=>[
        'name'=>'model',
        'type'=>'label',
        'label'=>'Model',
        'align'=>'left',
        'field'=>'model',
        'sortable'=>false
      ],
      'size'=>[
        'name'=>'size',
        'type'=>'label',
        'label'=>'Size',
        'align'=>'left',
        'field'=>'size',
        'sortable'=>false
      ],
      'country'=>[
        'name'=>'country',
        'type'=>'label',
        'label'=>'Country',
        'align'=>'left',
        'field'=>'country',
        'sortable'=>false
      ],
      'istaxable'=>[
        'name'=>'istaxable',
        'type'=>'label',
        'label'=>'Vatable',
        'align'=>'left',
        'field'=>'istaxable',
        'sortable'=>false
      ],
      'total'=>[
        'name'=>'total',
        'type'=>'input',
        'label'=>'Total',
        'readonly'=>'true',
        'style'=>$this->style
      ],
      'skuqty'=>[
        'name'=>'skuqty',
        'type'=>'label',
        'label'=>'SKU: ',
        'field'=>'skuqty',
        'fields'=>'',
        'readonly'=>true,
        'style'=>'font-size:100%'
      ],
      'newfactor'=>[
        'name'=>'newfactor',
        'type'=>'input',
        'label'=>'Factor',
        'readonly'=>'true'
      ],
      'itemcount'=>[
        'name'=>'itemcount',
        'type'=>'label',
        'label'=>'Item Count: ',
        'field'=>'itemcount',
        'readonly'=>true,
        'style'=>$this->style
      ],
      'orderno'=>[
        'name'=>'orderno',
        'type'=>'label',
        'label'=>'Order No.',
        'field'=>'orderno',
        'readonly'=>true,
        'style'=>$this->style
      ],
      'tel'=>[
        'name'=>'tel',
        'type'=>'input',
        'label'=>'Tel #',
        'readonly'=>'true'
      ],
      'doctype'=>[
        'name'=>'doctype',
        'label'=>'Document Type',
        'type'=>'option',
        'options'=>'[{ name: "dr", label: "DR" }, { name: "si", label: "SI" }]'
      ],
      'shipto'=>[
        'name'=>'shipto',
        'label'=>'Ship to',
        'type'=>'input',
        'readonly'=>'false'
      ],
      'terms'=>[
        'name'=>'terms',
        'label'=>'Select Terms',
        'type'=>'select',
        'options'=>'docterms',
        'readonly'=>'false'
      ],
      'brand'=>[
        'name'=>'brand',
        'label'=>'Search Brand',
        'type'=>'input',
        'readonly'=>'false'
      ],
      'part'=>[
        'name'=>'part',
        'label'=>'Search Part',
        'type'=>'input',
        'readonly'=>'false'
      ],
      'btnsearch'=>[
        'name'=>'btnsearch',
        'label'=>'Search',
        'type'=>'button',
        'action'=>'loadTableData'
      ],
      'searchdoc'=>[
        'name'=>'searchdoc',
        'label'=>'Search Document',
        'type'=>'lookup',
        'action'=>'',
        'fields'=>'',
        'readonly'=>'false',
        'enterfunc'=>'loadTableData'
      ],
      'rem'=>[
        'name'=>'rem',
        'label'=>'Remarks',
        'type'=>'label',
        'align'=>'left',
        'field'=>'rem',
        'sortable'=>false
      ],
      'sjstatus'=>[
        'name'=>'sjstatus',
        'label'=>'Status',
        'type'=>'label',
        'align'=>'left',
        'field'=>'sjstatus',
        'sortable'=>false
      ],
      'sjref'=>[
        'name'=>'sjref',
        'label'=>'Ref',
        'type'=>'label',
        'align'=>'left',
        'field'=>'sjref',
        'sortable'=>false
      ],
      'salestype'=>[
        'name'=>'salestype',
        'label'=>'',
        'type'=>'select',
        'options'=>'salestypes',
        'readonly'=>'false'
      ],
      'salesmonth'=>[
        'name'=>'salesmonth',
        'label'=>'Month',
        'type'=>'select',
        'options'=>'salesmonths',
        'readonly'=>'false'
      ],
      'salesyear'=>[
        'name'=>'salesyear',
        'label'=>'Year',
        'type'=>'select',
        'options'=>'salesyears',
        'readonly'=>'false'
      ],
      'paytype'=>[
        'name'=>'paytype',
        'label'=>'Payment Type',
        'type'=>'select',
        'options'=>'paytype',
        'readonly'=>'false',
        'enterfunc'=>'paytypechange'
      ],
      'payment'=>[
        'name'=>'payment',
        'label'=>'Payment',
        'type'=>'input',
        'readonly'=>'false'
      ],
      'change'=>[
        'name'=>'change',
        'type'=>'input',
        'label'=>'Change',
        'readonly'=>'true'
      ],
      'compute'=>[
        'name'=>'compute',
        'type'=>'button',
        'label'=>'Compute',
        'func'=>'computeTotal',
        'functype'=>'module'
      ],
      'brgy'=>[
        'name'=>'brgy',
        'type'=>'input',
        'label'=>'Barangay',
        'readonly'=>'true'
      ],
      'area'=>[
        'name'=>'area',
        'type'=>'input',
        'label'=>'Area',
        'readonly'=>'true'
      ],
      'province'=>[
        'name'=>'province',
        'type'=>'input',
        'label'=>'Province',
        'readonly'=>'true'
      ],
      'username'=>[
        'name'=>'username',
        'type'=>'input',
        'label'=>'Username',
        'readonly'=>'false'
      ],
      'password'=>[
        'name'=>'password',
        'type'=>'password',
        'label'=>'Password',
        'readonly'=>'false'
      ],
      'category'=>[
        'name'=>'category',
        'type'=>'lookup',
        'label'=>'Category',
        'action'=>'categorylookup',
        'fields'=>'category',
        'readonly'=>'true'
      ],
      'tenant'=>[
        'name'=>'tenant',
        'type'=>'lookup',
        'label'=>'Tenant',
        'action'=>'tenantslookup',
        'fields'=>'tenant',
        'readonly'=>'true'
      ],
      'arealabel'=>[
        'name'=>'arealabel',
        'type'=>'input',
        'label'=>'Area',
        'readonly'=>'true'
      ],
      'transtype'=>[
        'name'=>'transtype',
        'type'=>'select',
        'label'=>'Transaction Type',
        'options'=>'transtypeOpts',
        'enterfunc'=>'',
        'readonly'=>'false'
      ],
      'loc'=>[
        'name'=>'loc',
        'type'=>'input',
        'label'=>'Stall Number',
        'readonly'=>'true'
      ],
      'beginning'=>[
        'name'=>'beginning',
        'type'=>'input',
        'label'=>'Previous',
        'readonly'=>'true'
      ],
      'ending'=>[
        'name'=>'ending',
        'type'=>'input',
        'label'=>'Current',
        'readonly'=>'true'
      ],
      'consumption'=>[
        'name'=>'consumption',
        'type'=>'input',
        'label'=>'Consumption',
        'readonly'=>'true'
      ],
      'remarks'=>[
        'name'=>'remarks',
        'type'=>'input',
        'label'=>'Remarks',
        'readonly'=>'true'
      ],
      'rent'=>[
        'name'=>'rent',
        'type'=>'input',
        'label'=>'Rent'
      ],
      'cusa'=>[
        'name'=>'cusa',
        'type'=>'input',
        'label'=>'CUSA'
      ],
      'outstandingbal'=>[
        'name'=>'outstandingbal',
        'type'=>'input',
        'label'=>'Outstanding Balance',
        'readonly'=>'true'
      ],
      'balance'=>[
        'name'=>'balance',
        'type'=>'input',
        'label'=>'Balance',
        'readonly'=>'true'
      ],
      'saveamb'=>[
        'name'=>'saveamb',
        'type'=>'button',
        'label'=>'Save',
        'func'=>'saveAmb',
        'functype'=>'module'
      ],
      'colstatlabel'=>[
        'name'=>'colstatlabel',
        'type'=>'label',
        'label'=>''
      ],
      'sumticket1'=>[
        'name'=>'sumticket1',
        'type'=>'input',
        'label'=>'Start Ticket No.',
        'readonly'=>'false'
      ],
      'sumticket2'=>[
        'name'=>'sumticket2',
        'type'=>'input',
        'label'=>'End Ticket No.',
        'readonly'=>'false'
      ],
      'printsum'=>[
        'name'=>'printsum',
        'type'=>'button',
        'label'=>'Print',
        'func'=>'printsum',
        'functype'=>'module'
      ],
      'lcno'=>[
        'name'=>'lcno',
        'label'=>'LC No.',
        'type'=>'label',
        'field'=>'lcno',
        'sortable'=>'true'
      ],
      'bundleno'=>[
        'name'=>'bundleno',
        'type'=>'label',
        'label'=>'Coil No.',
        'field'=>'bundleno',
        'align'=>'left',
        'sortable'=>false
      ],
      'itemnetweight'=>[
        'name'=>'itemnetweight',
        'type'=>'label',
        'label'=>'Net Wt.',
        'field'=>'itemnetweight',
        'align'=>'left',
        'sortable'=>false
      ],
      'itemgrossweight'=>[
        'name'=>'itemgrossweight',
        'type'=>'label',
        'label'=>'Gross Wt.',
        'field'=>'itemgrossweight',
        'align'=>'left',
        'sortable'=>false
      ],
      'drno'=>[
        'name'=>'drno',
        'type'=>'input',
        'label'=>'DR #',
        'readonly'=>false
      ],
      'yourref'=>[
        'name'=>'yourref',
        'type'=>'label',
        'label'=>'Yourref',
        'field'=>'yourref',
        'sortable'=>'false'
      ],
      'ourref'=>[
        'name'=>'ourref',
        'type'=>'label',
        'label'=>'Ourref',
        'field'=>'ourref',
        'sortable'=>'false'
      ],
      'prdno'=>[
        'name'=>'prdno',
        'type'=>'label',
        'label'=>'PRD No.',
        'field'=>'prdno',
        'sortable'=>'false'
      ],
      'scanstat'=>[
        'name'=>'scanstat',
        'label'=>'',
        'type'=>'option',
        'show'=>'true',
        'options'=>'[{ name: "0", label: "Unscanned" }, { name: "1", label: "Scanned" }]'
      ],
      'searchcoil'=>[
        'name'=>'searchcoil',
        'type'=>'input',
        'label'=>'Search Coil No.',
        'readonly'=>false
      ],
      'class'=>[
        'name'=>'class',
        'type'=>'input',
        'label'=>'Class',
        'readonly'=>false
      ],
      'thickness'=>[
        'name'=>'thickness',
        'type'=>'input',
        'label'=>'Thickness',
        'readonly'=>false
      ],
      'width'=>[
        'name'=>'width',
        'type'=>'input',
        'label'=>'Width',
        'readonly'=>false
      ],
      'shiftt'=>[
        'name'=>'shiftt',
        'type'=>'lookup',
        'label'=>'Shift',
        'action'=>'shiftlookup',
        'fields'=>'shiftt',
        'readonly'=>true
      ],
      'seltype'=>[
        'name'=>'seltype',
        'type'=>'lookup',
        'label'=>'Type',
        'action'=>'seltypelookup',
        'fields'=>'seltype',
        'readonly'=>false
      ],
      'designation'=>[
        'name'=>'designation',
        'type'=>'lookup',
        'label'=>'Designation',
        'action'=>'designationlookup',
        'fields'=>'designation',
        'readonly'=>false
      ],
      'mass'=>[
        'name'=>'mass',
        'type'=>'input',
        'label'=>'Coating Mass',
        'readonly'=>false
      ],
      'weight'=>[
        'name'=>'weight',
        'type'=>'input',
        'label'=>'Weight',
        'readonly'=>false
      ],
      'addexit'=>[
        'name'=>'addexit',
        'label'=>'Add',
        'type'=>'button',
        'func'=>'addExitDataProd',
        'functype'=>'global'
      ],
      'color'=>[
        'name'=>'color',
        'label'=>'Color',
        'type'=>'input',
        'readonly'=>false
      ],
      'len'=>[
        'name'=>'len',
        'label'=>'Length',
        'type'=>'input',
        'readonly'=>false
      ],
      'prd'=>[
        'name'=>'prd',
        'label'=>'PRD No.',
        'type'=>'input',
        'readonly'=>false
      ],
      'scsono'=>[
        'name'=>'scsono',
        'label'=>'SC/SO No.',
        'type'=>'input',
        'readonly'=>false
      ],
      'custname'=>[
        'name'=>'custname',
        'label'=>'Customer Name',
        'type'=>'input',
        'readonly'=>false
      ],
      'fgtag'=>[
        'name'=>'fgtag',
        'label'=>'FG Tag #',
        'type'=>'input',
        'readonly'=>false
      ],
      'paintsupp'=>[
        'name'=>'paintsupp',
        'label'=>'Paint Supplier',
        'type'=>'lookup',
        'action'=>'paintsupplookup',
        'fields'=>'paintsupp',
        'readonly'=>true
      ],
      'dpr'=>[
        'name'=>'dpr',
        'label'=>'DPR #',
        'type'=>'input',
        'readonly'=>false
      ],
      'codenotlocate'=>[
        'name'=>'codenotlocate',
        'label'=>'Cannot locate code: ',
        'type'=>'label'
      ],
      'searchcode'=>[
        'name'=>'searchcode',
        'label'=>'Search Code',
        'type'=>'input',
        'readonly'=>false
      ],
      'uploadcsv'=>[
        'name'=>'uploadcsv',
        'label'=>'upload csv file',
        'type'=>'button',
        'func'=>'uploadcsv',
        'functype'=>'module'
      ],
      'showcode'=>[
        'name'=>'showcode',
        'label'=>'verify code',
        'type'=>'button',
        'func'=>'showcode',
        'functype'=>'module'
      ],
      'loaditems'=>[
        'name'=>'loaditems',
        'label'=>'item listing',
        'type'=>'button',
        'func'=>'loaditems',
        'functype'=>'module'
      ],
      'downloadcsv'=>[
        'name'=>'downloadcsv',
        'label'=>'generate csv',
        'type'=>'button',
        'func'=>'downloadcsv',
        'functype'=>'module'
      ],
      'assettype'=>[
        'name'=>'assettype',
        'label'=>'Asset Type Scan',
        'type'=>'input',
        'readonly'=>true
      ],
      'assetno'=>[
        'name'=>'assetno',
        'label'=>'Asset No.',
        'type'=>'input',
        'readonly'=>true
      ],
      'subaccount'=>[
        'name'=>'subaccount',
        'label'=>'Sub Account',
        'type'=>'input',
        'readonly'=>true
      ],
      'description'=>[
        'name'=>'description',
        'label'=>'Description',
        'type'=>'input',
        'readonly'=>true
      ],
      'assignee'=>[
        'name'=>'assignee',
        'label'=>'Assignee',
        'type'=>'input',
        'readonly'=>true
      ],
      'serial'=>[
        'name'=>'serial',
        'label'=>'Serial No.',
        'type'=>'input',
        'readonly'=>true
      ],
      'location'=>[
        'name'=>'location',
        'label'=>'Location',
        'type'=>'lookup',
        'action'=>'locationlookup',
        'fields'=>'location',
        'readonly'=>true
      ],
      'division'=>[
        'name'=>'division',
        'label'=>'Division',
        'type'=>'lookup',
        'action'=>'divisionlookup',
        'fields'=>'division',
        'readonly'=>true
      ],
      'recordid'=>[
        'name'=>'recordid',
        'label'=>'Record ID',
        'type'=>'label',
        'field'=>'recordid',
        'sortable'=>true
      ],
      'shortdesc'=>[
        'name'=>'shortdesc',
        'label'=>'Short Description',
        'type'=>'label',
        'field'=>'shortdesc',
        'sortable'=>true
      ],
      'cappdp'=>[
        'name'=>'cappdp',
        'label'=>'Cap. Pd',
        'type'=>'label',
        'field'=>'cappdp',
        'sortable'=>true
      ],
      'usefullife'=>[
        'name'=>'usefullife',
        'label'=>'Useful Life',
        'type'=>'label',
        'field'=>'usefullife',
        'sortable'=>true
      ],
      'capcost'=>[
        'name'=>'capcost',
        'label'=>'Cap Cost',
        'type'=>'label',
        'field'=>'capcost',
        'sortable'=>true
      ],
      'accddepn'=>[
        'name'=>'accddepn',
        'label'=>'Accd Depn',
        'type'=>'label',
        'field'=>'accddepn',
        'sortable'=>true
      ],
      'nbv'=>[
        'name'=>'nbv',
        'label'=>'NBV',
        'type'=>'label',
        'field'=>'nbv',
        'sortable'=>true
      ],
      'dateofbarcoding'=>[
        'name'=>'dateofbarcoding',
        'label'=>'Date of Barcoding',
        'type'=>'label',
        'field'=>'dateofbarcoding',
        'sortable'=>true
      ],
      'time'=>[
        'name'=>'time',
        'label'=>'Time',
        'type'=>'label',
        'field'=>'time',
        'sortable'=>true
      ],
      'user'=>[
        'name'=>'user',
        'label'=>'User',
        'type'=>'label',
        'field'=>'user',
        'sortable'=>true
      ],
      'scanneddate'=>[
        'name'=>'scanneddate',
        'label'=>'Scanned Date',
        'type'=>'label',
        'field'=>'scanneddate',
        'sortable'=>true
      ],
      'ifilter'=>[
        'name'=>'ifilter',
        'label'=>'',
        'type'=>'option',
        'options'=>'[{ name: "scanned", label: "Scanned" }, { name: "unscanned", label: "Unscanned" }, { name: "all", label: "All" }]'
      ],
      'email'=>[
        'name'=>'email',
        'label'=>'Email/Username',
        'type'=>'lookup',
        'action'=>'emaillookup',
        'fields'=>'email',
        'readonly'=>false
      ],
      'remember'=>[
        'name'=>'remember',
        'label'=>'Remember Me',
        'type'=>'checkbox',
        'class'=>'float-right',
        'readonly'=>false
      ],
      'login'=>[
        'name'=>'login',
        'label'=>'Submit',
        'type'=>'button',
        'func'=>'loginAccount',
        'functype'=>'module',
        'style'=>'margin-bottom:30px;'
      ],
      'logs'=>[
        'name'=>'logs',
        'label'=>'Logs',
        'type'=>'button',
        'func'=>'loadLogs',
        'functype'=>'module'
      ],
      'tdateid'=>[
        'name'=>'tdateid',
        'label'=>'Date',
        'field'=>'dateid',
        'type'=>'label',
        'sortable'=>true
      ],
      'tid'=>[
        'name'=>'tid',
        'label'=>'ID',
        'field'=>'id',
        'type'=>'label',
        'sortable'=>true
      ],
      'tname'=>[
        'name'=>'tname',
        'label'=>'Name',
        'field'=>'name',
        'type'=>'label',
        'sortable'=>true
      ],
      'ttimein'=>[
        'name'=>'ttimein',
        'label'=>'Time-In',
        'field'=>'timein',
        'type'=>'label',
        'sortable'=>true
      ],
      'ttimeout'=>[
        'name'=>'ttimeout',
        'label'=>'Time-Out',
        'field'=>'timeout',
        'type'=>'label',
        'sortable'=>true
      ],
      'tstatus'=>[
        'name'=>'tstatus',
        'label'=>'Timein Status',
        'field'=>'status',
        'type'=>'label',
        'sortable'=>true
      ],
      'tuploaddate'=>[
        'name'=>'tuploaddate',
        'label'=>'Timein Upload date',
        'field'=>'uploaddate',
        'type'=>'label',
        'sortable'=>true
      ],
      'tstatus2'=>[
        'name'=>'tstatus2',
        'label'=>'Timeout Status',
        'field'=>'status2',
        'type'=>'label',
        'sortable'=>true
      ],
      'tuploaddate2'=>[
        'name'=>'tuploaddate2',
        'label'=>'Timeout Upload date',
        'field'=>'uploaddate2',
        'type'=>'label',
        'sortable'=>true
      ],
      'timeinimg'=>[
        'name'=>'timeinimg',
        'type'=>'fiximage',
        'action'=>''
      ],
      'userimage'=>[
        'name'=>'userimage',
        'type'=>'image',
        'style'=>'width:100%;height:25vh;'
      ],
      'usercapimage'=>[
        'name'=>'usercapimage',
        'type'=>'image',
        'style'=>'width:100%;height:25vh;margin-top:30px;'
      ],
      'lbllogs'=>[
        'name'=>'lbllogs',
        'type'=>'label',
        'label'=>''
      ],
      'timeinorout'=>[
        'name'=>'timeinorout',
        'type'=>'select',
        'label'=>'Log Type',
        'options'=>[]
      ],
      'isprinted'=>[
        'name'=>'isprinted',
        'label'=>'Printed',
        'type'=>'label',
        'field'=>'isprinted'
      ],
      'rline'=>[
        'name'=>'rline',
        'label'=>'Line No.',
        'type'=>'label',
        'field'=>'rline',
        'sortable'=>true
      ],
      'rrlinex'=>[
        'name'=>'rrlinex',
        'label'=>'Prod Line',
        'type'=>'label',
        'field'=>'rrlinex',
        'sortable'=>true
      ],
      'printed'=>[
        'name'=>'printed',
        'label'=>'Printed',
        'type'=>'label',
        'field'=>'printed',
        'sortable'=>true
      ],
      'wht'=>[
        'name'=>'wht',
        'label'=>'Select wht',
        'type'=>'select',
        'options'=>[],
        'enterfunc'=>'loadSAPStocks',
        'readonly'=>'false'
      ],
      'guardname' => [
        'name'=>'guardname',
        'label'=>'Name',
        'type'=>'input',
        'autofocus'=>true,
        'readonly'=>false,
        'enterfunc'=>'timeinAppGuardLogin',
        'functype'=>'global'
      ],
      'searchstock'=>[
        'name'=>'searchstock',
        'label'=>'Search',
        'type'=>'input',
        'readonly'=>false
      ],
      'scanid'=>[
        'name'=>'scanid',
        'type'=>'button',
        'label'=>'Scan ID',
        'func'=>'scantimeinoutid',
        'functype'=>'module',
        'icon'=>'scan',
        'style'=>'font-size:150%;margin-top:40px;'
      ],
      'saveinventory'=>[
        'name'=>'saveinventory',
        'type'=>'button',
        // 'label'=>'Save',
        'icon'=>'save',
        'color'=>'primary',
        'func'=>'saveInventory',
        'functype'=>'module'
      ],
      'deleteinventory'=>[
        'name'=>'deleteinventory',
        'type'=>'button',
        // 'label'=>'Delete',
        'icon'=>'delete',
        'color'=>'primary',
        'func'=>'deleteInventory',
        'functype'=>'module'
      ],
      'sku'=>[
        'name'=>'sku',
        'type'=>'label',
        'label'=>'Customer SKU',
        'field'=>'sku',
        'sortable'=>true
      ],
      'syscount'=>[
        'name'=>'syscount',
        'type'=>'label',
        'label'=>'Sys. Count',
        'field'=>'syscount',
        'sortable'=>true
      ],
      'variance'=>[
        'name'=>'variance',
        'type'=>'label',
        'label'=>'Variance',
        'field'=>'variance',
        'sortable'=>true
      ],
      'sales'=>[
        'name'=>'sales',
        'type'=>'label',
        'label'=>'Sales',
        'field'=>'sales',
        'sortable'=>true
      ],
      'scanitem'=>[
        'name'=>'scanitem',
        'type'=>'lookup',
        'action'=>'invItemLookup',
        'label'=>'Scan Barcode',
        'readonly'=>false
      ],
      'generatetype'=>[
        'name'=>'generatetype',
        'label'=>'',
        'type'=>'option',
        'options'=>'[{ name: "all", label: "All" }, { name: "1", label: "With Variance" }, { name: "2", label: "No Variance" }]',
        'func'=>'selectGenType',
        'functype'=>'module'
      ],
      'signee'=>[
        'name'=>'signee',
        'label'=>'Input Signee Name',
        'type'=>'input',
        'autofocus'=>true,
        'enterfunc'=>'saveSignee',
        'functype'=>'module',
        'readonly'=>false
      ],
      'signee2'=>[
        'name'=>'signee2',
        'label'=>'Input Signee Name',
        'type'=>'input',
        'autofocus'=>true,
        'enterfunc'=>'saveSignee',
        'functype'=>'module',
        'readonly'=>false
      ],
      'signee3'=>[
        'name'=>'signee3',
        'label'=>'Input Signee Name',
        'type'=>'input',
        'autofocus'=>true,
        'enterfunc'=>'saveSignee',
        'functype'=>'module',
        'readonly'=>false
      ]
    ];
  }

  public function getFields($fieldnames) {
    $txtfield = $this->othersClass->array_only($this->fields, $fieldnames);
    return $txtfield;
  }

  public function create($fieldnames) {
    $txtplot = [];
    $txtfield = [];
    $labeldata = [];
    $i = 0;
    $b = 0;
    $this->coreFunctions = new coreFunctions;
    foreach($fieldnames as $key => $value) {
      if (is_array($value)) {
        $tmp = [];
        foreach($value as $key2 => $value2) {
          $tmp = $this->othersClass->array_add($tmp, $key2, $value2);
          $txtfield[$i] = $value2;
          $i++;
        }
        $txtplot[$b] = $tmp;
        $b++;
      } else {
        $txtplot[$b] = $value;
        $b++;
        $txtfield[$i] = $value;
        $i++;
      }
    }
    $final = $this->getFields($txtfield);
    foreach($final as $key => $value) {
      if (isset($final[$key]['labeldata'])) {
        $labeldata = $this->othersClass->array_add($labeldata, $key, $final[$key]['labeldata']);
      }
      if ($final[$key]['type'] == 'mqselect') {
        $data = $this->coreFunctions->opentable($final[$key]['sql']);
        $new = $this->objtoarray($data);
        $final[$key]['options'] = $new;
      }
    }
    $final = $this->othersClass->array_add($final, 'plot', $txtplot);
    if (!empty($labeldata)) {
      $final = $this->othersClass->array_add($final, 'labeldata', $labeldata);
    }
    return $final;
  }

  private function objecttoarray($data) {
    $new = [];
    foreach($data as $key => $value) {
      array_push($new, $value->display);
    }
    return $new;
  }

  public function getFieldsPlot($data) {
    $fields = [];
    $plot = [];
    if(!empty($data)) {
      $fields = [];
      $plot = [];
      foreach($data as $key => $r) {
        if($key == 'plot') {
          foreach($r as $rr) array_push($plot, $rr);
        } else {
          array_push($fields, $r);
        }
      }
    }
    return ['fields'=>json_encode($fields), 'plot'=>json_encode($plot)];
  }
}