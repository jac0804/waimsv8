<?php

namespace App\Http\Classes\modules\purchase;

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
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\Storage;

class po3
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PURCHASE ORDER';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => false];
  public $tablenum = 'transnum';
  public $head = 'pohead';
  public $hhead = 'hpohead';
  public $stock = 'postock';
  public $hstock = 'hpostock';
  public $tablelogs = 'transnum_log';
  public $statlogs = 'transnum_stat';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  public $infohead = 'headinfotrans';
  public $hinfohead = 'hheadinfotrans';
  private $stockselect;
  public $dqty = 'rrqty';
  public $hqty = 'qty';
  public $damt = 'rrcost';
  public $hamt = 'cost';
  private $fields = [
    'trno',
    'docno',
    'dateid',
    'due',
    'client',
    'clientname',
    'yourref',
    'ourref',
    'rem',
    'terms',
    'forex',
    'cur',
    'wh',
    'address',
    'projectid',
    'subproject',
    'branch',
    'deptid',
    'tax',
    'vattype',
    'empid',
    'sotrno',
    'billid',
    'shipid',
    'billcontactid',
    'shipcontactid',
    'revision',
    'rqtrno',
    'deldate',
    'deladdress',
    'whreceiver',
    'insurance',
    'ewtrate',
    'ewt',
    'projectid',
    'phaseid',
    'modelid',
    'blklotid',
    'amenityid',
    'subamenityid',
    'expiryid',
    'isfa'
  ];
  private $except = ['trno', 'dateid', 'due'];
  private $blnfields = ['isfa'];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;
  private $helpClass;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary']
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
    $this->reporter = new SBCPDF;
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 63
    );
    return $attrib;
  }

  public function loadaform($config)
  {
    ini_set('max_execution_time', -1);

    $center = $config['params']['center'];
    $this->modulecount('RR', 'red-3', 'Receiving Report', $center);
    $this->modulecount('employee', 'green', 'Active Employees', $center);
    $this->modulecount('regular', 'yellow-8', 'Regular Employees', $center);
    $this->modulecount('employee3', 'red-3', 'Sales & Operation', $center);
    $this->modulecount('employee4', 'pink-3', 'Support Group', $center);

    $this->companies();
    $sorting = ['qcard', 'actionlist', 'dailynotif', 'sbcgraph', 'sbclist'];
    return [
        'status' => true,
        'msg' => 'Loaded Success',
        'obj' => $this->config,
        'sorting' => $sorting
    ];
  }

  private function modulecount($doc, $color, $caption, $center)
  {
      $dateid = date('Y-m-d');
      $count = $total = 0;
      switch ($doc) {
          case 'employee':
              $count = $this->coreFunctions->datareader("select count(isactive) as value from employee where isactive=1");
              break;
          case 'regular':
              $count = $this->coreFunctions->datareader("select count(e.empstatus) as value from employee as emp left join empstatentry as e on e.line=emp.empstatus where emp.isactive=1 and ucase(e.empstatus)='REGULAR'");
              break;
          case 'RR':
              $qry = "select ifnull(count(glhead.trno), 0) as counting from glhead left join cntnum on cntnum.trno=glhead.trno where glhead.doc = '" . $doc . "' and cntnum.center='" . $center . "' and date(glhead.dateid) = '" . $dateid . "'";
              $pap = $this->coreFunctions->opentable($qry);
              $qry1 = "select ifnull(count(lahead.trno), 0) as counting from lahead left join cntnum on cntnum.trno=lahead.trno where lahead.doc = '" . $doc . "' and cntnum.center='" . $center . "' and date(lahead.dateid) = '" . $dateid . "'";
              $uap = $this->coreFunctions->opentable($qry1);
              $total = $pap[0]->counting + $uap[0]->counting;
              break;
      }

      if ($doc == 'RR') {
          $this->config['qcard'][$doc] =
          [
              'class' => 'bg-' . $color . ' text-white',
              'headalign' => 'right',
              'title' => $doc . ' Transaction',
              'subtitle' => $dateid . ' - ' . $total,
              'titlesize' => '20px',
              'subtitlesize' => '25px',
              'object' => 'btn',
              'isvertical' => true,
              'align' => 'right',
              'detail' => [
                'btn1' => [
                  'label' => 'Posted ' . $pap[0]->counting,
                  'img' => '/images/employee/default_emp_portal.png',
                  'type' => 'customform',
                  'action' => 'loadrr',
                  'lookupclass' => 'rrposted',
                  'classid' => 'posted'
                ],
                'btn2' => [
                  'label' => 'Unposted ' . $uap[0]->counting,
                  'type' => 'customform',
                  'action' => 'loadrr',
                  'lookupclass' => 'rrunposted',
                  'classid' => 'unposted'
                ]
              ]
          ];
      } else {
          $this->config['qcard'][$doc] =
              [
                  'class' => 'bg-' . $color . ' text-white',
                  'headalign' => 'left',
                  'title' => $count,
                  'subtitle' => $caption,
                  'titlesize' => '20px',
                  'subtitlesize' => '25px',
                  'object' => 'btn',
                  'isvertical' => true,
                  'align' => 'right',
                  'detail' => ['btn1' => [
                      'label' => '100%',
                      'type' => 'customform',
                      'action' => $doc,
                      'classid' => 'posted'
                  ],]
              ];
      }
  } // end function

  public function companies()
  {

      $curdate = $this->othersClass->getCurrentDate();

      $bday = $this->coreFunctions->opentable("select divname, '' as picture from division");

      $data = [];

      $row1 = [];
      $row2 = [];
      $row3 = [];

      foreach ($bday as $key => $value) {

          if ($value->picture == '') {
              $value->picture = 'images/employee/company_default.png';
          } else {
              $value->picture = ltrim($value->picture, '/');
          }

          $line = [
              'subtitle' => $value->divname,
              'subtitle2' => 'subtitle2',
              'subtitle3' => ['text' => 'subtitle3', 'icon' => 'list', 'color' => 'red'],
              'subtitle4' => 'subtitle4',
              'dateid' => 'date',
              'image' => Storage::disk('public')->url($value->picture)
          ];

          array_push($row1, $line);
      }

      array_push($data, ['title' => [
          'text' => "Total Employees: 2",
          'icon' => 'star',
          'bgcolor' => 'red-10',
          'textcolor' => 'white'
      ], 'data' => $row1]);

      $this->config['dailynotif']['companies'] = ['data' => $data, 'title' => ['text' => 'EMPLOYEE OVERVIEW 2025', 'icon' => 'rss_feed', 'bgcolor' => 'red-5', 'textcolor' => 'white']];
  }

  public function loadcustomform($config)
  {
    $this->config = $config;
    switch($config['params']['action2']) {
      case 'loadrr':
        $this->config['return'] = $this->loadrr($config);
        break;
    }
    return $this;
  }
  public function loadrr($config)
  {
    $txtfield = $this->rrcreateHeadField($config);
    $txtdata = $this->rrparamsdata($config);
    $data = $this->rrdata($config);
    $tab = $this->rrcreateTab($config);
    $tabbtn = $this->rrcreatetabbutton($config);
    $modulename = 'UNPOSTED - RR TRANSACTION';
    if($config['params']['classid'] == 'posted') $modulename = 'POSTED - RR TRANSACTION';
    $gridname = 'customformacctg';
    $issearchshow = true;
    $showclosebtn = true;
    $style = 'width:1200px;max-width:1200px;';
    return ['txtfield'=>$txtfield, 'txtdata'=>$txtdata, 'tab'=>$tab, 'tabbuttons'=>$tabbtn, 'status'=>true, 'msg'=>'Loaded Success', 'modulename'=>$modulename, 'doc'=>$this->config['params']['lookupclass'], 'action'=>$this->config['params']['action'], 'style'=>$style, 'gridname'=>$gridname, 'data'=>$data, 'issearchshow'=>$issearchshow, 'showclosebtn'=>$showclosebtn]; 
  }

  public function rrcreatetabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function rrcreateTab($config)
  {
    $tab = ['customformacctg' => ['gridcolumns' => ['action', 'status', 'dateid', 'docno', 'db', 'cr', 'bal', 'ref', 'rem']]];
    $stockbuttons = [];
    $stockbuttons = ['jumpmodule'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['customformacctg']['columns'][3]['align'] = 'right';
    $obj[0]['customformacctg']['columns'][4]['align'] = 'right';
    $obj[0]['customformacctg']['columns'][5]['align'] = 'right';
    return $obj;
  }

  public function rrdata($config)
  {
    return [];
  }

  public function rrparamsdata($config)
  {
    $doc = $config['params']['lookupclass'];
    $classid = $config['params']['classid'];
    switch ($classid) {
      case 'posted':
        $this->modulename = 'POSTED - RR TRANSACTION';
        break;
      case 'unposted':
        $this->modulename = 'UNPOSTED - RR TRANSACTION';
        break;
    }
    return $this->coreFunctions->opentable("select adddate(left(now(),10),-360) as dateid, 0.0 as db, 0.0 as cr,0.0 as bal,? as classid, '" . $doc . "' as doc ", [$classid]);
  }

  public function rrcreateHeadField($config)
  {
    $tab = ['customformacctg' => ['gridcolumns' => ['action', 'status', 'dateid', 'docno', 'db', 'cr', 'bal', 'ref', 'rem']]];
    $stockbuttons = [];
    $stockbuttons = ['jumpmodule'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0]['customformacctg']['columns'][3]['align'] = 'right';
    $obj[0]['customformacctg']['columns'][4]['align'] = 'right';
    $obj[0]['customformacctg']['columns'][5]['align'] = 'right';
    return $obj;
  }

  public function execute(){
   return response()->json($this->config['return'],200);  
  } // end function
} //end class
