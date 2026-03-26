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

class cash_advance_report
{
  public $modulename = 'Cash Advance Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '1000'];

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

    $fields = ['dateid', 'due', 'empcode', 'dcentername', 'costcenter', 'ddeptname'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'ddeptname.label', 'Department');
    data_set($col2, 'empcode.required', false);


    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);

    data_set($col2, 'costcenter.label', 'Item Group');

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
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as dateid,
      left(now(),10) as due,
      0 as empid,
      '' as empcode,
      '' as contra,
      '' as acnoname,
      '' as center,
      '' as centername,
      '' as dcentername,
      '' as code,
      '' as name,
      '0' as posttype,
      '' as dacnoname,
      '' as costcenter,'0' as costcenterid,
      '' as client,
      '' as clientname,
      '' as clientid,
      0 as deptid,
      '' as ddeptname, 
      '' as dept,
      '' as deptname ";
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

    $reporttype = $filters['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $costcenter = isset($filters['params']['dataparams']['costcenter']) ? $filters['params']['dataparams']['costcenter'] : "";
    $costcenterid = isset($filters['params']['dataparams']['costcenterid']) ? $filters['params']['dataparams']['costcenterid'] : 0;
    $companyid = $filters['params']['companyid'];

    $empcode = $filters['params']['dataparams']['empcode'];
    $empid = $filters['params']['dataparams']['empid'];

    $deptcode = $filters['params']['dataparams']['dept'];
    $deptid = $filters['params']['dataparams']['deptid'];

    $filter = "";

    if ($empcode != "") {
      $filter .= " and client.clientid = '" . $empid . "' ";
    }

    if ($costcenter != "") {
      $filter .= " and head.projectid = '" . $costcenterid . "' ";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }

    if ($deptcode != "") {
      $filter .= " and head.deptid = $deptid";
    }

    $filter .= " and coa.alias='ARCA'";

    switch ($reporttype) {
      case 0: // posted
        $query = "select a.trno, a.docno, a.dateid, a.client, a.clientname, a.acno,
         (a.db- a.cr) as  db, case when a.cr>0 then a.bal*-1 else a.bal end as bal, a.rem 
        from (
        select head.trno,head.docno, date(head.dateid) as dateid, client.clientname,
        sum(detail.db) as db, coa.acno, client.client,
        sum(detail.cr) as cr, sum(arledger.bal) as bal, head.rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        left join arledger on arledger.trno=detail.trno and arledger.line=detail.line
        left join coa on coa.acnoid = detail.acnoid
        left join client on client.clientid = detail.clientid
        left join cntnum on cntnum.trno=head.trno
        where date(head.dateid) between '" . $start . "' and '" . $end . "'
        and detail.refx=0 " . $filter . "
        group by head.trno, head.docno, dateid, client.clientname, coa.acno, client.client, head.rem
        having sum(arledger.bal) > 0) as a
        where a.acno is not null
        order by client,dateid,docno";
        break;

      case 1: // unposted
        $query = "select a.trno, a.docno, a.dateid, a.client, a.clientname, a.acno,
         (a.db- a.cr) as  db, a.bal, a.rem  from (
        select head.trno, head.docno, date(head.dateid) as dateid, client.clientname,
        sum(detail.db) as db, coa.acno, client.client,
        sum(detail.cr) as cr, sum(detail.db - detail.cr) as bal, head.rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        left join coa on coa.acnoid = detail.acnoid
        left join client on client.client = detail.client
        left join cntnum on cntnum.trno=head.trno
        where
        date(head.dateid) between '" . $start . "' and '" . $end . "'
        and detail.refx=0 " . $filter . "
        group by head.trno, head.docno, head.dateid, client.clientname, 
        coa.acno, client.client, head.rem) as a
        where a.acno is not null
        order by client,dateid,docno";
        break;
      default: // all

        $query = "select a.trno,  a.docno, a.dateid, a.client, a.clientname, a.acno, (a.db- a.cr) as  db, a.bal, a.rem 
        from (
        select  head.trno,head.docno, date(head.dateid) as dateid, client.clientname,
        sum(detail.db) as db, coa.acno, client.client,
        sum(detail.cr) as cr, sum(detail.db - detail.cr) as bal, head.rem
        from lahead as head
        left join ladetail as detail on head.trno = detail.trno
        left join coa on coa.acnoid = detail.acnoid
        left join client on client.client = detail.client
        left join cntnum on cntnum.trno=head.trno
        where
        date(head.dateid) between '" . $start . "' and '" . $end . "'
        and detail.refx=0 " . $filter . "
        group by  head.trno,head.docno, head.dateid, client.clientname, coa.acno, client.client, head.rem
        union all 
        select head.trno,head.docno, date(head.dateid) as dateid, client.clientname,
        sum(detail.db) as db, coa.acno, client.client,
        sum(detail.cr) as cr, sum(arledger.bal) as bal, head.rem
        from glhead as head
        left join gldetail as detail on head.trno = detail.trno
        left join arledger on arledger.trno=detail.trno and arledger.line=detail.line
        left join coa on coa.acnoid = detail.acnoid
        left join client on client.clientid = detail.clientid
        left join cntnum on cntnum.trno=head.trno
        where
        date(head.dateid) between '" . $start . "' and '" . $end . "'
        and detail.refx=0 " . $filter . "
        group by head.trno,head.docno, dateid, client.clientname, coa.acno, client.client, head.rem
        having sum(arledger.bal) > 0 ) as a
        where a.acno is not null
        order by  client,dateid,docno";
        break;
    } // end switch
    $result = $this->coreFunctions->opentable($query);
    return $result;
  }

  public function reportplotting($config)
  {

    $result = $this->default_query($config);
    $reportdata =  $this->DEFAULT_CASH_ADVANCE_LAYOUT($config, $result);
    return $reportdata;
  }

  private function headerlabel($params)
  {
    $reporttype = $params['params']['dataparams']['posttype'];
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
    $center = $params['params']['dataparams']['center'];
    $costcenter = isset($params['params']['dataparams']['costcenter']) ? $params['params']['dataparams']['costcenter'] : "";
    $center1 = $params['params']['center'];
    $username = $params['params']['user'];
    $empcode = $params['params']['dataparams']['empcode'];
    $empname = $this->coreFunctions->getfieldvalue('client', 'clientname', 'client=?', [$empcode]);

    $font = $this->companysetup->getrptfont($params['params']);
    if ($empcode == "") {
      $employee = "ALL";
    } else {
      $employee = $empcode . ' - ' . $empname;
    }

    switch ($reporttype) {
      case 0:
        $reporttype = 'posted';
        break;
      case 1:
        $reporttype = 'unposted';
        break;
      case 2:
        $reporttype = 'ALL';
        break;
    }

    if ($center == "") {
      $center = "ALL";
    }
    $dept   = $params['params']['dataparams']['ddeptname'];
    $costcenter = $params['params']['dataparams']['code'];
    if ($costcenter != "") {
      $costcenter = $params['params']['dataparams']['name'];
    } else {
      $costcenter = "ALL";
    }
    if ($dept != "") {
      $deptname = $params['params']['dataparams']['deptname'];
    } else {
      $deptname = "ALL";
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
    $str .= $this->reporter->col('CASH ADVANCE REPORT', 300, null, false, '1px solid ', '', 'L', $font, '15', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Center : ' . $center, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Project : ' . $costcenter, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Transaction: ' . strtoupper($reporttype), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->pagenumber('Page');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->col('Employee: ' . strtoupper($employee), null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Department : ' . $deptname, null, null, false, '1px solid ', '', 'L', $font, '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee', '200', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Transaction Date', '120', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Document#', '170', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Particular', '250', null, false, '1px solid', 'B', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('CA Amount', '130', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->col('Balance', '130', null, false, '1px solid', 'B', 'R', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function default_subtotal($db, $bal, $params)
  {
    $str = '';
    $fontsize = 9;
    $font = $this->companysetup->getrptfont($params['params']);
    $str .= $this->reporter->col('', '200', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '170', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '250', null, false, '1px solid', 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($db, 2), '130', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($bal, 2), '130', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '');
    return $str;
  } // end fn

  private function DEFAULT_CASH_ADVANCE_LAYOUT($params, $data)
  {
    $count = 36;
    $page = 35;
    $this->reporter->linecounter = 0;
    $str = '';
    $fontsize = 9;
    $font = $this->companysetup->getrptfont($params['params']);

    $str .= ' <br/><br/> ';
    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params);
    $totaldb = 0;
    $totalbal = 0;
    $db = 0;
    $bal = 0;
    $group = '';
    $a = $b = 0;
    $cnt = count((array)$data);
    $cnt1 = 0;
    if (!empty($data)) {
      foreach ($data as $key => $data_) {
        $cnt1 += 1;
        if (($group == '' || ($group != $data_->client && $data_->client != ''))) {
          if ($data_->client == '') {
            $group = 'NO Employee';
          } else {
            #subtotal here
            $str .= $this->DEFAULT_CASHADVANCE_SUBTOTAL($a, $b, $params);
            #subtotal end
            $str .= $this->reporter->addline();
            $a = 0;
            $b = 0;
            $group = $data_->client;
          }

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($data_->clientname, '200', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col($data_->dateid, '120', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col($data_->docno, '170', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col($data_->rem, '250', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col(number_format($data_->db, 2), '130', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col(number_format($data_->bal, 2), '130', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->endrow();
        } else {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '200', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col($data_->dateid, '120', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col($data_->docno, '170', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col($data_->rem, '250', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', 0);
          $str .= $this->reporter->col(number_format($data_->db, 2), '130', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->col(number_format($data_->bal, 2), '130', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px', 0);
          $str .= $this->reporter->endrow();
        }

        $totaldb += $data_->db;
        $db += $data_->db;
        $totalbal += $data_->bal;
        $bal += $data_->bal;

        $a += $data_->db;
        $b += $data_->bal;

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->printline();
          $str .= $this->reporter->page_break();
          $str .= $this->headerlabel($params);
          $page = $page + $count;
        } // end if

        $str .= $this->reporter->startrow();
        if ($cnt == $cnt1) {
          if ($data_->client == '') {
            $group = 'NO Employee';
          } else {
            #subtotal here
            $str .= $this->DEFAULT_CASHADVANCE_SUBTOTAL($a, $b, $params);
            #subtotal end
            $str .= $this->reporter->addline();
            $a = $b = 0;
            $group = $data_->client;
          } #end if
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable('1000');
        } # end if
        $str .= $this->reporter->endrow();
      }
    }

    $str .= $this->default_subtotal($db, $bal, $params);
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();
    return $str;
  }

  private function DEFAULT_CASHADVANCE_SUBTOTAL($a, $b, $params)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($params['params']);
    $fontsize = "9";
    $border = "1px solid ";

    $str .= $this->reporter->startrow();
    if ($a == 0 && $b == 0) {
      $str .= $this->reporter->col('', '200', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', 0);
      $str .= $this->reporter->col('', '120', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', 0);
      $str .= $this->reporter->col('', '170', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', 0);
      $str .= $this->reporter->col('', '250', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', 0);
      $str .= $this->reporter->col('', '130', '', false, $border, '', 'c', $font, $fontsize,  'b', '', '', '', 0);
      $str .= $this->reporter->col('', '130', false, '1px dashed', 'T', 'r', $font, $fontsize,  'i', '', '', '', 0);
    } else {
      $str .= $this->reporter->col('', '200', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', 0);
      $str .= $this->reporter->col('', '120', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', 0);
      $str .= $this->reporter->col('', '170', '', false, '1px dashed', 'T', 'c', $font, '',  'b', '', '', '', 0);
      $str .= $this->reporter->col('SUB TOTAL', '250', '', false, '1px dashed', 'T', 'l', $font, $fontsize,  'b', '', '', '', 0);
      $str .= $this->reporter->col('' . number_format($a, 2), '130', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
      $str .= $this->reporter->col('' . number_format($b, 2), '130', '', false, '1px dashed', 'T', 'r', $font, $fontsize,  'b', '', '', '', 0);
    } #end if
    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class