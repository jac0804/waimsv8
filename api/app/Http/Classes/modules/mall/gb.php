<?php

namespace App\Http\Classes\modules\mall;

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



class gb
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'Generate Billing';
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
    $attrib = array('view' => 4204);
    return $attrib;
  }


  public function createHeadbutton($config)
  {
    return [];
  }

  public function createTab($config)
  {
    $tab = [];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtab2($access, $config)
  {
    return [];
  }

  public function createtabbutton($config)
  {
    return [];
  }

  public function createHeadField($config)
  {
    $fields = ['month', 'byear'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'month.type', 'lookup');
    data_set($col1, 'month.readonly', true);
    data_set($col1, 'month.action', 'lookuprandom');
    data_set($col1, 'month.lookupclass', 'lookup_month');
    data_set($col1, 'byear.readonly', false);
    data_set($col1, 'byear.name', 'byear');

    $fields = ['client', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'client.label', 'Tenant');
    data_set($col2, 'client.lookupclass', 'tenant');
    data_set($col2, 'client.name', 'clientname');
    data_set($col2, 'client.readonly', false);
    data_set($col2, 'refresh.label', 'Create Billing');
    data_set($col2, 'refresh.action', 'refresh');

    $fields = [];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {

    $data = $this->coreFunctions->opentable("
      select '' as client,'' as month,'' as bmonth,'' as year,'' as byear");

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
      case 'refresh':
        return $this->generateBilling($config);
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
        return $this->generateBilling($config);
        break;

      default:
        return ['status' => false, 'msg' => 'Please check stockstatusposted (' . $action . ')'];
        break;
    } // end switch
  }

  private function generateBilling($config)
  {
    $bmonth = $config['params']['dataparams']['bmonth'];
    $byear = $config['params']['dataparams']['byear'];
    $tenant  = $config['params']['dataparams']['client'];
    $center  = $config['params']['center'];
    $user  = $config['params']['user'];
    $billdate = strtotime($byear . '-' . $bmonth . '-1');
    $filter = "";
    //if vatexsales is true all rates is considered vat ex
    $vatex = $this->companysetup->getvatexsales($config['params']);
    $daysdue = $this->companysetup->getdaysdue($config['params']);
    $peraccountar = $this->companysetup->peracctar($config['params']);
    $dateid = date("Y-m-d", strtotime("+" . intval($daysdue) . " day", $billdate));

    $duedate = strtotime($dateid);

    $prevbilldate =  strtotime("-1 month", $billdate);
    $prevduedate = strtotime("+" . intval($daysdue) . " days", $prevbilldate);
    $prevenddate =  strtotime("-1 day", $billdate);

    $billdate = $this->othersClass->datefilter(date("Y-m-d", $billdate));
    $prevbilldate = $this->othersClass->datefilter(date("Y-m-d", $prevbilldate));
    $duedate = $this->othersClass->datefilter(date("Y-m-d", $duedate));
    $prevduedate = $this->othersClass->datefilter(date("Y-m-d", $prevduedate));
    $prevenddate =  $this->othersClass->datefilter(date("Y-m-d", $prevenddate));

    if ($tenant != "") {
      $filter = " and c.client = '" . $tenant . "'";
    }

    //select tenants qry
    $qryselect = "select c.clientid,c.client, c.clientname, date_format(c.start,'%m/%d/%Y') as start, date_format(c.enddate,'%m/%d/%Y') as enddate,
    c.isnonvat,c.type, tinfo.leaserate as leaserate, tinfo.acrate as acrate, tinfo.cusarate as cusarate, tinfo.billtype, tinfo.rentcat, 
    tinfo.emulti, tinfo.semulti, tinfo.wmulti, tinfo.penalty, tinfo.mcharge as mcharge, tinfo.percentsales,
    tinfo.msales as msales, tinfo.elecrate as elecrate, tinfo.selecrate as selecrate, tinfo.waterrate as waterrate, tinfo.classification, 
    tinfo.eratecat,tinfo.wratecat, tinfo.tenanttype,loc.name as loc, loc.area, loc.emeter, loc.semeter, loc.wmeter,
    (select amt from electricrate where categoryid = elect.line order by dateid desc limit 1) as erate,(select amt from waterrate where categoryid = water.line order by dateid desc limit 1)  as wrate";


    $qry = $qryselect . "  from client as c
        left join tenantinfo as tinfo on tinfo.clientid = c.clientid
        left join loc as loc on loc.line = c.locid
        left join ratecategory as elect on elect.line = tinfo.eratecat
        left join ratecategory as water on water.line = tinfo.wratecat
        where c.center=? and c.istenant = 1 and c.isinactive = 0 and tinfo.billtype ='Monthly' " . $filter;

    $dttenant = $this->coreFunctions->opentable($qry, [$center]);

    //insert code for updating tenancy status
    foreach ($dttenant as $k => $v) {
      $tenancy = $this->coreFunctions->opentable("select line,monthsno,effectdate,status,datefrom,dateto from tenancystatus where date(effectdate) <='" . $billdate . "'  and clientid = " . $dttenant[$k]->clientid . " and applied <> 1 order by dateid limit 1");
      if (!empty($tenancy)) {
        switch ($tenancy[0]->status) {
          case 'Renewable':
          case 'Extended':
            $this->coreFunctions->execqry("update client set start = '" . $tenancy[0]->datefrom . "',enddate = '" . $tenancy[0]->dateto . "' where clientid = " . $dttenant[$k]->clientid);
            $this->coreFunctions->execqry("update tenancystatus set applied = 1,dateapplied = now(),appliedby = '" . $user . "' where clientid = " . $dttenant[$k]->clientid . " and line =" . $tenancy[0]->line);
            break;
          case 'Non-renewable':
            $this->coreFunctions->execqry("update client set isinactive =1 where clientid = " . $dttenant[$k]->clientid);
            $this->coreFunctions->execqry("update tenancystatus set applied = 1,dateapplied = now(),appliedby = '" . $user . "' where clientid = " . $dttenant[$k]->clientid . " and line =" . $tenancy[0]->line);
            break;
        }
      }
    }

    $dttenant = $this->coreFunctions->opentable($qry, [$center]); //requery after update tenancy

    //Checking if all utilities and other charges are posted
    if (!$this->checkifposted($config, "electricreading")) {
      return ['status' => false, 'msg' => 'Electricity Reading not yet posted.'];
    }

    if (!$this->checkifposted($config, "waterreading")) {
      return ['status' => false, 'msg' => 'Water Reading not yet posted.'];
    }

    if (!$this->checkifposted($config, "chargesbilling")) {
      return ['status' => false, 'msg' => 'Other Charges not yet posted.'];
    }

    //checking for unposted CR's, DM's, CM's, GJ's,APV's with AR Entry
    $unposted = $this->coreFunctions->datareader("select distinct cnt.docno as value from cntnum as cnt left join lahead as h on h.trno = cnt.trno 
    left join ladetail as d on d.trno = cnt.trno left join coa on coa.acnoid = d.acnoid left join client as c on c.client = h.client
    where cnt.postdate is null and cnt.doc in ('CR','GJ','GC','GD') and left(coa.alias,2) ='AR' and date(h.dateid) <='" . $billdate . "' and cnt.center = '" . $center . "' " . $filter . " limit 1");

    if (strlen($unposted) != 0) {
      return ['status' => false, 'msg' => 'There are unposted transactions...'];
    }

    $tax = 1;
    $tax1 = 1;
    $rent = 0;
    $sales = 0;
    $escal = 0;
    $water = 0;
    $elec = 0;
    $selec = 0;
    $others = 0;
    $vat = 0;
    $outbal = 0;
    $outar = 0;
    $outarbal = 0;
    $payments = 0;
    $gj = 0;
    $cm = 0;
    $dm = 0;
    $allpayments = 0;
    $allgj = 0;
    $allcm = 0;
    $prevsoareimbbal = 0; // reimbursible balance
    $prevsoabal = 0; // ar balance
    $prevsoaallbal = 0; //total balance
    $reimbpay = 0;
    $reimbgj = 0;
    $reimbbal = 0;

    foreach ($dttenant as $k => $v) {
      //Checking if month already billed
      $billed = $this->coreFunctions->opentable("select num.trno as value from cntnum as num left join glhead as h on h.trno = num.trno left join client as c on c.clientid = h.clientid 
      where date(h.dateid) = '" . $billdate . "' and num.doc ='MB' and num.center = '" . $center . "' and c.clientid = " . $dttenant[$k]->clientid);

      if (!empty($billed)) {
        if ($tenant <> '') {
          return ['status' => false, 'msg' => 'Already Billed...'];
        }
        goto nextclient;
      }

      $this->acctg = [];


      if ($dttenant[$k]->isnonvat <> 1) {
        $tax = 1.12;
        $tax1 = .12;
      }

      $escalrate = 0;
      $escal = $this->coreFunctions->opentable("select rate,line from escalation where date(dateid) <='" . $billdate . "'  and clientid = " . $dttenant[$k]->clientid . " and isapplied <> 1 order by dateid limit 1");
      if(!empty($escal)) $escal = $this->othersClass->val($escal[0]->rate);
      //getting rent vat ex
      switch (strtoupper($dttenant[$k]->rentcat)) {
        case '% OF SALES':
          $sales = $dttenant[$k]->msales;
          $sales = $this->othersClass->Discount($sales, $dttenant[$k]->percentsales) - $sales;

          if ($dttenant[$k]->leaserate != 0) {
            if ($escalrate != 0) {
              $rent = $this->othersClass->Discount($dttenant[$k]->leaserate, $escal[0]->rate);
              $this->coreFunctions->execqry("update escalation set oldrate =" . $dttenant[$k]->leaserate . ",isapplied = 1,dateapplied='" . $billdate . "', remarks = 'Escalation of " . $escal[0]->rate . " Applied on " . $billdate . " by " . $user . " from P " . $dttenant[$k]->leaserate . " to P " . $rent . " where line = " . $escal[0]->line . "'");
              $this->coreFunctions->execqry("update client set crlimit =" . $rent . " where clientid = " . $dttenant[0]->clientid);

              $rent = ($rent * $dttenant[$k]->area) / $tax;

              if ($rent < $sales) {
                $rent = $sales;
              }
            } else {
              $rent = ($dttenant[$k]->leaserate * $dttenant[$k]->area);
              if ($rent < $sales) {
                $rent = $sales;
              }
            }
          }


          break;
        case 'MONTHLY RENT + % OF SALES':
          $sales = $dttenant[$k]->msales;

          if ($dttenant[$k]->leaserate != 0) {
            if ($escalrate != 0) {
              $rent = $this->othersClass->Discount($dttenant[$k]->leaserate, $escal[0]->rate);
              $this->coreFunctions->execqry("update escalation set oldrate =" . $dttenant[$k]->leaserate . ",isapplied = 1,dateapplied='" . $billdate . "', remarks = 'Escalation of " . $escal[0]->rate . " Applied on " . $billdate . " by " . $user . " from P " . $dttenant[$k]->leaserate . " to P " . $rent . " where line = " . $escal[0]->line . "'");
              $this->coreFunctions->execqry("update client set crlimit =" . $rent . " where clientid = " . $dttenant[0]->clientid);

              $sales = $this->othersClass->Discount($sales, $dttenant[$k]->percentsales) - $sales;
              $rent = ($rent * $dttenant[$k]->area) + $sales;
            } else {
              $rent = ($dttenant[$k]->leaserate * $dttenant[$k]->area) + $sales;
            }
          }
          break;
        default:
          if ($dttenant[$k]->leaserate != 0) {
            if ($escalrate != 0) {
              $rent = $this->othersClass->Discount($dttenant[$k]->leaserate, $escal[0]->rate);

              $this->coreFunctions->execqry("update escalation set oldrate =" . $dttenant[$k]->leaserate . ",isapplied = 1,dateapplied='" . $billdate . "', remarks = 'Escalation of " . $escal[0]->rate . " Applied on " . $billdate . " by " . $user . " from P " . $dttenant[$k]->leaserate . " to P " . $rent . " where line = " . $escal[0]->line . "'");
              $this->coreFunctions->execqry("update client set crlimit =" . $rent . " where clientid = " . $dttenant[0]->clientid);

              $rent = ($rent * $dttenant[$k]->area);
            } else {
              $rent = ($dttenant[$k]->leaserate * $dttenant[$k]->area);
            }
          }
          break;
      } //switch

      $cusa = $dttenant[$k]->cusarate * $dttenant[$k]->area;
      $ac = $dttenant[$k]->acrate * $dttenant[$k]->area;

      //utilities
      if ($dttenant[$k]->waterrate != 0) {
        $water = $dttenant[$k]->waterrate;
      } else {
        if ($dttenant[$k]->wmeter != '') {
          $water = $this->coreFunctions->datareader("select (consump*wrate) as value from waterreading where bmonth=? and byear=? and clientid =?", [$bmonth, $byear, $dttenant[$k]->clientid]);
        }
      }

      if ($dttenant[$k]->elecrate != 0) {
        $elec = $dttenant[$k]->elecrate;
      } else {
        if ($dttenant[$k]->emeter != '') {
          $elec = $this->coreFunctions->datareader("select (consump*erate) as value from electricreading where bmonth=? and byear=? and clientid =?", [$bmonth, $byear, $dttenant[$k]->clientid]);
        }
      }

      //othercharges

      $dtother = $this->coreFunctions->opentable("select ifnull(sum(ch.amt),0) as amt,o.asset,o.revenue,o.isvat from chargesbilling as ch
      left join ocharges as o on o.line = ch.cline
      where ch.bmonth = " . $bmonth . " and ch.byear = " . $byear . " and ch.center = '" . $center . "' and
      ch.clientid = " . $dttenant[$k]->clientid . " and ch.isposted =1 group by o.asset,o.revenue,o.isvat");

      //computation of penalty
      //reimbursible charges not included on penalty (alias ARR)
      $prevsoaallbal = $this->coreFunctions->datareader("select amt as value from tenantbal where clientid = " . $dttenant[$k]->clientid . " and bmonth = month('" . $prevbilldate . "') and byear = year('" . $prevbilldate . "')");
      $prevsoabal = $this->coreFunctions->datareader("select aramt as value from tenantbal where clientid = " . $dttenant[$k]->clientid . " and bmonth = month('" . $prevbilldate . "') and byear = year('" . $prevbilldate . "')");
      $prevsoareimbbal = $this->coreFunctions->datareader("select reimb as value from tenantbal where clientid = " . $dttenant[$k]->clientid . " and bmonth = month('" . $prevbilldate . "') and byear = year('" . $prevbilldate . "')");

      //payment on or before due date
      $payments = $this->coreFunctions->datareader("select ifnull(sum(d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevduedate . "' 
      and h.doc in ('CR') and left(coa.alias,2)='AR' and left(coa.alias,3)<>'ARR'");

      //dm on or before due date
      $dm = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevduedate . "' 
      and h.doc in ('GD') and left(coa.alias,2)='AR'");

      //cm & gj on or before due date
      $cm = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevduedate . "' 
      and h.doc in ('GC') and left(coa.alias,2)='AR'");

      $gj = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevduedate . "' 
      and h.doc in ('GJ','AR') and left(coa.alias,2)='AR'");

      //payment for the month
      $allpayments = $this->coreFunctions->datareader("select ifnull(sum(d.db),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevenddate . "' 
      and h.doc in ('CR') and left(coa.alias,2)='AR' ");

      //all adjustment
      $allgj = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevenddate . "' 
      and h.doc in ('GJ','GD','AR') and left(coa.alias,2)='AR'");

      //all Cm
      $allcm = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevenddate . "' 
      and h.doc in ('GC') and left(coa.alias,2)='AR'");

      $arpay = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevenddate . "' 
      and h.doc in ('CR','GC') and left(coa.alias,2)='AR' and left(coa.alias,3)<> 'ARR'");

      $argj = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevenddate . "' 
      and h.doc in ('GJ','GD','AR') and left(coa.alias,3)='AR'  and left(coa.alias,3)<> 'ARR'");

      $reimbpay = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevenddate . "' 
      and h.doc in ('CR','GC') and left(coa.alias,3)='ARR'");

      $reimbgj = $this->coreFunctions->datareader("select ifnull(sum(d.db-d.cr),0) as value from glhead as h left join gldetail as d on d.trno = h.trno 
      left join coa on coa.acnoid = d.acnoid where h.clientid =" . $dttenant[$k]->clientid . " and date(h.dateid) between '" . $prevbilldate . "' and '" . $prevenddate . "' 
      and h.doc in ('GJ','GD','AR') and left(coa.alias,3)='ARR'");

      $outar = ($prevsoabal + $dm + $gj) - (abs($payments) + abs($cm)); //for penalty

      $outarbal = ($prevsoabal + $argj) - abs($arpay); // for aramt
      $outbal = ($prevsoaallbal + $allgj) - (abs($allpayments) + abs($allcm)); // for amt
      $reimbbal = ($prevsoareimbbal + $reimbgj) - abs($reimbpay); //for reimb

      $penalty = 0;
      $totalar = 0;

      $this->coreFunctions->LogConsole('Outar:' . $prevsoabal . '-' . $outar . 'Payment:' . $payments . '-' . $cm . '-' . $dm . '-' . $gj);

      if ($outar > 0) {
        if ($vatex) {
          $penalty = ($outar * ($dttenant[$k]->penalty / 100)) / $tax;
        } else {
          $penalty = ($outar * ($dttenant[$k]->penalty / 100));
        }
      }


      $this->coreFunctions->LogConsole('Penalty:' . $penalty);

      $aracct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR1'");
      $rentacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA1'");
      $cusaacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA2'");
      $acacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA3'");
      $wateracct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA4'");
      $elecacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA5'");
      $penaltyacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='SA6'");
      $vatacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='TX2'");

      $totalar = ($rent + $cusa + $ac + $elec + $water + $penalty);


      //generate SJ
      try {
        if ($totalar <> 0) {

          $trno = $this->othersClass->generatecntnum($config, 'cntnum', 'MB', 'MB');
          $config['params']['trno'] = $trno;

          if ($trno != -1) {
            $docno =  $this->coreFunctions->getfieldvalue('cntnum', 'docno', "trno=?", [$trno]);
            $head = ['trno' => $trno, 'doc' => "MB", 'docno' => $docno, 'dateid' => $billdate, 'client' => $dttenant[$k]->client, 'clientname' => $dttenant[$k]->clientname, 'rem' => 'Monthly Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear, 'due' => $duedate];

            $inserthead = $this->coreFunctions->sbcinsert("lahead", $head);

            if ($inserthead) {
              $this->logger->sbcwritelog($trno, $config, 'CREATE', $docno . ' - ' . $dttenant[$k]->client . ' - ' . $dttenant[$k]->clientname);
              if (!$peraccountar) {
                if ($vatex) {
                  $totalar = $totalar * $tax;
                }
                //AR
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $aracct,
                  'db' => $totalar,
                  'cr' => 0,
                  'rem' => 'Monthly Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear,
                  'postdate' => $billdate
                ];

                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              } else { //per ar account
                $arrentacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR1'");
                $arcusaacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR2'");
                $aracacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR3'");
                $arwateracct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR4'");
                $arelecacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR5'");
                $arpenaltyacct = $this->coreFunctions->getfieldvalue("coa", "acnoid", "alias='AR6'");

                if ($vatex) {
                  $totalar = $totalar * $tax;
                }

                //AR
                if ($vatex) {
                  $rent = $rent * $tax;
                  $cusa = $cusa * $tax;
                  $ac = $ac * $tax;
                  $water = $water * $tax;
                  $elec = $elec * $tax;
                  $penalty = $penalty * $tax;
                }


                if ($rent <> 0) {
                  $entry = [
                    'client' => $dttenant[$k]->client,
                    'acnoid' => $arrentacct,
                    'db' => $rent,
                    'cr' => 0,
                    'rem' => '',
                    'postdate' => $billdate
                  ];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }


                if ($cusa <> 0) {
                  $entry = [
                    'client' => $dttenant[$k]->client,
                    'acnoid' => $arcusaacct,
                    'db' => $cusa,
                    'cr' => 0,
                    'rem' => '',
                    'postdate' => $billdate
                  ];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }

                if ($ac <> 0) {
                  $entry = [
                    'client' => $dttenant[$k]->client,
                    'acnoid' => $aracacct,
                    'db' => $ac,
                    'cr' => 0,
                    'rem' => '',
                    'postdate' => $billdate
                  ];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }

                if ($water <> 0) {
                  $entry = [
                    'client' => $dttenant[$k]->client,
                    'acnoid' => $arwateracct,
                    'db' => $water,
                    'cr' => 0,
                    'rem' => '',
                    'postdate' => $billdate
                  ];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }

                if ($elec <> 0) {
                  $entry = [
                    'client' => $dttenant[$k]->client,
                    'acnoid' => $arelecacct,
                    'db' => $elec,
                    'cr' => 0,
                    'rem' => '',
                    'postdate' => $billdate
                  ];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }

                if ($penalty <> 0) {
                  $entry = [
                    'client' => $dttenant[$k]->client,
                    'acnoid' => $arpenaltyacct,
                    'db' => $penalty,
                    'cr' => 0,
                    'rem' => '',
                    'postdate' => $billdate
                  ];
                  $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
                }

                if ($vatex) {
                  $rent = $rent / $tax;
                  $cusa = $cusa / $tax;
                  $ac = $ac / $tax;
                  $water = $water / $tax;
                  $elec = $elec / $tax;
                  $penalty = $penalty / $tax;
                }
              }

              if (!$vatex) {
                $rent = $rent / $tax;
                $cusa = $cusa / $tax;
                $ac = $ac / $tax;
                $water = $water / $tax;
                $elec = $elec / $tax;
                $penalty = $penalty / $tax;
              }

              //Sales
              if ($rent <> 0) {
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $rentacct,
                  'db' => 0,
                  'cr' => $rent,
                  'rem' => 'Rent Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear,
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }

              if ($cusa <> 0) {
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $cusaacct,
                  'db' => 0,
                  'cr' => $cusa,
                  'rem' => 'CUSA Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear,
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }

              if ($ac <> 0) {
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $acacct,
                  'db' => 0,
                  'cr' => $ac,
                  'rem' => 'Aircon Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear,
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }

              if ($water <> 0) {
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $wateracct,
                  'db' => 0,
                  'cr' => $water,
                  'rem' => 'Water Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear,
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }

              if ($elec <> 0) {
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $elecacct,
                  'db' => 0,
                  'cr' => $elec,
                  'rem' => 'Electricity Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear,
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }

              if ($penalty <> 0) {
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $penaltyacct,
                  'db' => 0,
                  'cr' => $penalty,
                  'rem' => 'Penalty Charges for the month of ' . date("F", mktime(0, 0, 0, $bmonth, 10)) . ', ' . $byear,
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }

              $vat = ($rent + $cusa + $ac + $elec + $water + $penalty) * $tax1;

              //othercharges
              foreach ($dtother as $o => $c) {
                $amt = 0;

                if ($dtother[$o]->isvat == 1) {
                  $amt = $dtother[$o]->amt / $tax;
                  $vat = $vat + ($amt * $tax1);
                  $totalar = $totalar + $dtother[$o]->amt;
                } else {
                  $amt = $dtother[$o]->amt;
                  $totalar = $totalar + $amt;
                }

                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $dtother[$o]->asset,
                  'db' => $dtother[$o]->amt,
                  'cr' => 0,
                  'rem' => '',
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);

                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $dtother[$o]->revenue,
                  'db' => 0,
                  'cr' => $amt,
                  'rem' => '',
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
              }

              if ($vat <> 0) {
                $entry = [
                  'client' => $dttenant[$k]->client,
                  'acnoid' => $vatacct,
                  'db' => 0,
                  'cr' => $vat,
                  'rem' => '',
                  'postdate' => $billdate
                ];
                $this->acctg = $this->othersClass->upsertdetail($this->acctg, $entry, $config);
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
                }
                if ($this->coreFunctions->sbcinsert($this->detail, $this->acctg) == 1) {
                  $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION SUCCESS');
                  $status = true;
                } else {
                  $this->logger->sbcwritelog($trno, $config, 'DETAILS', 'AUTOMATIC ACCOUNTING DISTRIBUTION FAILED');
                  $status = false;
                }
              }

              $this->coreFunctions->LogConsole($totalar . 'out' . $outbal);

              //posting
              $ret = $this->othersClass->posttransacctg($config);
              if ($ret['status']) {
                $ar = $this->coreFunctions->datareader("select ifnull(sum(ar.bal),0) as value from arledger as ar left join coa on coa.acnoid = ar.acnoid where left(coa.alias,3)<>'ARR' and trno =" . $trno);
                $ar2 = $this->coreFunctions->datareader("select ifnull(sum(ar.bal),0) as value from arledger as ar left join coa on coa.acnoid = ar.acnoid where left(coa.alias,3)='ARR' and trno =" . $trno);
                //insert soa bal to tenantbal
                $tb['clientid'] = $dttenant[$k]->clientid;
                $tb['bmonth'] = $bmonth;
                $tb['byear'] = $byear;
                $tb['amt'] = ($totalar + $outbal);
                $tb['aramt'] = $outarbal + $ar;
                $tb['reimb'] = $reimbbal + $ar2;

                if ($this->coreFunctions->sbcinsert('tenantbal', $tb)) {
                  //delete localtables
                  try {
                    $qry = "insert into hwaterreading (line, clientid, wstart, wend, wrate, bmonth, byear, center, readstart, readend, isposted, consump)
                    select line, clientid, wstart, wend, wrate, bmonth, byear, center, readstart, readend, isposted, consump
                    from waterreading where clientid = " . $dttenant[$k]->clientid . " and bmonth = '" . $bmonth . "' and byear = '" . $byear . "' and center = '" . $center . "'";
                    $this->coreFunctions->execqry($qry, 'insert');

                    $qry = "insert into helectricreading (line, clientid, estart, eend, erate, bmonth, byear, center, readstart, readend, isposted, consump)
                    select line, clientid, estart, eend, erate, bmonth, byear, center, readstart, readend, isposted, consump
                    from electricreading where clientid = " . $dttenant[$k]->clientid . " and bmonth = '" . $bmonth . "' and byear = '" . $byear . "' and center = '" . $center . "'";
                    $this->coreFunctions->execqry($qry, 'insert');

                    $qry = "insert into hchargesbilling (line, cline, amt, bmonth, byear,clientid, center,rem, isposted)
                    select line, cline, amt, bmonth, byear,clientid, center,rem, isposted
                    from chargesbilling where clientid = " . $dttenant[$k]->clientid . " and bmonth = '" . $bmonth . "' and byear = '" . $byear . "' and center = '" . $center . "'";
                    $this->coreFunctions->execqry($qry, 'insert');

                    $this->coreFunctions->execqry("delete from waterreading where clientid = " . $dttenant[$k]->clientid . " and bmonth = '" . $bmonth . "' and byear = '" . $byear . "' and center = '" . $center . "'", 'delete');
                    $this->coreFunctions->execqry("delete from electricreading where clientid = " . $dttenant[$k]->clientid . " and bmonth = '" . $bmonth . "' and byear = '" . $byear . "' and center = '" . $center . "'", 'delete');
                    $this->coreFunctions->execqry("delete from chargesbilling where clientid = " . $dttenant[$k]->clientid . " and bmonth = '" . $bmonth . "' and byear = '" . $byear . "' and center = '" . $center . "'", 'delete');
                  } catch (Exception $ex) {
                    $this->coreFunctions->LogConsole(substr($ex, 0, 1000));
                    return ['status' => false, 'msg' => ' ' . substr($ex, 0, 1000)];
                  }
                }
              } else {
                $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
                $this->coreFunctions->execqry('delete from lahead where trno=?', 'delete', [$trno]);
                $this->coreFunctions->execqry('delete from ladetail where trno=?', 'delete', [$trno]);
                return ['status' => true, 'msg' => "Error in Posting" . $ret['msg']];
              }
            } else {
              $this->coreFunctions->execqry('delete from cntnum where trno=?', 'delete', [$trno]);
            }
          }
        }
      } catch (Exception $ex) {
        $this->coreFunctions->LogConsole(substr($ex, 0, 1000));
        return ['status' => false, 'msg' => ' ' . substr($ex, 0, 1000)];
      }
      nextclient:
    } //for tenant

    return ['status' => true, 'msg' => "Bill successfully generated.", 'action' => 'load'];
  }

  private function checkifposted($config, $tbl)
  {
    $bmonth = $config['params']['dataparams']['bmonth'];
    $byear = $config['params']['dataparams']['byear'];
    $tenant  = $config['params']['dataparams']['client'];
    $center  = $config['params']['center'];
    $filter = "";

    if ($tenant != "") {
      $filter = " and client.client = '" . $tenant . "'";
    }

    $isposted = $this->coreFunctions->datareader("select isposted as value from $tbl as t left join client on client.clientid = t.clientid 
    where t.isposted =0 and t.bmonth =? and t.byear = ? and t.center =? " . $filter . " limit 1", [$bmonth, $byear, $center], '', true);
    if ($isposted == 0) {
      return true;
    } else {
      return false;
    }
  }


  private function loaddata($config)
  {

    return ['status' => true, 'msg' => 'Successfully loaded.', 'action' => 'load', 'griddata' => ['entrygrid' => []]];
  }
} //end class
