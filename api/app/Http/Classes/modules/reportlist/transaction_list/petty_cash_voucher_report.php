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

class petty_cash_voucher_report
{
  public $modulename = 'Petty Cash Voucher Report';
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
    $companyid = $config['params']['companyid'];

    if ($companyid == 3) { //conti
      $fields = ['radioprint', 'start', 'end', 'dacnoname', 'dcentername', 'dclientname', 'costcenter'];
      $col1 = $this->fieldClass->create($fields);

      data_set($col1, 'dacnoname.label', 'Petty Cash Account');
      data_set($col1, 'dacnoname.lookupclass', 'PC');
      data_set($col1, 'dcentername.label', 'Center');
      data_set($col1, 'dcentername.required', true);
      data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
      data_set($col1, 'dclientname.label', 'Employee');
      data_set($col1, 'costcenter.label', 'Cost Center');
      data_set($col1, 'start.required', true);
      data_set($col1, 'end.required', true);

      $fields = ['radioincludepcv', 'radioreporttypepcv'];
    } else {
      $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'approved'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'approved.label', 'Prefix');
      data_set($col1, 'dcentername.required', true);
      data_set($col1, 'start.required', true);
      data_set($col1, 'end.required', true);
      $fields = ['radioreporttype'];
    }
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
    '' as contra,
    '' as dacnoname,
    '' as reportusers,
    '0' as clientid,
    '' as client,
    '' as dclientname,
    '' as code,
    '' as name,
    '' as costcenter,'0' as costcenterid,
    '0' as reporttypepcv,
    '0' as reportincpcv
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
    $companyid = $config['params']['companyid'];

    if ($companyid == 3) { //conti
      $reporttype = $config['params']['dataparams']['reporttypepcv'];
      switch ($reporttype) {
        case '0': // LISTING
          $result = $this->reportCONTILayout_LISTING($config);
          break;
        case '1': // DETAILED
          $result = $this->reportCONTILayout_DETAILED($config);
          break;
        case '2': // SUMMARIZED
          $result = $this->reportCONTILayout_SUMMARIZED($config);
          break;
      }
    } else {
      $center = $config['params']['center'];
      $username = $config['params']['user'];

      $reporttype = $config['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case '0': // SUMMARIZED
          $result = $this->reportDefaultLayout_SUMMARIZED($config);
          break;
        case '1': // DETAILED
          $result = $this->reportDefaultLayout_DETAILED($config);
          break;
      }
    }
    return $result;
  }

  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];

    if ($companyid == 3) { //conti
      $reporttype = $config['params']['dataparams']['reporttypepcv'];
      switch ($reporttype) {
        case '0': // LISTING
          $query = $this->CONTI_QUERY_LISTING($config);
          break;
        case '1': // DETAILED
          $query = $this->CONTI_QUERY_DETAILED($config);
          break;
        case '2': // SUMMARIZED
          $query = $this->CONTI_QUERY_SUMMARIZED($config);
          break;
      }
    } else {
      $reporttype = $config['params']['dataparams']['reporttype'];
      switch ($reporttype) {
        case '0': // SUMMARIZED
          $query = $this->default_QUERY_SUMMARIZED($config);
          break;
        case '1': // DETAILED
          $query = $this->default_QUERY_DETAILED($config);
          break;
      }
    }

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY_DETAILED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref 
      from svhead as head
      left join svdetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join transnum on transnum.trno=head.trno
      where head.doc='SV' and date(head.dateid) between '$start' and '$end' $filter 
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,detail.rem,detail.ref 
      from hsvhead as head
      left join hsvdetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join transnum on transnum.trno=head.trno
      where head.doc='SV' and date(head.dateid) between '$start' and '$end' $filter 
      order by docno,cr";
    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];
    $reporttype = $config['params']['dataparams']['reporttype'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    $query = "select docno, createby, date(dateid) as dateid, sum(db) as debit, sum(cr) as credit, rem
   from(
    select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,head.rem,detail.ref 
      from svhead as head
      left join svdetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join transnum on transnum.trno=head.trno
      where head.doc='SV' and date(head.dateid) between '$start' and '$end' $filter 
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.db,detail.cr,head.rem,detail.ref 
      from hsvhead as head
      left join hsvdetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join transnum on transnum.trno=head.trno
      where head.doc='SV' and date(head.dateid) between '$start' and '$end' $filter 
      order by dateid,docno) as t 
      group by t.docno, t.createby, t.dateid, t.rem";

    return $query;
  }

  public function default_header_detailed($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

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
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Petty Cash Voucher Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', 'false', '8px');
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
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total:', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }

        if ($docno == "" || $docno != $data->docno) {
          $docno = $data->docno;
          $debit = 0;
          $credit = 0;
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Docno#: ' . '</b>' . $data->docno, '200', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('<b>' . 'Date: ' . '</b>' . $data->dateid, '100', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<b>' . 'Supplier: ' . '</b>' . $data->hclientname, '100', null, false, $border, '', '', $font, $fontsize, '', '', '2px');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

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

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->postdate, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->acno, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->acnoname, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dclient, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cr, 2), '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
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
        if ($this->reporter->linecounter == $page) {

          $str .= $this->reporter->page_break();
          $str .= $this->default_header_detailed($config);

          $page = $page + $count;
        } //end if

        if ($i == (count((array)$result) - 1)) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($debit, 2), '115', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($credit, 2), '120', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '135', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
        }
        $i++;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '130', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
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

        $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col(number_format($data->credit, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
        $str .= $this->reporter->endrow($layoutsize);

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->summarized_header_DEFAULT($config, $layoutsize);
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
    $str .= $this->reporter->col(number_format($totaldb, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_header_DEFAULT($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['approved'];

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    if ($filterusername != "") {
      $user = $filterusername;
    } else {
      $user = "ALL USERS";
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Petty Cash Report Summarized', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Prefix: ' . $prefix, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Debit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Credit', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Notes', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  //CONTI
  public function CONTI_QUERY_LISTING($config)
  {
     $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $fcenter    = $config['params']['dataparams']['center'];
    $include = $config['params']['dataparams']['reportincpcv'];
    $clientid = $config['params']['dataparams']['clientid'];
    $clientcode = $config['params']['dataparams']['client'];
    $costcenter = $config['params']['dataparams']['costcenter'];
    $acnoname = $config['params']['dataparams']['dacnoname'];
    $acnoid = $config['params']['dataparams']['acnoid'];

    $filter = "";
    $left = "";

    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($clientcode != "") {
      $left .= " left join client as hclient on hclient.client=head.client ";
      $filter .= " and hclient.clientid = '$clientid'";
    }

    if ($costcenter != "") {
      $cc = $config['params']['dataparams']['costcenterid'];
      $filter .= " and head.projectid = '$cc'";
    }
    if ($acnoname != "") {
      $filter .= " and coa.acnoid = '$acnoid'";
    }
    switch ($include) {
      case '0':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
            head.tax, head.vattype,sum(head.amt) as debit,'' as credit,coa.acnoid,coa.acnoname
          from svhead as head
          left join projectmasterfile as proj on proj.line = head.projectid
           $left
          left join coa on coa.acno=head.contra
          left join transnum on transnum.trno=head.trno
          where head.doc='SV' and head.dateid between '$start' and '$end' $filter 
          group by head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name,
          head.tax, head.vattype,coa.acnoid,coa.acnoname
          order by dateid,docno";

        break;
      case '1':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                  head.tax, head.vattype,sum(detail.db) as debit,sum(detail.cr) as credit,head.cvtrno
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = head.projectid
                 $left
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.cvtrno = 0 and head.dateid between '$start' and '$end' $filter 
                group by head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name,
                head.tax, head.vattype,head.cvtrno
                order by dateid,docno";
        break;
      case '2':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                  head.tax, head.vattype,sum(detail.db) as debit,sum(detail.cr) as credit,head.cvtrno
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = head.projectid
                 $left
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.cvtrno <> 0 and head.dateid between '$start' and '$end' $filter 
                group by head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name,
                head.tax, head.vattype,head.cvtrno
                order by dateid,docno";
        break;
      case '3':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                    head.tax, head.vattype,round(sum(head.amt),2) as released, '' as posted,'' as tagged
                  from svhead as head
                  left join projectmasterfile as proj on proj.line = head.projectid
                   $left
                  left join coa on coa.acno=head.contra
                  left join transnum on transnum.trno=head.trno
                  where head.doc='SV' and head.dateid between '$start' and '$end' $filter 
                  group by head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name,
                  head.tax, head.vattype
                  union all
                  select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                    head.tax, head.vattype,'' as released, round(sum(detail.db),2) as posted,'' as tagged
                  from hsvhead as head
                  left join hsvdetail as detail on detail.trno=head.trno
                  left join projectmasterfile as proj on proj.line = head.projectid
                   $left
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acno=head.contra
                  left join transnum on transnum.trno=head.trno
                  where head.doc='SV' and head.cvtrno = 0 and head.dateid between '$start' and '$end' $filter 
                  group by head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name,
                  head.tax, head.vattype
                  union all
                  select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                    head.tax, head.vattype,'' as released, '' as posted, round(sum(detail.db),2) as tagged
                  from hsvhead as head
                  left join hsvdetail as detail on detail.trno=head.trno
                  left join projectmasterfile as proj on proj.line = head.projectid
                   $left
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acno=head.contra
                  left join transnum on transnum.trno=head.trno
                  where head.doc='SV' and head.cvtrno <> 0 and head.dateid between '$start' and '$end' $filter 
                  group by head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name,
                  head.tax, head.vattype
                  order by dateid,docno";


        break;
    }
    return $query;
  }


  public function CONTI_QUERY_DETAILED($config)
  {
    $center     = $config['params']['center'];


    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['reporttypepcv'];
    $fcenter    = $config['params']['dataparams']['center'];
    $include = $config['params']['dataparams']['reportincpcv'];
    $client = $config['params']['dataparams']['dclientname'];
    $costcenter = $config['params']['dataparams']['costcenter'];
    $acnoname = $config['params']['dataparams']['dacnoname'];

    $filter = "";

    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($client != "") {
      $code = $config['params']['dataparams']['client'];
      $filter .= " and head.client = '$code'";
    }

    if ($costcenter != "") {
      $cc = $config['params']['dataparams']['costcenterid'];
      $filter .= " and detail.projectid = '$cc'";
    }

    if ($acnoname != "") {
      $contra = $config['params']['dataparams']['acnoname'];
      $filter .= " and coa.acnoname = '$contra'";
    }


    switch ($include) {
      case '0':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
              head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,coa.acnoid,coa.acnoname,
              dcoa.acnoname as dacnoname,detail.acnoid
            from svhead as head
            left join svdetail as detail on detail.trno=head.trno
            left join projectmasterfile as proj on proj.line = detail.projectid
            left join client as hclient on hclient.client=head.client
            left join client as dclient on dclient.client=detail.client
            left join coa on coa.acno=head.contra
            left join coa as dcoa on dcoa.acnoid=detail.acnoid
            left join transnum on transnum.trno=head.trno
            where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter 
            union all
            select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
              head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,coa.acnoid,coa.acnoname,
              dcoa.acnoname as dacnoname,detail.acnoid
            from svhead as head
            left join svdetail as detail on detail.trno=head.trno
            left join projectmasterfile as proj on proj.line = detail.projectid
            left join client as hclient on hclient.client=head.client
            left join client as dclient on dclient.client=detail.client
            left join coa on coa.acno=head.contra
            left join coa as dcoa on dcoa.acnoid=detail.acnoid
            left join transnum on transnum.trno=head.trno
            where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter 
            order by dateid,docno";
        break;
      case '1':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                  head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,coa.acnoid,coa.acnoname,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno = 0 and head.dateid between '$start' and '$end' $filter
                union all
                select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                  head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,coa.acnoid,coa.acnoname,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno = 0 and head.dateid between '$start' and '$end' $filter
                order by dateid,docno";
        break;
      case '2':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                  head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,coa.acnoid,coa.acnoname,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno <> 0 and head.dateid between '$start' and '$end' $filter
                union all
                select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                  head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,coa.acnoid,coa.acnoname,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno <> 0 and head.dateid between '$start' and '$end' $filter
                order by dateid,docno";
        break;
      case '3':
        $query = "select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,coa.acnoid,coa.acnoname,
                dcoa.acnoname as dacnoname,detail.acnoid
              from svhead as head
              left join svdetail as detail on detail.trno=head.trno
              left join projectmasterfile as proj on proj.line = detail.projectid
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acno=head.contra
              left join coa as dcoa on dcoa.acnoid=detail.acnoid
              left join transnum on transnum.trno=head.trno
              where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,coa.acnoid,coa.acnoname,
                dcoa.acnoname as dacnoname,detail.acnoid
              from svhead as head
              left join svdetail as detail on detail.trno=head.trno
              left join projectmasterfile as proj on proj.line = detail.projectid
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acno=head.contra
              left join coa as dcoa on dcoa.acnoid=detail.acnoid
              left join transnum on transnum.trno=head.trno
              where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter 
              union all
              select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,coa.acnoid,coa.acnoname,
                dcoa.acnoname as dacnoname,detail.acnoid
              from hsvhead as head
              left join hsvdetail as detail on detail.trno=head.trno
              left join projectmasterfile as proj on proj.line = detail.projectid
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acno=head.contra
              left join coa as dcoa on dcoa.acnoid=detail.acnoid
              left join transnum on transnum.trno=head.trno
              where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter
              union all
              select head.dateid,head.docno,head.ourref,head.yourref, head.clientname,head.rem,proj.name as costcenter,
                head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,coa.acnoid,coa.acnoname,
                dcoa.acnoname as dacnoname,detail.acnoid
              from hsvhead as head
              left join hsvdetail as detail on detail.trno=head.trno
              left join projectmasterfile as proj on proj.line = detail.projectid
              left join client as hclient on hclient.client=head.client
              left join client as dclient on dclient.client=detail.client
              left join coa on coa.acno=head.contra
              left join coa as dcoa on dcoa.acnoid=detail.acnoid
              left join transnum on transnum.trno=head.trno
              where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter
              order by dateid,docno";

        break;
    }
    return $query;
  }

  public function CONTI_QUERY_SUMMARIZED($config)
  {
    $center     = $config['params']['center'];


    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $reporttype = $config['params']['dataparams']['reporttypepcv'];
    $fcenter    = $config['params']['dataparams']['center'];
    $include = $config['params']['dataparams']['reportincpcv'];
    $client = $config['params']['dataparams']['dclientname'];
    $costcenter = $config['params']['dataparams']['costcenter'];
    $acnoname = $config['params']['dataparams']['dacnoname'];

    $filter = "";

    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($client != "") {
      $code = $config['params']['dataparams']['client'];
      $filter .= " and head.client = '$code'";
    }

    if ($costcenter != "") {
      $cc = $config['params']['dataparams']['costcenterid'];
      $filter .= " and detail.projectid = '$cc'";
    }

    if ($acnoname != "") {
      $contra = $config['params']['dataparams']['acnoname'];
      $filter .= " and coa.acnoname = '$contra'";
    }


    switch ($include) {
      case '0':
        $query = "select costcenter,tax,vattype,sum(vatable) as vatable, sum(nonvat) as nonvat,dacnoname,acnoid from (
            select proj.name as costcenter,head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,
              dcoa.acnoname as dacnoname,detail.acnoid
            from svhead as head
            left join svdetail as detail on detail.trno=head.trno
            left join projectmasterfile as proj on proj.line = detail.projectid
            left join client as hclient on hclient.client=head.client
            left join client as dclient on dclient.client=detail.client
            left join coa on coa.acno=head.contra
            left join coa as dcoa on dcoa.acnoid=detail.acnoid
            left join transnum on transnum.trno=head.trno
            where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter 
            union all
            select proj.name as costcenter,head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,
              dcoa.acnoname as dacnoname,detail.acnoid
            from svhead as head
            left join svdetail as detail on detail.trno=head.trno
            left join projectmasterfile as proj on proj.line = detail.projectid
            left join client as hclient on hclient.client=head.client
            left join client as dclient on dclient.client=detail.client
            left join coa on coa.acno=head.contra
            left join coa as dcoa on dcoa.acnoid=detail.acnoid
            left join transnum on transnum.trno=head.trno
            where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter ) as a
            group by dacnoname,costcenter,tax,vattype,acnoid
            order by dacnoname,costcenter";
        break;
      case '1':
        $query = "select costcenter,tax,vattype,sum(vatable) as vatable, sum(nonvat) as nonvat,dacnoname,acnoid from (
                select proj.name as costcenter,head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno = 0 and head.dateid between '$start' and '$end' $filter
                union all
                select proj.name as costcenter,head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno = 0 and head.dateid between '$start' and '$end' $filter ) as a
                group by dacnoname,costcenter,tax,vattype,acnoid
                order by dacnoname,costcenter";
        break;
      case '2':
        $query = "select costcenter,tax,vattype,sum(vatable) as vatable, sum(nonvat) as nonvat,dacnoname,acnoid from(
                select proj.name as costcenter,head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno <> 0 and head.dateid between '$start' and '$end' $filter
                union all
                select proj.name as costcenter,head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,
                  dcoa.acnoname as dacnoname,detail.acnoid
                from hsvhead as head
                left join hsvdetail as detail on detail.trno=head.trno
                left join projectmasterfile as proj on proj.line = detail.projectid
                left join client as hclient on hclient.client=head.client
                left join client as dclient on dclient.client=detail.client
                left join coa on coa.acno=head.contra
                left join coa as dcoa on dcoa.acnoid=detail.acnoid
                left join transnum on transnum.trno=head.trno
                where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.cvtrno <> 0 and head.dateid between '$start' and '$end' $filter ) as a
                group by dacnoname,costcenter,tax,vattype,acnoid
                order by dacnoname,costcenter";
        break;
      case '3':
        $query = "select costcenter,tax,vattype,sum(vatable) as vatable, sum(nonvat) as nonvat,dacnoname,acnoid from(
                  select proj.name as costcenter,head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,
                        dcoa.acnoname as dacnoname,detail.acnoid
                  from svhead as head
                  left join svdetail as detail on detail.trno=head.trno
                  left join projectmasterfile as proj on proj.line = detail.projectid
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acno=head.contra
                  left join coa as dcoa on dcoa.acnoid=detail.acnoid
                  left join transnum on transnum.trno=head.trno
                  where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter
                  union all
                  select proj.name as costcenter,head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,
                        dcoa.acnoname as dacnoname,detail.acnoid
                  from svhead as head
                  left join svdetail as detail on detail.trno=head.trno
                  left join projectmasterfile as proj on proj.line = detail.projectid
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acno=head.contra
                  left join coa as dcoa on dcoa.acnoid=detail.acnoid
                  left join transnum on transnum.trno=head.trno
                  where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter
                  union all
                  select proj.name as costcenter,head.tax, head.vattype,round(detail.db,2) as vatable, '' as nonvat,
                        dcoa.acnoname as dacnoname,detail.acnoid
                  from hsvhead as head
                  left join hsvdetail as detail on detail.trno=head.trno
                  left join projectmasterfile as proj on proj.line = detail.projectid
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acno=head.contra
                  left join coa as dcoa on dcoa.acnoid=detail.acnoid
                  left join transnum on transnum.trno=head.trno
                  where head.doc='SV' and head.vattype = 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter
                  union all
                  select proj.name as costcenter,head.tax, head.vattype,'' as vatable, round(detail.db,2) as nonvat,
                        dcoa.acnoname as dacnoname,detail.acnoid
                  from hsvhead as head
                  left join hsvdetail as detail on detail.trno=head.trno
                  left join projectmasterfile as proj on proj.line = detail.projectid
                  left join client as hclient on hclient.client=head.client
                  left join client as dclient on dclient.client=detail.client
                  left join coa on coa.acno=head.contra
                  left join coa as dcoa on dcoa.acnoid=detail.acnoid
                  left join transnum on transnum.trno=head.trno
                  where head.doc='SV' and head.vattype <> 'VATABLE' and left(dcoa.alias,2) <> 'PC' and head.dateid between '$start' and '$end' $filter) as a
                  group by dacnoname, costcenter, tax,vattype,acnoid
                  order by dacnoname,costcenter";
        break;
    }
    return $query;
  }

  public function reportCONTILayout_LISTING($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize11 = "10";
    $fontsize13 = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->listing_header_CONTI($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totaldb = 0;
    $totalcr = 0;
    $totalreleased = 0;
    $totalposted = 0;
    $totaltagged = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $str .= $this->reporter->addline();

        // $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($config['params']['dataparams']['reportincpcv'] == '3') {
          $totalreleased += $data->released;
          $totalposted += $data->posted;
          $totaltagged += $data->tagged;

          $str .= $this->reporter->col($data->dateid, '70', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->ourref, '120', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->yourref, '90', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->clientname, '150', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->costcenter, '80', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          if ($data->vattype == 'VATABLE') {
            $str .= $this->reporter->col('/', '50', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          } else {
            $str .= $this->reporter->col('', '50', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          }
          $str .= $this->reporter->col($data->released . '&nbsp&nbsp', '80', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->posted . '&nbsp&nbsp', '80', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->tagged . '&nbsp&nbsp', '80', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
        } else {
          $totaldb += $data->debit;
          $totalcr += $data->credit;
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->ourref, '120', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->clientname, '200', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          $str .= $this->reporter->col($data->costcenter, '100', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
          if ($data->vattype == 'VATABLE') {
            $str .= $this->reporter->col('/', '80', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          } else {
            $str .= $this->reporter->col('', '80', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
          }
          $str .= $this->reporter->col(number_format($data->debit, 2), '100', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
        }

        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->listing_header_CONTI($config, $layoutsize);
          $page = $page + $count;
        } //end if

      }
    }

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
      $str .= $this->reporter->col('Total: ', '160', null, false, $border, '', 'R', $font, $fontsize13, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($totalreleased, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($totalposted, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($totaltagged, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '');
    } else {
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '');
      $str .= $this->reporter->col('Total: ', '100', null, false, $border, '', 'R', $font, $fontsize13, 'B', '', '', '');
      $str .= $this->reporter->col(number_format($totaldb, 2), '100', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '');
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function listing_header_CONTI($config, $layoutsize)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $acnoname = $config['params']['dataparams']['dacnoname'];
    $costcenter = $config['params']['dataparams']['costcenter'];
    $clientname = $config['params']['dataparams']['dclientname'];
    $centercode = $config['params']['dataparams']['dcentername'];


    $start      = date("F-d-y", strtotime($config['params']['dataparams']['start']));
    $end        = date("F-d-y", strtotime($config['params']['dataparams']['end']));


    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize11 = "10";
    $fontsize13 = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LIST OF PETTY CASH VOUCHERS', '700', null, false, $border, '', '', $font, '16', 'B', '', '');
    $str .= $this->reporter->col('Type: Listing', '300', null, false, $border, '', '', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . ' TO ' . $end, '700', null, false, $border, '', '', $font, $fontsize11, '', '', '');


    switch ($config['params']['dataparams']['reportincpcv']) {
      case '0':
        $str .= $this->reporter->col('Include: [/] Unposted', '300', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '1':
        $str .= $this->reporter->col('Include: [/] Posted/ Untagged', '300', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '2':
        $str .= $this->reporter->col('Include: [/] Posted/ Tagged', '300', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '3':
        $str .= $this->reporter->col('Include: [/] Unposted', '300', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center: ' . $centercode, '400', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    if ($acnoname == '') {
      $acnoname = 'ALL';
    } else {
      $acnoname = $config['params']['dataparams']['dacnoname'];
    }

    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('Account: ' . $acnoname, '363', null, false, $border, '', '', $font, $fontsize11, '', '', '');
      $str .= $this->reporter->col('[/] Posted/ Untagged', '237', null, false, $border, '', '', $font, '10', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Account: ' . $acnoname, '600', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($clientname == '') {
      $clientname = 'ALL';
    } else {
      $clientname = $config['params']['dataparams']['clientname'];
    }
    $str .= $this->reporter->col('Employee: ' . $clientname, '400', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    if ($costcenter == '') {
      $costcenter = 'ALL';
    }
    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('Cost Center: ' . $costcenter, '363', null, false, $border, '', '', $font, $fontsize11, '', '', '');
      $str .= $this->reporter->col('[/] Posted/ Tagged', '237', null, false, $border, '', '', $font, '10', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Cost Center: ' . $costcenter, '600', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= '<br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('Date', '70', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Doc No', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Our Ref', '120', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Yourref', '90', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Name', '150', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Cost Center', '80', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Vat', '50', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Doc No', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Our Ref', '120', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Name', '200', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Cost Center', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
      $str .= $this->reporter->col('Vat', '80', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    }



    switch ($config['params']['dataparams']['reportincpcv']) {
      case '0':
        $str .= $this->reporter->col('Released', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
        break;
      case '1':
        $str .= $this->reporter->col('Posted', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
        break;
      case '2':
        $str .= $this->reporter->col('Tagged', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
        break;
      case '3':
        $str .= $this->reporter->col('Released', '80', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
        $str .= $this->reporter->col('Posted', '80', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
        $str .= $this->reporter->col('Tagged', '80', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
        break;
    }
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportCONTILayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '1200';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize11 = "10";
    $fontsize13 = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->CONTI_header_detailed($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totalvat = 0;
    $totalnonvat = 0;
    $grandtotal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $totalvat += $data->vatable;
        $totalnonvat += $data->nonvat;
        $str .= $this->reporter->col($data->dateid, '70', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->ourref, '120', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->yourref, '100', null, false, $border, '', 'CT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->clientname, '170', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->acnoid, '90', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->dacnoname, '150', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->costcenter, '100', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->vatable . '&nbsp&nbsp', '100', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->nonvat . '&nbsp&nbsp', '100', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');

        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->CONTI_header_detailed($config, $layoutsize);
          $page = $page + $count;
        } //end if

      }
    }
    $grandtotal = $totalvat + $totalnonvat;
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, $border, '', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
    $str .= $this->reporter->col('', '160', null, false, $border, '', 'R', $font, $fontsize13, 'B', '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalvat, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalnonvat, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function CONTI_header_detailed($config, $layoutsize)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $acnoname = $config['params']['dataparams']['dacnoname'];
    $costcenter = $config['params']['dataparams']['costcenter'];
    $clientname = $config['params']['dataparams']['dclientname'];
    $centercode = $config['params']['dataparams']['dcentername'];


    $start      = date("F-d-y", strtotime($config['params']['dataparams']['start']));
    $end        = date("F-d-y", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize11 = "10";
    $fontsize13 = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LIST OF PETTY CASH VOUCHERS', '800', null, false, $border, '', '', $font, '16', 'B', '', '');
    $str .= $this->reporter->col('Type: Detailed', '400', null, false, $border, '', '', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . ' TO ' . $end, '800', null, false, $border, '', '', $font, $fontsize11, '', '', '');


    switch ($config['params']['dataparams']['reportincpcv']) {
      case '0':
        $str .= $this->reporter->col('Include: [/] Unposted', '400', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '1':
        $str .= $this->reporter->col('Include: [/] Posted/ Untagged', '400', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '2':
        $str .= $this->reporter->col('Include: [/] Posted/ Tagged', '400', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '3':
        $str .= $this->reporter->col('Include: [/] Unposted', '400', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center: ' . $centercode, '450', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    if ($acnoname == '') {
      $acnoname = 'ALL';
    } else {
      $acnoname = $config['params']['dataparams']['dacnoname'];
    }

    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('Account: ' . $acnoname, '414', null, false, $border, '', '', $font, $fontsize11, '', '', '');
      $str .= $this->reporter->col('[/] Posted/ Untagged', '336', null, false, $border, '', '', $font, '10', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Account: ' . $acnoname, '750', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($clientname == '') {
      $clientname = 'ALL';
    } else {
      $clientname = $config['params']['dataparams']['clientname'];
    }
    $str .= $this->reporter->col('Employee: ' . $clientname, '450', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    if ($costcenter == '') {
      $costcenter = 'ALL';
    }
    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('Cost Center: ' . $costcenter, '414', null, false, $border, '', '', $font, $fontsize11, '', '', '');
      $str .= $this->reporter->col('[/] Posted/ Tagged', '336', null, false, $border, '', '', $font, '10', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Cost Center: ' . $costcenter, '750', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= '<br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date', '70', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Doc No', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Our Ref', '120', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Yourref', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Name', '170', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Notes', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Acct No', '90', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Acct Name', '150', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Cost Center', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Vatable', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Non Vat', '100', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportCONTILayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 40;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize11 = "10";
    $fontsize13 = "10";
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->summarized_header_CONTI($config, $layoutsize);

    $i = 0;
    $docno = "";
    $supplier = "";
    $debit = 0;
    $credit = 0;
    $totalvat = 0;
    $totalnonvat = 0;
    $grandtotal = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();

        $totalvat += $data->vatable;
        $totalnonvat += $data->nonvat;

        $str .= $this->reporter->col($data->acnoid, '150', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->dacnoname, '200', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        $str .= $this->reporter->col($data->costcenter, '150', null, false, $border, '', 'LT', $font, $fontsize13, '', '', '');
        if ($data->vatable == 0) {
          $str .= $this->reporter->col('', '150', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->vatable, 2) . '&nbsp&nbsp', '150', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
        }
        if ($data->nonvat == 0) {
          $str .= $this->reporter->col('', '150', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
        } else {
          $str .= $this->reporter->col(number_format($data->nonvat, 2) . '&nbsp&nbsp', '150', null, false, $border, '', 'RT', $font, $fontsize13, '', '', '');
        }

        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->summarized_header_CONTI($config, $layoutsize);
          $page = $page + $count;
        } //end if

      }
    }
    $grandtotal = $totalvat + $totalnonvat;
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('Grand Total: ', '150', null, false, $border, '', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '200', null, false, $border, '', 'R', $font, $fontsize11, 'B', '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize11, 'R', '', '');
    $str .= $this->reporter->col(number_format($totalvat, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '', '');
    $str .= $this->reporter->col(number_format($totalnonvat, 2) . '&nbsp&nbsp', '150', null, false, $border, 'T', 'R', $font, $fontsize13, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function summarized_header_CONTI($config, $layoutsize)
  {
    $companyid = $config['params']['companyid'];
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $acnoname = $config['params']['dataparams']['dacnoname'];
    $costcenter = $config['params']['dataparams']['costcenter'];
    $clientname = $config['params']['dataparams']['dclientname'];
    $centercode = $config['params']['dataparams']['dcentername'];


    $start      = date("F-d-y", strtotime($config['params']['dataparams']['start']));
    $end        = date("F-d-y", strtotime($config['params']['dataparams']['end']));

    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize11 = "10";
    $fontsize13 = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LIST OF PETTY CASH VOUCHERS', '550', null, false, $border, '', '', $font, '16', 'B', '', '');
    $str .= $this->reporter->col('Type: Summarized', '250', null, false, $border, '', '', $font, '10', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($start . ' TO ' . $end, '550', null, false, $border, '', '', $font, $fontsize11, '', '', '');


    switch ($config['params']['dataparams']['reportincpcv']) {
      case '0':
        $str .= $this->reporter->col('Include: [/] Unposted', '250', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '1':
        $str .= $this->reporter->col('Include: [/] Posted/ Untagged', '250', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '2':
        $str .= $this->reporter->col('Include: [/] Posted/ Tagged', '250', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
      case '3':
        $str .= $this->reporter->col('Include: [/] Unposted', '250', null, false, $border, '', '', $font, '10', 'B', '', '');
        break;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center: ' . $centercode, '300', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    if ($acnoname == '') {
      $acnoname = 'ALL';
    } else {
      $acnoname = $config['params']['dataparams']['dacnoname'];
    }

    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('Account: ' . $acnoname, '314', null, false, $border, '', '', $font, $fontsize11, '', '', '');
      $str .= $this->reporter->col('[/] Posted/ Untagged', '186', null, false, $border, '', '', $font, '10', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Account: ' . $acnoname, '500', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($clientname == '') {
      $clientname = 'ALL';
    } else {
      $clientname = $config['params']['dataparams']['clientname'];
    }
    $str .= $this->reporter->col('Employee: ' . $clientname, '300', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    if ($costcenter == '') {
      $costcenter = 'ALL';
    }
    if ($config['params']['dataparams']['reportincpcv'] == '3') {
      $str .= $this->reporter->col('Cost Center: ' . $costcenter, '314', null, false, $border, '', '', $font, $fontsize11, '', '', '');
      $str .= $this->reporter->col('[/] Posted/ Tagged', '186', null, false, $border, '', '', $font, '10', 'B', '', '');
    } else {
      $str .= $this->reporter->col('Cost Center: ' . $costcenter, '500', null, false, $border, '', '', $font, $fontsize11, '', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();

    $str .= '<br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('Acct No', '150', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Acct Name', '200', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Cost Center', '150', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Vatable', '150', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');
    $str .= $this->reporter->col('Non Vat', '150', null, false, $border, 'B', 'C', $font, $fontsize13, 'B', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class