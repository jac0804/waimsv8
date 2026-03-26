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

class counter_receipt
{
  public $modulename = 'Counter Receipt Report';
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
    $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'approved', 'dagentname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'approved.label', 'Prefix');
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['radioreporttype'];
    $col2 = $this->fieldClass->create($fields);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    return $this->coreFunctions->opentable("select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as userid,
    '' as username,
    '' as approved,
    '0' as reporttype,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
    '' as reportusers,
    '' as agent, '' as agentname,'0' as agentid,
    '' as dagentname
    ");
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
     $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }

    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $reporttype = $config['params']['dataparams']['reporttype'];
    switch ($reporttype) {
      case '0': // SUMMARIZED
        $query = $this->default_QUERY_SUMMARIZED($config);
        break;
      case '1': // DETAILED
        $query = $this->default_QUERY_DETAILED($config);
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];

    $agent     = $config['params']['dataparams']['agent'];
    $agentid     = $config['params']['dataparams']['agentid'];

    $filter = "";
    $leftj = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    if ($agent != "") {
      $leftj .= " left join client as agent on agent.client=head.agent ";
      $filter .= " and agent.clientid = '$agentid' ";
    }

    $query = "select head.docno,head.client as hclient,head.clientname as hclientname,
    date(head.dateid) as hdateid,date_format(ar.dateid,'%Y-%m-%d') as ddateid,coa.acno,coa.acnoname,concat(left(dclient.client,2),
    right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.checkno,
    detail.db,detail.cr,detail.rem,detail.ref from krhead as head
    left join arledger as ar on ar.kr=head.trno
    left join gldetail as detail on detail.trno=ar.trno and detail.line=ar.line
    left join client as dclient on dclient.clientid=ar.clientid
    left join coa on coa.acnoid=ar.acnoid
    left join transnum on transnum.trno=head.trno  $leftj 
    where head.doc='kr' and head.dateid between '$start' and '$end' $filter 
    union all
    select head.docno,head.client as hclient,head.clientname as hclientname,
    date(head.dateid) as hdateid,date_format(ar.dateid,'%Y-%m-%d') as ddateid,coa.acno,coa.acnoname,concat(left(dclient.client,2),
    right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.checkno,detail.db,
    detail.cr,detail.rem,detail.ref from hkrhead as head
    left join arledger as ar on ar.kr=head.trno
    left join gldetail as detail on detail.trno=ar.trno and detail.line=ar.line
    left join client as dclient on dclient.clientid=ar.clientid
    left join coa on coa.acnoid=ar.acnoid
    left join transnum on transnum.trno=head.trno  $leftj 
    where head.doc='kr' and head.dateid between '$start' and '$end' $filter 
    order by docno,cr";

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $fcenter    = $config['params']['dataparams']['center'];

    $agent     = $config['params']['dataparams']['agent'];
    $agentid     = $config['params']['dataparams']['agentid'];

    $filter = "";
    $leftj = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }
    if ($agent != "") {
      $leftj .= " left join client as agent on agent.client=head.agent ";
      $filter .= " and agent.clientid = '$agentid' ";
    }

    $query = "select docno, date(hdateid) as dateid, GROUP_CONCAT(IF(checkno='', NULL, checkno)) as checkno, sum(db) as debit, sum(cr) as credit, rem, hclient
  from(
    select head.docno,head.client as hclient,head.clientname as hclientname,
    head.dateid as hdateid,date_format(ar.dateid,'%Y-%m-%d') as ddateid,coa.acno,coa.acnoname,concat(left(dclient.client,2),
    right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.checkno,
    detail.db,detail.cr,head.rem,detail.ref from krhead as head
    left join arledger as ar on ar.kr=head.trno
    left join gldetail as detail on detail.trno=ar.trno and detail.line=ar.line
    left join client as dclient on dclient.clientid=ar.clientid
    left join coa on coa.acnoid=ar.acnoid
    left join transnum on transnum.trno=head.trno $leftj
    where head.doc='kr' and head.dateid between '$start' and '$end' $filter 
    union all
    select head.docno,head.client as hclient,head.clientname as hclientname,
    head.dateid as hdateid,date_format(ar.dateid,'%Y-%m-%d') as ddateid,coa.acno,coa.acnoname,concat(left(dclient.client,2),
    right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.checkno,detail.db,
    detail.cr,head.rem,detail.ref from hkrhead as head
    left join arledger as ar on ar.kr=head.trno
    left join gldetail as detail on detail.trno=ar.trno and detail.line=ar.line
    left join client as dclient on dclient.clientid=ar.clientid
    left join coa on coa.acnoid=ar.acnoid
    left join transnum on transnum.trno=head.trno $leftj
    where head.doc='kr' and head.dateid between '$start' and '$end' $filter 
    order by hdateid,docno) as t 
    group by docno, hdateid, rem, hclient order by docno";
    return $query;
  }

  public function default_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Counter Receipt Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header_detailed($config);

    $str .= $this->reporter->printline();
    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        if ($docno != "" && $docno != $data->docno) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B',  '', '', '');
          $str .= $this->reporter->col('Total:', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B',  '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B',  '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '200', null, false, $border, '', '', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->hdateid, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Customer: ' . '</b>' . $data->hclientname, '100', null, false, $border, '', '', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Account', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Title', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Customer/Supplier', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('Reference', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $str .= $this->reporter->begintable('1000');
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->ddateid, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->checkno, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acno, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dclient, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2) . '&nbsp&nbsp', '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2) . '&nbsp&nbsp', '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $debit += $data->db;
          $credit += $data->cr;
          $totaldb += $data->db;
          $totalcr += $data->cr;
        }
        $str .= $this->reporter->endtable();
        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->begintable('1000');
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->default_header_detailed($config);
          $page = $page + $count;
        } //end if
      }
    }
    $str .= $this->reporter->begintable('1000');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
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
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
    $str .= $this->summarized_header_table($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totaldb += $data->debit;
        $totalcr += $data->credit;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $checkno = str_replace(',', '<br>', $data->checkno);
        $str .= $this->reporter->col($checkno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->debit, 2) . '&nbsp&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2) . '&nbsp&nbsp', '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
          $str .= $this->summarized_header_table($config, $layoutsize);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '100', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_header_table($config, $layoutsize)
  {
    $str = "";
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check#', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function summarized_header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Counter Receipt Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class