<?php

namespace App\Http\Classes\modules\reportlist\construction_reports;

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

class subcontractor_report
{
  public $modulename = 'Subcontractor Report';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'legal', 'layoutSize' => '1400'];


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

    $fields = ['radioprint', 'start', 'end', 'dclientname', 'dprojectname', 'optionstatus'];

    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'dclientname.lookupclass', 'subsupplier');


    $options = array(
      ['label' => 'Unposted', 'value' => 'Unposted', 'color' => 'blue'],
      ['label' => 'Posted', 'value' => 'Posted', 'color' => 'blue'],
      ['label' => 'All', 'value' => 'All', 'color' => 'blue']
    );

    data_set($col1, "optionstatus.options", $options);

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);


    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
      'default' as print,
       adddate(left(now(),10),-360) as start, date_add(date(now()),interval 1 month) as end,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '' as dprojectname, '' as projectname,0 as projectid,
       '' as projectcode,
      'All' as status
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
    $companyid = $config['params']['companyid'];

    switch ($companyid) {
      case 8: //maxipro
        return $this->report_MaxiproLayout($config);
        break;
    }
  }

  public function reportMaxiproQry($config)
  {
    // QUERY

    $client     = $config['params']['dataparams']['client'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));

    $status = $config['params']['dataparams']['status'];
    $projectname = $config['params']['dataparams']['projectname'];

    $projectid = 0;
    if ($projectname != '') {
      $projectid = $config['params']['dataparams']['projectid'];
    }

    $filter = "";

    if ($client != '') {
      $filter .= " and client.client = '" . $client . "' ";
    }

    if ($projectid != 0) {
      $filter .= " and detail.projectid = " . $projectid . " ";
    }

    switch ($status) {
      case 'Unposted':
        $qry = "select client.clientname,head.dateid,head.docno,coa.acnoname,detail.db,0 as deductedamt,0 as arbal,detail.refx,detail.linex,
                      ifnull((select hh.docno from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex),'') as apv,
                      ifnull((select hh.dateid from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex),'') as apvdate,
                       ifnull((select sum(deets.db) from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx),0) as apvamt
              from lahead as head
              left join ladetail as detail on detail.trno=head.trno
              left join client on client.client=head.client
              left join coa on coa.acnoid=detail.acnoid
              where client.iscontractor = 1 and head.doc in ('GJ','MI','CV') and left(coa.alias,2) not in ('CB','IN') and detail.acnoid <> 5352 and head.dateid between '$start' and '$end' $filter
              order by clientname,dateid,docno,acnoname,apv";
        break;
      case 'Posted':
        $qry = "select client.clientname,head.dateid,head.docno,coa.acnoname,detail.db,0 as deductedamt,0 as arbal,detail.refx,detail.linex,
                      ifnull((select hh.docno from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex),'') as apv,
                      ifnull((select hh.dateid from glhead as hh left join gldetail as deets on hh.trno=deets.trno 
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex),'') as apvdate,
                      ifnull((select sum(deets.db) from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx),0) as apvamt
              from glhead as head
              left join gldetail as detail on detail.trno=head.trno
              left join client on client.clientid=head.clientid
              left join coa on coa.acnoid=detail.acnoid
              where client.iscontractor = 1 and head.doc in ('GJ','MI','CV') 
                    and left(coa.alias,2) not in ('CB','IN') and detail.acnoid <> 5352 
                    and head.dateid between '$start' and '$end' $filter
              order by clientname,dateid,docno,acnoname,apv";
        break;
      default:
        $qry = "select client.clientname,head.dateid,head.docno,coa.acnoname,detail.db,0 as deductedamt,0 as arbal,detail.refx,detail.linex,
                      ifnull((select hh.docno from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex),'') as apv,
                      ifnull((select hh.dateid from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex),'') as apvdate,
                      ifnull((select sum(deets.db) from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx),0) as apvamt
                from lahead as head
                left join ladetail as detail on detail.trno=head.trno
                left join client on client.client=head.client
                left join coa on coa.acnoid=detail.acnoid
                where client.iscontractor = 1 and head.doc in ('GJ','MI','CV') and left(coa.alias,2) not in ('CB','IN') and detail.acnoid <> 5352
                 and head.dateid between '$start' and '$end' $filter
                union all
                select client.clientname,head.dateid,head.docno,coa.acnoname,detail.db,0 as deductedamt,0 as arbal,detail.refx,detail.linex,
                      ifnull((select hh.docno from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex),'') as apv,
                      ifnull((select hh.dateid from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx and deets.line=detail.linex ),'') as apvdate,
                      ifnull((select sum(deets.db) from glhead as hh left join gldetail as deets on hh.trno=deets.trno
                       where hh.doc='PV' and deets.trno=detail.refx),0) as apvamt
                from glhead as head
                left join gldetail as detail on detail.trno=head.trno
                left join client on client.clientid=head.clientid
                left join coa on coa.acnoid=detail.acnoid
                where client.iscontractor = 1 and head.doc in ('GJ','MI','CV') and left(coa.alias,2) not in ('CB','IN') and detail.acnoid <> 5352
                and head.dateid between '$start' and '$end' $filter
                order by clientname,dateid,docno,acnoname,apv";
        break;
    }

    $data = $this->coreFunctions->opentable($qry);
    return $data;
  }

  private function displayHeaderMaxiPro($config)
  {
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $client     = $config['params']['dataparams']['client'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $report     = $config['params']['dataparams']['status'];

    $project = $config['params']['dataparams']['projectname'];

    if ($client == '') {
      $client = 'ALL';
    }

    if ($project == '') {
      $project = 'ALL';
    }
    switch ($report) {
      case 'Unposted':
        $report = 'UNPOSTED';
        break;
      case 'Posted':
        $report = 'POSTED';
        break;
      default:
        $report = 'ALL';
        break;
    }

    $str = '';
    $layoutsize = '1300';
    $font = "Century Gothic";
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
    $str .= $this->reporter->col('Accounts Receivable - Subcontractor Report(Detailed/Summary)', null, null, false, '10px solid ', '', '', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date: ' . date('m/d/Y', strtotime($start)) . ' to ' . date('m/d/Y', strtotime($end)), null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier: ' . $client, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Transaction Type: ' . $report, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Project: ' . $project, null, null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endtable();


    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();

    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Supplier', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CV/MI/GJ Doc. Date', '150', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('CV/MI/GJ Document #', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Particular', '200', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Amount', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('Deducted Amount', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APV', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('APV Date', '120', null, false, $border, 'TB', 'C', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('AR Bal', '120', null, false, $border, 'TB', 'R', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= '<br/>';
    return $str;
  }

  public function report_MaxiproLayout($config)
  {
    $result = $this->reportMaxiproQry($config);

    $count = 32;
    $page = 32;
    $layoutsize = '1400';
    $font = "Century Gothic";
    $fontsize ="10";
    $border = "1px solid ";
    $bankcharge = 0;
    $fines = 0;
    $bank = '';
    $status = $config['params']['dataparams']['status'];
    $arbal = 0;
    $amt = 0;

    $totalamt = 0;
    $totaldb = 0;
    $totalarbal = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeaderMaxiPro($config);
    $clientname = '';
    foreach ($result as $key => $data) {


      if ($data->apv != "") {
        $amt = $data->apvamt;
      } else {
        if ($data->refx == 0) {
          $amt = $data->db;
        }
      }

      $arbal = $amt - $data->db;

      if ($clientname == '' || $clientname != $data->clientname) {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col($data->clientname, '150', null, false, $border, 'T', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '150', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '200', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col('', '120', null, false, $border, 'T', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->dateid, '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->acnoname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($amt, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->apv, '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->apvdate, '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($arbal, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');


        $str .= $this->reporter->endrow();
      } else {
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('', '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->dateid, '150', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->docno, '200', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->acnoname, '200', null, false, $border, '', 'L', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($amt, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($data->db, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->apv, '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col($data->apvdate, '120', null, false, $border, '', 'C', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->col(number_format($arbal, 2), '120', null, false, $border, '', 'R', $font, $fontsize, 'B', '', '');
        $str .= $this->reporter->endrow();
      }
      $clientname = $data->clientname;

      $totalamt = $totalamt + $amt;
      $totaldb = $totaldb + $data->db;
      $totalarbal = $totalarbal + $arbal;

      //remove paging
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeaderMaxiPro($config);
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->begintable($layoutsize);
        $page = $page + $count;
      }
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '150', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('', '200', null, false, $border, 'TB', 'RT', $font, $fontsize, '', '', '');
    $str .= $this->reporter->col('GRAND TOTAL: ', '200', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalamt, 2), '120', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '120', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col('', '120', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->col(number_format($totalarbal, 2), '120', null, false, $border, 'TB', 'RT', $font, $fontsize, 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }
}//end class