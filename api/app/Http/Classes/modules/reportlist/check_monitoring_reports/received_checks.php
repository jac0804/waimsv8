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


class received_checks
{
  public $modulename = 'Received Checks';
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

    if ($companyid == 19) { //housegem
      $fields = ['dateid', 'end', 'dcentername', 'dclientname'];
      $col2 = $this->fieldClass->create($fields);
      data_set($col2, 'dclientname.lookupclass', 'lookupclient');
      data_set($col2, 'dclientname.label', 'Customer');
    } else {
      $fields = ['dateid', 'due', 'dcentername'];
      $col2 = $this->fieldClass->create($fields);
    }
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);
    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);


    $fields = ['radioposttype', 'radioreporttype'];
    $col3 = $this->fieldClass->create($fields);
    data_set($col3, 'radioreporttype.label', 'Date based on:');
    data_set(
      $col3,
      'radioreporttype.options',
      [
        ['label' => 'Transaction Date', 'value' => '0', 'color' => 'pink'],
        ['label' => 'Check Date', 'value' => '1', 'color' => 'pink']
      ]
    );

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
        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as dateid,
                            left(now(),10) as due,'" . $defaultcenter[0]['center'] . "' as center,
                            '" . $defaultcenter[0]['centername'] . "' as centername,
                            '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
                            '0' as posttype,'0' as reporttype";
        break;
      case 19: //housegem
        $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as dateid,
                            left(now(),10) as due,'' as center,'' as centername,'' as dcentername,
                            '0' as posttype,'0' as reporttype, '' as client, '' as clientname, 
                            '' as dclientname, left(now(),10) as end";
        break;
      default:
        $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as dateid,
                            left(now(),10) as due,'' as center,'' as centername,'' as dcentername,
                            '0' as posttype,'0' as reporttype";
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
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = '';
    if ($companyid == 19) { //housegem
      $end = date('Y-m-d', strtotime($filters['params']['dataparams']['end']));
    } else {
      $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    }
    $isposted = $filters['params']['dataparams']['posttype'];
    $checks = $filters['params']['dataparams']['reporttype'];
    $center = $filters['params']['dataparams']['center'];
    $filter = "";

    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "'  ";
    }

    if ($companyid == 19) { //housegem
      $client = $filters['params']['dataparams']['client'];
      $clientid = '';
      if ($client != '') {
        $clientid = $this->coreFunctions->getfieldvalue("client", "clientid", "client=?", [$client]);
        if ($isposted == 1) {
          $filter .= " and head.client='" . $client . "' ";
        } else {
          $filter .= " and head.clientid='" . $clientid . "' ";
        }
      }
    }

    switch ($isposted) {
      case 1:
        if ($checks == 0) {
          $ch = "date(head.dateid)";
        } else {
          $ch = "date(detail.postdate)";
        } //end if

        $query = "select 'received checks' as type,  trdate  as pridate, chkdate as suppdate,
                          clientname, docno, chkinfo, amount, postdate, ref 
                  from (select detail.postdate as chkdate, head.client, head.clientname, head.docno,
                                head.dateid as trdate, detail.checkno as chkinfo, 
                                abs(detail.db-detail.cr) as amount,date(cntnum.postdate) as postdate, 
                                (select ifnull(group_concat(distinct ref),'')  
                                from ladetail where trno = head.trno and ref <> '' 
                                      and left(ref,2) in ('SJ', 'DR') ) as ref
                        from ((lahead as head left join ladetail as detail on detail.trno=head.trno)
                        left join coa on coa.acnoid=detail.acnoid) 
                        left join cntnum on cntnum.trno=head.trno
                        where cntnum.doc='cr'  and left(coa.alias, 2)='cr' 
                              and $ch between '" . $start . "' and '" . $end . "' " . $filter . ") as t 
                  order by clientname";
        break;

      case 0:
        if ($checks == 0) {
          $ch = "date(head.dateid)";
        } else {
          $ch = "date(cr.checkdate)";
        } //end if

        $query = "select 'received checks' as type, trdate as pridate, chkdate as suppdate,
                          clientname, docno, chkinfo, amount, postdate, ref 
                  from (select cr.checkdate as chkdate, head.clientname, head.docno, head.dateid as trdate,
                              cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client,
                              date(cntnum.postdate) as postdate, (select ifnull(group_concat(distinct ref),'')  
                              from gldetail where trno = head.trno and ref <> '' and left(ref,2) in ('SJ', 'DR') ) as ref
                        from ((crledger as cr 
                        left join glhead as head on head.trno=cr.trno)
                        left join client on client.clientid=head.clientid) 
                        left join cntnum on cntnum.trno=cr.trno
                        where  $ch between '" . $start . "' and '" . $end . "' " . $filter . ") as rc  
                  where clientname<>'' 
                  order by clientname";
        break;
      case 2: //all
        if ($checks == 0) {
          $ch = "date(head.dateid)";
        } else {
          $ch = "date(detail.postdate)";
        } //end if

        $query = "select 'received checks' as type, trdate as pridate, chkdate as suppdate,
                          clientname, docno, chkinfo, amount, postdate, ref
                  from(select cr.checkdate as chkdate, head.clientname, head.docno, head.dateid as trdate,
                              cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client,
                              date(cntnum.postdate) as postdate,(select ifnull(group_concat(distinct ref),'')
                              from gldetail where trno = head.trno and ref <> '' 
                              and left(ref,2) in ('SJ', 'DR') ) as ref
                      from ((crledger as cr
                      left join glhead as head on head.trno=cr.trno)
                      left join client on client.clientid=head.clientid)
                      left join gldetail as detail on detail.trno=cr.trno and detail.line=cr.line
                      left join cntnum on cntnum.trno=cr.trno
                      where $ch between '" . $start . "' and '" . $end . "' " . $filter . " ) as rc
                  where clientname <> ''
                  group by trdate, chkdate,  clientname, docno, chkinfo, amount, postdate, ref
                  union all
                  select 'received checks' as type,  trdate  as pridate, chkdate as suppdate,
                          clientname, docno, chkinfo, amount, postdate, ref 
                  from (select detail.postdate as chkdate, head.client, head.clientname, head.docno,
                              head.dateid as trdate, detail.checkno as chkinfo, 
                              abs(detail.db-detail.cr) as amount,date(cntnum.postdate) as postdate, 
                              (select ifnull(group_concat(distinct ref),'')  
                              from ladetail where trno = head.trno and ref <> '' 
                              and left(ref,2) in ('SJ', 'DR') ) as ref
                        from ((lahead as head 
                        left join ladetail as detail on detail.trno=head.trno)
                        left join coa on coa.acnoid=detail.acnoid) 
                        left join cntnum on cntnum.trno=head.trno
                        where cntnum.doc='cr' and left(coa.alias, 2)='cr' 
                              and $ch between '" . $start . "' and '" . $end . "' " . $filter . ") as t 
                  order by clientname";
        break;
    }
    $data = $this->coreFunctions->opentable($query);
    return $data;
  }

  public function VITALINE_query($filters)
  {
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $isposted = $filters['params']['dataparams']['posttype'];
    $checks = $filters['params']['dataparams']['reporttype'];
    $center = $filters['params']['dataparams']['center'];
    $filter = "";

    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "'  ";
    }

    switch ($isposted) {
      case 1:
        if ($checks == 0) {
          $ch = "head.dateid";
          $transdate = ", head.dateid as transdate";
          $sorting = "dateid";
        } else {
          $ch = "detail.postdate";
          $transdate = ", detail.postdate as transdate";
          $sorting = "postdate";
        } //end if

        $query = "select 'received checks' as type,  trdate  as pridate, chkdate as suppdate,
                          clientname, docno, chkinfo, amount, postdate, dateid, transdate from
                          (select detail.postdate as chkdate, head.client, head.clientname, head.docno,
                          head.dateid as trdate, head.dateid as dateid, detail.checkno as chkinfo, 
                          abs(detail.db-detail.cr) as amount,date(cntnum.postdate) as postdate $transdate
                  from ((lahead as head 
                  left join ladetail as detail on detail.trno=head.trno)
                  left join coa on coa.acnoid=detail.acnoid)left 
                  join cntnum on cntnum.trno=head.trno
                  where cntnum.doc='cr'  and left(coa.alias, 2)='cr' 
                        and $ch between '" . $start . "' and '" . $end . "' " . $filter . ") as t 
                  order by $sorting ASC";
        break;
      case 0:
        if ($checks == 0) {
          $ch = "head.dateid";
          $transdate = ", head.dateid as transdate";
          $sorting = "dateid";
        } else {
          $ch = "cr.checkdate";
          $transdate = ", cr.checkdate as transdate";
          $sorting = "checkdate";
        } //end if

        $query = "select 'received checks' as type, trdate as pridate, chkdate as suppdate, checkdate,
                          clientname, docno, chkinfo, amount, postdate, dateid, transdate 
                  from (select date(cr.checkdate) as chkdate, head.clientname, date(cr.checkdate) as checkdate,
                                head.docno, date(head.dateid) as trdate, date(head.dateid) as dateid,
                                cr.checkno as chkinfo, abs(cr.db-cr.cr) as amount, client.client,
                                date(cntnum.postdate) as postdate $transdate
                        from ((crledger as cr 
                        left join glhead as head on head.trno=cr.trno)
                        left join client on client.clientid=head.clientid)
                        left join cntnum on cntnum.trno=cr.trno
                        where  $ch between '" . $start . "' and '" . $end . "' " . $filter . ") as rc  
                  where clientname<>'' 
                  order by $sorting ASC";
        break;
    }

    $data = $this->coreFunctions->opentable($query);
    return $data;
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
        $reportdata =  $this->VITALINE_RECIEVED_CHECKS_LAYOUT($config, $result);
        break;

      case 21: //kinggeorge
        $reportdata =  $this->kinggeorge_layout($config, $result);
        break;

      default:
        $reportdata =  $this->DEFAULT_RECIEVED_CHECKS_LAYOUT($config, $result);
        break;
    }
    return $reportdata;
  }

  private function DEFAULT_RECEIVED_CHECKS_HEADER($params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $companyid = $params['params']['companyid'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = '';
    if ($companyid == 19) { //housegem
      $end = date("Y-m-d", strtotime($params['params']['dataparams']['end']));
    } else {
      $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
    }
    $isposted = $params['params']['dataparams']['posttype'];
    $checks = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['dataparams']['center'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

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

    if ($center == '') {
      $center = 'ALL';
    }

    $str = '';

    
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($center1, $username, $params);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

   

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RECEIVED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Center: ' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->printline();

    return $str;
  }

  private function default_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '600');
    $str .= $this->reporter->col('Document #', '150', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Trans. Date', '125', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Amount', '125', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '', '');

    return $str;
  }

  private function DEFAULT_RECIEVED_CHECKS_LAYOUT($params, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = "";
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $clientname = '';
    $c = 0;
    $c2 = 0;
    $total = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->DEFAULT_RECEIVED_CHECKS_HEADER($params);
    $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      if ($clientname != $value->clientname) {
        if ($clientname != '') {
          #subtotal here
          $str .= $this->DEFAULT_RECEIVED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end
          $c = 0;
        }
        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->clientname, '800', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'L', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->docno, '150', '', false, $border, '', 'L', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->pridate)), '125', '', false, $border, '', 'L', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'L', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->suppdate)), '100', '', false, $border, '', 'R', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, $decimal_currency), '125', '', false, $border, '', 'R', $font, '',  '', '', '', '', '');

      $clientname = $value->clientname;
      $c = $c + $value->amount;
      $c2 = $value->amount;
      $total = $total + $c2;
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->DEFAULT_RECEIVED_CHECKS_HEADER($params);
        }
        $str .= $this->default_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {
          #subtotal here
          $str .= $this->DEFAULT_RECEIVED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end
          $str .= $this->reporter->addline();
          $c = 0;
          $group = $value->clientname;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if
      $str .= $this->reporter->endrow();
    } // end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');

    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn


  private function DEFAULT_RECEIVED_CHECKS_SUBTOTAL($params, $c)
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
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'R', $font, '',  'B', '', '', '', '');

    if ($c == 0) {
      $str .= $this->reporter->col('', '125', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    } else {
      $str .= $this->reporter->col('' . number_format($c, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function kinggeorge_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $str .= $this->reporter->begintable();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Customer Name', '200', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '600');
    $str .= $this->reporter->col('Document #', '150', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Trans. Date', '125', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'L', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Ref', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Amount', '125', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  private function kinggeorge_header($params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';
    $companyid = $params['params']['companyid'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));

    $isposted = $params['params']['dataparams']['posttype'];
    $checks = $params['params']['dataparams']['reporttype'];
    $center = $params['params']['dataparams']['center'];
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];

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

    if ($center == '') {
      $center = 'ALL';
    }

    $str = '';

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center1, $username, $params);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RECEIVED CHECKS', null, null, false, $border, '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Center: ' . $center, null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, $border, '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->printline();

    return $str;
  }

  private function kinggeorge_layout($params, $data)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $fontsize11 = 11;
    $padding = '';
    $margin = '';

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = "";
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $clientname = '';
    $c = 0;
    $c2 = 0;
    $total = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport('1000');

    #header here
    $str .= $this->kinggeorge_header($params);
    $str .= $this->kinggeorge_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      if ($clientname != $value->clientname) {
        if ($clientname != '') {
          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '200', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px dashed', '', 'R', $font, '',  'B', '', '', '', '');

          if ($c == 0) {
            $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
          } else {
            $str .= $this->reporter->col('' . number_format($c, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
          }
          $str .= $this->reporter->endrow();

          #subtotal end
          $c = 0;
        }
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($value->clientname, '200', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, $border, '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->docno, '150', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->pridate)), '125', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(date('M-d-Y', strtotime($value->suppdate)), '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(wordwrap($value->ref, 16, "\n", true), '100', '', false, $border, '', 'LT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, $decimal_currency), '125', '', false, $border, '', 'RT', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->endrow();
      $clientname = $value->clientname;
      $c = $c + $value->amount;
      $c2 = $value->amount;
      $total = $total + $c2;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();

        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->kinggeorge_header($params);
        }
        $str .= $this->kinggeorge_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $page = $page + $count;
      }

      if ($cnt == $cnt1) {
        if ($value->clientname == '') {
          $group = 'NO GROUP';
        } else {
          $str .= $this->reporter->addline();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '200', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
          $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px dashed', '', 'R', $font, '',  'B', '', '', '', '');

          if ($c == 0) {
            $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
          } else {
            $str .= $this->reporter->col('' . number_format($c, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
          }
          $str .= $this->reporter->endrow();
          $c = 0;
          $group = $value->clientname;
        } #end if
      } # end if
    } // end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
      $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '100', '', false, $border, '', 'C', $font, '',  'B', '', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '125', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '125', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  } // end fn

  private function VITALINE_RECEIVED_CHECKS_HEADER($params)
  {
    $border = '1px solid';
    $border_line = '';
    $alignment = '';

    $font = $this->companysetup->getrptfont($params['params']);
    $font_size = '10';
    $padding = '';
    $margin = '';

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
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

    if ($center == '') {
      $center = 'ALL';
    }

    $str = '';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($params['params']['center'], $params['params']['user']);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RECEIVED CHECKS', null, null, false, '1px solid ', '', '', $font, '15', 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Date Base on : ' . strtoupper($checksstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Center: ' . $center, null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($ispostedstr), null, null, false, '1px solid ', '', '', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->printline();

    return $str;
  }

  private function vitaline_table_cols($layoutsize, $border, $font, $fontsize, $config)
  {
    $str = '';
    $companyid = $config['params']['companyid'];
    $checks = $config['params']['dataparams']['reporttype'];

    $str .= $this->reporter->begintable();
    $str .= $this->reporter->col('Post Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '600');
    if ($checks == 0) {
      $str .= $this->reporter->col('Collection Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '');
    }
    $str .= $this->reporter->col('Document #', '150', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '');
    if ($checks == 0) {
      $str .= $this->reporter->col('Check Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Collection Date', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '');
    }
    $str .= $this->reporter->col('Check Info', '100', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Customer Name', '150', '', false, '1px dashed', 'B', 'C', $font, '',  'B', '', '', '', '600');
    $str .= $this->reporter->col('Amount', '100', '', false, '1px dashed', 'B', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->endtable();

    return $str;
  }

  private function VITALINE_RECIEVED_CHECKS_LAYOUT($params, $data)
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

    $companyid = $params['params']['companyid'];
    $decimal_currency = $this->companysetup->getdecimal('currency', $params['params']);

    $str = "";
    $count = 40;
    $page = 40;

    $clientname = '';
    $transdate = '';
    $c = 0;
    $c2 = 0;
    $total = 0;

    $cnt = count((array)$data);
    $cnt1 = 0;

    if (empty($data)) {
      return $this->othersClass->emptydata($params);
    }

    $str .= $this->reporter->beginreport();

    #header here
    $str .= $this->VITALINE_RECEIVED_CHECKS_HEADER($params);
    $str .= $this->vitaline_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
    #header end

    $str .= $this->reporter->begintable();

    foreach ($data as $key => $value) {
      $cnt1 += 1;
      if ($transdate != $value->transdate) {
        if ($transdate != '') {
          #subtotal here
          $str .= $this->VITALINE_RECEIVED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end
          $c = 0;
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '', '');
        if ($checks == 0) {
          $str .= $this->reporter->col(date('m-d-Y', strtotime($value->pridate)), '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '', '');
        } else {
          $str .= $this->reporter->col(date('m-d-Y', strtotime($value->suppdate)), '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '', '');
        }
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
        $str .= $this->reporter->endrow();
      }

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($value->postdate, '150', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'L', $font, '',  'B', '', '', '', '');
      $str .= $this->reporter->col($value->docno, '100', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '', '');
      if ($checks == 0) {
        $str .= $this->reporter->col(date('m-d-Y', strtotime($value->suppdate)), '100', '', false, '1px solid', '', 'C', $font, '',  '', '', '', '', '');
      } else {
        $str .= $this->reporter->col(date('m-d-Y', strtotime($value->pridate)), '100', '', false, '1px solid', '', 'C', $font, '',  '', '', '', '', '');
      }
      $str .= $this->reporter->col($value->chkinfo, '100', '', false, '1px solid', '', 'C', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col($value->clientname, '150', '', false, '1px solid', '', 'L', $font, '',  '', '', '', '', '');
      $str .= $this->reporter->col(number_format($value->amount, $decimal_currency), '100', '', false, '1px solid', '', 'R', $font, '',  '', '', '', '', '');

      $clientname = $value->clientname;
      $transdate = $value->transdate;
      $c = $c + $value->amount;
      $c2 = $value->amount;
      $total = $total + $c2;
      $str .= $this->reporter->addline();
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        #header here
        $allowfirstpage = $this->companysetup->getisfirstpageheader($params['params']);
        if (!$allowfirstpage) {
          $str .= $this->VITALINE_RECEIVED_CHECKS_HEADER($params);
        }
        $str .= $this->vitaline_table_cols($this->reportParams['layoutSize'], $border, $font, $fontsize11, $params);
        #header end
        $str .= $this->reporter->begintable();
        $page = $page + $count;
      }
      $str .= $this->reporter->startrow();
      if ($cnt == $cnt1) {
        if ($value->transdate == '') {
          $group = 'NO GROUP';
        } else {
          #subtotal here
          $str .= $this->VITALINE_RECEIVED_CHECKS_SUBTOTAL($params, $c);
          #subtotal end
          $str .= $this->reporter->addline();
          $c = 0;
          $group = $value->transdate;
        } #end if
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable('800');
      } # end if
      $str .= $this->reporter->endrow();
    } // end foreach

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');

    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
      $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    } else {
      $str .= $this->reporter->col('Sub Total : ', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
      $str .= $this->reporter->col(number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Grand Total : ', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col(number_format($total, 2), '100', '', false, '1px dashed', 'T', 'R', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  } // end fn

  private function VITALINE_RECEIVED_CHECKS_SUBTOTAL($params, $c)
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
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '150', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('', '100', '', false, '1px solid', '', 'C', $font, '',  'B', '', '', '', '');
    $str .= $this->reporter->col('Sub Total : ', '100', '', false, '1px solid', '', 'R', $font, '',  'B', '', '', '', '');

    if ($c == 0) {
      $str .= $this->reporter->col('', '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    } else {
      $str .= $this->reporter->col('' . number_format($c, 2), '100', '', false, '1px dashed', 'T', 'R', $font, '',  'I', '', '', '', '');
    }
    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class