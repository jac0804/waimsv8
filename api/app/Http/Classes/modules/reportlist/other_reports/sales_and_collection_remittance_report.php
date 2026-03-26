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

class sales_and_collection_remittance_report
{
  public $modulename = 'Sales And Collection Remittance';
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

    $fields = ['start', 'end', 'dcentername'];

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', 'StartDate');
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.label', 'EndDate');
    data_set($col2, 'end.readonly', false);


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
        '0' as posttype";
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

  public function default_query($config)
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $isposted = $config['params']['dataparams']['posttype'];
    $center = $config['params']['dataparams']['center'];
    $filter = "";

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

    switch ($isposted) {
      case 0: //posted
        $query =  "select 
        case when head.doc='SK' then 'cash' else 'collection' end as grouping,
        head.docno, head.doc,date(head.dateid) as dateid, head.ourref, client.client as custcode, client.clientname as custname, detail.checkno, date(detail.postdate) as checkdate,
        case when left(coa.alias,2) in ('CA') then detail.db else 0 end as cash,
        case when left(coa.alias,2) = 'CR' then detail.db else 0 end as checkamount
        from glhead as head
        left join gldetail as detail on detail.trno=head.trno
        left join client on client.clientid=head.clientid
        left join client as agent on agent.clientid=head.agentid
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno

        where head.dateid between '$start' and '$end' and
        left(coa.alias,2) in ('CA', 'CR') 
        $filter 
        order by head.docno desc,head.dateid";
        break;
      case 1: //unposted
        $query = "select 
        case when head.doc='SK' then 'cash' else 'collection' end as grouping,
        head.docno, head.doc,date(head.dateid) as dateid, head.ourref, client.client as custcode, client.clientname as custname, detail.checkno, date(detail.postdate) as checkdate,
        case when left(coa.alias,2) = 'CA' then detail.db else 0 end as cash,
        case when left(coa.alias,2) = 'CR' then detail.db else 0 end as checkamount
        from lahead as head
        left join ladetail as detail on detail.trno=head.trno
        left join client on client.client=head.client
        left join client as agent on agent.client=head.agent
        left join coa on coa.acnoid=detail.acnoid
        left join cntnum on cntnum.trno=head.trno
        where left(coa.alias,2) in ('CA', 'CR') 
        and head.dateid between '$start' and '$end'
        $filter 
        order by docno desc, dateid desc";
        break;
      default:
        $query = "select x.grouping, x.docno, x.doc, x.dateid, x.ourref, x.custcode, x.custname, x.checkno, x.checkdate, x.cash, x.checkamount
        from (select case when head.doc='SK' then 'cash' else 'collection' end as grouping,
        head.docno,
        head.doc,
        date(head.dateid) as dateid,
        head.ourref,
        client.client as custcode,
        client.clientname as custname,
        detail.checkno,
        date(detail.postdate) as checkdate,
        case when left(coa.alias,2) = 'CA' then detail.db else 0 end as cash,
        case when left(coa.alias,2) = 'CR' then detail.db else 0 end as checkamount
        from glhead as head
        left join gldetail as detail on detail.trno = head.trno
        left join client on client.clientid = head.clientid
        left join client as agent on agent.clientid = head.agentid
        left join coa on coa.acnoid = detail.acnoid
        left join cntnum on cntnum.trno = head.trno
        where date(head.dateid) between '$start' and '$end'
        and left(coa.alias,2) in ('CA', 'CR')
        $filter
        union all
        select case when head.doc='SK' then 'cash' else 'collection' end as grouping,
        head.docno,
        head.doc,
        date(head.dateid) as dateid,
        head.ourref,
        client.client as custcode,
        client.clientname as custname,
        detail.checkno,
        date(detail.postdate) as checkdate,
        case when left(coa.alias,2) = 'CA' then detail.db else 0 end as cash,
        case when left(coa.alias,2) = 'CR' then detail.db else 0 end as checkamount
        from lahead as head
        left join ladetail as detail on detail.trno = head.trno
        left join client on client.client = head.client
        left join client as agent on agent.client = head.agent
        left join coa on coa.acnoid = detail.acnoid
        left join cntnum on cntnum.trno = head.trno
        where date(head.dateid) between '$start' and '$end'
        and left(coa.alias,2) in ('CA', 'CR')
        $filter
        ) as x
        order by x.docno desc, x.dateid desc;";
    } //end switch

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }
  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->default_query($config);

    $reportdata =  $this->DEFAULT_LAYOUT($result, $config);
    return $reportdata;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Docno', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Ourref', '80', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Cust Code', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cust Name', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Check No', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check Date', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Check Amount', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cash', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_HEADER($params)
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
    $str .= $this->reporter->col('SALES AND COLLECTION REMITTANCE', null, null, false, $border, '', '', $font, '15', 'B', '', '');
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

  private function DEFAULT_LAYOUT($data, $params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '9';
    $fontsize10 = 10;
    $fontsize11 = 10;
    $padding = '';
    $margin = '20';

    $this->reporter->linecounter = 0;

    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 41;
    $page = 40;

    $group = $str = '';
    $c = $subcash = $subcheck = $totalcash = $totalcheck = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->DEFAULT_HEADER($params);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize10, $params);
    #header end

    #loop starts

    $cashLabel = 0;
    $checkLabel = 0;

    $str .= $this->reporter->begintable('800');

    foreach ($data as $key => $value) {
      $cnt1 += 1;

      if ($value->grouping == 'cash') {
        if ($cashLabel == 0) {

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('Cash Sales', '90', null, false, $border, '', 'L', $font, $fontsize11, 'B', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');

          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');

          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');

          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
          $cashLabel = 1;
        }
      } else {
        if ($checkLabel == 0) {

          if ($subcash > 0 || $subcheck > 0) {
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $fontsize11, 'B', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');

            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('Total: ', '90', null, false, $border, '', 'L', $font, $fontsize11, 'B', '', '');

            $str .= $this->reporter->col(number_format($subcash, 2), '90', null, false, $border, '', 'R', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col(number_format($subcheck, 2), '90', null, false, $border, '', 'R', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->endrow();
            $subcash = 0;
            $subcheck = 0;


            $str .= $this->reporter->startrow();
            $str .= $this->reporter->addline();
            $str .= $this->reporter->col('&nbsp', '90', null, false, $border, '', 'L', $font, $fontsize11, 'B', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');

            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');

            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $fontsize11, 'B', '', '');

            $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $fontsize10, 'B', '', '');
            $str .= $this->reporter->endrow();
          }
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->addline();
          $str .= $this->reporter->col('Collection', '90', null, false, $border, '', 'L', $font, $fontsize11, 'B', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');

          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');

          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');

          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
          $str .= $this->reporter->endrow();
          $checkLabel = 1;
        }
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($value->docno, '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(date('m/d/Y', strtotime($value->dateid)), '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->ourref, '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($value->custcode, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($value->custname, '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($value->checkno, '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(date('m/d/Y', strtotime($value->checkdate)), '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col(number_format($value->cash, 2), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($value->checkamount, 2), '90', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      $subcash = $subcash + $value->cash;
      $subcheck = $subcheck + $value->checkamount;
      $totalcash = $totalcash + $value->cash;
      $totalcheck = $totalcheck + $value->checkamount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->DEFAULT_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize10, $params);
        #header end
        $str .= $this->reporter->begintable('800');
        $page += $count;
      } # end if

      $str .= $this->reporter->startrow();

      $str .= $this->reporter->endrow();
    } //end foreach

    if ($subcash > 0 || $subcheck > 0) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $fontsize11, 'B', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '80', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('', '90', null, false, $border, '', 'L', $font, $font_size, '', '', '');

      $str .= $this->reporter->col('', '90', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col('Total: ', '90', null, false, $border, '', 'L', $font, $fontsize11, 'B', '', '');

      $str .= $this->reporter->col(number_format($subcash, 2), '90', null, false, $border, '', 'R', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->col(number_format($subcheck, 2), '90', null, false, $border, '', 'R', $font, $fontsize10, 'B', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Grand Total:', '90', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col(number_format($totalcash, 2), '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalcheck, 2), '90', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class