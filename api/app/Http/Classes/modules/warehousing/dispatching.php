<?php

namespace App\Http\Classes\modules\warehousing;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\builder\helpClass;

class dispatching
{
  public $modulename = 'DISPATCHING';
  public $gridname = 'inventory';

  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $stock = 'lastock';
  public $tablelogs = 'table_log';

  private $fields = ['checkerid', 'checkerlocid'];

  public $transdoc = "'SD', 'SE', 'SF', 'SH'";

  private $btnClass;
  private $fieldClass;
  private $tabClass;

  private $companysetup;
  private $coreFunctions;
  private $othersClass;

  public $showfilteroption = true;
  public $showfilter = true;
  public $showcreatebtn = false;

  public $showfilterlabel = [
    ['val' => 'draft', 'label' => 'Pending', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Dispatched', 'color' => 'primary']
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
    $this->helpClass = new helpClass;
  }

  public function getAttrib()
  {
    $attrib = array(
      'load' => 2035,
      'view' => 2035
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $lblstatus = 1;
    $listdocument = 2;
    $clientname = 3;
    $scheddate = 4;
    $checkerdate = 5;
    $truck = 6;
    $address = 7;

    $getcols = ['action', 'lblstatus', 'listdocument', 'clientname', 'scheddate', 'checkerdate', 'truck', 'address'];
    $stockbuttons = ['view', 'showstockitems'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';
    $cols[$lblstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$scheddate]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; max-width:80px;';
    $cols[$checkerdate]['style'] = 'width:80px;whiteSpace: normal;min-width:80px; max-width:80px;';
    $cols[$listdocument]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$address]['style'] = 'width:300px;whiteSpace: normal;min-width:300px; max-width:300px;';

    $cols[$lblstatus]['align'] = 'left';
    $cols[$clientname]['label'] = 'Customer Name';
    return $cols;
  }

  public function loaddoclisting($config)
  {
    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];

    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));
    $option = $config['params']['itemfilter'];

    $qry = $this->selectqry($config, $option, $date1, $date2);
    $qry .= " order by stat, scheddate desc";

    $data = $this->coreFunctions->opentable($qry, [$center, $center]);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }


  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createHeadField($config)
  {
    $fields = ['client', 'dateid'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.class', 'docno sbccsreadonly');
    data_set($col1, 'client.label', 'Document No.');
    data_set($col1, 'dateid.label', 'Transaction Date');

    $fields = ['checkerloc', 'clientname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'checkerloc.type', 'input');
    data_set($col2, 'checkerloc.class', 'docno sbccsreadonly');

    data_set($col2, 'clientname.label', 'Customer Name');
    data_set($col2, 'clientname.type', 'input');

    $fields = ['forloading'];
    $col3 = $this->fieldClass->create($fields);

    $fields = ['postwhclr', 'rescedule'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'postwhclr.label', 'IN-TRANSIT');
    data_set($col4, 'postwhclr.access', 'view');
    data_set($col4, 'postwhclr.confirmlabel', 'Proceed for logistics?');
    data_set($col4, 'postwhclr.icon', 'local_shipping');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }


  public function createTab($config)
  {
    $tab = [$this->gridname => [
      'gridcolumns' => ['boxno',  'scanby', 'scandate', 'action']
    ]];

    $stockbuttons = ['showboxdetails'];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;

    $obj[0][$this->gridname]['columns'][2]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][3]['label'] = 'View items';

    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['scanbox'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  private function resetdata($client = '')
  {
    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['checker'] = '';
    $data[0]['checkerid'] = 0;
    $data[0]['checkerloc'] = '';
    $data[0]['checkerlocid'] = 0;
    $data[0]['dateid'] = null;

    return $data;
  }

  private function selectqry($config, $option = '', $date1 = '', $date2 = '')
  {
    $optionfilter = '';
    if ($option != '') {
      switch ($option) {
        case 'posted':
          $optionfilter = " and ci.dispatchdate is not null and num.status<>'VOID'";
          break;

        default:
          $optionfilter = " and ci.dispatchdate is null and num.status<>'VOID'";
          break;
      }
    }

    $datefilter = "";
    if ($date1 != "" && $date2 != "") {
      $datefilter = " and date(ci.scheddate) between '$date1' and '$date2'";
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'client.clientname'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "select head.trno, head.trno as clientid, head.docno, head.docno as client,head.clientname,left(head.dateid,10) as dateid,
        ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, num.crtldate, num.status as stat, 
        date(ci.scheddate) as scheddate, date(ci.checkerdate) as checkerdate, 
        tr.clientname as truck, head.address, ci.dispatchdate
        from " . $this->head . " as head 
        left join " . $this->tablenum . " as num on num.trno=head.trno
        left join cntnuminfo as ci on ci.trno=head.trno 
        left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        left join client as tr on tr.clientid=ci.truckid
        where head.lockdate is not null and head.doc in (" . $this->transdoc  . ")
        and num.center = ? and num.crtldate is not null and ci.scheddate is not null and ci.checkerdate is not null " . $filtersearch . " "
      . $optionfilter
      . $datefilter;
    return $qry;
  }

  public function loadheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $center = $config['params']['center'];
    $userid = $config['params']['adminid'];

    $qry = $this->selectqry($config);
    $qry .= " and head.trno=?";
    $head = $this->coreFunctions->opentable($qry, [$center, $trno, $center, $trno]);
    if (!empty($head)) {
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      $loaddate = $this->coreFunctions->getfieldvalue("cntnuminfo", "forloaddate", "trno=?", [$trno]);
      $hideforloading = false;
      if ($loaddate) {
        $hideforloading = true;
      }

      $dispatchdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "dispatchdate", "trno=?", [$trno]);
      if ($dispatchdate) {
        $hidetabbtn = ['btnscanbox' => true];
        $hideobj = ['forloading' => true, 'postwhclr' => true, 'rescedule' => false];
      } else {
        $hidetabbtn = ['btnscanbox' => false];
        $hideobj = ['forloading' => $hideforloading, 'postwhclr' => false, 'rescedule' => true];
      }

      $stock = $this->openstock($config);
      return  ['head' => $head, 'griddata' => ['inventory' => $stock], 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj, 'hidetabbtn' => $hidetabbtn];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'griddata' => [], 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function openstock($config)
  {
    $trno = $config['params']['clientid'];
    $qry = "select distinct boxno, trno, scandate, scanby, '' as bgcolor from boxinginfo where trno=? order by boxno";
    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function stockstatusposted($config)
  {
    $clientid = $config['params']['clientid'];
    $action = $config['params']['action'];

    switch ($action) {
      case 'forloading':
        $checkerdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerdate", "trno=?", [$clientid]);
        if ($checkerdate == null) {
          return ['status' => false, 'msg' => 'Cannot proceed for loading. Not yet check by the warehouse checker'];
        }

        $loaddate = $this->coreFunctions->getfieldvalue("cntnuminfo", "forloaddate", "trno=?", [$clientid]);
        if ($loaddate) {
          return ['status' => false, 'msg' => 'Already tagged for loading.'];
        } else {
          $current_time = $this->othersClass->getCurrentTimeStamp();
          $this->coreFunctions->execqry("update cntnuminfo as ci left join cntnum as c on c.trno=ci.trno set ci.status='FOR LOADING', c.status='FOR LOADING', ci.forloaddate='" . $current_time . "', ci.forloadby='" . $config['params']['user'] . "' where c.trno=?", 'update', [$clientid]);
          return ['status' => true, 'msg' => 'Successfully updated.'];
        }
        break;

      case 'scanbox':
        $loaddate = $this->coreFunctions->getfieldvalue("cntnuminfo", "forloaddate", "trno=?", [$clientid]);
        if (!$loaddate) {
          return ['status' => false, 'msg' => 'Not yet tagged as For Loading.'];
        }

        $msg = 'Box/SKU scanned successfully.';
        $status = false;
        $str = explode("-", $config['params']['barcode']);

        if (count($str) == 2) {
          $docno = $this->coreFunctions->getfieldvalue("lahead", "docno", "trno=?", [$clientid]);
          if ($docno == $str[0]) {
            $exist = $this->coreFunctions->opentable("select boxno, scandate from boxinginfo where trno=? and boxno=? limit 1", [$clientid, $str[1]]);
            if (!empty($exist)) {
              if ($exist[0]->scandate == null) {
                $current_time = $this->othersClass->getCurrentTimeStamp();
                $this->coreFunctions->sbcupdate("boxinginfo", ['scandate' => $current_time, 'scanby' => $config['params']['user']], ['trno' => $clientid, 'boxno' => $str[1]]);
              } else {
                $msg = "Box/SKU " . $str[1] . " already scanned.";
                $status = false;
              }
            } else {
              $msg = "Box/SKU " . $str[1] . " doesn`t exist.";
              $status = false;
            }
          } else {
            $msg = "Scan Box/SKU doesn't exist in this document # " . $docno;
            $status = false;
          }
        } else {
          $itemid = $this->coreFunctions->getfieldvalue("item", "itemid", "barcode=?", [$config['params']['barcode']]);
          if ($itemid) {
            $exist = $this->coreFunctions->opentable("select line from boxinginfo where trno=? and itemid=? and scandate is null limit 1", [$clientid, $itemid]);
            if (!empty($exist)) {
              $current_time = $this->othersClass->getCurrentTimeStamp();
              $this->coreFunctions->sbcupdate("boxinginfo", ['scandate' => $current_time, 'scanby' => $config['params']['user']], ['trno' => $clientid, 'line' => $exist[0]->line]);
            } else {
              $msg = "Box/SKU " . $config['params']['barcode'] . " doesn`t exist.";
              $status = false;
            }
          }
        }
        //kapag false return status lang nag-rereload
        $stock = $this->openstock($config);
        return ['status' => $status, 'msg' => $msg, 'action' => 'reload', 'griddata' => $stock];
        break;

      case 'post':

        $crtldate = $this->coreFunctions->getfieldvalue("cntnum", "crtldate", "trno=?", [$clientid]);
        if ($crtldate == null) {
          return ['status' => false, 'msg' => 'Cannot post; not yet checked by the Inventory Controller.'];
        }

        $pendingpicker = $this->coreFunctions->datareader("select trno as value from lastock where pickerend is null and trno=?", [$clientid]);
        if ($pendingpicker) {
          return ['status' => false, 'msg' => 'Cannot post; some items are not yet picked.'];
        }

        $truckid = $this->coreFunctions->getfieldvalue("cntnuminfo", "truckid", "trno=?", [$clientid]);
        if ($truckid == 0) {
          return ['status' => false, 'msg' => 'Cannot post; please specify a valid truck.'];
        }

        $scheddate = $this->coreFunctions->getfieldvalue("cntnuminfo", "scheddate", "trno=?", [$clientid]);
        if ($scheddate == null) {
          return ['status' => false, 'msg' => 'Cannot post; please specify the schedule date.'];
        }

        $pendingscan = $this->coreFunctions->datareader("select trno as value from boxinginfo where scandate is null and trno=?", [$clientid]);
        if ($pendingscan) {
          return ['status' => false, 'msg' => 'Cannot post; some boxes are not yet scanned.'];
        }

        $checkerdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerdate", "trno=?", [$clientid]);
        if ($checkerdate == null) {
          return ['status' => false, 'msg' => 'Cannot post; not yet checked by the Warehouse Checker.'];
        }

        $this->coreFunctions->execqry("update cntnuminfo as ci left join cntnum as c on c.trno=ci.trno set ci.status='IN-TRANSIT', c.status='IN-TRANSIT', ci.dispatchdate=now(), ci.dispatchby='" . $config['params']['user'] . "' where c.trno=?", 'update', [$clientid]);

        $this->logger->sbcwritelog($clientid, $config, 'WAREHOUSING', 'DISPATCHED');

        return ['status' => true, 'msg' => 'Successfully updated.'];
        break;

      case 'rescedule':
        $dispatchdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "dispatchdate", "trno=?", [$clientid]);
        if ($dispatchdate == null) {
          return ['status' => false, 'msg' => 'Only Intransit status is allowed to reschedule.'];
        }

        $data = $this->coreFunctions->opentable("select trno, dispatchdate, dispatchby, truckid, scheddate from cntnuminfo where trno=?", [$clientid]);

        $update = $this->coreFunctions->execqry("update cntnuminfo as ci left join cntnum as c on c.trno=ci.trno set ci.status='RESCHEDULE', c.status='RESCHEDULE', ci.dispatchdate=null, ci.dispatchby='', scheddate = null  where c.trno=?", 'update', [$clientid]);
        if ($update) {
          $rescedule = [
            'trno' => $data[0]->trno,
            'dispatchdate' => $data[0]->dispatchdate,
            'dispatchby' => $data[0]->dispatchby,
            'scheddate' => $data[0]->scheddate,
            'truckid' => $data[0]->truckid,
            'dateid' => $this->othersClass->getCurrentTimeStamp(),
            'userid' => $config['params']['user']
          ];
          $this->coreFunctions->sbcinsert("reschedule", $rescedule);

          $this->logger->sbcwritelog($clientid, $config, 'WAREHOUSING', 'RESCHEDULE');
        }
        return ['status' => true, 'msg' => 'Successfully reschedule.'];
        break;

      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $config['params']['action'] . ')'];
        break;
    }
  }
}
