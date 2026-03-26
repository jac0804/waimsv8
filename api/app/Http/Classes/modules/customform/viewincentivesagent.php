<?php

namespace App\Http\Classes\modules\customform;

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

class viewincentivesagent
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AGENT INCENTIVES';
  public $gridname = 'tableentry';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%';
  public $issearchshow = true;
  public $showclosebtn = false;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 2518, 'view' => 2518);
    return $attrib;
  }

  public function createTab($config)
  {
    $docno = 0;
    $depodate = 0;
    $amt = 0;
    $agentname = 0;
    $tab = [
      'tableentry' => ['action' => 'warehousingentry', 'lookupclass' => 'viewgridincentives', 'label' => 'LIST']
    ];
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
    $fields = [['start', 'end'], 'agentname'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['radionincentivetype', 'refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, "refresh.label", "GENERATE");
    data_set($col2, "refresh.action", "load");

    $fields = ['radionincentivestatus', 'agrelease', 'print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $start = "curdate()";
    $end = "curdate()";
    $agentid = 0;
    $agent = '';
    $agentname = '';
    $doc = '';
    $printoption = '';
    $agrelease = "''";

    if (isset($config['params']['dataparams'])) {
      if ($config['params']['dataparams']['start'] == null) {
        $start = "null";
      } else {
        $start = "date('" . $config['params']['dataparams']['start'] . "')";
      }

      if ($config['params']['dataparams']['end'] == null) {
        $end = "null";
      } else {
        $end = "date('" . $config['params']['dataparams']['end'] . "')";
      }

      if ($config['params']['dataparams']['agrelease'] == null || $config['params']['dataparams']['agrelease'] == '') {
        $agrelease = "''";
      } else {
        $agrelease = "date('" . $config['params']['dataparams']['agrelease'] . "')";
      }

      $agentid = $config['params']['dataparams']['agentid'];
      $agent = $config['params']['dataparams']['agent'];
      $agentname = $config['params']['dataparams']['agentname'];
      $doc = $config['params']['dataparams']['incentivetype'];
      $printoption = $config['params']['dataparams']['incentivestatus'];
    }

    $qry = "select " . $start . " as `start`, " . $end . " as `end`, '" . $agent . "' as agent, " . $agentid . " as agentid, '" . $agentname . "' as agentname, 
    '" . $doc . "' as incentivetype, '" . $printoption . "' as incentivestatus," . $agrelease  . " as agrelease";
    return $this->coreFunctions->opentable($qry);
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $agentid = $config['params']['dataparams']['agentid'];
    $doc = $config['params']['dataparams']['incentivetype'];

    $doc = $config['params']['dataparams']['incentivetype'];
    if ($doc == "") {
      return ['status' => false, 'msg' => 'Successfully loaded.', 'msg' => 'Please select valid SJ'];
    }

    $filteragent = '';
    $filteragent2 = '';
    if ($agentid != 0) {
      $filteragent = " and i.agentid=" . $agentid;
      $filteragent2 = " and i.agentid2=" . $agentid;
    }

    $qry = "select i.ptrno, i.trno, i.line, date(i.depodate) as depodate, i.acnoid, ar.docno, i.amt, i.clientid, h.ourref, h.clientname,
      i.agentid, ag.clientname as agentname, ag.comm as agentcom, i.agentquota, i.agentcomamt, ag2.comm as agent2com, i.agent2quota, i.agent2comamt,
      i.agentid2, ag2.clientname as manager, 'agent' as agtype, if(i.agrelease is null,'false','true') as added, '" . $end . "' as releasedate,
      ifnull((select group_concat(distinct coa.acnoname separator ', ') from gldetail as d left join coa on coa.acnoid=d.acnoid where d.trno=i.ptrno and d.refx=0 and left(coa.alias,2) in ('CA', 'CR', 'CB')),'') as paymenttype     
      from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
      left join client as ag on ag.clientid=i.agentid
      left join client as ag2 on ag2.clientid=ag.parent
      left join glhead as h on h.trno=i.trno
      where i.doc='" . $doc . "' and i.agrelease is null and date(i.depodate) between ? and ?" . $filteragent . "
      union all
      select i.ptrno, i.trno, i.line, date(i.depodate) as depodate, i.acnoid, ar.docno, i.amt, i.clientid, h.ourref, h.clientname,
      i.agentid, ag.clientname as agentname, ag.comm as agentcom, i.agentquota, i.agentcomamt, ag2.comm as agent2com, i.agent2quota, i.agent2comamt,
      i.agentid2, ag2.clientname as manager, 'manager' as agtype, if(i.ag2release is null,'false','true') as added, '" . $end . "' as releasedate,
      ifnull((select group_concat(distinct coa.acnoname separator ', ') from gldetail as d left join coa on coa.acnoid=d.acnoid where d.trno=i.ptrno and d.refx=0 and left(coa.alias,2) in ('CA', 'CR', 'CB')),'') as paymenttype     
      from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
      left join client as ag on ag.clientid=i.agentid
      left join client as ag2 on ag2.clientid=ag.parent
      left join glhead as h on h.trno=i.trno
      where i.doc='" . $doc . "' and i.ag2release is null and ag2.clientid is not null and i.agentid<>i.agentid2 and date(i.depodate) between ? and ?" . $filteragent2 . " order by docno, depodate";

    $data = $this->coreFunctions->opentable($qry, [$start, $end, $start, $end]);

    foreach ($data as $key => $value) {

      $value->agentcomamt = $value->amt * ($value->agentcom / 100);

      if ($value->agtype == 'agent') {
        $update = [
          'agentcomamt' => $value->agentcomamt,
          'agentcom' => $value->agentcom
        ];
        $this->coreFunctions->sbcupdate("incentives", $update, ['trno' => $value->trno, 'line' => $value->line, 'ptrno' => $value->ptrno]);
      } else {
        $value->agentcom = $value->agentcom / 2;
        $value->agentcomamt = $value->amt * ($value->agentcom / 100);
        $value->agentname = $value->manager;
        $update = [
          'agent2comamt' => $value->agentcomamt,
          'agent2com' => $value->agentcom
        ];
        $this->coreFunctions->sbcupdate("incentives", $update, ['trno' => $value->trno, 'line' => $value->line, 'ptrno' => $value->ptrno]);
      }

      $value->amt = number_format($value->amt, 2);
      $value->agentcomamt = number_format($value->agentcomamt, 2);
    }

    $txtdata = $this->paramsdata($config);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'tableentrydata' => $data, 'txtdata' => $txtdata];
  }

  public function reportsetup($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $style = 'width:500px;max-width:500px;';

    $border = "1px solid ";
    $font =  "Century Gothic";
    $fontsize = "11";

    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $str = '';

    $type = $config['params']['dataparams']['incentivestatus'];
    if ($type == "") {
      return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => 'Please select valid print option', 'style' => $style, 'directprint' => true];
    }

    $doc = $config['params']['dataparams']['incentivetype'];
    if ($doc == "") {
      return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => 'Please select valid SJ ', 'style' => $style, 'directprint' => true];
    }

    $status = $config['params']['dataparams']['incentivestatus'];
    if ($status == "1") {
      if ($config['params']['dataparams']['agrelease'] == null || $config['params']['dataparams']['agrelease'] == '') {
        return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => 'Please select valid Released date', 'style' => $style, 'directprint' => true];
      }
    }

    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];
    $agrelease = $config['params']['dataparams']['agrelease'];
    if ($agrelease != null && $agrelease != '') {
      $agrelease = 'Release Date: ' . date_format(date_create($agrelease), "m/d/Y");
    } else {
      $agrelease = '';
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("AGENT INCENTIVES", '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Date Covered: " . date_format(date_create($start), "m/d/Y") . " to " . date_format(date_create($end), "m/d/Y"), '580', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($agrelease, '220', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Agent', '100', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Payment Date', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Document #', '150', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DR #', '80', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ammount', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Incentive %', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Incentive Amt', '80', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $prev_agent = '';
    $totalamt = 0;
    $totalcom = 0;
    $result = $this->getdata($config);
    $counter = 0;
    foreach ($result as $key => $value) {

      if ($prev_agent != '') {
        if ($prev_agent != $value->clientname) {
          SubtotalHere:
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalamt, $decimal), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, 'T', '', '');
          $str .= $this->reporter->col(number_format($totalcom, $decimal), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $totalamt = 0;
          $totalcom = 0;

          if ($counter >= count($result)) {
            break;
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
        }
      }

      $str .= $this->reporter->startrow();
      $name = $value->clientname;
      if ($prev_agent == $value->clientname) {
        $name = '';
      }
      $str .= $this->reporter->col($name, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->customername, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->depodate, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->docno, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->ourref, '80', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, $decimal), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $amt = 0;
      if ($value->agtype == 'agent') {
        $str .= $this->reporter->col(number_format($value->agentcom, $decimal), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $amt = $value->agentcomamt;
      } else {
        $str .= $this->reporter->col(number_format($value->agent2com, $decimal), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $amt = $value->agent2comamt;
      }
      $str .= $this->reporter->col(number_format($amt, $decimal), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt = $totalamt + $value->amt;
      $totalcom = $totalcom + $amt;

      $prev_agent = $value->clientname;
      $counter = $counter + 1;

      if ($counter >= count($result)) {
        goto SubtotalHere;
      }
    }

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => $str, 'style' => $style, 'directprint' => true];
  }


  private function getdata($config)
  {
    $start = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['start']);
    $end = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['end']);
    $agentid = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];
    $doc = $config['params']['dataparams']['incentivetype'];
    $status = $config['params']['dataparams']['incentivestatus'];

    $start = $this->othersClass->sbcdateformat($start);
    $end = $this->othersClass->sbcdateformat($end);

    $filteragent = '';
    $filteragent2 = '';
    if ($agent != '') {
      $filteragent = ' and i.agentid=' . $agentid;
      $filteragent2 = ' and i.agentid2=' . $agentid;
    }

    $status2 = '';
    if ($status == "1") {
      $agrelease = $config['params']['dataparams']['agrelease'];
      $agrelease = $this->othersClass->sanitizekeyfield('dateid', $agrelease);
      $agrelease = $this->othersClass->sbcdateformat($agrelease);

      $status = " and i.agrelease is not null and date(i.agrelease)='" . $agrelease . "'";
      $status2 = " and i.ag2release is not null and date(i.ag2release)='" . $agrelease . "'";
    } else {
      $status = ' and i.agrelease is null';
      $status2 = ' and i.ag2release is null';
    }

    $qry = "select i.ptrno, i.trno, i.line, date(i.depodate) as depodate, i.acnoid, ar.docno, i.amt, i.clientid, 
    i.agentid, ag.clientname as agentname, ag.comm as agentcom, i.agentquota, i.agentcomamt, i.agent2com, i.agent2quota, i.agent2comamt,
    i.agentid2, ag2.clientname as manager, 'agent' as agtype, i.agrelease as agrelease, ag.clientname as clientname, h.ourref, h.clientname as customername
    from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
    left join client as ag on ag.clientid=i.agentid
    left join client as ag2 on ag2.clientid=ag.parent
    left join glhead as h on h.trno=ar.trno
    where i.doc='" . $doc . "' and date(i.depodate) between '" . $start . "' and '" . $end . "'" . $filteragent . $status . "
    union all
    select i.ptrno, i.trno, i.line, date(i.depodate) as depodate, i.acnoid, ar.docno, i.amt, i.clientid, 
    i.agentid, ag.clientname as agentname, ag.comm as agentcom, i.agentquota, i.agentcomamt, i.agent2com, i.agent2quota, i.agent2comamt,
    i.agentid2, ag2.clientname as manager, 'manager' as agtype, i.ag2release as agrelease, ag2.clientname as clientname, h.ourref, h.clientname as customername
    from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
    left join client as ag on ag.clientid=i.agentid
    left join client as ag2 on ag2.clientid=ag.parent
    left join glhead as h on h.trno=ar.trno
    where i.doc='" . $doc . "' and ag2.clientid is not null and i.agentid<>i.agentid2 
    and date(i.depodate) between '" . $start . "' and '" . $end . "'" . $filteragent2 . $status2 . "
    order by docno, depodate";
    

    return $this->coreFunctions->opentable($qry);
  }
} //end class
