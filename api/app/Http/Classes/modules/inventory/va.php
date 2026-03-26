<?php

namespace App\Http\Classes\modules\inventory;

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
use App\Http\Classes\SBCPDF;

class va
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'VOYAGE REPORT';
  public $gridname = 'accounting';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $tablenum = 'transnum';
  public $head = 'rvoyage';
  public $hhead = 'hrvoyage';
  public $prefix = 'VA';
  public $tablelogs = 'transnum_log';
  public $tablelogs_del = 'del_transnum_log';
  public $htablelogs = 'htransnum_log';
  private $stockselect;

  private $fields = [
    'trno', 'docno', 'dateid', 'whid', 'yourref', 'ourref', 'notes',

    'port', 'arrival', 'departure', 'timeatsea', 'enginerpm',
    'avespeed', 'enginefueloil',
    'cylinderoil', 'enginelubeoil', 'hiexhaust', 'loexhaust',
    'exhaustgas', 'hicoolwater', 'locoolwater', 'lopress',
    'fwpress', 'airpress', 'airinletpress', 'coolerin',
    'coolerout', 'coolerfwin', 'coolerfwout', 'seawatertemp',
    'engroomtemp',

    'begcash', 'addcash', 'usagefeeamt', 'mooringamt', 'coastguardclearanceamt',
    'pilotageamt', 'lifebouyamt', 'bunkeringamt', 'sopamt', 'othersamt',
    'purchaseamt', 'crewsubsistenceamt', 'waterexpamt', 'localtranspoamt',
    'others2amt', 'reqcash',
    'usagefee', 'mooring', 'coastguardclearance',
    'pilotage', 'lifebouy', 'bunkering',
    'sop', 'others', 'purchase', 'crewsubsistence',
    'waterexp', 'localtranspo', 'others2', 'totalcash', 'totalexpenses', 'cashbalance'
  ];

  private $except = ['trno'];
  private $blnfields = [];
  private $acctg = [];
  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = true;
  private $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'view' => 2205,
      'edit' => 2206,
      'new' => 2207,
      'save' => 2208,
      'delete' => 2210,
      'print' => 2211,
      'post' => 2214,
      'unpost' => 2215,
      'lock' => 2212,
      'unlock' => 2213
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'liststatus', 'docno', 'clientname', 'dateid'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:5px;whiteSpace: normal;min-width:5px;';
    $cols[2]['label'] = 'Warehouse Name';

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
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno'];
      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }


    switch ($itemfilter) {
      case 'draft':
        $condition = ' and num.postdate is null ';
        break;
      case 'posted':
        $condition = ' and num.postdate is not null ';
        break;
    }

    $fields = "select head.trno as clientid, 
      head.docno as client, head.trno as trno, 
      head.docno as docno, date(head.dateid) as dateid,
      ifnull(wh.clientid, 0) as whid, ifnull(wh.client, '') as whcode, ifnull(wh.clientname, '') as clientname";

    $qry = " 
      " . $fields . ", 'DRAFT' as status
      FROM " . $this->head . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as wh on wh.clientid = head.whid
      where num.doc = '$doc' and date(head.dateid) BETWEEN '$date1' and '$date2'
      " . $condition . " " . $filtersearch . "
      UNION ALL
      " . $fields . ", 'POSTED' as status
      FROM " . $this->hhead . " as head
      left join " . $this->tablenum . " as num on num.trno = head.trno
      left join client as wh on wh.clientid = head.whid
      where num.doc = '$doc' and date(head.dateid) BETWEEN '$date1' and '$date2'
      " . $condition . " " . $filtersearch . "
      order by dateid desc, docno desc
    ";

    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.', 'qry' => $qry];
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
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    //TAB1
    $fields = [
      'port', 'arrival', 'departure', 'enginerpm', 'timeatsea', 'avespeed', 'enginefueloil',
      'cylinderoil', 'enginelubeoil', 'hiexhaust'
    ];
    $col1 = $this->fieldClass->create($fields);

    $fields = [
      'loexhaust', 'exhaustgas',
      'hicoolwater', 'locoolwater', 'lopress', 'fwpress', 'airpress', 'airinletpress'
    ];
    $col2 = $this->fieldClass->create($fields);

    $fields = [
      'coolerin', 'coolerout',
      'coolerfwin', 'coolerfwout', 'seawatertemp', 'engroomtemp',
    ];
    $col3 = $this->fieldClass->create($fields);



    $fields = [
      'begcashlabel', 'addcashlabel', 'usagefee', 'mooring', 'coastguardclearance', 'pilotage',
      'lifebouy', 'bunkering', 'sop', 'others'
    ];
    $col11 = $this->fieldClass->create($fields);

    $fields = [
      'begcash', 'addcash', 'usagefeeamt', 'mooringamt', 'coastguardclearanceamt', 'pilotageamt',
      'lifebouyamt', 'bunkeringamt', 'sopamt', 'othersamt'
    ];
    $col22 = $this->fieldClass->create($fields);

    data_set($col22, 'begcash.label', '');
    data_set($col22, 'addcash.label', '');
    data_set($col22, 'usagefeeamt.label', '');
    data_set($col22, 'mooringamt.label', '');
    data_set($col22, 'coastguardclearanceamt.label', '');
    data_set($col22, 'pilotageamt.label', '');
    data_set($col22, 'lifebouyamt.label', '');
    data_set($col22, 'bunkeringamt.label', '');
    data_set($col22, 'sopamt.label', '');
    data_set($col22, 'othersamt.label', '');

    $fields = [
      'purchase', 'crewsubsistence', 'waterexp', 'localtranspo', 'others2', 'totalcashlabel', 'totalexpenseslabel',
      'cashbalancelabel', 'reqcashlabel'
    ];
    $col33 = $this->fieldClass->create($fields);

    $fields = [
      'purchaseamt', 'crewsubsistenceamt', 'waterexpamt', 'localtranspoamt', 'others2amt', 'totalcash', 'totalexpenses',
      'cashbalance', 'reqcash'
    ];
    $col44 = $this->fieldClass->create($fields);
    data_set($col44, 'purchaseamt.label', '');
    data_set($col44, 'crewsubsistenceamt.label', '');
    data_set($col44, 'waterexpamt.label', '');
    data_set($col44, 'localtranspoamt.label', '');
    data_set($col44, 'others2amt.label', '');

    data_set($col44, 'totalcash.label', '');
    data_set($col44, 'totalexpenses.label', '');
    data_set($col44, 'cashbalance.label', '');
    data_set($col44, 'reqcash.label', '');


    $tab = [
      'multiinput1' => ['inputcolumn' => ['col1' => $col1, 'col2' => $col2, 'col3' => $col3], 'label' => 'ENGINE LOG'],
      'multiinput2' => ['inputcolumn' => ['col1' => $col11, 'col2' => $col22, 'col3' => $col33, 'col4' => $col44], 'label' => 'FUND LIQUIDATION']
    ];

    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['docno', 'dwhname', 'yourref', 'ourref'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dateid', 'notes'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'notes.type', 'ctextarea');
    data_set($col2, 'notes.maxlength', 500);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function createnewtransaction($docno, $params)
  {
    $data = [];
    $data[0]['trno'] = 0;
    $data[0]['clientid'] = 0;
    $data[0]['docno'] = $docno;
    $data[0]['client'] = $docno;
    $data[0]['dateid'] = $this->othersClass->getCurrentDate();
    $data[0]['yourref'] = '';
    $data[0]['ourref'] = '';
    $data[0]['notes'] = '';
    $data[0]['whid'] = 0;
    $data[0]['wh'] = '';
    $data[0]['whname'] = '';

    $data[0]['port'] = '';
    $data[0]['arrival'] = '';
    $data[0]['departure'] = '';
    $data[0]['enginerpm'] = '';
    $data[0]['timeatsea'] = '';
    $data[0]['avespeed'] = '';
    $data[0]['enginefueloil'] = '';
    $data[0]['cylinderoil'] = '';
    $data[0]['enginelubeoil'] = '';
    $data[0]['hiexhaust'] = '';
    $data[0]['loexhaust'] = '';
    $data[0]['exhaustgas'] = '';
    $data[0]['hicoolwater'] = '';
    $data[0]['locoolwater'] = '';
    $data[0]['lopress'] = '';
    $data[0]['fwpress'] = '';
    $data[0]['airpress'] = '';
    $data[0]['airinletpress'] = '';
    $data[0]['coolerin'] = '';
    $data[0]['coolerout'] = '';
    $data[0]['coolerfwin'] = '';
    $data[0]['coolerfwout'] = '';
    $data[0]['seawatertemp'] = '';
    $data[0]['engroomtemp'] = '';

    $data[0]['begcash'] = '0';
    $data[0]['addcash'] = '0';
    $data[0]['usagefeeamt'] = '0';
    $data[0]['mooringamt'] = '0';
    $data[0]['coastguardclearanceamt'] = '0';
    $data[0]['pilotageamt'] = '0';
    $data[0]['lifebouyamt'] = '0';
    $data[0]['bunkeringamt'] = '0';
    $data[0]['sopamt'] = '0';
    $data[0]['othersamt'] = '0';

    $data[0]['purchaseamt'] = '0';
    $data[0]['crewsubsistenceamt'] = '0';
    $data[0]['waterexpamt'] = '0';
    $data[0]['localtranspoamt'] = '0';
    $data[0]['others2amt'] = '0';
    $data[0]['reqcash'] = '0';

    $data[0]['usagefee'] = '';
    $data[0]['mooring'] = '';
    $data[0]['coastguardclearance'] = '';
    $data[0]['pilotage'] = '';
    $data[0]['lifebouy'] = '';
    $data[0]['bunkering'] = '';
    $data[0]['sop'] = '';
    $data[0]['others'] = '';

    $data[0]['purchase'] = '';
    $data[0]['crewsubsistence'] = '';
    $data[0]['waterexp'] = '';
    $data[0]['localtranspo'] = '';
    $data[0]['others2'] = '';

    $data[0]['totalcash'] = '0';
    $data[0]['totalexpenses'] = '0';
    $data[0]['cashbalance'] = '0';

    return $data;
  }


  public function loadheaddata($config)
  {
    $doc = $config['params']['doc'];
    $trno = $config['params']['trno'];
    $center = $config['params']['center'];
    if ($trno == 0) {
      $trno = $this->othersClass->readprofile($doc, $config);
      if ($trno == '') {
        $trno = $this->coreFunctions->datareader("select trno as value from " . $this->tablenum . " where doc=? and center=? order by trno desc limit 1", [$doc, $center]);
      }
      $config['params']['trno'] = $trno;
    } else {
      $this->othersClass->checkprofile($doc, $trno, $config);
    }
    $center = $config['params']['center'];
    $head = [];
    $islocked = $this->othersClass->islocked($config);
    $isposted = $this->othersClass->isposted($config);

    $fields = "
      head.trno as clientid, head.docno as client, 
      ifnull(wh.clientid, 0) as whid, ifnull(wh.client, '') as wh, ifnull(wh.clientname, '') as whname,
      head.trno, head.docno, head.dateid, head.whid, head.yourref, head.ourref, 
      head.notes, head.port, head.arrival, head.departure, head.enginerpm, 
      head.timeatsea, head.avespeed, head.enginefueloil, head.cylinderoil, 
      head.enginelubeoil, head.hiexhaust, head.loexhaust, head.exhaustgas, 
      head.hicoolwater, head.locoolwater, head.lopress, head.fwpress, 
      head.airpress, head.airinletpress, head.coolerin, head.coolerout, 
      head.coolerfwin, head.coolerfwout, head.seawatertemp, head.engroomtemp, 
      head.begcash, head.addcash, head.usagefeeamt, head.mooringamt, 
      head.coastguardclearanceamt, head.pilotageamt, head.lifebouyamt, 
      head.bunkeringamt, head.sopamt, head.othersamt, head.purchaseamt, 
      head.crewsubsistenceamt, head.waterexpamt, head.localtranspoamt, 
      head.others2amt, head.reqcash,
      head.usagefee, head.mooring, head.coastguardclearance,
      head.pilotage, head.lifebouy,
      head.bunkering, head.sop, head.others,
      head.purchase, head.crewsubsistence, head.waterexp,
      head.localtranspo, head.others2, (head.begcash + head.addcash) as totalcash, 
      (head.usagefeeamt + head.mooringamt + head.coastguardclearanceamt + head.pilotageamt + head.lifebouyamt + 
      head.bunkeringamt + head.sopamt + head.othersamt + head.purchaseamt + head.crewsubsistenceamt + head.waterexpamt +
      head.localtranspoamt + head.others2amt) as totalexpenses, 
      ((head.begcash + head.addcash)-(head.usagefeeamt + head.mooringamt + head.coastguardclearanceamt + head.pilotageamt + head.lifebouyamt + 
      head.bunkeringamt + head.sopamt + head.othersamt + head.purchaseamt + head.crewsubsistenceamt + head.waterexpamt +
      head.localtranspoamt + head.others2amt)) as cashbalance
    ";

    $qry = "select " . $fields . " 
    FROM " . $this->head . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno
    left join client as wh on wh.clientid = head.whid
    where head.trno = '$trno' and num.doc = '$doc'
    UNION ALL
    select " . $fields . "
    FROM " . $this->hhead . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno
    left join client as wh on wh.clientid = head.whid
    where head.trno = '$trno' and num.doc = '$doc'";

    $head = $this->coreFunctions->opentable($qry);
    if (!empty($head)) {
      $viewdate = $this->othersClass->getCurrentTimeStamp();
      $viewby = $config['params']['user'];
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $this->coreFunctions->sbcupdate($this->head, ['viewdate' => $viewdate, 'viewby' => $viewby], ['trno' => $trno]);
      return  ['head' => $head, 'griddata' => ['inventory' => []], 'islocked' => $islocked, 'isposted' => $isposted, 'isnew' => false, 'status' => true, 'msg' => $msg];
    } else {
      $head[0]['trno'] = 0;
      $head[0]['docno'] = '';
      return ['status' => false, 'isnew' => true, 'head' => $head, 'griddata' => ['inventory' => []], 'msg' => 'Data Head Fetched Failed'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $data = [];
    if ($isupdate) {
      unset($head['docno']);
    }
    $clientid = 0;
    $msg  = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }

    if ($isupdate) {
      $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['editby'] = $config['params']['user'];
      $data['whid'] = $this->coreFunctions->datareader("select clientid as value from client where client = '" . $head['wh'] . "'");
      $this->coreFunctions->sbcupdate($this->head, $data, ['trno' => $head['trno']]);
      $trno = $head['trno'];
    } else {
      $data['whid'] = $this->coreFunctions->datareader("select clientid as value from client where client = '" . $head['wh'] . "'");
      $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
      $data['createby'] = $config['params']['user'];

      $trno = $this->coreFunctions->insertGetId($this->head, $data);
      $this->logger->sbcwritelog($head['trno'], $config, 'CREATE', $head['docno'] . ' - ' . $head['wh'] . ' - ' . $head['whname']);
    }
    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $trno, 'da' => $data];
  } // end function

  public function getlastclient($pref)
  {
    $length = strlen($pref);
    $return = '';
    if ($length == 0) {
      $return = $this->coreFunctions->datareader('select empcode as value from app  order by empcode desc limit 1');
    } else {
      $return = $this->coreFunctions->datareader('select empcode as value from app where  left(empcode,?)=? order by empcode desc limit 1', [$length, $pref]);
    }
    return $return;
  }


  public function deletetrans($config)
  {
    $trno = $config['params']['trno'];
    $doc = $config['params']['doc'];
    $docno = $this->coreFunctions->getfieldvalue($this->head, 'docno', 'trno=?', [$trno]);

    $qry = "select trno as value from " . $this->head . " where trno < ? order by trno desc limit 1 ";
    $trno2 = $this->coreFunctions->datareader($qry, [$trno]);

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where trno=?', 'delete', [$trno]);
    $this->logger->sbcdel_log($trno, $config, $docno);
    return ['trno' => $trno2, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function

  public function posttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);
    $msg = '';
    $qry = "insert into " . $this->hhead . " (trno, docno, dateid, whid, yourref, ourref,
      notes, port, arrival, departure, enginerpm, timeatsea, avespeed, enginefueloil,
      cylinderoil, enginelubeoil, hiexhaust, loexhaust, exhaustgas, hicoolwater, 
      locoolwater, lopress, fwpress, airpress, airinletpress, coolerin, coolerout, 
      coolerfwin, coolerfwout, seawatertemp, engroomtemp, begcash, addcash, 
      usagefeeamt, mooringamt, coastguardclearanceamt, pilotageamt, lifebouyamt, 
      bunkeringamt, sopamt, othersamt, purchaseamt, crewsubsistenceamt, waterexpamt, 
      localtranspoamt, others2amt, reqcash,
      usagefee, mooring, coastguardclearance,
      pilotage, lifebouy, bunkering, sop, others,
      purchase, crewsubsistence, waterexp,
      localtranspo, others2, viewdate, viewby)
      select trno, docno, dateid, whid, yourref, ourref, notes, port, arrival, 
      departure, enginerpm, timeatsea, avespeed, enginefueloil, cylinderoil, 
      enginelubeoil, hiexhaust, loexhaust, exhaustgas, hicoolwater, locoolwater, 
      lopress, fwpress, airpress, airinletpress, coolerin, coolerout, coolerfwin, 
      coolerfwout, seawatertemp, engroomtemp, begcash, addcash, usagefeeamt, 
      mooringamt, coastguardclearanceamt, pilotageamt, lifebouyamt, bunkeringamt, 
      sopamt, othersamt, purchaseamt, crewsubsistenceamt, waterexpamt, 
      localtranspoamt, others2amt, reqcash,
      usagefee, mooring, coastguardclearance,
      pilotage, lifebouy, bunkering, sop, others,
      purchase, crewsubsistence, waterexp,
      localtranspo, others2, viewdate, viewby
      from " . $this->head . " where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);
    if ($result === 1) {
    } else {
      $msg = "Posting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $date = $this->othersClass->getCurrentTimeStamp();
      $data = ['postdate' => $date, 'postedby' => $user];
      $this->coreFunctions->sbcupdate($config['docmodule']->tablenum, $data, ['trno' => $trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'POSTED', $docno);
      $this->othersClass->sbctransferlog($trno, $config, $this->htablelogs);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully posted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function unposttrans($config)
  {
    $trno = $config['params']['trno'];
    $user = $config['params']['user'];
    $doc = $config['params']['doc'];
    $msg = '';

    $docno = $this->coreFunctions->datareader('select docno as value from ' . $config['docmodule']->tablenum . ' where trno=?', [$trno]);

    $qry = "insert into " . $this->head . " (trno, docno, dateid, whid, yourref, ourref,
      notes, port, arrival, departure, enginerpm, timeatsea, avespeed, enginefueloil,
      cylinderoil, enginelubeoil, hiexhaust, loexhaust, exhaustgas, hicoolwater, 
      locoolwater, lopress, fwpress, airpress, airinletpress, coolerin, coolerout, 
      coolerfwin, coolerfwout, seawatertemp, engroomtemp, begcash, addcash, 
      usagefeeamt, mooringamt, coastguardclearanceamt, pilotageamt, lifebouyamt, 
      bunkeringamt, sopamt, othersamt, purchaseamt, crewsubsistenceamt, waterexpamt, 
      localtranspoamt, others2amt, reqcash,
      usagefee, mooring, coastguardclearance,
      pilotage, lifebouy, bunkering, sop, others,
      purchase, crewsubsistence, waterexp,
      localtranspo, others2, viewdate, viewby)
      select trno, docno, dateid, whid, yourref, ourref, notes, port, arrival, 
      departure, enginerpm, timeatsea, avespeed, enginefueloil, cylinderoil, 
      enginelubeoil, hiexhaust, loexhaust, exhaustgas, hicoolwater, locoolwater, 
      lopress, fwpress, airpress, airinletpress, coolerin, coolerout, coolerfwin, 
      coolerfwout, seawatertemp, engroomtemp, begcash, addcash, usagefeeamt, 
      mooringamt, coastguardclearanceamt, pilotageamt, lifebouyamt, bunkeringamt, 
      sopamt, othersamt, purchaseamt, crewsubsistenceamt, waterexpamt, 
      localtranspoamt, others2amt, reqcash,
      usagefee, mooring, coastguardclearance,
      pilotage, lifebouy, bunkering, sop, others,
      purchase, crewsubsistence, waterexp,
      localtranspo, others2, viewdate, viewby
      from " . $this->hhead . " where trno=?";
    $result = $this->coreFunctions->execqry($qry, 'insert', [$trno]);

    if ($result === 1) {
    } else {
      $msg = "Unposting failed. Kindly check the head data.";
    }

    if ($msg === '') {
      $docno = $this->coreFunctions->getfieldvalue($config['docmodule']->tablenum, 'docno', 'trno=?', [$trno]);
      $this->coreFunctions->execqry("update " . $config['docmodule']->tablenum . " set postdate=null, postedby='' where trno=?", 'update', [$trno]);
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->hhead . " where trno=?", "delete", [$trno]);
      $this->logger->sbcwritelog($trno, $config, 'UNPOSTED', $docno);
      return ['trno' => $trno, 'status' => true, 'msg' => 'Successfully unposted.'];
    } else {
      $this->coreFunctions->execqry("delete from " . $config['docmodule']->head . " where trno=?", "delete", [$trno]);
      return ['trno' => $trno, 'status' => false, 'msg' => $msg];
    }
  } //end function

  public function reportsetup($config)
  {
    $txtfield = $this->createreportfilter();
    $txtdata = $this->reportparamsdata($config);
    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';
    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function createreportfilter()
  {
    $fields = [
      'prepared',
      'approved',
      'received',
      'print'
    ];

    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function reportparamsdata($config)
  {
    return $this->coreFunctions->opentable("
    select
      'default' as print,
      '' as prepared,
      '' as approved,
      '' as received
  ");
  }

  public function generateResult($config)
  {
    $doc      = $config['params']['doc'];
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $trno = $config['params']['dataid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];


    $fields = "
      head.trno as clientid, head.docno as client, 
      ifnull(wh.clientid, 0) as whid, ifnull(wh.client, '') as wh, ifnull(wh.clientname, '') as whname,
      head.trno, concat(left(head.docno,2),right(head.docno,4)) as docno, head.dateid, head.whid, head.yourref, head.ourref, 
      head.notes, head.port, head.arrival, head.departure, head.enginerpm, 
      head.timeatsea, head.avespeed, head.enginefueloil, head.cylinderoil, 
      head.enginelubeoil, head.hiexhaust, head.loexhaust, head.exhaustgas, 
      head.hicoolwater, head.locoolwater, head.lopress, head.fwpress, 
      head.airpress, head.airinletpress, head.coolerin, head.coolerout, 
      head.coolerfwin, head.coolerfwout, head.seawatertemp, head.engroomtemp, 
      head.begcash, head.addcash, head.usagefeeamt, head.mooringamt, 
      head.coastguardclearanceamt, head.pilotageamt, head.lifebouyamt, 
      head.bunkeringamt, head.sopamt, head.othersamt, head.purchaseamt, 
      head.crewsubsistenceamt, head.waterexpamt, head.localtranspoamt, 
      head.others2amt, head.reqcash,
      head.usagefee, head.mooring, head.coastguardclearance,
      head.pilotage, head.lifebouy, head.bunkering, head.sop, head.others,
      head.purchase, head.crewsubsistence, head.waterexp,
      head.localtranspo, head.others2, (head.begcash + head.addcash) as totalcash, 
      (head.usagefeeamt + head.mooringamt + head.coastguardclearanceamt + head.pilotageamt + head.lifebouyamt + 
      head.bunkeringamt + head.sopamt + head.othersamt + head.purchaseamt + head.crewsubsistenceamt + head.waterexpamt +
      head.localtranspoamt + head.others2amt) as totalexpenses, 
      ((head.begcash + head.addcash)-(head.usagefeeamt + head.mooringamt + head.coastguardclearanceamt + head.pilotageamt + head.lifebouyamt + 
      head.bunkeringamt + head.sopamt + head.othersamt + head.purchaseamt + head.crewsubsistenceamt + head.waterexpamt +
      head.localtranspoamt + head.others2amt)) as cashbalance,head.totalcash, head.totalexpenses,head.cashbalance";

    $query = "select " . $fields . " 
    FROM " . $this->head . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno
    left join client as wh on wh.clientid = head.whid
    where head.trno = '$trno' and num.doc = '$doc'
    UNION ALL
    select " . $fields . " 
    FROM " . $this->hhead . " as head
    left join " . $this->tablenum . " as num on num.trno = head.trno
    left join client as wh on wh.clientid = head.whid
    where head.trno = '$trno' and num.doc = '$doc'";

    return $this->coreFunctions->opentable($query);
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function reportplotting($config)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = 'Century Gothic';
    $font_size = '10';
    $padding = '';
    $margin = '';

    $data     = $this->generateResult($config);
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $prepared   = $config['params']['dataparams']['prepared'];
    $approved   = $config['params']['dataparams']['approved'];
    $received   = $config['params']['dataparams']['received'];

    $str = '';
    $count = 55;
    $page = 54;
    $str .= $this->reporter->beginreport();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('VOYAGE REPORT ', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Docno:', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->docno, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date:', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->dateid, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Wh/Vessel:', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->whname, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Yourref:', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->yourref, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Ourref:', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->ourref, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Notes:', '50', null, false, $border, '', 'LT', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col(nl2br($data[0]->notes), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col('', '300', null, false, $border, '', 'LT', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Port', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->port, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('Cool Water Highest/Cyl Nr.', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->hicoolwater, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Time Arrival', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->arrival, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('Cool Water Lowest/Cyl Nr.', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->locoolwater, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Time Departure', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->departure, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('L.O. Press.', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->lopress, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Main Engine RPM.', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->enginerpm, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('Cool F.W. Press', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->fwpress, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Time At Sea', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->timeatsea, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('Scay. Air Press', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->airpress, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Average Speed', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->avespeed, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('Scay. Air Inlet Temp', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->airinletpress, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Main Engine Fuel Oil Consumption', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->enginefueloil, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('LO. Cooler In', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->coolerin, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Cylinder Oil Consumption', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->cylinderoil, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('LO. Cooler Out', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->coolerout, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Main Engine Lube Oil Sump Tank Sounding', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->enginelubeoil, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('F.W. Cooler F.W. In', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->coolerfwin, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Highest Exhaust Temp/Cyl Nr.', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->hiexhaust, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('F.W. Cooler F.W. Out', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->coolerfwout, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Lowest Exhaust Temp/Cyl Nr.', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->loexhaust, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('Sea Water Temp', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->seawatertemp, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('T/C Exhaust Gas Outlet Temperature', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->exhaustgas, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');

    $str .= $this->reporter->col('Eng Room Temp', '50', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->engroomtemp, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('FUND LIQUIDATION', '250', null, false, $border, '', 'L', $font, '14', 'B', '', '1px');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->col('Cash Beginning', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->begcash, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Add Cash Received', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->addcash, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $cash_received = $data[0]->begcash + $data[0]->addcash;
    $str .= $this->reporter->col(number_format($cash_received, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Port Charges', '150', null, false, $border, 'B', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Usage Fee/PPA Clearance', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->usagefee, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->usagefeeamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Mooring/Unmooring', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->mooring, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->mooringamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Coast Guard Clearance', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->coastguardclearance, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->coastguardclearanceamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Pliotage', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->pilotage, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->pilotageamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Life Bouy/Marker', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->lifebouy, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->lifebouyamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Bunkering Permit', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->bunkering, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->bunkeringamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SOP', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->sop, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->sopamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Others', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->others, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->othersamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL PORT CHARGES', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $totalport_charges =
      $data[0]->usagefeeamt +
      $data[0]->mooringamt +
      $data[0]->coastguardclearanceamt +
      $data[0]->pilotageamt +
      $data[0]->lifebouyamt +
      $data[0]->bunkeringamt +
      $data[0]->sopamt +
      $data[0]->othersamt;
    $str .= $this->reporter->col(number_format($totalport_charges, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('&nbsp;', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('&nbsp;', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Purchases', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->purchase, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->purchaseamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Crew Subsistence', '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->crewsubsistence, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->crewsubsistenceamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Water Expense', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->waterexp, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->waterexpamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Local Transportation', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->localtranspo, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->localtranspoamt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Others', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col($data[0]->others2, '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->others2amt, 2), '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '8px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL EXPENSE', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $total_expense =
      $data[0]->purchaseamt +
      $data[0]->crewsubsistenceamt +
      $data[0]->waterexpamt +
      $data[0]->localtranspoamt +
      $data[0]->others2amt;
    $str .= $this->reporter->col(number_format($data[0]->totalexpenses, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CASH BALANCE', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $cash_balance = (($totalport_charges + $cash_received) - $total_expense);
    $str .= $this->reporter->col(number_format($data[0]->cashbalance, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Requested Cash', '150', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, '11', '', '', '1px');
    $str .= $this->reporter->col(number_format($data[0]->reqcash, 2), '100', null, false, $border, '', 'L', $font, '11', 'B', '', '1px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'L', $font, '12', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($prepared, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col($approved, '266', null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
} //end class
