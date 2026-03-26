<?php

namespace App\Http\Classes\modules\reportlist\payroll_other_report;

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

class earning_and_deduction_transaction_history
{
  public $modulename = 'Earning and Deduction Transaction History';
  private $companysetup;
  private $coreFunctions;
  private $fieldClass;
  private $othersClass;
  private $reporter;
  public $month;
  public $year;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;

  public $reportParams = ['orientation' => 'p', 'format' => 'letter', 'layoutSize' => '800'];

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
    $fields = ['radioprint', 'dclientname', 'repearnded'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dclientname.lookupclass', 'lookupemployee');
    data_set($col1, 'dclientname.label', 'Employee');
    data_set($col1, 'dclientname.required', true);
    data_set($col1, 'repearnded.lookupclass', 'lookupearndedrpt');
    data_set($col1, 'repearnded.required', true);

    $fields = ['start', 'end', 'print'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'start.required', true);
    data_set($col2, 'end.required', true);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    return $this->coreFunctions->opentable("select 
      'default' as print,
      '' as client,
      '' as clientname,
      '' as dclientname,
      '' as dclientname,
      '' as earndedid,
      '' as earnded,
      adddate(left(now(),10),-360) as start,
      left(now(),10) as end
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
    $center = $config['params']['center'];
    $username = $config['params']['user'];

    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    $client     = $config['params']['dataparams']['client'];
    $earndedid = $config['params']['dataparams']['earndedid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $companyid = $config['params']['companyid'];
    $docno = "case when b.batch is null then (concat(left(ss.docno,3),right(ss.docno,6))) else b.batch end";
    if($companyid == 28){
      $docno = "case when b.batch is null then case when ismanual = 1 then st.docno else '' end else b.batch end";
    }
    $query = "      
      select x.dateid,x.docno,x.remarks,x.cr,x.db from (
        select date(ss.dateid) as dateid, concat(left(ss.docno,3),right(ss.docno,6)) as docno, ss.remarks, 0 as cr, ss.amt as db
        from standardsetup as ss
        left join paccount as pa on pa.line=ss.acnoid
        left join client as emp on emp.clientid=ss.empid
        where date(ss.dateid) between '$start' and '$end' and emp.client = '$client' and pa.line='$earndedid'
        
        union all
        select date(ss.dateid) as dateid, concat(left(ss.docno,3),right(ss.docno,6)) as docno, ss.remarks, 0 as cr, ss.amt as db
        from standardsetupadv as ss
        left join paccount as pa on pa.line=ss.acnoid
        left join client as emp on emp.clientid=ss.empid
        where date(ss.dateid) between '$start' and '$end' and emp.client = '$client' and pa.line='$earndedid'

        union all
        
        select date(st.dateid) as dateid, 
        $docno as docno,
        concat(date(b.startdate),' to ',date(b.enddate)) as remarks,sum(st.cr) as cr, 0 as db
        from standardsetup as ss
        left join standardtrans as st on st.trno=ss.trno
        left join batch as b on b.line=st.batchid
        left join paccount as pa on pa.line=ss.acnoid
        left join client as emp on emp.clientid=ss.empid
        where date(ss.dateid) between '$start' and '$end' and emp.client = '$client' and pa.line='$earndedid'
        group by st.dateid, b.batch,st.docno, st.ismanual, ss.remarks,st.db,b.startdate,b.enddate

        union all
        
        select date(st.dateid) as dateid, 
        $docno as docno,
        concat(date(b.startdate),' to ',date(b.enddate)) as remarks,sum(st.cr) as cr, 0 as db
        from standardsetupadv as ss
        left join standardtransadv as st on st.trno=ss.trno
        left join batch as b on b.line=st.batchid
        left join paccount as pa on pa.line=ss.acnoid
        left join client as emp on emp.clientid=ss.empid
        where date(ss.dateid) between '$start' and '$end' and emp.client = '$client' and pa.line='$earndedid'
        group by st.dateid, b.batch,st.docno, st.ismanual, ss.remarks,st.db,b.startdate,b.enddate        
      ) as x

      order by dateid, docno";

    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $border = '1px dotted';

    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $client     = $config['params']['dataparams']['dclientname'];
    $earnded = $config['params']['dataparams']['earnded'];
    $start = $config['params']['dataparams']['start'];
    $end = $config['params']['dataparams']['end'];

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];

    $str = '';


    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('EARNING/DEDUCTION TRANSACTION HISTORY', null, null, false, $border, '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date From: <b>' . $start . '</b> To: <b>' . $end . '</b>', null, null, false, $border, '', 'C', $font, '13', '', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Earning/Deduction : ' . $earnded, null, null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Employee : ' . $client, null, null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', '125', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DOC NO', '125', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('REMARKS', '200', null, false, $border, 'TB', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('DEBIT', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('CREDIT', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('BALANCE', '75', null, false, $border, 'TB', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);


    $border = '1px solid';

    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $count = 55;
    $page = 55;

    $client     = $config['params']['dataparams']['client'];
    $earndedid = $config['params']['dataparams']['earndedid'];
    $start = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end = date("Y-m-d", strtotime($config['params']['dataparams']['end']));


    $str = '';
    $begbal = 0;
    $totaldb = 0;
    $totalcr = 0;


    $str .= $this->reporter->beginreport($this->reportParams['layoutSize']);
    $str .= $this->displayHeader($config);

    $counter = 0;
    $str .= $this->reporter->begintable($this->reportParams['layoutSize']);


    // $balqry = "select pa.line,emp.client,sum(ss.amt) as bal
    // from standardsetupadv as ss
    // left join paccount as pa on pa.line=ss.acnoid
    // left join client as emp on emp.clientid=ss.empid
    // where pa.line=$earndedid and emp.client='$client' and date(ss.dateid)<'$start'
    // group by pa.line,emp.client";


    $balqry = "  select sum(db- cr) as bal from (
       select  0 as cr, ss.amt as db, date(ss.dateid) as dateid, ss.docno
        from standardsetupadv as ss
        left join paccount as pa on pa.line=ss.acnoid
        left join client as emp on emp.clientid=ss.empid
            where pa.line=$earndedid and emp.client='$client' and date(ss.dateid)<'$start'

        union all

        select st.cr as cr, 0 as db, date(ss.dateid) as dateid, ss.docno
        from standardsetupadv as ss
        left join standardtransadv as st on st.trno=ss.trno
        left join batch as b on b.line=st.batchid
        left join paccount as pa on pa.line=ss.acnoid
        left join client as emp on emp.clientid=ss.empid
            where pa.line=$earndedid and emp.client='$client' and date(st.dateid)<'$start'

        order by dateid
  ) as x";

    $balance = json_decode(json_encode($this->coreFunctions->opentable($balqry)), true);

    foreach ($result as $key => $data) {


      if ($counter == 0) {
        $counter++;
        $str .= $this->reporter->startrow();
        $str .= $this->reporter->addline();
        $str .= $this->reporter->col('Beginning Balance.', '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');

        $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');

        $begbal = (isset($balance[0]['bal']) ? $balance[0]['bal'] : 0);
        $str .= $this->reporter->col(number_format($begbal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
        $str .= $this->reporter->endrow();
      }
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->addline();
      $str .= $this->reporter->col($data->dateid, '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->docno, '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');

      $str .= $this->reporter->col($data->remarks, '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->db, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->cr, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $begbal += ($data->db - $data->cr);
      $totaldb += $data->db;
      $totalcr += $data->cr;
      $str .= $this->reporter->col(number_format($begbal, 2), '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();

      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config);
        $str .= $this->reporter->endrow();
        $str .= $this->reporter->begintable($this->reportParams['layoutSize']);
        $page = $page + $count;
      }
    }
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->addline();
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');
    $str .= $this->reporter->col('', '125', null, false, $border, '', 'C', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '200', null, false, $border, '', 'L', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totaldb, 2), '75', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->col(number_format($totalcr, 2), '75', null, false, $border, 'T', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('', '75', null, false, $border, '', 'R', $font, $font_size, '', '', '');
    $str .= $this->reporter->endrow();


    $str .= $this->reporter->endtable();
    $str .= $this->reporter->endreport();

    return $str;
  }
}//end class