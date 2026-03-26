<?php

namespace App\Http\Classes\modules\reportlist\supplier;

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

class rental_payment
{
  public $modulename = 'Rental Payment';
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $companyid = $config['params']['companyid'];

    $fields = ['radioprint', 'dclientname', 'year'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'year.type', 'lookup');
    data_set($col1, 'year.class', 'csyear sbccsreadonly');
    data_set($col1, 'year.lookupclass', 'lookupyear');
    data_set($col1, 'year.action', 'lookupyear');
    data_set($col1, 'dclientname.lookupclass', 'stockcardsupplier');


    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
    year(now()) as year,
    '' as dclientname,
    '' as client,
    '' as clientname,
    0 as supplier
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
    $systemtype = $this->companysetup->getsystemtype($config['params']);
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY
    $year     = $config['params']['dataparams']['year'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $client   = $config['params']['dataparams']['client'];

    $filter   = "";
    if ($clientname != "") {
      $filter .= " and client.client = '$client'";
    }

    if ($year != "") {
      $filter .= " and year(detail.postdate) = '$year'";
    }

    $query = "
    select 
    sum(docstamp) as docstamp,
    sum(discount) as discount,
    sum(cwt) as cwt,
    sum(ewt) as ewt,
    sum(grossamt) as grossamt,
    sum(checkamt) as checkamt,
    group_concat(DISTINCT if(checkno = '',null,checkno)) as checkno, date_format(postdate,'%M') as m,
    group_concat(DISTINCT if(rem = '',null,rem) separator ' <br/> , ') as rem, 
    group_concat(DISTINCT if(postdate = '',null,date(postdate))) as chkdate
    from(
    select sum(if(coa.acno = '\\\\50161',(detail.db-detail.cr),'')) as docstamp,
    sum(if(coa.acno = '\\\\1014805',(detail.cr-detail.db),'')) as discount,
    sum(if(coa.alias = 'wt2',(detail.cr-detail.db),'')) as cwt,
    sum(if(coa.alias = 'apwt1',(detail.cr-detail.db),'')) as ewt,
    sum(detail.db) as grossamt,
    sum(if(left(coa.alias,2) = 'cb',(detail.cr),'')) as checkamt,
    detail.checkno, detail.postdate, head.rem
    from lahead as head
    left join ladetail as detail on head.trno = detail.trno
    left join coa on detail.acnoid = coa.acnoid
    left join client on client.client = head.client
    where head.doc = 'cv'  $filter
    group by detail.checkno, detail.postdate, head.rem
    union all
    select sum(if(coa.acno = '\\\\50161',(detail.db-detail.cr),'')) as docstamp,
    sum(if(coa.acno = '\\\\1014805',(detail.cr-detail.db),'')) as discount,
    sum(if(coa.alias = 'wt2',(detail.cr-detail.db),'')) as cwt,
    sum(if(coa.alias = 'apwt1',(detail.cr-detail.db),'')) as ewt,
    sum(detail.db) as grossamt,
    sum(if(left(coa.alias,2) = 'cb',(detail.cr),'')) as checkamt,
    detail.checkno, detail.postdate, head.rem
    from glhead as head
    left join gldetail as detail on head.trno = detail.trno
    left join coa on detail.acnoid = coa.acnoid
    left join client on client.clientid = head.clientid
    where head.doc = 'cv'  $filter
    group by detail.checkno, detail.postdate, head.rem) as x
    group by date_format(postdate,'%M')
    order by postdate
    ";

    
    return $this->coreFunctions->opentable($query);
  }


  private function displayHeader($config)
  {


    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $year     = $config['params']['dataparams']['year'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $client   = $config['params']['dataparams']['client'];

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
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('RENTAL PAYMENT', null, null, false, $border, '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier : ' . ($clientname != '' ? strtoupper($clientname) : 'ALL'), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Year : ' . ($year != '' ? strtoupper($year) : ''), NULL, null, false, $border, '', 'L', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->pagenumber('Page', NULL, null, false, $border, '', 'R', $font, $fontsize, '', '', '4px');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DOC STAMP', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CWT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Past Due/ Disc', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('EWT', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Gross Amount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check #', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check Date', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Check Amt', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Discount', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Over/Short', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Remarks', '100', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(strtoupper($year), null, null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $year     = $config['params']['dataparams']['year'];
    $clientname   = $config['params']['dataparams']['clientname'];
    $client   = $config['params']['dataparams']['client'];

    $companyid = $config['params']['companyid'];
    $decimalcurr = $this->companysetup->getdecimal('currency', $config['params']);
    $decimalqty = $this->companysetup->getdecimal('qty', $config['params']);
    $decimalprice = $this->companysetup->getdecimal('price', $config['params']);

    $result = $this->reportDefault($config);
    $count = 64;
    $page = 63;

    $layoutsize = '1000';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid ";
    $str = '';

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);
    foreach ($result as $key => $data) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->m, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->docstamp, $decimalcurr), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->cwt, $decimalcurr), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->ewt, $decimalcurr), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->grossamt, $decimalcurr), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->checkno, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->chkdate, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->checkamt, $decimalcurr), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->discount, $decimalcurr), '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->rem, '100', null, false, $border, '', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->printline();
    $str .= $this->reporter->endreport();

    return $str;
  }
}