<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class detailed_customer_receivables
{
  public $modulename = 'Detailed Customer receivables';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1300'];

  public function __construct()
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    // $companyid = 5;
    switch ($companyid) {
     
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'end', 'dclientname','dbranchname','collectorname'];
        
        break;
      default:
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dbranchname', 'collectorname', 'contra'];
        
        break;
     
    }

    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'contra.lookupclass', 'AR');
    

    
    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['radioretagging'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      adddate(date(now()),-360) as start,date(now()) as end,
      '' as branch,
      '' as branchname,
      '' as client,
      '0' as posttype,
      '0' as reporttype,
      '' as dclientname,
      '' as dbranchname,
      '' as branchcode,
      '' as agent,
      '' as agentname,
      '' as dagentname,
      '' as contra,
      '0' as acnoid,
      '0' as tagging,
      '' as collectorname,
      '' as collectorcode,
      '' as collector,
      '0' as collectorid
      ";

    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportplotting($config, $result)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $data = $this->reportDefaultLayout_LAYOUT_DETAILED($config, $result);

    return $data;
  }

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->reportDefault_QUERY_AFTI($config); // POSTED
    $result = $this->coreFunctions->opentable($query);
    return $this->reportplotting($config, $result);
  }

  public function reportDefault_QUERY_AFTI($config)
  {
    $client       = $config['params']['dataparams']['client'];
    $contra = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];
    $tagging = $config['params']['dataparams']['tagging'];
    $collectorid = $config['params']['dataparams']['collectorid'];
    $collectorname = $config['params']['dataparams']['collectorname'];
    $branch = $config['params']['dataparams']['branch'];
    $branchname = $config['params']['dataparams']['branchname'];
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end =  date('Y-m-d', strtotime($config['params']['dataparams']['end']));

    $filter = "";
    $filter1 = "";
    if ($client != "") {
      $filter = " and client.client='$client' and client.client <> 'C000008502' and client.client <> 'C000002117' ";
    }

    // if ($contra != "") {
      $filter .= " and coa.acnoid in (5141,5396) ";
    // }

    if ($tagging == '0') {
      $filter .= " and client.iscustomer = 1 ";
    } else if ($tagging == 1) {
      $filter .= " and client.isemployee = 1 ";
    }

    if ($collectorname != '') {
      $filter = " and client.collectorid='$collectorid'";
    }

    if ($branchname != '') {
      $filter = " and gld.branch='$branch'";
    }

    $query = "select ifnull(agent,'') as agent, ifnull(agentname,'') as agentname, dateid, clientname, siref, ifnull(due,'') as due,
    yourref, (db-cr) as db, (db-cr)-ifnull((select sum(vpay.cr-vpay.db) from ( 
      select detail.refx AS refx,detail.linex AS linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
      from ladetail detail left join lahead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
      where detail.refx > 0 and date(head.dateid) between '$start' and '$end' 
      union all 
      select detail.refx,detail.linex,detail.db AS db,detail.cr AS cr,detail.line AS line 
      from gldetail detail left join glhead head on head.trno = detail.trno left join coa on coa.acnoid = detail.acnoid 
      left join client dclient on dclient.clientid = detail.clientid left join client on client.clientid = head.clientid 
      where detail.refx > 0 and date(head.dateid) between '$start' and '$end') as vpay where vpay.refx=x.trno and vpay.linex=x.line),0) as balance, elapse,
      terms,ifnull(cofficer,'') as cofficer,trno,alias,qttrno,line
      from (
      select 'p' as tr,head.trno,detail.line,head.doc,detail.ref, client.clientname, 
      ifnull(client.clientname,'no name') as name,
      date(detail.dateid) as dateid, detail.docno, (case ifnull(head.due,'') when '' then 0 else datediff(now(), head.due) end) as elapse,
      detail.db,detail.cr,
      (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance,
      (case when head.doc = 'AR' then gld.poref else head.yourref end) as yourref,
      (case when head.doc = 'AR' then gld.rem else head.docno end) as siref,
      head.terms, agent.client as agent, agent.clientname as agentname,
      head.due,collector.clientname as cofficer,coa.alias,gld.qttrno
      from (arledger as detail 
      left join client on client.clientid=detail.clientid)
      left join cntnum on cntnum.trno=detail.trno
      left join glhead as head on head.trno=detail.trno
      left join gldetail as gld on gld.trno=detail.trno and detail.line = gld.line
      left join client as agent on agent.clientid = detail.agentid
      left join client as collector on collector.clientid=agent.collectorid
      left join coa as coa on coa.acnoid = detail.acnoid
      left join delstatus as ds on ds.trno = head.trno
      where date(head.dateid) between '$start' and '$end' $filter $filter1  
    ) as x
    group by x.agent, x.agentname, x.dateid, x.clientname, x.siref, x.due, x.yourref, x.db,x.cr, x.elapse,
    x.terms,x.cofficer,x.trno,x.alias,x.qttrno,x.line
    order by agentname, clientname";
    // var_dump($query);
    return $query;
  }

  private function displayHeader_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $branch = $config['params']['dataparams']['branchname'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $contra = "";

    if ($companyid == 10) { //afti
      $contra   = $config['params']['dataparams']['contra'];
    }



    $str = '';
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DETAILED CUSTOMER RECEIVABLES', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= '</br>';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Transaction : ' . strtoupper($reporttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Branch : ' . $branch, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');



    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('SALES PARTNER', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COLLECTION OFFICER', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('POSTING DATE', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SI #', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DUE DATE', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO REFERENCE', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INVOICE AMOUNT', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOWNPAYMENT', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OUTSTANDING AMOUNT', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AGE', '50', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CURRENT', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91+', '70', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RETENTION', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TERMS', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_LAYOUT_DETAILED($config, $result)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $reporttype   = $config['params']['dataparams']['reporttype'];

    $this->reporter->linecounter = 0;
    $count = 52;
    $page = 55;

    $str = '';
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_DETAILED($config);
    $totinv = 0;
    $totdp = 0;
    $totamt = 0;
    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $totret = 0;

    foreach ($result as $key => $data) {
      if (floatval($data->balance) != 0) {
        $a = 0;
        $b = 0;
        $c = 0;
        $d = 0;
        $e = 0;
        $deposit = 0;
        $qtsalesperson = $data->agentname;
        $qtcollectionOfficer = $data->cofficer;
        $poref = $data->yourref;

        if ($data->alias == 'AR5') {
          if ($data->qttrno != 0) {
            $qry = "select c.clientname as cofficer,a.clientname as salesperson,head.yourref 
          from hqshead as head left join client as a on a.client = head.agent left join client as c on c.clientid = a.collectorid where head.trno = ?";

            $qtdetails = $this->coreFunctions->opentable($qry, [$data->qttrno]);
            if (!empty($qtdetails)) {
              $qtsalesperson = $qtdetails[0]->salesperson;
              $qtcollectionOfficer = $qtdetails[0]->cofficer;
              $poref = $qtdetails[0]->yourref;
            }
          }
        }

        $paytrno = $this->coreFunctions->datareader("
      select group_concat(distinct trno) as value from (
        select trno from ladetail where refx = " . $data->trno . " and linex = " . $data->line . "
        union all 
        select trno from gldetail where refx = " . $data->trno . " and linex = " . $data->line . "
      ) as a ");
        if ($paytrno != '') {


          $deposit = $this->coreFunctions->datareader("
        select ifnull(sum(db),0) as value from (
          select d.db from gldetail as d
          left join coa as c on c.acnoid=d.acnoid
          where d.trno in (" . $paytrno . ") and left(c.alias,3)='AR5' and d.refx<>0
          union all
          select d.db from ladetail as d
          left join coa as c on c.acnoid=d.acnoid
          where d.trno in (" . $paytrno . ") and left(c.alias,3)='AR5' and d.refx <>0
        ) as a");
        }


        $retention = 0;
        if ($data->alias == 'AR6') {
          $retention = $data->balance;
          // $str .= $this->reporter->col(number_format($retention, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }

        if ($data->elapse >= 0 && $data->elapse <= 30) {
          if ($data->alias != 'AR6') {
            $a = $data->balance;
          }
        }

        if ($data->elapse < 0) {
          if ($data->alias != 'AR6') {
            $a = $data->balance;
          }
        }


        if ($data->elapse >= 31 && $data->elapse <= 60) {
          if ($data->alias != 'AR6') {
            $b = $data->balance;
          }
        }
        if ($data->elapse >= 61 && $data->elapse <= 90) {
          if ($data->alias != 'AR6') {
            $c = $data->balance;
          }
        }
        if ($data->elapse >= 91) {
          if ($data->alias != 'AR6') {
            $d = $data->balance;
          }
        }

        $siref = $data->siref;
        if ($data->siref != '') {
          if (substr($data->siref, 0, 2) == 'DR') {
            $siref = str_replace("DR", "SI", $data->siref);
          }
        }

        $this->reporter->addline();
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($qtsalesperson, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        // $str .= $this->reporter->col($data->alias, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($qtcollectionOfficer, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $postdate = date_create($data->dateid);
        $postdate = date_format($postdate, "m/d/y");
        // POSTING DATE
        $str .= $this->reporter->col($postdate, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


        // CUSTOMER NAME
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        // SI#
        $str .= $this->reporter->col($siref, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $duedate = date_create($data->due);
        $duedate = date_format($duedate, "m/d/y");
        // DUE DATE
        $str .= $this->reporter->col($duedate, '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        // PO REF
        $str .= $this->reporter->col($poref, '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        //INV AMT / DOWNPAYMENT / OUTSTANDING
        $invamt = 0;
        if ($data->alias == 'AR5') {
          $invamt = 0;
          $str .= $this->reporter->col('0.00', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $deposit = $data->db;
          if ($data->db < 1) {
            $deposit = $data->db * -1;
          }
          $str .= $this->reporter->col(number_format($deposit, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          // $str .= $this->reporter->col(number_format($data->balance, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          // $str .= $this->reporter->col(number_format($data->balance, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        } else {
          $invamt = $data->db;
          $str .= $this->reporter->col(number_format($data->db, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format(floatval($deposit), 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          
        }
        
        
        $outstanding = $data->balance;// + $retention;
        $str .= $this->reporter->col(number_format($outstanding, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        if ($data->alias == 'AR1') {
          $str .= $this->reporter->col($data->elapse, '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }else{
          $str .= $this->reporter->col('', '50', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }
        
        // 0 to 30
        $str .= $this->reporter->col(($a <> 0 ? number_format($a, 2) : '-'), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        // 31 to 60
        $str .= $this->reporter->col(($b <> 0 ? number_format($b, 2) : '-'), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        // 61 to 90
        $str .= $this->reporter->col(($c <> 0 ? number_format($c, 2) : '-'), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        // 91 ++
        $str .= $this->reporter->col(($d <> 0 ? number_format($d, 2) : '-'), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

        if ($data->alias == 'AR6') {         
          $str .= $this->reporter->col(number_format($retention, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }else{
          $str .= $this->reporter->col('0.00', '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        }

        // TERMS
        $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $totinv = $totinv + $invamt;
        $totdp = $totdp + $deposit;
        $totamt = $totamt + $data->balance;
        $tota = $tota + $a;
        $totb = $totb + $b;
        $totc = $totc + $c;
        $totd = $totd + $d;
        $totret = $totret + $retention;
      }
    }

    

    // $str .= $this->reporter->endtable();
    // $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL:', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totinv, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totdp, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');    
    $str .= $this->reporter->col(number_format($totamt, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totret, 2), '70', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class