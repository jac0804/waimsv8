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

class po2
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'PURCHASE ORDER';
  public $gridname = 'customformacctg';
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
  public $issearchshow = true;
  public $showclosebtn = true;
  public $style = 'width:1200px;max-width:1200px;';

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
    $sorting = ['qcard', 'actionlist', 'dailynotif', 'sbcgraph', 'sbclist'];
    return [
        'status' => true,
        'msg' => 'Loaded Success',
        'obj' => $this->config,
        'sorting' => $sorting
    ];
    $data = [
      [
        'class' => 'bg-primary text-white',
        'headalign' => 'right',
        'title' => 'PO Transaction',
        'subtitle' => 'Subtitle 1',
        'titlesize' => '20px',
        'subtitlesize' => '25px',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'btnalign' => 'left',
        'detail' => [
          'btn1' => [
            'label' => 'Posted',
            'type' => 'customform',
            'action' => 'loadrr',
            'lookupclass' => 'rrposted',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted',
            'type' => 'customform',
            'action' => 'loadrr',
            'lookupclass' => 'rrunposted',
            'classid' => 'unposted'
          ]
        ]
      ], [
        'class' => 'bg-primary text-white',
        'headalign' => 'right',
        'title' => 'PO Transaction',
        'subtitle' => 'Subtitle 1',
        'titlesize' => '20px',
        'subtitlesize' => '25px',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'btnalign' => 'right',
        'detail' => [
          'btn1' => [
            'label' => 'Posted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'unposted'
          ]
        ]
      ], [
        'class' => 'bg-primary text-white',
        'headalign' => 'right',
        'title' => 'PO Transaction',
        'subtitle' => 'Subtitle 1',
        'titlesize' => '20px',
        'subtitlesize' => '25px',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'btnalign' => 'right',
        'detail' => [
          'btn1' => [
            'label' => 'Posted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'unposted'
          ]
        ]
      ], [
        'class' => 'bg-primary text-white',
        'headalign' => 'right',
        'title' => 'PO Transaction',
        'subtitle' => 'Subtitle 1',
        'titlesize' => '20px',
        'subtitlesize' => '25px',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'btnalign' => 'left',
        'detail' => [
          'btn1' => [
            'label' => 'Posted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'unposted'
          ]
        ]
      ], [
        'class' => 'bg-primary text-white',
        'headalign' => 'right',
        'title' => 'PO Transaction',
        'subtitle' => 'Subtitle 1',
        'titlesize' => '20px',
        'subtitlesize' => '25px',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'btnalign' => 'right',
        'detail' => [
          'btn1' => [
            'label' => 'Posted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'unposted'
          ]
        ]
      ], [
        'class' => 'bg-primary text-white',
        'headalign' => 'right',
        'title' => 'PO Transaction',
        'subtitle' => 'Subtitle 1',
        'titlesize' => '20px',
        'subtitlesize' => '25px',
        'object' => 'btn',
        'isvertical' => true,
        'align' => 'right',
        'btnalign' => 'right',
        'detail' => [
          'btn1' => [
            'label' => 'Posted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'posted'
          ],
          'btn2' => [
            'label' => 'Unposted',
            'type' => 'customform',
            'action' => 'PO',
            'classid' => 'unposted'
          ]
        ]
      ]
    ];
    return ['status'=>true, 'msg'=>'Data loaded', 'data'=>$data];
  }

  public function paramsdata($config)
  {
      $doc = $config['params']['lookupclass'];
      $classid = $config['params']['classid'];
      switch ($classid) {
          case 'posted':
              $this->modulename = 'POSTED - PO TRANSACTION';
              break;
          case 'unposted':
              $this->modulename = 'UNPOSTED - PO TRANSACTION';
              break;
      }
      return $this->coreFunctions->opentable("select left(now(),10) as dateid,? as classid, '" . $doc . "' as doc ", [$classid]);
  }

  public function createHeadField($config)
  {
      $fields = ['dateid'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'dateid.readonly', false);

      $fields = ['refresh'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'refresh.action', 'rr');

      return array('col1' => $col1, 'col2' => $col2);
  }

  public function data($config)
  {
      return [];
  }

  public function createTab($config)
  {

      $column = ['action', 'status', 'dateid', 'docno', 'client', 'amount', 'rem'];
      foreach ($column as $key => $value) {
          $$value = $key;
      }
      $tab = [
          $this->gridname => [
              'gridcolumns' => $column
          ]
      ];

      $stockbuttons = ['jumpmodule'];

      $obj = $this->tabClass->createtab($tab, $stockbuttons);
      $obj[0][$this->gridname]['columns'][$action]['style'] = 'width:5%;max-width:10%;';
      $obj[0][$this->gridname]['columns'][$status]['style'] = 'width:5%;max-width:10%;';
      $obj[0][$this->gridname]['columns'][$client]['label'] = 'Customer/Supplier';
      $obj[0][$this->gridname]['columns'][$client]['style'] = 'width:15%';
      $obj[0][$this->gridname]['columns'][$docno]['style'] = 'width:5%;max-width:10%;';
      $obj[0][$this->gridname]['columns'][$dateid]['style'] = 'width:5%;max-width:10%;';

      return $obj;
  }

  public function createtabbutton($config)
  {
      $tbuttons = [];
      $obj = [];
      return $obj;
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
    $fields = ['dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dateid.readonly', false);
    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'refresh.action', 'rr');
    $fields = [['db', 'cr']];
    $col3 = $this->fieldClass->create($fields);
    $fields = ['bal'];
    $col4 = $this->fieldClass->create($fields);
    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function execute(){
   return response()->json($this->config['return'],200);  
  } // end function

  public function loaddata($config)
  {
    $companyid = $config['params']['companyid'];
    $doc = $config['params']['dataparams']['doc'];
    $url = $this->checkdoc($doc, $companyid);
    $center = $config['params']['center'];
    $date = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $classid = $config['params']['dataparams']['classid'];
    $filter = " and date(apledger.dateid) >='$date'";
    $filter2 = " and date(head.dateid) >='$date'";
    if ($companyid == 47){//kstar
      $filter = " and date(apledger.dateid) ='$date' ";
      $filter2 = " and date(head.dateid) ='$date' ";
    }
    switch ($classid) {
      case 'posted':
        $qry = "select trno, line, doc, docno, date_format(dateid,'%m/%d/%y') as dateid, 'DBTODO' as tabtype, '$url' as url,
                  'module' as moduletype,
            FORMAT(db,2) as db, 
            FORMAT(cr,2) as cr,
            FORMAT(bal,2) as bal,
            ref, rem, status from (select `cntnum`.`doc` as `doc`,apledger.`docno`,`apledger`.`trno` as `trno`,
            `apledger`.`line` as `line`,
            `apledger`.`dateid` as `dateid`,`apledger`.`db` as `db`,`apledger`.`cr` as `cr`,apledger.bal,
            `apledger`.`clientid` as `clientid`,`apledger`.`ref` as `ref`,'' as agent,
            (`detail`.`rem`) as `rem`,((case when (`apledger`.`cr` > 0) then 1 else -(1) end) * `apledger`.`bal`) as `balance`,
            0 as `fbal`,`head`.`ourref` as `reference`,'POSTED' as `status` from ((((`apledger`
            left join `cntnum` on((`cntnum`.`trno` = `apledger`.`trno`))) left join `gldetail` as detail
            on(((`detail`.`trno` = `apledger`.`trno`) and (`detail`.`line` = `apledger`.`line`))))
            left join `glhead` as head on((`head`.`trno` = `cntnum`.`trno`)))) where cntnum.doc='RR' and apledger.bal>0 $filter
            and cntnum.center = '$center'
            ) as t  order by dateid desc,docno";
        break;
      case 'unposted':
        $qry = "select trno, line, doc, docno, date_format(dateid,'%m/%d/%y') as dateid, 'DBTODO' as tabtype, '$url' as url,
                  'module' as moduletype,
            FORMAT(db,2) as db, 
            FORMAT(cr,2) as cr,
            FORMAT(bal,2) as bal,
            ref, rem, status from (
            select `head`.`doc` as `doc`,head.docno,`head`.`trno` as `trno`,`detail`.`line` as `line`,`head`.`dateid` as `dateid`,
            0 as `db`,ifnull(sum(detail.ext),0) as `cr`,ifnull(sum(detail.ext),0) as `bal`,
            `client`.`clientid` as `clientid`,'' as `ref`,'' as `agent`,`detail`.`rem` as `rem`,
            sum(detail.ext) as `balance`,0 as `fbal`,'' as `reference`,'UNPOSTED' as `status`
            from `lahead` as head
            left join `lastock` as detail on `detail`.`trno` = `head`.`trno`
            left join `client` on `client`.`client` = `head`.`client`
            left join cntnum on cntnum.trno = head.trno 
            where cntnum.doc = 'RR' and cntnum.center = '$center' $filter2
            group by head.doc, head.docno, head.trno, detail.line, head.dateid, client.clientid, detail.rem) as t  order by dateid desc,docno";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'data' => $data];
  }

  public function checkdoc($doc,$companyid)
  {
    $url = '';
    switch (strtolower($doc)) {
      case 'rr':
        $folderloc = 'purchase';
        if($companyid == 47){ // kstar
          $folderloc = 'kitchenstar';
        }
        $url = "/module/" . $folderloc . "/";
        break;
    }
    return $url;
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
} //end class
