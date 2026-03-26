<?php

namespace App\Http\Classes\modules\reportlist\accounting_books;

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

class daily_cash_flow
{
  public $modulename = 'Sales Journal';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'L', 'format' => 'letter', 'layoutSize' => '4000'];

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

    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['month', 'year', 'print'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'month.type', 'lookup');
    data_set($col2, 'month.readonly', true);
    data_set($col2, 'month.action', 'lookuprandom');
    data_set($col2, 'month.lookupclass', 'lookup_month');
    data_set($col2, 'year.type', 'lookup');
    data_set($col2, 'year.class', 'sbccsreadonly');
    data_set($col2, 'year.lookupclass', 'lookupyear');
    data_set($col2, 'year.action', 'lookupyear');

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

    return $this->coreFunctions->opentable("select 'default' as print,
      date_format(now(), '%M') as month,
      month(now()) as bmonth,
      year(now()) as year");
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }


  public function default_query($filters)
  {
    $companyid = $filters['params']['companyid'];
    $month = $filters['params']['dataparams']['month'];
    $bmonth = $filters['params']['dataparams']['bmonth'];
    $year = $filters['params']['dataparams']['year'];
    $date = $year . '-' . $bmonth . '-01';

    $sales_qry = " select ifnull(sum(ext),0) as value from glhead as head 
                  left join glstock as stock on head.trno = stock.trno
                  where head.doc = 'SJ' and  date(head.dateid) < date('$date') ";

    $sales = $this->coreFunctions->datareader($sales_qry);


    $chk_qry = "select ifnull(sum(e),0) as value from (select sum(detail.db-detail.cr) as e from lahead as head 
                  left join ladetail as detail on head.trno = detail.trno
                  left join coa on coa.acnoid = detail.acnoid 
                  where head.doc = 'CR' and  coa.alias = 'PC3' and date(head.dateid) < date('$date')
                  union all
                  select sum(detail.db-detail.cr) as e from glhead as head 
                  left join gldetail as detail on head.trno = detail.trno
                  left join coa on coa.acnoid = detail.acnoid 
                  where head.doc = 'CR' and  coa.alias = 'PC3' and date(head.dateid) < date('$date') ) as x ";

    $chk = $this->coreFunctions->datareader($chk_qry);

    $expenses_qry = "select ifnull(sum(e),0) as value from (select sum(detail.db-detail.cr) as e from lahead as head 
                  left join ladetail as detail on head.trno = detail.trno
                  left join coa on coa.acnoid = detail.acnoid 
                  where head.doc = 'CV' and coa.cat = 'E' and date(head.dateid) < date('$date')
                  union all
                  select sum(detail.db-detail.cr) as e from glhead as head 
                  left join gldetail as detail on head.trno = detail.trno
                  left join coa on coa.acnoid = detail.acnoid 
                  where head.doc = 'CV' and  coa.cat = 'E' and date(head.dateid) < date('$date') ) as x ";

    $expenses = $this->coreFunctions->datareader($expenses_qry);


    $cashchk_qry = "select ifnull(sum(e),0) as value from (select sum(detail.cr-detail.db) as e from lahead as head 
                  left join ladetail as detail on head.trno = detail.trno
                  left join coa on coa.acnoid = detail.acnoid 
                  where  head.doc = 'CV' and left(alias,2) <> 'PC' and  coa.cat <> 'E' and date(head.dateid) < date('$date')
                  union all
                  select sum(detail.cr-detail.db) as e from glhead as head 
                  left join gldetail as detail on head.trno = detail.trno
                  left join coa on coa.acnoid = detail.acnoid 
                  where  head.doc = 'CV' and left(alias,2) <> 'PC' and  coa.cat <> 'E' and date(head.dateid) < date('$date') ) as x ";

    $cashchk = $this->coreFunctions->datareader($cashchk_qry);

    $beginingbal = ($sales + $chk) - ($expenses + $cashchk);

    return $beginingbal;
  }

  public function reportplotting($config)
  {

    $result = $this->default_query($config);
    $companyid = $config['params']['companyid'];

    $reportdata =  $this->daily_cash_flow($config, $result);
    return $reportdata;
  }

  private function daily_cash_flow($config, $beginingbal)
  {
    $fontsize10 = '10';
    $month = $config['params']['dataparams']['month'];
    $bmonth = $config['params']['dataparams']['bmonth'];
    $year = $config['params']['dataparams']['year'];

    $grptotal = $wstotal = $pc3total = $expensestotal = $cashchecktotal = [];
    $p1 = $p2 = $p3 = $p4 = $p5 = $p6 = $p7 = $p8 = $p9 = $p10 = $p11 = $p12 = $p13 = $p14 = $p15 = $p16 = $p17 = $p18 = $p19 = $p20 = $p21 = $p22 = $p23 = $p24 = $p25 = $p26 = $p27 = $p28 =
      $p28 = $p29 = $p30 = $p31 = $psalestotal =

      $n1 = $n2 = $n3 = $n4 = $n5 = $n6 = $n7 = $n8 = $n9 = $n10 = $n11 = $n12 = $n13 = $n14 = $n14 = $n15 = $n16 = $n17 = $n18 = $n19 = $n20 = $n21 = $n22 = $n23 = $n24 = $n25 = $n26 = $n27 = $n28 = $n29 = $n30 = $n31 = $ntotal = 0;

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']); //FONT UPDATED

    $str = "";
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DAILY CASH FLOW', null, null, '', $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MONTH : ' . strtoupper($month), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('YEAR : ' . strtoupper($year), null, null, '', $border, '', 'l', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, '', $border, '', 'r', $font, $fontsize10, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $columns = [
      strtoupper($month . " " . $year),
      '1',
      '2',
      '3',
      '4',
      '5',
      '6',
      '7',
      '8',
      '9',
      '10',
      '11',
      '12',
      '13',
      '14',
      '15',
      '16',
      '17',
      '18',
      '19',
      '20',
      '21',
      '22',
      '23',
      '24',
      '25',
      '26',
      '27',
      '28',
      '29',
      '30',
      '31',
      'TOTAL',
    ];

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();

    foreach ($columns as $key => $value) {
      $str .= $this->reporter->col($value, '200', null, 'TLBR', $border, 'TLBR', 'C', $font, '12', 'B', '', '');
    }
    $str .= $this->reporter->endrow();


    // sales with group
    $grpqry = "select grp.stockgrp_name as groupname,
     sum(case when day(head.dateid)=1 then ifnull(stock.ext,0) else 0 end) as a1,
     sum(case when day(head.dateid)=2 then ifnull(stock.ext,0) else 0 end) as a2,
     sum(case when day(head.dateid)=3 then ifnull(stock.ext,0) else 0 end) as a3,
     sum(case when day(head.dateid)=4 then ifnull(stock.ext,0) else 0 end) as a4,
     sum(case when day(head.dateid)=5 then ifnull(stock.ext,0) else 0 end) as a5,
     sum(case when day(head.dateid)=6 then ifnull(stock.ext,0) else 0 end) as a6,
     sum(case when day(head.dateid)=7 then ifnull(stock.ext,0) else 0 end) as a7,
     sum(case when day(head.dateid)=8 then ifnull(stock.ext,0) else 0 end) as a8,
     sum(case when day(head.dateid)=9 then ifnull(stock.ext,0) else 0 end) as a9,
     sum(case when day(head.dateid)=10 then ifnull(stock.ext,0) else 0 end) as a10,
     sum(case when day(head.dateid)=11 then ifnull(stock.ext,0) else 0 end) as a11,
     sum(case when day(head.dateid)=12 then ifnull(stock.ext,0) else 0 end) as a12,
     sum(case when day(head.dateid)=13 then ifnull(stock.ext,0) else 0 end) as a13,
     sum(case when day(head.dateid)=14 then ifnull(stock.ext,0) else 0 end) as a14,
     sum(case when day(head.dateid)=15 then ifnull(stock.ext,0) else 0 end) as a15,
     sum(case when day(head.dateid)=16 then ifnull(stock.ext,0) else 0 end) as a16,
     sum(case when day(head.dateid)=17 then ifnull(stock.ext,0) else 0 end) as a17,
     sum(case when day(head.dateid)=18 then ifnull(stock.ext,0) else 0 end) as a18,
     sum(case when day(head.dateid)=19 then ifnull(stock.ext,0) else 0 end) as a19,
     sum(case when day(head.dateid)=20 then ifnull(stock.ext,0) else 0 end) as a20,
     sum(case when day(head.dateid)=21 then ifnull(stock.ext,0) else 0 end) as a21,
     sum(case when day(head.dateid)=22 then ifnull(stock.ext,0) else 0 end) as a22,
     sum(case when day(head.dateid)=23 then ifnull(stock.ext,0) else 0 end) as a23,
     sum(case when day(head.dateid)=24 then ifnull(stock.ext,0) else 0 end) as a24,
     sum(case when day(head.dateid)=25 then ifnull(stock.ext,0) else 0 end) as a25,
     sum(case when day(head.dateid)=26 then ifnull(stock.ext,0) else 0 end) as a26,
     sum(case when day(head.dateid)=27 then ifnull(stock.ext,0) else 0 end) as a27,
     sum(case when day(head.dateid)=28 then ifnull(stock.ext,0) else 0 end) as a28,
     sum(case when day(head.dateid)=29 then ifnull(stock.ext,0) else 0 end) as a29,
     sum(case when day(head.dateid)=30 then ifnull(stock.ext,0) else 0 end) as a30,
     sum(case when day(head.dateid)=31 then ifnull(stock.ext,0) else 0 end) as a31
    from glhead as head 
    left join glstock as stock on head.trno = stock.trno
    left join item on item.itemid = stock.itemid
    left join stockgrp_masterfile as grp on grp.stockgrp_id = item.groupid
    left join cntnum as num on num.trno = head.trno
    where head.doc = 'SJ' and num.bref = 'SJ' and  month(head.dateid) = '$bmonth' and year(head.dateid) = '$year'
    group by grp.stockgrp_name";

    $grpdata = $this->coreFunctions->opentable($grpqry);


    // ws
    $wspqry = "select 'CASH PAYMENT WS' as ws,
    sum(case when day(head.dateid)=1 then ifnull(stock.ext,0) else 0 end) as a1,
    sum(case when day(head.dateid)=2 then ifnull(stock.ext,0) else 0 end) as a2,
    sum(case when day(head.dateid)=3 then ifnull(stock.ext,0) else 0 end) as a3,
    sum(case when day(head.dateid)=4 then ifnull(stock.ext,0) else 0 end) as a4,
    sum(case when day(head.dateid)=5 then ifnull(stock.ext,0) else 0 end) as a5,
    sum(case when day(head.dateid)=6 then ifnull(stock.ext,0) else 0 end) as a6,
    sum(case when day(head.dateid)=7 then ifnull(stock.ext,0) else 0 end) as a7,
    sum(case when day(head.dateid)=8 then ifnull(stock.ext,0) else 0 end) as a8,
    sum(case when day(head.dateid)=9 then ifnull(stock.ext,0) else 0 end) as a9,
    sum(case when day(head.dateid)=10 then ifnull(stock.ext,0) else 0 end) as a10,
    sum(case when day(head.dateid)=11 then ifnull(stock.ext,0) else 0 end) as a11,
    sum(case when day(head.dateid)=12 then ifnull(stock.ext,0) else 0 end) as a12,
    sum(case when day(head.dateid)=13 then ifnull(stock.ext,0) else 0 end) as a13,
    sum(case when day(head.dateid)=14 then ifnull(stock.ext,0) else 0 end) as a14,
    sum(case when day(head.dateid)=15 then ifnull(stock.ext,0) else 0 end) as a15,
    sum(case when day(head.dateid)=16 then ifnull(stock.ext,0) else 0 end) as a16,
    sum(case when day(head.dateid)=17 then ifnull(stock.ext,0) else 0 end) as a17,
    sum(case when day(head.dateid)=18 then ifnull(stock.ext,0) else 0 end) as a18,
    sum(case when day(head.dateid)=19 then ifnull(stock.ext,0) else 0 end) as a19,
    sum(case when day(head.dateid)=20 then ifnull(stock.ext,0) else 0 end) as a20,
    sum(case when day(head.dateid)=21 then ifnull(stock.ext,0) else 0 end) as a21,
    sum(case when day(head.dateid)=22 then ifnull(stock.ext,0) else 0 end) as a22,
    sum(case when day(head.dateid)=23 then ifnull(stock.ext,0) else 0 end) as a23,
    sum(case when day(head.dateid)=24 then ifnull(stock.ext,0) else 0 end) as a24,
    sum(case when day(head.dateid)=25 then ifnull(stock.ext,0) else 0 end) as a25,
    sum(case when day(head.dateid)=26 then ifnull(stock.ext,0) else 0 end) as a26,
    sum(case when day(head.dateid)=27 then ifnull(stock.ext,0) else 0 end) as a27,
    sum(case when day(head.dateid)=28 then ifnull(stock.ext,0) else 0 end) as a28,
    sum(case when day(head.dateid)=29 then ifnull(stock.ext,0) else 0 end) as a29,
    sum(case when day(head.dateid)=30 then ifnull(stock.ext,0) else 0 end) as a30,
    sum(case when day(head.dateid)=31 then ifnull(stock.ext,0) else 0 end) as a31
    from glhead as head 
    left join glstock as stock on head.trno = stock.trno
    left join cntnum as num on num.trno = head.trno
    where head.doc = 'SJ' and num.bref = 'WS' and  month(head.dateid) = '$bmonth' and year(head.dateid) = '$year'
    ";

    $wsdata = $this->coreFunctions->opentable($wspqry);

    $pc3_qry = "
    select acnoname,
    sum(case when day(dateid)=1 then ifnull(e,0) else 0 end) as a1,
    sum(case when day(dateid)=2 then ifnull(e,0) else 0 end) as a2,
    sum(case when day(dateid)=3 then ifnull(e,0) else 0 end) as a3,
    sum(case when day(dateid)=4 then ifnull(e,0) else 0 end) as a4,
    sum(case when day(dateid)=5 then ifnull(e,0) else 0 end) as a5,
    sum(case when day(dateid)=6 then ifnull(e,0) else 0 end) as a6,
    sum(case when day(dateid)=7 then ifnull(e,0) else 0 end) as a7,
    sum(case when day(dateid)=8 then ifnull(e,0) else 0 end) as a8,
    sum(case when day(dateid)=9 then ifnull(e,0) else 0 end) as a9,
    sum(case when day(dateid)=10 then ifnull(e,0) else 0 end) as a10,
    sum(case when day(dateid)=11 then ifnull(e,0) else 0 end) as a11,
    sum(case when day(dateid)=12 then ifnull(e,0) else 0 end) as a12,
    sum(case when day(dateid)=13 then ifnull(e,0) else 0 end) as a13,
    sum(case when day(dateid)=14 then ifnull(e,0) else 0 end) as a14,
    sum(case when day(dateid)=15 then ifnull(e,0) else 0 end) as a15,
    sum(case when day(dateid)=16 then ifnull(e,0) else 0 end) as a16,
    sum(case when day(dateid)=17 then ifnull(e,0) else 0 end) as a17,
    sum(case when day(dateid)=18 then ifnull(e,0) else 0 end) as a18,
    sum(case when day(dateid)=19 then ifnull(e,0) else 0 end) as a19,
    sum(case when day(dateid)=20 then ifnull(e,0) else 0 end) as a20,
    sum(case when day(dateid)=21 then ifnull(e,0) else 0 end) as a21,
    sum(case when day(dateid)=22 then ifnull(e,0) else 0 end) as a22,
    sum(case when day(dateid)=23 then ifnull(e,0) else 0 end) as a23,
    sum(case when day(dateid)=24 then ifnull(e,0) else 0 end) as a24,
    sum(case when day(dateid)=25 then ifnull(e,0) else 0 end) as a25,
    sum(case when day(dateid)=26 then ifnull(e,0) else 0 end) as a26,
    sum(case when day(dateid)=27 then ifnull(e,0) else 0 end) as a27,
    sum(case when day(dateid)=28 then ifnull(e,0) else 0 end) as a28,
    sum(case when day(dateid)=29 then ifnull(e,0) else 0 end) as a29,
    sum(case when day(dateid)=30 then ifnull(e,0) else 0 end) as a30,
    sum(case when day(dateid)=31 then ifnull(e,0) else 0 end) as a31
      from (select head.dateid, coa.acnoname, detail.db-detail.cr as e from lahead as head 
      left join ladetail as detail on head.trno = detail.trno
      left join coa on coa.acnoid = detail.acnoid 
      where head.doc = 'CR' and  coa.alias = 'PC3' and month(head.dateid) = '$bmonth' and year(head.dateid) = '$year'
      union all
      select head.dateid,  coa.acnoname as a,  detail.db-detail.cr as e from glhead as head 
      left join gldetail as detail on head.trno = detail.trno
      left join coa on coa.acnoid = detail.acnoid 
      where head.doc = 'CR' and  coa.alias = 'PC3' and month(head.dateid) = '$bmonth' and year(head.dateid) = '$year' ) as x 
    group by acnoname";

    $pc3data = $this->coreFunctions->opentable($pc3_qry);


    $expenses_qry = "
    select acnoname,
    sum(case when day(dateid)=1 then ifnull(e,0) else 0 end) as a1,
    sum(case when day(dateid)=2 then ifnull(e,0) else 0 end) as a2,
    sum(case when day(dateid)=3 then ifnull(e,0) else 0 end) as a3,
    sum(case when day(dateid)=4 then ifnull(e,0) else 0 end) as a4,
    sum(case when day(dateid)=5 then ifnull(e,0) else 0 end) as a5,
    sum(case when day(dateid)=6 then ifnull(e,0) else 0 end) as a6,
    sum(case when day(dateid)=7 then ifnull(e,0) else 0 end) as a7,
    sum(case when day(dateid)=8 then ifnull(e,0) else 0 end) as a8,
    sum(case when day(dateid)=9 then ifnull(e,0) else 0 end) as a9,
    sum(case when day(dateid)=10 then ifnull(e,0) else 0 end) as a10,
    sum(case when day(dateid)=11 then ifnull(e,0) else 0 end) as a11,
    sum(case when day(dateid)=12 then ifnull(e,0) else 0 end) as a12,
    sum(case when day(dateid)=13 then ifnull(e,0) else 0 end) as a13,
    sum(case when day(dateid)=14 then ifnull(e,0) else 0 end) as a14,
    sum(case when day(dateid)=15 then ifnull(e,0) else 0 end) as a15,
    sum(case when day(dateid)=16 then ifnull(e,0) else 0 end) as a16,
    sum(case when day(dateid)=17 then ifnull(e,0) else 0 end) as a17,
    sum(case when day(dateid)=18 then ifnull(e,0) else 0 end) as a18,
    sum(case when day(dateid)=19 then ifnull(e,0) else 0 end) as a19,
    sum(case when day(dateid)=20 then ifnull(e,0) else 0 end) as a20,
    sum(case when day(dateid)=21 then ifnull(e,0) else 0 end) as a21,
    sum(case when day(dateid)=22 then ifnull(e,0) else 0 end) as a22,
    sum(case when day(dateid)=23 then ifnull(e,0) else 0 end) as a23,
    sum(case when day(dateid)=24 then ifnull(e,0) else 0 end) as a24,
    sum(case when day(dateid)=25 then ifnull(e,0) else 0 end) as a25,
    sum(case when day(dateid)=26 then ifnull(e,0) else 0 end) as a26,
    sum(case when day(dateid)=27 then ifnull(e,0) else 0 end) as a27,
    sum(case when day(dateid)=28 then ifnull(e,0) else 0 end) as a28,
    sum(case when day(dateid)=29 then ifnull(e,0) else 0 end) as a29,
    sum(case when day(dateid)=30 then ifnull(e,0) else 0 end) as a30,
    sum(case when day(dateid)=31 then ifnull(e,0) else 0 end) as a31
      from (select head.dateid, coa.acnoname, detail.db-detail.cr as e from lahead as head 
      left join ladetail as detail on head.trno = detail.trno
      left join coa on coa.acnoid = detail.acnoid 
      where head.doc = 'CV' and  coa.cat = 'E' and month(head.dateid) = '$bmonth' and year(head.dateid) = '$year'
      union all
      select head.dateid,  coa.acnoname as a,  detail.db-detail.cr as e from glhead as head 
      left join gldetail as detail on head.trno = detail.trno
      left join coa on coa.acnoid = detail.acnoid 
      where head.doc = 'CV' and  coa.cat = 'E' and month(head.dateid) = '$bmonth' and year(head.dateid) = '$year' ) as x 
    group by acnoname";

    $expenses_data = $this->coreFunctions->opentable($expenses_qry);


    $cashcheck_qry = "
    select acnoname,
    sum(case when day(dateid)=1 then ifnull(e,0) else 0 end) as a1,
    sum(case when day(dateid)=2 then ifnull(e,0) else 0 end) as a2,
    sum(case when day(dateid)=3 then ifnull(e,0) else 0 end) as a3,
    sum(case when day(dateid)=4 then ifnull(e,0) else 0 end) as a4,
    sum(case when day(dateid)=5 then ifnull(e,0) else 0 end) as a5,
    sum(case when day(dateid)=6 then ifnull(e,0) else 0 end) as a6,
    sum(case when day(dateid)=7 then ifnull(e,0) else 0 end) as a7,
    sum(case when day(dateid)=8 then ifnull(e,0) else 0 end) as a8,
    sum(case when day(dateid)=9 then ifnull(e,0) else 0 end) as a9,
    sum(case when day(dateid)=10 then ifnull(e,0) else 0 end) as a10,
    sum(case when day(dateid)=11 then ifnull(e,0) else 0 end) as a11,
    sum(case when day(dateid)=12 then ifnull(e,0) else 0 end) as a12,
    sum(case when day(dateid)=13 then ifnull(e,0) else 0 end) as a13,
    sum(case when day(dateid)=14 then ifnull(e,0) else 0 end) as a14,
    sum(case when day(dateid)=15 then ifnull(e,0) else 0 end) as a15,
    sum(case when day(dateid)=16 then ifnull(e,0) else 0 end) as a16,
    sum(case when day(dateid)=17 then ifnull(e,0) else 0 end) as a17,
    sum(case when day(dateid)=18 then ifnull(e,0) else 0 end) as a18,
    sum(case when day(dateid)=19 then ifnull(e,0) else 0 end) as a19,
    sum(case when day(dateid)=20 then ifnull(e,0) else 0 end) as a20,
    sum(case when day(dateid)=21 then ifnull(e,0) else 0 end) as a21,
    sum(case when day(dateid)=22 then ifnull(e,0) else 0 end) as a22,
    sum(case when day(dateid)=23 then ifnull(e,0) else 0 end) as a23,
    sum(case when day(dateid)=24 then ifnull(e,0) else 0 end) as a24,
    sum(case when day(dateid)=25 then ifnull(e,0) else 0 end) as a25,
    sum(case when day(dateid)=26 then ifnull(e,0) else 0 end) as a26,
    sum(case when day(dateid)=27 then ifnull(e,0) else 0 end) as a27,
    sum(case when day(dateid)=28 then ifnull(e,0) else 0 end) as a28,
    sum(case when day(dateid)=29 then ifnull(e,0) else 0 end) as a29,
    sum(case when day(dateid)=30 then ifnull(e,0) else 0 end) as a30,
    sum(case when day(dateid)=31 then ifnull(e,0) else 0 end) as a31
      from (select head.dateid, coa.acnoname, detail.db-detail.cr as e from lahead as head 
      left join ladetail as detail on head.trno = detail.trno
      left join coa on coa.acnoid = detail.acnoid 
      where head.doc = 'CV' and left(alias,2) <> 'PC' and  coa.cat <> 'E' and month(head.dateid) = '$bmonth' and year(head.dateid) = '$year'
      union all
      select head.dateid,  coa.acnoname as a,  detail.db-detail.cr as e from glhead as head 
      left join gldetail as detail on head.trno = detail.trno
      left join coa on coa.acnoid = detail.acnoid 
      where head.doc = 'CV' and left(alias,2) <> 'PC' and coa.cat <> 'E' and month(head.dateid) = '$bmonth' and year(head.dateid) = '$year' ) as x 
    group by acnoname";

    $cashcheck_data = $this->coreFunctions->opentable($cashcheck_qry);


    foreach ($grpdata as $key => $value) {

      $p1 += $value->a1;
      $p2 += $value->a2;
      $p3 += $value->a3;
      $p4 += $value->a4;
      $p5 += $value->a5;
      $p6 += $value->a6;
      $p7 += $value->a7;
      $p8 += $value->a8;
      $p9 += $value->a9;
      $p10 += $value->a10;
      $p11 += $value->a11;
      $p12 += $value->a12;
      $p13 += $value->a13;
      $p14 += $value->a14;
      $p15 += $value->a15;
      $p16 += $value->a16;
      $p17 += $value->a17;
      $p18 += $value->a18;
      $p19 += $value->a19;
      $p20 += $value->a20;
      $p21 += $value->a21;
      $p22 += $value->a22;
      $p23 += $value->a23;
      $p24 += $value->a24;
      $p25 += $value->a25;
      $p26 += $value->a26;
      $p27 += $value->a27;
      $p28 += $value->a28;
      $p29 += $value->a29;
      $p30 += $value->a30;
      $p31 += $value->a31;

      $grptotal[$value->groupname] = $value->a1 + $value->a2 + $value->a3 +  $value->a4 + $value->a5 + $value->a6 + $value->a7 + $value->a8 +
        $value->a9 + $value->a10 + $value->a11 + $value->a12 + $value->a13 + $value->a14 + $value->a15 + $value->a16 + $value->a17 + $value->a18 + $value->a19 +
        $value->a20 + $value->a21 + $value->a22 + $value->a23 + $value->a24 + $value->a25 + $value->a26 + $value->a27 + $value->a28 + $value->a29 + $value->a30 +
        $value->a31;
    }

    foreach ($wsdata as $key => $value) {

      $p1 += $value->a1;
      $p2 += $value->a2;
      $p3 += $value->a3;
      $p4 += $value->a4;
      $p5 += $value->a5;
      $p6 += $value->a6;
      $p7 += $value->a7;
      $p8 += $value->a8;
      $p9 += $value->a9;
      $p10 += $value->a10;
      $p11 += $value->a11;
      $p12 += $value->a12;
      $p13 += $value->a13;
      $p14 += $value->a14;
      $p15 += $value->a15;
      $p16 += $value->a16;
      $p17 += $value->a17;
      $p18 += $value->a18;
      $p19 += $value->a19;
      $p20 += $value->a20;
      $p21 += $value->a21;
      $p22 += $value->a22;
      $p23 += $value->a23;
      $p24 += $value->a24;
      $p25 += $value->a25;
      $p26 += $value->a26;
      $p27 += $value->a27;
      $p28 += $value->a28;
      $p29 += $value->a29;
      $p30 += $value->a30;
      $p31 += $value->a31;
      $wstotal[$value->ws] = $value->a1 + $value->a2 + $value->a3 +  $value->a4 + $value->a5 + $value->a6 + $value->a7 + $value->a8 +
        $value->a9 + $value->a10 + $value->a11 + $value->a12 + $value->a13 + $value->a14 + $value->a15 + $value->a16 + $value->a17 + $value->a18 + $value->a19 +
        $value->a20 + $value->a21 + $value->a22 + $value->a23 + $value->a24 + $value->a25 + $value->a26 + $value->a27 + $value->a28 + $value->a29 + $value->a30 +
        $value->a31;
    }

    foreach ($pc3data as $key => $value) {

      $p1 += $value->a1;
      $p2 += $value->a2;
      $p3 += $value->a3;
      $p4 += $value->a4;
      $p5 += $value->a5;
      $p6 += $value->a6;
      $p7 += $value->a7;
      $p8 += $value->a8;
      $p9 += $value->a9;
      $p10 += $value->a10;
      $p11 += $value->a11;
      $p12 += $value->a12;
      $p13 += $value->a13;
      $p14 += $value->a14;
      $p15 += $value->a15;
      $p16 += $value->a16;
      $p17 += $value->a17;
      $p18 += $value->a18;
      $p19 += $value->a19;
      $p20 += $value->a20;
      $p21 += $value->a21;
      $p22 += $value->a22;
      $p23 += $value->a23;
      $p24 += $value->a24;
      $p25 += $value->a25;
      $p26 += $value->a26;
      $p27 += $value->a27;
      $p28 += $value->a28;
      $p29 += $value->a29;
      $p30 += $value->a30;
      $p31 += $value->a31;

      $pc3total[$value->acnoname] = $value->a1 + $value->a2 + $value->a3 +  $value->a4 + $value->a5 + $value->a6 + $value->a7 + $value->a8 +
        $value->a9 + $value->a10 + $value->a11 + $value->a12 + $value->a13 + $value->a14 + $value->a15 + $value->a16 + $value->a17 + $value->a18 + $value->a19 +
        $value->a20 + $value->a21 + $value->a22 + $value->a23 + $value->a24 + $value->a25 + $value->a26 + $value->a27 + $value->a28 + $value->a29 + $value->a30 +
        $value->a31;
    }

    // nn

    foreach ($expenses_data as $key => $value) {

      $n1 += $value->a1;
      $n2 += $value->a2;
      $n3 += $value->a3;
      $n4 += $value->a4;
      $n5 += $value->a5;
      $n6 += $value->a6;
      $n7 += $value->a7;
      $n8 += $value->a8;
      $n9 += $value->a9;
      $n10 += $value->a10;
      $n11 += $value->a11;
      $n12 += $value->a12;
      $n13 += $value->a13;
      $n14 += $value->a14;
      $n15 += $value->a15;
      $n16 += $value->a16;
      $n17 += $value->a17;
      $n18 += $value->a18;
      $n19 += $value->a19;
      $n20 += $value->a20;
      $n21 += $value->a21;
      $n22 += $value->a22;
      $n23 += $value->a23;
      $n24 += $value->a24;
      $n25 += $value->a25;
      $n26 += $value->a26;
      $n27 += $value->a27;
      $n28 += $value->a28;
      $n29 += $value->a29;
      $n30 += $value->a30;
      $n31 += $value->a31;

      $expensestotal[$value->acnoname] = $value->a1 + $value->a2 + $value->a3 +  $value->a4 + $value->a5 + $value->a6 + $value->a7 + $value->a8 +
        $value->a9 + $value->a10 + $value->a11 + $value->a12 + $value->a13 + $value->a14 + $value->a15 + $value->a16 + $value->a17 + $value->a18 + $value->a19 +
        $value->a20 + $value->a21 + $value->a22 + $value->a23 + $value->a24 + $value->a25 + $value->a26 + $value->a27 + $value->a28 + $value->a29 + $value->a30 +
        $value->a31;
    }

    foreach ($cashcheck_data as $key => $value) {

      $n1 += $value->a1;
      $n2 += $value->a2;
      $n3 += $value->a3;
      $n4 += $value->a4;
      $n5 += $value->a5;
      $n6 += $value->a6;
      $n7 += $value->a7;
      $n8 += $value->a8;
      $n9 += $value->a9;
      $n10 += $value->a10;
      $n11 += $value->a11;
      $n12 += $value->a12;
      $n13 += $value->a13;
      $n14 += $value->a14;
      $n15 += $value->a15;
      $n16 += $value->a16;
      $n17 += $value->a17;
      $n18 += $value->a18;
      $n19 += $value->a19;
      $n20 += $value->a20;
      $n21 += $value->a21;
      $n22 += $value->a22;
      $n23 += $value->a23;
      $n24 += $value->a24;
      $n25 += $value->a25;
      $n26 += $value->a26;
      $n27 += $value->a27;
      $n28 += $value->a28;
      $n29 += $value->a29;
      $n30 += $value->a30;
      $n31 += $value->a31;

      $cashchecktotal[$value->acnoname] = $value->a1 + $value->a2 + $value->a3 +  $value->a4 + $value->a5 + $value->a6 + $value->a7 + $value->a8 +
        $value->a9 + $value->a10 + $value->a11 + $value->a12 + $value->a13 + $value->a14 + $value->a15 + $value->a16 + $value->a17 + $value->a18 + $value->a19 +
        $value->a20 + $value->a21 + $value->a22 + $value->a23 + $value->a24 + $value->a25 + $value->a26 + $value->a27 + $value->a28 + $value->a29 + $value->a30 +
        $value->a31;
    }

    $ca2 = ($beginingbal + $p1) - $n1;
    $ca3 = ($ca2 + $p2) - $n2;
    $ca4 = ($ca3 + $p3) - $n3;
    $ca5 = ($ca4 + $p4) - $n4;
    $ca6 = ($ca5 + $p5) - $n5;

    $ca7 = ($ca6 + $p6) - $n6;
    $ca8 = ($ca7 + $p7) - $n7;
    $ca9 = ($ca8 + $p8) - $n8;
    $ca10 = ($ca9 + $p9) - $n9;
    $ca11 = ($ca10 + $p10) - $n10;
    $ca12 = ($ca11 + $p11) - $n11;
    $ca13 = ($ca12 + $p12) - $n12;
    $ca14 = ($ca13 + $p13) - $n13;
    $ca15 = ($ca14 + $p14) - $n14;
    $ca16 = ($ca15 + $p15) - $n15;
    $ca17 = ($ca16 + $p16) - $n16;
    $ca18 = ($ca17 + $p17) - $n17;
    $ca19 = ($ca18 + $p18) - $n18;
    $ca20 = ($ca19 + $p19) - $n19;
    $ca21 = ($ca20 + $p20) - $n20;
    $ca22 = ($ca21 + $p21) - $n21;
    $ca23 = ($ca22 + $p22) - $n22;
    $ca24 = ($ca23 + $p23) - $n23;
    $ca25 = ($ca24 + $p24) - $n24;
    $ca26 = ($ca25 + $p25) - $n25;
    $ca27 = ($ca26 + $p26) - $n26;
    $ca28 = ($ca27 + $p27) - $n27;
    $ca29 = ($ca28 + $p28) - $n28;
    $ca30 = ($ca29 + $p29) - $n29;
    $ca31 = ($ca30 + $p30) - $n30;
    $ca32 = ($ca31 + $p31) - $n31;

    // cash available
    $c2 = [
      'CASH AVAILABLE',
      number_format($beginingbal, 2),
      number_format($ca2, 2),
      number_format($ca3, 2),
      number_format($ca4, 2),
      number_format($ca5, 2),
      number_format($ca6, 2),
      number_format($ca7, 2),
      number_format($ca8, 2),
      number_format($ca9, 2),
      number_format($ca10, 2),
      number_format($ca11, 2),
      number_format($ca12, 2),
      number_format($ca13, 2),
      number_format($ca14, 2),
      number_format($ca15, 2),
      number_format($ca16, 2),
      number_format($ca17, 2),
      number_format($ca18, 2),
      number_format($ca19, 2),
      number_format($ca20, 2),
      number_format($ca21, 2),
      number_format($ca22, 2),
      number_format($ca23, 2),
      number_format($ca24, 2),
      number_format($ca25, 2),
      number_format($ca26, 2),
      number_format($ca27, 2),
      number_format($ca28, 2),
      number_format($ca29, 2),
      number_format($ca30, 2),
      number_format($ca31, 2),
      'TOTAL',
    ];



    /// layouting
    $str .= $this->reporter->startrow();
    foreach ($c2 as $key => $value) {
      $str .= $this->reporter->col($value, '200', null, 'TLBR', $border, 'TLBR', 'C', $font, '12', '', '', '');
    }
    $str .= $this->reporter->endrow();

    foreach ($grpdata as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($value->groupname), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', 'red', '');
      $str .= $this->reporter->col(number_format($value->a1, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a2, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a3, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a4, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a5, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a6, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a7, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a8, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a9, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a10, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a11, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a12, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a13, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a14, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a15, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a16, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a17, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a18, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a19, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a20, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a21, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a22, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a23, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a24, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a25, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a26, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a27, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a28, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a29, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a30, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a31, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($grptotal[$value->groupname], 2), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    foreach ($wsdata as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($value->ws), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', 'orange', '');
      $str .= $this->reporter->col(number_format($value->a1, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a2, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a3, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a4, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a5, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a6, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a7, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a8, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a9, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a10, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a11, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a12, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a13, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a14, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a15, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a16, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a17, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a18, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a19, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a20, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a21, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a22, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a23, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a24, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a25, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a26, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a27, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a28, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a29, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a30, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a31, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($wstotal[$value->ws], 2), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    foreach ($pc3data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($value->acnoname), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', 'green', '');
      $str .= $this->reporter->col(number_format($value->a1, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a2, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a3, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a4, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a5, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a6, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a7, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a8, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a9, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a10, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a11, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a12, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a13, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a14, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a15, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a16, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a17, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a18, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a19, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a20, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a21, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a22, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a23, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a24, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a25, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a26, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a27, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a28, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a29, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a30, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a31, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($pc3total[$value->acnoname], 2), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($p1, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p2, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p3, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p4, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p5, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p6, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p7, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p8, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p9, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p10, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p11, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p12, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p13, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p14, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p15, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p16, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p17, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p18, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p19, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p20, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p21, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p22, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p23, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p24, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p25, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p26, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p27, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p28, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p29, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p30, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
    $str .= $this->reporter->col(number_format($p31, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');

    $psalestotal = $p1 + $p2 + $p3 +  $p4 + $p5 + $p6 + $p7 + $p8 +
      $p9 + $p10 + $p11 + $p12 + $p13 + $p14 + $p15 + $p16 + $p17 + $p18 + $p19 +
      $p20 + $p21 + $p22 + $p23 + $p24 + $p25 + $p26 + $p27 + $p28 + $p29 + $p30 +
      $p31;
    $str .= $this->reporter->col(number_format($psalestotal, 2), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();

    foreach ($expenses_data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($value->acnoname), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', 'blue', '');
      $str .= $this->reporter->col(number_format($value->a1, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a2, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a3, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a4, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a5, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a6, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a7, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a8, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a9, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a10, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a11, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a12, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a13, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a14, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a15, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a16, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a17, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a18, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a19, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a20, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a21, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a22, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a23, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a24, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a25, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a26, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a27, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a28, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a29, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a30, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a31, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($expensestotal[$value->acnoname], 2), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    foreach ($cashcheck_data as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($value->acnoname), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', 'purple', '');
      $str .= $this->reporter->col(number_format($value->a1, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a2, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a3, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a4, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a5, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a6, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a7, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a8, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a9, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a10, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a11, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a12, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a13, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a14, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a15, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a16, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a17, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a18, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a19, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a20, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a21, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a22, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a23, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a24, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a25, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a26, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a27, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a28, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a29, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a30, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($value->a31, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', '', '', '4px');
      $str .= $this->reporter->col(number_format($cashchecktotal[$value->acnoname], 2), '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL CASH AVAILABLE', '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col(number_format($ca2, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca3, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca4, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca5, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca6, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca7, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca8, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca9, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca10, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca11, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca12, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca13, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca14, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca15, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca16, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca17, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca18, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca19, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca20, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca21, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca22, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca23, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca24, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca25, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca26, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca27, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca28, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca29, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca30, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca31, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');
    $str .= $this->reporter->col(number_format($ca32, 2), '200', null, 'TLBR', $border, 'TLBR', 'R', $font, '12', 'B', '', '4px');

    $str .= $this->reporter->col('', '200', null, 'TLBR', $border, 'TLBR', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();



    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class