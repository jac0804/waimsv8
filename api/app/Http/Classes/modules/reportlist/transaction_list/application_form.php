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

class application_form
{
  public $modulename = 'Application Form';
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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dcentername'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);


    $fields = ['radioposttype'];
    $col2 = $this->fieldClass->create($fields);
    data_set(
      $col2,
      'radioposttype.options',
      [
        ['label' => 'Approved', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Pending', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $paramstr = "select 'default' as print,adddate(left(now(),10),-360) as start,left(now(),10) as end,'' as client,'' as clientname, '0' as clientid,
                        '' as userid,'0' as posttype,'' as dclientname,
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
    $result = $this->reportDefaultLayout_SUMMARIZED($config);

    return $result;
  }

  public function reportDefault($config)
  {

    $query = $this->default_QUERY($config);
    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];
    $clientid     = $config['params']['dataparams']['clientid'];
    $clientname     = $config['params']['dataparams']['clientname'];
    $posttype   = $config['params']['dataparams']['posttype'];
    $filter = "";
    $leftjoin = "";

    if ($client != "") {
      $leftjoin = "left join client on client.client = head.client";
      $filter .= " and client.clientid = '$clientid' ";
    }

    $fcenter    = $config['params']['dataparams']['center'];
    if ($fcenter != "") {
      $filter .= " and num.center = '$fcenter'";
    }


    switch ($posttype) {
      case 0: // posted
        $query = "
        select 'APPROVED' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from heahead as head
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 

        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount

        order by docno ";
        break;

      case 1: // unposted
        $query = "select 'PENDING' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from eahead as head
        left join eainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "

        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount
        
        order by docno ";
        break;

      default: // all
        $query = "select 'APPROVED' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from heahead as head
        left join heainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        
        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount

        union all

        select 'PENDING' as status, 
        head.docno,
        left(head.dateid,10) as dateid,
        concat(head.lname,', ',head.fname,' ',head.mname)  as borrower,
        ifnull(r.reqtype,'') as loantype,
        head.terms,info.amount
        from eahead as head
        left join eainfo as info on info.trno=head.trno
        left join reqcategory as r on r.line=head.planid
        $leftjoin
        left join transnum as num on num.trno=head.trno
        
        
        where head.doc='LE'  
        and date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " 
        
        group by head.docno,head.dateid,
        head.lname,head.fname,head.mname,
        r.reqtype,head.terms,info.amount
        
        order by docno ";

        break;
    } // end switch posttype

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


    $user = "ALL USERS";


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Application Form Report (' . $reporttype . ')', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('User: ' . $user, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('Transaction Type: ' . $posttype, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');
    $str .= $this->reporter->col('', null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '', '8px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

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
    $str .= $this->tableheader($layoutsize, $config);

    $totalamount = 0;

    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->status, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->borrower, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col($data->loantype, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->terms, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->amount, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totalamount = $totalamount + $data->amount;
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $isfirstpageheader = $this->companysetup->getisfirstpageheader($config['params']);
          if (!$isfirstpageheader) $str .= $this->header_DEFAULT($config);
          $str .= $this->tableheader($layoutsize, $config);
          $page = $page + $count;
        } //end if
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('TOTAL :', '100', null, false, $border, 'TB', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalamount, 2), '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheader($layoutsize, $config)
  {
    $str = '';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();


    $str .= $this->reporter->col('Status', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Docno', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Date', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Borrower', '200', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Loan Type', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Terms', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }
}//end class