<?php

namespace App\Http\Classes\modules\reportlist\construction_reports;

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
use Illuminate\Support\Facades\URL;

class job_order_reports
{
  public $modulename = 'Job Order Reports';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];



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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dprojectname', 'subprojectname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'subprojectname.required', false);
    data_set($col1, 'subprojectname.readonly', false);
    data_set($col1, 'dprojectname.lookupclass', 'projectcode');

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
    return $this->coreFunctions->opentable("select 
    'default' as print,
     adddate(left(now(),10),-360) as start,
     left(now(),10) as end,
      '' as client,
      '' as clientname,
      '' as dclientname,
     '0' as posttype,
     '0' as reporttype, 
     '' as dprojectname, 
     '' as projectname, 
     '' as projectcode,
     '' as subprojectname
    ");
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0: // summarized
        $result = $this->default_layout($config);
        break;
      case 1: //detailed
        $result = $this->detailed_layout($config);
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->default_query($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_query($config)
  {
    ini_set('max_execution_time', 0);
    ini_set('memory_limit', '-1');
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $project = $config['params']['dataparams']['dprojectname'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    $client     = $config['params']['dataparams']['client'];

    if ($project != "") {
      $projectid = $config['params']['dataparams']['projectid'];
    }
    if ($subprojectname != "") {
      $subproject = $config['params']['dataparams']['subproject'];
    }

    $reporttype = $config['params']['dataparams']['reporttype'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";

    if ($client != "") {
      $filter .= " and supp.client = '$client' ";
    }

    if ($project != "") {
      $filter .= " and head.projectid = '" . $projectid . "' ";
    }
    if ($subprojectname != "") {
      $filter .= " and head.subproject = '" . $subproject . "' ";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "select head.docno as jonum, supp.clientid,supp.clientname as subcon, '' as jor,
                            date(head.dateid) as dateid, head.workloc, head.workdesc,
                            item.itemid,item.itemname, stock.qty, stock.uom, stock.rrcost, stock.ext,head.rem, 
                            stock.qa,(case when stock.void <> 0 then (stock.rrqty-stock.qa) else 0 end ) as voidqty,
                      ifnull((select group_concat(distinct docno SEPARATOR '\r') from (
                                  select jc.docno,jcs.ref,jcs.itemid from jchead as jc
                                  left join jcstock as jcs on jcs.trno=jc.trno
                                  union all
                                  select jc.docno,jcs.ref,jcs.itemid from hjchead as jc
                                  left join hjcstock as jcs on jcs.trno=jc.trno
                                  )as a where ref =head.docno and itemid=stock.itemid),'') as jcdocno,
                      ifnull((select sum(ext) from (
                                  select jcs.ext,jcs.ref,jcs.itemid from jchead as jc
                                  left join jcstock as jcs on jcs.trno=jc.trno
                                  union all
                                  select jcs.ext,jcs.ref,jcs.itemid from hjchead as jc
                                  left join hjcstock as jcs on jcs.trno=jc.trno) as a
                                  where ref = head.docno and itemid=stock.itemid),0) as jcamt,
                      ifnull((select group_concat(distinct format(cr,2) separator '\r') as cr from (
                                  select jcd.cr,jcs.ref,jcs.itemid from jchead as jc
                                  left join jcstock as jcs on jcs.trno=jc.trno
                                  left join ladetail as jcd on jcd.trno= jc.trno
                                  where jc.doc='JC' and jcd.acnoid = 5188
                                  union all
                                  select jcd.cr,jcs.ref,jcs.itemid
                                  from hjchead as jc
                                  left join hjcstock as jcs on jcs.trno=jc.trno
                                  left join gldetail as jcd on jcd.trno=jc.trno
                                  where jc.doc='JC' and jcd.acnoid = 5188) as a
                                  where ref = head.docno and itemid=stock.itemid),'') as retention
                      from hjohead as head
                      left join hjostock as stock on stock.trno = head.trno
                      left join client as supp on supp.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join transnum as num on num.trno = head.trno
                      left join projectmasterfile as prj on prj.line = head.projectid
                      left join subproject as sprj on sprj.line = head.subproject
                      where num.doc = 'JO' $filter
                      order by subcon, jonum";
            break;

          case 1: // unposted

            $query = "select head.docno as jonum, supp.clientid,supp.clientname as subcon, '' as jor,
                            date(head.dateid) as dateid, head.workloc, head.workdesc,
                            item.itemid,item.itemname, stock.qty, stock.uom, stock.rrcost, stock.ext,
                            head.rem, stock.qa,
                            (case when stock.void <> 0 then (stock.rrqty-stock.qa) else 0 end ) as voidqty,
                            '' as jcdocno, 0 as jcamt,'' as retention
                      from johead as head
                      left join jostock as stock on stock.trno = head.trno
                      left join client as supp on supp.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join transnum as num on num.trno = head.trno
                      left join projectmasterfile as prj on prj.line = head.projectid
                      left join subproject as sprj on sprj.line = head.subproject
                      where num.doc = 'JO' $filter
                      order by subcon, jonum";
            break;

          default: // all

            $query = "select head.docno as jonum, supp.clientid,supp.clientname as subcon, '' as jor,
                            date(head.dateid) as dateid, head.workloc, head.workdesc,
                            item.itemid,item.itemname, stock.qty, stock.uom, stock.rrcost, stock.ext,head.rem, stock.qa,
                            (case when stock.void <> 0 then (stock.rrqty-stock.qa) else 0 end ) as voidqty,
                             '' as jcdocno, 0 as jcamt,'' as retention
                      from johead as head
                      left join jostock as stock on stock.trno = head.trno
                      left join client as supp on supp.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join transnum as num on num.trno = head.trno
                      left join projectmasterfile as prj on prj.line = head.projectid
                      left join subproject as sprj on sprj.line = head.subproject
                      where num.doc = 'JO' $filter
                      union all 
                      select head.docno as jonum, supp.clientid,supp.clientname as subcon, '' as jor,
                            date(head.dateid) as dateid, head.workloc, head.workdesc,
                            item.itemid,item.itemname, stock.qty, stock.uom, stock.rrcost, stock.ext,head.rem, stock.qa,
                            (case when stock.void <> 0 then (stock.rrqty-stock.qa) else 0 end ) as voidqty,
                      ifnull((select group_concat(distinct docno SEPARATOR '\r') from (
                                  select jc.docno,jcs.ref,jcs.itemid from jchead as jc
                                  left join jcstock as jcs on jcs.trno=jc.trno
                                  union all
                                  select jc.docno,jcs.ref,jcs.itemid from hjchead as jc
                                  left join hjcstock as jcs on jcs.trno=jc.trno
                                  )as a where ref =head.docno and itemid=stock.itemid),'') as jcdocno,
                      ifnull((select sum(ext) from (select jcs.ext,jcs.ref,jcs.itemid from jchead as jc
                                  left join jcstock as jcs on jcs.trno=jc.trno
                                  union all
                                  select jcs.ext,jcs.ref,jcs.itemid from hjchead as jc
                                  left join hjcstock as jcs on jcs.trno=jc.trno) as a
                                  where ref = head.docno and itemid=stock.itemid),0) as jcamt,
                      ifnull((select group_concat(distinct format(cr,2) separator '\r') as cr from (
                                  select jcd.cr,jcs.ref,jcs.itemid
                                  from jchead as jc
                                  left join jcstock as jcs on jcs.trno=jc.trno
                                  left join ladetail as jcd on jcd.trno= jc.trno
                                  where jc.doc='JC' and jcd.acnoid = 5188
                                  union all
                                  select jcd.cr,jcs.ref,jcs.itemid
                                  from hjchead as jc
                                  left join hjcstock as jcs on jcs.trno=jc.trno
                                  left join gldetail as jcd on jcd.trno=jc.trno
                                  where jc.doc='JC' and jcd.acnoid = 5188) as a
                                  where ref = head.docno and itemid=stock.itemid),'') as retention
                      from hjohead as head
                      left join hjostock as stock on stock.trno = head.trno
                      left join client as supp on supp.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join transnum as num on num.trno = head.trno
                      left join projectmasterfile as prj on prj.line = head.projectid
                      left join subproject as sprj on sprj.line = head.subproject
                      where num.doc = 'JO' $filter
                      order by subcon, jonum";

            break;
        } // end switch posttype
        break;
      case 1: //detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select trno,jodate,jodocno,transtype,subcon,jrdate,jrdocno,barcode,itemname,
                                   workdesc,joqty,joamt,jototalamt,jcdate,jcdocno,jcqty,jcamt,jctotalamt,
                                   retention,jostat,projcode,projname,subproject ,void,voidqty
                            from (select head.trno,left(head.dateid,10) as jodate,head.docno as jodocno,
                                        'JOB ORDER' as transtype,head.clientname as subcon,left(jr.dateid,10) as jrdate,head.yourref as jrdocno,item.barcode,itemname,
                                        head.workdesc,stock.rrqty as joqty,stock.rrcost as joamt,stock.ext as jototalamt,
                                        (select group_concat(distinct dateid separator '\r') 
                                        from (select left(jc.dateid,10) as dateid,jcs.ref,jcs.itemid
                                              from jchead as jc 
                                              left join jcstock as jcs on jcs.trno=jc.trno
                                              union all 
                                              select left(jc.dateid,10) as dateid,jcs.ref,jcs.itemid 
                                              from hjchead as jc 
                                              left join hjcstock as jcs on jcs.trno=jc.trno )as a where ref =head.docno and itemid=stock.itemid ) as jcdate,
                                        (select group_concat(distinct docno SEPARATOR '\r') 
                                        from (select jc.docno,jcs.ref,jcs.itemid 
                                              from jchead as jc 
                                              left join jcstock as jcs on jcs.trno=jc.trno 
                                              union all 
                                              select jc.docno,jcs.ref,jcs.itemid 
                                              from hjchead as jc 
                                              left join hjcstock as jcs on jcs.trno=jc.trno ) as a 
                                        where ref =head.docno and itemid=stock.itemid ) as jcdocno,
                                        (select sum(rrqty)
                                        from (select jcs.rrqty,jcs.ref,jcs.itemid from jchead as jc left join jcstock as jcs on jcs.trno=jc.trno
                                              union all
                                              select jcs.rrqty,jcs.ref,jcs.itemid from hjchead as jc left join hjcstock as jcs on jcs.trno=jc.trno ) as a 
                                        where itemid=stock.itemid and ref=head.docno) as jcqty,
                                        (select group_concat(distinct format(rrcost,2) separator ',') as rrcost 
                                        from (select jcs.rrcost,jcs.ref,jcs.itemid 
                                              from jchead as jc 
                                              left join jcstock as jcs on jcs.trno=jc.trno 
                                              union all 
                                              select jcs.rrcost,jcs.ref,jcs.itemid 
                                              from hjchead as jc 
                                              left join hjcstock as jcs on jcs.trno=jc.trno ) as a where ref = head.docno and itemid=stock.itemid) as jcamt,
                                              (select sum(ext)
                                        from (select jcs.ext,jcs.ref,jcs.itemid from jchead as jc left join jcstock as jcs on jcs.trno=jc.trno
                                              union all
                                              select jcs.ext,jcs.ref,jcs.itemid from hjchead as jc left join hjcstock as jcs on jcs.trno=jc.trno ) as a where itemid=stock.itemid and ref=head.docno) as jctotalamt,
                                        (select group_concat(distinct format(cr,2) separator '\r') as cr
                                        from (select jcd.cr,jcs.ref,jcs.itemid
                                              from jchead as jc
                                              left join jcstock as jcs on jcs.trno=jc.trno
                                              left join ladetail as jcd on jcd.trno= jc.trno
                                              where jc.doc='JC' and jcd.acnoid = 5188
                                              union all
                                              select jcd.cr,jcs.ref,jcs.itemid
                                              from hjchead as jc
                                              left join hjcstock as jcs on jcs.trno=jc.trno
                                              left join gldetail as jcd on jcd.trno=jc.trno
                                              where jc.doc='JC' and jcd.acnoid = 5188) as a
                                        where ref = head.docno and itemid=stock.itemid) as retention,
                                        stat.status as jostat, proj.code as projcode,
                                        proj.name as projname, subproj.subproject,
                                  case when stock.void=0 then 'False' else 'True' end as void,
                                  ifnull((select sum(rrqty-qa) from (
                            select s.rrqty,s.qa,h.trno,s.itemid from hjohead as h left join hjostock as s on s.trno=h.trno
                            where h.doc='JO' and s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                                  from hjostock as stock
                                  left join hjohead as head on head.trno=stock.trno
                                  left join client as supp on supp.client=head.client
                                  left join hprhead as jr on jr.docno=head.yourref
                                  left join item on item.itemid =stock.itemid
                                  left join transnum as num on num.trno=head.trno
                                  left join trxstatus as stat on stat.line=num.statid
                                  left join projectmasterfile as proj on proj.line=head.projectid
                                  left join subproject as subproj on subproj.line = head.subproject and subproj.projectid=proj.line
                                  where head.doc='JO' and head.dateid between '$start' and '$end' $filter) as k
                      order by jodocno,subcon,projname,subproject";
            break;
          case 1: // unposted
            $query = "select head.trno,left(head.dateid,10) as jodate,head.docno as jodocno,
                                  'JOB ORDER' as transtype,head.clientname as subcon,left(jr.dateid,10) as jrdate,
                                  head.yourref as jrdocno,item.barcode,item.itemname,head.workdesc,stock.rrqty as joqty,stock.rrcost as joamt,stock.ext as jototalamt, '' as jcdate, '' as jcdocno, 0 as jcqty,0 as jcamt, 0 as jctotalamt, 
                                  0 as retention, 'Pending' as jostat,  
                                  proj.code as projcode,proj.name as projname, subproj.subproject,
                                  case when stock.void=0 then 'False' else 'True' end as void,
                                  ifnull((select sum(rrqty-qa) from (
                            select s.rrqty,s.qa,h.trno,s.itemid from johead as h left join jostock as s on s.trno=h.trno
                            where h.doc='JO' and s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                            from jostock as stock
                            left join johead as head on head.trno=stock.trno
                            left join client as supp on supp.client=head.client
                            left join hprhead as jr on jr.docno=head.yourref
                            left join item on item.itemid =stock.itemid
                            left join transnum as num on num.trno=head.trno
                            left join trxstatus as stat on stat.line=num.statid
                            left join projectmasterfile as proj on proj.line=head.projectid
                            left join subproject as subproj on subproj.line = head.subproject and subproj.projectid=proj.line
                            where head.doc='JO' and head.dateid between '$start' and '$end' $filter
                      order by jodocno,subcon,projname,subproject";
            break;

          default: // all
            $query = "select trno,jodate,jodocno,transtype,subcon,jrdate,jrdocno,barcode,itemname,workdesc,joqty,joamt,jototalamt,
                             jcdate,jcdocno,jcqty,jcamt,jctotalamt,retention,jostat,projcode,projname, subproject,void,voidqty
                      from (select head.trno,left(head.dateid,10) as jodate,head.docno as jodocno,
                                  'JOB ORDER' as transtype,head.clientname as subcon,left(jr.dateid,10) as jrdate,
                                  head.yourref as jrdocno,item.barcode,item.itemname,head.workdesc,stock.rrqty as joqty,stock.rrcost as joamt, stock.ext as jototalamt,'' as jcdate, '' as jcdocno, 0 as jcqty,0 as jcamt,0 as jctotalamt, 
                                  '' as retention, 'Pending' as jostat, 
                                  proj.code as projcode,proj.name as projname, subproj.subproject,
                                  case when stock.void=0 then 'False' else 'True' end as void,
                                  ifnull((select sum(rrqty) from (
                            select s.rrqty,h.trno,s.itemid from johead as h left join jostock as s on s.trno=h.trno
                            where h.doc='JO' and s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                            from jostock as stock
                            left join johead as head on head.trno=stock.trno
                            left join client as supp on supp.client=head.client
                            left join hprhead as jr on jr.docno=head.yourref
                            left join item on item.itemid =stock.itemid
                            left join transnum as num on num.trno=head.trno
                            left join trxstatus as stat on stat.line=num.statid
                            left join projectmasterfile as proj on proj.line=head.projectid
                            left join subproject as subproj on subproj.line = head.subproject and subproj.projectid=proj.line
                            where head.doc='JO' and head.dateid between '$start' and '$end' $filter
                            union all
                            select trno,jodate,jodocno,transtype,subcon,jrdate,jrdocno,barcode,itemname,
                                   workdesc,joqty,joamt,jototalamt,jcdate,jcdocno,jcqty,jcamt,jctotalamt,retention,jostat,projcode,projname,subproject,void,voidqty
                            from (select head.trno,left(head.dateid,10) as jodate,head.docno as jodocno,
                                        'JOB ORDER' as transtype,head.clientname as subcon,left(jr.dateid,10) as jrdate,head.yourref as jrdocno,item.barcode,itemname,
                                        head.workdesc,stock.rrqty as joqty,stock.rrcost as joamt,stock.ext as jototalamt,
                                        (select group_concat(distinct dateid separator '\r') 
                                        from (select left(jc.dateid,10) as dateid,jcs.ref,jcs.itemid
                                              from jchead as jc 
                                              left join jcstock as jcs on jcs.trno=jc.trno
                                              union all 
                                              select left(jc.dateid,10) as dateid,jcs.ref,jcs.itemid
                                              from hjchead as jc 
                                              left join hjcstock as jcs on jcs.trno=jc.trno)as a where ref =head.docno and itemid=stock.itemid) as jcdate,
                                        (select group_concat(distinct docno SEPARATOR '\r') 
                                        from (select jc.docno,jcs.ref,jcs.itemid
                                              from jchead as jc 
                                              left join jcstock as jcs on jcs.trno=jc.trno 
                                              union all 
                                              select jc.docno,jcs.ref,jcs.itemid 
                                              from hjchead as jc 
                                              left join hjcstock as jcs on jcs.trno=jc.trno) as a 
                                        where ref =head.docno and itemid=stock.itemid) as jcdocno,
                                        (select sum(rrqty)
                                        from (select jcs.rrqty,jcs.ref,jcs.itemid from jchead as jc left join jcstock as jcs on jcs.trno=jc.trno
                                              union all
                                              select jcs.rrqty,jcs.ref,jcs.itemid from hjchead as jc left join hjcstock as jcs on jcs.trno=jc.trno ) as a where itemid=stock.itemid and ref=head.docno) as jcqty,
                                        (select group_concat(distinct format(rrcost,2) separator ',') as rrcost 
                                        from (select jcs.rrcost,jcs.ref,jcs.itemid 
                                              from jchead as jc 
                                              left join jcstock as jcs on jcs.trno=jc.trno 
                                              union all 
                                              select jcs.rrcost,jcs.ref,jcs.itemid 
                                              from hjchead as jc 
                                              left join hjcstock as jcs on jcs.trno=jc.trno ) as a where ref = head.docno and itemid=stock.itemid) as jcamt,
                                        (select sum(ext)
                                        from (select jcs.ext,jcs.ref,jcs.itemid from jchead as jc left join jcstock as jcs on jcs.trno=jc.trno
                                              union all
                                              select jcs.ext,jcs.ref,jcs.itemid from hjchead as jc left join hjcstock as jcs on jcs.trno=jc.trno ) as a where itemid=stock.itemid and ref=head.docno) as jctotalamt,
                                        (select group_concat(distinct format(cr,2) separator '\r') as cr
                                        from (select jcd.cr,jcs.ref,jcs.itemid
                                              from jchead as jc
                                              left join jcstock as jcs on jcs.trno=jc.trno
                                              left join ladetail as jcd on jcd.trno= jc.trno
                                              where jc.doc='JC' and jcd.acnoid = 5188
                                              union all
                                              select jcd.cr,jcs.ref,jcs.itemid
                                              from hjchead as jc
                                              left join hjcstock as jcs on jcs.trno=jc.trno
                                              left join gldetail as jcd on jcd.trno=jc.trno
                                              where jc.doc='JC' and jcd.acnoid = 5188) as a
                                        where ref = head.docno and itemid=stock.itemid) as retention,
                                        stat.status as jostat, proj.code as projcode,
                                        proj.name as projname, subproj.subproject,
                                        case when stock.void=0 then 'False' else 'True' end as void,
                                        ifnull((select sum(rrqty-qa) from (
                            select s.rrqty,s.qa,h.trno,s.itemid from hjohead as h left join hjostock as s on s.trno=h.trno
                            where h.doc='JO' and s.void <> 0) as a where a.trno=head.trno and itemid=stock.itemid),0) as voidqty
                                  from hjostock as stock
                                  left join hjohead as head on head.trno=stock.trno
                                  left join client as supp on supp.client=head.client
                                  left join hprhead as jr on jr.docno=head.yourref
                                  left join item on item.itemid =stock.itemid
                                  left join transnum as num on num.trno=head.trno
                                  left join trxstatus as stat on stat.line=num.statid
                                  left join projectmasterfile as proj on proj.line=head.projectid
                                  left join subproject as subproj on subproj.line = head.subproject and subproj.projectid=proj.line
                                  where head.doc='JO' and head.dateid between '$start' and '$end' $filter) as k ) as a
                                  order by jodate,subcon,projname,subproject";
            break;
        } // end switch posttype
        break;
    }

    $this->coreFunctions->LogConsole($query);
    return $query;
  }

  private function generateReportHeader($center, $username)
  {
    $qry = "select name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    return $str;
  } //end function generate report header

  public function default_header($config)
  {
    $mdc = URL::to('/images/reports/mdc.jpg');
    $tuv = URL::to('/images/reports/tuv.jpg');

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $projectname = $config['params']['dataparams']['dprojectname'];
    $subproject  = $config['params']['dataparams']['subprojectname'];

    $str = "";
    $layoutsize = '1850';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($projectname)) {
      $projectname = 'ALL';
    } else {
      $projectname = $config['params']['dataparams']['dprojectname'];
    }

    if (empty($subproject)) {
      $subproject = 'ALL';
    } else {
      $subproject = $config['params']['dataparams']['subprojectname'];
    }

    $str .= "<div style='position: relative;'>";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->generateReportHeader($center, $username);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<div style='position:absolute; top: 60px;'>";
    $str .= $this->reporter->col('<img src ="' . $mdc . '" alt="MDC" width="140px" height ="70px">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
    $str .= $this->reporter->col('<img src ="' . $tuv . '" alt="TUV" width="140px" height ="70px" style="margin-left: 1900px;">', '10', null, false, '2px solid ', '', 'R', 'Century Gothic', '15', 'B', '', '');
    $str .= "</div>";

    $str .= "</div>";

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>PROJECT NAME</b>: ' . $projectname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<b>SUB PROJECT</b>: ' . $subproject, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= "<br>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUBCON NAME', '150', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('JO ORDER#', '120', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('WORK LOCATION', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('WORK DESCRIPTION', '230', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('QTY', '80', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('UNIT', '80', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('UNIT PRICE', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('VOID QTY', '70', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('VOID AMOUNT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('JOB COMPLETE', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('AMOUNT BILLED', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('AMOUNT PAYMENT', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('JO BALANCE', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('JC DOCNO', '120', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('RETENTION', '100', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function default_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid  = $config['params']['companyid'];
    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $count = 38;
    $page = 38;

    $str = '';
    $layoutsize = '1850';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $borderdotted = "1px dotted ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header($config);

    $docno = "";
    $clientname = "";

    $voidamt = 0;
    $jobal = 0;

    $qty_subtotal = 0;
    $totalext = 0;
    $totalvoidqty = 0;
    $totalvoidamt = 0;
    $totaljcamt = 0;
    $totaljobal = 0;

    $totalext = 0;
    $totalvoidqty = 0;
    $totalvoidamt = 0;
    $totaljcamt = 0;
    $totaljobal = 0;
    $totalamtpaid = 0;

    $gtotalqty = 0;
    $gtotalext = 0;
    $gtotalvoidqty = 0;
    $gtotalvoidamt = 0;
    $gtotaljcamt = 0;
    $gtotaljobal = 0;
    $gtotalamtpaid = 0;

    $project = $config['params']['dataparams']['dprojectname'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];
    foreach ($result as $key => $data) {
      $amt_billed = $data->qa * $data->rrcost;
      $balance = $data->ext - $amt_billed;

      if ($data->voidqty != 0) {
        $voidamt = $data->voidqty * $data->rrcost;
        $jobal = $balance - $voidamt;
      } else {
        $jobal = $balance;
      }


      if ($data->ext > 0) {
        $percentage = ((($data->qa + $data->voidqty) * $data->rrcost)  / $data->ext) * 100;
      } else {
        $percentage = 0;
      }

      if ($project != "") {
        $projectid = $config['params']['dataparams']['projectid'];
      }
      if ($subprojectname != "") {
        $subproject = $config['params']['dataparams']['subproject'];
      }

      $filter = '';
      if ($project != "") {
        $filter .= " and apv.projectid = '" . $projectid . "' ";
      }
      if ($subprojectname != "") {
        $filter .= " and jc.subproject = '" . $subproject . "' ";
      }

      $cvdb = "select sum(db) as db from (
                  select cv.trno,cv.docno as cvdocno,apv.docno as apvdocno,ifnull(cvd.db,0) as db,jc.docno as jcdocno,jcs.ref, jo.docno as jodocno
                  from lahead as cv
                  left join ladetail as cvd on cvd.trno=cv.trno
                  left join glhead as apv on apv.trno=cvd.refx
                  left join gldetail as apvd on apvd.trno=apv.trno
                  left join hjchead as jc on jc.docno = apvd.ref
                  left join hjcstock as jcs on jcs.trno=jc.trno
                  left join hjohead as jo on jo.docno = jcs.ref
                  where cv.doc='CV' and apv.doc='PV' and jc.doc='JC' and jcs.ref ='$data->jonum' $filter
                  group by jodocno,jcdocno,apvdocno,cvdocno,trno,db,ref
                  union all
                  select cv.trno,cv.docno as cvdocno,apv.docno as apvdocno,ifnull(cvd.db,0) as db,jc.docno as jcdocno,jcs.ref, jo.docno as jodocno
                  from glhead as cv
                  left join gldetail as cvd on cvd.trno=cv.trno
                  left join glhead as apv on apv.trno=cvd.refx
                  left join gldetail as apvd on apvd.trno=apv.trno
                  left join hjchead as jc on jc.docno = apvd.ref
                  left join hjcstock as jcs on jcs.trno=jc.trno
                  left join hjohead as jo on jo.docno = jcs.ref
                  where cv.doc='CV' and apv.doc='PV' and jc.doc='JC' and jcs.ref ='$data->jonum' $filter
                  group by jodocno,jcdocno,apvdocno,cvdocno,trno,db,ref) as a";
      $cvdbresult = $this->coreFunctions->opentable($cvdb);

      $str .= $this->reporter->addline();
      if ($docno != $data->jonum) {

        $gtotalqty += $qty_subtotal;
        $gtotalext += $totalext;
        $gtotalvoidqty += $totalvoidqty;
        $gtotalvoidamt += $totalvoidamt;
        $gtotaljcamt += $totaljcamt;
        $gtotaljobal += $totaljobal;
        $gtotalamtpaid += $totalamtpaid;

        // SUBTOTAL
        if ($docno != "") {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col("", '150', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col("", '120', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col("SUBTOTAL", '230', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '4px');
          $str .= $this->reporter->col(number_format($qty_subtotal, $decimal), '80', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '80', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalvoidqty, $decimal), '70', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalvoidamt, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totaljcamt, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalamtpaid, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totaljobal, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '120', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $qty_subtotal = 0;
          $totalext = 0;
          $totalvoidqty = 0;
          $totalvoidamt = 0;
          $totaljcamt = 0;
          $totaljobal = 0;
          $totalamtpaid = 0;
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($clientname != $data->subcon) {
          $str .= $this->reporter->col("", '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col("", '120', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '230', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '70', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '120', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();


        foreach ($cvdbresult as $key => $cv) {
          if ($clientname != $data->subcon) {
            $str .= $this->reporter->col($data->subcon, '150', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
          } else {
            $str .= $this->reporter->col("", '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          }
          $str .= $this->reporter->col($data->jonum, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->workloc, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->workdesc, '230', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '70', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col("", '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($cv->db, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '120', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');

          $totalamtpaid += $cv->db;
        }
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->endtable();
      }

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col("", '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col("", '120', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col("", '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col("", '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col($data->itemname, '230', null, false, $border, 'B', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->qty, $decimal), '80', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->uom, '80', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->rrcost, $decimal), '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->ext, $decimal), '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->voidqty == 0 ? '-' : number_format($data->voidqty, $decimal), '70', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($voidamt == 0 ? '-' : number_format($voidamt, $decimal), '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($percentage, 2) . "%", '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->jcamt, $decimal), '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($jobal == 0 ? '-' : number_format($jobal, $decimal), '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->jcdocno, '120', null, false, $border, 'B', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(nl2br($data->retention), '100', null, false, $border, 'B', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $clientname = $data->subcon;
      $docno = $data->jonum;

      $qty_subtotal += $data->qty;
      $totalext += $data->ext;
      $totalvoidqty += $data->voidqty;
      $totalvoidamt += $voidamt;
      $totaljcamt += $data->jcamt;
      $totaljobal += $jobal;
    }


    //SUBTOTAL
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '150', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '120', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("SUBTOTAL", '230', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($qty_subtotal, $decimal), '80', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("", '80', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalvoidqty, $decimal), '70', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalvoidamt, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totaljcamt, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamtpaid, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totaljobal, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $gtotalqty += $qty_subtotal;
    $gtotalext += $totalext;
    $gtotalvoidqty += $totalvoidqty;
    $gtotalvoidamt += $totalvoidamt;
    $gtotaljcamt += $totaljcamt;
    $gtotaljobal += $totaljobal;
    $gtotalamtpaid += $totalamtpaid;

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("", '150', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '120', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("GRAND TOTAL", '230', null, false, $borderdotted, 'B', 'L', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col(number_format($gtotalqty, $decimal), '80', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("", '80', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtotalext, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtotalvoidqty, $decimal), '70', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtotalvoidamt, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col("", '100', null, false, $borderdotted, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtotaljcamt, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtotalamtpaid, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($gtotaljobal, $decimal), '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $borderdotted, 'B', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $qty_subtotal = 0;
    $totalext = 0;
    $totalvoidqty = 0;
    $totalvoidamt = 0;
    $totaljcamt = 0;
    $totaljobal = 0;
    $totalamtpaid = 0;


    $str .= $this->reporter->endreport();

    return $str;
  }

  public function detailed_header($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $client     = $config['params']['dataparams']['client'];
    $projectname = $config['params']['dataparams']['dprojectname'];
    $subproject  = $config['params']['dataparams']['subprojectname'];

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

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

    if (empty($projectname)) {
      $projectname = 'ALL';
    } else {
      $projectname = $config['params']['dataparams']['dprojectname'];
    }

    if (empty($subproject)) {
      $subproject = 'ALL';
    } else {
      $subproject = $config['params']['dataparams']['subprojectname'];
    }

    $str = '';
    $layoutsize = '2500';
    $font =  "Century Gothic";
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
    $str .= $this->reporter->col('Job Order Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project: ' . $projectname, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sub Project: ' . $subproject, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('JO Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JO Number', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Subcontractor', '190', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JR Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JR Number', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JO Works Description', '310', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Service Code', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Services', '250', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JO Total Qty', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Void Qty', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JC Date', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JC Number', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JC Total Qty', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('JO Balance Qty', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '4px');

    $str .= $this->reporter->col('Project', '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->col('Subproject', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function detailed_layout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];

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

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '2500';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $totaljoqty = 0;
    $totaljoamt = 0;
    $totalrrqty = 0;
    $totalrramt = 0;
    $totalcvamt = 0;

    $totaljobal = 0;
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->detailed_header($config);
    $docno = "";
    $i = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaljobal = ($data->joqty - $data->voidqty) - $data->jcqty;
        // var_dump('JOQTY' . ' - ' . $data->joqty);
        // var_dump('VOIDQTY' . ' - ' . $data->voidqty);
        // var_dump('JCQTY' . ' - ' . $data->jcqty);


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->jodate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->jodocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->subcon, '190', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->jrdate, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->jrdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->workdesc, '310', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->itemname, '250', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->joqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->voidqty == 0 ? '' : number_format($data->voidqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->jcdate, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->jcdocno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->jcqty, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($totaljobal == 0 ? '-' : number_format($totaljobal, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->projname, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->subproject, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->endtable();

        $i++;
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->detailed_header($config);
          $page = $page + $count;
        } //end if

      }
    }

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class