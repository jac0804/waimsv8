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

class petty_cash_request_report
{
  public $modulename = 'Petty Cash Request Report';
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
      $fields = ['radioprint', 'start', 'end', 'dacnoname', 'dcentername', 'reportusers', 'prefix'];
      $col1 = $this->fieldClass->create($fields);
      data_set($col1, 'dacnoname.label', 'Petty Cash Account');
      data_set($col1, 'dacnoname.lookupclass', 'PC');
    } else {
      $fields = ['radioprint', 'start', 'end', 'dcentername', 'reportusers', 'prefix'];
      switch ($companyid) {
        case 10: //afti
        case 12: //afti usd
          array_push($fields, 'project');
          $col1 = $this->fieldClass->create($fields);
          data_set($col1, 'project.required', false);
          data_set($col1, 'project.label', 'Item Group');
          break;
        default:
          $col1 = $this->fieldClass->create($fields);
          break;
      }
    }
    data_set($col1, 'prefix.readonly', false);
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

    $paramstr = "select 
          'default' as print,
          adddate(left(now(),10),-360) as start,
          left(now(),10) as end,
          '' as userid,
          '' as username,
          '' as prefix,
          '0' as reporttype,
          '" . $defaultcenter[0]['center'] . "' as center,
          '" . $defaultcenter[0]['centername'] . "' as centername,
          '" . $defaultcenter[0]['dcentername'] . "' as dcentername,
          '' as reportusers,
          '' as contra,
          '' as dacnoname, 
          '0' as acnoid,
          '' as project, '' as projectid, '' as projectname
          ";
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
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['prefix'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($companyid == 3) { //conti
      $acnoname = $config['params']['dataparams']['dacnoname'];
      $acnoid = $config['params']['dataparams']['acnoid'];
      if ($acnoname != "") {
        $filter .= " and coa.acnoid = '$acnoid'";
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $project = $config['params']['dataparams']['projectid'];
      if ($prjid != "") {
        $filter1 .= " and detail.projectid = $project";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.amt as amt,detail.rem,detail.ref 
      from pqhead as head
      left join pqdetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join coa as hcoa on hcoa.acno=head.contra
      left join transnum on transnum.trno=head.trno
      where head.doc='PQ' and head.dateid between '$start' and '$end' $filter $filter1
      union all
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,date(head.dateid) as dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
      concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.amt as amt,detail.rem,detail.ref 
      from hpqhead as head
      left join hpqdetail as detail on detail.trno=head.trno 
      left join client as hclient on hclient.client=head.client
      left join client as dclient on dclient.client=detail.client
      left join coa on coa.acnoid=detail.acnoid
      left join coa as hcoa on hcoa.acno=head.contra
      left join transnum on transnum.trno=head.trno
      where head.doc='PQ' and head.dateid between '$start' and '$end' $filter $filter1
      order by dateid,docno";

    return $query;
  }

  public function default_QUERY_SUMMARIZED($config)
  {
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $filterusername  = $config['params']['dataparams']['username'];
    $prefix     = $config['params']['dataparams']['prefix'];
    $fcenter    = $config['params']['dataparams']['center'];

    $filter = "";
    $filter1 = "";
    if ($prefix != "") {
      $filter .= " and transnum.bref = '$prefix' ";
    }
    if ($filterusername != "") {
      $filter .= " and head.createby = '$filterusername' ";
    }
    if ($fcenter != "") {
      $filter .= " and transnum.center = '$fcenter'";
    }

    if ($companyid == 3) { //conti
      $acnoname = $config['params']['dataparams']['dacnoname'];
      $acnoid = $config['params']['dataparams']['acnoid'];

      if ($acnoname != "") {
        $filter .= " and coa.acnoid = '$acnoid'";
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $prjid = $config['params']['dataparams']['project'];
      $project = $config['params']['dataparams']['projectid'];
      if ($prjid != "") {
        $filter1 .= " and detail.projectid = $project";
      }
    } else {
      $filter1 .= "";
    }

    $query = "select docno,hclient,hclientname, createby, date(dateid) as dateid, sum(amt) as amt, rem, hacnoname
    from(
      select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.amt as amt,head.rem,detail.ref ,hcoa.acnoname as hacnoname
        from pqhead as head
        left join pqdetail as detail on detail.trno=head.trno 
        left join client as hclient on hclient.client=head.client
        left join client as dclient on dclient.client=detail.client
        left join coa on coa.acnoid=detail.acnoid
        left join coa as hcoa on hcoa.acno=head.contra
        left join transnum on transnum.trno=head.trno
        where head.doc='PQ' and head.dateid between '$start' and '$end' $filter $filter1
        union all
        select head.createby,head.docno,hclient.client as hclient,hclient.clientname as hclientname,head.dateid,date_format(detail.postdate,'%Y-%m-%d') as postdate,coa.acno,coa.acnoname,
        concat(left(dclient.client,2),right(dclient.client,7)) as dclient,dclient.clientname as dclientname,detail.amt as amt,head.rem,detail.ref ,hcoa.acnoname as hacnoname
        from hpqhead as head
        left join hpqdetail as detail on detail.trno=head.trno 
        left join client as hclient on hclient.client=head.client
        left join client as dclient on dclient.client=detail.client
        left join coa on coa.acnoid=detail.acnoid
        left join coa as hcoa on hcoa.acno=head.contra
        left join transnum on transnum.trno=head.trno
        where head.doc='PQ' and head.dateid between '$start' and '$end' $filter $filter1
        order by dateid,docno) as t 
        group by t.docno,t.hclient,t.hclientname, t.createby, t.dateid, t.rem, t.hacnoname";
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
    $prefix     = $config['params']['dataparams']['prefix'];

    if ($companyid == 3) { //conti
      $acnoname     = $config['params']['dataparams']['dacnoname'];
      if ($acnoname == "") {
        $acnoname = "ALL";
      }
    }

    if ($companyid == 10 || $companyid == 12) { // afti, afti usd
      $proj   = $config['params']['dataparams']['project'];
      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

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
    $str .= $this->reporter->col('Petty Cash Request Report Detailed', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '550', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '150', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->col('Project : ' . $projname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      if ($companyid == 3) { //conti
        $str .= $this->reporter->col('Account: ' . $acnoname, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('User: ' . $user, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
  
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
    $amt = 0;
    $totalamt = 0;
    $debit = 0;
    $credit = 0;

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
          $str .= $this->reporter->col(number_format($amt, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
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
          $amt = 0;
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
          $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
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
        $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, '10px solid ', '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->rem, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->ref, '100', null, false, '10px solid ', '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->addline();

        if ($docno == $data->docno) {
          $amt += $data->amt;
          $totalamt += $data->amt;
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
          $str .= $this->reporter->col('Total:', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col(number_format($amt, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '', '');
          $str .= $this->reporter->col('', '50', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '', '');
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
        $i++;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Grand Total: ', '100', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col(number_format($totalamt, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', '50', null, false, '1px dotted', '', '', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', '200', null, false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $companyid = $config['params']['companyid'];
    $count = 41;
    $page = 40;
    $this->reporter->linecounter = 0;

    $str = '';
    $layoutsize = '800';
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
    $totalamt = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $totalamt += $data->amt;
        $str .= $this->reporter->addline();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        if ($companyid == 3) { //conti
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $data->hacnoname, '150', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->hclientname, '200', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $data->rem, '150', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        } else {
          $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col(number_format($data->amt, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'R', '', '');
          $str .= $this->reporter->col('&nbsp&nbsp&nbsp&nbsp' . $data->rem, '200', null, false, $border, '', 'L', $font, $fontsize, 'R', '', '');
        }

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
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '250', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '150', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col("<div style='height:10px;'></div>", '200', null, false, $border, 'T', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->col('Grand Total:', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '150', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, '', 'C', $font, $fontsize, 'R', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

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
    $prefix     = $config['params']['dataparams']['prefix'];
    if ($companyid == 3) { //conti
      $acnoname     = $config['params']['dataparams']['dacnoname'];
      if ($acnoname == "") {
        $acnoname = "ALL";
      }
    }

    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $proj   = $config['params']['dataparams']['project'];

      if ($proj != "") {
        $projname = $config['params']['dataparams']['projectname'];
      } else {
        $projname = "ALL";
      }
    }

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


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Petty Cash Request Report Summarized', '800', null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    if ($companyid == 10 || $companyid == 12) { //afti, afti usd
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '400', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('User : ' . $user, '140', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix : ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->col('Project : ' . $projname, '160', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->endrow();
    } else {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      if ($companyid == 3) { //conti
        $str .= $this->reporter->col('Account: ' . $acnoname, '300', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      }
      $str .= $this->reporter->col('User: ' . $user, '100', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Prefix: ' . $prefix, '100', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
      $str .= $this->reporter->endrow();
    }

    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    if ($companyid == 3) { //conti
      $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('PC Acct.', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Name', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Notes', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    } else {
      $str .= $this->reporter->col('Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Docno', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Amount', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col('Notes', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class