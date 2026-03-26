<?php

namespace App\Http\Classes\modules\reportlist\supplier;

use Illuminate\Http\Request;
use App\Http\Requests;
use Illuminate\Support\Facades\URL;

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

class withholding_tax_report
{
  public $modulename = 'Withholding Tax Report';
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
    $fields = ['radioprint', 'start', 'end'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'start.required', true);
    data_set($col1, 'end.required', true);

    $fields = ['dclientname', 'dcentername', 'position'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dclientname.required', true);

    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {

    return $this->coreFunctions->opentable("select 
    'default' as print,
    '' as start,
    '' as end,
    '' as center,
    '' as client,
    '' as clientname,
    '' as position
    ");
  }

  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $str = $this->reportDefault($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }

  public function reportDefault($config)
  {
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $supplier = $config['params']['dataparams']['client'];
    $center = $config['params']['dataparams']['center'];

    $filter = "";
    if ($center == '') {
      $filter .= " and cntnum.center='$center'";
    }

    if ($supplier != "") {
      $filter .= " and client.client='" . $supplier . "'";
    }

    $query = "select `month`,`yr`,client,clientname,address,rem,yourref,ourref,tin,acno,acnoname,ref,postdate,sum(db) as db,sum(cr) as cr,dclient,checkno,ewtcode,ewtdesc,ewtrate,zipcode,payortin, payoraddress, payorzipcode, payorcompname from(
    select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
    head.address,detail.rem, head.yourref, head.ourref,client.tin,
    coa.acno, coa.acnoname, detail.ref,detail.postdate,
    detail.db, detail.cr, detail.client as dclient, detail.checkno,
    detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate, client.zipcode,
    center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
    from lahead as head
    left join ladetail as detail on detail.trno=head.trno
    left join client on client.client=head.client
    left join ewtlist on ewtlist.code = detail.ewtcode
    left join coa on coa.acnoid=detail.acnoid
    left join cntnum on cntnum.trno = head.trno
    left join center on center.code = cntnum.center
    where head.dateid between '$start' and '$end' and (detail.isewt = 1 or detail.isvewt=1)  $filter
    
    UNION ALL
    
    select month(head.dateid) as month,year(head.dateid) as yr, head.docno, client.client, client.clientname,
    head.address,detail.rem, head.yourref, head.ourref,client.tin,
    coa.acno, coa.acnoname, detail.ref, detail.postdate,
    detail.db, detail.cr, dclient.client as dclient, detail.checkno,
    detail.ewtcode,ewtlist.description as ewtdesc,detail.ewtrate, client.zipcode,
    center.tin as payortin, center.address as payoraddress, center.zipcode as payorzipcode, center.name as payorcompname
    from glhead as head
    left join gldetail as detail on detail.trno=head.trno
    left join client on client.clientid=head.clientid
    left join coa on coa.acnoid=detail.acnoid
    left join client as dclient on dclient.clientid=detail.clientid
    left join ewtlist on ewtlist.code = detail.ewtcode
    left join cntnum on cntnum.trno = head.trno
    left join center on center.code = cntnum.center
    where head.dateid between '$start' and '$end' and (detail.isewt = 1 or detail.isvewt=1)  $filter)
    as tbl group by client,`month`,yr,ewtcode order by tbl.ewtdesc";

    $array = $this->coreFunctions->opentable($query);
    $result1 = json_decode(json_encode($array), true);

    $arrs = [];
    foreach ($result1 as $key => $value) {
      $ewtrateval = floatval($value['ewtrate']) / 100;
      if ($value['db'] == 0) {

        if ($value['cr'] < 0) {
          $db = $value['cr'];
        } else {
          $db = floatval($value['cr']) * -1;
        }

        $ewtamt = $db * $ewtrateval;
      } else {

        if ($value['db'] < 0) {
          $db = floatval($value['db']) * -1;
        } else {
          $db = $value['db'];
        }

        $ewtamt = $db * $ewtrateval;
      }

      $arrs[$value['ewtcode']]['oamt'] = $db;
      $arrs[$value['ewtcode']]['xamt'] = $ewtamt;
      $arrs[$value['ewtcode']]['month'] = $value['month'];
    }

    $keyers = '';
    $finalarrs = [];
    foreach ($arrs as $key => $value) {
      if ($keyers == '') {
        $keyers = $key;
        $finalarrs[$key]['oamt'] = $value['oamt'];
        $finalarrs[$key]['xamt'] = $value['xamt'];
      } else {
        if ($keyers == $key) {
          $finalarrs[$key]['oamt'] = floatval($finalarrs[$key]['oamt']) + floatval($value['oamt']);
          $finalarrs[$key]['xamt'] = floatval($finalarrs[$key]['xamt']) + floatval($value['xamt']);
        } else {
          $finalarrs[$key]['oamt'] = $value['oamt'];
          $finalarrs[$key]['xamt'] = $value['xamt'];
        }
      }
      $finalarrs[$key]['month'] = $value['month'];
    }

    if (empty($result1)) {
      $returnarr[0]['payee'] = '';
      $returnarr[0]['tin'] = '';
      $returnarr[0]['payortin'] = '';
      $returnarr[0]['address'] = '';
      $returnarr[0]['month'] = '';
      $returnarr[0]['yr'] = '';
      $returnarr[0]['payorcompname'] = '';
      $returnarr[0]['payoraddress'] = '';
      $returnarr[0]['payorzipcode'] = '';
    } else {
      $returnarr[0]['payee'] = $result1[0]['clientname'];
      $returnarr[0]['tin'] = $result1[0]['tin'];
      $returnarr[0]['payortin'] = $result1[0]['payortin'];
      $returnarr[0]['address'] = $result1[0]['address'];
      $returnarr[0]['month'] = $result1[0]['month'];
      $returnarr[0]['yr'] = $result1[0]['yr'];
      $returnarr[0]['payorcompname'] = $result1[0]['payorcompname'];
      $returnarr[0]['payoraddress'] = $result1[0]['payoraddress'];
      $returnarr[0]['payorzipcode'] = $result1[0]['payorzipcode'];
    }

    $result = ['head' => $returnarr, 'detail' => $finalarrs, 'res' => $result1];
    return $this->reportplotting($config, $result);
  }

  public function reportplotting($config, $result)
  {
    $result = $this->DEFAULT_WITHHOLDINGTAX_REPORT($config, $result);
    return $result;
  }

  private function DEFAULT_WITHHOLDINGTAX_REPORT($filters, $data)
  {

    $str = '';
    $fontsize = '10';
    $count = 60;
    $page = 58;
    $font = $this->companysetup->getrptfont($filters['params']);
    $birlogo = URL::to('fimages/reports/birlogo.PNG');
    $birblogo = URL::to('fimages/reports/birbarcode.PNG');
    // col($txt='',$w=null,$h=null, $bg=false,  $b=false, $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m='',$len=0)

    if (empty($data)) {
      return $this->othersClass->emptydata($filters);
    }

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->endtable();
    $str .= '';


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('For BIR&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspBCS/<br/>Use Only&nbsp&nbsp&nbspItem:', '10', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('<img src ="' . $birlogo . '" alt="BIR" width="60px" height ="60px">', '10', null, false, '2px solid ', '', 'R', $font, '15', 'B', '', '');
    $str .= $this->reporter->col('Republic of the Philippines<br />Department of Finance<br />Bureau of Internal Revenue', '60', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '90', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('BIR Form No. <h4><b> 2307 </b></h4> January 2018 (ENCS)', '135', null, false, '2px solid ', 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '55', null, false, '2px solid ', 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Certificate of Creditable Tax <br> Withheld At Source', '450', null, false, '2px solid ', 'RTB', 'C', $font, '16', 'B', '', '');


    $str .= $this->reporter->col('<img src ="' . $birblogo . '" alt="BIR" width="200px" height ="50px">', '130', null, false, '2px solid ', 'TB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', 'RTB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Fill in all applicable spaces. Mark all appropriate boxes with an "X"', '100', null, false, '2px solid ', 'LRTB', 'L', $font, '9', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('1', '40', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('For the Period', '120', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '70', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('From', '70', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');


    switch ($data['head'][0]['month']) {
      case '1':
      case '2':
      case '3':
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');


        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('03', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        break;

      case '4':
      case '5':
      case '6':
        $str .= $this->reporter->col('04', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');


        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', $font, $fontsize, '', '', '');

        $str .= $this->reporter->col('06', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        break;

      case '7':
      case '8':
      case '9':
        $str .= $this->reporter->col('07', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', $font, $fontsize, '', '', '3px');

        $str .= $this->reporter->col('09', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('30', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        break;

      default:
        $str .= $this->reporter->col('10', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('01', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');

        $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid', 'LR', 'L', $font, $fontsize, '', '', '3px');

        $str .= $this->reporter->col('12', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col('31', '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        $str .= $this->reporter->col((isset($data['head'][0]['yr']) ? $data['head'][0]['yr'] : ''), '10', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
        break;
    }

    $str .= $this->reporter->col('(MM/DD/YY)', '270', null, false, '2px solid ', 'R', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part I-Payee Information', '800', null, false, '2px solid ', 'TLBR', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('2', '20', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '3px');


    $str .= $this->reporter->col((isset($data['head'][0]['tin']) ? $data['head'][0]['tin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'L', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('3', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Payee`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->col((isset($data['head'][0]['payee']) ? $data['head'][0]['payee'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('4', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '3px');

    $str .= $this->reporter->col('4A', '10', null, false, '2px solid', '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->col((isset($data['head'][0]['address']) ? $data['head'][0]['address'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data['res'][0]['zipcode']) ? $data['res'][0]['zipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('5', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Foreign Address, <i>if applicable <i/>", '610', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->col('', '760', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, 'B', '', '10px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part II-Payor Information', '800', null, false, '2px solid ', 'TLBR', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'T', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('6', '20', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Tax Payer Identification Number (TIN)', '150', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '3px');


    $str .= $this->reporter->col((isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), '400', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('7', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Payor`s Name <i>(Last Name, First Name, Middle Name for Individual or Registered Name for Non-Individual)</i>", '610', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '3px');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'L', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->col((isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : ''), '760', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, 'B', '', '3px');

    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('8', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col("Registered Address", '610', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '50', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('8A', '10', null, false, '2px solid', '', 'L', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('Zipcode', '10', null, false, '2px solid ', 'R', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '30', null, false, '2px solid ', 'L', 'C', $font, $fontsize, 'B', '', '');


    $str .= $this->reporter->col((isset($data['head'][0]['payoraddress']) ? $data['head'][0]['payoraddress'] : ''), '620', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data['head'][0]['payorzipcode']) ? $data['head'][0]['payorzipcode'] : ''), '50', null, false, '2px solid ', 'LRTB', 'C', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '2px', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '125', null, false, '2px solid ', 'LB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '180', null, false, '2px solid ', 'B', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '300', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '195', null, false, '2px solid ', 'RB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Part III-Details of Monthly Income Payments and Taxes Withheld', '800', null, false, '2px solid ', 'TLBR', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRT', 'C', $font, $fontsize, '', '', '3px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRT', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('AMOUNT OF INCOME PAYMENTS', '380', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Income Payments Subject to Expanded Withholding Tax', '200', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('ATC', '80', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('1st Month of the Quarter', '95', null, false, '2px solid', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('2nd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('3rd Month of the Quarter', '95', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Total', '95', null, false, '2px solid', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Tax Withheld For the Quarter', '140', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LR', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LR', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('', '800', null, false, '2px solid ', 'LTRB', 'C', $font, '12', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');

    $total = 0;
    $totalwtx1 = 0;
    $totalwtx2 = 0;
    $totalwtx3 = 0;
    $totalwtx = 0;
    $a = -1;
    foreach ($data['detail'] as $key => $value) {
      $a++;
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data['res'][$a]['ewtdesc'], '200', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($key, '80', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');

      switch ($data['detail'][$key]['month']) {
        case '1':
        case '4':
        case '7':
        case '10':
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid', 'LRB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, '', '', '');
          $totalwtx1 +=  $data['detail'][$key]['oamt'];
          break;
        case '2':
        case '5':
        case '8':
        case '11':
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, '', '', '');
          $totalwtx2 +=  $data['detail'][$key]['oamt'];
          break;
        default:
          $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, '', '', '');
          $str .= $this->reporter->col(number_format($data['detail'][$key]['oamt'], 2), '95', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, '', '', '');
          $totalwtx3 +=  $data['detail'][$key]['oamt'];
          break;
      }
      $total = number_format($data['detail'][$key]['oamt'], 2);
      $str .= $this->reporter->col($total, '95', null, false, '2px solid', 'LRB', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col(number_format($data['detail'][$key]['xamt'], 2), '140', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, '', '', '');

      $totalwtx += $data['detail'][$key]['oamt'];
    }
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $totaltax = 0;

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LR', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LR', 'R', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(($totalwtx1 != 0 ? number_format($totalwtx1, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($totalwtx2 != 0 ? number_format($totalwtx2, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($totalwtx3 != 0 ? number_format($totalwtx3, 2) : ''), '95', null, false, '2px solid ', 'LR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(($totalwtx != 0 ? number_format($totalwtx, 2) : ''), '95', null, false, '2px solid', 'LR', 'R', $font, $fontsize, 'B', '', '');

    foreach ($data['detail'] as $key2 => $value2) {

      $totaltax = $totaltax + $data['detail'][$key2]['xamt'];
    }

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LR', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Money Payments Subjects to Withholding of Business Tax (Government & Private)', '200', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '200', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '10px');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '140', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Total', '200', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '80', null, false, '2px solid ', 'LRB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '95', null, false, '2px solid', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col(number_format($totaltax, 2), '140', null, false, '2px solid ', 'LRB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('We declare, under the penalties of perjury, that this certificate has been made in good faith, verified by us, and to the best of our knowledge and belief, is true and correct, pursuant to the provisions of the National Internal Revenue Code, as amended, and the regulations issued under authority thereof. Further, we give our consent  to the processing of our information as contemplated under  the *Data Privacy Act of 2012 (R.A. No. 10173) for legitimate and lawful  purposes.', '800', null, false, '2px solid ', 'LRT', 'C', $font, $fontsize, '', '', '3px');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col((isset($data['head'][0]['payorcompname']) ? $data['head'][0]['payorcompname'] : ''), '395', null, false, '2px solid ', '', 'C', $font, $fontsize, 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col((isset($data['head'][0]['payortin']) ? $data['head'][0]['payortin'] : ''), '175', null, false, '2px solid ', '', 'C', $font, $fontsize, 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col(ucwords($filters['params']['dataparams']['position']), '175', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payor/Payor`s Authorized Representative/Tax Agent
          <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
            Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();



    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('CONFORME:', '800', null, false, '2px solid ', 'TLBR', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', '', $font, $fontsize, 'B', '', '3px');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', $font, $fontsize, 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', $font, $fontsize, 'B', '', '13px');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', $font, $fontsize, 'B', '', '13px');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LT', '', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'T', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'T', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'T', 'C', $font, $fontsize, 'B', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RT', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Signature over Printed Name of Payee/Payee`s Authorized Representative/Tax Agent
          <br/>(Indicate Title/Designation and TIN)', '800', null, false, '2px solid ', 'LRB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'L', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', '', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Tax Agent Accreditation No./<br/>
              Attorney`s Roll No. (if applicable)', '150', null, false, '2px solid ', 'L', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '5', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '120', null, false, '2px solid', 'LRTB', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('Date of Issue<br/>(MM/DD/YYY)', '10', null, false, '2px solid ', '', 'L', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('Date of Expiry<br/>(MM/DD/YYYY)', '10', null, false, '2px solid ', '', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid ', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '20', null, false, '2px solid', 'LTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '30', null, false, '2px solid', 'LRTB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'R', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '10', null, false, '2px solid ', 'LB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '395', null, false, '2px solid ', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid ', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'B', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '175', null, false, '2px solid', 'B', 'C', $font, $fontsize, '', '', '');

    $str .= $this->reporter->col('', '15', null, false, '2px solid ', 'RB', 'C', $font, $fontsize, '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}
