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

class outstanding_customer_receivables
{
  public $modulename = 'Outstanding Customer Receivables';
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
    $fields = ['radioprint','asofdate','dclientname','dcentername'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'radioprint.options', [
        ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
    ]);
    data_set($col1, 'dclientname.lookupclass', 'lookupclient_rep');
    data_set($col1, 'dclientname.label', 'Customer');
    data_set($col1, 'dcentername.lookupclass', 'getmultibranch');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];
    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);
    $currentDate = $this->othersClass->getCurrentDate();
    return $this->coreFunctions->opentable("select 
    'default' as print,
      ' " . $currentDate ." ' as asofdate,
    '' as client,
    '' as dclientname, 
    '0' as clientid,
     '' as dcentername, 
     '' as center,
     '' as centername
    ");
  }

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
    $data = $this->default_query($config);
    return $this->report_default_detailed($config, $data);
  }

  public function default_query($config)
  {
    $dcenter = $config['params']['dataparams']['center'];
    $client       = $config['params']['dataparams']['client'];
    $clientid       = $config['params']['dataparams']['clientid'];
    $asof         = date('Y-m-d', strtotime($config['params']['dataparams']['asofdate']));

    $filter = "";
    if ($client != "") {
      $filter = " and client.clientid='$clientid'";
    }
    if ($dcenter != "0") {
      $filter .= " and cntnum.center='$dcenter'";
    }

    $query = "select client.clientname, concat(head.yourref, ' ', head.rem) as particular,
            date(detail.dateid) as dateid, detail.docno, datediff(now(), detail.dateid) as elapse,
            (case when detail.db>0 then detail.bal else (detail.bal*-1) end) as balance
            from (arledger as detail
            left join client on client.clientid=detail.clientid)
            left join cntnum on cntnum.trno=detail.trno
            left join glhead as head on head.trno=detail.trno
            left join gldetail as gdetail on gdetail.trno=detail.trno and gdetail.line=detail.line
            left join coa on coa.acnoid=gdetail.acnoid
            where detail.bal<>0 and left(coa.alias,2)='AR' and left(detail.docno,2) = 'DR' 
            and date(detail.dateid)<='$asof' $filter 
            group by client.clientname, detail.dateid, detail.docno, head.yourref, head.rem, detail.bal, detail.db
            order by detail.docno, client.clientname, detail.dateid";
            // var_dump($query);

    return $this->coreFunctions->opentable($query);
  }

  public function displayHeader($config, $recordCount)
    {
        $center     = $config['params']['center'];
        $username   = $config['params']['user'];
        $companyid  = $config['params']['companyid'];
        $asof       = $config['params']['dataparams']['asofdate'];


        $str      = '';
        $font     = 'Tahoma';
        $fontsize = "11";
        $border   = "1px solid ";
        $layoutsize = 1000;

        $qry = "select code,name,address,tel from center where code = '" . $center . "'";
        $headerdata = $this->coreFunctions->opentable($qry);

        $reporttimestamp = $this->reporter->setreporttimestamp($config, $username, $headerdata);
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($reporttimestamp, '1000', null, false, '', '', 'L', $font, $fontsize);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        // $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);   
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col( $this->coreFunctions->opentable($qry)[0]->name, null, null, false, '', '', 'C', $font, '14', 'B', '', '');
         $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col($this->coreFunctions->opentable($qry)[0]->address, null, null, false, '', '', 'C', $font, '14', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= '<br/>';

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('OUTSTANDING CUSTOMER RECEIVABLE', null, null, false, '10px solid ', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('AS OF ' . $asof, null, null, false, '', '', 'C', $font, '15', 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable();
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->pagenumber('Page', '980', null, false, $border, '', 'R', $font, $fontsize, '', '30px', '5px');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        $str .= $this->reporter->begintable($layoutsize);
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('CUSTOMER NAME','225', null, false, $border, 'B', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('','10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('PARTICULAR','250', null, false, $border, 'B',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('','10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DATE','130', null, false, $border, 'B',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('','10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DOC #','135', null, false, $border, 'B',  'C', $font, $fontsize, 'B');
        $str .= $this->reporter->col('','10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('DAYS','100', null, false, $border, 'B',  'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('','10', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('BALANCE DUE','110', null, false, $border, 'B',  'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->endtable();

        return $str;
    }

  public function report_default_detailed($config, $data)
  {
      $str        = '';
      $layoutsize = '1000';
      $font       = 'Tahoma';
      $fontsize   = '10';
      $border     = '1px solid';

      if (empty($data)) {
          return $this->othersClass->emptydata($config);
      }

      $str .= $this->reporter->beginreport($layoutsize);
      $str .= $this->displayHeader($config, count($data));

      $grandTotal = 0;
      $lastClient = ''; 

      foreach ($data as $row) {

          $balance     = floatval($row->balance);
          $grandTotal += $balance;

          $dateid = date("Y-m-d", strtotime($row->dateid));

          // $clientName = '';
          // if ($lastClient != $row->clientname) {
          //     $clientName = $row->clientname;
          //     $lastClient = $row->clientname;
          // }

          $str .= $this->reporter->begintable($layoutsize);
          $str .= $this->reporter->startrow();
          $str .= $this->reporter->col($row->clientname,'225', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col('','10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($row->particular,'250', null, false, $border, '', 'LT', $font, $fontsize, '',  '', '');
          $str .= $this->reporter->col('','10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($dateid,'130', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
          $str .= $this->reporter->col('','10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($row->docno,'135', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
          $str .= $this->reporter->col('','10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col($row->elapse,'100', null, false, $border, '', 'CT', $font, $fontsize, '',  '', '');
          $str .= $this->reporter->col('','10', null, false, $border, '', 'LT', $font, $fontsize, 'B', '', '');
          $str .= $this->reporter->col(number_format($balance, 2), '110', null, false, $border, '', 'RT', $font, $fontsize, '',  '', '');
          $str .= $this->reporter->endrow();
          $str .= $this->reporter->endtable();
      }

      // Grand total
      $str .= $this->reporter->begintable($layoutsize);
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('','150', null, false, '1px dotted', 'TB', 'LT', $font, $fontsize, '',  '', '');
      $str .= $this->reporter->col('GRAND TOTAL','605', null, false, '1px dotted', 'TB', 'LT', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->col(number_format($grandTotal, 2), '245', null, false, '1px dotted', 'TB', 'RT', $font, $fontsize, 'B', '', '');
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();

      $str .= $this->reporter->endreport();

      return $str;
  }



}//end of class