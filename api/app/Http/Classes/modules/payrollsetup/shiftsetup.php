<?php

namespace App\Http\Classes\modules\payrollsetup;

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

class shiftsetup
{
  private $btnClass;
  private $fieldClass;
  private $tabClass;
  public $modulename = 'SHIFT SETUP';
  public $gridname = 'inventory';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  private $logger;
  private $sqlquery;
  public $expirystatus = ['readonly' => false, 'show' => false, 'showdate' => true];
  public $head = 'tmshifts';
  public $prefix = '';
  public $tablelogs = 'payroll_log';
  public $tablelogs_del = '';
  private $stockselect;

  private $fields = [
    'shftcode',
    'tschedin',
    'tschedout',
    'flexit',
    'gtin',
    'gbrkin',
    'ndifffrom',
    'ndiffto',
    'elapse',
    'sig',
    'isonelog',
    'isdefault'
  ];
  // 'remarks','acno','days','bal',
  private $except = ['clientid', 'client'];
  private $blnfields = ['flexit', 'isonelog', 'isdefault'];
  public $showfilteroption = false;
  public $showfilter = false;
  public $showcreatebtn = true;
  private $reporter;


  public function __construct()
  {
    $this->btnClass = new buttonClass;
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->sqlquery = new sqlquery;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array(
      'new' => 1563,
      'save' => 1561,
      'delete' => 1564,
      'view' => 1565,
      'print' => 1562,
      'edit' => 1346,
    );
    return $attrib;
  }

  public function createdoclisting($config)
  {
    $getcols = ['action', 'listclient', 'datestart', 'dateend'];
    $stockbuttons = ['view'];
    $cols = $this->tabClass->createdoclisting($getcols, $stockbuttons);
    $cols[0]['style'] = 'width:40px;whiteSpace: normal;min-width:40px;';
    $cols[1]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[2]['style'] = 'width:100px;whiteSpace: normal;min-width:100px;';
    $cols[3]['style'] = 'width:400px;whiteSpace: normal;min-width:400px;';

    $cols[1]['align'] = 'text-left';

    $cols[2]['name'] = 'ftime';
    $cols[2]['field'] = 'ftime';
    $cols[2]['label'] = 'From';

    $cols[3]['name'] = 'ttime';
    $cols[3]['field'] = 'ttime';
    $cols[3]['label'] = 'To';

    return $cols;
  }

  public function loaddoclisting($config)
  {
    $filtersearch = "";
    if (isset($config['params']['search'])) {
      $searchfield = ['shftcode', 'flexit', 'gtin', 'gbrkin', 'ndifffrom', 'ndiffto', 'elapse'];

      $search = $config['params']['search'];
      if ($search != "") {
        $filtersearch = $this->othersClass->multisearch($searchfield, $search);
      }
      $limit = "";
    }
    $qry = "
          select 
            line as clientid,
            shftcode as client, 
            date(tschedin) as tschedin,
            time(tschedin) as ftime, 
            date(tschedout) as tschedout,
            time(tschedout) as ttime,
            flexit, gtin, isonelog,
            gbrkin, ndifffrom, 
            ndiffto, elapse
          from " . $this->head . "
          where 1=1 $filtersearch
        ";
    $data = $this->coreFunctions->opentable($qry);

    return ['data' => $data, 'status' => true, 'msg' => 'Listing successfully loaded.'];
  }

  public function createHeadbutton($config)
  {
    $btns = array(
      'load',
      'new',
      'save',
      'delete',
      'cancel',
      'print',
      'logs',
      'edit',
      'backlisting',
      'toggleup',
      'toggledown'
    );
    $buttons = $this->btnClass->create($btns);
    return $buttons;
  } // createHeadbutton

  public function createTab($access, $config)
  {
    $tab = ['tableentry' => ['action' => 'payrollentry', 'lookupclass' => 'entryshiftsetupdetails', 'label' => 'DETAILS']];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];
    $fields = ['client', ['ftime', 'ttime']];
    if ($companyid == 45) { // pdpi
      array_push($fields, ['breakoutam', 'breakinam'], ['breakoutpm', 'breakinpm']);
    }
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'client.type', 'input');
    data_set($col1, 'client.class', 'csclient');
    data_set($col1, 'client.label', 'Code');
    // data_set($col1, 'client.action', 'lookupledger');
    // data_set($col1, 'client.lookupclass', 'lookupledgershiftsetup');

    data_set($col1, 'ftime.label', 'From');

    $fields = [['start', 'end'], ['maximum', 'minimum']];
    if ($companyid == 45) { // pdpi
      array_push($fields, ['sig',]);
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'maximum.name', 'gtin');
    data_set($col2, 'maximum.label', 'Grace Period TimeIn(min)');
    data_set($col2, 'minimum.name', 'gbrkin');
    data_set($col2, 'minimum.label', 'Grace Period OT(min)');

    data_set($col2, 'maximum.type', 'cinput');
    data_set($col2, 'minimum.type', 'cinput');

    data_set($col2, 'start.name', 'ndifffrom');
    data_set($col2, 'start.label', 'Night Diff. From');
    data_set($col2, 'start.type', 'time');
    data_set($col2, 'end.name', 'ndiffto');
    data_set($col2, 'end.label', 'Night Diff To');
    data_set($col2, 'end.type', 'time');

    $fields = ['flexit'];
    if ($companyid == 58 || $companyid == 62) { // cdo / onesky
      array_push($fields, 'isonelog');
    }
    array_push($fields, 'isdefault');
    $col3 = $this->fieldClass->create($fields);
    if ($companyid == 62) { // onesky
      data_set($col3, 'isonelog.label', 'Fixed 12 Hrs');
      unset($col3['isdefault']);
      unset($col3['flexit']);
    }

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function newclient($config)
  {
    $data = $this->resetdata($config, $config['newclient']);
    return  ['head' => $data, 'islocked' => false, 'isposted' => false, 'status' => true, 'isnew' => true, 'msg' => 'Ready for New Ledger'];
  }

  private function resetdata($config, $client = '')
  {
    $companyid = $config['params']['companyid'];

    $data = [];
    $data[0]['clientid'] = 0;
    $data[0]['client'] = $client;
    $data[0]['shftcode'] = '';
    $data[0]['ftime'] = '00:00';
    $data[0]['line'] = 0;
    $data[0]['ttime'] = '00:00';
    $data[0]['tschedin'] = $this->othersClass->getCurrentDate();
    $data[0]['tschedout'] = $this->othersClass->getCurrentDate();
    $data[0]['flexit'] = '0';
    $data[0]['gtin'] = 0;
    $data[0]['gbrkin'] = 0;
    $data[0]['ndifffrom'] = '00:00';
    $data[0]['ndiffto'] = '00:00';
    $data[0]['elapse'] = 0;

    $data[0]['breakoutam'] = '00:00';
    $data[0]['breakinam'] = '00:00';
    $data[0]['breakinpm'] = '00:00';
    $data[0]['breakoutpm'] = '00:00';

    if ($companyid == 58) {
      $data[0]['breakoutam'] = '10:00';
      $data[0]['breakinam'] = '10:15';
      $data[0]['breakinpm'] = '15:00';
      $data[0]['breakoutpm'] = '15:15';
    }

    $data[0]['isonelog'] = '0';
    $data[0]['isdefault'] = '0';
    return $data;
  }


  public function loadheaddata($config)
  {
    $clientid = $config['params']['clientid'];

    $clientid = $this->othersClass->val($clientid);
    if ($clientid == 0) $clientid = $this->getlastclient();
    $qryselect = "
        select 
          line as clientid, 
          shftcode as client, 
          line,
          shftcode,
          date(tschedin) as tschedin,
          time(tschedin) as ftime, 
          date(tschedout) as tschedout,
          time(tschedout) as ttime, flexit,
          time(breakoutam) as breakoutam,
          time(breakinam) as breakinam,
          time(breakinpm) as breakinpm,
          time(breakoutpm) as breakoutpm,
          gtin, gbrkin, time(ndifffrom) as ndifffrom, time(ndiffto) as ndiffto, elapse, sig, isonelog,isdefault";

    $qry = $qryselect . " from 
        " . $this->head . " as shift
        where shift.line = ?";
    $head = $this->coreFunctions->opentable($qry, [$clientid]);
    if (!empty($head)) {
      foreach ($this->blnfields as $key => $value) {
        if ($head[0]->$value) {
          $head[0]->$value = "1";
        } else
          $head[0]->$value = "0";
      }
      $msg = 'Data Fetched Success';
      if (isset($config['msg'])) {
        $msg = $config['msg'];
      }

      return  ['reloadtableentry' => true, 'head' => $head, 'isnew' => false, 'status' => true, 'msg' => $msg, 'islocked' => false, 'isposted' => false, 'qq' => $config['params']['clientid']];
    } else {
      $head = $this->resetdata($config);
      return ['reloadtableentry' => true, 'status' => false, 'isnew' => true, 'head' => $head, 'msg' => 'Data Fetched Failed, either somebody already deleted the transaction or modified...'];
    }
  }

  public function updatehead($config, $isupdate)
  {
    $head = $config['params']['head'];
    $center = $config['params']['center'];
    $companyid = $config['params']['companyid'];
    $data = [];
    if ($isupdate) {
      // unset($this->fields['shftcode']);
      $data['shftcode'] = $head['client'];
      $head['shftcode'] = $head['client'];
    } else {
      $data['shftcode'] = $head['client'];
      $head['shftcode'] = $head['client'];
    }
    $clientid = 0;
    $msg = '';
    foreach ($this->fields as $key) {
      if (array_key_exists($key, $head)) {
        $data[$key] = $head[$key];
        if (!in_array($key, $this->except)) {
          $data[$key] = $this->othersClass->sanitizekeyfield($key, $data[$key]);
        } //end if 
      }
    }
    $addfield = "";
    $addfield2 = "";
    $addvalue = "";
    if ($companyid == 45 || $companyid == 58) { // pdpi
      $data['breakoutam'] = $head['tschedin'] . " " . $head['breakoutam'];
      $data['breakinam'] = $head['tschedout'] . " " . $head['breakinam'];
      $data['breakinpm'] = $head['tschedin'] . " " . $head['breakinpm'];
      $data['breakoutpm'] = $head['tschedout'] . " " . $head['breakoutpm'];
      $addfield = ",
              brk1stin = concat(date(schedin), ' ', '" . $head['breakinam'] . "'),
              brk1stout = concat(date(schedout), ' ', '" . $head['breakoutam'] . "'),
              brk2ndin = concat(date(schedin), ' ', '" . $head['breakinpm'] . "'),
              brk2ndout = concat(date(schedout), ' ', '" . $head['breakoutpm'] . "')";
      $addfield2 = ", brk1stin, brk1stout, brk2ndout, brk2ndin";
      $addvalue = ",'" . date('Y-m-d H:i', strtotime($data['breakinam'])) . "',
              '" . date('Y-m-d H:i', strtotime($data['breakoutam'])) . "','" . date('Y-m-d H:i', strtotime($data['breakoutpm'])) . "',
              '" . date('Y-m-d H:i', strtotime($data['breakinpm'])) . "'";
    }
    $data['tschedin'] = $head['tschedin'] . " " . $head['ftime'];
    $data['tschedout'] = $head['tschedout'] . " " . $head['ttime'];

    $data['ndifffrom'] = $head['tschedin'] . " " . $head['ndifffrom'];
    $data['ndiffto'] = $head['tschedout'] . " " . $head['ndiffto'];

    $data['editdate'] = $this->othersClass->getCurrentTimeStamp();
    $data['editby'] = $config['params']['user'];
    if ($isupdate) {
      $checking = $this->coreFunctions->datareader("select shftcode as value from tmshifts where shftcode = ? and line = ?", [$head['shftcode'], $head['clientid']]);
      if (!empty($checking)) {
        unset($data["shftcode"]);
      } else {
        $checking1 = $this->coreFunctions->datareader("select shftcode as value from tmshifts where shftcode = ?", [$head['shftcode']]);
        if (!empty($checking1)) {
          return ['status' => false, 'msg' => "Already Exist", 'clientid' => $head['clientid']];
        }
      }

      $this->coreFunctions->sbcupdate($this->head, $data, ['line' => $head['line']]);
      $clientid = $head['clientid'];


      $existdata = $this->coreFunctions->opentable("select line from shiftdetail where shiftsid=" . $clientid);
      if (empty($existdata)) {
        goto insertdetailhere;
      }

      // if ($clientid) {
      //   for ($i = 1; $i <= 7; $i++) {
      //     $qry = "update shiftdetail set schedin = concat(date(schedin), ' ', '" . $head['ftime'] . "'), 
      //         schedout = concat(date(schedout), ' ', '" . $head['ttime'] . "')
      //         $addfield
      //         where dayn = '" . $i . "' and shiftsid = '" . $clientid . "'";
      //     $this->coreFunctions->execqry($qry, 'update');
      //   }
      // }
    } else {
      $checking = $this->coreFunctions->datareader("select shftcode as value from tmshifts where shftcode = ?", [$head['shftcode']]);
      if ($checking != "") {
        return ['status' => false, 'msg' => "Already Exist", 'clientid' => $head['clientid']];
      }
      $clientid = $this->coreFunctions->insertGetId($this->head, $data);

      $this->logger->sbcmasterlog(
        $clientid,
        $config,
        'CREATE' . ' - ' . $head['shftcode'] . ' FROM: ' . $head['ftime'] . ' TO: ' . $head['ttime']
      );
      insertdetailhere:
      if ($clientid) {
        $days = 0;
        for ($i = 1; $i <= 7; $i++) {
          $breakin = date("Y-m-d 13:00", strtotime("+$days days"));
          $breakout = date("Y-m-d 12:00", strtotime("+$days days"));
          $qry = "insert into shiftdetail(dayn, shiftsid, schedin, schedout, breakin, breakout $addfield2 )
              values
              ('" . $i . "', '" . $clientid . "', '" . date('Y-m-d H:i', strtotime($data['tschedin'] . " +$days days")) . "',
              '" . date('Y-m-d H:i', strtotime($data['tschedout'] . " +$days days")) . "', 
              '" . $breakin . "', '" . $breakout . "' $addvalue ) ";
          $this->coreFunctions->execqry($qry, 'insert');
          $days++;
        }
      }
    }

    return ['status' => $msg == '' ? true : false, 'msg' => $msg, 'clientid' => $clientid];
  } // end function

  public function getlastclient()
  {
    $last_id = $this->coreFunctions->datareader("select line as value 
        from " . $this->head . " 
        order by line DESC LIMIT 1");

    return $last_id;
  }

  public function openstock($clientid, $config)
  {
    $qry = 'select schedin, schedout, breakin, breakout, tothrs, brk1stin,brk1stout, brk2ndin,brk2ndout,
      from shiftdetail 
      where shiftsid=?';
    return $this->coreFunctions->opentable($qry, [$clientid]);
  }

  public function deletetrans($config)
  {
    $clientid = $config['params']['clientid'];

    $qry1 = "select shiftid as value from employee where shiftid=?";
    $count = $this->coreFunctions->datareader($qry1, [$clientid], '', true);

    if (($count != 0)) {
      return ['clientid' => $clientid, 'status' => false, 'msg' => 'Already have transaction...'];
    }

    $this->coreFunctions->execqry('delete from ' . $this->head . ' where line=?', 'delete', [$clientid]);
    return ['clientid' => 0, 'status' => true, 'msg' => 'Successfully deleted.'];
  } //end function


  // -> print function
  public function reportsetup($config)
  {
    // $txtfield = $this->createreportfilter();
    // $txtdata = $this->reportparamsdata($config);

    $txtfield = app($this->companysetup->getreportpath($config['params']))->createreportfilter($config);
    $txtdata = app($this->companysetup->getreportpath($config['params']))->reportparamsdata($config);

    $modulename = $this->modulename;
    $data = [];
    $style = 'width:500px;max-width:500px;';

    return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  }

  public function reportdata($config)
  {
    // $data = $this->report_default_query($config['params']['dataid']);
    // $str = $this->reportplotting($config, $data);

    $data = app($this->companysetup->getreportpath($config['params']))->report_default_query($config);
    $str = app($this->companysetup->getreportpath($config['params']))->reportplotting($config, $data);

    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  }

  // public function reportsetup($config)
  // {
  //   $txtfield = $this->createreportfilter();
  //   $txtdata = $this->reportparamsdata($config);
  //   $modulename = $this->modulename;
  //   $data = [];
  //   $style = 'width:500px;max-width:500px;';
  //   return ['status' => true, 'msg' => 'Loaded Success', 'modulename' => $modulename, 'data' => $data, 'txtfield' => $txtfield, 'txtdata' => $txtdata, 'style' => $style, 'directprint' => false];
  // }

  // public function reportdata($config)
  // {
  //   $data = $this->report_default_query($config);
  //   $str = $this->rpt_leavesetup_masterfile_layout($data, $config);
  //   return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str];
  // }

  // public function createreportfilter()
  // {
  //   $fields = ['radioprint', 'prepared', 'approved', 'received', 'print'];
  //   $col1 = $this->fieldClass->create($fields);
  //   return array('col1' => $col1);
  // }

  // public function reportparamsdata($config)
  // {
  //   return $this->coreFunctions->opentable(
  //     "select 
  //       'default' as print,
  //       '' as prepared,
  //       '' as approved,
  //       '' as received
  //       "
  //   );
  // }

  // private function report_default_query($config)
  // {
  //   $trno = $config['params']['dataid'];
  //   $query = "
  //       select
  //         head.shftcode,
  //         date(head.tschedin) as tschedin,
  //         time(head.tschedin) as ftime,
  //         date(head.tschedout) as tschedout,
  //         time(head.tschedout) as ttime, head.flexit,
  //         head.gtin, gbrkin, time(head.ndifffrom) as ndifffrom,
  //         time(head.ndiffto) as ndiffto, head.elapse,
  //         case 
  //           when details.dayn = '1' then 'Monday'
  //           when details.dayn = '2' then 'Tuesday'
  //           when details.dayn = '3' then 'Wednesday'
  //           when details.dayn = '4' then 'Thursday'
  //           when details.dayn = '5' then 'Friday'
  //           when details.dayn = '6' then 'Saturday'
  //           when details.dayn = '7' then 'Sunday'
  //         end as dayn,
  //         time_format(details.schedin, '%H:%i %p') as schedin,
  //         time_format(details.schedout, '%H:%i %p') as schedout,
  //         time_format(details.breakout, '%H:%i %p') as breakout,
  //         time_format(details.breakin, '%H:%i %p') as breakin,
  //         details.tothrs
  //       from tmshifts as head
  //       left join shiftdetail as details on head.line = details.shiftsid
  //       where head.line = '$trno'
  //       order by details.line
  //     ";

  //   $result = json_decode(json_encode($this->coreFunctions->opentable($query)), true);
  //   return $result;
  // } //end fn

  // private function rpt_default_header($data, $filters)
  // {

  //   $companyid = $filters['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

  //   $center = $filters['params']['center'];
  //   $username = $filters['params']['user'];

  //   $str = '';
  //   $layoutsize = '1000';
  //   $font = "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->letterhead($center, $username);
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/><br/>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('SHIFT SETUP', '800', null, false, $border, '', 'L', $font, '18', 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();
  //   $str .= '<br/>';

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
  //   $str .= $this->reporter->col('Code:', '50', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col($data[0]['shftcode'], '690', null, false, $border, '', 'L', $font, $fontsize, '', '', '2px');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, '10', '', '', '4px');
  //   $str .= $this->reporter->pagenumber('Page');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Days', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Sched In', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Sched Out', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Lunch Out', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Lunch In', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
  //   $str .= $this->reporter->col('Total Hours', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '2px');
  //   // $str .= $this->reporter->col('Rate','300',null,false,$border,'B','L',$font,$fontsize,'B','','2px');
  //   $str .= $this->reporter->endrow();
  //   return $str;
  // }

  // private function rpt_leavesetup_masterfile_layout($data, $filters)
  // {
  //   $companyid = $filters['params']['companyid'];
  //   $decimal = $this->companysetup->getdecimal('currency', $filters['params']);

  //   $center = $filters['params']['center'];
  //   $username = $filters['params']['user'];

  //   $str = '';
  //   $layoutsize = '1000';
  //   $font = "Century Gothic";
  //   $fontsize = "11";
  //   $border = "1px solid ";
  //   $count = 35;
  //   $page = 35;

  //   $str .= $this->reporter->beginreport();
  //   $str .= $this->rpt_default_header($data, $filters);
  //   $totalext = 0;
  //   for ($i = 0; $i < count($data); $i++) {
  //     $str .= $this->reporter->startrow();
  //     $str .= $this->reporter->addline();
  //     $str .= $this->reporter->col($data[$i]['dayn'], '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['schedin'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['schedout'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['breakout'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['breakin'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->col($data[$i]['tothrs'], '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '3px');
  //     $str .= $this->reporter->endrow();

  //     if ($this->reporter->linecounter == $page) {
  //       $str .= $this->reporter->endtable();
  //       $str .= $this->reporter->page_break();
  //       $str .= $this->rpt_default_header($data, $filters);
  //       $str .= $this->reporter->printline();
  //       $page = $page + $count;
  //     }
  //   }

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->printline();

  //   $str .=  '<br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col('Prepared By : ', '266', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->col('Approved By :', '266', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->col('Received By :', '266', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .=  '<br/>';
  //   $str .= $this->reporter->begintable('800');
  //   $str .= $this->reporter->startrow();
  //   $str .= $this->reporter->col($filters['params']['dataparams']['prepared'], '266', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->col($filters['params']['dataparams']['approved'], '266', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->col($filters['params']['dataparams']['received'], '266', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
  //   $str .= $this->reporter->endrow();
  //   $str .= $this->reporter->endtable();

  //   $str .= $this->reporter->endtable();
  //   $str .= $this->reporter->endreport();
  //   return $str;
  // } //end fn




} //end class
