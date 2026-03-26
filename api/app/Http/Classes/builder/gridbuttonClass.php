<?php

namespace App\Http\Classes\builder;

use DB;
use Exception;
use Throwable;

class gridbuttonClass
{

  private $gridbuttons = [];
  private $headgridbuttons = [];

  public function getgridbuttons()
  {
    $this->buttonsArray();
    return $this->gridbuttons;
  }

  public function getheadgridbuttons()
  {
    $this->buttonsArray();
    return $this->headgridbuttons;
  }

  public function __construct() {} //end function

  public function buttonsArray()
  {
    //head grid buttons
    //viewacctg - lookupclass
    //viewdistribution - ledgerclass
    $this->headgridbuttons = [
      'viewacctg' => [
        'label' => 'View Accounting Entry',
        'icon' => 'table_chart',
        'lookupclass' => 'viewacctg',
        'action' => 'viewacctg',
        'access' => 'acctg',
        'visible' => false
      ],
      'viewdistribution' => [
        'label' => 'View Accounting Entry',
        'icon' => 'table_chart',
        'lookupclass' => 'viewdistribution',
        'action' => 'customform',
        'access' => 'acctg',
        'visible' => false
      ],
      'itemvoiding' => [
        'label' => 'Void Items',
        'icon' => 'table_chart',
        'lookupclass' => 'updateitemvoid',
        'action' => 'customform',
        'access' => 'edititem',
        'visible' => false
      ],
      'itemqtyvoiding' => [
        'label' => 'Void Items',
        'icon' => 'table_chart',
        'lookupclass' => 'viewitemqtyvoiding',
        'action' => 'tableentry',
        'access' => 'edititem',
        'visible' => false
      ],
      'viewref' => [
        'label' => 'Ref Documents',
        'icon' => 'format_list_bulleted',
        'lookupclass' => 'viewref',
        'action' => 'customform',
        'access' => 'view',
        'visible' => false
      ],
      'viewdeliverydate' => [
        'label' => 'DELIVERY DATE',
        'icon' => 'speaker_notes',
        'lookupclass' => 'deliverydate',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => false
      ],
      'viewitemstockinfo' => [
        'label' => 'Item Info',
        'icon' => 'speaker_notes',
        'lookupclass' => 'viewitemstockinfo',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => false
      ],
      'viewacctginfo' => [
        'label' => 'Accounting Entry Info',
        'icon' => 'speaker_notes',
        'lookupclass' => 'viewacctginfo',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => false
      ],
      'sq_makepo' => [
        'label' => 'Make PO',
        'icon' => 'save',
        'lookupclass' => 'lookupsqsupplier',
        'action' => 'lookupsqsupplier',
        'access' => 'view',
        'visible' => false
      ],
      'tripapproved' => [
        'label' => 'TRIP APPROVED',
        'icon' => 'check',
        'lookupclass' => 'tripapproved',
        'action' => 'stockstatusposted',
        'access' => 'tripapproved',
        'visible' => false,
        'confirm' => true,
        'confirmlabel' => 'Approve trip?'
      ],
      'tripdisapproved' => [
        'label' => 'TRIP DISAPPROVED',
        'icon' => 'close',
        'lookupclass' => 'tripdisapproved',
        'action' => 'stockstatusposted',
        'access' => 'tripdisapproved',
        'visible' => false,
        'confirm' => true,
        'confirmlabel' => 'Disapprove trip?'
      ],
      'tagreceived' => [
        'label' => 'Received',
        'icon' => 'save',
        'lookupclass' => 'tagreceived',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'visible' => true
      ],
      'disapprove' => [
        'label' => 'Disapprove',
        'icon' => 'delete',
        'lookupclass' => 'disapprove',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'visible' => true
      ],
      'untagreceived' => [
        'label' => 'Unreceived',
        'icon' => 'save',
        'lookupclass' => 'untagreceived',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'visible' => true
      ],
      'viewdiagram' => [
        'label' => 'Diagram',
        'icon' => 'account_tree',
        'lookupclass' => 'viewref',
        'action' => 'diagram',
        'access' => 'view',
        'visible' => false
      ],
      'viewversion' => [
        'label' => 'View Version',
        'icon' => 'table_chart',
        'lookupclass' => 'entryviewrevision',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => false
      ],
      'viewflowchart' => [
        'label' => 'Flowchart',
        'icon' => 'table_chart',
        'lookupclass' => 'viewref',
        'action' => 'flowchart',
        'access' => 'view',
        'visible' => false
      ],
      'adjust' => [
        'label' => 'Adjust Inventory',
        'icon' => 'table_chart',
        'lookupclass' => 'adjustpc',
        'action' => 'adjustpc',
        'access' => 'view',
        'visible' => false
      ],
      'generatepr' => [
        'label' => 'Generate PR',
        'icon' => 'refresh',
        'lookupclass' => 'entrygeneratepr',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => false
      ],
      'adddocumentcntnum' => [
        'label' => 'Documents',
        'icon' => 'batch_prediction',
        'lookupclass' => 'entrycntnumpicture',
        'action' => 'documententry',
        'access' => 'view',
        'visible' => false
      ],
      'adddocumenttransnum' => [
        'label' => 'Documents',
        'icon' => 'batch_prediction',
        'lookupclass' => 'entrytransnumpicture',
        'action' => 'documententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewotherfees' => [
        'label' => 'View Otherfees',
        'icon' => 'table_chart',
        'lookupclass' => 'viewotherfees',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewcredentials' => [
        'label' => 'View Credentials',
        'icon' => 'table_chart',
        'lookupclass' => 'viewcredentials',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewsosummary' => [
        'label' => 'View Summary',
        'icon' => 'table_chart',
        'lookupclass' => 'entryassummary',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewenacctg' => [
        'label' => 'View Accounting Entry',
        'icon' => 'table_chart',
        'lookupclass' => 'viewenacctg',
        'action' => 'viewenacctg',
        'access' => 'acctg',
        'visible' => false
      ],
      'generateenbilling' => [
        'label' => 'Generate Billing',
        'icon' => 'table_chart',
        'lookupclass' => 'generateenbilling',
        'action' => 'generateenbilling',
        'access' => 'view',
        'visible' => false
      ],
      'viewcurriculum' => [
        'label' => 'View Curriculum',
        'icon' => 'table_chart',
        'lookupclass' => 'viewcurriculum',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewbooks' => [
        'label' => 'View Books',
        'icon' => 'table_chart',
        'lookupclass' => 'viewbooks',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'reportcard' => [
        'label' => 'Report Card Setup',
        'icon' => 'article',
        'lookupclass' => 'entryreportcardsetup',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'regstudent' => [
        'label' => 'Registered Student',
        'icon' => 'table_chart',
        'lookupclass' => 'entryregstudents',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'regstudentbatch' => [
        'label' => 'Registered Batch',
        'icon' => 'table_chart',
        'lookupclass' => 'regstudentbatch',
        'action' => 'regstudentbatch',
        'access' => 'view',
        'visible' => false
      ],
      'dropstudent' => [
        'label' => 'Drop Student',
        'icon' => 'table_chart',
        'lookupclass' => 'dropstudent',
        'action' => 'dropstudent',
        'access' => 'view',
        'visible' => false
      ],
      'studlevelup' => [
        'label' => 'Student Level Up',
        'icon' => 'table_chart',
        'lookupclass' => 'entrystudlevelup',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewgeneratedstudents' => [
        'label' => 'View Student Grade',
        'icon' => 'table_chart',
        'lookupclass' => 'viewgeneratedstudents',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewgradesubcomp' => [
        'label' => 'View Grade Sub Component',
        'icon' => 'table_chart',
        'lookupclass' => 'viewgradesubcomp',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewgradeentry' => [
        'label' => 'View Grade Entry',
        'icon' => 'table_chart',
        'lookupclass' => 'viewgradeentry',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewcomponentgrade' => [
        'label' => 'View Component Grade',
        'icon' => 'table_chart',
        'lookupclass' => 'viewcomponentgrade',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'viewquartergrade' => [
        'label' => 'View Quarter Grade',
        'icon' => 'table_chart',
        'lookupclass' => 'viewquartergrade',
        'action' => 'enrollmententry',
        'access' => 'view',
        'visible' => false
      ],
      'generatedepsched' => [
        'label' => 'Generate Depreciation Schedule',
        'icon' => 'table_chart',
        'lookupclass' => 'generatedepsched',
        'action' => 'generatedepsched',
        'access' => 'view',
        'visible' => false
      ],
      'editboq' => [
        'label' => 'Edit Items',
        'icon' => 'table_chart',
        'lookupclass' => 'editboq',
        'action' => 'tableentry',
        'access' => 'edititem',
        'visible' => false
      ],

      'dtaddstatus' => [
        'label' => 'Add Status',
        'icon' => 'add',
        'action' => 'lookupdtstatus',
        'lookupclass' => 'lookupdtstatus',
        'class' => 'csdtaddstatus',
        'access' => 'additem',
        'visible' => false,
      ],
      'makejo' => [
        'label' => 'Make JO',
        'icon' => 'save',
        'lookupclass' => 'makejo',
        'action' => 'lookupsqsupplier',
        'access' => 'view',
        'visible' => false
      ],
      'makecv' => [
        'label' => 'Make Payment',
        'icon' => 'payments',
        'lookupclass' => 'makepayment',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'visible' => false
      ],
      'showheaditembalance' => [
        'label' => 'Show Balance',
        'icon' => 'table_chart',
        'lookupclass' => 'showheaditembalance',
        'action' => 'customform',
        'access' => 'acctg',
        'visible' => false
      ],
      'view_items' => [
        'label' => 'VIEW ITEMS',
        'icon' => 'table_chart',
        'lookupclass' => 'view_items',
        'action' => 'customform',
        'access' => 'acctg',
        'visible' => false
      ],

      'viewsobreakdown' => [
        'label' => 'SO Breakdown',
        'icon' => 'speaker_notes',
        'lookupclass' => 'viewsobreakdown',
        'action' => 'tableentry',
        'access' => 'view',
        'visible' => false
      ],
      'genapv' => [
        'label' => 'Generate APV',
        'icon' => 'table_chart',
        'lookupclass' => 'generateapv',
        'action' => 'stockstatusposted2',
        'access' => 'view',
        'visible' => false
      ],




    ];


    // setup of stock buttons
    $this->gridbuttons = [
      'save' => [
        'name' => 'save',
        'icon' => 'save',
        'action' => 'saveperitem',
        'class' => 'btnstocksave',
        'access' => 'edititem',
        'color' => 'blue',
        'label' => 'Save changes'
      ],
      'view' => [
        'name' => 'view',
        'icon' => 'view_module',
        'action' => 'view',
        'class' => 'btnstockview',
        'access' => 'view',
        'color' => 'blue',
        'lookupclass' => ''
      ],
      'sortline' => [
        'name' => 'sortline',
        'icon' => 'reorder',
        'action' => 'customform',
        'lookupclass' => 'viewsortline',
        'class' => 'btnsortline',
        'access' => 'edititem',
        'color' => 'green',
        'label' => 'Sort line'
      ],
      'editquestion' => [
        'name' => 'editquestion',
        'icon' => 'edit_note',
        'action' => 'customform',
        'lookupclass' => 'addquestion',
        'class' => 'btneditquestion',
        'access' => 'edititem',
        'color' => 'green',
        'label' => 'Edit'
      ],
      'download' => [
        'name' => 'download',
        'icon' => 'cloud_download',
        'action' => 'download',
        'class' => 'btnstockdownload',
        'access' => 'edititem',
        'color' => 'blue'
      ],
      'delete' => [
        'name' => 'delete',
        'icon' => 'delete',
        'class' => 'btnstockdelete',
        'action' => 'deleteitem',
        'access' => 'deleteitem',
        'color' => 'red',
        'label' => 'Delete item'
      ],
      'stockinfo' => [
        'name' => 'stockinfo',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'stockinfo',
        'lookupclass' => 'viewstockinfo',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'green'
      ],
      'detailinfo' => [
        'name' => 'detailinfo',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'detailinfo',
        'lookupclass' => 'viewacctginfo',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'green'
      ],
      'reassignbtn' => [
        'name' => 'reassignbtn',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'reassignbtn',
        'lookupclass' => 'reassignment',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'green'
      ],
      'leaveentitledbtn' => [
        'name' => 'multigrid',
        'label' => 'Set Range',
        'icon' => 'speaker_notes',
        'action' => 'tableentry',
        'class' => 'leaveentitledbtn',
        'lookupclass' => 'viewleaveentitled',
        'access' => 'additem',
        'color' => 'green'
      ],
      'whinfo' => [
        'name' => 'whinfo',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'whinfo',
        'lookupclass' => 'viewwhnotes',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'green'
      ],
      'timecardinfo' => [
        'name' => 'timecardinfo',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'timecardinfo',
        'lookupclass' => 'viewtimecarddetail',
        'label' => 'Timecard Info',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'green'
      ],
      'showbalance' => [
        'name' => 'showbalance',
        'icon' => 'photo_library',
        'action' => 'showbalance',
        'class' => 'btnstockshowbalance',
        'access' => 'view',
        'color' => 'orange',
        'label' => 'Show balance'
      ],
      'showcomponent' => [
        'name' => 'showbalance',
        'icon' => 'list',
        'action' => 'showcomponent',
        'class' => 'btnstockshowbalance',
        'access' => 'view',
        'color' => 'cyan',
        'confirm' => false,
        'lookupclass' => 'view',
        'label' => 'Show Components'
      ],
      'ccsubject' => [
        'name' => 'ccsubject',
        'icon' => 'visibility',
        'action' => 'tableentry',
        'lookupclass' => 'entryccsubject',
        'class' => 'btnentryccsubject',
        'label' => 'Subjects',
        'access' => 'view',
        'color' => 'green'
      ],
      'books' => [
        'name' => 'ccsubject',
        'icon' => 'book',
        'action' => 'tableentry',
        'lookupclass' => 'entrybooks',
        'class' => 'btnentryccsubject',
        'label' => 'Books',
        'access' => 'view',
        'color' => 'orange'
      ],
      'duplicate' => [
        'name' => 'duplicate',
        'icon' => 'done',
        'class' => 'btnstockdelete',
        'action' => 'duplicate',
        'label' => 'Duplicate',
        'access' => 'view',
        'color' => 'purple'
      ],
      'studpoints' => [
        'name' => 'studpoints',
        'icon' => 'visibility',
        'action' => 'tableentry',
        'lookupclass' => 'entrystudpoints',
        'class' => 'btnentrystudpoints',
        'access' => 'view',
        'color' => 'green'
      ],
      'gradesubcom' => [
        'name' => 'gradesubcom',
        'icon' => 'visibility',
        'action' => 'tableentry',
        'lookupclass' => 'entrygradesubcom',
        'class' => 'btnentrygradesubcom',
        'access' => 'view',
        'color' => 'green'
      ],
      'serialin' => [
        'name' => 'serialin',
        'icon' => 'add',
        'action' => 'tableentry',
        'lookupclass' => 'entryserialin',
        'class' => 'btnstockserialno',
        'access' => 'view',
        'color' => 'green'
      ],
      'serialout' => [
        'name' => 'serialout',
        'icon' => 'remove',
        'action' => 'tableentry',
        'lookupclass' => 'entryserialout',
        'class' => 'btnstockserialno',
        'access' => 'view',
        'color' => 'green'
      ],
      'dsave' => [
        'name' => 'dsave',
        'icon' => 'save',
        'action' => 'saveperdetail',
        'access' => 'edititem',
        'class' => 'btnstockdsave',
        'color' => 'blue'
      ],
      'ddelete' => [
        'name' => 'ddelete',
        'icon' => 'delete',
        'action' => 'deletedetail',
        'access' => 'deleteitem',
        'class' => 'btnstockddelete',
        'color' => 'red'
      ],
      'view_unposted' => [
        'name' => 'View Unposted',
        'icon' => 'photo_library',
        'action' => 'lookupsetup',
        'class' => 'btnstockview_unposted',
        'lookupclass' => 'viewunposted',
        'access' => 'view',
        'visible' => true,
        'color' => 'teal'
      ],
      'approvedcanvass' => [
        'name' => 'Approved',
        'icon' => 'photo_library',
        'action' => 'customform',
        'class' => 'btnstockapprovedcanvass',
        'lookupclass' => 'approvedcanvass',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'viewuominfo' => [
        'name' => 'viewuominfo',
        'icon' => 'photo_library',
        'action' => 'customform',
        'class' => 'btnstockviewuominfo',
        'lookupclass' => 'viewuominfo',
        'access' => 'view',
        'color' => 'green',
        'label' => 'viewuominfo'
      ],
      'viewheaduominfo' => [
        'name' => 'viewheaduominfo',
        'icon' => 'photo_library',
        'action' => 'customform',
        'class' => 'btnstockviewuominfo',
        'lookupclass' => 'viewheaduominfo',
        'access' => 'view',
        'color' => 'green',
        'label' => 'viewheaduominfo'
      ],
      'tabstocksoinfo' => [
        'name' => 'Stock SO',
        'icon' => 'photo_library',
        'action' => 'customform',
        'class' => 'btnstocktabstockso',
        'lookupclass' => 'tabstockso',
        'access' => 'view',
        'color' => 'green',
        'label' => 'tabstockso'
      ],
      'sbcremarks' => [
        'name' => 'SBC REMARKS',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btnsbcremarks',
        'lookupclass' => 'sbcremarks',
        'access' => 'view',
        'color' => 'green',
        'label' => 'sbcremaks'
      ],
      'viewentrysbcremarks' => [
        'name' => 'customform',
        'label' => 'SBC REMARKS',
        'icon' => 'photo_library',
        'action' => 'customform',
        'lookupclass' => 'viewentrysbcremarks',
        'class' => 'btnentrysbcremarks',
        'access' => 'view',
        'color' => 'orange'
      ],
      'tabheadinfo' => [
        'name' => 'ADD FEES',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btntabheadinfo',
        'lookupclass' => 'tabheadinfo',
        'access' => 'view',
        'color' => 'green',
        'label' => 'addfees'
      ],
      'viewcddetail' => [
        'name' => 'viewcddetail',
        'icon' => 'photo_library',
        'action' => 'customform',
        'class' => 'btnstockviewcddetail',
        'lookupclass' => 'viewcddetail',
        'access' => 'view',
        'color' => 'green',
        'label' => 'View details'
      ],
      'compliantass' => [
        'name' => 'COMPLAINTS/ASSESSMENT',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btncompliantass',
        'lookupclass' => 'compliantass',
        'access' => 'view',
        'color' => 'green',
        'label' => 'ComplaintsAssessment'
      ],
      'mitagged' => [
        'name' => 'MI',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btnmitagged',
        'lookupclass' => 'mitagged',
        'access' => 'view',
        'color' => 'green',
        'label' => 'MI Tagged'
      ],
      'jobdone' => [
        'name' => 'MI',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btnjobdone',
        'lookupclass' => 'jobdone',
        'access' => 'view',
        'color' => 'green',
        'label' => 'Action Job Done'
      ],
      //construction
      'addstages' => [
        'name' => 'tableentry',
        'icon' => 'add',
        'action' => 'construction',
        'lookupclass' => 'entryprojectstages',
        'class' => 'btnentrystockstages',
        'access' => 'view',
        'color' => 'green',
      ],
      'rrstockinfo' => [
        'name' => 'tableentry',
        'label' => 'RR Items',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btnrrstockinfo',
        'lookupclass' => 'viewrrstockinfo',
        'access' => 'edititem',
        'color' => 'orange'
      ],
      'rrstockinfoposted' => [
        'name' => 'multigrid',
        'label' => 'RR Items',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btnrrstockinfo',
        'lookupclass' => 'viewrrstockinfo',
        'access' => 'edititem',
        'color' => 'orange'
      ],

      'viewprinfo' => [
        'name' => 'tableentry',
        'label' => 'PR Info',
        'icon' => 'view_module',
        'action' => 'customform',
        'class' => 'btnviewprinfo',
        'lookupclass' => 'viewprinfo',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'viewhistoricalcomments' => [
        'name' => 'tableentry',
        'label' => 'Historical Comments',
        'icon' => 'notes',
        'action' => 'customform',
        'class' => 'btnviewhistoricalcomments',
        'lookupclass' => 'viewhistoricalcomments',
        'access' => 'edititem',
        'visible' => true,
        'color' => 'grey'
      ],
      'viewcvitems' => [
        'name' => 'tableentry',
        'label' => 'PO Items',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btnviewcvitems',
        'lookupclass' => 'viewcvitems',
        'access' => 'edititem',
        'color' => 'brown'
      ],
      'viewcvitemsposted' => [
        'name' => 'multigrid',
        'label' => 'PO Items',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'class' => 'btnviewcvitems',
        'lookupclass' => 'viewcvitems',
        'access' => 'edititem',
        'color' => 'brown'
      ],
      'viewnotes' => [
        'name' => 'tableentry',
        'label' => 'Notes',
        'icon' => 'notes',
        'action' => 'customform',
        'class' => 'btnviewnotes',
        'lookupclass' => 'viewnotes',
        'access' => 'edititem',
        'color' => 'brown'
      ],
      'referencemodule' => [
        'name' => 'Reference',
        'icon' => 'cast_connected',
        'action' => 'tableentry',
        'lookupclass' => 'entryjumpmodule',
        'class' => 'btnstockserialno',
        'access' => 'view',
        'color' => 'green'
      ],
      'serialloc' => [
        'name' => 'Serial/Loc',
        'icon' => 'cast_connected',
        'action' => 'tableentry',
        'lookupclass' => 'entryjumpmodule',
        'class' => 'btnstockserialno',
        'access' => 'view',
        'color' => 'green'
      ],
      'outgoingtrans' => [
        'name' => 'Outgoingtrans',
        'icon' => 'visibility',
        'action' => 'tableentry',
        'lookupclass' => 'entryoutgoingtrans',
        'class' => 'btnoutgoingtrans',
        'access' => 'view',
        'color' => 'green'
      ],
      'jumpmodule' => [
        'name' => 'View',
        'icon' => 'visibility',
        'action' => 'jumpmodule',
        'lookupclass' => 'entryjumpmodule',
        'class' => 'btnstockserialno',
        'access' => 'view',
        'color' => 'green'
      ],
      'showpackinglist' => [
        'name' => 'tableentry',
        'icon' => 'photo_library',
        'action' => 'warehousingentry',
        'lookupclass' => 'entrypackingstock',
        'class' => 'btnstockshowpackinglist',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showsobreakdown' => [
        'name' => 'tableentry',
        'label' => 'SO Breakdown',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'lookupclass' => 'entrysobreakdown',
        'class' => 'btnshowsobreakdown',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showuomdetail' => [
        'name' => 'tableentry',
        'label' => 'Uom Detail',
        'icon' => 'view_module',
        'action' => 'tableentry',
        'lookupclass' => 'entryuomdetail',
        'class' => 'btnentryuomdetail',
        'access' => 'view',
        'color' => 'orange'
      ],
      'viewentrysoposted' => [
        'name' => 'customform',
        'label' => 'SO Breakdown',
        'icon' => 'photo_library',
        'action' => 'customform',
        'lookupclass' => 'viewentrysoposted',
        'class' => 'btnentrysoposted',
        'access' => 'view',
        'color' => 'orange'
      ],
      'entrysoposted' => [
        'name' => 'tableentry',
        'label' => 'SO Breakdown Posted',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'lookupclass' => 'entrysoposted',
        'class' => 'btnentrysoposted',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showboxdetails' => [
        'name' => 'tableentry',
        'label' => 'Box Details',
        'icon' => 'photo_library',
        'action' => 'warehousingentry',
        'lookupclass' => 'entryboxdetails',
        'class' => 'btnstockshowboxdetails',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showstockitems' => [
        'name' => 'tableentry',
        'icon' => 'photo_library',
        'label' => 'Item Details',
        'action' => 'warehousingentry',
        'class' => 'btnshowstockitems',
        'lookupclass' => 'viewshowstockitems',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'showstockitems_whchecker' => [
        'name' => 'tableentry',
        'icon' => 'photo_library',
        'label' => 'Item Details',
        'action' => 'warehousingentry',
        'class' => 'btnshowstockitems_whchecker',
        'lookupclass' => 'viewshowstockitems_whchecker',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'assigncolor' => [
        'name' => 'assigncolor',
        'label' => 'Assign Color',
        'icon' => 'check',
        'action' => 'generic',
        'access' => 'view',
        'class' => 'btnassigncolor',
        'color' => 'green'
      ],
      'removecolor' => [
        'name' => 'removecolor',
        'label' => 'Remove Color',
        'icon' => 'close',
        'action' => 'generic',
        'access' => 'view',
        'class' => 'btnremovecolor',
        'color' => 'red'
      ],
      'clearpr' => [
        'name' => 'clearpr',
        'label' => 'Clear Item',
        'icon' => 'done_all',
        'action' => 'generic',
        'access' => 'view',
        'class' => 'btnclearpr',
        'color' => 'indigo',
        'confirm' => true,
        'confirmlabel' => 'Are you sure want to clear this item?'
      ],
      'pickerdrop' => [
        'name' => 'pickerdrop',
        'label' => 'Drop to Deposit Location',
        'icon' => 'check',
        'action' => 'pickerdrop',
        'access' => 'view',
        'class' => 'btnpickerdrop',
        'color' => 'green'
      ],

      'pickerdropall' => [
        'name' => 'pickerdropall',
        'label' => 'Drop all items in Deposit Location',
        'icon' => 'done_all',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'class' => 'btnpickerdropall',
        'color' => 'orange',
        'confirm' => true,
        'confirmlabel' => 'Drop all items in Deposit Location?'
      ],
      'postomitem' => [
        'name' => 'postomitem',
        'label' => 'Post item',
        'icon' => 'done_all',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'class' => 'btnpostomitem',
        'color' => 'blue'
      ],
      'voiditems' => [
        'name' => 'voiditems',
        'label' => 'Void item',
        'icon' => 'remove',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'class' => 'btnvoiditems',
        'color' => 'red',
        'confirm' => true,
        'confirmlabel' => 'Do you want to void this item?'
      ],
      'assetin' => [
        'name' => 'assetin',
        'label' => 'IN Asset',
        'icon' => 'login',
        'action' => 'assetin',
        'access' => 'view',
        'class' => 'btnassetin',
        'color' => 'blue',
        'visible' => true,
        'confirm' => true,
        'confirmlabel' => 'Do you want to mark as IN this asset?'
      ],
      'assetout' => [
        'name' => 'assetout',
        'label' => 'OUT Asset',
        'icon' => 'logout',
        'action' => 'assetout',
        'access' => 'view',
        'class' => 'btnassetout',
        'color' => 'red',
        'visible' => true,
        'confirm' => true,
        'confirmlabel' => 'Do you want to mark as OUT this asset?'
      ],
      'validate' => [ //sample for action grid button doc listing
        'name' => 'validate',
        'label' => 'Scan Location',
        'icon' => 'check',
        'action' => 'scantext',
        'action2' => 'validate',
        'access' => 'view',
        'class' => 'btnvalidate',
        'color' => 'green'
      ],
      'showwhloc' => [
        'name' => 'tableentry',
        'icon' => 'photo_library',
        'action' => 'tableentry',
        'lookupclass' => 'entrylocation',
        'class' => 'btnstockshowwhloc',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showsubcat' => [
        'name' => 'tableentry',
        'label' => 'Sub-Category',
        'icon' => 'format_align_left',
        'action' => 'warehousingentry',
        'lookupclass' => 'entryitemsubcategory',
        'class' => 'btnshowsubcat',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showcheckerreplacement' => [
        'name' => 'tableentry',
        'label' => 'For Replacement Items',
        'icon' => 'format_align_left',
        'action' => 'warehousingentry',
        'lookupclass' => 'entrywhcheckerreplacement',
        'class' => 'btnshowcheckerreplacement',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showtimeline' => [
        'name' => 'tableentry',
        'label' => 'Status',
        'icon' => 'format_align_left',
        'action' => 'tableentry',
        'lookupclass' => 'showtimeline',
        'class' => 'btnshowtimeline',
        'access' => 'view',
        'color' => 'orange'
      ],

      'scanlocitem' => [
        'name' => 'Scan SKU',
        'icon' => 'flash_on',
        'action' => 'scanlocitem',
        'lookupclass' => 'scanlocitem',
        'class' => 'btnscanlocitem',
        'access' => 'view',
        'color' => 'orange'
      ],
      'splitqty' => [
        'name' => 'Split Quantity',
        'icon' => 'call_split',
        'action' => 'splitqty',
        'lookupclass' => 'splitqty',
        'class' => 'btnsplitqty',
        'access' => 'view',
        'color' => 'blue'
      ],
      'polatesttrans' => [
        'name' => 'showbalance',
        'label' => 'View Latest Transactions',
        'icon' => 'format_list_bulleted',
        'class' => 'btnpolatesttrans',
        'action' => 'lookuppolatesttrans',
        'lookupclass' => 'lookuppolatesttrans',
        'access' => 'view',
        'visible' => true,
        'color' => 'teal'
      ],
      'approvedtrans' => [
        'name' => 'Approved',
        'icon' => 'photo_library',
        'action' => 'optioncustomform',
        'class' => 'btnstockapprovedcanvass',
        'lookupclass' => 'approvedtrans',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'otentryapplication' => [
        'name' => 'OT HRS',
        'icon' => 'photo_library',
        'action' => 'customformdialog',
        'class' => 'btnothrsapproved',
        'lookupclass' => 'otentryapplication',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'customformrem' => [
        'name' => 'RETURN REMARKS',
        'icon' => 'check',
        'action' => 'customformdialog',
        'class' => 'btncustomformrem',
        'lookupclass' => 'customformrem',
        'access' => 'view',
        'visible' => true,
        'color' => 'red'
      ],
      'customformrevisionom' => [
        'name' => 'FOR REVISION',
        'icon' => 'refresh',
        'action' => 'customformdialog',
        'class' => 'btncustomformrevisionom',
        'lookupclass' => 'customformrevisionom',
        'access' => 'view',
        'visible' => true,
        'color' => 'red'
      ],
      'viewuomdet' => [
        'name' => 'Uom Detail',
        'icon' => 'view_module',
        'action' => 'customform',
        'class' => 'btnviewuomdet',
        'lookupclass' => 'viewuomdet',
        'access' => 'view',
        'visible' => true,
        'color' => 'red'
      ],

      'customformupdateinfo' => [
        'name' => 'UPDATE',
        'icon' => 'edit',
        'action' => 'customformdialog',
        'class' => 'btncustomformupdateinfo',
        'lookupclass' => 'customformupdateinfo',
        'access' => 'view',
        'visible' => true,
        'color' => 'red',
        'addedparams' => ['color']
      ],
      'addcompatible' => [
        'name' => 'compatible',
        'icon' => 'add',
        'action' => 'optioncustomform',
        'class' => 'btnaddcompatible',
        'lookupclass' => 'addcompatible',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'diagram' => [
        'name' => 'diagram',
        'icon' => 'photo_library',
        'action' => 'diagram',
        'class' => 'btndiagram',
        'access' => 'view',
        'color' => 'blue',
        'lookupclass' => 'viewref'
      ],
      'incentivedocno' => [
        'name' => 'multigrid',
        'icon' => 'photo_library',
        'action' => 'warehousingentry',
        'class' => 'btnaddsub',
        'lookupclass' => 'entryincentivedocno',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],

      'pickitemcompatible' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnpickpickitemcompatible',
        'lookupclass' => 'pickitemcompatible',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'additemsubcat' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'warehousingentry',
        'class' => 'btnadditemsubcat',
        'lookupclass' => 'entryitemsubcat',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'addapprovercat' => [
        'name' => 'multigrid',
        'label' => 'Add Category',
        'icon' => 'add',
        'action' => 'ati',
        'class' => 'btnaddapprovercat',
        'lookupclass' => 'entryaddapprovercat',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'addapproverdept' => [
        'name' => 'multigrid',
        'label' => 'Add Department',
        'icon' => 'add',
        'action' => 'ati',
        'class' => 'btnaddapproverdept',
        'lookupclass' => 'entryaddapproverdept',
        'access' => 'view',
        'visible' => true,
        'color' => 'blue'
      ],
      'addapproverusers' => [
        'name' => 'multigrid',
        'label' => 'Add Approver',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaddapproverusers',
        'lookupclass' => 'entryaddapproverusers',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'addapprovers' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaaddapprovers',
        'lookupclass' => 'entryapprovers',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'addlinearapprovers' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaaddlinearapprovers',
        'lookupclass' => 'entrylinearapprovers',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'addsubpowercat' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'label' => 'Add Sub-Category',
        'action' => 'kwhmonitoring',
        'class' => 'btnentrysubpowercat',
        'lookupclass' => 'entrysubpowercat',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'addsubpowercat2' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'label' => 'Add Sub-Category',
        'action' => 'kwhmonitoring',
        'class' => 'btnentrysubpowercat2',
        'lookupclass' => 'entrysubpowercat2',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'bankcharges' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaddbankcharges',
        'lookupclass' => 'entrybankcharges',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'sync' => [
        'name' => 'sync',
        'icon' => 'sync',
        'action' => 'syncperitem',
        'class' => 'btnsyncsave',
        'access' => 'edititem',
        'color' => 'yellow'
      ],
      'approverequest' => [
        'name' => 'approverequest',
        'icon' => 'check',
        'action' => 'stockstatusposted',
        'class' => 'btnapproverequest',
        'access' => 'view',
        'color' => 'green'
      ],
      'disapproverequest' => [
        'name' => 'disapproverequest',
        'label' => 'Disapprove Request',
        'icon' => 'close',
        'action' => 'stockstatusposted',
        'class' => 'btndisapproverequest',
        'access' => 'view',
        'color' => 'red'
      ],
      'forcheckingcd' => [
        'name' => 'forcheckingcd',
        'icon' => 'check',
        'label' => 'For Checking',
        'action' => 'stockstatusposted',
        'class' => 'btnforcheckingcd',
        'access' => 'view',
        'color' => 'blue',
        'confirm' => true,
        'confirmlabel' => 'Tag this canvass as For Checking?'
      ],
      'approvesummary' => [
        'name' => 'approvesummary',
        'icon' => 'check',
        'label' => 'Approve canvass',
        'action' => 'approvedsummary',
        'class' => 'btnapprovesummary',
        'access' => 'view',
        'color' => 'green',
        'confirm' => true,
        'confirmlabel' => 'Are you sure you want to approve this canvass?'
      ],
      'disapprovesummary' => [
        'name' => 'disapprovesummary',
        'icon' => 'close',
        'label' => 'Reject canvass',
        'action' => 'disapprovedsummary',
        'class' => 'btndisapprovesummary',
        'access' => 'view',
        'color' => 'red',
        'confirm' => true,
        'confirmlabel' => 'Are you sure you want to reject this canvass?'
      ],
      'viewsalesgroupagent' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnviewsalesgroupagent',
        'lookupclass' => 'viewsalesgroupagent',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'addsub' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'construction',
        'class' => 'btnaddsub',
        'lookupclass' => 'addsubstages',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'addpritem' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'customform',
        'class' => 'btnaddsub',
        'lookupclass' => 'addpritem',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'addsubitems' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'construction',
        'lookupclass' => 'addsubitems',
        'class' => 'btnentrystockstages',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary',
      ],
      'addsubactivity' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'construction',
        'lookupclass' => 'addsubactivity',
        'class' => 'btnentrystockstages',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary',
      ],
      'addprojactivity' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'construction',
        'class' => 'btnaddsub',
        'lookupclass' => 'entryprojectactivity',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'addpsubactivity' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'construction',
        'class' => 'btnaddsub',
        'lookupclass' => 'entryprojectsubactivity',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'iteminfo' => [
        'name' => 'iteminfo',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'iteminfo',
        'lookupclass' => 'viewiteminfo',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'green'
      ],
      'rcattendance' => [
        'name' => 'tableentry',
        'icon' => 'event_available',
        'action' => 'tableentry',
        'label' => 'Attendance',
        'lookupclass' => 'entryrcattendance',
        'class' => 'btnentryrcattendance',
        'access' => 'edititem',
        'color' => 'green'
      ],
      'rcremarks' => [
        'name' => 'tableentry',
        'icon' => 'notes',
        'action' => 'tableentry',
        'label' => 'Report Card Remarks',
        'lookupclass' => 'entryrcremarks',
        'class' => 'btnentryrcremarks',
        'access' => 'edititem',
        'color' => 'green'
      ],
      'rcenggrade' => [
        'name' => 'tableentry',
        'icon' => 'history_edu',
        'action' => 'tableentry',
        'label' => 'English Grades',
        'lookupclass' => 'entryrcenggrades',
        'class' => 'btnentryrcenggrades',
        'access' => 'edititem',
        'color' => 'green'
      ],
      'rcchigrade' => [
        'name' => 'tableentry',
        'icon' => 'history_edu',
        'action' => 'tableentry',
        'label' => 'Chinese Grades',
        'lookupclass' => 'entryrcchigrades',
        'class' => 'btnentryrcchigrades',
        'access' => 'edititem',
        'color' => 'green'
      ],
      'oschecker' => [
        'name' => 'oschecker',
        'icon' => 'notes',
        'action' => 'customform',
        'class' => 'oschecker',
        'lookupclass' => 'viewoschecker',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'teal'
      ],
      'view_advance' => [
        'name' => 'multigrid',
        'icon' => 'speaker_notes',
        'action' => 'tableentry',
        'class' => 'btnview_advance',
        'lookupclass' => 'entrypaymentadvance',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'view_loans' => [
        'name' => 'multigrid',
        'icon' => 'speaker_notes',
        'action' => 'tableentry',
        'class' => 'btnview_loans',
        'lookupclass' => 'entrypaymentloans',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'addempbudget' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaddempbudget',
        'lookupclass' => 'entryempbudget',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'addattendee' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaddattendee',
        'lookupclass' => 'entryattendee',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'addvritems' => [
        'name' => 'tableentry',
        'label' => 'ITEMS/CARGO',
        'icon' => 'inventory',
        'action' => 'vehiclescheduling',
        'lookupclass' => 'entryvritems',
        'class' => 'btnentrystockitem',
        'access' => 'add',
        'color' => 'green',
      ],
      'addpassenger' => [
        'name' => 'tableentry',
        'label' => 'PASSENGERS',
        'icon' => 'person_add',
        'action' => 'vehiclescheduling',
        'lookupclass' => 'entryvrpassenger',
        'class' => 'btnentrypassenger',
        'access' => 'add',
        'color' => 'blue',
      ],
      'addapprover' => [
        'name' => 'tableentry',
        'label' => 'Approver',
        'icon' => 'person_add',
        'action' => 'vehiclescheduling',
        'lookupclass' => 'entryvrpassenger',
        'class' => 'btnentrypassenger',
        'access' => 'add',
        'color' => 'blue',
      ],
      'view_customeritems' => [
        'name' => 'multigrid',
        'icon' => 'speaker_notes',
        'action' => 'vehiclescheduling',
        'class' => 'btnview_view_customeritems',
        'lookupclass' => 'entryvrcustomeritems',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],

      'duplicatedoc' => [
        'name' => 'duplicatedoc',
        'icon' => 'content_copy',
        'action' => 'stockstatusposted',
        'class' => 'btndiagram',
        'access' => 'new',
        'color' => 'blue',
        'lookupclass' => 'duplicatedoc'
      ],
      'addpistock' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'production',
        'class' => 'btnaddpistock',
        'lookupclass' => 'addpistock',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'createprofile' => [
        'name' => 'stockstatusposted',
        'icon' => 'person_add',
        'action' => 'tableentry',
        'action2' => 'createprofile',
        'class' => 'btncreateprofile',
        'access' => 'new',
        'color' => 'primary',
        'lookupclass' => 'entryattendee'
      ],
      'addphase' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaddphase',
        'lookupclass' => 'entryphase',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary',
        'label' => 'Add Phase'
      ],
      'addblklot' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnaddblklot',
        'lookupclass' => 'entryblklot',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary',
        'label' => 'Add Blk&Lot'
      ],
      'addsubamenity' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'realestate',
        'class' => 'btnaddsubamenity',
        'lookupclass' => 'entrysubamenities',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary',
        'label' => 'Add Sub-Amenity'
      ],
      'addhousemodel' => [
        'name' => 'multigrid',
        'icon' => 'house',
        'action' => 'tableentry',
        'class' => 'btnhousemodel',
        'lookupclass' => 'entryhousemodel',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary',
        'label' => 'Add House Model'
      ],
      'listingshowbalance' => [
        'name' => 'tableentry',
        'label' => 'Show Balance',
        'icon' => 'photo_library',
        'action' => 'warehousingentry',
        'lookupclass' => 'entryshowbalance',
        'class' => 'btnshowbalance',
        'access' => 'view',
        'color' => 'orange'
      ],
      'listingshowproductinquirybalance' => [
        'name' => 'tableentry',
        'label' => 'Show Product Inquiry Balance',
        'icon' => 'visibility',
        'action' => 'warehousingentry',
        'lookupclass' => 'entryshowproductinquirybalance',
        'class' => 'btnshowproductinquirybalance',
        'access' => 'view',
        'color' => 'orange'
      ],
      'listingshowcolorbalance' => [
        'name' => 'multigrid',
        'label' => 'Show Color Balance',
        'icon' => 'visibility',
        'action' => 'warehousingentry',
        'lookupclass' => 'entryshowcolorbalance',
        'class' => 'btnshowcolorbalance',
        'access' => 'view',
        'color' => 'red'
      ],
      'listingshowserialnocolor' => [
        'name' => 'multigrid',
        'label' => 'Show Serial No Of Color',
        'icon' => 'visibility',
        'action' => 'warehousingentry',
        'lookupclass' => 'entryshowserialnocolor',
        'class' => 'btnshowserialnocolor',
        'access' => 'view',
        'color' => 'red'
      ],
      'addplantype' => [
        'name' => 'multigrid',
        'icon' => 'spa',
        'action' => 'tableentry',
        'class' => 'btnplantype',
        'lookupclass' => 'entryplantype',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary',
        'label' => 'Add Plan Types'
      ],
      'disconnect' => [
        'name' => 'disconnect',
        'label' => 'Disconnection',
        'icon' => 'close',
        'action' => 'stockstatusposted',
        'access' => 'view',
        'class' => 'btndisconnect',
        'color' => 'red',
        'confirm' => true,
        'confirmlabel' => 'Are you sure want to disconnect?'
      ],
      'new' => [
        'name' => 'new',
        'icon' => 'add',
        'action' => 'newitem',
        'class' => 'btnnewitem',
        'access' => 'edititem',
        'color' => 'blue',
        'label' => 'New Item',
        'confirm' => true,
        'confirmlabel' => 'Are you sure you want to assign new barcode?'
      ],
      'stockcolor' => [
        'name' => 'tableentry',
        'icon' => 'speaker_notes',
        'action' => 'tableentry',
        'class' => 'stockcolor',
        'lookupclass' => 'entrystockcolor',
        'access' => 'edititem',
        'totalfield' => [],
        'color' => 'green'
      ],
      'entryempprojectlog' => [
        'name' => 'multigrid',
        'label' => 'Project',
        'icon' => 'view_module',
        'action' => 'tableentry',
        'class' => 'btnviewempproject',
        'lookupclass' => 'entryempprojectlog',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'entryempprojectlogb' => [
        'name' => 'multigrid',
        'label' => 'Project',
        'icon' => 'view_module',
        'action' => 'tableentry',
        'class' => 'btnviewempproject2',
        'lookupclass' => 'entryempprojectlogb',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'viewtsdetail' => [
        'name' => 'viewtsdetail',
        'icon' => 'visibility',
        'action' => 'customform',
        'class' => 'btnviewtsdetail',
        'lookupclass' => 'viewtsdetail',
        'access' => 'view',
        'color' => 'primary'
      ],
      'viewobapplication' => [
        'name' => 'viewobapplication',
        'icon' => 'visibility',
        'action' => 'customform',
        'class' => 'btnviewobapplication',
        'lookupclass' => 'viewobapplication',
        'access' => 'view',
        'color' => 'primary'
      ],
      'viewloandetail' => [
        'name' => 'viewloandetail',
        'icon' => 'view_module',
        'action' => 'customform',
        'class' => 'btnviewloandetail',
        'lookupclass' => 'viewloandetail',
        'access' => 'view',
        'color' => 'primary'
      ],
      'viewloanattachment' => [
        'name' => 'viewloanattachment',
        'icon' => 'web_stories',
        'action' => 'customform',
        'class' => 'btnviewloanattachment',
        'lookupclass' => 'viewloanattachment',
        'access' => 'view',
        'color' => 'primary'
      ],
      'viewenginehistory' => [
        'name' => 'tableentry',
        'label' => 'Engine History',
        'icon' => 'visibility',
        'action' => 'tableentry',
        'lookupclass' => 'viewenginehistory',
        'class' => 'btnenginehistory',
        'access' => 'view',
        'color' => 'orange'
      ],
      'showremhistory' => [
        'name' => 'tableentry',
        'label' => 'View Remarks',
        'icon' => 'format_align_left',
        'action' => 'tableentry',
        'lookupclass' => 'showremhistory',
        'class' => 'btnshowremhistory',
        'access' => 'viewrem',
        'color' => 'orange'
      ],
      'customformremarks' => [
        'name' => 'Add Remarks',
        'icon' => 'edit',
        'action' => 'customformdialog',
        'class' => 'btncustomformremarks',
        'lookupclass' => 'customformremarks',
        'access' => 'editrem',
        'visible' => true,
        'color' => 'green'
      ],
      'customformappstat' => [
        'name' => 'Applicant Status',
        'icon' => 'edit',
        'action' => 'customformdialog',
        'class' => 'btncustomformappstat',
        'lookupclass' => 'customformappstat',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],


      'loanpurpose' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'tableentry',
        'class' => 'btnloanpurpose',
        'lookupclass' => 'loanpurpose',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'supervisors' => [
        'name' => 'multigrid',
        'label' => 'Supervisors',
        'icon' => 'supervisor_account',
        'action' => 'tableentry',
        'class' => 'btnviewsupervisors',
        'lookupclass' => 'viewsupervisors',
        'access' => 'additem',
        'color' => 'primary'
      ],
      'approvers' => [
        'name' => 'multigrid',
        'label' => 'Approvers',
        'icon' => 'supervisor_account',
        'action' => 'tableentry',
        'class' => 'btnviewapprovers',
        'lookupclass' => 'viewapprovers',
        'access' => 'additem',
        'color' => 'primary'
      ],
      'approve' => [
        'name' => 'approve',
        'label' => 'Approve',
        'icon' => 'check',
        'action' => 'approveapp',
        'class' => 'btnapproveapp',
        'access' => 'view',
        'color' => 'primary'
      ],
      'disapprove' => [
        'name' => 'disapprove',
        'label' => 'Disapprove',
        'icon' => 'close',
        'action' => 'disapproveapp',
        'class' => 'btndisapproveapp',
        'access' => 'view',
        'color' => 'negative'
      ],
      'accept' => [
        'name' => 'accept',
        'label' => 'Accept',
        'icon' => 'check',
        'action' => 'accept',
        'class' => 'btnaccept',
        'access' => 'view',
        'color' => 'primary'
      ],
      'process' => [
        'name' => 'process',
        'label' => 'Process',
        'icon' => 'check',
        'action' => 'processapp',
        'class' => 'btnprocessapp',
        'access' => 'view',
        'color' => 'primary'
      ],

      'applications' => [
        'name' => 'multigrid',
        'icon' => 'format_align_left',
        'action' => 'tableentry',
        'class' => 'btnapplications',
        'lookupclass' => 'viewallapp',
        'access' => 'view',
        'visible' => true,
        'color' => 'green'
      ],
      'addattachments' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'announcemententry',
        'class' => 'btnadditemnoticeattach',
        'lookupclass' => 'addattachments',
        'label' => 'Attachments',
        'access' => 'load',
        'visible' => true,
        'color' => 'primary'
      ],
      'viewtaskinfo' => [
        'name' => 'tableentry',
        'label' => 'Task Details',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'btnviewtaskinfo',
        'lookupclass' => 'viewtaskinfo',
        'access' => 'edititem',
        'visible' => true,
        'color' => 'orange'
      ],

      'addcomments' => [
        'name' => 'Comments',
        'icon' => 'notes',
        'action' => 'customformdialog',
        'class' => 'btnviewcomments',
        'lookupclass' => 'viewhistoricalcomments',
        'access' => 'edititem',
        'visible' => true,
        'color' => 'green'
      ],


      'assigntask' => [
        'name' => 'assigntask',
        'label' => 'Assign Task',
        'icon' => 'check',
        'action' => 'assigntask',
        'access' => 'view',
        'class' => 'btnassigntask',
        'color' => 'green'
      ],

      'completetask' => [
        'name' => 'completetask',
        'label' => 'Complete Task',
        'icon' => 'check',
        'action' => 'completetask',
        'access' => 'view',
        'class' => 'btncompletetask',
        'color' => 'orange'
      ],
      'reassign' => [
        'name' => 'reassign',
        'label' => 'Cancel/Reassign',
        'icon' => 'refresh',
        'action' => 'reassign',
        'class' => 'btnareassign',
        'access' => 'view',
        'color' => 'primary'
      ],
      'viewrsdetail' => [
        'name' => 'tableentry',
        'label' => 'Designation Details',
        'icon' => 'format_align_left',
        'action' => 'tableentry',
        'lookupclass' => 'tabdesignation',
        'class' => 'btnviewrsdetail',
        'access' => 'view',
        'color' => 'orange'
      ],

      'itemhistory' => [
        'name' => 'itemhistory',
        'label' => 'Transaction History',
        'icon' => 'visibility',
        'action' => 'customform',
        'class' => 'btnitemhistory',
        'lookupclass' => 'viewstockcardtransactionledger',
        'access' => 'view',
        'color' => 'green'
      ],

      'poitemhistory' => [
        'name' => 'poitemhistory',
        'label' => 'PO History',
        'icon' => 'visibility',
        'action' => 'customform',
        'class' => 'btnpoitemhistory',
        'lookupclass' => 'viewstockcardpo',
        'access' => 'view',
        'color' => 'orange'
      ],

      'intransaction' => [
        'name' => 'intransaction',
        'label' => 'In-Transaction',
        'icon' => 'visibility',
        'action' => 'customformdialog',
        'class' => 'btnintransaction',
        'lookupclass' => 'viewstockcardrr',
        'access' => 'view',
        'color' => 'green'
      ],

      'soitemhistory' => [
        'name' => 'soitemhistory',
        'label' => 'SO History',
        'icon' => 'visibility',
        'action' => 'customformdialog',
        'class' => 'btnsoitemhistory',
        'lookupclass' => 'viewstockcardso',
        'access' => 'view',
        'color' => 'primary'
      ],

      'cancel' => [
        'name' => 'cancel',
        'icon' => 'refresh',
        'class' => 'btnstockcancel',
        'action' => 'cancel',
        'access' => 'view',
        'color' => 'orange',
        'label' => 'Cancel Task'
      ],


      'commenthistory' => [
        'name' => 'taskentry',
        'label' => 'Comments',
        'icon' => 'notes',
        'action' => 'customform',
        'class' => 'btncommenthistory',
        'lookupclass' => 'commenthistory',
        'access' => 'edititem',
        'visible' => true,
        'color' => 'grey'
      ],

      'undone' => [
        'name' => 'undone',
        'label' => 'Undone task',
        'icon' => 'refresh',
        'class' => 'btnstockundone',
        'action' => 'undone',
        'access' => 'view',
        'color' => 'red',
        'visible' => true
      ],

      'viewtaskinfo2' => [
        'name' => 'tableentry',
        'label' => 'Task Details',
        'icon' => 'speaker_notes',
        'action' => 'customform',
        'class' => 'btnviewtaskinfo2',
        'lookupclass' => 'viewtaskinfo2',
        'access' => 'edititem',
        'visible' => true,
        'color' => 'orange'
      ],
      'service' => [
        'name' => 'multigrid',
        'label' => 'Add Service',
        'icon' => 'design_services',
        'action' => 'tableentry',
        'class' => 'btnviewservice',
        'lookupclass' => 'viewservice',
        'access' => 'additem',
        'color' => 'primary'
      ],
      'addpic' => [
        'name' => 'multigrid',
        'icon' => 'add',
        'action' => 'adddocument',
        'class' => 'btnaddpic',
        'lookupclass' => 'addattachments',
        'label' => 'Add Pic',
        'access' => 'load',
        'visible' => true,
        'color' => 'primary'
      ],
      'viewdailytaskattachment' => [
        'name' => 'viewdailytaskattachment',
        'icon' => 'web_stories',
        'action' => 'customform',
        'class' => 'btnviewdailytaskattachment',
        'lookupclass' => 'viewdailytaskattachment',
        'access' => 'view',
        'color' => 'primary'
      ],

      'viewsection' => [
        'name' => 'multigrid',
        'icon' => 'web_stories',
        'action' => 'hrisentry',
        'class' => 'btnviewsection',
        'lookupclass' => 'viewsection',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'viewbranch' => [
        'name' => 'multigrid',
        'icon' => 'web_stories',
        'action' => 'hrisentry',
        'class' => 'btnviewbranch',
        'lookupclass' => 'viewbranch',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'viewdepartment' => [
        'name' => 'multigrid',
        'icon' => 'web_stories',
        'action' => 'hrisentry',
        'class' => 'btnviewdepartment',
        'lookupclass' => 'viewdepartment',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'viewposition' => [
        'name' => 'multigrid',
        'icon' => 'web_stories',
        'action' => 'hrisentry',
        'class' => 'btnviewposition',
        'lookupclass' => 'viewposition',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'viewemployee' => [
        'name' => 'multigrid',
        'icon' => 'web_stories',
        'action' => 'hrisentry',
        'class' => 'btnviewemployee',
        'lookupclass' => 'viewemployee',
        'access' => 'view',
        'visible' => true,
        'color' => 'primary'
      ],
      'enddate' => [
        'name' => 'tableentry',
        'label' => 'End',
        'icon' => 'done_all',
        'class' => 'btnstockenddate',
        'action' => 'customform',
        'lookupclass' => 'viewenddate',
        'access' => 'edititem',
        'color' => 'red',
        'visible' => true
      ],

    ];
  }
} // end class
