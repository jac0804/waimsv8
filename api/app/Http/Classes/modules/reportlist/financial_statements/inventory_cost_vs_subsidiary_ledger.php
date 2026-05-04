<?php

namespace App\Http\Classes\modules\reportlist\financial_statements;

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

class inventory_cost_vs_subsidiary_ledger
{
  public $modulename = 'Inventory Cost Vs Subsidiary Ledger';
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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['start', 'end', 'dcentername'];
    $col2 = $this->fieldClass->create($fields);

    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);
    data_set($col2, 'start.label', 'Start Date');
    data_set($col2, 'end.label', 'End Date');

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $paramstr = "select 'default' as print,  adddate(left(now(),10),-360) as start,
                left(now(),10) as end,'' as center,'' as centername,'' as dcentername";

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

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->default_QUERY($config);

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {
    $start        = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end          = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $center    = $config['params']['center'];
  
          $query = "select head.docno,   date(head.dateid) as dateid, sum(d.db - d.cr) as cos,
               (select sum(stock.cost * stock.qty) from glstock as stock
                where stock.trno = head.trno and stock.iss = 0) as totalinv
                from glhead as head
                left join gldetail as d on d.trno = head.trno
                left join coa on coa.acnoid = d.acnoid
                left join cntnum on cntnum.trno = head.trno
                where head.dateid between '$start' and '$end' and cntnum.center = '" . $center . "'  and left(coa.alias,2) = 'CG'
                and exists ( select 1  from glstock as stock  where stock.trno = head.trno and stock.iss = 0 )
                group by head.docno, head.trno, date(head.dateid)

                union all

                select head.docno,   date(head.dateid) as dateid, sum(d.db - d.cr) as cos,
                (select sum(stock.cost * stock.iss)  from glstock as stock
                where stock.trno = head.trno  and stock.qty = 0) as totalinv
                from glhead as head
                left join gldetail as d on d.trno = head.trno
                left join coa on coa.acnoid = d.acnoid
                left join cntnum on cntnum.trno = head.trno
                where head.dateid between '$start' and '$end' and cntnum.center = '" . $center . "'  and left(coa.alias,2) = 'CG'
                and exists ( select 1  from glstock as stock  where stock.trno = head.trno and stock.qty = 0 )
                group by head.docno, head.trno, date(head.dateid)";
    return $query;
  }


  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $count = 55;
    $page = 55;
    $this->reporter->linecounter = 0;
    $str = '';

    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $layoutsize = '1000';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);
    $tldifference = 0;
    $tlinv = 0;
    $tlcost = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $str .= $this->reporter->addline();
        $str .= $this->reporter->startrow();
        $difference = $data->totalinv - abs($data->cos);
        if (round($difference, 2) == 0) {
          $difference = 0;
        }
        $str .= $this->reporter->col($data->dateid, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col($data->docno, '110', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->totalinv, 2), '256', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->cos, 2), '256', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($difference, 2), '256', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $tldifference += $difference;
        $tlcost += $data->cos;
        $tlinv += $data->totalinv;

        if ($this->reporter->linecounter == $page) {
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', $font,  $fontsize, '', '');
          $str .= $this->reporter->col('', '110', null, false, $border, 'T', 'L', $font,  $fontsize, '', '');
          $str .= $this->reporter->col('', '256', null, false, $border, 'T', 'R', $font,  $fontsize, '', '');
          $str .= $this->reporter->col('', '256', null, false, $border, 'T', 'R', $font,  $fontsize, '', '');
          $str .= $this->reporter->col('', '256', null, false, $border, 'T', 'C', $font,  $fontsize, '', '');
          $str .= $this->reporter->endrow();

          $str .= $this->reporter->endtable();
          $str .= $this->reporter->page_break();
          $str .= $this->header_DEFAULT($config);
          $page = $page + $count;
        }
      }
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '1000', null, false, '1px dotted', 'B', '', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '110', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('TOTAL', '122', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($tlinv, 2), '256', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($tlcost, 2), '256', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($tldifference, 2), '256', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $font = $this->companysetup->getrptfont($config['params']);
    $str = '';
    $layoutsize = '1000';
    $fontsize = "10";
    $border = "1px solid ";
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('INVENTORY COST VS. SUBSIDIARY LEDGER', null, null, false, $border, '', '', $font, '15', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, '250', null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date', '110', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Doc#', '122', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Inv. Cost', '256', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Cost of Sales', '256', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Difference', '256', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class