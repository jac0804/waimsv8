<?php

namespace App\Http\Classes\modules\reportlist\check_monitoring_reports;

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
use DeepCopy\f001\B;

class issued_checks
{
  public $modulename = 'Issued Checks';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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

    switch ($companyid) {
      case 8: //maxipro
        $fields = ['start', 'end', 'dprojectname'];
        break;

      case 19: //housegem
        $fields = ['start', 'end', 'dcentername', 'dclientname'];
        break;
      default:
        $fields = ['start', 'end', 'dcentername'];
        break;
    }


    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', 'StartDate');
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.label', 'EndDate');
    data_set($col2, 'end.readonly', false);
    data_set($col2, 'dclientname.lookupclass', 'lookupgjclient');
    data_set($col2, 'dclientname.label', 'Supplier/Customer');

    $fields = ['radioposttype', 'radioreporttype'];

    if ($companyid == 55) { //afli
      if (($key = array_search('radioreporttype', $fields)) !== false) {
        unset($fields[$key]);
      }
      // data_set($col2, 'start.type', 'datetime');
      // data_set($col2, 'end.type', 'datetime');
    }
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'radioreporttype.label', 'Date based on:');
    if ($companyid != 55) { // not afli
      data_set(
        $col3,
        'radioreporttype.options',
        [
          ['label' => 'Transaction Date', 'value' => '0', 'color' => 'pink'],
          ['label' => 'Check Date', 'value' => '1', 'color' => 'pink']
        ]
      );
    }

    data_set(
      $col3,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    $fields = ['print'];
    $col4 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3, 'col4' => $col4);
  }

  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,
                            left(now(),10) as end,'" . $defaultcenter[0]['center'] . "' as center,
                            '" . $defaultcenter[0]['centername'] . "' as centername,
                            '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                            '0' as posttype,'' as dclientname,'' as client,'' as clientname,'0' as reporttype";
        break;
      case 8: //maxipro
        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,
                            left(now(),10) as end,'' as center,'' as centername,'' as dcentername,
                            '0' as posttype,'' as dclientname,'' as client,'' as clientname,
                            '0' as reporttype, '' as dprojectname , '' as projectname,'' as projectcode";
        break;
      default:
        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,
                            left(now(),10) as end,'' as center,'' as centername,'' as dcentername,
                            '0' as posttype,'' as dclientname,'' as client,'' as clientname,
                            '0' as reporttype";
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

    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $isposted = $filters['params']['dataparams']['posttype'];
    $checks = $filters['params']['dataparams']['reporttype'];
    $client = $filters['params']['dataparams']['client'];
    $center = $filters['params']['dataparams']['center'];
    $filter = "";

    if ($companyid == 8) { //maxipro
      $project = $filters['params']['dataparams']['dprojectname'];
      if ($project != "") {
        $projectid = $filters['params']['dataparams']['projectid'];
        $filter .= " and head.projectid= '" . $projectid . "' ";
      }
    } else {
      $center = $filters['params']['dataparams']['center'];
      if ($center != "") {
        $filter .= " and cntnum.center= '" . $center . "' ";
      }

      if ($client != "") {
        $filter .= " and client.client = '" . $client . "' ";
      }
    }

    if ($checks == 0) {
      $ch = "date(head.dateid)";
    } else {
      $ch = "date(detail.postdate)";
    } //end if

    switch ($companyid) {
      case 8: //maxipro
        switch ($isposted) {
          case 1:
            $query = "select detail.postdate as chkdate, head.client, head.clientname, head.docno,
                                head.dateid as trdate, detail.checkno as chkinfo, 
                                abs(detail.db - detail.cr) as amount,date(cntnum.postdate) as postdate,
                                head.projectid,proj.code as projcode, proj.name as projname,
                                case when head.paymode = 'D' then 'Debit'
                                when head.paymode = 'O' then 'Online'
                                when head.paymode = 'C' then 'Check' end as paymode
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                      left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                      left join projectmasterfile as proj on proj.line=head.projectid
                      where head.doc='cv' and (left(coa.alias, 2)='cb' or coa.alias='AP99')
                      and $ch between '" . $start . "' and '" . $end . "'  " . $filter . "
                      order by $ch,docno";

            break;
          //end case 

          case 0:
            $query = "select cr.checkdate AS chkdate, head.clientname, head.docno, head.dateid AS trdate,
                                cr.checkno AS chkinfo, ABS(cr.db-cr.cr) AS amount, client.client,
                                date(cntnum.postdate) as postdate,head.projectid,proj.code as projcode, 
                                proj.name as projname,case when head.paymode = 'D' then 'Debit Payment'
                                when head.paymode = 'O' then 'Online Payment'
                                when head.paymode = 'C' then 'Check Payment' end as paymode
                      FROM ((cbledger AS cr LEFT JOIN glhead AS head ON head.trno=cr.trno)
                      LEFT JOIN client ON client.clientid=head.clientid)LEFT JOIN cntnum ON cntnum.trno=cr.trno
                      LEFT JOIN gldetail AS detail ON head.trno = detail.trno
                      LEFT JOIN coa ON coa.acnoid = detail.acnoid
                      left join projectmasterfile as proj on proj.line=head.projectid
                      WHERE head.doc='cv' AND  $ch between '" . $start . "' AND '" . $end . "' " . $filter . "
                      AND (LEFT(coa.alias,2) = 'CB' or coa.alias='AP99')
                      ORDER BY $ch,docno";
            break;
        } // end switch
        break;


      case 19: //housegem
        switch ($isposted) {
          case 1:
            $query = "select detail.postdate as chkdate, head.client, head.clientname, head.docno,
                            head.dateid as trdate, detail.checkno as chkinfo, abs(detail.db - detail.cr) as amount,
                            date(cntnum.postdate) as postdate,head.projectid,proj.code as projcode, 
                            proj.name as projname,head.rem
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                      left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                      left join projectmasterfile as proj on proj.line=head.projectid
                      left join client on client.client = head.client
                      where head.doc='cv' and (left(coa.alias, 2)='cb' or coa.alias='AP99')
                      and $ch between '" . $start . "' and '" . $end . "'  " . $filter . "
                      order by chkinfo, docno";
            break;
          //end case 

          case 0:
            $query = "select detail.postdate AS chkdate, head.clientname, head.docno, head.dateid AS trdate,
                            detail.checkno AS chkinfo, ABS(detail.db-detail.cr) AS amount, client.client,
                            date(cntnum.postdate) as postdate,head.projectid,proj.code as projcode, 
                            proj.name as projname,head.rem
                      FROM gldetail AS detail LEFT JOIN glhead AS head ON head.trno=detail.trno
                      LEFT JOIN client ON client.clientid=head.clientid LEFT JOIN cntnum ON cntnum.trno=detail.trno
                      LEFT JOIN coa ON coa.acnoid = detail.acnoid
                      left join projectmasterfile as proj on proj.line=head.projectid
                      WHERE head.doc='cv' AND  $ch between '" . $start . "' AND '" . $end . "' " . $filter . "
                      AND (LEFT(coa.alias,2) = 'CB'  )
                      ORDER BY chkinfo, docno";
            break;
        } // end switch
        break;

      case 26: //bee healthy
        switch ($isposted) {
          case 1:
            $query = "select detail.postdate as chkdate, head.client, head.clientname, head.docno,
                              head.dateid as trdate, detail.checkno as chkinfo, abs(detail.db - detail.cr) as amount,
                              date(cntnum.postdate) as postdate,head.projectid,proj.code as projcode, 
                              proj.name as projname,coa.acnoname, coa.acno, client.tel, client.tel2, 
                              client.rem as pdetails2, head.rem as parti1,head.createby
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                      left join client on client.client=head.client
                      left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                      left join projectmasterfile as proj on proj.line=head.projectid
                      where head.doc='cv' and (left(coa.alias, 2)='cb' or coa.alias='AP99')
                      and $ch between '" . $start . "' and '" . $end . "'  " . $filter . "
                      order by chkinfo, docno";
            break;
          //end case 

          case 0:
            $query = "select cr.checkdate as chkdate, head.clientname, head.docno, head.dateid as trdate,
                            cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client,
                            date(cntnum.postdate) as postdate,head.projectid,proj.code as projcode, 
                            proj.name as projname,coa.acnoname, coa.acno, client.tel, client.tel2, 
                            client.rem as pdetails2, head.rem as parti1,head.createby
                      from ((cbledger as cr left join glhead as head on head.trno=cr.trno)
                      left join client on client.clientid=head.clientid)left join cntnum on cntnum.trno=cr.trno
                      left join gldetail as detail on head.trno = detail.trno
                      left join coa on coa.acnoid = detail.acnoid
                      left join projectmasterfile as proj on proj.line=head.projectid
                      where head.doc='cv' and  $ch between '" . $start . "' and '" . $end . "' " . $filter . "
                            and (left(coa.alias,2) = 'cb')
                      order by chkinfo, docno";
            break;
        } // end switch
        break;

      case 55: //afli
        switch ($isposted) {
          case 0: //posted
            $query = "select coa.acnoname,cr.checkdate AS chkdate,
            if(concat(info.fname,info.mname,info.lname) <> '',TRIM(concat(info.fname, ' ', info.mname, ' ', info.lname)),head.clientname) as clientname, head.docno, 
                            head.dateid AS trdate,cr.checkno AS chkinfo,(cr.db-cr.cr) AS amount, 
                            client.client,date(cntnum.postdate) as postdate,head.projectid,
                            proj.code as projcode, proj.name as projname,head.rem, head.yourref
                      FROM ((cbledger AS cr LEFT JOIN glhead AS head ON head.trno=cr.trno)
                      LEFT JOIN client ON client.clientid=head.clientid
                      left join clientinfo as info on info.clientid = client.clientid)
                      LEFT JOIN cntnum ON cntnum.trno=cr.trno
                      LEFT JOIN gldetail AS detail ON detail.trno = cr.trno and detail.line = cr.line
                      LEFT JOIN coa ON coa.acnoid = detail.acnoid
                      left join projectmasterfile as proj on proj.line=head.projectid
                      WHERE head.doc='cv' AND  date(cntnum.printcheck) between '" . $start . "' AND '" . $end . "' " . $filter . "
                      AND (LEFT(coa.alias,2) = 'CB')
                      order by chkdate, docno";
            break;
          case 1: //unposted
            $query = "select coa.acnoname,detail.postdate as chkdate, head.client, if(concat(info.fname,info.mname,info.lname) <> '',TRIM(concat(info.fname, ' ', info.mname, ' ', info.lname)),head.clientname) as clientname, 
                            head.docno,head.dateid as trdate, detail.checkno as chkinfo, 
                            (detail.db - detail.cr) as amount,date(cntnum.postdate) as postdate,
                            head.projectid,proj.code as projcode, proj.name as projname,head.rem,head.yourref
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno
                      left join client on client.client=head.client
                      left join clientinfo as info on info.clientid = client.clientid)
                      left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                      left join projectmasterfile as proj on proj.line=head.projectid
                      where head.doc='cv' and (left(coa.alias, 2)='cb' or coa.alias='AP99')
                      and date(cntnum.printcheck) between '" . $start . "' and '" . $end . "'  " . $filter . "
                      order by chkdate, docno";
            break;
          //end case 
          case 2: //all 
            $query = "select coa.acnoname,cr.checkdate AS chkdate, if(concat(info.fname,info.mname,info.lname) <> '',TRIM(concat(info.fname, ' ', info.mname, ' ', info.lname)),head.clientname) as clientname, head.docno, 
                            head.dateid AS trdate,cr.checkno AS chkinfo, (cr.db-cr.cr) AS amount, 
                            client.client,date(cntnum.postdate) as postdate,head.projectid,
                            proj.code as projcode, proj.name as projname,head.rem, head.yourref
                      FROM ((cbledger AS cr LEFT JOIN glhead AS head ON head.trno=cr.trno)
                      LEFT JOIN client ON client.clientid=head.clientid
                      left join clientinfo as info on info.clientid = client.clientid)
                      LEFT JOIN cntnum ON cntnum.trno=cr.trno
                      LEFT JOIN gldetail AS detail ON detail.trno = cr.trno and detail.line = cr.line
                      LEFT JOIN coa ON coa.acnoid = detail.acnoid
                      left join projectmasterfile as proj on proj.line=head.projectid
                      WHERE head.doc='cv' and date(cntnum.printcheck) between '" . $start . "' AND '" . $end . "' " . $filter . "
                      AND (LEFT(coa.alias,2) = 'CB')
                      union all
                      select coa.acnoname,detail.postdate as chkdate, if(concat(info.fname,info.mname,info.lname) <> '',TRIM(concat(info.fname, ' ', info.mname, ' ', info.lname)),head.clientname) as clientname, head.docno, 
                            head.dateid as trdate,detail.checkno as chkinfo, (detail.db - detail.cr) as amount,
                            head.client,date(cntnum.postdate) as postdate,head.projectid,proj.code as projcode, 
                            proj.name as projname,head.rem,head.yourref
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno
                      LEFT JOIN client ON client.client=head.client
                      left join clientinfo as info on info.clientid = client.clientid)
                      left join coa on coa.acnoid=detail.acnoid)
                      left join cntnum on cntnum.trno=head.trno
                      left join projectmasterfile as proj on proj.line=head.projectid
                      where head.doc='cv' and (left(coa.alias, 2)='cb' or coa.alias='AP99')
                      and  date(cntnum.printcheck) between '" . $start . "' AND '" . $end . "' " . $filter . "
                      order by chkdate, docno";
            break;
        }
        break;
      default:
        switch ($isposted) {
          case 0: //posted
            $query = "select coa.acnoname,cr.checkdate AS chkdate, head.clientname, head.docno, 
                            head.dateid AS trdate,cr.checkno AS chkinfo,(cr.db-cr.cr) AS amount, 
                            client.client,date(cntnum.postdate) as postdate,head.projectid,
                            proj.code as projcode, proj.name as projname,head.rem, head.yourref
                      FROM ((cbledger AS cr LEFT JOIN glhead AS head ON head.trno=cr.trno)
                      LEFT JOIN client ON client.clientid=head.clientid)LEFT JOIN cntnum ON cntnum.trno=cr.trno
                      LEFT JOIN gldetail AS detail ON detail.trno = cr.trno and detail.line = cr.line
                      LEFT JOIN coa ON coa.acnoid = detail.acnoid
                      left join projectmasterfile as proj on proj.line=head.projectid
                      WHERE head.doc='cv' AND  $ch between '" . $start . "' AND '" . $end . "' " . $filter . "
                      AND (LEFT(coa.alias,2) = 'CB')
                      order by chkinfo, docno";
            break;
          case 1: //unposted
            $query = "select coa.acnoname,detail.postdate as chkdate, head.client, head.clientname, 
                            head.docno,head.dateid as trdate, detail.checkno as chkinfo, 
                            (detail.db - detail.cr) as amount,date(cntnum.postdate) as postdate,
                            head.projectid,proj.code as projcode, proj.name as projname,head.rem,head.yourref
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                      left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                      left join projectmasterfile as proj on proj.line=head.projectid
                      where head.doc='cv' and (left(coa.alias, 2)='cb' or coa.alias='AP99')
                      and $ch between '" . $start . "' and '" . $end . "'  " . $filter . "
                      order by chkinfo, docno";
            break;
          //end case 
          case 2: //all 
            $query = "select coa.acnoname,cr.checkdate AS chkdate, head.clientname, head.docno, 
                            head.dateid AS trdate,cr.checkno AS chkinfo, (cr.db-cr.cr) AS amount, 
                            client.client,date(cntnum.postdate) as postdate,head.projectid,
                            proj.code as projcode, proj.name as projname,head.rem, head.yourref
                      FROM ((cbledger AS cr LEFT JOIN glhead AS head ON head.trno=cr.trno)
                      LEFT JOIN client ON client.clientid=head.clientid)
                      LEFT JOIN cntnum ON cntnum.trno=cr.trno
                      LEFT JOIN gldetail AS detail ON detail.trno = cr.trno and detail.line = cr.line
                      LEFT JOIN coa ON coa.acnoid = detail.acnoid
                      left join projectmasterfile as proj on proj.line=head.projectid
                      WHERE head.doc='cv' and  $ch between '" . $start . "' AND '" . $end . "' " . $filter . "
                      AND (LEFT(coa.alias,2) = 'CB')
                      union all
                      select coa.acnoname,detail.postdate as chkdate, head.clientname, head.docno, 
                            head.dateid as trdate,detail.checkno as chkinfo, (detail.db - detail.cr) as amount,
                            head.client,date(cntnum.postdate) as postdate,head.projectid,proj.code as projcode, 
                            proj.name as projname,head.rem,head.yourref
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                      left join coa on coa.acnoid=detail.acnoid)
                      left join cntnum on cntnum.trno=head.trno
                      left join projectmasterfile as proj on proj.line=head.projectid
                      where head.doc='cv' and (left(coa.alias, 2)='cb' or coa.alias='AP99')
                      and  $ch between '" . $start . "' AND '" . $end . "' " . $filter . "
                      order by chkinfo, docno";
            break;
        } // end switch

        break;
    }

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function VITALINE_query($filters)
  {
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $isposted = $filters['params']['dataparams']['posttype'];
    $checks = $filters['params']['dataparams']['reporttype'];
    $center = $filters['params']['dataparams']['center'];
    $filter = "";

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

    switch ($isposted) {
      case 1: // unposted
        if ($checks == 0) {
          $ch = "head.dateid";
          $transdate = ", head.dateid as transdate";
          $sorting = "postdate";
        } else {
          $ch = "detail.postdate";
          $transdate = ", detail.postdate as transdate";
          $sorting = "postdate";
        } //end if

        $query = "select detail.postdate as chkdate, head.client, head.clientname, head.docno,
                        head.dateid as trdate, detail.checkno as chkinfo, abs(detail.db - detail.cr) as amount,
                        date(cntnum.postdate) as postdate $transdate
                  from ((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                  where head.doc='cv' and left(coa.alias, 2)='cb'
                  and $ch between '" . $start . "' and '" . $end . "'  " . $filter . "
                  order by $sorting ASC";
        return $this->coreFunctions->opentable($query);
        break;
      //end case 

      case 0: //posted
        if ($checks == 0) {
          $ch = "head.dateid";
          $transdate = ", head.dateid as transdate";
          $sorting = "postdate";
        } else {
          $ch = "cr.checkdate";
          $transdate = ", cr.checkdate as transdate";
          $sorting = "postdate";
        } //end if

        $query = "select cr.checkdate AS chkdate, head.clientname, head.docno, head.dateid AS trdate,
                        cr.checkno AS chkinfo, ABS(cr.db-cr.cr) AS amount, client.client,
                        date(cntnum.postdate) as postdate $transdate
                  FROM ((cbledger AS cr LEFT JOIN glhead AS head ON head.trno=cr.trno)
                  LEFT JOIN client ON client.clientid=head.clientid)LEFT JOIN cntnum ON cntnum.trno=cr.trno
                  LEFT JOIN coa ON coa.acnoid = cr.acnoid
                  WHERE head.doc='cv' AND  $ch between '" . $start . "' AND '" . $end . "' " . $filter . "
                        AND LEFT(coa.alias,2) = 'CB'
                  ORDER BY $sorting ASC";
        return $this->coreFunctions->opentable($query);
        break;

      case 2: //all
        if ($checks == 0) {
          $query = "select detail.postdate as chkdate, head.clientname, head.docno, head.dateid as trdate, 
                  detail.checkno as chkinfo, abs(detail.db - detail.cr) as amount, head.client, 
                  date(cntnum.postdate) as postdate, head.dateid as transdate
                  from ((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                  where head.doc='cv' and left(coa.alias, 2)='cb'
                  and head.dateid between '" . $start . "' and '" . $end . "'  " . $filter . "
                  union all
                  select cr.checkdate as chkdate, head.clientname, head.docno, head.dateid as trdate,
                  cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client,
                  date(cntnum.postdate) as postdate, head.dateid as transdate
                  from ((cbledger as cr left join glhead as head on head.trno=cr.trno)
                  left join client on client.clientid=head.clientid)left join cntnum on cntnum.trno=cr.trno
                  left join coa on coa.acnoid = cr.acnoid
                  where head.doc='cv' and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
                  and left(coa.alias,2) = 'cb'
                  order by postdate asc";
        } else {
          $query = "select detail.postdate as chkdate, head.clientname, head.docno, head.dateid as trdate, 
                  detail.checkno as chkinfo, abs(detail.db - detail.cr) as amount, head.client, 
                  date(cntnum.postdate) as postdate, detail.postdate as transdate
                  from ((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join coa on coa.acnoid=detail.acnoid)left join cntnum on cntnum.trno=head.trno
                  where head.doc='cv' and left(coa.alias, 2)='cb'
                  and detail.postdate between '" . $start . "' and '" . $end . "'  " . $filter . "
                  union all
                  select cr.checkdate as chkdate, head.clientname, head.docno, head.dateid as trdate,
                  cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client,
                  date(cntnum.postdate) as postdate, cr.checkdate as transdate
                  from ((cbledger as cr left join glhead as head on head.trno=cr.trno)
                  left join client on client.clientid=head.clientid)left join cntnum on cntnum.trno=cr.trno
                  left join coa on coa.acnoid = cr.acnoid
                  where head.doc='cv' and cr.checkdate between '" . $start . "' and '" . $end . "' " . $filter . "
                  and left(coa.alias,2) = 'cb'
                  order by postdate asc";
        } //end if

        return $this->coreFunctions->opentable($query);
        break;
    } // end switch
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $result = $this->VITALINE_query($config);
        break;
      default:
        $result = $this->default_query($config);
        break;
    }

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $reportdata =  $this->VITALINE_ISSUED_CHECKS_LAYOUT($config, $result);
        break;
      case 8: //maxipro
        $reportdata =  $this->MAXIPRO_ISSUED_CHECKS_LAYOUT($config, $result);
        break;
      case 26: //bee healthy
        $reportdata =  $this->bee_layout($config, $result);
        break;
      case 55: //afli
        $this->reportParams['orientation'] = 'l';
        $reportdata =  $this->AFLI_ISSUED_CHECKS_LAYOUT($config, $result);
        break;
      default:
        $reportdata =  $this->DEFAULT_ISSUED_CHECKS_LAYOUT($config, $result);
        break;
    }
    return $reportdata;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable();

    switch ($companyid) {
      case 55: //afli
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Payee', '170', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Document #', '140', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Application NO.', '140', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Notes', '250', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
        // $str .= $this->reporter->col('Trans. Date', '125', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '');
        break;
      case 46: //msse
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier Name', '150', '', false, '1px dashed', 'B', 'LT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'B', 'CT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Bank', '200', '', false, '1px dashed', 'B', 'LT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'LT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'CT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'RT', $font, '',  'B', '', '', '');
        break;
      default:
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('Supplier Name', '120', '', false, '1px dashed', 'B', 'LT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Document #', '150', '', false, '1px dashed', 'B', 'CT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Notes', '125', '', false, '1px dashed', 'B', 'LT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Trans. Date', '105', '', false, '1px dashed', 'B', 'CT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'LT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'CT', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'RT', $font, '',  'B', '', '', '');
        break;
    }

    return $str;
  }


  private function ISSUED_CHECKS_HEADER($params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $companyid = $params['params']['companyid'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $isposted = $params['params']['dataparams']['posttype'];
    $checks = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['dataparams']['center'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $project = isset($params['params']['dataparams']['dprojectname']) ? $params['params']['dataparams']['dprojectname'] : '';

    switch ($isposted) {
      case 0:
        $ispostedstr = 'posted';
        break;
      case 1:
        $ispostedstr = 'unposted';
      case 2:
        $ispostedstr = 'all';
        break;
    }

    if ($checks == 0) {
      $checksstr = 'transaction date';
    } else {
      $checksstr = 'checkdate';
    }

    $str = '';

     
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center1, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

    

    $str .= '<br/><br/>'; # new 

    # BEGIN TABLE 
    $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ISSUED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');

    if ($companyid == 8) { //maxipro
      if ($project != '') {
        $str .= $this->reporter->col('Project:' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
      } else {
        $str .= $this->reporter->col('Project: ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
      }
    } else {
      if ($center != '') {
        $str .= $this->reporter->col('Center:' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
      } else {
        $str .= $this->reporter->col('Center: ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
      }
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    # END TABLE
    $str .= $this->reporter->printline();
    return $str;
  }

  private function DEFAULT_ISSUED_CHECKS_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '9';
    $fontsize10 = 10;
    $padding = '';
    $margin = '';

    $str = '';
    $count = 16;
    $page = 15;
    $this->reporter->linecounter = 0;
    $clientname = '';
    $c = 0;
    $total = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport('800');

    #header here
    $str .= $this->ISSUED_CHECKS_HEADER($params);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize10, $params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {
      $amtt =0;
      $cnt1 += 1;
      if ($value->amount != 0) {
        if ($value->amount > 0) {
          $value->amount = $value->amount * -1;
        } else {
          $value->amount = abs($value->amount);
        }
        $amtt = number_format($value->amount, 2);
      }

      if ($clientname != $value->clientname) {

        if ($clientname != '') {
          #subtotal
          $str .= $this->ISSUED_CHECKS_SUBTOTAL($params, $c);
          #subtotal
          $str .= $this->reporter->addline();
          $c = 0;
        } // END IF 

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->clientname, '800', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable();
      } // END IF 

      switch ($companyid) {
        case 55: //afli
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'L', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->docno, '100', '', false, $border, '', 'L', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->yourref, '100', '', false, $border, '', 'C', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->rem, '75', '', false, $border, '', 'L', $font, '',  '', '', '', '');
          $str .= $this->reporter->col(date('M-d-Y', strtotime($value->trdate)), '125', '', false, $border, '', 'L', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'L', $font, '',  '', '', '', '');
          $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '100', '', false, $border, '', 'R', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($amtt, '100', '', false, $border, '', 'R', $font, '',  '', '', '', '');
          $str .= $this->reporter->endrow();
          break;
        case 46:
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '150', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col('', '150', '', false, $border, '', 'CT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->acnoname, '200', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '100', '', false, $border, '', 'CT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($amtt, '100', '', false, $border, '', 'RT', $font, '',  '', '', '', '');
          $str .= $this->reporter->endrow();
          break;
        default:
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '120', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->docno, '150', '', false, $border, '', 'CT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->rem, '125', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col(date('M-d-Y', strtotime($value->trdate)), '105', '', false, $border, '', 'CT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '100', '', false, $border, '', 'CT', $font, '',  '', '', '', '');
          $str .= $this->reporter->col($amtt, '100', '', false, $border, '', 'RT', $font, '',  '', '', '', '');
          $str .= $this->reporter->endrow();
          break;
      }

      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('800');
      $clientname = $value->clientname;

      $c = $c + $value->amount;
      $total = $total + $value->amount;
      // fix issue check grand total

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->ISSUED_CHECKS_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize10, $params);

        #header end

        $str .= $this->reporter->begintable();
        $page = $page + $count;
      } # END IF linecounter

      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          $str .= $this->ISSUED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end

          $str .= $this->reporter->addline();

          $c = 0;
          $group = $value->clientname;
        } #end if


        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
      } # end if

      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '75', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '75', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '145', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');

    $str .= $this->reporter->col(number_format($total, 2), '120', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    $str .= $this->reporter->endreport();
    return $str;
  } // end fn


  private function MAXIPRO_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable('1000');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Trans Date', '100', '', false, '1px dashed', 'TB', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Document #', '100', '', false, '1px dashed', 'TB', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Payment Mode', '100', '', false, '1px dashed', 'TB', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Check Info', '80', '', false, '1px dashed', 'TB', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Check Date', '150', '', false, '1px dashed', 'TB', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Project Code', '100', '', false, '1px dashed', 'TB', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Supplier', '190', '', false, '1px dashed', 'TB', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Amount', '130', '', false, '1px dashed', 'TB', 'R', $font, '',  'B', '', '', '', '');
    return $str;
  }

  private function MAXIPRO_IC_HEADER($params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $companyid = $params['params']['companyid'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $isposted = $params['params']['dataparams']['posttype'];
    $checks = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['dataparams']['center'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $project = $params['params']['dataparams']['dprojectname'];

    if ($project != '') {
      $proj = $params['params']['dataparams']['projectname'];
    } else {
      $proj = 'ALL';
    }

    if ($isposted == 0) {
      $ispostedstr = 'posted';
    } else {
      $ispostedstr = 'unposted';
    }

    if ($checks == 0) {
      $checksstr = 'transaction date';
    } else {
      $checksstr = 'check date';
    }

    $str = '';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    # BEGIN TABLE 
    $str .= $this->reporter->begintable('1000', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ISSUED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Project: ' . $proj, null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    # END TABLE

    $str .= '<br>';
    return $str;
  }

  private function MAXIPRO_ISSUED_CHECKS_LAYOUT($params, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '9';
    $fontsize10 = 10;
    $padding = '';
    $margin = '';

    $str = '';
    $count = 16;
    $page = 15;
    $this->reporter->linecounter = 0;
    $clientname = '';
    $c = 0;
    $total = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->MAXIPRO_IC_HEADER($params);

    $str .= $this->MAXIPRO_table_cols(1000, $border, $font, $fontsize10, $params);
    #header end

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->trdate)), '100', '', false, $border, '', 'CT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->docno, '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->paymode, '100', '', false, $border, '', 'CT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '80', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->chkdate)), '150', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->projcode, '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->clientname, '190', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, 2), '130', '', false, $border, '', 'RT', $font, '',  '', '', '', '', '');

      $c = $c + $value->amount;
      $total = $total + $value->amount;
      // fix issue check grand total

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->page_break();

        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->MAXIPRO_IC_HEADER($params);
        }

        $str .= $this->MAXIPRO_table_cols(1000, $border, $font, $fontsize10, $params);

        #header end
        $page = $page + $count;
      } # END IF linecounter

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '580', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '190', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '130', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

  private function VITALINE_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $checks = $config['params']['dataparams']['reporttype'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->col('Post Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    if ($checks == 0) {
      $str .= $this->reporter->col('Collection Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    } else {
      $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    }
    $str .= $this->reporter->col('Document #', '150', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    if ($checks == 0) {
      $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    } else {
      $str .= $this->reporter->col('Collection Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    }
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Supplier Name', '150', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '');

    return $str;
  }

  private function VITALINE_ISSUED_CHECKS_HEADER($params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $isposted = $params['params']['dataparams']['posttype'];
    $checks = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['dataparams']['center'];

    if ($isposted == 0) {
      $ispostedstr = 'posted';
    } else {
      $ispostedstr = 'unposted';
    }

    if ($checks == 0) {
      $checksstr = 'transaction date';
    } else {
      $checksstr = 'checkdate';
    }

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>'; # new 

    # BEGIN TABLE 
    $str .= $this->reporter->begintable('800', null, '', '1px solid ', '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ISSUED CHECKS', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    if ($center != '') {
      $str .= $this->reporter->col('Center:' . $center, null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Center: ALL', null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    # END TABLE
    $str .= $this->reporter->printline();
    return $str;
  }

  private function VITALINE_ISSUED_CHECKS_LAYOUT($params, $data)
  {
    $checks = $params['params']['dataparams']['reporttype'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $str = '';
    $count = 10;
    $page = 10;
    $clientname = '';
    $transdate = '';
    $c = 0;
    $total = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    #header here
    $str .= $this->VITALINE_ISSUED_CHECKS_HEADER($params);
    $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      if ($transdate != $value->transdate) {
        if ($transdate != '') {
          #subtotal
          $str .= $this->VITALINE_ISSUED_CHECKS_SUBTOTAL($params, $c);
          #subtotal
          $str .= $this->reporter->addline();
          $c = 0;
        } // END IF 

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
        if ($checks == 0) {
          $str .= $this->reporter->col(date('m-d-Y', strtotime($value->trdate)), '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
        } else {
          $str .= $this->reporter->col(date('m-d-Y', strtotime($value->chkdate)), '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
        }
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
        $str .= $this->reporter->endrow();
      } // END IF 

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('m-d-Y', strtotime($value->postdate)), '150', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->docno, '100', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '');
      if ($checks == 0) {
        $str .= $this->reporter->col(date('m-d-Y', strtotime($value->chkdate)), '100', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '');
      } else {
        $str .= $this->reporter->col(date('m-d-Y', strtotime($value->trdate)), '100', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '');
      }
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->clientname, '150', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, 2), '100', '', false, '1px solid', '', 'R', $font, '',  '', '', '', '');
      $str .= $this->reporter->endrow();
      $clientname = $value->clientname;
      $transdate = $value->transdate;

      $c = $c + $value->amount;
      $total = $total + $value->amount;
      // fix issue check grand total

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->VITALINE_ISSUED_CHECKS_HEADER($params);
        }
        $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);

        #header end
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      } # END IF linecounter

      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->transdate == '') {
          $group = 'NO GROUP';
        } else {
          #subtotal here
          $str .= $this->VITALINE_ISSUED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end
          $str .= $this->reporter->addline();
          $c = 0;
          $group = $value->transdate;
        } #end if

        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if

      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'I', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');

    $str .= $this->reporter->col(number_format($total, 2), '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn


  private function ISSUED_CHECKS_SUBTOTAL($params, $c)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '75', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'R', $font, '',  'b', '', '', '');
    if ($c == 0) {
      $str .= $this->reporter->col('0', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    } else {
      $str .= $this->reporter->col(number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    }
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function VITALINE_ISSUED_CHECKS_SUBTOTAL($params, $c)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $str = '';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '200', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px solid', '', 'R', $font, '',  'B', '', '', '');
    if ($c == 0) {
      $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '');
    } else {
      $str .= $this->reporter->col(number_format($c, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'i', '', '', '');
    }
    $str .= $this->reporter->endrow();

    return $str;
  }


  private function BEE_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '75', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CHECK #', '100', null, false, $border, 'TBLR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VOUCHER', '75', null, false, $border, 'TBLR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BANK', '150', null, false, $border, 'TBLR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PAYEE', '150', null, false, $border, 'TBLR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('COST CODE', '75', null, false, $border, 'TBLR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PAYEE DETAILS 1', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PAYEE DETAILS 2', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PARTICULARS 1', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PARTICULARS 2', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PARTICULARS 3', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATED BY', '100', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function bee_header($config)
  { // bee healthy     
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $isposted = $config['params']['dataparams']['posttype'];
    $checks = $config['params']['dataparams']['reporttype'];
    $center1 = $config['params']['center'];
    $project = isset($config['params']['dataparams']['dprojectname']) ? $config['params']['dataparams']['dprojectname'] : '';

    if ($isposted == 0) {
      $ispostedstr = 'posted';
    } else {
      $ispostedstr = 'unposted';
    }

    if ($checks == 0) {
      $checksstr = 'transaction date';
    } else {
      $checksstr = 'checkdate';
    }

    $str = '';
    $layoutsize = '1600';

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($this->modulename), '100', null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000', null, '', '1px solid ', '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    if ($center != '') {
      $str .= $this->reporter->col('Center:' . $center, null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Center: ALL', null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    }
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function bee_layout($config, $result)
  {
    $count = 16;
    $page = 14;
    $layoutsize = '1600';

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $borderx = "TBLR";
    $companyid = $config['params']['companyid'];

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->bee_header($config);

    $str .= $this->BEE_table_cols($layoutsize, $border, $font, $fontsize + 1, $config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col(date('d/m/Y', strtotime($data->trdate)), '75', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->chkinfo, '100', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->acnoname, '150', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '150', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->acno, '75', null, false, $border, $borderx, 'C', $font, $fontsize, '', '', '');
      if ($data->tel == '' and $data->tel2 == '') {
        $paydetail1 = '';
      } else {
        $paydetail1 = $data->tel . ' / ' . $data->tel2;
      }
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, $borderx, 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($paydetail1, '150', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->pdetails2, '150', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->parti1, '150', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, $borderx, '', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->createby, '100', null, false, $border, $borderx, 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $allowfirstpage = $this->companysetup->getisfirstpageheader($config['params']);
        if (!$allowfirstpage) {
          $str .= $this->bee_header($config);
        }
        $str .= $this->BEE_table_cols($layoutsize, $border, $font, $fontsize + 1, $config);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
  private function AFLI_ISSUED_CHECKS_HEADER($params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $companyid = $params['params']['companyid'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    $isposted = $params['params']['dataparams']['posttype'];
    $checks = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['dataparams']['center'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $project = isset($params['params']['dataparams']['dprojectname']) ? $params['params']['dataparams']['dprojectname'] : '';
    $layoutsize = 1000;

    switch ($isposted) {
      case 0:
        $ispostedstr = 'posted';
        break;
      case 1:
        $ispostedstr = 'unposted';
      case 2:
        $ispostedstr = 'all';
        break;
    }

    if ($checks == 0) {
      $checksstr = 'transaction date';
    } else {
      $checksstr = 'checkdate';
    }

    $str = '';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>'; # new 

    # BEGIN TABLE 
    $str .= $this->reporter->begintable($layoutsize, null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ISSUED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');

    if ($companyid == 8) { //maxipro
      if ($project != '') {
        $str .= $this->reporter->col('Project:' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
      } else {
        $str .= $this->reporter->col('Project: ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
      }
    } else {
      if ($center != '') {
        $str .= $this->reporter->col('Center:' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
      } else {
        $str .= $this->reporter->col('Center: ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
      }
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    # END TABLE
    $str .= $this->reporter->printline();
    return $str;
  }
  private function AFLI_ISSUED_CHECKS_LAYOUT($params, $data)
  {
    $companyid = $params['params']['companyid'];
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '9';
    $fontsize10 = 10;
    $padding = '';
    $margin = '';
    $layoutsize = 1000;

    $str = '';
    $count = 16;
    $page = 15;
    $this->reporter->linecounter = 0;
    $clientname = '';
    $c = 0;
    $total = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport($layoutsize);

    #header here
    $str .= $this->AFLI_ISSUED_CHECKS_HEADER($params);

    $str .= $this->default_table_cols($layoutsize, $border, $font, $fontsize10, $params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      if ($value->amount != 0) {
        if ($value->amount > 0) {
          $value->amount = $value->amount * -1;
        } else {
          $value->amount = abs($value->amount);
        }
        $amtt = number_format($value->amount, 2);
      }

      if ($clientname != $value->clientname) {

        if ($clientname != '') {
          #subtotal
          // $str .= $this->ISSUED_CHECKS_SUBTOTAL($params, $c);
          #subtotal
          // $str .= $this->reporter->addline();
          $c = 0;
        } // END IF 

        $str .= $this->reporter->begintable($layoutsize);
      } // END IF 


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->clientname, '170', '', false, $border, '', 'LT', $font, '',  'B', '', '', '');
      $str .= $this->reporter->col($value->docno, '140', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->yourref, '140', '', false, $border, '', 'CT', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->rem, '250', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '');
      $str .= $this->reporter->col(date('m/d/Y', strtotime($value->chkdate)), '100', '', false, $border, '', 'RT', $font, '',  '', '', '', '');
      $str .= $this->reporter->col($amtt, '100', '', false, $border, '', 'RT', $font, '',  '', '', '', '');
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $clientname = $value->clientname;

      $c = $c + $value->amount;
      $total = $total + $value->amount;
      // fix issue check grand total

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->AFLI_ISSUED_CHECKS_HEADER($params);
        }
        $str .= $this->default_table_cols($layoutsize, $border, $font, $fontsize10, $params);

        #header end

        $str .= $this->reporter->begintable();
        $page = $page + $count;
      } # END IF linecounter

      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          // $str .= $this->ISSUED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end

          // $str .= $this->reporter->addline();

          $c = 0;
          $group = $value->clientname;
        } #end if


        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
      } # end if

      $str .= $this->reporter->endrow();
    } //end foreach


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '170', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '140', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '140', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '220', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');

    $str .= $this->reporter->col(number_format($total, 2), '130', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '');

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= '<br>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PREPARED BY:', '330', '', false, '1px dashed', '', 'C', $font, '',  '', '', '', '');
    $str .= $this->reporter->col('CHECKED BY:', '340', '', false, '1px dashed', '', 'C', $font, '',  '', '', '', '');
    $str .= $this->reporter->col('APPROVED BY:', '330', '', false, '1px dashed', '', 'C', $font, '',  '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LOVELY JEAN V. ARTATES', '330', '', false, '1px dashed', '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('JANINE B. ANGOR', '340', '', false, '1px dashed', '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('HYACINTH O. VITERBO', '330', '', false, '1px dashed', '', 'C', $font, '',  'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

}//end class