<?php

namespace App\Http\Classes\lookup;

use Exception;
use Throwable;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\sqlquery;
use Illuminate\Http\Request;
use App\Http\Requests;
use DateTime;

class payrolllookup
{
  private $othersClass;
  private $sqlquery;
  private $coreFunctions;

  public function __construct()
  {
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->sqlquery = new sqlquery;
  }


  public function lookupshiftcode($config)
  {
    $lookupclass = isset($config['params']['lookupclass']) ? $config['params']['lookupclass'] : '';
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Shift',
      'style' => 'width:900px;max-width:900px;'
    );
    $plotting = ['shiftcode' => 'shftcode', 'shiftid' => 'line'];

    if ($lookupclass == 'lookupshiftcode2') { //one sky
      $plotting = ['shiftcode2' => 'shftcode2', 'shiftid2' => 'line'];
    }
    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => $plotting
    );

    // lookup columns
    $cols = array(
      array('name' => 'shftcode', 'label' => 'Code', 'align' => 'left', 'field' => 'shftcode', 'sortable' => true, 'style' => 'font-size:16px;'),
    );

    $qry = "
    select 0 as line, '' as shftcode, '' as shftcode2 union all
    select line, shftcode,shftcode as shftcode2 from  tmshifts order by shftcode";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }


  public function lookuppaytrancurrent($config)
  {

    $title = 'Process';
    if (isset($config['params']['addedparams'][2])) {
      $title = 'Process - (' . $config['params']['addedparams'][2] . ')';
    }

    $lookupsetup = array(
      'type' => 'single',
      'title' =>  $title,
      'style' => 'width:1000px;max-width:1000px;height:95%;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array()
    );

    $cols = array(
      array('name' => 'code', 'label' => 'CODE', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:12px;'),
      array('name' => 'codename', 'label' => 'ACCOUNT NAME', 'align' => 'left', 'field' => 'codename', 'sortable' => true, 'style' => 'font-size:12px;'),
      array('name' => 'db', 'label' => 'EARNINGS', 'align' => 'right', 'field' => 'db', 'sortable' => true, 'style' => 'font-size:12px;'),
      array('name' => 'cr', 'label' => 'DEDUCTIONS', 'align' => 'right', 'field' => 'cr', 'sortable' => true, 'style' => 'font-size:12px;'),
    );

    $empid = $config['params']['addedparams'][0];
    $batchid = $config['params']['addedparams'][1];

    $qry = "select p.code, p.codename, FORMAT(sum(t.db),2) as db, FORMAT(sum(t.cr),2) as cr from paytrancurrent as t left join paccount as p on p.line=t.acnoid where t.empid=" . $empid . " and t.batchid=" . $batchid . " group by p.code, p.codename, t.torder order by t.torder";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }

  public function earndedlookup($config)
  {

    $lookupsetup = [
      'type' => 'single',
      'title' => 'Earning and Deduction',
      'style' => 'width:1000px;max-width:1000px;'
    ];
    $plotsetup = [
      'plottype' => 'plothead',
      'plotting' => ['earnded' => 'codename', 'earndedid' => 'acnoid']
    ];
    $cols = [
      ['name' => 'code', 'label' => 'Code', 'align' => 'left', 'field' => 'code', 'sortable' => true, 'style' => 'font-size:16px;'],
      ['name' => 'codename', 'label' => 'Account Name', 'align' => 'left', 'field' => 'codename', 'sortable' => true, 'style' => 'font-size:16px;']
    ];
    switch ($config['params']['lookupclass']) {
      case 'lookupearnded':
        $qry = "select distinct(s.acnoid) as acnoid, s.acno as code, p.codename from standardsetup as s left join paccount as p on p.line=s.acnoid";

        break;
      case 'lookupearndedall':

        $qry = "select distinct(s.acnoid) as acnoid, s.acno as code, p.codename from standardsetup as s left join paccount as p on p.line=s.acnoid
        union all
        select distinct(s.acnoid) as acnoid, s.acno as code, p.codename from standardsetupadv as s left join paccount as p on p.line=s.acnoid order by codename";
        break;

      default:
        if ($config['params']['companyid'] == 28) { //xcomp
          $qry = "select p.line as acnoid,p.code,p.codename from paccount as p ";
          break;
        } else {
          $qry = "select p.line as acnoid,p.code,p.codename from paccount as p where p.alias like '%LOAN%' or p.alias like '%DEDUCTION%'";
          break;
        }
    }

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }



  public function lookupvieweempnotimeinout($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'No Timein Record',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array()
    );

    $cols = array(
      array('name' => 'empid', 'label' => 'Employee Code', 'align' => 'left', 'field' => 'empid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;')
    );



    $date = $config['params']['addedparams'][0];
    $dateid = date('Y-m-d', strtotime($date));
    $qry = "select   client.client as empid, client.clientname as name from employee as emp left join client on client.clientid=emp.empid
    where emp.isactive=1 and '" . $dateid . "' not in (select left(t.timeinout,10) as timeinout from timerec as t where t.userid=emp.idbarcode and date(t.timeinout)='" . $dateid . "')
    order by client.clientname";
    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];    //rowen.04/18/24
  }


  public function lookupviewempnodeployment($config)
  {

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'No Deployment record',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array()
    );

    $cols = array(
      array('name' => 'empid', 'label' => 'Employee Code', 'align' => 'left', 'field' => 'empid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'name', 'label' => 'Name', 'align' => 'left', 'field' => 'name', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $date = $config['params']['addedparams'][0];
    $dateid = date('Y-m-d', strtotime($date));

    $qry = "select client.client as empid, client.clientname as name
    from timerec as t left join employee as emp on emp.idbarcode=t.userid left join client on client.clientid=emp.empid
    where emp.isactive=1 and  date(t.timeinout) = '" . $dateid . "'
    and '" . $dateid . "'  not in (select left(prj.dateid,10) from empprojdetail as prj where prj.empid=emp.empid and date(prj.dateid)='" . $dateid . "')
    group by client.client, client.clientname
    order by client.clientname";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];   //rowen.04/18/24
  }
  public function lookupcompany($config)
  {

    $lookupsetup = array(
      'type' => 'single',
      'title' => 'Company List',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('company' => 'company', 'divid' => 'divid')
    );

    $cols = array(
      array('name' => 'company', 'label' => 'Company Name', 'align' => 'left', 'field' => 'company', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 0 as divid, '' as company
    union all 
    select divid,divname as company from division";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupsections($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Sections',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('sectionname' => 'sectionname', 'sectid' => 'sectid')
    );

    $cols = array(
      array('name' => 'sectionname', 'label' => 'Section Name', 'align' => 'left', 'field' => 'sectionname', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = " select 0  as sectid, '' as sectionname
    union all
    select line as sectid,area as sectionname from rateexempt";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupdepartments($config)
  {
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Department',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('department' => 'department', 'deptid' => 'deptid')
    );

    $cols = array(
      array('name' => 'department', 'label' => 'Department Name', 'align' => 'left', 'field' => 'department', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 0 as deptid, '' as department
     union all 
     select clientid as deptid,clientname as department from client where isdepartment = 1";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupdaytype($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Day Type',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $qry = "
    select 'WORKING' as daytype
    union all 
    select 'RESTDAY' as daytype";

    $plottype = 'plothead';
    // camera all application lookup working and resday na lang 10-27-2025
    if (strtoupper($doc) == 'TTC' || strtoupper($doc) == 'EMPTIMECARD') {
      //   $qry .= "
      // union all 
      // select 'SP' as daytype
      // union all 
      // select 'LEG' as daytype";
      $plottype = 'plotgrid';
    }

    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => array('daytype' => 'daytype')
    );

    $cols = array(
      array('name' => 'daytype', 'label' => 'Day Type', 'align' => 'left', 'field' => 'daytype', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $data = $this->coreFunctions->opentable($qry);

    switch (strtoupper($doc)) {
      case 'TTC':
      case 'EMPTIMECARD':
        $table = $config['params']['table'];
        $rowindex = $config['params']['index'];
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
        break;
      default:
        return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];

        break;
    }
  }
  public function lookuptimeshift($config)
  {
    $empid = $config['params']['adminid'];
    $dateid = $config['params']['addedparams'][0];

    $dateid = $this->othersClass->sbcdateformat($dateid);
    $dayname = date('l', strtotime($dateid));

    $daynum = [
      'Sunday'    => 1,
      'Monday'    => 2,
      'Tuesday'   => 3,
      'Wednesday' => 4,
      'Thursday'  => 5,
      'Friday'    => 6,
      'Saturday'  => 7
    ];

    $dayn = $daynum[$dayname];
    $lookupsetup = array(

      'type' => 'single',
      'title' => 'List of Schedule',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('shiftcode' => 'shiftcode', 'schedin' => 'schedin', 'schedout' => 'schedout', 'dayn' => 'dayn', 'shiftid' => 'shiftid')
    );

    $cols = array(
      array('name' => 'shiftcode', 'label' => 'Shift Code', 'align' => 'left', 'field' => 'shiftcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedin', 'label' => 'Time In', 'align' => 'left', 'field' => 'schedin', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedout', 'label' => 'Time Out', 'align' => 'left', 'field' => 'schedout', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "
    select detail.dayn,tm.line as shiftid,tm.shftcode as shiftcode, time(IFNULL(detail.schedin,'00:00:00')) as schedin, time(IFNULL(detail.schedout,'00:00:00')) as schedout 
	  from tmshifts as tm left join shiftdetail as detail on detail.shiftsid = tm.line
    where detail.dayn = $dayn ";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupresday_and_working($config)
  {
    $doc = $config['params']['doc'];
    $empid = $config['params']['adminid'];
    $curdate = $this->othersClass->getCurrentDate();
    $date = new DateTime($curdate);
    $date->modify('+1 month');

    $newdate = $date->modify('last day of this month');
    $endate = $newdate->format('Y-m-d');
    $lookupsetup = array(

      'type' => 'single',
      'title' => 'List of Schedule',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('dateid' => 'dateid', 'schedin' => 'schdin', 'schedout' => 'schdout', 'schediin' => 'schediin', 'schedoutt' => 'schedoutt')
    );

    $cols = array(
      array('name' => 'dateid', 'label' => 'Schedule Date', 'align' => 'left', 'field' => 'dateid', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schdin', 'label' => 'Schedule In', 'align' => 'left', 'field' => 'schdin', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schdout', 'label' => 'Schedule Out', 'align' => 'left', 'field' => 'schdout', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    if ($doc == 'RESTDAY') {
      $daytype = 'WORKING';
    } else {
      $daytype = 'RESTDAY';
    }
    $shiftid = $this->coreFunctions->datareader("select shiftid as value from employee where empid =?", [$empid]);
    $filter = " and tm.line = $shiftid and tc.daytype = '" . $daytype . "' and tc.dateid between '" . $curdate . "' and '" . $endate . "'";
    $qry = "
    select tc.dateid as dateid,time(tc.schedin) as schdin, time(tc.schedout) as schdout,tm.shftcode,tc.schedout as schedoutt,tc.schedin as schediin
    from tmshifts as tm 
	  left join timecard  as tc on tc.shiftid = tm.line
    where tc.empid = $empid $filter order by dateid";

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupshift($config)
  {
    $doc = $config['params']['doc'];
    $dayn = $config['params']['row']['dayn'];
    $dateid = $config['params']['row']['dateid'];
    $lookupsetup = array(

      'type' => 'single',
      'title' => 'List of Schedule',
      'style' => 'width:1000px;max-width:1000px;height:700px'
    );

    $plotsetup = array(
      'plottype' => 'plotgrid',
      'plotting' => array(
        'schedin' => 'schedin',
        'schedout' => 'schedout',
        'shiftcode' => 'shiftcode',
        'shiftid' => 'shiftid',
        'reghrs' => 'reghrs',
        'schedbrkout' => 'schedbrkout',
        'schedbrkin' => 'schedbrkin'
      )
    );

    $cols = array(
      array('name' => 'shiftcode', 'label' => 'Shift Code', 'align' => 'left', 'field' => 'shiftcode', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'ftime', 'label' => 'From', 'align' => 'left', 'field' => 'ftime', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'stime', 'label' => 'To', 'align' => 'left', 'field' => 'stime', 'sortable' => true, 'style' => 'font-size:16px;')
    );
    $qry = "select shd.dayn,date_format(timestamp('" . $dateid . "',time(shd.schedin)), '%Y-%m-%d %H:%i') as schedin,
    date_format(timestamp('" . $dateid . "',time(shd.schedout)),'%Y-%m-%d %H:%i') as schedout,
    tm.shftcode as shiftcode,tm.line as shiftid,shd.shiftsid,shd.tothrs as reghrs,shd.breakin as schedbrkin,shd.breakout as schedbrkout,
    time(IFNULL(shd.schedin,'00:00:00')) as ftime,time(IFNULL(shd.schedout,'00:00:00')) as stime
    from tmshifts as tm 
    left join shiftdetail as shd on shd.shiftsid = tm.line
    where shd.dayn = $dayn ";

    $data = $this->coreFunctions->opentable($qry);
    $table = $config['params']['table'];
    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
  }


  public function loookupholidaylocation($config)
  {
    $doc = $config['params']['doc'];
    $lookupsetup = array(
      'type' => 'single',
      'title' => 'List of Holiday Locations',
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plottype = 'plothead';

    if ($doc == 'EMPLOYEE') {
      $qry = " select locname as location from emploc ";
    } else {
      $qry = " select location from holidayloc ";
    }

    $plotsetup = array(
      'plottype' => $plottype,
      'plotting' => array('emploc' => 'location')
    );

    $cols = array(
      array('name' => 'location', 'label' => 'Location', 'align' => 'left', 'field' => 'location', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $data = $this->coreFunctions->opentable($qry);
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupchangetime($config)
  {
    $label = 'Change Time';
    $lookupsetup = array(

      'type' => 'single',
      'title' => $label,
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => 'plothead',
      'plotting' => array('changetime' => 'changetime', 'schedin' => 'schedin', 'schedout' => 'schedout')
    );

    $cols = array(
      array('name' => 'schedin', 'label' => 'Time In', 'align' => 'left', 'field' => 'schedin', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedout', 'label' => 'Time Out', 'align' => 'left', 'field' => 'schedout', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $qry = "select 'Change Time' as changetime,line,times,time(times) as schedin,time(date_add(times, interval 9 hour)) as schedout from timesetup";
    $data = $this->coreFunctions->opentable($qry);

    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup];
  }
  public function lookupchangetimettc($config)
  {
    $plotype = 'plotgrid';
    $row = $config['params']['row'];
    $label = 'Change Time';
    $lookupsetup = array(

      'type' => 'single',
      'title' => $label,
      'style' => 'width:1000px;max-width:1000px;'
    );

    $plotsetup = array(
      'plottype' => $plotype,
      'plotting' => array('changetime' => 'changetime', 'schedin' => 'schedin', 'schedout' => 'schedout')
    );

    $cols = array(
      array('name' => 'schedin', 'label' => 'Time In', 'align' => 'left', 'field' => 'schedin', 'sortable' => true, 'style' => 'font-size:16px;'),
      array('name' => 'schedout', 'label' => 'Time Out', 'align' => 'left', 'field' => 'schedout', 'sortable' => true, 'style' => 'font-size:16px;')
    );

    $schedin = date('Y-m-d', strtotime($row['schedin']));
    $qry = "select '' as changetime,line,times,date_format(timestamp('" . $schedin . "',time(times)),'%Y-%m-%d %H:%i') as schedin,
      date_format(timestamp(date_add(date('" . $row['schedin'] . "'),interval case when date(date_add(timestamp('" . $schedin . "',time(times)),INTERVAL 9 HOUR)) > date('" . $row['schedin'] . "') then 1 else 0 end day),TIME(date_add(timestamp('" . $schedin . "',time(times)), interval 9 hour))),'%Y-%m-%d %H:%i') as schedout
      from timesetup";

    $data = $this->coreFunctions->opentable($qry);
    $table = $config['params']['table'];
    $rowindex = $config['params']['index'];
    return ['status' => true, 'msg' => 'ok', 'data' => $data, 'lookupsetup' => $lookupsetup, 'cols' => $cols, 'plotsetup' => $plotsetup, 'table' => $table, 'rowindex' => $rowindex];
  }
}
