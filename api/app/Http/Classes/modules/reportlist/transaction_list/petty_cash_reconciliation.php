<?php

namespace App\Http\Classes\modules\reportlist\transaction_list;

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

class petty_cash_reconciliation
{
  public $modulename = 'Petty Cash Reconciliation';
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
    $fields = ['radioprint'];
    $col1 = $this->fieldClass->create($fields);

    $fields = ['dateid', 'due', 'dacnoname', 'dcentername'];
    $col2 = $this->fieldClass->create($fields);
    data_set($col2, 'dateid.label', 'StartDate');
    data_set($col2, 'dateid.readonly', false);
    data_set($col2, 'due.label', 'EndDate');
    data_set($col2, 'due.readonly', false);
    data_set($col2, 'dacnoname.label', 'Petty Cash Account');
    data_set($col2, 'dacnoname.lookupclass', 'PC');
    data_set($col2, 'dcentername.label', 'Center');
    data_set($col2, 'dcentername.required', true);


    $fields = ['print'];
    $col3 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2, 'col3' => $col3);
  }

  public function paramsdata($config)
  {
    $center = $config['params']['center'];

    return $this->coreFunctions->opentable("select 
      'default' as print,
      adddate(left(now(),10),-360) as dateid,
      left(now(),10) as due,
      '' as contra,
      '' as acnoname,
      '0' as acnoid,
      '' as center,
      '' as centername,
      '$center' as dcentername,
      '' as dacnoname
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

  public function default_query($filters)
  {

    $start = date("Y-m-d", strtotime($filters['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($filters['params']['dataparams']['due']));
    $center = $filters['params']['dataparams']['center'];
    $acno = $filters['params']['dataparams']['contra'];
    $acnoid = $filters['params']['dataparams']['acnoid'];



    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);


    $filter = "";
    if ($acno != "ALL") {
      $filter .= " and coa.acnoid='" . $acnoid . "' ";
    }

    if ($center != "") {
      $filter .= " and cntnum.center='" . $center . "' ";
    }

    $bal = 0;
    $query = "
  select a.dateid,a.center,a.docno,a.client,client.clientname,a.ref,a.checkno,case a.ref when '' 
  then a.rem else concat(a.rem,'(',a.ref,')') end as rem,coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail
  from (
     select head.trno as trno,head.doc as doc,head.docno as docno,head.dateid as dateid,cntnum.center,
  client.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
  coa.acno as acno,coa.acnoname as acnoname,round(detail.db,2) as db,round(detail.cr,2) as cr,
  coa.alias as alias,detail.ref as ref,detail.postdate as postdate,dclient.client as dclient,
  detail.rem as drem,detail.checkno as checkno,coa.acnoid as acnoid,'p' as tr 
  from
  ((((glhead as head left join gldetail as detail on((head.trno = detail.trno))) left join coa
  on((coa.acnoid = detail.acnoid))) left join client on((client.clientid = head.clientid)))
  left join client as dclient on((dclient.clientid = detail.clientid))) left join cntnum on cntnum.trno=head.trno
  where date(head.dateid) between '" . $start . "' and '" . $end . "'  " . $filter . " 
  group by  head.trno,head.doc,head.docno,head.dateid,cntnum.center,
  client.client,head.clientname,head.rem,detail.line,
  coa.acno,coa.acnoname,coa.alias,detail.ref,detail.postdate,dclient.client,
  detail.rem,detail.checkno,coa.acnoid,detail.db,detail.cr) as a
  left join coa on a.acno=coa.acno left join client on client.client = a.client
  union all 
  select a.dateid,a.center,a.docno,a.client,client.clientname,a.ref,a.checkno,case a.ref when '' 
  then a.rem else concat(a.rem,'(',a.ref,')') end as rem,coa.acno,coa.acnoname,a.db,a.cr,0 as begbal,coa.detail
  from 
  (select head.trno as trno,head.doc as doc,head.docno as docno,head.dateid as dateid,cntnum.center,
  head.client as client,head.clientname as clientname,head.rem as rem,detail.line as line,
  coa.acno as acno,coa.acnoname as acnoname,detail.db as db,detail.cr as cr,
  coa.alias as alias,detail.ref as ref,null as postdate,detail.client as dclient,detail.rem as drem,
  detail.checkno as checkno ,coa.acnoid as acnoid,'u' as tr 
  from ((lahead as head left join ladetail as detail on ((head.trno = detail.trno)))
  left join coa on ((coa.acnoid = detail.acnoid)))left join cntnum on cntnum.trno=head.trno 
  where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . "
  group by head.trno ,head.doc,head.docno,head.dateid,cntnum.center,
  head.client,head.clientname,head.rem ,detail.line,
  coa.acno,coa.acnoname ,detail.db,detail.cr,
  coa.alias,detail.ref,detail.client,detail.rem,
  detail.checkno,coa.acnoid) as a
  left join coa on a.acno=coa.acno left join client on client.client = a.client
  order by  acno,dateid,docno";
    $result = $this->coreFunctions->opentable($query);

    return $result;
  }

  public function reportplotting($config)
  {

    $result = $this->default_query($config);
    $reportdata =  $this->PETTY_CASH_RECON_LAYOUT($config, $result);
    return $reportdata;
  }

  private function headerlabel($params)
  {

    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
    $center = $params['params']['dataparams']['center'];
    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $centercode = $params['params']['dataparams']['dcentername'];
    $companyid = $params['params']['companyid'];
    $ccenter = $params['params']['center'];
    $username = $params['params']['user'];

    if ($acnoname == "") {
      $acnoname = "ALL";
    } else {
      $acnoname = $acno . ' - ' . $acnoname;
    }


    if ($center == "") {
      $center = "ALL";
    }

    $str = '';
    if ($companyid == 3) { //conti
      $qry = "select name,address,tel,code from center where code = '" . $ccenter . "'";
      $headerdata = $this->coreFunctions->opentable($qry);
      $current_timestamp = $this->othersClass->getCurrentTimeStamp();

      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();

      $reporttimestamp = $this->reporter->setreporttimestamp($params, $username, $headerdata);

      $str .=  $this->reporter->col($reporttimestamp, '600', null, false, '1px solid ', '', 'L', 'Century Gothic', '13', '', '', '');
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->name), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '14', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();

      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->address), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col(strtoupper($headerdata[0]->tel), null, null, false, '1px solid ', '', 'c', 'Century Gothic', '13', 'B', '', '') . '<br />';
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    } else {
      $str .= $this->reporter->begintable('800');
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->letterhead($ccenter, $username);
      $str .= $this->reporter->endrow();
      $str .= $this->reporter->endtable();
    }
    $str .= '<br/><br/>';

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();

    $str .= $this->reporter->col('PETTY CASH RECONCILIATION', '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '15', 'B', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col(date('M-d-Y', strtotime($start)) . ' TO ' . date('M-d-Y', strtotime($end)), '800', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('', '200', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Center:' . $centercode, '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');
    $str .= $this->reporter->col('Accounts: ' . strtoupper($acnoname), '400', null, false, '1px solid ', '', 'L', 'Century Gothic', '10', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->printline();

    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    //        $txt='',$w=null,$h=null, $bg=false,  $b=false,$b_='', $al='', $f='', $fs='',$fw='',$fc='',$pad='',$m=''
    $str .= $this->reporter->col('', '200', null, false, '1px solid', 'B', 'L', 'Century Gothic', '10', 'B', '', '');

    $str .= $this->reporter->col('Name', '240', null, false, '1px solid', 'B', 'L', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Released', '120', null, false, '1px solid', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Posted', '120', null, false, '1px solid', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->col('Amount', '120', null, false, '1px solid', 'B', 'R', 'Century Gothic', '10', 'B', '', '');
    $str .= $this->reporter->endrow();
    return $str;
  }

  private function default_subtotal($db, $cr, $bal, $params)
  {
    $str = '';
    $fontsize = 9;
    $font = $this->companysetup->getrptfont($params['params']);
    $col3 = array(
      array('60', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('170', null,  false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '', ''),
      array('160', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('75', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
      array('100', null, false, '1px solid', 'T', 'R', $font, $fontsize, 'B', '', '2px', '', ''),
    );
    $value2 = array('', '', '', 'SUB TOTAL : ', number_format($db, 2), number_format($cr, 2), number_format($bal, 2));
    $str .= $this->reporter->row($col3, $value2);
    return $str;
  } // end fn

  private function PETTY_CASH_RECON_LAYOUT($params, $data)
  {
    $start = date("Y-m-d", strtotime($params['params']['dataparams']['dateid']));
    $end = date("Y-m-d", strtotime($params['params']['dataparams']['due']));
    $center = $params['params']['dataparams']['center'];
    $acno = $params['params']['dataparams']['contra'];
    $acnoname = $params['params']['dataparams']['acnoname'];
    $companyid = $params['params']['companyid'];

    $cost = "";
    $filter = "";

    if ($acno == "") {
      $acno = "ALL";
    }

    $cat = $this->coreFunctions->getfieldvalue('coa', 'cat', 'acno=?', [$acno]);


    $filter = "";
    if ($acno != "ALL") {
      $filter .= " and coa.acno='\\" . $acno . "'";
    }

    $filter1 = "";

    if ($center != "") {
      $filter1 .= " and cntnum.center='" . $center . "'";
    }


    $count = 58;
    $page = 48;
    $str = '';

    $fontsize = 10;
    $font = $this->companysetup->getrptfont($params['params']);

    $col = array(
      array('60', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
    );

    $col2 = array(
      array('60', null, false, '1px solid', '', 'C', 'Century Gothic', '9', 'B', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', 'B', '', '1px', '', ''),
      array('170', null,  false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('160', null, false, '1px solid', '', 'L', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('75', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
      array('100', null, false, '1px solid', '', 'R', 'Century Gothic', '9', '', '', '1px', '', ''),
    );

    $str .= ' <br/><br/> ';

    $str .= $this->reporter->beginreport();
    $str .= $this->headerlabel($params);

    $totaldb = 0;
    $totalcr = 0;
    $totalbal = 0;
    $db = 0;
    $cr = 0;
    $bal = 0;
    $acno = '';
    $acno2 = '';

    $posted = 0;
    $unposted = 0;

    $str .= $this->reporter->startrow();

    $str .= $this->reporter->endrow();

    $query = $this->subsidiaryledger_query($start, $end, $filter, $filter1);
    $data1 = $this->coreFunctions->opentable($query);
    $result = json_decode(json_encode($data1), true);

    $query1 = $this->PostedPCV_query($start, $end, $filter);
    $data2 = $this->coreFunctions->opentable($query1);
    $postedpcv = json_decode(json_encode($data2), true);


    $query2 = $this->UnpostedPCV_query($start, $end, $filter);
    $data3 = $this->coreFunctions->opentable($query2);
    $unpostedpcv = json_decode(json_encode($data3), true);

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($result[0]['docno'], '200', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('', '240', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col(number_format($result[0]['amount'], 2), '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->endrow();

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col($result[1]['docno'], '200', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('', '240', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col(number_format($result[1]['amount'], 2), '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->endrow();

    $bal = $result[0]['amount'] + $result[1]['amount'];


    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Posted PCV`s', '200', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '',  '', '4px');
    $str .= $this->reporter->col('', '240', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px');
    $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($postedpcv); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px');
      $str .= $this->reporter->col($postedpcv[$i]['clientname'], '240', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
      $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
      $str .= $this->reporter->col(number_format($postedpcv[$i]['posted'], 2), '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
      $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px');
      $str .= $this->reporter->endrow();
      $posted = $posted + $postedpcv[$i]['posted'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LESS Subtotal: ', '200', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('', '240', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col(number_format($posted, 2), '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->endrow();

    //unposted

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Unposted PCV`s', '200', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('', '240', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '',  '', '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->endrow();

    for ($i = 0; $i < count($unpostedpcv); $i++) {
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col('', '200', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px');
      $str .= $this->reporter->col($unpostedpcv[$i]['clientname'], '240', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
      $str .= $this->reporter->col(number_format($unpostedpcv[$i]['released'], 2), '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
      $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px');
      $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '', '4px');
      $str .= $this->reporter->endrow();
      $unposted = $unposted + $unpostedpcv[$i]['released'];
    }

    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('LESS Subtotal: ', '200', null, false, '1px solid', '', 'L', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->col('', '240', null, false, '1px solid', '', 'L', $font, $fontsize, '', '', '', '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px solid', '', 'R', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col(number_format($unposted, 2), '120', null, false, '1px solid', '', 'R', $font, $fontsize, 'B', '', '', '4px');
    $str .= $this->reporter->endrow();


    $totalbal = $bal - ($posted + $unposted);
    $str .= $this->reporter->begintable('800');
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Balance :', '200', null, false, '1px dotted', '', 'L', $font, $fontsize, 'B', '', '', '', '');
    $str .= $this->reporter->col('', '240', null, false, '1px dotted', '', 'L', $font, $fontsize, '', '', '',  '4px');
    $str .= $this->reporter->col('', '120', null, false, '1px dotted', '', 'R', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col('', '120', null,  false, '1px dotted', '', 'R', $font, $fontsize, '', '', '', '', '');
    $str .= $this->reporter->col(number_format($totalbal, 2), '120', null,  false, '1px dotted', '', 'R', $font, $fontsize, 'B', '', '', '', '');

    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';

    $str .= $this->reporter->endreport();

    return $str;
  }

  private function subsidiaryledger_query($start, $end, $filter, $filter1)
  {

    $query = "select 'Beginning Balance' as docno,'' as clientname, '' as released, '' as posted,
  ifnull(sum(round(db-cr,2)),0) as amount
from (select head.docno as docno,date(head.dateid) as dateid, round(detail.db,2) as db,
        round(detail.cr,2) as cr, coa.acnoid as acnoid, coa.acno,coa.acnoname
from ((((glhead as head
left join gldetail as detail on((head.trno = detail.trno)))
left join coa on((coa.acnoid = detail.acnoid)))
left join client on((client.clientid = head.clientid)))
left join client as dclient on((dclient.clientid = detail.clientid)))
left join cntnum on cntnum.trno=head.trno
where date(head.dateid) < '" . $start . "' " . $filter . " " . $filter1 . "
group by head.docno,head.dateid, coa.acno,coa.acnoname,detail.db,detail.cr,coa.acnoid
union all
select head.docno as docno,date(head.dateid) as dateid, round(detail.db,2) as db,
       round(detail.cr,2) as cr, coa.acnoid as acnoid, coa.acno,coa.acnoname
from ((((lahead as head
left join ladetail as detail on((head.trno = detail.trno)))
left join coa on((coa.acnoid = detail.acnoid)))
left join client on((client.client = head.client)))
left join client as dclient on((dclient.client = detail.client)))
left join cntnum on cntnum.trno=head.trno
where date(head.dateid) < '" . $start . "' " . $filter . " " . $filter1 . "
group by head.docno,head.dateid, coa.acno,coa.acnoname,detail.db,detail.cr,coa.acnoid) as a
left join coa on a.acno=coa.acno
where coa.acno is not null
union all
select 'Petty Cash Adjustments' as docno,'' as clientname,'' as released, '' as posted,
 sum(a.db) as amount
from ( select head.docno as docno,date(head.dateid) as dateid, detail.db as db,
         detail.cr as cr, coa.acnoid as acnoid,coa.acno,coa.acnoname
  from ((lahead as head
  left join ladetail as detail on ((head.trno = detail.trno)))
  left join coa on ((coa.acnoid = detail.acnoid)))
  left join cntnum on cntnum.trno=head.trno
  where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . "
  group by head.docno,head.dateid, coa.acno,coa.acnoname,detail.db,detail.cr , coa.acnoid
  union all
  select head.docno as docno,date(head.dateid) as dateid, round(detail.db,2) as db,
         round(detail.cr,2) as cr, coa.acnoid as acnoid,coa.acno,coa.acnoname
  from ((((glhead as head
  left join gldetail as detail on((head.trno = detail.trno)))
  left join coa on((coa.acnoid = detail.acnoid)))
  left join client on((client.clientid = head.clientid)))
  left join client as dclient on((dclient.clientid = detail.clientid)))
  left join cntnum on cntnum.trno=head.trno
  where date(head.dateid) between '" . $start . "' and '" . $end . "' " . $filter . " " . $filter1 . "
  group by head.docno,head.dateid, coa.acno,coa.acnoname,detail.db,detail.cr, coa.acnoid) as a
left join coa on a.acno=coa.acno
where coa.acno is not null";

    return $query;
  } // end fn


  private function PostedPCV_query($start, $end, $filter)
  {

    $query = "select 'Posted PCV' as docno,'' as released, round(sum(detail.db),2) as posted,
          round(sum(detail.db),2) as amount,head.clientname
   from hsvhead as head
   left join hsvdetail as detail on detail.trno=head.trno
   left join projectmasterfile as proj on proj.line = head.projectid
   left join client as hclient on hclient.client=head.client
   left join client as dclient on dclient.client=detail.client
   left join coa on coa.acno=head.contra
   left join transnum on transnum.trno=head.trno
   where head.doc='SV' and head.cvtrno = 0 and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . " 
   group by clientname";
    return $query;
  } // end fn

  private function UnpostedPCV_query($start, $end, $filter)
  {

    $query = "select 'Unposted PCV' as docno,round(sum(head.amt),2) as released, '' as posted,
          round(sum(head.amt),2) as amount,head.clientname
   from svhead as head
   left join projectmasterfile as proj on proj.line = head.projectid
   left join client as hclient on hclient.client=head.client
   left join coa on coa.acno=head.contra
   left join transnum on transnum.trno=head.trno
   where head.doc='SV' and head.dateid between '" . $start . "' and '" . $end . "' " . $filter . "
   group by clientname";
    return $query;
  } // end fn


}//end class