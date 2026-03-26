<?php

namespace App\Http\Classes\modules\reportlist\customers;

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

class sales_monitoring_breakdown_output_report
{
  public $modulename = 'Sales Monitoring Breakdown Output Report';
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

    $fields = ['radioprint', 'end'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'end.label', 'Date to Breakdown');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 24: //GOODFOUND CEMENT
        $paramstr = "select 'default' as print,left(now(),10) as `end`";
        break;
    }

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

  public function reportDefault($config)
  {
    // QUERY
    $query = $this->default_QUERY($config);

    return $this->coreFunctions->opentable($query);
  }

  public function default_QUERY($config)
  {

    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $qry = "select
    distinct c.areacode,
    c.region,
    ifnull(sum(portqty1to10),0) as port1,ifnull(sum(portqty11to20),0) as port2,ifnull(sum(portqty21tolast),0) as port3,
    ifnull(sum(pozzqty1to10),0) as pozz1,ifnull(sum(pozzqty11to20),0) as pozz2,ifnull(sum(pozzqty21tolast),0) as pozz3
    from client as c
    left join
    (
    select client.clientid,
    (case when icat.line=1 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-1 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-10 DAY))
    then sum(stock.isqty) else 0 end) as portqty1to10,
    
    (case when icat.line=1 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-11 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-20 DAY))
    then sum(stock.isqty) else 0 end) as portqty11to20,
    
    (case when icat.line=1 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-21 DAY) and last_day('" . $end . "'))
    then sum(stock.isqty) else 0 end) as portqty21tolast,
    
    (case when icat.line=3114 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-1 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-10 DAY))
    then sum(stock.isqty) else 0 end) as pozzqty1to10,
    
    (case when icat.line=3114 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-11 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-20 DAY))
    then sum(stock.isqty) else 0 end) as pozzqty11to20,
    
    (case when icat.line=3114 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-21 DAY) and last_day('" . $end . "'))
    then sum(stock.isqty) else 0 end) as pozzqty21tolast
    
    from lahead as head
    left join client on head.client=client.client
    left join lastock as stock on stock.trno=head.trno
    left join item as i on i.itemid=stock.itemid
    left join itemcategory as icat on icat.line=i.category
    where head.doc='SJ'
    group by client.clientid,icat.line,head.dateid
    
    union all

    select client.clientid,
    (case when icat.line=1 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-1 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-10 DAY))
    then sum(stock.isqty) else 0 end) as portqty1to10,
    
    (case when icat.line=1 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-11 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-20 DAY))
    then sum(stock.isqty) else 0 end) as portqty11to20,
    
    (case when icat.line=1 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-21 DAY) and last_day('" . $end . "'))
    then sum(stock.isqty) else 0 end) as portqty21tolast,
    
    (case when icat.line=3114 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-1 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-10 DAY))
    then sum(stock.isqty) else 0 end) as pozzqty1to10,
    
    (case when icat.line=3114 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-11 DAY) and DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-20 DAY))
    then sum(stock.isqty) else 0 end) as pozzqty11to20,
    
    (case when icat.line=3114 and (head.dateid between DATE_SUB('" . $end . "', INTERVAL DAYOFMONTH('" . $end . "')-21 DAY) and last_day('" . $end . "'))
    then sum(stock.isqty) else 0 end) as pozzqty21tolast
    
    
    from glhead as head
    left join client on head.clientid=client.clientid
    left join glstock as stock on stock.trno=head.trno
    left join item as i on i.itemid=stock.itemid
    left join itemcategory as icat on icat.line=i.category
    where head.doc='SJ'
    group by client.clientid,icat.line,head.dateid
    ) as a on c.clientid=a.clientid
    group by c.clientid,c.region,c.areacode";

    return $qry;
  }

  public function header_DEFAULT($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $result = $this->reportDefault($config);
    $end        = date("F", strtotime($config['params']['dataparams']['end']));
    $date        = date("F d, Y", strtotime($config['params']['dataparams']['end']));

    $count = 38;
    $page = 40;

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

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($date . ' - Sales Monitoring Breakdown Output Report', '1000', null, false, $border, 'TLR', 'C', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $port = 0;
    $pozz = 0;
    if (!empty($result)) {
      foreach ($result as $key => $data) {
        $port += $data->port1 + $data->port2 + $data->port3;
        $pozz += $data->pozz1 + $data->pozz2 + $data->pozz3;
      }
    }

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For the Month of ' . $end, '500', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Portland', '300', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($port, 2), '200', null, false, $border, 'LTR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '500', null, false, $border, 'L', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Pozolan', '300', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($pozz, 2), '200', null, false, $border, 'LTR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();


    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $month        = date("F", strtotime($config['params']['dataparams']['end']));
    $lastday        = date("Y-m-t", strtotime($config['params']['dataparams']['end']));
    $lastday        = date("d", strtotime($lastday));

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = 11;
    $border = "1px solid ";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->header_DEFAULT($config);


    $total = 0;
    $count = 0;
    $fontsize = $fontsize - 2;

    $linetotal = 0;
    $region = '';


    $subtotalport1 = 0;
    $subtotalpozz1 = 0;

    $subtotalport2 = 0;
    $subtotalpozz2 = 0;

    $subtotalport3 = 0;
    $subtotalpozz3 = 0;

    $subtotal = 0;

    $grandtotal = 0;


    $str .= $this->reporter->begintable($layoutsize);
    if (!empty($result)) {
      foreach ($result as $key => $data) {

        if ($region == '' || $region != $data->region) {

          if ($subtotal != 0) {
            $str .= $this->reporter->startrow();

            $str .= $this->reporter->col('TOTAL:', '250', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotalport1, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotalpozz1, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');

            $str .= $this->reporter->col(number_format($subtotalport2, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotalpozz2, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');

            $str .= $this->reporter->col(number_format($subtotalport3, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
            $str .= $this->reporter->col(number_format($subtotalpozz3, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');

            $str .= $this->reporter->col(number_format($subtotal, 2), '150', null, false, $border, 'LTR', 'R', $font, $fontsize, 'B', '', '');

            $grandtotal += $subtotal;

            $subtotalport1 = 0;
            $subtotalpozz1 = 0;

            $subtotalport2 = 0;
            $subtotalpozz2 = 0;

            $subtotalport3 = 0;
            $subtotalpozz3 = 0;

            $subtotal = 0;
          }
          $region = $data->region;

          $str .= $this->reporter->startrow();

          $str .= $this->reporter->col($region, '250', null, false, $border, 'LT', 'L', $font, $fontsize + 3, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->reporter->col('', '150', null, false, $border, 'TR', 'C', $font, $fontsize, 'B', '', '');

          $str .= $this->tableheader($config, $layoutsize, $font, $fontsize, $border, $month, $lastday);
        }



        $str .= $this->reporter->startrow();
        if ($data->areacode == '') {
          $str .= $this->reporter->col('No Area Code', '250', null, false, $border, 'LT', 'C', $font, $fontsize, '', '', '');
        } else {
          $str .= $this->reporter->col($data->areacode, '250', null, false, $border, 'LT', 'C', $font, $fontsize, '', '', '');
        }

        $str .= $this->reporter->col(number_format($data->port1, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->pozz1, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col(number_format($data->port2, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->pozz2, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col(number_format($data->port3, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($data->pozz3, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, '', '', '');
        $linetotal = $data->port1 + $data->port2 + $data->port3 + $data->pozz1 + $data->pozz2 + $data->pozz3;
        $str .= $this->reporter->col(number_format($linetotal, 2), '150', null, false, $border, 'LTR', 'R', $font, $fontsize, '', '', '');
        $subtotalport1 += $data->port1;
        $subtotalpozz1 += $data->pozz1;

        $subtotalport2 += $data->port2;
        $subtotalpozz2 += $data->pozz2;

        $subtotalport3 += $data->port3;
        $subtotalpozz3 += $data->pozz3;
        $subtotal += $linetotal;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('TOTAL:', '250', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotalport1, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotalpozz1, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col(number_format($subtotalport2, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotalpozz2, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col(number_format($subtotalport3, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($subtotalpozz3, 2), '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col(number_format($subtotal, 2), '150', null, false, $border, 'LTR', 'R', $font, $fontsize, 'B', '', '');

    $grandtotal += $subtotal;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('GRAND TOTAL : ', '100', null, false, $border, 'T', 'R', $font, $fontsize + 3, 'B', '', '');

    $str .= $this->reporter->col(number_format($grandtotal, 2), '150', null, false, $border, 'T', 'R', $font, $fontsize + 3, 'B', '', '');


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }

  public function tableheader($config, $layoutsize, $font, $fontsize, $border, $month, $lastday)
  {
    $companyid = $config['params']['companyid'];
    $str = '';

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '250', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('10 -', '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($month, '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('20 -', '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($month, '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col($lastday . ' -', '100', null, false, $border, 'LT', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($month, '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '150', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Area Code', '250', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Portland', '100', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Pozzolan', '100', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Portland', '100', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Pozzolan', '100', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Portland', '100', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Pozzolan', '100', null, false, $border, 'LT', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Total', '150', null, false, $border, 'LTR', 'C', $font, $fontsize, 'B', '', '');

    return $str;
  }
}//end class