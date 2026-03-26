<?php

namespace App\Http\Classes\modules\customform;

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

class viewincentivesdealer
{
  private $fieldClass;
  private $tabClass;
  public $modulename = 'DEALER CUSTOMER';
  public $gridname = 'tableentry';
  private $companysetup;
  private $coreFunctions;
  private $othersClass;
  public $style = 'width:100%';
  public $issearchshow = true;
  public $showclosebtn = false;

  private $reporter;

  public function __construct()
  {
    $this->fieldClass = new txtfieldClass;
    $this->tabClass = new tabClass;
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->reporter = new SBCPDF;
  }

  public function getAttrib()
  {
    $attrib = array('load' => 2518, 'view' => 2518);
    return $attrib;
  }

  public function createTab($config)
  {
    $tab = [
      'tableentry' => ['action' => 'warehousingentry', 'lookupclass' => 'viewgridincentivesdealer', 'label' => 'LIST']
    ];
    $stockbuttons = [];
    $obj = $this->tabClass->createtab($tab, $stockbuttons);
    return $obj;
  }

  public function createtabbutton($config)
  {
    $tbuttons = [];
    $obj = $this->tabClass->createtabbutton($tbuttons);
    return $obj;
  }

  public function createHeadField($config)
  {
    $fields = [['start', 'end'], 'clientname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, "clientname.type", "lookup");
    data_set($col1, "clientname.action", "lookupclient");
    data_set($col1, "clientname.lookupclass", "customerdealer");

    $fields = ['refresh'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, "refresh.label", "GENERATE");
    data_set($col2, "refresh.action", "load");

    $fields = ['radionincentivestatus', 'print'];
    $col3 = $this->fieldClass->create($fields);
    $options = array(
      ['label' => 'Draft', 'value' => '0', 'color' => 'red'],
      ['label' => 'Released', 'value' => '1', 'color' => 'red'],
      ['label' => 'All', 'value' => '2', 'color' => 'red']
    );
    data_set($col3, "radionincentivestatus.options", $options);



    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $start = "DATE_FORMAT(NOW() ,'%Y-01-01')";
    $end = "curdate()";
    $clientid = 0;
    $client = '';
    $clientname = '';
    $printoption = '2';
    $agrelease = "''";

    if (isset($config['params']['dataparams'])) {
      if ($config['params']['dataparams']['start'] == null) {
        $start = "null";
      } else {
        $start = "date('" . $config['params']['dataparams']['start'] . "')";
      }

      if ($config['params']['dataparams']['end'] == null) {
        $end = "null";
      } else {
        $end = "date('" . $config['params']['dataparams']['end'] . "')";
      }

      $clientid = $config['params']['dataparams']['clientid'];
      $client = $config['params']['dataparams']['client'];
      $clientname = $config['params']['dataparams']['clientname'];
      $printoption = $config['params']['dataparams']['incentivestatus'];
    }

    $qry = "select " . $start . " as `start`, " . $end . " as `end`, '" . $client . "' as client, " . $clientid . " as clientid, '" . $clientname . "' as clientname, 
    '" . $printoption . "' as incentivestatus," . $agrelease  . " as agrelease";
    return $this->coreFunctions->opentable($qry);
  }

  public function data()
  {
    return [];
  }

  public function loaddata($config)
  {
    $start = date('Y-m-d', strtotime($config['params']['dataparams']['start']));
    $end = date('Y-m-d', strtotime($config['params']['dataparams']['end']));
    $clientid = $config['params']['dataparams']['clientid'];

    $return = [];

    $filterclient = '';
    if ($clientid != 0) {
      $filterclient = " and i.clientid=" . $clientid;
    }

    $qry = "select i.ptrno, i.trno, i.line, i.acnoid, ar.docno, i.amt, i.clientid, client.clientname,
          client.quota as clientquota, client.comm as clientcom, i.clientcomamt, 'false' as added, '" . $end . "' as releasedate, i.clientrelease
          from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
          left join client on client.clientid=i.clientid
          where i.doc='SD' and date(i.depodate) between ? and ? " . $filterclient;
    $data = $this->coreFunctions->opentable($qry, [$start, $end]);

    foreach ($data as $key => $value) {

      $commamt = $value->amt * ($value->clientcom / 100);

      $row = [
        'clientquota' => $value->clientquota,
        'clientcom' => $value->clientcom,
        'clientcomamt' => $commamt
      ];

      $this->coreFunctions->sbcupdate("incentives", $row, ["trno" => $value->trno, "line" => $value->line]);
    }

    $qry = "select i.clientid, client.clientname, FORMAT(sum(i.amt),2) as amt, 
    FORMAT(i.clientquota,2) as clientquota, i.clientcom, FORMAT(sum(i.clientcomamt),2) as clientcomamt, 
    'false' as added, '" . $start . "' as startdate, '" . $end . "' as releasedate, 'false' as isquota
    from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
    left join client on client.clientid=i.clientid
    where i.doc='SD' and date(i.depodate) between ? and ? " . $filterclient . "
    group by i.clientid, client.clientname, i.clientquota, i.clientcom";

    $return = $this->coreFunctions->opentable($qry, [$start, $end]);

    foreach ($return as $key => $value) {
      if ($value->amt < $value->clientquota) {
        $value->clientcomamt = number_format(0, 2);
      } else {
        $value->isquota = 'true';
      }
    }

    $txtdata = $this->paramsdata($config);

    return ['status' => true, 'msg' => 'Successfully loaded.', 'tableentrydata' => $return, 'txtdata' => $txtdata];
  }

  public function reportsetup($config)
  {
    $center = $config['params']['center'];
    $username = $config['params']['user'];
    $companyid = $config['params']['companyid'];

    $style = 'width:500px;max-width:500px;';

    $border = "1px solid ";
    $font =  "Century Gothic";
    $fontsize = "11";

    $type = $config['params']['dataparams']['incentivestatus'];
    if ($type == "") {
      return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => 'Please select valid print option', 'style' => $style, 'directprint' => true];
    }

    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];

    $option = 'Format: &nbsp;ALL';
    switch ($type) {
      case '0':
        $option = 'Format: &nbsp;DRAFT';
        break;

      case '1':
        $option = 'Format: &nbsp;RELEASES';
        break;
    }


    $decimal = $this->companysetup->getdecimal('currency', $config['params']);

    $str = '';

    $str .= $this->reporter->beginreport();
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->letterhead($center, $username);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("DEALER CUSTOMER", '580', null, false, $border, '', 'L', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col("Date Covered: " . date_format(date_create($start), "m/d/Y") . " to " . date_format(date_create($end), "m/d/Y"), '580', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($option, '220', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Name', '200', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Quota', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Incentive %', '90', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Incentive Amt', '100', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('&nbsp;&nbsp;Released Date', '140', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Released By', '70', null, false, $border, 'TB', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $result = $this->getdata($config);

    $prev_agent = '';
    $counter = 0;
    $totalamt = 0;
    $totalcom = 0;

    $gtotalamt = 0;
    $gtotalcom = 0;

    foreach ($result as $key => $value) {

      if ($prev_agent != '') {
        if ($prev_agent != $value->clientname) {
          SubtotalHere:

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($totalamt != 0 ? number_format($totalamt, $decimal) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($totalcom != 0 ? number_format($totalcom, $decimal) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '140', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          $totalamt = 0;
          $totalcom = 0;

          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col('<br>', '250', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->endrow();

          if ($counter >= count($result)) {
            break;
          }
        }
      }

      $str .= $this->reporter->startrow();
      $name = $value->clientname;
      $quota = $value->clientquota != 0 ? number_format($value->clientquota, $decimal) : '-';
      if ($prev_agent == $value->clientname) {
        $name = '';
        $quota = '';
      }
      $str .= $this->reporter->col($name, '200', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($quota, '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->amt != 0 ? number_format($value->amt, $decimal) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->clientcom != 0 ? number_format($value->clientcom, $decimal) : '-', '90', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->clientcomamt != 0 ? number_format($value->clientcomamt, $decimal) : '-', '100', null, false, $border, '', 'R', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col('&nbsp;&nbsp;' . $value->released, '140', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->col($value->releaseby, '70', null, false, $border, '', 'L', $font, $fontsize, '', '', '');
      $str .= $this->reporter->endrow();

      $totalamt = $totalamt + $value->amt;
      $totalcom = $totalcom + $value->clientcomamt;

      $gtotalamt = $gtotalamt + $value->amt;
      $gtotalcom = $gtotalcom + $value->clientcomamt;

      $prev_agent = $value->clientname;
      $counter = $counter + 1;

      if ($counter >= count($result)) {
        goto SubtotalHere;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL', '200', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($gtotalamt != 0 ? number_format($gtotalamt, $decimal) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '90', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col($gtotalcom != 0 ? number_format($gtotalcom, $decimal) : '-', '100', null, false, $border, 'T', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '140', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '70', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return ['status' => true, 'msg' => 'Successfully loaded.', 'report' => $str, 'style' => $style, 'directprint' => true];
  }

  private function getdata($config)
  {
    $start = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['start']);
    $end = $this->othersClass->sanitizekeyfield('dateid', $config['params']['dataparams']['end']);
    $status = $config['params']['dataparams']['incentivestatus'];

    switch ($status) {
      case "0":
        $status = ' and i.clientrelease is null';
        break;

      case "1":
        $status = ' and i.clientrelease is not null';
        break;

      default:
        $status = '';
        break;
    }

    $qry = "select i.ptrno, i.trno, i.line, i.acnoid, ar.docno, i.amt, i.clientid, client.clientname, 'false' as isselected,
    client.quota as clientquota, client.comm as clientcom, FORMAT(i.clientcomamt,2) as clientcomamt, 
    i.clientrelease as released, i.clientreleaseby as releaseby
    from incentives as i left join arledger as ar on ar.trno=i.trno and ar.line=i.line 
    left join client on client.clientid=i.clientid
    where i.doc='SD' and date(i.depodate) between ? and ? " . $status . " order by clientname";

    return $this->coreFunctions->opentable($qry, [$start, $end]);
  }
} //end class
