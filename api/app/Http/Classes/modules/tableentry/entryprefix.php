<?php

namespace App\Http\Classes\modules\tableentry;

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
use App\Http\Classes\sbcdb\trigger;
use App\Http\Classes\sbcdb\waims;
use App\Http\Classes\sbcdb\waims2;
use App\Http\Classes\sbcdb\customersupport;
use App\Http\Classes\sbcdb\enrollment;
use App\Http\Classes\sbcdb\fams;
use App\Http\Classes\sbcdb\hms;
use App\Http\Classes\sbcdb\hris;
use App\Http\Classes\sbcdb\pos;
use App\Http\Classes\sbcdb\bms;
use App\Http\Classes\sbcdb\payroll;
use App\Http\Classes\sbcdb\warehousing;
use App\Http\Classes\sbcdb\documentmanagement;
use App\Http\Classes\sbcdb\vsched;
use App\Http\Classes\sbcdb\reindex;

use Carbon\Carbon;


ini_set('memory_limit', '-1');
ini_set('max_execution_time', 0);

class entryprefix
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Manage Prefixes';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $table = 'profile';
  private $othersClass;
  public $style = 'width:100%;height:100%;';
  private $fields = ['pvalue', 'yr'];
  public $showclosebtn = false;
  private $trigger;
  private $waims;
  private $waims2;
  private $customersupport;
  private $enrollment;
  private $hms;
  private $hris;
  private $pos;
  private $bms;
  private $payroll;
  private $warehousing;
  private $documentmanagement;
  private $fams;
  private $vsched;
  private $logger;
  private $reindex;
  private $acctg = [];


  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->trigger = new trigger;
    $this->waims = new waims;
    $this->waims2 = new waims2;
    $this->customersupport = new customersupport;
    $this->enrollment = new enrollment;
    $this->hms = new hms;
    $this->pos = new pos;
    $this->bms = new bms;
    $this->hris = new hris;
    $this->payroll = new payroll;
    $this->warehousing = new warehousing;
    $this->documentmanagement = new documentmanagement;
    $this->fams = new fams;
    $this->vsched = new vsched;
    $this->logger = new Logger;
    $this->reindex = new reindex;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 0);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [$this->gridname => ['gridcolumns' => ['action', 'psection', 'pvalue', 'yr']]];

    $stockbuttons = ['save'];

    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    // action
    $obj[0][$this->gridname]['columns'][0]['style'] = "width:40px;whiteSpace: normal;min-width:40px;";
    $obj[0][$this->gridname]['columns'][1]['style'] = "width:400px;whiteSpace: normal;min-width:400px;";
    $obj[0][$this->gridname]['columns'][2]['style'] = "width:500px;whiteSpace: normal;min-width:500px;";
    if (!$this->companysetup->getdocyr($config['params'])) {
      $obj[0][$this->gridname]['columns'][3]['type'] = 'coldel';
    }
    $obj[0][$this->gridname]['columns'] = $this->tabClass->delcol($obj, $this->gridname);
    return $obj;
  }

  // NO FUNCTION FOR ADD BUTTON
  public function createtabbutton($config)
  {

    $tbuttons = ['multiaction', 'saveallentry'];
    //'updatepostatus'  - mitsukoshi
    //'recalc' - unihome
    //'updatepostatus' - housegem reentry CM inv and cogs
    // 'updatepostatus' - kinggeorge update tagging in uom

    //2024.02.19
    // if ($config['params']['companyid'] == 3) {
    //   array_push($tbuttons, 'updatepostatus');
    // }

    //2025.08.05
    // if ($config['params']['companyid'] == 56) {
    //   if ($config['params']['user'] == 'sbc') array_push($tbuttons, 'updatepostatus');
    // }

    //2023.05.31
    $obj = $this->tabClass->createtabbutton($tbuttons);
    $obj[0]['label'] = "CHECK FIELDS";



    //2023.05.31
    // if ($config['params']['companyid'] == 21) {
    // $obj[2]['label'] = "REDISTRIBUTE MERCH INV";
    // $obj[2]['action'] = "redistributeinvcost";
    // $obj[2]['lookupclass'] = "redistributeinvcost";

    //2023.10.25
    // $obj[2]['label'] = "TAG UOM";
    // $obj[2]['action'] = "taguomforsales";
    // $obj[2]['lookupclass'] = "taguomforsales";

    //2024.03.25 -due to update TS cost
    // $obj[2]['label'] = "REDISTRIBUTE MERCH INV";
    // $obj[2]['action'] = "redistributeentry";
    // $obj[2]['lookupclass'] = "redistributeentry";

    //updateisscost
    // $obj[2]['label'] = "UPDATE COST";
    // $obj[2]['action'] = "updateisscost";
    // $obj[2]['lookupclass'] = "updateisscost";
    // }

    //2024.02.19
    // if ($config['params']['companyid'] == 3) { //conti
    //   $obj[2]['label'] = "DELETE UNUSED ITEMS";
    //   $obj[2]['action'] = "deleteitems";
    //   $obj[2]['lookupclass'] = "deleteitems";
    // }

    //2025.08.05
    // if ($config['params']['companyid'] == 56) { //homeworks
    //   $obj[2]['label'] = "REDISTRIBUTE SJS/SRS AP";
    //   $obj[2]['action'] = "redistributenetp";
    //   $obj[2]['lookupclass'] = "redistributenetp";
    // }

    return $obj;
  }

  public function tableentrystatus($config)
  {
    ini_set('memory_limit', '-1');
    ini_set('max_execution_time', 0);

    $start = Carbon::parse($this->othersClass->getCurrentTimeStamp());
    switch ($config['params']['lookupclass2']) {
      case 'deletetriggers':
        $this->trigger->deletetriggers();
        break;
      case 'dataupdatewaims':
        $this->waims->dataupdatewaims();
        break;
      case 'tableupdatewaims':
        $this->waims->tableupdatewaims($config);
        break;
      case 'tableupdatewaims2':
        $this->waims2->tableupdatewaims2($config);
        break;
      case 'tableupdatehms':
        //10.31.2020 fmm
        $this->hms->tableupdatehms();
        break;
      case 'tableupdatefams':
        $this->fams->tableupdatefams();
        break;
        // 04.28.2022 jiks  
      case 'tableupdatevsched':
        $this->vsched->tableupdatevsched($config);
        break;
      case 'tableupdateenrollment':
        if ($this->companysetup->getsystemtype($config['params']) == 'SSMS') {
          $this->enrollment->tableupdateenrollment();
        }
        break;
      case 'tableupdatecustomersupport':
        //11.09.2020
        $this->customersupport->tableupdatecustomersupport();
        break;
      case 'tableupdatehris':
        // 11.20.2020
        $this->hris->tableupdatehris();
        break;
      case 'tableupdatepayroll':
        // 11.23.2020
        $this->payroll->tableupdatepayroll();
        break;
      case 'tableupdatewarehousing':
        // 05.14.2021
        $this->warehousing->tableupdatewarehousing();
        break;
        // GLEN 02.08.22
      case 'modifyLengthField':
        $this->waims->modifyLengthField($config);
        break;
      case 'tableupdatepos':
        $this->coreFunctions->LogConsole("Update POS Tables");
        $this->pos->tableupdatepos($config);
        break;
      case 'tableupdatebms':
        $this->bms->tableupdatebms($config);
        break;
      case 'reindex':
        $this->reindex->reindex($config);
        break;
      case 'createtriggers':
        $this->trigger->createtriggers($config); // -- ALWAYS AT THE BUTTOM PART
        break;
      case 'cleardb_proc':
        $this->trigger->cleardb_proc();
        break;
      case 'tableupdatedocumentmanagement':
        //FRED 05.05.2021
        $this->waims->lowecasefieldname();
        $this->documentmanagement->tableupdatedocumentmanagement();
        break;

      case 'updatepostatus':
        $this->updateboxcount();
        return $this->updatepostatus();
        break;

      case 'recalc':
        return $this->recalc($config);
        break;

      case 'recalcamt':
        return $this->recalcamt($config);
        break;

      case 'redistributeinvcost':
        return $this->redistributeinvcost($config);
        break;

      case 'updateisscost':
        return $this->updateisscost($config);
        break;

      case 'redistributeentry':
        return $this->redistributeentry($config);
        break;
      case 'testapi':
        return $this->testapi($config);
        break;

      case 'deleteitems':
        return $this->deleteitems($config);
        break;

      case 'taguomforsales':
        return $this->taguomforsales($config);
        break;

      case 'recomputecost':
        return $this->recomputecost($config);
        break;

      case 'redistributenetp':
        return $this->redistributenetp($config);
        break;
    }

    $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
    $elapsed = $start->diffInSeconds($end);
    return ['status' => true, 'msg' => $config['params']['lookupclass2'] . ' - TABLES UPDATE. Execution time: ' . $elapsed . "sec(s)"];
  }

  public function redistributenetp($config)
  {
    $cntnum = $this->coreFunctions->opentable("select h.trno, h.dateid, h.doc, h.docno from glhead as h where left(h.docno,3) in ('SJS','SRS')");
    foreach ($cntnum as $key => $value) {

      $this->coreFunctions->LogConsole('trno: ' . $value->trno . ' docno: ' . $value->docno);

      $this->coreFunctions->execqry("delete from gldetail where trno=" . $value->trno . " and acnoid in (select acnoid from coa where alias in ('CG3','IN3','CG2','TX1','AP2','ARBC','OIBC', 'ARMS', 'OIMS', 'CG1', 'AP1'))");

      $config['params']['trno'] = $value->trno;
      $config['params']['docno'] = $value->docno;

      $acctg = [];

      $dataAPOut = $this->coreFunctions->opentable("SELECT stock.whid, client.client, client.clientid, sum(info.comap) as comap, sum(info.cardcharge) as cardcharge, sum(info.comap2) as comap2, sum(info.netap) as netap
                    from glstock as stock LEFT JOIN hstockinfo AS info ON info.trno=stock.trno AND info.line=stock.line LEFT JOIN client ON client.clientid=stock.suppid 
                    left join item on item.itemid=stock.itemid where item.channel='OUTRIGHT' and stock.trno=" . $value->trno . " group by stock.whid, client.clientid, client.client");

      foreach ($dataAPOut as $keyAP => $valAP) {
        $netAP = ($valAP->comap - $valAP->comap2 - $valAP->cardcharge);
        if ($netAP != 0) {
          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG3']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 :  $netAP, 'cr' => $value->doc == 'CM' ?  $netAP : 0, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['IN3']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ?  $netAP : 0, 'cr' => $value->doc == 'CM' ? 0 :  $netAP, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
        }
      }

      $dataAPOut = $this->coreFunctions->opentable("SELECT stock.whid, client.client, client.clientid, sum(info.comap) as comap, sum(info.cardcharge) as cardcharge, sum(info.comap2) as comap2, sum(info.netap) as netap
                    from glstock as stock LEFT JOIN hstockinfo AS info ON info.trno=stock.trno AND info.line=stock.line LEFT JOIN client ON client.clientid=stock.suppid left join item on item.itemid=stock.itemid 
                    where item.channel='CONSIGNMENT' and stock.trno=" . $value->trno . " group by stock.whid, client.clientid, client.client");

      foreach ($dataAPOut as $keyAP => $valAP) {
        $netAP = ($valAP->comap - $valAP->comap2 - $valAP->cardcharge);
        if ($netAP != 0) {
          $dcNetComAP = $dcInputTax = 0;
          if ($valAP->comap != 0) {
            $dcInputTax = number_format((($valAP->comap / 1.12) * 0.12), 2, '.', '');
            $dcNetComAP = number_format($valAP->comap - $dcInputTax, 2, '.', '');
          }

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG2']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 : $dcNetComAP, 'cr' => $value->doc == 'CM' ? $dcNetComAP : 0, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['TX1']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 : $dcInputTax, 'cr' => $value->doc == 'CM' ? $dcInputTax : 0, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['AP2']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? $valAP->comap : 0, 'cr' => $value->doc == 'CM' ? 0 : $valAP->comap, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

          if ($valAP->cardcharge != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARBC']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ?  0 : $valAP->cardcharge, 'cr' => $value->doc == 'CM' ? $valAP->cardcharge : 0, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIBC']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ?  $valAP->cardcharge : 0, 'cr' => $value->doc == 'CM' ? 0 : $valAP->cardcharge, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
          }

          if ($valAP->comap2 != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARMS']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 : $valAP->comap2, 'cr' => $value->doc == 'CM' ? $valAP->comap2 : 0, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIMS']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? $valAP->comap2 : 0, 'cr' => $value->doc == 'CM' ? 0 : $valAP->comap2, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
          }
        }
      }

      $dataAPOut = $this->coreFunctions->opentable("SELECT stock.whid, client.client, client.clientid, sum(info.comap) as comap, sum(info.cardcharge) as cardcharge, sum(info.comap2) as comap2, sum(info.netap) as netap
                    from glstock as stock LEFT JOIN hstockinfo AS info ON info.trno=stock.trno AND info.line=stock.line LEFT JOIN client ON client.clientid=stock.suppid left join item on item.itemid=stock.itemid 
                    where item.channel='CONCESSION' and stock.trno=" . $value->trno . " group by stock.whid, client.clientid, client.client");

      foreach ($dataAPOut as $keyAP => $valAP) {
        $netAP = ($valAP->comap - $valAP->comap2 - $valAP->cardcharge);
        if ($netAP != 0) {
          $dcNetComAP = $dcInputTax = 0;
          if ($valAP->comap != 0) {
            $dcInputTax = number_format((($valAP->comap / 1.12) * 0.12), 2, '.', '');
            $dcNetComAP = number_format($valAP->comap - $dcInputTax, 2, '.', '');
          }

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['CG1']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 : $dcNetComAP, 'cr' => $value->doc == 'CM' ? $dcNetComAP : 0, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['TX1']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 : $dcInputTax, 'cr' => $value->doc == 'CM' ? $dcInputTax : 0, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

          $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['AP1']);
          $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? $valAP->comap : 0, 'cr' => $value->doc == 'CM' ? 0 : $valAP->comap, 'postdate' => $value->dateid];
          $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

          if ($valAP->cardcharge != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARBC']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 : $valAP->cardcharge, 'cr' => $value->doc == 'CM' ? $valAP->cardcharge : 0, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIBC']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? $valAP->cardcharge : 0, 'cr' => $value->doc == 'CM' ? 0 : $valAP->cardcharge, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
          }

          if ($valAP->comap2 != 0) {
            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['ARMS']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? 0 : $valAP->comap2, 'cr' => $value->doc == 'CM' ? $valAP->comap2 : 0, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);

            $acnoid = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ['OIMS']);
            $entry = ['acnoid' => $acnoid, 'client' => $valAP->client, 'clientid' => $valAP->clientid, 'db' => $value->doc == 'CM' ? $valAP->comap2 : 0, 'cr' => $value->doc == 'CM' ?  0 : $valAP->comap2, 'postdate' => $value->dateid];
            $acctg = $this->othersClass->upsertdetail($acctg, $entry, $config);
          }
        }
      }

      if (!empty($acctg)) {
        $current_timestamp = $this->othersClass->getCurrentTimeStamp();

        $qry = "select line as value from gldetail where trno=? order by line desc limit 1";
        $line = $this->coreFunctions->datareader($qry, [$config['params']['trno']], '', true);
        $line += 1;

        foreach ($acctg as $key3 => $value) {
          foreach ($value as $key2 => $value2) {
            $acctg[$key3][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
          }
          $acctg[$key3]['line'] = $line;
          $acctg[$key3]['editdate'] = $current_timestamp;
          $acctg[$key3]['editby'] = $config['params']['user'];
          $acctg[$key3]['encodeddate'] = $current_timestamp;
          $acctg[$key3]['encodedby'] = 'EXTRACTION';
          $acctg[$key3]['trno'] = $config['params']['trno'];
          $acctg[$key3]['db'] = round($acctg[$key3]['db'], 2);
          $acctg[$key3]['cr'] = round($acctg[$key3]['cr'], 2);
          $acctg[$key3]['fdb'] = round($acctg[$key3]['fdb'], 2);
          $acctg[$key3]['fcr'] = round($acctg[$key3]['fcr'], 2);

          unset($acctg[$key3]['client']);

          if ($this->coreFunctions->sbcinsert("gldetail", $acctg[$key3]) == 1) {
          } else {
            return ['status' => false, 'msg' => 'Extraction Failed-Error on Accounting Entry(' . 'trno: ' . $config['params']['trno'] . ' docno: ' . $config['params']['docno'] . ')'];
          }
          $line += 1;
        }

        $this->coreFunctions->execqry(" insert into arledger(dateid,trno,line,acnoid,clientid,db,cr,bal,docno,ref,agentid,fdb,fcr,forex)
                select d.postdate,d.trno,d.line,coa.acnoid,d.clientid,round(d.db,2),round(d.cr,2),round(d.db+d.cr,2) as bal, head.docno,d.ref,d.agentid,d.fdb,d.fcr,d.forex
                from glhead as head left join gldetail as d on head.trno=d.trno left join coa on coa.acnoid=d.acnoid left join arledger as ar on ar.trno=d.trno and ar.line=d.trno
                where left(coa.alias,2)='AR' and d.trno=" . $config['params']['trno'] . " and d.refx=0  and ar.trno is null");

        $this->coreFunctions->execqry("insert into apledger(dateid,trno,line,acnoid,clientid,db,cr,bal,fdb,fcr,docno,ref,cur,forex)
              select d.postdate,d.trno,d.line,d.acnoid,d.clientid,round(d.db,2),round(d.cr,2),round(d.db,2)+round(d.cr,2) as bal,d.fdb,d.fcr,head.docno,d.ref,d.cur,d.forex
              from glhead as head left join gldetail as d on head.trno=d.trno
              left join coa on coa.acnoid=d.acnoid left join apledger as ap on ap.trno=d.trno and ap.line=d.line
              where left(coa.alias,2)='AP' and d.trno=" . $config['params']['trno'] . " and d.refx=0 and ap.trno is null");
      }
    }

    return ['status' => true, 'msg' => 'Finished.'];
  }

  public function recomputecost($config)
  {
    ini_set('max_execution_time', 0);

    $data = $this->coreFunctions->opentable("select h.docno, h.doc, s.trno, s.line, s.cost, rs.cost as costrr, s.itemid, s.iss
              from glstock as s left join glhead as h on h.trno=s.trno
              left join costing as c on c.trno=s.trno and c.line=s.line
              left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex
              where s.cost=0 and s.iss<>0 and rs.cost<>0");

    $ctr = 1;
    foreach ($data as $key => $value) {

      $costing = $this->coreFunctions->opentable("select rs.cost, c.served from costing as c left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex where c.trno=" . $value->trno . " and c.line=" . $value->line);
      if (count($costing) == 1) {
        $this->coreFunctions->LogConsole($ctr . ". trno:" . $value->trno . ", line:" . $value->line . ", cost:" . $value->costrr);
        $this->coreFunctions->execqry("update glstock set cost=" . $value->costrr . " where trno=" . $value->trno . " and line=" . $value->line . " and refx=0");
        $this->coreFunctions->execqry("update glstock set cost=" . $value->costrr . " where trno=" . $value->trno . " and tstrno=" . $value->trno . " and tsline=" . $value->line);
        $this->coreFunctions->execqry("update rrstatus set cost=" . $value->costrr . ", isrecosting=1 where trno=" . $value->trno . " and itemid=" . $value->itemid);

        $this->coreFunctions->execqry("update glstock as s1 left join glstock as s2 on s2.tstrno=s1.trno and s2.tsline=s1.line
                    left join rrstatus as rs on rs.trno=s2.trno and rs.line=s2.line set rs.cost=s1.cost, rs.isrecosting=1 where s1.trno=" . $value->trno . " and s1.line=" . $value->line);
      }

      $ctr += 1;
    }

    return ['status' => true, 'msg' => 'Finished updating'];
  }

  private function deleteitems($config)
  {
    $ctr = 0;
    $items = $this->coreFunctions->opentable("select itemid, barcode from item order by itemid");
    foreach ($items as $key => $value) {

      $exist = $this->coreFunctions->opentable("
          select stock.trno from lastock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from prstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from hprstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from cdstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from hcdstock as stock where stock.itemid=" . $value->itemid . "         
          union all
          select stock.trno from postock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from hpostock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from sostock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from hsostock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from qsstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from hqsstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from qtstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from hqtstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from trstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from htrstock as stock where stock.itemid=" . $value->itemid . "
          union all
          select stock.trno from glstock as stock  where stock.itemid=" . $value->itemid . "
      ");

      if (empty($exist)) {
        $ctr += 1;
        $this->coreFunctions->LogConsole('No transactions:' . $value->barcode . "(" . $value->itemid . ") Ctr: " .  $ctr);
        $this->coreFunctions->execqry("delete from item where itemid=" . $value->itemid);
        $this->coreFunctions->execqry("delete from uom where itemid=" . $value->itemid);
        $log = [
          'trno' => $value->itemid,
          'docno' => $value->barcode,
          'field' => 'TRANSACTION',
          'userid' => $config['params']['user'],
          'userid' => $this->othersClass->getCurrentTimeStamp(),
        ];
        $this->coreFunctions->sbcinsert("del_item_log", $log);
      }
    }

    return ['status' => true, 'msg' => 'Finished deleting'];
  }

  private function taguomforsales($config)
  {
    $data = $this->coreFunctions->opentable("select i.itemid, i.barcode, count(uom.itemid) as cnt
    from item as i left join uom on uom.itemid=i.itemid
    group by i.itemid, i.barcode");

    foreach ($data as $key => $value) {
      if ($value->cnt > 1) {
        $this->coreFunctions->execqry("update uom set issales=1 where factor>1 and itemid=" . $value->itemid);
      } else {
        $this->coreFunctions->execqry("update uom set issales=1 where factor=1 and itemid=" . $value->itemid);
      }
    }

    return ['status' => true, 'msg' => 'Finished updating'];
  }

  private function updatepostatus()
  {
    $po = $this->coreFunctions->opentable("select trno, postdate from transnum where doc='PO'");
    $this->updatestatid($po, 'po');

    $sa = $this->coreFunctions->opentable("select trno, postdate from transnum where doc='SA'");
    $this->updatestatid($sa, 'sa');

    $sb = $this->coreFunctions->opentable("select trno, postdate from transnum where doc='SB'");
    $this->updatestatid($sb, 'sb');

    $sc = $this->coreFunctions->opentable("select trno, postdate from transnum where doc='SC'");
    $this->updatestatid($sc, 'sc');

    return ['status' => true, 'msg' => 'Finished updating'];
  }

  function updatestatid($arr, $doc)
  {
    $table = "h" . $doc . "stock";
    if ($table != '') {
      foreach ($arr as $key => $val) {
        if ($val->postdate == null) {
          $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $val->trno);
        } else {
          $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from " . $table . " where trno=? and qty>qa", [$val->trno]);
          if ($status) {
            $status = $this->coreFunctions->datareader("select ifnull(count(trno),0) as value from " . $table . " where trno=? and qa<>0", [$val->trno]);
            if ($status) {
              $this->coreFunctions->execqry("update transnum set statid=6 where trno=" . $val->trno);
            } else {
              $this->coreFunctions->execqry("update transnum set statid=5 where trno=" . $val->trno);
            }
          } else {
            $this->coreFunctions->execqry("update transnum set statid=7 where trno=" . $val->trno);
          }
        }
      }
    }
  }


  function updateboxcount()
  {
    $this->coreFunctions->execqry("update cntnuminfo  as ci set ci.boxcount=(ifnull((select max(boxno) from boxinginfo where boxinginfo.trno=ci.trno), 0))");
    $this->coreFunctions->execqry("update hcntnuminfo  as ci set ci.boxcount=(ifnull((select max(boxno) from hboxinginfo where hboxinginfo.trno=ci.trno), 0))");
  }

  function redistributeinvcost($config)
  {

    $companyid= $config['params']['companyid'];
    $acnoid = 377;
    $filter = " h.doc in ('SJ','RR', 'AJ', 'DM') ";
    if($companyid == 60){//transpower
      $acnoid = 4940;
      $filter =" s.iss<>0 and s.cost<>0 ";
    }
    $qry = "select trno, docno, doc from ( 
            select (sum(round(s.qty * s.cost,2))-sum(round(s.iss * s.cost,2))) as cost, s.trno, h.docno, h.dateid, h.doc,
            ifnull(round((select sum(d.db-d.cr) from gldetail as d where d.trno=s.trno and d.acnoid=$acnoid),2),0) as acctg
            from glstock as s left join item on item.itemid=s.itemid
            left join glhead as h on h.trno=s.trno
            where $filter
            group by s.trno, h.docno, h.dateid, h.doc) as x 
            where cost<>acctg
            order by dateid,docno";


    $data = $this->coreFunctions->opentable($qry);
    $result = $this->redistributeentrybydoc($config, $data);
    if (!$result['status']) {
      return $result;
    }
    return ['status' => true, 'msg' => 'Finished updating'];
  }

  function recalcamt($config)
  {
  }

  function recalc($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $item = $this->coreFunctions->opentable("select itemid from (
            select lastock.itemid, lastock.qty, lastock.iss from lastock left join item on item.itemid=lastock.itemid where item.isrecalc=0
            union all
            select glstock.itemid, glstock.qty, glstock.iss from glstock left join item on item.itemid=glstock.itemid where item.isrecalc=0) as b 
            group by itemid
            having (sum(qty)-sum(iss))<>0");

    foreach ($item as $key => $value) {
      $itemid = $value->itemid;
      $this->coreFunctions->execqry("delete from costing where itemid=?", 'delete', [$itemid]);
      $this->coreFunctions->execqry("update rrstatus set bal=qty where itemid=?", 'update', [$itemid]);

      $qry = "select h.dateid, s.trno, s.line, s.itemid, s.uom, s.whid, s.isamt, s.amt, s.isqty, s.rrqty, s.iss, s.ext, h.tax, s.disc, 0 as posted, s.cost, h.cur, h.doc, s.loc, s.expiry,s.ref
          from lastock as s left join lahead as h on h.trno=s.trno where s.itemid=? and s.iss<>0
          union all
          select h.dateid, s.trno, s.line, s.itemid, s.uom, s.whid, s.isamt, s.amt, s.isqty, s.rrqty, s.iss, s.ext, h.tax, s.disc, 1 as posted, s.cost, h.cur, h.doc, s.loc, s.expiry,s.ref
          from glstock as s left join glhead as h on h.trno=s.trno where s.itemid=? and s.iss<>0 order by dateid";
      app('App\Http\Classes\modules\customform\viewstockcardtransactionledger')->recalctrans($config, $itemid, $qry);

      $this->coreFunctions->execqry("update item set isrecalc=1 where itemid=?", 'update', [$itemid]);
    }

    return ['status' => true, 'msg' => 'Finished updating'];
  }

  function reupdatecost($config)
  {
    $data = $this->coreFunctions->opentable("select h.doc, s.trno, s.line, s.qty, s.iss, s.cost, s.refx, s.linex from glhead as h left join glstock as s on s.trno=h.trno where h.isreentryinv=1");
    foreach ($data as $key => $value) {
      switch ($value->doc) {
        case 'CM':
          if ($value->qty != 0) {
            $this->othersClass->logConsole('update costing');
            $this->coreFunctions->execqry("update costing as c left join glhead as h on h.trno=c.trno set h.isreentryinv=1 where c.refx=?", 'update', [$value->trno]);
          }
          break;
      }
    }
    return ['status' => true, 'msg' => 'Finished'];
  }

  function updateisscost($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');

    $data = $this->coreFunctions->opentable("select h.docno, h.doc, s.trno, s.line, s.iss, round(s.cost,4) as cost,
            round((select round(ifnull(sum(rs.cost * c.served) / stock.iss,0), 6) from glstock as stock left join costing as c on c.trno=stock.trno and c.line=stock.line
            left join rrstatus as rs on rs.trno=c.refx and rs.line=c.linex 
            where stock.trno=s.trno and stock.line=s.line  
            group by stock.trno,stock.line,stock.iss),4) as actualcost
            from glstock as s left join glhead as h on h.trno=s.trno where s.iss<>0 and h.doc in ('TS', 'AJ', 'DM')");

    foreach ($data as $key => $value) {
      if ($value->actualcost != $value->cost) {
        $this->coreFunctions->execqry("update glstock set cost=" . $value->actualcost . " where trno=" . $value->trno . " and line=" . $value->line);
        if ($value->actualcost == 'AJ') {
          $this->coreFunctions->execqry("update glstock set ext=round((iss*cost),2)*-1 where trno=" . $value->trno . " and line=" . $value->line);
        }
        $this->coreFunctions->execqry("update glhead set isreentryinv=1 where trno=" . $value->trno);
        $this->coreFunctions->execqry("update cntnum set isok=2 where trno=" . $value->trno);
      }
    }

    return ['status' => true, 'msg' => 'Finished'];
  }

  function redistributeentry($config)
  {
    // 2024.03.25 - restribute from TS update cost
    $data = $this->coreFunctions->opentable("select h.trno, h.docno, h.doc from glhead as h left join cntnum as c on c.trno=h.trno where h.doc='DM' and h.isreentryinv=1 and c.isok=2");
    $result = $this->redistributeentrybydoc($config, $data);
    if (!$result['status']) {
      return $result;
    }

    $data = $this->coreFunctions->opentable("select h.trno, h.docno, h.doc from glhead as h left join cntnum as c on c.trno=h.trno where h.doc='SJ' and h.isreentryinv=1 and c.isok=2");
    $result = $this->redistributeentrybydoc($config, $data);
    if (!$result['status']) {
      return $result;
    }

    $data = $this->coreFunctions->opentable("select h.trno, h.docno, h.doc from glhead as h left join cntnum as c on c.trno=h.trno where h.doc='AJ' and h.isreentryinv=1 and c.isok=2");
    $result = $this->redistributeentrybydoc($config, $data);
    if (!$result['status']) {
      return $result;
    }
    // end of 2024.03.25 - restribute from TS update cost


    return ['status' => true, 'msg' => 'Finished'];
  }

  function redistributeentrybydoc($config, $data)
  {
    $success = false;
    foreach ($data as $key => $val) {
      $this->acctg = [];
      $this->othersClass->logConsole('trno: ' . $val->trno .  ' - '  . $val->docno);

      try {
        $qry = 'select head.trno,head.dateid,head.doc,head.docno,client.clientid as client,head.tax,head.contra,head.cur,head.forex,stock.ext,wh.clientid as wh,ifnull(item.asset,"") as asset,ifnull(item.revenue,"") as revenue,item.expense,
          stock.isamt,stock.cost,stock.amt,stock.disc,stock.rrqty,stock.qty,stock.iss,stock.fcost,head.projectid,client.rev,((stock.qty-stock.iss) * stock.cost) as ajext
          from glhead as head left join glstock as stock on stock.trno=head.trno
          left join client as wh on wh.clientid=stock.whid
          left join client on client.clientid=head.clientid
          left join item on item.itemid=stock.itemid where head.trno=?';

        $stock = $this->coreFunctions->opentable($qry, [$val->trno]);
        $tax = 0;

        if (!empty($stock)) {
          $invacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['IN1']);
          $revacct = $this->coreFunctions->getfieldvalue('coa', 'acno', 'alias=?', ['SA1']);

          $forex = $stock[0]->forex;

          $vat = $stock[0]->tax;
          $tax1 = 0;
          $tax2 = 0;
          if ($vat != 0) {
            $tax1 = 1 + ($vat / 100);
            $tax2 = $vat / 100;
          }

          $this->othersClass->logConsole($stock[0]->docno);

          foreach ($stock as $key => $value) {

            $params = [];

            switch ($stock[$key]->doc) {
              case 'SJ':
              case 'DM':
                $stock[$key]->qty = $stock[$key]->iss;
                break;
              case 'AJ':
                if ($stock[$key]->rrqty < 0) {
                  $stock[$key]->qty = $stock[$key]->iss;
                }
                $stock[$key]->ext = $stock[$key]->ajext;
                break;
            }

            $disc = $stock[$key]->isamt - ($this->othersClass->discount($stock[$key]->isamt, $stock[$key]->disc));
            if ($vat != 0) {
              $tax = number_format(($stock[$key]->ext / $tax1), 2, '.', '');
              $tax = number_format(($stock[$key]->ext - $tax), 2, '.', '');
            }

            if ($stock[$key]->revenue != '') {
              $revacct = $stock[$key]->revenue;
            } else {
              if ($stock[$key]->rev != '' && $stock[$key]->rev != '\\') {
                $revacct = $stock[$key]->rev;
              }
            }
            $expense = isset($stock[$key]->expense) ? $stock[$key]->expense : '';

            switch ($stock[$key]->doc) {
              case 'SJ':
              case 'DM':
                $cost = number_format($stock[$key]->cost * $stock[$key]->qty, 2, '.', '');
                break;
              case 'AJ':
                $cost = $stock[$key]->ajext;
                break;
              default:
                $cost = $stock[$key]->cost * $stock[$key]->qty;
                break;
            }

            $params = [
              'client' => $stock[$key]->client,
              'acno' => $stock[$key]->contra,
              'ext' => number_format($stock[$key]->ext, 2, '.', ''),
              'wh' => $stock[$key]->wh,
              'date' => $stock[$key]->dateid,
              'inventory' => $stock[$key]->asset !== '' ? $stock[$key]->asset : $invacct,
              'revenue' => $stock[$key]->revenue !== '' ? $stock[$key]->revenue : $revacct,
              'expense' => $expense,
              'tax' =>  $tax,
              'discamt' => number_format($disc * $stock[$key]->rrqty, 2, '.', ''),
              'cost' => $cost,
              'cur' => $stock[$key]->cur,
              'forex' => $stock[$key]->forex,
              'fcost' => number_format($stock[$key]->fcost * $stock[$key]->qty, 2, '.', ''),
              'projectid' => $stock[$key]->projectid,
              'doc' => $stock[$key]->doc
            ];
            $config['params']['doc'] = $stock[$key]->doc;
            $config['params']['trno'] = $val->trno;

            $this->distribution($params, $config);
          }

          $this->othersClass->logConsole('distribution - ' . $val->docno);

          if (!empty($this->acctg)) {
            $this->othersClass->logConsole('acctg - ' . json_encode($this->acctg));
            $line = $this->coreFunctions->datareader("select max(line)+1 as value from gldetail where trno=?", [$val->trno]);
            if ($line == '') {
              $line = 0;
            }

            $current_timestamp = $this->othersClass->getCurrentTimeStamp();
            foreach ($this->acctg as $key => $value) {
              foreach ($value as $key2 => $value2) {
                $this->acctg[$key][$key2] = $this->othersClass->sanitizekeyfield($key2, $value2);
              }

              $this->acctg[$key]['line'] =  $line;

              $this->acctg[$key]['editdate'] = $current_timestamp;
              $this->acctg[$key]['editby'] = $config['params']['user'];
              $this->acctg[$key]['encodeddate'] = $current_timestamp;
              $this->acctg[$key]['encodedby'] = $config['params']['user'];
              $this->acctg[$key]['trno'] = $config['params']['trno'];
              $this->acctg[$key]['db'] = number_format($this->acctg[$key]['db'], 2, '.', '');
              $this->acctg[$key]['cr'] = number_format($this->acctg[$key]['cr'], 2, '.', '');
              $this->acctg[$key]['fdb'] = number_format($this->acctg[$key]['fdb'], 2, '.', '');
              $this->acctg[$key]['fcr'] = number_format($this->acctg[$key]['fcr'], 2, '.', '');

              $this->acctg[$key]['clientid'] = $this->acctg[$key]['client'];
              unset($this->acctg[$key]['client']);

              $this->coreFunctions->execqry("delete from gldetail where trno=? and acnoid=?", 'delete', [$val->trno, $this->acctg[$key]['acnoid']]);

              $line += 1;
            }

            if ($this->coreFunctions->sbcinsert('gldetail', $this->acctg) == 1) {
              $this->logger->sbcwritelog($val->trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING RE-DISTRIBUTION SUCCESS', 'table_log');
            } else {
              $this->othersClass->logConsole('failed distribution - ' . $val->docno);
              $this->logger->sbcwritelog($val->trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING RE-DISTRIBUTION FAILED', 'table_log');
            }

            checkvariancehere:
            $bal = $this->coreFunctions->getfieldvalue('gldetail', 'sum(db-cr)', 'trno=?', [$val->trno]);
            if ($bal == 0) {
            } else {
              $glc = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias=?", ["GLC"], '', true);
              if ($val->doc == 'DM') {
                $glcexist = $this->coreFunctions->getfieldvalue("gldetail", "trno", "trno=? and acnoid=?", [$val->trno, $glc], '', true);
                if ($glcexist != 0) {
                  $this->othersClass->logConsole('delete glc entry');
                  $this->coreFunctions->execqry("delete from gldetail where trno=? and acnoid=?", 'delete', [$val->trno,  $glc]);
                  goto  checkvariancehere;
                }
              }

              switch ($val->doc) {
                case 'RR':
                case 'AJ':
                case 'DM':

                  if ($glc != 0) {
                    $stock[0]->forex = $stock[0]->forex == 0 ? 1 : $stock[0]->forex;
                    if ($bal > 0) {
                      $dbglc = 0;
                      $crglc = $bal;
                      $fdbglc = 0;
                      $fcrglc = $bal / $stock[0]->forex;
                    } else {
                      $dbglc = abs($bal);
                      $crglc = 0;
                      $fdbglc = abs($bal) / $stock[0]->forex;
                      $fcrglc = 0;
                    }

                    if ($stock[0]->forex == 1) {
                      $fdbglc = 0;
                      $fcrglc = 0;
                    }

                    $line = $this->coreFunctions->datareader("select max(line)+1 as value from gldetail where trno=?", [$val->trno]);
                    if ($line == '') {
                      $line = 0;
                    }
                    $entry = [
                      'line' => $line,
                      'acnoid' => $glc,
                      'clientid' => $stock[0]->wh,
                      'db' =>  $dbglc,
                      'cr' => $crglc,
                      'postdate' => $stock[0]->dateid,
                      'cur' => $stock[0]->cur,
                      'forex' => $stock[0]->forex,
                      'fcr' => $fcrglc,
                      'fdb' => $fdbglc,
                      'projectid' => $stock[0]->projectid,
                      'encodeddate' => $current_timestamp,
                      'encodedby' => $config['params']['user'],
                      'trno' => $config['params']['trno'],
                      'rem' => 'AUTO-ENTRY'
                    ];

                    $this->othersClass->logConsole('glc - ' . json_encode($entry));
                    if ($this->coreFunctions->sbcinsert('gldetail', $entry) == 1) {
                      goto checkvariancehere;
                    } else {
                      return ['status' => true, 'msg' => 'GLC failed to insert ' . $val->docno];
                    }
                  } else {
                    $this->othersClass->logConsole('GLC missing ' . $val->docno);
                    return ['status' => true, 'msg' => 'GLC missing ' . $val->docno];
                  }
                  break;
                default:
                  $this->othersClass->logConsole('Account not balance - ' . $bal);
                  return ['status' => true, 'msg' => 'Account not balance ' . $val->docno];
                  break;
              }
            }
          }
        }

        $success = true;
      } catch (Exception $ex) {
        $success = false;
        $this->othersClass->logConsole('Redistribution error - line:' . $ex->getLine() . ' - ' . json_encode($ex));
        $this->coreFunctions->sbclogger('Redistribution error - line:' . $ex->getLine() . ' - ' . json_encode($ex));
        return ['status' => false, 'msg' => json_encode($ex)];
      }

      if ($success) {
        $this->coreFunctions->sbcupdate("cntnum", ['isok' => 1], ["trno" => $val->trno]);
      }
    }

    return ['status' => true, 'msg' => 'Success'];
  }

  public function distribution($params, $config)
  {
    $entry = [];
    $forex = $params['forex'];
    $cur = $params['cur'];
    $invamt = number_format($params['cost'], 2, '.', '');

    $dbcogs = 0;
    $crcogs = 0;

    $this->othersClass->logConsole('distribution func - ' . $params['doc'] . ' - amt: ' . $invamt);
    //INV
    if (floatval($invamt) != 0) {
      switch (strtoupper($params['doc'])) {
        case 'CM':
          $dbinv = $invamt;
          $crinv = 0;
          $dbcogs = 0;
          $crcogs = $invamt;
          break;
        case 'SJ':
          $dbinv = 0;
          $crinv = $invamt;
          $dbcogs = $invamt;
          $crcogs = 0;
          break;
        case 'RR':
          $dbinv = $invamt;
          $crinv = 0;
          break;
        case 'AJ':
          if ($invamt > 0) {
            $dbinv = $invamt;
            $crinv = 0;
          } else {
            $dbinv = 0;
            $crinv = abs($invamt);
          }
          break;
        case 'DM':
          $dbinv = 0;
          $crinv = $invamt;
          break;
      }

      $acnoid = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['inventory']]);
      $entry = ['acnoid' => $acnoid, 'client' => $params['wh'], 'db' =>  $dbinv, 'cr' => $crinv, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => 0, 'fdb' => floatval($forex) == 1 ? 0 : ($params['fcost']), 'projectid' => $params['projectid']];
      $this->othersClass->logConsole('inv - ' . json_encode($entry));
      $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

      $this->othersClass->logConsole('cogs accnt - ' . $params['expense']);
      if ($params['expense'] == '') {
        $cogs = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'alias=?', ['CG1']);
      } else {
        $cogs = $this->coreFunctions->getfieldvalue('coa', 'acnoid', 'acno=?', [$params['expense']]);
      }

      if ($dbcogs != 0 || $crcogs != 0) {
        $entry = ['acnoid' => $cogs, 'client' => $params['wh'], 'db' =>  $dbcogs, 'cr' => $crcogs, 'postdate' => $params['date'], 'cur' => $cur, 'forex' => $forex, 'fcr' => floatval($forex) == 1 ? 0 : ($params['fcost']), 'fdb' => 0, 'projectid' => $params['projectid']];
        $this->othersClass->logConsole('cogs - ' . json_encode($entry));
        $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
      }
    }
  }


  public function add($config)
  {
    $start = Carbon::parse($this->othersClass->getCurrentTimeStamp());
    $this->tableupdate($config);
    $end = Carbon::parse($this->othersClass->getCurrentTimeStamp());
    $elapsed = $start->diffInMinutes($end);
    return ['status' => true, 'msg' => 'TABLES UPDATE. Execution time(min):' . $elapsed];
  }

  private function selectqry()
  {

    $sql = "line,doc, master as psection,
            pvalue,yr";
    return $sql;
  }

  public function save($config)
  {
    $data = [];
    $row = $config['params']['row'];
    foreach ($this->fields as $key => $value) {
      $data[$value] = $this->othersClass->sanitizekeyfield($value, $row[$value]);
    }
    if ($row['line'] == 0) {
      $line = $this->coreFunctions->insertGetId($this->table, $data);
      if ($line != 0) {
        $returnrow = $this->loaddataperrecord($line);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    } else {
      if ($this->coreFunctions->sbcupdate($this->table, $data, ['line' => $row['line']]) == 1) {
        $returnrow = $this->loaddataperrecord($row['line']);
        return ['status' => true, 'msg' => 'Successfully saved.', 'row' => $returnrow];
      } else {
        return ['status' => false, 'msg' => 'Saving failed.'];
      }
    }
  } //end function

  public function saveallentry($config)
  {
    $data = $config['params']['data'];
    foreach ($data as $key => $value) {
      $data2 = [];
      if ($data[$key]['bgcolor'] != '') {
        foreach ($this->fields as $key2 => $value2) {
          $data2[$value2] = $this->othersClass->sanitizekeyfield($value2, $data[$key][$value2]);
        }
        if ($data[$key]['line'] == 0) {
          $line = $this->coreFunctions->insertGetId($this->table, $data2);
        } else {
          $this->coreFunctions->sbcupdate($this->table, $data2, ['line' => $data[$key]['line']]);
        }
      } // end if
    } // foreach
    $returndata = $this->loaddata($config);
    return ['status' => true, 'msg' => 'All saved successfully.', 'data' => $returndata];
  } // end function 

  private function loaddataperrecord($line)
  {
    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where doc='SED' and line=?";
    $data = $this->coreFunctions->opentable($qry, [$line]);
    return $data;
  }

  public function loaddata($config)
  {

    $alloweddoc = $this->coreFunctions->opentable('select doc from left_menu');
    $filter = '';
    foreach ($alloweddoc as $key => $value) {
      switch ($value->doc) {
        case 'customer':
          $value->doc = 'CL';
          break;
        case 'supplier':
          $value->doc = 'SL';
          break;
        case 'warehouse':
          $value->doc = 'WH';
          break;
        case 'employee':
        case 'employeemasterfile':
          $value->doc = 'EM';
          break;
        case 'department':
          $value->doc = 'DE';
          break;
        case 'agent':
          $value->doc = 'AG';
          break;
        case 'stockcard':
          $value->doc = 'IT';
          break;
      }
      if ($filter == '') {
        $filter = "'" . $value->doc . "'";
      } else {
        $filter .= ",'" . $value->doc . "'";
      }
    }

    $strfilter = '';
    if ($filter != '') {
      $strfilter = " and psection in (" . $filter . ")";
    }

    $select = $this->selectqry();
    $select = $select . ",'' as bgcolor ";
    $qry = "select " . $select . " from " . $this->table . " where doc='SED' " . $strfilter;
    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  // ADD NEW COLUMN
  private function tableupdate($config)
  {


    $this->trigger->deletetriggers();
    $this->waims->dataupdatewaims();
    $this->waims->tableupdatewaims($config);

    //10.31.2020 fmm
    $this->hms->tableupdatehms();

    $this->enrollment->tableupdateenrollment();

    //11.09.2020
    $this->customersupport->tableupdatecustomersupport();

    // 11.20.2020
    $this->hris->tableupdatehris();

    // 11.23.2020
    $this->payroll->tableupdatepayroll();

    // 05.14.2021
    $this->warehousing->tableupdatewarehousing();

    // GLEN 02.08.22
    $this->waims->modifyLengthField();

    $this->trigger->createtriggers($config); // -- ALWAYS AT THE BUTTOM PART

    $this->trigger->cleardb_proc();

    //FRED 05.05.2021
    $this->waims->lowecasefieldname();

    $this->documentmanagement->tableupdatedocumentmanagement();
  }
} //end class
