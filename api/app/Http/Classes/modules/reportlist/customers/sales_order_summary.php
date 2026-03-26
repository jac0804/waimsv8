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

class sales_order_summary
{
  public $modulename = 'Sales Order Summary Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '2000'];



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
    $fields = ['radioprint', 'start', 'end', 'dclientname'];

    switch ($companyid) {
      case 10: //afti
      case 12: //afti usd
        array_push($fields, 'project', 'ddeptname', 'industry');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookupindustry');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '' as client,
        '' as clientname,
        '' as dclientname,
        '0' as posttype";

    if ($companyid == 10) { //afti
      $paramstr .= ", '' as project, '' as projectid, '' as projectname,
                      '' as ddeptname, '' as dept, '' as deptname,'' as industry";
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

  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];


    $result = $this->reportDefault_New_Layout_SUMMARIZED($config);

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY

    $query = $this->default_QUERY($config);


    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname     = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];


    $filter = "";
    $filter1 = "";
    $filter2 = "";
    $joins = "";
    if ($client != "") {
      $filter .= " and qthead.client = '$client' ";
    }

    if ($companyid == 10) { //afti
      $prjid = $config['params']['dataparams']['project'];
      $deptid = $config['params']['dataparams']['ddeptname'];
      $project = $config['params']['dataparams']['projectid'];
      $indus = $config['params']['dataparams']['industry'];
      if ($deptid == "") {
        $dept = "";
      } else {
        $dept = $config['params']['dataparams']['deptid'];
      }
      if ($prjid != "") {
        $filter1 .= " and qsstock.projectid = $project";
        $filter2 .= " and srstock.projectid = $project";
      }
      if ($deptid != "") {
        $filter1 .= " and qthead.deptid = $dept";
      }
      if ($indus != "") {
        $filter1 .= " and indus.industry = '$indus'";
      }
      $joins = "left join client as indus on indus.client=qthead.client";
    } else {
      $filter1 .= "";
    }

    switch ($posttype) {
      case '0'; // posted
        $query = "select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,
        sohead.delcharge,ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,
        qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from hsqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from hsshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join 
        (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins

        ";
        break;
      case '1': // unposted
        $query = "select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from sqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from sshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins

        ";
        break;
      case '2': // all 
        $query = "
         select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from hsqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,
        sohead.delcharge,ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from hsshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from sqhead as sohead
        left join hqshead as qthead on qthead.sotrno = sohead.trno
        left join hqsstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins

        union all

        select sohead.doc,sohead.trno as sotrno,qthead.trno as qttrno,date(sohead.dateid) as sodateid, sohead.docno as sonum,
        qthead.client as customercode, qthead.clientname as customername,
        qthead.yourref,ifnull(salesperson.clientname,'') as salespersonname,
        branch.clientname as branchname,ifnull(sjstock.docno,'') as sjdocno,sohead.delcharge,
        ifnull(del.delcharge,'') as actualdelcharge,   ifnull(del.modeofdelivery,'') as modeofdelivery, ifnull(del.trackingno,'') as trackingno,qthead.tax,sum(qsstock.ext) as ext,ifnull(sjstock.ins,0) as insurance
        from sshead as sohead
        left join hsrhead as qthead on qthead.sotrno = sohead.trno
        left join hsrstock as qsstock on qsstock.trno=qthead.trno
        left join client as salesperson on salesperson.client = qthead.agent
        left join client as branch on branch.clientid = qthead.branch
        left join (select s.refx,h.docno,h.trno,sum(s.insurance) as ins  from lastock as s left join lahead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno
        union all select s.refx,h.docno,h.trno,sum(s.insurance) as ins from glstock as s left join glhead as h on h.trno = s.trno where h.doc in ('SJ','AI') and s.refx<>0 group by  h.trno,s.refx,h.docno) as sjstock on sjstock.refx=qthead.trno
        left join delstatus as del on del.trno=sjstock.trno
        $joins
        where qthead.client is not null and date(sohead.dateid) between '" . $start . "' and '" . $end . "'
        $filter $filter1
        group by sohead.doc,sohead.trno,qthead.trno,sohead.dateid, sohead.docno, qthead.client,
        qthead.clientname, qthead.yourref, salesperson.clientname, branch.clientname,sjstock.docno,sohead.delcharge,del.delcharge,  del.modeofdelivery,del.trackingno,qthead.tax,sjstock.ins
        
        ";
        break;
    }

    return $query;
  }

  public function header_DEFAULT_New($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];


    if ($companyid == 10) { //afti
      $dept   = $config['params']['dataparams']['ddeptname'];
      $proj   = $config['params']['dataparams']['project'];
      $indus   = $config['params']['dataparams']['industry'];
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

      if ($indus == "") {
        $indus = 'ALL';
      }
    }

    $str = '';
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Order Summary Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    if ($companyid == 10) { //afti
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Industry : ' . $indus, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    // Po Date x 
    // SO# x 
    // Customer x 
    // Cust. PO x 
    // ERP# x 
    // PO Amount c >
    // VAT c = total per item grp ext *.12 >
    // Delivery Charge new field yesterday c  >
    // Actual Delivery Amount SJ > del status > del amount c  >
    // Total PO = po amount + vatc >
    // Item Group c >
    // DR/SI# c  >
    // Sales Person x 
    // Status check doc listing c


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PO Date', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SO#', '50', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Cust. PO", '85', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ERP#', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO Amount', '70', null, false, $border, 'BT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VAT', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total PO', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Del Amt', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Insurance', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DR/SI#', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Mode of Delivery', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Tracking #', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '85', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefault_New_Layout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1300';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "8";
    $border = "0.25px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT_New($config);

    $totalext = 0;
    $totalbal = 0;



    foreach ($result as $key => $data) {
      //to get PO Details (ERP column)
      $qry1 = "select docno from (select group_concat(distinct head.docno) as docno
                from hpohead as head left join transnum as num on num.trno = head.trno
                left join hqshead as qthead on qthead.sotrno=head.sotrno
                left join hqsstock as qsstock on qsstock.trno=qthead.trno
                left join item as i on i.itemid=qsstock.itemid
                where head.sotrno = $data->sotrno
              union all
              select group_concat(distinct head.docno) as docno
                from pohead as head left join transnum as num on num.trno = head.trno
                left join hqshead as qthead on qthead.sotrno=head.sotrno
                left join hqsstock as qsstock on qsstock.trno=qthead.trno
                left join item as i on i.itemid=qsstock.itemid
                where head.sotrno = $data->sotrno
                union all
                select group_concat(distinct joh.docno) as docno
                from hjohead as joh left join transnum as num on num.trno = joh.trno left join hjostock as jos on jos.trno = joh.trno
                left join hsrstock as qsstock on qsstock.trno = jos.refx and qsstock.line = jos.line
                where qsstock.trno = $data->qttrno
                union all
                select group_concat(distinct joh.docno) as docno
                from johead as joh left join transnum as num on num.trno = joh.trno left join jostock as jos on jos.trno = joh.trno
                left join hsrstock as qsstock on qsstock.trno = jos.refx and qsstock.line = jos.line
                where qsstock.trno = $data->qttrno) as a where docno is not null";
      $subresult1 = $this->coreFunctions->opentable($qry1);

      $erpnum = '';
      $poamount = 0;
      $vat = 0;
      $totalpo = 0;
      $salestatus = '';
      $insurance = 0;
      $tblstock = '';
      $htable = '';

      if ($data->doc == 'SQ') {
        $htable = 'sqhead';
      } else {
        $htable = 'srhead';
      }
      //getting status
      $isposted = $this->othersClass->isposted2($data->sotrno, 'transnum');

      if ($isposted) { //checking if status for dr/si,overdue,complete,close
        if ($data->doc == 'SQ') {
          $served = $this->coreFunctions->datareader("select sum(iss-(voidqty+sjqa)) as value from hqsstock where trno = " . $data->qttrno);
        } else {
          $served = $this->coreFunctions->datareader("select sum(iss-(voidqty+sjqa)) as value from hsrstock where trno = " . $data->qttrno);
        }

        if ($served != 0) {
          //overdue or For dr/si
          $deldate = $this->coreFunctions->datareader("select ifnull(deldate,'') as value from hqshead where trno= " . $data->qttrno);
          if ($deldate != '' &&  $deldate <  date('Y-m-d H:i:s')) {
            $salestatus = 'OVERDUE';
          } else {
            $bal = $this->coreFunctions->datareader("select ifnull(sum(bal),0) as value from (select rr.bal from rrstatus as rr left join hqsstock as s on s.itemid = rr.itemid and s.whid = rr.whid where s.trno = " . $data->qttrno . "
             union all select rr.bal from rrstatus as rr left join hqtstock as s on s.itemid = rr.itemid and s.whid = rr.whid where s.trno = " . $data->qttrno . ") as a");
            if ($bal == 0) {
              $salestatus = 'POSTED';
            } else {
              $salestatus = 'FOR DR/SI';
            }
          }
        } else {
          //check if posted dr/si
          $sj = $this->coreFunctions->datareader("select distinct trno as value from lastock where refx = " . $data->qttrno);
          if ($sj != 0) {
            $salestatus = 'COMPLETE';
          } else {
            $salestatus = 'CLOSE';
          }
        }
      } else { //draft or locked
        $s = $this->coreFunctions->datareader("select trno as value from " . $htable . " where  lockdate is not null and trno = " . $data->sotrno);
        if (floatVal($s) != 0) {
          $salestatus = 'LOCKED';
        } else {
          $salestatus = 'DRAFT';
        }
      }

      $str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->sodateid, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->sonum, '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '85', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->customername, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      if (!empty($subresult1)) {
        foreach ($subresult1 as $key => $data1) {
          $erpnum = str_replace(",", ",<br>", $data1->docno);
        }
      }

      $poamount = $data->ext;

      if ($data->tax != 0) {
        $vat = $poamount * 0.12;
      } else {
        $vat = 0;
      }
      $totalpo = $poamount + $vat;

      $str .= $this->reporter->col($erpnum, '85', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($poamount, 2), '70', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($vat, 2), '85', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col(number_format($totalpo, 2), '85', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col($data->actualdelcharge, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->insurance, 2), '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->sjdocno, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->salespersonname, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->modeofdelivery, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->trackingno, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col($salestatus, '85', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientname = $config['params']['dataparams']['clientname'];

    if ($companyid == 10) { //afti
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
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Order Summary Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    if ($companyid == 10) { //afti
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Department : ' . $deptname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project : ' . $projname, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PO Processed Date', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Order', '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Customer's PO #", '100', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Person', '200', null, false, $border, 'BT', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('ERP #', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Branch', '100', null, false, $border, 'BT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

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

    $totalext = 0;
    $totalbal = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->addline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->sodateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->sonum, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->customername, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->salespersonname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $erpnum = str_replace(",", "<br>", $data->erpnum);
      $str .= $this->reporter->col($erpnum, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->branchname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->header_DEFAULT($config);
        $page = $page + $count;
      } //end if
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class