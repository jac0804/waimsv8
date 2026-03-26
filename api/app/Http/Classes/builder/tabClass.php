<?php


namespace App\Http\Classes\builder;

use DB;
use Exception;
use Throwable;
use App\Http\Classes\othersClass;
use App\Http\Classes\builder\gridcolumnClass;
use App\Http\Classes\builder\gridbuttonClass;

class tabClass
{

  private $tabs = [];
  private $columns = [];
  private $tabbuttons = [];
  private $stockbuttons = [];
  private $headgridbtns = [];
  private $othersClass;
  private $gridcolumnClass;
  private $gridbuttonClass;


  public function __construct() {} // end function

  public function tabArray()
  {
    $this->othersClass = new othersClass;
    $this->gridcolumnClass = new gridcolumnClass;
    $this->gridbuttonClass = new gridbuttonClass;



    //setup of tabs
    $this->tabs = array(
      'multigrid' => array(
        'label' => 'Test',
        'name' => 'Test',
        'obj' => 'multigrid',
        'columns' => ''
      ),
      'multigrid2' => array(
        'label' => 'Test',
        'name' => 'Test',
        'obj' => 'multigrid',
        'columns' => ''
      ),
      'multigrid3' => array(
        'label' => 'Test',
        'name' => 'Test',
        'obj' => 'multigrid',
        'columns' => ''
      ),
      'inventory' => array(
        'label' => 'Inventory',
        'name' => 'inventory',
        'obj' => 'stockentrygrid',
        'columns' => '',
        'totalfield' => 'ext',
        'descriptionrow' => ['itemname', 'barcode', 'Itemname'],
        'showtotal' => true
      ),
      'hrisdetail' => array(
        'label' => 'Detail',
        'name' => 'hrisdetail',
        'obj' => 'stockentrygrid',
        'columns' => '',
        'descriptionrow' => [],
        'showtotal' => false,
        'totalfield' => ''
      ),
      'serviceinventory' => array(
        'label' => 'Service Items',
        'name' => 'serviceinventory',
        'obj' => 'stockentrygrid',
        'columns' => '',
        'totalfield' => 'ext',
        'descriptionrow' => ['itemname', 'barcode', 'Itemname'],
        'showtotal' => true
      ),
      'adddocument' => array(
        'label' => 'Attachments',
        'name' => 'documents',
        'icon' => 'batch_prediction',
        'obj' => 'event',
        'class' => 'btnadddocument',
        'visible' => true
      ),
      'otherfees' => array(
        'label' => 'Other Fees',
        'name' => 'otherfees',
        'obj' => 'stockentrygrid',
        'columns' => '',
        'descriptionrow' => ['acnoname', 'acno', 'Account Name'],
        'showtotal' => true
      ),
      'credentials' => array(
        'label' => 'Credentials',
        'name' => 'credentials',
        'obj' => 'stockentrygrid',
        'columns' => '',
        'descriptionrow' => ['acnoname', 'acno', 'Account Name'],
        'showtotal' => true
      ),
      'accounting' => array(
        'label' => 'Accounting',
        'name' => 'accounting',
        'obj' => 'acctgentrygrid',
        'columns' => '',
        'descriptionrow' => ['acnoname', 'acno', 'Account Name']
      ),
      'customformacctg' => array(
        'label' => 'customformacctg',
        'name' => 'customformacctg',
        'columns' => '',
        'totalfield' => ['db', 'cr', 'bal'],
        'obj' => 'sbc_showgrid'
      ),
      'customformlisting' => array(
        'label' => 'customformlisting',
        'name' => 'customformlisting',
        'columns' => '',
        'obj' => 'sbc_showgrid'
      ),
      'voidgrid' => array(
        'label' => 'Inventory',
        'name' => 'inventory',
        'obj' => 'sbc_checkboxgrid',
        'columns' => ''
      ),
      'viewrefgrid' => array(
        'label' => 'Reference',
        'name' => 'inventory',
        'obj' => 'sbc_showgrid',
        'columns' => ''
      ),
      'sbc_showgrid2' => array(
        'label' => 'Reference',
        'name' => 'inventory',
        'obj' => 'sbc_showgrid',
        'columns' => ''
      ),
      'tableentry' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'tableentry2' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'viewdepsched' => array(
        'label' => 'Depreciation Schedule',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'customform' => array(
        'label' => 'Reference',
        'name' => 'customform',
        'obj' => 'customform',
        'action' => 'customform'
      ),
      'customform2' => array(
        'label' => 'Reference',
        'name' => 'customform',
        'obj' => 'customform',
        'action' => 'customform'
      ),
      'singleinput' => array(
        'label' => 'Reference',
        'name' => 'singleinput',
        'obj' => 'singleinput'
      ),
      'multiinput1' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput2' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput3' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput4' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput5' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput6' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput7' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput8' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput9' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'multiinput10' => array(
        'label' => 'Reference',
        'name' => 'multiinput',
        'obj' => 'multiinput'
      ),
      'qcardcss' => array(
        'label' => 'Reference',
        'name' => 'qcardcss',
        'obj' => 'qcardcss'
      ),

      'rwncomment' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),

      'carefdoc' => array(
        'label' => 'Reference Documents',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'editgrid' => array(
        'label' => 'Reference',
        'name' => 'editgrid',
        'obj' => 'editgrid',
        'columns' => '',
        'descriptionrow' => ['clientname', 'client'],
        'style' => 'max-height:410px; height:410px; overflow:auto;'
      ),
      'jobdesctab' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'charges' => array(
        'label' => 'Charges',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'skilldesctab' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'applisttab' => array(
        'label' => 'Applicant',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'passengertab' => array(
        'label' => 'Passenger',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'approvedusertab' => array(
        'label' => 'Passenger',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'remtab' => array(
        'label' => 'Remarks',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'designationtab' => array(
        'label' => 'Designation',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'earningdeductiontab' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'advancetab' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'manualpaymenttab' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'entrygrid' => array(
        'label' => 'Schedule List',
        'name' => 'entrygrid',
        'obj' => 'entrygrid',
        'columns' => ''
      ),
      'entrygrid2' => array(
        'label' => 'Schedule List',
        'name' => 'entrygrid',
        'obj' => 'entrygrid',
        'columns' => ''
      ),
      'stockinfotab' => array(
        'label' => 'Stock Info',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'stathistorytab' => array(
        'label' => 'Status History',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'notehistorytab' => array(
        'label' => 'Note History',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'calendar' => array(
        'label' => 'Calendar',
        'name' => 'calendar',
        'obj' => 'calendar'
      ),
      'calendar2' => array(
        'label' => 'Calendar',
        'name' => 'calendar',
        'obj' => 'calendar'
      ),
      'scurriculum' => array(
        'label' => 'CURRICULUM',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'archive' => array(
        'label' => 'ARCHIVE',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'reference' => array(
        'label' => 'Reference',
        'name' => 'inventory',
        'obj' => 'stockentrygrid',
        'columns' => '',
        'totalfield' => '',
        'descriptionrow' => [],
        'showtotal' => false
      ),
      'viewpcv' => array(
        'label' => 'PCV List',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'incomingpo' => array(
        'label' => 'INCOMING PO',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'viewprojref' => array(
        'label' => 'Reference',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'branchwh' => array(
        'label' => 'Warehouse',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'branchstation' => array(
        'label' => 'Station',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'branchbrand' => array(
        'label' => 'Brand',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'branchagent' => array(
        'label' => 'Agent',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'branchusers' => array(
        'label' => 'Users',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'branchbankterminal' => array(
        'label' => 'Bank Terminal',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
      'branchjob' => array(
        'label' => 'JOB LIST',
        'name' => 'tableentry',
        'obj' => 'tableentry',
        'columns' => ''
      ),
    );








    // setup of tab buttons
    $this->tabbuttons = array(
      'generateclient' => array(
        'label' => 'Create Profile',
        'icon' => 'batch_prediction',
        'class' => 'btngenerateclient',
        'lookupclass' => 'stockstatusposted',
        'action' => 'generateclient',
        'access' => 'save',
        'visible' => true
      ),
      'pendingcidetail' => array(
        'label' => 'COI',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingci',
        'lookupclass' => 'pendingcidetail',
        'action' => 'pendingcidetail',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['citrno']
      ),
      'pendingprrr' => array(
        'label' => 'RR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingprrr',
        'lookupclass' => 'pendingprrr',
        'action' => 'pendingprrr',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingpr' => array(
        'label' => 'PR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpr',
        'lookupclass' => 'pendingprsummary',
        'action' => 'pendingprsummary',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['yourref']
      ),
      'pendingcd' => array(
        'label' => 'CANVASS',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingcd',
        'lookupclass' => 'pendingcdsummary',
        'action' => 'pendingcdsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingrr' => array(
        'label' => 'RR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingrr',
        'lookupclass' => 'pendingrrsummary',
        'action' => 'pendingrrsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingrrsn' => array(
        'label' => 'Pending RR',
        'icon' => 'batch_prediction',
        'class' => 'pendingrrsn',
        'lookupclass' => 'pendingrrsnsummary',
        'action' => 'pendingrrsnsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingpn' => array(
        'label' => 'PN',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpn',
        'lookupclass' => 'pendingpnsummary',
        'action' => 'pendingpnsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingop' => array(
        'label' => 'Sales Activity',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingop',
        'lookupclass' => 'pendingopsummary',
        'action' => 'pendingopsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingrp' => array(
        'label' => 'Packing List RR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingrp',
        'lookupclass' => 'pendingrpsummary',
        'action' => 'pendingrpsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingpo' => array(
        'label' => 'PO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpo',
        'lookupclass' => 'pendingposummary',
        'action' => 'pendingposummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingat' => array(
        'label' => 'AC',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingat',
        'lookupclass' => 'pendingatsummary',
        'action' => 'pendingatsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'poreqpayment' => array(
        'label' => 'PO',
        'icon' => 'batch_prediction',
        'class' => 'btnporeqpayment',
        'lookupclass' => 'pendingporeqpaydetail',
        'action' => 'pendingporeqpaydetail',
        'access' => 'additem',
        'visible' => true
      ),
      'paymentreleased' => array(
        'label' => 'VOID',
        'icon' => 'batch_prediction',
        'class' => 'btnpaymentreleased',
        'lookupclass' => 'pendingpaymentreleased',
        'action' => 'pendingpaymentreleased',
        'access' => 'additem',
        'visible' => true
      ),
      'eggitems' => array(
        'label' => 'Egg Items',
        'icon' => 'batch_prediction',
        'class' => 'btneggitems',
        'lookupclass' => 'eggitemsummary',
        'action' => 'eggitemsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingjb' => array(
        'label' => 'JO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingjb',
        'lookupclass' => 'pendingjbsummary',
        'action' => 'pendingjbsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingparts' => array(
        'label' => 'Parts Request',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingparts',
        'lookupclass' => 'pendingpartssummary',
        'action' => 'pendingpartssummary',
        'access' => 'additem',
        'visible' => true
      ),
      'criticalstocks' => array(
        'label' => 'Critical Stocks',
        'icon' => 'batch_prediction',
        'class' => 'btncriticalstocks',
        'lookupclass' => 'criticalstocks',
        'action' => 'criticalstocks',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingmr' => array(
        'label' => 'MR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingmr',
        'lookupclass' => 'pendingmrsummary',
        'action' => 'pendingmrsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingwa' => array(
        'label' => 'Warrany Request',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingwa',
        'lookupclass' => 'pendingwasummary',
        'action' => 'pendingwasummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingsq' => array(
        'label' => 'SO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingsq',
        'lookupclass' => 'pendingsqsummary',
        'action' => 'pendingsqsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingsqpo' => array(
        'label' => 'SO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingsqpo',
        'lookupclass' => 'pendingsqposummary',
        'action' => 'pendingsqpodetail',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['sotrno']
      ),
      'pendingso' => array(
        'label' => 'SO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingso',
        'lookupclass' => 'pendingsosummary',
        'action' => 'pendingsosummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingoq' => array(
        'label' => 'OQ',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingoq',
        'lookupclass' => 'pendingoqsummary',
        'action' => 'pendingoqsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingsj' => array(
        'label' => 'SJ',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingsj',
        'lookupclass' => 'pendingsjsummary',
        'action' => 'pendingsjsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingsi' => array(
        'label' => 'SS',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingsi',
        'lookupclass' => 'pendingsisummary',
        'action' => 'pendingsisummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingdr' => array(
        'label' => 'DR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingdr',
        'lookupclass' => 'pendingdrsummary',
        'action' => 'pendingdrsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingtr' => array(
        'label' => 'TR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingtr',
        'lookupclass' => 'pendingtrsummary',
        'action' => 'pendingtrsummary',
        'access' => 'additem',
        'visible' => true
      ),

      'getbouncedar' => array(
        'label' => 'Bounced Cheque',
        'icon' => 'batch_prediction',
        'class' => 'btngetbouncedar',
        'lookupclass' => 'getbouncedardetail',
        'action' => 'getbouncedardetail',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingpl' => array(
        'label' => 'PL',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpl',
        'lookupclass' => 'pendingplsummary',
        'action' => 'pendingplsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingwb' => array(
        'label' => 'WAYBILL',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingwb',
        'lookupclass' => 'pendingwbsummary',
        'action' => 'pendingwbsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingsplitqty' => array(
        'label' => 'SPLIT QTY - WHMAN',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingsplitqty',
        'lookupclass' => 'pendingsplitqtydetail',
        'action' => 'pendingsplitqtydetail',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingsplitqtypicker' => array(
        'label' => 'PICKER ADJ.',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingsplitqtypicker',
        'lookupclass' => 'pendingsplitqtypicker',
        'action' => 'pendingsplitqtypicker',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['whid']
      ),
      'poserial' => array(
        'label' => 'POSerial',
        'icon' => 'add_box',
        'class' => 'btnadditem',
        'lookupclass' => 'poserial',
        'action' => 'poserial',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingqt' => array(
        'label' => 'Quotation',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingqt',
        'lookupclass' => 'pendingqtsummary',
        'action' => 'pendingqtsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingqs' => array(
        'label' => 'SO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingqs',
        'lookupclass' => 'pendingqssummary',
        'action' => 'pendingqssummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingao' => array(
        'label' => 'SO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingao',
        'lookupclass' => 'pendingaosummary',
        'action' => 'pendingaosummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingbr' => array(
        'label' => 'BR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingbr',
        'lookupclass' => 'pendingbrdetails',
        'action' => 'pendingbrdetails',
        'access' => 'additem',
        'visible' => true
      ),
      'subactivity' => array(
        'label' => 'SUBACTIVITY',
        'icon' => 'add_box',
        'class' => 'btnsubactivity',
        'lookupclass' => 'subactivitydetails',
        'action' => 'subactivitydetails',
        'access' => 'additem',
        'visible' => true
      ),
      'createversion' => array(
        'label' => 'C.Version',
        'icon' => 'add_box',
        'class' => 'btncreateversion',
        'lookupclass' => 'createversion',
        'action' => 'createversion',
        'access' => 'additem',
        'visible' => true
      ),
      'viewversion' => array(
        'label' => 'ViewVersion',
        'icon' => 'add_box',
        'class' => 'btnviewversion',
        'lookupclass' => 'entryviewrevision',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'additem' => array(
        'label' => 'Add Item',
        'icon' => 'add_box',
        'class' => 'btnadditem',
        'lookupclass' => 'additem',
        'action' => 'additem',
        'access' => 'additem',
        'visible' => true,
        'islocalsave' => true
      ),

      'threshold' => array(
        'label' => 'Threshold',
        'icon' => 'batch_prediction',
        'class' => 'btnthreshold',
        'lookupclass' => 'threshold',
        'action' => 'threshold',
        'access' => 'additem',
        'visible' => true
      ),

      'itemlookup' => array(
        'label' => 'Add Item',
        'icon' => 'add_box',
        'class' => 'btnitemlookup',
        'lookupclass' => 'itemlookup',
        'action' => 'itemlookup',
        'access' => 'additem',
        'visible' => true
      ),

      'prtagged' => array(
        'label' => 'PR',
        'icon' => 'batch_prediction',
        'class' => 'btnprtagged',
        'lookupclass' => 'prtagged',
        'action' => 'prtagged',
        'access' => 'additem',
        'visible' => true
      ),

      'drtagged' => array(
        'label' => 'TAG DR',
        'icon' => 'batch_prediction',
        'class' => 'btndrtagged',
        'lookupclass' => 'drtagged',
        'action' => 'drtagged',
        'access' => 'additem',
        'visible' => true
      ),

      'dntagged' => array(
        'label' => 'TAG DR RETURN',
        'icon' => 'batch_prediction',
        'class' => 'btndntagged',
        'lookupclass' => 'dntagged',
        'action' => 'dntagged',
        'access' => 'additem',
        'visible' => true
      ),

      'sotagged' => array(
        'label' => 'TAG SO',
        'icon' => 'batch_prediction',
        'class' => 'btnsotagged',
        'lookupclass' => 'sotagged',
        'action' => 'sotagged',
        'access' => 'additem',
        'visible' => true
      ),
      'addrow' => array(
        'label' => 'New Row',
        'icon' => 'add_box',
        'class' => 'btnaddrow',
        'lookupclass' => 'addrow',
        'action' => 'addrow',
        'access' => 'additem',
        'visible' => true
      ),
      'saveallentry' => array(
        'label' => 'Save all',
        'icon' => 'save',
        'class' => 'btnsaveallentry',
        'lookupclass' => 'loaddata',
        'action' => 'saveallentry',
        'access' => 'saveallentry',
        'visible' => true
      ),
      'assignbarcode' => array(
        'label' => 'Assign Barcode',
        'icon' => 'save',
        'class' => 'btnassignbarcode',
        'lookupclass' => 'assignbarcode',
        'action' => 'saveallentry',
        'access' => 'saveallentry',
        'visible' => true
      ),
      'unmarkall' => array(
        'label' => 'Unmark All',
        'icon' => 'close',
        'class' => 'btnunmarkall',
        'lookupclass' => 'loaddata',
        'action' => 'unmarkall',
        'access' => 'saveallentry',
        'visible' => true
      ),
      'approved' => array(
        'label' => 'APPROVE',
        'icon' => 'check',
        'class' => 'btnsaveallentry',
        'lookupclass' => 'loaddata',
        'action' => 'approved',
        'access' => 'approved',
        'visible' => true
      ),
      'disapproved' => array(
        'label' => 'DISAPPROVE',
        'icon' => 'close',
        'class' => 'btnsaveallentry',
        'lookupclass' => 'loaddata',
        'action' => 'disapproved',
        'access' => 'disapproved',
        'visible' => true
      ),
      'print' => array(
        'label' => 'Print',
        'icon' => 'print',
        'class' => 'btnprint',
        'lookupclass' => 'print',
        'action' => 'print',
        'access' => 'print',
        'visible' => true
      ),
      'additemcomponent' => array(
        'label' => 'Add Item',
        'icon' => 'add_box',
        'class' => 'btnadditemcomponent',
        'lookupclass' => 'additemcomponent',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'addbooks' => array(
        'label' => 'Add Books',
        'icon' => 'add_box',
        'class' => 'btnadditem',
        'lookupclass' => 'addbooks',
        'action' => 'addbooks',
        'access' => 'additem',
        'visible' => true
      ),
      'quickadd' => array(
        'label' => 'Quick Add',
        'icon' => 'flash_on',
        'class' => 'btnquickadd',
        'lookupclass' => 'quickadditem',
        'action' => 'quickadditem',
        'access' => 'additem',
        'visible' => true
      ),
      'scanpallet' => array(
        'label' => 'Scan Pallet',
        'icon' => 'flash_on',
        'class' => 'btnscanpallet',
        'lookupclass' => 'scanpallet',
        'action' => 'scanpallet',
        'access' => 'additem',
        'visible' => true
      ),
      'scanbarcode' => array(
        'label' => 'Scan Barcode',
        'icon' => 'flash_on',
        'class' => 'btnscanbarcode',
        'lookupclass' => 'scantext',
        'action' => 'scanbarcode',
        'access' => 'additem',
        'visible' => true
      ),
      'scanlocationrr' => array(
        'label' => 'Scan',
        'icon' => 'flash_on',
        'class' => 'btnscanlocationrr',
        'lookupclass' => 'scantext',
        'action' => 'scanlocationrr',
        'access' => 'additem',
        'visible' => true
      ),
      'scanbox' => array(
        'label' => 'Scan Box / SKU',
        'icon' => 'flash_on',
        'class' => 'btnscanbox',
        'lookupclass' => 'scantext',
        'action' => 'scanbox',
        'access' => 'view',
        'visible' => true,
        'addedaction' => ['action' => 'lookupdispatchbarcode', 'lookupclass' => 'dispatchbarcode']
      ),
      'scanlocation' => array(
        'label' => 'Scan Location',
        'icon' => 'flash_on',
        'class' => 'btnscanlocation',
        'lookupclass' => 'scanlocation',
        'action' => 'scanlocation',
        'access' => 'edit',
        'visible' => true
      ),
      'changelocation' => array(
        'name' => 'backlisting',
        'label' => 'Change Location',
        'icon' => 'import_export',
        'class' => 'btnchangelocation',
        'lookupclass' => 'whpickerchangeloc',
        'action' => 'customform',
        'access' => 'edit',
        'visible' => true,
        'addedparams' => ['trno', 'line', 'sjtype', 'isqty', 'drqty']
      ),
      'splitqtypicker' => array(
        'name' => 'backlisting',
        'label' => 'Split Qty',
        'icon' => 'call_split',
        'class' => 'btnsplitqtypicker',
        'lookupclass' => 'whpickersplitqty',
        'action' => 'customform',
        'access' => 'edit',
        'visible' => true,
        'addedparams' => ['trno', 'line', 'sjtype']
      ),
      'saveitem' => array(
        'label' => 'Save Item',
        'icon' => 'save',
        'class' => 'btnsaveitem',
        'lookupclass' => 'edititem',
        'action' => 'saveitem',
        'access' => 'edititem',
        'visible' => true
      ),
      'savesched' => array(
        'label' => 'Save Sched',
        'icon' => 'save',
        'class' => 'btnsaveitem',
        'lookupclass' => 'edititem',
        'action' => 'saveitem',
        'access' => 'edititem',
        'visible' => true
      ),
      'deleteallitem' => array(
        'label' => 'Delete Item',
        'icon' => 'delete',
        'class' => 'btndeleteallitem',
        'lookupclass' => 'deleteallitem',
        'action' => 'deleteallitem',
        'access' => 'deleteitem',
        'visible' => true
      ),
      'deleteallsched' => array(
        'label' => 'Delete Sched',
        'icon' => 'delete',
        'class' => 'btndeleteallitem',
        'lookupclass' => 'deleteallitem',
        'action' => 'deleteallitem',
        'access' => 'deleteitem',
        'visible' => true
      ),
      'saveall' => array(
        'label' => 'SAVE CHANGES',
        'icon' => 'save',
        'class' => 'btntableentry btnsaveall',
        'lookupclass' => 'loaddata',
        'action' => 'getdata',
        'access' => 'post',
        'visible' => true,
        'confirm' => true,
        'confirmlabel' => 'Save Changes?'
      ),
      'approveall' => array(
        'label' => 'Approve All',
        'icon' => 'check',
        'class' => 'btntableentry btnapproveall',
        'lookupclass' => 'loaddata',
        'action' => 'approveall',
        'access' => 'post',
        'visible' => true
      ),
      'approveallreq' => array(
        'label' => 'Approve All Requested Qty',
        'icon' => 'check',
        'class' => 'btntableentry btnapproveallreq',
        'lookupclass' => 'loaddata',
        'action' => 'approveallreq',
        'access' => 'post',
        'visible' => true
      ),
      'generatepr' => array(
        'label' => 'Generate PR',
        'icon' => 'check',
        'class' => 'btntableentry btngeneratepr',
        'lookupclass' => 'loaddata',
        'action' => 'approveall',
        'access' => 'post',
        'visible' => true
      ),
      'unpaid' => array(
        'label' => 'UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaid',
        'lookupclass' => 'unpaid',
        'action' => 'unpaid',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaiddm' => array(
        'label' => 'RETURN',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaid',
        'lookupclass' => 'unpaiddm',
        'action' => 'unpaid',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidall' => array(
        'label' => 'ALL UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaidall',
        'lookupclass' => 'unpaidall',
        'action' => 'unpaid',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidchild' => array(
        'label' => 'UNPAID CHILD ACCNT.',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaidchild',
        'lookupclass' => 'unpaidchild',
        'action' => 'unpaid',
        'access' => 'additem',
        'visible' => true
      ),
      'checks' => array(
        'label' => 'RECEIVED CHECKS',
        'icon' => 'batch_prediction',
        'class' => 'btncheck',
        'lookupclass' => 'checks',
        'action' => 'checks',
        'access' => 'additem',
        'visible' => true
      ),

      'projchecks' => array(
        'label' => 'PROJ CHECKS',
        'icon' => 'batch_prediction',
        'class' => 'btncheck',
        'lookupclass' => 'projchecks',
        'action' => 'projchecks',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['projectid']
      ),
      'addrecord' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btntableentry btnaddrecord',
        'lookupclass' => 'add',
        'action' => 'add',
        'access' => 'additem',
        'visible' => true
      ),
      'multiaction' => array(
        'label' => 'multiaction',
        'icon' => 'add_box',
        'class' => 'btntableentry btnaddrecord',
        'lookupclass' => ['deletetriggers', 'dataupdatewaims', 'tableupdatewaims', 'tableupdatewaims2', 'tableupdatehms', 'tableupdateenrollment', 'tableupdatecustomersupport', 'tableupdatehris', 'tableupdatepayroll', 'tableupdatewarehousing', 'tableupdatedocumentmanagement', 'tableupdatefams', 'tableupdatevsched', 'tableupdatepos', 'tableupdatebms', 'reindex', 'createtriggers', 'modifyLengthField', 'cleardb_proc'], //,2023.01.20 FMM removed - 'modifyLengthField' - databases are already updated
        'action' => 'multiaction',
        'access' => 'additem',
        'visible' => true
      ),
      'updatepostatus' => array(
        'label' => 'Update PO/SO Status',
        'icon' => 'save',
        'class' => 'btnupdatepostatus',
        'lookupclass' => 'updatepostatus',
        'action' => 'updatepostatus',
        'access' => 'additem',
        'visible' => true
      ),
      'recalc' => array(
        'label' => 'Recalc',
        'icon' => 'save',
        'class' => 'btnrecalc',
        'lookupclass' => 'recalc',
        'action' => 'recalc',
        'access' => 'additem',
        'visible' => true
      ),
      'addserial' => array(
        'label' => 'Add Serial',
        'icon' => 'add_box',
        'class' => 'btntableentry btnaddrecord',
        'lookupclass' => 'add',
        'action' => 'addserial',
        'access' => 'additem',
        'visible' => true
      ),
      'addserialinout' => array(
        'label' => 'Add Serial',
        'icon' => 'add_box',
        'class' => 'btntableentry btnaddrecord',
        'lookupclass' => 'lookupserial',
        'lookupclass2' => 'lookupserial',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'generatestudcurriculum' => array(
        'label' => 'GENERATE CURRICULUM',
        'icon' => 'batch_prediction',
        'class' => 'btnheadstock',
        'lookupclass' => 'loaddata',
        'action' => 'generatecurriculum',
        'access' => 'extract',
        'visible' => true
      ),
      'archivestudcurriculum' => array(
        'label' => 'ARCHIVE CURRICULUM',
        'icon' => 'folder_special',
        'class' => 'btnheadstock',
        'lookupclass' => 'loaddata',
        'action' => 'archivecurriculum',
        'access' => 'additem',
        'visible' => true
      ),
      'viewar' => array(
        'label' => 'A/R',
        'icon' => 'insert_chart_outlined',
        'class' => 'btnviewar',
        'lookupclass' => 'viewar',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewap' => array(
        'label' => 'A/P',
        'icon' => 'folder_special',
        'class' => 'btnviewap',
        'lookupclass' => 'viewap',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewpdc' => array(
        'label' => 'PDC',
        'icon' => 'insert_invitation',
        'class' => 'btnviewpdc',
        'lookupclass' => 'viewpdc',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewrc' => array(
        'label' => 'RC',
        'icon' => 'money_off',
        'class' => 'btnviewrc',
        'lookupclass' => 'viewrc',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewinv' => array(
        'label' => 'Inventory',
        'icon' => 'batch_prediction',
        'class' => 'btnviewinv',
        'lookupclass' => 'viewcustomerinv',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewstockcardtransactionledger' => array(
        'label' => 'History',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardtransactionledger',
        'lookupclass' => 'viewstockcardtransactionledger',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewstockcardrr' => array(
        'label' => 'IN-Transaction',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardrr',
        'lookupclass' => 'viewstockcardrr',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewserialhistory' => array(
        'label' => 'Serial',
        'icon' => 'batch_prediction',
        'class' => 'btnviewserialhistory',
        'lookupclass' => 'viewserialhistory',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewenginehistory' => array(
        'label' => 'Serial',
        'icon' => 'batch_prediction',
        'class' => 'btnviewenginehistory',
        'lookupclass' => 'viewenginehistory',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'entrystockcarduom' => array(
        'label' => 'UOM',
        'icon' => 'batch_prediction',
        'class' => 'btnentrystockcarduom',
        'lookupclass' => 'entryuom',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'entrystockcardcompatible' => array(
        'label' => 'COMPATIBLE',
        'icon' => 'batch_prediction',
        'class' => 'btnentrystockcardcompatible',
        'lookupclass' => 'stockcardcompatible',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'pickcompatible' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnpickcompatible',
        'lookupclass' => 'pickcompatible',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'assignuser' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnassignuser',
        'lookupclass' => 'assignuser',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'assignmodule' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnassignmodule',
        'lookupclass' => 'assignmodule',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'addrefdoc' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnaddrefdoc',
        'lookupclass' => 'addrefdoc',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'addreqcat' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnaddreqcat',
        'lookupclass' => 'addreqcat',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'adduserapprover' => array(
        'label' => 'ADD APPROVER',
        'icon' => 'batch_prediction',
        'class' => 'btnadduserapprover',
        'lookupclass' => 'adduserapprover',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'adddept' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnadddept',
        'lookupclass' => 'adddept',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'pickitemcompatible' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnpickitemcompatible',
        'lookupclass' => 'pickitemcompatible',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'entryitemsubcat' => array(
        'label' => 'ADD',
        'icon' => 'batch_prediction',
        'class' => 'btnentryitemsubcat',
        'lookupclass' => 'entryitemsubcat',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'viewstockcardwh' => array(
        'label' => 'WH',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardwh',
        'lookupclass' => 'viewstockcardwh',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewstockcardpo' => array(
        'label' => 'PO',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardpo',
        'lookupclass' => 'viewstockcardpo',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),

      'viewstockcardpr' => array(
        'label' => 'PR',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardpr',
        'lookupclass' => 'viewstockcardpr',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewstockcardso' => array(
        'label' => 'SO',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardso',
        'lookupclass' => 'viewstockcardso',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewstockcardcomponent' => array(
        'label' => 'COMPONENTS',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardcomponent',
        'lookupclass' => 'entrycomponent',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'generateewt' => array(
        'label' => 'Generate EWT',
        'icon' => 'batch_prediction',
        'class' => 'btngenerateewt',
        'lookupclass' => 'stockstatus',
        'action' => 'generateewt',
        'access' => 'edititem',
        'visible' => true
      ),
      'viewstockcardstocklevel' => array(
        'label' => 'STOCK LEVEL',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstockcardstocklevel',
        'lookupclass' => 'entrystocklevel',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'addwarehouse' => array(
        'label' => 'Add Warehouse',
        'icon' => 'add_box',
        'class' => 'btnaddwarehouse',
        'lookupclass' => 'addwarehouse',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'addbranch' => array(
        'label' => 'Add Branch',
        'icon' => 'add_box',
        'class' => 'btnaddbranch',
        'lookupclass' => 'addbranch',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidkr' => array(
        'label' => 'UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnunpaidkr',
        'lookupclass' => 'getkr',
        'action' => 'getkr',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidkrall' => array(
        'label' => 'PENDING AR',
        'icon' => 'batch_prediction',
        'class' => 'btnunpaidkrall',
        'lookupclass' => 'getkrall',
        'action' => 'getkrall',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidkp' => array(
        'label' => 'UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnunpaidkp',
        'lookupclass' => 'getkp',
        'action' => 'getkp',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidka' => array(
        'label' => 'UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnunpaidka',
        'lookupclass' => 'getka',
        'action' => 'getka',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidpy' => array(
        'label' => 'UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnunpaidpy',
        'lookupclass' => 'getpy',
        'action' => 'getpy',
        'access' => 'additem',
        'visible' => true
      ),
      'viewapprovetrqty' => array(
        'label' => 'Approve Qty',
        'icon' => 'batch_prediction',
        'class' => 'btnviewapprovetrqty',
        'lookupclass' => 'entryapprovetrqty',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'activitymaster' => array(
        'label' => 'Activity',
        'icon' => 'batch_prediction',
        'class' => 'btnactivitymaster',
        'lookupclass' => 'activitymaster',
        'action' => 'activitymaster',
        'access' => 'additem',
        'visible' => true
      ),
      'unpaidmccollection' => array(
        'label' => 'UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaid',
        'lookupclass' => 'unpaidmccollection',
        'action' => 'unpaid',
        'access' => 'additem',
        'visible' => true
      ),
      'mccollection' => array(
        'label' => 'MC',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaid',
        'lookupclass' => 'getmccollection',
        'action' => 'lookupmccollection',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingmi' => array(
        'label' => 'TAG MI',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaid',
        'lookupclass' => 'getpendingmi',
        'action' => 'lookuppendingmi',
        'access' => 'additem',
        'visible' => true
      ),
      'pendigco' => array(
        'label' => 'CO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendigco',
        'lookupclass' => 'getpendigco',
        'action' => 'lookuppendigco',
        'access' => 'additem',
        'visible' => true
      ),

      'pendingcor' => array(
        'label' => 'COR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingcor',
        'lookupclass' => 'getpendingco',
        'action' => 'lookuppengdigcor',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['client']
      ),
      'viewloan' => array(
        'label' => 'LOAN HISTORY',
        'icon' => 'batch_prediction',
        'class' => 'btnviewloan',
        'lookupclass' => 'viewloan',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true,
      ),
      'viewloansched' => array(
        'label' => 'LOAN SCHEDULE',
        'icon' => 'batch_prediction',
        'class' => 'btnviewloansched',
        'lookupclass' => 'viewloansched',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true,
      ),

      //Enrollment
      'viewstudenthistory' => array(
        'label' => 'History',
        'icon' => 'history',
        'class' => 'btnviewstudenthistory',
        'lookupclass' => 'entrystudenthistory',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => true
      ),
      'viewstudentcredentials' => array(
        'label' => 'Credentials',
        'icon' => 'batch_prediction',
        'class' => 'btnviewstudentcredentials',
        'lookupclass' => 'entrystudentcredentials',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => true
      ),
      'addstudentcredential' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnaddstudentcredential',
        'lookupclass' => 'addstudentcredential',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'viewsubject' => array(
        'label' => 'Subjects',
        'icon' => 'batch_prediction',
        'class' => 'btnsubject',
        'lookupclass' => 'entrysubject',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => true
      ),
      'addsubject' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addsubject',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'generateestud' => array(
        'label' => 'Generate Enrolled Students',
        'icon' => 'miscellaneous_services',
        'class' => 'btnheadstock',
        'lookupclass' => 'loaddata',
        'action' => 'generateestud',
        'access' => 'additem',
        'visible' => true
      ),
      'addcomponent' => array(
        'label' => 'Add Component',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupcomponent',
        'action' => 'lookupcomponent',
        'access' => 'additem',
        'visible' => true
      ),
      'addgecomponent' => array(
        'label' => 'Add Component',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupgecomponent',
        'action' => 'lookupgecomponent',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['schedtrno', 'schedline']
      ),
      'addcredentials' => array(
        'label' => 'Add Credentials',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupaddcredentials',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'addotherfees' => array(
        'label' => 'Other Fees',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupaddotherfees',
        'action' => 'lookupsetup',
        'action2' => 'addas',
        'access' => 'view',
        'visible' => true
      ),
      'addstudents' => array(
        'label' => 'Select Students',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupaddstudents',
        'action' => 'lookupsetup',
        'action2' => 'addas',
        'access' => 'view',
        'visible' => true
      ),
      'getschedule' => array(
        'label' => 'Select Sched',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupsched',
        'action' => 'lookupsched',
        'access' => 'additem',
        'visible' => true,
      ),
      'duplicatecc' => array(
        'label' => 'Duplicate Curriculum',
        'icon' => 'folder_special',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupcurriculum',
        'action' => 'lookupcurriculum',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['trno']
      ),
      'duplicategecomp' => array(
        'label' => 'Duplicate Component',
        'icon' => 'folder_special',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupgecomp',
        'action' => 'lookupgecomp',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['syid', 'periodid', 'courseid']
      ),
      'getreg' => array(
        'label' => 'Select Reg',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupreg',
        'action' => 'lookupreg',
        'access' => 'additem',
        'visible' => true,
      ),
      'getassessment' => array(
        'label' => 'Assessment',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupassessment',
        'action' => 'lookupassessment',
        'access' => 'additem',
        'visible' => true,
      ),

      //ENROLLMENT
      'generateattendance' => array(
        'label' => 'GENERATE ATTENDANCE',
        'icon' => 'calendar_today',
        'class' => 'btnheadstock',
        'lookupclass' => 'stockstatus',
        'action' => 'generateattendance',
        'access' => 'additem',
        'visible' => true
      ),
      'generatesubject' => array(
        'label' => 'GENERATE SUBJECT',
        'icon' => 'batch_prediction',
        'class' => 'btnheadstock',
        'lookupclass' => 'stockstatus',
        'action' => 'generatesubject',
        'access' => 'additem',
        'visible' => true
      ),
      'reportcard' => array(
        'label' => 'Report Card Setup',
        'icon' => 'article',
        'class' => 'btntableentry',
        'lookupclass' => 'entryreportcardsetup',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => true
      ),
      'generatecurriculum' => array(
        'label' => 'GENERATE',
        'icon' => 'batch_prediction',
        'class' => 'btntableentry',
        'lookupclass' => 'lookupcurriculum',
        'action' => 'lookupcurriculum',
        'access' => 'view',
        'visible' => true
      ),
      'getregstudent' => array(
        'label' => 'Select Student',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'lookupregstudent',
        'action' => 'lookupregstudent',
        'access' => 'additem',
        'visible' => true,
      ),

      //HRIS
      'viewdep' => array(
        'label' => 'Dependents',
        'icon' => 'batch_prediction',
        'class' => 'btnempdependent',
        'lookupclass' => 'entryappdependents',
        'action' => 'hrisentry',
        'access' => 'view',
        'visible' => true
      ),
      'vieweduc' => array(
        'label' => 'Education',
        'icon' => 'batch_prediction',
        'class' => 'btnempeducation',
        'lookupclass' => 'entryappeducation',
        'action' => 'hrisentry',
        'access' => 'view',
        'visible' => true
      ),
      'viewemployment' => array(
        'label' => 'Employment',
        'icon' => 'batch_prediction',
        'class' => 'btnempemployment',
        'lookupclass' => 'entryappemployment',
        'action' => 'hrisentry',
        'access' => 'view',
        'visible' => true
      ),
      'viewrequirements' => array(
        'label' => 'Requirements',
        'icon' => 'batch_prediction',
        'class' => 'btnempreq',
        'lookupclass' => 'entryappreq',
        'action' => 'hrisentry',
        'access' => 'view',
        'visible' => true
      ),
      'addemprequire' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addemprequire',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'viewemptest' => array(
        'label' => 'Pre-employment Test',
        'icon' => 'batch_prediction',
        'class' => 'btnemptest',
        'lookupclass' => 'entryapptest',
        'action' => 'hrisentry',
        'access' => 'view',
        'visible' => true
      ),
      'addemppretest' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addemppretest',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingturnoveritems' => array(
        'label' => 'Turn Over of Items',
        'icon' => 'batch_prediction',
        'class' => 'btnheadstock',
        'lookupclass' => 'pendingturnoveritems',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'jobskillreq' => array(
        'label' => 'Skill Requirements',
        'icon' => 'batch_prediction',
        'class' => 'btnheadstock',
        'lookupclass' => 'entryskillreq',
        'action' => 'hrisentry',
        'access' => 'view',
        'visible' => true
      ),
      'addskillreq' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addskillreq',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'addapplist' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addapplist',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),

      'addempgrid' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addempgrid',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'jobdesc' => array(
        'label' => 'JOB DESCRIPTION',
        'icon' => 'batch_prediction',
        'class' => 'btnheadstock',
        'lookupclass' => 'entryjobdesc',
        'action' => 'hrisentry',
        'access' => 'additem',
        'visible' => true
      ),
      'addquestion' => array(
        'label' => 'Add Question',
        'icon' => 'add_box',
        'class' => 'btnaddquestion',
        'lookupclass' => 'addquestion',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true,
        'addedparams' => ['objtype']
      ),
      'viewincidentmemo' => array(
        'label' => 'Incident Memo',
        'icon' => 'batch_prediction',
        'class' => 'btnviewincidentmemo',
        'lookupclass' => 'viewincidentmemo',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewnoticetoexplain' => array(
        'label' => 'Notice to Explain',
        'icon' => 'batch_prediction',
        'class' => 'btnviewnoticetoexplain',
        'lookupclass' => 'viewnoticetoexplain',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewnoticedisciplinary' => array(
        'label' => 'Notice of Disciplinary',
        'icon' => 'batch_prediction',
        'class' => 'btnviewnoticedisciplinary',
        'lookupclass' => 'viewnoticedisciplinary',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'viewempstatchangehistory' => array(
        'label' => 'Details',
        'icon' => 'batch_prediction',
        'class' => 'btnviewempstatchangehistory',
        'lookupclass' => 'viewempstatchangehistory',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'adddocument' => array(
        'label' => 'Documents',
        'icon' => 'batch_prediction',
        'class' => 'btnadddocument',
        'lookupclass' => 'entrycntnumpicture',
        'action' => 'documententry',
        'access' => 'view',
        'visible' => true
      ),
      'viewtodo' => array(
        'label' => 'To Do',
        'icon' => 'batch_prediction',
        'class' => 'btnviewtodo',
        'lookupclass' => 'entrycntnumtodo',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'viewratesetupdetail' => array(
        'label' => 'Details',
        'icon' => 'batch_prediction',
        'class' => 'btnviewratesetupdetail',
        'lookupclass' => 'viewratesetupdetail',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'entrypayrollsetup' => array(
        'label' => 'ENTRY',
        'icon' => 'batch_prediction',
        'class' => 'btnEntrypayrollsetup',
        'lookupclass' => 'entrypayrollsetup',
        'action' => 'hrisentry',
        'access' => 'additem',
        'visible' => true
      ),
      'otherfees' => array(
        'label' => 'Other Fees',
        'icon' => 'batch_prediction',
        'class' => 'btnadddocument',
        'lookupclass' => 'entryotherfees',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => true
      ),
      'credentials' => array(
        'label' => 'Credentials',
        'icon' => 'batch_prediction',
        'class' => 'btnadddocument',
        'lookupclass' => 'entrycredentials',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => true
      ),
      'assummary' => array(
        'label' => 'Summary',
        'icon' => 'batch_prediction',
        'class' => 'btnadddocument',
        'lookupclass' => 'entryassummary',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => true
      ),
      'postinout' => array(
        'label' => 'POST ACTUAL IN/OUT',
        'icon' => 'batch_prediction',
        'class' => 'btnadddocument',
        'lookupclass' => 'loaddata',
        'action' => 'postinout',
        'access' => 'postinout',
        'visible' => true
      ),
      //hms
      'addratecode' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addratecode',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'viewroomrates' => array(
        'label' => 'RATES',
        'icon' => 'batch_prediction',
        'class' => 'btnviewroomrates',
        'lookupclass' => 'entryrates',
        'action' => 'hmsentry',
        'access' => 'additem',
        'visible' => true
      ),
      'addroom' => array(
        'label' => 'ROOM LIST',
        'icon' => 'batch_prediction',
        'class' => 'btnaddroom',
        'lookupclass' => 'entryroomlist',
        'action' => 'hmsentry',
        'access' => 'additem',
        'visible' => true
      ),
      //end of hms


      'viewpayrollsetupprocess' => array(
        'label' => 'VIEW PROCESS',
        'icon' => 'batch_prediction',
        'class' => 'btnviewpayrollsetupprocess',
        'lookupclass' => 'viewpayrollsetupprocess',
        'action' => 'viewpayrollsetupprocess',
        'access' => 'view',
        'visible' => true,
        'addedparams' => ['empid', 'batchid', 'empname']
      ),
      'createschedule' => array(
        'label' => 'creates chedule',
        'icon' => 'batch_prediction',
        'class' => 'btncreateschedule',
        'lookupclass' => 'stockstatus',
        'action' => 'createschedule',
        'access' => 'view',
        'visible' => true
      ),

      'defaults' => array(
        'label' => 'DEFAULT',
        'icon' => 'add_box',
        'class' => 'btntableentry btnadddefaults',
        'lookupclass' => 'loaddata',
        'action' => 'adddefaults',
        'access' => 'additem',
        'visible' => true
      ),
      'addstages' => array(
        'label' => 'Add Stages',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addstages',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingboq' => array(
        'label' => 'BOQ',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingboq',
        'lookupclass' => 'pendingboqsummary',
        'action' => 'pendingboqsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingboq_mi' => array(
        'label' => 'ADD ITEM',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingboq',
        'lookupclass' => 'pendingboqdetail_mi',
        'action' => 'pendingboqdetail_mi',
        'access' => 'additem',
        'visible' => true
      ),
      'entrylocation' => array(
        'label' => 'Location',
        'icon' => 'batch_prediction',
        'class' => 'btnentrylocation',
        'lookupclass' => 'entrylocation',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => true
      ),
      'pendingjo' => array(
        'label' => 'JO',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingjo',
        'lookupclass' => 'pendingjosummary',
        'action' => 'pendingjosummary',
        'access' => 'additem',
        'visible' => true
      ),
      'compatible' => array(
        'label' => 'COMPATIBLE',
        'icon' => 'batch_prediction',
        'class' => 'btnviewpicompatible',
        'lookupclass' => 'lookupcompatible',
        'action' => 'lookupsetup',
        'access' => 'view',
        'visible' => true
      ),

      'incoming' => array(
        'label' => 'INCOMING',
        'icon' => 'archive',
        'class' => 'btnviewpiincoming',
        'lookupclass' => 'lookupincoming',
        'action' => 'lookupsetup',
        'access' => 'view',
        'visible' => true
      ),

      'openbox' => array(
        'label' => 'Open Box',
        'icon' => 'add_box',
        'class' => 'btnopenbox',
        'lookupclass' => 'openbox',
        'action' => 'openbox',
        'access' => 'view',
        'visible' => true
      ),
      'reopenbox' => array(
        'label' => 'Add in Box',
        'icon' => 'add_box',
        'class' => 'btnreopenbox',
        'lookupclass' => 'scantext',
        'action' => 'scanboxno',
        'access' => 'view',
        'visible' => true,
        'addedaction' => ['action' => 'lookupreopenbox', 'lookupclass' => 'reopenbox']
      ),
      'perpallet' => array(
        'label' => 'Per Pallet',
        'icon' => 'archive',
        'class' => 'btnperpallet',
        'lookupclass' => 'perpallet',
        'action' => 'scanpalletloc',
        'access' => 'view',
        'visible' => true
      ),
      'dropoff' => array(
        'label' => 'Drop-Off',
        'icon' => 'save_alt',
        'class' => 'btndropoff',
        'lookupclass' => 'dropoff',
        'action' => 'dropoff',
        'access' => 'view',
        'visible' => true,
        'addedparams' => ['palletid'],
        'confirm' => true,
        'confirmlabel' => 'Drop-off selected pallet/item?'
      ),
      'removepallet' => array(
        'label' => 'SKU to location',
        'icon' => 'archive',
        'class' => 'btndremovepallet',
        'lookupclass' => 'removepallet',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'visible' => true,
        'addedparams' => ['palletid']
      ),
      'pendingpcr' => array(
        'label' => 'PCR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpcr',
        'lookupclass' => 'pendingpcr',
        'action' => 'pendingpcr',
        'access' => 'additem',
        'visible' => true,
        'addedparams' => ['contra']
      ),
      'pendingpcv' => array(
        'label' => 'Tag PCV',
        'icon' => 'batch_prediction',
        'class' => 'btnpcv',
        'lookupclass' => 'pendingpcv',
        'action' => 'pendingpcv',
        'access' => 'additem',
        'visible' => true
      ),
      'addpcv' => array(
        'label' => 'Add PCV',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addpcv',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'whlog' => array(
        'label' => 'Logs',
        'icon' => 'description',
        'class' => 'btnwhlogs',
        'lookupclass' => 'whlog',
        'action' => 'lookupsetup',
        'access' => 'view',
        'visible' => true
      ),
      'refresh' => array(
        'label' => 'Refresh',
        'icon' => 'refresh',
        'class' => 'btnrefresh',
        'lookupclass' => 'refresh',
        'action' => 'actionbtn',
        'access' => 'view',
        'visible' => true
      ),
      'uploadexcel' => array(
        'label' => 'Upload Excel',
        'icon' => 'description',
        'class' => 'btnuploadexcel',
        'lookupclass' => 'uploadexcel',
        'action' => 'uploadexcel',
        'access' => 'view',
        'visible' => true
      ),
      'downloadexcel' => array(
        'label' => 'Download Excel',
        'icon' => 'description',
        'class' => 'btnuploadexcel',
        'lookupclass' => 'downloadexcel',
        'action' => 'downloadexcel',
        'access' => 'view',
        'visible' => true
      ),
      'exportcsv' => array(
        'label' => 'Export CSV',
        'icon' => 'description',
        'class' => 'btnexportcsv',
        'lookupclass' => 'exportcsv',
        'action' => 'exportcsv',
        'access' => 'load',
        'visible' => true
      ),
      'readfile' => array(
        'label' => 'Read File',
        'icon' => 'description',
        'class' => 'btnreadfile',
        'lookupclass' => 'readfile',
        'action' => 'readfile',
        'access' => 'load',
        'visible' => true
      ),
      'viewboxdetail' => array(
        'label' => 'BOX DETAILS',
        'icon' => 'batch_prediction',
        'class' => 'btnviewboxdetail',
        'lookupclass' => 'viewboxdetail',
        'action' => 'customform',
        'access' => 'view',
        'visible' => true
      ),
      'pendingjc' => array(
        'label' => 'JC',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingjc',
        'lookupclass' => 'pendingjcsummary',
        'action' => 'pendingjcsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingcn' => array(
        'label' => 'CN',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingcn',
        'lookupclass' => 'pendingcnsummary',
        'action' => 'pendingcnsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'syncall' => array(
        'label' => 'Sync all',
        'icon' => 'sync',
        'class' => 'btnsyncallentry',
        'lookupclass' => 'loaddata',
        'action' => 'syncallentry',
        'access' => 'syncallentry',
        'visible' => true
      ),
      'masterfilelogs' => [
        'label' => 'Logs',
        'icon' => 'description',
        'class' => 'csmasterfilelogs',
        'lookupclass' => 'lookuplogs',
        'action' => 'lookupsetup',
        'access' => 'view',
        'visible' => true
      ],
      'dtaddstatus' => [
        'label' => 'Add Status',
        'icon' => 'add',
        'class' => 'csdtaddstatus',
        'lookupclass' => 'lookupdtstatus',
        'action' => 'lookupdtstatus',
        'access' => 'additem',
        'visible' => true

      ],
      'addsubitem' => array(
        'label' => 'Add Items',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addsubitem',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingqts' => array(
        'label' => 'Service Qoutation',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingqts',
        'lookupclass' => 'pendingqtssummary',
        'action' => 'pendingqtssummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingsr' => array(
        'label' => 'SR',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingsr',
        'lookupclass' => 'pendingsrsummary',
        'action' => 'pendingsrsummary',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingos' => array(
        'label' => 'OS',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingos',
        'lookupclass' => 'pendingossummary',
        'action' => 'pendingossummary',
        'access' => 'additem',
        'visible' => true
      ),
      'addsubstages' => array(
        'label' => 'Add Activity',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addsubstages',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'addsubactivity' => array(
        'label' => 'Add Activity',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addsubactivity',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingrfso' => array(
        'label' => 'DR/SI',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingso',
        'lookupclass' => 'pendingrfso',
        'action' => 'pendingrfso',
        'access' => 'additem',
        'visible' => true
      ),
      'addproject' => array(
        'label' => 'Add Project',
        'icon' => 'add_box',
        'class' => 'btntableentry btnaddrecord',
        'lookupclass' => 'add',
        'action' => 'addproject',
        'access' => 'additem',
        'visible' => true
      ),
      'addcustomer' => array(
        'label' => 'Add',
        'icon' => 'add_box',
        'class' => 'btnheadstock',
        'lookupclass' => 'addcustomer',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingca' => array(
        'label' => 'CA',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnca',
        'lookupclass' => 'pendingca',
        'action' => 'pendingca',
        'access' => 'additem',
        'visible' => true
      ),
      'addpiprocess' => array(
        'label' => 'Add Process',
        'icon' => 'add',
        'class' => 'btnaddprocess',
        'lookupclass' => 'addprocess',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingpd' => array(
        'label' => 'PD',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpd',
        'lookupclass' => 'lookuppendingpd',
        'action' => 'lookuppendingpd',
        'access' => 'additem',
        'visible' => true
      ),
      'unlinksq' => array(
        'label' => 'Remove Items',
        'icon' => 'delete',
        'class' => 'btnheadstock',
        'lookupclass' => 'deleteallitem',
        'action' => 'deleteallitem',
        'access' => 'deleteitem',
        'visible' => true
      ),
      'generateqtcomp' => array(
        'label' => 'Load',
        'icon' => 'folder_special',
        'class' => 'btngenerateqtcomp',
        'lookupclass' => 'stockstatus',
        'action' => 'generateqtcomp',
        'access' => 'edititem',
        'visible' => true
      ),
      'closeentry' => array(
        'label' => 'Closing Entry',
        'icon' => 'backspace  ',
        'class' => 'btncloseentry',
        'lookupclass' => 'stockstatus',
        'action' => 'closeentry',
        'access' => 'edititem',
        'visible' => true
      ),
      'generatesurcharge' => array(
        'label' => 'Generate Surcharge',
        'icon' => 'refresh',
        'class' => 'btngeneratesurcharge',
        'lookupclass' => 'headtablestatus',
        'action' => 'generatesurcharge',
        'access' => 'surcharge',
        'visible' => true
      ),
      'release' => array(
        'label' => 'Release',
        'icon' => 'save',
        'class' => 'btnrelease',
        'lookupclass' => 'headtablestatus',
        'action' => 'release',
        'access' => 'saveallentry',
        'visible' => true
      ),
      'testapi' => array(
        'label' => 'Test API',
        'icon' => 'save',
        'class' => 'btnsaveallentry',
        'lookupclass' => 'testapi',
        'action' => 'testapi',
        'access' => 'testapi',
        'visible' => true
      ),
      'generatepaysched' => array(
        'label' => 'Generate Payment Schedule',
        'icon' => 'batch_prediction',
        'class' => 'btngeneratepaysched',
        'lookupclass' => 'stockstatus',
        'action' => 'generatepaysched',
        'access' => 'edititem',
        'visible' => true
      ),
      'generateautoentry' => array(
        'label' => 'Generate Auto Entry',
        'icon' => 'batch_prediction',
        'class' => 'btngenerateentry',
        'lookupclass' => 'stockstatus',
        'action' => 'generateautoentry',
        'access' => 'edititem',
        'visible' => true
      ),
      'pdc' => array(
        'label' => 'PDC',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpdc',
        'lookupclass' => 'lookuppendingpdc',
        'action' => 'lookuppendingpdc',
        'access' => 'additem',
        'visible' => true
      ),
      'refillitem' => array(
        'label' => 'Refill',
        'icon' => 'batch_prediction',
        'class' => 'btntableentry btnrefill',
        'lookupclass' => 'stockstatus',
        'action' => 'refillitem',
        'access' => 'additem',
        'visible' => true,
        'confirm' => true,
        'confirmlabel' => 'This will overwrite the items, Continue?'
      ),
      'post' => array(
        'label' => 'Post',
        'icon' => 'save',
        'class' => 'btnpostcharges',
        'lookupclass' => 'loaddata',
        'action' => 'postcharges',
        'access' => 'additem',
        'visible' => true
      ),
      'assigntempbarcode' => array(
        'label' => 'Assign Temp Barcode',
        'icon' => 'save',
        'class' => 'btnassigntempbarcode',
        'lookupclass' => 'assigntempbarcode',
        'action' => 'tableentry2',
        'access' => 'save',
        'visible' => true
      ),
      'saveandclose' => array(
        'label' => 'Save and Close',
        'icon' => 'save',
        'class' => 'btnsaveallentry',
        'lookupclass' => 'saveandclose',
        'action' => 'saveandclose',
        'access' => 'saveallentry',
        'visible' => true,
        'confirm' => true,
        'confirmlabel' => 'Do you want to save and update the transactions?'
      ),
      'cancel' => array(
        'label' => 'Cancel',
        'icon' => 'close',
        'class' => 'btnsaveallentry',
        'lookupclass' => 'cancel',
        'action' => 'saveandclose',
        'access' => 'saveallentry',
        'visible' => true
      ),
      'applybarcode' => array(
        'label' => 'Apply barcode',
        'icon' => 'done_all',
        'class' => 'btnsaveallentry',
        'lookupclass' => 'loaddata',
        'action' => 'applybarcode',
        'access' => 'saveallentry',
        'visible' => true
      ),
      'markall' => array(
        'label' => 'Mark/Unmark',
        'icon' => 'done_all',
        'class' => 'btnmarkall',
        'lookupclass' => 'loaddata',
        'action' => 'markall',
        'access' => 'saveallentry',
        'visible' => true
      ),
      'unpaidpercust' => array(
        'label' => 'UNPAID',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btnunpaid',
        'lookupclass' => 'unpaidpercust',
        'action' => 'unpaid',
        'access' => 'additem',
        'visible' => true
      ),
      'pendingpy' => array(
        'label' => 'PL',
        'icon' => 'batch_prediction',
        'class' => 'btnpendingpy',
        'lookupclass' => 'pendingpysummary',
        'action' => 'pendingpysummary',
        'access' => 'additem',
        'visible' => true
      ),

      'vieweempnotimeinout' => array(
        'label' => 'No Time IN',
        'icon' => 'batch_prediction',
        'class' => 'btnvieweempnotimeinout',
        'lookupclass' => 'vieweempnotimeinout',
        'action' => 'vieweempnotimeinout',
        'access' => 'view',
        'visible' => true,
        'addedparams' => ['dateid']
      ),

      'viewempnodeployment' => array(
        'label' => 'No Deployment Record',
        'icon' => 'batch_prediction',
        'class' => 'btnviewempnodeployment',
        'lookupclass' => 'viewempnodeployment',
        'action' => 'viewempnodeployment',
        'access' => 'view',
        'visible' => true,
        'addedparams' => ['dateid']
      ),

      'addcor' => array(
        'label' => 'COR',
        'icon' => 'batch_prediction',
        'class' => 'btnaddcor',
        'lookupclass' => 'addcor',
        'action' => 'addcor',
        'access' => 'additem',
        'visible' => true
      ),
      'dp' => array(
        'label' => 'Advance Payment',
        'icon' => 'batch_prediction',
        'class' => 'btnheaddetail btndp',
        'lookupclass' => 'stockstatus',
        'action' => 'getdp',
        'access' => 'additem',
        'visible' => true
      ),

      'undepositeddscollection' => array(
        'label' => 'UNDEPOSITED COLLECTION',
        'icon' => 'batch_prediction',
        'class' => 'btnundepositeddscol',
        'lookupclass' => 'getdsundepositedcol',
        'action' => 'lookupdsundepositedcol',
        'access' => 'additem',
        'visible' => true
      ),
      'rchecks' => array(
        'label' => 'RECEIVED CHECKS',
        'icon' => 'batch_prediction',
        'class' => 'btnrchecks',
        'lookupclass' => 'getrc',
        'action' => 'getrc',
        'access' => 'additem',
        'visible' => true
      ),
      'addexpense' => array(
        'label' => 'Add Expense',
        'icon' => 'add_box',
        'class' => 'btntableentry btnaddrecord',
        'lookupclass' => 'addexpense',
        'action' => 'lookupsetup',
        'access' => 'additem',
        'visible' => true
      ),

      'generateleave' => array(
        'label' => 'GENERATE LEAVE',
        'icon' => 'batch_prediction',
        'class' => 'btnheadstock',
        'lookupclass' => 'loaddata',
        'action' => 'loaddata',
        'access' => 'additem',
        'visible' => true
      ),
      'multiitem' => array(
        'label' => 'ADD ITEM',
        'icon' => 'add_box',
        'class' => 'btnmultiitem',
        'lookupclass' => 'multiitem',
        'action' => 'multiitem',
        'access' => 'additem',
        'visible' => true
      ),

    );
  }

  public function delcol($obj, $gridname, $gridindex = 0)
  {
    $columns = [];
    foreach ($obj[$gridindex][$gridname]['columns'] as $key => $value) {
      if ($value['type'] != 'coldel') {
        array_push($columns, $value);
      }
    }
    return $columns;
  } //end function

  public function delcollisting($obj)
  {
    $columns = [];
    foreach ($obj as $key => $value) {
      if ($value['type'] != 'coldel') {
        array_push($columns, $value);
      }
    }
    return $columns;
  } //end function


  public function createtab($ptab, $pstockbuttons)
  {
    $this->tabArray();

    $tab = [];
    $ltabs = [];
    $stockbtn = [];
    $lheadgridbtns = [];
    $this->columns = $this->gridcolumnClass->getcolumn();
    $this->stockbuttons = $this->gridbuttonClass->getgridbuttons();
    $this->headgridbtns = $this->gridbuttonClass->getheadgridbuttons();
    foreach ($ptab as $key => $value) {
      $tab = $this->othersClass->array_only($this->tabs, [$key]);
      switch ($tab[$key]['obj']) {
        case 'acctgentrygrid':
        case 'stockentrygrid':
        case 'editgrid':
        case 'entrygrid':
          $tab[$key]['columns'] = $this->othersClass->array_only($this->columns, $ptab[$key]['gridcolumns']);
          if (isset($ptab[$key]['stockbuttons'])) {
            if (is_array($ptab[$key]['stockbuttons']) && !empty($ptab[$key]['stockbuttons'])) {
              $stockbtn = $this->othersClass->array_only($this->stockbuttons, $ptab[$key]['stockbuttons']);
              $tab[$key]['columns']['action']['btns'] = $stockbtn;
            }
          } else {
            if (is_array($pstockbuttons) && !empty($pstockbuttons)) {
              $stockbtn = $this->othersClass->array_only($this->stockbuttons, $pstockbuttons);
              if (isset($ptab[$key]['sortbuttons'])) {
                $tab[$key]['columns']['action']['btns'] = $this->othersClass->sortarray($stockbtn, $ptab[$key]['sortbuttons']);
              } else {
                $tab[$key]['columns']['action']['btns'] = $stockbtn;
              }
            }
          }
          $sortcolumn = [];
          if (isset($ptab[$key]['sortcolumns'])) {
            $sortcolumn = $ptab[$key]['sortcolumns'];
          } else {
            $sortcolumn = $ptab[$key]['gridcolumns'];
          }
          $tab[$key]['columns'] = $this->othersClass->sortarray($tab[$key]['columns'], $sortcolumn);
          // list($k, $v) = array_divide($tab[$key]['columns']);
          $k = array_keys($tab[$key]['columns']);
          $v = array_values($tab[$key]['columns']);
          $tab[$key]['columns'] = $v;
          if (isset($ptab[$key]['gridcolumns'])) {
            $tab[$key]['visiblecol'] = $ptab[$key]['gridcolumns'];
          }
          if (isset($ptab[$key]['computefield'])) {
            $tab[$key]['computefield'] = $ptab[$key]['computefield'];
          }
          if (isset($ptab[$key]['headgridbtns'])) {
            $lheadgridbtns = $this->othersClass->array_only($this->headgridbtns, $ptab[$key]['headgridbtns']);
            $tab[$key]['headgridbtns'] = $lheadgridbtns;
          }
          if (isset($ptab[$key]['checkchanges'])) {
            $tab[$key]['checkchanges'] = $ptab[$key]['checkchanges'];
          }
          if (isset($ptab[$key]['gridheadinput'])) {
            $tab[$key]['gridheadinput'] = $ptab[$key]['gridheadinput'];
          }
          if (isset($ptab[$key]['rowperpage'])) {
            $tab[$key]['rowperpage'] = $ptab[$key]['rowperpage'];
          } else {
            $tab[$key]['rowperpage'] = 25;
          }
          break;
        case 'sbc_showgrid':
        case 'sbc_checkboxgrid':
          $tab[$key]['columns'] = $this->othersClass->array_only($this->columns, $ptab[$key]['gridcolumns']);
          if (isset($ptab[$key]['stockbuttons'])) {
            if (is_array($ptab[$key]['stockbuttons']) && !empty($ptab[$key]['stockbuttons'])) {
              $stockbtn = $this->othersClass->array_only($this->stockbuttons, $ptab[$key]['stockbuttons']);
              $tab[$key]['columns']['action']['btns'] = $stockbtn;
            }
          } else {
            if (is_array($pstockbuttons) && !empty($pstockbuttons)) {
              $stockbtn = $this->othersClass->array_only($this->stockbuttons, $pstockbuttons);
              $tab[$key]['columns']['action']['btns'] = $stockbtn;
            }
          }
          $tab[$key]['columns'] = $this->othersClass->sortarray($tab[$key]['columns'], $ptab[$key]['gridcolumns']);
          // list($k, $v) = array_divide($tab[$key]['columns']);
          $k = array_keys($tab[$key]['columns']);
          $v = array_values($tab[$key]['columns']);
          $tab[$key]['columns'] = $v;
          if (isset($ptab[$key]['gridcolumns'])) {
            $tab[$key]['visiblecol'] = $ptab[$key]['gridcolumns'];
          }
          if (isset($ptab[$key]['gridheadinput'])) {
            $tab[$key]['gridheadinput'] = $ptab[$key]['gridheadinput'];
          }
          break;
        case 'event':
        case 'customform':
          if (isset($ptab[$key]['event'])) {
            $tab[$key]['event'] = $ptab[$key]['event'];
          }
          if (isset($ptab[$key]['label'])) {
            $tab[$key]['label'] = $ptab[$key]['label'];
          }
          break;
        case 'multigrid':
        case 'tableentry':
          if (isset($ptab[$key]['action'])) {
            $tab[$key]['action'] = $ptab[$key]['action'];
          }
          if (isset($ptab[$key]['name'])) {
            $tab[$key]['name'] = $ptab[$key]['name'];
          }
          if (isset($ptab[$key]['access'])) {
            $tab[$key]['access'] = $ptab[$key]['access'];
          }
          if (isset($ptab[$key]['checkchanges'])) {
            $tab[$key]['checkchanges'] = $ptab[$key]['checkchanges'];
          }
          if (isset($ptab[$key]['addedparams'])) {
            $tab[$key]['addedparams'] = $ptab[$key]['addedparams'];
          }
          if (isset($ptab[$key]['gridheadinput'])) {
            $tab[$key]['gridheadinput'] = $ptab[$key]['gridheadinput'];
          }
          if (isset($ptab[$key]['lookupclass'])) {
            $tab[$key]['lookupclass'] = $ptab[$key]['lookupclass'];
          }
          if (isset($ptab[$key]['label'])) {
            $tab[$key]['label'] = $ptab[$key]['label'];
          }
          break;
        case 'qcardcss':
          if (isset($ptab[$key]['btn'])) {
            $tab[$key]['btn'] = $ptab[$key]['btn'];
          }
          if (isset($ptab[$key]['label'])) {
            $tab[$key]['label'] = $ptab[$key]['label'];
          }
          if (isset($ptab[$key]['visible'])) {
            $tab[$key]['visible'] = $ptab[$key]['visible'];
          }
          break;
        case 'singleinput':
        case 'multiinput':
          if (isset($ptab[$key]['inputcolumn'])) {
            $tab[$key]['inputcolumn'] = $ptab[$key]['inputcolumn'];
          }
          if (isset($ptab[$key]['label'])) {
            $tab[$key]['label'] = $ptab[$key]['label'];
          }
          break;
        case 'calendar':
        case 'calendar2':
          $tab[$key]['action'] = $key;
          if (isset($ptab[$key]['label'])) {
            $tab[$key]['label'] = $ptab[$key]['label'];
          }
          break;
      }
      array_push($ltabs, $tab);
    }
    return $ltabs;
  } // end function

  public function createtabbutton($ptbuttons)
  {
    $tbuttons = [];
    if (is_array($ptbuttons) && !empty($ptbuttons)) {
      foreach ($ptbuttons as $key => $value) {
        array_push($tbuttons, $this->tabbuttons[$value]);
      }
    }
    return $tbuttons;
  } //end function


  public function createdoclisting($getcols, $pstockbuttons = [])
  {
    $this->tabArray();

    $cols = [];
    $this->columns = $this->gridcolumnClass->getcolumn();
    $this->stockbuttons = $this->gridbuttonClass->getgridbuttons();
    if (is_array($getcols) && !empty($getcols)) {
      $cols = $this->othersClass->array_only($this->columns, $getcols);
      if (is_array($pstockbuttons) && !empty($pstockbuttons)) {
        $stockbtn = $this->othersClass->array_only($this->stockbuttons, $pstockbuttons);
        $cols['action']['btns'] = $stockbtn;
      }
      $cols = $this->othersClass->sortarray($cols, $getcols);
      // list($k, $v) = array_divide($cols);
      $k = array_keys($cols);
      $v = array_values($cols);
      $cols = $v;
    }
    return $cols;
  }
} // end class
