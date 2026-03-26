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

class material_issuance_report
{
  public $modulename = 'Material Issuance Report';
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
    $fields = ['radioprint', 'start', 'end'];

    if ($companyid == 19) { //housegem
      array_push($fields, 'truck', 'dcentername', 'reportusers', 'approved');
      $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'truck.name', 'clientname');
      data_set($col1, 'truck.lookupclass', 'lookupmitruck');
      data_set($col1, 'truck.required', false);
    } else {
      array_push($fields, 'dclientname', 'dcentername', 'reportusers', 'approved');

      if ($companyid == 8) { //maxipro
        array_push($fields, 'project', 'subprojectname');
      }
      if ($companyid == 43) { //mighty
        array_push($fields, 'dprojectname');
      }
      $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'dclientname.lookupclass', 'wasupplier');
      data_set($col1, 'dclientname.label', 'Customer');
      if ($companyid == 8) { //maxipro
        data_set($col1, 'project.name', "projectname");
        data_set($col1, 'subprojectname.type', "lookup");
        data_set($col1, 'subprojectname.action', "lookupsubproject");
        data_set($col1, 'subprojectname.addedparams', ['projectid']);
        data_set($col1, 'subprojectname.lookupclass', 'default');
        data_set($col1, 'subprojectname.required', false);
        data_set($col1, 'project.required', false);
      }
    }

    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'reportusers.lookupclass', 'user');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);
    data_set($col1, 'dcentername.required', true);

    $fields = ['radioposttype', 'radioreporttype', 'radiosorting'];

    if ($config['params']['companyid'] == 19) { //housegem
      array_push($fields, 'radioitemformat');
    }
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
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    // $plotting = array('truckid' => 'clientid', 'client' => 'client', 'clientname' => 'clientname');


    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 'default' as print,adddate(left(now(),10),-360) as start,
                  left(now(),10) as end,'' as client,'0' as clientid,'' as clientname,
                  '' as userid,'' as username,'' as approved,'0' as posttype,'0' as reporttype, 
                  'ASC' as sorting,'" . $defaultcenter[0]['center'] . "' as center,
                  '" . $defaultcenter[0]['centername'] . "' as centername,
                  '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                  '' as dclientname,'' as reportusers,'All' as radioitemformat,
                  '' as truck,'' as truckid,'0' as subproject,'' as subprojectname,'' as project,
                  '' as projectcode,'0' as projectid,'' as projectname");
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
    $companyid = $config['params']['companyid'];
    $username = $config['params']['user'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($companyid) {
      case 28: //xcomp
        switch ($reporttype) {
          case 0:
            $result = $this->xcomp_Layout_SUMMARIZED($config);
            break;

          case 1:
            $result = $this->xcomp_Layout_DETAILED($config);
            break;
        }
        break;
      case 19: //housegem
        switch ($reporttype) {
          case 0:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;

          case 1:
            $result = $this->report_housegem_Layout_DETAILED($config);
            break;
        }
        break;

      default:
        switch ($reporttype) {
          case 0:
            $result = $this->reportDefaultLayout_SUMMARIZED($config);
            break;

          case 1:
            $result = $this->reportDefaultLayout_DETAILED($config);
            break;
        }
        break;
    }


    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 19: //housegem
        $query = $this->housegem_QUERY($config);
        break;
      default:
        $query = $this->default_QUERY($config);
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function housegem_QUERY($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $reporttype = $config['params']['dataparams']['reporttype'];
    $sorting    = $config['params']['dataparams']['sorting'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $fcenter    = $config['params']['dataparams']['center'];
    $filter = "";

    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['truckid'];
    $reportiformat = $config['params']['dataparams']['radioitemformat'];
    if ($reportiformat == 'NInv') {
      $filter .= " and item.isnoninv = 1";
    }



    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cnt.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cnt.center = '$fcenter'";
    }

    $addfield = "";
    $addjoin = "";
    $groupby = "";


    $addfields = "";
    $addfieldd = "";


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                      wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref $addfields  " . $addfield . "
                      from glhead as head 
                      left join glstock as stock on stock.trno = head.trno 
                      left join item as item on item.itemid = stock.itemid 
                      left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
                      left join client as client on client.clientid = head.clientid  
                      left join client as wh on wh.clientid = stock.whid 
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname,
                      wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
                      order by head.docno $sorting";
            break;

          case 1: // unposted
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                      wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref $addfields " . $addfield . "
                      from lahead as head
                      left join lastock as stock on stock.trno = head.trno
                      left join client as client on client.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
                      left join client as wh on wh.clientid = stock.whid
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname,
                      wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
                      order by head.docno $sorting";
            break;

          default: // all
            $query = "select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                      wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref  $addfields " . $addfield . "
                      from lahead as head
                      left join lastock as stock on stock.trno = head.trno
                      left join client as client on client.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
                      left join client as wh on wh.clientid = stock.whid
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter
                      group by head.docno, head.clientname,
                      wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
                      union all
                      select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
                      wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref $addfields   " . $addfield . "
                      from glhead as head 
                      left join glstock as stock on stock.trno = head.trno 
                      left join item as item on item.itemid = stock.itemid 
                      left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
                      left join client as client on client.clientid = head.clientid  
                      left join client as wh on wh.clientid = stock.whid 
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
                      group by head.docno, head.clientname,
                      wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
                      order by docno $sorting";
            break;
        } // end switch posttype

        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "select head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref, 
                      head.yourref, concat(coa.acno,'-',coa.acnoname) as account,
                      item.barcode, item.itemname, uom.uom, wh.clientname as wh, 
                      stock.isqty, stock.amt, stock.ext, stock.disc, stock.rem ,stock.iss,stock.isamt,stock.loc,
                      (select group_concat(c.clientname SEPARATOR ', ')
                      from hcntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=0 ) as customer,
                      (select group_concat(c.clientname SEPARATOR ', ')
                      from hcntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=1 ) as helper,d.clientname as driver
                      $addfieldd " . $addfield . "
                      from glhead as head 
                      left join glstock as stock on stock.trno = head.trno 
                      left join item as item on item.itemid = stock.itemid 
                      left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
                      left join client as client on client.clientid = head.clientid  
                      left join client as wh on wh.clientid = stock.whid 
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                      left join hcntnuminfo as num on num.trno=head.trno
                      left join client as d on d.clientid=num.driverid
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
                      order by head.docno $sorting";
            break;

          case 1: // unposted
            $query = "select
                      head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref,
                      head.yourref, concat(coa.acno,'-',coa.acnoname) as account,
                      item.barcode, stock.isqty, stock.amt, stock.ext, stock.disc, item.itemname, stock.rem,
                      uom.uom, wh.clientname,stock.iss,stock.isamt,stock.loc,wh.clientname as wh,
                      (select group_concat(c.clientname SEPARATOR ', ')
                      from cntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=0 ) as customer,
                      (select group_concat(c.clientname SEPARATOR ', ')
                      from cntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=1 ) as helper,d.clientname as driver
                      $addfieldd " . $addfield . "
                      from lahead as head
                      left join lastock as stock on stock.trno = head.trno
                      left join client as client on client.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
                      left join client as wh on wh.clientid = stock.whid
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                      left join cntnuminfo as num on num.trno=head.trno
                      left join client as d on d.clientid=num.driverid
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
                      order by head.docno $sorting";
            break;

          default: // all
            $query = "select head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref, 
                      head.yourref, concat(coa.acno,'-',coa.acnoname) as account,
                      item.barcode, item.itemname, uom.uom, wh.clientname as wh, 
                      stock.isqty, stock.amt, stock.ext, stock.disc, stock.rem ,stock.iss,stock.isamt,stock.loc,
                      (select group_concat(c.clientname SEPARATOR ', ')
                      from hcntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=0 ) as customer,
                      (select group_concat(c.clientname SEPARATOR ', ')
                      from hcntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=1 ) as helper,d.clientname as driver
                      
                      $addfieldd " . $addfield . "
                      from glhead as head 
                      left join glstock as stock on stock.trno = head.trno 
                      left join item as item on item.itemid = stock.itemid 
                      left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
                      left join client as client on client.clientid = head.clientid  
                      left join client as wh on wh.clientid = stock.whid 
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                      left join hcntnuminfo as num on num.trno=head.trno
                      left join client as d on d.clientid=num.driverid
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
                      union all
                      select
                      head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref,
                      head.yourref,concat(coa.acno,'-',coa.acnoname) as account,
                      item.barcode,item.itemname,uom.uom, wh.clientname as wh,
                      stock.isqty, stock.amt, stock.ext, stock.disc, stock.rem, stock.iss,stock.isamt,stock.loc,
                     
                     (select group_concat(c.clientname SEPARATOR ', ')
                      from cntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=0 ) as customer,
                      (select group_concat(c.clientname SEPARATOR ', ')
                      from cntclient as h
                      left join client as c on c.clientid=h.clientid
                      where h.trno=head.trno and h.ishelper=1 ) as helper,d.clientname as driver
                      $addfieldd " . $addfield . "
                      from lahead as head
                      left join lastock as stock on stock.trno = head.trno
                      left join client as client on client.client = head.client
                      left join item as item on item.itemid = stock.itemid
                      left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
                      left join client as wh on wh.clientid = stock.whid
                      left join cntnum as cnt on cnt.trno = head.trno
                      left join coa on coa.acno = head.contra
                      left join cntnuminfo as num on num.trno=head.trno
                      left join client as d on d.clientid=num.driverid
                        " . $addjoin . "
                      where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
                      order by docno $sorting";
            break;
        }
        break;
    }

    return $query;
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

    if ($config['params']['companyid'] == 19) { //housegem
      $client     = $config['params']['dataparams']['client'];
      $clientid     = $config['params']['dataparams']['truckid'];
      $reportiformat = $config['params']['dataparams']['radioitemformat'];
      if ($reportiformat == 'NInv') {
        $filter .= " and item.isnoninv = 1";
      }
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      $project = $config['params']['dataparams']['project'];
      $projectid = $config['params']['dataparams']['projectid'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];
      $subprojectid = $config['params']['dataparams']['subproject'];

      if ($project != "") {
        $filter .= " and head.projectid = '" . $projectid . "' ";
      }

      if ($subprojectname != "") {
        $filter .= " and sp.line  = $subprojectid";
      }
    }

    if ($config['params']['companyid'] == 43) { // mighty
      $project = $config['params']['dataparams']['project'];
      $projectid = $config['params']['dataparams']['projectid'];

      if ($project != "") {
        $filter .= " and head.projectid = '" . $projectid . "' ";
      }
    }



    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($prefix != "") {
      $filter .= " and cnt.bref = '$prefix' ";
    }
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }
    if ($fcenter != "") {
      $filter .= " and cnt.center = '$fcenter'";
    }

    $addfield = "";
    $addjoin = "";
    $groupby = "";


    $addfields = "";
    $addfieldd = "";

    if ($this->companysetup->isconstruction($config['params'])) {
      $addfield = ",ifnull(project.name,'') as projectname, ph.code as phase, 
      hm.model as housemodel,bl.blk as blklot, bl.lot,cohead.docno as codocno";
      $addjoin = "left join projectmasterfile as project on project.line=head.projectid
                  left join phase as ph on ph.line = head.phaseid
                  left join housemodel as hm on hm.line = head.modelid
                  left join blklot as bl on bl.line = head.blklotid
                  left join hcohead as cohead on cohead.trno = head.cotrno";
      $groupby = ",project.name,ph.code,hm.model,bl.blk,bl.lot,cohead.docno";
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      $addfields = " ,ifnull(group_concat(distinct stock.ref SEPARATOR '\n'),'') as ref ";
      $addfieldd = " , stock.ref";
      $addjoin .= "left join projectmasterfile as proj on proj.line=head.projectid
                  left join subproject as sp on sp.projectid = proj.line and sp.line=head.subproject ";
    }
    if ($config['params']['companyid'] == 43) { //mighty
      $addjoin .= "left join projectmasterfile as proj on proj.line=head.projectid";
    }


    switch ($reporttype) {
      case 0: // summarized
        switch ($posttype) {
          case 0: // posted
            $query = "
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref $addfields  " . $addfield . "
          from glhead as head 
          left join glstock as stock on stock.trno = head.trno 
          left join item as item on item.itemid = stock.itemid 
          left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
          left join client as client on client.clientid = head.clientid  
          left join client as wh on wh.clientid = stock.whid 
          left join cntnum as cnt on cnt.trno = head.trno
          left join coa on coa.acno = head.contra
            " . $addjoin . "
          where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
          order by head.docno $sorting";
            break;

          case 1: // unposted
            $query = "
          select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref $addfields " . $addfield . "
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join client as client on client.client = head.client
          left join item as item on item.itemid = stock.itemid
          left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
          left join client as wh on wh.clientid = stock.whid
          left join cntnum as cnt on cnt.trno = head.trno
          left join coa on coa.acno = head.contra
            " . $addjoin . "
          where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
          order by head.docno $sorting";
            break;

          default: // all
            $query = "
          select 'UNPOSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref  $addfields " . $addfield . "
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join client as client on client.client = head.client
          left join item as item on item.itemid = stock.itemid
          left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
          left join client as wh on wh.clientid = stock.whid
          left join cntnum as cnt on cnt.trno = head.trno
          left join coa on coa.acno = head.contra
            " . $addjoin . "
          where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
          union all
          select 'POSTED' as status, head.docno, head.clientname as supplier,sum(stock.ext) as ext,
          wh.clientname, head.createby, left(head.dateid,10) as dateid,head.yourref,head.ourref $addfields   " . $addfield . "
          from glhead as head 
          left join glstock as stock on stock.trno = head.trno 
          left join item as item on item.itemid = stock.itemid 
          left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
          left join client as client on client.clientid = head.clientid  
          left join client as wh on wh.clientid = stock.whid 
          left join cntnum as cnt on cnt.trno = head.trno
          left join coa on coa.acno = head.contra
            " . $addjoin . "
          where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
          group by head.docno, head.clientname,
          wh.clientname, head.createby, head.dateid,head.yourref,head.ourref " . $groupby . "
          order by docno $sorting";
            break;
        } // end switch posttype

        break;

      case 1: // detailed
        switch ($posttype) {
          case 0: // posted
            $query = "
          select
          head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref, 
          head.yourref, concat(coa.acno,'-',coa.acnoname) as account,
          item.barcode, item.itemname, uom.uom, wh.clientname as wh, 
          stock.isqty, stock.amt, stock.ext, stock.disc, stock.rem ,stock.iss,stock.isamt,stock.loc $addfieldd " . $addfield . "
          from glhead as head 
          left join glstock as stock on stock.trno = head.trno 
          left join item as item on item.itemid = stock.itemid 
          left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
          left join client as client on client.clientid = head.clientid  
          left join client as wh on wh.clientid = stock.whid 
          left join cntnum as cnt on cnt.trno = head.trno
          left join coa on coa.acno = head.contra
            " . $addjoin . "
          where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
          order by head.docno $sorting";
            break;

          case 1: // unposted
            $query = "
          select
          head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref,
          head.yourref, concat(coa.acno,'-',coa.acnoname) as account,
          item.barcode, stock.isqty, stock.amt, stock.ext, stock.disc, item.itemname, stock.rem,
          uom.uom, wh.clientname,stock.iss,stock.isamt,stock.loc,wh.clientname as wh $addfieldd " . $addfield . "
          from lahead as head
          left join lastock as stock on stock.trno = head.trno
          left join client as client on client.client = head.client
          left join item as item on item.itemid = stock.itemid
          left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
          left join client as wh on wh.clientid = stock.whid
          left join cntnum as cnt on cnt.trno = head.trno
          left join coa on coa.acno = head.contra
            " . $addjoin . "
          where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
          order by head.docno $sorting";
            break;

          default: // all
            $query = "
            select
            head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref, 
            head.yourref, concat(coa.acno,'-',coa.acnoname) as account,
            item.barcode, item.itemname, uom.uom, wh.clientname as wh, 
            stock.isqty, stock.amt, stock.ext, stock.disc, stock.rem ,stock.iss,stock.isamt,stock.loc $addfieldd " . $addfield . "
            from glhead as head 
            left join glstock as stock on stock.trno = head.trno 
            left join item as item on item.itemid = stock.itemid 
            left join uom as uom on stock.uom = uom.uom and item.itemid = uom.itemid 
            left join client as client on client.clientid = head.clientid  
            left join client as wh on wh.clientid = stock.whid 
            left join cntnum as cnt on cnt.trno = head.trno
            left join coa on coa.acno = head.contra
              " . $addjoin . "
            where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
            union all
            select
            head.trno, head.docno, client.clientname, date(head.dateid) as dateid, head.ourref,
            head.yourref,concat(coa.acno,'-',coa.acnoname) as account,
            item.barcode,item.itemname,uom.uom, wh.clientname as wh,
            stock.isqty, stock.amt, stock.ext, stock.disc, stock.rem, stock.iss,stock.isamt,stock.loc $addfieldd " . $addfield . "
            from lahead as head
            left join lastock as stock on stock.trno = head.trno
            left join client as client on client.client = head.client
            left join item as item on item.itemid = stock.itemid
            left join uom as uom on uom.uom = stock.uom and item.itemid = uom.itemid
            left join client as wh on wh.clientid = stock.whid
            left join cntnum as cnt on cnt.trno = head.trno
            left join coa on coa.acno = head.contra
              " . $addjoin . "
            where head.doc = 'MI' and date(head.dateid) between '$start' and '$end' $filter 
            order by docno $sorting
          ";
            break;
        }
        break;
    }

    return $query;
  }

  public function xcomp_header_DEFAULT($config)
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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Material Issuance Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  public function xcomp_tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '50', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CUSTOMER', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OURREF', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function xcomp_Layout_SUMMARIZED($config)
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
    $str .= $this->xcomp_header_DEFAULT($config);
    $str .= $this->xcomp_tableheader($layoutsize, $config);


    $totalext = 0;
    $totalbal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '50', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->supplier, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->createby, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), '75', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->status, '75', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $totalext = $totalext + $data->ext;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->xcomp_header_DEFAULT($config);
          $str .= $this->xcomp_tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '50', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalext, 2), '75', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function xcomp_Layout_DETAILED($config)
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
    $str .= $this->xcomp_header_DEFAULT($config);
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
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Yourref: ' . $data->yourref, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Ourref: ' . $data->ourref, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
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
    if ($this->companysetup->isconstruction($config['params'])) {
      $layoutsize = '1500';
    } else {
      $layoutsize = '1000';
    }

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
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '5px', '8px');
          $str .= $this->reporter->endrow();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Doc#: ' . $data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->col('Date: ' . $data->dateid, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          $str .= $this->reporter->endrow();


          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Customer: ' . $data->clientname, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          if ($config['params']['companyid'] == 49) { //hotmix
            $str .= $this->reporter->col('Yourref: ' . $data->yourref, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
            $str .= $this->reporter->col('Ourref: ' . $data->ourref, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($this->companysetup->isconstruction($config['params'])) {
            $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Item Description', '170', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

            $str .= $this->reporter->col('Project Name', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Phase', '120', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('House Model', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Block & Lot', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          } else {
            $str .= $this->reporter->col('Barcode', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Item Description', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Quantity', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('UOM', '60', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Discount', '70', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total Price', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Warehouse', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            if ($config['params']['companyid'] == 8) { //maxipro
              $str .= $this->reporter->col('Reference', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            } else {
              $str .= $this->reporter->col('Location', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
            }
            $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($this->companysetup->isconstruction($config['params'])) {
          $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '170', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->iss, 2), '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->clientname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');

          $str .= $this->reporter->col($data->projectname, '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->phase, '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->housemodel, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->blklot . ' - ' . $data->lot, '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        } else {
          $str .= $this->reporter->col($data->barcode, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->itemname, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->iss, 2), '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->uom, '60', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->isamt, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->disc, '70', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
          $str .= $this->reporter->col($data->wh, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
          if ($config['params']['companyid'] == 8) { //maxipro
            $str .= $this->reporter->col($data->ref, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          } else {
            $str .= $this->reporter->col($data->loc, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
          }

          $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }
        $str .= $this->reporter->endtable();

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          if ($this->companysetup->isconstruction($config['params'])) {
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '170', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '60', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '70', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '140', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('', '130', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col('Total', '140', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
            $str .= $this->reporter->col(number_format($total, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          } else {
            $str .= $this->reporter->col('Total: ' . number_format($total, 2), '600', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          }
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
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

    if ($this->companysetup->isconstruction($config['params'])) {
      if ($reporttype == 'Summarized') { //summarized
        $layoutsize = 1200;
      } else {
        $layoutsize = 1500; //detailed
      }
    } else {
      $layoutsize = 1000;

      if ($companyid == 19) { // housegem
        $layoutsize = 1800;
        if ($reporttype == 'Summarized') {
          $layoutsize = 1000;
        }
      }
    }

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
      $str .= $this->reporter->letterhead($center, $username, $config);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);

    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    if ($config['params']['companyid'] == 8) { //maxipro
      $projectname = $config['params']['dataparams']['projectname'];
      $subprojectname = $config['params']['dataparams']['subprojectname'];
      $client = $config['params']['dataparams']['client'];
      $clientname = $config['params']['dataparams']['clientname'];


      if ($projectname == '') {
        $projectname = 'ALL';
      }
      if ($subprojectname == '') {
        $subprojectname = 'ALL';
      }
      if ($client == '') {
        $client = 'ALL';
      } else {
        $client = $client . ' ~ ' . $clientname;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Material Issuance Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Sorting By: ' . $sorting, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    if ($config['params']['companyid'] == 8) { //maxipro
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Project: ' . $projectname, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub Project: ' . $subprojectname, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Customer: ' . $client, 1000, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
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

    $count = 38;
    $page = 40;

    $str = '';

    switch ($companyid) {
      case 49: //hotmix
      case 8: //maxipro
        $layoutsize = '1200';
        break;
      default:
        $layoutsize = '1000';
        break;
    }

    if ($this->companysetup->isconstruction($config['params'])) {
      // $layoutsize = $this->reportParams['layoutSize'];
      $layoutsize = '1200';
    }

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
        if ($this->companysetup->isconstruction($config['params'])) {
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->supplier, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->projectname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->phase, '130', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->housemodel, '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->blklot . ' - ' . $data->lot, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->createby, '90', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->status, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($data->dateid, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->supplier, '300', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');

          switch ($companyid) {
            case 49: //hotmix
              $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
              $str .= $this->reporter->col($data->ourref, '100', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
              break;

            case 8: //maxipro
              $str .= $this->reporter->col($data->ref, '150', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
              break;
          }
          $str .= $this->reporter->col($data->createby, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data->ext, 2), '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        }
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
    if ($this->companysetup->isconstruction($config['params'])) {
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '140', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL :', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '300', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      switch ($companyid) {
        case 49: //hotmix
          $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          break;

        case 8: //maxipro
          $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          break;
      }

      $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($totalext, 2), '150', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    }
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

    switch ($config['params']['companyid']) {
      case 49: //hotmix
      case 8: //maxipro
        $layoutsize = '1200';
        break;
      default:
        $layoutsize = '1000';
        break;
    }

    if ($this->companysetup->isconstruction($config['params'])) {
      $layoutsize = '1200';
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($this->companysetup->isconstruction($config['params'])) {
      $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CUSTOMER', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '110', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col('PROJECT NAME', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PHASE', '130', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('HOUSE MODEL', '140', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BLOCK & LOT', '150', null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '');

      $str .= $this->reporter->col('CREATE BY', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('STATUS', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('DATE', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CUSTOMER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DOCUMENT #', '120', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      switch ($config['params']['companyid']) {
        case 49: //hotmix
          $str .= $this->reporter->col('YOURREF', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('OURREF', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          break;
        case 8: //maxipro
          $str .= $this->reporter->col('REFERENCE', '150', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
          break;
      }

      $str .= $this->reporter->col('CREATE BY', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
  public function report_housegem_Layout_DETAILED($config)
  {
    $result = $this->reportDefault($config);

    $str = '';
    $layoutsize = 1800;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 9;
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    // $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->reporter->beginreport(1800, null, false,  false, '', '', '', '', '', '', '', '25px;', 'margin-top:5px;');
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
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('', 100, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 80, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 90, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 150, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 50, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 80, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 90, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 100, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 100, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 200, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 200, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), 140, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->endrow();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $total = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col('Doc#', 100, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Customer', 120, null, false, $border, 'B', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Date', 80, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Barcode', 90, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Item Description', 150, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Quantity', 60, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('UOM', 50, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Price', 60, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Discount', 60, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total Price', 80, null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Warehouse', 120, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Location', 90, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Notes', 100, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Yourref', 100, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Customer', 200, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Helper', 200, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Driver', 140, null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');

          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($data->docno, 100, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->clientname, 120, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->dateid, 80, null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->barcode, 90, null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->itemname, 150, null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->iss, 2), 60, null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->uom, 50, null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->isamt, 2), 60, null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->disc, 60, null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col(number_format($data->ext, 2), 80, null, false, $border, '', 'RT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->wh, 120, null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->loc, 90, null, false, $border, '', 'CT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->rem, 100, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->yourref, 100, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->customer, 200, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->helper, 200, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col($data->driver, 140, null, false, $border, '', 'LT', $font, $fontsize, '', '', '', '');

        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($docno == $data->docno) {
          $total += $data->ext;
        }

        $str .= $this->reporter->addline();
        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', 100, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 120, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 80, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 90, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 150, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 50, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 60, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 80, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 120, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 90, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 100, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 100, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 200, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', 200, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ' . number_format($total, 2), 140, null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class