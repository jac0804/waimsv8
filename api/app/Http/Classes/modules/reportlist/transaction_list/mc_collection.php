<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

use App\Http\Classes\builder\txtfieldClass;
use App\Http\Classes\companysetup;
use App\Http\Classes\coreFunctions;
use App\Http\Classes\modules\hrisentry\empreqmaster;
use App\Http\Classes\othersClass;
use App\Http\Classes\SBCPDF;

class mc_collection
{
  public $modulename = 'MC Collection';
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
    $fields = ['radioprint', 'start', 'end', 'dcentername'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'dcentername.required', true);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);

    $paramstr = "select 'default' as print, adddate(left(now(),10),-360) as start, left(now(),10) as end, '0' as reporttype,
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
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    $start  = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end    = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $center = $config['params']['dataparams']['center'];

    $filter = '';
    if (!empty($center)) $filter .= "and num.center = '$center'";

    $query = "select head.docno, left(detail.dateid, 10) as dateid, cl.clientname, head.rem, head.yourref, head.ourref, 
    head.amount, head.modeofpayment, detail.penalty, detail.interest 
    from mchead as head
    left join mcdetail as detail on detail.trno = head.trno
    left join transnum as num on num.trno = head.trno
    left join client as cl on cl.clientid = head.clientid
    where head.doc = 'MC' and date(head.dateid) between '$start' and '$end' $filter
    union all 
    select head.docno, left(detail.dateid, 10) as dateid, cl.clientname, head.rem, head.yourref, head.ourref,
    head.amount, head.modeofpayment, detail.penalty, detail.interest 
    from hmchead as head
    left join hmcdetail as detail on detail.trno = head.trno
    left join transnum as num on num.trno = head.trno
    left join client as cl on cl.clientid =  head.clientid
    where head.doc = 'MC' and date(head.dateid) between '$start' and '$end' $filter
    order by docno, dateid";

    return $this->coreFunctions->opentable($query);
  }

  public function displayHeader($config, $layoutsize)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('MC Collection Report', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow(NULL, null, false, $border, 'R', $font, $fontsize, '', '', '', '');
    $str .= $this->reporter->col('Date Range: ' . $start . ' to ' . $end, null, null, false, $border, '', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CLIENTNAME', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMOUNT', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('PENALTY', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('INTEREST', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CR NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('RF NO.', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('NOTES', '125', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    return $str;
  }

  public function reportDefaultLayout($config)
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

    $str = '';
    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config, $layoutsize);

    $docno = '';
    $clientname = '';
    $crno = '';
    $rfno = '';
    $totalAmount = 0;
    $totalInterest = 0;

    $docno = '';
    $clientname = '';
    $crno = '';
    $rfno = '';
    $totalAmount = 0;
    $totalInterest = 0;
    $grandTotalAmount = 0;
    $grandTotalInterest = 0;

    foreach ($result as $key => $data) {
      if ($docno != $data->docno) {
        if ($docno != '') {
          // ADD SUB TOTAL OF AMOUNT AND INTEREST
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('SUB TOTAL: ', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalAmount, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($totalInterest, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', null, '20', false, $border, '', 'L', $font, $fontsize, '', '', '');
          $str .= $this->reporter->endrow();
        }

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
        $str .= $this->reporter->endrow();

        $docno = $data->docno;
        $clientname = '';
        $crno = '';
        $rfno = '';
        $totalAmount = 0;
        $totalInterest = 0;
      }

      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($clientname != $data->clientname) ? $data->clientname : '', '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amount, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($data->penalty == 0) ? '' : $data->penalty, '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->interest, 2), '80', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($crno != $data->yourref) ? $data->yourref : '', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(($rfno != $data->ourref) ? $data->ourref : '', '80', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->rem, '300', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      // CALCULATE TOTAL AMOUNT AND INTEREST
      $totalAmount += $data->amount;
      $totalInterest += $data->interest;
      $grandTotalAmount += $totalAmount;
      $grandTotalInterest += $totalInterest;

      $clientname = $data->clientname;
      $crno = $data->yourref;
      $rfno = $data->ourref;

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $layoutsize);
        $page += $count;
      }
    } //end foreach

    // SUB TOTAL OF AMOUNT AND INTEREST
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('SUB TOTAL: ', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalAmount, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($totalInterest, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    // GRAND TOTAL OF AMOUNT AND INTEREST
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL: ', '100', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($grandTotalAmount, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(number_format($grandTotalInterest, 2), '80', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, $border, 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class

//DATE - 100 WIDTH
//CLIENTNAME - 200 WIDTH
//AMOUNT - 80 WIDTH
//PENALTY - 80 WIDTH
//INTEREST - 80 WIDTH
//CR NO. - 80 WIDTH
//RF NO. - 80 WIDTH
//NOTES - 300 WIDTH
