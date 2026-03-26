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
use Illuminate\Support\Facades\URL;


class paid_accounts_report
{
  public $modulename = 'Paid Accounts Report';
  public $companysetup;
  public $coreFunctions;
  public $fieldClass;
  public $othersClass;
  public $reporter;
  public $style = 'width:1200px;max-width:1200px;';
  public $directprint = false;
  public $reportParams = ['orientation' => 'l', 'format' => 'letter', 'layoutSize' => '1200'];


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
    $fields = ['radioprint', 'start', 'end', 'pref', 'seqstart', 'client'];
    $col1 = $this->fieldClass->create($fields);

    data_set($col1, 'pref.type', 'lookup');
    data_set($col1, 'pref.readonly', true);
    data_set($col1, 'pref.class', 'csprefix sbccsreadonly');
    data_set($col1, 'pref.lookupclass', 'lookupprefix');
    data_set($col1, 'pref.action', 'lookupprefix');

    data_set($col1, 'seqstart.label', 'Docno Series #');


    data_set($col1, 'client.type', 'lookup');
    data_set($col1, 'client.readonly', true);
    data_set($col1, 'client.class', 'csclient sbccsreadonly');
    data_set($col1, 'client.lookupclass', 'supplier');
    data_set($col1, 'client.action', 'lookupclient');
    data_set($col1, 'client.required', false);




    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);



    return array('col1' => $col1, 'col' => $col3);
  }


  public function paramsdata($config)
  {
    $companyid = $config['params']['companyid'];
    $center = $config['params']['center'];

    $defaultcenter = json_decode(json_encode($this->coreFunctions->opentable("select code as center,name as centername,concat(code,'~',name) as dcentername from center where code='$center'")), true);


    $paramstr = "select 
            'default' as print,
             adddate(left(now(),10),-360) as start,left(now(),10) as end,
            '' as clientid,
            '' as client,
            '0' as posttype,
            '' as dclientname,
            '' as dagentname,
            '' as agent,
            '' as agentname,
            '' as agentid,
            '' as docno,
            '' as seqstart,
            '' as pref";

    return $this->coreFunctions->opentable($paramstr);
  }
  public function getloaddata($config)
  {
    return [];
  }

  public function reportdata($config)
  {
    $companyid = $config['params']['companyid'];
    $str = $this->reportplotting($config);
    return ['status' => true, 'msg' => 'Generating report successfully.', 'report' => $str, 'params' => $this->reportParams];
  }
  public function reportplotting($config)
  {
    $center   = $config['params']['center'];
    $username = $config['params']['user'];
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end     = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prefix    = $config['params']['dataparams']['pref'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $client = $config['params']['dataparams']['client'];

    return $this->reportDefaultLayout($config);
  }
  public function reportDefault($config)
  {

    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end     = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $prefix    = $config['params']['dataparams']['pref'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $client = $config['params']['dataparams']['client'];
    $filter = "";

    $filter1 = "";

    if ($prefix != '') {
      $filter .= " and num.bref like '%$prefix%' ";
    }
    if ($seqstart != '') {
      $pos = strcspn($seqstart, '0123456789');
      $seqs = (int) substr($seqstart, $pos);
      $filter .= " and num.docno like '%$seqs%' ";
    }
    if ($client != '') {
      $filter .= " and client.client = '$client' ";
    }

    if ($client != '') {
      $filter1 .= " and client.client = '$client' ";
    }
    if ($prefix != '') {
      $filter1 .= " and num.bref like '%$prefix%' ";
    }



    $query = "select num.bref,num.seq,date(head.dateid) as dateid, head.docno, client.client, client.clientname, ap.db, ap.cr, detail.rem, '' AS ref, 
              date(head.dateid) AS refdate, 0 AS refdb, 0 AS refcr, detail.trno, detail.line, 0 AS reftrno, c.acnoname
              FROM apledger AS ap 
              LEFT JOIN glhead AS head ON head.trno = ap.trno 
              LEFT JOIN client ON client.clientid = ap.clientid
              LEFT JOIN gldetail AS detail ON detail.trno = ap.trno AND detail.line = ap.line 
              LEFT JOIN cntnum AS num ON head.trno = num.trno
              LEFT JOIN coa AS c ON c.acnoid=detail.acnoid
              WHERE date(head.dateid) between '$start' and '$end' $filter
              UNION ALL
              SELECT num.bref,num.seq,date(head.dateid) as dateid, head.docno, client.client, client.clientname, ap.db, ap.cr, detail.rem, '' AS ref, 
              date(head.dateid) AS refdate, 0 AS refdb, 0 AS refcr, detail.trno, detail.line, 0 AS reftrno, c.acnoname
              FROM arledger AS ap 
              LEFT JOIN glhead AS head ON head.trno = ap.trno 
              LEFT JOIN client ON client.clientid = ap.clientid
              LEFT JOIN gldetail AS detail ON detail.trno = ap.trno AND detail.line = ap.line 
              LEFT JOIN cntnum AS num ON head.trno = num.trno
              LEFT JOIN coa AS c ON c.acnoid=detail.acnoid
              WHERE date(head.dateid) between '$start' and '$end' $filter
              UNION ALL
              SELECT num.bref,num.seq,date(detail.postdate) AS dateid, detail.ref AS docno, client.client, client.clientname, 0 AS db, 0 AS cr, detail.rem, 
              head.docno AS ref, date(head.dateid) AS refdate, detail.db AS refdb, detail.cr AS refcr, detail.refx AS trno, detail.linex AS line, detail.trno AS reftrno, c.acnoname
              FROM glhead AS head 
              LEFT JOIN gldetail AS detail ON detail.trno = head.trno 
              LEFT JOIN client ON client.clientid = detail.clientid
              LEFT JOIN cntnum AS num ON detail.refx = num.trno 
              LEFT JOIN coa AS c ON c.acnoid=detail.acnoid
              WHERE detail.refx <> 0 and date(head.dateid) between '$start' and '$end' $filter1

              ORDER BY clientname, docno, trno, line, refdate,reftrno";

    // var_dump($query);



    return $this->coreFunctions->opentable($query);
  }
  public function displayHeader($config)
  {
    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = 10;
    $center     = $config['params']['center'];
    $username   = $config['params']['user'];
    $companyid = $config['params']['companyid'];
    $start     = $config['params']['dataparams']['start'];
    $end     = $config['params']['dataparams']['end'];
    $prefix    = $config['params']['dataparams']['pref'];
    $seqstart    = $config['params']['dataparams']['seqstart'];
    $client = $config['params']['dataparams']['client'];
    $layoutsize = 1200;


    $str = '';


    // $logopath = URL::to($this->companysetup->getlogopath($config['params']) . 'sbclogo1.jpg');
    $path = $this->companysetup->getlogopath($config['params']);
    $path = str_replace('public/', '', $path);
    $logopath = URL::to($path . 'sbclogo1.jpg');

    $str .= "<div style='margin-bottom:30px; text-align:left;margin-left:-110px;margin-top:-20px;'>"; //margin-top:-30px;
    $str .= "<img src='{$logopath}' width='1350' height='300'>";
    $str .= "</div>";

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('PAID ACCOUNTS REPORT', null, null, false, '1px solid ', '', 'C', $font, '18', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Date Range : ' . $start . ' to ' . $end, null, null, false, '1px solid ', '', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Prefix : ' . (!empty($prefix) ? $prefix : 'ALL'), null, null, false, '1px solid ', '', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Docno Series : ' . (!empty($seqstart) ? $seqstart : 'ALL'), null, null, false, '1px solid ', '', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('Client : ' . (!empty($client) ? $client : 'ALL'), null, null, false, '1px solid ', '', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= '<br/><br/>';


    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $str = '';
    $result = $this->reportDefault($config);


    $border = '1px solid';
    $font = 'Century Gothic';
    $font_size = 10;
    $count = 55;
    $page = 55;
    $str = '';
    $border2 = '1px solid';
    $layoutsize = 1200;

    $totalDb = 0;
    $totalCr = 0;
    $totalRefDb = 0;
    $totalRefCr = 0;

    $prevLine = '';
    $prevtrno = '';
    $prevDocno = '';
    $prevClient = '';
    $prevRem = '';

    $balance = 0;
    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }


    $str .= $this->reporter->beginreport($layoutsize);
    $str .= $this->displayHeader($config);

    $str .= $this->reporter->begintable($layoutsize);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('DATE', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('DOCUMENT', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('NAME', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('DEBIT', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('CREDIT', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('REMARKS', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('REFRENCE', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');

    $str .= $this->reporter->col('REF.DATE', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('REF.DB', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('REF.CR', null, null, false, '1px solid ', 'TBL', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->col('BALANCE', null, null, false, '1px solid ', 'TBLR', '', $font, '12', 'B', '', '', '');
    $str .= $this->reporter->endrow();


    foreach ($result as $key => $data) {


      if ($prevClient != '' && ($prevClient != $data->clientname || $prevLine != $data->line || $prevtrno != $data->trno)) {

        $str .= $this->reporter->startrow();
        $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');

        $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');

        $str .= $this->reporter->col('TOTAL', null, 3, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');

        $str .= $this->reporter->col(number_format($totalDb, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');
        $str .= $this->reporter->col(number_format($totalCr, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');

        $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');

        $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');
        $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');

        $str .= $this->reporter->col(number_format($totalRefDb, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');
        $str .= $this->reporter->col(number_format($totalRefCr, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');

        $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1px solid ', 'TBLR', 'R', $font, '10', 'B');
        $str .= $this->reporter->endrow();


        $totalDb = 0;
        $totalCr = 0;
        $totalRefDb = 0;
        $totalRefCr = 0;
        $balance = 0;
      }


      $totalDb += $data->db;
      $totalCr += $data->cr;
      $totalRefDb += $data->refdb;
      $totalRefCr += $data->refcr;

      $balance += (($data->db + $data->cr) - ($data->refdb + $data->refcr));

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(($data->ref ? null : $data->dateid), 80, null, false, '1px solid ', 'TBL', 'C', $font, '10');
      $str .= $this->reporter->col(($data->ref ? null : $data->docno), null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col(($data->ref ? null : $data->clientname), null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col(($data->ref ? null : number_format($data->db, 2)), 80, null, false, '1px solid ', 'TBL', 'R', $font, '10');
      $str .= $this->reporter->col(($data->ref ? null : number_format($data->cr, 2)), 80, null, false, '1px solid ', 'TBL', 'R', $font, '10');
      $str .= $this->reporter->col(($data->ref ? null : $data->acnoname . ' - ' . $data->rem), null, null, false, '1px solid ', 'TBL', '', $font, '10');

      $str .= $this->reporter->col($data->ref, null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col(($data->ref != null ? $data->refdate : null), 80, null, false, '1px solid ', 'TBL', 'C', $font, '10');
      $str .= $this->reporter->col(($data->ref != null ? number_format($data->refdb, 2) : ''), 80, null, false, '1px solid ', 'TBL', 'R', $font, '10');
      $str .= $this->reporter->col(($data->ref != null ? number_format($data->refcr, 2) : ''), 80, null, false, '1px solid ', 'TBL', 'R', $font, '10');

      $str .= $this->reporter->col(number_format($balance, 2), 80, null, false, '1px solid ', 'TBLR', 'R', $font, '10');

      $str .= $this->reporter->endrow();

      $prevClient = $data->clientname;
      $prevLine = $data->line;
      $prevtrno = $data->trno;
    }

    if ($totalDb != 0 || $totalCr != 0 || $totalRefDb != 0 || $totalRefCr != 0 || $balance != 0) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col('TOTAL', null, 3, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');
      $str .= $this->reporter->col(number_format($totalDb, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');
      $str .= $this->reporter->col(number_format($totalCr, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');
      $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col('', null, null, false, '1px solid ', 'TBL', '', $font, '10');
      $str .= $this->reporter->col(number_format($totalRefDb, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');
      $str .= $this->reporter->col(number_format($totalRefCr, 2), null, null, false, '1px solid ', 'TBL', 'R', $font, '10', 'B');
      $str .= $this->reporter->col(number_format($balance, 2), null, null, false, '1px solid ', 'TBLR', 'R', $font, '10', 'B');
      $str .= $this->reporter->endrow();
    }
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}
