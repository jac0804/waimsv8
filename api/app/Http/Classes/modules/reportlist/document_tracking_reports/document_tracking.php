<?php

namespace App\Http\Classes\modules\reportlist\document_tracking_reports;

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

class document_tracking
{
  public $modulename = 'Document Tracking Report';
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

    $fields = ['radioprint', 'start', 'end', 'dtuserlevel', 'reportusers', 'dtstatus'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radiodtfilter', 'radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $paramstr = "select 
      'default' as print,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end,
      '' as dtuserlevel,
      '' as userid,
      '' as username,
      '' as dtstatus,
      0 as radiodtfilter,
      '0' as reporttype,
      '' as reportusers";

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

  public function reportplotting($config)
  {
    switch ($config['params']['dataparams']['reporttype']) {
      case 0: // summarized
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      case 1: // detailed
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $userlevel = $config['params']['dataparams']['dtuserlevel'];
    $userid = $config['params']['dataparams']['userid'];
    $username = $config['params']['dataparams']['username'];
    $dtstatus = $config['params']['dataparams']['dtstatus'];
    $radiodtfilter = $config['params']['dataparams']['radiodtfilter'];

    $filter = "";

    if ($userlevel != "") {
      $filter .= " and u.username = '" . $userlevel . "' ";
    }
    if ($username != "") {
      $filter .= " and ua.username = '" . $username . "' ";
    }
    if ($dtstatus != "") {
      $filter .= " and sl.status = '" . $dtstatus . "' ";
    }

    switch ($radiodtfilter) {
      case 0:
        $query = "select head.docno, date(head.dateid) as dateid, head.terms, date(head.invdate) as invdate, head.invoiceno, head.amt, date(head.currentdate) as currentdate, sl.status,
        u.username as usertype, ua.username
        from dt_dthead as head 
          left join dt_status as s on head.currentstatusid=s.id
          left join dt_statuslist as sl on sl.id=s.statusdoc
          left join users as u on u.idno=head.currentusertypeid
          left join useraccess as ua on ua.userid=head.currentuserid
        where date(head.currentdate) between '" . $start . "' and '" . $end . "' " . $filter;
        break;
      case 1:
        $query = "select head.docno, date(stock.dateid) as dateid, head.terms, date(head.invdate) as invdate, head.invoiceno, head.amt, date(stock.dateid) as currentdate,
        sl.status, u.username as usertype, ua.username
        from dt_dthead as head
          left join dt_dtstock as stock on stock.trno=head.trno
          left join dt_status as s on stock.docstatusid=s.id
          left join dt_statuslist as sl on sl.id=s.statusdoc
          left join users as u on u.idno=stock.usertypeid
          left join useraccess as ua on ua.userid=stock.userid
        where date(stock.dateid) between '" . $start . "' and '" . $end . "' " . $filter;
        break;
    }

    return $this->coreFunctions->opentable($query);
  }

  public function loadSummarizedData($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $userlevel = $config['params']['dataparams']['dtuserlevel'];
    $userid = $config['params']['dataparams']['userid'];
    $username = $config['params']['dataparams']['username'];
    $dtstatus = $config['params']['dataparams']['dtstatus'];
    $radiodtfilter = $config['params']['dataparams']['radiodtfilter'];
    $filter = "";
    if ($userlevel != "") {
      $filter .= " and u.username = '" . $userlevel . "' ";
    }
    if ($username != "") {
      $filter .= " and ua.username = '" . $username . "' ";
    }
    if ($dtstatus != "") {
      $filter .= " and sl.status = '" . $dtstatus . "' ";
    }
    switch ($radiodtfilter) {
      case 0:
        $query = "select count(head.trno) as cnt,sl.status,u.username as usertype, ua.username
        from dt_dthead as head
          left join dt_status as s on head.currentstatusid=s.id
          left join dt_statuslist as sl on sl.id=s.statusdoc
          left join users as u on u.idno=head.currentusertypeid
          left join useraccess as ua on ua.userid=head.currentuserid
        where date(head.currentdate) between '" . $start . "' and '" . $end . "' " . $filter . "
      group by sl.status, u.username, ua.username";
        break;
      case 1:
        $query = "select count(head.trno) as cnt,sl.status,u.username as usertype, ua.username
        from dt_dthead as head
          left join dt_dtstock as stock on stock.trno=head.trno
          left join dt_status as s on stock.docstatusid=s.id
          left join dt_statuslist as sl on sl.id=s.statusdoc
          left join users as u on u.idno=stock.usertypeid
          left join useraccess as ua on ua.userid=stock.userid
        where date(stock.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
        group by sl.status, u.username, ua.username";
        break;
    }
    return $this->coreFunctions->opentable($query);
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $userlevel  = $config['params']['dataparams']['dtuserlevel'];
    $username   = $config['params']['dataparams']['username'];
    $dtstatus   = $config['params']['dataparams']['dtstatus'];
    $radiodtfilter = $config['params']['dataparams']['radiodtfilter'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $str = '';
    $layoutsize = '1000';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";
    $total = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_detailed_DEFAULT($config, $result);
    $docno = "";
    $i = 0;
    if (!empty($result)) {
      $userlevels = [];
      foreach ($result as $r) array_push($userlevels, $r->usertype);
      if (!empty($userlevels)) $userlevels = array_unique($userlevels);
      foreach ($userlevels as $ul) {
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($ul, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->col('', '800', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
        $str .= $this->reporter->endrow();
        foreach ($result as $key => $data) {
          if ($data->usertype == $ul) {
            $str .= $this->reporter->begintable($layoutsize);
            $str .= $this->reporter->startrow();
            $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $data->username, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->invdate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->invoiceno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->amt, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
            $str .= $this->reporter->endrow();
            $str .= $this->reporter->endtable();
            $i++;
          }
        }
        $str .= $this->reporter->endtable();
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $userlevel  = $config['params']['dataparams']['dtuserlevel'];
    $username   = $config['params']['dataparams']['username'];
    $dtstatus   = $config['params']['dataparams']['dtstatus'];
    $radiodtfilter = $config['params']['dataparams']['radiodtfilter'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    $status = $this->getcolval('dt_statuslist', 'distinct status', '', '');

    $count = 61;
    $page = 60;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config, $result);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('USERNAME', '100', null, false, $border, 'B', 'C', $font, '11', 'B', '', '', '');
    if (!empty($status)) {
      foreach ($status as $st) {
        $str .= $this->reporter->col($st->status, '100', null, false, $border, 'B', 'C', $font, '11', 'B', '', '', '');
      }
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $result2 = $this->loadSummarizedData($config);
    $totals = [];
    $subtotals = [];
    foreach ($status as $st) {
      $totals[$st->status] = '';
      $subtotals[$st->status] = '';
    }
    $str .= $this->reporter->begintable($layoutsize);
    // userlevel
    $userlevels = [];
    foreach ($result as $r) array_push($userlevels, $r->usertype);
    if (!empty($userlevels)) $userlevels = array_unique($userlevels);
    // username
    $users = [];
    foreach ($userlevels as $ul) {
      $users[$ul] = [];
      foreach ($result as $r) {
        if ($r->usertype == $ul) {
          array_push($users[$ul], $r->username);
          $users[$ul] = array_unique($users[$ul]);
        }
      }
    }
    $userss = [];
    foreach ($users as $usk => $us) {
      foreach ($us as $user) {
        foreach ($status as $st) {
          $userss[$usk][$user][$st->status] = '';
          foreach ($result2 as $key => $data) {
            if ($data->status == $st->status && $data->usertype == $usk && $data->username == $user) {
              $userss[$usk][$user][$st->status] = $data->cnt;
            }
          }
        }
      }
    }

    foreach ($userss as $usk => $us) { // usertype
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($usk, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
      $str .= $this->reporter->col('', '800', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
      $str .= $this->reporter->endrow();
      foreach ($us as $userk => $user) { // username
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $userk, '100', null, false, $border, '', 'L', $font, $fontsize, '', '', '', '');
        foreach ($user as $ustk => $ust) {
          $str .= $this->reporter->col($ust, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '', '');
          $subtotals[$ustk] += $ust;
          $totals[$ustk] += $ust;
        }
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
      }
      $str .= $this->reporter->printline();
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Sub Total', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
      foreach ($status as $st) {
        if (isset($subtotals[$st->status])) {
          if ($subtotals[$st->status] == 0) $subtotals[$st->status] = '';
        } else {
          $subtotals[$st->status] = '';
        }
        $str .= $this->reporter->col($subtotals[$st->status], '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
      }
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
      foreach ($status as $st) {
        $subtotals[$st->status] = '';
      }
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->printline();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    foreach ($status as $st) {
      if (isset($totals[$st->status])) {
        if ($totals[$st->status] == 0) $totals[$st->status] = '';
      } else {
        $totals[$st->status] = '';
      }
      $str .= $this->reporter->col($totals[$st->status], '100', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $userlevel  = $config['params']['dataparams']['dtuserlevel'];
    $username   = $config['params']['dataparams']['username'];
    $dtstatus   = $config['params']['dataparams']['dtstatus'];
    $radiodtfilter = $config['params']['dataparams']['radiodtfilter'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }
    if ($radiodtfilter == 0) {
      $radiodtfilter = "By Current Status";
    } else {
      $radiodtfilter = "By Tagged Status";
    }

    $str = '';
    $layoutsize = $this->reportParams['layoutSize'];
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($username != "") {
      $user = $username;
    } else {
      $user = "ALL USERS";
    }
    if ($userlevel == "") {
      $userlevel = "ALL USER LEVELS";
    }
    if ($dtstatus == "") {
      $dtstatus = "ALL STATUS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Document Tracking Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '500', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User Level: ' . $userlevel, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Username: ' . $user, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Status: ' . $dtstatus, '250', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '');
    $str .= $this->reporter->col('Date Filter: ' . $radiodtfilter, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    return $str;
  }

  public function header_detailed_DEFAULT($config, $result)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $userlevel  = $config['params']['dataparams']['dtuserlevel'];
    $username   = $config['params']['dataparams']['username'];
    $dtstatus   = $config['params']['dataparams']['dtstatus'];
    $radiodtfilter = $config['params']['dataparams']['radiodtfilter'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    if ($reporttype == 0) {
      $reporttype = 'Summarized';
    } else {
      $reporttype = 'Detailed';
    }
    if ($radiodtfilter == 0) {
      $radiodtfilter = "By Current Status";
    } else {
      $radiodtfilter = "By Tagged Status";
    }

    $str = '';
    $layoutsize = 1000;
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->header_DEFAULT($config, $result);
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('USERNAME', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DOCNO', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('DATEID', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('TERMS', '100', null, false, $border, 'B', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('INVDATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('INVOICE NO.', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function tableheader($layoutsize)
  {
    $str = '';
    $font =  "Century Gothic";
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SUPPLIER', '300', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOCUMENT #', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CREATE BY', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('STATUS', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  // Function to get Field Values
  public function getcolval($mqry, $field, $filter, $orderby)
  {
    return $this->coreFunctions->opentable("select $field from $mqry as x where 1=1 $filter $orderby");
  }
}//end class