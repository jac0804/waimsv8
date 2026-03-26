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

class sales_activity_report
{
  public $modulename = 'Sales Activity Report';
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
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'reportusers', 'approved'];
    switch ($companyid) {
      case 10: //afti
        array_push($fields, 'project', 'ddeptname');
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        break;
      default:
        $col1 = $this->fieldClass->create($fields);
        break;
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'reportusers.lookupclass', 'user');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.required', true);

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
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '0' as clientid,
        '0' as deptid,
        '' as client,
        '' as clientname,
        '' as userid,
        '' as username,
        '' as approved,
        '0' as posttype,
        '0' as reporttype, 
        'ASC' as sorting,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '' as dclientname,'' as reportusers,
         '' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname";
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
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case 0:
        // $result = $this->reportDefaultLayout_SUMMARIZED($config);
        if ($companyid == 10 || $companyid == 12) { //afti
          $result = $this->reportDefaultLayout_SUMMARIZEDnew($config);
        } else {
          $result = $this->reportDefaultLayout_SUMMARIZED($config);
        }
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

    $query = $this->default_QUERY($config);

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $companyid = $config['params']['companyid'];
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
    $projectcode = $config['params']['dataparams']['project'];
    $dept = $config['params']['dataparams']['dept'];
    $projectid = $config['params']['dataparams']['projectid'];
    $deptid = $config['params']['dataparams']['deptid'];
    $dept = $config['params']['dataparams']['dept'];

    $filter = "";
    $filter1 = "";
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      // $projectcode = $config['params']['dataparams']['project'];
      // $dept = $config['params']['dataparams']['dept'];
      // $projectid = $config['params']['dataparams']['projectid'];
      // $deptid = $config['params']['dataparams']['deptid'];
      // $dept = $config['params']['dataparams']['dept'];
      // if ($deptid == "") {
      //   $dept = "";
      // } else {
      //   $dept = $config['params']['dataparams']['deptid'];
      // }
      if ($projectcode != "") {
        $filter1 .= " and stock.projectid = $projectid";
      }
      if ($dept != "") {
        $filter1 .= " and head.deptid = $deptid";
      }

      $barcodeitemnamefield = ",item.partno as barcode, concat(model.model_name,' ',brand.brand_desc,' ',i.itemdescription) as itemname";
      $addjoin = "left join model_masterfile as model on model.model_id=item.model left join frontend_ebrands as brand on brand.brandid = item.brand left join iteminfo as i on i.itemid  = item.itemid";
    } else {
      $filter1 .= "";

      $barcodeitemnamefield = ",item.barcode,item.itemname";
      $addjoin = "";
    }


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            //   $query = "
            // select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
            // wh.clientname, head.createby, left(head.dateid,10) as dateid,branch.clientname as branchname,
            // dept.client as deptcode, dept.clientname as deptname
            // from hopstock as stock
            // left join hophead as head on head.trno=stock.trno
            // left join item on item.itemid=stock.itemid
            // left join transnum on transnum.trno=head.trno
            // left join client on client.clientid=stock.whid
            // left join client as wh on wh.client = head.wh
            // left join client as supp on supp.client = head.client
            // left join client as branch on branch.clientid = head.branch
            // left join client as dept on dept.clientid = head.deptid
            // where head.doc='OP'  and date(head.dateid) between '$start' and '$end' $filter $filter1
            // group by head.docno, head.clientname,
            // wh.clientname, head.createby, head.dateid, branch.clientname, dept.client, dept.clientname
            // order by docno $sorting";
            //   break;
            $query = "select 'POSTED' as status, head.docno,left(head.createdate,10) as createdate,supp.client as customerid, head.clientname as customername,
            case when att.contactid = 0 then att.contactname 
            else concat(cp.lname,', ',cp.fname,' ',cp.mname) end as contactname,
            att.contactno,left(head.dateid,10) as dateid,
            att.department, att.designation,att.email,agent.clientname as salesperson,
            head.rem,att.mrktremarks,att.status as actstat, head.source,att.saleremarks,
            sum(stock.ext) as ext,att.isinactive,head.trno,(case
            when head.source = 'Exhibit' then ex.title
            when head.source = 'Seminar' then sem.title
            when head.source = 'Others' then ifnull(source.description, ' ')
            when head.source = 'Principal Leads' then projectx.name
            else head.source
            end) as source
          from hopstock as stock
          left join hophead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as supp on supp.client = head.client

          left join attendee as att on att.optrno=head.trno
          left join client as agent on agent.client = head.agent
          left join client as cl on cl.clientid = att.clientid
          left join contactperson as cp on cp.line = att.contactid and cp.clientid = cl.clientid
          left join exhibit as ex on head.sourceid = ex.line 
          left join seminar as sem on head.sourceid = sem.line
          left join source as source on source.line = head.sourceid
          left join projectmasterfile as projectx on projectx.line = head.sourceid
          where head.doc='OP'  and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.createdate,supp.client, att.contactname,att.contactno,head.clientname,
          head.createby, head.dateid, att.department, att.designation,
          att.email,att.mrktremarks,att.status,head.rem,agent.clientname, head.source,att.saleremarks,att.isinactive,att.contactid,
          cp.lname,cp.fname,cp.mname,head.trno,ex.title,sem.title,source.description,projectx.name
          order by docno $sorting";
            break;

          case 1: // unposted
            //   $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
            // wh.clientname, head.createby, left(head.dateid,10) as dateid,branch.clientname as branchname,
            // dept.client as deptcode, dept.clientname as deptname
            // from opstock as stock
            // left join ophead as head on head.trno=stock.trno
            // left join item on item.itemid=stock.itemid
            // left join transnum on transnum.trno=head.trno
            // left join client on client.clientid=stock.whid
            // left join client as supp on supp.client = head.client
            // left join client as wh on wh.client = head.wh
            // left join client as branch on branch.clientid = head.branch
            // left join client as dept on dept.clientid = head.deptid
            // where head.doc='OP' and date(head.dateid) between '$start' and '$end' $filter $filter1
            // group by head.docno, head.clientname,
            // wh.clientname, head.createby, head.dateid, branch.clientname, dept.client, dept.clientname
            // order by docno $sorting";

            $query = "select 'UNPOSTED' as status, head.docno,left(head.createdate,10) as createdate,supp.client as customerid, head.clientname as customername,
          case when att.contactid = 0 then att.contactname 
          else concat(cp.lname,', ',cp.fname,' ',cp.mname) end as contactname,
          att.contactno,left(head.dateid,10) as dateid,
          att.department, att.designation,att.email,agent.clientname as salesperson,
          head.rem,att.mrktremarks,att.status as actstat, head.source,att.saleremarks,
          sum(stock.ext) as ext,att.isinactive,head.trno,(case
          when head.source = 'Exhibit' then ex.title
          when head.source = 'Seminar' then sem.title
          when head.source = 'Others' then ifnull(source.description, ' ')
          when head.source = 'Principal Leads' then projectx.name
          else head.source
          end) as source
          from opstock as stock
          left join ophead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as supp on supp.client = head.client

          left join attendee as att on att.optrno=head.trno
          left join client as agent on agent.client = head.agent
          left join client as cl on cl.clientid = att.clientid
          left join contactperson as cp on cp.line = att.contactid and cp.clientid = cl.clientid
          left join exhibit as ex on head.sourceid = ex.line 
          left join seminar as sem on head.sourceid = sem.line
          left join source as source on source.line = head.sourceid
          left join projectmasterfile as projectx on projectx.line = head.sourceid
          where head.doc='OP'  and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.createdate,supp.client, att.contactname,att.contactno,head.clientname,
          head.createby, head.dateid, att.department, att.designation,
          att.email,att.mrktremarks,att.status,head.rem,agent.clientname, head.source,att.saleremarks,att.isinactive,att.contactid,
          cp.lname,cp.fname,cp.mname,head.trno,ex.title,sem.title,source.description,projectx.name
          order by docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno,left(head.createdate,10) as createdate,supp.client as customerid, head.clientname as customername,
          case when att.contactid = 0 then att.contactname 
          else concat(cp.lname,', ',cp.fname,' ',cp.mname) end as contactname,
          att.contactno,left(head.dateid,10) as dateid,
          att.department, att.designation,att.email,agent.clientname as salesperson,
          head.rem,att.mrktremarks,att.status as actstat, head.source,att.saleremarks,
          sum(stock.ext) as ext,att.isinactive,head.trno,(case
          when head.source = 'Exhibit' then ex.title
          when head.source = 'Seminar' then sem.title
          when head.source = 'Others' then ifnull(source.description, ' ')
          when head.source = 'Principal Leads' then projectx.name
          else head.source
        end) as source
          from opstock as stock
          left join ophead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as supp on supp.client = head.client
          left join attendee as att on att.optrno=head.trno
          left join client as agent on agent.client = head.agent
           left join client as cl on cl.clientid = att.clientid
          left join contactperson as cp on cp.line = att.contactid and cp.clientid = cl.clientid
           left join exhibit as ex on head.sourceid = ex.line 
          left join seminar as sem on head.sourceid = sem.line
          left join source as source on source.line = head.sourceid
          left join projectmasterfile as projectx on projectx.line = head.sourceid
          where head.doc='OP'  and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.createdate,supp.client, att.contactname,att.contactno,head.clientname,
          head.createby, head.dateid, att.department, att.designation,
          att.email,att.mrktremarks,att.status,head.rem,agent.clientname, head.source,att.saleremarks,att.isinactive,att.contactid,
          cp.lname,cp.fname,cp.mname,head.trno,ex.title,sem.title,source.description,projectx.name

          union all

          select 'POSTED' as status, head.docno,left(head.createdate,10) as createdate,supp.client as customerid, head.clientname as customername,
          case when att.contactid = 0 then att.contactname 
          else concat(cp.lname,', ',cp.fname,' ',cp.mname) end as contactname,
          att.contactno,left(head.dateid,10) as dateid,
          att.department, att.designation,att.email,agent.clientname as salesperson,
          head.rem,att.mrktremarks,att.status as actstat, head.source as src,att.saleremarks,
          sum(stock.ext) as ext,att.isinactive,head.trno,(case
          when head.source = 'Exhibit' then ex.title
          when head.source = 'Seminar' then sem.title
          when head.source = 'Others' then ifnull(source.description, ' ')
          when head.source = 'Principal Leads' then projectx.name
          else head.source
        end) as source
          from hopstock as stock
          left join hophead as head on head.trno=stock.trno
          left join transnum on transnum.trno=head.trno
          left join client as supp on supp.client = head.client
          left join attendee as att on att.optrno=head.trno
          left join client as agent on agent.client = head.agent
          left join client as cl on cl.clientid = att.clientid
          left join contactperson as cp on cp.line = att.contactid and cp.clientid = cl.clientid
          left join exhibit as ex on head.sourceid = ex.line 
          left join seminar as sem on head.sourceid = sem.line
          left join source as source on source.line = head.sourceid
          left join projectmasterfile as projectx on projectx.line = head.sourceid
          where head.doc='OP'  and date(head.dateid) between '$start' and '$end' $filter $filter1
          group by head.docno,head.createdate,supp.client, att.contactname,att.contactno,head.clientname,
          head.createby, head.dateid, att.department, att.designation,
          att.email,att.mrktremarks,att.status,head.rem,agent.clientname, head.source,att.saleremarks,att.isinactive,att.contactid,
          cp.lname,cp.fname,cp.mname,head.trno,ex.title,sem.title,source.description,projectx.name
          order by docno $sorting";
            break;
        } // end switch posttype
        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select a.yourref, a.docno, a.supplier, a.barcode, a.itemname, a.uom, a.iss, a.isamt, a.disc, 
          a.ext, a.clientname,a.createby, a.loc, a.rem, a.dateid, a.qa, a.branchname, a.deptcode, a.deptname
          from (
          select head.yourref,head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.iss,stock.isamt,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,
          round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,branch.clientname as branchname,
          dept.client as deptcode, dept.clientname as deptname
          from hopstock as stock
          left join hophead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join client as branch on branch.clientid = head.branch
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='OP' and date(head.dateid) between '$start' and '$end' $filter $filter1) as a
          order by a.docno $sorting";
            break;

          case 1: // unposted
            $query = "select a.yourref, a.docno, a.supplier, a.barcode, a.itemname, a.uom, a.iss, a.isamt, a.disc, 
          a.ext, a.clientname,a.createby, a.loc, a.rem, a.dateid, a.qa, a.branchname, a.deptcode, a.deptname
          from (select head.yourref,head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.iss,stock.isamt,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,
          round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,branch.clientname as branchname,
          dept.client as deptcode, dept.clientname as deptname
          from opstock as stock
          left join ophead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join client as branch on branch.clientid = head.branch
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='op' and date(head.dateid) between '$start' and '$end' $filter $filter1
          ) as a
          order by a.docno $sorting";
            break;

          default: // sana all talaga
            $query = "select a.yourref, a.docno, a.supplier, a.barcode, a.itemname, a.uom, a.iss, a.isamt, a.disc, 
          a.ext, a.clientname,a.createby, a.loc, a.rem, a.dateid, a.qa, a.branchname
          from (select head.yourref,head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.iss,stock.isamt,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,
          round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,branch.clientname as branchname,
          dept.client as deptcode, dept.clientname as deptname
          from opstock as stock
          left join ophead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join client as branch on branch.clientid = head.branch
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='op' and date(head.dateid) between '$start' and '$end' $filter $filter1
          union all
          select head.yourref,head.docno,head.clientname as supplier
          " . $barcodeitemnamefield . ",stock.uom,stock.iss,stock.isamt,stock.disc,stock.ext,
          client.clientname,head.createby,stock.loc,stock.rem,head.dateid,
          round((stock.iss-stock.qa)/ case when ifnull(uom.factor,0)=0 then 1 else uom.factor end,2) as qa,branch.clientname as branchname,
          dept.client as deptcode, dept.clientname as deptname
          from hopstock as stock
          left join hophead as head on head.trno=stock.trno
          left join item on item.itemid=stock.itemid
          left join transnum on transnum.trno=head.trno
          left join client on client.clientid=stock.whid
          left join uom on uom.itemid=item.itemid and uom.uom=stock.uom
          left join client as branch on branch.clientid = head.branch
          left join client as dept on dept.clientid = head.deptid
          " . $addjoin . "
          where head.doc='op' and date(head.dateid) between '$start' and '$end' $filter $filter1) as a
          order by a.docno $sorting";
            break;
        }
        break;
    }
    // var_dump($query);
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

    $layoutsize = '800';
    if ($reporttype == 0) {
      $reporttype = 'Summarized';
      if ($companyid == 10 || $companyid == 12) {
        $layoutsize = '1500';
      }
    } else {
      $reporttype = 'Detailed';
    }

    $str = '';


    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Activity Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    if ($companyid == 10) { //afti
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '130', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Department : ' . $deptname, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type : ' . $posttype, '160', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by : ' . $sorting, '130', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Project : ' . $projname, '210', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User: ' . $user, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Transaction Type: ' . $posttype, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Sort by: ' . $sorting, '200', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 41;
    $page = 40;
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
          $str .= '<br/>';
          if ($companyid == 10 || $companyid == 12) { //afti, afti usd
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Customer: ' . $data->supplier, '300', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          } else {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Doc#: ' . $data->docno, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Date: ' . $data->dateid, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('', '150', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();

            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('Customer: ' . $data->supplier, '400', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col('Warehouse: ' . $data->clientname, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->endrow();
          }

          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          switch ($companyid) {
            case 10: //afti
            case 12: //afti usd
              $str .= $this->reporter->col('SKU/Part No.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
            default:
              $str .= $this->reporter->col('Barcode', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
              break;
          }
          $str .= $this->reporter->col('Item Description', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Branch', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', '62', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->barcode, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->branchname, '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, '62', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);

          $page = $page + $count;
        } //end if

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

    $count = 41;
    $page = 40;
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


    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, 'max-width:70px;overflow-wrap: break-word;'); //,'',0,'max-width:50px;overflow-wrap: break-word;'
        $str .= $this->reporter->col($data->branchname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

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
    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '250', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BRANCH', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  public function tableheadernew($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document#', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Create Date', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer ID', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Contact Name', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Contact#', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Department', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Designation', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Email', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    
    $str .= $this->reporter->col('Sales Person', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Source', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Sales Remarks', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout_SUMMARIZEDnew($config)
  {
    $result = $this->reportDefault($config);
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;
    $str = '';
    $layoutsize = '1500';
    $this->reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => $layoutsize];
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "8";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheadernew($layoutsize, $config);


    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $qry = "select trno, line, left(dateid, 10) as dateid, starttime, endtime, rem, calltype, contact
      from calllogs
      where trno = '" . $data->trno . "'
      order by dateid DESC LIMIT 1";
        $calllogs = $this->coreFunctions->opentable($qry);
        $salesrem = $data->saleremarks;
        if(!empty($calllogs)){
          $salesrem = $calllogs[0]->rem;
        }
        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createdate, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customerid, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->customername, '110', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '', 0, 'max-width:110px;overflow-wrap: break-word;'); //,'',0,'max-width:50px;overflow-wrap: break-word;'
        $str .= $this->reporter->col($data->contactname, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->contactno, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->department, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->designation, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->email, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->salesperson, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->source, '50', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($salesrem, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '50', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheadernew($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }
    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '', 0, 'max-width:70px;overflow-wrap: break-word;'); //,'',0,'max-width:50px;overflow-wrap: break-word;'
    $str .= $this->reporter->col('', '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class