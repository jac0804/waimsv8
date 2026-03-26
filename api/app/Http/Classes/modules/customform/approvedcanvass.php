<?php

namespace App\Http\Classes\modules\customform;

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

class approvedcanvass
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Approval Form';
  public $gridname = 'editgrid';
  private $companysetup;
  private $coreFunctions;
  public $tablelogs = 'transnum_log';
  private $othersClass;
  private $logger;
  public $style = 'width:100%;max-width:100%;height:100%;';
  public $issearchshow = false;
  public $showclosebtn = true;



  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
  }

  public function createTab($config)
  {
    $radiostatus = 0;
    $ctrlno = 1;
    $carem = 2;
    $rem = 3;
    $dateid = 4;
    $docno = 5;
    $itemname = 6;
    $specs = 7;
    $rrcost = 8;
    $rrqty2 = 9;
    $rrqty = 10;
    $uom = 11;
    $disc = 12;
    $ext = 13;
    $isinvoice = 14;
    $amt1 = 15;
    $amt2 = 16;
    $isprefer = 17;
    $isadv = 18;
    $requestorname = 19;
    $purpose = 20;
    $department = 21;

    $tab = [
      $this->gridname => [
        'gridcolumns' => ['radiostatus', 'ctrlno', 'carem', 'rem', 'dateid', 'docno', 'itemname', 'specs', 'rrcost', 'rrqty2', 'rrqty', 'uom', 'disc', 'ext', 'isinvoice', 'amt1', 'amt2', 'isprefer', 'isadv', 'requestorname', 'purpose', 'department']
      ]
    ];

    $stockbuttons = ['approvedcanvass'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);

    $obj[0][$this->gridname]['style'] = 'max-height:600px; height:600px; overflow:auto;';

    // action
    $obj[0][$this->gridname]['columns'][$radiostatus]['style'] = "width:20px;whiteSpace: normal;min-width:20px;";

    $obj[0][$this->gridname]['columns'][$rem]['label'] = 'Notes (Canvasser)';
    $obj[0][$this->gridname]['columns'][$rem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$carem]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";

    $obj[0][$this->gridname]['columns'][$dateid]['style'] = "width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][$dateid]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$docno]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$docno]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$rrcost]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$rrqty]['style'] = "width:120px;whiteSpace: normal;min-width:120px;";
    $obj[0][$this->gridname]['columns'][$rrqty]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$rrqty2]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$disc]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][$disc]['readonly'] = true;
    $obj[0][$this->gridname]['columns'][$ext]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";


    $obj[0][$this->gridname]['columns'][$amt1]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";
    $obj[0][$this->gridname]['columns'][$amt2]['style'] = "width:80px;whiteSpace: normal;min-width:80px;";

    $obj[0][$this->gridname]['columns'][$ext]['type'] = "input";

    $obj[0][$this->gridname]['columns'][$amt1]['label'] = "Freight Fees";
    $obj[0][$this->gridname]['columns'][$amt2]['label'] = "Installation Fees";

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$docno]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$ext]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$rem]['type'] = "label";

    if ($config['params']['companyid'] == 16) { //ati
      $obj[0][$this->gridname]['columns'][$rrcost]['type'] = 'label';
      $obj[0][$this->gridname]['columns'][$itemname]['type'] = "label";
      $obj[0][$this->gridname]['columns'][$specs]['type'] = "label";

      $obj[0][$this->gridname]['columns'][$amt1]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$amt2]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$itemname]['label'] = "Item Name";

      $obj[0][$this->gridname]['columns'][$itemname]['style'] = "width:150px;whiteSpace: normal;min-width:150px;";

      $obj[0][$this->gridname]['columns'][$rrqty2]['label'] = "PR Qty";
      $obj[0][$this->gridname]['columns'][$rrqty2]['type'] = "label";

      $obj[0][$this->gridname]['columns'][$rrqty]['readonly'] = false;

      $obj[0][$this->gridname]['columns'][$isprefer]['checkfield'] = 'isprefer2';
      $obj[0][$this->gridname]['columns'][$isadv]['checkfield'] = 'isadv';

      $obj[0][$this->gridname]['columns'][$isinvoice]['type'] = "label";

      $obj[0][$this->gridname]['columns'][$purpose]['style'] = "width:280px;whiteSpace: normal;min-width:280px;";
      $obj[0][$this->gridname]['columns'][$department]['style'] = "width:200px;whiteSpace: normal;min-width:200px;";
    } else {
      $obj[0][$this->gridname]['columns'][$carem]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$itemname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$specs]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$rrqty2]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$isprefer]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$isadv]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$isinvoice]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$uom]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$requestorname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$purpose]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$department]['type'] = "coldel";
    }

    if ($config['params']['doc'] == 'CANVASSAPPROVAL2') {
      $obj[0][$this->gridname]['columns'][$radiostatus]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$uom]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$requestorname]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$purpose]['type'] = "coldel";
      $obj[0][$this->gridname]['columns'][$department]['type'] = "coldel";

      $obj[0][$this->gridname]['columns'][$rem]['readonly'] = true;
      $obj[0][$this->gridname]['columns'][$rrcost]['readonly'] = true;
    }

    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
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
    $fields = ['barcode'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, "barcode.type", "input");

    $fields = ['itemname'];
    $col2 = $this->fieldClass->create($fields);

    $fields = [];
    if ($config['params']['companyid'] == 16 && $config['params']['doc'] == 'CANVASSAPPROVAL2') { //ati
      array_push($fields, 'amt');
    }
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'amt.label', 'Approved Cost');

    $fields = [];
    switch ($config['params']['companyid']) {
      case 16: //ati
        if ($config['params']['doc'] != 'CANVASSAPPROVAL2') {
          $fields = ['refresh'];
        }
        break;
      default:
        $fields = ['refresh'];
        break;
    }
    $col4 = $this->fieldClass->create($fields);
    data_set($col4, 'refresh.action', 'ap');
    data_set($col4, 'refresh.label', 'SAVE');

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $doc = $config['params']['doc'];
    $barcode = isset($config['params']['row']['barcode']) ? $config['params']['row']['barcode'] : '';
    $rrcost = isset($config['params']['row']['rrcost']) ? $this->othersClass->sanitizekeyfield('qty', $config['params']['row']['rrcost'])  : 0;
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $arrfilter = [];

    switch ($systemtype) {
      case 'ATI':
        $reqtrno = $config['params']['row']['reqtrno'];
        $reqline = $config['params']['row']['reqline'];
        $qry = "select '' as barcode, info.itemdesc as itemname,FORMAT(" . $rrcost . ",2) as amt from hstockinfotrans as info where info.trno=? and info.line=? group by info.itemdesc";
        $arrfilter = [$reqtrno, $reqline];
        break;

      default:
        $qry = "select item.barcode, item.itemname from item where item.barcode=? ";
        $arrfilter = [$barcode];
        break;
    }

    $data = $this->coreFunctions->opentable($qry,  $arrfilter);
    return $data;
  }

  public function data($config, $preqtrno = 0, $preqline = 0)
  {
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    if (isset($config['params']['row'])) {
      $barcode = isset($config['params']['row']['barcode']) ? $config['params']['row']['barcode'] : '';
    }
    if (isset($config['params']['dataparams'])) {
      $barcode = isset($config['params']['dataparams']['barcode']) ? $config['params']['dataparams']['barcode'] : '';
    }

    $addedfields = '';
    $addedleftjoin = '';
    $filter = '';
    $arrfilter = [];

    switch ($systemtype) {
      case 'ATI':
        $addedfields = ", item.itemname, info.itemdesc, stock.reqtrno, stock.reqline, info.specs, case when stock.isprefer=0 then 'false' else 'true' end as isprefer, 'true' as isprefer2,
        case when hinfo.isadv=0 then 'false' else 'true' end as isadv, 'true' as isadv2,dept.clientname as department,info.ctrlno,
        ifnull(info.purpose,'') as purpose,ifnull(info.requestorname,'') as requestorname, stock.waivedqty, uom3.factor, stock.qty, stockinfo.uom2, stockinfo.uom3,  
         case when hinfo.isinvoice=0 then 'NO' else 'YES' end as isinvoice ";
        $addedleftjoin = ' left join hstockinfotrans as info on info.trno=stock.reqtrno and info.line=stock.reqline left join client as dept on dept.clientid=stock.deptid 
        left join uomlist as uom3 on uom3.uom=stockinfo.uom3 and uom3.isconvert=1';

        if (isset($config['params']['row'])) {
          $reqtrno = isset($config['params']['row']['reqtrno']) ? $config['params']['row']['reqtrno'] : 0;
          $reqline = isset($config['params']['row']['reqline']) ? $config['params']['row']['reqline'] : 0;
        } else {
          if (isset($config['params']['rows'][0])) {
            $reqtrno = isset($config['params']['rows'][0]['reqtrno']) ? $config['params']['rows'][0]['reqtrno'] : 0;
            $reqline = isset($config['params']['rows'][0]['reqline']) ? $config['params']['rows'][0]['reqline'] : 0;
          } else {
            $reqtrno = $preqtrno;
            $reqline = $preqline;
          }
        }

        $filter = ' and stock.reqtrno=? and stock.reqline=?';
        $arrfilter = [$reqtrno, $reqline];
        break;

      default:
        $addedfields = ", item.barcode, item.itemname";
        $filter = ' and item.barcode=? ';
        $arrfilter = [$barcode];
        break;
    }

    $filterstatus = ' and stock.status=0 ';
    if ($config['params']['doc'] == 'CANVASSAPPROVAL2') {
      $filterstatus = ' and stock.status=2';
    }

    $qry = "select date(head.dateid) as dateid,head.docno,head.client,head.clientname,stock.trno,stock.line,format(stock.rrcost,2) as rrcost,format(stock.rrqty,2) as rrqty,stock.disc,format(stock.ext,2) as ext,stock.status,stock.uom,stock.itemid,
      '' as bgcolor,stock.rem, FORMAT(stockinfo.amt1," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt1,stock.rrqty2,
      FORMAT(stockinfo.amt2," . $this->companysetup->getdecimal('currency', $config['params']) . ") as amt2, stock.refx, stock.linex " . $addedfields . "
      from hcdhead as head left join hcdstock as stock on stock.trno=head.trno left join hheadinfotrans as hinfo on hinfo.trno=head.trno
      left join item on item.itemid=stock.itemid left join hstockinfotrans as stockinfo on stockinfo.trno=stock.trno and stockinfo.line=stock.line " . $addedleftjoin . "
      where stock.void=0 " . $filterstatus . $filter . " order by item.barcode";

    $data = $this->coreFunctions->opentable($qry, $arrfilter);
    return $data;
  } //end function

  public function loaddata($config)
  {
    $rows = $config['params']['rows'];
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $msg = 'Successfully updated.';
    $status = true;

    $blnApproved = false;
    $reqtrno = 0;
    $reqline = 0;

    foreach ($rows as $key) {
      if ($config['params']['companyid'] == 16) { //ati
        if ($key['status'] == 1) {
          if ($key['waivedqty'] == 0) {

            $basetotal = $key['rrqty2'] * $key['factor'];

            if ($basetotal == 0) { // to approved previous canvass
              if ($key['rrqty'] > $key['rrqty2']) {
                $msg = 'Approved quantity must not be greater than request quantity of ' . $key['rrqty2'];
                $status = false;
                goto ExitHere;
                break;
              }
            } else {
              // if ($key['rrqty'] > $key['rrqty2']) {
              if ($key['qty'] > $basetotal) {
                $msg = 'Approved quantity must not be greater than request base quantity base of ' . number_format($basetotal, 2);
                $status = false;
                goto ExitHere;
                break;
              }
            }
          }
        }
      }

      if ($key['status'] == 1) {
        if ($blnApproved) {
          $msg = 'Please approve one supplier only.';
          $status = false;
          goto ExitHere;
          break;
        } else {
          $blnApproved = true;
          $reqtrno = isset($key['reqtrno']) ? $key['reqtrno'] : 0;
          $reqline = isset($key['reqline']) ? $key['reqline'] : 0;
        }
      }
    }

    foreach ($rows as $key) {

      $rrcost = 0;
      $cost = 0;
      $ext = 0;

      if ($key['status'] == 1) {
        if ($config['params']['companyid'] == 16) { //ati
          $approvedqty = $this->coreFunctions->datareader("select ifnull(sum(s.qty),0) as value from hcdstock as s where s.approveddate is not null and s.status=1 and s.void=0 and s.waivedqty=0 and s.reqtrno=? and s.reqline=?", [$reqtrno, $reqline], '', true);

          if ($approvedqty != 0) {
            $basetotal = $key['rrqty2'] * $key['factor'];

            if ($basetotal == 0) {
              if (($approvedqty + $key['rrqty']) > $key['rrqty2']) {
                return ['status' => false, 'msg' => 'Request quantity of ' . number_format($key['rrqty2'], 2) . ' for item has already been approved. You are not allow to post another canvass sheet'];
              }
            } else {
              if ($key['waivedqty'] == 0) {
                if (($approvedqty + $key['qty']) > $basetotal) {

                  $appdoc = $this->coreFunctions->datareader("select group_concat(concat(h.docno,' - ',cast(format(s.rrqty,2) as char),' ',s.uom)) as value 
                                  from hcdstock as s left join hcdhead as h on h.trno=s.trno
                                  where s.approveddate is not null and s.status=1 and s.void=0 and s.waivedqty=0 and s.reqtrno=? and s.reqline=?", [$reqtrno, $reqline]);

                  $approvecanvassref = '';
                  if ($appdoc != '') {
                    $approvecanvassref = '. Approved Canvass ref. ' . $appdoc;
                  }

                  return ['status' => false, 'msg' => 'Request quantity of ' . number_format($key['rrqty2'], 2) . ' for item ' . $key['itemdesc'] . ' has already been approved. You are not allow to approve another canvass sheet. 
                          Base quantity approved: ' . number_format($approvedqty, 2) . '. For approval canvass based quantity: ' . number_format($key['qty'], 2) . $approvecanvassref];
                }
              }
            }
          }
        }
      }

      $this->coreFunctions->execqry('update hcdstock set status=?, approveddate=?,approvedby=?,rem=?  where trno=? and line=?', 'update', [$key['status'], $current_timestamp, $config['params']['user'], $key['rem'], $key['trno'], $key['line']]);


      if ($config['params']['companyid'] == 3) { //conti
        if ($key['status'] == 1) {
          $qry1 = "select stock.qty from hcdhead left join hcdstock as stock on stock.trno= hcdhead.trno where hcdhead.doc='CD' and stock.void = 0 and stock.status=1 and stock.refx=" . $key['refx'] . " and stock.linex=" . $key['linex'];
          $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
          $qty = $this->coreFunctions->datareader($qry2);
          if ($qty === '') {
            $qty = 0;
          }

          $resultqa = $this->coreFunctions->execqry("update hprstock set qa=" . $qty . " where trno=" . $key['refx'] . " and line=" . $key['linex'], 'update');
          if (!$resultqa) {
            $this->coreFunctions->execqry("update hcdstock set status=0, approveddate=null,approvedby='',rem=''  where trno=? and line=?", 'update', [$key['status'], $current_timestamp, $config['params']['user'], $key['rem'], $key['trno'], $key['line']]);
            $msg .= 'Total approved canvass quantity is greater than request quantity.';
            $key['status'] = 0;
          }
        }
      }

      if ($config['params']['companyid'] == 16) { //ati
        $qry = "select ifnull(uom.factor,1) as factor from item left join uom on uom.itemid=item.itemid and uom.uom=? where item.itemid=?";
        $item = $this->coreFunctions->opentable($qry, [$key['uom'], $key['itemid']]);
        $factor = 1;
        if (!empty($item)) {
          $item[0]->factor = $this->othersClass->val($item[0]->factor);
          if ($item[0]->factor !== 0) $factor = $item[0]->factor;
        }

        $key['rrcost'] = $this->othersClass->sanitizekeyfield('amt',  $key['rrcost']);
        $key['rrqty'] = $this->othersClass->sanitizekeyfield('qty',  $key['rrqty']);

        $computedata = $this->othersClass->computestock($key['rrcost'], $key['disc'], $key['rrqty'], $factor);

        $stock = [
          'rrcost' => $key['rrcost'],
          'cost' => $computedata['amt'],
          'ext' => $computedata['ext'],
          'editby' =>  $config['params']['user'],
          'editdate' => $this->othersClass->getCurrentTimeStamp()
        ];
        foreach ($stock as $key2 => $value) {
          $stock[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
        }

        $this->coreFunctions->sbcupdate('hcdstock', $stock, ['trno' => $key['trno'], 'line' => $key['line']]);

        if ($key['status'] == 2) {
          $pending = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from hcdstock where trno=? and status<>2", [$key['trno']], '', true);
          if ($pending == 0) $this->coreFunctions->execqry("update transnum set statid=77 where trno=" . $key['trno']);
        }

        if ($key['status'] == 1) {

          $this->coreFunctions->execqry("update hprstock set uom='" . $key['uom'] . "' where trno=" . $key['refx'] . " and line=" . $key['linex'], 'update');
          $this->coreFunctions->execqry("update hstockinfotrans set uom2='" . $key['uom2'] . "',uom3='" . $key['uom3'] . "' where trno=" . $key['refx'] . " and line=" . $key['linex'], 'update');

          //recompute qty of RR
          $prdata = $this->coreFunctions->opentable("select pr.itemid, pr.rrqty, pr.rrcost, pr.uom, ifnull(uom.factor,0), info.uom2, info.uom3, ifnull(uom2.factor,0) as factor2, ifnull(uom3.factor,0) as factor3
                                                    from hprstock as pr left join uom on uom.itemid=pr.itemid and uom.uom=pr.uom 
                                                    left join hstockinfotrans as info on info.trno=pr.trno and info.line=pr.line
                                                    left join uomlist as uom2 on uom2.uom=info.uom2 and uom2.isconvert=1
                                                    left join uomlist as uom3 on uom3.uom=info.uom3 and uom3.isconvert=1
                                                    where pr.trno=? and pr.line=?", [$key['refx'], $key['linex']]);
          if (!empty($prdata)) {
            if ($prdata[0]->uom3 != '') {
              if ($prdata[0]->factor3 == 0) {
                $prdata[0]->factor3 = 1;
              }

              $computeprdata = $this->othersClass->computestock($prdata[0]->rrcost, '', $prdata[0]->rrqty, $prdata[0]->factor3);
              $prdataupdate = [
                'editby' =>  $config['params']['user'],
                'editdate' => $this->othersClass->getCurrentTimeStamp(),
                'qty' => $computeprdata['qty'],
                'cost' => $computeprdata['amt'],
                'ext' =>  round($computeprdata['ext'], $this->companysetup->getdecimal('qty', $config['params']))
              ];

              $this->coreFunctions->sbcupdate("hprstock", $prdataupdate, ['trno' => $key['refx'], 'line' => $key['linex']]);
            }
          }

          $this->coreFunctions->execqry("update hprstock set statrem='Canvass Sheet - Approved',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=?", 'update', [$reqtrno, $reqline]);

          $qry1 = "";
          $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $key['refx'] . " and stock.linex=" . $key['linex'];
          $qry1 = $qry1 . " union all select stock.qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $key['refx'] . " and stock.linex=" . $key['linex'];
          $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
          $cdqa = $this->coreFunctions->datareader($qry2);
          if ($cdqa == '') {
            $cdqa = 0;
          }
          $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . " where trno=" . $key['refx'] . " and line=" . $key['linex']);

          $this->logger->sbcwritelog($key['trno'], $config, 'STOCK', 'Line: ' . $key['line'] . ' - APPROVED item ');
        } elseif ($key['status'] == 2) {
          $this->coreFunctions->execqry("update hprstock set statrem='Canvass Sheet - Rejected',statdate='" . $this->othersClass->getCurrentTimeStamp() . "' where trno=? and line=? and statrem<>'Canvass Sheet - Approved'", 'update', [$reqtrno, $reqline]);

          if ($config['params']['doc'] == 'CANVASSAPPROVAL') {
            $qry1 = "";
            $qry1 = "select stock.qty from cdhead as head left join cdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $key['refx'] . " and stock.linex=" . $key['linex'];
            $qry1 = $qry1 . " union all select stock.qty from hcdhead as head left join hcdstock as stock on stock.trno=head.trno where head.doc='CD' and stock.void=0 and stock.status=1 and stock.refx=" . $key['refx'] . " and stock.linex=" . $key['linex'];
            $qry2 = "select ifnull(sum(qty),0) as value from (" . $qry1 . ") as t";
            $cdqa = $this->coreFunctions->datareader($qry2);
            if ($cdqa == '') {
              $cdqa = 0;
            }
            $this->coreFunctions->execqry("update hprstock set cdqa=" . $cdqa . " where trno=" . $key['refx'] . " and line=" . $key['linex']);
          }

          $this->logger->sbcwritelog($key['trno'], $config, 'STOCK', 'Line: ' . $key['line'] . ' - REJECTED item ');
        }
      }

      $stockinfo = [
        'amt1' => $key['amt1'],
        'amt2' => $key['amt2'],
        'editby' =>  $config['params']['user'],
        'editdate' => $this->othersClass->getCurrentTimeStamp(),
      ];
      if ($config['params']['companyid'] == 16) {
        if (isset($key['carem'])) {
          $stockinfo['carem'] = $key['carem'];
        }
      }
      foreach ($stockinfo as $key2 => $value) {
        $stockinfo[$key2] = $this->othersClass->sanitizekeyfield($key2, $value);
      }
      $this->coreFunctions->sbcupdate('hstockinfotrans', $stockinfo, ['trno' => $key['trno'], 'line' => $key['line']]);
    }

    ExitHere:
    $data = $this->data($config, $reqtrno, $reqline);
    return ['status' => $status, 'msg' =>  $msg, 'data' => $data];
  } //end function
































} //end class
