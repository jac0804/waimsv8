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

class loan_summary
{
  public $modulename = 'Loan Summary';
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
    $company = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);


    $fields = ['radioreporttype', 'radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    data_set(
      $col2,
      'radioreporttype.options',
      [
        ['label' => 'Summary', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Detailed', 'value' => '1', 'color' => 'teal']
      ]
    );


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,'' as clientname, '0' as clientid,
                        '' as userid,
                        '0' as reporttype, 
                        '0' as posttype,
                        '' as dclientname,
                        '" . $defaultcenter[0]['center'] . "' as center,
                        '" . $defaultcenter[0]['centername'] . "' as centername,
                        '" . $defaultcenter[0]['dcentername'] . "' as dcentername";
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
    $companyid = $config['params']['companyid'];
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0': //summary
        $result = $this->reportDefaultLayout_SUMMARIZED($config);
        break;
      case '1': //detailed
        $result = $this->reportDefaultLayout_DETAILED($config);
        break;
    }



    return $result;
  }

  public function reportDefault($config)
  {
    $reporttype = $config['params']['dataparams']['reporttype'];

    switch ($reporttype) {
      case '0': //summary
        $query = $this->default_qry_summary($config);
        break;
      case '1': //detailed
        $query = $this->default_qry_detailed($config);
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  public function default_qry_summary($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $reporttype = $config['params']['dataparams']['reporttype'];


    $filter = "";
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and num.center = '$fcenter'";
    }




    switch ($posttype) {
      case 0: // posted
        $query = "select loantype,terms,sum(amount) as amount,sum(bal) as bal from (
        select ifnull(r.reqtype,'') as loantype,
        head.terms,sum(info.amount) as amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal
         from glhead as cp
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client  
        where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 

        group by cp.trno,r.reqtype,head.terms,info.amount) as a
        group by loantype,terms
        order by loantype,terms ";
        break;

      case 1: // unposted
        $query = "select loantype,terms,sum(amount) as amount,sum(bal) as bal from (select ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal
        from lahead as cp
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client
         where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 

        group by cp.trno,r.reqtype,head.terms,info.amount) as a
        group by loantype,terms
        order by loantype,terms ";
        break;

      default: // all
        $query = "select loantype,terms,sum(amount) as amount,sum(bal) as bal from (select ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount as amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal
        from glhead as cp
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client  
        where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        group by cp.trno,
        r.reqtype,head.terms,info.amount

        union all

        select ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount as amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal
        from lahead as cp
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client
         where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 

        group by cp.trno,r.reqtype,head.terms,info.amount) as a

        group by loantype,terms
        order by loantype,terms ";
        break;
    } // end switch posttype

    return $query;
  }


  public function default_qry_detailed($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $posttype   = $config['params']['dataparams']['posttype'];

    $filter = "";
    if ($client != "") {
      $filter .= " and client.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and num.center = '$fcenter'";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "
        select 
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,date(cp.dateid) as cvdate,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal,
        tnum.seq as docno,num.seq as cvno
         from glhead as cp
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join transnum as tnum on tnum.trno = head.trno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client  
        where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 

        order by cvdate,docno ";
        break;

      case 1: // unposted
        $query = "select 
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,date(cp.dateid) as cvdate,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal,
        tnum.seq as docno
        from lahead as cp,num.seq as cvno
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join transnum as tnum on tnum.trno = head.trno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client
         where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 


        order by cvdate,docno ";
        break;

      default: // all
        $query = "select 
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,date(cp.dateid) as cvdate,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal,
        tnum.seq as docno,num.seq as cvno
         from glhead as cp
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join transnum as tnum on tnum.trno = head.trno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client  
        where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        union all
        select 
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,date(cp.dateid) as cvdate,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount,(select sum(ar.bal) from arledger as ar left join coa on coa.acnoid = ar.acnoid where ar.trno=cp.trno and ar.bal<>0 and coa.alias in ('AR1','AR5')) as bal,
        tnum.seq as docno,num.seq as cvno
        from lahead as cp
        left join cntnum as num on num.trno=cp.trno
        left join heahead as head on head.trno=num.dptrno
        left join transnum as tnum on tnum.trno = head.trno
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        left join client on client.client = head.client
         where cp.doc='CV'  and num.dptrno<>0
        and date(cp.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "         
        order by cvdate,docno ";
        break;
    } // end switch posttype
    // var_dump($query);
    return $query;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:
        $posttype = 'Posted';
        break;

      case 1:
        $posttype = 'Unposted';
        break;

      default:
        $posttype = 'All';
        break;
    }

    $reporttype = 'Summarized';

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


    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);


    $user = "ALL USERS";


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Loan Summary Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '275', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, '275', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function tableheader_summary($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('Loan Type', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Terms', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Balance', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();

    return $str;
  }


  public function tableheader_detailed($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Release Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Loan Type', '125', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Loan Application #', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CV #', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Borrower', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Terms', '90', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Balance', '75', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout_SUMMARIZED($config)
  {
    $result = $this->reportDefault($config);
    $count = 61;
    $page = 60;
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
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader_summary($layoutsize, $config);

    $totalamount = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->loantype, '300', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->bal, 2), '200', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totalamount = $totalamount + $data->amount;
        $str .= $this->reporter->endrow();

        if ($this->reporter->linecounter >= $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader_summary($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    // $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('TOTAL :', '200', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamount, 2), '200', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }


  public function reportDefaultLayout_DETAILED($config)
  {
    $result = $this->reportDefault($config);
    $count = 61;
    $page = 60;
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
    $str .= $this->header_DEFAULT($config);
    $str .= $this->tableheader_detailed($layoutsize, $config);

    $totalamount = 0;
    $totalbal = 0;

    $subtotalamt = 0;
    $months = '';
    $previousMonthYear = '';
    $i = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {

        $monthhere  = date("F", strtotime($data->cvdate));
        $yearhere = date("Y",  strtotime($data->cvdate));

        $currentMonthYear = $monthhere . ' ' . $yearhere;

        if ($months != '' && $months != $currentMonthYear) {
          SubtotalHere:
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '100', '', false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '125', '', false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '90', '', false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '90', '', false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          // $str .= $this->reporter->col('', '260', '', false, '1px dotted', 'T', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col($previousMonthYear   . ' ' . 'Total:', '290', '', false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          // $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(($subtotalamt == 0) ? '-' : number_format($subtotalamt, 2), '75', '', false, '1px dotted', 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '75', '', false, '1px dotted', 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
          $subtotalamt = 0;
          if ($i == (count((array)$result) - 1)) {
            break;
          }
          $str .= $this->reporter->addline();
          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->tableheader_detailed($layoutsize, $config);
            $page = $page + $count;
          }
        }

        if ($months == '' || $months != $currentMonthYear) {
          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($currentMonthYear, '100', null, false, '1px solid ', '', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '700', '', false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();

          $str .= $this->reporter->addline();

          if ($this->reporter->linecounter == $page) {
            $str .= $this->reporter->endtable();
            $str .= $this->reporter->page_break();
            $str .= $this->tableheader_detailed($layoutsize, $config);
            $page = $page + $count;
          }

          $months = $currentMonthYear;
          $previousMonthYear = $monthhere;
        }


        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->cvdate, '100', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->loantype, '125', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->cvno, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->borrower, '200', null, false, $border, '', 'LT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->terms, '90', null, false, $border, '', 'CT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '75', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->bal, 2), '75', null, false, $border, '', 'RT', $font, $fontsize, '', '', '');
        $totalamount = $totalamount + $data->amount;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();


        $subtotalamt += $data->amount;


        // if ($this->reporter->linecounter == $page) {
        //     $str .= $this->reporter->endtable();
        //     $str .= $this->reporter->page_break();
        //    $str .= $this->tableheader_detailed($layoutsize, $config);
        //     $page = $page + $count;
        // }

        if ($i == (count((array)$result) - 1)) {
          goto SubtotalHere;
        }
        $i++;

        // if ($this->reporter->linecounter >= $page) {
        //   $str .= $this->reporter->endtable();
        //   $str .= $this->reporter->page_break();
        //   $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
        //   if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
        //   $str .= $this->tableheader_detailed($layoutsize, $config);
        //   $page = $page + $count;
        // } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    // $str .= $this->reporter->col('', '260', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Grand Total :', '290', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    // $str .= $this->reporter->col('', '90', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totalamount, 2), '75', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '75', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class