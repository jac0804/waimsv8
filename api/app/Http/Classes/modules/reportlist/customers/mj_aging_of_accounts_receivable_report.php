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

class mj_aging_of_accounts_receivable_report
{
  public $modulename = 'MJ Aging Of Accounts Receivable Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '3000'];

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
    $fields = ['radioprint', 'start', 'dclientname', 'dcentername'];
    if ($config['params']['companyid'] == 36) array_push($fields, 'category'); //rozlab

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.label', 'As of');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
      'default' as print,
      date(now()) as start,
      '' as center,
      '' as client,
      '' as dclientname,
      '' as dcentername,
      '' as category,
      '' as dagentname,
      '' as agent,
      '' as agentname,
      '' as agentid
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
    $companyid = $config['params']['companyid'];

    $result = $this->reportDefaultLayout($config, $result);
    return $result;
  }

  public function reportDefault($config)
  {
    $companyid = $config['params']['companyid'];

    $query = $this->reportDefault_QUERY($config);

    $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
    return $this->reportplotting($config, $result);
  }


  public function reportDefault_QUERY($config)
  {
    $filtercenter = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';
    $client = isset($config['params']['dataparams']['client']) ? $config['params']['dataparams']['client'] : '';


    $asof = '';
    if (isset($config['params']['dataparams']['start'])) {

      $asof     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    }

    $companyid   = $config['params']['companyid'];

    $filter = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($filtercenter != "") {
      $filter .= " and cntnum.center='$filtercenter'";
    }

    $initial_date = date("Y-m-03", strtotime($asof));
    $check_if_sunday = date("N", strtotime($initial_date));


    if ($check_if_sunday == 7) {
      //meaning 3rd day of month is sunday, 4th day is monday, start of count would be tuesday
      $start_date = date("Y-m-05", strtotime($asof));
    } else {
      //meaning 3rd day of month is monday, 4th day is tuesday start of count
      $start_date = date("Y-m-04", strtotime($asof));
    }



    $create_date_for_ranges = date_create($start_date);
    date_add($create_date_for_ranges, date_interval_create_from_date_string("1 month"));
    $forward_by_30_date = date_format($create_date_for_ranges, "Y-m-d");


    $qry = "
      select x.center,x.trno,x.due,x.dateid,x.docno,x.last_ar,x.clientid,x.clientname,x.terms,x.collector,x.downpayment,
      x.model,
      x.ext_beg as ext_beginning_balance,
      sum(x.bal_ar) as total_ar_bal,
      datediff(now(), x.dateid) as elapsed,
      datediff(x.last_ar, x.dateid) as date_to_maturity,
      sum(case when (datediff(now(), x.dateid)>=0 and datediff(now(), x.dateid)<=30) then ifnull(x.db - x.cr,0) else 0 end) as current, 
      sum(case when (datediff(now(), x.dateid)>=31 and datediff(now(), x.dateid)<=60) then ifnull(x.db - x.cr,0) else 0 end) as past_due, 
      sum(case when (datediff(now(), x.dateid)>=61 and datediff(now(), x.dateid)<=90) then ifnull(x.db - x.cr,0) else 0 end) as over_due, 
      sum(case when (datediff(now(), x.dateid)>=91) then ifnull(x.db - x.cr,0) else 0 end) as delinquent,
      sum(case when (datediff(now(), x.dateid)>datediff(x.last_ar, x.dateid)) then ifnull(x.db - x.cr,0) else 0 end) as matured,

      ifnull((
        select d.db-d.cr
        from glhead as h 
        left join gldetail as d on d.trno = h.trno 
        left join coa as c on c.acnoid=d.acnoid
        where 
        h.clientid=x.clientid
        and left(c.alias,2) in ('CA','CB')
        and date(d.postdate) > '$forward_by_30_date'

        order by dateid desc limit 1
      ),0) as excess_pay,

      payment_details.paydate,
      payment_details.paydoc,
      ifnull(sum(payment_details.amount),0) as payamount,
      ifnull(sum(payment_details.rebate),0) as rebate,
      ifnull(sum(payment_details.penalty),0) as penalty,
      payment_details.alias,
      payment_details.pay_current,
      payment_details.pay_past_due,
      payment_details.pay_over_due,
      payment_details.pay_delinquent,
      payment_details.pay_matured,

      ifnull(sum(payment_details_adv.adv_amount),0) as adv_payamount,
      ifnull(sum(payment_details_adv.adv_rebate),0) as adv_rebate,

      ifnull(sum(adv_prev_pay.amount),0) as advancepay
      FROM(

        select 
        detail.trno,detail.line,head.docno,
        ifnull(head.due,'') as due,head.dateid,
        num.center,c.clientid,c.clientname,c.terms,'' as collector,
        ifnull(hinfo.downpayment,0) as downpayment,
        ifnull(stock.ext,0) as ext_beg,
        detail.db,detail.cr,
        (select ar.dateid from arledger as ar where ar.clientid=c.clientid order by dateid desc limit 1) as last_ar,
        (select sum(ar.bal) from arledger as ar where ar.clientid=c.clientid and ar.trno=detail.trno and ar.line=detail.line) as bal_ar,
        concat(ifnull(item.itemname, ''), ifnull(mm.model_name,'') , ifnull(brand.brand_desc,''), ifnull(sot.color, ''),
        ifnull(sot.serial, ''), ifnull(sot.chassis, ''))
        as model


        from glhead as head
        left join glstock as stock on stock.trno=head.trno 
        left join gldetail as detail on detail.trno=head.trno 
        left join cntnum as num on num.trno=head.trno
        left join client as c on c.clientid=head.clientid
        left join hcntnuminfo as hinfo on hinfo.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid


        left join serialout as sot on sot.trno = stock.trno and sot.line = stock.line
        left join item on item.itemid = stock.itemid
        left join model_masterfile as mm on mm.model_id = item.model
        left join frontend_ebrands as brand on brand.brandid = item.brand

        where head.doc = 'MJ' and date(head.dateid) <= '$start_date'
        and left(coa.alias,2) = 'AR' $filter

      ) AS X 
      left join ( 
        select h.trno, 
        group_concat(h.dateid order by h.dateid asc SEPARATOR'---' ) as paydate, 
        group_concat(h.docno order by h.dateid asc SEPARATOR'---' ) as paydoc, 
        sum(d.db-d.cr) as amount,d.clientid,c.alias,d.type, 
        (case when c.alias='AR5' then sum(d.db-d.cr) else 0 end) as rebate, 
        (case when (c.alias='SA6' and d.type='P' ) then sum(d.db-d.cr) else 0 end) as penalty, 
        sum(case when (datediff(now(), h.dateid)>=0 and datediff(now(), h.dateid) <=30) then ifnull(d.db - d.cr,0) else 0 end) as pay_current, 
        sum(case when (datediff(now(), h.dateid)>=31 and datediff(now(), h.dateid)<=60) then ifnull(d.db - d.cr,0) else 0 end) as pay_past_due, 
        sum(case when (datediff(now(), h.dateid)>=61 and datediff(now(), h.dateid)<=90) then ifnull(d.db - d.cr,0) else 0 end) as pay_over_due, 
        sum(case when (datediff(now(), h.dateid)>=91) then ifnull(d.db - d.cr,0) else 0 end) as pay_delinquent,
        sum(case when (datediff(now(), h.dateid)>datediff(
        (select ar.dateid from arledger as ar where ar.clientid=payc.clientid order by dateid desc limit 1), h.dateid)) then ifnull(d.db - d.cr,0) else 0 end) as pay_matured
        from glhead as h 
        left join gldetail as d on d.trno = h.trno 
        left join client as payc on payc.clientid=h.clientid
        left join coa as c on c.acnoid=d.acnoid
        where 
        (left(c.alias,2) in ('CA','CB','CR') or c.alias in ('AR5') or (c.alias in ('SA6') and d.type='P'))
        and date(h.dateid) between '$start_date' and '$forward_by_30_date'
        and h.doc='CR'
        group by h.trno,d.clientid,c.alias,d.type
        order by clientid
      ) as payment_details on payment_details.clientid=x.clientid and payment_details.trno=x.trno

      left join ( 
        select h.trno, d.clientid,
        sum(d.db-d.cr) as adv_amount,
        (case when c.alias='AR5' then sum(d.db-d.cr) else 0 end) as adv_rebate
        from glhead as h 
        left join gldetail as d on d.trno = h.trno 
        left join client as payc on payc.clientid=h.clientid
        left join coa as c on c.acnoid=d.acnoid
        where 
        (left(c.alias,2) in ('CA','CB','CR') or c.alias in ('AR5') or (c.alias in ('SA6') and d.type='P'))
        and date(h.dateid) > '$forward_by_30_date'
              and h.doc='CR'
        group by h.trno,d.clientid,c.alias,d.type
      ) as payment_details_adv on payment_details_adv.clientid=x.clientid and payment_details_adv.trno=x.trno


      left join (
        select 
        sum(d.db-d.cr) as amount,h.trno,d.clientid

        from glhead as h 
        left join gldetail as d on d.trno = h.trno 
        left join coa as c on c.acnoid=d.acnoid
        where 
        (left(c.alias,2) in ('CA','CB','CR'))
        and date(h.dateid) > '$start_date'
        and h.doc='CR'group by h.trno,d.clientid order by clientid
      )as adv_prev_pay on adv_prev_pay.clientid=x.clientid and adv_prev_pay.trno=x.trno 
      group by x.center,x.trno,x.due,x.dateid,x.docno,x.clientid,x.clientname,x.terms,x.collector,x.downpayment,x.model,x.ext_beg,
      x.last_ar,payment_details.paydate,payment_details.paydoc,payment_details.alias,payment_details.pay_current,payment_details.pay_past_due,
      payment_details.pay_over_due,payment_details.pay_delinquent,payment_details.pay_matured 
      ORDER BY clientname 
    ";

    return $qry;
  }


  private function reportDefaultLayout($params, $data)
  {

    $str = "";


    $layoutsize = $this->reportParams['layoutSize'];

    $center     = $params['params']['center'];
    $username   = $params['params']['user'];

    $filtercenter = isset($config['params']['dataparams']['center']) ? $config['params']['dataparams']['center'] : '';
    $client = isset($config['params']['dataparams']['client']) ? $config['params']['dataparams']['client'] : '';


    $companyid    = $params['params']['companyid'];
    $postStatus = '';
    $start   = $params['params']['dataparams']['start'];
    $asof     = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $client       = $params['params']['dataparams']['client'];



    $count = 38;
    $page = 40;

    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('AGING OF ACCOUNTS RECEIVABLE', null, null, false, '1px solid ', '', '', 'Century Gothic', '18', 'B', '', '') . '<br />';

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('As of ' . date('F j, Y', strtotime($start)), null, null, false, '1px solid ', '', '', 'Century Gothic', '12', '', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    if ($client == '') {
      $str .= $this->reporter->col('Customer : ALL', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Customer : ' . strtoupper($client), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    }
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Transaction : ' . strtoupper($postStatus), '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Center : ' . $center, '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);

    //1
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //A
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //B
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //C
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //D
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //E

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //F
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //G
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //H
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //I
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //J

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //K
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //L


    $str .= $this->reporter->col('EXPECTED RECEIVABLES FOR THE CURRENT MONTH', '375', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //MNOPQ

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //R

    $str .= $this->reporter->col('PAYMENT DETAILS FOR THE CURRENT MONTH', '375', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //STUVW

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //X

    $str .= $this->reporter->col('ADVANCE PAYMENT FOR UPCOMING DUES', '150', null, false, '1px solid ', '', 'C', 'Century Gothic', '8', 'B', '', ''); //YZ

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AA
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AB
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AC

    $str .= $this->reporter->col('BREAKDOWN OF PAYMENT FOR THE MONTH', '450', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', '');
    //AD AE AF AG AH AI

    $str .= $this->reporter->col('EXPECTED RECEIVABLES FOR THE NEXT AGING', '300', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AJ AK AL


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    //2
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BRANCH', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //A
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //B
    $str .= $this->reporter->col('DUE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //C
    $str .= $this->reporter->col('CEBUANA', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //D
    $str .= $this->reporter->col("CUSTOMER'S", '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //E

    $str .= $this->reporter->col('MODEL', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //F
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //G
    $str .= $this->reporter->col('TERM', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //H
    $str .= $this->reporter->col('ADDRESS', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //I
    $str .= $this->reporter->col('COLLECTOR', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //J

    $str .= $this->reporter->col('AMOUNT', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //K
    $str .= $this->reporter->col('BEGINNING', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //L

    $str .= $this->reporter->col('CURRENT', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //M
    $str .= $this->reporter->col('PAST DUE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //N
    $str .= $this->reporter->col('OVER DUE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //O
    $str .= $this->reporter->col('DELINQUENT', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //P
    $str .= $this->reporter->col('MATURED', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //Q

    $str .= $this->reporter->col('EXCESS FROM', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //R

    $str .= $this->reporter->col('DATE OF', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //S
    $str .= $this->reporter->col('CR#', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //T
    $str .= $this->reporter->col('AMOUNT', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //U
    $str .= $this->reporter->col('REBATE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //V
    $str .= $this->reporter->col('PENALTY', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //W

    $str .= $this->reporter->col('EXCESS FROM', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //X

    $str .= $this->reporter->col('AMOUNT', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //Y
    $str .= $this->reporter->col('REBATE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //Z

    $str .= $this->reporter->col('WAIVED', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AA
    $str .= $this->reporter->col('SPECIAL', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AB
    $str .= $this->reporter->col('ENDING', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AC

    $str .= $this->reporter->col('CURRENT', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AD
    $str .= $this->reporter->col('PAST DUE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AE
    $str .= $this->reporter->col('OVER DUE', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AF
    $str .= $this->reporter->col('DELINQUENT', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AG
    $str .= $this->reporter->col('MATURED', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AH
    $str .= $this->reporter->col('ADVANCES FROM', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AI

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AJ
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AK
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AL


    $str .= $this->reporter->endrow();

    //3
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //A
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //B
    $str .= $this->reporter->col('DATE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //C
    $str .= $this->reporter->col('ACCOUNT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //D
    $str .= $this->reporter->col('NAME', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //E

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //F
    $str .= $this->reporter->col('RELEASED', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //G
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //H
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //I
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //J

    $str .= $this->reporter->col('FINANCE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //K
    $str .= $this->reporter->col('BALANCE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //L

    $str .= $this->reporter->col('1-30 DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //M
    $str .= $this->reporter->col('31-60 DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //N
    $str .= $this->reporter->col('61-90 DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //O
    $str .= $this->reporter->col('91+ DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //P
    $str .= $this->reporter->col('BEYOND TERM', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //Q

    $str .= $this->reporter->col('PAYMENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //R

    $str .= $this->reporter->col('PAYMENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //S
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //T
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //U
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //V
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //W

    $str .= $this->reporter->col('FULL PAYMENT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //X

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //Y
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //Z

    $str .= $this->reporter->col('PENALTY', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AA
    $str .= $this->reporter->col('DISCOUNT', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AB
    $str .= $this->reporter->col('BALANCE', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AC

    $str .= $this->reporter->col('1-30 DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AD
    $str .= $this->reporter->col('31-60 DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AE
    $str .= $this->reporter->col('61-90 DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AF
    $str .= $this->reporter->col('91+ DAYS', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AG
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AH
    $str .= $this->reporter->col('PREVIOUS MONTH', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AI

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AJ
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AK
    $str .= $this->reporter->col('', '150', null, false, '1px solid ', 'B', 'C', 'Century Gothic', '9', 'B', '', ''); //AL


    $str .= $this->reporter->endrow();



    $initial_date = date("Y-m-03", strtotime($asof));
    $check_if_sunday = date("N", strtotime($initial_date));


    if ($check_if_sunday == 7) {
      //meaning 3rd day of month is sunday, 4th day is monday, start of count would be tuesday
      $start_date = date("Y-m-05", strtotime($asof));
    } else {
      //meaning 3rd day of month is monday, 4th day is tuesday start of count
      $start_date = date("Y-m-04", strtotime($asof));
    }

    // $data[$i]['']
    $column_ac = 0;
    $column_aj = 0;
    $column_ak = 0;
    $column_al = 0;

    $total_K = 0;
    $total_L = 0;
    $total_M = 0;
    $total_N = 0;
    $total_O = 0;
    $total_P = 0;
    $total_R = 0;
    $total_S = 0;
    $total_T = 0;
    $total_U = 0;
    $total_V = 0;
    $total_W = 0;
    $total_X = 0;
    $total_Y = 0;
    $total_Z = 0;

    $total_AA = 0;
    $total_AB = 0;
    $total_AC = 0;
    $total_AD = 0;
    $total_AE = 0;
    $total_AF = 0;
    $total_AG = 0;
    $total_AH = 0;
    $total_AI = 0;
    $total_AJ = 0;
    $total_AK = 0;
    $total_AL = 0;
    for ($i = 0; $i < count($data); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data[$i]['center'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //A
      $str .= $this->reporter->col($i + 1, '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //B
      $str .= $this->reporter->col($data[$i]['due'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //C
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //D
      $str .= $this->reporter->col($data[$i]['clientname'], '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '9', 'B', '', ''); //E

      $str .= $this->reporter->col($data[$i]['model'], '75', null, false, '1px solid ', '', 'L', 'Century Gothic', '9', 'B', '', ''); //F
      $str .= $this->reporter->col($data[$i]['dateid'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //G
      $str .= $this->reporter->col($data[$i]['terms'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //H
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //I
      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //J

      $str .= $this->reporter->col(number_format($data[$i]['ext_beginning_balance'] - $data[$i]['downpayment'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //K
      $total_K += $data[$i]['ext_beginning_balance'] - $data[$i]['downpayment'];

      $str .= $this->reporter->col(number_format($data[$i]['total_ar_bal'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //L
      $total_L += $data[$i]['total_ar_bal'];

      $str .= $this->reporter->col(number_format($data[$i]['current'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //M
      $total_M += $data[$i]['current'];

      $str .= $this->reporter->col(number_format($data[$i]['past_due'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //N
      $total_N += $data[$i]['past_due'];

      $str .= $this->reporter->col(number_format($data[$i]['over_due'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //O
      $total_O += $data[$i]['over_due'];


      $str .= $this->reporter->col(number_format($data[$i]['delinquent'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //P
      $total_P += $data[$i]['delinquent'];

      $str .= $this->reporter->col(number_format($data[$i]['matured'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //Q
      $str .= $this->reporter->col(number_format($data[$i]['excess_pay'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //R
      $total_R += $data[$i]['excess_pay'];

      $str .= $this->reporter->col($data[$i]['paydate'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //S
      $total_S += $data[$i]['paydate'];

      $str .= $this->reporter->col($data[$i]['paydoc'], '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //T
      $total_T += $data[$i]['paydoc'];

      $str .= $this->reporter->col(number_format($data[$i]['payamount'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //U
      $total_U += $data[$i]['payamount'];

      $str .= $this->reporter->col(number_format($data[$i]['rebate'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //V
      $total_V += $data[$i]['rebate'];

      $str .= $this->reporter->col(number_format($data[$i]['penalty'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //W
      $total_W += $data[$i]['penalty'];

      $str .= $this->reporter->col(number_format($data[$i]['excess_pay'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //X
      $total_X += $data[$i]['excess_pay'];

      $str .= $this->reporter->col(number_format($data[$i]['adv_payamount'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //Y
      $total_Y += $data[$i]['adv_payamount'];

      $str .= $this->reporter->col(number_format($data[$i]['adv_rebate'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //Z
      $total_Z += $data[$i]['adv_rebate'];

      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AA
      $total_AA += 0;

      $str .= $this->reporter->col('', '75', null, false, '1px solid ', '', 'C', 'Century Gothic', '9', 'B', '', ''); //AB
      $total_AB += 0;

      $column_ac = $data[$i]['total_ar_bal'] - $data[$i]['payamount'] - $data[$i]['rebate'] - $data[$i]['excess_pay'] - $data[$i]['adv_payamount'] - $data[$i]['adv_rebate'];
      $str .= $this->reporter->col(number_format($column_ac, 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AC
      $total_AC += $column_ac;

      $str .= $this->reporter->col(number_format($data[$i]['pay_current'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AD
      $total_AD += $data[$i]['pay_current'];

      $str .= $this->reporter->col(number_format($data[$i]['pay_past_due'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AE
      $total_AE += $data[$i]['pay_past_due'];

      $str .= $this->reporter->col(number_format($data[$i]['pay_over_due'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AF
      $total_AF += $data[$i]['pay_over_due'];

      $str .= $this->reporter->col(number_format($data[$i]['pay_delinquent'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AG
      $total_AG += $data[$i]['pay_delinquent'];

      $str .= $this->reporter->col(number_format($data[$i]['pay_matured'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AH
      $total_AH += $data[$i]['pay_matured'];

      $str .= $this->reporter->col(number_format($data[$i]['advancepay'], 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AI
      $total_AI += $data[$i]['advancepay'];



      //M $data[$i]['current']
      //AD $data[$i]['pay_current']
      //AI $data[$i]['advancepay']

      //N $data[$i]['past_due']
      //AE $data[$i]['pay_over_due']

      //O $data[$i]['over_due']
      //P $data[$i]['delinquent']
      //AF $data[$i]['pay_over_due']
      //AG $data[$i]['pay_delinquent']
      $column_aj = $data[$i]['current'] - $data[$i]['pay_current'] - $data[$i]['advancepay']; //M-AD-AI
      $column_ak = $data[$i]['past_due'] - $data[$i]['pay_over_due']; //N-AE
      $column_al = ($data[$i]['over_due'] + $data[$i]['delinquent']) - $data[$i]['pay_over_due'] - $data[$i]['pay_delinquent']; //O+P-AF-AG



      $str .= $this->reporter->col(number_format($column_aj, 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AJ
      $total_AJ += $column_aj;

      $str .= $this->reporter->col(number_format($column_ak, 2), '75', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AK
      $total_AK += $column_ak;

      $str .= $this->reporter->col(number_format($column_al, 2), '150', null, false, '1px solid ', '', 'R', 'Century Gothic', '9', 'B', '', ''); //AL
      $total_AL += $column_al;




      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //A
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //B
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //C
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //D
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '9', 'B', '', ''); //E

    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'L', 'Century Gothic', '9', 'B', '', ''); //F
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //G
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //H
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //I
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //J

    $str .= $this->reporter->col(number_format($total_K, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //K
    $str .= $this->reporter->col(number_format($total_L, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //L
    $str .= $this->reporter->col(number_format($total_M, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //M
    $str .= $this->reporter->col(number_format($total_N, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //N
    $str .= $this->reporter->col(number_format($total_O, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //O
    $str .= $this->reporter->col(number_format($total_P, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //P
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'C', 'Century Gothic', '9', 'B', '', ''); //Q
    $str .= $this->reporter->col(number_format($total_R, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //R
    $str .= $this->reporter->col(number_format($total_S, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //S
    $str .= $this->reporter->col(number_format($total_T, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //T
    $str .= $this->reporter->col(number_format($total_U, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //U
    $str .= $this->reporter->col(number_format($total_V, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //V
    $str .= $this->reporter->col(number_format($total_W, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //W
    $str .= $this->reporter->col(number_format($total_X, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //X
    $str .= $this->reporter->col(number_format($total_Y, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //Y
    $str .= $this->reporter->col(number_format($total_Z, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //Z
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AA
    $str .= $this->reporter->col('', '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AB
    $str .= $this->reporter->col(number_format($total_AC, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AC
    $str .= $this->reporter->col(number_format($total_AD, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AD
    $str .= $this->reporter->col(number_format($total_AE, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AE
    $str .= $this->reporter->col(number_format($total_AF, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AF
    $str .= $this->reporter->col(number_format($total_AG, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AG
    $str .= $this->reporter->col(number_format($total_AH, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AH
    $str .= $this->reporter->col(number_format($total_AI, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AI
    $str .= $this->reporter->col(number_format($total_AJ, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AJ
    $str .= $this->reporter->col(number_format($total_AK, 2), '75', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AK
    $str .= $this->reporter->col(number_format($total_AL, 2), '150', null, false, '1px solid ', 'T', 'R', 'Century Gothic', '9', 'B', '', ''); //AL


    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();
    return $str;
  } //end function



}
