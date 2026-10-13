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

class daily_piece_rate_report
{
  public $modulename = 'Daily Piece Rate Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1850px;max-width:1850px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'legal', 'layoutSize' => '1850'];

  public function __construct($params = null)
  {
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass($params);
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {

    $fields = ['radioprint', 'start', 'type', 'itemcategoryname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'type.label', 'Type');
    data_set($col1, 'type.required', false);
    data_set($col1, 'type.type', 'lookup');
    data_set($col1, 'type.action', 'lookuprandom');
    data_set($col1, 'type.lookupclass', 'lookup_shifttype');
    data_set($col1, 'type.class', 'sbccsreadonly');
    data_set($col1, 'itemcategoryname.action', 'lookupcategory');
    data_set($col1, 'itemcategoryname.lookupclass', 'lookupcategoryitemcategory');
    data_set($col1, 'itemcategoryname.class', 'cscategory sbccsreadonly');


    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $qry = "select 'default' as print,left(now(),10) as start, '' as type, 0 as catid, '' as itemcategoryname";

    return $this->coreFunctions->opentable($qry);
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
    $result = $this->reportDefaultLayout_DPR($config);
    return $result;
  }

  // Kasama lahat ng item ng category kahit 0 ang qty, para laging kumpleto ang columns.
  public function reportDefault($config)
  {
    $data = $this->default_QUERY_DPR($config);

    $query = "select e.catid, icat.name as categoryname, e.empid, cl.clientname, i.itemid,
                     ifnull(d.qty,0) as qty, ifnull(d.boxes,0) as boxes, ifnull(d.rate,0) as rate
              from (select distinct catid, empid from ($data) as x) as e
              inner join (select distinct catid, itemid from ($data) as y) as i on i.catid = e.catid
              left join (select catid, empid, itemid,
                                sum(pcswork) as qty,
                                sum(if(payqty > 0, pcswork / payqty, 0)) as boxes,
                                if(sum(pcswork) > 0, max(payrate), 0) as rate
                         from ($data) as z
                         group by catid, empid, itemid) as d
                     on d.catid = e.catid and d.empid = e.empid and d.itemid = i.itemid
              left join itemcategory as icat on icat.line = e.catid
              left join client as cl on cl.clientid = e.empid
              left join item as it on it.itemid = i.itemid
              order by icat.name, e.catid, cl.clientname, e.empid, it.shortname, i.itemid";

    return $this->coreFunctions->opentable($query);
  }

  // default_ITEMS_DPR - HEADER AT TOTAL NG COLUMNS NG ISANG CATEGORY
  // Tinatawag tuwing NAGPAPALIT ng category sa loop.
  public function default_ITEMS_DPR($config, $catid)
  {
    $data = $this->default_QUERY_DPR($config);

    $query = "select d.itemid, it.shortname, sum(d.pcswork) as total
              from ($data) as d
              left join item as it on it.itemid = d.itemid
              where d.catid = '$catid'
              group by d.itemid, it.shortname
              order by it.shortname, d.itemid";

    return $this->coreFunctions->opentable($query);
  }

  // RAW DATA (pinagkukunan ng lahat ng query)
  public function default_QUERY_DPR($config)
  {
    $center   = $config['params']['center'];
    $start    = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $type     = $config['params']['dataparams']['type'];
    $catid    = $config['params']['dataparams']['catid'];
    $category = $config['params']['dataparams']['category'];

    $filter = "";
    if ($category != "") {
      $filter .= " and it.category = '$catid'";
    }
    if ($type != "") {
      $filter .= " and head.type = '$type'";
    }

    $query = "select it.category as catid, stock.itemid, stock.empid, stock.pcswork, stock.amount, it.payqty, ifnull(it.payrate,0) as payrate
              from dahead as head
              inner join piecetrans as stock on stock.trno = head.trno
              left join hrisnum as num on num.trno = head.trno
              left join item as it on it.itemid = stock.itemid
              where date(head.dateid) = '$start' and num.center = '$center' $filter
              union all
              select it.category as catid, stock.itemid, stock.empid, stock.pcswork, stock.amount, it.payqty, ifnull(it.payrate,0) as payrate
              from hdahead as head
              inner join hpiecetrans as stock on stock.trno = head.trno
              left join hrisnum as num on num.trno = head.trno
              left join item as it on it.itemid = stock.itemid
              where date(head.dateid) = '$start' and num.center = '$center' $filter";

    return $query;
  }



  public function default_header_dpr($config)
  {
    $center   = $config['params']['center'];
    $start    = date("m-d-Y", strtotime($config['params']['dataparams']['start']));
    $type     = $config['params']['dataparams']['type'];
    $category = $config['params']['dataparams']['itemcategoryname'];
    if ($category == "") {
      $category = "ALL";
    }

    if ($type == "") {
      $type = "ALL";
    }

    $str = '';
    $layoutsize = '1850';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $company = $this->coreFunctions->datareader("select name as value from center where code = '$center'");

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($company), null, null, false, $border, '', 'L', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DAILY PIECE RATE REPORT', null, null, false, $border, '', 'L', $font, '14', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Trans. Date : <u>' . $start . '</u>', '280', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Type : <u>' . $type . '</u>', '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Category : <u>('" . $category . "')</u>", null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    return $str;
  }


  public function default_tableheader_dpr($config, $categoryname, $items)
  {
    // header ng table: categoryname, shortcode, QTY PCS, PIECES / BOXES / RATE
    $str = '';
    // lapad ng table = name(290) + 120 kada item + PIECES/BOXES/RATE(360)
    $layoutsize = 290 + (count($items) * 120) + 360;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('<u>' . $categoryname . '</u>', '290', null, false, $border, '', 'C', $font, '11', 'B', '', '20px');
    foreach ($items as $item) {
      $str .= $this->reporter->col($item->shortname, '120', null, false, $border, 'LRT', 'C', $font, $fontsize, 'B', '', '20px');
    }
    // 3 bakanteng cell sa itaas ng PIECES / BOXES / RATE (para pantay ang column)
    $str .= $this->reporter->col('', '120', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '20px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '20px');
    $str .= $this->reporter->col('', '120', null, false, $border, 'RT', 'C', $font, $fontsize, 'B', '', '20px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '290', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '10px');
    foreach ($items as $item) {
      $str .= $this->reporter->col('QTY PCS', '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '10px');
    }
    $str .= $this->reporter->col('PIECES', '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->col('BOXES', '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->col('RATE', '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, 'B', '', '10px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    return $str;
  }


  // TOTAL row ng isang category
  public function default_total_dpr($config, $items, $totalpieces, $totalboxes, $totalrate)
  {
    $str = '';
    $tablesize = 290 + (count($items) * 120) + 360;
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $pad = '12px';

    $str .= $this->reporter->begintable($tablesize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL :', '290', null, false, $border, 'LRTB', 'R', $font, '11', 'B', '', $pad);
    foreach ($items as $item) {
      $str .= $this->reporter->col(number_format($item->total, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
    }
    $str .= $this->reporter->col(number_format($totalpieces, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
    $str .= $this->reporter->col(number_format($totalboxes, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
    $str .= $this->reporter->col(number_format($totalrate, 2), '120', null, false, $border, 'LRTB', 'C', $font, '11', '', '', $pad);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/>';

    return $str;
  }


  public function reportDefaultLayout_DPR($config)
  {
    // ini_set('max_execution_time', -1);
    // ini_set('memory_limit', '-1');

    $result = $this->reportDefault($config); // isang row = isang kahon

    $str = '';
    $layoutsize = '1850';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $pad = '12px';

    $page = 15;  // ilang row kada page
    $line = 0;   // bilang ng row sa page ngayon

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_header_dpr($config);

    $catid = '';
    $empid = '';
    $items = [];

    $pieces = 0;      // ng employee
    $boxes = 0;
    $rate = 0;
    $totalpieces = 0; // ng category
    $totalboxes = 0;
    $totalrate = 0;
    $grandpieces = 0; // ng buong report
    $grandrate = 0;

    foreach ($result as $data) {

      // NAGPALIT NG EMPLOYEE -> tapusin ang row ng naunang employee
      if ($empid != '' && ($empid != $data->empid || $catid != $data->catid)) {
        $str .= $this->reporter->col(number_format($pieces, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
        $str .= $this->reporter->col(number_format($boxes, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
        $str .= $this->reporter->col(number_format($rate, 2), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $line++;

        $totalpieces += $pieces;
        $totalboxes += $boxes;
        $totalrate += $rate;
      }

      // NAGPALIT NG CATEGORY -> TOTAL ng luma, header ng bago
      if ($catid != $data->catid) {
        if ($catid != '') {
          $str .= $this->default_total_dpr($config, $items, $totalpieces, $totalboxes, $totalrate);
          $line = $line + 2;
          $grandpieces += $totalpieces;
          $grandrate += $totalrate;
        }

        $items = $this->default_ITEMS_DPR($config, $data->catid);

        if ($line + 3 > $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->default_header_dpr($config);
          $line = 0;
        }
        $str .= $this->default_tableheader_dpr($config, $data->categoryname, $items);

        $totalpieces = 0;
        $totalboxes = 0;
        $totalrate = 0;
      }

      // BAGONG EMPLOYEE -> i-print ang pangalan
      if ($empid != $data->empid || $catid != $data->catid) {
        if ($line >= $page) {
          $str .= $this->reporter->page_break();
          $str .= $this->default_header_dpr($config);
          $str .= $this->default_tableheader_dpr($config, $data->categoryname, $items);
          $line = 0;
        }

        $tablesize = 290 + (count($items) * 120) + 360;
        $str .= $this->reporter->begintable($tablesize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->clientname, '290', null, false, $border, 'LRTB', 'L', $font, $fontsize, 'B', '', $pad);

        $empid = $data->empid;
        $catid = $data->catid;
        $pieces = 0;
        $boxes = 0;
        $rate = 0;
      }

      //ISANG KAHON qty ng employee sa item na ito
      if ($data->qty == 0) {
        $str .= $this->reporter->col('-', '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
      } else {
        $str .= $this->reporter->col(number_format($data->qty, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
      }

      $pieces += $data->qty;
      $boxes += $data->boxes;
      $rate += $data->rate;
    }

    // TAPUSIN ANG HULING EMPLOYEE AT HULING CATEGORY
    $str .= $this->reporter->col(number_format($pieces, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
    $str .= $this->reporter->col(number_format($boxes, 0), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
    $str .= $this->reporter->col(number_format($rate, 2), '120', null, false, $border, 'LRTB', 'C', $font, $fontsize, '', '', $pad);
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $line++;

    $totalpieces += $pieces;
    $totalboxes += $boxes;
    $totalrate += $rate;
    $grandpieces += $totalpieces;
    $grandrate += $totalrate;

    $str .= $this->default_total_dpr($config, $items, $totalpieces, $totalboxes, $totalrate);
    $line = $line + 2;

    // GRAND TOTAL
    if ($line + 2 > $page) {
      $str .= $this->reporter->page_break();
      $str .= $this->default_header_dpr($config);
    }

    $str .= '<br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL PIECES :', '290', null, false, $border, '', 'R', $font, '11', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandpieces, 2), '160', null, false, $border, '', 'R', $font, '11', 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL RATE :', '290', null, false, $border, '', 'R', $font, '11', 'B', '', '');
    $str .= $this->reporter->col(number_format($grandrate, 2), '160', null, false, $border, '', 'R', $font, '11', 'B', '', '');
    $str .= $this->reporter->col('', null, null, false, $border, '', '', $font, '11', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class