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

class logistics
{

  public $modulename = 'LOGISTICS';
  public $gridname = 'inventory';

  public $tablenum = 'cntnum';
  public $head = 'lahead';
  public $stock = 'lastock';
  public $detail = 'ladetail';

  public $hhead = 'glhead';
  public $hstock = 'glstock';
  public $hdetail = 'gldetail';

  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $tablelogs_del = 'del_table_log';

  public $transdoc = "'SD', 'SE', 'SF', 'SH'";

  private $fields = ['truckid', 'scheddate', 'receivedate', 'receiveby', 'courier'];
  private $acctg = [];

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
    ['val' => 'draft', 'label' => 'Draft', 'color' => 'primary'],
    ['val' => 'posted', 'label' => 'Intransit', 'color' => 'primary'],
    ['val' => 'complete', 'label' => 'Complete', 'color' => 'primary']
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
      'load' => 2037,
      'view' => 2037,
      'edit' => 2038,
      'save' => 2038,
      'post' => 2039,
      'print' => 2451
    );
    return $attrib;
  }

  public function paramsdatalisting($config)
  {
    $fields = [['waybill', 'waybillamt']];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, "waybill.lookupclass", "lookupwaybill");
    data_set($col1, "waybill.action", "lookupwaybill");

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, "print.label", "PRINT WAYBILL");

    $data = $this->coreFunctions->opentable("select '' as waybill, '0.00' as waybillamt");
    return ['status' => true, 'data' => $data[0], 'txtfield' => ['col1' => $col1, 'col2' => $col2]];
  }

  public function createdoclisting($config)
  {
    $action = 0;
    $lblstatus = 1;
    $listdocument = 2;
    $clientname = 3;
    $listdate = 4;
    $ref = 5;
    $scheddate = 6;
    $truck = 7;
    $plateno = 8;
    $courier = 9;
    $receivedate = 10;
    $receiveby = 11;
    $transtype = 12;
    $deliverytypename = 13;
    $boxcount = 14;
    $waybill = 14;

    $getcols = [
      'action', 'lblstatus', 'listdocument', 'clientname', 'listdate', 'ref', 'scheddate', 'truck',
      'plateno', 'courier', 'receivedate', 'receiveby', 'transtype', 'deliverytypename', 'boxcount', 'waybill'
    ];

    $stockbuttons = ['view', 'showboxdetails', 'showstockitems'];

    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[$action]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$lblstatus]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$clientname]['style'] = 'width:150px;whiteSpace: normal;min-width:150px; max-width:150px;';
    $cols[$ref]['style'] = 'width:100px;whiteSpace: normal;min-width:100px; max-width:100px;';

    $cols[$lblstatus]['align'] = 'text-left';

    $cols[$clientname]['label'] = 'Customer Name';

    $cols[$ref]['label'] = 'Ref No.';
    $cols[$ref]['type'] = 'label';

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $center = $config['params']['center'];
    $option = $config['params']['itemfilter'];
    $date1 = date('Y-m-d', strtotime($config['params']['date1']));
    $date2 = date('Y-m-d', strtotime($config['params']['date2']));

    $condition = '';
    $searchfilter = $config['params']['search'];

    $limit = "limit 100";

    $qry = $this->selectqry($config);

    $defaultsort = " ci.courier, ci.scheddate, num.status";
    switch ($option) {
      case 'draft':
        $qry .= " and ci.dispatchdate is null and ci.logisticdate is null and num.status<>'VOID' and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? $condition";
        $defaultsort = " head.dateid asc, head.clientname";
        break;
      case 'posted':
        $qry .= " and ci.dispatchdate is not null and ci.logisticdate is null and num.status<>'VOID' and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? $condition";
        $defaultsort = " head.dateid desc, head.clientname";
        break;
      case 'complete':
        $qry .= " and CONVERT(head.dateid,DATE)>=? and CONVERT(head.dateid,DATE)<=? $condition";
        $defaultsort = " head.dateid desc, head.clientname";
        break;
    }

    $qry .= " order by $defaultsort $limit";

    $data = $this->coreFunctions->opentable($qry, [$center, $date1, $date2]);
    $data = $this->othersClass->updatetranstype($data);
    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }


  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'edit',
      'print',
      'save',
      'post',
      'unpost',
      'cancel',
      'backlisting',
      'toggleup',
      'toggledown'
    );

    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createHeadField($config)
  {
    $fields = ['client', 'dateid', 'checker', 'checkerloc'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.class', 'docno sbccsreadonly');
    data_set($col1, 'client.label', 'Document No.');

    data_set($col1, 'checker.type', 'input');
    data_set($col1, 'checkerloc.type', 'input');

    $fields = ['scheddate', ['truck', 'plateno'], 'clientname', 'ourref'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'plateno.class', 'plateno sbccsreadonly');
    data_set($col2, 'plateno.type', 'input');

    data_set($col2, 'clientname.class', 'csclientname sbccsreadonly');
    data_set($col2, 'clientname.type', 'input');
    data_set($col2, 'clientname.label', 'Customer Name');

    data_set($col2, 'ourref.class', 'csyourref sbccsreadonly');
    data_set($col2, 'ourref.label', 'Ref No.');

    $fields = ['courier', 'receivedate', 'received', 'waybill'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'received.name', 'receiveby');

    data_set($col3, 'courier.type', 'lookup');
    data_set($col3, 'courier.class', 'cscourier sbccsreadonly');

    $fields = ['postwhclr'];
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'postwhclr.label', 'POST');
    data_set($col4, 'postwhclr.confirmlabel', 'Proceed to posting?');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }


  public function createTab($config)
  {
    $barcode = 0;
    $itemdesc = 1;
    $isqty = 2;
    $isamt = 3;
    $disc = 4;
    $ext = 5;
    $picker = 6;
    $location = 7;
    $tab = [
      $this->gridname => ['gridcolumns' => ['barcode', 'itemdesc', 'isqty', 'isamt', 'disc', 'ext', 'picker', 'location']],
      'multigrid' => ['action' => 'warehousingentry', 'lookupclass' => 'viewboxdetail', 'label' => 'BOX DETAIL']
    ];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['columns'][$barcode]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$itemdesc]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$isqty]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$isamt]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$disc]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$ext]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$picker]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$location]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$isqty]['align'] = 'right';

    $obj[0][$this->gridname]['columns'][$barcode]['style'] = 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;';
    $obj[0][$this->gridname]['columns'][$itemdesc]['style'] = 'width:300px;whiteSpace: normal;min-width:300px;max-width:300px;';
    $obj[0][$this->gridname]['columns'][$disc]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
    $obj[0][$this->gridname]['columns'][$isqty]['style'] = 'text-align:right;width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';
    $obj[0][$this->gridname]['columns'][$location]['style'] = 'width:80px;whiteSpace: normal;min-width:80px;max-width:80px;';


    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['showtotal'] = false;

    return $obj;
  }

  public function createTab2($config)
  {
    $tab = ['tableentry' => ['action' => 'warehousingentry', 'lookupclass' => 'viewschedulehistory', 'label' => 'SCHEDULE HISTORY']];
    $box = $this->tabClass->createtab($tab, []);

    return [
      'SCHEDULE HISTORY' => ['icon' => 'fa fa-calendar-alt', 'tab' => $box]
    ];
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
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
    $data[0]['scheddate'] = null;
    $data[0]['truckid'] = 0;
    $data[0]['truck'] = '';
    $data[0]['receivedate'] = null;
    $data[0]['receiveby'] = '';
    $data[0]['checker'] = '';
    $data[0]['checkerloc'] = '';
    $data[0]['plateno'] = '';
    $data[0]['shipto'] = '';
    $data[0]['courier'] = '';

    $data[0]['waybill'] = '';
    return $data;
  }

  private function selectqry($config)
  {
    $option = '';
    if (isset($config['params']['itemfilter'])) {
      $option = $config['params']['itemfilter'];
    }

    if (isset($config['params']['row']['isposted'])) {
      if ($config['params']['row']['isposted'] == 'true') {
        $option = 'complete';
      }
    }

    $headtable = $this->head;
    $cntnuminfo = "cntnuminfo";
    $status = '';

    if ($option == 'complete') {
      $headtable = $this->hhead;
      $cntnuminfo = "hcntnuminfo";
      $status = " and num.postdate is not null and num.status<>'VOID'";
    }

    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['head.docno', 'head.clientname', 'head.yourref', 'head.ourref', 'tr.clientname', 'tr.plateno', 'ci.courier', 'ci.receiveby', 'dttype.name'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
    }


    $qry = "select head.trno, head.doc, '' as transtype, head.trno as clientid, head.docno, head.docno as client,head.clientname,left(head.dateid,10) as dateid,
        ifnull(client.clientname,'') as checker, ifnull(cl.name,'') as checkerloc, num.crtldate, num.status as stat, date(ci.scheddate) as scheddate, ci.checkerdate,
        ci.truckid, tr.clientname as truck, tr.plateno, date(ci.receivedate) as receivedate, ci.receiveby, dtype.name as deliverytypename, head.shipto, ci.courier,
        ci.boxcount, head.waybill, head.ourref as ref, head.ourref, head.waybill, if(num.postdate is not null,'true','false') as isposted
        from " . $headtable . " as head left join " . $this->tablenum . " as num on num.trno=head.trno
        left join " . $cntnuminfo . " as ci on ci.trno=head.trno left join client on client.clientid=ci.checkerid
        left join checkerloc as cl on cl.line=ci.checkerlocid
        left join client as tr on tr.clientid=ci.truckid
        left join deliverytype as dtype on dtype.line=head.deliverytype
        where head.lockdate is not null and head.doc in (" . $this->transdoc . ") and num.center = ? and num.crtldate is not null "
      . $filtersearch . " " . $status;

    return $qry;
  }

  public function loadheaddata($config)
  {
    $trno = $config['params']['clientid'];
    $center = $config['params']['center'];

    $qry = $this->selectqry($config);
    $qry .= " and head.trno=?";
    $head = $this->coreFunctions->opentable($qry, [$center, $trno]);
    if (!empty($head)) {
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }
      $stock = $this->openstock($config);

      $hideobj = [];
      $posted = false;
      if (isset($config['params']['row']['isposted'])) {
        if ($config['params']['row']['isposted'] == 'true') {
          $hideobj = ['postwhclr' => true];
          $posted = true;
        }
      }

      return  [
        'head' => $head, 'griddata' => ['inventory' => $stock], 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false,
        'isposted' => $posted, 'qq' => $config['params']['clientid'], 'hideobj' => $hideobj
      ];
    } else {
      $head = $this->resetdata();
      return ['status' => false, 'griddata' => [], 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function openstock($config)
  {
    $stock = $this->stock;

    if (isset($config['params']['row']['isposted'])) {
      if ($config['params']['row']['isposted'] == 'true') {
        $stock = $this->hstock;
      }
    }

    $qtydec = $this->companysetup->getdecimal('qty', $config['params']);
    $amtdec = $this->companysetup->getdecimal('price', $config['params']);

    $trno = $config['params']['clientid'];
    $qry = "select item.barcode, item.itemname as itemdesc, stock.disc,
        FORMAT(stock.isqty," . $qtydec . ") as isqty, FORMAT(stock.isamt," . $amtdec . ") as isamt,
        FORMAT(stock.ext," . $amtdec . ") as ext,
        ifnull(client.clientname,'') as picker, loc.loc as location, pallet.name as pallet
        from " . $stock . " as stock left join item on item.itemid=stock.itemid
        left join client on client.clientid=stock.pickerid
        left join location as loc on loc.line=stock.locid
        left join pallet on pallet.line=stock.palletid
        where stock.trno=?";

    return $this->coreFunctions->opentable($qry, [$trno]);
  }

  public function updatehead($config, $udpate)
  {
    $head = $config['params']['head'];
    $trno  = $head['clientid'];
    $data = [];
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {        
        $data[$key] = $this->othersClass->sanitizekeyfield($key, $head[$key]);
      }
    }

    $head['shipto']  = $this->othersClass->sanitizekeyfield('shipto', $head['shipto']);
    $head['waybill']  = $this->othersClass->sanitizekeyfield('shipto', $head['waybill']);

    $this->coreFunctions->sbcupdate('cntnuminfo', $data, ['trno' => $trno]);
    $this->coreFunctions->sbcupdate('lahead', ['shipto' => $head['shipto'], 'waybill' => $head['waybill']], ['trno' => $trno]);

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $trno];
  } // end function

  public function stockstatusposted($config)
  {
    switch ($config['params']['action']) {
      case 'post':
        $clientid = $config['params']['clientid'];

        $crtldate = $this->coreFunctions->getfieldvalue("cntnum", "crtldate", "trno=?", [$clientid]);
        if ($crtldate == null) {
          return ['status' => false, 'msg' => 'Cannot post; not yet checked by the Inventory Controller.'];
        }

        $pendingpicker = $this->coreFunctions->datareader("select trno as value from lastock where pickerend is null and trno=?", [$clientid]);
        if ($pendingpicker) {
          return ['status' => false, 'msg' => 'Cannot post; some items are not yet picked.'];
        }

        $checkerdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "checkerdate", "trno=?", [$clientid]);
        if ($checkerdate == null) {
          return ['status' => false, 'msg' => 'Cannot post; not yet checked by the Warehouse Checker.'];
        }

        $truckid = $this->coreFunctions->getfieldvalue("cntnuminfo", "truckid", "trno=?", [$clientid]);
        if ($truckid == 0) {
          return ['status' => false, 'msg' => 'Cannot post; please specify a valid truck.'];
        }

        $dispatchdate = $this->coreFunctions->getfieldvalue("cntnuminfo", "dispatchdate", "trno=?", [$clientid]);
        if ($dispatchdate == null) {
          return ['status' => false, 'msg' => 'Cannot post; not yet dispatched.'];
        }

        $scheddate = $this->coreFunctions->getfieldvalue("cntnuminfo", "scheddate", "trno=?", [$clientid]);
        if ($scheddate == null) {
          return ['status' => false, 'msg' => 'Cannot post; please specify the schedule date.'];
        }

        $receivedate = $this->coreFunctions->getfieldvalue("cntnuminfo", "receivedate", "trno=?", [$clientid]);
        if ($receivedate == null) {
          return ['status' => false, 'msg' => 'Cannot post; please specify the receive date.'];
        }

        $receiveby = $this->coreFunctions->getfieldvalue("cntnuminfo", "receiveby", "trno=?", [$clientid]);
        if ($receiveby == null) {
          return ['status' => false, 'msg' => 'Cannot post; please specify Receive By.'];
        }

        $status = $this->coreFunctions->getfieldvalue("cntnum", "status", "trno=?", [$clientid]);
        if ($status != 'IN-TRANSIT') {
          return ['status' => false, 'msg' => 'Only IN-TRANSIT status is allowed to post.'];
        }

        $check = $this->checkbeforeposting($config, $config['params']['clientid']);
        if ($check['status']) {
          if ($this->createdistribution($config, $config['params']['clientid'])) {
            $post =  $this->othersClass->posttranstock($config);
            if ($post) {
              $current_time = $this->othersClass->getCurrentTimeStamp();
              $this->coreFunctions->execqry("update hcntnuminfo set status='LOGISTICS POSTED', logisticdate='" . $current_time . "', logisticby='" . $config['params']['user'] . "' where trno=?", 'update', [$clientid]);
              $this->coreFunctions->sbcupdate("cntnum", ['status' => 'DELIVERED'], ['trno' => $clientid]);
              return ['status' => true, 'msg' => 'Successfully updated.'];
            } else {
              return ['status' => false, 'msg' => 'Posting failed.'];
            }
          } else {
            return ['status' => false, 'msg' => 'Posting failed. Failed to distribute accounts.'];
          }
        } else {
          return ['status' => false, 'msg' => 'Posting failed. ' . $check['msg']];
        }

        break;

      default:
        return ['status' => false, 'msg' => 'Please check stockstatus (' . $config['params']['action'] . ')'];
        break;
    }
  }


  private  function checkbeforeposting($config, $trno)
  {
    $checkacct = $this->othersClass->checkcoaacct(['AR1', 'IN1', 'SD1', 'TX2', 'CG1']);

    if ($checkacct != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Accounts not yet setup:' . $checkacct];
    }

    $stock = $this->openstock($trno, $config);
    $checkcosting = $this->othersClass->checkcosting($stock);
    if ($checkcosting != '') {
      return ['trno' => $trno, 'status' => false, 'msg' => 'Unable to Post. ' . $checkcosting];
    }

    $override = $this->othersClass->checkAccess($config['params']['user'], 1729);

    $client = $this->coreFunctions->getfieldvalue($this->head, "client", "trno=?", [$trno]);
    $islimit = $this->coreFunctions->getfieldvalue("client", "isnocrlimit", "client=?", [$client]);

    if (floatval($islimit) == 0) {
      if ($override == '0') {
        $crline = $this->coreFunctions->getfieldvalue($this->head, "crline", "trno=?", [$trno]);
        $overdue = $this->coreFunctions->getfieldvalue($this->head, "overdue", "trno=?", [$trno]);
        $totalso = $this->coreFunctions->getfieldvalue($this->stock, "sum(ext)", "trno=?", [$trno]);
        $cstatus = $this->coreFunctions->getfieldvalue("client", "status", "client=?", [$client]);
        if (floatval($overdue) <> 0) {
          if (floatval($crline) < floatval($totalso) || $cstatus <> 'ACTIVE') {
            $this->logger->sbcwritelog($trno, $config, 'POST', 'Above Credit Limit/ Customer Status is not Active');
            return ['status' => false, 'msg' => 'Posting failed. Account is past due, credit limit has been exceeded, or customer status is not active.'];
          }
        }
      }
    }

    return ['status' => true, 'msg' => ''];
  }

  public function createdistribution($config)
  {
    $trno = $config['params']['clientid'];
    $status = true;
    $this->coreFunctions->execqry('delete from ' . $this->detail . ' where trno=?', 'delete', [$trno]);

    $qry = 'select head.dateid,head.client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.client as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,stock.isamt,stock.disc,stock.isqty,stock.cost,stock.iss,stock.fcost,head.projectid,client.rev,stock.rebate
            from ' . $this->head . ' as head left join ' . $this->stock . ' as stock on stock.trno=head.trno
            left join item on item.itemid=stock.itemid left join client on client.client = head.client left join client as wh on wh.clientid = stock.whid where head.trno=?';

    $stock = $this->coreFunctions->opentable($qry, [$trno]);
    if (!empty($stock)) {
      $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
      $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SA1']);
      $vat = floatval($stock[0]->tax);
      $tax1 = 0;
      $tax2 = 0;
      if ($vat !== 0) {
        $tax1 = 1 + ($vat / 100);
        $tax2 = $vat / 100;
      }

      foreach ($stock as $key => $value) {
        $params = [];
        $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
        if ($vat !== 0) {
          $tax = round(($stock[$key]->ext / $tax1), 2);
          $tax = round($stock[$key]->ext - $tax, 2);
        }

        if ($stock[$key]->revenue != '') {
          $revacct = $stock[$key]->revenue;
        } else {
          if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
            $revacct = $stock[$key]->rev;
          }
        }

        $params = [
          'client' => $stock[$key]->client,
          'acno' => $stock[$key]->contra,
          'ext' => $stock[$key]->ext,
          'wh' => $stock[$key]->wh,
          'date' => $stock[$key]->dateid,
          'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
          'revenue' => $revacct,
          'tax' =>  $tax,
          'discamt' => $disc * $stock[$key]->isqty,
          'cur' => $stock[$key]->cur,
          'forex' => $stock[$key]->forex,
          'cost' => round($stock[$key]->cost * $stock[$key]->iss, 2),
          'fcost' => round($stock[$key]->fcost * $stock[$key]->iss, 2),
          'projectid' => $stock[$key]->projectid,
          'rebate' => $stock[$key]->rebate
        ];
        $this->distribution($params, $config);
      }
    }

    if (!empty($this->acctg)) {
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();
      foreach ($this->acctg as $key => $value) {
        foreach ($value as $key2 => $value2) {
          $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
        }
        $this->acctg[$key]['editdate'] = $current_timestamp;
        $this->acctg[$key]['editby'] = $config['params']['user'];
        $this->acctg[$key]['encodeddate'] = $current_timestamp;
        $this->acctg[$key]['encodedby'] = $config['params']['user'];
        $this->acctg[$key]['trno'] = $trno;
        $this->acctg[$key]['db'] = round($this->acctg[$key]['db'], 2);
        $this->acctg[$key]['cr'] = round($this->acctg[$key]['cr'], 2);
        $this->acctg[$key]['fdb'] = round($this->acctg[$key]['fdb'], 2);
        $this->acctg[$key]['fcr'] = round($this->acctg[$key]['fcr'], 2);
      }
      if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
        $status = true;
      } else {
        $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
        $status = false;
      }
    }

    $ext = $this->coreFunctions->datareader("select sum(ext) as value from lastock where trno=?", [$trno]);
    $detail = $this->coreFunctions->datareader("select count(trno) as value from ladetail where trno=?", [$trno]);
    if ($ext != 0) {
      if ($detail == 0) {
        $status = false;
      }
    }

    return $status;
  } //end function


  public function distribution($params, $config)
  {
    $entry = [];

    $forex = $params['forex'];
    $cur = $params['cur'];
    $sales = 0;

    if (floatval($forex) == 0) {
      $forex = 1;
    }

    //AR
    if (floatval($params['ext']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => ($params['ext'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => floatval($forex) == 1 ? 0 : $params['ext'], 'fcr' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //disc
    if (floatval($params['discamt']) != 0) {
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SD1']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'db' => ($params['discamt'] * $forex), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['discamt']), 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    //rebate vitaline
    if (floatval($params['rebate']) != 0) {
      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['AR3']);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => 0, 'cr' => $params['rebate'] * $forex, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $params['rebate'], 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }

    if (floatval($params['tax']) != 0) {
      //sales
      $sales = ($params['ext'] - $params['rebate'] - $params['tax']);
      $sales  = $sales + $params['discamt'];
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }


      // input tax
      $input = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['TX2']);
      $entry = ['acnoid' => $input, 'client' => $params['client'], 'cr' => ($params['tax'] * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['tax']), 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    } else {
      //sales
      $sales = ($params['ext'] - $params['rebate']);
      $sales = round(($sales + $params['discamt']), 2);
      if (floatval($sales) != 0) {
        $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['revenue']]);
        $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'cr' => ($sales * $forex), 'db' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : $sales, 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }

    if (floatval($params['cost']) != 0) {

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' => 0, 'cr' => $params['cost'], 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => $params['fcost'], 'fdb' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['acno']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['client'], 'db' => ($params['cost']), 'cr' => 0, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fdb' => $params['fcost'], 'fcr' => 0, 'projectid' => $params['projectid']];
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
    }
  } //end function

  // start
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
    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config['params']['dataid']);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  public function doclistingreport($config)
  {
    $style = 'width:500px;max-width:500px;';
    $result = $this->loaddoclisting($config);

    if (!$result['status']) {
      return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => 'Failed to generate report', 'style' => $style, 'directprint' => true];
    }

    $data = app($this->companysetup->getreportpath($config['params']))->report_waybill_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => $str, 'style' => $style, 'directprint' => true];
  }
}
