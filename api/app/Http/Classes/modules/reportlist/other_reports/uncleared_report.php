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

class uncleared_report
{
  public $modulename = 'Uncleared Report';
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
    $this->companysetup = new companysetup;
    $this->coreFunctions = new coreFunctions;
    $this->othersClass = new othersClass;
    $this->fieldClass = new txtfieldClass;
    $this->reporter = new SBCPDF;
  }

  public function createHeadField($config)
  {
    $fields = ['radioprint', 'start', 'end', 'dacnoname'];
    $col1 = $this->fieldClass->create($fields);
    data_set($col1, 'dacnoname.label', 'Account');
    data_set($col1, 'dacnoname.lookupclass', 'CB');

    $fields = ['print'];
    $col2 = $this->fieldClass->create($fields);

    return array('col1' => $col1, 'col2' => $col2);
  }

  public function paramsdata($config)
  {
    // NAME NG INPUT YUNG NAKA ALIAS
    return $this->coreFunctions->opentable("select 
    'default' as print,
     adddate(left(now(),10),-360) as start,   left(now(),10) as end,
    '' as contra,
    '' as acnoname,'' as dacnoname,'0' as acnoid");
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
    return $this->reportDefaultLayout($config);
  }

  public function reportDefault($config)
  {
    // QUERY

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $contra = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];

    $filter   = '';

    if ($contra != '') {
      $filter .= " and acnoid='$acnoid'";
    }

    $query = "select sort,trno,line, docno,status,left(transdate,10) as transdate,date(checkdate) as checkdate,checkno,
    deposit,withrawal,bal,clientname as suppname,rem,
     acno,acnoname,clearday,cname,ccenter,acnoid
      from (
         select 1 as sort,gldetail.trno as trno,gldetail.line as line,glhead.docno as docno,'POSTED' as status,date(glhead.dateid) as transdate,
         date(gldetail.postdate) as checkdate,gldetail.checkno,gldetail.db as deposit,gldetail.cr as withrawal, 0.00 as bal,
         client.client as client,  glhead.clientname as clientname, if(glhead.doc = 'DS', glhead.rem, gldetail.rem) as rem,
         coa.acno as acno,coa.acnoname,coa.acnoid,
         left(ifnull(gldetail.clearday,''),10)  as clearday,
         center.name as cname,cntnum.center as ccenter

         from glhead left join gldetail on gldetail.trno = glhead.trno
         left join coa on coa.acnoid = gldetail.acnoid
         left join client on client.clientid = gldetail.clientid
         left join cntnum on cntnum.trno = glhead.trno
         left join center on center.code = cntnum.center
         where glhead.doc in ('ds','cv','cr','gj','ar','ap')
        union all
         select 3 as sort,ladetail.trno as trno,ladetail.line as line,lahead.docno as docno,'UNPOSTED' as status,lahead.dateid,
         ladetail.postdate,ladetail.checkno,ladetail.db as deposit,ladetail.cr as withrawal,0.00 as bal,
         client.client as client,lahead.clientname as clientname, ladetail.rem ,
         coa.acno as acno,coa.acnoname,coa.acnoid,
         left(ifnull(ladetail.clearday,''),10)  as clearday,
          center.name as cname,cntnum.center as ccenter
         from lahead left join ladetail on ladetail.trno = lahead.trno
         left join coa on coa.acnoid = ladetail.acnoid
         left join client on client.client = ladetail.client
         left join cntnum on cntnum.trno = ladetail.trno
         left join center on center.code = cntnum.center
         where lahead.doc in ('ds','cv','cr','gj','ar','ap')

        union all

         select 2 as sort,0 as trno,0 as line,'Recon' as docno,'Recon' as status,brecon.dateid as transdate,
         brecon.dateid as checkdate,'Recon' as checkno,brecon.bal as deposit,0 as withrawal,0.00 as bal,
         '' as client, '' as clientname,concat('Recon-' , date_format(brecon.dateid,'%b %d %Y')) as rem,
         
          brecon.acno as acno,coa.acnoname,coa.acnoid,
         left(ifnull(brecon.dateid,''),10) as clearday,
         '' as cname,'' as ccenter
         from brecon
         left join coa on coa.acno = brecon.acno where (brecon.line <>2)
         ) as brecon
          where date(checkdate) between '$start' and '$end' and clearday=''  $filter";
    return $this->coreFunctions->opentable($query);
  }

  private function displayHeader($config)
  {

    $contra = $config['params']['dataparams']['contra'];
    $acnoname = $config['params']['dataparams']['acnoname'];

    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';

    $center = $config['params']['center'];
    $username = $config['params']['user'];

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->letterhead($center, $username, $config);
    $str .= $this->reporter->endtable();
    $str .= '<br/><br/>';
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('UNCLEARED REPORT', null, null, false, '10px solid ', '', 'C', $font, '18', 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow(NULL, null, false, $border, '', 'R', $font, $font_size, '', '', '');

    $str .= $this->reporter->col('Account: ', '50', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($contra, '70', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(' Account Name: ', '200', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($acnoname, '390', null, false, $border, '', 'L', $font, $font_size, 'B', '', '');

    $str .= $this->reporter->col('Date: ', '50', null, false, $border, '', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col($start . ' - ' . $end, '240', null, false, $border, '', 'L', $font, $font_size, 'B', '', ''); //150
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('Status', '70', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Transaction Date', '80', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Check Date', '80', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Check #', '70', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Deposit', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Withdrawal', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Balance', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Doc #', '90', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Supp Name', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Notes', '100', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col('Branch', '110', '', false, $border, 'TBLR', 'C', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    // $str .= $this->reporter->endtable();
    return $str;
  }

  public function reportDefaultLayout($config)
  {
    $result = $this->reportDefault($config);
    $start      = date("Y-m-d", strtotime($config['params']['dataparams']['start']));
    $end        = date("Y-m-d", strtotime($config['params']['dataparams']['end']));
    $contra = $config['params']['dataparams']['contra'];
    $acnoid = $config['params']['dataparams']['acnoid'];
    $filter   = '';

    if ($contra != '') {
      $filter .= " and coa.acnoid='$acnoid'";
    }
    $border = '1px solid';
    $font = $this->companysetup->getrptfont($config['params']);
    $font_size = '10';
    $count = 15;
    $page = 15;
    $this->reporter->linecounter = 0;

    if (empty($result)) {
      return $this->othersClass->emptydata($config);
    }

    $str = '';
    $layoutsize = '1000';
    $str .= $this->reporter->beginreport($layoutsize, null, false, false, '', '', '', '', '', '', '', '120px;margin-top:10px;');
    $str .= $this->displayHeader($config);
    $rbal = 0;
    $qry = "select round(ifnull(sum(db)-sum(cr),0),2) as balance from(
          select sort,trno,line,brecon.type,docno,left(dateid,10) as dateid,brecon.acno,
          left(postdate,10) as postdate,db,cr,checkno,rem,acnoname,0.00 as bal,clientname,clearday
          from
          (select 'p' as type,1 as sort,gldetail.trno as trno,gldetail.line as line,gldetail.clearday as clearday,
          glhead.docno as docno,glhead.dateid as dateid,coa.acno as acno,coa.acnoname,gldetail.postdate as postdate,
          gldetail.db as db,gldetail.cr as cr, gldetail.checkno as checkno,gldetail.rem as rem,
          client.client as client,glhead.clientname as clientname
          from (((glhead left join gldetail on((gldetail.trno = glhead.trno)))
          left join coa on((coa.acnoid = gldetail.acnoid)))
          left join client on((client.clientid = gldetail.clientid)))  where (glhead.doc in ('ds','cv','cr','gj','ar','ap') $filter)
          union all
          select 'p' as type,2 as sort,0 as trno,0 as line,brecon.dateid as clearday,'Recon' as docno,
          brecon.dateid as dateid,brecon.acno as acno,coa.acnoname,brecon.dateid as postdate,
          brecon.bal as db,0 as cr,'Recon' as checkno,concat('Recon-' , date_format(brecon.dateid,'%b %d %Y')) as rem,
          '' as client, '' as clientname
          from brecon left join coa on coa.acno = brecon.acno where (brecon.line <> 2) $filter) as brecon
          ) as A where clearday is null and date(postdate) <='$end'";
    $data2 = $this->coreFunctions->opentable($qry);

    foreach ($result as $key => $data) {
      $rbal = $rbal + ($data->deposit - $data->withrawal);
      $str .= $this->reporter->addline();
      $str .= $this->reporter->startrow();
      $str .= $this->reporter->col($data->status, '70', '', false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, 'max-width:70px;overflow-wrap: break-word;');
      $str .= $this->reporter->col($data->transdate, '80', '', false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->checkdate, '80', '', false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, 'max-width:80px;overflow-wrap: break-word;');
      $str .= $this->reporter->col($data->checkno, '70', '', false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, 'max-width:70px;overflow-wrap: break-word;');
      $str .= $this->reporter->col(number_format($data->deposit, 2), '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($data->withrawal, 2), '100', '', false, $border, '', 'R', $font, $font_size, '', '', '');
      $str .= $this->reporter->col(number_format($rbal, 2), '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->docno, '90', '', false, $border, '', 'C', $font, $font_size, '', '', '', '', 0, 'max-width:90px;overflow-wrap: break-word;');
      $str .= $this->reporter->col($data->suppname, '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->rem, '100', '', false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->col($data->cname . ' - ' . $data->ccenter, '110', '', false, $border, '', 'C', $font, $font_size, '', '', '');
      $str .= $this->reporter->endrow();
      if ($this->reporter->linecounter == $page) {
        $str .= $this->reporter->endtable();
        $str .= $this->reporter->page_break();
        $str .= $this->displayHeader($config, $layoutsize);
        $page += $count;
      }
    } //end foreach
    $str .= $this->reporter->endtable();
    $str .= $this->reporter->begintable($layoutsize);
    $str .= $this->reporter->startrow();
    $str .= $this->reporter->col('TOTAL UNCLEARED: ', '800', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->col(number_format($data2[0]->balance, 2), '200', null, false, $border, 'TB', 'R', $font, $font_size, 'B', '', '');
    $str .= $this->reporter->endrow();
    $str .= $this->reporter->endtable();

    $str .= $this->reporter->endreport();
    return $str;
  }
}//end class