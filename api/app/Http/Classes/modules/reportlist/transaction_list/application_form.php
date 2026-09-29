<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class application_form
{
  public $modulename = 'Application Form';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];



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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);


    $fields = ['radioreporttype', 'radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioreporttype.options', [
      ['label' => 'Default', 'value' => '0', 'color' => 'orange'],
      ['label' => 'Client Monitoring Report', 'value' => '1', 'color' => 'orange']
    ]);
    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Approved', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Pending', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,'' as clientname, '0' as clientid,
                        '' as userid,'2' as posttype,'' as dclientname,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                        '0' as reporttype";
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

  public function reportplotting($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($reporttype == '0') {
      $result = $this->reportDefaultLayout_SUMMARIZED($config);
    } else {
      $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1000'];
      $result = $this->customer_monitoring_layout($config);
    }
    return $result;
  }

  public function reportDefault($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($reporttype == '1') {
      $query = $this->customer_monitoring_QUERY($config);
    } else {
      $query = $this->default_QUERY($config);
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $clientname = $config['params']['dataparams']['clientname'];
    $posttype = $config['params']['dataparams']['posttype'];
    $filter = "";
    $leftjoin = "";

    if ($client != "") {
      $leftjoin = "left join client on client.client = head.client";
      $filter .= " and client.clientid = '$clientid' ";
    }

    $fcenter = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and num.center = '$fcenter'";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "
        select 'APPROVED' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from heahead as head
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 

        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount

        order by docno ";
        break;

      case 1: // unposted
        $query = "select 'PENDING' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from eahead as head
        left join eainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "

        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount
        
        order by docno ";
        break;

      default: // all
        $query = "select 'APPROVED' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from heahead as head
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        
        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount

        union all

        select 'PENDING' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from eahead as head
        left join eainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        
        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount
        
        order by docno ";

        break;
    } // end switch posttype

    return $query;
  }

  public function customer_monitoring_QUERY($config)
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client = $config['params']['dataparams']['client'];
    $clientid = $config['params']['dataparams']['clientid'];
    $fcenter = $config['params']['dataparams']['center'];

    $filter = "";

    // client filter (replaces the hardcoded gldetail.clientid = 44)
    if ($client != "") {
      $filter .= " and gldetail.clientid = " . intval($clientid) . " ";
    }

    // center filter (replaces the hardcoded t.center = '001')
    if ($fcenter != "") {
      $filter .= " and t.center = '" . addslashes($fcenter) . "' ";
    }

    $query = "
    select head.trno, 'APPROVED' as `status`, head.docno as lenumber, sched.principal,
      head.clientname as borrower, head.contactno as phone, r.reqtype as category,
      head.terms as lcycle, info.clientname as coborrower, head.contactno2 as phone2,
      ifnull(date(info.bday), '') as bday, head.email,
      ifnull(info.pf + info.nf, 0) as processfee,
      ifnull(info.annotationfee + info.docstamp1 + info.articles + info.annotationexp
        + info.otransfer + info.rpt + info.handling + info.appraisal + info.filing
        + info.referral + info.cancellation4 + info.cancellation7
        + info.annotationoc1 + info.annotationoc2 + info.cancellationu + info.mri, 0) as otherfees,
      ifnull(info.mri, 0) as mri, ifnull(info.docstamp, 0) as dst,
      date(head.releasedate) as rdate, month(head.releasedate) as rmonth, year(head.releasedate) as ryear,
      ifnull(info.entryfee + info.lrf + info.itfee + info.regfee + info.docstamp
        + info.nf2 + info.nf3 + info.ofee, 0) as takeout,
      date(sched.startdate) as startdate, date(sched.enddate) as duedate,
      info.amortization, info.country as pheld,
      sum(case when coa.alias = 'AR2' then gldetail.db else 0 end) as interest,
      sum(case when coa.alias = 'AR3' then gldetail.db else 0 end) as pfnf,
      ifnull(sum(ar.bal), 0) as receivable, terms.days as tinmonths
    from glhead
    left join gldetail on gldetail.trno = glhead.trno
    left join coa on coa.acnoid = gldetail.acnoid
    left join arledger as ar on ar.trno = gldetail.trno and ar.line = gldetail.line
    left join transnum as t on t.cvtrno = glhead.trno
    left join heahead as head on head.trno = t.trno
    left join heainfo as info on info.trno = head.trno
    left join (
    select trno,
        min(dateid) as startdate,
        max(dateid) as enddate,
        sum(principal) as principal,
        sum(bal) as bal
      from htempdetailinfo
      group by trno
    ) as sched on sched.trno = info.trno
    left join reqcategory as r on r.line = head.planid
    left join terms on terms.terms=head.terms
    where glhead.doc = 'CV' and head.doc = 'LE'
    and coa.alias in ('AR1','AR2','AR3')
    and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
    group by head.trno, head.docno, head.clientname, head.contactno, head.contactno2,
    head.email, head.terms, head.releasedate,
    r.reqtype, sched.principal, sched.startdate, sched.enddate, sched.bal,
    info.clientname, info.bday, info.pf, info.nf, info.mri, info.docstamp,
    info.entryfee, info.lrf, info.itfee, info.regfee, info.nf2, info.nf3, info.ofee,
    info.annotationfee, info.docstamp1, info.articles, info.annotationexp,
    info.otransfer, info.rpt, info.handling, info.appraisal, info.filing,
    info.referral, info.cancellation4, info.cancellation7,
    info.annotationoc1, info.annotationoc2, info.cancellationu,
    info.amortization, info.country, terms.days
    order by head.docno ";

    return $query;
  }

  public function customer_monitoring_header($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    // switch ($posttype) {
    //   case 0:
    //     $posttype = 'Approved';
    //     break;

    //   case 1:
    //     $posttype = 'Pending';
    //     break;

    //   default:
    //     $posttype = 'All';
    //     break;
    // }

    $str = '';


    $layoutsize = '2000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "7";
    $border = "1px solid ";

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->name, null, null, false, $border, '', 'C', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->address, null, null, false, $border, '', 'C', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($headerdata[0]->tel, null, null, false, $border, '', 'C', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center, $username, $config);
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();


    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);


    $user = "ALL USERS";

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CUSTOMER MONITORING REPORT', null, null, false, $border, '', '', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end . '&nbsp&nbsp&nbsp&nbsp&nbsp     User: ' . $user, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    // $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    // $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('STATUS', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LOAN CYCLE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('LE #', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE RECEIVED', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('YEAR', '40', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MONTH', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CATEGORY', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BORROWER`S NAME', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PHONE NUMBER', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('POSITION HELD', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BIRTHDATE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('EMAIL', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CO-BORROWER', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PHONE NUMBER', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PRINCIPAL', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INTEREST', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PROCESSING FEE', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OTHER FEES', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MRI', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DST', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('FI', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TAKEOUT FEE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('%', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL RECEIVABLE', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TERMS<br>' . '(in months)</br>', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('START DATE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DUE DATE', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('MONTHLY AMORTIZATION', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function customer_monitoring_layout($config)
  {
    $result = $this->reportDefault($config);
    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '2000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "7";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->customer_monitoring_header($config);

    $totalreceivable = 0;
    $year = '';
    $month = '';

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $year = date('Y', strtotime($data->rdate));
        $month = date('F', strtotime($data->rdate));
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->status, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->lcycle, '80', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->lenumber, '80', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rdate, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($year, '40', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($month, '50', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->category, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->borrower, '170', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->phone, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->pheld, '70', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->bday, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->email, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->coborrower, '170', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->phone2, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->principal == 0 ? '-' : number_format($data->principal, 2), '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->interest == 0 ? '-' : number_format($data->interest, 2), '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->pfnf == 0 ? '-' : number_format($data->pfnf, 2), '70', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->otherfees == 0 ? '-' : number_format($data->otherfees, 2), '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->mri == 0 ? '-' : number_format($data->mri, 2), '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dst == 0 ? '-' : number_format($data->dst, 2), '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->takeout == 0 ? '-' : number_format($data->takeout, 2), '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->receivable == 0 ? '-' : number_format($data->receivable, 2), '70', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->tinmonths, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->startdate, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->duedate, '60', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->amortization == 0 ? '-' : number_format($data->amortization, 2), '80', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $totalreceivable += $data->receivable;

      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '40', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '180', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL: ', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($totalreceivable, '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $reporttype = 'Summarized';

    $str = '';


    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);


    $user = "ALL USERS";


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Application Form Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);

    $totalamount = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->borrower, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->loantype, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totalamount = $totalamount + $data->amount;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader)
            $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamount, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Borrower', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Loan Type', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Terms', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class