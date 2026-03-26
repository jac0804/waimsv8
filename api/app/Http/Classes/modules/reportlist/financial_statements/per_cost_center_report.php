<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

use Illuminate\Http\Request;
use App\Http\Requests;
use Session;
use PDF;
use TCPDF_FONTS;
use Illuminate\Support\Facades\Storage;

use Mail;
use App\Mail\SendMail;


use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Milon\Barcode\DNS1D;

use App\Http\Classes\builder\buttonClass;
use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\builder\tabClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use App\Http\Classes\Logger;
use App\Http\Classes\SBCPDF;
use App\Http\Classes\builder\helpClass;
use Illuminate\Support\Facades\URL;

class per_cost_center_report
{
  public $modulename = 'Per Cost Center Report';
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
    $this->fieldClass = new txtfieldClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->logger = new Logger;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'start', 'end', 'dcentername', 'dclientname'];

    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dcentername.action', 'lookupcenter_reports');
    data_set($col1, 'dclientname.lookupclass', 'replookupdepartment');
    data_set($col1, 'dclientname.label', 'Cost Center');

    $fields = ['print'];

    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "
    select 'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '' as client,
    '' as dclientname,
    '' as clientname,
    '" . $defaultcenter[0]['center'] . "' as center,
    '" . $defaultcenter[0]['centername'] . "' as centername,
    '" . $defaultcenter[0]['dcentername'] . "' as dcentername
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

    $result = $this->reportDefaultLayout($config);

    return $result;
  }
  public function getDeptQuery($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['client'];
    $deptname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['dataparams']['center'];
    $centername = $config['params']['dataparams']['centername'];
    $filter = '';
    if ($deptname != '') {
      $filter .= " and dept.client='$dept'";
    }

    if ($centername != '' && $centername != 'ALL') {
      $filter .= " and num.center='$center'";
    }

    return "
      select
      parent.acnoname as accttitle,
      right(dept.client,4) as dept,
      (ifnull(sum(detail.db),0)-
      ifnull(sum(detail.cr),0)) as amt
      from glhead as head
      left join gldetail as detail on detail.trno=head.trno
      left join client as dept on dept.clientid=detail.deptid
      left join coa as c on c.acnoid=detail.acnoid
      left join coa as parent on c.parent=parent.acno
      left join cntnum as num on num.trno=head.trno
      where c.cat='E' and date(head.dateid) between '$start' and '$end' $filter
      group by parent.acnoname,dept.client
      union all
      select
      parent.acnoname as accttitle,
      right(dept.client,4) as dept,
      (ifnull(sum(detail.db),0)-
      ifnull(sum(detail.cr),0)) as amt
      from lahead as head
      left join ladetail as detail on detail.trno=head.trno
      left join client as dept on dept.clientid=detail.deptid
      left join coa as c on c.acnoid=detail.acnoid
      left join coa as parent on c.parent=parent.acno
      left join cntnum as num on num.trno=head.trno
      where c.cat='E' and date(head.dateid) between '$start' and '$end' $filter
      group by parent.acnoname,dept.client
      order by dept asc
      ";
  }
  // QUERY
  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $dept     = $config['params']['dataparams']['client'];
    $deptname = $config['params']['dataparams']['clientname'];

    $center     = $config['params']['dataparams']['center'];
    $centername = $config['params']['dataparams']['centername'];
    $filter = '';
    if ($deptname != '') {
      $filter .= " and dept.client='$dept'";
    }

    if ($centername != '' && $centername != 'ALL') {
      $filter .= " and num.center='$center'";
    }
    $deptQuery  = $this->getDeptQuery($config);
    $deptCount = json_decode(json_encode($this->coreFunctions->opentable(
      "select count(distinct dept) as cnt from(" .
        $deptQuery . ") as a"
    )), true);
    $count = $deptCount[0]['cnt'];


    $query = "select
              $count as rows,
              parent.acno as code,
              parent.acnoname as accttitle,
              right(dept.client,4) as dept,    
              (ifnull(sum(detail.db),0)-
              ifnull(sum(detail.cr),0)) as amt
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno
              left join client as dept on dept.clientid=detail.deptid
              left join coa as c on c.acnoid=detail.acnoid
              left join coa as parent on c.parent=parent.acno
              left join cntnum as num on num.trno=head.trno
              where c.cat='E' and date(head.dateid) between '$start' and '$end' $filter
              group by parent.acno,parent.acnoname,dept.client
              union all
              select
              $count as rows,
              parent.acno as code,
              parent.acnoname as accttitle,
              right(dept.client,4) as dept,    
              (ifnull(sum(detail.db),0)-
              ifnull(sum(detail.cr),0)) as amt
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno
              left join client as dept on dept.clientid=detail.deptid
              left join coa as c on c.acnoid=detail.acnoid
              left join coa as parent on c.parent=parent.acno
              left join cntnum as num on num.trno=head.trno
              where c.cat='E' and date(head.dateid) between '$start' and '$end' $filter
              group by parent.acno,parent.acnoname,dept.client
              order by code asc";
    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('PER COST CENTER REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MANUFACTURING EXPENSE', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    return $str;
  }

  public function reportDefaultLayout($config)
  {

    $result  = $this->reportDefault($config);
    $deptQuery  = $this->getDeptQuery($config);
    $dept = $this->coreFunctions->opentable(
      "select distinct dept from(" .
        $deptQuery . ") as a"
    );
    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 48;
    $page = 50;

    $str = '';
    $columnCount = 0;
    $columnCount = count($dept);
    $layoutsize = (100 * $columnCount) + (550);
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 11;
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $subtotal = 0;
    $remtotal = 0;

    $accountWithHighestCount = 0;
    $account = '';
    $accountRecord = 0;


    $acctgroup = '';
    $grouptotal = 0;
    $grandtotal = 0;
    $subtotal = 0;
    $i = 0;
    $x = 0;
    $display = 0;

    //start grid
    $str .= $this->reporter->begintable($layoutsize);
    //first row
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNT TITLE', 350, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    //for displaying all the distinct departments
    foreach ($dept as $key => $count) {
      $str .= $this->reporter->col($count->dept, 100, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('TOTAL', 120, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    foreach ($result as $key => $data) {
      //end of group
      if ($acctgroup != '' && $acctgroup != $data->code . ' - ' . $data->accttitle) {
        $grandtotal += $grouptotal;
        $grouptotal = 0;
        $str .= $this->reporter->endrow();
        $i = 0;
      }

      //start of group
      if ($acctgroup == '' || $acctgroup != $data->code . ' - ' . $data->accttitle) {
        $acctgroup = $data->code . ' - ' . $data->accttitle;

        //for keeping track of total per account
        foreach ($result as $key => $total) {
          if ($total->code . ' - ' . $total->accttitle == $acctgroup) {
            $grouptotal += $total->amt;
          }
        }
      }
      //for displaying amt fields
      if ($i == 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($acctgroup, 350, null, false, $border, 'TLB', 'L', $font, $fontsize, '', '', '');
        foreach ($dept as $key => $count2) {
          foreach ($result as $key => $displayamt) {
            //checking if plot is equal dept and account title
            if ($count2->dept == $displayamt->dept && $acctgroup == $displayamt->code . ' - ' . $displayamt->accttitle) {
              $display = $displayamt->amt;
            }
          }
          if ($display != 0) {
            $str .= $this->reporter->col(number_format($display, 2), 100, null, false, $border, 'TLB', 'R', $font, $fontsize, '', '', '');
            $display = 0;
          } else {
            $str .= $this->reporter->col('', 100, null, false, $border, 'TLB', 'R', $font, $fontsize, '', '', '');
          }
        }
        $str .= $this->reporter->col(number_format($grouptotal, 2), 120, null, false, $border, 'TLBR', 'R', $font, $fontsize, '', '', '');
        $i++;
      }
    } //loop end
    //last row
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', 350, null, false, $border, 'TBL', 'C', $font, $fontsize, 'B', '', '');
    foreach ($dept as $key => $count3) {
      foreach ($result as $key => $totalamt) {
        //checking if under same dept and summing for subtotal
        if ($count3->dept == $totalamt->dept) {
          $subtotal += $totalamt->amt;
        }
      }
      $str .= $this->reporter->col(number_format($subtotal, 2), 100, null, false, $border, 'BTL', 'R', $font, $fontsize, 'B', '', '');
      $grandtotal += $subtotal;
      $subtotal = 0;
    }
    $str .= $this->reporter->col(number_format($grandtotal, 2), 120, null, false, $border, 'TLBR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();



    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class