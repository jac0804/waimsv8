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

class cost_adjustment_report
{
  public $modulename = 'Cost Adjustment Report';
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
    $fields = ['radioprint', 'start', 'end', 'print'];
    $col1 = $this->fieldClass->create($fields);
    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $companyid       = $config['params']['companyid'];
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-360) as start,left(now(),10) as end";

    // NAME NG INPUT YUNG NAKA ALIAS
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

    $result = $this->reportDefaultLayout($config);

    return $result;
  }
  // QUERY
  public function reportDefault($config)
  {
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center    = $config['params']['center'];

     $centerfilter="";
    if($config['params']['companyid']==60){//transpower
     $centerfilter=" and num.center = '$center' ";
    }

    $query = "select pvi.ref as rrdocno,head.docno as pvdocno,rrhead.invoiceno as suppinv,i.itemname,
    rrstock.cost as rrcost,pvi.amt as adjustcost
    from lahead as head
    left join pvitem as pvi on pvi.trno=head.trno
    left join glstock as rrstock on rrstock.trno=pvi.refx and rrstock.line=pvi.linex
    left join glhead as rrhead on rrhead.trno=rrstock.trno
    left join item as i on i.itemid=pvi.itemid
    left join cntnum as num on num.trno = head.trno
    where head.doc='PV' and pvi.ref is not null and date(head.dateid) between '$start' and '$end'  $centerfilter
    union all
    select pvi.ref as rrdocno,head.docno as pvdocno,rrhead.invoiceno as suppinv,i.itemname,
    rrstock.cost as rrcost,pvi.amt as adjustcost
    from glhead as head
    left join pvitem as pvi on pvi.trno=head.trno
    left join glstock as rrstock on rrstock.trno=pvi.refx and rrstock.line=pvi.linex
    left join glhead as rrhead on rrhead.trno=rrstock.trno
    left join item as i on i.itemid=pvi.itemid
    left join cntnum as num on num.trno = head.trno
    where head.doc='PV' and pvi.ref is not null and date(head.dateid) between '$start' and '$end'  $centerfilter
    order by rrdocno";

    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];

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

    $str .= '<br><br>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('COST ADJUSTMENT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br>';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, $border, '', 'L', $font, $fontsize, '','', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RR #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('SUPP INV.', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('ITEM', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('RR COST', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('ADJUSTED COST', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('APV #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('CURRENT COST', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '3px');
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result  = $this->reportDefault($config);
    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];

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
    $str .= $this->default_displayHeader($config);

    $str .= $this->reporter->begintable($layoutsize);

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->rrdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->suppinv, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->itemname, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->rrcost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->adjustcost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->pvdocno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->rrcost + $data->adjustcost, 2), '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->default_displayHeader($config);
        $str .= $this->reporter->endrow();
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class