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

class outsource_summary_report
{
  public $modulename = 'Outsource Summary Report';
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
    $fields = ['radioprint', 'start', 'end', 'radioposttype', 'print'];

    $col1 = $this->fieldClass->create($fields);
    data_set(
      $col1,
      'radioposttype.options',
      [
        ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
        ['label' => 'Unposted', 'value' => '1', 'color' => 'teal'],
        ['label' => 'All', 'value' => '2', 'color' => 'teal']
      ]
    );

    return array('col1' => $col1);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $companyid       = $config['params']['companyid'];
    $paramstr = "select 
    'default' as print,
    adddate(left(now(),10),-360) as start,
    left(now(),10) as end,
    '0' as posttype";

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
    $posttype   = $config['params']['dataparams']['posttype'];

    switch ($posttype) {
      case 0:

        $query = "
        select ifnull(tele.clientname,'') as acct,
        sum(case when head.vendor = 'LOCAL' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as lphamt,
        sum(case when head.vendor = 'LOCAL' and stock.currency='USD' then stock.ext else 0 end) as lusamt,
        sum(case when head.vendor = 'LOCAL' and stock.currency='SGD' then stock.ext else 0 end) as lsgamt,
        sum(case when head.vendor = 'AFTECH' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as aphamt,
        sum(case when head.vendor = 'AFTECH' and stock.currency='USD' then stock.ext else 0 end) as ausamt,
        sum(case when head.vendor = 'AFTECH' and stock.currency='SGD' then stock.ext else 0 end) as asgamt
        from hoshead as head
        left join hosstock as stock on stock.trno=head.trno
        left join client as tele on tele.clientid=head.telesalesid
        where head.telesalesid!=0 and date(head.dateid) between '$start' and '$end'
        group by tele.clientname
        order by acct";
        break;

      case 1:

        $query = "
      select ifnull(tele.clientname,'') as acct,
      sum(case when head.vendor = 'LOCAL' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as lphamt,
      sum(case when head.vendor = 'LOCAL' and stock.currency='USD' then stock.ext else 0 end) as lusamt,
      sum(case when head.vendor = 'LOCAL' and stock.currency='SGD' then stock.ext else 0 end) as lsgamt,
      sum(case when head.vendor = 'AFTECH' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as aphamt,
      sum(case when head.vendor = 'AFTECH' and stock.currency='USD' then stock.ext else 0 end) as ausamt,
      sum(case when head.vendor = 'AFTECH' and stock.currency='SGD' then stock.ext else 0 end) as asgamt
      from oshead as head
      left join osstock as stock on stock.trno=head.trno
      left join client as tele on tele.clientid=head.telesalesid
      where head.telesalesid!=0 and date(head.dateid) between '$start' and '$end'
      group by tele.clientname
      order by acct";
        break;

      default:

        $query = "
      select ifnull(tele.clientname,'') as acct,
      sum(case when head.vendor = 'LOCAL' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as lphamt,
      sum(case when head.vendor = 'LOCAL' and stock.currency='USD' then stock.ext else 0 end) as lusamt,
      sum(case when head.vendor = 'LOCAL' and stock.currency='SGD' then stock.ext else 0 end) as lsgamt,
      sum(case when head.vendor = 'AFTECH' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as aphamt,
      sum(case when head.vendor = 'AFTECH' and stock.currency='USD' then stock.ext else 0 end) as ausamt,
      sum(case when head.vendor = 'AFTECH' and stock.currency='SGD' then stock.ext else 0 end) as asgamt
      from oshead as head
      left join osstock as stock on stock.trno=head.trno
      left join client as tele on tele.clientid=head.telesalesid
      where head.telesalesid!=0 and date(head.dateid) between '$start' and '$end'
      group by tele.clientname
      union all
      select ifnull(tele.clientname,'') as acct,
      sum(case when head.vendor = 'LOCAL' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as lphamt,
      sum(case when head.vendor = 'LOCAL' and stock.currency='USD' then stock.ext else 0 end) as lusamt,
      sum(case when head.vendor = 'LOCAL' and stock.currency='SGD' then stock.ext else 0 end) as lsgamt,
      sum(case when head.vendor = 'AFTECH' and (stock.currency='PHP' or stock.currency='') then stock.ext else 0 end) as aphamt,
      sum(case when head.vendor = 'AFTECH' and stock.currency='USD' then stock.ext else 0 end) as ausamt,
      sum(case when head.vendor = 'AFTECH' and stock.currency='SGD' then stock.ext else 0 end) as asgamt
      from hoshead as head
      left join hosstock as stock on stock.trno=head.trno
      left join client as tele on tele.clientid=head.telesalesid
      where head.telesalesid!=0 and date(head.dateid) between '$start' and '$end'
      group by tele.clientname
      order by acct";
        break;
    }


    return $this->coreFunctions->opentable($query);
  }

  private function default_displayHeader($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $start        = $config['params']['dataparams']['start'];
    $end          = $config['params']['dataparams']['end'];
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
    $str .= $this->reporter->col('OUTSOURCE SUMMARY REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br>';
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow(null, null, false, $border, '', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '300', null, false, $border, '', 'L', $font, $fontsize, '','', '', '');
    $str .= $this->reporter->col('Transaction Type : ' . $posttype, '300', null, false, $border, '', 'L', $font, $fontsize,'', '', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '280', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('AFTECH QUOTES', '360', null, false, $border, 'TR', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('LOCAL OUTSOURCE QUOTES', '360', null, false, $border, 'TR', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('ACCOUNTS', '280', null, false, $border, 'LRB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('USD', '120', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('SGD', '120', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PHP', '120', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('USD', '120', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('SGD', '120', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('PHP', '120', null, false, $border, 'TRB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result  = $this->reportDefault($config);
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
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
    $totalaus = 0;
    $totalasg = 0;
    $totalaph = 0;
    $totallus = 0;
    $totallsg = 0;
    $totallph = 0;

    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      if ($data->ausamt == 0) {
        $aftius = '';
      } else {
        $aftius = number_format($data->ausamt, $decimalcurr);
      }
      if ($data->asgamt == 0) {
        $aftisg = '';
      } else {
        $aftisg = number_format($data->asgamt, $decimalcurr);
      }
      if ($data->aphamt == 0) {
        $aftiph = '';
      } else {
        $aftiph = number_format($data->aphamt, $decimalcurr);
      }

      if ($data->lusamt == 0) {
        $localus = '';
      } else {
        $localus = number_format($data->lusamt, $decimalcurr);
      }
      if ($data->lsgamt == 0) {
        $localsg = '';
      } else {
        $localsg = number_format($data->lsgamt, $decimalcurr);
      }
      if ($data->lphamt == 0) {
        $localph = '';
      } else {
        $localph = number_format($data->lphamt, $decimalcurr);
      }

      $str .= $this->reporter->col($data->acct, '280', null, false, $border, 'LBR', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($aftius, '120', null, false, $border, 'RB', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($aftisg, '120', null, false, $border, 'RB', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($aftiph, '120', null, false, $border, 'RB', 'C', $font, $fontsize, '', '', '3px');

      $str .= $this->reporter->col($localus, '120', null, false, $border, 'RB', 'C', $font, $fontsize, '', '', '3px');
      $str .= $this->reporter->col($localsg, '120', null, false, $border, 'RB', 'C', $font, $fontsize, '', '', '3px');

      $str .= $this->reporter->col($localph, '120', null, false, $border, 'BR', 'C', $font, $fontsize, '', '', '3px');

      $totalaus += $data->ausamt;
      $totalasg += $data->asgamt;
      $totalaph += $data->aphamt;

      $totallus += $data->lusamt;
      $totallsg += $data->lsgamt;
      $totallph += $data->lphamt;
    }

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('GRAND TOTAL', '280', null, false, $border, 'LBR', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalaus, $decimalcurr), '120', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalasg, $decimalcurr), '120', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totalaph, $decimalcurr), '120', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col(number_format($totallus, $decimalcurr), '120', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totallsg, $decimalcurr), '120', null, false, $border, 'RB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col(number_format($totallph, $decimalcurr), '120', null, false, $border, 'BR', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class