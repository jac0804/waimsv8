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

class life_plan_agreement_list
{
  public $modulename = 'Lifeplan Agreement List';
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
    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dagentname', 'radioposttype'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.label', 'Payor');
    data_set($col1, 'dclientname.lookupclass', 'payors');
    data_set(
      $col1,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );
    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as dclientname,
      '' as clientname,
      '' as client,
      '0' as clientid,
      '' as dagentname,
      '' as agent,
      '' as agentname,
      0 as agentid,
      '0' as posttype
      ");
  }

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
    $result = $this->reportDefaultLayout($config);
    return $result;
  }

  public function reportDefault($config)
  {
    $center = $config['params']['center'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $clientid = $config['params']['dataparams']['clientid'];
    $client = $config['params']['dataparams']['client'];
    $agentid = $config['params']['dataparams']['agentid'];
    $agent = $config['params']['dataparams']['agent'];
    $leftjoin = "";
    $filter = " where cntnum.doc='CP' and cntnum.center='" . $center . "' and date(head.dateid) between '" . $start . "' and '" . $end . "' ";
    if ($agent != '') $filter .= " and agent.clientid='" . $agentid . "' ";
    if ($client != "") {
      $leftjoin .= " left join client as cl on cl.client=ehead.client";
      $filter .= " and cl.clientid='$clientid' ";
    }

    switch ($config['params']['dataparams']['posttype']) {
      case 0: // posted
        $qry = "select 'Posted' as status, head.docno, ehead.docno as contractno, concat(info.lname, ' ', info.fname, ' ', info.mname, ' ', info.ext) as planholder, DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), info.bday)), '%Y') + 0 as age,
          plan.name as plantype, agent.clientname as agentname, concat(ehead.lname, ' ', ehead.fname, ' ', ehead.mname, ' ', ehead.ext) as payor, ehead.terms
          from glhead as head
          left join cntnum on cntnum.trno=head.trno
          left join heahead as ehead on ehead.trno=head.aftrno
          left join heainfo as info on info.trno=ehead.trno
          left join plantype as plan on plan.line=ehead.planid
          left join client as agent on agent.client=ehead.agent " . $filter;
        break;
      case 1: // unposted
        $qry = "select 'Unposted' as status, head.docno, ehead.docno as contractno, concat(info.lname, ' ', info.fname, ' ', info.mname, ' ', info.ext) as planholder, DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), info.bday)), '%Y') + 0 as age,
          plan.name as plantype, agent.clientname as agentname, concat(ehead.lname, ' ', ehead.fname, ' ', ehead.mname, ' ', ehead.ext) as payor, ehead.terms
          from lahead as head
          left join cntnum on cntnum.trno=head.trno
          left join heahead as ehead on ehead.trno=head.aftrno
          left join heainfo as info on info.trno=ehead.trno
          left join plantype as plan on plan.line=ehead.planid
          left join client as agent on agent.client=ehead.agent " . $filter;
        break;
      default: // all
        $qry = "select 'Unposted' as status, head.docno, ehead.docno as contractno, concat(info.lname, ' ', info.fname, ' ', info.mname, ' ', info.ext) as planholder, DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), info.bday)), '%Y') + 0 as age,
          plan.name as plantype, agent.clientname as agentname, concat(ehead.lname, ' ', ehead.fname, ' ', ehead.mname, ' ', ehead.ext) as payor, ehead.terms
          from lahead as head
          left join cntnum on cntnum.trno=head.trno
          left join heahead as ehead on ehead.trno=head.aftrno
          left join heainfo as info on info.trno=ehead.trno
          left join plantype as plan on plan.line=ehead.planid
          left join client as agent on agent.client=ehead.agent " . $filter . "
        union all
        select 'Posted' as status, head.docno, ehead.docno as contractno, concat(info.lname, ' ', info.fname, ' ', info.mname, ' ', info.ext) as planholder, DATE_FORMAT(FROM_DAYS(DATEDIFF(NOW(), info.bday)), '%Y') + 0 as age,
          plan.name as plantype, agent.clientname as agentname, concat(ehead.lname, ' ', ehead.fname, ' ', ehead.mname, ' ', ehead.ext) as payor, ehead.terms
          from glhead as head
          left join cntnum on cntnum.trno=head.trno
          left join heahead as ehead on ehead.trno=head.aftrno
          left join heainfo as info on info.trno=ehead.trno
          left join plantype as plan on plan.line=ehead.planid
          left join client as agent on agent.client=ehead.agent " . $filter;
        break;
    }

    return $this->coreFunctions->opentable($qry);
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $count = 38;
    $page = 40;
    $str = "";
    $layoutsize = "1000";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = '10';
    $border = '1px solid';

    if (empty($result)) return $this->othersClass->emptydata($config);

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config, $layoutsize);
    $str .= $this->tableheader($layoutsize, $config);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '125', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->contractno, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->planholder, '125', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->age, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->plantype, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->agentname, '125', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->payor, '125', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->terms, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->status, '100', null, false, $border, 'B', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endtable();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->header_DEFAULT($config, $layoutsize);
        $str .= $this->tableheader($layoutsize, $config);
        $page += $count;
      }
    }
    $str .= $this->reporter->endreport();
    return $str;
  }

  public function header_DEFAULT($config, $layoutsize)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Lifeplan Agreement List', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

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
    $str .= $this->reporter->col('Contract No.', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Application No.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Plan Holder', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Age', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Plan Type', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Agent', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Payor', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Payment Terms', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class