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
use DateTime;

class bounced_checks
{
  public $modulename = 'Bounced Checks';
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

    switch ($companyid) {
      case 19: //housegem
        array_push($fields, 'dclientname');
        break;
      case 59: //roosevelt
        array_push($fields, 'radioreporttype');
        break;
    }

    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.label', 'StartDate');
    data_set($col2, 'start.readonly', false);
    data_set($col2, 'end.label', 'EndDate');
    data_set($col2, 'end.readonly', false);
    data_set($col2, 'dclientname.lookupclass', 'lookupgjclient');
    data_set($col2, 'dclientname.label', 'Supplier/Customer');

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

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $center = $config['params']['center'];
        $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,
                            left(now(),10) as end,'" . $defaultcenter[0]['center'] . "' as center,
                            '" . $defaultcenter[0]['centername'] . "' as centername,
                            '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                            '0' as posttype, '' as client,'' as clientname,'' as dclientname";
        break;
      default:
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start,
                            left(now(),10) as end,'' as center,'' as centername,'' as dcentername,
                            '0' as posttype,'' as client,'' as clientname,'' as dclientname, '0' as reporttype";
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
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $isposted = $filters['params']['dataparams']['posttype'];
    $client = $filters['params']['dataparams']['client'];
    $center = $filters['params']['dataparams']['center'];
    $filter = "";

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

    if ($client != "") {
      $filter .= " and client.client= '" . $client . "' ";
    }

    // switch ($filters['params']['companyid']) {
    // case 59: //roosevelt
    // switch ($isposted) {
    //   case 1: //posted
    //     $query = "select head.clientname, sum(abs(detail.db-detail.cr)) as amount
    //       from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
    //         left join coa on coa.acnoid=detail.acnoid
    //         left join client on client.clientid = head.clientid)
    //         left join cntnum on cntnum.trno=head.trno
    //         where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " and 
    //           ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
    //         group by head.clientname
    //         order by head.clientname";
    //     break;
    //   case 0: //unposted
    //     $query = "select head.clientname, sum(abs(detail.db-detail.cr)) as amount
    //       from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
    //         left join coa on coa.acnoid=detail.acnoid
    //         left join client on client.client = head.client)
    //         left join cntnum on cntnum.trno=head.trno
    //       where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
    //         and ifnull((select sum(d.trno) from ladetail as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
    //       group by head.clientname
    //       order by head.clientname";
    //     break;
    //   default: //all
    //     $query = "select clientname, sum(amount) as amount from (
    //         select head.clientname, abs(detail.db-detail.cr) as amount
    //       from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
    //         left join coa on coa.acnoid=detail.acnoid
    //         left join client on client.clientid = head.clientid)
    //         left join cntnum on cntnum.trno=head.trno
    //         where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " and 
    //           ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
    //         group by head.clientname, detail.db,detail.cr
    //       union all
    //       select head.clientname, abs(detail.db-detail.cr) as amount
    //       from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
    //         left join coa on coa.acnoid=detail.acnoid
    //         left join client on client.client = head.client)
    //         left join cntnum on cntnum.trno=head.trno
    //       where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
    //         and ifnull((select sum(d.trno) from ladetail as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
    //       group by head.clientname, detail.db,detail.cr
    //       order by clientname) as t group by clientname";
    //     break;
    // }
    // break;
    // default:
    switch ($isposted) {
      case 1: //posted
        $query =  " select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, 
                              detail.postdate as chkdate,abs(detail.db-detail.cr) as amount,
                              date(cntnum.postdate) as postdate
                        from ((lahead as head 
                        left join ladetail as detail on detail.trno=head.trno) 
                        left join coa on coa.acnoid=detail.acnoid 
                        left join client on client.client = head.client)
                        left join cntnum on cntnum.trno=head.trno
                        where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                              and ifnull((select sum(d.trno) from ladetail as d left join coa as c on c.acnoid=d.acnoid
                                          where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                        group by head.clientname, head.dateid, head.docno, detail.checkno, detail.postdate,detail.db,detail.cr, cntnum.postdate
                        order by clientname, trdate, docno";
        break;

      case 0: //unposted
        $query = "  select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, detail.postdate as chkdate,
                              abs(detail.db-detail.cr) as amount, date(cntnum.postdate) as postdate
                        from ((glhead as head left join gldetail as detail on detail.trno=head.trno) 
                        left join coa on coa.acnoid=detail.acnoid 
                        left join client on client.clientid = head.clientid)
                        left join cntnum on cntnum.trno=head.trno
                        where left(coa.alias, 2) in ('cr','cb') 
                              and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                              and ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid
                                          where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                        group by head.clientname, head.dateid, head.docno, detail.checkno, detail.postdate,
                                  detail.db,detail.cr, cntnum.postdate
                        order by clientname, trdate, docno";
        break;

      case 2: //all
        $query = "select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, 
                            detail.postdate as chkdate, abs(detail.db-detail.cr) as amount, 
                            date(cntnum.postdate) as postdate
                      from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                      left join coa on coa.acnoid=detail.acnoid
                      left join client on client.clientid = head.clientid)
                      left join cntnum on cntnum.trno=head.trno
                      where left(coa.alias, 2) in ('cr','cb') 
                            and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                            and ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid
                            where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                      group by head.clientname, head.dateid, head.docno, detail.checkno, 
                              detail.postdate,detail.db,detail.cr, cntnum.postdate
                      union all
                      select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, detail.postdate as chkdate,
                            abs(detail.db-detail.cr) as amount, date(cntnum.postdate) as postdate
                      from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                      left join coa on coa.acnoid=detail.acnoid
                      left join client on client.client = head.client)
                      left join cntnum on cntnum.trno=head.trno
                      where left(coa.alias, 2) in ('cr','cb') 
                            and date(head.dateid)  between '" . $start . "' and '" . $end . "' " . $filter . "
                            and ifnull((select sum(d.trno) from ladetail as d 
                                        left join coa as c on c.acnoid=d.acnoid
                                        where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                      group by head.clientname, head.dateid, head.docno, detail.checkno, detail.postdate, 
                              detail.db,detail.cr, cntnum.postdate
                      order by clientname, trdate, docno";
        break;
    } //end switch
    //     break;
    // }

    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function roosevelt_query($filters)
  {
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['end']));
    $isposted = $filters['params']['dataparams']['posttype'];
    $client = $filters['params']['dataparams']['client'];
    $center = $filters['params']['dataparams']['center'];
    $reporttype = $filters['params']['dataparams']['reporttype'];
    $filter = "";

    if ($center != "") {
      $filter .= " and cntnum.center= '" . $center . "' ";
    }

    if ($client != "") {
      $filter .= " and client.client= '" . $client . "' ";
    }

    switch ($reporttype) {
      case '0': //summarized
        switch ($isposted) {
          case 0: //posted
            $query = "select head.clientname, sum(abs(detail.db-detail.cr)) as amount
              from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid
                left join client on client.clientid = head.clientid)
                left join cntnum on cntnum.trno=head.trno
                where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " and 
                  ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                group by head.clientname
                order by head.clientname";
            break;
          case 1: //unposted
            $query = "select head.clientname, sum(abs(detail.db-detail.cr)) as amount
              from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid
                left join client on client.client = head.client)
                left join cntnum on cntnum.trno=head.trno
              where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                and ifnull((select sum(d.trno) from ladetail as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
              group by head.clientname
              order by head.clientname";
            break;
          default: //all
            $query = "select clientname, sum(amount) as amount from (
                select head.clientname, abs(detail.db-detail.cr) as amount
              from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid
                left join client on client.clientid = head.clientid)
                left join cntnum on cntnum.trno=head.trno
                where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " and 
                  ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                group by head.clientname, detail.db,detail.cr
              union all
              select head.clientname, abs(detail.db-detail.cr) as amount
              from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                left join coa on coa.acnoid=detail.acnoid
                left join client on client.client = head.client)
                left join cntnum on cntnum.trno=head.trno
              where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                and ifnull((select sum(d.trno) from ladetail as d left join coa as c on c.acnoid=d.acnoid where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
              group by head.clientname, detail.db,detail.cr
              order by clientname) as t group by clientname";
            break;
        }
        break;
      case '1': //detailed
        switch ($isposted) {
          case 1: //unposted
            $query =  " select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, 
                                  detail.postdate as chkdate,abs(detail.db-detail.cr) as amount,
                                  date(cntnum.postdate) as postdate
                            from ((lahead as head 
                            left join ladetail as detail on detail.trno=head.trno) 
                            left join coa on coa.acnoid=detail.acnoid 
                            left join client on client.client = head.client)
                            left join cntnum on cntnum.trno=head.trno
                            where left(coa.alias, 2) in ('cr','cb') and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                                  and ifnull((select sum(d.trno) from ladetail as d left join coa as c on c.acnoid=d.acnoid
                                              where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                            group by head.clientname, head.dateid, head.docno, detail.checkno, detail.postdate,detail.db,detail.cr, cntnum.postdate
                            order by clientname, trdate, docno";
            break;

          case 0: //posted
            $query = "  select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, detail.postdate as chkdate,
                                  abs(detail.db-detail.cr) as amount, date(cntnum.postdate) as postdate
                            from ((glhead as head left join gldetail as detail on detail.trno=head.trno) 
                            left join coa on coa.acnoid=detail.acnoid 
                            left join client on client.clientid = head.clientid)
                            left join cntnum on cntnum.trno=head.trno
                            where left(coa.alias, 2) in ('cr','cb') 
                                  and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                                  and ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid
                                              where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                            group by head.clientname, head.dateid, head.docno, detail.checkno, detail.postdate,
                                      detail.db,detail.cr, cntnum.postdate
                            order by clientname, trdate, docno";
            break;

          case 2: //all
            $query = "select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, 
                                detail.postdate as chkdate, abs(detail.db-detail.cr) as amount, 
                                date(cntnum.postdate) as postdate
                          from ((glhead as head left join gldetail as detail on detail.trno=head.trno)
                          left join coa on coa.acnoid=detail.acnoid
                          left join client on client.clientid = head.clientid)
                          left join cntnum on cntnum.trno=head.trno
                          where left(coa.alias, 2) in ('cr','cb') 
                                and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
                                and ifnull((select sum(d.trno) from arledger as d left join coa as c on c.acnoid=d.acnoid
                                where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                          group by head.clientname, head.dateid, head.docno, detail.checkno, 
                                  detail.postdate,detail.db,detail.cr, cntnum.postdate
                          union all
                          select head.clientname, head.dateid as trdate, head.docno, detail.checkno as chkinfo, detail.postdate as chkdate,
                                abs(detail.db-detail.cr) as amount, date(cntnum.postdate) as postdate
                          from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                          left join coa on coa.acnoid=detail.acnoid
                          left join client on client.client = head.client)
                          left join cntnum on cntnum.trno=head.trno
                          where left(coa.alias, 2) in ('cr','cb') 
                                and date(head.dateid)  between '" . $start . "' and '" . $end . "' " . $filter . "
                                and ifnull((select sum(d.trno) from ladetail as d 
                                            left join coa as c on c.acnoid=d.acnoid
                                            where d.trno=head.trno and c.alias='arb' and d.db>0), 0)>0
                          group by head.clientname, head.dateid, head.docno, detail.checkno, detail.postdate, 
                                  detail.db,detail.cr, cntnum.postdate
                          order by clientname, trdate, docno";
            break;
        }
        break;
    }

    // var_dump($query);
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }


  public function reportplotting($config)
  {
    $companyid = $config['params']['companyid'];
    if ($companyid == 59) { //roosevelt
      $result = $this->roosevelt_query($config);
    } else {
      $result = $this->default_query($config);
    }


    switch ($companyid) {
      case 1: //vitaline
      case 23: //labsol cebu
        $reportdata =  $this->VITALINE_BOUNCHECK_CHECK_LAYOUT($result, $config);
        break;
      case 59: //roosevelt
        if ($config['params']['dataparams']['reporttype'] == '0') {
          $reportdata = $this->DEFAULT_SUMMARY_BOUNCE_CHECK_LAYOUT($result, $config);
        } else {
          $reportdata =  $this->DEFAULT_BOUNCHECK_CHECK_LAYOUT_detailed_roosevelt($result, $config);
        }
        break;
      default:
        $reportdata =  $this->DEFAULT_BOUNCHECK_CHECK_LAYOUT($result, $config);
        break;
    }
    return $reportdata;
  }

  private function DEFAULT_SUMMARY_BOUNCE_CHECK_LAYOUT($data, $params)
  {
    $border = '1px solid';
    $border_line = $alignment = $padding = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $margin = '20';
    $this->reporter->linecounter = 0;
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 41;
    $page = 40;
    $col = array(
      array('350', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('150', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'r', $font, '',  '', '', '', '', $margin)
    );
    $group = $str = '';
    $c = $total = $total1 = $cnt1 = 0;
    $cnt = count((array)$data);
    if (empty($data)) return $this->othersClass->emptydata($params);

    $str .= $this->reporter->beginreport();
    $str .= $this->DEFAULT_BOUNCED_CHECK_HEADER($params);
    $str .= $this->summary_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable('800');

    foreach ($data as $key => $data_) {
      $cnt1 += 1;
      $total += $data_->amount;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data_->clientname, '450', '', false, $border, '', 'L', $font, '', '', '', '', '', $margin);
      $str .= $this->reporter->col(number_format($data_->amount, $decimal_currency), '200', '', false, $border, '', 'R', $font, '', '', '', '', '', $margin);
      $str .= $this->reporter->col('', '150', '', false, $border, '', 'R', $font, '', '', '', '', '', $margin);
      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Grand Total', '450', '', false, '1px dashed', 'T', 'L', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '200', '', false, '1px dashed', 'T', 'R', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function summary_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '450', '', false, '1px dashed', 'B', 'L', $font, '', 'B', '', '', '');
    $str .= $this->reporter->col('Bounced Check Balance', '200', '', false, '1px dashed', 'B', 'C', $font, '', 'B', '', '', '');
    $str .= $this->reporter->col('Effective Balance', '150', '', false, '1px dashed', 'B', 'C', $font, '', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Document #', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Trans. Date', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_BOUNCED_CHECK_HEADER($params)
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
    if ($companyid == 59) { //roosevelt
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('BOUNCED CHECKS', null, null, false, $border, '', 'C', $font, '13', 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

      $startdate = $start;
      $startt = new DateTime($startdate);
      $start = $startt->format('m/d/Y');

      $enddate = $end;
      $endd = new DateTime($enddate);
      $end = $endd->format('m/d/Y');

      $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= '<br/>';

      $str .= $this->reporter->begintable('800');
    } else {

    
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center1, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      

      $str .= '<br/><br/>';

      $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('BOUNCED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
      $str .= $this->reporter->endrow();


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
      $str .= $this->reporter->col('', '200');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    if ($center != '') {
      $str .= $this->reporter->col('Center:' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Center:' . ' ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
    }

    switch ($isposted) {
      case 0:
        $ming = 'posted';
        break;
      case 1:
        $ming = 'unposted';
      case 2:
        $ming = 'all';
        break;
    }
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ming), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->printline();


    return $str;
  }

  private function DEFAULT_BOUNCHECK_CHECK_LAYOUT($data, $params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '20';

    $this->reporter->linecounter = 0;

    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 41;
    $page = 40;
    $col = array(
      array('350', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('150', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'r', $font, '',  '', '', '', '', $margin)
    );
    $group = $str = '';
    $c = $total = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->DEFAULT_BOUNCED_CHECK_HEADER($params);

    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable('800');

    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      if (($group == '' || ($group != $data_->clientname && $data_->clientname != ''))) {

        if ($data_->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          $str .= $this->DEFAULT_BOUNCED_CHECK_SUBTOTAL($params, $c);
          #subtotal end
          $str .= $this->reporter->addline();
          $c = 0;
          $group = $data_->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($group, '200', '', false, $border, '', 'L', $font, '',  'B', '', '', $margin, 0);
        if ($c == 0) {
          $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', $margin, 0);
        } else {
          $str .= $this->reporter->col('Sub Total: ' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
        } #endif
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
      } # end if


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col($data_->docno, '200', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data_->trdate)), '100', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col($data_->chkinfo, '100', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data_->chkdate)), '100', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(number_format($data_->amount, $decimal_currency), '100', '', false, $border, '', 'R', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->endrow();


      $clientname = $data_->clientname;
      $c += $data_->amount;
      $total = $total + $data_->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->DEFAULT_BOUNCED_CHECK_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $str .= $this->reporter->begintable('800');
        $page += $count;
      } # end if


      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($data_->clientname == '') {
          $group = 'NO GROUP';
        } else {
          #subtotal here
          $str .= $this->DEFAULT_BOUNCED_CHECK_SUBTOTAL($params, $c);
          #subtotal end

          $str .= $this->reporter->addline();

          $c = 0;
          $group = $data_->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if
      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('Grand Total :', '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '150', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }


  private function VITALINE_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '165', '', false, '1px dashed', 'B', 'L', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Post Date', '135', '', false, '1px dashed', 'B', 'L', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Document #', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Trans. Date', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('Amount', '90', '', false, '1px dashed', 'B', 'R', $font, '',  'b', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function VITALINE_BOUNCED_CHECK_HEADER($params)
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

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800', null, '', $border, '', '', $font, '', '', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BOUNCED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
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
    } else {
      $ming = 'unposted';
    }
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ming), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    return $str;
  }

  private function VITALINE_BOUNCHECK_CHECK_LAYOUT($data, $params)
  {

    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '20';

    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = $page = 40;
    $col = array(
      array('350', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('150', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'l', $font, '',  '', '', '', '', $margin),
      array('200', '', false, $border, '', 'r', $font, '',  '', '', '', '', $margin)
    );
    $group = $str = '';
    $c = $total = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->VITALINE_BOUNCED_CHECK_HEADER($params);

    $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable('800');

    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      if (($group == '' || ($group != $data_->clientname && $data_->clientname != ''))) {

        if ($data_->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          $str .= $this->VITALINE_BOUNCED_CHECK_SUBTOTAL($params, $c);
          #subtotal end
          $str .= $this->reporter->addline();
          $c = 0;
          $group = $data_->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($group, '200', '', false, $border, '', 'l', $font, '',  'b', '', '', '', $margin);
        if ($c == 0) {
          $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
        } else {
          $str .= $this->reporter->col('', '', null, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
          $str .= $this->reporter->col('Sub Total: ' . number_format($c, 2), '90', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
        } #endif
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable('800');
      } # end if

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '220', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col($data_->postdate, '100', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col($data_->docno, '200', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data_->trdate)), '100', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col($data_->chkinfo, '130', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data_->chkdate)), '100', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(number_format($data_->amount, $decimal_currency), '100', '', false, $border, '', 'R', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->endrow();

      $clientname = $data_->clientname;
      $c += $data_->amount;
      $total = $total + $data_->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here

        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->VITALINE_BOUNCED_CHECK_HEADER($params);
        }
        $str .= $this->VITALINE_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $str .= $this->reporter->begintable('800');
        $page += $count;
      } # end if


      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($data_->clientname == '') {
          $group = 'NO GROUP';
        } else {
          #subtotal here
          $str .= $this->VITALINE_BOUNCED_CHECK_SUBTOTAL($params, $c);
          #subtotal end

          $str .= $this->reporter->addline();

          $c = 0;
          $group = $data_->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if
      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('Grand Total :', '100', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '80', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function DEFAULT_BOUNCED_CHECK_SUBTOTAL($params, $c)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $companyid = $params['params']['companyid'];
    $font_size = '10';
    $padding = '';
    $margin = '20';

    $str = '';
    if ($companyid == 59) { //rooosevelt
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('', '150', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('', '150', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);


      if ($c == 0) {
        $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '', $margin);
        $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
      } else {
        $str .= $this->reporter->col('Sub Total : ', '150', '', false, '1px solid', '', 'r', $font, '',  'b', '', '', '', $margin);
        $str .= $this->reporter->col('' . number_format($c, 2), '150', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
      } #end if

      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('', '150', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'c', $font, '',  'b', '', '', '', $margin);


      if ($c == 0) {
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '', $margin);
        $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
      } else {
        $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px solid', '', 'r', $font, '',  'b', '', '', '', $margin);
        $str .= $this->reporter->col('' . number_format($c, 2), '150', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
      } #end if

      $str .= $this->reporter->endrow();
    }

    return $str;
  }

  private function VITALINE_BOUNCED_CHECK_SUBTOTAL($params, $c)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '20';

    $str = '';

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '', $margin);
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '', $margin);
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '', $margin);
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '', $margin);


    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'c', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
    } else {
      $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
      $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px solid', '', 'r', $font, '',  'b', '', '', '', $margin);
      $str .= $this->reporter->col('' . number_format($c, 2), '90', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
    } #end if

    $str .= $this->reporter->endrow();
    return $str;
  }



  private function DEFAULT_BOUNCED_CHECK_HEADER_detailed_roosevelt($params)
  {

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $layoutsize = '1000';

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
    // $str .= $this->reporter->begintable($layoutsize);
    // $str .= $this->reporter->startrow();
    // $str .= $this->reporter->letterhead($center1, $username, $params);
    // $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'C', $font, '14', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, $border, '', 'C', $font, '13', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('BOUNCED CHECKS', null, null, false, $border, '', 'C', $font, '13', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(null, null, '', $border, '', 'r', $font, '10', '', '');

    $startdate = $start;
    $startt = new DateTime($startdate);
    $start = $startt->format('m/d/Y');

    $enddate = $end;
    $endd = new DateTime($enddate);
    $end = $endd->format('m/d/Y');

    $str .= $this->reporter->col('From ' . $start . ' TO ' . $end, null, null, '', $border, '', 'C', $font, '12', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    if ($center != '') {
      $str .= $this->reporter->col('Center:' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
    } else {
      $str .= $this->reporter->col('Center:' . ' ALL', null, null, false, $border, '', '', $font, '10', '', '', '');
    }

    switch ($isposted) {
      case 0:
        $ming = 'posted';
        break;
      case 1:
        $ming = 'unposted';
      case 2:
        $ming = 'all';
        break;
    }
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ming), null, null, false, $border, '', '', $font, '10', '', '', '');
    // $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->pagenumber('Page', null, null, false, $border, '', 'R', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    // $str .= $this->reporter->printline();

    return $str;
  }


  private function default_table_cols_detailed_roosevelt($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '5', false, '1px solid', 'T', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '200', '5', false, '1px solid', 'T', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '5', false, '1px solid', 'T', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '5', false, '1px solid', 'T', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '5', false, '1px solid', 'T', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('', '150', '5', false, '1px solid', 'T', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Document #', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Trans. Date', '150', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Check Info', '150', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Check Date', '150', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '');
    $str .= $this->reporter->col('Amount', '150', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function DEFAULT_BOUNCHECK_CHECK_LAYOUT_detailed_roosevelt($data, $params)
  {

    $border = '1px solid';
    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = '11';
    $margin = '20';

    $this->reporter->linecounter = 0;

    // for decimal settings
    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $count = 41;
    $page = 40;
    $layoutsize = '1000';
    $group = $str = '';
    $c = $total = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();
    // $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '25px;margin-top:10px;margin-left:200px');

    #header here
    $str .= $this->DEFAULT_BOUNCED_CHECK_HEADER_detailed_roosevelt($params);

    $str .= $this->default_table_cols_detailed_roosevelt($layoutsize, $border, $font, $fontsize11, $params);
    #header end

    #loop starts

    $str .= $this->reporter->begintable($layoutsize);

    foreach ($data as $key => $data_) {
      $cnt1 += 1;

      if (($group == '' || ($group != $data_->clientname && $data_->clientname != ''))) {

        if ($data_->clientname == '') {
          $group = 'NO GROUP';
        } else {

          #subtotal here
          $str .= $this->DEFAULT_BOUNCED_CHECK_SUBTOTAL($params, $c);
          #subtotal end
          $str .= $this->reporter->addline();
          $c = 0;
          $group = $data_->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($group, $layoutsize, '', false, $border, '', 'L', $font, '',  'B', '', '', $margin, 0);
        if ($c == 0) {
          $str .= $this->reporter->col('', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', $margin, 0);
        } else {
          $str .= $this->reporter->col('Sub Total: ' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'r', $font, '',  'i', '', '', '', $margin);
        } #endif
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
      } # end if


      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col($data_->docno, '200', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data_->trdate)), '150', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col($data_->chkinfo, '150', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(date('m/d/Y', strtotime($data_->chkdate)), '150', '', false, $border, '', 'L', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->col(number_format($data_->amount, $decimal_currency), '150', '', false, $border, '', 'R', $font, '',  '', '', '', '', $margin);
      $str .= $this->reporter->endrow();


      $clientname = $data_->clientname;
      $c += $data_->amount;
      $total = $total + $data_->amount;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {

          $str .= $this->DEFAULT_BOUNCED_CHECK_HEADER($params);
        }
        $str .= $this->default_table_cols_detailed_roosevelt($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $str .= $this->reporter->begintable($layoutsize);
        $page += $count;
      } # end if


      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($data_->clientname == '') {
          $group = 'NO GROUP';
        } else {
          #subtotal here
          $str .= $this->DEFAULT_BOUNCED_CHECK_SUBTOTAL($params, $c);
          #subtotal end

          $str .= $this->reporter->addline();

          $c = 0;
          $group = $data_->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
      } # end if
      $str .= $this->reporter->endrow();
    } //end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '');
    $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col('Grand Total :', '150', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '150', '', false, '1px dashed', 'T', 'r', $font, '',  'b', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class