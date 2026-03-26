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
use App\Http\Classes\modules\inventory\va;
use App\Http\Classes\sqlquery;
use App\Http\Classes\SBCPDF;

class customer_sales_report
{
  public $modulename = 'Customer Sales Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '800'];

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
    $systemtype = $this->companysetup->getsystemtype($config['params']);

    switch ($companyid) {
      case 1: //vitaline
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername'];
        $col1 = $this->fieldClass->create($fields);
        break;
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'brandname'];
        $col1 = $this->fieldClass->create($fields);
        break;
      case 10: //afti
      case 12: //afti usd
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'project', 'ddeptname', 'industry', 'radiotypeofreportsales'];
        $col1 = $this->fieldClass->create($fields);
        data_set($col1, 'project.required', false);
        data_set($col1, 'ddeptname.label', 'Department');
        data_set($col1, 'project.label', 'Item Group');
        data_set($col1, 'industry.readonly', true);
        data_set($col1, 'industry.type', 'lookup');
        data_set($col1, 'industry.lookupclass', 'lookupindustry');
        data_set($col1, 'industry.action', 'lookupindustry');
        data_set($col1, 'dcentername.required', false);
        break;

      case 21: //king george
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'categoryname', 'subcatname', 'brandname', 'radiotypeofreportsales'];
        goto createCol1Here;
        break;

      case 36: //rozlab
        $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'groupid', 'dagentname', 'categoryname', 'subcatname', 'customercategory', 'radiotypeofreportsales'];
        data_set($col1, 'groupid.lookupclass', 'lookupclientgroupledger');
        data_set($col1, 'groupid.action', 'lookupclientgroupledger');
        data_set($col1, 'groupid.class', 'csgroup sbccsreadonly');
        data_set($col1, 'groupid.readonly', true);
        goto createCol1Here;
        break;
      default:
        if ($systemtype == 'AMS' || $systemtype == 'EAPPLICATION') {
          $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname'];
        } else {
          if ($companyid == 36) { //rozlab

          } else if ($companyid == 59) { //roosevelt
            $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'categoryname', 'subcatname', 'area', 'radiotypeofreportsales'];
          } else {
            $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername', 'dagentname', 'categoryname', 'subcatname', 'radiotypeofreportsales'];
          }
        }
        createCol1Here:
        $col1 = $this->fieldClass->create($fields);
        if ($companyid == 59) { // roosevelt
          data_set($col1, 'area.readonly', true);
        }
        data_set($col1, 'dcentername.required', false);
        break;
    }

    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    if ($companyid == 34) { //evergreen
      data_set($col1, 'dclientname.label', 'Payor');
    }
    data_set($col1, 'categoryname.action', 'lookupcategoryitemstockcard');

    data_set($col1, 'subcatname.action', 'lookupsubcatitemstockcard');
    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 52: //technolab
        $fields = ['radiotypeofreportdrsi', 'radioposttype', 'radiosortby'];
        break;
      case 59: //roosevelt
        $fields = ['radiotypeofreportformat', 'radioposttype', 'radiosortby'];
        break;
      default:
        $fields = ['radioposttype', 'radiosortby'];
        break;
    }
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'radioposttype.options', [
      ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
      ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
      ['label' => 'All', 'value' => '2', 'color' => 'teal']
    ]);

    if ($companyid == 59) { //roosevelt
      data_set($col2, 'radiosortby.options', [
        ['label' => 'Document #', 'value' => 'docno', 'color' => 'orange'],
        ['label' => 'Date', 'value' => 'dateid', 'color' => 'orange'],
        ['label' => 'Customer Name', 'value' => 'clientname', 'color' => 'orange']
      ]);
    }

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];
    // $addparams = '';
    // if ($companyid == 59) { //roosevelt
    //   $addparams = " ,  'sis' as typeofformat";
    // }

    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,left(now(),10) as end,
                        '' as client,'0' as clientid, '' as clientname,'report' as typeofreport, 'sis' as typeofformat,
                        '0' as posttype,'docno' as sortby,'' as dclientname,'' as dagentname,'' as agent,
                         '' as category,'' as categoryname,'' as subcat, 'si' as typeofdrsi,
                         '" . $defaultcenter[0]['center'] . "' as center,
                         '" . $defaultcenter[0]['centername'] . "' as centername,
                         '" . $defaultcenter[0]['dcentername'] . "' as dcentername
                         
                         ,'' as project, '' as projectid, '' as projectname, '' as ddeptname, '' as dept, '' as deptname,'' as industry 
                         
                         , '' as brandname, '' as brandid

                         , '' as brand,'' as agentid,'' as agentname, '' as area

                         , '' as subcatname, '' as subcatid, '' as customercategory, '' as category_id, '' as category_name,'' as groupid 
                         ";


    return $this->coreFunctions->opentable($paramstr);
  }

  // put here the plotting string if direct printing
  public function getloaddata($config)
  {
    return [];
  }
  // 
  public function reportdata($config)
  {
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }
  // GET THE FINISH LAYOUT OF REPORT
  public function reportplotting($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $format = $config['params']['dataparams']['typeofformat'];
    // ADD SWITCH IF EVER MORE LAYOUT OR PER COMPANY

    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $typeofdrsi = $config['params']['dataparams']['typeofdrsi'];
        $result = $this->REBATE_DEFAULT_LAYOUT($config);
        break;
      case 28: //xcomp
        switch ($typeofreport) {
          case 'report':
            $result = $this->xcomp_Layout_REPORT($config);
            break;
          case 'lessreturn':
            $result = $this->xcomp_Layout_LESSRETURN($config);
            break;
          case 'return':
            $result = $this->xcomp_Layout_RETURN($config);
            break;
        }
        break;
      case 32: //3m
        switch ($typeofreport) {
          case 'report':
            $result = $this->threeem_Layout_REPORT($config);
            break;
          case 'lessreturn':
            $result = $this->threeem_Layout_LESSRETURN($config);
            break;
          case 'return':
            $result = $this->threeem_Layout_RETURN($config);
            break;
        }
        break;
      case 26: //ams beehealthy
      case 35: //ams aquamax
      case 48: //ams seastar
      case 34: //eapplication evergreen
        switch ($typeofreport) {
          case 'report':
            $result = $this->ams_eapplication_Layout_REPORT($config);
            break;
          case 'lessreturn':
            $result = $this->ams_eapplication_Layout_LESSRETURN($config);
            break;
          case 'return':
            $result = $this->ams_eapplication_Layout_RETURN($config);
            break;
        }
        break;
      case 21: //king george
        switch ($typeofreport) {
          case 'report':
            $result = $this->kinggeorgeLayout_REPORT($config);
            break;
          case 'lessreturn':
            $result = $this->reportDefaultLayout_LESSRETURN($config);
            break;
          case 'return':
            $result = $this->reportDefaultLayout_RETURN($config);
            break;
        }
        break;
      case 59: //roosevelt
        switch ($format) {
          case 'sis': //Sales Invoice Summary
            switch ($typeofreport) {
              case 'report':
              case 'lessreturn':
              case 'return':
                $result = $this->roosevelt_sis_reportDefaultLayout_REPORT($config);
                break;
            }
            break;
          case 'gft': //Government for Tin
            switch ($typeofreport) {
              case 'report':
              case 'lessreturn':
              case 'return':
                $result = $this->roosevelt_gft_reportDefaultLayout_REPORT($config);
                break;
            }
            break;
        }

        break;

      default:
        switch ($typeofreport) {
          case 'report':
            $result = $this->reportDefaultLayout_REPORT($config);
            break;
          case 'lessreturn':
            $result = $this->reportDefaultLayout_LESSRETURN($config);
            break;
          case 'return':
            $result = $this->reportDefaultLayout_RETURN($config);
            break;
        }
        break;
    }

    return $result;
  }


  //header
  private function HEADER_LESS_REBATE($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $typeofdrsi = $config['params']['dataparams']['typeofdrsi'];

    $label = "Delivery Receipt";

    if ($typeofdrsi == 'si') {
      $label = "Sales Invoice";
    }

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Type: ' . $label, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } elseif ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    if ($config['params']['companyid'] == 23 || $config['params']['companyid'] == 41  || $config['params']['companyid'] == 52) { // labsol cebu, labsol manila & technolab
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Brand: ' . ($config['params']['dataparams']['brandname'] != '' ? $config['params']['dataparams']['brandname'] : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SALES LESS REBATE', $layoutsize, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PO #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  //data
  public function LESS_REBATE($config)
  {
    $companyid = $config['params']['companyid'];
    $center       = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $typeofdrsi = $config['params']['dataparams']['typeofdrsi'];

    $filter = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($typeofdrsi == 'si') {
      $filter .= " and left(head.docno, 2) = 'SI'";
    } else {
      $filter .= " and left(head.docno, 2) = 'DR'";
    }

    switch ($companyid) {
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $brandid = $config['params']['dataparams']['brandid'];
        if ($config['params']['dataparams']['brandname'] != '') {
          $filter .= " and brand.brandid=" . $brandid;
        }
        break;
    }

    switch ($posttype) {
      case '0': // posted
        switch ($companyid) {
          case 23: //labsol cebu
          case 41: //labsol paranaque
          case 52: //technolab
            $query = "select 'posted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                        client.client, client.clientname, 
                        (select sum(d.db-d.cr) from gldetail as d left join coa on coa.acnoid=d.acnoid where d.trno=head.trno and coa.alias IN ('AR1','AR2','AR3')) as amount
                      from glhead as head
                      left join glstock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join frontend_ebrands as brand on brand.brandid=item.brand
                      left join client as client on client.clientid = head.clientid
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ' and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.trno,head.docno, head.yourref, client.client, client.clientname, head.dateid
                      order by $sortby
                    ";
            break;
          case 1: //vitaline
            $query = "select status,docno,yourref,dateid,client,clientname,(db-cr) as amount 
                      from (select 'posted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                        client.client, client.clientname ,agent.client as agentcode,agent.clientname as agentname,
                        item.brand, sum(detail.db) as db, sum(detail.cr) as cr
                      from glhead as head
                      left join gldetail as detail on detail.trno = head.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as client on client.clientid = head.clientid
                      left join cntnum as cntnum on cntnum.trno = head.trno 
                      left join glstock as stock on stock.trno=head.trno 
                      left join client as agent on agent.clientid=head.agentid 
                      left join item on item.itemid=stock.itemid
                      where head.doc = 'SJ' and coa.alias IN ('AR1','AR3') and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.docno, head.yourref, client.client, client.clientname, 
                               head.dateid ,agent.client,agent.clientname,item.brand
                      order by $sortby) as k
                      group by status,docno,yourref,dateid,client,clientname,db,cr
                    ";
            break;
        }

        break;

      case '1': // unposted
        switch ($companyid) {
          case 23: //labsol cebu
          case 41: //labsol paranaque
          case 52: //technolab
            $query = "select
                        'posted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                        client.client, client.clientname, 
                      (select sum(d.db-d.cr) from ladetail as d left join coa on coa.acnoid=d.acnoid where d.trno=head.trno and coa.alias IN ('AR1','AR2','AR3') ) as amount
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join frontend_ebrands as brand on brand.brandid=item.brand
                      left join client as client on client.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ' and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.trno,head.docno, head.yourref, client.client, client.clientname, head.dateid
                      order by $sortby
                    ";
            break;
          case 1: //vita
            $query = "select 'posted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                        client.client, client.clientname, sum(detail.db - detail.cr) as amount
                      from lahead as head
                      left join ladetail as detail on detail.trno = head.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as client on client.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ' and coa.alias IN ('AR1','AR3') and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.docno, head.yourref, client.client, client.clientname, head.dateid
                      order by $sortby
                    ";
            break;
        }

        break;

      default:
        switch ($companyid) { // all
          case 23: //labsol cebu
          case 41: //labsol paranaque
          case 52: //technolab
            $query = "select 'unposted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                      client.client, client.clientname, 
                      (select sum(d.db-d.cr) from ladetail as d left join coa on coa.acnoid=d.acnoid where d.trno=head.trno and coa.alias IN ('AR1','AR2','AR3') ) as amount
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join frontend_ebrands as brand on brand.brandid=item.brand
                      left join client as client on client.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ'  and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.trno,head.docno, head.yourref, client.client, client.clientname, head.dateid
                      union all
                      select 'posted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                      client.client, client.clientname, 
                      (select sum(d.db-d.cr) from gldetail as d left join coa on coa.acnoid=d.acnoid where d.trno=head.trno and coa.alias IN ('AR1','AR2','AR3')) as amount
                      from glhead as head
                      left join glstock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join frontend_ebrands as brand on brand.brandid=item.brand
                      left join client as client on client.clientid = head.clientid
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ'   and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.trno,head.docno, head.yourref, client.client, client.clientname, head.dateid
                      order by $sortby
                    ";
            break;
          case 1: //vita
            $query = "select 'posted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                      client.client, client.clientname, sum(detail.db - detail.cr) as amount
                      from glhead as head
                      left join gldetail as detail on detail.trno = head.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as client on client.clientid = head.clientid
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ' and coa.alias IN ('AR1','AR3')  and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.docno, head.yourref, client.client, client.clientname, head.dateid
                      union all
                      select 'posted' as status, head.docno, head.yourref, date(head.dateid) as dateid,
                      client.client, client.clientname, sum(detail.db - detail.cr) as amount
                      from lahead as head
                      left join ladetail as detail on detail.trno = head.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as client on client.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ' and coa.alias IN ('AR1','AR3')  and date(head.dateid) between '" . $start . "' and '" . $end . "' $filter
                      group by head.docno, head.yourref, client.client, client.clientname, head.dateid
                      order by $sortby
                    ";
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  //header
  private function HEADER_REBATE($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $typeofdrsi = $config['params']['dataparams']['typeofdrsi'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $label = "Delivery Receipt";

    if ($typeofdrsi == 'si') {
      $label = "Sales Invoice";
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();
    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Type: ' . $label, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else {
      $posttype = 'Unposted';
    }

    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();

    if ($config['params']['companyid'] == 23 || $config['params']['companyid'] == 41 || $config['params']['companyid'] == 52) { //labsol & technolab
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Brand: ' . ($config['params']['dataparams']['brandname'] != '' ? $config['params']['dataparams']['brandname'] : 'ALL'), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('REBATE', $layoutsize, null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '400', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  //data
  public function REBATE($config)
  {
    $companyid = $config['params']['companyid'];
    $center       = $config['params']['dataparams']['center'];

    $clientid       = $config['params']['dataparams']['clientid'];
    $client       = $config['params']['dataparams']['client'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $typeofdrsi = $config['params']['dataparams']['typeofdrsi'];

    $filter = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($typeofdrsi == 'si') {
      $filter .= " and left(head.docno, 2) = 'SI'";
    } else {
      $filter .= " and left(head.docno, 2) = 'DR'";
    }

    switch ($companyid) {
      case 23: //labsol cebu
      case 41: //labsol paranaque
      case 52: //technolab
        $brandid = $config['params']['dataparams']['brandid'];
        if ($config['params']['dataparams']['brandname'] != '') {
          $filter .= " and brand.brandid=" . $brandid;
        }
        break;
    }

    switch ($posttype) {
      case '0': // posted
        $query = "select 'posted' as status, head.docno, date(head.dateid) as dateid, client.client, 
                         client.clientname , sum(detail.cr) as amount
                  from glhead as head
                  left join glstock as stock on stock.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join frontend_ebrands as brand on brand.brandid=item.brand
                  left join gldetail as detail on detail.trno = head.trno
                  left join coa as coa on coa.acnoid = detail.acnoid
                  left join client as client on client.clientid = head.clientid
                  left join cntnum as cntnum on cntnum.trno = head.trno
                  where head.doc = 'SJ' and coa.alias IN ('AR3') $filter
                  group by head.docno, client.client, client.clientname, head.dateid
                  order by $sortby ";

        break;

      case '1': // unposted
        $query = "select status,docno,dateid,client,clientname,amount from (
            select 'posted' as status, head.docno, date(head.dateid) as dateid,
                        client.client, client.clientname ,agent.client as agentcode,
                        agent.clientname as agentname,item.brand, sum(detail.cr) as amount
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join frontend_ebrands as brand on brand.brandid=item.brand
                      left join ladetail as detail on detail.trno = head.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as client on client.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      left join client as agent on agent.client=head.agent
                      where head.doc = 'SJ' and coa.alias IN ('AR3') $filter
                      group by head.docno, client.client, client.clientname, head.dateid,agent.client,agent.clientname,item.brand
                      order by $sortby) as k
                      group by status,docno,dateid,client,clientname,amount
                      ";

        break;
      default:
        $query = "select 'posted' as status, head.docno, date(head.dateid) as dateid,
                          client.client, client.clientname , sum(detail.cr) as amount
                      from glhead as head
                      left join glstock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join frontend_ebrands as brand on brand.brandid=item.brand
                      left join gldetail as detail on detail.trno = head.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as client on client.clientid = head.clientid
                      left join cntnum as cntnum on cntnum.trno = head.trno
                      where head.doc = 'SJ' and coa.alias IN ('AR3') $filter
                      group by head.docno, client.client, client.clientname, head.dateid
                      union all
                      select 'unposted' as status, head.docno, date(head.dateid) as dateid,
                            client.client, client.clientname , sum(detail.cr) as amount
                      from lahead as head
                      left join lastock as stock on stock.trno=head.trno
                      left join item on item.itemid=stock.itemid
                      left join frontend_ebrands as brand on brand.brandid=item.brand
                      left join ladetail as detail on detail.trno = head.trno
                      left join coa as coa on coa.acnoid = detail.acnoid
                      left join client as client on client.client = head.client
                      left join cntnum as cntnum on cntnum.trno = head.trno 
                      where head.doc = 'SJ' and coa.alias IN ('AR3') $filter
                      group by head.docno, client.client, client.clientname, head.dateid
                      order by $sortby";

        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function REBATE_DEFAULT_LAYOUT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $count = 50;
    $page = 48;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->HEADER_LESS_REBATE($config);

    $Tot = 0;

    $less_rebate = $this->LESS_REBATE($config);
    $bal = 0;
    foreach ($less_rebate as $key => $data) {
      $bal += $data->amount;
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($bal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->HEADER_LESS_REBATE($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '400', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    $str .= $this->reporter->page_break();

    # -------------------------------------- REBATE -------------------------------------------
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->HEADER_REBATE($config);

    $Tot = 0;

    $rebate = $this->REBATE($config);

    foreach ($rebate as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->HEADER_REBATE($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '400', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
  // vita base

  // AMS
  // beehealthy 26
  // beehealthy 26
  // aquamax 35
  // aquamax 35
  // seastart 48
  // seastart 48

  // EAPPLICATION
  // evergreen 34
  //data
  public function report_ams_eapplication_qry($config)
  {
    // QUERY

    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $client       = $config['params']['dataparams']['client'];
    $agent       = $config['params']['dataparams']['agent'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $category  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $center       = $config['params']['dataparams']['center'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $clientgroup = isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }
    if ($custcategory != "") {
      $filter .= " and custcat.cat_name='$custcategory'";
    }

    $center       = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $filter1 .= "";


    $addfield = "";

    $leftjoincat = '';


    switch ($posttype) {
      case '0': // POSTED
        switch ($typeofreport) {
          case 'report':
            $query = "select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno, client.client, client.clientname, agent.client as agcode,
                  agent.clientname as agent, sum(detail.cr-detail.db) as amount, head.rem, head.ourref, head.yourref
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where head.doc in ('sj','mj', 'sd', 'se', 'sf','cp') and left(coa.alias,2) in ('SA', 'SD', 'SR')
                  and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " 
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.rem, head.ourref, head.yourref
                  order by " . $sortby;

            break;

          case 'lessreturn':
            $query = "select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
                sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount " . $addfield . "
                from glhead as head left join glstock as stock on stock.trno=head.trno
                
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
      
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  $leftjoincat
                
                where head.doc in ('sj','mj','sd','se','sf','cm')
                and head.dateid between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
                
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname,head.doc " . $addfield . "
                order by $sortby";
            break;
          case 'return':
            $query = "
                select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                sum(stock.ext) as amount " . $addfield . "
                from glhead as head 
              
                left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
      
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  $leftjoincat
              
                where head.doc='CM' 
                and head.dateid between '$start' and '$end' 
                $filter $filter1 and item.isofficesupplies =0
                
                group by head.dateid, head.docno, client.client, client.clientname, agent.client, 
                agent.clientname " . $addfield . "
                order by $sortby";
            break;
            break;
        }
        break;
      case  '1': // UNPOSTED
        switch ($typeofreport) {
          case 'report':

            $query = "select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno, client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
                  head.yourref, head.ourref, sum(detail.cr-detail.db) as amount
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
                  left join client on client.client=head.client
                  left join client as agent on agent.client=head.agent
                  left join cntnum on cntnum.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where head.doc in ('sj', 'sd', 'se', 'sf','cp') and head.dateid between '" . $start . "' and '" . $end . "' and left(coa.alias,2) in ('SA', 'SD', 'SR') " . $filter . " " . $filter1 . "
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.yourref, head.ourref, head.rem
                  order by " . $sortby;
            break;

          case 'lessreturn':
            $query = "
                select head.doc,'sales less return' as type, 'u' as tr, 
                date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, 
                agent.client as agcode, agent.clientname as agent,
                sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount " . $addfield . "
                
                
                from lahead as head left join lastock as stock on stock.trno=head.trno
              
              
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
              
              
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  $leftjoincat

                where head.doc in ('sj','mj','sd','se','sf','cm') 
                and head.dateid between '$start' and '$end' $filter $filter1
                and item.isofficesupplies =0
              
              
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname,head.doc " . $addfield . "
                order by $sortby";
            break;

          case 'return':
            $query = "
                select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                sum(stock.ext) as amount " . $addfield . "
              
              
                from lahead as head 
                
                
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  $leftjoincat


                where head.doc='cm' and head.dateid between '$start' and '$end' $filter $filter1
                and item.isofficesupplies=0
              
                group by head.dateid, head.docno, client.client, client.clientname, agent.client,
                agent.clientname " . $addfield . "
                order by $sortby";
            break;
        }
        break;
      default:
        switch ($typeofreport) {
          case 'report':

            $query =
              "select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno, client.client, client.clientname, agent.client as agcode,
                  agent.clientname as agent, sum(detail.cr-detail.db) as amount, head.rem, head.ourref, head.yourref
                  from glhead as head
                  left join gldetail as detail on detail.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where head.doc in ('sj', 'sd', 'se', 'sf','cp') and left(coa.alias,2) in ('SA', 'SD', 'SR')
                  and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . " 
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname, head.rem, head.ourref, head.yourref
                  union all
                  select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno, client.client, client.clientname, agent.client as agcode, 
                  agent.clientname as agent,sum(detail.cr-detail.db) as amount, head.rem, head.ourref, head.yourref
                  from lahead as head
                  left join ladetail as detail on detail.trno=head.trno
                  left join client on client.client=head.client
                  left join client as agent on agent.client=head.agent
                  left join cntnum on cntnum.trno=head.trno
                  left join coa on coa.acnoid=detail.acnoid
                  where head.doc in ('sj', 'sd', 'se', 'sf','cp') and head.dateid between '" . $start . "' and '" . $end . "' and left(coa.alias,2) in ('SA', 'SD', 'SR') " . $filter . " " . $filter1 . "
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client, agent.clientname,  head.rem, head.ourref, head.yourref
                  order by " . $sortby;


            break;

          case 'lessreturn':
            $query = "
                select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno, 
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
                sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount " . $addfield . "
                from glhead as head left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                $leftjoincat
                where head.doc in ('sj','mj','sd','se','sf','cm')
                and head.dateid between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname,head.doc " . $addfield . "
                union all
                select head.doc,'sales less return' as type, 'u' as tr, 
                date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, 
                agent.client as agcode, agent.clientname as agent,
                sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount " . $addfield . "
                from lahead as head left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                $leftjoincat
                where head.doc in ('sj','mj','sd','se','sf','cm') 
                and head.dateid between '$start' and '$end' $filter $filter1
                and item.isofficesupplies =0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname,head.doc " . $addfield . "
                order by $sortby";
            break;

          case 'return':
            $query = "
                select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                sum(stock.ext) as amount " . $addfield . "
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                $leftjoincat
                where head.doc='CM' 
                and head.dateid between '$start' and '$end' 
                $filter $filter1 and item.isofficesupplies =0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client, 
                agent.clientname " . $addfield . "
                union all
                select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                sum(stock.ext) as amount " . $addfield . "
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                left join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                $leftjoincat
                where head.doc='cm' and head.dateid between '$start' and '$end' $filter $filter1
                and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client,
                agent.clientname " . $addfield . "
                order by $sortby";
            break;
        }
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  private function ams_eapplication_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $categoryname  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $str = '';
    $layoutsize = '800';
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
    $str .= $this->reporter->col('SALES ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($custcategory == '') {
      $str .= $this->reporter->col('Cust-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Cust-Category: ' . strtoupper($custcategory),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();



    $str .= $this->reporter->endtable();
    $name = 'CLIENT NAME';
    if ($companyid == 34) {
      $name = 'PLAN HOLDERS NAME';
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($name, '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  public function ams_eapplication_Layout_REPORT($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->report_ams_eapplication_qry($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->ams_eapplication_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->ams_eapplication_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function ams_eapplication_Layout_LESSRETURN($config)
  {
    $result = $this->report_ams_eapplication_qry($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];


    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->ams_eapplication_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      if ($companyid == 32) { //3m
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->brgy, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->area, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format(
          $data->amount,
          2
        ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format(
          $data->amount,
          2
        ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->ams_eapplication_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    if ($companyid == 32) { //3m
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function ams_eapplication_Layout_RETURN($config)
  {
    $result = $this->report_ams_eapplication_qry($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->ams_eapplication_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->ams_eapplication_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
  // evergreen 34


  //vitaline 1 aims
  //labsol cebu 23 aims
  //vitaline 1 aims


  //afti 10 12

  //data
  public function reportAfti($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $client       = $config['params']['dataparams']['client'];
    $agent       = $config['params']['dataparams']['agent'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $category  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $center       = $config['params']['dataparams']['center'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $clientgroup = isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }
    $filter1 = "";
    if ($client != "") {
      $filter .= " and client.client='$client'";
    }
    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }
    if ($custcategory != "") {
      $filter .= " and custcat.cat_name='$custcategory'";
    }

    $center       = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    $prjid = $config['params']['dataparams']['project'];
    $deptid = $config['params']['dataparams']['ddeptname'];
    $project = $config['params']['dataparams']['projectid'];
    $indus = $config['params']['dataparams']['industry'];
    if ($deptid == "") {
      $dept = "";
    } else {
      $dept = $config['params']['dataparams']['deptid'];
    }
    if ($prjid != "") {
      $filter1 .= " and stock.projectid = $project";
    }
    if ($deptid != "") {
      $filter1 .= " and head.deptid = $dept";
    }
    if ($indus != "") {
      $filter1 .= " and client.industry = '$indus'";
    }



    switch ($posttype) {
      case '0': // POSTED
        //
        switch ($typeofreport) {
          case 'report':
            $query = "
                    select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                    client.client, client.clientname, agent.client as agcode, 
                    agent.clientname as agent, sum(stock.ext) as amount, head.rem, head.ourref, head.yourref 

                    from glhead as head 

                    left join glstock as stock on stock.trno=head.trno
                    left join client on client.clientid=head.clientid
                    left join client as agent on agent.clientid=head.agentid
                    left join cntnum on cntnum.trno=head.trno

                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat

                    where head.doc in ('sj','mj','sd','se','sf')
                    and date(head.dateid) between '$start' and '$end' 
                    $filter $filter1 and item.isofficesupplies = 0

                    group by head.dateid, head.docno, client.client, 
                    client.clientname, agent.client, agent.clientname, head.rem, head.ourref, head.yourref 

                    order by $sortby";
            break;
          case 'lessreturn':
            $query = "select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
                  sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount 
                  from glhead as head left join glstock as stock on stock.trno=head.trno
                  
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno

                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat
                  
                  where head.doc in ('sj','mj','sd','se','sf','cm')
                  and head.dateid between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
                  
                  group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname,head.doc 
                  order by $sortby";
            break;
          case 'return':
            $query = "
                  select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                  sum(stock.ext) as amount 
                  from glhead as head 
                
                  left join glstock as stock on stock.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno

                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat
                
                  where head.doc='CM' 
                  and head.dateid between '$start' and '$end' 
                  $filter $filter1 and item.isofficesupplies =0
                  
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client, 
                  agent.clientname 
                  order by $sortby";
            break;
            break;
        }
        //
        break;
      case  '1': // UNPOSTED
        //
        switch ($typeofreport) {
          case 'report':
            $query = "
                    select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                    client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                    head.yourref, sum(stock.ext) as amount, head.rem, head.ourref 
                      
                    from lahead as head 
                  
                  
                    left join lastock as stock on stock.trno=head.trno
                    left join client on client.client=head.client
                    left join client as agent on agent.client=head.agent
                    left join cntnum on cntnum.trno=head.trno
                    

                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat
                  
                    where head.doc in ('sj','mj','sd','se','sf') and head.dateid between '$start' and '$end' 
                    $filter $filter1 and item.isofficesupplies =0
                  
                  
                    group by head.dateid, head.docno, client.client, 
                    client.clientname, agent.client, agent.clientname, head.yourref, head.ourref, head.rem 
                    order by $sortby";

            break;
          case 'lessreturn':
            $query = "
                  select head.doc,'sales less return' as type, 'u' as tr, 
                  date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, 
                  agent.client as agcode, agent.clientname as agent,
                  sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount 
                  
                  
                  from lahead as head left join lastock as stock on stock.trno=head.trno
                
                
                  left join client on client.client=head.client
                  left join client as agent on agent.client=head.agent
                  left join cntnum on cntnum.trno=head.trno
                
                
                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat

                  where head.doc in ('sj','mj','sd','se','sf','cm') 
                  and head.dateid between '$start' and '$end' $filter $filter1
                  and item.isofficesupplies =0
                
                
                  group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname,head.doc 
                  order by $sortby";
            break;
          case 'return':
            $query = "
                  select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                  sum(stock.ext) as amount 
                
                
                  from lahead as head 
                  
                  
                  left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join client as agent on agent.client=head.agent
                  left join cntnum on cntnum.trno=head.trno
                  
                    left join item on item.itemid=stock.itemid
                    left join itemcategory as cat on cat.line = item.category
                    left join itemsubcategory as subcat on subcat.line = item.subcat


                  where head.doc='cm' and head.dateid between '$start' and '$end' $filter $filter1
                  and item.isofficesupplies=0
                
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client,
                  agent.clientname 
                  order by $sortby";
            break;
        }
        //
        break;
      default:
        //
        switch ($typeofreport) {
          case 'report':
            $query = "
                  select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, 
                  agent.clientname as agent, sum(stock.ext) as amount, head.rem, head.ourref,head.yourref 
                  from glhead as head 
                  left join glstock as stock on stock.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc in ('sj','mj','sd','se','sf')
                  and date(head.dateid) between '$start' and '$end' 
                  $filter $filter1 and item.isofficesupplies = 0
                  group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname, head.yourref,head.ourref,head.rem 

                  union all
                  
                  select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, 
                  agent.clientname as agent,sum(stock.ext) as amount, head.rem, head.ourref,head.yourref 
                  from lahead as head 
                  left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join client as agent on agent.client=head.agent
                  left join cntnum on cntnum.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc in ('sj','mj','sd','se','sf') and head.dateid between '$start' and '$end' 
                  $filter $filter1 and item.isofficesupplies =0

                  group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname, head.yourref, head.ourref, head.rem 
                  order by $sortby";
            break;
          case 'lessreturn':
            $query = "
                  select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno, 
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
                  sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount 
                  from glhead as head left join glstock as stock on stock.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc in ('sj','mj','sd','se','sf','cm')
                  and head.dateid between '$start' and '$end' $filter $filter1 and item.isofficesupplies=0
                  group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname,head.doc 
                  union all
                  select head.doc,'sales less return' as type, 'u' as tr, 
                  date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, 
                  agent.client as agcode, agent.clientname as agent,
                  sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount 
                  from lahead as head left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join client as agent on agent.client=head.agent
                  left join cntnum on cntnum.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc in ('sj','mj','sd','se','sf','cm') 
                  and head.dateid between '$start' and '$end' $filter $filter1
                  and item.isofficesupplies =0
                  group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname,head.doc 
                  order by $sortby";
            break;
          case 'return':
            $query = "
                  select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                  sum(stock.ext) as amount 
                  from glhead as head 
                  left join glstock as stock on stock.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc='CM' 
                  and head.dateid between '$start' and '$end' 
                  $filter $filter1 and item.isofficesupplies =0
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client, 
                  agent.clientname 
                  union all
                  select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                  sum(stock.ext) as amount 
                  from lahead as head 
                  left join lastock as stock on stock.trno=head.trno
                  left join client on client.client=head.client
                  left join client as agent on agent.client=head.agent
                  left join cntnum on cntnum.trno=head.trno
                  left join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc='cm' and head.dateid between '$start' and '$end' $filter $filter1
                  and item.isofficesupplies=0
                  group by head.dateid, head.docno, client.client, client.clientname, agent.client,
                  agent.clientname 
                  order by $sortby";
            break;
        }
        //
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  private function afti_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $categoryname  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $dept   = $config['params']['dataparams']['ddeptname'];
    $proj   = $config['params']['dataparams']['project'];
    $indus   = $config['params']['dataparams']['industry'];
    if ($dept != "") {
      $deptname = $config['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
    }

    if ($proj != "") {
      $projname = $config['params']['dataparams']['projectname'];
    } else {
      $projname = "ALL";
    }

    if ($indus == "") {
      $indus = 'ALL';
    }


    $str = '';
    $layoutsize = '800';
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
    $str .= $this->reporter->col('SALES ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($custcategory == '') {
      $str .= $this->reporter->col('Cust-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Cust-Category: ' . strtoupper($custcategory),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Industry: ' . $indus, null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Department : ' . $deptname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Project : ' . $projname, '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $name = 'CLIENT NAME';

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($name, '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  public function aftisummarysalesreport($config)
  {
    $result = $this->reportAfti($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $filtercenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->afti_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->afti_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
  //afti


  //default 

  //data
  public function reportDefault($config)
  {
    // QUERY

    $companyid = $config['params']['companyid'];
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $agent       = $config['params']['dataparams']['agent'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $category  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $center       = $config['params']['dataparams']['center'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $clientgroup = isset($config['params']['dataparams']['groupid']) ? $config['params']['dataparams']['groupid'] : '';
    $brand  = isset($config['params']['dataparams']['brandname']) ? $config['params']['dataparams']['brandname'] : '';

    $filter = "";
    if ($category != "") {
      $filter = $filter . " and item.category='$category'";
    }

    if ($subcatname != "") {
      $filter = $filter . " and item.subcat='$subcatname'";
    }
    if ($brand != "") {
      $brandid       = $config['params']['dataparams']['brandid'];
      $filter = $filter . " and item.brand='$brandid'";
    }
    if ($client != "") {
      $filter .= " and client.clientid='$clientid'";
    }
    if ($agent != "") {
      $filter .= " and agent.client='$agent'";
    }

    $center       = $config['params']['dataparams']['center'];
    if ($center != "") {
      $filter .= " and cntnum.center='$center'";
    }

    if ($companyid == 59) { //roosevelt
      $area = $config['params']['dataparams']['area'];
      $filter .= " and client.area='" . $area . "'";
    }


    switch ($posttype) {
      case '0': // POSTED
        //
        switch ($typeofreport) {
          case 'report':
            $query = "
                  select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, 
                  agent.clientname as agent, sum(stock.ext) as amount, head.rem, head.ourref, head.yourref ,client.brgy, client.area,
                  head.terms, date(head.due) as due, ar.bal, sum(stock.iss*stock.cost) as cost,head.vattype,client.tin,client.addr
                  from glhead as head 
                  left join glstock as stock on stock.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  left join arledger as ar on ar.trno = head.trno
                   where head.doc in ('sj','mj','sd','se','sf')
                  and date(head.dateid) between '$start' and '$end' 
                  $filter and item.isofficesupplies = 0
                   group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname, head.rem, head.ourref, head.yourref ,client.brgy, client.area,
                   head.terms,head.due,ar.bal,head.vattype,client.tin,client.addr
                  order by $sortby";


            break;
          case 'lessreturn':
            $query = "select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
                  sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                  from glhead as head left join glstock as stock on stock.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc in ('sj','mj','sd','se','sf','cm')
                  and date(head.dateid) between '$start' and '$end' $filter  and item.isofficesupplies=0
                   group by head.dateid, head.docno, client.client, 
                  client.clientname, agent.client, agent.clientname,client.brgy, client.area,head.doc,head.vattype,client.tin,client.addr
                  order by $sortby";
            break;
          case 'return':
            $query = "
                  select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                  client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                  sum(stock.ext) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                  from glhead as head 
                  left join glstock as stock on stock.trno=head.trno
                  left join client on client.clientid=head.clientid
                  left join client as agent on agent.clientid=head.agentid
                  left join cntnum on cntnum.trno=head.trno
                  join item on item.itemid=stock.itemid
                  left join itemcategory as cat on cat.line = item.category
                  left join itemsubcategory as subcat on subcat.line = item.subcat
                  where head.doc='CM' 
                  and  date(head.dateid) between '$start' and '$end' 
                  $filter  and item.isofficesupplies =0
                   group by head.dateid, head.docno, client.client, client.clientname, agent.client, 
                  agent.clientname ,client.brgy, client.area,head.vattype,client.tin,client.addr
                  order by $sortby";
            break;
        }
        //
        break;
      case  '1': // UNPOSTED
        //
        switch ($typeofreport) {
          case 'report':
            $query = "
                select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                head.yourref, sum(stock.ext) as amount, head.rem, head.ourref ,client.brgy, client.area,
                head.terms, date(head.due) as due,sum(stock.ext) as bal, sum(stock.iss*stock.cost) as cost,head.vattype,client.tin,client.addr
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf') and date(head.dateid) between '$start' and '$end' 
                $filter and item.isofficesupplies =0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname, head.yourref, head.ourref, head.rem ,client.brgy, client.area,
                 head.terms,head.due,head.vattype,client.tin,client.addr
                order by $sortby";


            break;

          case 'lessreturn':
            $query = "
                select head.doc,'sales less return' as type, 'u' as tr, 
                date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, 
                agent.client as agcode, agent.clientname as agent,
                sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                from lahead as head left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf','cm') 
                and  date(head.dateid) between '$start' and '$end' $filter 
                and item.isofficesupplies =0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname,head.doc ,client.brgy, client.area,head.vattype,client.tin,client.addr
                order by $sortby";
            break;

          case 'return':
            $query = "
                select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                sum(stock.ext) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc='cm' and  date(head.dateid) between '$start' and '$end' $filter 
                and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client,
                agent.clientname ,client.brgy, client.area,head.vattype,client.tin,client.addr
                order by $sortby";
            break;
        }
        //
        break;
      default:
        //
        switch ($typeofreport) {
          case 'report':
            $query = "
                select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, 
                agent.clientname as agent, sum(stock.ext) as amount, head.rem, head.ourref,head.yourref ,client.brgy, client.area,
                head.terms, date(head.due) as due,ar.bal, sum(stock.iss*stock.cost) as cost,head.vattype,client.tin,client.addr
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                 left join arledger as ar on ar.trno = head.trno
                where head.doc in ('sj','mj','sd','se','sf')
                and date(head.dateid) between '$start' and '$end' 
                $filter and item.isofficesupplies = 0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname, head.yourref,head.ourref,head.rem,client.brgy, client.area,
                head.terms,head.due,ar.bal,head.vattype,client.tin,client.addr
      
                union all
                
                select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, 
                agent.clientname as agent,sum(stock.ext) as amount, head.rem, head.ourref,head.yourref ,client.brgy, client.area,
                 head.terms, date(head.due) as due,sum(stock.ext) as bal, sum(stock.iss*stock.cost) as cost,head.vattype,client.tin,client.addr
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf') and  date(head.dateid) between '$start' and '$end' 
                $filter and item.isofficesupplies =0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname, head.yourref, head.ourref, head.rem ,client.brgy, client.area,
                head.terms,head.due,head.vattype,client.tin,client.addr
                order by $sortby";


            break;

          case 'lessreturn':
            $query = "
                select head.doc,'sales' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno, 
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent,
                sum(case when head.doc='sj' then (stock.ext) else (stock.ext)*-1 end) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                from glhead as head left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf','cm')
                and  date(head.dateid) between '$start' and '$end' $filter and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname,head.doc ,client.brgy, client.area,head.vattype,client.tin,client.addr
                union all
                select head.doc,'sales less return' as type, 'u' as tr, 
                date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, 
                agent.client as agcode, agent.clientname as agent,
                sum(case when head.doc='sj' then stock.ext else (stock.ext*-1) end) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                from lahead as head left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc in ('sj','mj','sd','se','sf','cm') 
                and  date(head.dateid) between '$start' and '$end' $filter 
                and item.isofficesupplies =0
                group by head.dateid, head.docno, client.client, 
                client.clientname, agent.client, agent.clientname,head.doc ,client.brgy, client.area,head.vattype,client.tin,client.addr
                order by $sortby";
            break;

          case 'return':
            $query = "
                select 'sales return' as type, 'u' as tr,  date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                sum(stock.ext) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                from glhead as head 
                left join glstock as stock on stock.trno=head.trno
                left join client on client.clientid=head.clientid
                left join client as agent on agent.clientid=head.agentid
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc='CM' 
                and  date(head.dateid) between '$start' and '$end' 
                $filter and item.isofficesupplies =0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client, 
                agent.clientname ,client.brgy, client.area,head.vattype,client.tin,client.addr
                union all
                select 'sales return' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
                client.client, client.clientname, agent.client as agcode, agent.clientname as agent, 
                sum(stock.ext) as amount ,client.brgy, client.area,head.vattype,client.tin,client.addr
                from lahead as head 
                left join lastock as stock on stock.trno=head.trno
                left join client on client.client=head.client
                left join client as agent on agent.client=head.agent
                left join cntnum on cntnum.trno=head.trno
                join item on item.itemid=stock.itemid
                left join itemcategory as cat on cat.line = item.category
                left join itemsubcategory as subcat on subcat.line = item.subcat
                where head.doc='cm' and  date(head.dateid) between '$start' and '$end' $filter 
                and item.isofficesupplies=0
                group by head.dateid, head.docno, client.client, client.clientname, agent.client,
                agent.clientname ,client.brgy, client.area,head.vattype,client.tin,client.addr
                order by $sortby";
            break;
        }
        break;
    }
    return $this->coreFunctions->opentable($query);
  }


  //default

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $categoryname  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';

    if ($companyid == 36) {
      $layoutsize = '1000';
    } else {
      $layoutsize = '800';
    }

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
    $str .= $this->reporter->col('SALES ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($custcategory == '') {
      $str .= $this->reporter->col('Cust-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Cust-Category: ' . strtoupper($custcategory),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $name = 'CLIENT NAME';


    // $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 36) { //rozlab
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DOCUMENT #', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('TERMS', '130', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DUE DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CLIENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($name, '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('BALANCE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('CLIENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col($name, '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
    }


    $str .= $this->reporter->endtable();
    return $str;
  }

  private function kinggeorge_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $categoryname  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';

    $layoutsize = '800';

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
    $str .= $this->reporter->col('SALES ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($custcategory == '') {
      $str .= $this->reporter->col('Cust-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Cust-Category: ' . strtoupper($custcategory),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $name = 'CLIENT NAME';


    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($name, '300', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL COST', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  public function kinggeorgeLayout_REPORT($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->kinggeorge_displayHeader($config);

    $Tot = 0;
    $ToC = 0;
    $amt = 0;
    $bal = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      // $str .= $this->reporter->startrow();
      // $str .= $this->reporter->addline();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->cost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();


      $Tot = $Tot + $data->amount;
      $ToC = $ToC + $data->cost;
      $bal += $data->bal;

      // if ($this->reporter->linecounter >= $page) {
      //   $str .= $this->reporter->endtable();
      //   $str .= $this->reporter->page_break();
      //   $str .= $this->default_displayHeader($config);
      //   $str .= $this->reporter->endrow();
      //   $str .= $this->reporter->endtable();
      //   $page = $page + $count;
      // }
    }

    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($ToC, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }


  public function reportDefaultLayout_REPORT($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 34;
    $page = 36;

    $str = '';
    if ($companyid == 36) { //rozlab
      $layoutsize = '1000';
    } else {
      $layoutsize = '800';
    }
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $Tot = 0;
    $amt = 0;
    $bal = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      // $str .= $this->reporter->startrow();
      // $str .= $this->reporter->addline();

      if ($companyid == 36) { //rozlab
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data->docno, '120', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->terms, '130', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->due, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->bal, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
      }


      $Tot = $Tot + $data->amount;
      $bal += $data->bal;

      if ($companyid != 36) { //not rozlab
        if ($this->reporter->linecounter >= $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $page = $page + $count;
        }
      }
    }

    if ($companyid == 36) { //rozlab
      $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '130', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($bal, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    }


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function reportDefaultLayout_LESSRETURN($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];


    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($companyid != 36) { //not rozlab
        if ($this->reporter->linecounter >= $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $page = $page + $count;
        }
      }
    }


    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_RETURN($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($companyid != 36) { //not rozlab
        if ($this->reporter->linecounter >= $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->default_displayHeader($config);
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $page = $page + $count;
        }
      }
    }


    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
  //default

  //xcomp
  private function xcomp_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $categoryname  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $layoutsize = '800';
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
    $str .= $this->reporter->col('SALES ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($custcategory == '') {
      $str .= $this->reporter->col('Cust-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Cust-Category: ' . strtoupper($custcategory),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $name = 'CLIENT NAME';


    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '70', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('OURREF', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('YOURREF', '80', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '170', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOTES', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL AMOUNT', '90', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  public function xcomp_Layout_REPORT($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->xcomp_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();

      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '70', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->ourref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->yourref, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '110', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '170', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->xcomp_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '110', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '170', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function xcomp_Layout_LESSRETURN($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];


    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->xcomp_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->xcomp_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300px', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function xcomp_Layout_RETURN($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->xcomp_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->docno, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->xcomp_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }


    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '300', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }
  //xcomp


  //3m
  private function threeem_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $categoryname  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));



    $str = '';
    $layoutsize = '800';
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
    $str .= $this->reporter->col('SALES ' . strtoupper($typeofreport), null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($custcategory == '') {
      $str .= $this->reporter->col('Cust-Category: ALL',  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Cust-Category: ' . strtoupper($custcategory),  null, null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->endrow();



    $str .= $this->reporter->endtable();
    $name = 'CLIENT NAME';

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENT NAME', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('BARANGAY', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AREA', '75', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL AMOUNT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    return $str;
  }

  public function threeem_Layout_REPORT($config)
  {
    $companyid = $config['params']['companyid'];
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->threeem_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();


      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->brgy, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->area, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->threeem_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function threeem_Layout_LESSRETURN($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];


    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->threeem_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->brgy, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->area, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format(
        $data->amount,
        2
      ), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->threeem_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150px', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function threeem_Layout_RETURN($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $companyid = $config['params']['companyid'];

    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->threeem_displayHeader($config);

    $Tot = 0;
    $amt = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->client, '150', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->brgy, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->area, '75', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;

      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->threeem_displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL :', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($Tot, 2), '100px', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();


    return $str;
  }

  private function roosevelt_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];


    $client       = $config['params']['dataparams']['client'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $posttype     = $config['params']['dataparams']['posttype'];
    $categoryname  = isset($config['params']['dataparams']['category']) ? $config['params']['dataparams']['category'] : '';
    $subcatname =  $config['params']['dataparams']['subcat'];
    $typeofreport = $config['params']['dataparams']['typeofreport'];
    $sortby       = $config['params']['dataparams']['sortby'];
    $custcategory = isset($config['params']['dataparams']['categoryname']) ? $config['params']['dataparams']['categoryname'] : '';
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $format = $config['params']['dataparams']['typeofformat'];
    $font = $this->companysetup->getrptfont($config['params']);

    $str = '';

    $fontsize = "10";
    $border = "1px solid ";
    $typename = "";
    $layoutsize = '1000';

    switch ($typeofreport) {
      case 'report':
        $typename = 'SALES REPORT';
        break;
      case 'lessreturn':
        $typename = 'SALES LESS RETURN';
        break;
      case 'return':
        $typename = 'SALES RETURN';
        break;
    }
    $formatname = "";
    switch ($format) {
      case 'sis':
        $formatname = 'SALES INVOICE SUMMARY';

        break;
      case 'gft':
        $formatname = 'GOVERNMENT FOR TIN';

        break;
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($formatname, null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    // $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Period : ' . date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '400', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

    if ($posttype == '0') {
      $posttype = 'Posted';
    } else if ($posttype == '1') {
      $posttype = 'Unposted';
    } else {
      $posttype = 'ALL';
    }

    $filtercenter = $config['params']['dataparams']['center'];
    if ($filtercenter == "") {
      $filtercenter = 'ALL';
    }

    $str .= $this->reporter->col('Transaction : ' . strtoupper($posttype), '180', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Center : ' . strtoupper($filtercenter), '120', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    if ($sortby == 'docno') {
      $str .= $this->reporter->col('Sort By : Document #', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sort By : Date', '150', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    // $str .= $this->reporter->pagenumber('Page','150');
    $str .= $this->reporter->pagenumber('Page', '150',  null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    if ($categoryname == '') {
      $str .= $this->reporter->col('Category : ALL', '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Category : ' . strtoupper($categoryname),  '250', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($subcatname == '') {
      $str .= $this->reporter->col('Sub-Category: ALL',  '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Sub-Category : ' . strtoupper($subcatname),  '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    if ($custcategory == '') {
      $str .= $this->reporter->col('Cust-Category: ALL',  null, null, false, $border, '200', 'L', $font, $fontsize, '', '', '');
    } else {
      $str .= $this->reporter->col('Cust-Category: ' . strtoupper($custcategory),  '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
    }

    $str .= $this->reporter->col('Type: ' . $typename,  '250', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $name = 'CUSTOMER';

    switch ($format) {
      case 'sis':
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '120', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($name, '280', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('DOCUMENT #', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('AMOUNT', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('VATABLE', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('NET OF VAT', '150', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        break;
      case 'gft':
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('DATE', '120', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col($name, '200', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('TIN', '160', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('ADDRESS', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('SALES', '90', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('VATABLE', '90', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->col('NET OF VAT', '90', null, false, $border, 'TBLR', 'C', $font, $fontsize, 'B', '', '5px');
        $str .= $this->reporter->endrow();
        break;
    }
    // $str .= $this->reporter->endtable();
    return $str;
  }


  public function roosevelt_sis_reportDefaultLayout_REPORT($config)
  {

    $result = $this->reportDefault($config);
    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->roosevelt_displayHeader($config);

    $Tot = 0;
    $tlvat = 0;
    $nvat = 0;

    foreach ($result as $key => $data) {

      // $str .= $this->reporter->startrow();
      // $str .= $this->reporter->addline();
      if ($data->vattype == 'VATABLE') {
        $vatable = $data->amount / 1.12;
        $netofvat = $data->amount / 1.12;
      } else {
        $vatable = 0;
        $netofvat = 0;
      }
      // $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->dateid, '120', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->clientname, '280', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->docno, '150', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->amount, 2), '150', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($vatable != 0 ? number_format($vatable, 2) : '-', '150', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($netofvat != 0 ? number_format($vatable, 2) : '-', '150', null, false, $border, 'LBR', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;
      $tlvat += $vatable;
      $nvat += $netofvat;


      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->roosevelt_displayHeader($config);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('GRAND TOTAL', '120', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '280', null, false, $border, 'TBL', 'C', $font, $fontsize, '', '', '5px');
    // $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col(number_format($Tot, 2), '150', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tlvat, 2), '150', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($nvat, 2), '150', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '5px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }

  public function roosevelt_gft_reportDefaultLayout_REPORT($config)
  {

    $result = $this->reportDefault($config);
    $count = 34;
    $page = 36;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->roosevelt_displayHeader($config);

    $Tot = 0;
    $tlvat = 0;
    $nvat = 0;

    foreach ($result as $key => $data) {

      // $str .= $this->reporter->startrow();
      // $str .= $this->reporter->addline();
      if ($data->vattype == 'VATABLE') {
        $vatable = $data->amount / 1.12;
        $netofvat = $data->amount / 1.12;
      } else {
        $vatable = 0;
        $netofvat = 0;
      }
      // $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      // $tin = '000-000-000-00000';
      $str .= $this->reporter->col($data->dateid, '120', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->clientname, '200', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->docno, '100', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->tin, '160', null, false, $border, 'LB', 'C', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($data->addr, '150', null, false, $border, 'LB', 'L', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col(number_format($data->amount, 2), '90', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($vatable != 0 ? number_format($vatable, 2) : '-', '90', null, false, $border, 'LB', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->col($netofvat != 0 ? number_format($vatable, 2) : '-', '90', null, false, $border, 'LBR', 'R', $font, $fontsize, '', '', '5px');
      $str .= $this->reporter->endrow();
      $Tot = $Tot + $data->amount;
      $tlvat += $vatable;
      $nvat += $netofvat;


      if ($this->reporter->linecounter >= $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->roosevelt_displayHeader($config);
        // $str .= $this->reporter->endrow();
        // $str .= $this->reporter->endtable();
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->col('GRAND TOTAL', '120', null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TBL', 'C', $font, $fontsize, '', '', '5px');
    // $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TBL', 'C', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '160', null, false, $border, 'TBL', 'C', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TBL', 'C', $font, $fontsize, '', '', '5px');
    $str .= $this->reporter->col(number_format($Tot, 2), '90', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($tlvat, 2), '90', null, false, $border, 'TBL', 'R', $font, $fontsize, 'B', '', '5px');
    $str .= $this->reporter->col(number_format($nvat, 2), '90', null, false, $border, 'TBLR', 'R', $font, $fontsize, 'B', '', '5px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }




  //3m

  //default

  // select x.type,x.tr,x.dateid,x.docno,x.client,x.clientname,x.agcode,x.agent,x.amount,x.rem,x.ourref,x.yourref from(


  //   select 'sales' as type, 'P' as tr, date(head.dateid) as dateid, head.docno,
  //   client.client, client.clientname, 
  //   agent.client as agcode, agent.clientname as agent, 
  //   sum(stock.ext) as amount, head.rem, head.ourref,head.yourref,stock.itemid
  //   from glhead as head 
  //   left join glstock as stock on stock.trno=head.trno
  //   left join cntnum on cntnum.trno=head.trno

  //   left join client on client.clientid=head.clientid
  //   left join client as agent on agent.clientid=head.agentid

  //   where head.doc in ('sj','mj','sd','se','sf')
  //   #and date(head.dateid) between '$start' and '$end' 
  //   #$filter 
  //   #and item.isofficesupplies = 0
  //   group by head.dateid, head.docno, client.client, 
  //   client.clientname, agent.client, agent.clientname, head.yourref,head.ourref,head.rem,stock.itemid

  //   union all

  //   select 'sales' as type, 'u' as tr, date(head.dateid) as dateid, head.docno,
  //   client.client, client.clientname, 
  //   agent.client as agcode, agent.clientname as agent,
  //   sum(stock.ext) as amount, head.rem, head.ourref,head.yourref ,stock.itemid
  //   from lahead as head 
  //   left join lastock as stock on stock.trno=head.trno
  //   left join cntnum on cntnum.trno=head.trno

  //   left join client on client.client=head.client
  //   left join client as agent on agent.client=head.agent



  //   where head.doc in ('sj','mj','sd','se','sf') 
  //   #and head.dateid between '$start' and '$end' 
  //   #$filter 


  //   group by head.dateid, head.docno, client.client, 
  //   client.clientname, agent.client, agent.clientname, head.yourref, head.ourref, head.rem,stock.itemid

  //   ) as x


  //   left join item on item.itemid=x.itemid
  //   left join itemcategory as cat on cat.line = item.category
  //   left join itemsubcategory as subcat on subcat.line = item.subcat


  //   where item.isofficesupplies =0

  //   order by docno


}//end class