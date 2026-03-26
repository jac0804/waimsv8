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

class detailed_per_account_report
{
  public $modulename = 'Detailed Per Account Report';
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
    $companyid = $config['params']['companyid'];
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

    $result = $this->reportDefaultLayout($config);

    return $result;
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


    $query = "select
    detail.trno,c.acno,c.acnoname,parent.acnoname as parentexp,head.docno,date(head.dateid) as dateid,
    dept.client,dept.clientname,
    right(dept.client,4) as dept,
    (ifnull(detail.db,0)-ifnull(detail.cr,0)) as amt
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno
    left join coa as c on c.acnoid=detail.acnoid
    left join client as dept on dept.clientid=detail.deptid
    left join coa as parent on c.parent=parent.acno
    left join cntnum as num on num.trno=head.trno
    where c.cat='E' and date(head.dateid) between '$start' and '$end' $filter
    union all
    select
    detail.trno,c.acno,c.acnoname,parent.acnoname as parentexp,head.docno,date(head.dateid) as dateid,
    dept.client,dept.clientname,
    right(dept.client,4) as dept,
    (ifnull(detail.db,0)-ifnull(detail.cr,0)) as amt
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    left join coa as c on c.acnoid=detail.acnoid
    left join client as dept on dept.clientid=detail.deptid
    left join coa as parent on c.parent=parent.acno
    left join cntnum as num on num.trno=head.trno
    where c.cat='E' and date(head.dateid) between '$start' and '$end' $filter
    order by parentexp";

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
    $str .= $this->reporter->col('DETAILED PER ACCOUNT REPORT', null, null, false, $border, '', '', $font, '18', 'B', '', '') . '<br />';
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('Date Range: ' . $start . ' - ' . $end, '160', null, false, $border, '', 'L', $font, $fontsize, '', '', '');


    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    //($txt='',$w=null,$h=null, $bg=false,$b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='')
    $str .= $this->reporter->col('ACCOUNT', '300', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('DATE', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('CENTER', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AMT', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('TOTAL', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');



    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result  = $this->reportDefault($config);


    $username   = $config['params']['user'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $client     = $config['params']['dataparams']['client'];

    $count = 48;
    $page = 50;

    $str = '';
    $layoutsize = '800';
    $font = $this->companysetup->getrptfont($config['params']);
    $fontsize = "10";
    $border = "1px solid";

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->default_displayHeader($config);

    $subtotal = 0;
    $grandtotal = 0;
    $parentexp = '';
    foreach ($result as $key => $data) {
      if ($parentexp != '' && $parentexp != $data->parentexp) {
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col('', '300', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($subtotal, 2), '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
        $grandtotal += $subtotal;
        $subtotal = 0;
      }
      if ($parentexp == '' || $parentexp != $data->parentexp) {
        $parentexp = $data->parentexp;
        $str .= $this->reporter->startrow();

        $str .= $this->reporter->col($parentexp, '300', null, false, $border, 'TL', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');

        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, 'B', '', '');
      }

      $str .= $this->reporter->startrow();

      $str .= $this->reporter->col($data->acno . '-' . $data->acnoname, '300', null, false, $border, 'TL', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($data->dateid, '100', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');

      $str .= $this->reporter->col($data->dept, '100', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data->amt, 2), '100', null, false, $border, 'TL', 'C', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('', '100', null, false, $border, 'TLR', 'C', $font, $fontsize, '', '', '');
      $subtotal += $data->amt;
    }
    $grandtotal += $subtotal;
    $subtotal = 0;
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '300', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '100', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'TLB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($grandtotal, 2), '100', null, false, $border, 'TLBR', 'C', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class