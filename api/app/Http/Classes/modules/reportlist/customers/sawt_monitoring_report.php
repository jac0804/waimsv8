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

class sawt_monitoring_report
{
  public $modulename = 'SAWT Monitoring Report';
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


    $fields = ['radioprint', 'start', 'end', 'radioposttype'];


    $col1 = $this->fieldClass->create($fields);


    data_set(
      $col1,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => 'post', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => 'unpost', 'color' => 'teal'],
        ['label' => 'All', 'value' => 'all', 'color' => 'teal']
      ]
    );


    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    left(now(),10) as start,
    left(now(),10) as end,'all' as posttype
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY

    $posttype   = $config['params']['dataparams']['posttype'];

    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));



    switch ($posttype) {
      case 'unpost':
        $query = "select head.trno,head.docno as sino,head.dateid as sidate,cust.clientname as sicustname,cust.tin as sitin,head.vattype,
      sum(stock.ext) as ext,0 as cr,'' as crno,'' as crdate,0 as ewt,0 as ucwt,0 as collected,head.trno,sum(stock.ext) as bal
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client as cust on cust.client=head.client
      where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end'
      group by  head.trno, head.docno, head.dateid, cust.clientname, cust.tin, head.vattype,head.trno
      order by sino";
        break;
      case 'post':
        $query = "
      select distinct head.trno,head.docno as sino,head.dateid as sidate,cust.clientname as sicustname,cust.tin as sitin,head.vattype,
      sum(stock.ext) as ext, 0 as cr, '' as crno,'' as crdate,0 as ewt,0 as ucwt,0 as collected,head.trno,sum(ar.bal) as bal
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client as cust on cust.clientid=head.clientid
      left join arledger as ar on ar.trno=head.trno
      where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end'
      group by head.trno, head.docno, head.dateid, cust.clientname, cust.tin, head.vattype, ar.dateid, head.trno
      order by sino";
        break;

      default:
        $query = "select head.trno,head.docno as sino,head.dateid as sidate,cust.clientname as sicustname,cust.tin as sitin,head.vattype,
      sum(stock.ext) as ext,0 as cr,'' as crno,'' as crdate,0 as ewt,0 as ucwt,0 as collected,head.trno,sum(stock.ext) as bal
      from lahead as head
      left join lastock as stock on stock.trno=head.trno
      left join client as cust on cust.client=head.client
      where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end'
      group by  head.trno, head.docno, head.dateid, cust.clientname, cust.tin, head.vattype,head.trno
      union all
      select distinct head.trno,head.docno as sino,head.dateid as sidate,cust.clientname as sicustname,cust.tin as sitin,head.vattype,
      sum(stock.ext) as ext, 0 as cr, '' as crno,'' as crdate,0 as ewt,0 as ucwt,0 as collected,head.trno,sum(ar.bal) as bal
      from glhead as head
      left join glstock as stock on stock.trno=head.trno
      left join client as cust on cust.clientid=head.clientid
      left join arledger as ar on ar.trno=head.trno
      where head.doc in ('SJ','AI') and head.dateid between '$start' and '$end'
      group by head.trno, head.docno, head.dateid, cust.clientname, cust.tin, head.vattype, ar.dateid, head.trno
      order by sino";

        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $qry = "select code,name,address,tel from center where code = '" . $center . "'";
    $headerdata = $this->coreFunctions->opentable($qry);

    $str = '';
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "9";
    $border = "1px solid ";





    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, $border, '', 'L', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('SAWT Monitoring ', null, null, false, $border, '', '', $font, '13', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SI#', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('SI Date', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Customer Name', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Tin', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vatable', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('12% Vat', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('VZR', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Vat Type', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Total Invoice Amt', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount Collected', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Outstanding Balance', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CWT', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Uncollected CWT', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CR#', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CR Date', '71', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();


    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);

    $count = 10;
    $page = 10;
    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "8";
    $border = "1px solid ";


    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    $totalvatable = 0;
    $total12percent = 0;
    $totalvzr = 0;
    $totalinvoice = 0;
    $totalcollected = 0;
    $totaloutstanding = 0;
    $collected = 0;
    $ewt = 0;
    $crno = '';
    $crdate = '';
    $ucwt = 0;
    foreach ($result as $key => $data) {
      $vattype = '';
      $vat = 0;
      $zerovat = 0;
      $outstanding = 0;
      $vattype = $data->vattype;
      if ($vattype == 'VATABLE') {
        $vat = $data->ext;
      }
      if ($vattype == 'ZERO-RATED') {
        $zerovat = $data->ext;
      }

      //get payment details and cwt
      $crqry = "select group_concat(h.docno separator '/') as docno,group_concat(h.dateid) as dateid,
      sum((select db-cr from gldetail as gd left join coa on coa.acnoid = gd.acnoid where gd.trno = d.trno and (coa.alias ='AR5' or left(coa.alias,2) in ('CA','CR','CB')))) as collected,
      ifnull(sum((select db-cr from gldetail as gd left join coa on coa.acnoid = gd.acnoid where gd.trno = d.trno and coa.alias ='WT2')),0) as ewt,
      ifnull(sum((select db-cr from arledger as gd left join coa on coa.acnoid = gd.acnoid where gd.trno = d.trno and coa.alias ='ARWT' and gd.bal<>0)),0) as ucwt
       from glhead as h left join gldetail as d on d.trno = h.trno
      where  d.refx= " . $data->trno;
      $crdet = $this->coreFunctions->opentable($crqry);

      if (!empty($crdet)) {
        $collected = $crdet[0]->collected;
        $ewt = $crdet[0]->ewt;
        $crno = $crdet[0]->docno;
        $crdate = $crdet[0]->dateid;
        $ucwt = $crdet[0]->ucwt;
      }

      $str .= $this->reporter->startrow();

      // 1st, Docno
      $str .= $this->reporter->col($data->sino, '71', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      // 2nd, Trans Date
      $str .= $this->reporter->col($data->sidate, '71', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      // 3rd, Cust Name
      $str .= $this->reporter->col($data->sicustname, '71', null, false, $border, '', 'L', $font, $fontsize, '', '', '');

      // 4th, Tin  
      $str .= $this->reporter->col($data->sitin, '71', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      // 5th, 6th column, Vatable, 12%
      if ($vat == 0) {
        $str .= $this->reporter->col('-', '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('-', '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col(number_format($vat, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col(number_format($vat * 0.12, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totalvatable += $vat;
        $total12percent += ($vat * 0.12);
      }

      // 7th column, VZR
      if ($zerovat == 0) {
        $str .= $this->reporter->col('-', '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      } else {
        $str .= $this->reporter->col(number_format($zerovat, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totalvzr += $zerovat;
      }

      // 8th, 9th, Type, Total Invoice Amt
      switch ($vattype) {
        case 'VATABLE':
          $str .= $this->reporter->col('V', '71', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($vat * 1.12, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $totalinvoice += ($vat * 1.12);
          $outstanding = ($vat * 1.12) - $collected - $ewt;

          break;
        case 'ZERO-RATED':
          $str .= $this->reporter->col('ZRV', '71', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($zerovat, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          $totalinvoice += $zerovat;

          $outstanding = $zerovat - $collected - $ewt;
          break;
        default:
          $str .= $this->reporter->col('', '71', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
          break;
      }

      // 10th, Amount Collected
      $str .= $this->reporter->col(number_format($collected, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $totalcollected += $collected;

      // 11th, Outstanding
      if ($data->bal != 0) {
        $str .= $this->reporter->col(number_format($outstanding, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
        $totaloutstanding += $outstanding;
      } else {
        $str .= $this->reporter->col(number_format($data->bal, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      }


      // 12th, EWT
      $str .= $this->reporter->col(number_format($ewt, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      // uncollected cwt 
      $str .= $this->reporter->col(number_format($ucwt, 2), '71', null, false, $border, '', 'R', $font, $fontsize, '', '', '');

      // 13th, CR Reference
      $str .= $this->reporter->col($crno, '71', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      // 14th, CR date
      $str .= $this->reporter->col($crdate, '71', null, false, $border, '', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    //vatable
    $str .= $this->reporter->col(number_format($totalvatable, 2), '71', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');

    //12%
    $str .= $this->reporter->col(number_format($total12percent, 2), '71', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    //VZR
    $str .= $this->reporter->col(number_format($totalvzr, 2), '71', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    //invoice
    $str .= $this->reporter->col(number_format($totalinvoice, 2), '71', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    //collected
    $str .= $this->reporter->col(number_format($totalcollected, 2), '71', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    //outstanding
    $str .= $this->reporter->col(number_format($totaloutstanding, 2), '71', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '71', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class