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

class sales_order_report_afti
{
  public $modulename = 'Sales Order Report';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved', 'project', 'ddeptname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'reportusers.lookupclass', 'user');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.required', false);
    data_set($col1, 'project.required', false);
    data_set($col1, 'ddeptname.label', 'Department');
    data_set($col1, 'project.label', 'Item Group');


    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];
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

    $center = $config['params']['center'];
    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '' as clientname,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as posttype,
    '0' as reporttype, 
    'ASC' as sorting,
    '' as center,'$center' as dcentername,
    '' as dclientname,'' as reportusers,
    '' as project, '' as projectid, '' as projectname,
    '' as ddeptname, '' as dept, '' as deptname,'0' as clientid
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
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0:
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;

      case 1:
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    $query = $this->default_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    $leftjoin = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      if ($reporttype == 0) {
        $filter .= " and indus.clientid = '$clientid' ";
      } else {
        $leftjoin .= " left join client as cl on cl.client=qthead.client ";
        $filter .= " and cl.clientid = '$clientid' ";
      }
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $prjid = $config['params']['dataparams']['project'];
    $deptid = $config['params']['dataparams']['ddeptname'];
    $project = $config['params']['dataparams']['projectid'];
    if ($deptid == "") {
      $dept = "";
    } else {
      $dept = $config['params']['dataparams']['deptid'];
    }
    if ($prjid != "") {
      $filter1 .= " and qsstock.projectid = $project";
    }
    if ($deptid != "") {
      $filter1 .= " and qthead.deptid = $dept";
    }

    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case '0'; // posted
            $query = "select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,
        sohead.delcharge,ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.deldate) as deldate
        from hsqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch 
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client $leftjoin
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.deldate

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.due) as deldate
        from hsshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch $leftjoin
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.due
        order by sodateid
        ";
            break;
          case '1': // unposted
            $query = "select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.deldate) as deldate
        from sqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client $leftjoin
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.deldate

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.due) as deldate
        from sshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client $leftjoin
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.due
        order by sodateid
        ";
            break;
          default: // all 
            $query = "
         select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.deldate) as deldate
        from hsqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client $leftjoin
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.deldate

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,
        sohead.delcharge,ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.due) as deldate
        from hsshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client $leftjoin
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.due

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.deldate) as deldate
        from sqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client $leftjoin
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.deldate

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,date(qthead.due) as deldate
        from sshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0
        union all select s.refx,h.docno,h.trno from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        left join client as indus on indus.client=qthead.client $leftjoin
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,qthead.due
        order by sodateid
        ";
            break;
        }
        break;

      case 1: // detailed

        switch ($posttype) {
          case 0: // posted
            $query = "select a.yourref,a.docno,a.dateid,a.client,a.clientname,a.barcode, a.itemname,a.uom,a.iss,a.isamt,
                            a.disc,a.ext,a.createby,a.rem, a.loc, a.qa, a.deptcode, a.deptname from(
                            select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                    item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                    head.createby,qsstock.rem,qsstock.loc,
                                    round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                    dept.client as deptcode, dept.clientname as deptname
                            from hsqhead as head
                            left join hqshead as qthead on qthead.sotrno=head.trno
                            left join hqsstock as qsstock on qsstock.trno=qthead.trno
                            left join item on item.itemid=qsstock.itemid
                            left join client as dept on dept.clientid = qthead.deptid
                            left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                            $leftjoin
                            where head.doc='SQ'  and date(head.dateid) between '$start' and '$end' $filter $filter1

                            union all

                            select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                    item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                    head.createby,qsstock.rem,qsstock.loc,
                                    round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                    dept.client as deptcode, dept.clientname as deptname
                            from hsshead as head
                            left join hsrhead as qthead on qthead.sotrno = head.trno
                            left join hsrstock as qsstock on qsstock.trno=qthead.trno
                            left join item on item.itemid=qsstock.itemid
                            left join client as dept on dept.clientid = qthead.deptid
                            left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                            $leftjoin
                            where head.doc='AO'  and date(head.dateid) between '$start' and '$end' $filter $filter1

                            ) as a
                    order by dateid, docno $sorting";
            break;
          case 1: // unposted
            $query = "select a.yourref,a.docno,a.dateid,a.client,a.clientname,a.barcode, a.itemname,a.uom,a.iss,a.isamt,
                        a.disc,a.ext,a.createby,a.rem, a.loc, a.qa, a.deptcode, a.deptname from(
                        select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                head.createby,qsstock.rem,qsstock.loc,
                                round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                dept.client as deptcode, dept.clientname as deptname
                        from sqhead as head
                        left join hqshead as qthead on qthead.sotrno=head.trno
                        left join hqsstock as qsstock on qsstock.trno=qthead.trno
                        left join item on item.itemid=qsstock.itemid
                        left join client as dept on dept.clientid = qthead.deptid
                        left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                         $leftjoin
                        where head.doc='SQ' and date(head.dateid) between '$start' and '$end' $filter $filter1

                        union all

                        select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                  item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                  head.createby,qsstock.rem,qsstock.loc,
                                  round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                  dept.client as deptcode, dept.clientname as deptname
                          from sshead as head
                          left join hsrhead as qthead on qthead.sotrno = head.trno
                          left join hsrstock as qsstock on qsstock.trno=qthead.trno
                          left join item on item.itemid=qsstock.itemid
                          left join client as dept on dept.clientid = qthead.deptid
                          left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                           $leftjoin
                          where head.doc='AO'  and date(head.dateid) between '$start' and '$end' $filter $filter1

                        ) as a
                order by dateid, docno $sorting";
            break;
          default: // sana all
            $query = "select a.yourref,a.docno,a.dateid,a.client,a.clientname,a.barcode, a.itemname,a.uom,a.iss,a.isamt,
                        a.disc,a.ext,a.createby,a.rem, a.loc, a.qa, a.deptcode, a.deptname from(
                        select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                head.createby,qsstock.rem,qsstock.loc,
                                round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                dept.client as deptcode, dept.clientname as deptname
                        from hsqhead as head
                        left join hqshead as qthead on qthead.sotrno=head.trno
                        left join hqsstock as qsstock on qsstock.trno=qthead.trno
                        left join item on item.itemid=qsstock.itemid
                        left join client as dept on dept.clientid = qthead.deptid
                        left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                         $leftjoin
                        where head.doc='SQ' and date(head.dateid) between '$start' and '$end' $filter $filter1
                      
                        union all

                        select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                head.createby,qsstock.rem,qsstock.loc,
                                round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                dept.client as deptcode, dept.clientname as deptname
                        from sqhead as head
                        left join hqshead as qthead on qthead.sotrno=head.trno
                        left join hqsstock as qsstock on qsstock.trno=qthead.trno
                        left join item on item.itemid=qsstock.itemid
                        left join client as dept on dept.clientid = qthead.deptid
                        left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                         $leftjoin
                        where head.doc='SQ' and date(head.dateid) between '$start' and '$end' $filter $filter1

                        union all

                        select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                head.createby,qsstock.rem,qsstock.loc,
                                round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                dept.client as deptcode, dept.clientname as deptname
                        from sshead as head
                        left join hsrhead as qthead on qthead.sotrno = head.trno
                        left join hsrstock as qsstock on qsstock.trno=qthead.trno
                        left join item on item.itemid=qsstock.itemid
                        left join client as dept on dept.clientid = qthead.deptid
                        left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                         $leftjoin
                        where head.doc='AO'  and date(head.dateid) between '$start' and '$end' $filter $filter1
                        
                        union all

                        select qthead.yourref, head.docno,left(head.dateid,10) as dateid,qthead.client,qthead.clientname,
                                item.barcode,item.itemname,qsstock.uom,qsstock.iss,qsstock.isamt,qsstock.disc,qsstock.ext,
                                head.createby,qsstock.rem,qsstock.loc,
                                round((qsstock.iss-qsstock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,
                                dept.client as deptcode, dept.clientname as deptname
                        from hsshead as head
                        left join hsrhead as qthead on qthead.sotrno = head.trno
                        left join hsrstock as qsstock on qsstock.trno=qthead.trno
                        left join item on item.itemid=qsstock.itemid
                        left join client as dept on dept.clientid = qthead.deptid
                        left join uom on uom.itemid=item.itemid and uom.uom=qsstock.uom
                         $leftjoin
                        where head.doc='AO'  and date(head.dateid) between '$start' and '$end' $filter $filter1) as a
                order by dateid, docno $sorting";
            break;
        }
        break;
    }
    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $dept   = $config['params']['dataparams']['ddeptname'];
    $proj   = $config['params']['dataparams']['project'];

    if ($dept != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    if ($proj != "") {
      $projname = $config['params']['dataparams']['projectname'];
    } else {
      $projname = "ALL";
    }

    if ($sorting == 'ASC') {
      $sorting = 'Ascending';
    } else {
      $sorting = 'Descending';
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

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }

    $str = '';
    $count = 38;
    $page = 40;

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if ($companyid == 3) { //conti
      $qry = "select name,address,tel from center where code = '" . $center . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .=  $this->reporter->col($username . '&nbsp' . date_format(date_create($current_timestamp), 'm/d/Y H:i:s') . '&nbsp' . $center . '&nbsp'  . 'RSSC', '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Order Report (' . $reporttype . ')', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User : ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix : ' . $prefix, '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sort by : ' . $sorting, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Project : ' . $projname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 38;
    $page = 40;
    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $docno = "";
    $i = 0;
    $total = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Sales Order#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }



  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader($layoutsize, $config);


    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $qry1 = "select group_concat(distinct 
                concat(
                  case when num.bref = 'POD' then 'D' 
                  else 'P' 
                end,
                right(num.yr,2),
                right(head.docno,5))) as docno,sum(qsstock.ext) as ext, head.insurance
                from hpohead as head
                left join hqshead as qthead on qthead.sotrno=head.sotrno
                left join hqsstock as qsstock on qsstock.trno=qthead.trno
                left join item as i on i.itemid=qsstock.itemid
                left join transnum as num on num.trno = head.trno
                where head.sotrno = $data->sotrno
                group by head.docno, head.insurance
              union all
              select group_concat(distinct 
                concat(
                  case when num.bref = 'POD' then 'D' 
                  else 'P' 
                end,
                right(num.yr,2),
                right(head.docno,5))) as docno,sum(qsstock.ext) as ext, head.insurance
                from pohead as head
                left join hqshead as qthead on qthead.sotrno=head.sotrno
                left join hqsstock as qsstock on qsstock.trno=qthead.trno
                left join item as i on i.itemid=qsstock.itemid
                left join transnum as num on num.trno = head.trno
                where head.sotrno = $data->sotrno
                group by head.docno,head.insurance
                union all
                select group_concat(distinct 
                concat(
                  case when num.bref = 'JO' then 'O' 
                  else 'O' 
                end,
                right(num.yr,2),
                right(joh.docno,5))) as docno, sum(qsstock.ext) as ext, qsstock.insurance 
                from hjohead as joh left join hjostock as jos on jos.trno = joh.trno
                left join hsrstock as qsstock on qsstock.trno = jos.refx and qsstock.line = jos.line
                left join transnum as num on num.trno = joh.trno
                where qsstock.trno = $data->qttrno
                group by joh.docno,qsstock.insurance
                union all
                select group_concat(distinct 
                concat(
                  case when num.bref = 'JO' then 'O' 
                  else 'O' 
                end,
                right(num.yr,2),
                right(joh.docno,5))) as docno, sum(qsstock.ext) as ext, qsstock.insurance 
                from johead as joh left join jostock as jos on jos.trno = joh.trno
                left join hsrstock as qsstock on qsstock.trno = jos.refx and qsstock.line = jos.line
                left join transnum as num on num.trno = joh.trno
                where qsstock.trno = $data->qttrno
                group by joh.docno,qsstock.insurance
                ";
        $subresult1 = $this->coreFunctions->opentable($qry1);

        $erpnum = '';

        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->sodateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->deldate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->sonum, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customername, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        if (!empty($subresult1)) {
          foreach ($subresult1 as $key => $data1) {
            if ($erpnum <> '') {
              $erpnum = $erpnum . "," . str_replace(",", ",<br>", $data1->docno);
            } else {
              $erpnum = str_replace(",", ",<br>", $data1->docno);
            }
          }
        }
        $str .= $this->reporter->col($erpnum, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->salespersonname, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
    }


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
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DELIVERY DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES ORDER #', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER NAME', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER PO NO.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ERP#/REMARKS', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SALES PERSON', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class