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

class viewincentivesagentannual
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'AGENT INCENTIVES - ANNUAL';
  public $gridname = 'tableentry';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%';
  public $issearchshow = true;
  public $showclosebtn = false;

  private $reporter;

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
    $tab = [
      'tableentry' => ['action' => 'warehousingentry', 'lookupclass' => 'viewgridincentivesannual', 'label' => 'LIST']
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
    data_set($col3, "agrelease.lookupclass", "agreleaseyr");

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $start = "DATE_FORMAT(NOW() ,'%Y-01-01')";
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

    $return = [];

    $filteragent = '';
    $filteragent2 = '';
    if ($agentid != 0) {
      $filteragent = " and i.agentid=" . $agentid;
      $filteragent2 = " and i.agentid2=" . $agentid;
    }

    $qry = "select agentid, clientname, quota, comm from (
      select ag.clientid as agentid, ag.clientname, ag.quota, ag.comm from incentives as i left join client as ag on ag.clientid=i.agentid where ag.clientid is not null and date(i.depodate) between ? and ? " . $filteragent . " group by ag.clientid, ag.clientname, ag.quota, ag.comm
      union all
      select ag.clientid as agentid, ag.clientname, ag.quota, ag.comm from incentives as i left join client as ag on ag.clientid=i.agentid2 where ag.clientid is not null and date(i.depodate) between ? and ? " . $filteragent2 . " group by ag.clientid, ag.clientname, ag.quota, ag.comm) 
      as ag group by agentid, clientname, quota, comm order by clientname";
    $agent = $this->coreFunctions->opentable($qry, [$start, $end, $start, $end]);

    $this->coreFunctions->execqry('delete from incentivesyr where agrelease is null');

    foreach ($agent as $ag => $agval) {
      $qry = "select i.ptrno, i.trno, i.line, date(i.depodate) as depodate, i.acnoid, ar.docno, i.amt, i.clientid, 
      i.agentid, ag.clientname as agentname, i.agentcom, i.agentquota, i.agentcomamt, i.agent2com, i.agent2quota, i.agent2comamt,
      i.agentid2, ag2.clientname as manager, 'agent' as agtype, 'false' as added, '" . $end . "' as releasedate, i.agreleaseyr, i.ag2releaseyr
      from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
      left join client as ag on ag.clientid=i.agentid
      left join client as ag2 on ag2.clientid=i.agentid2
      where i.doc='" . $doc . "' and date(i.depodate) between ? and ? and i.agentid=" . $agval->agentid;

      $data = $this->coreFunctions->opentable($qry, [$start, $end]);

      $isquota = false;
      $totalamt = 0;
      $totalreleaseamt = 0;
      $comamt = 0;
      $managerid = 0;
      $managercom = 0;
      $managercomamt = 0;

      $arr_manager = [];

      if (!empty($data)) {
        foreach ($data as $key => $value) {
          $totalamt = $totalamt + $value->amt;
          if ($value->agreleaseyr != null) {
            $totalreleaseamt = $totalreleaseamt + $value->amt;
          }
        }

        if ($totalamt >= $agval->quota) {
          $isquota = true;

          foreach ($data as $key => $value) {
            if ($value->agreleaseyr == null) {
              $comamt = $comamt + ($value->amt * ($agval->comm / 100));
            }
          }

          $qry = "select sum(amt) as amt, agentid2, agentid, agentcom, ag2releaseyr, doc from incentives 
          where doc='" . $doc . "' and agentid=? and agentid2<>0 and date(depodate) between ? and ? and ag2releaseyr is null
          group by agentid2, agentid, agentcom, ag2releaseyr, doc";
          $managercom = $this->coreFunctions->opentable($qry, [$agval->agentid, $start, $end]);

          foreach ($managercom as $mc => $mv) {

            $managercom = $mv->agentcom / 2;
            $managercomamt =  $mv->amt *  ($managercom / 100);

            if ($managercomamt != 0) {
              array_push($arr_manager, [
                'agentid' => $mv->agentid2,
                'agentquota' => 0,
                'amt' => $mv->amt,
                'agentcom' => $managercom,
                'agentcomamt' => $managercomamt,
                'agtype' => 1,
                'sjagent' => $mv->agentid,
                'doc' => $mv->doc
              ]);
            }
          }
        }
      }

      if ($totalamt != 0) {
        if ($totalamt - $totalreleaseamt > 0) {
          $arr = [
            'sdate' => $start,
            'edate' => $end,
            'agentid' => $agval->agentid,
            'agentquota' => $agval->quota,
            'amt' => $totalamt,
            'agentcom' => $agval->comm,
            'agentcomamt' => $comamt,
            'doc' => $doc
          ];
          $this->coreFunctions->sbcinsert("incentivesyr", $arr);
        }

        $arr2 = [];
        if ($isquota) {
          foreach ($arr_manager as $kman => $vman) {
            $arr2 = [
              'sdate' => $start,
              'edate' => $end,
              'agentid' => $vman['agentid'],
              'agentquota' => 0,
              'amt' => $vman['amt'],
              'agentcom' => $vman['agentcom'],
              'agentcomamt' => $vman['agentcomamt'],
              'agtype' => 1,
              'doc' => $vman['doc'],
              'sjagent' => $vman['sjagent']
            ];
            $this->coreFunctions->sbcinsert("incentivesyr", $arr2);
          }
        }
      } //end off inserting incentivesyr
    } //end of agent

    if ($agentid != 0) {
      $filteragent = " and i.agentid=" . $agentid;
    }

    $return = $this->coreFunctions->opentable("select 'false' as added,  i.agentid, client.clientname as agentname, FORMAT(i.agentquota,2) as agentquota, 
    FORMAT(sum(i.amt),2) as amt, i.agentcom, FORMAT(sum(i.agentcomamt),2) as agentcomamt, if(i.agtype=1,'MANAGER','AGENT') as agtype, i.agtype as agtypecode, i.sdate, i.edate, i.sjagent
    from incentivesyr as i left join client on client.clientid=i.agentid
    where i.doc='" . $doc . "' and i.agrelease is null " . $filteragent . "
    group by i.agentid, client.clientname, i.agentquota, i.agentcom, i.agtype, i.sdate, i.edate, i.sjagent
    order by i.agtype, client.clientname");

    $txtdata = $this->paramsdata($config);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'tableentrydata' => $return, 'txtdata' => $txtdata];
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
    $str .= $this->reporter->col("AGENT INCENTIVES (ANNUAL)", '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
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
    $str .= $this->reporter->col('Agent', '250', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '10', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quota', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Incentive %', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Incentive Amt', '110', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Type', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $totalamt = 0;
    $totalcom = 0;
    $result = $this->getdata($config);

    foreach ($result as $key => $value) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->agentname, '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($value->amt, $decimal), '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->agentquota != 0 ? number_format($value->agentquota, $decimal) : '-', '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->agentcom != 0 ? number_format($value->agentcom, $decimal) : '-', '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->agentcomamt != 0 ? number_format($value->agentcomamt, $decimal) : '-', '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->agtype, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt = $totalamt + $value->amt;
      $totalcom = $totalcom + $value->agentcomamt;
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '250', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, $decimal), '10', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcom, $decimal), '110', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

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

    $filteragent = '';
    if ($agent != '') {
      $filteragent = ' and i.agentid=' . $agentid;
    }

    if ($status == "1") {
      $agrelease = $config['params']['dataparams']['agrelease'];
      $agrelease = $this->othersClass->sanitizekeyfield('dateid', $agrelease);
      $agrelease = $this->othersClass->sbcdateformat($agrelease);

      $status = " and i.agrelease is not null and date(i.agrelease)='" . $agrelease . "'";
    } else {
      $status = ' and i.agrelease is null';
    }

    $qry = "select 'false' as added,  i.agentid, client.clientname as agentname, i.agentquota, 
    sum(i.amt) as amt, i.agentcom, sum(i.agentcomamt) as agentcomamt, if(i.agtype=1,'MANAGER','AGENT') as agtype, i.agtype as agtypecode, i.sdate, i.edate, i.sjagent
    from incentivesyr as i left join client on client.clientid=i.agentid
    where i.doc='" . $doc . "' and date(i.sdate)>='" . $start . "' and date(i.edate)<='" . $end . "' " . $status . $filteragent . "
    group by i.agentid, client.clientname, i.agentquota, i.agentcom, i.agtype, i.sdate, i.edate, i.sjagent
    order by i.agtype, client.clientname";

    return $this->coreFunctions->opentable($qry);
  }
} //end class
