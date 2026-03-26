<?php

namespace App\Http\Classes\modules\pos;

use Illuminate\Http\Request;
use App\Http\Requests;
use DB;
use Session;
use Exception;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\sqlquery;



class extraction
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Sales Extraction';
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

    $columns = ['dateid', 'branch', 'station', 'docno', 'client', 'clientname', 'amt', 'ext', 'amt2'];

    foreach ($columns as $key => $value) {
      $$value = $key;
    }

    $tab = [$this->gridname => ['gridcolumns' => $columns]];
    $stockbuttons = [];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // $obj[0][$this->gridname]['obj'] = 'editgrid';
    $obj[0][$this->gridname]['descriptionrow'] = [];
    $obj[0][$this->gridname]['totalfield'] = '';
    $obj[0][$this->gridname]['label'] = 'TRANSACTIONS';

    $obj[0][$this->gridname]['columns'][4]['label'] = "Customer Code";
    $obj[0][$this->gridname]['columns'][5]['label'] = "Customer Name";
    $obj[0][$this->gridname]['columns'][0]['type'] = "label";
    $obj[0][$this->gridname]['columns'][1]['type'] = "label";
    $obj[0][$this->gridname]['columns'][1]['style'] = "text-align:left;width:180px;whiteSpace: normal;min-width:180px;";
    $obj[0][$this->gridname]['columns'][2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][3]['type'] = "label";
    $obj[0][$this->gridname]['columns'][4]['type'] = "label";
    $obj[0][$this->gridname]['columns'][5]['type'] = "label";
    $obj[0][$this->gridname]['columns'][5]['style'] = "text-align:left;width:180px;whiteSpace: normal;min-width:180px;";
    $obj[0][$this->gridname]['columns'][6]['type'] = "label";
    $obj[0][$this->gridname]['columns'][6]['align'] = "text-right";
    $obj[0][$this->gridname]['columns'][6]['style'] = "text-align:right;width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][6]['label'] = "POS Amount";
    $obj[0][$this->gridname]['columns'][7]['type'] = "label";
    $obj[0][$this->gridname]['columns'][7]['align'] = "text-right";
    $obj[0][$this->gridname]['columns'][7]['style'] = "text-align:right;width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][7]['label'] = "SJS Amount";

    $obj[0][$this->gridname]['columns'][$amt2]['label'] = "OOS Amout";
    $obj[0][$this->gridname]['columns'][$amt2]['type'] = "label";
    $obj[0][$this->gridname]['columns'][$amt2]['style'] = "text-align:right;width:90px;whiteSpace: normal;min-width:90px;";
    $obj[0][$this->gridname]['columns'][$amt2]['align'] = "text-right";

    return $obj;
  }

  public function createtab2($access, $config)
  {
    $stationinfo = ['customform' => [
      'action' => 'customform',
      'lookupclass' => 'viewstationinfo',
      'addedparams' => ['client', 'dateid'],
      'totalfield' => ['amt', 'journalamt']
    ]];


    $return['STATION DETAILS'] = ['icon' => 'fa fa-envelope', 'customform' => $stationinfo];
    return $return;
  }

  public function createtabbutton($config)
  {
    $tbuttons = ['saveallentry'];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = 'CLOSING';
    $obj[0]['confirmlabel'] = 'Are you sure you want to close this sales?';
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
    data_set($col2, 'dateid.lookupclass', 'lookupjournal');
    data_set($col2, 'dateid.action', 'lookupjournal');
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
        return $this->extractsales($config);
        break;
      case 'refresh': //loading all SJS
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

  private function extractsales($config)
  { //transfer all queries to sqlquery
    $branch = $config['params']['dataparams']['client'];
    $dateid = date('Y-m-d', strtotime($config['params']['dataparams']['dateid']));
    $branchfilter  = '';
    $branchfilter2  = '';
    $head = [];
    $stock = [];
    $data = [];
    $os = [];
    $fields = ['trno', 'doc', 'docno', 'client', 'clientname', 'wh', 'dateid', 'contra', 'rem'];
    $counter = 3;

    if ($branch != '') {
      $branchfilter = " and client.client ='" . $branch . "'";
      $branchfilter2 = " and branch ='" . $branch . "'";
    }

    //checking if there are unextracted previous sales 
    $exist = $this->coreFunctions->datareader("select dateid as value from journal where date(dateid)<'" . $dateid . "' and isok2 =0 " . $branchfilter2 . "  limit 1");
    if (strlen($exist) != 0) {
      return ['status' => false, 'msg' => 'There are pending transactions from previous date. Extraction Failed.'];
    }

    //check if head and journal are tally
    $qry = "select sum(branchamt) as branchamt,sum(journalamt) as journalamt,branch,station,branchname, sum(journalreturnamt) as journalreturnamt, sum(returnamt) as returnamt,
    (select printdate from journal as jn where date(jn.dateid)='" . date('Y-m-d', strtotime($dateid)) . "' and jn.station = a.station) as printdate from (
    select 0 as branchamt,j.amt as journalamt,j.branch,j.station,client.clientname as branchname, returnamt+voidamt as journalreturnamt, 0 as returnamt
    from journal as j  left join client  on client.client = j.branch
    where date(j.dateid) ='" . date('Y-m-d', strtotime($dateid)) . "' and j.isok2 <> 1 " . $branchfilter . "
    group by j.branch,j.station,j.amt,j.branch,j.station,client.clientname,j.returnamt,j.voidamt
    union all
    select sum(b.amt) as branchamt,0 as journalamt,b.branch,b.station,client.clientname as branchname, 0, abs(if(b.amt<0,b.amt,0)) as returnamt
    from  head as b left join client  on client.client = b.branch
    where b.doc='BP'  and date(b.dateid) = '" . date('Y-m-d', strtotime($dateid)) . "' " . $branchfilter . "
    group by b.branch,b.station,b.amt,client.clientname order by branch,station) as a group by branch,station,branchname";

    $check = $this->coreFunctions->opentable($qry);
    $branchamt = 0;
    $journalamt = 0;
    foreach ($check as $m => $n) {
      $branchamt = number_format($check[$m]->branchamt, 2, '.', '');
      $journalamt = number_format($check[$m]->journalamt, 2, '.', '');
      if ($branchamt != $journalamt) {
        return ['status' => false, 'msg' => 'Transaction not yet fully synced (' . $check[$m]->branch . '). Extraction Failed for Station ' . $check[$m]->journalamt . '. Transaction: ' . $branchamt . ' - Reading: ' . $journalamt];
      }
    }

    //checking of LA vs journal - return amount only for posting of returns/void
    $journalreturnamt = 0;
    foreach ($check as $m => $n) {
      $journalreturnamt = number_format($check[$m]->journalreturnamt, 2, '.', '');

      $qry = "select ifnull(sum(amt),0) as value from (
          select (s.ext+(info.lessvat+info.sramt+info.pwdamt)) as amt
          from lastock as s left join stockinfo as info on info.trno=s.trno and info.line=s.line left join lahead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno
          where h.dateid='" . date('Y-m-d', strtotime($dateid)) . "' and c.station='" . $check[$m]->station . "' and h.doc='CM'
          union all
          select (s.ext+(info.lessvat+info.sramt+info.pwdamt)) as amt
          from glstock as s left join hstockinfo as info on info.trno=s.trno and info.line=s.line left join glhead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno
          where h.dateid='" . date('Y-m-d', strtotime($dateid)) . "' and c.station='" . $check[$m]->station . "' and h.doc='CM') as s";

      $laamt = $this->coreFunctions->datareader($qry, [], '', true);
      $laamt = number_format($laamt, 2, '.', '');
      if ($laamt != $journalreturnamt) {
        return ['status' => false, 'msg' => "Extraction Failed. Transaction: Extracted Return/Void Amount:" . $laamt . " - Return/Void Reading: " . $journalreturnamt . "<br> Variance: " . number_format($laamt - $journalreturnamt, 2)];
      }
    }

    //checking if accounts already setup
    // $exist = $this->coreFunctions->datareader("select doc as value from profile where psection='ACCT' and pvalue ='' limit 1");
    // if (strlen($exist)!=0){
    //   return['status'=>false,'msg'=>'Please complete POS Payment Setup.'];
    // }

    //extract return,void
    $rows = $config['params']['rows'];

    $systype = $this->companysetup->getsystemtype($config['params']);
    if ($systype == 'AIMSPOS') {
      switch ($config['params']['companyid']) {
        case 56: //homeworks
          $acctgentry = $this->createacctgentry_homeworks($config, $rows, 'CM');
          break;
        default:
          $acctgentry = $this->createacctgentry($config, $rows, 'CM');
          break;
      }

      if (!$acctgentry['status']) {
        return $acctgentry;
      }
    }

    recheckos:
    while ($counter <> 0) {
      //get all wh with OS items
      $qry = "select distinct wh.client from journal
      left join cntnum on cntnum.station = journal.station
      left join lahead on lahead.trno = cntnum.trno and lahead.dateid = journal.dateid
      left join client on client.clientid = lahead.branch
      left join lastock on lastock.trno = lahead.trno
      left join client as wh on wh.clientid = lastock.whid
      where date(journal.dateid) ='" . date('Y-m-d', strtotime($dateid)) . "'  and date(lahead.dateid) ='" . date('Y-m-d', strtotime($dateid)) . "' and lastock.isqty2 <>0 " . $branchfilter;

      // $this->coreFunctions->LogConsole($qry);
      $whouse = $this->coreFunctions->opentable($qry);

      //$this->coreFunctions->LogConsole($counter.' '.count($whouse));
      if (!empty($whouse)) {
        if ($this->companysetup->autoaj($config['params'])) {
          $counter = $counter - 1;
          //create AJ for each wh                          
          foreach ($whouse as $key => $v) {
            $trno = $this->othersClass->generatecntnum($config, 'cntnum', 'AJ', 'AJ', $this->companysetup->getdocumentlength($config['params']));

            if ($trno > 0) {
              $head['doc'] = 'AJ';
              $head['trno'] = $trno;
              $head['docno'] = $this->coreFunctions->getfieldvalue('cntnum', 'docno', 'trno=?', [$trno]);
              $head['dateid'] = $dateid;
              $head['contra'] = $this->coreFunctions->getfieldvalue("coa", "acno", "alias='IS1'");
              $head['wh'] = $whouse[$key]->client;
              $head['client'] = $whouse[$key]->client;
              $head['clientname'] = $this->coreFunctions->getfieldvalue("client", "clientname", "client='" . $whouse[$key]->client . "'");
              $head['rem'] = 'Extract Sales Discrepancy ' . $dateid . ' ' . $branch;

              foreach ($fields as $k) {
                $data[$k] = $head[$k];
                $data[$k] = $this->othersClass->sanitizekeyfield($k, $data[$k]);
              }
              $data['createdate'] = $this->othersClass->getCurrentTimeStamp();
              $data['createby'] = $config['params']['user'];
              $this->coreFunctions->sbcinsert('lahead', $data);
              $this->logger->sbcwritelog($trno, $config, 'AUTO CREATE', $head['docno'] . ' - ' . $head['wh']);

              //create stock entry
              $line = 1;
              $data2 = $this->coreFunctions->opentable($this->sqlquery->getOSItems($dateid, $whouse[$key]->client));

              foreach ($data2 as $key2 => $val) {
                $barcode = $this->coreFunctions->getfieldvalue('item', 'barcode', 'itemid =?', [$data2[$key2]->itemid]);
                $computedata = $this->othersClass->computestock($data2[$key2]->cost, '', $data2[$key2]->isqty2, $data2[$key2]->factor, 0, 'P', 0, 0, 1);
                $stock = [
                  'trno' => $trno,
                  'line' => $line,
                  'itemid' => $data2[$key2]->itemid,
                  'rrcost' => round($data2[$key2]->cost, 2),
                  'cost' => $computedata['amt'],
                  'rrqty' => $data2[$key2]->isqty2,
                  'qty' => $computedata['qty'],
                  'ext' => $computedata['ext'],
                  'whid' => $data2[$key2]->clientid,
                  'uom' => $data2[$key2]->uom,
                  'expiry' => $data2[$key2]->expiry,
                  'loc' => $data2[$key2]->loc
                ];

                foreach ($stock as $s => $vv) {
                  $stock[$s] = $this->othersClass->sanitizekeyfield($s, $stock[$s]);
                }

                $stock['encodeddate'] = $this->othersClass->getCurrentTimeStamp();
                $stock['encodedby'] = $config['params']['user'];
                if ($this->coreFunctions->sbcinsert('lastock', $stock) == 1) {
                  $this->logger->sbcwritelog($trno, $config, 'AUTO STOCK', 'ADD - Line:' . $line . ' Barcode:' . $barcode . ' Qty:' . $stock['rrqty'] . ' Cost:' . $computedata['amt']);
                }
                $line = $line + 1;
              }

              //postAJ
              $path = 'App\Http\Classes\modules\inventory\aj';
              $config['params']['trno'] = $trno;
              $return = app($path)->posttrans($config);
              if ($return['status']) {
                $returnupdatejs = $this->updatesjs($config, $dateid, $whouse[$key]->client);
                if (!$returnupdatejs['status']) {
                  return ['status' => false, 'msg' => $returnupdatejs['msg']];
                }
                //return ['status' => true, 'msg' => 'Successfully Created Inventory Adjustment'];
              } else {
                return $return;
              }
            } else {
              return ['status' => false, 'msg' => 'Error on creating new Adjustment'];
            }
          } // foreach wh


        } else {
          //$counter = 0;
          foreach ($whouse as $key => $v) {
            $returnupdatejs = $this->updatesjs($config, $dateid, $whouse[$key]->client);
            if (!$returnupdatejs['status']) {
              return ['status' => false, 'msg' => $returnupdatejs['msg']];
            }
          }
          $this->coreFunctions->LogConsole($counter);
          $counter = $counter - 1;

          goto recheckos;
          //return ['status' => false, 'msg' => 'There are out of stock items'];
        }
      } else {
        $counter = 0;
      }
    } //while

    //recheck if there are still OOS
    if (!$this->companysetup->autoaj($config['params'])) {
      $qry = "select distinct wh.client from journal
      left join cntnum on cntnum.station = journal.station
      left join lahead on lahead.trno = cntnum.trno and lahead.dateid = journal.dateid
      left join client on client.clientid = lahead.branch
      left join lastock on lastock.trno = lahead.trno
      left join client as wh on wh.clientid = lastock.whid
      where date(journal.dateid) ='" . date('Y-m-d', strtotime($dateid)) . "'  and date(lahead.dateid) ='" . date('Y-m-d', strtotime($dateid)) . "' and lastock.isqty2 <>0 " . $branchfilter;

      $whouse = $this->coreFunctions->opentable($qry);

      if (!empty($whouse)) {
        return ['status' => false, 'msg' => 'There are out of stock items'];
      }
    }

    //checking of LA vs journal
    $journalamt2 = 0;
    foreach ($check as $m => $n) {
      $journalamt2 = number_format($check[$m]->journalamt, 2, '.', '');

      $qry = "select ifnull(sum(amt),0) as value from (
          select (s.ext-(info.lessvat+info.sramt+info.pwdamt)) as amt
          from lastock as s left join stockinfo as info on info.trno=s.trno and info.line=s.line left join lahead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno
          where h.dateid='" . date('Y-m-d', strtotime($dateid)) . "' and c.station='" . $check[$m]->station . "' and h.doc='SJ'
          union all
          select (s.ext-(info.lessvat+info.sramt+info.pwdamt)) as amt
          from glstock as s left join hstockinfo as info on info.trno=s.trno and info.line=s.line left join glhead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno
          where h.dateid='" . date('Y-m-d', strtotime($dateid)) . "' and c.station='" . $check[$m]->station . "' and h.doc='SJ'
          union all          
          select (s.ext+(info.lessvat+info.sramt+info.pwdamt)) * -1 as amt
          from lastock as s left join stockinfo as info on info.trno=s.trno and info.line=s.line left join lahead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno
          where h.dateid='" . date('Y-m-d', strtotime($dateid)) . "' and c.station='" . $check[$m]->station . "' and h.doc='CM'
          union all
          select (s.ext+(info.lessvat+info.sramt+info.pwdamt)) * -1 as amt
          from glstock as s left join hstockinfo as info on info.trno=s.trno and info.line=s.line left join glhead as h on h.trno=s.trno left join cntnum as c on c.trno=h.trno
          where h.dateid='" . date('Y-m-d', strtotime($dateid)) . "' and c.station='" . $check[$m]->station . "' and h.doc='CM') as s";

      $laamt = $this->coreFunctions->datareader($qry, [], '', true);
      $laamt = number_format($laamt, 2, '.', '');
      if ($laamt != $journalamt2) {
        return ['status' => false, 'msg' => "Extraction Failed. Transaction: Extracted Amount: " . $laamt . " - Reading: " . $journalamt2 . "<br> Variance: " . number_format($laamt - $journalamt2, 2)];
      }
    }


    $systype = $this->companysetup->getsystemtype($config['params']);
    if ($systype == 'AIMSPOS') {
      //create acctg entries
      $rows = $config['params']['rows'];

      switch ($config['params']['companyid']) {
        case 56: //homeworks
          $this->recomputenetap($config, $rows, 'CM');
          $acctgentry = $this->createacctgentry_homeworks($config, $rows, 'SJ');
          break;
        default:
          $acctgentry = $this->createacctgentry($config, $rows, 'SJ');
          break;
      }

      if (!$acctgentry['status']) {
        return $acctgentry;
      }
    } else {
      $rows = $config['params']['rows'];
      foreach ($rows as $key) {
        $config['params']['trno'] = $key['trno'];
        $this->coreFunctions->LogConsole($config['params']['trno']);
        $return = $this->othersClass->posttranstock($config);
        if (!$return['status']) {
          return ['status' => false, 'msg' => $return['msg'] . "(" . $key['trno'] . ")"];
        }
      }
    }

    $data = $this->coreFunctions->opentable($this->selectqry($branchfilter, $dateid));
    // $this->coreFunctions->LogConsole("update journal set isok2 =1 where dateid='".$dateid."' ".$branchfilter2);

    //tagging of isok2
    if (floatval(count($data)) == '0') {
      $eod = $this->othersClass->getCurrentTimeStamp();
      $qry = "update journal set isok2 =1, eod='" . $eod . "' where date(dateid)='" . date('Y-m-d', strtotime($dateid)) . "' " . $branchfilter2;
      if ($this->coreFunctions->execqry($qry, 'update')) {
        $this->logger->sbcwritelog('0', $config, 'JOURNAL', 'TAGGING JOURNAL- ' . $qry);
        return ['status' => true, 'msg' => 'Extraction Success', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
      } else {
        return ['status' => false, 'msg' => 'Extraction Failed', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
      }
    } else {
      $this->logger->sbcwritelog('0', $config, 'JOURNAL', 'Extraction Failed .' . $branch . ' ' . date('Y-m-d', strtotime($dateid)) . '. Count: ' . floatval(count($data)));
      return ['status' => false, 'msg' => 'Extraction Failed', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
    }
  }
  private function selectqry($branchfilter, $dateid)
  {
    return  "select cntnum.doc,cntnum.trno,lahead.dateid,lahead.docno,lahead.client,lahead.clientname,cntnum.station,client.clientid as branchid,client.client as branch,
    sum(head.amt) as amt, ifnull((select sum(s.ext-abs(info.lessvat)-abs(info.sramt)-abs(info.pwdamt)) from lastock as s left join stockinfo as info on info.trno=s.trno and info.line=s.line where s.trno=lahead.trno),0) as ext,
    ifnull((select round(sum(s.isqty2*s.amt),2) from lastock as s where s.trno=lahead.trno),0) as amt2
    from lahead
    left join head on head.webtrno = lahead.trno
    left join client on client.clientid = lahead.branch
    left join cntnum on cntnum.trno = lahead.trno
    where date(lahead.dateid) ='" . date('Y-m-d', strtotime($dateid)) . "' " . $branchfilter . "
    group by cntnum.trno,cntnum.doc,lahead.docno,lahead.dateid,lahead.client,lahead.clientname,cntnum.station,client.client,client.clientid,lahead.docno,lahead.trno 
    order by cntnum.doc";

    // "select cntnum.doc,cntnum.trno,lahead.dateid,lahead.docno,lahead.client,lahead.clientname,cntnum.station,client.clientid as branchid,client.client as branch,sum(head.amt) as amt from journal 
    // left join cntnum on cntnum.station = journal.station
    // left join lahead on lahead.trno = cntnum.trno and lahead.dateid = journal.dateid	
    //     left join head on head.webtrno = lahead.trno
    //     left join client on client.clientid = lahead.branch    
    //     where date(journal.dateid) ='".date('Y-m-d', strtotime($dateid))."'  and date(lahead.dateid) ='".date('Y-m-d', strtotime($dateid))."'  ". $branchfilter . "
    //     group by cntnum.trno,cntnum.doc,lahead.docno,lahead.dateid,lahead.client,lahead.clientname,cntnum.station,client.client,client.clientid order by lahead.docno";

  }

  private function loaddata($config, $branch, $dateid)
  {
    $branchfilter = "";
    if ($branch != "") {
      $branchfilter = " and client.client ='" . $branch . "'";
    }

    $qry = $this->selectqry($branchfilter, $dateid);
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => $data]];
  }


  private function updatesjs($config, $dateid, $wh)
  {
    $status = true;
    $msg = '';

    $barcode = '';

    try {
      $data = $this->coreFunctions->opentable($this->sqlquery->getOSItems($dateid, $wh));
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      foreach ($data as $key => $v) {
        $barcode = $data[$key]->barcode;
        if ($data[$key]->factor == null) {
          return ['status' => false, 'msg' => 'Invalid UOM factor for item ' . $barcode];
        }
        $onhand = $this->sqlquery->getcurrentbal($data[$key]->barcode, $wh, $data[$key]->loc, $data[$key]->expiry);
        $computedata = $this->othersClass->computestock($data[$key]->isamt, '', $data[$key]->isqty2, $data[$key]->factor, 0, 'P', 0, 0, 1);
        $this->coreFunctions->LogConsole($data[$key]->barcode . '-' . $onhand . '-' . $computedata['qty']);
        if ($onhand != 0) {
          if ($onhand >= $computedata['qty']) { //with balance
            $isqty  = $data[$key]->isqty + $data[$key]->isqty2;
            $iss = $data[$key]->iss + $computedata['qty'];
            $computeext = $this->othersClass->computestock($data[$key]->isamt, $data[$key]->disc, $isqty, $data[$key]->factor, 0, 'P', 0, 0, 1);
            $ext = number_format($computeext['ext'], 2, '.', '');
            $pending = 0;
            $this->coreFunctions->sbcupdate('lastock', ['isqty' => $isqty, 'iss' => $iss, 'ext' => $ext, 'isqty2' => $pending, 'editby' => 'EXTRACTION', 'editdate' => $current_timestamp], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
            $cost = $this->othersClass->computecosting($data[$key]->itemid, $data[$key]->clientid, $data[$key]->loc, $data[$key]->expiry, $data[$key]->trno, $data[$key]->line, $iss, 'SJ', $config['params']['companyid']);
            if ($cost != -1) {
              $this->coreFunctions->sbcupdate('lastock', ['cost' => $cost], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
              $this->logger->sbcwritelog($data[$key]->trno, $config, 'EXTRACTION', 'COST - Line:' . $data[$key]->line . ' barcode:' . $data[$key]->barcode . ' Cost:' . $cost);
            } else {
              $onhand = $this->sqlquery->getcurrentbal($data[$key]->barcode, $wh, $data[$key]->loc, $data[$key]->expiry);
              $isqty = $onhand * $data[$key]->factor;
              $pending = $data[$key]->original_qty - $isqty;
              $computeext = $this->othersClass->computestock($data[$key]->isamt, $data[$key]->disc, $isqty, $data[$key]->factor, 0, 'P', 0, 0, 1);
              $this->coreFunctions->sbcupdate('lastock', ['isqty' => $isqty, 'iss' => $onhand, 'isqty2' => $pending, 'ext' => number_format($computeext['ext'], 2, '.', ''), 'editby' => 'EXTRACTION', 'editdate' => $current_timestamp], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
              $cost = $this->othersClass->computecosting($data[$key]->itemid, $data[$key]->clientid, $data[$key]->loc, $data[$key]->expiry, $data[$key]->trno, $data[$key]->line, $iss, 'SJ', $config['params']['companyid']);
              $this->coreFunctions->sbcupdate('lastock', ['cost' => $cost], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
              $this->logger->sbcwritelog($data[$key]->trno, $config, 'EXTRACTION', 'STOCK - Line:' . $data[$key]->line . ' barcode:' . $data[$key]->barcode . ' Qty' . $onhand . ' Cost: ' . $cost);
            }
          } else { //bal not sufficient
            $isqty = $data[$key]->isqty + ($onhand / $data[$key]->factor);
            $iss = $data[$key]->iss + $onhand;
            $computeext = $this->othersClass->computestock($data[$key]->isamt, $data[$key]->disc, $isqty, $data[$key]->factor, 0, 'P', 0, 0, 1);
            $ext = number_format($computeext['ext'], 2, '.', '');
            $pending = $data[$key]->original_qty - $isqty;
            $this->coreFunctions->sbcupdate('lastock', ['isqty' => $isqty, 'iss' => $iss, 'ext' => $ext, 'isqty2' => $pending, 'editby' => 'EXTRACTION', 'editdate' => $current_timestamp], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
            $cost = $this->othersClass->computecosting($data[$key]->itemid, $data[$key]->clientid, $data[$key]->loc, $data[$key]->expiry, $data[$key]->trno, $data[$key]->line, $iss, 'SJ', $config['params']['companyid']);
            if ($cost != -1) {
              $this->coreFunctions->sbcupdate('lastock', ['cost' => $cost], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
              $this->logger->sbcwritelog($data[$key]->trno, $config, 'EXTRACTION', 'COST - Line:' . $data[$key]->line . ' barcode:' . $data[$key]->barcode . ' Cost:' . $cost);
            } else {
              $onhand = $this->sqlquery->getcurrentbal($data[$key]->barcode, $wh, $data[$key]->loc, $data[$key]->expiry);
              $isqty = $onhand * $data[$key]->factor;
              $pending = $data[$key]->original_qty - $isqty;
              $computeext = $this->othersClass->computestock($data[$key]->isamt, $data[$key]->disc, $isqty, $data[$key]->factor, 0, 'P', 0, 0, 1);
              $this->coreFunctions->sbcupdate('lastock', ['isqty' => $isqty, 'iss' => $onhand, 'isqty2' => $pending, 'ext' => number_format($computeext['ext'], 2, '.', ''), 'editby' => 'EXTRACTION', 'editdate' => $current_timestamp], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
              $cost = $this->othersClass->computecosting($data[$key]->itemid, $data[$key]->clientid, $data[$key]->loc, $data[$key]->expiry, $data[$key]->trno, $data[$key]->line, $iss, 'SJ', $config['params']['companyid']);
              $this->coreFunctions->sbcupdate('lastock', ['cost' => $cost], ['trno' => $data[$key]->trno, 'line' => $data[$key]->line]);
              $this->logger->sbcwritelog($data[$key]->trno, $config, 'EXTRACTION', 'STOCK - Line:' . $data[$key]->line . ' barcode:' . $data[$key]->barcode . ' Qty' . $onhand . ' Cost: ' . $cost);
            }
          }
        }
      }
    } catch (Exception $e) {
      $msg = $barcode . ' - ' . substr($e, 0, 1000);
      $status = false;
    }

    return ['status' => $status, 'msg' => $msg];
  }

  private function createacctgentry($config, $rows, $doc)
  {
    $blnextract = true;

    foreach ($rows as $key) {
      $dcVat = 0;
      $dcTvat = 0;
      $dcAmt = 0;
      $dcSRAmt = 0;
      $dcPWDAmt = 0;
      $dcDisc = 0;
      $dclessvat = 0;
      $dcext = 0;
      $dcSales = 0;
      $this->acctg = [];

      $qry = "select cntnum.doc,cntnum.trno,lahead.dateid,lahead.docno,lahead.client,lahead.clientname,cntnum.station,client.clientid as branchid,client.client as branch,
        round(head.amt,2) as amt,head.cash,head.cheque,head.voucher,head.card,head.nvat,head.vatamt,head.vatex,head.cr,head.sramt as discsr,head.discamt,head.pwdamt,head.empdisc,head.debit,
        head.smac,head.onlinedeals,head.vipdisc,head.smacdisc,head.oddisc,head.eplus,head.terminalid,head.voucherno,head.checktype,head.gcamt,head.lessvat,head.lp,
        c.clientid,head.trno as storetrno,head.bref from lahead
        left join head on head.webtrno = lahead.trno
        left join client on client.clientid = lahead.branch
        left join client as c on c.client = lahead.client
        left join cntnum on cntnum.trno = lahead.trno
        where cntnum.doc = '" . $doc . "' and lahead.dateid ='" . date('Y-m-d', strtotime($key['dateid'])) . "' and lahead.trno = " . $key['trno'];
      //group by cntnum.trno,cntnum.doc,lahead.docno,lahead.dateid,lahead.client,lahead.clientname,cntnum.station,client.client,client.clientid";
      $config['params']['trno'] = $key['trno'];
      $data = $this->coreFunctions->opentable($qry);
      if (!empty($data)) {
        $this->coreFunctions->execqry("delete from ladetail where trno = " . $key['trno']);
        foreach ($data as $k => $val) {
          //cash
          if ($data[$k]->cash != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CSH']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for cash transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->cash < 0 ? 0 : abs($data[$k]->cash), 'cr' => $data[$k]->cash > 0 ? 0 : abs($data[$k]->cash), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end cash

          //cr (ar sales)
          if ($data[$k]->cr != 0) {
            if ($data[$k]->bref == 'V') {
              $refsi = $this->coreFunctions->datareader("select distinct ref as value from stock left join head on head.trno = stock.trno and head.station = stock.station where head.webtrno = " . $key['trno'] . " limit 1");
              $sitrno =  $this->coreFunctions->datareader("select trno as value from head where docno ='" . $refsi . "' and station ='" . $data[$k]->station . "'");
              $arref = explode("~", $this->coreFunctions->datareader("select concat(trno,'~',line) as value from gldetail where storetrno = " . $sitrno . " and station ='" . $data[$k]->station . "'"));
              $ref = $this->coreFunctions->getfieldvalue('cntnum', 'docno', 'trno=?', [$arref[0]]);
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'AR']);
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => abs($data[$k]->cr), 'db' => 0, 'postdate' => $data[$k]->dateid, 'storetrno' => $data[$k]->storetrno, 'station' => $data[$k]->station, 'refx' => $arref[0], 'linex' => $arref[1], 'ref' => $ref];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'AR']);
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->cr < 0 ? 0 : abs($data[$k]->cr), 'cr' => $data[$k]->cr > 0 ? 0 : abs($data[$k]->cr), 'postdate' => $data[$k]->dateid, 'storetrno' => $data[$k]->storetrno, 'station' => $data[$k]->station];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end cr


          //check
          if ($data[$k]->cheque != 0) {
            // if ($data[$k]->checktype != '') {
            //   $chk = str_split($data[$k]->checktype, ",");
            //   $chkt = "";
            //   $suppid = 0;
            //   $supp = '';
            //   $checkamt = 0;
            //   $variance = 0;
            //   for ($i = 0; $i <= count($chk) - 1; $i++) {
            //     $c = str_split($chk[$i], "~");
            //     $chkt = $c[0];
            //     $suppid = $this->coreFunctions->getfieldvalue('checktypes', 'clientid', 'type=?', [$chkt]);
            //     $supp = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$suppid]);
            //     $acnoid = $this->coreFunctions->getfieldvalue('checktypes', 'acnoid', 'type=?', [$chkt]);

            //     if ($acnoid == 0) {
            //       return ['status' => false, 'msg' => 'Please setup account for ' . $chkt . ' payment type'];
            //     }

            //     if ($suppid == 0) {
            //       $suppid = $data[$k]->clientid;
            //       $supp = $data[$k]->client;
            //     }

            //     $entry = ['acnoid' => $acnoid, 'client' => $supp, 'db' => $c[1] < 0 ? 0 : abs($c[1]), 'cr' => $c[1] > 0 ? 0 : abs($c[1]), 'postdate' => $data[$k]->dateid];
            //     $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            //     $checkamt = $checkamt + $c[0];
            //   }

            //   if ($checkamt != $data[$key]->cheque) {
            //     $variance = $checkamt != $data[$key]->cheque;
            //     $entry = ['acnoid' => $acnoid, 'client' => $supp, 'db' => $variance > 0 ? 0 : abs($variance), 'cr' => $variance < 0 ? 0 : abs($variance), 'postdate' => $data[$k]->dateid];
            //     $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            //   }
            // } else {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CHK']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for check transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->cheque < 0 ? 0 : abs($data[$k]->cheque), 'cr' => $data[$k]->cheque > 0 ? 0 : abs($data[$k]->cheque), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
            //}
          } //end cheque

          //card
          if ($data[$k]->card != 0) {
            if ($data[$k]->terminalid != '') {
              $j = explode(",", $data[$k]->terminalid);
              $terminal = "";
              for ($i = 0; $i <= count($j) - 1; $i++) {
                $chargeamt = 0;
                $ewtamt = 0;
                $card = 0;
                if (substr($j[$i], 0, 1) == 'C') {
                  $c = explode("~", $j[$i]);
                  $terminal = substr($c[0], 1, strlen($c[0]));
                  if ($terminal == '') {
                    $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CRD']);
                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for card transactions. (' . $key['trno'] . ' - ' . $data[$k]->terminalid . ')'];
                    } else {
                      $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->card < 0 ? 0 : abs($c[1]), 'cr' => $data[$k]->card > 0 ? 0 : abs($c[1]), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }
                  } else {
                    $acnoid = $this->coreFunctions->getfieldvalue('branchbank', 'acnoid', 'isinactive =0 and clientid =? and terminalid=?', [$data[$k]->branchid, $terminal]);
                    $charge = $this->coreFunctions->getfieldvalue('bankcharges', 'rate', 'inactive =0 and type =? and terminalid=?', [$c[2], $terminal]);
                    $ewt = $this->coreFunctions->getfieldvalue('bankcharges', 'ewt', 'inactive =0 and type =? and terminalid=?', [$c[2], $terminal]);

                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for card transactions. (' . $key['trno'] . ' - ' . $data[$k]->terminalid . ')'];
                    }

                    if ($charge != "") {
                      $bcacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias ='BC'");
                      if ($bcacnoid == 0) {
                        return ['status' => false, 'msg' => 'Please setup account for Bank Charges(BC)'];
                      }
                      $chargeamt = round(floatval($c[1]) - $this->othersClass->Discount(floatval($c[1]), $charge), 2);

                      if ($ewt != "") {
                        $ewtamt = round(floatval($c[1]) - $this->othersClass->Discount(floatval($c[1]), $ewt), 2);
                      }
                      $card = floatval($c[1]) - $chargeamt - $ewtamt;
                    } else {
                      $card = floatval($c[1]);
                    }

                    $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $card < 0 ? 0 : abs($card), 'cr' => $card > 0 ? 0 : abs($card), 'postdate' => $data[$k]->dateid];
                    $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                    if ($chargeamt != 0) {
                      $entry = ['acnoid' => $bcacnoid, 'client' => $data[$k]->client, 'db' => $chargeamt < 0 ? 0 : abs($chargeamt), 'cr' => $chargeamt > 0 ? 0 : abs($chargeamt), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }

                    if ($ewtamt != 0) {
                      $ewtacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias ='CWT'");
                      $entry = ['acnoid' => $ewtacnoid, 'client' => $data[$k]->client, 'db' => $ewtamt < 0 ? 0 : abs($ewtamt), 'cr' => $ewtamt > 0 ? 0 : abs($ewtamt), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }
                  }
                } // end if C
              }
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CRD']);
              if ($acnoid == 0) {
                return ['status' => false, 'msg' => 'Please setup account for card transactions. (' . $key['trno'] . ' - ' . $data[$k]->terminalid . ')'];
              } else {
                $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->card < 0 ? 0 : abs($data[$k]->card), 'cr' => $data[$k]->card > 0 ? 0 : abs($data[$k]->card), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }
            }
          } //end card

          //voucher
          if ($data[$k]->voucher != 0) { //giftchecks
            // if ($data[$k]->voucherno != "") {
            //   // $j = str_split($data[$k]->voucherno,",");
            //   // for($i = 0; $i <= count($j)-1; $i++){

            //   // }
            // } 
            if ($data[$k]->checktype != '') {
              $chk = explode(",", $data[$k]->checktype); //str_split($data[$k]->checktype, ",");
              $chkt = "";
              $suppid = 0;
              $supp = '';
              $checkamt = 0;
              $variance = 0;
              for ($i = 0; $i <= count($chk) - 1; $i++) {
                $c = explode("~", $chk[$i]); //str_split($chk[$i], "~");
                $chkt = $c[0];
                $suppid = $this->coreFunctions->getfieldvalue('checktypes', 'clientid', 'type=?', [$chkt]);
                $supp = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$suppid]);
                $acnoid = $this->coreFunctions->getfieldvalue('checktypes', 'acnoid', 'type=?', [$chkt]);

                if ($acnoid == 0) {
                  return ['status' => false, 'msg' => 'Please setup account for ' . $chkt . ' payment type'];
                }

                if ($suppid == 0) {
                  $suppid = $data[$k]->clientid;
                  $supp = $data[$k]->client;
                }

                $entry = ['acnoid' => $acnoid, 'client' => $supp, 'db' => $c[1] < 0 ? 0 : abs($c[1]), 'cr' => $c[1] > 0 ? 0 : abs($c[1]), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                $checkamt = $checkamt + $c[1];
              }

              if ($checkamt != $data[$k]->voucher) {
                $variance = $checkamt - $data[$k]->voucher;
                $entry = ['acnoid' => $acnoid, 'client' => $supp, 'db' => $variance > 0 ? 0 : abs($variance), 'cr' => $variance < 0 ? 0 : abs($variance), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'VC']);
              if ($acnoid == 0) {
                return ['status' => false, 'msg' => 'Please setup account for voucher transactions'];
              } else {
                $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->voucher < 0 ? 0 : abs($data[$k]->voucher), 'cr' => $data[$k]->voucher > 0 ? 0 : abs($data[$k]->voucher), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }
            }
          } //end voucher

          //lp
          if ($data[$k]->lp != 0) { //lp
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'LP']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Loyalty Points transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->lp < 0 ? 0 : abs($data[$k]->lp), 'cr' => $data[$k]->lp > 0 ? 0 : abs($data[$k]->lp), 'postdate' => $data[$k]->dateid, 'station' => $data[$k]->station];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end lp

          //debit
          if ($data[$k]->debit != 0) {
            if ($data[$k]->terminalid != '') {
              $j = explode(",", $data[$k]->terminalid);
              $terminal = "";
              for ($i = 0; $i <= count($j) - 1; $i++) {
                $chargeamt = 0;
                $ewtamt = 0;
                $card = 0;
                if (substr($j[$i], 0, 1) == 'D') {
                  $c = explode("~", $j[$i]);
                  $terminal = substr($c[0], 1, strlen($c[0]));
                  if ($terminal == '') {
                    $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'DEBIT']);
                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for debit transactions'];
                    } else {
                      $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->debit < 0 ? 0 : abs($c[1]), 'cr' => $data[$k]->debit > 0 ? 0 : abs($c[1]), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }
                  } else {
                    $acnoid = $this->coreFunctions->getfieldvalue('branchbank', 'acnoid', 'isinactive =0 and clientid =? and terminalid=?', [$data[$k]->branchid, $terminal]);
                    $charge = $this->coreFunctions->getfieldvalue('bankcharges', 'rate', 'inactive =0 and type =? and terminalid=?', [$c[2], $terminal]);
                    $ewt = $this->coreFunctions->getfieldvalue('bankcharges', 'ewt', 'inactive =0 and type =? and terminalid=?', [$c[2], $terminal]);

                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for debit transactions terminalid - ' . $terminal];
                    }

                    if ($charge != "") {
                      $bcacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias ='BC'");
                      if ($bcacnoid == 0) {
                        return ['status' => false, 'msg' => 'Please setup account for Bank Charges(BC)'];
                      }
                      $chargeamt = round(floatval($c[1]) - $this->othersClass->Discount(floatval($c[1]), $charge), 2);

                      if ($ewt != "") {
                        $ewtamt = round(floatval($c[1]) - $this->othersClass->Discount(floatval($c[1]), $ewt), 2);
                      }
                      $card = floatval($c[1]) - $chargeamt - $ewtamt;
                    } else {
                      $card = floatval($c[1]);
                    }

                    $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $card < 0 ? 0 : abs($card), 'cr' => $card > 0 ? 0 : abs($card), 'postdate' => $data[$k]->dateid];
                    $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                    if ($chargeamt != 0) {
                      $entry = ['acnoid' => $bcacnoid, 'client' => $data[$k]->client, 'db' => $chargeamt < 0 ? 0 : abs($chargeamt), 'cr' => $chargeamt > 0 ? 0 : abs($chargeamt), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }

                    if ($ewtamt != 0) {
                      $ewtacnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias ='CWT'");
                      $entry = ['acnoid' => $ewtacnoid, 'client' => $data[$k]->client, 'db' => $ewtamt < 0 ? 0 : abs($ewtamt), 'cr' => $ewtamt > 0 ? 0 : abs($ewtamt), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }
                  }
                } // end if D
              }
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'DEBIT']);
              if ($acnoid == 0) {
                return ['status' => false, 'msg' => 'Please setup account for debit transactions'];
              } else {
                $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->debit < 0 ? 0 : abs($data[$k]->debit), 'cr' => $data[$k]->debit > 0 ? 0 : abs($data[$k]->debit), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }
            }
          } //end debit

          //smac
          if ($data[$k]->smac != 0) { //smac
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SMAC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for SMAC transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->smac < 0 ? 0 : abs($data[$k]->smac), 'cr' => $data[$k]->smac > 0 ? 0 : abs($data[$k]->smac), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end smac

          //eplus
          if ($data[$k]->eplus != 0) { //eplus
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'EPLUS']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for E-PLUS transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->eplus < 0 ? 0 : abs($data[$k]->eplus), 'cr' => $data[$k]->eplus > 0 ? 0 : abs($data[$k]->eplus), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end eplus

          //onlinedeals
          if ($data[$k]->onlinedeals != 0) { //onlinedeals
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'ONLINE']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for online deals transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->onlinedeals < 0 ? 0 : abs($data[$k]->onlinedeals), 'cr' => $data[$k]->onlinedeals > 0 ? 0 : abs($data[$k]->onlinedeals), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end onlinedeals

          //discamt
          if ($data[$k]->discamt != 0) { //discamt
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'DISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales Discounts.'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->discamt < 0 ? 0 : abs($data[$k]->discamt), 'cr' => $data[$k]->discamt > 0 ? 0 : abs($data[$k]->discamt), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end disc

          //smacdisc
          if ($data[$k]->smacdisc != 0) { //smacdisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SMACDISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for SMAC Discounts.'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->smacdisc < 0 ? 0 : abs($data[$k]->smacdisc), 'cr' => $data[$k]->smacdisc > 0 ? 0 : abs($data[$k]->smacdisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end smacdisc

          //vipdisc
          if ($data[$k]->vipdisc != 0) { //vipdisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'VIPDISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for VIP transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->vipdisc < 0 ? 0 : abs($data[$k]->vipdisc), 'cr' => $data[$k]->vipdisc > 0 ? 0 : abs($data[$k]->vipdisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end vipdisc

          //oddisc
          if ($data[$k]->oddisc != 0) { //oddisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'ODISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for online deals discount.'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->oddisc < 0 ? 0 : abs($data[$k]->oddisc), 'cr' => $data[$k]->oddisc > 0 ? 0 : abs($data[$k]->oddisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end odisc

          //discsr
          if ($data[$k]->discsr != 0) { //discsr
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Senior Discount'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->discsr < 0 ? 0 : abs($data[$k]->discsr), 'cr' => $data[$k]->discsr > 0 ? 0 : abs($data[$k]->discsr), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end discsr

          //pwdamt
          if ($data[$k]->pwdamt != 0) { //pwdamt
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'PWD']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for PWD transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->pwdamt < 0 ? 0 : abs($data[$k]->pwdamt), 'cr' => $data[$k]->pwdamt > 0 ? 0 : abs($data[$k]->pwdamt), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end pwdamt

          //empdisc
          if ($data[$k]->empdisc != 0) { //empdisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'EMP']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Employee discount'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->empdisc < 0 ? 0 : abs($data[$k]->empdisc), 'cr' => $data[$k]->empdisc > 0 ? 0 : abs($data[$k]->empdisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end empdisc

          //vatamt
          if ($data[$k]->vatamt != 0) { //vatamt
            $dcTvat = $dcTvat + $data[$k]->vatamt;
          } //end vatamt

          //gcamt
          if ($data[$k]->gcamt != 0) { //gcamt
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'GC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for GC transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => $data[$k]->gcamt < 0 ? 0 : abs($data[$k]->gcamt), 'db' => $data[$k]->gcamt > 0 ? 0 : abs($data[$k]->gcamt), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end gcamt

          $dcAmt = $dcAmt + $data[$k]->amt;
          $dcDisc = $dcDisc + ($data[$k]->discamt + $data[$k]->empdisc  + $data[$k]->vipdisc);
          $dcSRAmt = $dcSRAmt + $data[$k]->discsr;
          $dcPWDAmt = $dcPWDAmt + $data[$k]->pwdamt;
        } // end for data

        //compute sales entry
        $dclessvat = $this->coreFunctions->datareader("select ifnull(sum(stock.lessvat),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line
            where lastock.trno = " . $key['trno']);
        $dcext = $this->coreFunctions->datareader("select ifnull(sum(lastock.ext+abs(stock.discamt)),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line 
        left join item on item.itemid = lastock.itemid where item.barcode not in ('*') and  lastock.trno = " . $key['trno']); //lastock.itemid not in (9274,9275,9276) and - for reserved codes
        $subtotal = $this->coreFunctions->datareader("select ifnull(sum(lastock.ext),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line 
        left join item on item.itemid = lastock.itemid where item.barcode in ('*') and  lastock.trno = " . $key['trno']);

        $this->coreFunctions->LogConsole($dcext);
        $this->coreFunctions->LogConsole($subtotal);
        $this->coreFunctions->LogConsole($dclessvat);

        //return['status'=>false,'msg'=>'Please setup account for Sales'];

        if ($dcext != 0) {
          $dcSales = $dcSales + (abs($dcext) - abs($subtotal) - abs($dclessvat) - abs($dcTvat));
        }

        $dclessvat = 0;
        $dcext = 0;

        if ($dcSales != 0) {
          if ($key['doc'] == 'CM') {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SR']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales Return'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => abs($dcSales), 'cr' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } else {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SA']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => abs($dcSales), 'db' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        $dcAmt = round(abs($dcAmt), 2);
        $dcDisc = round(abs($dcDisc), 2);
        $dcSRAmt = round(abs($dcSRAmt), 2);
        $dcPWDAmt = round(abs($dcPWDAmt), 2);
        $dcSales = round($dcSales, 2);
        $dcTvat = round(abs($dcTvat), 2);

        $this->coreFunctions->LogConsole('Amt:' . $dcAmt);
        $this->coreFunctions->LogConsole('Disc:' . $dcDisc);
        $this->coreFunctions->LogConsole('SRAmt:' . $dcSRAmt);
        $this->coreFunctions->LogConsole('PWDAmt:' . $dcPWDAmt);
        $this->coreFunctions->LogConsole('Sales:' . $dcSales);
        $this->coreFunctions->LogConsole('VAT:' . $dcTvat);
        // $this->coreFunctions->LogConsole(abs(($dcAmt + $dcDisc + $dcSRAmt + $dcPWDAmt) - ($dcTvat + $dcSales)));
        //checking for 0.01 discrepancy
        if ((($dcAmt + $dcDisc + $dcSRAmt + $dcPWDAmt) - ($dcTvat + $dcSales)) < 0) {
          $dcTvat = $dcTvat - abs(($dcAmt + $dcDisc + $dcSRAmt + $dcPWDAmt) - ($dcTvat + $dcSales));
        } else {
          $dcTvat = $dcTvat + abs(($dcAmt + $dcDisc + $dcSRAmt + $dcPWDAmt) - ($dcTvat + $dcSales));
        }

        //outputvat
        if ($dcTvat != 0) {
          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='TX2'");
          if ($acnoid == 0) {
            return ['status' => false, 'msg' => 'Please setup account for Output VAT'];
          } else {
            if ($key['doc'] == 'CM') {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $dcTvat, 'cr' =>  0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => 0, 'cr' => $dcTvat, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        if ($key['doc'] == 'CM') {
          $cost = $this->coreFunctions->getfieldvalue("lastock", "sum(cost*qty)", "trno=?", [$key['trno']]);
        } else {
          $cost = $this->coreFunctions->getfieldvalue("lastock", "sum(cost*iss)", "trno=?", [$key['trno']]);
        }

        if ($cost != 0) { //cogs & inv
          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG1']);
          $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $key['doc'] == 'CM' ? 0 : $cost, 'cr' => $key['doc'] == 'CM' ? $cost : 0, 'postdate' => $data[$k]->dateid];
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['IN1']);
          $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $key['doc'] == 'CM' ? $cost : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $cost, 'postdate' => $data[$k]->dateid];
          $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        }

        $config['params']['trno'] = $key['trno'];
        if (!empty($this->acctg)) {
          $current_timestamp = $this->othersClass->getCurrentTimeStamp();
          foreach ($this->acctg as $key3 => $value) {
            foreach ($value as $key2 => $value2) {
              $this->acctg[$key3][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
            }
            $this->acctg[$key3]['editdate'] = $current_timestamp;
            $this->acctg[$key3]['editby'] = $config['params']['user'];
            $this->acctg[$key3]['encodeddate'] = $current_timestamp;
            $this->acctg[$key3]['encodedby'] = 'EXTRACTION';
            $this->acctg[$key3]['trno'] = $key['trno'];
            $this->acctg[$key3]['db'] = round($this->acctg[$key3]['db'], 2);
            $this->acctg[$key3]['cr'] = round($this->acctg[$key3]['cr'], 2);
            $this->acctg[$key3]['fdb'] = round($this->acctg[$key3]['fdb'], 2);
            $this->acctg[$key3]['fcr'] = round($this->acctg[$key3]['fcr'], 2);

            if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg[$key3]) == 1) {
              $this->logger->sbcwritelog($key['trno'], $config, 'DETAILS', 'EXTRACT SALES ENTRY');
              $this->coreFunctions->LogConsole($this->acctg[$key3]['refx']);
              if ($this->acctg[$key3]['refx'] != 0) {
                $acno = $this->coreFunctions->getfieldvalue('coa', 'acno', 'acnoid =?', [$this->acctg[$key3]['acnoid']]);
                if (!$this->sqlquery->setupdatebal($this->acctg[$key3]['refx'], $this->acctg[$key3]['linex'], $acno, $config)) {
                  $this->coreFunctions->sbcupdate('ladetail', ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $key['trno'], 'line' => $this->acctg[$key3]['line']]);
                  return ['status' => false, 'msg' => 'Extraction Failed-Error on Applying Payment'];
                  $this->sqlquery->setupdatebal($this->acctg[$key3]['refx'], $this->acctg[$key3]['linex'], $acno, $config);
                }
              }
            } else {
              $this->logger->sbcwritelog($key['trno'], $config, 'DETAILS', 'EXTRACT SALES ENTRY FAILED');
              return ['status' => false, 'msg' => 'Extraction Failed-Error on Accounting Entry(' . $key['trno'] . ')'];
            }
          }
        }

        $return = $this->othersClass->posttranstock($config);
        if (!$return['status']) {
          return ['status' => false, 'msg' => $return['msg'] . "(" . $key['trno'] . ")"];
        }
      } //end empty data  

    } //end for rows
    return ['status' => true];
  } // end function

  private function recomputenetap($config, $rows, $doc)
  {

    foreach ($rows as $key) {
      $qry = "select s.trno, s.line, s.iss, s.qty, s.cost, s.ext, info.comm1, info.comm2, info.comap2, info.comap, item.channel, info.cardcharge, info.netap
        from lastock as s left join stockinfo as info on info.trno=s.trno and info.line=s.line
        left join item on item.itemid=s.itemid where s.trno=" . $key['trno'];

      $data = $this->coreFunctions->opentable($qry);

      foreach ($data as $key2 => $value) {
        $comap1 = 0;
        $comap2 = 0;

        if ($value->channel == 'CONCESSION') {
          if ($value->comm1 != 0) {
            $commrate = $value->comm1;
            $commamt = 0;

            if (abs($value->ext) > 0) {
              $commamt = number_format(abs($value->ext) *  ($commrate / 100), 2, '.', '') * -1;
              $comap1 = abs($value->ext) + $commamt;
            }
          }
        } else {
          $defaultcost = $value->cost;
          if ($value->iss > 0) {
            if (abs($value->ext) > 0) {
              $comap1 = number_format($value->iss *  $defaultcost, 2, '.', '');
            }
          }
          if ($value->qty > 0) {
            $comap1 = number_format($value->qty *  $defaultcost, 2, '.', '');
          }
        }

        if ($value->comm2 != 0) {
          if (abs($value->ext) > 0) {
            $comap2 = number_format(abs($comap1) *  ($value->comm2 / 100), 2, '.', '');
          }
        }

        $dataupdate = ['comap' => $comap1, 'comap2' => $comap2, 'netap' => $comap1 - $comap2 - $value->cardcharge];
        $this->coreFunctions->sbcupdate("stockinfo", $dataupdate, ['trno' => $value->trno, 'line' => $value->line]);
      }
    }
  }

  private function createacctgentry_homeworks($config, $rows, $doc)
  {

    $blnextract = true;

    foreach ($rows as $key) {
      $dcVat = 0;
      $dcTvat = 0;
      $dcAmt = 0;
      $dcSRAmt = 0;
      $dcPWDAmt = 0;
      $dcDisc = 0;
      $dclessvat = 0;
      $dcext = 0;
      $dcSales = 0;
      $dcServiceSales = 0;
      $dcConsignSales = 0;
      $dcConcessionSales = 0;
      $this->acctg = [];

      $qry = "select cntnum.doc,cntnum.trno,lahead.dateid,lahead.docno,lahead.client,lahead.clientname,cntnum.station,client.clientid as branchid,client.client as branch,
        round(head.amt,2) as amt,head.cash,head.cheque,head.voucher,head.card,head.nvat,head.vatamt,head.vatex,head.cr,head.sramt as discsr,head.discamt,head.pwdamt,head.empdisc,head.debit,
        head.smac,head.onlinedeals,head.vipdisc,head.smacdisc,head.oddisc,head.eplus,head.terminalid,head.voucherno,head.checktype,head.gcamt,head.lessvat,head.lp,
        c.clientid,head.trno as storetrno,head.bref,head.deposit,head.depodetail from lahead
        left join head on head.webtrno = lahead.trno
        left join client on client.clientid = lahead.branch
        left join client as c on c.client = lahead.client
        left join cntnum on cntnum.trno = lahead.trno
        where cntnum.doc = '" . $doc . "' and lahead.dateid ='" . date('Y-m-d', strtotime($key['dateid'])) . "' and lahead.trno = " . $key['trno'];
      //group by cntnum.trno,cntnum.doc,lahead.docno,lahead.dateid,lahead.client,lahead.clientname,cntnum.station,client.client,client.clientid";
      $config['params']['trno'] = $key['trno'];
      $data = $this->coreFunctions->opentable($qry);
      if (!empty($data)) {
        $this->coreFunctions->execqry("delete from ladetail where trno = " . $key['trno']);

        foreach ($data as $k => $val) {
          //cash
          if ($data[$k]->cash != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CSH']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for cash transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->cash < 0 ? 0 : abs($data[$k]->cash), 'cr' => $data[$k]->cash > 0 ? 0 : abs($data[$k]->cash), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end cash

          //cr (ar sales)
          if ($data[$k]->cr != 0) {
            if ($data[$k]->bref == 'V') {
              $refsi = $this->coreFunctions->datareader("select distinct ref as value from stock left join head on head.trno = stock.trno and head.station = stock.station where head.webtrno = " . $key['trno'] . " limit 1");
              $sitrno =  $this->coreFunctions->datareader("select trno as value from head where docno ='" . $refsi . "' and station ='" . $data[$k]->station . "'");
              $arref = explode("~", $this->coreFunctions->datareader("select concat(trno,'~',line) as value from gldetail where storetrno = " . $sitrno . " and station ='" . $data[$k]->station . "'"));
              $ref = $this->coreFunctions->getfieldvalue('cntnum', 'docno', 'trno=?', [$arref[0]]);
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'AR']);
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => abs($data[$k]->cr), 'db' => 0, 'postdate' => $data[$k]->dateid, 'storetrno' => $data[$k]->storetrno, 'station' => $data[$k]->station, 'refx' => $arref[0], 'linex' => $arref[1], 'ref' => $ref];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'AR']);
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->cr < 0 ? 0 : abs($data[$k]->cr), 'cr' => $data[$k]->cr > 0 ? 0 : abs($data[$k]->cr), 'postdate' => $data[$k]->dateid, 'storetrno' => $data[$k]->storetrno, 'station' => $data[$k]->station];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end cr


          //check
          if ($data[$k]->cheque != 0) {
            if ($data[$k]->checktype != '') {
              $chk = explode(",", $data[$k]->checktype);
              $chkt = "";
              $suppid = 0;
              $supp = '';
              $checkamt = 0;
              $variance = 0;
              for ($i = 0; $i <= count($chk) - 1; $i++) {
                $c = explode("~", $chk[$i]);
                $chkt = $c[0];
                $suppid = $this->coreFunctions->getfieldvalue('checktypes', 'clientid', 'type=?', [$chkt]);
                $supp = $this->coreFunctions->getfieldvalue('client', 'client', 'clientid=?', [$suppid]);
                $acnoid = $this->coreFunctions->getfieldvalue('checktypes', 'acnoid', 'type=?', [$chkt]);

                if ($acnoid == 0) {
                  return ['status' => false, 'msg' => 'Please setup account for ' . $chkt . ' payment type'];
                }

                if ($suppid == 0) {
                  $suppid = $data[$k]->clientid;
                  $supp = $data[$k]->client;
                }

                $entry = ['acnoid' => $acnoid, 'client' => $supp, 'db' => $c[1] < 0 ? 0 : abs($c[1]), 'cr' => $c[1] > 0 ? 0 : abs($c[1]), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                $checkamt = $checkamt + $c[1];
              }
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CHK']);
              if ($acnoid == 0) {
                return ['status' => false, 'msg' => 'Please setup account for check transactions'];
              } else {
                $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->cheque < 0 ? 0 : abs($data[$k]->cheque), 'cr' => $data[$k]->cheque > 0 ? 0 : abs($data[$k]->cheque), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }
            }
          } //end cheque

          //card
          if ($data[$k]->card != 0) {
            if ($data[$k]->terminalid != '') {
              $j = explode(",", $data[$k]->terminalid);
              $terminal = "";
              for ($i = 0; $i <= count($j) - 1; $i++) {
                $chargeamt = 0;
                $ewtamt = 0;
                $card = 0;
                if (substr($j[$i], 0, 1) == 'C') {
                  $c = explode("~", $j[$i]);
                  $terminal = substr($c[0], 1, strlen($c[0]));
                  if ($terminal == '') {
                    $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CRD']);
                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for card transactions. (' . $key['trno'] . ' - ' . $data[$k]->terminalid . ')'];
                    } else {
                      $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->card < 0 ? 0 : abs($c[1]), 'cr' => $data[$k]->card > 0 ? 0 : abs($c[1]), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }
                  } else {
                    $acnoid = $this->coreFunctions->getfieldvalue('branchbank', 'acnoid', 'isinactive =0 and clientid =? and terminalid=?', [$data[$k]->branchid, $terminal]);
                    $ewt = $this->coreFunctions->getfieldvalue('bankcharges', 'ewt', 'inactive =0 and type =? and terminalid=?', [$c[2], $terminal]);

                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for card transactions. (' . $key['trno'] . ' - ' . $data[$k]->terminalid . ')'];
                    }

                    $card = floatval($c[1]);

                    $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $card < 0 ? 0 : abs($card), 'cr' => $card > 0 ? 0 : abs($card), 'postdate' => $data[$k]->dateid];
                    $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                  }
                } // end if C
              }
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'CRD']);
              if ($acnoid == 0) {
                return ['status' => false, 'msg' => 'Please setup account for card transactions. (' . $key['trno'] . ' - ' . $data[$k]->terminalid . ')'];
              } else {
                $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->card < 0 ? 0 : abs($data[$k]->card), 'cr' => $data[$k]->card > 0 ? 0 : abs($data[$k]->card), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }
            }
          } //end card

          //voucher
          if ($data[$k]->voucher != 0) { //giftchecks
            // if ($data[$k]->voucherno != "") {
            //   // $j = str_split($data[$k]->voucherno,",");
            //   // for($i = 0; $i <= count($j)-1; $i++){

            //   // }
            // } 
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'VC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for voucher transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->voucher < 0 ? 0 : abs($data[$k]->voucher), 'cr' => $data[$k]->voucher > 0 ? 0 : abs($data[$k]->voucher), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end voucher

          //lp
          if ($data[$k]->lp != 0) { //lp
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'LP']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Loyalty Points transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->lp < 0 ? 0 : abs($data[$k]->lp), 'cr' => $data[$k]->lp > 0 ? 0 : abs($data[$k]->lp), 'postdate' => $data[$k]->dateid, 'station' => $data[$k]->station];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end lp

          //debit
          if ($data[$k]->debit != 0) {
            if ($data[$k]->terminalid != '') {
              $j = explode(",", $data[$k]->terminalid);
              $terminal = "";
              for ($i = 0; $i <= count($j) - 1; $i++) {
                $chargeamt = 0;
                $ewtamt = 0;
                $card = 0;
                if (substr($j[$i], 0, 1) == 'D') {
                  $c = explode("~", $j[$i]);
                  $terminal = substr($c[0], 1, strlen($c[0]));
                  if ($terminal == '') {
                    $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'DEBIT']);
                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for debit transactions'];
                    } else {
                      $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->debit < 0 ? 0 : abs($c[1]), 'cr' => $data[$k]->debit > 0 ? 0 : abs($c[1]), 'postdate' => $data[$k]->dateid];
                      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                    }
                  } else {
                    $acnoid = $this->coreFunctions->getfieldvalue('branchbank', 'acnoid', 'isinactive =0 and clientid =? and terminalid=?', [$data[$k]->branchid, $terminal]);
                    $charge = $this->coreFunctions->getfieldvalue('bankcharges', 'rate', 'inactive =0 and type =? and terminalid=?', [$c[2], $terminal]);
                    $ewt = $this->coreFunctions->getfieldvalue('bankcharges', 'ewt', 'inactive =0 and type =? and terminalid=?', [$c[2], $terminal]);

                    if ($acnoid == 0) {
                      return ['status' => false, 'msg' => 'Please setup account for debit transactions terminalid - ' . $terminal];
                    }

                    $card = floatval($c[1]);

                    $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $card < 0 ? 0 : abs($card), 'cr' => $card > 0 ? 0 : abs($card), 'postdate' => $data[$k]->dateid];
                    $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                  }
                } // end if D
              }
            } else {
              $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'DEBIT']);
              if ($acnoid == 0) {
                return ['status' => false, 'msg' => 'Please setup account for debit transactions'];
              } else {
                $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->debit < 0 ? 0 : abs($data[$k]->debit), 'cr' => $data[$k]->debit > 0 ? 0 : abs($data[$k]->debit), 'postdate' => $data[$k]->dateid];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }
            }
          } //end debit

          //smac
          if ($data[$k]->smac != 0) { //smac
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SMAC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for SMAC transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->smac < 0 ? 0 : abs($data[$k]->smac), 'cr' => $data[$k]->smac > 0 ? 0 : abs($data[$k]->smac), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end smac

          //eplus
          if ($data[$k]->eplus != 0) { //eplus
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'EPLUS']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for E-PLUS transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->eplus < 0 ? 0 : abs($data[$k]->eplus), 'cr' => $data[$k]->eplus > 0 ? 0 : abs($data[$k]->eplus), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end eplus

          //onlinedeals
          if ($data[$k]->onlinedeals != 0) { //onlinedeals
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'ONLINE']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for online deals transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->onlinedeals < 0 ? 0 : abs($data[$k]->onlinedeals), 'cr' => $data[$k]->onlinedeals > 0 ? 0 : abs($data[$k]->onlinedeals), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end onlinedeals

          //discamt
          if ($data[$k]->discamt != 0) { //discamt
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'DISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales Discounts.'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->discamt < 0 ? 0 : abs($data[$k]->discamt), 'cr' => $data[$k]->discamt > 0 ? 0 : abs($data[$k]->discamt), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end disc

          //smacdisc
          if ($data[$k]->smacdisc != 0) { //smacdisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SMACDISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for SMAC Discounts.'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->smacdisc < 0 ? 0 : abs($data[$k]->smacdisc), 'cr' => $data[$k]->smacdisc > 0 ? 0 : abs($data[$k]->smacdisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end smacdisc

          //vipdisc
          if ($data[$k]->vipdisc != 0) { //vipdisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'VIPDISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for VIP transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->vipdisc < 0 ? 0 : abs($data[$k]->vipdisc), 'cr' => $data[$k]->vipdisc > 0 ? 0 : abs($data[$k]->vipdisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end vipdisc

          //oddisc
          if ($data[$k]->oddisc != 0) { //oddisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'ODISC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for online deals discount.'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->oddisc < 0 ? 0 : abs($data[$k]->oddisc), 'cr' => $data[$k]->oddisc > 0 ? 0 : abs($data[$k]->oddisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end odisc

          //discsr
          if ($data[$k]->discsr != 0) { //discsr
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Senior Discount'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->discsr < 0 ? 0 : abs($data[$k]->discsr), 'cr' => $data[$k]->discsr > 0 ? 0 : abs($data[$k]->discsr), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end discsr

          //pwdamt
          if ($data[$k]->pwdamt != 0) { //pwdamt
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'PWD']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for PWD transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->pwdamt < 0 ? 0 : abs($data[$k]->pwdamt), 'cr' => $data[$k]->pwdamt > 0 ? 0 : abs($data[$k]->pwdamt), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end pwdamt

          //empdisc
          if ($data[$k]->empdisc != 0) { //empdisc
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'EMP']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Employee discount'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $data[$k]->empdisc < 0 ? 0 : abs($data[$k]->empdisc), 'cr' => $data[$k]->empdisc > 0 ? 0 : abs($data[$k]->empdisc), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end empdisc

          //vatamt
          if ($data[$k]->vatamt != 0) { //vatamt
            $dcTvat = $dcTvat + $data[$k]->vatamt;
          } //end vatamt

          //gcamt
          if ($data[$k]->gcamt != 0) { //gcamt
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'GC']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for GC transactions'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => $data[$k]->gcamt < 0 ? 0 : abs($data[$k]->gcamt), 'db' => $data[$k]->gcamt > 0 ? 0 : abs($data[$k]->gcamt), 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } //end gcamt

          //deposit
          if ($data[$k]->deposit != 0) { //deposit
            if ($data[$k]->depodetail != '') {
              $arlid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARL1']);

              $dp = explode(",", $data[$k]->depodetail);
              $dpRef = $dpStation = $dpBranch = '';
              $dpAmt = 0;

              for ($i = 0; $i <= count($dp) - 1; $i++) {
                $dpT = explode("~", $dp[$i]);
                if (count($dpT) == 2) {
                  $dpRef = $dpT[0];
                  $dpAmt = $dpT[1];
                } else {
                  $dpRef = $dpT[0];
                  $dpAmt = $dpT[1];
                  $dpStation = $dpT[2];
                  $dpBranch = substr($dpT[2], 0, 4);
                }

                $filteramt = '';
                $filterStation = '';

                if ($dpStation == '') {
                  //return layaway
                  if ($dpAmt > 0) {
                    $filteramt = ' and cr.bal<>0 and ar.cr=' . $dpAmt;
                  } else {
                    $filteramt = ' and ar.cr=' . abs($dpAmt);
                  }
                } else {
                  $filterStation = " and num.station='" . $dpStation . "'";
                }

                $sql = "select ar.trno, ar.line, ar.dateid, ar.docno, d.dpref, ar.acnoid, ar.clientid, ar.db, ar.cr, ar.bal, client.client, coa.acno, num.station
                        FROM arledger AS ar LEFT JOIN coa ON coa.acnoid=ar.acnoid LEFT JOIN gldetail AS d ON d.trno=ar.trno AND d.line=ar.line LEFT JOIN client ON client.clientid=ar.clientid LEFT JOIN cntnum AS num ON num.trno=ar.trno
                        WHERE LEFT(ar.docno,3)='CRS' AND coa.alias IN ('ARL1','ARL2') and d.dpref='" . $dpRef . "' and ar.cr>0 " . $filteramt . $filterStation;

                $dataLAY = $this->coreFunctions->opentable($sql);

                if (empty($dataLAY)) {
                  return ['status' => false, 'msg' => 'Please extract pending Layaway transactions.'];
                }

                foreach ($dataLAY as $keyL => $valueL) {
                  if ($data[$k]->deposit != $dpAmt) {
                    $entry = [
                      'acnoid' => $valueL->acnoid,
                      'client' => $valueL->client,
                      'db' => $data[$k]->deposit > 0 ? abs($dpAmt) : 0,
                      'cr' => $data[$k]->deposit > 0 ? 0 : abs($dpAmt),
                      'postdate' => $valueL->dateid,
                      'rem' => 'LAYAWAY~' . $dpRef,
                      'refx' => $valueL->trno,
                      'linex' => $valueL->line,
                      'ref' => $valueL->docno
                    ];
                  } else {
                    $entry = [
                      'acnoid' => $valueL->acnoid,
                      'client' => $valueL->client,
                      'db' => $data[$k]->deposit > 0 ? abs($data[$k]->deposit) : 0,
                      'cr' => $data[$k]->deposit > 0 ? 0 : abs($data[$k]->deposit),
                      'postdate' => $valueL->dateid,
                      'rem' => 'LAYAWAY~' . $dpRef,
                      'refx' => $valueL->trno,
                      'linex' => $valueL->line,
                      'ref' => $valueL->docno
                    ];
                  }

                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }
              }
            }
          } //end deposit

          $dcAmt = $dcAmt + $data[$k]->amt;
          $dcDisc = $dcDisc + ($data[$k]->discamt + $data[$k]->empdisc  + $data[$k]->vipdisc);
          $dcSRAmt = $dcSRAmt + $data[$k]->discsr;
          $dcPWDAmt = $dcPWDAmt + $data[$k]->pwdamt;
        } // end for data

        //compute sales entry
        $dclessvat = $this->coreFunctions->datareader("select ifnull(sum(stock.lessvat),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid=lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='OUTRIGHT'");
        $dcext = $this->coreFunctions->datareader("select ifnull(sum(lastock.ext+abs(stock.discamt)),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where item.barcode not in ('*') and  lastock.trno = " . $key['trno'] . " and item.channel='OUTRIGHT'");
        $vatamt_c = $this->coreFunctions->datareader("select ifnull(sum(stock.vatamt),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='OUTRIGHT'");
        $this->coreFunctions->LogConsole('VAT per sales type:' . ($vatamt_c + $dclessvat));
        if ($dcext != 0) {
          $dcSales = $dcSales + round((abs($dcext) - abs($vatamt_c) - abs($dclessvat)), 2);
        }

        $dclessvat = 0;
        $dcext = 0;
        $vatamt_c = 0;

        $dclessvat = $this->coreFunctions->datareader("select ifnull(sum(stock.lessvat),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid=lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='SERVICE' and item.isgc=0");
        $dcext = $this->coreFunctions->datareader("select ifnull(sum(lastock.ext+abs(stock.discamt)),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where item.barcode not in ('*') and  lastock.trno = " . $key['trno'] . " and item.channel='SERVICE' and item.isgc=0");
        $vatamt_c = $this->coreFunctions->datareader("select ifnull(sum(stock.vatamt),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='SERVICE' and item.isgc=0");
        $this->coreFunctions->LogConsole('VAT per sales type:' . ($vatamt_c + $dclessvat));
        if ($dcext != 0) {
          $dcServiceSales = $dcServiceSales + round((abs($dcext) - abs($vatamt_c) - abs($dclessvat)), 2);
        }

        $dclessvat = 0;
        $dcext = 0;
        $vatamt_c = 0;

        $dclessvat = $this->coreFunctions->datareader("select ifnull(sum(stock.lessvat),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid=lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='CONSIGNMENT' and item.isgc=0");
        $dcext = $this->coreFunctions->datareader("select ifnull(sum(lastock.ext+abs(stock.discamt)),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where item.barcode not in ('*') and  lastock.trno = " . $key['trno'] . " and item.channel='CONSIGNMENT' and item.isgc=0");
        $vatamt_c = $this->coreFunctions->datareader("select ifnull(sum(stock.vatamt),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='CONSIGNMENT' and item.isgc=0");
        $this->coreFunctions->LogConsole('VAT per sales type:' . ($vatamt_c + $dclessvat));
        if ($dcext != 0) {
          $dcConsignSales = $dcConsignSales + round((abs($dcext) - abs($vatamt_c) - abs($dclessvat)), 2);
        }

        $dclessvat = 0;
        $dcext = 0;
        $vatamt_c = 0;

        $dclessvat = $this->coreFunctions->datareader("select ifnull(sum(stock.lessvat),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid=lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='CONCESSION' and item.isgc=0");
        $dcext = $this->coreFunctions->datareader("select ifnull(sum(lastock.ext+abs(stock.discamt)),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where item.barcode not in ('*') and  lastock.trno = " . $key['trno'] . " and item.channel='CONCESSION' and item.isgc=0");
        $vatamt_c = $this->coreFunctions->datareader("select ifnull(sum(stock.vatamt),0) as value from lastock left join stockinfo as stock on stock.trno = lastock.trno and stock.line = lastock.line left join item on item.itemid = lastock.itemid where lastock.trno = " . $key['trno'] . " and item.channel='CONCESSION' and item.isgc=0");
        $this->coreFunctions->LogConsole('VAT per sales type:' . ($vatamt_c + $dclessvat));
        if ($dcext != 0) {
          $dcConcessionSales = $dcConcessionSales + round((abs($dcext) - abs($vatamt_c) - abs($dclessvat)), 2);
        }

        $dclessvat = 0;
        $dcext = 0;
        $vatamt_c = 0;

        if ($dcSales != 0) {
          if ($key['doc'] == 'CM') {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SR']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales Return'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => abs($dcSales), 'cr' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } else {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SA']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => abs($dcSales), 'db' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        if ($dcServiceSales != 0) {
          if ($key['doc'] == 'CM') {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SR']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales Return'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => abs($dcServiceSales), 'cr' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } else {
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SS1']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Service Sales'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => abs($dcServiceSales), 'db' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        if ($dcConcessionSales != 0) {
          if ($key['doc'] == 'CM') {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SR']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales Return'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => abs($dcConcessionSales), 'cr' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } else {
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA1']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Concession Sales'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => abs($dcConcessionSales), 'db' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        if ($dcConsignSales != 0) {
          if ($key['doc'] == 'CM') {
            $acnoid = $this->coreFunctions->getfieldvalue('profile', 'pvalue', 'psection=? and doc=?', ['ACCT', 'SR']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Sales Return'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => abs($dcConsignSales), 'cr' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          } else {
            $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['SA2']);
            if ($acnoid == 0) {
              return ['status' => false, 'msg' => 'Please setup account for Consign Sales'];
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'cr' => abs($dcConsignSales), 'db' => 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        $dcAmt = round(abs($dcAmt), 2);
        $dcDisc = round(abs($dcDisc), 2);
        $dcSRAmt = round(abs($dcSRAmt), 2);
        $dcPWDAmt = round(abs($dcPWDAmt), 2);
        $dcSales = round($dcSales, 2);
        $dcServiceSales = round($dcServiceSales, 2);
        $dcConsignSales = round($dcConsignSales, 2);
        $dcConcessionSales = round($dcConcessionSales, 2);
        $dcTvat = round(abs($dcTvat), 2);

        // $this->coreFunctions->sbclogger('Trno:' . $key['trno']);
        // $this->coreFunctions->sbclogger('Gross:' . ($dcAmt + $dcDisc + $dcSRAmt + $dcPWDAmt));
        // $this->coreFunctions->sbclogger('Amt:' . $dcAmt);
        // $this->coreFunctions->sbclogger('Disc:' . $dcDisc);
        // $this->coreFunctions->sbclogger('SRAmt:' . $dcSRAmt);
        // $this->coreFunctions->sbclogger('PWDAmt:' . $dcPWDAmt);
        // $this->coreFunctions->sbclogger('Sales:' . $dcSales);
        // $this->coreFunctions->sbclogger('ServiceSales:' . $dcServiceSales);
        // $this->coreFunctions->sbclogger('ConsignSales:' . $dcConsignSales);
        // $this->coreFunctions->sbclogger('ConcessionSales:' .  $dcConcessionSales);
        // $this->coreFunctions->sbclogger('TotalSales:' .  ($dcSales + $dcServiceSales + $dcConsignSales + $dcConcessionSales));

        $this->coreFunctions->LogConsole('VAT:' . $dcTvat);
        // $this->coreFunctions->LogConsole(abs(($dcAmt + $dcDisc + $dcSRAmt + $dcPWDAmt) - ($dcTvat + $dcSales)));
        //checking for 0.01 discrepancy

        $variance = ($dcAmt + $dcDisc + $dcSRAmt + $dcPWDAmt) - ($dcTvat + $dcSales + $dcServiceSales + $dcConsignSales + $dcConcessionSales);
        if ($variance < 0) {
          $this->coreFunctions->sbclogger('variance < zero: ' . $variance);
          $dcTvat = $dcTvat - abs($variance);
        } elseif ($variance > 0) {
          $this->coreFunctions->sbclogger('variance > zero:' . $variance);
          $dcTvat = $dcTvat + abs($variance);
        }

        //outputvat
        if ($dcTvat != 0) {
          // $this->coreFunctions->sbclogger('Entry VAT:' . $dcTvat);
          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='TX2'");
          if ($acnoid == 0) {
            return ['status' => false, 'msg' => 'Please setup account for Output VAT'];
          } else {
            if ($key['doc'] == 'CM') {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $dcTvat, 'cr' =>  0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            } else {
              $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => 0, 'cr' => $dcTvat, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        // if ($key['doc'] == 'CM') {
        //   $cost = $this->coreFunctions->getfieldvalue("lastock", "sum(cost*qty)", "trno=?", [$key['trno']]);
        // } else {
        //   $cost = $this->coreFunctions->getfieldvalue("lastock", "sum(cost*iss)", "trno=?", [$key['trno']]);
        // }

        // if ($cost != 0) { //cogs & inv
        //   $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG1']);
        //   $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $key['doc'] == 'CM' ? 0 : $cost, 'cr' => $key['doc'] == 'CM' ? $cost : 0, 'postdate' => $data[$k]->dateid];
        //   $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

        //   $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['IN1']);
        //   $entry = ['acnoid' => $acnoid, 'client' => $data[$k]->client, 'db' => $key['doc'] == 'CM' ? $cost : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $cost, 'postdate' => $data[$k]->dateid];
        //   $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
        // }


        $dataAPOut = $this->coreFunctions->opentable("SELECT stock.whid, client.client, sum(info.comap) as comap, sum(info.cardcharge) as cardcharge, sum(info.comap2) as comap2, sum(info.netap) as netap
                    from lastock as stock LEFT JOIN stockinfo AS info ON info.trno=stock.trno AND info.line=stock.line LEFT JOIN client ON client.clientid=stock.suppid left join item on item.itemid=stock.itemid where item.channel='OUTRIGHT' and stock.trno=" . $key['trno'] . " group by stock.whid, client.client");

        foreach ($dataAPOut as $keyAP => $valAP) {
          if ($valAP->netap > 0) {
            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG3']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $valAP->netap, 'cr' => $key['doc'] == 'CM' ? $valAP->netap : 0, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['IN3']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? $valAP->netap : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $valAP->netap, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
          }
        }


        $dataAPOut = $this->coreFunctions->opentable("SELECT stock.whid, client.client, sum(info.comap) as comap, sum(info.cardcharge) as cardcharge, sum(info.comap2) as comap2, sum(info.netap) as netap
                    from lastock as stock LEFT JOIN stockinfo AS info ON info.trno=stock.trno AND info.line=stock.line LEFT JOIN client ON client.clientid=stock.suppid left join item on item.itemid=stock.itemid where item.channel='CONSIGNMENT' and stock.trno=" . $key['trno'] . " group by stock.whid, client.client");

        foreach ($dataAPOut as $keyAP => $valAP) {
          if ($valAP->netap > 0) {
            $dcNetComAP = $dcInputTax = 0;
            if ($valAP->comap != 0) {
              $dcInputTax = number_format((($valAP->comap / 1.12) * 0.12), 2, '.', '');
              $dcNetComAP = number_format($valAP->comap - $dcInputTax, 2, '.', '');
            }

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG2']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $dcNetComAP, 'cr' => $key['doc'] == 'CM' ? $dcNetComAP : 0, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['TX1']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $dcInputTax, 'cr' => $key['doc'] == 'CM' ? $dcInputTax : 0, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['AP2']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? $valAP->comap : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $valAP->comap, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            if ($valAP->cardcharge != 0) {
              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARBC']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $valAP->cardcharge, 'cr' => $key['doc'] == 'CM' ?  $valAP->cardcharge : 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIBC']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ?  $valAP->cardcharge : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $valAP->cardcharge, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }

            if ($valAP->comap2 != 0) {
              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARMS']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $valAP->comap2, 'cr' => $key['doc'] == 'CM' ? $valAP->comap2 : 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIMS']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? $valAP->comap2 : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $valAP->comap2, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        $dataAPOut = $this->coreFunctions->opentable("SELECT stock.whid, client.client, sum(info.comap) as comap, sum(info.cardcharge) as cardcharge, sum(info.comap2) as comap2, sum(info.netap) as netap
                    from lastock as stock LEFT JOIN stockinfo AS info ON info.trno=stock.trno AND info.line=stock.line LEFT JOIN client ON client.clientid=stock.suppid left join item on item.itemid=stock.itemid where item.channel='CONCESSION' and stock.trno=" . $key['trno'] . " group by stock.whid, client.client");

        foreach ($dataAPOut as $keyAP => $valAP) {
          if ($valAP->netap > 0) {
            $dcNetComAP = $dcInputTax = 0;
            if ($valAP->comap != 0) {
              $dcInputTax = number_format((($valAP->comap / 1.12) * 0.12), 2, '.', '');
              $dcNetComAP = number_format($valAP->comap - $dcInputTax, 2, '.', '');
            }

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG1']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $dcNetComAP, 'cr' => $key['doc'] == 'CM' ? $dcNetComAP : 0, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['TX1']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $dcInputTax, 'cr' => $key['doc'] == 'CM' ? $dcInputTax : 0, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['AP1']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? $valAP->comap : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $valAP->comap, 'postdate' => $data[$k]->dateid];
            $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

            if ($valAP->cardcharge != 0) {
              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARBC']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $valAP->cardcharge, 'cr' => $key['doc'] == 'CM' ? $valAP->cardcharge : 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIBC']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ?  $valAP->cardcharge : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $valAP->cardcharge, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }

            if ($valAP->comap2 != 0) {
              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARMS']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? 0 : $valAP->comap2, 'cr' => $key['doc'] == 'CM' ? $valAP->comap2 : 0, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

              $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIMS']);
              $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'db' => $key['doc'] == 'CM' ? $valAP->comap2 : 0, 'cr' => $key['doc'] == 'CM' ? 0 : $valAP->comap2, 'postdate' => $data[$k]->dateid];
              $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
            }
          }
        }

        $config['params']['trno'] = $key['trno'];
        if (!empty($this->acctg)) {
          $current_timestamp = $this->othersClass->getCurrentTimeStamp();
          foreach ($this->acctg as $key3 => $value) {
            foreach ($value as $key2 => $value2) {
              $this->acctg[$key3][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
            }
            $this->acctg[$key3]['editdate'] = $current_timestamp;
            $this->acctg[$key3]['editby'] = $config['params']['user'];
            $this->acctg[$key3]['encodeddate'] = $current_timestamp;
            $this->acctg[$key3]['encodedby'] = 'EXTRACTION';
            $this->acctg[$key3]['trno'] = $key['trno'];
            $this->acctg[$key3]['db'] = round($this->acctg[$key3]['db'], 2);
            $this->acctg[$key3]['cr'] = round($this->acctg[$key3]['cr'], 2);
            $this->acctg[$key3]['fdb'] = round($this->acctg[$key3]['fdb'], 2);
            $this->acctg[$key3]['fcr'] = round($this->acctg[$key3]['fcr'], 2);

            if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg[$key3]) == 1) {
              $this->logger->sbcwritelog($key['trno'], $config, 'DETAILS', 'EXTRACT SALES ENTRY');
              $this->coreFunctions->LogConsole($this->acctg[$key3]['refx']);
              if ($this->acctg[$key3]['refx'] != 0) {
                $acno = $this->coreFunctions->getfieldvalue('coa', 'acno', 'acnoid =?', [$this->acctg[$key3]['acnoid']]);
                if (!$this->sqlquery->setupdatebal($this->acctg[$key3]['refx'], $this->acctg[$key3]['linex'], $acno, $config)) {
                  $this->coreFunctions->sbcupdate('ladetail', ['db' => 0, 'cr' => 0, 'fdb' => 0, 'fcr' => 0], ['trno' => $key['trno'], 'line' => $this->acctg[$key3]['line']]);
                  $this->sqlquery->setupdatebal($this->acctg[$key3]['refx'], $this->acctg[$key3]['linex'], $acno, $config);
                  return ['status' => false, 'msg' => 'Extraction Failed-Error on Applying Payment' . isset($this->acctg[$key3]['ref']) ? $this->acctg[$key3]['ref'] : ''];
                }
              }
            } else {
              $this->logger->sbcwritelog($key['trno'], $config, 'DETAILS', 'EXTRACT SALES ENTRY FAILED');
              return ['status' => false, 'msg' => 'Extraction Failed-Error on Accounting Entry(' . $key['trno'] . ')'];
            }
          }
        }

        $checkvar = $this->coreFunctions->opentable("select client, sum(db-cr) as variance from ladetail where trno=" . $key['trno'] . " group by client having sum(db-cr)<>0");
        if (!empty($checkvar)) {
          foreach ($checkvar as $keyVar => $valVar) {
            if (abs($valVar->variance) == 0.01) {
              $upalias = 'SA';
              $upfield = 'cr';
              if ($data[0]->doc == 'CM') {
                $upalias = 'SR';
                $upfield = 'db';
              }
              $linetoupdate = $this->coreFunctions->datareader("select d.line as value from ladetail as d left join coa on coa.acnoid=d.acnoid where d.trno=" . $key['trno'] . " and d.client='" . $valVar->client . "' and left(coa.alias,2)='" . $upalias . "'", [], '', true);
              if ($linetoupdate != 0) {
                if ($valVar->variance < 0) {
                  $this->coreFunctions->execqry("update ladetail set " . $upfield . "=" . $upfield . "+" . abs($valVar->variance) . " where trno=" . $key['trno'] . " and line=" .  $linetoupdate);
                } else {
                  $this->coreFunctions->execqry("update ladetail set " . $upfield . "=" . $upfield . "-" . abs($valVar->variance) . " where trno=" . $key['trno'] . " and line=" .  $linetoupdate);
                }
              }
            }
          }
        }

        $return = $this->othersClass->posttranstock($config);
        if (!$return['status']) {
          return ['status' => false, 'msg' => $return['msg'] . "(" . $key['trno'] . ")"];
        }
      } //end empty data  

    } //end for rows
    return ['status' => true];
  }
} //end class
