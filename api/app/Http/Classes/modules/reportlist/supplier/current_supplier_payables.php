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

class current_supplier_payables
{
  public $modulename = 'Current Supplier Payables';
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

    $fields = ['radioprint', 'dclientname', 'dcentername', 'contra'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'contra.lookupclass', 'AP');
    data_set($col1, 'dcentername.required', false);

    $fields = ['radioposttype', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
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
    // $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 
      'default' as print,
      '' as centername,
      '' as contra,
      '0' as acnoid,
      '' as acnoname,
      '' as client,
      '' as clientname,
      '0' as posttype,
      '0' as reporttype,
      '' as dclientname,
      '" . $defaultcenter[0]['center'] . "' as center,
      '" . $defaultcenter[0]['centername'] . "' as centername,
      '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
      0 as deptid, '' as ddeptname, '' as dept, '' as deptname";

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
    // $center = $config['params']['center'];
    // $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 28: //xcomp
        switch ($reporttype) {
          case '0':
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case  '1':
            $result = $this->xcomp_Layout_DETAILED($config);
            break;
        }
        break;

      default:
        switch ($reporttype) {
          case '0':
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;
          case  '1':
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // $center     = $config['params']['dataparams']['center'];
    // $client     = $config['params']['dataparams']['client'];
    $posttype   = $config['params']['dataparams']['posttype'];
    // $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($posttype) {
      case '0':
        $query = $this->reportDefault_POSTED($config);
        break;
      case  '1':
        $query = $this->reportDefault_UNPOSTED($config);
        break;
      default:
        $query = $this->default_QUERY_ALL($config);
    }

    return $this->coreFunctions->opentable($query);
  }

  public function reportDefault_POSTED($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    // $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $contra = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];
    // $acnoname = $config['params']['dataparams']['acnoname'];
    $filter = "";
    $filter1 = "";
    $filter2 = "";

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($contra != '') {
      $filter .= " and coa.acnoid=$acnoid";
    }
    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      if ($deptname) {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    if ($companyid == 39) $filter2 = " and client.issupplier=1"; //cbbsi

    switch ($reporttype) {
      case '0': // SUMMARIZE
        $query = "select clientname, name, sum(balance) as balance from (
        select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
        (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance
        from (apledger as detail 
        left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno 
        left join glhead as head on head.trno=detail.trno
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        where detail.bal<>0 and left(coa.alias,2)='AP'
        $filter $filter1 $filter2
        ) as x group by clientname, name order by clientname ";
        break;
      case '1': // DETAILED
        switch ($companyid) {
          case 8: //maxipro
            $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance, head.yourref, head.ourref,
            ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from (apledger as detail
            left join glhead as head on head.trno = detail.trno
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            where detail.bal<>0  
            $filter $filter1
            order by client.clientname, detail.dateid, detail.docno";
            break;
          case 11: //summit
            $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance, head.yourref, head.ourref,
            ifnull((select group_concat(distinct ref) from gldetail left join glhead on glhead.trno=gldetail.trno
            where gldetail.trno=detail.trno and gldetail.line=detail.line and glhead.doc='AP' ),'') as reference,head.doc
            from (apledger as detail
            left join glhead as head on head.trno = detail.trno
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            left join coa on coa.acnoid=detail.acnoid
            where detail.bal<>0  
            $filter $filter1
            order by client.clientname, detail.dateid, detail.docno";
            break;
          default:
            $query = "select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance, head.yourref,head.ourref
            from (apledger as detail
            left join glhead as head on head.trno = detail.trno
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AP'
            $filter $filter1 $filter2
            order by client.clientname, detail.dateid, detail.docno";
            break;
        }
        break;
    }

    return $query;
  }

  public function reportDefault_UNPOSTED($config)
  {
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    // $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $company = $config['params']['companyid'];
    $contra       = $config['params']['dataparams']['contra'];
    // $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    // $filter2 = "";
    $filter3 = "";

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid=$acnoid";
    }

    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($company == 10 || $company == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      if ($deptname) {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }

    if ($company == 39) $filter3 = " and client.issupplier=1";

    switch ($company) {
      case 6: // MITSUKOSHI
        $summaryaddqry = " union all
        select 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RP') 
        $filter  
        group by client.clientname, head.dateid, head.docno";
        $detailaddqry = " union all
        select 'v' as tr, client.clientname,
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('RP')  
        $filter  
        group by client.clientname, head.dateid, head.docno, head.yourref";
        break;
      default:
        $summaryaddqry = "";
        $detailaddqry = "";
        break;
    }

    switch ($reporttype) {
      case '0': // SUMMARIZE
        $query = "select clientname, name, sum(balance) as balance 
        from (select 'u' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        (detail.cr - detail.db) as balance
        from lahead as head 
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('AP','GJ') and left(coa.alias,2)='ap' and detail.refx = 0
        $filter $filter1 $filter3
        union all select 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join ladetail as detail on detail.trno=head.trno
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RR') and left(coa.alias,2)='AP'
        $filter $filter1 $filter3
        group by client.clientname, head.dateid, head.docno
        $summaryaddqry
        ) as x group by clientname, name
        order by clientname";
        break;
      case  '1': // DETAILED
        if ($company == 8) {
          $query = "select 'u' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          (detail.cr - detail.db) as balance, head.yourref, head.ourref,
          ifnull((select group_concat(distinct stock.ref separator ', ')
          from lastock as stock where stock.trno=head.trno),'') as ponum
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno
          left join client on client.client=head.client
          left join coa on coa.acnoid=detail.acnoid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0 $filter $filter1
          union all 
          select 'v' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          sum(stock.ext) as balance, head.yourref, head.ourref,
          ifnull((select group_concat(distinct stock.ref separator ', ')
          from lastock as stock where stock.trno=head.trno),'') as ponum
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('RR') $filter $filter1
          group by client.clientname, head.dateid, head.docno, head.yourref, head.ourref, ponum $detailaddqry
          order by clientname,dateid, docno ";
        } else {
          if ($company == 11) {
            $ref1 = "detail.ref as reference";
            $ref2 = " '' as reference";
          } else {
            $ref1 = "detail.ref";
            $ref2 = " '' as ref";
          }
          $query = "select 'u' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          (detail.cr - detail.db) as balance, head.yourref, head.ourref ,$ref1,head.doc
          from lahead as head 
          left join ladetail as detail on detail.trno=head.trno
          left join client on client.client=head.client
          left join coa on coa.acnoid=detail.acnoid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0 $filter $filter1 $filter3
          union all 
          select 'v' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          sum(stock.ext) as balance, head.yourref, head.ourref ,$ref2,head.doc
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join ladetail as detail on detail.trno=head.trno
          left join coa on coa.acnoid=detail.acnoid
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('RR') and left(coa.alias,2)='ap' $filter $filter1 $filter3
          group by head.doc,client.clientname, head.dateid, head.docno, head.yourref, head.ourref $detailaddqry
          order by clientname,dateid,docno";
        }
        break;
    }

    return $query;
  }

  public function default_QUERY_ALL($config)
  {
    $center     = $config['params']['dataparams']['center'];
    $client     = $config['params']['dataparams']['client'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $company = $config['params']['companyid'];
    $contra       = $config['params']['dataparams']['contra'];
    // $acnoname       = $config['params']['dataparams']['acnoname'];
    $acnoid       = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }

    if ($contra != '') {
      $filter .= " and coa.acnoid=$acnoid";
    }

    if ($center != '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($company == 10 || $company == 12) {
      $deptname = $config['params']['dataparams']['ddeptname'];
      if ($deptname) {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
    }
    if ($company == 39) $filter3 = " and client.issupplier=1";

    switch ($company) {
      case '6': // MITSUKOSHI
        $summaryaddqry = " union all
        select 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RP') 
        $filter  
        group by client.clientname, head.dateid, head.docno";

        $detailaddqry = " union all
        select 'v' as tr, client.clientname,
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
        sum(stock.ext) as balance, head.yourref, head.ourref
        from lahead as head
        left join lastock as stock on stock.trno=head.trno
        left join client on client.clientid=stock.suppid
        left join cntnum on cntnum.trno=head.trno
        where head.doc in ('RP')  
        $filter  
        group by client.clientname, head.dateid, head.docno, head.yourref, head.ourref
        ";
        break;
      default:
        $summaryaddqry = "";
        $detailaddqry = "";
        break;
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 2: // all
            $query = "select x.clientname, x.name, sum(x.balance) as balance from (
            select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance
            from (apledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AP'
            $filter $filter1 $filter2
            union all
            select 'u' as tr, client.clientname, 
            ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
            (detail.cr - detail.db) as balance
            from lahead as head 
            left join ladetail as detail on detail.trno=head.trno
            left join client on client.client=head.client
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc in ('AP','GJ') and left(coa.alias,2)='ap' and detail.refx = 0
            $filter $filter1 $filter3
            union all 
            select 'v' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
            sum(stock.ext) as balance
            from lahead as head 
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join ladetail as detail on detail.trno=head.trno
            left join coa on coa.acnoid=detail.acnoid
            left join cntnum on cntnum.trno=head.trno 
            where head.doc in ('RR') and left(coa.alias,2)='AP'
            $filter $filter1 $filter3
            group by client.clientname, head.dateid, head.docno
            $summaryaddqry
            ) as x 
            group by x.clientname, x.name
            order by x.clientname;";
            break;
        }
        break;
      case 1: // detailed
        switch ($posttype) {
          case 2:
            if ($company == 8) {
              $query = "select * from (
              select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
              (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance, head.yourref, head.ourref,
              ifnull((select group_concat(distinct stock.ref separator ', ')
              from glstock as stock where stock.trno=head.trno),'') as ponum
              from (apledger as detail
              left join glhead as head on head.trno = detail.trno
              left join client on client.clientid=detail.clientid)
              left join cntnum on cntnum.trno=detail.trno 
              where detail.bal<>0  
              $filter $filter1 
              union all
              select 'u' as tr, client.clientname, 
              ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
              (detail.cr - detail.db) as balance, head.yourref, head.ourref,
              ifnull((select group_concat(distinct stock.ref separator ', ')
              from lastock as stock where stock.trno=head.trno),'') as ponum
              from lahead as head 
              left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0  $filter $filter1 
              union all 
              select 'v' as tr, client.clientname, 
              ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
              sum(stock.ext) as balance, head.yourref, head.ourref,
              ifnull((select group_concat(distinct stock.ref separator ', ')
              from lastock as stock where stock.trno=head.trno),'') as ponum
              from lahead as head 
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno 
              where head.doc in ('RR') $filter $filter1 
              group by client.clientname, head.dateid, head.docno, head.yourref, head.ourref, ponum $detailaddqry
              ) as result
              order by clientname, dateid, docno;";
            } else {
              if ($company == 11) {
                $ref1 = "detail.ref as reference";
                $ref2 = " '' as reference";
              } else {
                $ref1 = "detail.ref";
                $ref2 = " '' as ref";
              }
              $query = "select x.tr, x.clientname, x.name, x.dateid, x.docno, x.elapse, x.balance, x.yourref, x.ourref 
              from (select 'p' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
              (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance, head.yourref,head.ourref, gdetail.ref, head.doc
              from apledger as detail
              left join glhead as head on head.trno = detail.trno
              left join client on client.clientid=detail.clientid
              left join cntnum on cntnum.trno=detail.trno 
              left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
              left join coa on coa.acnoid=gdetail.acnoid
              where detail.bal<>0
              $filter $filter1 
              union all
              select 'u' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
              (detail.cr - detail.db) as balance, head.yourref, head.ourref, $ref1, head.doc
              from lahead as head 
              left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0 $filter $filter1 $filter3 
              union all 
              select 'v' as tr, client.clientname, ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
              sum(stock.ext) as balance, head.yourref, head.ourref, $ref2, head.doc
              from lahead as head 
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join ladetail as detail on detail.trno=head.trno
              left join coa on coa.acnoid=detail.acnoid
              left join cntnum on cntnum.trno=head.trno 
              where head.doc in ('RR') and left(coa.alias,2)='ap' $filter $filter1 $filter3 
              group by head.doc,client.clientname, head.dateid, head.docno, head.yourref, head.ourref
              $detailaddqry
              ) as x
              order by x.clientname,x.dateid,x.docno;";
            }
            break;
        }
        break;
    }

    return $query;
  }

  private function displayHeader_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $filtercentername = $config['params']['dataparams']['centername'];
    // $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];
    $contra = $config['params']['dataparams']['contra'];

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED CURRENT SUPPLIER PAYABLES', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($clientname == '') {
      $str .= $this->reporter->col('Supplier : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Supplier : ' . strtoupper($clientname), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == 0) {
      $posttype = 'Posted';
    } else if ($posttype == 1) {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    if ($filtercenter == '') {
      $filtercenter = 'ALL';
      $c = "ALL";
    } else {
      $c = $filtercenter . '-' . $filtercentername;
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $c, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('SUPPLIER', '400', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PO #', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('No. of days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BALANCE', '150', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    } else if ($companyid == 36) { //rozlab
      $str .= $this->reporter->col('SUPPLIER', '240', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '195', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('YOURREF', '125', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('OURREF', '175', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('No. of days', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('SUPPLIER', '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('No. of days', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BALANCE', '110', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    // $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];
    $count = 43;
    $page = 42;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    // $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_DETAILED($config);
    $layoutsize = '1000';
    $amt = null;
    $clientname = "";
    $subtotal = 0;

    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {

      // $display = $data->clientname;
      $docno = $data->docno;
      $date = $data->dateid;
      $order = $data->elapse;
      $served = $data->balance;
      $yourref = $data->yourref;
      $ourref = $data->ourref;

      if ($clientname != $data->clientname) {

        if ($clientname != "") {

          $str .= $this->reporter->startrow();
          if ($companyid == 8) { //maxipro
            $str .= $this->reporter->col('', '400', null, false, '1px dotted ', 'B', 'L', 'B', '10', '', 'b', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col('', '150', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col('SUB TOTAL : ', '100', null, false, '1px dotted ', 'BT', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '150', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
          } else if ($companyid == 36) { //rozlab
            $str .= $this->reporter->col('', '240', null, false, '1px dotted ', 'B', 'L', 'B', '10', '', 'b', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col('', '195', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col('', '125', null, false, '1px dotted ', 'B', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('SUB TOTAL : ', '175', null, false, '1px dotted ', 'BT', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'L', 'B', '10', '', 'b', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col('SUB TOTAL : ', '110', null, false, '1px dotted ', 'BT', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '110', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', ''); #SubtotalperSupplier
          }

          $str .= $this->reporter->endrow();
          $subtotal = 0;
        }

        $str .= $this->reporter->startrow();
        if ($companyid == 8) { //maipro
          $str .= $this->reporter->col($data->clientname, '400', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        } else if ($companyid == 36) { //rozlab
          $str .= $this->reporter->col($data->clientname, '240', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '195', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '125', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '175', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        } else {
          $str .= $this->reporter->col($data->clientname, '110', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        }

        $str .= $this->reporter->endrow();
      }

      $subtotal = $subtotal + $served;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col('', '400', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($date, '100', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($docno, '100', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ponum, '150', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($order, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($served, 2), '150', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('', '240', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($date, '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');

        if ($companyid == 11) { //summit
          if ($data->doc == 'AP') {
            if ($data->reference == "") {
              $str .= $this->reporter->col($docno, '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col($data->reference, '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
            }
          } else {
            $str .= $this->reporter->col($docno, '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
          }
        } else if ($companyid == 36) { //rozlab
          $str .= $this->reporter->col($docno, '195', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($yourref, '125', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($ourref, '175', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($docno, '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->col(number_format($order, 2), '100', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($served, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->endrow();

      $clientname = $data->clientname;
      $amt = $amt + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader_DETAILED($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '400', null, false, '1px dotted ', '', 'L', 'B', '10', '', 'b', '');
      $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('SUB TOTAL : ', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '150', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    } else if ($companyid == 36) { //rozlab
      $str .= $this->reporter->col('', '250', null, false, '1px dotted ', 'B', 'L', 'B', '10', '', 'b', '');
      $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('SUB TOTAL : ', '250', null, false, '1px dotted ', 'BT', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', 'B', '10', '', 'b', '');
      $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('', '110', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
      $str .= $this->reporter->col('SUB TOTAL : ', '110', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '110', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', ''); #SubAllTotal
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '400', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px solid ', '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '150', null, false, '1px solid ', '', 'R', $font, $fontsize, 'b', '', '');
    } else if ($companyid == 36) { //rozlab
      $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL : ', '250', null, false, '1px solid ', '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, 'b', '', '');
    } else {
      $str .= $this->reporter->col('', '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '110', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL : ', '110', null, false, '1px solid ', '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($amt, 2), '110', null, false, '1px solid ', '', 'R', $font, $fontsize, 'b', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function reportDefaultLayout_DETAILED_SUBTOTAL($subtotal = 0, $layoutsize, $config)
  {

    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    // $border = "1px solid ";

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'L', 'B', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
    $str .= $this->reporter->col('', '110', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
    $str .= $this->reporter->col('SUB TOTAL : ', '110', null, false, '1px dotted ', 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotal, 2), '110', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function displayHeader_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $filtercentername = $config['params']['dataparams']['centername'];
    // $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];
    $contra = $config['params']['dataparams']['contra'];


    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $dept   = $config['params']['dataparams']['ddeptname'];
      if ($dept != "") {
        $deptname = $config['params']['dataparams']['deptname'];
      } else {
        $deptname = "ALL";
      }
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CURRENT SUPPLIER PAYABLES - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($clientname == '') {
      $str .= $this->reporter->col('Supplier : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Supplier : ' . strtoupper($clientname), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == 0) {
      $posttype = 'Posted';
    } else if ($posttype == 1) {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    if ($filtercenter == '') {
      $filtercenter = 'ALL';
      $c = "ALL";
    } else {
      $c = $filtercenter . '-' . $filtercentername;
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $c, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER', '110px', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '110px', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];

    // $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];

    $count = 51;
    $page = 50;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->displayHeader_SUMMARIZED($config);

    $amt = null;
    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $display = $data->clientname;
      $served = $data->balance;

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($display, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($served, 2), '110px', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $amt = $amt + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->displayHeader_SUMMARIZED($config);
        $str .= $this->reporter->addline();

        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL : ', '110px', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($amt, 2), '110px', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  private function xcomp_Header_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    $filtercentername = $config['params']['dataparams']['centername'];
    // $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];
    $contra = $config['params']['dataparams']['contra'];

    // if ($companyid == 10 || $companyid == 12) { //afti, afti usd
    //   $dept   = $config['params']['dataparams']['ddeptname'];
    //   $deptname = $dept != "" ? $config['params']['dataparams']['deptname'] : "ALL";
    // }

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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DETAILED CURRENT SUPPLIER PAYABLES', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($clientname == '') {
      $str .= $this->reporter->col('Supplier : ALL', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Supplier : ' . strtoupper($clientname), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == 0) {
      $posttype = 'Posted';
    } else if ($posttype == 1) {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    if ($filtercenter == '') {
      $filtercenter = 'ALL';
      $c = "ALL";
    } else {
      $c = $filtercenter . '-' . $filtercentername;
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . $c, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('Account : ' . strtoupper($contra), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '200', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('YOURREF', '250', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('No. of days', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function xcomp_Layout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    // $center     = $config['params']['center'];
    // $username   = $config['params']['user'];
    // $companyid = $config['params']['companyid'];

    // $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    // $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];
    $count = 43;
    $page = 42;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    // $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);

    $str .= $this->xcomp_Header_DETAILED($config);
    $layoutsize = '1000';
    $amt = null;
    $clientname = "";
    $subtotal = 0;

    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {

      // $display = $data->clientname;
      $docno = $data->docno;
      $yourref = $data->yourref;
      $date = $data->dateid;
      $order = $data->elapse;
      $served = $data->balance;

      if ($clientname != $data->clientname) {
        if ($clientname != "") {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '250', null, false, '1px dotted ', 'B', 'L', 'B', '10', '', 'b', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'B', 'L', 'B', '10', 'B', '', '');
          $str .= $this->reporter->col('SUB TOTAL : ', '250', null, false, '1px dotted ', 'BT', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'BT', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $subtotal = 0;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, '250', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '250', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }

      $subtotal = $subtotal + $served;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($date, '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($docno, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($yourref, '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($order, 2), '100', null, false, '1px solid ', '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($served, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $clientname = $data->clientname;
      $amt = $amt + $data->balance;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->xcomp_Header_DETAILED($config);
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '250', null, false, '1px dotted ', '', 'L', 'B', '10', '', 'b', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted ', '', 'L', 'B', '10', 'B', '', '');
    $str .= $this->reporter->col('SUB TOTAL : ', '250', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL : ', '250', null, false, '1px solid ', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px solid ', '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($amt, 2), '100', null, false, '1px solid ', '', 'R', $font, $fontsize, 'b', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
