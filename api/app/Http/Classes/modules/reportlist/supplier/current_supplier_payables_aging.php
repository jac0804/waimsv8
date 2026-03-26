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

class current_supplier_payables_aging
{
  public $modulename = 'Current Supplier Payables Aging';
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
    if ($config['params']['companyid'] == 36 || $config['params']['companyid'] == 27) { //rozlab,nte
      $this->modulename = 'Supplier Payables Aging';
    }
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'dclientname', 'dcentername', 'contra'];
    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'ddeptname.label', 'Department');
        break;
      case 36: //rozlab
      case 27: //nte
        $fields = ['radioprint', 'asofdate', 'dclientname', 'dcentername', 'contra'];

        $col1 = $this->fieldClass->create($fields);
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dcentername.required', false);
    data_set($col1, 'contra.lookupclass', 'AP');
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
    '' as client,
    '' as clientname,
    '0' as posttype,
    '0' as reporttype,
    '' as dclientname,
    '' as contra,
    '0' as acnoid,
    '' as acnoname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    0 as deptid,
    '' as ddeptname, 
    '' as dept, 
    '' as deptname,
    date('" . $this->othersClass->getCurrentTimeStamp() . "') as asofdate";

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

    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0':
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      case '1':
        $result = $this->reportDefaultLayout_DETAILED($config);
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
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($contra != '') {
      $filter .= " and coa.acnoid=$acnoid";
    }
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      if ($deptname) {
        $deptid = $config['params']['dataparams']['deptid'];
        if ($reporttype == '0') {
          $filter1 .= " and gdetail.deptid = $deptid";
        } else {
          $filter1 .= " and head.deptid = $deptid";
        }
      }
    } else {
      $filter1 .= "";
    }

    if ($companyid == 36 || $companyid == 27) { //rozlab,nte
      $asofdate = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
      $filter .= " and detail.dateid<='$asofdate'";
    }

    switch ($reporttype) {
      case '1': // DETAILED
        switch ($companyid) {
          case 8: //maxipro
            $query = "select 'p' as tr, client.clientname, 
            ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance,head.yourref,
            ifnull((select group_concat(distinct stock.ref separator ', ')
            from glstock as stock where stock.trno=head.trno),'') as ponum
            from (apledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            left join glhead as head on head.trno=detail.trno
            where detail.bal<>0
            $filter $filter1
            order by client.clientname, detail.dateid, detail.docno";
            break;
          case 11: //summit
            $query = "select 'p' as tr, head.trno,head.doc,detail.ref,client.clientname, 
            ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance,head.yourref,
            ifnull((select group_concat(distinct ref) from gldetail left join glhead on glhead.trno=gldetail.trno
            where gldetail.trno=detail.trno and gldetail.line=detail.line and glhead.doc='AP' ),'') as reference
            from (apledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            left join glhead as head on head.trno=detail.trno
            left join coa on coa.acnoid=detail.acnoid
            where detail.bal<>0
            $filter $filter1
            order by client.clientname, detail.dateid, detail.docno";
            break;
          default:
            $query = "select 'p' as tr, head.trno,head.doc,detail.ref,client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance,head.yourref
            from (apledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AP'
            $filter $filter1
            order by client.clientname, detail.dateid, detail.docno";
            break;
        }
        break;
      case '0': // SUMARIZED
        $query = "select clientname, name, sum(balance) as balance, elapse 
        from (select client.clientname, ifnull(client.clientname,'no name') as name,
        date(detail.dateid) as dateid, detail.docno, 
        datediff(now(), detail.dateid) as elapse,
        (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance
        from (apledger as detail 
        left join client on client.clientid=detail.clientid)
        left join cntnum on cntnum.trno=detail.trno 
        left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
        left join coa on coa.acnoid=gdetail.acnoid
        where detail.bal<>0 and left(coa.alias,2)='AP'
        $filter $filter1) as x 
        group by clientname, name, elapse
        order by clientname";
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
    $contra = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];
    // $acnoname = $config['params']['dataparams']['acnoname'];
    $companyid = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($contra != '') {
      $filter2 .= " and coa.acnoid=$acnoid";
    }
    if ($center != "") {
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

    if ($companyid == 36 || $companyid == 27) { //rozlab, nte
      $asofdate = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
      $filter .= " and date(head.dateid)<='$asofdate'";
    }

    switch ($companyid) {
      case 6: // MITSUKOSHI
        $summaryaddqry = "union all 
        select cntnum.center, 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RP')
        $filter
        group by cntnum.center, client.clientname, head.dateid, head.docno";

        $detailaddqry = " union all 
        select cntnum.center, 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance, head.yourref
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RP')
        $filter
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref";
        break;
      default:
        $summaryaddqry = "";
        $detailaddqry = "";
        break;
    }

    switch ($reporttype) {
      case '1': // DETAILED
        if ($companyid == 8) { //maxipro
          $query = "select cntnum.center, 'u' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          detail.cr as balance, head.yourref,
          ifnull((select group_concat(distinct stock.ref separator ', ')
          from lastock as stock where stock.trno=head.trno),'') as ponum
          from (((lahead as head 
          left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0 $filter $filter1
          union all 
          select cntnum.center, 'v' as tr, client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          sum(stock.ext) as balance, head.yourref,
          ifnull((select group_concat(distinct stock.ref separator ', ')
          from lastock as stock where stock.trno=head.trno),'') as ponum
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('RR') $filter $filter1
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,ponum $detailaddqry
          order by clientname, dateid, docno";
        } else {
          if ($companyid == 11) { //summit
            $ref1 = "detail.ref as reference";
            $ref2 = " '' as reference";
          } else {
            $ref1 = "detail.ref";
            $ref2 = " '' as ref";
          }
          $query = "select cntnum.center, 'u' as tr, head.trno,head.doc,$ref1,client.clientname, ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          detail.cr as balance, head.yourref
          from (((lahead as head 
          left join ladetail as detail on detail.trno=head.trno)
          left join client on client.client=head.client)
          left join coa on coa.acnoid=detail.acnoid)
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0 $filter $filter1 $filter2
          union all 
          select cntnum.center, 'v' as tr, head.trno,head.doc,$ref2,client.clientname, 
          ifnull(client.clientname,'no name') as name,
          date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
          sum(stock.ext) as balance, head.yourref
          from lahead as head 
          left join lastock as stock on stock.trno=head.trno
          left join client on client.client=head.client
          left join cntnum on cntnum.trno=head.trno 
          where head.doc in ('RR') $filter $filter1
          group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc $detailaddqry
          order by clientname, dateid, docno";
        }
        break;
      case '0': // SUMMARIZED
        $query = "select clientname, name, elapse, sum(balance) as balance
        from (select cntnum.center, 'u' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        detail.cr as balance
        from (((lahead as head 
        left join ladetail as detail on detail.trno=head.trno)
        left join client on client.client=head.client)
        left join coa on coa.acnoid=detail.acnoid)
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0
        $filter $filter1 $filter2
        union all 
        select cntnum.center, 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RR')
        $filter $filter1
        group by cntnum.center, client.clientname, head.dateid, head.docno
        $summaryaddqry
        ) as x group by clientname, name, elapse
        order by clientname, name";
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
    $contra = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];
    // $acnoname = $config['params']['dataparams']['acnoname'];
    $companyid = $config['params']['companyid'];

    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $filter3 = "";

    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($contra != '') {
      $filter2 .= " and coa.acnoid=$acnoid";
    }
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $deptname = $config['params']['dataparams']['ddeptname'];
      if ($deptname) {
        $deptid = $config['params']['dataparams']['deptid'];
        $filter1 .= " and head.deptid = $deptid";
        $filter3 .= " and gdetail.deptid = $deptid";
      }
    } else {
      $filter1 .= "";
      $filter3 .= "";
    }

    $apdate = '';
    $headdate = '';
    if ($companyid == 36 || $companyid == 27) { //rozlab, nte
      $asofdate = date("Y-m-d", strtotime($config['params']['dataparams']['asofdate']));
      $apdate = " and date(detail.dateid)<='$asofdate'";
      $headdate = " and date(head.dateid)<='$asofdate'";
    }

    switch ($companyid) {
      case 6: // MITSUKOSHI
        $summaryaddqry = "union all 
        select cntnum.center, 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RP')
        $filter
        group by cntnum.center, client.clientname, head.dateid, head.docno";

        $detailaddqry = " union all 
        select cntnum.center, 'v' as tr, client.clientname, 
        ifnull(client.clientname,'no name') as name,
        date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
        sum(stock.ext) as balance, head.yourref
        from lahead as head 
        left join lastock as stock on stock.trno=head.trno
        left join client on client.client=head.client
        left join cntnum on cntnum.trno=head.trno 
        where head.doc in ('RP')
        $filter
        group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref
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
            $query = "select * from (
            select clientname, name, sum(balance) as balance, elapse 
            from (select client.clientname, ifnull(client.clientname,'no name') as name,
            date(detail.dateid) as dateid, detail.docno, 
            datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance
            from (apledger as detail 
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno 
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='ap' $apdate 
            $filter $filter3) as x 
            group by clientname, name, elapse
            union all select clientname, name, elapse, sum(balance) as balance
            from (select cntnum.center, 'u' as tr, client.clientname, 
            ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, detail.cr as balance
            from (((lahead as head 
            left join ladetail as detail on detail.trno=head.trno)
            left join client on client.client=head.client)
            left join coa on coa.acnoid=detail.acnoid)
            left join cntnum on cntnum.trno=head.trno 
            where head.doc in ('ap', 'gj') and left(coa.alias,2)='ap' and detail.refx = 0 $headdate $filter $filter1 $filter2
            union all select cntnum.center, 'v' as tr, client.clientname, 
            ifnull(client.clientname,'no name') as name,
            date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, sum(stock.ext) as balance
            from lahead as head 
            left join lastock as stock on stock.trno=head.trno
            left join client on client.client=head.client
            left join cntnum on cntnum.trno=head.trno 
            where head.doc in ('rr') $headdate $filter $filter1
            group by cntnum.center, client.clientname, head.dateid, head.docno
            $summaryaddqry
            ) as x 
            group by clientname, name, elapse
            ) as result
            order by clientname, name;";
            break;
        }
        break;
      case 1: // detailed
        switch ($posttype) {
          case 2:
            if ($companyid == 8) { //maxipro
              $query = "select client.clientname, 
              ifnull(client.clientname,'no name') as name,
              date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
              (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance,head.yourref,
              ifnull((select group_concat(distinct stock.ref separator ', ')
              from glstock as stock where stock.trno=head.trno),'') as ponum
              from (apledger as detail 
              left join client on client.clientid=detail.clientid)
              left join cntnum on cntnum.trno=detail.trno 
              left join glhead as head on head.trno=detail.trno
              where detail.bal<>0
              $filter $filter1 
              union all select client.clientname, 
              ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
              detail.cr as balance, head.yourref,
              ifnull((select group_concat(distinct stock.ref separator ', ')
              from lastock as stock where stock.trno=head.trno),'') as ponum
              from (((lahead as head 
              left join ladetail as detail on detail.trno=head.trno)
              left join client on client.client=head.client)
              left join coa on coa.acnoid=detail.acnoid)
              left join cntnum on cntnum.trno=head.trno 
              where head.doc in ('AP', 'GJ') and left(coa.alias,2)='ap' and detail.refx = 0  $filter $filter1 
              union all  select client.clientname, 
              ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, 
              sum(stock.ext) as balance, head.yourref,
              ifnull((select group_concat(distinct stock.ref separator ', ')
              from lastock as stock where stock.trno=head.trno),'') as ponum
              from lahead as head 
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno 
              where head.doc in ('RR') $filter $filter1 
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,ponum $detailaddqry
              order by name, dateid, docno;";
            } else {
              if ($companyid == 11) { //summit
                $ref1 = "detail.ref as reference";
                $ref2 = " '' as reference";
              } else {
                $ref1 = "detail.ref";
                $ref2 = " '' as ref";
              }
              $query = "select 'p' as tr, head.trno, head.doc, detail.ref, client.clientname, ifnull(client.clientname,'no name') as name,
              date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
              (case when detail.db>0 then (detail.bal*-1) else detail.bal end) as balance, head.yourref
              from (apledger as detail
              left join client on client.clientid=detail.clientid)
              left join cntnum on cntnum.trno=detail.trno
              left join glhead as head on head.trno=detail.trno
              left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
              left join coa on coa.acnoid=gdetail.acnoid
              where detail.bal<>0 and left(coa.alias,2)='ap' $apdate
              $filter $filter1
              union all select 'u' as tr, head.trno, head.doc, $ref1, client.clientname,
              ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse,
              detail.cr as balance, head.yourref
              from (((lahead as head
              left join ladetail as detail on detail.trno=head.trno)
              left join client on client.client=head.client)
              left join coa on coa.acnoid=detail.acnoid)
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('ap', 'gj') and left(coa.alias,2)='ap' and detail.refx = 0 $headdate $filter $filter1 $filter2
              union all select 'v' as tr, head.trno, head.doc, $ref2, client.clientname,
              ifnull(client.clientname,'no name') as name,
              date(head.dateid) as dateid, head.docno, datediff(now(), head.dateid) as elapse, sum(stock.ext) as balance, head.yourref
              from lahead as head
              left join lastock as stock on stock.trno=head.trno
              left join client on client.client=head.client
              left join cntnum on cntnum.trno=head.trno
              where head.doc in ('rr') $headdate $filter $filter1
              group by cntnum.center, client.clientname, head.dateid, head.docno, head.yourref,head.trno,head.doc $detailaddqry
              order by name, dateid, docno;";
            }
            break;
        }
        break;
    }
    return $query;
  }

  private function displayHeaderTable_DETAILED($config)
  {
    $str = "";
    $companyid = $config['params']['companyid'];
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = "1px solid";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUPPLIER', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('PO #', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->col('0-30 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('120+ days', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function displayHeader_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];

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
    $str .= $this->reporter->col('DETAILED CURRENT SUPPLIER PAYABLES AGING', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($clientname == '') {
      $str .= $this->reporter->col('Supplier : ALL', '110', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    } else {
      $str .= $this->reporter->col('Supplier : ' . strtoupper($clientname), '300', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    }

    $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == 0) {
      $posttype = 'Posted';
    } else if ($posttype == 1) {
      $posttype = 'Unposted';
    } else {
      $posttype = 'All';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '110', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', 'b', '');
    $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($filtercenter == '') {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Center : ' . $filtercenter, '110', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

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

    $this->reporter->linecounter = 0;
    $count = 50;
    $page = 50;
    $companylist = [36]; //rozlab

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_DETAILED($config);
    $str .= $this->displayHeaderTable_DETAILED($config);

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;

    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $gt = 0;

    $customer = "";
    // $docno = "";
    $subtotal = 0;

    $suba = 0;
    $subb = 0;
    $subc = 0;
    $subd = 0;
    $sube = 0;

    foreach ($result as $key => $data) {
      if ($customer != $data->clientname) {
        if ($customer != '') {
          $this->reporter->addline();
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($companyid == 11) { //summit
            $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('SUB TOTAL : ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($suba, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subc, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subd, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($sube, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
          } else {
            if ($companyid == 8) { //maxipro
              $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            }
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
          }

          $str .= $this->reporter->endrow();

          $str .= '<br/>';

          if (!in_array($companyid, $companylist)) {
            if ($this->reporter->linecounter == $page) {
              $str .= $this->reporter->endtable();
              $str .= $this->reporter->page_break();
              $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
              if (!$isfirstpageheader) $str .= $this->displayHeader_DETAILED($config);
              $str .= $this->displayHeaderTable_DETAILED($config);
              $page = $page + $count;
            }
          }
        }

        $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, $layoutsize, null, false, '1px dotted ', '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $subtotal = 0;
        $suba = 0;
        $subb = 0;
        $subc = 0;
        $subd = 0;
        $sube = 0;

        if (!in_array($companyid, $companylist)) {
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
            if (!$isfirstpageheader) $str .= $this->displayHeader_DETAILED($config);
            $str .= $this->displayHeaderTable_DETAILED($config);
            $page = $page + $count;
          }
        }
      }

      $a = 0;
      $b = 0;
      $c = 0;
      $d = 0;
      $e = 0;

      if ($data->elapse <= 30) {
        $a = $data->balance;
      }

      if ($data->elapse >= 31 && $data->elapse <= 60) {
        $b = $data->balance;
      }
      if ($data->elapse >= 61 && $data->elapse <= 90) {
        $c = $data->balance;
      }
      if ($data->elapse >= 91 && $data->elapse <= 120) {
        $d = $data->balance;
      }
      if ($data->elapse > 120) {
        $e = $data->balance;
      }

      $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col('&nbsp', '100', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        $str .= $this->reporter->col($data->ponum, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('&nbsp', '120', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
        if ($companyid == 11) { //summit
          if ($data->doc == 'AP') {
            if ($data->reference == "") {
              $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            } else {
              $str .= $this->reporter->col($data->reference, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
            }
          } else {
            $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          }
        } else {
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        }


        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      }

      $str .= $this->reporter->col(($a > 0 ? number_format($a, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($b > 0 ? number_format($b, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($c > 0 ? number_format($c, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($d > 0 ? number_format($d, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($e > 0 ? number_format($e, 2) : '-'), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->balance, 2), '100', null, false, $border, '', 'r', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();

      $tota += $a;

      $totb += $b;
      $totc += $c;
      $totd += $d;
      $tote += $e;
      $subtotal += $data->balance;
      $gt += $data->balance;
      $customer = $data->clientname;

      $suba += $a;
      $subb += $b;
      $subc += $c;
      $subd += $d;
      $sube += $e;

      if (!in_array($companyid, $companylist)) {
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->displayHeader_DETAILED($config);
          $str .= $this->displayHeaderTable_DETAILED($config);
          $page = $page + $count;
        }
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 11) { //summit
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($suba, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($subb, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($subc, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($subd, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($sube, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    } else {
      if ($companyid == 8) { //maxipro
        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('SUB TOTAL:', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, '1px dotted ', 'T', 'R', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 8) { //maxipro
      $str .= $this->reporter->col('', '200', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->col('', '100', null, false, '1px dotted ', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL : ', '100', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gt, 2), '100', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function displayHeaderTable_SUMMARIZED($config)
  {
    $str = "";
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CLIENT NAME', '110px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('0-30 days', '110px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('31-60 days', '110px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('61-90 days', '110px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('91-120 days', '110px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('120+ days', '110px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '110px', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function displayHeader_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $filtercenter = $config['params']['dataparams']['center'];
    // $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    // $reporttype   = $config['params']['dataparams']['reporttype'];

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
    $str .= $this->reporter->col('CURRENT SUPPLIER PAYABLES AGING - SUMMARY', null, null, false, $border, '', '', $font, '18', 'B', '', '');
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

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($filtercenter == '') {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Center : ' . $filtercenter, '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', 'b', '');
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->col('Department : ' . $deptname, '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '110px', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
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
    $companyid = $config['params']['companyid'];
    $companylist = [36]; //rozlab
    $this->reporter->linecounter = 0;
    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader_SUMMARIZED($config);
    $str .= $this->displayHeaderTable_SUMMARIZED($config);

    $a = 0;
    $b = 0;
    $c = 0;
    $d = 0;
    $e = 0;

    $tota = 0;
    $totb = 0;
    $totc = 0;
    $totd = 0;
    $tote = 0;
    $gt = 0;

    $clientname = "";
    $subtota = 0;
    $subtotb = 0;
    $subtotc = 0;
    $subtotd = 0;
    $subtote = 0;
    $subgt = 0;
    $counter = 0;
    foreach ($result as $key => $data) {
      $counter++;
      if ($clientname == "") {
        $clientname = $data->clientname;

        if ($data->elapse <= 30) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $a = $data->balance;
          $subtota = $subtota + $a;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 31 && $data->elapse <= 60) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $b = $data->balance;
          $subtotb = $subtotb + $b;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 61 && $data->elapse <= 90) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $c = $data->balance;
          $subtotc = $subtotc + $c;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse >= 91 && $data->elapse <= 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $d = $data->balance;
          $subtotd = $subtotd + $d;
          $subgt = $subgt + $data->balance;
        }
        if ($data->elapse > 120) {
          $a = 0;
          $b = 0;
          $c = 0;
          $d = 0;
          $e = 0;
          $e = $data->balance;
          $subtote = $subtote + $e;
          $subgt = $subgt + $data->balance;
        }
      } else {
        if ($clientname != $data->clientname) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col($clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtota > 0 ? number_format($subtota, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotb > 0 ? number_format($subtotb, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotc > 0 ? number_format($subtotc, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotd > 0 ? number_format($subtotd, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtote > 0 ? number_format($subtote, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($subgt, 2), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $subtota = 0;
          $subtotb = 0;
          $subtotc = 0;
          $subtotd = 0;
          $subtote = 0;
          $subgt = 0;

          $clientname = $data->clientname;

          if ($data->elapse <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data->balance;
            $subtota = $subtota + $a;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 31 && $data->elapse <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data->balance;
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 61 && $data->elapse <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data->balance;
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 91 && $data->elapse <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data->balance;
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse > 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data->balance;
            $subtote = $subtote + $e;
            $subgt = $subgt + $data->balance;
          }
        } else {

          if ($data->elapse <= 30) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $a = $data->balance;
            $subtota = $subtota + $a;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 31 && $data->elapse <= 60) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $b = $data->balance;
            $subtotb = $subtotb + $b;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 61 && $data->elapse <= 90) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $c = $data->balance;
            $subtotc = $subtotc + $c;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 91 && $data->elapse <= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $d = $data->balance;
            $subtotd = $subtotd + $d;
            $subgt = $subgt + $data->balance;
          }
          if ($data->elapse >= 120) {
            $a = 0;
            $b = 0;
            $c = 0;
            $d = 0;
            $e = 0;
            $e = $data->balance;
            $subtote = $subtote + $e;
            $subgt = $subgt + $data->balance;
          }
        }
      }
      $tota = $tota + $a;

      $totb = $totb + $b;
      $totc = $totc + $c;
      $totd = $totd + $d;
      $tote = $tote + $e;
      $gt = $gt + $data->balance;


      if (!in_array($companyid, $companylist)) {
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->displayHeader_SUMMARIZED($config);
          $str .= $this->displayHeaderTable_SUMMARIZED($config);
          $page = $page + $count;
        }
      }

      if ($counter == count($result)) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($clientname, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(($subtota > 0 ? number_format($subtota, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(($subtotb > 0 ? number_format($subtotb, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(($subtotc > 0 ? number_format($subtotc, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(($subtotd > 0 ? number_format($subtotd, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(($subtote > 0 ? number_format($subtote, 2) : '-'), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($subgt, 2), '110', null, false, $border, '', 'r', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subtota = 0;
        $subtotb = 0;
        $subtotc = 0;
        $subtotd = 0;
        $subtote = 0;
        $subgt = 0;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL : ', '110', null, false, '1px dotted', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tota, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totb, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totc, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totd, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($tote, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($gt, 2), '110', null, false, '1px dotted ', 'T', 'r', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}
