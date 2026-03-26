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

class collection_report
{
  public $modulename = 'Collection Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1400'];


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

    switch ($companyid) {
      case 8: //maxipro
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dprojectname', 'subprojectname', 'optionstatus'];
        break;
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'radioreporttype'];
        break;
      case 55: //afli
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'optionstatus', 'prepared', 'checked', 'approved'];
        break;
      case 29: // SBC
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'optionstatus', 'prepared', 'checked', 'approved'];
        break;
      default:
        $fields = ['radioprint', 'dclientname'];
        break;
    }

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');

    data_set($col1, 'subprojectname.required', false);
    data_set($col1, 'subprojectname.readonly', false);
    data_set($col1, 'dprojectname.lookupclass', 'projectcode');
    data_set($col1, 'approved.label', 'Posted by');
    $options = array(
      ['label' => 'With SI', 'value' => 'SI', 'color' => 'red'],
      ['label' => 'Customer Deposits', 'value' => 'CD', 'color' => 'red'],
      ['label' => 'Unapplied Customer Deposits', 'value' => 'UCD', 'color' => 'red'],
      ['label' => 'PDC', 'value' => 'PDC', 'color' => 'red'],
      ['label' => 'All', 'value' => 'ALL', 'color' => 'red']
    );

    data_set($col1, "radioreporttype.options", $options);

    $options = array(
      ['label' => 'Unposted', 'value' => 'Unposted', 'color' => 'blue'],
      ['label' => 'Posted', 'value' => 'Posted', 'color' => 'blue'],
      ['label' => 'All', 'value' => 'All', 'color' => 'blue']
    );

    data_set($col1, "optionstatus.options", $options);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $user = $config['params']['user'];
    $config['params']['doc'] = $this->modulename;
    $signatories = $this->othersClass->getSignatories($config);
    $prepared = $this->coreFunctions->datareader("select name as value from useraccess where username = '" . $user . "'");
    $approved = '';
    $checked =  '';
    foreach ($signatories as $key => $value) {
      switch ($value->fieldname) {
        case 'posted':
          $approved = $value->fieldvalue;
          break;
        case 'checked':
          $checked = $value->fieldvalue;
          break;
      }
    }
    switch ($companyid) {
      case 8: //maxipro
      case 55: //afli
      case 29: //sbc


        return $this->coreFunctions->opentable("select 
      'default' as print,
      adddate(left(now(),10),-360) as start, date_add(date(now()),interval 1 month) as end,
      '' as client,
      '0' as clientid,
      '' as clientname,
      '' as dclientname,
      '' as dprojectname, '' as projectname,0 as projectid,
       '' as projectcode,'' as subprojectname,0 as subproject,
      'All' as status,
      '" . $prepared . "' as prepared,
      '" . $checked . "' as checked,
      '" . $approved . "' as approved
      ");
        break;

      default:
        return $this->coreFunctions->opentable("select 
      'default' as print,
      date(now()) as start, date_add(date(now()),interval 1 month) as end,
      '' as client,
      '' as clientname,
      '' as dclientname,'ALL' as reporttype
      ");
        break;
    }
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
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 8: //maxipro
        return $this->report_MaxiproLayout($config);
        break;

      case 55: //afli
        return $this->report_Afli_Layout($config);
        break;

      case 29: // SBC
        return $this->report_SBC_Layout($config);
        break;

      default:
        return $this->reportDefaultLayout($config);
        break;
    }
  }

  public function reportMaxiproQry($config)
  {
    // QUERY

    $client     = $config['params']['dataparams']['client'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $status = $config['params']['dataparams']['status'];
    $projectname = $config['params']['dataparams']['projectname'];
    $subprojectname = $config['params']['dataparams']['subprojectname'];

    $projectid = 0;
    if ($projectname != '') {
      $projectid = $config['params']['dataparams']['projectid'];
    }

    $subproject = 0;
    if ($subprojectname != '') {
      $subproject = $config['params']['dataparams']['subproject'];
    }

    $filter = "";

    if ($client != '') {
      $filter .= " and client.client = '" . $client . "' ";
    }

    if ($projectid != 0) {
      $filter .= " and detail.projectid = " . $projectid . " ";
    }

    if ($subproject != 0) {
      $filter .= " and detail.subproject = " . $subproject . " ";
    }


    switch ($status) {
      case 'Unposted':
        $qry = "select head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby,
                    (select sum(d.db) from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'PB') as pbamt,
                        (select sum(d.db) recoup from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR3') as recoup, 
                    tcp.tcp,tcp.ocp,
                    (select sum(d.db) from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'AD') as advamt,
                    (select sum(d.db) retention from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR2') as retention,
                    (select sum(d.db) ewt from lahead as h left join ladetail as d on d.trno=h.trno
                          left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR4') as ewt,
                          head.yourref as particular, head.rem2 as wacref
                from lahead as head
                left join ladetail as detail on detail.trno=head.trno
                left join client on client.client=head.client
                left join (select projectid,tcp,ocp from pmhead
                union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
                where head.doc='PB' and head.dateid between '$start' and '$end' $filter
                group by head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby, tcp.tcp,tcp.ocp,head.yourref,head.rem2
                order by head.dateid,head.docno";
        break;
      case 'Posted':
        $qry = "select head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby,
                    (select sum(d.db) from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'PB') as pbamt,
                        (select sum(d.db) recoup from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR3') as recoup, 
                    tcp.tcp,tcp.ocp,
                    (select sum(d.db) from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'PB') as pbamount,
                    (select sum(d.db) from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'AD') as advamt,
                    (select sum(d.db) retention from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR2') as retention,
                    (select sum(d.db) ewt from glhead as h left join gldetail as d on d.trno=h.trno
                          left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR4') as ewt,
                    head.yourref as particular, head.rem2 as wacref
                from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join client on client.clientid=head.clientid
                left join (select projectid,tcp,ocp from pmhead
                union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
                where head.doc='PB' and head.dateid between '$start' and '$end' $filter
                group by head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby, tcp.tcp,tcp.ocp,head.yourref,head.rem2
                order by head.dateid,head.docno";
        break;
      default:
        $qry = "select head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby,
                    (select sum(d.db) from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'PB') as pbamt,
                        (select sum(d.db) recoup from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR3') as recoup, 
                    tcp.tcp,tcp.ocp,
                    (select sum(d.db) from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'AD') as advamt,
                    (select sum(d.db) retention from lahead as h left join ladetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR2') as retention,
                    (select sum(d.db) ewt from lahead as h left join ladetail as d on d.trno=h.trno
                          left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR4') as ewt,
                          head.yourref as particular, head.rem2 as wacref
                from lahead as head
                left join ladetail as detail on detail.trno=head.trno
                left join client on client.client=head.client
                left join (select projectid,tcp,ocp from pmhead
                union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
                where head.doc='PB' and head.dateid between '$start' and '$end' $filter
                group by head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby, tcp.tcp,tcp.ocp,head.yourref,head.rem2
                union all
                select head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby,
                (select sum(d.db) from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'PB') as pbamt,
                        (select sum(d.db) recoup from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR3') as recoup, tcp.tcp,tcp.ocp,
                    (select sum(d.db) from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and left(h.docno,2) = 'AD') as advamt,
                    (select sum(d.db) retention from glhead as h left join gldetail as d on d.trno=h.trno
                    left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR2') as retention,
                    (select sum(d.db) ewt from glhead as h left join gldetail as d on d.trno=h.trno
                          left join coa on coa.acnoid=d.acnoid where h.trno=head.trno and coa.alias='AR4') as ewt,
                    head.yourref as particular, head.rem2 as wacref
                from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join client on client.clientid=head.clientid
                left join (select projectid,tcp,ocp from pmhead
                union all select projectid,tcp,ocp from hpmhead as pmhead) as tcp on tcp.projectid = head.projectid
                where head.doc='PB' and head.dateid between '$start' and '$end' $filter
                group by head.trno,head.dateid,client.clientname,head.docno,head.rem,head.createby, tcp.tcp,tcp.ocp,head.yourref,head.rem2
                order by dateid,docno";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }
  public function afli_query($config)
  {
    $clientid     = $config['params']['dataparams']['clientid'];
    $client     = $config['params']['dataparams']['client'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype     = $config['params']['dataparams']['status'];
    $filter = "";
    $filter1 = "";
    if ($clientid != 0 || $client != '') {
      $filter .= " and head.client = '$client' ";
      $filter1 .= " and head.clientid = '$clientid' ";
    }
    $typeofquery = "";
    switch ($posttype) {
      case 'Unposted':

        $typeofquery = "select date(head.dateid) AS dateid,cl.clientname,head.ourref as pmode,head.docno,
                      (case when coa.alias in ('AR1','AR5','AR6') then detail.cr else 0 end) as principal,
                      (case when coa.alias = 'AR2' then detail.cr else 0 end) as interest,
                      (case when coa.alias = 'SA3' then detail.cr else 0 end) as profee,
                      (case when coa.alias = 'SA6' then detail.cr else 0 end) as penalty
                      from ladetail as detail 
                      left join lahead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid 
                      left join client as cl on cl.client = detail.client 
                      where head.doc = 'CR' and head.dateid between '$start' and '$end' $filter
                      and coa.alias IN('SA3','AR2','AR1','SA6','AR5','AR6')
                      group by head.docno,head.dateid,cl.clientname,coa.alias,detail.cr,head.ourref";
        break;
      case 'Posted':
        $typeofquery = "select date(head.dateid) AS dateid,cl.clientname,head.ourref as pmode,head.docno,
                      (case when coa.alias in ('AR1','AR5','AR6') then detail.cr else 0 end) as principal,
                      (case when coa.alias = 'AR2' then detail.cr else 0 end) as interest,
                      (case when coa.alias = 'SA3' then detail.cr else 0 end) as profee,
                      (case when coa.alias = 'SA6' then detail.cr else 0 end) as penalty
                      from gldetail as detail 
                      left join glhead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid 
                      left join client as cl on cl.clientid = detail.clientid 
                      where head.doc = 'CR' and head.dateid between '$start' and '$end' $filter1 
                      and coa.alias IN('SA3','AR2','AR1','SA6','AR5','AR6')
                      group by head.docno,head.dateid,cl.clientname,coa.alias,detail.cr,head.ourref";
        break;

      default:
        $typeofquery = "select date(head.dateid) AS dateid,cl.clientname,head.ourref as pmode,head.docno,
                      (case when coa.alias in ('AR1','AR5','AR6') then detail.cr else 0 end) as principal,
                      (case when coa.alias = 'AR2' then detail.cr else 0 end) as interest,
                      (case when coa.alias = 'SA3' then detail.cr else 0 end) as profee,
                      (case when coa.alias = 'SA6' then detail.cr else 0 end) as penalty
                      from ladetail as detail 
                      left join lahead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid 
                      left join client as cl on cl.client = detail.client 
                      where head.doc = 'CR' and head.dateid between '$start' and '$end' $filter 
                      and coa.alias IN('SA3','AR2','AR1','SA6','AR5','AR6')
                      group by head.docno,head.dateid,cl.clientname,coa.alias,detail.cr,head.ourref,detail.postdate
                      union all
                      select date(head.dateid) AS dateid,cl.clientname,head.ourref as pmode,head.docno,
                      (case when coa.alias in ('AR1','AR5','AR6') then detail.cr else 0 end) as principal,
                      (case when coa.alias = 'AR2' then detail.cr else 0 end) as interest,
                      (case when coa.alias = 'SA3' then detail.cr else 0 end) as profee,
                      (case when coa.alias = 'SA6' then detail.cr else 0 end) as penalty
                      from gldetail as detail 
                      left join glhead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid 
                      left join client as cl on cl.clientid = detail.clientid 
                      where head.doc = 'CR' and head.dateid between '$start' and '$end' $filter1 
                      and coa.alias IN('SA3','AR2','AR1','SA6','AR5','AR6')
                      group by head.docno,head.dateid,cl.clientname,coa.alias,detail.cr,head.ourref,detail.postdate";
        break;
    }
    $query = "select clientname,dateid,pmode,docno,sum(principal) as principal,sum(interest) as interest,sum(profee) as profee, sum(penalty) as penalty FROM (
              $typeofquery
              ) AS v group by docno,clientname,dateid,pmode";
    return $this->coreFunctions->opentable($query);
  }
  // sbc
  public function sbc_query($config)
  {
    $clientid     = $config['params']['dataparams']['clientid'];
    $client     = $config['params']['dataparams']['client'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype     = $config['params']['dataparams']['status'];
    $filter = "";
    $filter1 = "";
    if ($clientid != 0 || $client != '') {
      $filter .= " and head.client = '$client' ";
      $filter1 .= " and head.clientid = '$clientid' ";
    }
    $sbcquery = "";
    switch ($posttype) {
      case 'Unposted':

        $sbcquery = "select date(head.dateid) AS dateid,DATE_FORMAT(head.dateid,'%Y-%m') as yearmonth,
                      DATE_FORMAT(head.dateid,'%M %Y') as monthlabel,cl.clientname,head.docno,center.name,head.rem,
                      SUM(detail.db) as amount
                      from ladetail as detail
                      left join lahead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as cl on cl.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      left join center  on center.code = cntnum.center
                      where head.doc='CR'  and date(head.dateid) between '$start' and '$end' $filter
                      and left(coa.alias,2) in ('CA','CR','PC', 'CB')
                      group by head.docno,head.dateid,cl.clientname,center.name,head.rem,coa.alias";
        break;
      case 'Posted':
        $sbcquery = "select date(head.dateid) AS dateid,DATE_FORMAT(head.dateid,'%Y-%m') as yearmonth,
                      DATE_FORMAT(head.dateid,'%M %Y') as monthlabel,cl.clientname,head.docno,center.name,head.rem,
                      SUM(detail.db) as amount
                      from gldetail as detail
                      left join glhead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as cl on cl.clientid = head.clientid
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      left join center  on center.code = cntnum.center
                      where head.doc='CR' and date(head.dateid) between '$start' and '$end'   $filter1 
                      and left(coa.alias,2) in ('CA','CR','PC', 'CB')
                      group by head.docno,head.dateid,cl.clientname,center.name,head.rem,coa.alias";
        break;

      default:
        $sbcquery = "select date(head.dateid) AS dateid,DATE_FORMAT(head.dateid,'%Y-%m') as yearmonth,
                      DATE_FORMAT(head.dateid,'%M %Y') as monthlabel,cl.clientname,head.docno,center.name,head.rem,
                      SUM(detail.db) as amount
                      from ladetail as detail
                      left join lahead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as cl on cl.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      left join center  on center.code = cntnum.center
                      where head.doc='CR'  and date(head.dateid) between '$start' and '$end' $filter 
                      and left(coa.alias,2) in ('CA','CR','PC', 'CB')
                      group by head.docno,head.dateid,cl.clientname,center.name,head.rem,coa.alias
                      union all
                      select date(head.dateid) AS dateid,DATE_FORMAT(head.dateid,'%Y-%m') as yearmonth,
                      DATE_FORMAT(head.dateid,'%M %Y') as monthlabel,cl.clientname,head.docno,center.name,head.rem,
                      SUM(detail.db) as amount
                      from gldetail as detail
                      left join glhead as head on head.trno = detail.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as cl on cl.clientid = head.clientid
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      left join center  on center.code = cntnum.center
                      where head.doc='CR' and date(head.dateid) between '$start' and '$end' $filter1 
                      and left(coa.alias,2) in ('CA','CR','PC', 'CB')
                      group by head.docno,head.dateid,cl.clientname,center.name,head.rem,coa.alias";
        break;
    }
    // $query = "select yearmonth,monthlabel,clientname,clientname,dateid,docno,name,rem,SUM(amount) as amount FROM (
    //           $sbcquery
    //           ) AS v group by yearmonth,monthlabel,docno,clientname,dateid,name,rem";
    $query = "select yearmonth, monthlabel, clientname, dateid, docno, name, rem, SUM(amount) as amount
          FROM (
              $sbcquery
          ) AS v
          GROUP BY yearmonth, monthlabel, dateid, docno, name, clientname, rem
          ORDER BY dateid, docno ";
    //  var_dump($query);
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeaderMaxiPro($config)
  {
    $result = $this->reportMaxiproQry($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $report     = $config['params']['dataparams']['status'];

    $project = $config['params']['dataparams']['projectname'];
    $subproject = $config['params']['dataparams']['subprojectname'];

    if ($client == '') {
      $client = 'ALL';
    }

    if ($project == '') {
      $project = 'ALL';
    }

    if ($subproject == '') {
      $subproject = 'ALL';
    }

    switch ($report) {
      case 'Unposted':
        $report = 'UNPOSTED';
        break;
      case 'Posted':
        $report = 'POSTED';
        break;
      default:
        $report = 'ALL';
        break;
    }

    $str = '';
    $layoutsize = '1320';
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
    $str .= $this->reporter->col('COLLECTION REPORT', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer: ' . $client, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction Type: ' . $report, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project: ' . $project, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Subproject: ' . $subproject, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Revised Contract: ' . number_format($result[0]->tcp, 2), null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('DOCUMENT NO.', '120', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '15px');
    $str .= $this->reporter->col('PARTICULAR', '100', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('WAC REFERENCE No.', '280', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('GROSS AMOUNT', '150', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('15% RECOUPMENT', '150', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('10% RETENTION', '150', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('2% EWT', '100', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('NET AMOUNT', '150', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '8px');
    $str .= $this->reporter->col('DATE CLAIMED', '120', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '8px');


    $str .= $this->reporter->endrow();

    $str .= '<br/><br/>';

    return $str;
  }

  public function report_MaxiproLayout($config)
  {
    $result = $this->reportMaxiproQry($config);

    $count = 30;
    $page = 30;
    $layoutsize = '1320';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $bankcharge = 0;
    $fines = 0;
    $bank = '';
    $status = $config['params']['dataparams']['status'];
    $totalpbamt = 0;
    $totaladvamt = 0;
    $totalrecoup = 0;
    $totalcolamt = 0;
    $totalpbbal = 0;
    $remconbal = 0;
    $remamtrecoup = 0;

    $netamt = 0;

    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeaderMaxiPro($config);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $netamt = $data->pbamt - ($data->recoup + $data->retention + $data->ewt);

      $str .= $this->reporter->col($data->docno, '120', null, false, $border, 'TBL', 'CT', $font, $fontsize, '', '', '10px');
      $str .= $this->reporter->col($data->particular, '100', null, false, $border, 'TBL', 'LT', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col($data->wacref, '280', null, false, $border, 'TBL', 'LT', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col($data->pbamt == 0 ? '-' : number_format($data->pbamt, 2), '150', null, false, $border, 'TBL', 'RT', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col($data->recoup == 0 ? '-' : number_format($data->recoup, 2), '150', null, false, $border, 'TBL', 'RT', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col($data->retention == 0 ? '-' : number_format($data->retention, 2), '150', null, false, $border, 'TBL', 'RT', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col($data->ewt == 0 ? '-' : number_format($data->ewt, 2), '100', null, false, $border, 'TBL', 'RT', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col($netamt == 0 ? '-' : number_format($netamt, 2), '150', null, false, $border, 'TBL', 'RT', $font, $fontsize, '', '', '4px');
      $str .= $this->reporter->col($data->dateid, '120', null, false, $border, 'TBLR', 'CT', $font, $fontsize, '', '', '4px');

      $str .= $this->reporter->endrow();


      //remove paging
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeaderMaxiPro($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }


    return $str;
  }

  public function reportDefault($config)
  {

    $client     = $config['params']['dataparams']['client'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $report     = $config['params']['dataparams']['reporttype'];
    $filter   = "";
    $reportdata = [];
    if ($client != "") {
      $filter .= " and cl.client = '" . $client . "'";
    }

    if ($report == 'PDC') {
      $filter .= " and coa.alias = 'CR1'";
    }

    $i = 0;

    $query = "select head.trno, head.docno as journalentry,date(head.dateid) as crdate, 
                   head.crref as collectionreceipt, head.yourref as sinum, cl.clientname,
                   0 as bankcharge, 0 as netamt, detail.checkno as paymentdetails,
                   (case left(coa.alias,2) when 'CB' then coa.acnoname else '' end) as banktransfer, 
                   group_concat(case left(coa.alias,2) when 'CB' then date(detail.postdate) else date(crled.depodate) end separator '/') as datedeposited, di.rem as remarks, '' as jovrem,
                   (select sum(d.db) from gldetail as d left join coa as c on c.acnoid = d.acnoid where d.trno = detail.trno and left(c.alias,2) in ('CA','CB','CR')) as cramt, 0 as lessewt, detail.checkno, detail.ref,coa.alias,detail.refx, 0 as fines 
            from glhead as head
            left join gldetail as detail on detail.trno = head.trno
            left join crledger as crled on crled.trno = head.trno and crled.line = detail.line
            left join client as cl on cl.clientid = head.clientid
            left join coa as coa on coa.acnoid = detail.acnoid
            left join hdetailinfo as di on di.trno = detail.trno and di.line = detail.line
            where head.doc = 'CR' and coa.alias not in ('WT2','BC1','EX1','ARWT','PT1','PD1','WT3') and left(coa.alias,2)<>'AR' and detail.db<>0 and
                  date(head.dateid) between '$start' and '$end' " . $filter . "
            group by head.trno, head.docno, date(head.dateid), collectionreceipt, head.yourref, cl.clientname,
                  bankcharge, netamt, detail.checkno,detail.trno, date(crled.checkdate),
                  banktransfer, crled.depodate, di.rem, jovrem,detail.db,detail.cr, coa.alias,
                  detail.ref,detail.refx,detail.postdate 
             order by crdate,journalentry";
    // if ($report == 'UCD') {
    //   $query = "select * from ( $query ) as g WHERE g.arbal <> 0";
    // }
    $data = $this->coreFunctions->opentable($query);
    // var_dump($data);
    $data = json_decode(json_encode($data), true);
    // var_dump('after decode');
    // var_dump($data);

    if ($report != "") {
      switch ($report) {
        case 'SI':
          foreach ($data as $key => $v) {
            $refx = $this->coreFunctions->opentable("select refx from ladetail where trno = " . $data[$key]['trno'] . " and refx <>0 union all select refx from gldetail where trno = " . $data[$key]['trno'] . " and refx <>0");
            if (!empty($refx)) {
              foreach ($refx as $x => $y) {
                $refdoc = $this->coreFunctions->datareader("select doc as value from cntnum where trno = " . $refx[$x]->refx);
                if ($refdoc == 'SJ' || $refdoc == 'AI' || $refdoc == 'AR') {
                  $reportdata[$i]['trno'] = $data[$key]['trno'];
                  $reportdata[$i]['journalentry'] = $data[$key]['journalentry'];
                  $reportdata[$i]['crdate'] = $data[$key]['crdate'];
                  $reportdata[$i]['collectionreceipt'] = $data[$key]['collectionreceipt'];
                  $reportdata[$i]['clientname'] = $data[$key]['clientname'];
                  $reportdata[$i]['bankcharge'] = $data[$key]['bankcharge'];
                  $reportdata[$i]['netamt'] = $data[$key]['netamt'];
                  $reportdata[$i]['paymentdetails'] = $data[$key]['paymentdetails'];
                  $reportdata[$i]['banktransfer'] = $data[$key]['banktransfer'];
                  $reportdata[$i]['datedeposited'] = $data[$key]['datedeposited'];
                  $reportdata[$i]['remarks'] = $data[$key]['remarks'];
                  $reportdata[$i]['cramt'] = $data[$key]['cramt'];
                  $reportdata[$i]['lessewt'] = $data[$key]['lessewt'];
                  $reportdata[$i]['checkno'] = $data[$key]['checkno'];
                  $reportdata[$i]['ref'] = $data[$key]['ref'];
                  $reportdata[$i]['alias'] = $data[$key]['alias'];
                  $reportdata[$i]['refx'] = $data[$key]['refx'];
                  $reportdata[$i]['sinum'] = $data[$key]['sinum'];
                  $reportdata[$i]['fines'] = $data[$key]['fines'];
                  $i += 1;
                  goto nextcr;
                }
              }
            }
            nextcr:
          }
          break;
        case 'CD':
          foreach ($data as $key => $v) {
            $refx = $this->coreFunctions->opentable(
              "
              select c.alias, d.poref as ref 
              from ladetail as d 
              left join coa as c on c.acnoid = d.acnoid 
              left join transnum as t on t.trno = d.qttrno 
              where d.trno = " . $data[$key]['trno'] . " and d.cr<>0 
              union all 
              select c.alias , d.poref as ref 
              from gldetail as d 
              left join coa as c on c.acnoid = d.acnoid 
              left join transnum as t on t.trno = d.qttrno 
              where d.trno = " . $data[$key]['trno'] . " and d.cr<>0"
            );
            if (!empty($refx)) {
              foreach ($refx as $x => $y) {
                if ($refx[$x]->alias == 'AR5') {
                  $reportdata[$i]['trno'] = $data[$key]['trno'];
                  $reportdata[$i]['journalentry'] = $data[$key]['journalentry'];
                  $reportdata[$i]['crdate'] = $data[$key]['crdate'];
                  $reportdata[$i]['collectionreceipt'] = $data[$key]['collectionreceipt'];
                  $reportdata[$i]['clientname'] = $data[$key]['clientname'];
                  $reportdata[$i]['bankcharge'] = $data[$key]['bankcharge'];
                  $reportdata[$i]['netamt'] = $data[$key]['netamt'];
                  $reportdata[$i]['paymentdetails'] = $data[$key]['paymentdetails'];
                  $reportdata[$i]['banktransfer'] = $data[$key]['banktransfer'];
                  $reportdata[$i]['datedeposited'] = $data[$key]['datedeposited'];
                  $reportdata[$i]['remarks'] = $data[$key]['remarks'];
                  $reportdata[$i]['cramt'] = $data[$key]['cramt'];
                  $reportdata[$i]['lessewt'] = $data[$key]['lessewt'];
                  $reportdata[$i]['checkno'] = $data[$key]['checkno'];
                  $reportdata[$i]['ref'] = $data[$key]['ref'];
                  $reportdata[$i]['alias'] = $data[$key]['alias'];
                  $reportdata[$i]['refx'] = $data[$key]['refx'];
                  $reportdata[$i]['sinum'] = $refx[$x]->ref;
                  $reportdata[$i]['fines'] = $data[$key]['fines'];
                  $i += 1;
                  goto nextcr2;
                }
              }
            }
            nextcr2:
          }
          break;
          case 'UCD':
            foreach ($data as $key => $v) {
              $refx = $this->coreFunctions->opentable("
                select c.alias, d.poref as ref ,abs(d.cr-d.db) as amt
                from ladetail as d 
                left join coa as c on c.acnoid = d.acnoid 
                left join transnum as t on t.trno = d.qttrno 
                where d.trno = " . $data[$key]['trno'] . " and d.cr<>0 
                union all 
                select c.alias , d.poref as ref, ar.bal as amt
                from gldetail as d 
                left join coa as c on c.acnoid = d.acnoid left join arledger as ar on ar.trno = d.trno and ar.line = d.line
                left join transnum as t on t.trno = d.qttrno 
                where d.trno = " . $data[$key]['trno'] . " and d.cr<>0 and ar.bal<>0"
              );
              if (!empty($refx)) {
                foreach ($refx as $x => $y) {
                  if ($refx[$x]->alias == 'AR5') {
                    $reportdata[$i]['trno'] = $data[$key]['trno'];
                    $reportdata[$i]['journalentry'] = $data[$key]['journalentry'];
                    $reportdata[$i]['crdate'] = $data[$key]['crdate'];
                    $reportdata[$i]['collectionreceipt'] = $data[$key]['collectionreceipt'];
                    $reportdata[$i]['clientname'] = $data[$key]['clientname'];
                    $reportdata[$i]['bankcharge'] = $data[$key]['bankcharge'];
                    $reportdata[$i]['netamt'] = $refx[$x]->amt;
                    $reportdata[$i]['paymentdetails'] = $data[$key]['paymentdetails'];
                    $reportdata[$i]['banktransfer'] = $data[$key]['banktransfer'];
                    $reportdata[$i]['datedeposited'] = $data[$key]['datedeposited'];
                    $reportdata[$i]['remarks'] = $data[$key]['remarks'];
                    $reportdata[$i]['cramt'] = $refx[$x]->amt;
                    $reportdata[$i]['lessewt'] = $data[$key]['lessewt'];
                    $reportdata[$i]['checkno'] = $data[$key]['checkno'];
                    $reportdata[$i]['ref'] = $data[$key]['ref'];
                    $reportdata[$i]['alias'] = $data[$key]['alias'];
                    $reportdata[$i]['refx'] = $data[$key]['refx'];
                    $reportdata[$i]['sinum'] = $refx[$x]->ref;
                    $reportdata[$i]['fines'] = $data[$key]['fines'];
                    $i += 1;
                    goto nextcr3;
                  }
                }
              }
              nextcr3:
            }
          break;
        case 'PDC':
          $reportdata = $data;
          break;
        default:
          $reportdata = $data;
          break;
      }
    }

    return $reportdata;
  }

  private function displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $report     = $config['params']['dataparams']['reporttype'];

    if ($client == '') {
      $client = 'ALL';
    }

    switch ($report) {
      case 'SI':
        $report = 'With SI';
        break;
      case 'CD':
        $report = 'Customer Deposits';
        break;
      case 'PDC':
        $report = 'PDC';
        break;

      default:
        $report = 'ALL';
        break;
    }

    $str = '';
    $layoutsize = '1400';
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
    $str .= $this->reporter->col('COLLECTION REPORT', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer: ' . $client, null, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Report Type: ' . $report, null, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Journal Entry', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CR Date', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Collection Receipt', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SI #/ Ref. #', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '350', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CR Amount', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Less EWT', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Bank Charge', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Fines', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Net Amount', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payment Details', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Bank Deposited/ Transfer', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date Deposited', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 10;
    $page = 10;
    $layoutsize = '1400';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $bankcharge = 0;
    $fines = 0;
    $bank = '';
    $report = $config['params']['dataparams']['reporttype'];
    $totalcramt = 0;
    $totalnetamt = 0;
    $netamt = 0;

    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
    foreach ($result as $key => $data) {

      $siref = $this->coreFunctions->datareader("
    select value from (select group_concat(case left(ref,2) when 'DR' then concat('SI',right(s.ref,6))
      when 'AR' then s.rem
      else concat(left(s.ref,3),right(s.ref,5)) end separator '/') as value
    from gldetail as s left join coa on coa.acnoid = s.acnoid
    where s.trno = " . $result[$key]['trno'] . " and s.refx<>0 and coa.alias <> 'AR5'
    union all
    select group_concat(case left(ref,2) when 'DR' then concat('SI',right(s.ref,6)) 
    when 'AR' then s.rem
    else concat(left(s.ref,3),right(s.ref,5)) end separator '/') as value
    from ladetail as s 
    left join coa as coa on coa.acnoid = s.acnoid
    where s.trno =" . $result[$key]['trno'] . " and s.refx<>0 and coa.alias <> 'AR5') as a where value is not null
    ");

      if ($siref == '') {
        $siref = $this->coreFunctions->datareader("select case d.qttrno when 0 then d.poref else qthead.yourref end as value from ladetail as d 
      left join coa as c on c.acnoid = d.acnoid
      left join (select h.docno,h.due,h.yourref,h.trno from qshead as h left join terms on terms.terms = h.terms  union all select h.docno,h.due,h.yourref,h.trno from hqshead as h left join terms on terms.terms = h.terms ) as qthead on qthead.trno = d.qttrno and d.qttrno <>0
      where d.trno = " . $result[$key]['trno'] . " and d.cr<>0 and c.alias = 'AR5'
      union all 
      select case d.qttrno when 0 then d.poref else qthead.yourref end as value from gldetail as d 
      left join coa as c on c.acnoid = d.acnoid
      left join (select h.docno,h.due,h.yourref,h.trno from qshead as h left join terms on terms.terms = h.terms  union all select h.docno,h.due,h.yourref,h.trno from hqshead as h left join terms on terms.terms = h.terms ) as qthead on qthead.trno = d.qttrno and d.qttrno <>0
      where d.trno = " . $result[$key]['trno'] . " and d.cr<>0 and c.alias = 'AR5'");
      }

      $siref = $siref != '' ? $siref : '';

      $lessewt = $this->coreFunctions->datareader("
    select value from (select sum(s.db) as value 
    from gldetail as s
    left join coa as coa on coa.acnoid = s.acnoid
    where s.trno = '" . $result[$key]['trno'] . "' and coa.alias in ('WT2','ARWT')
    union all
    select sum(s.db) as value 
    from ladetail as s
    left join coa as coa on coa.acnoid = s.acnoid
    where s.trno = '" . $result[$key]['trno'] . "' and coa.alias in ('WT2','ARWT')) as a where value is not null");

      $bankcharge = $this->coreFunctions->datareader("
    select value from (select sum(s.db-s.cr) as value 
    from gldetail as s
    left join coa as coa on coa.acnoid = s.acnoid
    where s.trno = '" . $result[$key]['trno'] . "' and coa.alias = 'BC1'
    union all
    select sum(s.db-s.cr) as value 
    from ladetail as s
    left join coa as coa on coa.acnoid = s.acnoid
    where s.trno = '" . $result[$key]['trno'] . "' and coa.alias = 'BC1') as a where value is not null");

      $fines = $this->coreFunctions->datareader("
    select value from (select sum(s.db-s.cr) as value 
    from gldetail as s
    left join coa as coa on coa.acnoid = s.acnoid
    where s.trno = '" . $result[$key]['trno'] . "' and coa.alias = 'PT1'
    union all
    select sum(s.db-s.cr) as value 
    from ladetail as s
    left join coa as coa on coa.acnoid = s.acnoid
    where s.trno = '" . $result[$key]['trno'] . "' and coa.alias = 'PT1') as a where value is not null");

      $banktransfer = $this->coreFunctions->datareader("select coa.acnoname as value
              from glhead as head
              left join gldetail as detail on detail.trno = head.trno
              left join coa as coa on coa.acno = head.contra
              where detail.refx = '" . $result[$key]['trno'] . "' and head.doc ='DS'
              union all
              select coa.acnoname as value
              from lahead as head
              left join ladetail as detail on detail.trno = head.trno
              left join coa as coa on coa.acno = head.contra
              where detail.refx = '" . $result[$key]['trno'] . "' and head.doc ='DS'
              union all 
              select (select coa.acnoname from gldetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='ARB') as value
              from glhead as head
              left join gldetail as detail on detail.trno = head.trno
              where detail.refx = '" . $result[$key]['trno'] . "' and head.doc ='GJ'
              union all
              select (select coa.acnoname from ladetail as d left join coa on coa.acnoid = d.acnoid where d.trno = head.trno and coa.alias ='ARB') as value
              from lahead as head
              left join ladetail as detail on detail.trno = head.trno
              where detail.refx = '" . $result[$key]['trno'] . "' and head.doc ='GJ' limit 1");

      $banktransfer = $result[$key]['banktransfer'] != '' ? $result[$key]['banktransfer'] : $banktransfer;
      $cramt = $result[$key]['cramt'] + $bankcharge;
      $netamt = $result[$key]['cramt'];

      $crdate = date("m/d/Y", strtotime($result[$key]['crdate']));
      $datedeposited = date("m/d/Y", strtotime($result[$key]['datedeposited']));

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($result[$key]['journalentry'], '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($crdate, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($result[$key]['collectionreceipt'], '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($siref, '80', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($result[$key]['clientname'], '350', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($cramt, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');


      $str .= $this->reporter->col($lessewt == 0 ? '-' : number_format($lessewt, 2), '80', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($bankcharge == 0 ? '-' : number_format($bankcharge, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($fines == 0 ? '-' : number_format($fines, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($netamt, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($result[$key]['paymentdetails'], '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($banktransfer, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($datedeposited, '70', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($result[$key]['remarks'], '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();


      $totalcramt = $totalcramt + $cramt;
      $totalnetamt = $totalnetamt + $netamt;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL', '350', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalcramt, 2), '100', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalnetamt, 2), '100', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', '', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
  public function displayHeader_Afli($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];

    if ($client == '') {
      $client = 'ALL';
    }

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COLLECTION REPORT', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '410', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('For the Period Beginning:', '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('' . date('m/d/Y', strtotime($start)), '80', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Ending', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('' . date('m/d/Y', strtotime($end)), '80', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '410', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Receipt Date', '80', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Receipt No.', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Customer's Name", '370', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Principal', '80', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Interest', '80', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Processing Fee', '100', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notarial Fee', '80', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Penalty', '80', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RPT', '80', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total', '100', null, false, $border, 'TBRL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  // Para sa New Report
  public function displayHeader_SBC($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];

    if ($client == '') {
      $client = 'ALL';
    }

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COLLECTION REPORT', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '410', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('For the Period Beginning:', '170', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('' . date('m/d/Y', strtotime($start)), '80', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Ending', '50', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('' . date('m/d/Y', strtotime($end)), '80', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '410', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Document No.', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Center', '210', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Client Name", '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Particular", '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function report_Afli_Layout($config)
  {
    $result = $this->afli_query($config);
    $dataparams = $config['params']['dataparams'];
    $font = $this->companysetup->getrptfont($config['params']);
    $count = 25;
    $page = 25;
    $layoutsize = '1200';
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '5px;margin-top:10px;');
    $str .= $this->displayHeader_Afli($config);



    $total = 0;
    $gtotal = 0;
    //total 
    $totalprin = 0;
    $totalint = 0;
    $totalpro = 0;
    $totalpenal = 0;

    // total cash
    $totalcprint = 0;
    $totalcint = 0;
    $totalcprofe = 0;
    $totalcpen = 0;
    $totalccash = 0;

    // total non cash
    $totalncprint = 0;
    $totalncint = 0;
    $totalncprofe = 0;
    $totalncpen = 0;
    $totalncash = 0;
    $str .= $this->reporter->begintable($layoutsize);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->docno, '150', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('' . $data->clientname, '370', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('' . number_format($data->principal, 2), '80', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('' . number_format($data->interest, 2), '80', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $profee = $data->profee != 0 ? $data->profee / 2 : 0;
      $str .= $this->reporter->col('' . number_format($profee, 2), '100', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('' . number_format($profee, 2), '80', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('' . number_format($data->penalty, 2), '80', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('-', '80', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '');
      $total = $data->principal + $data->interest + $data->profee + $data->penalty;
      $str .= $this->reporter->col('' . number_format($total, 2), '100', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $totalprin += $data->principal;
      $totalint += $data->interest;
      $totalpro += $data->profee;
      $totalpenal += $data->penalty;
      $gtotal += $total;

      if ($data->pmode == 'Cash') {
        // cash
        $totalcprint += $data->principal;
        $totalcint += $data->interest;
        $totalcprofe  += $data->profee;
        $totalcpen += $data->penalty;
      } else {
        // Non- Cash 
        $totalncprint += $data->principal;
        $totalncint += $data->interest;
        $totalncprofe += $data->profee;
        $totalncpen += $data->penalty;
      }

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        // $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        // if (!$isfirstpageheader) $str .= $this->displayHeader($config, count($result));
        // $str .= $this->displayHeadertable($config);
        $str .= $this->displayHeader_Afli($config);
        $str .= $this->reporter->begintable($layoutsize);


        // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '5px;margin-top:10px;');


        $page = $page + $count;
      }
    }
    $totalccash += ($totalcprint + $totalcint + $totalcprofe + $totalcpen);
    $totalncash += ($totalncprint + $totalncint + $totalncprofe + $totalncpen);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Collection', '370', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalprin, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalint, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $totalpro = $totalpro != 0 ? $totalpro / 2 : 0;
    $str .= $this->reporter->col('' . number_format($totalpro, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalpro, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalpenal, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('-', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('' . number_format($gtotal, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // Non - Cash
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Non - Cash Collection', '370', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalncprint, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalncint, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $totalncprofe = $totalncprofe != 0 ? $totalncprofe / 2 : 0;
    $str .= $this->reporter->col('' . number_format($totalncprofe, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalncprofe, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalncpen, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('-', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('' . number_format($totalncash, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    // Cash 
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total Cash Collection', '370', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalcprint, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalcint, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $totalcprofe = $totalcprofe != 0 ? $totalcprofe / 2 : 0;
    $str .= $this->reporter->col('' . number_format($totalcprofe, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalcprofe, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . number_format($totalcpen, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('-', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('' . number_format($totalccash, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $config['params']['doc'] = $this->modulename;
    if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'posted', $dataparams['approved']);
    if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Prepared by: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . $config['params']['dataparams']['prepared'], '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Checked by: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . $config['params']['dataparams']['checked'], '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Posted By: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('' . $config['params']['dataparams']['approved'], '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }

  // Para sa New Report
  public function report_SBC_Layout($config)
  {
    $result = $this->sbc_query($config);
    $dataparams = $config['params']['dataparams'];
    $font = $this->companysetup->getrptfont($config['params']);
    $count = 25;
    $page = 25;
    $layoutsize = '1200';
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '5px;margin-top:10px;');
    $str .= $this->displayHeader_SBC($config);

    $str .= $this->reporter->begintable($layoutsize);
    $currentMonth = '';

    // Month subtotal
    $subamount = $amount = 0;

    foreach ($result as $key => $data) {

      // Month-Year format 
      // $monthYear = date('F Y', strtotime($data->dateid));
      $monthYear = $data->monthlabel;

      // Kapag magpapalit ng month, print month header
      if ($currentMonth !== '' && $currentMonth !== $monthYear) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($currentMonth . ' - SUB TOTAL', '850', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->col(number_format($subamount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $subamount = 0;
      }
      if ($currentMonth !== $monthYear) {
        $currentMonth = $monthYear;

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($currentMonth, '120', null, false, $border, '', 'L', $font, $fontsize, 'B');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }

      // Compute subtotal per month
      $subamount += $data->amount;

      //  Transaction row
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '120', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize);
      $str .= $this->reporter->col($data->name, '210', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col($data->rem, '300', null, false, $border, '', 'L', $font, $fontsize);
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize);
      $str .= $this->reporter->endtable();

      // Page Break 
      if ($this->reporter->linecounter >= $page) {

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        $str .= $this->displayHeader_SBC($config);
        $str .= $this->reporter->begintable($layoutsize);

        $page += $count;
      }
    }
    // Last Month 
    if ($currentMonth !== '') {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($currentMonth . ' - SUB TOTAL', '850', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->col(number_format($subamount, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }



    // $totalccash += ($totalcprint + $totalcint + $totalcprofe + $totalcpen);
    // $totalncash += ($totalncprint + $totalncint + $totalncprofe + $totalncpen);
    $str .= $this->reporter->endtable();


    // For footer but not needed for now
    // $str .= '<br/><br/>';
    // $config['params']['doc'] = $this->modulename;
    // if (isset($dataparams['approved'])) $this->othersClass->writeSignatories($config, 'posted', $dataparams['approved']);
    // if (isset($dataparams['checked'])) $this->othersClass->writeSignatories($config, 'checked', $dataparams['checked']);

    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('Prepared by: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('' . $config['params']['dataparams']['prepared'], '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('Checked by: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('' . $config['params']['dataparams']['checked'], '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '50', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('Posted By: ', '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('' . $config['params']['dataparams']['approved'], '150', null, false, $border, 'B', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();


    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class