<?php

namespace App\Http\Classes\modules\reportlist\items;

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

class withdrawal_summary_report
{
  public $modulename = 'Withdrawal Summary Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  private $logger;
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

    $fields = ['radioprint', 'start', 'end'];

    $col1 = $this->fieldClass->create($fields);
    $fields = ['print'];

    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {

    $paramstr = "
    select 'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end
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
  public function getsidecolQuery($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    return "
    select
    ifnull(case when i.body='' then ic.name else i.body end,'') as sidecol,
    right(dept.client,4) as downcol,
    ifnull(sum(stock.ext),0) as amt
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client as dept on dept.clientid=head.deptid
    left join item as i on i.itemid=stock.itemid
    left join itemcategory as ic on ic.line=i.category

    where head.doc in ('RM','RN') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
    group by i.body,ic.name,dept.client
    union all
    select
    ifnull(case when i.body='' then ic.name else i.body end,'') as sidecol,
    right(dept.client,4) as downcol,
    ifnull(sum(stock.ext),0) as amt
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as dept on dept.clientid=head.deptid
    left join item as i on i.itemid=stock.itemid
    left join itemcategory as ic on ic.line=i.category

    where head.doc in ('RM','RN') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
    group by i.body,ic.name,dept.client
    order by downcol
      ";
  }
  // QUERY
  public function reportDefault($config)
  {
    // QUERY
    $companyid = $config['params']['companyid'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $query = "select
    ifnull(case when i.body='' then ic.name else i.body end,'') as sidecol,
    right(dept.client,4) as downcol,
    ifnull(sum(stock.ext),0) as amt
    from lahead as head
    left join lastock as stock on stock.trno=head.trno
    left join client as dept on dept.clientid=head.deptid
    left join item as i on i.itemid=stock.itemid
    left join itemcategory as ic on ic.line=i.category

    where head.doc in ('RM','RN') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
    group by i.body,ic.name,dept.client
    union all
    select
    ifnull(case when i.body='' then ic.name else i.body end,'') as sidecol,
    right(dept.client,4) as downcol,
    ifnull(sum(stock.ext),0) as amt
    from glhead as head
    left join glstock as stock on stock.trno=head.trno
    left join client as dept on dept.clientid=head.deptid
    left join item as i on i.itemid=stock.itemid
    left join itemcategory as ic on ic.line=i.category

    where head.doc in ('RM','RN') and stock.iscomponent = 0 and date(head.dateid) between '$start' and '$end'
    group by i.body,ic.name,dept.client
    order by downcol";

    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $date      = date("F, Y", strtotime($config['params']['dataparams']['start']));


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

    $str .= $this->reporter->col('WITHDRAWAL SUMMARY REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Month of: ' . $date, null, null, false, $border, '', '', $font, '18', 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }

  public function reportDefaultLayout($config)
  {

    $result  = $this->reportDefault($config);

    $sidecolQuery  = $this->getsidecolQuery($config);
    $sidecol = $this->coreFunctions->opentable(
      "select distinct sidecol from(" .
        $sidecolQuery . ") as a"
    );

    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 11;
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $subtotal = 0;



    $downcolgroup = '';
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
    $str .= $this->reporter->col('COST CENTER', 200, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    //for displaying all the distinct sidecolumns
    foreach ($sidecol as $key => $count) {
      $str .= $this->reporter->col($count->sidecol, 150, null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    }
    $str .= $this->reporter->col('Total', 100, null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $fontsize = $fontsize - 3;
    foreach ($result as $key => $data) {
      //end of group
      if ($downcolgroup != '' && $downcolgroup != $data->downcol) {
        $grandtotal += $grouptotal;
        $grouptotal = 0;
        $str .= $this->reporter->endrow();
        $i = 0;
      }

      //start of group
      if ($downcolgroup == '' || $downcolgroup != $data->downcol) {
        $downcolgroup = $data->downcol;

        //for keeping track of total per downcolumn
        foreach ($result as $key => $total) {
          if ($total->downcol == $downcolgroup) {
            $grouptotal += $total->amt;
          }
        }
      }
      //for displaying amt fields
      if ($i == 0) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($downcolgroup, 200, null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
        foreach ($sidecol as $key => $count2) {
          foreach ($result as $key => $displayamt) {
            //checking if plot is equal sidecol and downcol
            if ($count2->sidecol == $displayamt->sidecol && $downcolgroup == $displayamt->downcol) {
              $display = $displayamt->amt;
            }
          }
          if ($display != 0) {
            $str .= $this->reporter->col(number_format($display, 2), 150, null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
            $display = 0;
          } else {
            $str .= $this->reporter->col('', 150, null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
          }
        }
        $str .= $this->reporter->col(number_format($grouptotal, 2), 100, null, false, $border, 'TLBR', 'C', $font, $fontsize + 2, 'B', '', '');
        $i++;
      }
    } //loop end
    //last row
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Sub Total', 200, null, false, $border, 'TBL', 'C', $font, $fontsize + 2, 'B', '', '');
    foreach ($sidecol as $key => $count3) {
      foreach ($result as $key => $totalamt) {
        //checking if under same dept and summing for subtotal
        if ($count3->sidecol == $totalamt->sidecol) {
          $subtotal += $totalamt->amt;
        }
      }
      $str .= $this->reporter->col(number_format($subtotal, 2), 150, null, false, $border, 'BTL', 'C', $font, $fontsize + 2, 'B', '', '');
      $grandtotal += $subtotal;
      $subtotal = 0;
    }
    $str .= $this->reporter->col(number_format($grandtotal, 2), 100, null, false, $border, 'TLBR', 'C', $font, $fontsize + 2, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class