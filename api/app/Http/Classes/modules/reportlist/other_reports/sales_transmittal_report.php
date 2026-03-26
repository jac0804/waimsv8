<?php

namespace App\Http\Classes\modules\reportlist\other_reports;

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

class sales_transmittal_report
{
  public $modulename = 'Sales Transmittal';
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

    $fields = ['start', 'end', 'dcentername', 'dagentname'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', 'StartDate');
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.label', 'EndDate');
    data_set($col2, 'end.readonly', false);

    data_set($col2, 'dagentname.action', 'lookupagentreport');


    $fields = ['radioposttype'];
    $col3 = $this->fieldClass->create($fields);

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
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 
        'default' as print,
        adddate(left(now(),10),-360) as start,
        left(now(),10) as end,
        '" . $defaultcenter[0]['center'] . "' as center,
        '" . $defaultcenter[0]['centername'] . "' as centername,
        '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
        '0' as posttype,
         0 as agentid,
        '' as agent,
        '' as agentname";
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

  public function CBBSI_QUERY($config)
  {

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $isposted = $config['params']['dataparams']['posttype'];
    $agent = $config['params']['dataparams']['agent'];
    $agentid = $config['params']['dataparams']['agentid'];
    $center = $config['params']['dataparams']['center'];
    $filter = "";

    if ($center != "") {
      $filter .= " and num.center= '" . $center . "' ";
    }

    if ($agent != "") {
      $filter .= " and agent.clientid= '" . $agentid . "' ";
    }

    switch ($isposted) {
      case 0: //posted
        $query =  "select head.docno, date(head.dateid) as dateid,
        head.ourref,head.yourref,
        concat(left(client.client,2),right(client.client,5)) as custcode, client.clientname as custname,
        concat(left(agent.client,2),right(agent.client,5)) as agcode, agent.clientname as agname,
        sum(detail.db) as ext,
        ifnull(left(coa.alias,2),'') as alias
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join client as agent on agent.clientid=head.agentid
        left join coa on coa.acno=head.contra
        left join cntnum as num on num.trno=head.trno
        where 
        head.doc='SK'
        and head.dateid between '$start' and '$end'
        $filter 
        group by head.trno,head.docno, head.dateid,
        head.ourref,head.yourref,
        client.client, client.clientname,
        agent.client, agent.clientname,coa.alias
        order by head.dateid";
        break;
      case 1: //unposted
        $query = "
        select 
        allsk.trno,allsk.docno, allsk.dateid,
        allsk.ourref,allsk.yourref,
        allsk.custcode, allsk.custname,
        allsk.agcode, allsk.agname,
        sum(allsk.ext) as ext,
        allsk.alias from (
        select head.trno,head.docno,head.dateid,head.ourref,head.yourref,
        concat(left(c.client,2),right(c.client,5)) as custcode, c.clientname as custname,
        concat(left(a.client,2),right(a.client,5)) as agcode, a.clientname as agname
        ifnull(hs.ext,0) as ext,
        ifnull(left(coa.alias,2),'') as alias
        from lahead as head
        left join client as c on c.client=head.client
        left join client as a on a.client=head.agent
        left join coa on coa.acno=head.contra
        left join transnum as num on num.sitagging=head.trno
        left join hsohead as hh on hh.trno=num.trno
        left join hsostock as hs on hs.trno=hh.trno
        where head.doc='SK' and head.dateid between '$start' and '$end'
        $filter 
        union all
        select head.trno,head.docno,head.dateid,head.ourref,head.yourref,
        concat(left(c.client,2),right(c.client,5)) as custcode, c.clientname as custname,
        concat(left(a.client,2),right(a.client,5)) as agcode, a.clientname as agname,
        case hh.doc when 'DR' then hs.ext else hs.ext*-1 end as ext,
        ifnull(left(coa.alias,2),'') as alias
        from lahead as head
        left join client as c on c.client=head.client
        left join client as a on a.client=head.agent
        left join coa on coa.acno=head.contra
        left join cntnum as num on num.svnum=head.trno
        left join glhead as hh on hh.trno=num.trno
        left join glstock as hs on hs.trno=hh.trno
        where head.doc='SK' and head.dateid between '$start' and '$end'
        $filter 
        ) as allsk
        group by 
        allsk.trno,allsk.docno, allsk.dateid,
        allsk.ourref,allsk.yourref,
        allsk.custcode, allsk.custname,
        allsk.agcode, allsk.agname,
        allsk.alias";
        break;
      default:
        $query = "select  x.trno, x.docno, x.dateid, x.ourref, x.yourref, x.custcode, x.custname, x.agcode, x.agname, sum(x.ext) as ext, x.alias 
        from (select head.trno, head.docno, date(head.dateid) as dateid, head.ourref, head.yourref,
        concat(left(client.client, 2), right(client.client, 5)) as custcode, client.clientname as custname,
        concat(left(agent.client, 2), right(agent.client, 5)) as agcode, agent.clientname as agname,
        sum(detail.db) as ext,
        ifnull(left(coa.alias, 2), '') as alias
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join client on client.clientid = head.clientid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acno = head.contra
        left join cntnum as num on num.trno = head.trno
        where head.doc = 'SK' and head.dateid between '$start' and '$end'
        $filter 
        group by head.trno, head.docno, head.dateid, head.ourref, head.yourref,
        client.client, client.clientname, agent.client, agent.clientname, coa.alias
        union all
        select head.trno, head.docno, date(head.dateid) as dateid, head.ourref, head.yourref,
        concat(left(c.client, 2), right(c.client, 5)) as custcode, c.clientname as custname,
        concat(left(a.client, 2), right(a.client, 5)) as agcode, a.clientname as agname,
        ifnull(hs.ext, 0) as ext,
         ifnull(left(coa.alias, 2), '') as alias
        from lahead as head
        left join client as c on c.client = head.client
        left join client as a on a.client = head.agent
        left join coa on coa.acno = head.contra
        left join transnum as num on num.sitagging = head.trno
        left join hsohead as hh on hh.trno = num.trno
        left join hsostock as hs on hs.trno = hh.trno
        where head.doc = 'SK' and head.dateid between '$start' and '$end'
        $filter 
        union all
        select head.trno, head.docno, date(head.dateid) as dateid, head.ourref, head.yourref,
        concat(left(c.client, 2), right(c.client, 5)) as custcode, c.clientname as custname,
        concat(left(a.client, 2), right(a.client, 5)) as agcode, a.clientname as agname,
        case hh.doc when 'DR' then hs.ext else hs.ext * -1 end as ext,
        ifnull(left(coa.alias, 2), '') as alias
        from lahead as head
        left join client as c on c.client = head.client
        left join client as a on a.client = head.agent
        left join coa on coa.acno = head.contra
        left join cntnum as num on num.svnum = head.trno
        left join glhead as hh on hh.trno = num.trno
        left join glstock as hs on hs.trno = hh.trno
        where head.doc = 'SK' and head.dateid between '$start' and '$end'
        $filter 
        ) as x
        group by  x.trno, x.docno, x.dateid, x.ourref, x.yourref, x.custcode, x.custname, x.agcode, x.agname, x.alias
        order by x.dateid";
    } //end switch

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->CBBSI_QUERY($config);

    $reportdata =  $this->CBBSI_LAYOUT($result, $config);

    return $reportdata;
  }

  private function CBBSI_TABLE_COLS($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Docno', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ourref', '85', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Yourref', '85', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Cust Code', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cust Name', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Agent', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Cash', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Charge', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function CBBSI_HEADER($params)
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
    $center = $params['params']['dataparams']['center'];
    $agent = $params['params']['dataparams']['agent'];
    $agentname = $params['params']['dataparams']['agentname'];
    $isposted = $params['params']['dataparams']['posttype'];
    $companyid = $params['params']['companyid'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

    $qry = "select name,address,tel from center where code = '" . $center1 . "'";
    $headerdata = $this->coreFunctions->opentable($qry);
    $current_timestamp = $this->othersClass->getCurrentTimeStamp();

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sales Transmittal', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '200');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    if ($center != '') {
      $str .= $this->reporter->col('Center:' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Center:' . ' ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
    }

    if ($agentname != '') {
      $str .= $this->reporter->col('Agent:' . $agent . '~' . $agentname, null, null, false, $border, '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Agent:' . ' ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
    }

    if ($isposted == 0) {
      $ming = 'posted';
    } else if ($isposted == 1) {
      $ming = 'unposted';
    } else {
      $ming = 'all';
    }

    $str .= $this->reporter->col('Transaction: ' . strtoupper($ming), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    return $str;
  }

  private function CBBSI_LAYOUT($data, $params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '9';
    $fontsize11 = 10;
    $padding = '';
    $margin = '20';

    $this->reporter->linecounter = 0;

    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $isposted = $params['params']['dataparams']['posttype'];

    $count = 41;
    $page = 40;

    $group = $str = '';
    $c = $totalcash = $totalcharge = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->CBBSI_HEADER($params);

    $str .= $this->CBBSI_TABLE_COLS($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable('800');

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      $cash = 0;
      $charge = 0;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->docno, '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(date('m/d/Y', strtotime($value->dateid)), '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->ourref, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->yourref, '85', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($value->custcode, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->custname, '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->agcode, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      if ($value->alias == 'CA') {
        $cash = $value->ext;
      }

      if ($isposted == 1) { //unposted
        if ($value->alias == 'AR') {
          $charge = $value->ext;
        }
      } else { //posted
        if ($value->alias == 'CR' || $value->alias == 'AR') {
          $charge = $value->ext;
        }
      }

      $str .= $this->reporter->col(number_format($cash, $decimal_currency), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($charge, $decimal_currency), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');


      $str .= $this->reporter->endrow();

      $totalcash = $totalcash + $cash;
      $totalcharge = $totalcharge + $charge;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->CBBSI_HEADER($params);
        }
        $str .= $this->CBBSI_TABLE_COLS($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $str .= $this->reporter->begintable('800');
        $page += $count;
      } # end if


      $str .= $this->reporter->startrow();

      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '85', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '85', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Grand Total :', '90', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col(number_format($totalcash, 2), '90', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcharge, 2), '90', null, false, $border, 'T', 'R', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class