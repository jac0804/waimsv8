<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class monthly_summary_of_ewt_report
{
  public $modulename = 'Monthly Summary of EWT Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1000'];

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

    if ($companyid == 56) { //homework
      data_set($col1, 'radioprint.options', [
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
        ['label' => 'CSV', 'value' => 'CSV', 'color' => 'red']
      ]);
    }

    $fields = ['dateid', 'due', 'dcentername'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col2 = $this->fieldClass->create($fields);
        data_set($col2, 'ddeptname.label', 'Department');
        break;
      case 56: //homeworks
        array_push($fields, 'dclientname');
        $col2 = $this->fieldClass->create($fields);
        break;
      default:
        $col2 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as dateid,left(now(),10) as due,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    0 as deptid, '' as ddeptname, '' as dept, '' as deptname";

    switch ($companyid) {
      case 56: //homeworks
        $paramstr .= ", '0' as clientid, '' as client, '' as clientname, '' as dclientname ";
        break;
    }

    return $this->coreFunctions->opentable($paramstr);
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
    $startdate  = $this->othersClass->datefilter(date("Y-m-d", strtotime($filters['params']['dataparams']['dateid'])));
    $enddate    = $this->othersClass->datefilter(date("Y-m-d", strtotime($filters['params']['dataparams']['due'])));

    $filter = "";
    $filter1 = "";

    $wt1 = '';
    $wt2 = '';
    $center     = $filters['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cnt.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $filters['params']['dataparams']['ddeptname'];
      if ($deptname != "") {
        $deptid = $filters['params']['dataparams']['deptid'];
        $filter1 .= " and h.deptid = $deptid";
      }
    } elseif ($companyid == 56) { //homeworks
      $client    = $filters['params']['dataparams']['client'];
      $clientid = $filters['params']['dataparams']['clientid'];
      if ($client != "") {
        $filter1 .= " and c.clientid = '$clientid'";
      }
      $wt1 = ',abs(ifnull((select sum(g.db-g.cr) from ladetail as g where trno=tbl1.trno and g.acnoid=5533),0)) as wt';
      $wt2 = ',abs(ifnull((select sum(g.db-g.cr) from gldetail as g where trno=tbl2.trno and g.acnoid=5533),0)) as wt';
    } else {
      $filter1 .= "";
    }

    $qry = "
      select * $wt1 from (
        select h.trno,h.docno, date(h.dateid) as dateid, c.clientname as supplier, c.client, c.tin, c.addr, sum(d.db-d.cr) as purchases, d.ewtrate  as rate, ewt.code,
        cent.name 
        from lahead as h
        left join ladetail as d on h.trno=d.trno
        left join client as c on c.client=h.client
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        left join center as cent on cent.code=cnt.center
        where date(h.dateid) between '$startdate' and '$enddate' $filter $filter1 
        and cnt.doc in ('GJ','CV','PV','RR','AC') 
        and (d.isvewt = 1 or d.isewt = 1) 
        group by h.trno,h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code,cent.name,d.ewtrate 
      ) as tbl1
      
        union all 
      select * $wt2 from (
        select h.trno,h.docno, date(h.dateid) as dateid, c.clientname as supplier, c.client, c.tin, c.addr, sum(d.db-d.cr) as purchases, d.ewtrate as rate, ewt.code,
        cent.name 
        from glhead as h
        left join gldetail as d on h.trno=d.trno
        left join client as c on c.clientid=h.clientid
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        left join center as cent on cent.code=cnt.center
        where date(h.dateid) between '$startdate' and '$enddate' $filter $filter1 
        and cnt.doc in ('GJ','CV','PV','RR','AC') 
        and (d.isvewt = 1 or d.isewt = 1)  
        group by h.trno,h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code,cent.name,d.ewtrate  
      ) as tbl2
      order by dateid, code, client, docno";
    $data = $this->coreFunctions->opentable($qry);
    
    return $data;
  }

  public function ROZLAB_query($filters)
  {
    $startdate  = $this->othersClass->datefilter(date("Y-m-d", strtotime($filters['params']['dataparams']['dateid'])));
    $enddate    = $this->othersClass->datefilter(date("Y-m-d", strtotime($filters['params']['dataparams']['due'])));

    $filter = "";
    $filter1 = "";

    $center     = $filters['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cnt.center='$center'";
    }

    $filter1 .= "";

    $qry = "
      select * from (
        select h.docno, date(h.dateid) as dateid, c.clientname as supplier, c.client, c.tin, c.addr, 
        sum(d.db-d.cr) as purchases,sum(d.db-d.cr)/1.12 as netvat, ewt.rate, ewt.code,
        (select sum(dewt.cr-dewt.db) from ladetail as dewt
        left join coa as cewt on cewt.acnoid=dewt.acnoid
        where left(cewt.alias,2)='WT' and dewt.trno=h.trno) as ewt
        from lahead as h
        left join ladetail as d on h.trno=d.trno
        left join client as c on c.client=h.client
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        where date(h.dateid) between '$startdate' and '$enddate' $filter $filter1 
        and cnt.doc in ('GJ','CV','PV','RR','AC') and left(coa.alias,2)='AP'
        and (d.isvewt = 1 or d.isewt = 1) 
        group by h.trno,h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code
        union all 
        select h.docno, date(h.dateid) as dateid, c.clientname as supplier, c.client, c.tin, c.addr, 
        sum(d.db-d.cr) as purchases,sum(d.db-d.cr)/1.12 as netvat, ewt.rate, ewt.code,
        (select sum(dewt.cr-dewt.db) from gldetail as dewt
        left join coa as cewt on cewt.acnoid=dewt.acnoid
        where left(cewt.alias,2)='WT' and dewt.trno=h.trno) as ewt
        from glhead as h
        left join gldetail as d on h.trno=d.trno
        left join client as c on c.clientid=h.clientid
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        where date(h.dateid) between '$startdate' and '$enddate' $filter $filter1 
        and cnt.doc in ('GJ','CV','PV','RR','AC') and left(coa.alias,2)='AP'
        and (d.isvewt = 1 or d.isewt = 1)  
        group by h.trno,h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code 
      ) as tbl
      order by dateid, code, client, docno";

    $data = $this->coreFunctions->opentable($qry);

    return $data;
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];

    if ($companyid == 36) {
      $result = $this->ROZLAB_query($config);
    } else {
      $result = $this->default_query($config);
    }

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $reportdata =  $this->AFTI_EWT_LAYOUT($result, $config);
        break;
      case 36: //ROZLAB
        $reportdata =  $this->ROZLAB_EWT_LAYOUT($result, $config);
        break;
      case 56: //homeworks
        $reportdata =  $this->HOMEWORKS_EWT_LAYOUT($result, $config);
        break;
      default:
        $reportdata =  $this->DEFAULT_EWT_LAYOUT($result, $config);
        break;
    }

    return $reportdata;
  }

  private function DEFAULT_EWT_HEADER($params)
  {
    $username   = $params['params']['user'];
    $ccenter   = $params['params']['center'];
    $companyid = $params['params']['companyid'];

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $params['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $params['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($ccenter, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Monthly Summary of Expanded Withholding Tax', null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period of ' . date('m/d/y', strtotime($start)) . ' - ' . date('m/d/y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');

    if ($companyid == 10 || $companyid == 12) { //aftii, afti usd
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '950', null, false, $border, '', '', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->pagenumber('Page');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Supplier', '175', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    switch ($companyid) {
      case 10:
      case 12:
        $str .= $this->reporter->col('Doc #', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('ATC', '100', '', false, $border, 'TB', 'C', $font, $fontsize, 'b', '', '', '');
        $str .= $this->reporter->col('Tax Base', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('EWT%', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('EWT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('Address', '175', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Doc #', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('Purchases', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('EWT%', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
        $str .= $this->reporter->col('EWT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_EWT_LAYOUT($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    // $count = 41;
    // $page = 40;
    $this->reporter->linecounter = 0;
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $group = $str = '';
    $a = $b = $c = $totala = $totalb = $totalc = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_EWT_HEADER($params);

    $date = "";
    $str .= $this->reporter->begintable('1000');
    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      if ($date != $data_->dateid) {
        if ($date != "") {

          $str .= $this->DEFAULT_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);

          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          $group = $data_->code;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
        if ($c == 0) {
          $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '');
        } else {
          $str .= $this->reporter->col('Sub Total: ' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->supplier, '175', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      switch ($companyid) {
        case 10:
        case 12:
          $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->code, '100', '', false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->purchases, $decimal_currency), '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->rate . '%', '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col($data_->addr, '175', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($data_->purchases, $decimal_currency), '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col($data_->rate . '%', '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
          break;
      }

      $ewtpercentage = $data_->rate / 100;
      $ewt = $data_->purchases * $ewtpercentage;

      $str .= $this->reporter->col(number_format($ewt, $decimal_currency), '100', '1', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->endrow();

      $date = $data_->dateid;
      $a += $data_->purchases;
      $b += $data_->rate;
      $c += $ewt;
      $totala = $totala + $data_->purchases;
      $totalb = $totalb + $data_->rate;
      $totalc = $totalc + $ewt;

      if ($cnt == $cnt1) {
        if ($data_->docno == '') {
          // $group = 'NO DATE';
        } else {

          $str .= $this->DEFAULT_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);


          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          // $group = $data_->docno;
        }
      }
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
    $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize, 'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        break;
      default:
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_EWT_SUBTOTAL($a, $b, $c, $companyid, $params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->startrow();
    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '175', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      switch ($companyid) {
        case 10:
        case 12:
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '175', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '', '');
          break;
      }
    } else {
      switch ($companyid) {
        case 10:
        case 12:
          $str .= $this->reporter->col('SUBTOTAL', '100', '', false, '1px dashed', 'T', 'l', $font, $fontsize,  'b', '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
          break;
      }
      $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
      switch ($companyid) {
        case 10:
        case 12:
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
          break;
        default:
          $str .= $this->reporter->col('', '175', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
          $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
          break;
      }
    }

    $str .= $this->reporter->endrow();
    return $str;
  }

  private function AFTI_EWT_LAYOUT($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    // $count = 41;
    // $page = 40;
    $this->reporter->linecounter = 0;
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $group = $str = '';
    $a = $b = $c = $totala = $totalb = $totalc = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->DEFAULT_EWT_HEADER($params);
    // $date = "";
    $str .= $this->reporter->begintable('1000');
    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '', '');
      $str .= $this->reporter->col($data_->supplier, '175', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '', '');
      $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '', '');
      $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '', '');
      $str .= $this->reporter->col($data_->code, '100', '', false, $border, '', 'c', $font, $fontsize, '', '', '', '', '');
      $str .= $this->reporter->col(number_format($data_->purchases, $decimal_currency), '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '', '');
      $str .= $this->reporter->col($data_->rate . '%', '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '', '');

      $ewtpercentage = $data_->rate / 100;
      $ewt = $data_->purchases * $ewtpercentage;

      $str .= $this->reporter->col(number_format($ewt, $decimal_currency), '100', '1', false, $border, '', 'r', $font, $fontsize,  '', '', '', '', '');
      $str .= $this->reporter->endrow();

      $date = $data_->dateid;
      $a += $data_->purchases;
      $b += $data_->rate;
      $c += $ewt;
      $totala = $totala + $data_->purchases;
      $totalb = $totalb + $data_->rate;
      $totalc = $totalc + $ewt;

      if ($cnt == $cnt1) {
        if ($data_->docno == '') {
          // $group = 'NO DATE';
        } else {

          $str .= $this->DEFAULT_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);
          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          // $group = $data_->docno;
        }
      }
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
    $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize, 'b', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
        break;
      default:
        $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', 0);
        $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
        $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
        $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
        break;
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function ROZLAB_EWT_HEADER($params)
  {
    $username   = $params['params']['user'];
    $ccenter   = $params['params']['center'];

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($ccenter, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Monthly Summary of Expanded Withholding Tax', null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period of ' . date('m/d/y', strtotime($start)) . ' - ' . date('m/d/y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Supplier', '150', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Tax ID No.', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Address', '150', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Doc #', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Purchases', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('Net Vat', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('EWT%', '75', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('EWT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function ROZLAB_EWT_LAYOUT($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    // $count = 41;
    // $page = 40;
    $this->reporter->linecounter = 0;
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $group = $str = '';
    $a = $b = $c = $totala = $totalb = $totalc = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    $str .= $this->reporter->beginreport('1000');
    $str .= $this->ROZLAB_EWT_HEADER($params);

    $date = "";
    $str .= $this->reporter->begintable('1000');
    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      if ($date != $data_->dateid) {
        if ($date != "") {

          $str .= $this->ROZLAB_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);

          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          $group = $data_->code;
        }
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
        if ($c == 0) {
          $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '');
        } else {
          $str .= $this->reporter->col('Sub Total: ' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('1000');
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->supplier, '150', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');

      $str .= $this->reporter->col($data_->addr, '150', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->docno, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col(number_format($data_->purchases, $decimal_currency), '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col(number_format($data_->netvat, $decimal_currency), '100', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->rate . '%', '75', '', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');

      $ewt = $data_->ewt;
      $str .= $this->reporter->col(number_format($ewt, $decimal_currency), '100', '1', false, $border, '', 'r', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->endrow();

      $date = $data_->dateid;
      $a += $data_->purchases;
      $b += $data_->netvat;
      $c += $ewt;
      $totala = $totala + $data_->purchases;
      $totalb = $totalb + $data_->netvat;
      $totalc = $totalc + $ewt;

      if ($cnt == $cnt1) {
        if ($data_->docno == '') {
          $group = 'NO DATE';
        } else {

          $str .= $this->ROZLAB_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);
          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          $group = $data_->docno;
        }
      }
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($totalb, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '75', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function ROZLAB_EWT_SUBTOTAL($a, $b, $c, $companyid, $params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->startrow();
    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '150', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '150', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '75', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '', '');
    } else {

      $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('' . number_format($b, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('', '75', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    return $str;
  }



  private function HM_EWT_HEADER($params)
  {
    $username   = $params['params']['user'];
    $ccenter   = $params['params']['center'];
    $companyid = $params['params']['companyid'];
    $center     = $params['params']['dataparams']['center'];

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

    $str = '';
    if ($center != '') {
      $layoutsize = '1000';
    } else {
      $layoutsize = '1100';
    }
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($ccenter, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize, null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Monthly Summary of Expanded Withholding Tax', null, null, false, $border, '', 'C', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize, null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Period of ' . date('m/d/y', strtotime($start)) . ' - ' . date('m/d/y', strtotime($end)), null, null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize, null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Print Date : ' . date('m/d/y'), '950', null, false, $border, '', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');

    // if ($center == '') {
    //   $str .= $this->reporter->col('Branch', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    // }
    $str .= $this->reporter->col('SUPPLIER CODE', '100', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('SUPPLIER NAME', '175', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('TIN', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');

    $str .= $this->reporter->col('ADDRESS', '175', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('DOCNO', '125', '', false, $border, 'TB', 'L', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('PURCHASES', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('EWT RATE', '100', '', false, $border, 'TB', 'C', $font, $fontsize,  'b', '', '', '');
    $str .= $this->reporter->col('EWT AMT', '100', '', false, $border, 'TB', 'R', $font, $fontsize,  'b', '', '', '');

    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    return $str;
  }
  private function HOMEWORKS_EWT_LAYOUT($data, $params)
  {
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);
    $center     = $params['params']['dataparams']['center'];

    // $count = 41;
    // $page = 40;
    $this->reporter->linecounter = 0;
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $group = $str = '';
    $a = $b = $c = $totala = $totalb = $totalc = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;


    if ($center != '') {
      $layoutsize = '1000';
    } else {
      $layoutsize = '1100';
    }

    if ($center == '') {
      $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:50px');
    } else {
      $str .= $this->reporter->beginreport($layoutsize);
    }

    $str .= $this->HM_EWT_HEADER($params);

    $date = "";
    // $str .= $this->reporter->begintable($layoutsize);
    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      if ($date != $data_->dateid) {
        if ($date != "") {

          $str .= $this->HM_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);

          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          $group = $data_->code;
        }
        $str .= $this->reporter->startrow();
        // $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'l', $font, $fontsize,  '', '', '', '');
        

        $str .= $this->reporter->col($data_->dateid, '100', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
        $str .= $this->reporter->col('', '175', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
        $str .= $this->reporter->col('', '175', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
        $str .= $this->reporter->col('', '125', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
        if ($c == 0) {
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col('', '100', '1', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
        } else {
          $str .= $this->reporter->col('Sub Total: ', '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
          $str .= $this->reporter->col(number_format($c, 2), '100', '1', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
        }
        
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        // $str .= $this->reporter->begintable($layoutsize);
      }


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      // if ($center == '') {
      $str .= $this->reporter->col($data_->client, '100', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      // }
      $str .= $this->reporter->col($data_->supplier, '175', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->tin, '125', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');

      $str .= $this->reporter->col($data_->addr, '175', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->docno, '125', '', false, $border, '', 'LT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col(number_format($data_->purchases, $decimal_currency), '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->col($data_->rate . '%', '100', '', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');


      // $ewtpercentage = $data_->rate / 100;
      // $ewt = $data_->purchases * $ewtpercentage;

      $str .= $this->reporter->col(number_format($data_->wt, $decimal_currency), '100', '1', false, $border, '', 'RT', $font, $fontsize,  '', '', '', '');
      $str .= $this->reporter->endrow();

      $date = $data_->dateid;
      $a += $data_->purchases;
      $b += $data_->rate;
      $c += $data_->wt;
      $totala = $totala + $data_->purchases;
      $totalb = $totalb + $data_->rate;
      $totalc = $totalc + $data_->wt;

      if ($cnt == $cnt1) {
        if ($data_->docno == '') {
          // $group = 'NO DATE';
        } else {

          $str .= $this->HM_EWT_SUBTOTAL($a, $b, $c, $companyid, $params);


          $str .= $this->reporter->addline();
          $a = 0;
          $b = 0;
          $c = 0;
          // $group = $data_->docno;
        }
      }
      $str .= $this->reporter->endrow();
    }

    // $str .= $this->reporter->endtable();

    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
    // if ($center == '') {
    $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'L', $font, $fontsize,  'B', '', '', '');
    // }
    $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');

    $str .= $this->reporter->col('', '175', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL : ', '125', '', false, $border, 'T', 'c', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($totala, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($totalc, 2), '100', '', false, $border, 'T', 'r', $font, $fontsize,  'b', '', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  private function HM_EWT_SUBTOTAL($a, $b, $c, $companyid, $params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $center     = $params['params']['dataparams']['center'];
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->startrow();
    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');

      // if ($center == '') {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      // }
      $str .= $this->reporter->col('', '175', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');

      $str .= $this->reporter->col('', '175', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', false, $border, '', 'r', $font, $fontsize,  'i', '', '', '', '');
    } else {

      $str .= $this->reporter->col('', '100', '', false, $border, 'B', 'c', $font, '',  'b', '', '', '', '');

      // if ($center == '') {
      $str .= $this->reporter->col('', '100', '', false, $border, 'B', 'c', $font, $fontsize,  'b', '', '', '', '');
      // }
      $str .= $this->reporter->col('', '175', '', false, $border, 'B', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, $border, 'B', 'c', $font, '',  'b', '', '', '', '');

      $str .= $this->reporter->col('', '175', '', false, $border, 'B', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('Sub Total :', '125', '', false, $border, 'B', 'c', $font, '',  'b', '', '', '', '');
      $str .= $this->reporter->col('' . number_format($a, 2), '100', '', false, $border, 'B', 'r', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, $border, 'B', 'r', $font, $fontsize,  'b', '', '', '', '');
      $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, $border, 'B', 'r', $font, $fontsize,  'b', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportdatacsv($config)
  {
    $companyid = $config['params']['companyid'];
    $startdate  = $this->othersClass->datefilter(date("Y-m-d", strtotime($config['params']['dataparams']['dateid'])));
    $enddate    = $this->othersClass->datefilter(date("Y-m-d", strtotime($config['params']['dataparams']['due'])));
    $filter = "";
    $filter1 = "";

    $center     = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cnt.center='$center'";
    }

    $client    = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    if ($client != "") {
      $filter1 .= " and c.clientid = '$clientid'";
    }
    
    $fields = '*';
    if($companyid == 56) { //homeworks
      // $wt = ',abs(ifnull((select sum(g.db-g.cr) from gldetail as g where trno=tbl.trno and g.acnoid=5533),0)) as `EWT AMT`';
      $wt1 = ',abs(ifnull((select sum(g.db-g.cr) from ladetail as g where trno=tbl1.trno and g.acnoid=5533),0)) as `EWT AMT`';
      $wt2 = ',abs(ifnull((select sum(g.db-g.cr) from gldetail as g where trno=tbl2.trno and g.acnoid=5533),0)) as `EWT AMT`';

      $fields1 = 'tbl1.branch as `BRANCH NAME`,tbl1.date as `DATE`,
      tbl1.client as `SUPPLIER CODE`,tbl1.client, tbl1.supplier as `SUPPLIER NAME`,tbl1.tin as `TIN`,tbl1.address as `ADDRESS`,tbl1.docno as `DOCNO`,tbl1.purchases as `PURCHASES`,tbl1.ewtrate as `EWTRATE`,tbl1.ewt as `EWT`';
      $fields2 = 'tbl2.branch as `BRANCH NAME`,tbl2.date as `DATE`,
      tbl2.client as `SUPPLIER CODE`,tbl2.client, tbl2.supplier as `SUPPLIER NAME`,tbl2.tin as `TIN`,tbl2.address as `ADDRESS`,tbl2.docno as `DOCNO`,tbl2.purchases as `PURCHASES`,tbl2.ewtrate as `EWTRATE`,tbl2.ewt as `EWT`';
    }

    $query = "
      select $fields1 $wt1 from (
        select  h.trno,date(h.dateid) as `DATE`, cent.name as `BRANCH`,   c.clientname as `SUPPLIER`,
                c.tin as `TIN`, c.addr as `ADDRESS`, h.docno as `DOCNO`, 
                sum(d.db-d.cr) as `PURCHASES`, ewt.rate as `EWTRATE`, ewt.code as `EWT`, c.client
        
        from lahead as h
        left join ladetail as d on h.trno=d.trno
        left join client as c on c.client=h.client
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        left join center as cent on cent.code=cnt.center
        where date(h.dateid) between '$startdate' and '$enddate' $filter $filter1 
        and cnt.doc in ('GJ','CV','PV','RR','AC') 
        and (d.isvewt = 1 or d.isewt = 1) 
        group by h.trno,h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code,cent.name
        ) as tbl1
        union all 
        select $fields2 $wt2 from (
        select h.trno,date(h.dateid) as `DATE`, cent.name as `BRANCH`,   c.clientname as `SUPPLIER`,
                c.tin as `TIN`, c.addr as `ADDRESS`, h.docno as `DOCNO`, 
                sum(d.db-d.cr) as `PURCHASES`, ewt.rate as `EWTRATE`, ewt.code as `EWT`, c.client
        from glhead as h
        left join gldetail as d on h.trno=d.trno
        left join client as c on c.clientid=h.clientid
        left join coa as coa on coa.acnoid=d.acnoid
        left join ewtlist as ewt on ewt.code = d.ewtcode
        left join cntnum as cnt on cnt.trno=h.trno
        left join center as cent on cent.code=cnt.center
        where date(h.dateid) between '$startdate' and '$enddate' $filter $filter1 
        and cnt.doc in ('GJ','CV','PV','RR','AC') 
        and (d.isvewt = 1 or d.isewt = 1)  
        group by h.trno,h.docno, h.dateid, ewt.rate, c.clientname, c.client, c.tin, c.addr, ewt.code,cent.name 
        ) as tbl2
      order by DATE, EWT, client, DOCNO";
      
    $data = $this->coreFunctions->opentable($query);

    foreach ($data as $row => $value) {
      $value->EWTRATE = $value->EWTRATE . '%';
      $value->PURCHASES = number_format($value->PURCHASES, 2);
      if ($center != "") {
        unset($value->BRANCH);
      }
      unset($value->client);
    }
    $status =  true;
    $msg = 'Generating CSV successfully';
    if (empty($data)) {
      $status =  false;
      $msg = 'No data Found';
    }
    return ['status' => $status, 'msg' => $msg, 'data' => $data, 'params' => $this->reportParams, 'name' => 'MonthlyExpandedWithholdingTax'];
  }
}
