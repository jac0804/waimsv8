<?php

namespace App\Http\Classes\modules\pos;

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

use Exception;

class otherextraction
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Other Extraction';
  public $gridname = 'entrygrid';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $sqlquery;
  private $logger;
  public $tablelogs = 'table_log';
  public $htablelogs = 'htable_log';
  public $style = 'width:100%;max-width:100%;';
  public $issearchshow = false;
  public $showclosebtn = false;
  public $head = 'lahead';
  public $hhead = 'glhead';
  public $stock = 'lastock';
  public $hstock = 'glstock';
  public $tablenum = 'cntnum';
  public $detail = 'ladetail';
  public $hdetail = 'gldetail';
  public $tablelogs_del = 'del_table_log';
  private $acctg = [];


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
    $this->logger = new Logger;
  }

  public function getAttrib()
  {
    $attrib = array('view' => 2515, 'save' => 2515, 'saveallentry' => 2515);
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $cols = [
      'dateid',
      'branch',
      'transtype',
      'station',
      'docno',
      'clientname',
      'rem'
    ];

    foreach ($cols as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => [
      'gridcolumns' => $cols
    ]];

    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['totalfield'] = '';
    $obj[0][$this->gridname]['label'] = 'TRANSACTIONS';

    $obj[0][$this->gridname]['columns'][$dateid]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$branch]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$transtype]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$station]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$docno]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$clientname]['type'] = 'label';
    $obj[0][$this->gridname]['columns'][$rem]['type'] = 'label';

    $obj[0][$this->gridname]['columns'][$clientname]['label'] = 'Name';
    return $obj;
  }

  public function createtab2($access, $config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'EXTRACT';
    $obj[0]['confirmlabel'] = 'Are you sure you want to extract this transaction?';
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = ['lblbranch', 'client'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.type', 'lookup');
    data_set($col1, 'client.label', 'Branch');
    data_set($col1, 'client.lookupclass', 'ebranch');
    data_set($col1, 'client.action', 'lookupclient');
    if ($config['params']['companyid'] == 56) { //homeworks
      data_set($col1, 'client.type', 'input');
    }

    $fields = ['lbldateid', 'dateid', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.type', 'lookup');
    data_set($col2, 'dateid.addedparams', ['client']);
    data_set($col2, 'dateid.lookupclass', 'lookupotherextraction');
    data_set($col2, 'dateid.action', 'lookupotherextraction');
    data_set($col2, 'refresh.label', 'Refresh');
    data_set($col2, 'refresh.action', 'refresh');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $client = '';
    if ($config['params']['companyid'] == 56) { //homeworks
      $client =   $this->companysetup->getbranchcenter($config['params']);
    }

    $data = $this->coreFunctions->opentable("select '" . $client . "' as client,'' as dateid");

    return $data[0];
  }

  public function data($config)
  {
    return $this->paramsdata($config);
  }

  public function headtablestatus($config)
  {
    $action = $config['params']["action2"];
    switch ($action) {
      case 'saveallentry': //generate entries
        return $this->extract($config);
        break;
      case 'refresh': //loading all CRS
        return $this->loaddata($config, $config['params']['dataparams']['client'], $config['params']['dataparams']['dateid']);
        break;

      default:
        return ['status' => false, 'msg' => 'Please check headtablestatus (' . $action . ')'];
        break;
    } // end switch
  }

  public function stockstatusposted($config)
  {

    $action = $config['params']["action"];
    switch ($action) {
      case 'refresh':
        return $this->loaddata($config, $config['params']['dataparams']['client'], $config['params']['dataparams']['dateid']);
        break;

      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $action . ')'];
        break;
    } // end switch
  }

  private function selectqry($branchfilter, $dateid)
  {
    return  "select 'LAYAWAY' as transtype, 'CR' as doc, line, docno, station, clientname, date(dateid) as dateid, branch, amt, client, clientname, rem
    FROM layaway WHERE isok2=0 AND date(dateid)='" . date('Y-m-d', strtotime($dateid)) . "' " . $branchfilter . ' order by dateid';
  }

  private function loaddata($config, $branch, $dateid)
  {
    $branchfilter = "";
    if ($branch != "") {
      $branchfilter = " and branch ='" . $branch . "'";
    }

    $qry = $this->selectqry($branchfilter, $dateid);
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  private function extract($config)
  {

    $branch = $config['params']['dataparams']['client'];
    $dateid = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));

    //checking if there are unextracted previous sales 
    $exist = $this->coreFunctions->datareader("select date(dateid) as value from layaway where date(dateid)<'" . $dateid . "' and isok2 =0 and branch='" . $branch . "' limit 1");
    if (strlen($exist) != 0) {
      return ['status' => false, 'msg' => 'There are pending transactions from previous date ' . $exist . '. Extraction Failed.'];
    }


    $params = $config;
    unset($params['params']['rows']);
    $status = true;
    $msg = '';

    foreach ($config['params']['rows'] as $key => $value) {

      switch ($value['doc']) {
        case 'CR':
          $result = $this->createCRS($value,  $params);
          if (!$result['status']) {
            $status = false;
            $msg .= $result['msg'] . '<br>';
          }
          break;
      }
    }

    $branchfilter = " and branch ='" . $branch . "'";
    $qry = $this->selectqry($branchfilter, $dateid);
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => $status, 'msg' => $msg, 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }

  private function createCRS($value, $config)
  {
    $this->acctg = [];
    $status = false;
    $msg = '';

    $trno = 0;
    $doc = 'CR';
    $pref = 'CRS';
    $path = 'App\Http\Classes\modules\receivable\cr';
    $referencemodule = '';

    $fields = ['trno', 'doc', 'docno', 'client', 'clientname', 'dateid', 'layref', 'rem', 'ourref'];
    $data = [];

    $qry = "select amt, cash, card, debit, others, carddetail, debitdetail, otherdetail, dtype
        from layaway where line=" . $value['line'] . " and docno='" . $value['docno'] . "' and branch='" . $value['branch'] . "' and station='" . $value['station'] . "' and date(dateid)='" . $value['dateid'] . "'";

    $info = $this->coreFunctions->opentable($qry);
    try {
      if (!empty($info)) {
        $value['cash'] = $info[0]->cash;
        $value['card'] = $info[0]->card;
        $value['carddetail'] = $info[0]->carddetail;
        $value['debit'] = $info[0]->debit;
        $value['debitdetail'] = $info[0]->debitdetail;
        $value['others'] = $info[0]->others;
        $value['otherdetail'] = $info[0]->otherdetail;
        $value['dtype'] = $info[0]->dtype;
      } else {
        return ['status' => false, 'msg' => 'Invalid payment details for reference ' . $value['docno']];
      }

      $config['params']['station'] = $value['station'];
      $trno = $this->othersClass->generatecntnum($config, 'cntnum',  $doc, $pref, $this->companysetup->getdocumentlength($config['params']), 0, '', true, 0, '');
      if ($trno > 0) {
        $config['params']['trno'] = $trno;

        $head['doc'] = $doc;
        $head['trno'] = $trno;
        $head['docno'] = $this->coreFunctions->getfieldvalue('cntnum', 'docno', 'trno=?', [$trno]);
        $head['dateid'] = $value['dateid'];
        $head['client'] = $value['client'];
        $head['clientname'] = $value['clientname'];
        $head['branchid'] = $this->coreFunctions->getfieldvalue('client', 'clientid', 'client=?', [$value['branch']]);
        $head['rem'] = $value['rem'];
        $head['layref'] = $value['docno'];
        $head['ourref'] = $value['dtype'];

        foreach ($fields as $k) {
          $data[$k] = $head[$k];
          $data[$k] = $this->othersClass->sanitizekeyfield($k, $data[$k]);
        }

        $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
        $data['createby'] = $config['params']['user'];

        $inserthead = $this->coreFunctions->sbcinsert('lahead', $data);
        if ($inserthead) {
          $this->logger->sbcwritelog($trno, $config, 'AUTO CREATE', $head['docno'] . ' - ' . $value['docno']);

          //create detail entry
          $line = 0;

          $cashid = $checkid = $cardid = $arid = $arid2 = $bcid = 0;
          $cashname = $checkname = $cardname = $arname = $arname2 = '';
          $dcAR = 0;

          $savingacct = $this->coreFunctions->getfieldvalue("client", "savingsacct", "client=?", [$value['branch']]);
          $cashid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno=?", [$savingacct]);

          $arid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARL1']);
          $arid2 = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARL2']);
          $bcid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['BC']);

          $cardstr = $this->coreFunctions->getfieldvalue("profile", "pvalue", "doc=? and psection=?", ['CARD', 'ACCTG']);
          $cardid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "acno=?", [$cardstr]);

          $detail = [];

          if ($value['cash'] != 0) {
            $line = $line + 1;
            $entry = ['acnoid' => $cashid, 'client' => $head['client'], 'db' => $value['cash'], 'cr' => 0, 'postdate' => $head['dateid'], 'dpref' => $value['docno']];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            $dcAR = $dcAR + $value['cash'];
          }

          if ($value['others'] != 0) {
            if ($value['otherdetail'] != '') {
              $strT = $client = '';
              $suppid = 0;

              $j = explode(",", $value['otherdetail']);
              for ($x = 0; $x <= count($j) - 1; $x++) {
                $k = explode("~", $j[$x]);
                $strT = $k[2];
                $suppid = $this->coreFunctions->datareader("select clientid as value from checktypes where type = '" . $strT . "'");
                $checkid = $this->coreFunctions->datareader("select acnoid as value from checktypes where type = '" . $strT . "'");
                if ($suppid == 0) {
                  $client = $head['client'];
                } else {
                  $client = $this->coreFunctions->getfieldvalue("client", "client", "clientid=?", [$suppid]);
                }

                $line = $line + 1;
                $entry = ['acnoid' => $checkid, 'client' => $client, 'db' => $k[1], 'cr' => 0, 'postdate' => $head['dateid'], 'dpref' => $value['docno'], 'rem' => $strT];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                $dcAR = $dcAR + $k[1];
              }
            }
          }

          if ($value['card'] != 0) {
            if ($value['carddetail'] != '') {
              $carddetail = '';
              $j = explode(",", $value['carddetail']);
              for ($x = 0; $x <= count($j) - 1; $x++) {
                if (substr($j[$x], 0, 1) == 'C') {
                  $strCharge = '';
                  $cardamt = $chargeamt = 0;

                  $k = explode("~", $j[$x]);

                  $s = explode("-", $k[5]);
                  $carddetail = trim($s[0]);
                  $cardid = $this->coreFunctions->datareader("select acnoid as value from branchbank AS b where clientid =" . $head['branchid'] . " and  terminalid = '" . $carddetail . "' and isinactive=0", [], '', true);
                  if ($cardid == 0) {
                    $cardid = $this->coreFunctions->datareader("select acnoid as value from branchbank AS b where clientid =" . $head['branchid'] . " and  terminalid = '" . $carddetail . "' and isinactive=1 limit 1", [], '', true);
                  }
                  $strCharge = $this->coreFunctions->datareader("select rate as value from bankcharges AS b where type ='" . $k[9] . "' and  terminalid = '" . $carddetail . "'");
                  if ($strCharge != "") {
                    $chargeamt = round($k[1] - $this->othersClass->Discount($k[1], $strCharge), 2);
                    $cardamt = $k[1] -   $chargeamt;
                  } else {
                    $cardamt = $k[1];
                  }
                  $line = $line + 1;
                  $entry = ['acnoid' => $cardid, 'client' => $head['client'], 'db' => $cardamt, 'cr' => 0, 'postdate' => $head['dateid'], 'dpref' => $value['docno']];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                  if ($chargeamt != 0) {
                    $line = $line + 1;
                    $entry = ['acnoid' => $bcid, 'client' => $head['client'], 'db' => $chargeamt, 'cr' => 0, 'postdate' => $head['dateid'], 'dpref' => $value['docno'], 'rem' => $strCharge];
                    $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                  }
                  $dcAR = $dcAR + $k[1];
                }
              }
            }
          }

          if ($value['debit'] != 0) {
            if ($value['debitdetail'] != '') {
              $carddetail = '';
              $j = explode(",", $value['debitdetail']);
              for ($x = 0; $x <= count($j) - 1; $x++) {
                if (substr($j[$x], 0, 1) == 'D') {
                  $strCharge = '';
                  $cardamt = $chargeamt = 0;

                  $k = explode("~", $j[$x]);

                  $s = explode("-", $k[5]);
                  $carddetail = trim($s[0]);
                  $cardid = $this->coreFunctions->datareader("select acnoid as value from branchbank AS b where clientid =" . $head['branchid'] . " and  terminalid = '" . $carddetail . "' and isinactive=0", [], '', true);
                  if ($cardid == 0) {
                    $cardid = $this->coreFunctions->datareader("select acnoid as value from branchbank AS b where clientid =" . $head['branchid'] . " and  terminalid = '" . $carddetail . "' and isinactive=1 limit 1", [], '', true);
                  }
                  $strCharge = $this->coreFunctions->datareader("select rate as value from bankcharges AS b where type ='" . $k[9] . "' and  terminalid = '" . $carddetail . "'", [], '', true);
                  if ($strCharge != "") {
                    $chargeamt = round($k[1] - $this->othersClass->Discount($k[1], $strCharge), 2);
                    $cardamt = $k[1] -   $chargeamt;
                  } else {
                    $cardamt = $k[1];
                  }
                  $line = $line + 1;
                  $entry = ['acnoid' => $cardid, 'client' => $head['client'], 'db' => $cardamt, 'cr' => 0, 'postdate' => $head['dateid'], 'dpref' => $value['docno']];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                  if ($chargeamt != 0) {
                    $line = $line + 1;
                    $entry = ['acnoid' => $bcid, 'client' => $head['client'], 'db' => $chargeamt, 'cr' => 0, 'postdate' => $head['dateid'], 'dpref' => $value['docno'], 'rem' => $strCharge];
                    $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                  }
                  $dcAR = $dcAR + $k[1];
                }
              }
            }
          }

          if ($value['dtype'] == 'HOMECREDIT') {
            $entry = ['acnoid' => $arid2, 'client' => $head['client'], 'db' => 0, 'cr' => $dcAR, 'postdate' => $head['dateid'], 'dpref' => $value['docno']];
          } else {
            $entry = ['acnoid' => $arid, 'client' => $head['client'], 'db' => 0, 'cr' => $dcAR, 'postdate' => $head['dateid'], 'dpref' => $value['docno']];
          }
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);


          if (!empty($this->acctg)) {
            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            foreach ($this->acctg as $key3 => $value3) {
              foreach ($value3 as $key2 => $value2) {
                $this->acctg[$key3][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
              }
              $this->acctg[$key3]['editdate'] = $current_timestamp;
              $this->acctg[$key3]['editby'] = $config['params']['user'];
              $this->acctg[$key3]['encodeddate'] = $current_timestamp;
              $this->acctg[$key3]['encodedby'] = 'EXTRACTION';
              $this->acctg[$key3]['trno'] = $trno;
              $this->acctg[$key3]['db'] = round($this->acctg[$key3]['db'], 2);
              $this->acctg[$key3]['cr'] = round($this->acctg[$key3]['cr'], 2);
              $this->acctg[$key3]['fdb'] = round($this->acctg[$key3]['fdb'], 2);
              $this->acctg[$key3]['fcr'] = round($this->acctg[$key3]['fcr'], 2);

              if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg[$key3]) == 1) {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'EXTRACTION ENTRY (AcnoID: ' . $this->acctg[$key3]['acnoid'] . ')');
              } else {
                $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'EXTRACTION ENTRY FAILED');
                return ['status' => false, 'msg' => 'Extraction Failed-Error on Accounting Entry(' . $trno . ')'];
              }
            }
          }

          //postCR
          $path = 'App\Http\Classes\modules\receivable\cr';
          $config['params']['trno'] = $trno;
          $return = app($path)->posttrans($config);

          if ($return['status']) {
            $this->coreFunctions->sbcupdate("layaway", ['isok2' => 1, 'webtrno' => $trno], ['line' => $value['line'], 'branch' => $value['branch'], 'station' => $value['station'], 'docno' => $value['docno']]);
          } else {
            $this->coreFunctions->execqry("delete from cntnum where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from lahead where trno=?", "delete", [$trno]);
            $this->coreFunctions->execqry("delete from ladetail where trno=?", "delete", [$trno]);
          }

          return $return;

          // return ['status' => true];
        }
      } else {
        return ['status' => false, 'msg' => 'Error on creating new received payment'];
      }
    } catch (Exception $e) {
      $status = false;
      $msg = substr($e, 0, 1000);
      $this->coreFunctions->sbclogger('createCRS - ' . $msg);
      $this->coreFunctions->LogConsole($msg);
      if ($trno != 0) {
        $this->coreFunctions->execqry("delete from cntnum where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from lahead where trno=?", "delete", [$trno]);
        $this->coreFunctions->execqry("delete from ladetail where trno=?", "delete", [$trno]);
      }
      return ['status' => false, 'msg' => 'Error on creating new received payment.<br>' . $msg];
    }
  }

  private function isLayawayExtracted($doco, $branch, $station)
  {

    $exist = $this->coreFunctions->datareader("");
  }
} //end class
